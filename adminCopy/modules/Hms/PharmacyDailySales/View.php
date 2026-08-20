<?
class CPL_Admin_Modules_Hms_PharmacyDailySales_View extends CP_Common_Lib_ModuleViewAbstract
{
    var $jssKeys = array('jqUITimePickerAddon-0.9.3');

    /**
     *
     */
    function getList($dataArray){
        $listObj = Zend_Registry::get('listObj');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

        $count   = 0;
        $rows    = '';
        $grandTotal    = 0;

        foreach ($dataArray as $row){
            $date = $fn->getCPDate($row['date'],"d-m-Y");

            $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
            $appendSql = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSql = "AND inv.site_id = {$cpSiteIdSession}";
            }

            $SQLSalesReturn = "
            SELECT 
                SUM(srh.qty_return * srh.price) as sales_return_amount 
            FROM sales_return_history srh
            LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
            LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
            LEFT JOIN (`order` o) ON (o.order_id = srh.order_id)
            WHERE o.order_status != 'Cancelled'
            AND o.order_type = 'POS'
            AND srh.date = '{$row['date']}'
            {$appendSql}
            ";
            $resultSalesReturn = $db->sql_query($SQLSalesReturn);
            $recSalesReturn    = $db->sql_fetchrow($resultSalesReturn);
            $salesReturn       =  $recSalesReturn['sales_return_amount'];

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
            AND invoice_date = '{$row['date']}'
            {$appendSql}
            ";
            $resultCollection = $db->sql_query($SQLCollection);
            $recCollection    = $db->sql_fetchrow($resultCollection);

            $totalCollection = $recCollection['total_amount'];

            if($row['date'] < '2019-04-05'){
                $totalCollection = $row['sales_amount'];
            } else {
                $totalCollection = $totalCollection;            
            }

            $totalAmount = $totalCollection + $row['excess_amount'] - $salesReturn;
            $grandTotal += $totalAmount;
            $totalAmount = number_format($totalAmount, 2);

            $rows .= "
            {$listObj->getListRowHeader($row, $count)}
            {$listObj->getGoToDetailText($count, $date)}
            {$listObj->getListDataCell($totalCollection)}
            {$listObj->getListDataCell($salesReturn)}
            {$listObj->getListDataCell($row['excess_amount'])}
            {$listObj->getListDataCell($totalAmount, 'right')}
            {$listObj->getListRowEnd($row['pharma_daily_sales_id'])}
            ";

            $count++ ;
        }

        $grandTotal = number_format($grandTotal, 2);

        $text = "
        {$this->model->getCreateCurrentDayPharmacyRecord()}
        {$listObj->getListHeader()}
        {$listObj->getListHeaderCell('Date', 'p.date')}
        {$listObj->getListHeaderCell('Sales Amount', 'p.sales_amount')}
        {$listObj->getListHeaderCell('Sales Return', '')}
        {$listObj->getListHeaderCell('Excess Amount', 'p.excess_amount' )}
        {$listObj->getListHeaderCell('Total', '', 'txtRight')}
        {$listObj->getListHeaderEnd()}
        <tr bgcolor='#CFDBE2'>
            <th colspan='4' class='totalFeesInList'></th>
            <th class='totalFeesInList'>Grand Total: {$grandTotal}</th>
            <th colspan='3' class='totalFeesInList'></th>
        </tr>
        {$rows}
        {$listObj->getListFooter()}
        ";

