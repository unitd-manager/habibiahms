<?
class CPL_Admin_Widgets_Hms_ProductSalesReport_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){

        /*$SQL = "
        SELECT i.*
              ,SUM(it.qty) AS qty
              ,it.unit_price
              ,it.record_id
              ,SUM(it.unit_price*it.qty) AS Amount
              ,p.title AS product_name
        FROM invoice i
        LEFT JOIN (invoice_item it) ON (it.invoice_id = i.invoice_id)
        LEFT JOIN (product p) ON (p.product_id = it.record_id)
        ";*/

        $SQL = "
        SELECT i.invoice_date
        ,i.invoice_id
              ,i.creation_date
              ,i.status
              ,SUM(it.qty) AS qty
              ,it.unit_price
              ,it.record_id
              ,SUM(it.unit_price*it.qty) AS Amount
              ,p.title AS product_name
              ,o.counter
     
        FROM order_item it
        LEFT JOIN (`order` o) ON (o.order_id = it.order_id)
        LEFT JOIN (`invoice` i) ON (i.order_id = o.order_id)
        LEFT JOIN (product p) ON (p.product_id = it.record_id)
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $fn = Zend_Registry::get('fn');
        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'i';

        $month        = date('m');
        $year         = date('Y');
        $monthVal     = $fn->getReqParam('month');
        $yearVal      = $fn->getReqParam('year');
        $invoice_date = $fn->getReqParam('invoice_date');
        $time_in      = $fn->getReqParam('time_in');
        $time_out     = $fn->getReqParam('time_out');
        $start_date   = $fn->getReqParam('start_date');
        $end_date     = $fn->getReqParam('end_date');
        $counterSales = $fn->getReqParam('counterSales');

        $previousDate       = date("Y-m-d", strtotime("yesterday"));

        $site_id = $fn->getSessionParam('cp_site_id');

        $current_date = date('Y-m-d');

