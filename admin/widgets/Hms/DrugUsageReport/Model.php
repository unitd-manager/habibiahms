<?
class CPL_Admin_Widgets_Hms_DrugUsageReport_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');
        $monthVal       = $fn->getReqParam('month');
        $yearVal        = $fn->getReqParam('year');

        $SQL = "
        SELECT ii.item_title
              ,ii.record_id
              ,ii.qty
              ,o.cust_first_name
              ,o.order_status
              ,i.invoice_date
        FROM invoice_item ii
        LEFT JOIN (invoice i) ON (i.invoice_id = ii.invoice_id)
        LEFT JOIN (`order` o) ON (o.order_id = i.order_id)
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
        $searchVar->mainTableAlias = 'i';
        $monthVal       = $fn->getReqParam('month');
        $yearVal        = $fn->getReqParam('year');
        $medicine        = $fn->getReqParam('medicine');

        $medical_test_id = $fn->getReqParam('medical_test_id');

        /*if ($monthVal != '') {
            $searchVar->sqlSearchVar[] = "DATE_FORMAT(creation_date, '%m') = '{$monthVal}'" ;
        }
        if ($yearVal != '') {
            $searchVar->sqlSearchVar[] = "DATE_FORMAT(creation_date, '%Y') = '{$yearVal}'" ;
        }*/

        if ($medicine != '') {
            $searchVar->sqlSearchVar[] = "ii.record_id = '{$medicine}'" ;
        }

        $searchVar->sqlSearchVar[] = "ii.record_id = '{$medicine}'" ;
        $searchVar->sqlSearchVar[] = "(ii.batch_no != '' OR ii.batch_no IS NOT NULL)" ;
        $searchVar->sqlSearchVar[] = "o.order_status != 'Cancelled'" ;
        //$searchVar->groupBy   = "title,Date_format(creation_date, '%m')";
        $searchVar->sortOrder = "i.invoice_date DESC";
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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'hms_labReportSummary');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }

    /**
     */
    function getExportToExcel(){
        $db       = Zend_Registry::get('db');
        $cpCfg    = Zend_Registry::get('cpCfg');
        $tv       = Zend_Registry::get('tv');
        $cpUtil   = Zend_Registry::get('cpUtil');
        $dateUtil = Zend_Registry::get('dateUtil');
        $fn       = Zend_Registry::get('fn');

        $rows = '';
        $totalOverAllCase = 0;
        $totalOverAll = 0;
        $month          = date('m');
        $year           = date('Y');
        $monthVal       = $fn->getReqParam('month');
        $yearVal        = $fn->getReqParam('year');
        $medicine        = $fn->getReqParam('medicine');

        set_time_limit(50000);
        ini_set('memory_limit', '512M');

        require_once("PHPExcel.php");
        include 'PHPExcel/IOFactory.php';

        $file_name = "labReportSummary__" . date("d-m-Y") . ".xls";

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

        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Name');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Medicine');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Qty');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Date');

        /******************** FORMAT HEADER *******************/
        $headStyle = array(
            'font' => array('bold' => true)
        );

        $lastCol    = $actSheet->getHighestColumn();
        $lastColInd = PHPExcel_Cell::columnIndexFromString($lastCol);
        $actSheet->getStyle("A1:{$lastCol}1")->applyFromArray($headStyle);

        for ($i=0; $i < $lastColInd; $i++){
            $colAlphabet = PHPExcel_Cell::stringFromColumnIndex($i);
            $actSheet->getColumnDimension($colAlphabet)->setAutoSize(true);
        }


        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $appendSqlSite = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlSite = "WHERE i.site_id = {$cpSiteIdSession}";
        }

        $SQL = "
        SELECT ii.item_title
              ,ii.record_id
              ,ii.qty
              ,o.cust_first_name
              ,i.invoice_date
        FROM invoice_item ii
        LEFT JOIN (invoice i) ON (i.invoice_id = ii.invoice_id)
        LEFT JOIN (`order` o) ON (o.order_id = i.order_id)
        {$appendSqlSite}
        AND ii.record_id = '{$medicine}'
        AND (ii.batch_no != '' OR ii.batch_no IS NOT NULL)
        AND o.order_status != 'Cancelled'
        ORDER BY i.invoice_date DESC
        ";

        $result = $db->sql_query($SQL);

        while ($row = $db->sql_fetchrow($result)) {
            $colc = 0;
            $rowc++;

            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['cust_first_name']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['item_title']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['qty']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['invoice_date']);

        }

        $colc = 0;
        $rowc++;
        $actSheet->getStyle("A{$rowc}:D{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

}