        return $text;
    }

    /**
     *
     */
    function getNew(){
        $formObj = Zend_Registry::get('formObj');

        $fielset1 = "
        {$formObj->getDateRow('Date', 'date')}
        ";

        $text = "
        {$formObj->getFieldSetWrapped('Key Details', $fielset1)}
        ";

        return $text;
    }

    /**
     *
     */
    function getEdit($row){
        $formObj = Zend_Registry::get('formObj');
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $formObj->mode = $tv['action'];
        $expVl     = array('sqlType' => 'OneField');
        $expNoEdit = array('isEditable' => 0);

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
        AND invoice_date = '{$row['date']}'
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
        AND srh.date = '{$row['date']}'
        {$appendSql}
        ";
        $resultSalesReturn = $db->sql_query($SQLSalesReturn);
        $recSalesReturn    = $db->sql_fetchrow($resultSalesReturn);
        $salesReturn       = $recSalesReturn['sales_return_amount'];

        if($row['date'] < '2019-04-05'){
            $totalCollection = $row['sales_amount'];
        } else {
            $totalCollection = $totalCollection;            
        }
        $totalAmount = $totalCollection + $row['excess_amount'] - $salesReturn;
        $totalAmount = number_format($totalAmount, 2);

        $totalAmountTd = "
        <div class='type-text ym-fbox-text row_totalAmount non-editable'>
            <label for='fld_totalAmount'>Total</label>
            <div class='txt' id='fld_totalAmount'>
                <span class='value pharmacyTotalAmount'>{$totalAmount}</span>
            </div>
        </div>";

        $creation_date     = $fn->getCPDate($row['creation_date'], 'd-m-Y H:i:s');
        $modification_date = $fn->getCPDate($row['modification_date'], 'd-m-Y H:i:s');

        $text = "
        <div class='linkPortalWrapper'>
            <div expanded='1' class='header'>
                <div class='floatbox'>
                    <div class='float_left'>Pharmacy Daily Sales Details</div>
                    <div class='toggle'></div>
                    <div class='float_right'>Creation : {$row['created_by']} on {$creation_date} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Modified : {$row['modified_by']} {$modification_date}</div>
                </div>
            </div>
            <div>
                <div class='linkPortalDataWrapper'>
                    <table class='thinlist'>
                        <tbody>
                            <tr>
                                <td width='20%'>{$formObj->getDateRow('Date', 'date', $row['date'])}</td>
                                <td width='20%'>{$formObj->getTBRow('Sales Amount', 'sales_amount', $totalCollection, $expNoEdit)}</td>
                                <td width='20%'>{$formObj->getTBRow('Sales Return', 'sales_return', $salesReturn, $expNoEdit)}</td>
                                <td width='20%'>{$formObj->getTBRow('Excess Amount', 'excess_amount', $row['excess_amount'])}</td>
                                <td width='20%'>{$totalAmountTd}</td>
                            </tr>
                            <tr>
                                <td width='20%' colspan='2'>{$formObj->getTARow('Notes', 'notes', $row['notes'])}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        ";

        return $text;
    }

    /**
     *
     */
    function getPrintDetail($row){
        $db = Zend_Registry::get('db');
        return $this->getDetail($row);
    }

    /**
     *
     */
    function getSearch(){
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');

        $expVl = array('sqlType' => 'OneField');

        $spArray = array(
            "Flagged"
           ,"Not-Flagged"
        );

        $fielset = "
        {$formObj->getDDRowByArr('Special Search', 'special_search', $spArray)}
        ";

        $text = "
        {$formObj->getFieldSetWrapped('Company Details', $fielset)}
        ";

        return $text;
    }

    /**
     *
     */
    function getRightPanel($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $displayLinkData = Zend_Registry::get('displayLinkData');
        $media = Zend_Registry::get('media');


        $record_id = $fn->getIssetParam($row, 'pharma_daily_sales_id');
     
        $text = "

         <div id='PharmacyDailySalesHistoryLinkPortal'>{$this->getAddPharmacyDailySalesHistory($record_id)}</div>
        {$media->getRightPanelMediaDisplay('Attachments', 'hms_pharmacyDailySales', 'attachment', $row)}
        ";

        return $text;
    }
     
     /**
     *
     */
    function getAddPharmacyDailySalesHistory($pharma_daily_sales_id=''){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');
        
        if($pharma_daily_sales_id == ''){
            $pharma_daily_sales_id = $fn->getReqParam('pharma_daily_sales_id');
        }

        $PharmacyDailySalesHistory = $this->getAddPharmacyDailySalesHistoryDetail($pharma_daily_sales_id);

        $recCount = $fn->getRecordCount('pharmacy_daily_sales_history', "pharma_daily_sales_id = '{$pharma_daily_sales_id}'");

        $SQL="
        SELECT SUM(excess_amount) AS excess_amount
        FROM pharmacy_daily_sales_history
        WHERE pharma_daily_sales_id = '{$pharma_daily_sales_id}'
        ";
        $result   = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);

        $header ="
        <thead>
            <tr>
                <th>Time In (H:M:S)</th>
                <th>Time Out (H:M:S)</th>
                <th>Bill No</th>
                <th>Amount</th>
                <th>Excess Amount ({$row['excess_amount']})</th>
                <th class='portalActBtns'></th>            
            </tr>
        </thead>
        ";

        if($recCount == 0){
            $header = "<tr><td align='center'>No Records Linked<br/><br/></td></tr>";
        }

        $formActionPharmacyDailySalesHistory = "index.php?module=hms_pharmacyDailySales&_spAction=PharmacyDailySalesHistory&pharma_daily_sales_id={$pharma_daily_sales_id}&showHTML=0";

        $add = "<div class='actBtns'>
                    <a id='AddPharmacyDailySalesHistory' href='{$formActionPharmacyDailySalesHistory}' pharma_daily_sales_id='{$pharma_daily_sales_id}'>
                        Add
                    </a>
                </div>
                ";

        $text = "
        <div class='linkPortalWrapper hms_pharmacy_daily_sales_historyLink'>
            <div class='header' expanded='1'>
                <div class='floatbox'>
                    <div class='float_left'>Pharmacy Daily Sales History</div>
                    <div class='txtRight'>
                        <span class='count'>({$recCount})</span>
                    </div>
                </div>
            </div>
            <div class='linkPortalDataWrapper'>
                <form>
                    <table class='renewallist'>
                        {$header}
                        <tbody id='AddPharmacyDailySalesHistoryPortal'>
                            {$PharmacyDailySalesHistory}
                        </tbody>
                    </table>
                    <input type='hidden' name='pharma_daily_sales_id' value='{$pharma_daily_sales_id}' />
                </form>
            </div>
            {$add}
        </div>
        ";

        return $text;

    }
       
    /**
     *
     */
    function getAddPharmacyDailySalesHistoryDetail($pharma_daily_sales_id = ''){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        if($pharma_daily_sales_id == ''){
            $pharma_daily_sales_id = $fn->getReqParam('pharma_daily_sales_id');
        }

        $pharmacy_daily_sales_history_id = $fn->getReqParam('pharmacy_daily_sales_history_id');

        $rows  = "";

        $SQL="
        SELECT pdh.*
        FROM pharmacy_daily_sales_history pdh
        WHERE pharma_daily_sales_id = '{$pharma_daily_sales_id}'
        ORDER BY pharmacy_daily_sales_history_id
        ";
        $result   = $db->sql_query($SQL);
        $numRows = $db->sql_numrows($result);
        
        $count = 1;
        while ($row = $db->sql_fetchrow($result)) {
            $formActionEditPharmacyDailySalesHistory = "index.php?module=hms_pharmacyDailySales&_spAction=EditPharmacyDailySalesHistory&pharmacy_daily_sales_history_id={$row['pharmacy_daily_sales_history_id']}&pharma_daily_sales_id={$pharma_daily_sales_id}&showHTML=0";

            $editIcon ="
            <div class='float_right'>
                <a class='EditPharmacyDailySalesHistory' href='{$formActionEditPharmacyDailySalesHistory}' pharmacy_daily_sales_history_id='{$row['pharmacy_daily_sales_history_id']}'  pharma_daily_sales_id='{$pharma_daily_sales_id}'>
                    <img src='/cmspilotv30/CP/admin/images/icons/btn_edit.png'>
                </a>
            </div>
            ";

            $deleteIcon ="
            <div class='float_right'>
                <a class='deletePharmacyDailySalesHistory' href='#'  pharmacy_daily_sales_history_id='{$row['pharmacy_daily_sales_history_id']}' pharma_daily_sales_id='{$pharma_daily_sales_id}'>
                    <img src='/cmspilotv30/CP/admin/images/icons/btn_remove.png'>
                </a>
            </div>
            ";

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
            AND (DATE_FORMAT(creation_date, '%H:%i:%s') >= '{$row['time_in']}'
            AND DATE_FORMAT(creation_date, '%H:%i:%s') <= '{$row['time_out']}')
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
            AND (DATE_FORMAT(srh.creation_date, '%H:%i:%s') >= '{$row['time_in']}'
            AND DATE_FORMAT(srh.creation_date, '%H:%i:%s') <= '{$row['time_out']}')
            {$appendSql}
            ";
            $resultSalesReturn = $db->sql_query($SQLSalesReturn);
            $recSalesReturn    = $db->sql_fetchrow($resultSalesReturn);
            $salesReturn       = $recSalesReturn['sales_return_amount'];

            $totalAmount = $totalCollection - $salesReturn;
            $totalAmount = number_format($totalAmount, 2);
            
            $rows .= "
                <tr>
                    <td>{$row['time_in']}</td>
                    <td>{$row['time_out']}</td>
                    <td>{$recCollection['start_bill_no']} - {$recCollection['end_bill_no']} ({$totalAmount})</td>                    
                    <td>{$row['amount']}</td>
                    <td>{$row['excess_amount']}</td>                    
                    <td>
                        {$deleteIcon}
                        {$editIcon}
                    </td>                    
                </tr>
            ";
            $count++;
        }

        $text = "{$rows}";

        return $text;
    }
    /**
     *
     */
    function getPharmacyDailySalesHistory() {
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpUtil = Zend_Registry::get('cpUtil');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $expVl = array('sqlType' => 'OneField');
        $expNoEdit  = array('isEditable' => 0);
        $tabindex  = array('tabindex' => 6);

        $pharma_daily_sales_id  = $fn->getReqParam('pharma_daily_sales_id');        
        
        $SQL="
        SELECT time_out
        FROM pharmacy_daily_sales_history
        WHERE pharma_daily_sales_id = '{$pharma_daily_sales_id}'
        ORDER BY pharmacy_daily_sales_history_id DESC
        LIMIT 1
        ";
        $result   = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);
        $numRows = $db->sql_numrows($result);

        $endTime = "";
        if($row['time_out'] != "") {
            $time    = strtotime($row['time_out']);
            $endTime = date("H:i:s", strtotime('+1 seconds', $time)); 
        } 

        if($cpSiteIdSession == 2) {
            if($numRows == 0) {
                $endTime = '17:00';
            }
        }

        $formAction = "index.php?_topRm=main&module=hms_pharmacyDailySales&_spAction=PharmacyDailySalesHistoryFormSubmit&showHTML=0";

        $text = "
        <form id='pharmacyDailySalesHistoryPortalForm' class='yform columnar' method='post' action='{$formAction}'>
            <input type='hidden' name='pharma_daily_sales_id' value='{$pharma_daily_sales_id}' />
            {$formObj->getTimeRow('Time In (H:M:S)', 'time_in', $endTime)}
            {$formObj->getTimeRow('Time Out (H:M:S)', 'time_out', '')}
            {$formObj->getTBRow('Bill No', 'bill_no', '', $expNoEdit)}
            {$formObj->getTBRow('Amount', 'amount', '')}
            {$formObj->getTBRow('Excess Amount', 'excess_amount', '')}
            <a class='btn btn-primary updateBillNos' pharma_daily_sales_id='{$pharma_daily_sales_id}'>Update</a>
        </form>
        ";
        return $text;
    }    
     /**
     *
     */
    function getEditPharmacyDailySalesHistory() {
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpUtil = Zend_Registry::get('cpUtil');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');
        $formObj = Zend_Registry::get('formObj');        
        $expNoEdit  = array('isEditable' => 0);
        
        $pharmacy_daily_sales_history_id  = $fn->getReqParam('pharmacy_daily_sales_history_id');
        $historyRec = $fn->getRecordRowByID('pharmacy_daily_sales_history', 'pharmacy_daily_sales_history_id', $pharmacy_daily_sales_history_id);           

        $formAction = "index.php?module=hms_pharmacyDailySales&_spAction=EditPharmacyDailySalesHistoryFormSubmit&showHTML=0&pharmacy_daily_sales_history_id={$pharmacy_daily_sales_history_id}";

        $recordRow  = $fn->getRecordRowByID('pharma_daily_sales', 'pharma_daily_sales_id', $historyRec['pharma_daily_sales_id']);

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
        AND (DATE_FORMAT(creation_date, '%H:%i:%s') >= '{$historyRec['time_in']}'
        AND DATE_FORMAT(creation_date, '%H:%i:%s') <= '{$historyRec['time_out']}')
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
        AND (DATE_FORMAT(srh.creation_date, '%H:%i:%s') >= '{$historyRec['time_in']}'
        AND DATE_FORMAT(srh.creation_date, '%H:%i:%s') <= '{$historyRec['time_out']}')
        {$appendSql}
        ";
        $resultSalesReturn = $db->sql_query($SQLSalesReturn);
        $recSalesReturn    = $db->sql_fetchrow($resultSalesReturn);
        $salesReturn       = $recSalesReturn['sales_return_amount'];

        $totalAmount = $totalCollection - $salesReturn;
        $totalAmount = number_format($totalAmount, 2);

        $billNo = "{$recCollection['start_bill_no']} - {$recCollection['end_bill_no']} ({$totalAmount})";
        
        $text = "
        <form id='pharmacyDailySalesHistoryPortalForm' class='yform columnar' method='post' action='{$formAction}'>
            {$formObj->getTIMERow('Time In' , 'time_in', $historyRec['time_in'])}
            {$formObj->getTIMERow('Time Out' , 'time_out', $historyRec['time_out'])}
            {$formObj->getTBRow('Bill No', 'bill_no', $billNo, $expNoEdit)}
            {$formObj->getTBRow('Amount' , 'amount', $historyRec['amount'])}
            {$formObj->getTBRow('Excess Amount' , 'excess_amount', $historyRec['excess_amount'])}
            <a class='btn btn-primary updateBillNos' pharma_daily_sales_id='{$historyRec['pharma_daily_sales_id']}'>Update</a>
            <input type='hidden' name='pharmacy_daily_sales_history_id' value='{$pharmacy_daily_sales_history_id}' />
        </form>
        ";               

        return $text;
    }

    /**
     *
     */
    function getQuickSearch() {
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpUtil = Zend_Registry::get('cpUtil');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');

        $currentMonth    = $fn->getReqParam('currentMonth');

        $spArray = array(
            "Flagged"
           ,"Not-Flagged"
        );
        $currMonthArray = array(
             "All Records"
            ,"CurrentMonth"
        );

        if($currentMonth == ""){
            $currentMonth = 'CurrentMonth';
        }

        $text = "
        <td>
            <select name='currentMonth'>
                {$cpUtil->getDropDown1($currMonthArray, $currentMonth)}
           </select>
        </td>
        <td>
            <select name='special_search'>
                <option value=''>Special Search</option
                {$cpUtil->getDropDown1($spArray, $tv['special_search'])}
           </select>
        </td>
        ";

        return $text;
    }
}
