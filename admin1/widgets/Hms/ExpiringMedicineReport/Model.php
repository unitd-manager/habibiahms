<?
class CPL_Admin_Widgets_Hms_ExpiringMedicineReport_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn    = Zend_Registry::get('fn');
        $db    = Zend_Registry::get('db');
        $siteId         = $fn->getSessionParam('cp_site_id');

        $SQL = "
        SELECT ibs.*
              ,i.minimum_order_level{$siteId}
              ,p.product_id AS productId
              ,p.title AS product_name
              ,p.item_code
              ,p.unit
              ,p.product_code
              ,p.category_id
              ,pop.expiry_date
              ,pop.return_qty
              ,pop.return_qty_ns
              ,SUM(ibs.current_stock - IFNULL(pop.return_qty_ns, 0)) AS stock
        FROM inventory_batchwise_stock ibs
        LEFT JOIN (po_product pop) ON (pop.po_product_id = ibs.po_product_id AND pop.product_id = ibs.product_id)
        LEFT JOIN (product p) ON (p.product_id = ibs.product_id)
        LEFT JOIN (medicine_site ms) ON (ibs.product_id = ms.product_id)
        LEFT JOIN (inventory i) ON (i.product_id = ibs.product_id)
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $tv        = Zend_Registry::get('tv');
        $fn        = Zend_Registry::get('fn');
        $cpCfg     = Zend_Registry::get('cpCfg');
        $searchVar = Zend_Registry::get('searchVar');

        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'ibs';
                
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $expiry_duration = $fn->getReqParam('expiry_duration');

        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $searchVar->sqlSearchVar[] = "ms.site_id = {$cpSiteIdSession}";
            //$searchVar->sqlSearchVar[] = "ibs.site_id = {$cpSiteIdSession}";
        }

        $searchVar->sqlSearchVar[] = "ibs.current_stock - IFNULL(pop.return_qty_ns, 0) > 0";

        if ($expiry_duration == '60') {
            $searchVar->sqlSearchVar[] = "(DATEDIFF(pop.expiry_date, Now()) > 30) AND (DATEDIFF(pop.expiry_date, Now()) <= 60)";

        } else if($expiry_duration == '30'){
            $searchVar->sqlSearchVar[] = "(DATEDIFF(pop.expiry_date, Now()) <= 30)";                
        }

        $searchVar->sqlSearchVar[] = "p.published = 1";
        $searchVar->groupBy   = "pop.product_id";
        $searchVar->sortOrder = "p.title ASC, stock DESC";
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
     *
     */
    function getExportToExcel(){
        $db       = Zend_Registry::get('db');
        $cpCfg    = Zend_Registry::get('cpCfg');
        $tv       = Zend_Registry::get('tv');
        $cpUtil   = Zend_Registry::get('cpUtil');
        $dateUtil = Zend_Registry::get('dateUtil');
        $fn       = Zend_Registry::get('fn');

        $rows  = '';
        $month = date('m');
        $year  = date('Y');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $expiry_duration = $fn->getReqParam('expiry_duration');

        set_time_limit(50000);
        ini_set('memory_limit', '512M');

        require_once("PHPExcel.php");
        include 'PHPExcel/IOFactory.php';

        $file_name = "Expiring_Medicine_Report_" . date("d-m-Y") . ".xls";

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
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Item Code');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Medicine Name');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Stock');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'MOL');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Expiry Date');

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

        $appendSQL = "";
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSQL .= " AND ms.site_id = {$cpSiteIdSession}";
            $appendSQL .= " AND ibs.site_id = {$cpSiteIdSession}";
        }

        if ($expiry_duration == '60') {
            $appendSQL .= " AND (DATEDIFF(pop.expiry_date, Now()) > 30) AND (DATEDIFF(pop.expiry_date, Now()) <= 60)";

        } else if($expiry_duration == '30'){
            $appendSQL .= " AND (DATEDIFF(pop.expiry_date, Now()) <= 30)";                
        }

        $SQL = "
        SELECT ibs.*
              ,i.minimum_order_level{$cpSiteIdSession}
              ,p.product_id AS productId
              ,p.title AS product_name
              ,p.item_code
              ,p.unit
              ,p.product_code
              ,p.category_id
              ,pop.expiry_date
              ,SUM(ibs.current_stock) AS stock
        FROM inventory_batchwise_stock ibs
        LEFT JOIN (po_product pop) ON (pop.po_product_id = ibs.po_product_id AND pop.product_id = ibs.product_id)
        LEFT JOIN (product p) ON (p.product_id = ibs.product_id)
        LEFT JOIN (medicine_site ms) ON (ibs.product_id = ms.product_id)
        LEFT JOIN (inventory i) ON (i.product_id = ibs.product_id)
        WHERE ibs.current_stock > 0
        AND p.published = 1
        {$appendSQL}
        GROUP BY pop.product_id
        ORDER BY p.title ASC, stock DESC
        ";
        $result = $db->sql_query($SQL);

        while ($row = $db->sql_fetchrow($result)) {
            $colc = 0;
            $rowc++;

            $expiry_date = "";
            if($row['expiry_date'] != "") {
                $expiry_date = $fn->getCPDate($row['expiry_date'], "d-m-Y");
            }

            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['item_code']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['product_name']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['stock']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['minimum_order_level'.$cpSiteIdSession]);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $expiry_date);
        }

        $colc = 0;
        $rowc++;
        $actSheet->getStyle("A{$rowc}:E{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

}