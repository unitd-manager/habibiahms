<?
class CPL_Admin_Modules_Hms_PharmacyDailySales_Model extends CP_Common_Lib_ModuleModelAbstract
{
    /**
     *
     */
    function getSQL() {

        $SQL = "
        SELECT p.*
        FROM pharma_daily_sales p
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar($linkRecType = '') {
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $searchVar = Zend_Registry::get('searchVar');
        $searchVar->mainTableAlias = 'p';

        $pharma_daily_sales_id   = $fn->getReqParam('pharma_daily_sales_id');
        $currentMonth = $fn->getReqParam('currentMonth');
        $month        = date('m');
        $year         = date('Y');

        if ($pharma_daily_sales_id != "") {
            $searchVar->sqlSearchVar[] = "p.pharma_daily_sales_id = '{$pharma_daily_sales_id}'";
        } else if ($tv['record_id'] != '') {
            $searchVar->sqlSearchVar[] = "p.pharma_daily_sales_id = '{$tv['record_id']}'";
        } else {
            $fn->setSearchVarForLinkData($searchVar, $linkRecType, 'p.pharma_daily_sales_id');

            if ($currentMonth == "" || $currentMonth == "CurrentMonth") {
                $searchVar->sqlSearchVar[] = "DATE_FORMAT(p.date, '%Y-%m') = '{$year}-{$month}'" ;
            }

            if ($tv['keyword'] != "") {
                $searchVar->sqlSearchVar[] = "(
                )";
            }

            //------------------------------------------------------------------------//
            if ($tv['special_search'] == "Flagged") {
                $searchVar->sqlSearchVar[] = "p.flag = 1";
            }

            if ($tv['special_search'] == "Not-Flagged") {
                $searchVar->sqlSearchVar[] = "(p.flag != 1 OR p.flag IS null)";
            }

            $searchVar->sortOrder = "p.date DESC";
        }
    }

    /**
     *
     */
    function getNewValidate() {
        $validate = Zend_Registry::get('validate');

        $validate->resetErrorArray();
        $validate->validateData('date', 'Please enter the Date');

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getAdd(){
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');

        if (!$this->getNewValidate()){
            return $validate->getErrorMessageXML();
        }

        $fa = $this->getFields();
        $id = $fn->addRecord($fa);
        $fn->returnAfterNewSave($id);
    }

    /**
     *
     */
    function getEditValidate() {
        $validate = Zend_Registry::get('validate');

        $validate->resetErrorArray();

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getSave(){
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $cpCfg = Zend_Registry::get('cpCfg');

        if (!$this->getEditValidate()){
            return $validate->getErrorMessageXML();
        }

        $fa = $this->getFields();
        $id = $fn->saveRecord($fa);
        $fn->returnAfterNewSave($id, $cpCfg['cp.pagetoReturnAfterSave']);
    }

    /**
     *
     */
    function getSaveList(){
        $fn = Zend_Registry::get('fn');
        $fn->getSaveList();
    }

    /**
     *
     */
    function getFields(){
        $fn = Zend_Registry::get('fn');

        $fa = array();
        $fa = $fn->addToFieldsArray($fa, 'date');
        $fa = $fn->addToFieldsArray($fa, 'sales_amount');
        $fa = $fn->addToFieldsArray($fa, 'excess_amount');
        $fa = $fn->addToFieldsArray($fa, 'site_id');
        $fa = $fn->addToFieldsArray($fa, 'notes');
        $fa = $fn->addToFieldsArray($fa, 'created_by');
        $fa = $fn->addToFieldsArray($fa, 'creation_date');
        $fa = $fn->addToFieldsArray($fa, 'modified_by');
        $fa = $fn->addToFieldsArray($fa, 'modification_date');

        return $fa;
    }
    
    /**
     *
     */
    function getCreateCurrentDayPharmacyRecord() {
        $fn     = Zend_Registry::get('fn');
        $db     = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        //http://habibiahms.localhost/admin/index.php?_topRm=finance&module=hms_pharmacyDailySales&_spAction=createCurrentDayPharmacyRecord&showHTML=0

        $currentDate = date("Y-m-d");
    //$currentDate = '2025-08-02';

        $SQLPharma = "
        SELECT p.pharma_daily_sales_id
        FROM pharma_daily_sales p
        WHERE p.date = '{$currentDate}'
        ";
        $resultPharma  = $db->sql_query($SQLPharma);
        $numRowsPharma = $db->sql_numrows($resultPharma);

        if($numRowsPharma == 0) {
            $fa = array();
            $fa['date']          = $currentDate;
            $fa['created_by']    = 'Super Admin';
            $fa['creation_date'] = date('Y-m-d H:i:s');
            $fa['site_id']    = '1';

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'pharma_daily_sales');
            $result = $db->sql_query($SQL);

            $fa1 = array();
            $fa1['date']          = $currentDate;
            $fa1['created_by']    = 'Super Admin';
            $fa1['creation_date'] = date('Y-m-d H:i:s');
            $fa1['site_id']    = '2';

            $SQL1 = $dbUtil->getInsertSQLStringFromArray($fa1, 'pharma_daily_sales');
            $result1 = $db->sql_query($SQL1);
        }
    }

     /**
     *
     */
    function getPharmacyDailySalesHistoryFormSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        if (!$this->getPharmacyDailySalesHistoryValidate()){
            return $validate->getErrorMessageXML();
        }

        $pharma_daily_sales_id         = $fn->getReqParam('pharma_daily_sales_id');
        $time_in                       = $fn->getPostParam('time_in');
        $time_out                      = $fn->getPostParam('time_out');
        $amount                        = $fn->getPostParam('amount');
        $excess_amount                 = $fn->getPostParam('excess_amount');
       
        $fa = array();        
        $fa['time_in']                = $time_in;
        $fa['time_out']               = $time_out;
        $fa['amount']                 = $amount;
        $fa['excess_amount']          = $excess_amount;
        $fa['pharma_daily_sales_id']  = $pharma_daily_sales_id;

        $insert = $dbUtil->getInsertSQLStringFromArray($fa, 'pharmacy_daily_sales_history');
        $result = $db->sql_query($insert);

        $fa = array();  

        $time    = strtotime($time_out);
        $endTime = date("H:i:s", strtotime('+1 seconds', $time));      
        $fa['time_in']     = $endTime;
        $fa['pharma_daily_sales_id']  = $pharma_daily_sales_id;

        $insert = $dbUtil->getInsertSQLStringFromArray($fa, 'pharmacy_daily_sales_history');
        $result = $db->sql_query($insert);

        return $validate->getSuccessMessageXML($excess_amount);
    }
    /**
     *
     */
    function getEditPharmacyDailySalesHistoryFormSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        if (!$this->getPharmacyDailySalesHistoryValidate()){
            return $validate->getErrorMessageXML();
        }
        
