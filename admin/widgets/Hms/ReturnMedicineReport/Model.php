<?
class CPL_Admin_Widgets_Hms_ReturnMedicineReport_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
       

        $SQL = "
        SELECT pop.*
        ,p.title AS product_name
        ,(IFNULL(pop.return_qty, 0) + IFNULL(pop.return_qty_ns, 0)) AS qty_return
        FROM po_product pop
        LEFT JOIN (product p) ON (p.product_id = pop.product_id)
        LEFT JOIN (purchase_order po) ON (po.purchase_order_id = pop.purchase_order_id)
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
        $searchVar->mainTableAlias = 'po';

        $po_product_id = $fn->getReqParam('po_product_id');
         $product_id = $fn->getReqParam('product_id');
        
        
        if ($po_product_id) {
            $searchVar->sqlSearchVar[] = "pop.po_product_id = '{$po_product_id}'";
        }
         if ($product_id) {
            $searchVar->sqlSearchVar[] = "p.product_id = '{$product_id}'";
        } 
        $searchVar->sqlSearchVar[] = "(pop.return_qty != '' OR pop.return_qty_ns != '')";
        $searchVar->sqlSearchVar[] = "(pop.return_qty_ns_paid IS NULL OR pop.return_qty_ns_paid = 0)";
       
        $searchVar->sortOrder = "p.title ASC";
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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'hms_returnmedicinereport');

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

        $file_name = "ReturnMedicineReport_" . date("d-m-Y") . ".xls";

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename={$file_name}");
        header("Content-Transfer-Encoding: binary ");

        $headStyle = array(
            'font' => array('bold' => true)
        );

        $topHeaderStyle = array(
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            )
            ,'font' => array('bold' => true, 'size' => 16)
        );
        $objPHPExcel = new PHPExcel();
        $rowc = 1;
        $colc = 0;
        $actSheet = &$objPHPExcel->getActiveSheet();
        $actSheet->mergeCells("A{$rowc}:G{$rowc}");
        $actSheet->getStyle("A{$rowc}:G{$rowc}")->applyFromArray($topHeaderStyle);
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Return Medicine Report');

        //--------------------------------------------------//
        $rowc++;
        $colc = 0;

        
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Medicine Name');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Return Qty');
        
     
        //$actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Standard');
       // $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Main Phone');
       // $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Main Fax');
       
       
       /******************** FORMAT HEADER *******************/
        $lastCol    = $actSheet->getHighestColumn();
        $lastColInd = PHPExcel_Cell::columnIndexFromString($lastCol);
        $actSheet->getStyle("A2:{$lastCol}2")->applyFromArray($headStyle);
      
        for ($i=0; $i < $lastColInd; $i++){
            $colAlphabet = PHPExcel_Cell::stringFromColumnIndex($i);
            $actSheet->getColumnDimension($colAlphabet)->setAutoSize(true);
        }

    
        $SQL = "
        SELECT pop.*
        ,p.title AS product_name
        ,(IFNULL(pop.return_qty, 0) + IFNULL(pop.return_qty_ns, 0)) AS qty_return
        FROM po_product pop
        LEFT JOIN (product p) ON (p.product_id = pop.product_id)
        LEFT JOIN (purchase_order po) ON (po.purchase_order_id = pop.purchase_order_id)
         WHERE pop.return_qty != '' OR pop.return_qty_ns != ''
        ";
       
        
        $result = $db->sql_query($SQL);
        $count = 1;
        while ($row = $db->sql_fetchrow($result)) {

            $colc = 0;
            $rowc++;

            
     $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['product_name'] ,$row['product_id']);
      $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['qty_return']);
            
           // $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['phone']);
           // $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['fax']);
            
            $count++;
        }

        $rowc++;
        $actSheet->getStyle("A{$rowc}:J{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }
}
