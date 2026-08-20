<?
class CPL_Admin_Widgets_Hms_DiabetesReport_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        $cpCfg = Zend_Registry::get('cpCfg');

        $SQL = "
       SELECT  pi.*, count(pv.patient_visit_id) as visit_count,pv.check_up_date
        FROM patient_information pi
        LEFT JOIN patient_visit pv ON pv.patient_information_id = pi.patient_information_id
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
        $searchVar->mainTableAlias = 'pi';
       
        $year    = $fn->getReqParam('year');
        $start_date 	= $fn->getReqParam('start_date');
        $end_date   	= $fn->getReqParam('end_date');
        $monthVal       = $fn->getReqParam('month');
        $yearVal        = $fn->getReqParam('year');
        $current_date  = date('Y-m-d');



        if ($start_date != '' && $end_date == '') {
            $searchVar->sqlSearchVar[] = "pv.check_up_date >= '{$start_date}' AND pv.check_up_date <= '{$current_date}'";
      } else if ($start_date == '' && $end_date != ''){
          $start_date = $year . '-' . $month . '-' . '01';
            $searchVar->sqlSearchVar[] = "pv.check_up_date >= '{$start_date}' AND pv.check_up_date <= '{$end_date}'";
      } else if ($start_date != '' && $end_date != '') {
            $searchVar->sqlSearchVar[] = "pv.check_up_date >= '{$start_date}' AND pv.check_up_date <= '{$end_date}'";
      } 

        
        // if ($monthVal != '') {
        //     $searchVar->sqlSearchVar[] = "DATE_FORMAT(inp.date_admitted, '%m') = '{$monthVal}'" ;
        // }
        // if ($yearVal != '') {
        //     $searchVar->sqlSearchVar[] = "DATE_FORMAT(inp.date_admitted, '%Y') = '{$yearVal}'" ;
        // }
    if ($year != '') {
       $searchVar->sqlSearchVar[] = "pi.address_area = '{$year}'" ;
    }
         $searchVar->sqlSearchVar[] = "pi.diabetes = 1";
       // $searchVar->sqlSearchVar[] = "e.first_name != ''";
      //  $searchVar->sqlSearchVar[] = "(e.position = 'Doctor' OR e.position = 'Nurse')";
     //   $searchVar->sqlSearchVar[] = "pv.status != 'Cancelled'";
        $searchVar->groupBy        = "pi.patient_information_id";
        //$searchVar->sortOrder      = "DATE_FORMAT(inp.date_admitted, '%M') ASC";
        //$searchVar->sortOrder = "pi.patient_information_id DESC";
            $searchVar->sortOrder = "visit_count DESC";

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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'hms_inPatientReport');

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

        set_time_limit(50000);
        ini_set('memory_limit', '512M');

        require_once("PHPExcel.php");
        include 'PHPExcel/IOFactory.php';

        $file_name = "DiabetesPatientReport__" . date("d-m-Y") . ".xls";

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
       
      
        $yearVal      = $fn->getReqParam('year');
        

        $actSheet = &$objPHPExcel->getActiveSheet();

      
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Patient Name');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Age');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Gender');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Phone');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Address');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Visit Count');

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

    

        if ($yearVal != '') {
            $startDateAppendSql .= "WHERE pi.address_area = '{$yearVal}'" ;
        }

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $appendSqlSite = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlSite = "AND pi.site_id = {$cpSiteIdSession}";
        }

        $SQL = "
        SELECT pi.*
        FROM patient_information pi
       
        {$startDateAppendSql}
        {$appendSqlSite}
        
        ";
        $result = $db->sql_query($SQL);

        while ($row = $db->sql_fetchrow($result)) {
            $colc = 0;
            $rowc++;
     

            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['name']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['age_year']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['gender']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['phone']);
         $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['address_area']);
         $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['visit_count']);
             

       }

        $colc = 0;
        $rowc++;

        $actSheet->getStyle("A{$rowc}:G{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }


}