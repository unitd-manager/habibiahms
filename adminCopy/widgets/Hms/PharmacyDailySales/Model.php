<?
class CPL_Admin_Widgets_Hms_PharmacyDailySales_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        
        $SQL = "
        SELECT p.*
        FROM pharma_daily_sales p
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'p';

        $last12Month    = date('Y-m-d',mktime (0,0,0,date("m")-12,1, date("Y")));
        $today          = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');
        $monthVal       = $fn->getReqParam('month');
        $month          = $fn->getReqParam('month');
        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');

        /*if($tv['module'] == 'tradingsg_dashboard'){
            if ($month != '') {
                $searchVar->sqlSearchVar[] = "DATE_FORMAT(p.date, '%m') = '{$month}'";            
            }
        }else {*/
            if ($monthVal != '') {
                $searchVar->sqlSearchVar[] = "DATE_FORMAT(p.date, '%m') = '{$monthVal}'" ;
            }
        //}

        $searchVar->sortOrder = "p.date desc";
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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'hms_pharmacyDailySales');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }
    /**
     *
     **
     */
    function getExportToExcel(){
        $db       = Zend_Registry::get('db');
        $cpCfg    = Zend_Registry::get('cpCfg');
        $tv       = Zend_Registry::get('tv');
        $cpUtil   = Zend_Registry::get('cpUtil');
        $dateUtil = Zend_Registry::get('dateUtil');
        $fn       = Zend_Registry::get('fn');

        $rows = '';

        ob_end_clean();
        set_time_limit(50000);
        ini_set('memory_limit', '512M');

        require_once("PHPExcel.php");
        include 'PHPExcel/IOFactory.php';

        $file_name = "PharmacyDailySales__" . date("d-m-Y") . ".xls";

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
        $monthValAppendSql = '';
        $yearValAppendSql = '';
        $startDateAppendSql = '';
        $start_date   = $fn->getReqParam('start_date');
        $end_date     = $fn->getReqParam('end_date');
        $monthVal     = $fn->getReqParam('month');
        $yearVal      = $fn->getReqParam('year');
        $month        = date('m');
        $year         = date('Y');
        $current_date = date('Y-m-d');
        $totalAmount = 0;

        $actSheet = &$objPHPExcel->getActiveSheet();

        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Date');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Sales Amount');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Excess Amount');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Total Amount');

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

        if ($monthVal != '') {
            $monthValAppendSql .= "DATE_FORMAT(p.date, '%m') = '{$monthVal}'" ;
        }

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $appendSql = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "AND p.site_id = {$cpSiteIdSession}";
        }

        $SQL = "
        SELECT p.*
        FROM pharma_daily_sales p
        WHERE {$monthValAppendSql}
        {$appendSql}
        ";
        $result = $db->sql_query($SQL);

        while ($row = $db->sql_fetchrow($result)) {
            $colc = 0;
            $rowc++;

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

            $date = $fn->getCPDate($row['date'],"d-m-Y");
            $totalAmount = $totalCollection + $row['excess_amount'] ;
            $totalAmount = number_format(round($totalAmount));   

            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $date);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $totalCollection);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['excess_amount']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $totalAmount);

       }

        $colc = 0;
        $rowc++;

        $actSheet->getStyle("A{$rowc}:G{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        ob_end_clean();
        $objWriter->save('php://output');
    }

}