        if ($counterSales == "Yesterday") {
            $searchVar->sqlSearchVar[] = "(i.invoice_date BETWEEN '{$previousDate}' AND '{$previousDate}')";
        } elseif ($counterSales == "Manual Stock") {
                // Show current invoice records where manual stock date is not equal to the current invoice date
                $searchVar->sqlSearchVar[] = "(i.invoice_date = '{$current_date}' )";
                $searchVar->sqlSearchVar[] = "NOT EXISTS (
                    SELECT date FROM manual_stock ms
                    WHERE ms.product_id = it.record_id
                    AND ms.site_id = {$site_id}
                    AND DATE(ms.date) ='{$current_date}'
                )";
                if ($time_in != '' && $time_out != '') {
                    $searchVar->sqlSearchVar[] = "DATE_FORMAT(i.creation_date, '%H:%i:%s') BETWEEN '{$time_in}' AND '{$time_out}'";
                }
             
        } else if ($counterSales != '') {
            $searchVar->sqlSearchVar[] = "o.counter = '1'";
        }            
        if ($start_date != '' && $end_date !='') {
            $searchVar->sqlSearchVar[] = "i.invoice_date >= '{$start_date}' AND i.invoice_date <= '{$end_date}'";
        } 

        if ($start_date == '') {
            if ($monthVal != '') {
                $searchVar->sqlSearchVar[] = "DATE_FORMAT(i.invoice_date, '%m') = '{$monthVal}'";
            }

            if ($yearVal != '') {
                $searchVar->sqlSearchVar[] = "DATE_FORMAT(i.invoice_date, '%Y') = '{$yearVal}'";
            }

            if ($monthVal == '' && $yearVal == ''){
                $start_date = $year . '-' . $month . '-' . '01';
                $end_date = $year . '-' . $month . '-' . '31';
                $searchVar->sqlSearchVar[] = "i.invoice_date >= '{$start_date}' AND i.invoice_date <= '{$end_date}'";
            }
        }

        if ($time_in != '' && $time_out != '') {
            $searchVar->sqlSearchVar[] = "DATE_FORMAT(i.creation_date, '%H:%i:%s') BETWEEN '{$time_in}' AND '{$time_out}'";
        }
    
           
        $searchVar->sqlSearchVar[] = "i.status != 'Cancelled'";
        $searchVar->sqlSearchVar[] = "it.qty != 0";
        
        $searchVar->sortOrder = "p.title ASC";
        $searchVar->groupBy   = "it.record_id";
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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'hms_productSalesReport');

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

        $file_name = "ProductSalesReport_" . date("d-m-Y") . ".xls";

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename={$file_name}");
        header("Content-Transfer-Encoding: binary ");

        $objPHPExcel = new PHPExcel();
        $actSheet    = &$objPHPExcel->getActiveSheet();

        //--------------------------------------------------//
        $rowc = 1;
        $colc = 0;
        $stock = '';
        $month        = date('m');
        $year         = date('Y');
        $monthVal     = $fn->getReqParam('month');
        $yearVal      = $fn->getReqParam('year');
        $invoice_date = $fn->getReqParam('invoice_date');
        $time_in      = $fn->getReqParam('time_in');
        $time_out     = $fn->getReqParam('time_out');
        $start_date   = $fn->getReqParam('start_date');
        $end_date     = $fn->getReqParam('end_date');
        $current_date = date('d-m-Y');

        /******************** FORMAT HEADER *******************/
        $headStyle = array(
            'font' => array('bold' => true)
        );

        $CompanyNameStyle = array(
            'font' => array('bold' => true, 'size'  => 22)
        );

        $CompanyAddressStyle = array(
            'font' => array('bold' => true, 'size'  => 14)
        );

        $CompanyAddressStyle2 = array(
            'font' => array('size'  => 15)
        );

        $BorderstyleArray = array(
            'borders' => array(
              'allborders' => array(
                  'style' => PHPExcel_Style_Border::BORDER_THIN,
              )
            )
        );

        $filterDetailsStyle = array(
            'font' => array('bold' => true, 'size'  => 12)
        );

        $productDetailsHeadingStyle = array(
            'font' => array('bold' => true, 'size'  => 12)
        );

        $productDetailsStyle = array(
            'font' => array('size'  => 12)
        );

        $lastCol    = $actSheet->getHighestColumn();
        $lastColInd = PHPExcel_Cell::columnIndexFromString($lastCol);
        $actSheet->getDefaultStyle()->getAlignment()->setWrapText(true);
        $actSheet->getStyle("A1:{$lastCol}1")->applyFromArray($headStyle);

        for ($i=0; $i < $lastColInd; $i++){
            $colAlphabet = PHPExcel_Cell::stringFromColumnIndex($i);
            $actSheet->getColumnDimension($colAlphabet)->setAutoSize(true);
        }
        
        $actSheet->getColumnDimension('A')->setWidth(3.29);
        $actSheet->getColumnDimension('B')->setWidth(32.59);
        $actSheet->getColumnDimension('C')->setWidth(9.14);
        $actSheet->getColumnDimension('D')->setWidth(10);
        $actSheet->getColumnDimension('E')->setWidth(8.14);
        $actSheet->getColumnDimension('F')->setWidth(12.43);
        $actSheet->getColumnDimension('G')->setWidth(12);

        $actSheet->getStyle('A'.$rowc.':C'.$rowc)->applyFromArray($CompanyNameStyle);
        $actSheet->mergeCells('A'.$rowc.':C'.$rowc);
        $actSheet->getStyle('A'.$rowc.':C'.$rowc)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, "STOCK CHECK LIST");

        $rowc++;
        $colc=0;

        /*$actSheet->getStyle('A'.$rowc.':G'.$rowc)->applyFromArray($CompanyAddressStyle);
        $actSheet->mergeCells('A'.$rowc.':G'.$rowc);
        $actSheet->getStyle('A'.$rowc.':G'.$rowc)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, "KURUKKUSALAI, THOOTHUKUDI - 628 722"); *?

        $rowc++;
        $colc=0;

        $actSheet->getStyle('A'.$rowc.':G'.$rowc)->applyFromArray($CompanyAddressStyle2);
        $actSheet->mergeCells('A'.$rowc.':G'.$rowc);
        $actSheet->getStyle('A'.$rowc.':G'.$rowc)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, "DL NO.TNY/2170/20-21   TIN: 33885882027");

        $rowc++;
        $colc=0;

        if($monthVal != "") {
            $monthValue = $monthVal;
        } else {
            $monthValue = $month;
        }

        if($yearVal != "") {
            $yearValue = $yearVal;
        } else {
            $yearValue = $year;
        }

        $rowc++;
        $colc=0;
        $monthName = $dateUtil->getLongMonthName($monthValue);
        /*$actSheet->getStyle('A'.$rowc.':B'.$rowc)->applyFromArray($filterDetailsStyle);
        $actSheet->mergeCells('A'.$rowc.':B'.$rowc);
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'From Time : '.$time_in.' - To Time : '.$time_out);*/
        $actSheet->mergeCells('A'.$rowc.':C'.$rowc);
        $start_date_formatted = '';
        $monthName  = '';
        
        if($start_date){
            $start_date_formatted = $dateUtil->formatDate($start_date, 'DD/MM/YYYY') ;       
            $actSheet->setCellValueByColumnAndRow($colc, $rowc, 'Date : '.$start_date_formatted);
        }
        else{
            $dateObj   = DateTime::createFromFormat('!m', $month);
            $monthName = $dateObj->format('F');            
            $actSheet->setCellValueByColumnAndRow($colc, $rowc, 'Month : '.$monthName);
        }



        $rowc++;
        $colc=0;
        $actSheet->getStyle('A'.$rowc.':C'.$rowc)->applyFromArray($filterDetailsStyle);
        $actSheet->mergeCells('A'.$rowc.':C'.$rowc);
        //$actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Month : '.$monthName-$yearValue);

        $rowc++;
        $colc=0;


        $actSheet->getStyle('A'.$rowc)->getAlignment()->applyFromArray(
            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                  'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,)
        );

        $actSheet->getStyle('B'.$rowc)->getAlignment()->applyFromArray(
            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                  'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,)
        );

        $actSheet->getStyle('C'.$rowc)->getAlignment()->applyFromArray(
            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                  'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,)
        );

        /*$actSheet->getStyle('D'.$rowc)->getAlignment()->applyFromArray(
            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                  'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,)
        );

        $actSheet->getStyle('E'.$rowc)->getAlignment()->applyFromArray(
            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                  'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,)
        );

        $actSheet->getStyle('F'.$rowc)->getAlignment()->applyFromArray(
            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                  'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,)
        );

        $actSheet->getStyle('G'.$rowc)->getAlignment()->applyFromArray(
            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                  'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,)
        );
        */

        $actSheet->getStyle('A'.$rowc.':C'.$rowc)->applyFromArray($BorderstyleArray);
        $actSheet->getStyle('A'.$rowc.':C'.$rowc)->applyFromArray($productDetailsHeadingStyle);
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'S.No');
        //$actSheet->mergeCells('B'.$rowc.':C'.$rowc);
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Medicine Name');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Qty');
        //$actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Amount');
        //$actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Sales Return');
       // $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Total Amount');
        if ($_SESSION['userGroupName'] == 'Administrator' || $_SESSION['userGroupName'] == 'Super Administrator'){
            //$actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Stock Balance');
        }

        $site_id = $fn->getSessionParam('cp_site_id');
        $appendSqlSite = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlSite = "AND i.site_id = {$site_id}";
        }

        $timeAppendSql = "";
        if ($time_in != '' && $time_out != '') {
            $timeAppendSql = "AND DATE_FORMAT(i.creation_date, '%H:%i:%s') BETWEEN '{$time_in}' AND '{$time_out}'";
        }

        $appendSqlDate = "";
        if ($start_date != '') {
            $appendSqlDate .= "AND i.invoice_date = '{$start_date}'";
        } 

        if ($start_date == '') {
            if ($monthVal != '') {
                $appendSqlDate .= "AND DATE_FORMAT(i.invoice_date, '%m') = '{$monthVal}'";
            }

            if ($yearVal != '') {
                $appendSqlDate .= "AND DATE_FORMAT(i.invoice_date, '%Y') = '{$yearVal}'";
            }

            if ($monthVal == '' && $yearVal == ''){
                $start_date = $year . '-' . $month . '-' . '01';
                $end_date = $year . '-' . $month . '-' . '31';
                $appendSqlDate .= "AND i.invoice_date >= '{$start_date}' AND i.invoice_date <= '{$end_date}'";
            }
        }

        $timeAppendReturnSql = "";
        if ($time_in != '' && $time_out != '') {
            $timeAppendReturnSql = "AND DATE_FORMAT(srh.creation_date, '%H:%i:%s') BETWEEN '{$time_in}' AND '{$time_out}'";
        }

        $appendSqlReturnDate = "";
        if ($start_date != '') {
            $appendSqlReturnDate .= "AND srh.date = '{$start_date}'";
        } 

        if ($start_date == '') {
            if ($monthVal != '') {
                $appendSqlReturnDate .= "AND DATE_FORMAT(srh.date, '%m') = '{$monthVal}'";
            }

            if ($yearVal != '') {
                $appendSqlReturnDate .= "AND DATE_FORMAT(srh.date, '%Y') = '{$yearVal}'";
            }

            if ($monthVal == '' && $yearVal == ''){
                $start_date = $year . '-' . $month . '-' . '01';
                $end_date = $year . '-' . $month . '-' . '31';
                $appendSqlReturnDate .= "AND srh.date >= '{$start_date}' AND srh.date <= '{$end_date}'";
            }
        }

        $SQL = "
        SELECT i.*
              ,SUM(it.qty) AS qty
              ,it.unit_price
              ,it.record_id
              ,SUM(it.unit_price*it.qty) AS Amount
              ,p.title AS product_name
        FROM invoice i
        LEFT JOIN (invoice_item it) ON (it.invoice_id = i.invoice_id)
        LEFT JOIN (product p) ON (p.product_id = it.record_id)
        WHERE it.qty != 0
        AND i.status != 'Cancelled'
        {$appendSqlSite}
        {$timeAppendSql}
        {$appendSqlDate}
        GROUP BY it.record_id
        ORDER BY p.title ASC
        ";
  
        $result = $db->sql_query($SQL);

        $appendSqlSiteStock = '';
        if($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlSiteStock = "AND ibs.site_id = {$site_id}";
        }

        $appendSql = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "AND i.site_id = {$site_id}";
        }

        $totalOverAll = 0;
        $count = 1;
        while ($row = $db->sql_fetchrow($result)) {
            if($row['record_id'] != '' && $row['product_name'] != ''){
                $SQLStock ="
                SELECT SUM(current_stock) AS current_stock
                FROM inventory_batchwise_stock ibs
                WHERE ibs.product_id = {$row['record_id']}
                {$appendSqlSiteStock}
                ";
                $resultStock = $db->sql_query($SQLStock);
                $rowStock    = $db->sql_fetchrow($resultStock);
                $stock       = $rowStock['current_stock'];

                $SQLSalesReturn = "
                SELECT SUM(srh.qty_return * srh.price) as sales_return_amount 
                FROM sales_return_history srh
                LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                LEFT JOIN (invoice i) ON (i.invoice_id = srh.invoice_id AND i.status != 'Cancelled')
                LEFT JOIN (`order` o) ON (o.order_id = srh.order_id)
                WHERE o.order_status != 'Cancelled'
                AND o.order_type = 'POS'
                AND ini.record_id = {$row['record_id']}
                {$timeAppendSql}
                {$appendSqlDate}
                {$appendSql}
                ";

                $resultSalesReturn    = $db->sql_query($SQLSalesReturn);
                $recSalesReturn       = $db->sql_fetchrow($resultSalesReturn);
                $salesReturn          = $recSalesReturn['sales_return_amount'];
                $totalAmount          = $row['Amount'] - $salesReturn;
                $salesReturn          = number_format($salesReturn, 2);
                $totalAmountFormatted = number_format($totalAmount, 2);
                $Amount               = number_format($row['Amount'], 2);
                
                $colc = 0;
                $rowc++;
                
                $actSheet->getStyle('A'.$rowc)->getAlignment()->applyFromArray(
                    array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                          'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,)
                );

                $actSheet->getStyle('B'.$rowc)->getAlignment()->applyFromArray(
                    array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                          'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,)
                );

                $actSheet->getStyle('C'.$rowc)->getAlignment()->applyFromArray(
                    array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                          'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,)
                );

                /*$actSheet->getStyle('D'.$rowc)->getAlignment()->applyFromArray(
                    array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                          'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,)
                );

                $actSheet->getStyle('E'.$rowc)->getAlignment()->applyFromArray(
                    array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                          'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,)
                );

                $actSheet->getStyle('F'.$rowc)->getAlignment()->applyFromArray(
                    array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                          'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,)
                );

                $actSheet->getStyle('G'.$rowc)->getAlignment()->applyFromArray(
                    array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                          'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,)
                );*/
                
                $actSheet->getStyle('A'.$rowc.':C'.$rowc)->applyFromArray($BorderstyleArray);
                $actSheet->getStyle('A'.$rowc.':C'.$rowc)->applyFromArray($productDetailsStyle);
                $actSheet->getStyle('A'.$rowc)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $count);
                //$actSheet->mergeCells('B'.$rowc.':C'.$rowc);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['product_name']);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
               /* $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $Amount);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $salesReturn);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $totalAmountFormatted);
                if ($_SESSION['userGroupName'] == 'Administrator' || $_SESSION['userGroupName'] == 'Super Administrator'){
                    $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $stock);
                }*/

                $totalOverAll += $totalAmount; 
                $count++;
            }
        }

        $totalOverAll = number_format(round($totalOverAll), 2);

        $colc = 0;
        $rowc++;

        /*$actSheet->getStyle('F'.$rowc)->getAlignment()->applyFromArray(
            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                  'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,)
        );

        $actSheet->getStyle('G'.$rowc)->getAlignment()->applyFromArray(
            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                  'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,)
        );

        $actSheet->getStyle('A'.$rowc.':G'.$rowc)->applyFromArray($BorderstyleArray);
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Total');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $totalOverAll);
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->getStyle("A{$rowc}:G{$rowc}")->applyFromArray($headStyle);
*/
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        ob_end_clean();
        $objWriter->save('php://output');
    }

}