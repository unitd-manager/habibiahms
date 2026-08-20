<?
class CPL_Admin_Widgets_Hms_MfgCompanyReport_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        $SQL = "
        SELECT mc.*
        FROM `medicine_company` mc
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
        $searchVar->mainTableAlias = 'mc';
        $month          = date('m');
        $year           = date('Y');
        $current_date   = date('Y-m-d');
        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $monthVal       = $fn->getReqParam('month');
        $yearVal        = $fn->getReqParam('year');
        $site_id        = $fn->getReqParam('site_id');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $medicine_company_id   = $fn->getReqParam('medicine_company_id');

        /*if ($start_date != '' && $end_date == '') {
            $searchVar->sqlSearchVar[] = "po.purchase_order_date >= '{$start_date}' AND po.purchase_order_date <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $searchVar->sqlSearchVar[] = "po.purchase_order_date >= '{$start_date}' AND po.purchase_order_date <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $searchVar->sqlSearchVar[] = "po.purchase_order_date >= '{$start_date}' AND po.purchase_order_date <= '{$end_date}'";
        } else if ($monthVal == '' && $yearVal == ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $end_date = $year . '-' . $month . '-' . '31';
            $searchVar->sqlSearchVar[] = "po.purchase_order_date >= '{$start_date}' AND po.purchase_order_date <= '{$end_date}'";
        }

        if ($start_date == '' && $end_date == '') {
            if ($monthVal != '') {
                $searchVar->sqlSearchVar[] = "DATE_FORMAT(po.purchase_order_date, '%m') = '{$monthVal}'" ;
            }

            if ($yearVal != '') {
                $searchVar->sqlSearchVar[] = "DATE_FORMAT(po.purchase_order_date, '%Y') = '{$yearVal}'" ;
            }
        }*/

        /*if ($medicine_company_id != '') {
            $searchVar->sqlSearchVar[] = "pop.medicine_company_id = '{$medicine_company_id}'" ;
        }*/

        /*if ($cpCfg['cp.hasMultiUniqueSites']) {
            $searchVar->sqlSearchVar[] ="po.site_id = {$cpSiteIdSession}";
        }*/

        //$searchVar->sqlSearchVar[] = "pop.medicine_company_id = '{$medicine_company_id}'" ;

        //$searchVar->sqlSearchVar[] = "pop.cost_price != 0.00" ;

        //$searchVar->groupBy   = "pop.product_id, pop.medicine_company_id";
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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'hms_mfgCompanyReport');

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

        set_time_limit(50000);
        ini_set('memory_limit', '512M');

        require_once("PHPExcel.php");
        include 'PHPExcel/IOFactory.php';

        $file_name = "MfgCompanyReport__" . date("d-m-Y") . ".xls";

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
        $grandTotal  = 0;
        $appendSql = '';
        $startDateAppendSql = '';
        $monthValAppendSql = '';
        $yearValAppendSql = '';
        $medicineCompanyIdAppendSql = '';

        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');
        $monthVal       = $fn->getReqParam('month');
        $yearVal        = $fn->getReqParam('year');
        $medicine_company_id   = $fn->getReqParam('medicine_company_id');

        $actSheet = &$objPHPExcel->getActiveSheet();

        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Medicine Name');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Price');

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

        if ($start_date != '' && $end_date == '') {
            $startDateAppendSql = "AND po.purchase_order_date >= '{$start_date}' AND po.purchase_order_date <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $startDateAppendSql = "AND po.purchase_order_date >= '{$start_date}' AND po.purchase_order_date <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $startDateAppendSql = "AND po.purchase_order_date >= '{$start_date}' AND po.purchase_order_date <= '{$end_date}'";
        } else if ($monthVal == '' && $yearVal == ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $end_date = $year . '-' . $month . '-' . '31';
            $startDateAppendSql = "AND po.purchase_order_date >= '{$start_date}' AND po.purchase_order_date <= '{$end_date}'";
        }

        if ($monthVal != '') {
            $monthValAppendSql = "AND DATE_FORMAT(po.purchase_order_date, '%m') = '{$monthVal}'" ;
        }
        if ($yearVal != '') {
            $yearValAppendSql = "AND DATE_FORMAT(po.purchase_order_date, '%Y') = '{$yearVal}'" ;
        }

        if ($medicine_company_id != '') {
            $medicineCompanyIdAppendSql = "AND mc.medicine_company_id = '{$medicine_company_id}'";
        }

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $appendSqlSite = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlSite = "AND po.site_id = {$cpSiteIdSession}";
        }

        $SQL = "
        SELECT mc.*
        FROM `medicine_company` mc
        ";

        $result = $db->sql_query($SQL);

        if($medicine_company_id != ''){
        while ($row = $db->sql_fetchrow($result)) {
            $SQL = "
            SELECT p.title AS product, p.product_id
            FROM product p
            WHERE p.medicine_company_id = '{$medicine_company_id}'
            ";
            $result = $db->sql_query($SQL);
            while ($row1 = $db->sql_fetchrow($result)) {
                $SQL2 = "
                SELECT SUM(pop.cost_price * pop.qty) AS total_value
                FROM po_product pop
                LEFT JOIN (purchase_order po) ON (po.purchase_order_id = pop.purchase_order_id)
                WHERE pop.product_id = '{$row1['product_id']}'
                {$appendSqlSite}
                {$startDateAppendSql}
                {$monthValAppendSql}
                {$yearValAppendSql}
                GROUP BY pop.product_id
                ";
                $result2 = $db->sql_query($SQL2);
                $row2 = $db->sql_fetchrow($result2);
                $total_value = number_format($row2['total_value'], 2);

                $colc = 0;
                $rowc++;

                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row1['product']);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $total_value);

                $grandTotal += $row2['total_value'];
            }
        }
        }

        $grandTotal = number_format($grandTotal, 2);

        $colc = 0;
        $rowc++;
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Total');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $grandTotal);

        $actSheet->getStyle("A{$rowc}:D{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        ob_start();
        $objWriter->save('php://output');
    }

}