        $pharmacy_daily_sales_history_id = $fn->getReqParam('pharmacy_daily_sales_history_id');
        $time_in                         = $fn->getPostParam('time_in');
        $time_out                        = $fn->getPostParam('time_out');
        $amount                          = $fn->getPostParam('amount');
        $excess_amount                   = $fn->getPostParam('excess_amount');

        $pdshRec = $fn->getRecordRowByID('pharmacy_daily_sales_history', 'pharmacy_daily_sales_history_id', $pharmacy_daily_sales_history_id);
        $excess_amount_val = 0;
        if ($excess_amount != $pdshRec['excess_amount']){
            $excess_amount_val = $excess_amount - $pdshRec['excess_amount'];
        }

        $fa1 = array();
        $fa1['time_in']                        = $time_in;
        $fa1['time_out']                       = $time_out;
        $fa1['amount']                         = $amount;
        $fa1['excess_amount']                  = $excess_amount;
        $fa1['pharmacy_daily_sales_history_id']= $pharmacy_daily_sales_history_id;
       
        $whereCondition = "WHERE pharmacy_daily_sales_history_id = {$pharmacy_daily_sales_history_id}" ;
        $sql  = $dbUtil->getUpdateSQLStringFromArray($fa1, "pharmacy_daily_sales_history", $whereCondition);
        $result   = $db->sql_query($sql);

