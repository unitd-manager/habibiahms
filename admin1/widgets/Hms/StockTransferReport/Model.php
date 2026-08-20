<?
class CPL_Admin_Widgets_Hms_StockTransferReport_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        $cpCfg = Zend_Registry::get('cpCfg');

        $SQL = "
        SELECT st.*
              ,p.title AS product_name
              ,pop.cost_price
              ,pop.gst
              ,pop.selling_price
              ,sth.qty
        FROM stock_transfer st
        LEFT JOIN (`stock_transfer_history` sth) ON (sth.stock_transfer_id = st.stock_transfer_id)
        LEFT JOIN (`po_product` pop) ON (pop.po_product_id = sth.po_product_id)
        LEFT JOIN (`product` p) ON (p.product_id = sth.product_id)
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'st';

        $start_date 	= $fn->getReqParam('start_date');
        $end_date   	= $fn->getReqParam('end_date');
        $monthVal       = $fn->getReqParam('month');
        $yearVal        = $fn->getReqParam('year');
        $site_id        = $fn->getReqParam('site_id');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        if ($start_date != '' && $end_date == '') {
            $searchVar->sqlSearchVar[] = "st.date >= '{$start_date}' AND st.date <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $searchVar->sqlSearchVar[] = "st.date >= '{$start_date}' AND st.date <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $searchVar->sqlSearchVar[] = "st.date >= '{$start_date}' AND st.date <= '{$end_date}'";
        } else if ($monthVal == '' && $yearVal == ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $end_date = $year . '-' . $month . '-' . '31';
            $searchVar->sqlSearchVar[] = "st.date >= '{$start_date}' AND st.date <= '{$end_date}'";
        }

        if ($monthVal != '') {
            $searchVar->sqlSearchVar[] = "DATE_FORMAT(st.date, '%m') = '{$monthVal}'" ;
        }

        $searchVar->sqlSearchVar[] = "st.transfer_type = 'External'";
        $searchVar->sqlSearchVar[] = "st.status != 'Cancelled'";
        $searchVar->sqlSearchVar[] = "(st.from_location = {$cpSiteIdSession} OR st.to_location = {$cpSiteIdSession})";


    }

    /**
     *
     * @param <type> $SQL
     * @return <type>
     */
    function getDataArray() {
        $ln = Zend_Registry::get('ln');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');

        $modelHelper = Zend_Registry::get('modelHelper');
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'hms_stockTransferReport');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }

    /**
     *
     */
    function getSqlForCount() {
        $fn    = Zend_Registry::get('fn');
        $db    = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $grandTotalAmount = 0;

        foreach($this->dataArray as $row){
            $grandTotalAmount += $row['qty'] * $row['cost_price'];
        }
        $grandTotalAmount = number_format(round($grandTotalAmount), 2);

        $text = "
        {$grandTotalAmount}
        ";

        return $text;
    }

    /**
     *
     */
    function getExportToExcel(){
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        set_time_limit(50000);
        ini_set('memory_limit', '512M');

        require_once("PHPExcel.php");
        include 'PHPExcel/IOFactory.php';

        $file_name = "StockTransferReport" . date("d-m-Y") . ".xls";

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
        $headStyle = array(
            'font' => array('bold' => true)
        );

        $actSheet->mergeCells("A{$rowc}:D{$rowc}");
        $actSheet->getStyle("A{$rowc}:D{$rowc}")->applyFromArray($headStyle);

        $colc = 0;
        $rowc++;
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'From Location');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'TO Location');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Product Name');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Rate');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'GST');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'MRP');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Qty');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Total');
        /******************** FORMAT HEADER *******************/
        $lastCol    = $actSheet->getHighestColumn();
        $lastColInd = PHPExcel_Cell::columnIndexFromString($lastCol);
        $actSheet->getStyle("A2:{$lastCol}2")->applyFromArray($headStyle);

        for ($i=0; $i < $lastColInd; $i++){
            $colAlphabet = PHPExcel_Cell::stringFromColumnIndex($i);
            $actSheet->getColumnDimension($colAlphabet)->setAutoSize(true);
        }

        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $monthVal       = $fn->getReqParam('month');
        $yearVal        = $fn->getReqParam('year');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');
        $startDateAppendSql = '';
        $monthValAppendSql = '';

        if ($start_date != '' && $end_date == '') {
            $startDateAppendSql .= "AND st.date >= '{$start_date}' AND st.date <= '{$current_date}'";
        } 

        else if ($start_date == '' && $end_date != ''){
            $start_date         = $year . '-' . $month . '-' . '01';
            $startDateAppendSql .= "AND st.date >= '{$start_date}' AND st.date <= '{$end_date}'";
        } 

        else if ($start_date != '' && $end_date != '') {
            $startDateAppendSql .= "AND st.date >= '{$start_date}' AND st.date <= '{$end_date}'";
        } 

        else if ($monthVal == '' && $yearVal == ''){
            $start_date         = $year . '-' . $month . '-' . '01';
            $end_date           = $year . '-' . $month . '-' . '31';
            $startDateAppendSql .= "AND st.date >= '{$start_date}' AND st.date <= '{$end_date}'";
        }

        if ($monthVal != '') {
            $monthValAppendSql = "AND DATE_FORMAT(st.date, '%m') = '{$monthVal}'" ;
        }

        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $site_id = $fn->getSessionParam('cp_site_id');
            if($site_id != ''){
                $startDateAppendSql .= "AND st.site_id = {$site_id}" ;
            }
        }
        
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
       
        $SQL = "
        SELECT st.*
              ,p.title AS product_name
              ,pop.cost_price
              ,pop.gst
              ,pop.selling_price
              ,sth.qty
        FROM stock_transfer st
        LEFT JOIN (`stock_transfer_history` sth) ON (sth.stock_transfer_id = st.stock_transfer_id)
        LEFT JOIN (`po_product` pop) ON (pop.po_product_id = sth.po_product_id)
        LEFT JOIN (`product` p) ON (p.product_id = sth.product_id)
        WHERE st.transfer_type = 'External'
        AND st.status != 'Cancelled'
        AND (st.from_location = {$cpSiteIdSession} OR st.to_location = {$cpSiteIdSession})
        {$monthValAppendSql}
        {$startDateAppendSql}
        ";

        $result = $db->sql_query($SQL);
        $total = 0;
        $count = 1;
        $grand_total = 0;
        while ($row = $db->sql_fetchrow($result)) {
            $colc = 0;
            $rowc++;

            $totalAmount = $row['qty'] * $row['cost_price'];
            $TotalAmount = number_format($totalAmount, 2);
            $grand_total += $totalAmount;

            $Sqlfrom = "
            SELECT title
            FROM site
            WHERE site_id = {$row['from_location']}
            ";
            $resultfrom = $db->sql_query($Sqlfrom);
            $from = $db->sql_fetchrow($resultfrom);

            $Sqlto = "
            SELECT title
            FROM site
            WHERE site_id = {$row['to_location']}
            ";
            $resultto = $db->sql_query($Sqlto);
            $to = $db->sql_fetchrow($resultto);
            
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $from['title']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $to['title']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['product_name']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['cost_price']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['gst']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['selling_price']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['qty']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $TotalAmount);


            $count++;
        }
        $grand_total = number_format($grand_total ,2);

        $colc = 0;
        $rowc++;

        $rowc++;
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Grand Total');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $grand_total);

        $actSheet->getStyle("A{$rowc}:D{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }    
}