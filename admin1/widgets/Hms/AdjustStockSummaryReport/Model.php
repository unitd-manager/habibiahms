<?
class CPL_Admin_Widgets_Hms_AdjustStockSummaryReport_Model extends CP_Common_Lib_WidgetModelAbstract
{
    
    function getSQL(){
        $fn     = Zend_Registry::get('fn');
        $siteId         = $fn->getSessionParam('cp_site_id');
               

        $SQL = "
        SELECT a.*
              ,p.title
        FROM `adjust_stock_log` a
        LEFT JOIN (product p) ON (p.product_id = a.product_id)
        LEFT JOIN (medicine_site ms) ON (a.product_id = ms.product_id AND ms.site_id = {$siteId})
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
        $searchVar->mainTableAlias = 'ms';

        $start_date   = $fn->getReqParam('start_date');
        $end_date     = $fn->getReqParam('end_date');
        $monthVal     = $fn->getReqParam('month');
        $yearVal      = $fn->getReqParam('year');
        $month        = date('m');
        $year         = date('Y');
        $current_date = date('Y-m-d');
        $last30days   = date('Y-m-d', strtotime('today - 7 days'));
        $on_leave    = $fn->getReqParam('on_leave');
        
        if ($monthVal != '') {
            $searchVar->sqlSearchVar[] = "DATE_FORMAT(a.creation_date, '%m') = '{$monthVal}'";
        }

        if ($yearVal != '') {
            $searchVar->sqlSearchVar[] = "DATE_FORMAT(a.creation_date, '%Y') = '{$yearVal}'";
        }
        
        $searchVar->sqlSearchVar[] = "a.actual_stock IS NOT NULL";

        $searchVar->groupBy   = "a.product_id";
        $searchVar->sortOrder = 'p.title';
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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'hms_attendanceReport');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }
    
    /**
     */
    function getExportToExcel(){
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

        $file_name = "AdjustStockReport_" . date("d-m-Y") . ".xls";

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

        $start_date   = $fn->getReqParam('start_date');
        $end_date     = $fn->getReqParam('end_date');
        $monthVal     = $fn->getReqParam('month');
        $yearVal      = $fn->getReqParam('year');
        $month        = date('m');
        $year         = date('Y');
        $current_date = date('Y-m-d');

        $monthValAppendSql  = '';
        $yearValAppendSql   = '';
        $startDateAppendSql = '';

        $actSheet = &$objPHPExcel->getActiveSheet();
        $dates = '';
        $current_date  = date('Y-m-d');
        $current_year  = date('Y');
        $current_month = date('m');
        $start_date = $current_year . '-' . $current_month . '-' . '01';
        $end_date = $current_year . '-' . $current_month . '-' . '31';

        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Name');
        for($i=$start_date; $i <= $end_date; $i++){
            $datesAtt  = date('d', strtotime($i));
            //$dates .= "{$datesAtt}";
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $datesAtt);
        }


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
            $startDateAppendSql .= "AND DATE_FORMAT(a.creation_date, '%m') = '{$monthVal}'" ;
        }

        if ($yearVal != '') {
            $startDateAppendSql .= " AND DATE_FORMAT(a.creation_date, '%Y') = '{$yearVal}'" ;
        }
  
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $appendSqlSite = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlSite = "ms.site_id = {$cpSiteIdSession}";
        }


        $SQL = "
        SELECT a.*
              ,p.title
        FROM `adjust_stock_log` a
        LEFT JOIN (product p) ON (p.product_id = a.product_id)
        LEFT JOIN (medicine_site ms) ON (a.product_id = ms.product_id AND ms.site_id = {$cpSiteIdSession})
        WHERE {$appendSqlSite}
        {$startDateAppendSql}
        GROUP BY a.product_id
        ORDER BY p.title
        ";

        $result = $db->sql_query($SQL);

        while ($row = $db->sql_fetchrow($result)) {
            $colc = 0;
            $rowc++;

            $dates = '';
            $current_date  = date('Y-m-d');
            $current_year  = date('Y');
            $current_month = date('m');
            $start_date = $current_year . '-' . $current_month . '-' . '01';
            $end_date = $current_year . '-' . $current_month . '-' . '31';

            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['title']);

            for($i=$start_date; $i <= $end_date; $i++){
                $on_leave = '';
                $datesAtt  = date('Y-m-d', strtotime($i));
                $SQL = "
                SELECT SUM(a.actual_stock - a.adjust_stock) as adjust_stock
                FROM `adjust_stock_log` a
                WHERE DATE_FORMAT(a.creation_date, '%Y-%m-%d') = '{$datesAtt}'
                AND a.product_id = {$row['product_id']}
                ";
                $result1 = $db->sql_query($SQL);
                $row1 = $db->sql_fetchrow($result1);
                $numRows = $db->sql_numrows($result1);

                //if($numRows > 0){
                $on_leave = $row1['adjust_stock'];
                //}
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $on_leave);
            }
            
        }

        $colc = 0;
        $rowc++;
        $actSheet->getStyle("A{$rowc}:G{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }
}