        return $validate->getSuccessMessageXML($excess_amount_val);
    }

    /**
     *
     */
    function getPharmacyDailySalesHistoryValidate() {
        $validate = Zend_Registry::get('validate');

        $validate->resetErrorArray();
        $validate->validateData('amount', 'Please enter amount');

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    } 
    /**
     *
     */
    function getTotalPharmacySalesAmount($date, $current_month, $current_year) {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $appendSqlCol     = "";
        $appendSqlSaleRet = "";
        $appendSqlPharma  = "";

        if ($current_month != "") {
            $search_from = date($current_year.'-'.$current_month.'-01');
            $search_to   = date("Y-m-t", strtotime($search_from));
        } else {
            $search_from = date('Y-m-01');
            $search_to   = date('Y-m-t');
        }

        if($date != "") {
            $appendSqlCol     = "AND invoice_date = '{$date}'";
            $appendSqlSaleRet = "AND srh.date = '{$date}'";
            $appendSqlPharma  = "WHERE p.date = '{$date}'";
        }
        else if($date == "") {
            $appendSqlCol     = "AND (invoice_date BETWEEN '{$search_from}' AND '{$search_to}')";
            $appendSqlSaleRet = "AND (srh.date BETWEEN '{$search_from}' AND '{$search_to}')";
            $appendSqlPharma  = "WHERE (p.date BETWEEN '{$search_from}' AND '{$search_to}')";
        }

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $appendSql = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "AND site_id = {$cpSiteIdSession}";
        }

        $SQLCollection = "
        SELECT SUM(invoice_amount) AS total_amount
        FROM `invoice`
        WHERE status != 'Cancelled'
        AND invoice_type = 'POS'
        {$appendSqlCol}
        {$appendSql}
        ";
        $resultCollection = $db->sql_query($SQLCollection);
        $recCollection    = $db->sql_fetchrow($resultCollection);

        $totalCollection = $recCollection['total_amount'];

        $SQLSalesReturn = "
        SELECT 
            SUM(srh.qty_return * srh.price) as sales_return_amount 
        FROM sales_return_history srh
        LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
        LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
        LEFT JOIN (`order` o) ON (o.order_id = srh.order_id)
        WHERE o.order_status != 'Cancelled'
        AND o.order_type = 'POS'
        {$appendSqlSaleRet}
        ";
        $resultSalesReturn = $db->sql_query($SQLSalesReturn);
        $recSalesReturn    = $db->sql_fetchrow($resultSalesReturn);

        $salesReturn =  $recSalesReturn['sales_return_amount'];

        $SQLPharma = "
        SELECT SUM(p.excess_amount) AS excess_amount
        FROM pharma_daily_sales p
        {$appendSqlPharma}
        ";
        $resultPharma = $db->sql_query($SQLPharma);
        $recPharma    = $db->sql_fetchrow($resultPharma);

        $totalAmount = $totalCollection + $recPharma['excess_amount'] - $salesReturn;

        $Values = array('salesReturn'     => $salesReturn
                       ,'totalCollection' => $totalCollection
                       ,'totalAmount'     => $totalAmount);

        return $Values;
    }

    /**
     *
     */
    function getUpdateBillNos() {
        $fn     = Zend_Registry::get('fn');
        $db     = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        
        $pharma_daily_sales_id = $fn->getReqParam('pharma_daily_sales_id');
        $time_in = $fn->getReqParam('time_in');
        $time_out = $fn->getReqParam('time_out');
        $amount = $fn->getReqParam('amount');

        $recordRow  = $fn->getRecordRowByID('pharma_daily_sales', 'pharma_daily_sales_id', $pharma_daily_sales_id);

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $appendSql = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "AND site_id = {$cpSiteIdSession}";
        }

        $SQLCollection = "
        SELECT SUM(invoice_amount) AS total_amount
             , MIN(order_id) AS start_bill_no
             , MAX(order_id) AS end_bill_no
        FROM `invoice`
        WHERE status != 'Cancelled'
        AND invoice_type = 'POS'
        AND invoice_date = '{$recordRow['date']}'
        AND (DATE_FORMAT(creation_date, '%H:%i:%s') >= '{$time_in}'
        AND DATE_FORMAT(creation_date, '%H:%i:%s') <= '{$time_out}')
        {$appendSql}
        ";
        $resultCollection = $db->sql_query($SQLCollection);
        $recCollection    = $db->sql_fetchrow($resultCollection);

        $totalCollection = $recCollection['total_amount'];

        $appendSql = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "AND inv.site_id = {$cpSiteIdSession}";
        }

        $SQLSalesReturn = "
        SELECT SUM(srh.qty_return * srh.price) as sales_return_amount 
        FROM sales_return_history srh
        LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
        LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled')
        LEFT JOIN (`order` o) ON (o.order_id = srh.order_id)
        WHERE o.order_status != 'Cancelled'
        AND o.order_type = 'POS'
        AND srh.date = '{$recordRow['date']}'
        AND (DATE_FORMAT(srh.creation_date, '%H:%i:%s') >= '{$time_in}'
        AND DATE_FORMAT(srh.creation_date, '%H:%i:%s') <= '{$time_out}')
        {$appendSql}
        ";
        $resultSalesReturn = $db->sql_query($SQLSalesReturn);
        $recSalesReturn    = $db->sql_fetchrow($resultSalesReturn);
        $salesReturn       = $recSalesReturn['sales_return_amount'];

        $totalAmount = $totalCollection - $salesReturn;
        if($amount == '' || $totalAmount == ''){
            $excess_amount = 0;
        } else {
            $excess_amount = $amount - $totalAmount;
        }
        $totalAmount = number_format($totalAmount, 2);

        $billNo = "{$recCollection['start_bill_no']} - {$recCollection['end_bill_no']} ({$totalAmount})";


        return $billNo.'_'.$excess_amount;
    }

    /**
     *
     */
    function getDeletePharmacyDailySalesHistory(){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cpUtil = Zend_Registry::get('cpUtil');

        $pharmacy_daily_sales_history_id = $fn->getReqParam('pharmacy_daily_sales_history_id');

        $SQL ="
        DELETE FROM pharmacy_daily_sales_history
        WHERE pharmacy_daily_sales_history_id = {$pharmacy_daily_sales_history_id}
        ";
        $result = $db->sql_query($SQL);
    }

    /**
     *
     */
    function getUpdateExcessAmount() {
        $fn     = Zend_Registry::get('fn');
        $db     = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        
        $pharma_daily_sales_id = $fn->getReqParam('pharma_daily_sales_id');
        $excess_amount = $fn->getReqParam('excess_amount');

        $recordRow  = $fn->getRecordRowByID('pharma_daily_sales', 'pharma_daily_sales_id', $pharma_daily_sales_id);

        $excessAmount = $recordRow['excess_amount'] + $excess_amount;

        $sqlUpdate = "
        UPDATE `pharma_daily_sales` SET excess_amount = '{$excessAmount}'
        WHERE pharma_daily_sales_id = '{$pharma_daily_sales_id}'
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);

        return $excessAmount;
    }
}
