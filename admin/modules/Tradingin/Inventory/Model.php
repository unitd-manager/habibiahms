<?
class CPL_Admin_Modules_Tradingin_Inventory_Model extends CP_Admin_Modules_Tradingin_Inventory_Model
{
    /**
     *
     */
    function getSQL() {
        $cpCfg  = Zend_Registry::get('cpCfg');
        $fn     = Zend_Registry::get('fn');
        $db     = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');

        $appendSqlStk   = "";
        $siteIdForField = "";
        $current_date   = $cpUtil->getISODateStr();

        if($cpCfg['cp.hasMultiUniqueSites']  == true){
            $siteId         = $fn->getSessionParam('cp_site_id');
            $siteIdForField = $siteId;
            $appendSqlStk   = "AND ibs.site_id = '{$siteId}'";
            $leftJoinMedicineSite = "LEFT JOIN (medicine_site ms) ON (i.product_id = ms.product_id)";
        }

        $currentDate = date("Y-m-d");

        $SQL = "
        SELECT i.*
              ,p.product_id AS productId
              ,p.title AS product_name
              ,p.item_code
              ,p.unit
              ,p.product_code
              ,p.category_id
              ,p.not_add_in_stock
              ,p.exclude_stock_difference
              ,ms.rake
              ,i.actual_stock{$siteIdForField} AS stock
             
        FROM inventory i
        LEFT JOIN (product p) ON (p.product_id = i.product_id)
      
        LEFT JOIN (medicine_site ms) ON (i.product_id = ms.product_id AND ms.site_id = {$siteId})
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar($linkRecType = '') {
        $tv        = Zend_Registry::get('tv');
        $fn        = Zend_Registry::get('fn');
        $cpCfg     = Zend_Registry::get('cpCfg');
        $searchVar = Zend_Registry::get('searchVar');
        $cpUtil    = Zend_Registry::get('cpUtil');

        $current_date = $cpUtil->getISODateStr();
        $searchVar->mainTableAlias = 'i';
        
        $cpSiteIdSession     = $fn->getSessionParam('cp_site_id');
        $inventory_id        = $fn->getReqParam('inventory_id');
        $supplier_id         = $fn->getReqParam('supplier_id');
        $category_id         = $fn->getReqParam('category_id');
        $employee_id         = $fn->getReqParam('employee_id');
        $minimum_order_level = $fn->getReqParam('minimum_order_level');
        $expiry_date         = $fn->getReqParam('expiry_date');
        $product_type         = $fn->getReqParam('product_type');

        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $searchVar->sqlSearchVar[] = "ms.site_id = {$cpSiteIdSession}";
        }

        if ($inventory_id != "") {
            $searchVar->sqlSearchVar[] = "i.inventory_id = '{$inventory_id}'";
        } else if ($tv['record_id'] != '') {
            $searchVar->sqlSearchVar[] = "i.product_id = '{$tv['record_id']}'";
        } else {
            $fn->setSearchVarForLinkData($searchVar, $linkRecType, 'i.inventory_id');

            if ($tv['keyword'] != "") {
                $searchVar->sqlSearchVar[] = "(
                    p.title LIKE '{$tv['keyword']}%'
                    || p.item_code LIKE '{$tv['keyword']}%'
                    || i.inventory_code LIKE '%{$tv['keyword']}%'
                )";
            } 

            /*else if ($supplier_id != '' ) {
            } else {
                $searchVar->sqlSearchVar[] = "i.actual_stock > 0";
            }*/

            //------------------------------------------------------------------------//
            if ($tv['special_search'] == "Stock Difference") {
                //$searchVar->sqlHavingVar[] = "stock_actual != manual_stock";
                $searchVar->sqlSearchVar[] = "i.product_id IN (
                    SELECT DISTINCT ms.product_id 
                    FROM manual_stock ms 
                    WHERE ms.product_id = i.product_id 
                      AND ms.site_id    = {$cpSiteIdSession}
                      AND ms.product_id NOT IN (332, 338, 785, 861, 1312)
                      AND ms.date       = '{$current_date}'
                      AND ms.stock     != ms.stock_difference
                    ORDER BY ms.manual_stock_id DESC
                  )
                ";
                $searchVar->sqlSearchVar[] = "(p.exclude_stock_difference = ''
                                            OR p.exclude_stock_difference IS NULL
                                            OR p.exclude_stock_difference = 0)";
            }

            if ($tv['special_search'] == "Sales Wise SD") {
                $start_date = date('Y-m-d', strtotime('-3 days'));
                $end_date = date('Y-m-d');
                $searchVar->sqlSearchVar[] = "i.product_id IN (
                    SELECT DISTINCT ii.record_id 
                    FROM invoice_item ii
                    LEFT JOIN (`invoice` inv) ON (inv.invoice_id = ii.invoice_id)
                    WHERE ii.record_id = i.product_id 
                      AND inv.site_id    = {$cpSiteIdSession}
                      AND inv.status != 'Cancelled'
                      AND (inv.invoice_date >= '{$start_date}' AND inv.invoice_date <= '{$end_date}')
                  )
                ";
                $searchVar->sqlSearchVar[] = "i.product_id NOT IN (
                    SELECT DISTINCT ms.product_id 
                    FROM manual_stock ms 
                    WHERE ms.product_id = i.product_id
                      AND ms.site_id    = {$cpSiteIdSession}
                  )
                ";

                $searchVar->sqlSearchVar[] = "(p.exclude_stock_difference = ''
                                            OR p.exclude_stock_difference IS NULL
                                            OR p.exclude_stock_difference = 0)";
            }

            if ($tv['special_search'] == "Past SD") {
                //$searchVar->sqlHavingVar[] = "pre_stock_actual != pre_manual_stock";
                $searchVar->sqlSearchVar[] = "i.product_id IN (
                    SELECT DISTINCT ms.product_id 
                    FROM manual_stock ms 
                    WHERE ms.product_id = i.product_id 
                      AND ms.site_id    = {$cpSiteIdSession}
                      AND ms.product_id NOT IN (332, 338, 785, 861, 1312)
                      AND ms.date      != '{$current_date}'
                      AND ms.stock     != ms.stock_difference
                    ORDER BY ms.manual_stock_id DESC
                  )
                ";

                $searchVar->sqlSearchVar[] = "(p.exclude_stock_difference = ''
                                            OR p.exclude_stock_difference IS NULL
                                            OR p.exclude_stock_difference = 0)";
            }

         if ($tv['category_id'] != '' ) {
                $searchVar->sqlSearchVar[] = "p.category_id = '{$tv['category_id']}'";
            }
           if ($tv['sub_category_id'] != '' ) {
                $searchVar->sqlSearchVar[] = "p.sub_category_id = '{$tv['sub_category_id']}'";
            }

            if ($employee_id != '' ) {
                $searchVar->sqlSearchVar[] = "i.product_id IN (
                    SELECT mv.product_id
                    FROM consultant_doctor mv
                    WHERE mv.employee_id = '{$employee_id}'
                    
                )";
            }

            if ($product_type != '' ) {
                $searchVar->sqlSearchVar[] = "p.product_type IN (
                    SELECT mv.value 
                    FROM `valuelist` mv
                    WHERE mv.value = '{$product_type}'
                   AND key_text = 'productType' 
                    
                )";
            }

            if ($minimum_order_level != '') {
                $mol_start_date = date('Y-m-d', strtotime('-90 days'));
                $mol_end_date   = date("Y-m-d", strtotime("yesterday"));
                /*$searchVar->sqlHavingVar[] = "stock <= (((SELECT SUM(it.qty) AS qty
                                            FROM invoice inv
                                            LEFT JOIN (invoice_item it) ON (it.invoice_id = inv.invoice_id)
                                            WHERE inv.invoice_date >= '{$mol_start_date}' AND inv.invoice_date <= '{$mol_end_date}'
                                              AND inv.status != 'Cancelled'
                                              AND inv.site_id = {$cpSiteIdSession}
                                              AND it.qty != 0
                                              AND it.record_id = i.product_id
                                            GROUP BY it.record_id)/3)/2)";*/
                                            
                $searchVar->sqlSearchVar[] = "p.not_add_in_stock != 1";

                $searchVar->sqlSearchVar[] = "i.minimum_order_level{$cpSiteIdSession} > 0";
                $searchVar->sqlHavingVar[] = "stock <= i.minimum_order_level{$cpSiteIdSession}";
            }

            if ($expiry_date == 'Expiry Date < 100') {
                $searchVar->sqlSearchVar[] = "(i.product_id IN (SELECT ibs.product_id
                                                FROM inventory_batchwise_stock ibs
                                                LEFT JOIN po_product po ON (po.po_product_id = ibs.po_product_id)
                                                WHERE ibs.current_stock  - IFNULL(po.return_qty_ns, 0)> 0
                                                AND ibs.site_id = {$cpSiteIdSession}
                                                AND DATEDIFF(po.expiry_date, Now()) < 100))";
            } else if($expiry_date == 'Expiry Date < 30'){
                $searchVar->sqlSearchVar[] = "(i.product_id IN (SELECT ibs.product_id
                                                FROM inventory_batchwise_stock ibs
                                                LEFT JOIN po_product po ON (po.po_product_id = ibs.po_product_id)
                                                WHERE ibs.current_stock  - IFNULL(po.return_qty_ns, 0)> 0 AND ibs.site_id = {$cpSiteIdSession}
                                                AND DATEDIFF(po.expiry_date, Now()) < 30))";                
            } else if($expiry_date == 'Expiry Date < 120'){
                $searchVar->sqlSearchVar[] = "(i.product_id IN (SELECT ibs.product_id
                                                FROM inventory_batchwise_stock ibs
                                                LEFT JOIN po_product po ON (po.po_product_id = ibs.po_product_id)
                                                WHERE ibs.current_stock  - IFNULL(po.return_qty_ns, 0)> 0
                                                AND ibs.site_id = {$cpSiteIdSession}
                                                AND DATEDIFF(po.expiry_date, Now()) < 120))";                
            } else if($expiry_date == 'Expiry Date < 60'){
                $searchVar->sqlSearchVar[] = "(i.product_id IN (SELECT ibs.product_id
                                                FROM inventory_batchwise_stock ibs
                                                LEFT JOIN po_product po ON (po.po_product_id = ibs.po_product_id)
                                                WHERE ibs.current_stock  - IFNULL(po.return_qty_ns, 0)> 0
                                                AND ibs.site_id = {$cpSiteIdSession}
                                                AND DATEDIFF(po.expiry_date, Now()) < 60))";                
            }
           
            if ($tv['special_search'] == "Flagged") {
                $searchVar->sqlSearchVar[] = "i.flag{$cpSiteIdSession} = 1";
            }

            $searchVar->sqlSearchVar[] = "p.published = 1";

            if ($tv['special_search'] == "Rack Wise SD") {
                $searchVar->sqlSearchVar[] = "ms.rake != ''";
                $searchVar->sortOrder = "ms.rake ASC, p.title ASC, stock DESC";
            } else {
                $searchVar->sortOrder = "p.title ASC, stock DESC";
            }
        }
    }

    /**
     *
     */
    function getNewValidate() {
        $validate = Zend_Registry::get('validate');

        $validate->resetErrorArray();
        //$validate->validateData('company_name', 'Please enter the company name');

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
        $fa = $fn->addToFieldsArray($fa, 'product_id');
        $fa = $fn->addToFieldsArray($fa, 'company_name');
        $fa = $fn->addToFieldsArray($fa, 'code');
        $fa = $fn->addToFieldsArray($fa, 'changed_stock');
        $fa = $fn->addToFieldsArray($fa, 'notes');
        $fa = $fn->addToFieldsArray($fa, 'minimum_order_level1');
        $fa = $fn->addToFieldsArray($fa, 'minimum_order_level2');
        $fa = $fn->addToFieldsArray($fa, 'minimum_order_level3');

        return $fa;
    }

    /**
     *
     */
    function getExportDataOld($dataArray){
        $db      = Zend_Registry::get('db');
        $phpExcel = includeCPClass('Lib', 'PhpExcelExportWrapper', 'PhpExcelExportWrapper');
        $dbUtil = Zend_Registry::get('dbUtil');

        $fa = array(
             'item_code'    => $phpExcel->getFldObj('ITEM CODE')
            ,'product_name' => $phpExcel->getFldObj('MEDICINE NAME')
            ,'stock'        => $phpExcel->getFldObj('STOCK')
        );

        //$dataArray = $dbUtil->getResultsetAsArray($result);

        $config = array(
             'fldsArr'   => $fa
            ,'dataArray' => $dataArray
        );

        return $phpExcel->exportData($config);
    }

    /**
     *
     */
    function getExportData($dataArray){
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $cpUtil = Zend_Registry::get('cpUtil');
        $dateUtil = Zend_Registry::get('dateUtil');
        $fn = Zend_Registry::get('fn');

        set_time_limit(50000);
        ini_set('memory_limit', '512M');

        require_once("PHPExcel.php");
        include 'PHPExcel/IOFactory.php';

        $file_name = "Inventory_" . date("d-m-Y") . ".xls";

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename={$file_name}");
        header("Content-Transfer-Encoding: binary ");

        $objPHPExcel = new PHPExcel();
        //--------------------------------------------------//
        $rowc = 1;
        $colc = 0;
        $actSheet = &$objPHPExcel->getActiveSheet();
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'NAME');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'STOCK');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'MS');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'STOCK DIFF');
        //$actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'MRP');
        //$actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'TOTAL AMOUNT');
        if( $_SESSION['userGroupName'] != "Administrator"){
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'RACK');
        }

        /******************** FORMAT HEADER *******************/
        $headStyle = array(
            'font' => array('bold' => true)
        );
        $stock_difference = '';

        $lastCol    = $actSheet->getHighestColumn();
        $lastColInd = PHPExcel_Cell::columnIndexFromString($lastCol);
        $actSheet->getStyle("A1:{$lastCol}1")->applyFromArray($headStyle);

        for ($i=0; $i < $lastColInd; $i++){
            $colAlphabet = PHPExcel_Cell::stringFromColumnIndex($i);
            $actSheet->getColumnDimension($colAlphabet)->setAutoSize(true);
        }

        $category_id         = $fn->getReqParam('category_id');
        $minimum_order_level = $fn->getReqParam('minimum_order_level');
        $expiry_date         = $fn->getReqParam('expiry_date');

        $appendSqlStk   = "";
        $siteIdForField = "";
        if($cpCfg['cp.hasMultiUniqueSites']  == true){
            $siteId         = $fn->getSessionParam('cp_site_id');
            $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
            $siteIdForField = $siteId;
            $appendSqlStk   = "AND ms.site_id = '{$siteId}'";
            $leftJoinMedicineSite = "LEFT JOIN (medicine_site ms) ON (i.product_id = ms.product_id)";
        }

        $appendSqlSD   = "";
        if ($tv['special_search'] == "Stock Difference") {
            //$searchVar->sqlHavingVar[] = "stock_actual != manual_stock";
            $appendSqlSD .= "AND i.product_id IN (
                SELECT DISTINCT ms.product_id 
                FROM manual_stock ms 
                WHERE ms.product_id = i.product_id 
                  AND ms.site_id    = {$cpSiteIdSession}
                  AND ms.product_id NOT IN (332, 338, 785, 861, 1312)
                  AND ms.date       = '{$current_date}'
                  AND ms.stock     != ms.stock_difference
                ORDER BY ms.manual_stock_id DESC
              )
            ";
            $appendSqlSD .= "AND (p.exclude_stock_difference = ''
                                        OR p.exclude_stock_difference IS NULL
                                        OR p.exclude_stock_difference = 0)";
        }

        if ($tv['special_search'] == "Sales Wise SD") {
            $start_date = date('Y-m-d', strtotime('-3 days'));
            $end_date = date('Y-m-d');
            $appendSqlSD .= "AND i.product_id IN (
                SELECT DISTINCT ii.record_id 
                FROM invoice_item ii
                LEFT JOIN (`invoice` inv) ON (inv.invoice_id = ii.invoice_id)
                WHERE ii.record_id = i.product_id 
                  AND inv.site_id    = {$cpSiteIdSession}
                  AND inv.status != 'Cancelled'
                  AND (inv.invoice_date >= '{$start_date}' AND inv.invoice_date <= '{$end_date}')
              )
            ";
            $appendSqlSD .= "AND i.product_id NOT IN (
                SELECT DISTINCT ms.product_id 
                FROM manual_stock ms 
                WHERE ms.product_id = i.product_id
                  AND ms.site_id    = {$cpSiteIdSession}
              )
            ";

            $appendSqlSD .= "AND (p.exclude_stock_difference = ''
                                        OR p.exclude_stock_difference IS NULL
                                        OR p.exclude_stock_difference = 0)";
        }

        if ($tv['special_search'] == "Past SD") {
            //$searchVar->sqlHavingVar[] = "pre_stock_actual != pre_manual_stock";
            $appendSqlSD .= "AND i.product_id IN (
                SELECT DISTINCT ms.product_id 
                FROM manual_stock ms 
                WHERE ms.product_id = i.product_id 
                  AND ms.site_id    = {$cpSiteIdSession}
                  AND ms.product_id NOT IN (332, 338, 785, 861, 1312)
                  AND ms.date      != '{$current_date}'
                  AND ms.stock     != ms.stock_difference
                ORDER BY ms.manual_stock_id DESC
              )
            ";

            $appendSqlSD .= "AND (p.exclude_stock_difference = ''
                                        OR p.exclude_stock_difference IS NULL
                                        OR p.exclude_stock_difference = 0)";
        }

        if ($category_id != '' ) {
            $appendSqlSD .= "AND p.category_id = '{$category_id}'";
        }

        if ($minimum_order_level != '') {                                        
            $appendSqlSD .= "AND p.not_add_in_stock != 1";
            $appendSqlSD .= "AND i.minimum_order_level{$siteId} > 0 HAVING stock <= i.minimum_order_level{$siteId}";
        }

        if ($expiry_date == 'Expiry Date < 100') {
            $appendSqlSD .= "AND (i.product_id IN (SELECT ibs.product_id
                                            FROM inventory_batchwise_stock ibs
                                            LEFT JOIN po_product po ON (po.po_product_id = ibs.po_product_id)
                                            WHERE ibs.current_stock  - IFNULL(po.return_qty_ns, 0)> 0
                                            AND ibs.site_id = {$cpSiteIdSession}
                                            AND DATEDIFF(po.expiry_date, Now()) < 100))";
        } else if($expiry_date == 'Expiry Date < 30'){
            $appendSqlSD .= "AND (i.product_id IN (SELECT ibs.product_id
                                            FROM inventory_batchwise_stock ibs
                                            LEFT JOIN po_product po ON (po.po_product_id = ibs.po_product_id)
                                            WHERE ibs.current_stock  - IFNULL(po.return_qty_ns, 0)> 0 AND ibs.site_id = {$cpSiteIdSession}
                                            AND DATEDIFF(po.expiry_date, Now()) < 30))";                
        } else if($expiry_date == 'Expiry Date < 120'){
            $appendSqlSD .= "AND (i.product_id IN (SELECT ibs.product_id
                                            FROM inventory_batchwise_stock ibs
                                            LEFT JOIN po_product po ON (po.po_product_id = ibs.po_product_id)
                                            WHERE ibs.current_stock  - IFNULL(po.return_qty_ns, 0)> 0
                                            AND ibs.site_id = {$cpSiteIdSession}
                                            AND DATEDIFF(po.expiry_date, Now()) < 120))";                
        } else if($expiry_date == 'Expiry Date < 60'){
            $appendSqlSD .= "AND (i.product_id IN (SELECT ibs.product_id
                                            FROM inventory_batchwise_stock ibs
                                            LEFT JOIN po_product po ON (po.po_product_id = ibs.po_product_id)
                                            WHERE ibs.current_stock  - IFNULL(po.return_qty_ns, 0)> 0
                                            AND ibs.site_id = {$cpSiteIdSession}
                                            AND DATEDIFF(po.expiry_date, Now()) < 60))";                
        }
           
        if ($tv['special_search'] == "Flagged") {
            $appendSqlSD .= "AND i.flag{$cpSiteIdSession} = 1";
        }

        if ($tv['special_search'] == "Rack Wise SD") {
            $appendSqlSD .= "AND ms.rake != ''";
        }
        $current_date = $cpUtil->getISODateStr();
        $cpSiteIdSession     = $fn->getSessionParam('cp_site_id');

        $appendSqlSD1= '';
        if ($tv['special_search'] == "Stock Difference") {
            $appendSqlSD1 .= " AND i.product_id IN (
                    SELECT DISTINCT ms.product_id 
                    FROM manual_stock ms 
                    WHERE ms.product_id = i.product_id 
                      AND ms.site_id    = {$cpSiteIdSession}
                      AND ms.product_id NOT IN (332, 338, 785, 861, 1312)
                      AND ms.date       = '{$current_date}'
                      AND ms.stock     != ms.stock_difference
                    ORDER BY ms.manual_stock_id DESC";
        }

        $appendSqlKwrd = "";

        if ($tv['keyword'] != "") {
            $appendSqlKwrd = "AND (
                p.title LIKE '{$tv['keyword']}%'
                || p.item_code LIKE '{$tv['keyword']}%'
                || i.inventory_code LIKE '%{$tv['keyword']}%'
            )";
        }

        if($appendSqlSD1){
            $appendSqlKwrd = '';
            $appendSqlSD = '';           
        }

        $SQL = "
        SELECT i.*
              ,p.product_id AS productId
              ,p.title AS product_name
              ,p.item_code
              ,p.unit
              ,p.product_code
              ,p.price
              ,i.actual_stock{$siteIdForField} AS stock
              ,ms.rake
        FROM inventory i
        LEFT JOIN (product p) ON (p.product_id = i.product_id)
        LEFT JOIN (medicine_site ms) ON (i.product_id = ms.product_id)
        WHERE p.published = 1 
        {$appendSqlKwrd}
        {$appendSqlStk}
        {$appendSqlSD}
        ORDER BY ms.rake, p.title ASC, stock DESC
        ";
        if($appendSqlSD1){
            $SQL = "
        SELECT i.*
              ,p.product_id AS productId
              ,p.title AS product_name
              ,p.item_code
              ,p.unit
              ,p.product_code
              ,p.category_id
              ,p.not_add_in_stock
              ,p.exclude_stock_difference
              ,ms.rake
              ,i.actual_stock1 AS stock
        FROM inventory i
        LEFT JOIN (product p) ON (p.product_id = i.product_id)
        LEFT JOIN (medicine_site ms) ON (i.product_id = ms.product_id AND ms.site_id = 1)
         WHERE ms.site_id = 1
            AND i.product_id IN (
                    SELECT DISTINCT ms.product_id 
                    FROM manual_stock ms 
                    WHERE ms.product_id = i.product_id 
                      AND ms.site_id    = {$cpSiteIdSession}
                      AND ms.product_id NOT IN (332, 338, 785, 861, 1312)
                      AND ms.date       = '{$current_date}'
                      AND ms.stock     != ms.stock_difference
                    ORDER BY ms.manual_stock_id DESC
                  )
          ";
        }
        $result = $db->sql_query($SQL);
        $total_amount = 0;
        //============================================================================= //
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $siteIdForField = "";
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $site_id        = $cpSiteIdSession;
            $siteIdForField = $site_id;
        }
        
        while ($row = $db->sql_fetchrow($result)) {
            //$stockArray = $fn->getStockForProduct($row['product_id'], $siteId);
            //$stock      = $stockArray['OverallStock'];
            list($intStockOverall, $decStockOverall) = explode('.', $row['actual_stock'.$siteIdForField]);
            $stock = $intStockOverall;
            $actual_stock = $stock;
            $stock = number_format($stock);
            //$stock      = $row['actual_stock'];
             $manualStockToday = '';
             $manualStockTodayRow = '';
            if($appendSqlSD1){
                $SQLMS = "
                SELECT ms.stock, ms.actual_stock, ms.date, ms.time
                FROM manual_stock ms
                WHERE ms.product_id = {$row['product_id']}
                  AND ms.site_id = {$cpSiteIdSession}
                  ORDER BY ms.manual_stock_id DESC
                ";
                $resultMS   = $db->sql_query($SQLMS);
                $rowMS = $db->sql_fetchrow($resultMS);
                $manualStockToday = $rowMS['stock'];
                $stock_difference = $manualStockToday - $stock;
            }

            $SQLPP = "
            SELECT pack_size
            FROM po_product
            WHERE product_id = {$row['product_id']}
            ORDER BY po_product_id DESC
            ";
            $resultPP = $db->sql_query($SQLPP);
            $rowPP = $db->sql_fetchrow($resultPP);
            if(is_numeric($rowPP['pack_size'])){
                $price = $row['price'] / $rowPP['pack_size'];
            } else {
                $price = $row['price'];
            }

            $amount = $price * $stock;
            $total_amount += $amount;

            $colc = 0;
            $rowc++;

            if( $_SESSION['userGroupName'] == "Administrator"){
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['product_name']);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $stock);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $manualStockToday);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $stock_difference);
                //$actSheet->setCellValueByColumnAndRow($colc++, $rowc, round($price, 2));
                //$actSheet->setCellValueByColumnAndRow($colc++, $rowc, round($amount, 2));
                //$actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['rake']);
            } else if($_SESSION['userGroupName'] == "Super Administrator"){
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['product_name']);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $stock);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $manualStockToday);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $stock_difference);
                //$actSheet->setCellValueByColumnAndRow($colc++, $rowc, round($price, 2));
                //$actSheet->setCellValueByColumnAndRow($colc++, $rowc, round($amount, 2));
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['rake']);
            }else{
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['product_name']);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $manualStockToday);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
                //$actSheet->setCellValueByColumnAndRow($colc++, $rowc, round($price, 2));
               // $actSheet->setCellValueByColumnAndRow($colc++, $rowc, round($amount, 2));
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['rake']);

            }
        }

        $colc = 0;
        $rowc++;
        //$actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        //$actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
       // $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
       // $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Total');
       // $actSheet->setCellValueByColumnAndRow($colc++, $rowc, round($total_amount, 2));

        $actSheet->getStyle("C{$rowc}:E{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        ob_end_clean();
        $objWriter->save('php://output');
    }

    /**
     *
     */
    function getImportData(){
        $phpExcel = includeCPClass('Lib', 'PhpExcelImportWrapper');
        $db = Zend_Registry::get('db');

        /*
        STOCK - qty_in_stock - qty
        PRODUCT - title
        Item Code - item_code
        Category - category_id
        FC from China - fc_price
        Purchase Cost from BLOSSOMS - price
        Product Weight - product_weight
        VAT% - vat_percentage
        Price per KG - weight_per_kg
        Product Display Price - selling_price
        Add Shipping Cost - logistics
        Comission Calculation Price (Less VAT & Logistics) - agent_price
        TC Comsn % ( 5% - 20%) - commission
        */

        $fa = array(
              'title' => $phpExcel->getImportFldObj('PRODUCT')
             //,'purchase_order_date' => $phpExcel->getImportFldObj('Purchase Date')
             ,'item_code' => $phpExcel->getImportFldObj('ITEM CODE')
             ,'inventory_code' => $phpExcel->getImportFldObj('Inventory Code')
             ,'inventory_id' => $phpExcel->getImportFldObj('Inventory Id')
             ,'code' => $phpExcel->getImportFldObj('Code')
             ,'color' => $phpExcel->getImportFldObj('Color')
             ,'size'  => $phpExcel->getImportFldObj('Size')
             ,'model'  => $phpExcel->getImportFldObj('Model')
             ,'stock' => $phpExcel->getImportFldObj('Stock')
             ,'product_id' => $phpExcel->getImportFldObj('Product Id')
        );

        $fa['title']['refOnly'] = true;
        $fa['item_code']['refOnly'] = true;
        $fa['inventory_code']['refOnly'] = true;
        //$fa['inventory_id']['refOnly'] = true;
        $fa['color']['refOnly'] = true;
        $fa['size']['refOnly'] = true;
        $fa['model']['refOnly'] = true;
        $fa['stock']['refOnly'] = true;
        $fa['product_id']['refOnly'] = true;
        $fa['code']['refOnly'] = true;

        /****************************************/
        $config = array(
             'module'              => 'tradingin_inventory'
            ,'matchFieldArr'       => array('inventory_id')
            ,'fldsArr'             => $fa
            ,'callbackAfterInsert' => 'importDataRowCallbackForStock'
        );

        return $phpExcel->importData($config);
    }

    /**
     *
     */
    function importDataRowCallbackForStock($inventory_id, $fa) {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        $stock = $fa['stock'];
        $item_code = $fa['item_code'];
        $title = $fa['title'];
        $inventory_code = $fa['inventory_code'];
        $code = $fa['code'];
        $color = $fa['color'];
        $size = $fa['size'];
        $model = $fa['model'];
        $product_id = $fa['product_id'];
        $inventory_id = $fa['inventory_id'];

        //$productRec  = $fn->getRecordRowByID('product', 'product_id', $product_id);
        $fa2 = array();
        $fa2['purchase_order_date']  = date('Y-m-d');
        $fa2['po_code'] = $this->getUpdatePOCode();

        $SQL = $dbUtil->getInsertSQLStringFromArray($fa2, 'purchase_order');
        $result = $db->sql_query($SQL);
        $purchase_order_id  = $db->sql_nextid();

        $fa3 = array();
        $fa3['product_id'] = $product_id;
        //$fa3['inventory_id'] = $inventory_id;
        $fa3['purchase_order_id']  = $purchase_order_id;
        $fa3['qty']  = $stock;
        //$fa3['qty_requested']  = $stock;
        $fa3['color_size_code']  = $code;
        $fa3 = $fn->addCreationDetailsToFieldsArray($fa3, 'po_product');

        $SQL = $dbUtil->getInsertSQLStringFromArray($fa3, 'po_product');
        $result = $db->sql_query($SQL);

        if($color != ''){
            $productColorChk = $fn->getRecordByCondition('product_color', "color = '{$color}' AND product_id = '{$product_id}'");
            if($productColorChk){
                //$productColorRec = $fn->getRecordByCondition('product_color', "code = '{$code}'");
                $qty = $productColorChk['qty'] + $stock;
                $SQLUpdate ="
                UPDATE product_color SET qty = {$qty}
                WHERE product_color_id = {$productColorChk['product_color_id']}";
                $resultUpdate = $db->sql_query($SQLUpdate);
                $product_color_id = $productColorChk['product_color_id'];
            } else {
                $fa = array();
                $fa['product_id'] = $product_id;
                $fa['color'] = $color;
                $fa['qty']  = $stock;
                $fa = $fn->addCreationDetailsToFieldsArray($fa, 'product_color');

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'product_color');
                $result = $db->sql_query($SQL);
                $product_color_id = $db->sql_nextid();
            }
        }

        if($size != ''){
            $productSizeChk = $fn->getRecordByCondition('product_size', "size = '{$size}' AND product_id = '{$product_id}'");
            if($productSizeChk){
                //$productSizeRec = $fn->getRecordByCondition('product_size', "code = '{$code}'");
                $qty = $productSizeChk['qty'] + $stock;
                $SQLUpdate ="
                UPDATE product_size SET qty = {$qty}
                WHERE product_size_id = {$productSizeChk['product_size_id']}";
                $resultUpdate = $db->sql_query($SQLUpdate);
                $product_size_id = $productSizeChk['product_size_id'];
            } else {
                $fa = array();
                $fa['product_id'] = $product_id;
                $fa['size'] = $size;
                $fa['qty']  = $stock;
                $fa = $fn->addCreationDetailsToFieldsArray($fa, 'product_size');

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'product_size');
                $result = $db->sql_query($SQL);
                $product_size_id = $db->sql_nextid();
            }

        }

        if($model != ''){
            $productModelChk = $fn->getRecordByCondition('product_model', "model = '{$model}' AND product_id = '{$product_id}'");
            if($productModelChk){
                $qty = $productModelChk['qty'] + $stock;
                $SQLUpdate ="
                UPDATE product_model SET qty = {$qty}
                WHERE product_model_id = {$productModelChk['product_model_id']}";
                $resultUpdate = $db->sql_query($SQLUpdate);
                $product_model_id = $productModelChk['product_model_id'];
            } else {
                $fa = array();
                $fa['product_id'] = $product_id;
                $fa['model'] = $model;
                $fa['qty']  = $stock;
                $fa = $fn->addCreationDetailsToFieldsArray($fa, 'product_model');

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'product_model');
                $result = $db->sql_query($SQL);
                $product_model_id = $db->sql_nextid();
            }

        }

        if($color != '' && $size != ''){
            $productColorSizeChk = $fn->getRecordByCondition('product_color_by_size', "product_color_id = '{$product_color_id}' AND product_size_id = '{$product_size_id}' AND product_id = '{$product_id}'");
            if($productColorSizeChk){
                //$productColorSizeRec = $fn->getRecordByCondition('product_color_by_size', "code = '{$code}'");
                $qty = $productColorSizeChk['qty'] + $stock;
                $SQLUpdate ="
                UPDATE product_color_by_size SET qty = {$qty}
                WHERE product_color_by_size_id = {$productColorSizeChk['product_color_by_size_id']}";
                $resultUpdate = $db->sql_query($SQLUpdate);
            } else {
                $fa = array();
                $fa['product_id'] = $product_id;
                $fa['product_color_id'] = $product_color_id;
                $fa['product_size_id']  = $product_size_id;
                $fa['qty']  = $stock;
                $fa = $fn->addCreationDetailsToFieldsArray($fa, 'product_color_by_size');

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'product_color_by_size');
                $result = $db->sql_query($SQL);
            }
        }
            $invRec = $fn->getRecordByCondition('inventory', "product_id = {$product_id}");
            $fa4 = array();
            $fa4['product_id'] = $product_id;
            $fa4['actual_stock'] = $invRec['actual_stock'] + $stock;
            //$fa4 = $fn->addCreationDetailsToFieldsArray($fa4, 'inventory');

            $whereCondition = "WHERE product_id = {$product_id}";
            $SQL = $dbUtil->getUpdateSQLStringFromArray($fa4, 'inventory', $whereCondition);
            $result = $db->sql_query($SQL);
    }

    /**
     *
     */
    function getUpdatePOCode() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        /* Updation of Purchase order Code */
        $poCode = $fn->getSettingsValueByKey("poCode");

        $POCode = $fn->getSettingsValueByKey('poCodePrefix') . $poCode;

        $SQL    = "UPDATE setting SET value = (value+1) WHERE key_text = 'poCode'";
        $result = $db->sql_query($SQL);

        return $POCode;
    }

    /**
     *
     */
    function getBatchProductUpdateCurrentStock1() {
        $db     = Zend_Registry::get('db');
        $fn     = Zend_Registry::get('fn');
        $tv     = Zend_Registry::get('tv');
        $cpCfg  = Zend_Registry::get('cpCfg');
        $dbUtil = Zend_Registry::get('dbUtil');
        
        $site_id = $fn->getSessionParam('cp_site_id');

        $sqlSite = "
        SELECT site_id
              ,title
        FROM site
        ORDER BY site_id
        ";
        $resultSite = $db->sql_query($sqlSite);
        while ($rowSite = $db->sql_fetchrow($resultSite)) {
            //AND product_id = 44
            
            $SQLProduct = "
            SELECT product_id
            FROM product
            WHERE published = 1
            ";

            $resultProduct = $db->sql_query($SQLProduct);
            $batchwiseStock = 0;
            $stockOverall   = 0;
            $batchwiseStockOverall = 0;
            while ($rowProduct = $db->sql_fetchrow($resultProduct)){
                $rows = "";
                $count = 1;
                $batchwiseStock = 0;
                $stockOverall   = 0;
                $batchwiseStockOverall = 0;
                $po_product_id     = "";
                $purchase_order_id = "";
                $batch_no          = "";

                $stockOverallArray = $fn->getStockForProduct($rowProduct['product_id'], $rowSite['site_id']);
                $stockOverall      = $stockOverallArray['OverallStock'];
                $batchCount        = $this->getBatchProductCountCheck($rowProduct['product_id'], $rowSite['site_id']);

                if($batchCount >= 1) {
                    $appendSql   = '';
                    $sqlAppendSt = '';
                    $stockTransferSQLForMultiSite = '';
                    if ($cpCfg['cp.hasMultiUniqueSites']) {
                        $appendSql   = "AND po.site_id = {$rowSite['site_id']}";
                        $sqlAppendSt = "AND st.to_location = {$rowSite['site_id']}";

                        $stockTransferSQLForMultiSite = "
                        UNION
                        SELECT  p.title
                               ,p.unit
                               ,p.item_code
                               ,st.stock_transfer_id AS po_code
                               ,st.stock_transfer_id AS purchase_order_id
                               ,pp.cost_price
                               ,pp.pack_size
                               ,pp.selling_price
                               ,pp.qty_requested AS qty
                               ,pp.gst
                               ,pp.batch_no AS batch_no
                               ,pp.expiry_date
                               ,p.hsn AS hsn_code
                               ,p.product_id AS product_id
                               ,p.title AS main_product_title
                               ,p.item_code AS main_product_code
                               ,'STOCK TRANSFER' AS stock_from
                        FROM stock_transfer_history sth
                        LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
                        LEFT JOIN po_product pp ON (pp.po_product_id = sth.po_product_id)
                        LEFT JOIN product p ON (p.product_id = pp.product_id)
                        WHERE sth.product_id = {$rowProduct['product_id']}
                          {$sqlAppendSt}
                        AND st.status = 'Delivered'        
                        GROUP BY batch_no
                        ORDER BY batch_no
                        ";
                    }

                    $SQLPO ="
                    SELECT p.title
                          ,p.unit
                          ,p.item_code
                          ,po.po_code AS po_code
                          ,po.purchase_order_id AS purchase_order_id
                          ,pp.cost_price
                          ,pp.pack_size
                          ,pp.selling_price
                          ,pp.qty_requested AS qty
                          ,pp.qty
                          ,pp.gst
                          ,pp.batch_no AS batch_no
                          ,pp.expiry_date
                          ,pp.po_product_id
                          ,p.hsn AS hsn_code
                          ,p.product_id AS product_id
                          ,p.title AS main_product_title
                          ,p.item_code AS main_product_code
                          ,'PURCHASE ORDER' AS stock_from
                          ,(SELECT SUM(inItm.qty) FROM invoice_item inItm
                            LEFT JOIN (`invoice` inv) ON (inv.invoice_id = inItm.invoice_id)
                            WHERE inItm.record_id = pp.product_id
                            AND inItm.not_add_in_stock != 1
                            AND inv.status = 'Paid'
                            AND inv.invoice_type = 'POS'
                            AND inItm.batch_no = pp.batch_no) AS Sales

                         ,(SELECT SUM(CASE 
                                   WHEN pop.pack_size REGEXP '^[+-]?[0-9]+$'
                                   THEN pop.qty * pop.pack_size
                                   ELSE pop.qty END) AS Purchase 
                            FROM po_product pop
                            LEFT JOIN purchase_order po ON (po.purchase_order_id = pop.purchase_order_id)
                            WHERE po.status != 'Cancelled'
                            AND pop.batch_no = pp.batch_no
                            AND pop.product_id = pp.product_id) AS Purchase
                    FROM po_product pp
                    LEFT JOIN product p ON (p.product_id = pp.product_id)
                    LEFT JOIN (purchase_order po) ON (po.purchase_order_id = pp.purchase_order_id)
                    WHERE pp.product_id = {$rowProduct['product_id']}
                    AND po.status != 'Cancelled'
                    {$appendSql}
                    GROUP BY pp.batch_no
                    ORDER BY 
                    CASE
                        WHEN (Purchase < Sales 
                              ) THEN 1
                        ELSE pp.po_product_id
                    END
                    {$stockTransferSQLForMultiSite}
                    ";
                    $resultPo = $db->sql_query($SQLPO);
                    $numRows = $db->sql_numrows($resultPo);
                    while ($rowPo = $db->sql_fetchrow($resultPo)){
                        $selling_price = $rowPo['selling_price'];
                        if($selling_price == ""){
                            $selling_price = 0;
                        }

                        $appendSqlOrd  = "AND o.site_id  = {$rowSite['site_id']}";
                        $appendSqlPur  = "AND po.site_id = {$rowSite['site_id']}";
                        $appendSqlInv  = "AND inv.site_id = {$rowSite['site_id']}";
                        $appendSqlStk  = "AND st.from_location = '{$rowSite['site_id']}'";
                        $appendSqlStk2 = "AND st.to_location = '{$rowSite['site_id']}'";
                        $appendSqlInT  = "AND site_id = {$rowSite['site_id']}";

                        $SQLOthersite = "
                        SELECT
                            (SELECT SUM(CASE 
                                        WHEN pp.pack_size REGEXP '^[+-]?[0-9]+$'
                                        THEN pp.qty * pp.pack_size
                                        ELSE pp.qty END) AS purchased_qty
                            FROM po_product pp
                            LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                            WHERE pp.product_id = {$rowPo['product_id']}
                            AND po.status != 'Cancelled'
                            AND pp.batch_no = '{$rowPo['batch_no']}'
                            {$appendSqlPur}
                            ) as product_qty_purchased

                           ,(SELECT SUM(damage_qty) FROM po_product pp
                             LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                             WHERE pp.product_id = {$rowPo['product_id']}
                             {$appendSqlPur}
                             AND pp.batch_no = '{$rowPo['batch_no']}') as damage_qty

                            ,(SELECT SUM(inItm.qty) FROM invoice_item inItm
                            LEFT JOIN (`invoice` inv) ON (inv.invoice_id = inItm.invoice_id)
                            WHERE inItm.record_id = {$rowPo['product_id']}
                            AND inItm.not_add_in_stock != 1
                            AND inv.status = 'Paid'
                            AND inv.invoice_type = 'POS'
                            AND inItm.batch_no = '{$rowPo['batch_no']}'
                            {$appendSqlInv}
                            ) as product_qty_sold

                            ,(SELECT  SUM(CASE 
                                        WHEN sth.pack_size REGEXP '^[+-]?[0-9]+$'
                                        THEN sth.qty * sth.pack_size
                                        ELSE sth.qty END)
                              FROM stock_transfer_history sth
                              LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
                              WHERE sth.product_id = {$rowPo['product_id']}
                              AND sth.batch_no = '{$rowPo['batch_no']}'
                              AND st.status = 'Delivered'
                              {$appendSqlStk}
                            ) as product_qty_sold_from_stock

                            ,(SELECT  SUM(CASE 
                                        WHEN sth.pack_size REGEXP '^[+-]?[0-9]+$'
                                        THEN sth.qty * sth.pack_size
                                        ELSE sth.qty END)
                              FROM stock_transfer_history sth
                              LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
                              WHERE sth.product_id = {$rowPo['product_id']}
                              AND sth.batch_no = '{$rowPo['batch_no']}'
                              AND st.status = 'Delivered'
                              {$appendSqlStk2}
                            ) as product_qty_sold_to_stock

                            ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                            LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                            LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                            WHERE ini.record_id = {$rowPo['product_id']}
                              AND srh.status = 'Approved'
                              AND ini.batch_no = '{$rowPo['batch_no']}'
                              {$appendSqlInv}
                            ) as sales_return_qty

                            ,(SELECT changed_stock 
                              FROM inventory
                              WHERE product_id = {$rowPo['product_id']}
                              {$appendSqlInT}
                            ) AS adjust_stock
                        ";
                        $resultothersite = $db->sql_query($SQLOthersite);
                        $rowothersite    = $db->sql_fetchrow($resultothersite);

                        $stock        = $rowothersite['product_qty_purchased'] - $rowothersite['product_qty_sold_from_stock'] + $rowothersite['product_qty_sold_to_stock'] - $rowothersite['product_qty_sold'] + $rowothersite['sales_return_qty'] - $rowothersite['damage_qty'];
                        $SoldQty      = $rowothersite['product_qty_sold'];
                        $PurchasedQty = $rowothersite['product_qty_purchased'];
                        $AdjustStock  = $rowothersite['adjust_stock'];

                        if($PurchasedQty < $SoldQty) {
                            $batchwiseStock = $PurchasedQty - $SoldQty;
                        } else if($PurchasedQty >= $SoldQty) {
                            if($batchwiseStock > 0) {
                                $batchwiseStock = $stock;
                            } else {
                                $batchwiseStock = $batchwiseStock + $stock;
                            }
                        }

                        //print($rowPo['batch_no']." - ".$rowPo['Purchase']." - ".$rowPo['Sales']." - ".$batchwiseStock." - ".$stock."<br/>");                
                        if($batchwiseStock > 0 || $count == $numRows) {
                            $fa = array();
                            $fa['product_id']         = $rowPo['product_id'];
                            $fa['po_product_id']      = $rowPo['po_product_id'];
                            $fa['purchase_order_id']  = $rowPo['purchase_order_id'];
                            $fa['batch_no']           = $rowPo['batch_no'];
                            $fa['site_id']            = $rowSite['site_id'];
                            $fa['current_stock']      = $batchwiseStock;

                            $fa = $fn->addCreationDetailsToFieldsArray($fa, 'inventory_batchwise_stock');
                            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'inventory_batchwise_stock');
                            $result = $db->sql_query($SQL);
                            print("Id: ".$rowPo['product_id']." / Name: ".$rowPo['title']." / Batch Wise Stock: ".$batchwiseStock."<br/>");
                            $batchwiseStockOverall += $batchwiseStock;
                        }

                        $count++;
                        $po_product_id     = $rowPo['po_product_id'];
                        $purchase_order_id = $rowPo['purchase_order_id'];
                        $batch_no          = $rowPo['batch_no'];
                    }
                }

                //print($batchwiseStockOverall."<br/>");
                //print($stockOverall."<br/>");
                
                if($batchwiseStockOverall <= 0) {
                    $SQLStock ="
                    SELECT current_stock
                    FROM inventory_batchwise_stock
                    WHERE product_id = {$rowProduct['product_id']}
                    ";
                    $resultStock  = $db->sql_query($SQLStock);
                    $numRowsStock = $db->sql_numrows($resultStock);

                    if ($stockOverall > 0 && $numRowsStock == 0) {
                        $SQLPOCheck  = "
                        SELECT  pp.po_product_id
                               ,pp.purchase_order_id
                               ,pp.batch_no
                        FROM po_product pp
                        LEFT JOIN purchase_order po ON (po.purchase_order_id = pp.purchase_order_id)
                        WHERE pp.product_id = {$rowProduct['product_id']}
                        AND po.status != 'Cancelled'
                        ORDER BY pp.po_product_id DESC
                        ";
                        $resultPOCheck = $db->sql_query($SQLPOCheck);
                        $rowPOCheck    = $db->sql_fetchrow($resultPOCheck);

                        $fa = array();
                        $fa['product_id']         = $rowProduct['product_id'];
                        $fa['po_product_id']      = $rowPOCheck['po_product_id'];
                        $fa['purchase_order_id']  = $rowPOCheck['purchase_order_id'];
                        $fa['batch_no']           = $rowPOCheck['batch_no'];
                        $fa['current_stock']      = $stockOverall;
                        $fa['site_id']            = $rowSite['site_id'];

                        $fa = $fn->addCreationDetailsToFieldsArray($fa, 'inventory_batchwise_stock');
                        $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'inventory_batchwise_stock');
                        $result = $db->sql_query($SQL);
                        print("OverallStock Stock: ".$stockOverall."<br/>");
                    }
                } 

                if ($batchwiseStockOverall != $stockOverall) {
                    $SQLStock ="
                    DELETE FROM inventory_batchwise_stock
                    WHERE product_id = {$rowProduct['product_id']}
                    ";
                    $resultStock  = $db->sql_query($SQLStock);

                    $SQLPOCheck  = "
                    SELECT  pp.po_product_id
                           ,pp.purchase_order_id
                           ,pp.batch_no
                    FROM po_product pp
                    LEFT JOIN purchase_order po ON (po.purchase_order_id = pp.purchase_order_id)
                    WHERE pp.product_id = {$rowProduct['product_id']}
                    AND po.status != 'Cancelled'
                    ORDER BY pp.po_product_id DESC
                    ";
                    $resultPOCheck = $db->sql_query($SQLPOCheck);
                    $rowPOCheck    = $db->sql_fetchrow($resultPOCheck);

                    $fa = array();
                    $fa['product_id']         = $rowProduct['product_id'];
                    $fa['po_product_id']      = $rowPOCheck['po_product_id'];
                    $fa['purchase_order_id']  = $rowPOCheck['purchase_order_id'];
                    $fa['batch_no']           = $rowPOCheck['batch_no'];
                    $fa['current_stock']      = $stockOverall;
                    $fa['site_id']            = $rowSite['site_id'];

                    $fa = $fn->addCreationDetailsToFieldsArray($fa, 'inventory_batchwise_stock');
                    $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'inventory_batchwise_stock');
                    $result = $db->sql_query($SQL);
                    print("OverallStock Stock Not Matched: ".$stockOverall."<br/>");
                }
            }
        }
    }   

    /**
     *
     */
    function getBatchProductUpdateCurrentStock() {
        $db     = Zend_Registry::get('db');
        $fn     = Zend_Registry::get('fn');
        $tv     = Zend_Registry::get('tv');
        $cpCfg  = Zend_Registry::get('cpCfg');
        $dbUtil = Zend_Registry::get('dbUtil');
        
        $site_id = $fn->getReqParam('site_id');

        $appendSqlSite  = "";
        if($site_id != ""){ 
            $appendSqlSite = "WHERE site_id = {$site_id}";
        }
        
        $sqlSite = "
        SELECT site_id
              ,title
        FROM site
        {$appendSqlSite}
        ORDER BY site_id
        ";
        $resultSite = $db->sql_query($sqlSite);
        while ($rowSite = $db->sql_fetchrow($resultSite)) {
            $sqlInventory = "
            SELECT i.*
                  ,p.product_id AS productId
                  ,p.title AS product_name
                  ,p.item_code
                  ,p.unit
                  ,p.product_code
            FROM inventory i
            LEFT JOIN (product p) ON (p.product_id = i.product_id)
            LEFT JOIN (medicine_site ms) ON (i.product_id = ms.product_id)
            WHERE ms.site_id = {$rowSite['site_id']}
            AND p.published = 1
            ORDER BY p.title ASC
            ";
            $resultInventory = $db->sql_query($sqlInventory);
            while ($rowInventory = $db->sql_fetchrow($resultInventory)) {
                $sqlAppend     = "";
                $leftjnAppend  = "";
                $leftjnAppend2 = "";
                if($cpCfg['cp.hasMultiUniqueSites']  == true){
                    $sqlAppend = ",s.title as site_title";
                    $leftjnAppend = "
                    LEFT JOIN site s ON s.site_id = po.site_id";
                    $leftjnAppend2 = "
                    LEFT JOIN site s ON s.site_id = st.to_location";
                }

                $appendSql   = '';
                $sqlAppendSt = '';
                $stockTransferSQLForMultiSite = '';
                if ($cpCfg['cp.hasMultiUniqueSites']) {
                    $appendSql   = "AND po.site_id = {$rowSite['site_id']}";
                    $sqlAppendSt = "AND st.to_location = {$rowSite['site_id']}";

                    $stockTransferSQLForMultiSite = "
                    UNION
                    SELECT  sth.batch_no AS batch_no
                           ,sth.product_id
                           ,pp.expiry_date
                           ,pp.po_product_id
                           ,pp.purchase_order_id
                           ,'Stock Transfer' AS StockFrom
                           {$sqlAppend}
                    FROM stock_transfer_history sth
                    LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
                    LEFT JOIN po_product pp ON (pp.product_id = sth.product_id AND pp.batch_no = sth.batch_no)
                    {$leftjnAppend2}
                    WHERE sth.product_id = {$rowInventory['product_id']}
                    AND st.status = 'Delivered'
                      {$sqlAppendSt}
                    GROUP BY batch_no
                    ";
                }

                $SQLBatchNo = "
                SELECT  pp.batch_no AS batch_no
                       ,pp.product_id
                       ,pp.expiry_date
                       ,pp.po_product_id
                       ,pp.purchase_order_id
                       ,'Purchase Order' AS StockFrom
                       {$sqlAppend}
                FROM po_product pp
                LEFT JOIN product p ON (p.product_id = pp.product_id)
                LEFT JOIN purchase_order po ON (po.purchase_order_id = pp.purchase_order_id)
                {$leftjnAppend}
                WHERE pp.product_id = {$rowInventory['product_id']}
                  AND po.site_id = {$rowSite['site_id']}
                  AND po.status != 'Cancelled'
                GROUP BY pp.batch_no
                {$stockTransferSQLForMultiSite}
                ";
                $resultBatchNo = $db->sql_query($SQLBatchNo);
                while ($rowBatchNo = $db->sql_fetchrow($resultBatchNo)) {
                    if($rowBatchNo['StockFrom'] == "Purchase Order") {
                        $appendSqlOrd  = "AND o.site_id = {$rowSite['site_id']}";
                        $appendSqlPur  = "AND po.site_id = {$rowSite['site_id']}";
                        $appendSqlInv  = "AND inv.site_id = {$rowSite['site_id']}";
                        $appendSqlStk  = "AND st.from_location = '{$rowSite['site_id']}'";
                        $appendSqlStk2 = "AND st.to_location = '{$rowSite['site_id']}'";

                        $SQLOthersite = "
                        SELECT
                            (SELECT SUM(CASE 
                                        WHEN pp.pack_size REGEXP '^[+-]?[0-9]+$'
                                        THEN pp.qty * pp.pack_size
                                        ELSE pp.qty END) AS purchased_qty
                            FROM po_product pp
                            LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                            WHERE pp.product_id = {$rowBatchNo['product_id']}
                            AND po.status != 'Cancelled'
                            AND pp.batch_no = '{$rowBatchNo['batch_no']}'
                            {$appendSqlPur}
                            ) as product_qty_purchased

                           ,(SELECT SUM(damage_qty) FROM po_product pp
                             LEFT JOIN purchase_order po ON (po.purchase_order_id = pp.purchase_order_id)
                             WHERE pp.product_id = {$rowBatchNo['product_id']}
                             {$appendSqlPur}
                             AND pp.batch_no = '{$rowBatchNo['batch_no']}') as damage_qty

                            ,(SELECT SUM(inItm.qty) FROM invoice_item inItm
                            LEFT JOIN (`invoice` inv) ON (inv.invoice_id = inItm.invoice_id)
                            WHERE inItm.record_id = {$rowBatchNo['product_id']}
                            AND inItm.not_add_in_stock != 1
                            AND inv.status = 'Paid'
                            AND inv.invoice_type = 'POS'
                            AND inItm.batch_no = '{$rowBatchNo['batch_no']}'
                            {$appendSqlInv}
                            ) as product_qty_sold

                            ,(SELECT  SUM(CASE 
                                        WHEN sth.pack_size REGEXP '^[+-]?[0-9]+$'
                                        THEN sth.qty * sth.pack_size
                                        ELSE sth.qty END)
                              FROM stock_transfer_history sth
                              LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
                              WHERE sth.product_id = {$rowBatchNo['product_id']}
                              AND sth.batch_no = '{$rowBatchNo['batch_no']}'
                              AND st.status = 'Delivered'
                              {$appendSqlStk}
                            ) as product_qty_sold_from_stock

                            ,(SELECT  SUM(CASE 
                                        WHEN sth.pack_size REGEXP '^[+-]?[0-9]+$'
                                        THEN sth.qty * sth.pack_size
                                        ELSE sth.qty END)
                              FROM stock_transfer_history sth
                              LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
                              WHERE sth.product_id = {$rowBatchNo['product_id']}
                              AND sth.batch_no = '{$rowBatchNo['batch_no']}'
                              AND st.status = 'Delivered'
                              {$appendSqlStk2}
                            ) as product_qty_sold_to_stock

                            ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                            LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                            LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                            WHERE ini.record_id = {$rowBatchNo['product_id']}
                              AND srh.status = 'Approved'
                              AND ini.batch_no = '{$rowBatchNo['batch_no']}'
                              {$appendSqlInv}
                            ) as sales_return_qty
                        ";
                        $resultothersite = $db->sql_query($SQLOthersite);
                        $rowothersite = $db->sql_fetchrow($resultothersite);

                        $stock = $rowothersite['product_qty_purchased'] - $rowothersite['product_qty_sold_from_stock'] + $rowothersite['product_qty_sold_to_stock'] - $rowothersite['product_qty_sold'] + $rowothersite['sales_return_qty'] - $rowothersite['damage_qty'];

                        $OverallStock   = $stock;
                        $PurchasedQty   = $rowothersite['product_qty_purchased'];
                        $SoldQty        = $rowothersite['product_qty_sold'] + $rowothersite['product_qty_sold_from_stock'];
                        $SalesReturnQty = $rowothersite['sales_return_qty'];
                        $DamagedQty     = $rowothersite['damage_qty'];
                        $expiry_date    = $fn->getCPDate($rowBatchNo['expiry_date'], 'd-m-Y');

                        $fa = array();
                        $fa['product_id']         = $rowBatchNo['product_id'];
                        $fa['po_product_id']      = $rowBatchNo['po_product_id'];
                        $fa['purchase_order_id']  = $rowBatchNo['purchase_order_id'];
                        $fa['batch_no']           = $rowBatchNo['batch_no'];
                        $fa['current_stock']      = $OverallStock;
                        $fa['site_id']            = $rowSite['site_id'];

                        $fa  = $fn->addCreationDetailsToFieldsArray($fa, 'inventory_batchwise_stock');
                        $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'inventory_batchwise_stock');
                        $result = $db->sql_query($SQL);

                        print("Purchase Order: ".$rowBatchNo['purchase_order_id'].' - '.$rowBatchNo['po_product_id'].' - '.$rowBatchNo['batch_no'].' - '.$expiry_date.' - '.$OverallStock."<br/>");
                    }

                    if($rowBatchNo['StockFrom'] == "Stock Transfer") {
                        $SQLPOCheck  = "
                        SELECT  ibs.po_product_id
                        FROM inventory_batchwise_stock ibs
                        WHERE ibs.product_id = {$rowBatchNo['product_id']}
                        AND ibs.batch_no     = '{$rowBatchNo['batch_no']}'
                        AND ibs.site_id      = {$rowSite['site_id']}
                        ";
                        $resultPOCheck  = $db->sql_query($SQLPOCheck);
                        $numRowsPOCheck = $db->sql_numrows($resultPOCheck);
                        
                        if($numRowsPOCheck == 0) {
                            $appendSqlOrd  = "AND o.site_id = {$rowSite['site_id']}";
                            $appendSqlPur  = "AND po.site_id = {$rowSite['site_id']}";
                            $appendSqlInv  = "AND inv.site_id = {$rowSite['site_id']}";
                            $appendSqlStk  = "AND st.from_location = '{$rowSite['site_id']}'";
                            $appendSqlStk2 = "AND st.to_location = '{$rowSite['site_id']}'";

                            $SQLOthersite = "
                            SELECT
                                (SELECT SUM(CASE 
                                            WHEN pp.pack_size REGEXP '^[+-]?[0-9]+$'
                                            THEN pp.qty * pp.pack_size
                                            ELSE pp.qty END) AS purchased_qty
                                FROM po_product pp
                                LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                                WHERE pp.product_id = {$rowBatchNo['product_id']}
                                AND po.status != 'Cancelled'
                                AND pp.batch_no = '{$rowBatchNo['batch_no']}'
                                {$appendSqlPur}
                                ) as product_qty_purchased

                               ,(SELECT SUM(damage_qty) FROM po_product pp
                                 LEFT JOIN purchase_order po ON (po.purchase_order_id = pp.purchase_order_id)
                                 WHERE pp.product_id = {$rowBatchNo['product_id']}
                                 {$appendSqlPur}
                                 AND pp.batch_no = '{$rowBatchNo['batch_no']}') as damage_qty

                                ,(SELECT SUM(inItm.qty) FROM invoice_item inItm
                                LEFT JOIN (`invoice` inv) ON (inv.invoice_id = inItm.invoice_id)
                                WHERE inItm.record_id = {$rowBatchNo['product_id']}
                                AND inItm.not_add_in_stock != 1
                                AND inv.status = 'Paid'
                                AND inv.invoice_type = 'POS'
                                AND inItm.batch_no = '{$rowBatchNo['batch_no']}'
                                {$appendSqlInv}
                                ) as product_qty_sold

                                ,(SELECT  SUM(CASE 
                                            WHEN sth.pack_size REGEXP '^[+-]?[0-9]+$'
                                            THEN sth.qty * sth.pack_size
                                            ELSE sth.qty END)
                                  FROM stock_transfer_history sth
                                  LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
                                  WHERE sth.product_id = {$rowBatchNo['product_id']}
                                  AND sth.batch_no = '{$rowBatchNo['batch_no']}'
                                  AND st.status = 'Delivered'
                                  {$appendSqlStk}
                                ) as product_qty_sold_from_stock

                                ,(SELECT  SUM(CASE 
                                            WHEN sth.pack_size REGEXP '^[+-]?[0-9]+$'
                                            THEN sth.qty * sth.pack_size
                                            ELSE sth.qty END)
                                  FROM stock_transfer_history sth
                                  LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
                                  WHERE sth.product_id = {$rowBatchNo['product_id']}
                                  AND sth.batch_no = '{$rowBatchNo['batch_no']}'
                                  AND st.status = 'Delivered'
                                  {$appendSqlStk2}
                                ) as product_qty_sold_to_stock

                                ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                                LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                                LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                                WHERE ini.record_id = {$rowBatchNo['product_id']}
                                  AND srh.status = 'Approved'
                                  AND ini.batch_no = '{$rowBatchNo['batch_no']}'
                                  {$appendSqlInv}
                                ) as sales_return_qty
                            ";
                            $resultothersite = $db->sql_query($SQLOthersite);
                            $rowothersite = $db->sql_fetchrow($resultothersite);

                            $stock = $rowothersite['product_qty_purchased'] - $rowothersite['product_qty_sold_from_stock'] + $rowothersite['product_qty_sold_to_stock'] - $rowothersite['product_qty_sold'] + $rowothersite['sales_return_qty'] - $rowothersite['damage_qty'];

                            $OverallStock   = $stock;
                            $PurchasedQty   = $rowothersite['product_qty_purchased'];
                            $SoldQty        = $rowothersite['product_qty_sold'] + $rowothersite['product_qty_sold_from_stock'];
                            $SalesReturnQty = $rowothersite['sales_return_qty'];
                            $DamagedQty     = $rowothersite['damage_qty'];
                            $expiry_date    = $fn->getCPDate($rowBatchNo['expiry_date'], 'd-m-Y');

                            $fa = array();
                            $fa['product_id']        = $rowBatchNo['product_id'];
                            $fa['po_product_id']     = $rowBatchNo['po_product_id'];
                            $fa['purchase_order_id'] = $rowBatchNo['purchase_order_id'];
                            $fa['batch_no']          = $rowBatchNo['batch_no'];
                            $fa['current_stock']     = $OverallStock;
                            $fa['site_id']           = $rowSite['site_id'];

                            $fa  = $fn->addCreationDetailsToFieldsArray($fa, 'inventory_batchwise_stock');
                            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'inventory_batchwise_stock');
                            $result = $db->sql_query($SQL);

                            print("Stock Transfer: ".$rowBatchNo['purchase_order_id'].' - '.$rowBatchNo['po_product_id'].' - '.$rowBatchNo['batch_no'].' - '.$expiry_date.' - '.$OverallStock."<br/>");
                        }
                    }
                }
            }
        }
    }

    /**
     *
     */
    function getBatchProductCountCheck($product_id, $site_id){
        $cpCfg   = Zend_Registry::get('cpCfg');
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil  = Zend_Registry::get('dbUtil');

        $thForSiteId  = "";
        $tdForSiteId  = "";
        $thForSiteId  = "";
        $rows         = "";
        $sqlAppend    = "";
        $sqlAppendSt  = "";
        $appendSqlStk2 = "";
        $stockTransferSQLForMultiSite = "";
        if($cpCfg['cp.hasMultiUniqueSites']  == true){
            $sqlAppend = "AND po.site_id = {$site_id}";
            $sqlAppendSt = "AND st.to_location = {$site_id}";

            $stockTransferSQLForMultiSite = "
            UNION
            SELECT  sth.batch_no AS batch_no
                   ,sth.product_id
                   ,pp.expiry_date
            FROM stock_transfer_history sth
            LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
            LEFT JOIN po_product pp ON (pp.po_product_id = sth.po_product_id)
            WHERE sth.product_id = {$product_id}
            AND st.status = 'Delivered'
              {$sqlAppendSt}
            GROUP BY batch_no
            ";
        }

        $SQLsitedetail="
        SELECT site_id
               ,title
        FROM site
        WHERE site_id = {$site_id}
        ";
        $resultsitedetail = $db->sql_query($SQLsitedetail);
        $rowsitedetail = $db->sql_fetchrow($resultsitedetail);

        $SQLBatchNo = "
        SELECT  pp.batch_no AS batch_no
               ,pp.product_id
               ,pp.expiry_date
        FROM po_product pp
        LEFT JOIN purchase_order po ON (po.purchase_order_id = pp.purchase_order_id)
        LEFT JOIN product p ON (p.product_id = pp.product_id)
        WHERE pp.product_id = {$product_id}
          {$sqlAppend}
        AND po.status != 'Cancelled'
        GROUP BY pp.batch_no
        {$stockTransferSQLForMultiSite}
        ";
        $resultBatchNo  = $db->sql_query($SQLBatchNo);
        $numRowsBatchNo = $db->sql_numrows($resultBatchNo);

        $count = 0;
        while ($rowBatchNo    = $db->sql_fetchrow($resultBatchNo)) {
            $appendSqlOrd = "AND o.site_id = {$site_id}";
            $appendSqlPur = "AND po.site_id = {$site_id}";
            $appendSqlInv = "AND inv.site_id = {$site_id}";
            $appendSqlStk = "AND st.from_location = '{$site_id}'";
            $appendSqlStk2 = "AND st.to_location = '{$site_id}'";

            $SQLOthersite = "
            SELECT
                (SELECT SUM(qty) FROM po_product pp
                 LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                 WHERE pp.product_id = {$rowBatchNo['product_id']}
                 AND po.status != 'Cancelled'
                 {$appendSqlPur}
                 AND pp.batch_no = '{$rowBatchNo['batch_no']}') as product_qty_purchased

               ,(SELECT SUM(damage_qty) FROM po_product pp
                 LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                 WHERE pp.product_id = {$rowBatchNo['product_id']}
                 {$appendSqlPur}
                 AND pp.batch_no = '{$rowBatchNo['batch_no']}') as damage_qty
                 
                ,(SELECT SUM(inItm.qty) FROM invoice_item inItm
                LEFT JOIN (`invoice` inv) ON (inv.invoice_id = inItm.invoice_id)
                WHERE inItm.record_id = {$rowBatchNo['product_id']}
                AND inItm.not_add_in_stock != 1
                AND inv.status = 'Paid'
                AND inv.invoice_type = 'POS'
                AND inItm.batch_no = '{$rowBatchNo['batch_no']}'
                {$appendSqlInv}
                ) as product_qty_sold

                ,(SELECT SUM(CASE 
                            WHEN sth.pack_size REGEXP '^[+-]?[0-9]+$'
                            THEN sth.qty * sth.pack_size
                            ELSE sth.qty END)
                  FROM stock_transfer_history sth
                  LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
                  WHERE sth.product_id = {$rowBatchNo['product_id']}
                  AND sth.batch_no = '{$rowBatchNo['batch_no']}'
                  AND st.status = 'Delivered'
                  {$appendSqlStk}
                ) as product_qty_sold_from_stock

                ,(SELECT SUM(CASE 
                            WHEN sth.pack_size REGEXP '^[+-]?[0-9]+$'
                            THEN sth.qty * sth.pack_size
                            ELSE sth.qty END)
                  FROM stock_transfer_history sth
                  LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
                  WHERE sth.product_id = {$rowBatchNo['product_id']}
                  AND sth.batch_no = '{$rowBatchNo['batch_no']}'
                  AND st.status = 'Delivered'
                  {$appendSqlStk2}
                ) as product_qty_sold_to_stock

                ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                WHERE ini.record_id = {$rowBatchNo['product_id']}
                  AND srh.status = 'Approved'
                  AND ini.batch_no = '{$rowBatchNo['batch_no']}'
                  {$appendSqlInv}
                ) as sales_return_qty
            ";
            $resultothersite = $db->sql_query($SQLOthersite);
            $rowothersite = $db->sql_fetchrow($resultothersite);

            $stock = $rowothersite['product_qty_purchased'] - $rowothersite['product_qty_sold_from_stock'] + $rowothersite['product_qty_sold_to_stock'] - $rowothersite['product_qty_sold'] + $rowothersite['sales_return_qty'] - $rowothersite['damage_qty'];

            if($stock > 0) {
                $count++;
            }
        }

        print $count;
    }

    /**
     *
     */
    function getCreateUpdateChangedStockRecord() {
        $cpCfg  = Zend_Registry::get('cpCfg');
        $fn     = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db     = Zend_Registry::get('db');

        $batch_no      = $fn->getReqParam('batch_no');
        $product_id    = $fn->getReqParam('product_id');
        $inventory_id  = $fn->getReqParam('inventory_id');
        $adjust_stock  = $fn->getReqParam('adjust_stock');
        $po_product_id = $fn->getReqParam('po_product_id');
        $site_id       = $fn->getReqParam('site_id');

        $appendSqlSite  = "";
        $siteIdForField = "";
        if($site_id != "") { 
            $appendSqlSite  = "AND site_id = {$site_id}";
            $siteIdForField = $site_id;
        }

        $SQLAdjustStock = "
        SELECT adjust_stock
              ,current_stock
              ,inventory_batchwise_stock_id
        FROM inventory_batchwise_stock
        WHERE product_id  = '{$product_id}'
        AND po_product_id = '{$po_product_id}'
        {$appendSqlSite}
        ";
        $resultAdjustStock  = $db->sql_query($SQLAdjustStock);
        $numRowsAdjustStock = $db->sql_numrows($resultAdjustStock);
        $rowAdjustStock = $db->sql_fetchrow($resultAdjustStock);

        /*if($adjust_stock < 0) {
            if($rowAdjustStock['adjust_stock'] < 0) {
                $AdjustStock = abs($adjust_stock - $rowAdjustStock['adjust_stock']);
                if($rowAdjustStock['adjust_stock'] > $adjust_stock) {
                    $AdjustStock = -$AdjustStock;
                } else {
                    $AdjustStock = $AdjustStock;
                }
            } else {
                if($rowAdjustStock['adjust_stock'] > 0) {
                    $AdjustStock = $adjust_stock - $rowAdjustStock['adjust_stock'];
                } else {
                    $AdjustStock = $adjust_stock + $rowAdjustStock['adjust_stock'];
                }
            }
        } else {
            $AdjustStock = $adjust_stock - $rowAdjustStock['adjust_stock'];
        }*/

        $stock = $rowAdjustStock['current_stock'] + $adjust_stock;

        $fa = array();
        $fa['adjust_stock']  = $adjust_stock;
        $fa['current_stock'] = $stock;
        $fa  = $fn->addModificationDetailsToFieldsArray($fa, 'inventory_batchwise_stock');
        $whereCondition = "WHERE inventory_batchwise_stock_id = {$rowAdjustStock['inventory_batchwise_stock_id']}";
        $SQL = $dbUtil->getUpdateSQLStringFromArray($fa, 'inventory_batchwise_stock', $whereCondition);
        $result = $db->sql_query($SQL);

        $SQLUpdateProduct = "
        UPDATE product SET qty_in_stock{$siteIdForField} = IFNULL(qty_in_stock{$siteIdForField}, 0)+{$adjust_stock}
        WHERE product_id = '{$product_id}'
        ";
        $resultUpdateProduct  = $db->sql_query($SQLUpdateProduct);
        
        $SQLUpdateInventory = "
        UPDATE inventory SET actual_stock{$siteIdForField} = IFNULL(actual_stock{$siteIdForField}, 0)+{$adjust_stock}
        WHERE product_id = '{$product_id}'
        ";
        $resultUpdateInventory  = $db->sql_query($SQLUpdateInventory);

        $fa2 = array();
        $fa2['product_id']    = $product_id;
        $fa2['batch_no']      = $batch_no;
        $fa2['inventory_id']  = $inventory_id;
        $fa2['po_product_id'] = $po_product_id;
        $fa2['adjust_stock']  = $adjust_stock;
        $fa2['inventory_batchwise_stock_id'] = $rowAdjustStock['inventory_batchwise_stock_id'];
        $fa2    = $fn->addCreationDetailsToFieldsArray($fa2, 'adjust_stock_log');
        $SQLLog = $dbUtil->getInsertSQLStringFromArray($fa2, 'adjust_stock_log');
        $resultLog = $db->sql_query($SQLLog);

        $invRec = $fn->getRecordRowByID('inventory', 'inventory_id', $inventory_id);

        $SQLMS = "
        SELECT ms.*
        FROM manual_stock ms
        WHERE ms.product_id = {$product_id}
          AND ms.site_id = {$site_id}
        ORDER BY ms.manual_stock_id DESC
        LIMIT 0,1
        ";
        $resultMS  = $db->sql_query($SQLMS);
        $numRowsMS = $db->sql_numrows($resultMS);
        $rowMS     = $db->sql_fetchrow($resultMS);

        if($numRowsMS > 0) {
            $fa3 = array();
            $fa3['stock_difference'] = $invRec['actual_stock'.$siteIdForField];
            $fa3  = $fn->addModificationDetailsToFieldsArray($fa3, 'manual_stock');
            $whereCondition = "WHERE manual_stock_id = {$rowMS['manual_stock_id']}";
            $SQLUpdate = $dbUtil->getUpdateSQLStringFromArray($fa3, 'manual_stock', $whereCondition);
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        return $invRec['actual_stock'.$siteIdForField];
    }

    /**
     *
     */
    function getCreateUpdateExpiredStockRecord() {
        $cpCfg  = Zend_Registry::get('cpCfg');
        $fn     = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db     = Zend_Registry::get('db');

        $product_id    = $fn->getReqParam('product_id');
        $inventory_id  = $fn->getReqParam('inventory_id');
        $expired_stock = $fn->getReqParam('expired_stock');
        $site_id       = $fn->getReqParam('site_id');

        $appendSqlSite  = "";
        $siteIdForField = "";
        if($site_id != "") { 
            $appendSqlSite  = "AND site_id = {$site_id}";
            $siteIdForField = $site_id;
        }

        $SQLInventory = "
        SELECT actual_stock{$siteIdForField}
        FROM inventory
        WHERE product_id = '{$product_id}'
        AND inventory_id = '{$inventory_id}'
        ";
        $resultInventory  = $db->sql_query($SQLInventory);
        $numRowsInventory = $db->sql_numrows($resultInventory);
        $rowInventory     = $db->sql_fetchrow($resultInventory);

        $stock = $rowInventory['actual_stock'.$siteIdForField] - $expired_stock;

        $SQLUpdateProduct = "
        UPDATE product SET qty_in_stock{$siteIdForField} = IFNULL(qty_in_stock{$siteIdForField}, 0)-{$expired_stock}
        WHERE product_id = '{$product_id}'
        ";
        $resultUpdateProduct  = $db->sql_query($SQLUpdateProduct);
        
        $SQLUpdateInventory = "
        UPDATE inventory SET actual_stock{$siteIdForField} = IFNULL(actual_stock{$siteIdForField}, 0)-{$expired_stock}
        WHERE product_id = '{$product_id}'
        ";
        $resultUpdateInventory  = $db->sql_query($SQLUpdateInventory);

        $fa2 = array();
        $fa2['product_id']    = $product_id;
        $fa2['inventory_id']  = $inventory_id;
        $fa2['site_id']       = $site_id;
        $fa2['expired_stock'] = $expired_stock;
        $fa2    = $fn->addCreationDetailsToFieldsArray($fa2, 'expired_stock_log');
        $SQLLog = $dbUtil->getInsertSQLStringFromArray($fa2, 'expired_stock_log');
        $resultLog = $db->sql_query($SQLLog);

        $invRec = $fn->getRecordRowByID('inventory', 'inventory_id', $inventory_id);

        $SQLMS = "
        SELECT ms.*
        FROM manual_stock ms
        WHERE ms.product_id = {$product_id}
          AND ms.site_id = {$site_id}
        ORDER BY ms.manual_stock_id DESC
        LIMIT 0,1
        ";
        $resultMS  = $db->sql_query($SQLMS);
        $numRowsMS = $db->sql_numrows($resultMS);
        $rowMS     = $db->sql_fetchrow($resultMS);

        if($numRowsMS > 0) {
            $fa3 = array();
            $fa3['stock_difference'] = $invRec['actual_stock'.$siteIdForField];
            $fa3  = $fn->addModificationDetailsToFieldsArray($fa3, 'manual_stock');
            $whereCondition = "WHERE manual_stock_id = {$rowMS['manual_stock_id']}";
            $SQLUpdate = $dbUtil->getUpdateSQLStringFromArray($fa3, 'manual_stock', $whereCondition);
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        return $invRec['actual_stock'.$siteIdForField];
    }

    /**
     *
     */
    function getUpdateCurrentStockInventoryBatchRecord() {
        $cpCfg  = Zend_Registry::get('cpCfg');
        $fn     = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db     = Zend_Registry::get('db');

        $inventory_batchwise_stock_id = $fn->getReqParam('inventory_batchwise_stock_id');
        $inventory_id  = $fn->getReqParam('inventory_id');
        $site_id       = $fn->getReqParam('site_id');
        $product_id    = $fn->getReqParam('product_id');
        $current_stock = $fn->getReqParam('current_stock');

        $appendSqlSite  = "";
        $siteIdForField = "";
        if($site_id != "") { 
            $appendSqlSite  = "AND site_id = {$site_id}";
            $siteIdForField = $site_id;
        }

        $invBatchRec = $fn->getRecordRowByID('inventory_batchwise_stock', 'inventory_batchwise_stock_id', $inventory_batchwise_stock_id);

        $SQLStockCheck = "
        SELECT SUM(current_stock) AS stockOverall
        FROM inventory_batchwise_stock
        WHERE product_id = {$product_id}
        {$appendSqlSite}
        ";
        $resultStockCheck = $db->sql_query($SQLStockCheck);
        $rowStockCheck    = $db->sql_fetchrow($resultStockCheck);

        $fa2 = array();
        $fa2['product_id']                   = $invBatchRec['product_id'];
        $fa2['batch_no']                     = $invBatchRec['batch_no'];
        $fa2['inventory_id']                 = $inventory_id;
        $fa2['po_product_id']                = $invBatchRec['po_product_id'];
        $fa2['adjust_stock']                 = $invBatchRec['current_stock'];
        $fa2['current_stock']                = $rowStockCheck['stockOverall'];
        $fa2['actual_stock']                 = $current_stock;
        $fa2['inventory_batchwise_stock_id'] = $invBatchRec['inventory_batchwise_stock_id'];
        $fa2       = $fn->addCreationDetailsToFieldsArray($fa2, 'adjust_stock_log');
        $SQLLog    = $dbUtil->getInsertSQLStringFromArray($fa2, 'adjust_stock_log');
        $resultLog = $db->sql_query($SQLLog);

        $fa3 = array();
        $fa3['current_stock'] = $current_stock;
        $fa3 = $fn->addModificationDetailsToFieldsArray($fa3, 'inventory_batchwise_stock');
        $whereCondition                  = "WHERE inventory_batchwise_stock_id = {$inventory_batchwise_stock_id}";
        $SQLUpdateInventoryBatchStock    = $dbUtil->getUpdateSQLStringFromArray($fa3, 'inventory_batchwise_stock', $whereCondition);
        $resultUpdateInventoryBatchStock = $db->sql_query($SQLUpdateInventoryBatchStock);

        $appendSqlSiteStock = '';
        if($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlSiteStock = "AND site_id = {$site_id}";
        }

        $SQLStock = "
        SELECT SUM(current_stock) AS stockOverall
        FROM inventory_batchwise_stock
        WHERE product_id = {$product_id}
        {$appendSqlSiteStock}
        ";
        $resultStock = $db->sql_query($SQLStock);
        $rowStock    = $db->sql_fetchrow($resultStock);
        $stock       = $rowStock['stockOverall'];

        $SQLUpdateProduct = "
        UPDATE product SET qty_in_stock{$siteIdForField} = {$stock}
        WHERE product_id = '{$product_id}'
        ";
        $resultUpdateProduct  = $db->sql_query($SQLUpdateProduct);
        
        $SQLUpdateInventory = "
        UPDATE inventory SET actual_stock{$siteIdForField} = {$stock}
        WHERE product_id = '{$product_id}'
        ";
        $resultUpdateInventory  = $db->sql_query($SQLUpdateInventory);

        $invRec = $fn->getRecordRowByID('inventory', 'inventory_id', $inventory_id);

        $SQLMS = "
        SELECT ms.*
        FROM manual_stock ms
        WHERE ms.product_id = {$product_id}
          AND ms.site_id = {$site_id}
        ORDER BY ms.manual_stock_id DESC
        LIMIT 0,1
        ";
        $resultMS  = $db->sql_query($SQLMS);
        $numRowsMS = $db->sql_numrows($resultMS);
        $rowMS     = $db->sql_fetchrow($resultMS);

        if($numRowsMS > 0) {
            $fa5 = array();
            $fa5['stock_difference'] = $invRec['actual_stock'.$siteIdForField];
            $fa5 = $fn->addModificationDetailsToFieldsArray($fa5, 'manual_stock');
            $whereCondition = "WHERE manual_stock_id = {$rowMS['manual_stock_id']}";
            $SQLUpdate      = $dbUtil->getUpdateSQLStringFromArray($fa5, 'manual_stock', $whereCondition);
            $resultUpdate   = $db->sql_query($SQLUpdate);
        }

        return $invRec['actual_stock'.$siteIdForField];
    }

    /**
     *
     */
    function getUpdateStockInInventoryAndProduct() {
        $db     = Zend_Registry::get('db');
        $fn     = Zend_Registry::get('fn');
        $tv     = Zend_Registry::get('tv');
        $cpCfg  = Zend_Registry::get('cpCfg');
        $dbUtil = Zend_Registry::get('dbUtil');
        
        $site_id = $fn->getReqParam('site_id');

        $appendSqlSite  = "";
        $siteIdForField = "";
        if($site_id != ""){ 
            $appendSqlSite  = "AND ibs.site_id = {$site_id}";
            $siteIdForField = $site_id;
        }

        $SQLBatchWiseStock = "
        SELECT SUM(ibs.current_stock) AS current_stock
              ,ibs.product_id
        FROM inventory_batchwise_stock ibs
        LEFT JOIN (purchase_order po) ON (po.purchase_order_id = ibs.purchase_order_id)
        WHERE po.status != 'Cancelled'
          {$appendSqlSite}
        GROUP BY ibs.product_id
        ";
        $resultBatchWiseStock  = $db->sql_query($SQLBatchWiseStock);
        $numRowsBatchWiseStock = $db->sql_numrows($resultBatchWiseStock);
        
        while($BatchWiseStock = $db->sql_fetchrow($resultBatchWiseStock)) {
            $SQLUpdateProduct = "
            UPDATE product SET qty_in_stock{$siteIdForField} = '{$BatchWiseStock['current_stock']}'
            WHERE product_id = '{$BatchWiseStock['product_id']}'
            ";
            $resultUpdateProduct  = $db->sql_query($SQLUpdateProduct);

            $SQLUpdateInventory = "
            UPDATE inventory SET actual_stock{$siteIdForField} = '{$BatchWiseStock['current_stock']}'
            WHERE product_id = '{$BatchWiseStock['product_id']}'
            ";
            $resultUpdateInventory  = $db->sql_query($SQLUpdateInventory);
        }
    }

    /**
     *
     */
    function getCreateManualStockRecord() {
        $cpCfg  = Zend_Registry::get('cpCfg');
        $fn     = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db     = Zend_Registry::get('db');

        $product_id    = $fn->getReqParam('product_id');
        $inventory_id  = $fn->getReqParam('inventory_id');
        $manual_stock  = $fn->getReqParam('manual_stock');
        $actual_stock  = $fn->getReqParam('actual_stock');
        $site_id  = $fn->getReqParam('site_id');
        $currentDate = date("Y-m-d");

        $rec = $fn->getRecordByCondition("manual_stock", "date = '{$currentDate}' AND product_id = '{$product_id}'");
        $invRec = $fn->getRecordRowByID('inventory', 'inventory_id', $inventory_id);

        if($rec['manual_stock_id'] != ''){
            $fa = array();
            $fa['stock']  = $manual_stock;
            $fa['time']   = date("H:i:s");
            $fa['stock_difference'] = $invRec['actual_stock'.$site_id];
            $fa  = $fn->addModificationDetailsToFieldsArray($fa, 'manual_stock');
            $whereCondition = "WHERE manual_stock_id = {$rec['manual_stock_id']}";
            $SQLUpdate = $dbUtil->getUpdateSQLStringFromArray($fa, 'manual_stock', $whereCondition);
            $resultUpdate = $db->sql_query($SQLUpdate);
        } else {
            $fa = array();
            $fa['stock']            = $manual_stock;
            $fa['actual_stock']     = $invRec['actual_stock'.$site_id];
            $fa['stock_difference'] = $invRec['actual_stock'.$site_id];
            $fa['date']             = $currentDate;
            $fa['time']             = date("H:i:s");
            $fa['product_id']       = $product_id;
            $fa['site_id']          = $site_id;
            $fa                     = $fn->addCreationDetailsToFieldsArray($fa, 'manual_stock');
            $SQLInsert    = $dbUtil->getInsertSQLStringFromArray($fa, 'manual_stock');
            $resultInsert = $db->sql_query($SQLInsert);
        }
    }

    /**
     *
     */
    function getUpdateMedPurchasedQty() {
        $db     = Zend_Registry::get('db');
        $fn     = Zend_Registry::get('fn');
        $tv     = Zend_Registry::get('tv');
        $cpCfg  = Zend_Registry::get('cpCfg');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        
        $med_purch_qty = $fn->getReqParam('med_purch_qty');
        $inventory_id = $fn->getReqParam('inventory_id');

        $SQLUpdateInventory = "
        UPDATE inventory SET med_purch_qty{$cpSiteIdSession} = '{$med_purch_qty}'
        WHERE inventory_id = '{$inventory_id}'
        ";
        $resultUpdateInventory  = $db->sql_query($SQLUpdateInventory);
    }

    /**
     *
     */
    function getUpdateExpectedQty() {
        $db     = Zend_Registry::get('db');
        $fn     = Zend_Registry::get('fn');
        $tv     = Zend_Registry::get('tv');
        $cpCfg  = Zend_Registry::get('cpCfg');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        
        $exp_qty      = $fn->getReqParam('exp_qty');
        $inventory_id = $fn->getReqParam('inventory_id');

        $SQLUpdateInventory = "
        UPDATE inventory SET exp_qty{$cpSiteIdSession} = '{$exp_qty}'
        WHERE inventory_id = '{$inventory_id}'
        ";
        $resultUpdateInventory  = $db->sql_query($SQLUpdateInventory);
    }

    /**
     *
     */
    function getFlagUnflagAllRecords() {
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $modulesArr = Zend_Registry::get('modulesArr');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cpUtil = Zend_Registry::get('cpUtil');
        $fn = Zend_Registry::get('fn');

        $cpSiteIdSession     = $fn->getSessionParam('cp_site_id');
        $category_id         = $fn->getReqParam('category_id');
        $minimum_order_level = $fn->getReqParam('minimum_order_level');
        $expiry_date         = $fn->getReqParam('expiry_date');
        $special_search      = $fn->getReqParam('special_search');
        $keyword             = $fn->getReqParam('keyword');

        $appendSqlStk   = "";
        $siteIdForField = "";
        if($cpCfg['cp.hasMultiUniqueSites']  == true){
            $siteId         = $fn->getSessionParam('cp_site_id');
            $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
            $siteIdForField = $siteId;
            $appendSqlStk   = " AND ms.site_id = '{$siteId}'";
        }

        $appendSqlSD   = "";
        if ($special_search == "Stock Difference") {
            //$searchVar->sqlHavingVar[] = "stock_actual != manual_stock";
            $appendSqlSD .= " AND i.product_id IN (
                SELECT DISTINCT ms.product_id 
                FROM manual_stock ms 
                WHERE ms.product_id = i.product_id 
                  AND ms.site_id    = {$cpSiteIdSession}
                  AND ms.product_id NOT IN (332, 338, 785, 861, 1312)
                  AND ms.date       = '{$current_date}'
                  AND ms.stock     != ms.stock_difference
                ORDER BY ms.manual_stock_id DESC
              )
            ";
            $appendSqlSD .= " AND (p.exclude_stock_difference = ''
                                        OR p.exclude_stock_difference IS NULL
                                        OR p.exclude_stock_difference = 0)";
        }

        if ($special_search == "Sales Wise SD") {
            $start_date = date('Y-m-d', strtotime('-3 days'));
            $end_date = date('Y-m-d');
            $appendSqlSD .= " AND i.product_id IN (
                SELECT DISTINCT ii.record_id 
                FROM invoice_item ii
                LEFT JOIN (`invoice` inv) ON (inv.invoice_id = ii.invoice_id)
                WHERE ii.record_id = i.product_id 
                  AND inv.site_id    = {$cpSiteIdSession}
                  AND inv.status != 'Cancelled'
                  AND (inv.invoice_date >= '{$start_date}' AND inv.invoice_date <= '{$end_date}')
              )
            ";
            $appendSqlSD .= " AND i.product_id NOT IN (
                SELECT DISTINCT ms.product_id 
                FROM manual_stock ms 
                WHERE ms.product_id = i.product_id
                  AND ms.site_id    = {$cpSiteIdSession}
              )
            ";

            $appendSqlSD .= " AND (p.exclude_stock_difference = ''
                                        OR p.exclude_stock_difference IS NULL
                                        OR p.exclude_stock_difference = 0)";
        }

        if ($special_search == "Past SD") {
            //$searchVar->sqlHavingVar[] = "pre_stock_actual != pre_manual_stock";
            $appendSqlSD .= " AND i.product_id IN (
                SELECT DISTINCT ms.product_id 
                FROM manual_stock ms 
                WHERE ms.product_id = i.product_id 
                  AND ms.site_id    = {$cpSiteIdSession}
                  AND ms.product_id NOT IN (332, 338, 785, 861, 1312)
                  AND ms.date      != '{$current_date}'
                  AND ms.stock     != ms.stock_difference
                ORDER BY ms.manual_stock_id DESC
              )
            ";

            $appendSqlSD .= " AND (p.exclude_stock_difference = ''
                                        OR p.exclude_stock_difference IS NULL
                                        OR p.exclude_stock_difference = 0)";
        }

        if ($category_id != '' ) {
            $appendSqlSD .= " AND p.category_id = '{$category_id}'";
        }

        if ($minimum_order_level != '') {                                        
            $appendSqlSD .= " AND p.not_add_in_stock != 1";
            $appendSqlSD .= " AND i.minimum_order_level{$siteId} > 0 HAVING stock <= i.minimum_order_level{$siteId}";
        }

        if ($expiry_date == 'Expiry Date < 100') {
            $appendSqlSD .= " AND (i.product_id IN (SELECT ibs.product_id
                                            FROM inventory_batchwise_stock ibs
                                            LEFT JOIN po_product po ON (po.po_product_id = ibs.po_product_id)
                                            WHERE ibs.current_stock  - IFNULL(po.return_qty_ns, 0)> 0
                                            AND ibs.site_id = {$cpSiteIdSession}
                                            AND DATEDIFF(po.expiry_date, Now()) < 100))";
        } else if($expiry_date == 'Expiry Date < 30'){
            $appendSqlSD .= " AND (i.product_id IN (SELECT ibs.product_id
                                            FROM inventory_batchwise_stock ibs
                                            LEFT JOIN po_product po ON (po.po_product_id = ibs.po_product_id)
                                            WHERE ibs.current_stock  - IFNULL(po.return_qty_ns, 0)> 0 AND ibs.site_id = {$cpSiteIdSession}
                                            AND DATEDIFF(po.expiry_date, Now()) < 30))";                
        } else if($expiry_date == 'Expiry Date < 120'){
            $appendSqlSD .= " AND (i.product_id IN (SELECT ibs.product_id
                                            FROM inventory_batchwise_stock ibs
                                            LEFT JOIN po_product po ON (po.po_product_id = ibs.po_product_id)
                                            WHERE ibs.current_stock  - IFNULL(po.return_qty_ns, 0)> 0
                                            AND ibs.site_id = {$cpSiteIdSession}
                                            AND DATEDIFF(po.expiry_date, Now()) < 120))";                
        } else if($expiry_date == 'Expiry Date < 60'){
            $appendSqlSD .= " AND (i.product_id IN (SELECT ibs.product_id
                                            FROM inventory_batchwise_stock ibs
                                            LEFT JOIN po_product po ON (po.po_product_id = ibs.po_product_id)
                                            WHERE ibs.current_stock  - IFNULL(po.return_qty_ns, 0)> 0
                                            AND ibs.site_id = {$cpSiteIdSession}
                                            AND DATEDIFF(po.expiry_date, Now()) < 60))";                
        }
           
        if ($tv['special_search'] == "Flagged") {
            $appendSqlSD .= " AND i.flag{$cpSiteIdSession} = 1";
        }

        if ($tv['special_search'] == "Rack Wise SD") {
            $appendSqlSD .= " AND ms.rake != ''";
        }

        $appendSqlKwrd = "";

        if ($keyword != "") {
            $appendSqlKwrd = " AND (
                p.title LIKE '{$keyword}%'
                || p.item_code LIKE '{$keyword}%'
                || i.inventory_code LIKE '%{$keyword}%'
            )";
        }

        $action     = $fn->getReqParam('action');
        $flagValue = 1;
        if ($action == 'unflagAll') {
            $flagValue = 0;
        }

        print $SQL = "
        SELECT i.*
              ,p.product_id AS productId
              ,p.title AS product_name
              ,p.item_code
              ,p.unit
              ,p.product_code
              ,p.price
              ,i.actual_stock{$siteIdForField} AS stock
              ,ms.rake
        FROM inventory i
        LEFT JOIN (product p) ON (p.product_id = i.product_id)
        LEFT JOIN (medicine_site ms) ON (i.product_id = ms.product_id)
        WHERE p.published = 1 
        {$appendSqlKwrd}
        {$appendSqlStk}
        {$appendSqlSD}
        ORDER BY ms.rake, p.title ASC, stock DESC
        ";
        $result = $db->sql_query($SQL);
        //============================================================================= //        
        while ($row = $db->sql_fetchrow($result)) {
            $updateSQL = "
            UPDATE inventory
            SET flag{$cpSiteIdSession} = {$flagValue}
            WHERE inventory_id = {$row['inventory_id']}
            ";
            $db->sql_query($updateSQL);
        }

    }

    /**
     *
     */
    function getFlagRecordByID() {
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $modulesArr = Zend_Registry::get('modulesArr');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cpUtil = Zend_Registry::get('cpUtil');
        $fn = Zend_Registry::get('fn');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $record_id    = $fn->getReqParam('record_id');
        $module       = $fn->getReqParam('room');
        $currentValue = $fn->getReqParam('currentValue');

        $color = $fn->getReqParam('color', 'red');
        $flag_fld = 'flag';
        if ($color != 'red') {
            $flag_fld = 'flag_' . $color;
        }

        $newValue     = $currentValue == 0 ? 1 : 0;
        $imageIcon    = $currentValue == 0 ? "flag_on_{$color}.png" : "flag_off.png";

        if (!is_numeric ($record_id)) {
            print "error:not a number";
            return;
        }

        //-----------------------------------------------------//
        $updateSQL = "
        UPDATE inventory
        SET flag{$cpSiteIdSession} = {$newValue}
        WHERE inventory_id = {$record_id}
        ";
        $db->sql_query($updateSQL);
        //-----------------------------------------------------//
        $text = "
        <a href='#'
           class='inv-list-flag'
           module='tradingin_inventory'
           record_id='{$record_id}'
           currentValue='{$newValue}'
           color='{$color}'>
            <img src='{$cpCfg['cp.commonImagesPathAlias']}icons/{$imageIcon}'>
        </a>
        ";
        return $text;

    }
}
