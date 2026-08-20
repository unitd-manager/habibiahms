<?
class CPL_Admin_Modules_Hms_InPatient_View extends CP_Common_Lib_ModuleViewAbstract
{
    /**
     *
     */
    function getList($dataArray){
        $listObj = Zend_Registry::get('listObj');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

        $count      = 0;
        $rows       = '';
        foreach ($dataArray as $row){

            $age = '';

            if($row['age_year'] != ''){
                $age = $row['age_year'].' Yrs';
            } elseif($row['age_month'] != ''){
                $age = $row['age_month'].' Months';
            } elseif($row['age_day'] != ''){
                $age = $row['age_day'].' Days';
            }

            // $date_admitted = $fn->getCPDate($row['date_admitted'], 'd');
            // $date_discharge = $fn->getCPDate($row['date_discharge'], 'd');
            
            // $no_of_days = '';
            // if($date_discharge != ''){
            //     $no_of_days = abs($date_discharge - $date_admitted); // Ensure no negative value
            // }


            $date_admitted = DateTime::createFromFormat('Y-m-d', $row['date_admitted']);
            $date_discharge = DateTime::createFromFormat('Y-m-d', $row['date_discharge']);
            
            $no_of_days = '';
            if ($date_discharge !== false && $date_admitted !== false) {
                $interval = $date_admitted->diff($date_discharge);
                $no_of_days = $interval->days;
            }
        
            
            $dateAdmitted  = $fn->getCPDate($row['date_admitted'], 'd-m-Y');
            $dateDischarge = $fn->getCPDate($row['date_discharge'], 'd-m-Y');
            $timeAdmitted  = $fn->getCPDate($row['time_admitted'], 'h:m a');

            $SQLEmpVisit = "
            SELECT SUM(consultation_fees) AS consultation_fees
                   ,employee_in_patient_id
            FROM employee_in_patient
            WHERE in_patient_id = {$row['in_patient_id']}
            ";
            $resultEmpVisit = $db->sql_query($SQLEmpVisit);
            $rowEmpVisit    = $db->sql_fetchrow($resultEmpVisit);

            $totalAmount = $rowEmpVisit['consultation_fees'] + $row['amount'] + $row['nursing_fees'] + $row['other_fees'] + $row['advance_payment'] + $row['oxygen_fees'] + $row['medicine_fees'];
            $theatreCharges = $row['theatre_charges'];
            
            if($totalAmount != ""){
                $totalAmount = number_format($totalAmount);
            }
            $pat_name = $row['patient_name'] . '/' . $age;
            $dateAdmitted = $dateAdmitted . '/' . $timeAdmitted;
            $rows .= "
            {$listObj->getListRowHeader($row, $count)}
            {$listObj->getListDataCell('IP-'.$row['code'])}
            {$listObj->getListDataCell($pat_name)}
            {$listObj->getListDataCell($row['address_area'])}
            {$listObj->getListDataCell($row['diagnosis'])}
            {$listObj->getListDataCell($dateAdmitted)}
            {$listObj->getListDataCell($row['admission_type'])}
            {$listObj->getListDataCell($totalAmount, 'right')}
            {$listObj->getListDataCell($theatreCharges, 'right')}
            {$listObj->getListDataCell($dateDischarge)}
            {$listObj->getListDataCell($no_of_days)}
            {$listObj->getListDataCell($row['status'])}
            {$listObj->getListRowEnd($row['patient_information_id'])}
            ";

            $count++ ;
        }


        
        $text = "
        {$listObj->getListHeader()}
        {$listObj->getListHeaderCell('IP Code', 'ip.code')}
        {$listObj->getListHeaderCell('Patient Name', 'p.name')}
        {$listObj->getListHeaderCell('Town/City', 'p.address_area')}
        {$listObj->getListHeaderCell('Diagnosis', 'ip.diagnosis')}
        {$listObj->getListHeaderCell('Date of Admission', 'ip.date_admitted')}
        {$listObj->getListHeaderCell('Admission Type', 'ip.admission_type')}
        {$listObj->getListHeaderCell('Admission Charges', '','txtRight')}
        {$listObj->getListHeaderCell('Theatre Charges', '','txtRight')}
        {$listObj->getListHeaderCell('Date of Discharge', 'ip.date_discharge')}
        {$listObj->getListHeaderCell('No of Days')}
        {$listObj->getListHeaderCell('Status', 'ip.status')}
        {$listObj->getListHeaderEnd()}
        {$rows}
        {$listObj->getListFooter()}
        ";

        return $text;
    }

    /**
     *
     */
    function getNew(){
        $formObj = Zend_Registry::get('formObj');

        $fielset1 = "
        {$formObj->getDateRow('Date Admitted (YYYY-MM-DD)', 'date_admitted')}
        ";

        $text = "
        {$formObj->getFieldSetWrapped('Key Details', $fielset1)}
        ";

        return $text;
    }
    
    /**
     *
     */
    function getEdit($row){
        $formObj = Zend_Registry::get('formObj');
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $formObj->mode = $tv['action'];

        $discountPercent = '';
        $cstNo = '';
        $tinNo = '';
        $oxygenFeesRow= '';
$medicineFeesRow ='';

        //$sqlCountry = getCPModelObj('common_geoCountry')->getCountryDDSQL();
        //$expCountry = array('detailValue' => $row['address_country']);

        $expVl           = array('sqlType' => 'OneField');
        $expBillType     = array('sqlType' => 'OneField', 'hideFirstOption' => 1);
        $sqlCategory     = $fn->getValueListSQL('patientVisitCategory');
        $sqlTitle        = $fn->getValueListSQL('patientVisitTitle');
        $sqlBillType     = $fn->getValueListSQL('billType');
        $expNoEdit       = array('isEditable' => 0);
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $followUp = array(
                       '1 week'  => 'One week later'
                      ,'2 weeks'  => 'Two weeks later'
                      ,'3 weeks'  => 'Three weeks later'
                      ,'4 weeks'  => 'Four weeks later'
                      ,'5 weeks'  => 'Five weeks later'
                      ,'6 weeks'  => 'Six weeks later'
                      ,'2 months'  => 'Two months later'
                      ,'3 months'  => 'Three months later'
                      ,'6 months'  => 'Six months later'
                      );
        $expArr = array('useKey' => 1);



        $appendSqlEmp = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlEmp = "AND site_id = {$cpSiteIdSession}";
        }

        $sqlEmployee = "
        SELECT employee_id
              ,CONCAT_WS(' ', first_name, middle_name, last_name) AS employee_name
        FROM employee
        WHERE (position = 'Doctor' OR position = 'Nurse')
        {$appendSqlEmp}
        ORDER BY employee_name
        ";

        $status  = $fn->getReqParam('status');

        $statusArray = array(
            "New"
           ,"Closed"
           ,"Cancelled"
        );

        $sqlGender      = $fn->getValueListSQL('gender');

        $medHisLbl = "
        <li class='first'>
            <a href='#tabs-1' class='chiefComplains'>Chief Complains</a>
        </li>
        ";

        $medHisTab = "
        <div id='tabs-1'>
        <div class='floatbox'>
            <div class='float_left complainTabClass'>
                <div id='chiefComplains'>{$this->getChiefComplainsDisplay($row['in_patient_id'])}</div>
            </div>
            <div class='float_left complainTabClass'>
                <div id='procedurePortal'>{$this->getProcedurePortalDisplay($row['in_patient_id'])}</div>
            </div>
            <div class='float_left'>
                <div id='summaryPortalDisplay'>{$this->getSummaryPortalDisplay($row['in_patient_id'])}</div>
            </div>
        </div>
        </div>
        ";

        $medicineLbl = "
        <li class='first'>
            <a href='#tabs-5' class='medicines'>Medicines</a>
        </li>
        ";

        $medicineTab = "
        <div id='tabs-5'>
            <div id='medicinesDisplay'>{$this->getMedicinesPortalDisplay($row['in_patient_id'])}</div>
        </div>
        ";

        $medicalTestLbl = "
        <li class='first'>
            <a href='#tabs-6' class='investigations'>Investigations</a>
        </li>
        ";

        $medicalTestTab = "
        <div id='tabs-6'>
            <div id='medicalDisplay'>{$this->getMedicalPortalDisplay($row['in_patient_id'])}</div>
        </div>
        ";

        $addDrLbl = "
        <li class='first'>
            <a href='#tabs-2'>Add Consultant</a>
        </li>
        ";

        $formActionAddDr = "index.php?module=hms_inPatient&_spAction=addDoctorRecord&in_patient_id={$row['in_patient_id']}&showHTML=0";
        $addDrTab = "
        <div id='tabs-2'>
            <div class='btn btn-info mb10'><a href='{$formActionAddDr}' id='addDoctorRecord' in_patient_id={$row['in_patient_id']}>Add Record</a></div>
            <div id='doctorDisplay'>{$this->getDoctorPortalDisplay($row['in_patient_id'])}</div>
        </div>
        ";

        $theaterCaseLbl = "
        <li class='first'>
            <a href='#tabs-7' class='theatercase'>Theater Case</a>
        </li>
        ";

        $theaterCaseTab = "
        <div id='tabs-7'>
            <div id='theaterCaseDisplay'></div>
        </div>
        ";

        $SQLEmpVisit = "
        SELECT consultation_fees
               ,employee_in_patient_id
        FROM employee_in_patient
        WHERE in_patient_id = {$row['in_patient_id']}
        ORDER BY employee_in_patient_id ASC
        ";
        $resultEmpVisit = $db->sql_query($SQLEmpVisit);
        $rowEmpVisit    = $db->sql_fetchrow($resultEmpVisit);

        $feesRow          = "{$formObj->getTBRow('Dr Fees', 'consultation_fees', $rowEmpVisit['consultation_fees'])}";
        $nursingFeesRow   = "{$formObj->getTBRow('Nursing Fees', 'nursing_fees', $row['nursing_fees'])}";
        $consultantFeesRow   = "{$formObj->getTBRow('Consultant Fees', 'dr_consultant_fees', $row['dr_consultant_fees'])}";
        $otherFeesRow     = "{$formObj->getTBRow('Other Fees', 'other_fees', $row['other_fees'])}";
        $oxygenFeesRow = "{$formObj->getTBRow('Oxygen Fees', 'oxygen_fees', $row['oxygen_fees'])}";
        $medicineFeesRow = "{$formObj->getTBRow('Medicine Fees', 'medicine_fees', $row['medicine_fees'])}";
        $roomRentRow      = "{$formObj->getTBRow('Room Rent', 'amount', $row['amount'])}";
        $advanceRow       = "{$formObj->getTBRow('Advance Payment', 'advance_payment', $row['advance_payment'])}";
        $surFeesRow       = "{$formObj->getTBRow('Surgeon Fees', 'surgeon_fees', $row['surgeon_fees'])}";
        $anaFeesRow       = "{$formObj->getTBRow('Anaesthetic Fees', 'anesthetic_fees', $row['anesthetic_fees'])}";
        $theatreFeesRow   = "{$formObj->getTBRow('Theatre Assistant Fees', 'theater_assistant_fees', $row['theater_assistant_fees'])}";
        $theatreChargeRow = "{$formObj->getTBRow('Theatre Charges', 'theatre_charges', $row['theatre_charges'])}";
        $refChargeRow     = "{$formObj->getTBRow('Referral Charges', 'referral_charges', $row['referral_charges'])}";
        $asstSurFeesRow   = "{$formObj->getTBRow('Asst Surgeon Fees', 'asst_surgeon_fees', $row['asst_surgeon_fees'])}";
        $otChargeRow      = "{$formObj->getTBRow('OT Charges', 'ot_charges', $row['ot_charges'])}";

        $gotoOrder            = '';
        $generateOrder        = '';
        $invoicePortalDisplay = '';
        $actionButtons        = '';
        $receiptPortalDisplay = '';
        $cancelAdmission      = '';

        $appendSql = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "AND site_id = {$cpSiteIdSession}";
        }

        $SQLOrder ="
        SELECT order_id
        FROM `order`
        WHERE in_patient_id = {$row['in_patient_id']}
        AND order_type = 'IP'
        {$appendSql}
        ";
        $resultOrder  = $db->sql_query($SQLOrder);
        $numRowsOrder = $db->sql_numrows($resultOrder);
        $rowOrder = $db->sql_fetchrow($resultOrder);

        if($numRowsOrder > 0){
            $SQLInvoice = "
            SELECT i.*
            FROM invoice i
            WHERE i.order_id = {$rowOrder['order_id']}
            AND i.status != 'Cancelled'
            ";
            $resultInvoice = $db->sql_query($SQLInvoice);
            $numRowsInvoice = $db->sql_numrows($resultInvoice);

            

            if($numRowsInvoice == 0){
                if($row['status'] != 'Cancelled'){
                    $generateOrder = "<a href='#' id='createOrderRecord' in_patient_id='{$row['in_patient_id']}' class='btn btn-info'>Generate Bill</a>";
                    $cancelAdmission = "<a in_patient_id='{$row['in_patient_id']}' class='btn btn-danger cancelAdmissionRecord'>Cancel Admission</a>";
                }
            }
            else{
                $billSummaryOrder = "index.php?module=hms_inPatient&_spAction=summaryInOrder&order_id={$rowOrder['order_id']}&showHTML=0";
                $generateOrder = "<div class='billSummaryOrder float_left'><a class='btn btn-primary' href='{$billSummaryOrder}' id='billSummaryOrder' order_id='{$rowOrder['order_id']}'>Bill Summary</a></div>";                

                $apChk = $this->getConditionForInvoiceItem($rowOrder['order_id'], 'Advance Payment');
                if($apChk > 0){
                    $advanceRow = "{$formObj->getTBRow('Advance Payment', 'advance_payment', $row['advance_payment'], $expNoEdit)}
                    <input type='hidden' name='advance_payment' value='{$row['advance_payment']}' />";
                } else {
                    $advanceRow = $formObj->getTBRow('Advance Payment', 'advance_payment', $row['advance_payment']);                    
                }

                $dnChk = $this->getConditionForInvoiceItem($rowOrder['order_id'], 'Doctor/Nurse');
                if($dnChk > 0){
                    $feesRow = "{$formObj->getTBRow('Dr Fees', 'consultation_fees', $rowEmpVisit['consultation_fees'], $expNoEdit)}
                    <input type='hidden' name='consultation_fees' value='{$rowEmpVisit['consultation_fees']}' />";
                } else {
                    $feesRow = $formObj->getTBRow('Dr Fees', 'consultation_fees', $rowEmpVisit['consultation_fees']);
                }

                $nfChk = $this->getConditionForInvoiceItem($rowOrder['order_id'], 'Nursing Fees');
                if($nfChk > 0){
                    $nursingFeesRow = "{$formObj->getTBRow('Nursing Fees', 'nursing_fees', $row['nursing_fees'], $expNoEdit)}
                    <input type='hidden' name='nursing_fees' value='{$row['nursing_fees']}' />";
                } else {
                    $nursingFeesRow = $formObj->getTBRow('Nursing Fees', 'nursing_fees', $row['nursing_fees']);
                }

                $consChk = $this->getConditionForInvoiceItem($rowOrder['order_id'], 'Consultant Fees');
                if($consChk > 0){
                    $consultantFeesRow = "{$formObj->getTBRow('Consultant Fees', 'dr_consultant_fees', $row['dr_consultant_fees'], $expNoEdit)}
                    <input type='hidden' name='dr_consultant_fees' value='{$row['dr_consultant_fees']}' />";
                } else {
                    $consultantFeesRow = $formObj->getTBRow('consultant Fees', 'dr_consultant_fees', $row['dr_consultant_fees']);
                }

                $ofChk = $this->getConditionForInvoiceItem($rowOrder['order_id'], 'Other Fees');
                if($ofChk > 0){
                    $otherFeesRow = "{$formObj->getTBRow('Other Fees', 'other_fees', $row['other_fees'], $expNoEdit)}
                    <input type='hidden' name='other_fees' value='{$row['other_fees']}' />";
                } else{
                    $otherFeesRow = $formObj->getTBRow('Other Fees', 'other_fees', $row['other_fees']);
                }

                $oxfChk = $this->getConditionForInvoiceItem($rowOrder['order_id'], 'Oxygen Fees');
                if($oxfChk > 0){
                    $oxygenFeesRow = "{$formObj->getTBRow('Oxygen Fees', 'oxygen_fees', $row['oxygen_fees'], $expNoEdit)}
                    <input type='hidden' name='oxygen_fees' value='{$row['oxygen_fees']}' />";
                } else{
                    $oxygenFeesRow = $formObj->getTBRow('Oxygen Fees', 'oxygen_fees', $row['oxygen_fees']);
                }

                $medfChk = $this->getConditionForInvoiceItem($rowOrder['order_id'], 'Medicine Fees');
                if($medfChk > 0){
                    $medicineFeesRow = "{$formObj->getTBRow('Medicine Fees', 'medicine_fees', $row['medicine_fees'], $expNoEdit)}
                    <input type='hidden' name='medicine_fees' value='{$row['medicine_fees']}' />";
                } else{
                    $medicineFeesRow = $formObj->getTBRow('Medicine Fees', 'medicine_fees', $row['medicine_fees']);
                }

                $rrChk = $this->getConditionForInvoiceItem($rowOrder['order_id'], 'Room Rent');
                if($rrChk > 0){
                    $roomRentRow = "{$formObj->getTBRow('Room Rent', 'amount', $row['amount'], $expNoEdit)}
                    <input type='hidden' name='amount' value='{$row['amount']}' />";
                } else {
                    $roomRentRow = $formObj->getTBRow('Room Rent', 'amount', $row['amount']);   
                }

                $sfChk = $this->getConditionForInvoiceItem($rowOrder['order_id'], 'Surgeon Fees');
                if($sfChk > 0){
                    $surFeesRow = "{$formObj->getTBRow('Surgeon Fees', 'surgeon_fees', $row['surgeon_fees'], $expNoEdit)}
                    <input type='hidden' name='surgeon_fees' value='{$row['surgeon_fees']}' />";
                } else {
                    $surFeesRow = "{$formObj->getTBRow('Surgeon Fees', 'surgeon_fees', $row['surgeon_fees'])}";
                }

                $asfChk = $this->getConditionForInvoiceItem($rowOrder['order_id'], 'Asst Surgeon Fees');
                if($asfChk > 0){
                    $asstSurFeesRow = "{$formObj->getTBRow('Asst Surgeon Fees', 'asst_surgeon_fees', $row['asst_surgeon_fees'], $expNoEdit)}
                    <input type='hidden' name='asst_surgeon_fees' value='{$row['asst_surgeon_fees']}' />";
                } else {
                    $asstSurFeesRow = "{$formObj->getTBRow('Asst Surgeon Fees', 'asst_surgeon_fees', $row['asst_surgeon_fees'])}";
                }

                $afChk = $this->getConditionForInvoiceItem($rowOrder['order_id'], 'Anaesthetic Fees');
                if($afChk > 0){
                    $anaFeesRow = "{$formObj->getTBRow('Anaesthetic Fees', 'anesthetic_fees', $row['anesthetic_fees'], $expNoEdit)}
                    <input type='hidden' name='anesthetic_fees' value='{$row['anesthetic_fees']}' />";
                } else {
                    $anaFeesRow = "{$formObj->getTBRow('Anaesthetic Fees', 'anesthetic_fees', $row['anesthetic_fees'])}";
                }

                $tafChk = $this->getConditionForInvoiceItem($rowOrder['order_id'], 'Theatre Assistant Fees');
                if($tafChk > 0){
                    $theatreFeesRow = "{$formObj->getTBRow('Theatre Assistant Fees', 'theater_assistant_fees', $row['theater_assistant_fees'], $expNoEdit)}
                    <input type='hidden' name='theater_assistant_fees' value='{$row['theater_assistant_fees']}' />";
                } else {
                    $theatreFeesRow = "{$formObj->getTBRow('Theatre Assistant Fees', 'theater_assistant_fees', $row['theater_assistant_fees'])}";
                }

                $tcChk = $this->getConditionForInvoiceItem($rowOrder['order_id'], 'Theatre Charges');
                if($tcChk > 0){
                    $theatreChargeRow = "{$formObj->getTBRow('Theatre Charges', 'theatre_charges', $row['theatre_charges'], $expNoEdit)}
                    <input type='hidden' name='theatre_charges' value='{$row['theatre_charges']}' />";
                } else {
                    $theatreChargeRow = "{$formObj->getTBRow('Theatre Charges', 'theatre_charges', $row['theatre_charges'])}";
                }

                $rcChk = $this->getConditionForInvoiceItem($rowOrder['order_id'], 'Referral Charges');
                if($rcChk > 0){
                    $refChargeRow = "{$formObj->getTBRow('Referral Charges', 'referral_charges', $row['referral_charges'], $expNoEdit)}
                    <input type='hidden' name='referral_charges' value='{$row['referral_charges']}' />";
                } else {
                    $refChargeRow = "{$formObj->getTBRow('Referral Charges', 'referral_charges', $row['referral_charges'])}";
                }

                $rcChk = $this->getConditionForInvoiceItem($rowOrder['order_id'], 'OT Charges');
                if($rcChk > 0){
                    $otChargeRow = "{$formObj->getTBRow('OT Charges', 'ot_charges', $row['ot_charges'], $expNoEdit)}
                    <otChargeRow type='hidden' name='ot_charges' value='{$row['ot_charges']}' />";
                } else {
                    $otChargeRow = "{$formObj->getTBRow('OT Charges', 'ot_charges', $row['ot_charges'])}";
                }

            }

            $modObj = getCPModuleObj('hms_order');
            $invoicePortalDisplay =  $modObj->view->getInvoicePortalDisplay($rowOrder['order_id']);

            $formActionReceipt = "index.php?module=hms_order&_spAction=generateReceiptForm&order_id={$rowOrder['order_id']}&patient_information_id={$row['patient_information_id']}&in_patient_id={$row['in_patient_id']}&showHTML=0";

            $actionButtons ="
            <div class='btn btn-info mb5'>
                <a href='{$formActionReceipt}' id='generateReceipt'>CREATE RECEIPT</a>
            </div>
            ";
            $receiptPortalDisplay =  $modObj->view->getReceiptPortalDisplay($rowOrder['order_id']);
        }
        else{
            if($row['status'] != 'Cancelled'){
                $generateOrder = "<a href='#' id='createOrderRecord' in_patient_id='{$row['in_patient_id']}' class='btn btn-info'>Generate Bill</a>";            
                $cancelAdmission = "<a in_patient_id='{$row['in_patient_id']}' class='btn btn-danger cancelAdmissionRecord'>Cancel Admission</a>";
            }
            
            $advanceRow       = $formObj->getTBRow('Advance Payment', 'advance_payment', $row['advance_payment']);
            $feesRow          = $formObj->getTBRow('Dr Fees', 'consultation_fees', $rowEmpVisit['consultation_fees']);
            $nursingFeesRow   = $formObj->getTBRow('Nursing Fees', 'nursing_fees', $row['nursing_fees']);
            $consultantFeesRow   = $formObj->getTBRow('Consultant Fees', 'dr_consultant_fees', $row['dr_consultant_fees']);
            $otherFeesRow     = $formObj->getTBRow('Other Fees', 'other_fees', $row['other_fees']);
            $oxygenFeesRow    = $formObj->getTBRow('Oxygen Fees', 'oxygen_fees', $row['oxygen_fees']);
            $medicineFeesRow  = $formObj->getTBRow('Medicine Fees', 'medicine_fees', $row['medicine_fees']);
            $roomRentRow      = $formObj->getTBRow('Room Rent', 'amount', $row['amount']);
            $surFeesRow       = $formObj->getTBRow('Surgeon Fees', 'surgeon_fees', $row['surgeon_fees']);
            $asstSurFeesRow   = $formObj->getTBRow('Asst Surgeon Fees', 'asst_surgeon_fees', $row['asst_surgeon_fees']);
            $anaFeesRow       = $formObj->getTBRow('Anaesthetic Fees', 'anesthetic_fees', $row['anesthetic_fees']);
            $theatreFeesRow   = $formObj->getTBRow('Theatre Assistant Fees', 'theater_assistant_fees', $row['theater_assistant_fees']);
            $theatreChargeRow = $formObj->getTBRow('Theatre Charges', 'theatre_charges', $row['theatre_charges']);
            $refChargeRow     = $formObj->getTBRow('Referral Charges', 'referral_charges', $row['referral_charges']);
            $otChargeRow      = $formObj->getTBRow('OT Charges', 'ot_charges', $row['ot_charges']);
        }
        
        $printPrescription = '';
        $labReport = '';
        $labReqReport = '';
        $printDischargeCard = '';
        if($row['status'] != 'Cancelled'){
            $urlPrescription = "index.php?module=hms_inPatient&_spAction=printPrescription&in_patient_id={$row['in_patient_id']}&showHTML=0";
            $printPrescription = "<a href='{$urlPrescription}' id='printPrescription' in_patient_id='{$row['in_patient_id']}' class='btn btn-info' target='_blank'>Print Prescription</a>";

            $urllabReport = "index.php?module=hms_inPatient&_spAction=printLabReport&in_patient_id={$row['in_patient_id']}&showHTML=0";
            $labReport = "<a href='{$urllabReport}' id='labReport' in_patient_id='{$row['in_patient_id']}' class='btn btn-info' target='_blank'>Lab Report</a>";

            $urllabReqReport = "index.php?module=hms_inPatient&_spAction=printLabRequestForm&in_patient_id={$row['in_patient_id']}&showHTML=0";
            $labReqReport = "<a href='{$urllabReqReport}' id='labReport' in_patient_id='{$row['in_patient_id']}' class='btn btn-info' target='_blank'>Lab Request Form</a>";
            
            $urlDischargeCard   = "index.php?module=hms_inPatient&_spAction=printDischargeCard&in_patient_id={$row['in_patient_id']}&showHTML=0";
            $printDischargeCard = "<a href='{$urlDischargeCard}' id='printDischargeCard' in_patient_id='{$row['in_patient_id']}' class='btn btn-info' target='_blank'>Discharge Summary</a>";
        }

        $gotoSearch = "
        <div class='floatbox editTopButtonActionDiv'>
            <div class='float_left'>
                {$generateOrder}
                {$printPrescription}
                {$labReqReport}
                {$labReport}
                {$printDischargeCard}
                {$gotoOrder}
                {$cancelAdmission}
            </div>
        </div>";

        $ip_code = "";
        if($row['code'] != ""){
            $ip_code = "IP-{$row['code']}";
        }

        $appendSqlEmp = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlEmp = "AND site_id = {$cpSiteIdSession}";
        }

        $sqlEmployee = "
        SELECT employee_id
              ,CONCAT_WS(' ', first_name, middle_name, last_name) AS employee_name
        FROM employee
        WHERE (position = 'Doctor' OR position = 'Surgery')
        AND (category = 'Anaesthetist' OR category = 'Surgeon' OR category = 'Doctor' OR category = 'Consultant' OR category = 'Duty Doctor')
        AND status = 'Active'
        {$appendSqlEmp}
        ORDER BY employee_name
        ";

        $totalAmount = $rowEmpVisit['consultation_fees'] + $row['amount'] + $row['nursing_fees'] + $row['other_fees'] + $row['advance_payment'] + $row['oxygen_fees'] + $row['medicine_fees'];
        $totalAmount = number_format($totalAmount);
        $totalAmount = $totalAmount.'RS';

        $patSumCount = $fn->getRecordCount('patient_visit', "patient_information_id = '{$row['patient_information_id']}' AND status != 'Cancelled'");
        $pastVisit = '';

        if($patSumCount > 0){
            $viewOverallSummaryLink = "index.php?_topRm=main&module=hms_inPatient&_spAction=overallSummary&patient_visit_id={$row['patient_visit_id']}&patient_information_id={$row['patient_information_id']}&in_patient_id={$row['in_patient_id']}&showHTML=0";

            $pastVisit="<a href='{$viewOverallSummaryLink}' class='viewOverallSummary'><u>Past Summary</u></a>";
        }

        $inPatientSummary = '';
        $viewPatientSummaryLink = "index.php?_topRm=main&module=hms_inPatient&_spAction=inPatientSummary&patient_visit_id={$row['patient_visit_id']}&patient_information_id={$row['patient_information_id']}&in_patient_id={$row['in_patient_id']}&showHTML=0";
        $inPatientSummary="<a href='{$viewPatientSummaryLink}' class='viewPatientSummary'><u>IN Patient Summary</u></a>";

        $admissionTypeArray = array(
            "Normal"
           ,"Surgery"
        );

        $surgeryTypeArray = array(
            "Minor"
           ,"Major"
        );

        $sqlEmployeeTheaterAssistant = "
        SELECT CONCAT_WS(' ', first_name, middle_name, last_name) AS employee_name
        FROM employee
        WHERE (category = 'Theater Assistant')
        AND position = 'Surgery'
        AND status = 'Active'
        {$appendSqlEmp}
        ORDER BY employee_name
        ";

        $sqlEmployeeAnesthetists = "
        SELECT CONCAT_WS(' ', first_name, middle_name, last_name) AS employee_name
        FROM employee
        WHERE (category = 'Anaesthetist')
        AND position = 'Surgery'
        AND status = 'Active'
        {$appendSqlEmp}
        ORDER BY employee_name
        ";

        $sqlEmployeeSurgeon = "
        SELECT CONCAT_WS(' ', first_name, middle_name, last_name) AS employee_name
        FROM employee
        WHERE (category = 'Surgeon')
        AND position = 'Surgery'
        AND status = 'Active'
        {$appendSqlEmp}
        ORDER BY employee_name
        ";

        $sqlEmployeeOT = "
        SELECT CONCAT_WS(' ', first_name, middle_name, last_name) AS employee_name
        FROM employee
        WHERE (position = 'Doctor' OR position = 'Surgery')
        AND (category = 'Anaesthetist' OR category = 'Surgeon' OR category = 'Doctor' OR category = 'Consultant' OR category = 'Duty Doctor')
        AND status = 'Active'
        {$appendSqlEmp}
        ORDER BY employee_name
        ";

        $expVl = array('sqlType' => 'OneField');

        $totalSurgeryAmount = $row['anesthetic_fees'] + $row['surgeon_fees'] + $row['asst_surgeon_fees'] + $row['theatre_charges'] + $row['ot_charges'] + $row['referral_charges'] + $row['theater_assistant_fees'];
        $totalSurgeryAmount = number_format($totalSurgeryAmount);
        $totalSurgeryAmount = $totalSurgeryAmount.'RS';

        if ($_SESSION['userGroupName'] == 'Administrator' || $_SESSION['userGroupName'] == 'Super Administrator'){
            $patInfoMainFld1 = "
            <td width='15%'>{$formObj->getTBRow('Name', 'name', $row['patient_name'])}</td>
            <td width='15%'>{$formObj->getTBRow('Gender' , 'gender', $row['gender'])}</td>
            <td width='15%'>{$formObj->getTBRow('Mobile', 'patient_phone', $row['patient_phone'])}</td>
            <td width='35%'>
                <div class='ageBox'>{$formObj->getTBRow('Age (Yrs)' , 'age_year', $row['age_year'])}</div>
                <div class='ageBox'>{$formObj->getTBRow('(Month)' , 'age_month', $row['age_month'])}</div>
                <div class='ageBox'>{$formObj->getTBRow('(Days)' , 'age_day', $row['age_day'])}</div>
            </td>
            <td width='15%'>{$formObj->getTBRow('Father Name', 'father_name', $row['father_name'])}</td>
            ";
            $patInfoMainFld2 = "
            <td width='15%'>{$formObj->getTBRow('Husband Name', 'spuse_name', $row['spuse_name'])}</td>
            <td width='15%'>{$formObj->getTBRow('Town/City', 'address_area', $row['address_area'])}</td>
            ";            
        } else {
            $patInfoMainFld1 = "
            <td width='15%'>{$formObj->getTBRow('Name', 'name', $row['patient_name'], $expNoEdit)}</td>
            <td width='15%'>{$formObj->getTBRow('Gender' , 'gender', $row['gender'], $expNoEdit)}</td>
            <td width='15%'>{$formObj->getTBRow('Mobile', 'patient_phone', $row['patient_phone'], $expNoEdit)}</td>
            <td width='35%'>
                <div class='ageBox'>{$formObj->getTBRow('Age (Yrs)' , 'age_year', $row['age_year'], $expNoEdit)}</div>
                <div class='ageBox'>{$formObj->getTBRow('(Month)' , 'age_month', $row['age_month'], $expNoEdit)}</div>
                <div class='ageBox'>{$formObj->getTBRow('(Days)' , 'age_day', $row['age_day'], $expNoEdit)}</div>
            </td>
            <td width='15%'>{$formObj->getTBRow('Father Name', 'father_name', $row['father_name'], $expNoEdit)}</td>
            ";            
            $patInfoMainFld2 = "
            <td width='15%'>{$formObj->getTBRow('Husband Name', 'spuse_name', $row['spuse_name'], $expNoEdit)}</td>
            <td width='15%'>{$formObj->getTBRow('Town/City', 'address_area', $row['address_area'], $expNoEdit)}</td>
            ";            
        }

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $SqlForOrdAmount = "
        SELECT o.order_id
        FROM `order`o
        WHERE o.in_patient_id = {$row['in_patient_id']}
          AND o.order_type = 'POS'
        ";
        $resultOrdAmount = $db->sql_query($SqlForOrdAmount);
        $medAmount = 0;
        while ($rowOrdAmount = $db->sql_fetchrow($resultOrdAmount)) {
            $SqlForInvAmount = "
            SELECT SUM(inv.invoice_amount) AS invoiceAmount
            FROM invoice inv
            WHERE inv.order_id = {$rowOrdAmount['order_id']} 
              AND inv.status != 'Cancelled'
            ";
            $resultInvAmount = $db->sql_query($SqlForInvAmount);
            $rowInvAmount    = $db->sql_fetchrow($resultInvAmount);

            $medAmount += $rowInvAmount['invoiceAmount'];
        }
        $medAmount = number_format($medAmount, 2);

        //{$formObj->getDDRowBySQL('Gender', 'gender', $sqlGender, $row['gender'], $expVl)}
        $text = "
        {$gotoSearch}
        <div class='linkPortalWrapper'>
            <div expanded='1' class='header'>
                <div class='floatbox'>
                    <div class='float_left InvoiceToggleHeading'>In Patient Details</div>
                    <div class='toggle'></div>
                    <div class='float_right ml20'>{$ip_code}</div>
                    <div class='float_right createdModifiedEditTop'><b>Created By :</b> {$row['created_by']} on {$row['creation_date']}&nbsp;&nbsp;&nbsp;&nbsp;<b>Modified By:</b> {$row['modified_by']} {$row['modification_date']}</div>
                </div>
            </div>
            <div>
                <div class='linkPortalDataWrapper'>
                    <table class='thinlist'>
                        <tbody>
                            <tr>
                                {$patInfoMainFld1}
                            </tr>
                            <tr>
                                {$patInfoMainFld2}
                                <td width='10%'>{$formObj->getDDRowByArr('Status', 'status', $statusArray, $row['status'], $expNoEdit)}</td>
                                <td colspan='2'>
                                <div class='ageBox tempBox'>{$formObj->getTBRow('Wt (in kgs)', 'weight', $row['weight'])}</div>
                                <div class='ageBox tempBox'>{$formObj->getTBRow('Temp-°F', 'temperature', $row['temperature'])}</div>
                                <div class='ageBox tempBox'>{$formObj->getTBRow('PR', 'pulse_rate', $row['pulse_rate'])}</div>
                                <div class='ageBox tempBox'>{$formObj->getTBRow('RR', 'respiratory_rate', $row['respiratory_rate'])}</div>
                                <div class='ageBox tempBox'>{$formObj->getTBRow('BP', 'blood_pressure', $row['blood_pressure'])}</div>
                                <div class='ageBox tempBox'>{$formObj->getTBRow('CRT', 'crt', $row['crt'])}</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class='linkPortalWrapper'>
            <div expanded='1' class='header'>
                <div class='floatbox'>
                    <div class='float_left InvoiceToggleHeading'>
                        Admission Details
                    </div>
                    <div class='admissionTotalAmount'>
                        - {$totalAmount}
                    </div>
                    <div class='toggle'></div>
                </div>
            </div>
            <div>
                <div class='linkPortalDataWrapper'>
                    <table class='thinlist'>
                        <tbody>
                            <tr>
                                <td>{$formObj->getDDRowByArr('Admission Type', 'admission_type', $admissionTypeArray, $row['admission_type'])}</td>
                                <td>{$formObj->getDateRow('Date of Admission', 'date_admitted', $row['date_admitted'])}</td>
                                <td>{$formObj->getTimeRow('Time of Admitted', 'time_admitted', $row['time_admitted'])}</td>
                                <td>{$formObj->getDateRow('Date of Discharge', 'date_discharge', $row['date_discharge'])}</td>
                                <td>{$formObj->getTimeRow('Time of Discharge', 'time_of_discharge', $row['time_of_discharge'])}</td>
                            </tr>
                            <tr>
                                <td>{$formObj->getTBRow('Days Stayed', 'days_stayed', $row['days_stayed'])}</td>
                                <td>{$advanceRow}</td>
                                <td>{$feesRow}</td>
                                <td>{$roomRentRow}</td>
                                <td>{$nursingFeesRow}</td>
                            </tr>
                            <tr>
                                <td>{$oxygenFeesRow}</td>
                                <td>{$medicineFeesRow}</td>
                                <td>{$otherFeesRow}</td>
                                <td>{$formObj->getDDRowBySQL('Consultant Dr.', 'consultant_doctor', $sqlEmployee, $row['consultant_doctor'])}</td>
                                <td>{$consultantFeesRow}</td>
                            </tr>
                            <tr> 
                                <td>{$formObj->getDDRowBySQL('Review with Dr.', 'review_doctor', $sqlEmployee, $row['review_doctor'])}</td>
                                <td>{$formObj->getDateRow('Review Date', 'date_review', $row['date_review'])}</td>
                                <td>{$formObj->getDDRowBySQL('OT Staff', 'ot_staff', $sqlEmployeeOT, $row['ot_staff'], $expVl)}</td>
                                <td>{$otChargeRow}</td>
                                <td>{$formObj->getTARow('Advice On Discharge', 'advice_discharge', $row['advice_discharge'])}</td>
                            </tr>
                            <tr> 
                            <td>{$formObj->getDDRowBySQL('Ref Dr.', 'ref_doctor', $sqlEmployee, $row['ref_doctor'])}</td>
                                <td>{$formObj->getTARow('Summary', 'summary', $row['summary'])}</td>
                                <td><div class='medicineAmountDisplay'>Medicines Amount : {$medAmount}</div></td>
                                <input type='hidden' name='employee_in_patient_id' value='{$rowEmpVisit['employee_in_patient_id']}' />
                                <input type='hidden' name='in_patient_id' value='{$row['in_patient_id']}' />
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class='linkPortalWrapper'>
            <div expanded='1' class='header'>
                <div class='floatbox'>
                    <div class='float_left InvoiceToggleHeading'>
                        Surgery Details
                    </div>
                    <div class='surgeryDetailsTotalAmount'>
                        - {$totalSurgeryAmount}
                    </div>
                    <div class='toggle'></div>
                </div>
            </div>
            <div>
                <div class='linkPortalDataWrapper'>
                    <table class='thinlist'>
                        <tbody>
                            <tr>
                                <td>{$formObj->getDateRow('Date of Surgery', 'date_surgery', $row['date_surgery'])}</td>
                                <td>{$formObj->getDDRowBySQL('Anaesthetist', 'anesthetists', $sqlEmployeeAnesthetists, $row['anesthetists'], $expVl)}</td>
                                <td>{$anaFeesRow}</td>
                                <td>{$formObj->getDDRowBySQL('Surgeon', 'surgeon', $sqlEmployeeSurgeon, $row['surgeon'], $expVl)}</td>
                                <td>{$formObj->getDDRowByArr('Surgery Type', 'surgery_type', $surgeryTypeArray, $row['surgery_type'])}</td>
                                <td>{$surFeesRow}</td>
                            </tr>
                            <tr>    
                                <td>{$asstSurFeesRow}</td>
                                <td>{$formObj->getTBRow('Duration of Surgery', 'duration_of_surgery', $row['duration_of_surgery'])}</td>
                                <td>{$formObj->getTBRow('Name of Surgery', 'name_of_surgery', $row['name_of_surgery'])}</td>
                                <td>{$formObj->getDDRowBySQL('Theater Assistant', 'theater_assistant', $sqlEmployeeTheaterAssistant, $row['theater_assistant'], $expVl)}</td>
                                <td>{$theatreFeesRow}</td>
                                <td>{$theatreChargeRow}</td>
                            </tr>
                            <tr>
                                <td>{$formObj->getTimeRow('Start Time', 'start_time', $row['start_time'])}</td>
                                <td>{$formObj->getTimeRow('End Time', 'end_time', $row['end_time'])}</td>
                                <td>{$refChargeRow}</td>
                                <input type='hidden' name='in_patient_id' value='{$row['in_patient_id']}' />
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class='linkPortalWrapper'>
            <div expanded='1' class='header'>
                <div class='floatbox'>
                    <div class='float_left InvoiceToggleHeading'>
                        {$pastVisit}
                    </div>
                    <div>
                        {$inPatientSummary}
                    </div>
                    <div class='toggle'></div>
                </div>
            </div>
            <div>
                <div class='linkPortalDataWrapper'>
                    <div id='tabs' class='mb20'>
                        <ul>
                            {$medHisLbl}
                            {$medicineLbl}
                            {$medicalTestLbl}
                            {$theaterCaseLbl}
                            {$addDrLbl}
                        </ul>
                        {$medHisTab}
                        {$medicineTab}
                        {$medicalTestTab}
                        {$theaterCaseTab}
                        {$addDrTab}
                        <div class='tab-footer'>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id='patientVisitInvoicePortal'>
            {$invoicePortalDisplay}
        </div>

        {$actionButtons}
        <div id='patientVisitReceiptPortal'>{$receiptPortalDisplay}</div>
        <input type='hidden' id='fld_order_id' name='order_id' value='{$rowOrder['order_id']}' />
        ";

        return $text;
    }

    /**
     *
     */
    function getOverallSummary(){
        $formObj = Zend_Registry::get('formObj');
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $patient_information_id = $fn->getReqParam('patient_information_id');
        $patient_visit_id = $fn->getReqParam('patient_visit_id');
        $in_patient_id = $fn->getReqParam('in_patient_id');        

        $appendSqlPV = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            //$appendSqlPV = "AND pv.site_id = {$cpSiteIdSession}";
        }

        $SQLPatientVisit = "
        SELECT pv.*
        FROM patient_visit pv
        WHERE pv.patient_information_id = '{$patient_information_id}'
        AND pv.status != 'Cancelled'
        ORDER BY check_up_date DESC
        ";
        $resultPv   = $db->sql_query($SQLPatientVisit);
        $employee_id_count = '';
        $treatment = '';
        $employeeTitle = '';
        $PvText = '';
        $balance_Amount = '0.00';
        $overall_balance_Amount = '0.00';
        while ($rowPv = $db->sql_fetchrow($resultPv)) {
            $SQLEV = "
            SELECT Distinct ev.employee_id
                  ,CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
                  ,ev.patient_visit_id
            FROM employee_visit ev
            LEFT JOIN (employee e) ON (e.employee_id = ev.employee_id)
            WHERE ev.patient_visit_id = '{$rowPv['patient_visit_id']}'
            GROUP BY employee_name
            ";
            $resultEv = $db->sql_query($SQLEV);
            $drAttended = '';
            while ($rowEv = $db->sql_fetchrow($resultEv)) {
                $drAttended .=$rowEv['employee_name'] . ', ';
            }
            $dr_attended = rtrim($drAttended,', ');
            $dr_attended =  substr($dr_attended , 0, 9);

            $SQL1 = "
            SELECT mt.title
            ,mt.notes
            FROM medical_test_visit mt
            WHERE mt.patient_visit_id = '{$rowPv['patient_visit_id']}'
            ";
            $result1 = $db->sql_query($SQL1);
            $labTest = '';
            while ($rowTv = $db->sql_fetchrow($result1)) {
                $labTest .= $rowTv['title'] . '-' . $rowTv['notes'] .  ', ';
            }
            $labTest = rtrim($labTest,', ');

            $SQL2 = "
            SELECT mt.*
            FROM medicines_visit mt
            WHERE mt.patient_visit_id = '{$rowPv['patient_visit_id']}'
            ";
            $result2 = $db->sql_query($SQL2);
            $medTest = '';
            $instructionDisplay = '';
            while ($row2 = $db->sql_fetchrow($result2)) {
                if($row2['qty'] == 0 || $row2['qty'] == ''){
                    $qty = "{$row2['qty']}";
                } else {
                    $qty = "({$row2['qty']})";                    
                }

                if($row2['instruction'] != ''){
                    $instruction = explode(", ", $row2['instruction']);
                    $instructionLen = count($instruction);

                    $morning = 0;
                    $noon = 0;
                    $night = 0;

                    if($row2['dosage'] == ''){
                        $row2['dosage'] = 1;
                    }

                    for($i=0;$i<$instructionLen;$i++){
                        //print $instruction[$i];
                        if($instruction[$i] == 'Morning'){
                            $morning = $row2['dosage'];
                        }
                        if($instruction[$i] == 'Noon'){
                            $noon = $row2['dosage'];
                        }
                        if($instruction[$i] == 'Night'){
                            $night = $row2['dosage'];
                        }
                    }

                    if($row2['instruction'] == 'STAT' || $row2['instruction'] == 'SOS'){
                        $instructionDisplay = $row2['instruction'];
                    } else {
                        $instructionDisplay = $morning.' - '.$noon.' - '.$night;
                    }
                }

                $medTest .="<div>{$row2['title']}{$qty} {$instructionDisplay}</div>";
            }
            //$medTest = rtrim($medTest,', ');

            $check_up_date = $fn->getCPDate($rowPv['check_up_date'],"d-m-Y");

            $orderRec = $fn->getRecordByCondition('order', "patient_visit_id = '{$rowPv['patient_visit_id']}' AND order_type = 'OP'");
            $total_invoice_amount = '0.00';
            $invoiced_Paid_Amount = '0.00';

            if($orderRec['order_id'] != ''){
                $subSqlForPercentSum = "
                SELECT SUM(ini.unit_price) AS total_invoice_amount
                       ,inv.discount 
                FROM `invoice`inv
                LEFT JOIN invoice_item ini ON (ini.invoice_id = inv.invoice_id)
                WHERE inv.order_id = {$orderRec['order_id']}
                AND inv.status != 'Cancelled'
                AND ini.record_type = 'Doctor/Nurse'
                ";
                $resultSubSql = $db->sql_query($subSqlForPercentSum);
                $rowSql       = $db->sql_fetchrow($resultSubSql);

                $total_invoice_amount = $rowSql['total_invoice_amount'] - $rowSql['discount'];
                $total_invoice_amount = number_format($total_invoice_amount, 2);
            }

            if($rowPv['site_id'] == $cpSiteIdSession) {
                $visit_code_Link = "index.php?_topRm=main&module=hms_patientVisit&_action=edit&patient_visit_id={$rowPv['patient_visit_id']}";
                $visit_codePVt = "<a href='{$visit_code_Link}' target='_blank'><u>VST- {$rowPv['visit_code']}</u></a>";
            } else {
                $visit_codePVt = "VST- {$rowPv['visit_code']}</a>";
            }

            $viewSummaryTreatmentLink = "index.php?_topRm=main&module=hms_patientVisit&_spAction=viewSummaryTreatment&patient_visit_id={$rowPv['patient_visit_id']}&showHTML=0";
            $viewSummaryTreatment = "<a href='{$viewSummaryTreatmentLink}' class='viewSummaryForTreatmentRecord'><u>View Summary</u></a>";

            //$applyMedicineLink = "index.php?_topRm=main&module=hms_patientVisit&_spAction=applyMedicine&patient_visit_id={$rowPv['patient_visit_id']}&patient_visit_id_main={$patient_visit_id}&showHTML=0";
            $applyMedicine = '';
            if($medTest != '') {
                $applyMedicine = "<a href='#' class='btn btn-info applyForMedicineRecord' patient_visit_id={$rowPv['patient_visit_id']} in_patient_id={$in_patient_id}>Apply</a>";
            }

            $PvText .= "
            <tr>
                <td width='10%'>{$check_up_date}</td>
                <td width='10%'>{$visit_codePVt}</td>
                <td width='10%'>{$dr_attended}</td>
                <td width='15%'>{$rowPv['complain']}</td>
                <td width='37%'>{$applyMedicine}{$medTest}</td>
                <td width='13%'>{$labTest}</td>
                <td width='5%'>{$total_invoice_amount}</td>
                <td width='10%'>{$rowPv['notes']}</td>
            </tr>
            ";
        }

        $text = "
        <div class=''>
            <div>
                <div class='patientVisitSummaryPortal'>
                    <table class='thinlist mb20 overallSummaryPortal'>
                        <thead>
                            <tr>
                                <th class=''>Date</td>
                                <th class=''>Code</td>
                                <th class=''>Attended By</td>
                                <th class=''>Disease List</td>
                                <th class=''>Medicine List</td>
                                <th class=''>Lab Test</td>
                                <th class=''>Fees</td>
                                <th class=''>Notes</td>
                            </tr>
                        </thead>
                        <tbody>
                            {$PvText}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <input type='hidden' id='fld_patient_information_id' value='{$patient_information_id}'>
        ";

        return $text;
    }

    /**
     *
     */
    function getInPatientSummary(){
        $formObj = Zend_Registry::get('formObj');
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $patient_visit_id = $fn->getReqParam('patient_visit_id');
        $patient_information_id = $fn->getReqParam('patient_information_id');
        $in_patient_id = $fn->getReqParam('in_patient_id');        
        $rows = '';

        $appendSqlPV = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlPV = "AND pv.site_id = {$cpSiteIdSession}";
        }

        $SQLInPatient = "
        SELECT ip.*
        FROM in_patient ip
        WHERE ip.patient_information_id = '{$patient_information_id}'
        ORDER BY ip.date_admitted DESC
        ";
        $resultIP   = $db->sql_query($SQLInPatient);
        while ($rowIP = $db->sql_fetchrow($resultIP)) {
            
            $fees = $rowIP['consulting_fees'] + $rowIP['nursing_fees'] + $rowIP['other_fees'] + $rowIP['amount']; 
            $date_admitted = $fn->getCPDate($rowIP['date_admitted'],"d-m-Y");
            $SQLMV = "
            SELECT mv.*
            FROM medicines_in_patient mv
            WHERE mv.in_patient_id = {$in_patient_id}
            AND mv.treatment_given = 1
            ORDER BY mv.medicines_in_patient_id
            ";
            $resultMV = $db->sql_query($SQLMV);
            $count = 0;
            $title = '';
            $days = '';
            while ($rowMV = $db->sql_fetchrow($resultMV)) {

                if($rowMV['days'] != ''){
                    $days = $rowMV['days'];                    
                }

                if($rowMV['title'] != ''){
                    $title .= $rowMV['title']."( ".$days.") ".", ";
                }

                
            }
                    $title = rtrim($title, ", ");


            $rows .= "
            <tr>
                <td>{$date_admitted}</td>
                <td>{$rowIP['diagnosis']}</td>
                <td>{$title}</td>
                <td>{$fees}</td>
            </tr>
            ";
        }

        $text = "
        <div class=''>
            <div>
                <div class='inPatientSummaryPortal'>
                    <table class='thinlist mb20 overallSummaryPortal'>
                        <thead>
                            <tr>
                                <th class=''>In patient Date</td>
                                <th class=''>Diagnosis</td>
                                <th class=''>Discharge Treatment</td>
                                <th class=''>Fees</td>
                            </tr>
                        </thead>
                        <tbody>
                            {$rows}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <input type='hidden' id='fld_patient_information_id' value='{$patient_information_id}'>
        ";

        return $text;
    }

    /**
     *
     */
    function getRightPanel($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $displayLinkData = Zend_Registry::get('displayLinkData');
        $media = Zend_Registry::get('media');


        $record_id = $fn->getIssetParam($row, 'in_patient_id');

        $text = "
        {$media->getRightPanelMediaDisplay('Attachments', 'hms_inPatient', 'attachment', $row)}
        ";

        return $text;
    }

    /**
     *
     */
    function getQuickSearch() {
        $db      = Zend_Registry::get('db');
        $dbUtil  = Zend_Registry::get('dbUtil');
        $cpUtil  = Zend_Registry::get('cpUtil');
        $tv      = Zend_Registry::get('tv');
        $fn      = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');
        $cpCfg   = Zend_Registry::get('cpCfg');

        $status  = $fn->getReqParam('status');
        $admission_type  = $fn->getReqParam('admission_type');
        $currentMonth    = $fn->getReqParam('currentMonth');
         $date_admitted1     = $fn->getReqParam('date_admitted_1');
        $date_admitted2     = $fn->getReqParam('date_admitted_2');

        $statusArray = array(
            "Admitted"
           ,"Closed"
           ,"On Hold"
           ,"Cancelled"
        );

        $admissionTypeArray = array(
            "Normal"
           ,"Surgery"
        );

        $currMonthArray = array(
             "All Records"
            ,"CurrentMonth"
        );

        if($currentMonth == ""){
            $currentMonth = 'CurrentMonth';
        }

        $text = "
        <td>
            <select name='currentMonth'>
                {$cpUtil->getDropDown1($currMonthArray, $currentMonth)}
           </select>
        </td>
        <td>
            <select name='admission_type'>
                <option value=''>Admission Type</option>
                {$cpUtil->getDropDown1($admissionTypeArray, $admission_type)}
           </select>
        </td>
        <td>
            <select name='status'>
                <option value=''>Status</option>
                {$cpUtil->getDropDown1($statusArray, $status)}
           </select>
        </td>
        <td>
            {$formObj->getDateRangeRow('Admission Date:', 'date_admitted', $date_admitted1, $date_admitted2)}
        </td>
        ";

        return $text;
    }

    /**
     *
     */
    function getChiefComplainsDisplay($in_patient_id=''){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        if($in_patient_id == ''){
            $in_patient_id = $fn->getReqParam('in_patient_id');
        }

        $formAction          = "index.php?_topRm=main&module=hms_inPatient&_spAction=chiefComplainsSubmit&showHTML=0";
        $rows                = "";
        $inputRow            = "";
        $viewComplainListLink = "index.php?_topRm=main&module=hms_inPatient&_spAction=complainList&in_patient_id={$in_patient_id}&showHTML=0";
        $viewComplainList     = "<a href='{$viewComplainListLink}' class='viewComplainListRecord btn btn-info float_left'>Complain List</a>";
        $patientVisitRec     = $fn->getRecordRowByID('in_patient', 'in_patient_id', $in_patient_id);

        $text = "
        <div id='' class=''>
            <form></form>
            <form id='portalForm_complainDisplay' class='yform columnar invoiceForm' method='post' action='{$formAction}'>
                <div class='floatbox'>
                    <div class='addComplain float_left mb10'>
                        <input type='text' value='' id='fld_complain_title' class='text' name='complain_title' placeholder='Add Complain' in_patient_id={$in_patient_id}>
                    </div>
                    <div class='float_left'>
                        <input class='btn btn-info complainSave' type='button' value='Add' name='portalForm' />
                    </div>
                    <div class='float_left'>
                        <input class='btn btn-warning' type='submit' value='Save' name='portalForm' />
                    </div>
                </div>

                <!--<div class='type-button floatbox'>
                    <input class='button float_left' type='submit' value='Save' name='portalForm' />
                    {$viewComplainList}
                </div>-->
                <div class=''>
                    {$formObj->getTARow('Complain', 'complain', $patientVisitRec['complain'])}
                </div>
                <input type='hidden' name='in_patient_id' value='{$in_patient_id}' />
            </form>
        </div>
        ";

        return $text;
    }

    /**
     *
     */
    function getProcedurePortalDisplay($in_patient_id=''){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        if($in_patient_id == ''){
            $in_patient_id = $fn->getReqParam('in_patient_id');
        }

        $formAction          = "index.php?_topRm=main&module=hms_inPatient&_spAction=procedurePortalSubmit&showHTML=0";
        $rows                = "";
        $inputRow            = "";
        $patientVisitRec     = $fn->getRecordRowByID('in_patient', 'in_patient_id', $in_patient_id);

        $text = "
        <div id='' class=''>
            <form></form>
            <form id='portalForm_procedureDisplay' class='yform columnar invoiceForm' method='post' action='{$formAction}'>
                <div class='floatbox'>
                    <div class='addProcedure float_left mb10'>
                        <input type='text' value='' id='fld_procedure_title' class='text' name='procedure_title' placeholder='Add Procedure' in_patient_id={$in_patient_id}>
                    </div>
                    <div class='float_left'>
                        <input class='btn btn-info procedureSave' type='button' value='Add' name='portalForm' />
                    </div>
                    <div class='float_left'>
                        <input class='btn btn-warning' type='submit' value='Save' name='portalForm' />
                    </div>
                </div>

                <div class=''>
                    {$formObj->getTARow('Procedure', 'in_patient_procedure', $patientVisitRec['in_patient_procedure'])}
                </div>
                <input type='hidden' name='in_patient_id' value='{$in_patient_id}' />
            </form>
        </div>
        ";

        return $text;
    }

    /**
     *
     */
    function getSummaryPortalDisplay($in_patient_id=''){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        if($in_patient_id == ''){
            $in_patient_id = $fn->getReqParam('in_patient_id');
        }

        $formAction          = "index.php?_topRm=main&module=hms_inPatient&_spAction=summaryPortalSubmit&showHTML=0";
        $rows                = "";
        $inputRow            = "";
        $viewDiseaseListLink = "index.php?_topRm=main&module=hms_inPatient&_spAction=diseaseList&in_patient_id={$in_patient_id}&showHTML=0";
        $viewDiseaseList     = "<a href='{$viewDiseaseListLink}' class='viewDiseaseListRecord btn btn-info float_left'>Diagnosis List</a>";
        $patientVisitRec     = $fn->getRecordRowByID('in_patient', 'in_patient_id', $in_patient_id);

        $text = "
        <div id='' class=''>
            <form></form>
            <form id='portalForm_summaryDisplay' class='yform columnar invoiceForm' method='post' action='{$formAction}'>
                <div class='floatbox'>
                    <div class='addDiagnosis float_left mb10'>
                        <input type='text' value='' id='fld_diagnosis_title' class='text' name='diagnosis_title' placeholder='Add Diagnosis' in_patient_id={$in_patient_id}>
                    </div>
                    <div class='float_left'>
                        <input class='btn btn-info diagnosisSave' type='button' value='Add' name='portalForm' />
                    </div>
                    <div class='float_left'>
                        <input class='btn btn-warning' type='submit' value='Save' name='portalForm' />
                    </div>
                </div>
                <!--<div class='type-button floatbox'>
                    <input class='button float_left' type='submit' value='Save' name='portalForm' />
                    {$viewDiseaseList}
                </div>-->
                <div class=''>
                    {$formObj->getTARow('Diagnosis', 'diagnosis', $patientVisitRec['diagnosis'])}
                </div>
                <input type='hidden' name='in_patient_id' value='{$in_patient_id}' />
            </form>
        </div>
        ";

        return $text;
    }

    /**
     *
     */
    function getMedicinesPortalDisplay($in_patient_id=''){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $cpUtil = Zend_Registry::get('cpUtil');

        if($in_patient_id == ''){
            $in_patient_id = $fn->getReqParam('in_patient_id');
        }

        $formAction = '';
        $rows  = "";
        $rowsPvt  = "";
        $stock = '';

        $sqlInstruction = $fn->getValueListSQL('instruction');

        $appendSqlEmp = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlEmp = "AND e.site_id = {$cpSiteIdSession}";
        }

        $sqlDoctor = "
        SELECT e.employee_id
              ,CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
        FROM employee e
        WHERE e.position IN ('Doctor', 'Nurse')
        {$appendSqlEmp}
        ORDER BY e.employee_id
        ";

        $SQL = "
        SELECT mv.*
              ,CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
        FROM medicines_in_patient mv
        LEFT JOIN (employee e) ON (e.employee_id = mv.employee_id)
        WHERE mv.in_patient_id = {$in_patient_id}
        ORDER BY mv.medicines_in_patient_id
        ";
        $result   = $db->sql_query($SQL);

        $SQLRoute      = $fn->getValueListSQL('route');
        $SQLdosage      = $fn->getValueListSQL('dosage');
        $amount = 0;

        while ($rowMV = $db->sql_fetchrow($result)) {
            $editURL = "index.php?_topRm=main&module=hms_inPatient&_spAction=editMedicineRecord&showHTML=0&in_patient_id={$rowMV['in_patient_id']}&medicines_in_patient_id={$rowMV['medicines_in_patient_id']}";
            $editRow = "<td><a href='{$editURL}' id='editMedicineRecord' in_patient_id={$rowMV['in_patient_id']}>Edit</a></td>";
                /*<td class='title'>
                    <input type='text' value='{$rowMV['title']}' name='title' disabled>
                </td>*/
            $medicine_Link = "index.php?_topRm=utils&module=hms_product&_action=edit&product_id={$rowMV['product_id']}";     

            if($rowMV['continue_medicine'] == 1){
                $checked = "checked='checked'";
            } else {
                $checked = '';       
            }

            if($rowMV['treatment_given'] == 1){
                $checkedTreatment = "checked='checked'";
            } else {
                $checkedTreatment = '';       
            }

            $SQLPrice = "
            SELECT pop.cost_price
            FROM po_product pop
            WHERE pop.product_id = '{$rowMV['product_id']}'
            ORDER BY pop.po_product_id DESC
            ";
            $resultPrice  = $db->sql_query($SQLPrice);
            $rowPrice     = $db->sql_fetchrow($resultPrice);

            $amount += $rowPrice['cost_price'] * $rowMV['qty'];

            $rows .= "
            <tr recid='{$rowMV['medicines_in_patient_id']}' product_id='{$rowMV['product_id']}' class='portal-row2 row-hms_inPatient__hms_product'>
                <td class='employee_id'>
                    <select name='employee_id'>
                        <option value=''>Please Select</option>
                        {$dbUtil->getDropDownFromSQLCols2($db, $sqlDoctor, $rowMV['employee_id'])}
                    </select>
                </td>
                <td class='title'>
                    <a href='{$medicine_Link}' target='_blank'><u>{$rowMV['title']}</u></a>
                </td>
                <td class='route'>
                    <select name='route' >
                        <option value=''>Select</option>
                            {$dbUtil->getDropDownFromSQLCols1($db, $SQLRoute, $rowMV['route'])}
                    </select>
                </td>
                <td class='dosage'>
                    <!--<select name='dosage'>
                        <option value=''>Select</option>
                        {$dbUtil->getDropDownFromSQLCols1($db, $SQLdosage, $rowMV['dosage'])}
                    </select>-->
                    <input type='text' value='{$rowMV['dosage']}' name='dosage'>
                </td>
                <td class='qty'>
                    <input type='text' value='{$rowMV['qty']}' name='qty' in_patient_id={$rowMV['in_patient_id']}>
                </td>
                <td class='instruction'>
                    <select name='instruction'>
                        <option value=''>Please Select</option>
                        {$dbUtil->getDropDownFromSQLCols1($db, $sqlInstruction, $rowMV['instruction'])}
                    </select>
                </td>
                <td class='days'>
                    <input type='text' value='{$rowMV['days']}' name='days'>
                </td>
                <td class='continue_medicine'>
                    <input  class='check' type='checkbox' value='{$rowMV['continue_medicine']}' name='continue_medicine' {$checked}>
                </td>
                <td class='treatment_given'>
                    <input  class='check' type='checkbox' value='{$rowMV['treatment_given']}' name='treatment_given' {$checkedTreatment}>
                </td>
                <td><a href='#' class='deleteMedicineRecord' medicines_in_patient_id='{$rowMV['medicines_in_patient_id']}' in_patient_id={$rowMV['in_patient_id']}><u>Delete</u></a></td>
            </tr>
            ";
        }

        $amount = number_format($amount, 2);

        $header ="
        <tr style='background-color:#EAEAE8;'>
            <th width='8%'>Dr</th>
            <th width='25%'>Medicine Name</th>
            <th width='7%'>Route</th>
            <th width='10%'>Dosage</th>
            <th width='5%'>Qty</th>
            <th width='22%'>Instruction</th>
            <th width='5%'>Days</th>
            <th width='5%'>Cont</th>
            <th width='5%'>Trtmt</th>
            <th width='8%'>Delete</th>
        </tr>
        ";

        $text = "
        <form></form>
        <div class='addExistingMedicine float_left mb10'>
            <input type='text' value='' id='fld_product_title' class='text' name='product_title' placeholder='Add Existing Medicine' in_patient_id={$in_patient_id}>
        </div>
        <div class='addNewMedicine float_left'>
            <input type='text' value='' id='fld_product_title' class='text' name='product_title_new' placeholder='Add New Medicine'>
        </div>
        <div class='float_left'>
            <input class='btn btn-info newProductTitle' type='button' value='Create' name='portalForm' />
        </div>
        <div class='float_left'>
            <input class='btn btn-warning medicineSave' type='button' value='Save' name='portalForm' />
        </div>

        <div class='float_left medicineAmountDisplay'>
            Amount : {$amount}
        </div>

        <div id='' class='doctorDisplay'>
            <form id='' class='' method='post' action='{$formAction}'>
                <div id='doctorPortalOuter'>
                    <table class='thinlist'>
                        {$header}
                        {$rows}
                    </table>
                </div>
            </form>
        </div>
        ";

        return $text;
    }

    /**
     */
    function getMedicalPortalDisplay($in_patient_id=''){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');
        $media = Zend_Registry::get('media');

        if($in_patient_id == ''){
            $in_patient_id = $fn->getReqParam('in_patient_id');
        }

        $formAction = "index.php?_topRm=main&module=hms_inPatient&_spAction=MedicalTestRecordSubmit&showHTML=0";
        $rows  = "";
        $catRows = '';
        $catLinks = '';

        $SQLCat = "
        SELECT value
              ,valuelist_id
        FROM valuelist
        WHERE key_text = 'investigationCategory'
          AND value != 'Vaccination'
        ORDER BY sort_order
        ";
        $resultCat   = $db->sql_query($SQLCat);
        $count = 0;
        while ($rowCat = $db->sql_fetchrow($resultCat)) {
            $SQLMedicalTest = "
            SELECT m.medical_test_id
                  ,m.title
                  ,m.fees
                  ,m.lab_supplier_fees
                  ,m.container
                  ,mtp.fees AS fees_history
                  ,mtp.medical_test_in_patient_id
                  ,mtp.notes
                  ,mtp.title AS title_history
                  ,mtp.test_repeat
                  ,mtp.creation_date
            FROM medical_test m
            LEFT JOIN medical_test_in_patient mtp ON (mtp.medical_test_id = m.medical_test_id AND mtp.in_patient_id = {$in_patient_id})
            WHERE m.category = '{$rowCat['value']}'
            ORDER BY m.title, mtp.medical_test_in_patient_id
            ";

            $result   = $db->sql_query($SQLMedicalTest);
            $inputRow  = "";
            $title ="{$rowCat['value']}";
            $countrow=1;
            $countAgain = 0;
            while ($row = $db->sql_fetchrow($result)) {
                $medTestVisitRec = $fn->getRecordByCondition('medical_test_in_patient', "in_patient_id = '{$in_patient_id}' AND title='{$row['title']}'");

                if($row['medical_test_in_patient_id'] != ''){
                    $checked = "checked='checked'";
                    $class ="";
                    $bgColor = "bgColorHighlight";
                } else {
                    $checked = '';
                    $class ="displayNone";
                    $bgColor = '';
                }

                if($medTestVisitRec['supplier_id'] > 0){
                    $checked1 = "checked='checked'";
                    $class1 ="";
                } else {
                    $checked1 = '';
                    $class1 ="displayNone";
                }

                if($row['notes'] != ''){
                    $notes = 'View Result';
                }else {
                    $notes = 'Add Result';
                }

                if($row['fees_history'] != ''){
                    $fees = $row['fees_history'];
                }else {
                    $fees = $row['fees'];
                }

                if($row['creation_date'] != ''){
                    $creationDate = $fn->getCPDate($row['creation_date'],"Y-m-d");
                }else {
                    $creationDate = date("Y-m-d");
                }

                        /*<div class='hideTreatmentDetails_{$row['title']}_{$count} hideLabDetails {$class} labVisitNotes'>
                            <input type='text' value='{$fees}' id='fld_fees' class='text mt10 mb10' name='fees[]'>
                            <div><a href='#' class='addNoteLab'>{$notes}</a></div>
                            <div class='hideNotesLab'>
                                <div class='type-text ym-fbox-text row_notes'>
                                    <textarea id='fld_notes' name='notes[]'>{$medTestVisitRec['notes']}</textarea>
                                </div>
                            </div>
                        </div>*/
                $divSeparation = '';
                if($countrow == 5){
                    $divSeparation = "</div><div class='col-md-4 col-sm-4'>";
                    $countrow = 0;
                }
                $SQLMTP = "
                SELECT m.medical_test_parameter_id
                      ,m.title
                      ,m.normal_value
                      ,m.medical_test_id
                FROM medical_test_parameter m
                WHERE m.medical_test_id = '{$row['medical_test_id']}'
                ORDER BY m.title
                ";

                $resultMTP   = $db->sql_query($SQLMTP);
                $numRows = $db->sql_numrows($resultMTP);
                $resultShow = '';
                $classResult = '';
                $view = '';
                if($numRows > 0){
                    $classResult = "hideme";

                    $viewMedPara = "index.php?_topRm=main&module=hms_inPatient&_spAction=viewMedicalParameters&in_patient_id={$in_patient_id}&medical_test_id={$row['medical_test_id']}&test_repeat={$row['test_repeat']}&showHTML=0";

                    $view = "<a href='{$viewMedPara}' class='viewMedPara float_right'><u>Add Result</u></a>";
                }

                $takeTestAgain = '';
                if($row['title_history'] != $row['title']){
                    $countAgain = 0;
                }

                if($row['title_history'] == $row['title'] && $countAgain == 0){
                    $takeTestAgain = "<a href='#' class='testAgain' in_patient_id = '{$in_patient_id}' medical_test_id='{$row['medical_test_id']}'><u>Take Test Again</u></a>";
                    $countAgain++;
                }
                $investigation_date_id = 'investigation_date_'.$row['medical_test_id'];
                $hospitalFees = $fees - $row['lab_supplier_fees'];
                $supplierRec = $fn->getRecordByCondition('supplier', "category = 'Lab'");
                
                $inputRow .= "
                <div class='medTestTitle'>
                    <div class='type-check ym-fbox-check labTestBox {$bgColor}'>
                        <input type='checkbox' id='title_{$count}' {$checked} value='{$row['title']}_{$count}' name='title[]' class='labTitle' in_patient_id = '{$in_patient_id}' medical_test_id='{$row['medical_test_id']}' test_repeat = '{$row['test_repeat']}'>
                        <label for='title_{$count}'>{$row['title']}</label>
                        <div class='hideTreatmentDetails_{$row['title']}_{$count} hideLabDetails {$class} labVisitNotes'>
                            {$view}
                            <div>
                                <label>Fees</label>
                                <input type='text' value='{$fees}' id='fld_fees' class='labFees text mt10 mb10' name='fees[]' in_patient_id = '{$in_patient_id}' medical_test_id='{$row['medical_test_id']}' test_repeat = '{$row['test_repeat']}'>
                            </div>
                            <div>
                                {$formObj->getDateRow('Date (Year-Month-Date)', $investigation_date_id, $creationDate)}
                                <input type='hidden' value='{$row['medical_test_id']}' name='medical_test_id' />
                                <input type='hidden' value='{$in_patient_id}' name='in_patient_id' />
                                <input type='hidden' value='{$row['test_repeat']}' name='test_repeat' />
                            </div>
                            <div class='{$classResult}'>
                                <label>Result</label>
                                <div class='type-text ym-fbox-text row_notes'>
                                    <textarea id='fld_notes' class='labNotes' name='notes[]' in_patient_id = '{$in_patient_id}' medical_test_id='{$row['medical_test_id']}' test_repeat = '{$row['test_repeat']}'>{$row['notes']}</textarea>
                                </div>
                            </div>
                            <div>
                                <input type='checkbox' id='' {$checked1} value='{$row['lab_supplier_fees']}' name='other_labs[]' class='otherLabs' in_patient_id = '{$in_patient_id}' medical_test_id='{$row['medical_test_id']}' supplier_id = {$supplierRec['supplier_id']} container = '{$row['container']}'>
                                <label for='{$row['medical_test_id']}'>Other Labs</label>
                            </div>
                            <div class='hideLabSupplierFees {$class1}'>
                                <div>
                                    <label>Lab Supplier Fees : {$row['lab_supplier_fees']}<label>
                                </div>
                                <div>
                                    <label>Hospital Fees : {$hospitalFees}<label>
                                </div>
                                <div>
                                    <label>Container : {$row['container']}<label>
                                </div>
                            </div>
                        </div>

                        <input type='hidden' value='{$row['medical_test_id']}' name='medical_test_id[]' />
                    </div>
                    <div class=''>{$takeTestAgain}</div>
                </div>
                {$divSeparation}
                ";
                $count ++;
                $countrow ++;
            }
            $catRows .= "
            <div class='panel panel-default'>
                <div class='panel-heading' id='{$title}'>
                    <strong>{$title}</strong>
                    <a href='#saveBtn' class='ml20'><u>Go to Top</u></a>
                    <a class='medTestMainSubmit btn btn-info ml20' in_patient_id = '{$in_patient_id}'>Save</a>
                </div>
                <div class='panel-body'><div class='floatbox col-md-4 col-sm-4'>{$inputRow}</div></div>
            </div>
            ";
            //$catLinks .= "<a href='#{$title}' class='mr20'><u>{$title}</u></a>";
            $catLinks .= "<li class='ui-state-default'><a href='#{$title}' valuelist_id='{$rowCat['valuelist_id']}' class='investigationCategoryLink mr20'><u>{$title}</u></a></li>";
        }

        $patientVisitRec = $fn->getRecordByCondition('in_patient', "in_patient_id = '{$in_patient_id}'");
        $text = "
        <div id='' class=''>
        <form></form>
            <form id='portalForm_medicalTestDisplay' class='yform columnar invoiceForm' method='post' action='{$formAction}'>
                <div class='type-button' id='saveBtn'></div>
                <ul id='sortableInvesticationCategories'>  
                    {$catLinks}
                </ul>
                <br/><br/><br/>
                <div class='floatbox'>{$catRows}</div>
                <input type='hidden' name='in_patient_id' value='{$in_patient_id}' />
            </form>
            <script type='text/javascript'>
                jQuery('document').ready(function () {
                    cpm.hms.inPatient.sortInvestigationGroups();
                });
            </script>
        </div>
        ";
                /*<div class='type-button' id='saveBtn'>
                    <input class='button' type='submit' value='Save' name='portalForm' />
                </div>*/

        return $text;
    }
    /**
     *
     */
    function getViewMedicalParameters() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');

        $medical_test_id = $fn->getReqParam('medical_test_id');
        $in_patient_id = $fn->getReqParam('in_patient_id');
        $test_repeat = $fn->getReqParam('test_repeat');

        if($test_repeat == ''){
            $test_repeat = 0;
        }

        $SQLMTP = "
        SELECT m.medical_test_parameter_id
              ,m.title
              ,m.normal_value
              ,m.medical_test_id
        FROM medical_test_parameter m
        WHERE m.medical_test_id = '{$medical_test_id}'
        ORDER BY m.medical_test_parameter_id
        ";

        $resultMTP   = $db->sql_query($SQLMTP);
        $numRows = $db->sql_numrows($resultMTP);
        $rowTitle = '';
        while ($rowMTP = $db->sql_fetchrow($resultMTP)) {
            $medVisitParaRec = $fn->getRecordByCondition('medical_visit_parameter', "medical_test_parameter_id = '{$rowMTP['medical_test_parameter_id']}' AND medical_test_id='{$rowMTP['medical_test_id']}' AND in_patient_id = {$in_patient_id} AND test_repeat = {$test_repeat}");

            $rowTitle .= "
            <div class='type-text ym-fbox-text row_notes medParaList'>
                <label>{$rowMTP['title']}</label> 
                <textarea id='fld_para_notes' class='med_para_notes' medical_test_id ='{$rowMTP['medical_test_id']}' medical_test_parameter_id={$rowMTP['medical_test_parameter_id']} in_patient_id = {$in_patient_id} test_repeat = {$test_repeat} name='para_notes[]'>{$medVisitParaRec['notes']}</textarea>
            </div>
            <input type='hidden' value='{$rowMTP['medical_test_parameter_id']}' name='medical_test_parameter_id[]' />
            <input type='hidden' value='{$rowMTP['medical_test_id']}' name='medical_test_id_para[]' />
            ";
        }
        $medTestVisitRecCon = $fn->getRecordByCondition('medical_test_in_patient', "in_patient_id = '{$in_patient_id}' AND medical_test_id='{$medical_test_id}' AND test_repeat = {$test_repeat}");
        if($medTestVisitRecCon['medical_test_in_patient_id'] != ''){
            $text = "
            {$rowTitle}
            <div><a class='medParaSubmit btn btn-info'>Save</a></div>
            ";
        } else{
            $text = "
            Please click save.
            ";            
        }


        return $text;
    }

    /**
     *
     */
    function getPrintPrescription() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        ini_set('memory_limit', '512M');
        set_time_limit(50000);

        include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
        include_once(CP_LOCAL_PATH.'lib/headfoot3.php');

        $pdf = new MYPDF_Local(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('USS');
        $pdf->SetSubject('Print Prescription');
        $pdf->SetTitle('Print Prescription');

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 04', PDF_HEADER_STRING);
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER,10);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 13);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        //$pdf->setPrintFooter(false);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        /*HEADER PART AND FOOTER PART FUNCTIONS HAS BEEN ADDED IN (headfoot.php) PATH INCLUDE: (admin/lib/headfoot.php)*/
        $pdf->AddPage('P', 'A5');

        $in_patient_id = $fn->getReqParam('in_patient_id');

        $SQL = "
        SELECT pv.*
              ,p.name AS patient_name
              ,p.age_year
              ,p.age_month
              ,p.age_day
              ,p.gender
              ,m.title AS medicine
              ,m.instruction
              ,m.dosage
              ,m.route
              ,m.qty
            FROM in_patient pv
            LEFT JOIN medicines_in_patient m ON (m.in_patient_id = pv.in_patient_id)
            LEFT JOIN (patient_information p) ON (p.patient_information_id = pv.patient_information_id)
            WHERE pv.in_patient_id = '{$in_patient_id}'
            ORDER BY 
            CASE
                WHEN (m.title LIKE 'inj.%' OR 
                      m.title LIKE '%.inj' OR
                      m.title LIKE 'inj %' OR
                      m.title LIKE '% inj'
                      ) THEN 1
                ELSE m.title
            END
        ";
        $result = $db->sql_query($SQL);
        $result2 = $db->sql_query($SQL);
        $company = $db->sql_fetchrow($result2);
        //============================================================================= //

        $pdf->SetFont('Courier','B',10);

        $today = date("d-m-Y");
        $gender = '';
        if($company['gender'] == 'Female'){
            $gender = 'F';
        }else if($company['gender'] == 'Male'){
            $gender = 'M';            
        }

        $currentDate   = date("d-m-Y");

        $visit_code = '';
        if($company['code'] != ''){
            $visit_code = 'IP-'.$company['code'];
        }

        $age = '';

        if($company['age_year'] != ''){
            $age = $company['age_year'].' Yrs';
        } elseif($company['age_month'] != ''){
            $age = $company['age_month'].' Months';
        } elseif($company['age_day'] != ''){
            $age = $company['age_day'].' Days';
        }

        $doctorName = 'DR.SHEIK ABDUL KHADER';
        /*if($company['first_name'] == 'DR.SHEIK ABDUL KHADER'){
            $doctorName = $company['first_name'];
        }*/
        
        $tbl1 = '
        <table border="0" width="100%" style="border-bottom:2px solid #000000; padding-bottom:3px;">
            <tr>
                <td width="67%">Pt Name : '.$company['patient_name'].'('.$gender .'/'.$age.')</td>
                <td width="33%" align="left">Date : '.$currentDate.'</td>
            </tr>
            <tr>
                <td width="67%" align="">'.$doctorName.'</td>
                <td width="33%" align="left">Code : '.$visit_code.'</td>
            </tr>
        </table>
        <table border="0" width="120%">
            <tr>
                <td align="right">Page '.$pdf->getAliasNumPage().'/'.$pdf->getAliasNbPages().'</td>
            </tr>
        </table>
        ';
        
        $fbsVal = $fn->getRecordByCondition('medical_test_in_patient', "in_patient_id = '{$in_patient_id}' AND title='FBS'");
        $rbsVal = $fn->getRecordByCondition('medical_test_in_patient', "in_patient_id = '{$in_patient_id}' AND title='RBS'");
        $ppVal = $fn->getRecordByCondition('medical_test_in_patient', "in_patient_id = '{$in_patient_id}' AND title='POST PRANDIAL'");

        $blood_pressure = '';
        if($company['blood_pressure'] != ''){
            $blood_pressure = $company['blood_pressure'].' mm/hg';
        }
        $fbsVal_notes = '';
        if($fbsVal['notes'] != ''){
            $fbsVal_notes = $fbsVal['notes'].' mg/dl';
        }
        $rbsVal_notes = '';
        if($rbsVal['notes'] != ''){
            $rbsVal_notes = $rbsVal['notes'].' mg/dl';
        }
        
        $ppVal_notes = '';
        if($ppVal['notes'] != ''){
            $ppVal_notes = $ppVal['notes'].' mg/dl';
        }
       $tbl2 = '
        <table border="0" width="100%" style="padding-top:10px;">
            <tr>
                <td width="18%" align="left">T- '.$company['temperature'] .'</td>
                <td width="35%" align="left">BP- '.$blood_pressure.'</td>
                <td width="22%" align="left">PR- '.$company['pulse_rate'].'</td>
                <td width="25%" align="left">WT- '.$company['weight'].'Kgs</td>
            </tr>
        </table>
        <table border="0" width="100%">
            <tr>
                <td width="33%" align="left">FBS- '.$fbsVal_notes.'</td>
                <td width="33%" align="left">RBS- '.$rbsVal_notes.'</td>
                <td width="33%" align="left">PP- '.$ppVal_notes.'</td>
            </tr>
        </table>
        ';

        /*$tblHead ='
        <table border="1" width="100%" cellpadding="5">
            <tr>
                <td width="13%">BILL NO :</td>
                <td width="37%">'.$orderNo.'</td>
                <td width="9%">DATE :</td>
                <td width="41%">'.$company['invoice_creation_date'].'</td>
            </tr>
        </table>
        ';*/

        $tbl3 = '';

        $tbl3 ='<table border="0" width="100%" cellpadding="4" style="border-top:1px solid #000000;">';

        $sub_total = 0;
        $count = 1;
        $total_qty = 0;
        $discount = 0;
        $discountValueTotal = 0;

        while ($row = $db->sql_fetchrow($result)) {
            if($row['medicine'] != '' && $row['instruction'] != ''){
                /*if($count == 1){
                    $tbl3 ='<table border="0" width="100%" cellpadding="4">';
                }*/
                $instruction = explode(", ", $row['instruction']);
                //print_r($instruction);
                $instructionLen = count($instruction);
                //print($instructionLen);

                $morning = 0;
                $noon = 0;
                $night = 0;

                if($row['dosage'] == ''){
                    $row['dosage'] = 1;
                }

                for($i=0;$i<$instructionLen;$i++){
                    //print $instruction[$i];
                    if($instruction[$i] == 'Morning'){
                        $morning = $row['dosage'];
                    }
                    if($instruction[$i] == 'Noon'){
                        $noon = $row['dosage'];
                    }
                    if($instruction[$i] == 'Night'){
                        $night = $row['dosage'];
                    }
                }

                if($row['qty'] == 0 || $row['qty'] == ''){
                    $qty = "{$row['qty']}";
                }else {
                    $qty = "({$row['qty']})";                    
                }

                $route = substr($row['route'], 0, 3);
                if($row['instruction'] == 'STAT' || $row['instruction'] == 'SOS'){
                    $tbl3 = $tbl3.'<tr>
                                        <td align="left" width="35%">'.$row['medicine'].'</td>
                                        <td align="left" width="10%">'.$qty.'</td>
                                        <td align="left" width="8%">'.$route.'</td>
                                        <td align="center" width="47%">'.$row['instruction'].'</td>
                                    </tr>
                                    ';
                } else {
                    $tbl3 = $tbl3.'<tr>
                                        <td align="left" width="35%">'.$row['medicine'].'</td>
                                        <td align="left" width="10%">'.$qty.'</td>
                                        <td align="left" width="8%">'.$route.'</td>
                                        <td align="center" width="13%">'.$morning.'</td>
                                        <td align="center" width="4%">-</td>
                                        <td align="center" width="13%">'.$noon.'</td>
                                        <td align="center" width="4%">-</td>
                                        <td align="center" width="13%">'.$night.'</td>
                                    </tr>
                                    ';
                }
                if($count == 1){
                    //$tbl3 = $tbl3.'</table>';
                }
            }
            $count++;
        }
                    $tbl3 = $tbl3.'</table>';
        $SQL1 = "
        SELECT mt.*
              ,m.normal_value
        FROM medical_test_in_patient mt
        LEFT JOIN medical_test m ON (m.medical_test_id = mt.medical_test_id)
        WHERE mt.in_patient_id = '{$in_patient_id}'
        ";
        $result1 = $db->sql_query($SQL1);
        $tbl4 = '
        <table cellpadding="4" border="0">
            <thead>
            <tr><th colspan="3" style="text-decoration:underline;">Reports:</th></tr>
            <tr>
                <th style="text-decoration:underline;">TEST</th>
                <th style="text-decoration:underline;">FINDINGS</th>
                <th style="text-decoration:underline;">REF-VALUE</th>
            </tr>
            </thead>
        ';
        while ($row1 = $db->sql_fetchrow($result1)) {

            /*$tbl4 = $tbl4.'<tr>
                                <td align="left">'.$row1['title'].'</td>
                                <td align="left">'.$row1['notes'].'</td>
                                <td align="left">'.$row1['normal_value'].'</td>
                            </tr>
                            ';*/
        }

        $tbl4 = $tbl4.'</table>';

        $pdf->writeHTML($tbl1, false, false, false, false, '');
        $pdf->writeHTML($tbl2, true, false, false, false, '');
        //$pdf->writeHTML($tblHead, true, false, false, false, '');
        $pdf->writeHTML($tbl3, false, false, false, false, '');
        //$pdf->writeHTML($tbl4, true, false, false, false, '');
        $download_title = 'Prescription.pdf';
        ob_start();
        $pdf->Output($download_title, 'I');
    }

    /**
     *
     */
    function getPrintLabReport() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        ini_set('memory_limit', '512M');
        set_time_limit(50000);

        include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
        include_once(CP_LOCAL_PATH.'lib/headfoot1.php');

        $pdf = new MYPDF_Local(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('USS');
        $pdf->SetSubject('Lab Report');
        $pdf->SetTitle('Lab Report');

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 04', PDF_HEADER_STRING);
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER,10);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        //$pdf->setPrintFooter(false);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        /*HEADER PART AND FOOTER PART FUNCTIONS HAS BEEN ADDED IN (headfoot.php) PATH INCLUDE: (admin/lib/headfoot.php)*/
        $pdf->AddPage('P', 'A5');

        $in_patient_id = $fn->getReqParam('in_patient_id');

        $SQL = "
        SELECT pv.*
              ,p.name AS patient_name
              ,p.age_year
              ,p.age_month
              ,p.age_day
              ,p.gender
              ,e.first_name
              ,m.title AS medicine
              ,m.instruction
              ,m.dosage
              ,m.route
              ,m.qty
            FROM in_patient pv
            LEFT JOIN medicines_in_patient m ON (m.in_patient_id = pv.in_patient_id)
            LEFT JOIN (patient_information p) ON (p.patient_information_id = pv.patient_information_id)
            LEFT JOIN (employee_in_patient ev) ON (ev.in_patient_id = pv.in_patient_id)
            LEFT JOIN (employee e) ON (e.employee_id = ev.employee_id)
            WHERE pv.in_patient_id = '{$in_patient_id}'
            ORDER BY m.medicines_in_patient_id
        ";
        $result = $db->sql_query($SQL);
        $result2 = $db->sql_query($SQL);
        $company = $db->sql_fetchrow($result2);
        //============================================================================= //

        $pdf->SetFont('Courier','B', 10);

        $today = date("d-m-Y");
        $gender = '';
        if($company['gender'] == 'Female'){
            $gender = 'F';
        }else if($company['gender'] == 'Male'){
            $gender = 'M';            
        }

        $currentDate   = date("d-m-Y");

        $visit_code = '';
        if($company['code'] != ''){
            $visit_code = 'IP-'.$company['code'];
        }

        $age = '';

        if($company['age_year'] != ''){
            $age = $company['age_year'].' Yrs';
        } elseif($company['age_month'] != ''){
            $age = $company['age_month'].' Months';
        } elseif($company['age_day'] != ''){
            $age = $company['age_day'].' Days';
        }

        $doctorName = '';
        if($company['first_name'] == 'DR.SHEIK ABDUL KHADER'){
            $doctorName = $company['first_name'];
        }
        
        $tbl1 = '
        <table border="0" width="100%" style="border-bottom:2px solid #000000; padding-bottom:3px;">
            <tr>
                <td width="70%">Pt Name:'.$company['patient_name'].'('.$gender .'/'.$age.')</td>
                <td width="30%" align="left">Date:'.$currentDate.'</td>
            </tr>
            <tr>
                <td width="70%" align="">Ref By:Dr.SHEIK ABDUL KHADER</td>
                <td width="30%" align="left">Code:'.$visit_code.'</td>
            </tr>
        </table>
        <table border="0" width="120%">
            <tr>
                <td align="right">Page '.$pdf->getAliasNumPage().'/'.$pdf->getAliasNbPages().'</td>
            </tr>
        </table>
        ';

        $SQL1 = "
        SELECT mt.*
              ,m.normal_value
              ,m.fees AS amount
              ,m.units
        FROM medical_test_in_patient mt
        LEFT JOIN medical_test m ON (m.medical_test_id = mt.medical_test_id)
        WHERE mt.in_patient_id = '{$in_patient_id}'
        ";
        $result1 = $db->sql_query($SQL1);
        $numRows1 = $db->sql_numrows($result1);
        $marginTop = '';
        if($numRows1 == 1){
            $marginTop = "<br/><br/>";
        }

        $tbl4 = '
        <table cellpadding="4" border="0">
            <thead>
            <tr><th colspan="3" style="text-decoration:underline;">Reports:</th></tr>
            <tr bgcolor="#D3D3D3" >
                <th style="border-left:1px solid #9A9A93;border-top:1px solid #9A9A93;border-bottom:1px solid #9A9A93;" width="35%">Test Name</th>
                <th style="border-top:1px solid #9A9A93;border-bottom:1px solid #9A9A93;" width="25%">Value</th>
                <th style="border-top:1px solid #9A9A93;border-bottom:1px solid #9A9A93;" width="19%">Units</th>
                <th style="border-right:1px solid #9A9A93;border-top:1px solid #9A9A93;border-bottom:1px solid #9A9A93;" width="21%">Ref.Range</th>
            </tr>
            </thead>
        ';
        while ($row1 = $db->sql_fetchrow($result1)) {

            $SQLMTP = "
            SELECT m.medical_test_parameter_id
                  ,m.title
                  ,m.normal_value
                  ,m.medical_test_id
                  ,m.units
            FROM medical_test_parameter m
            WHERE m.medical_test_id = '{$row1['medical_test_id']}'
            ORDER BY m.medical_test_parameter_id
            ";

            $resultMTP   = $db->sql_query($SQLMTP);
            $numRowsMTP = $db->sql_numrows($resultMTP);

            if($numRowsMTP > 0){
                $tbl4 = $tbl4.'
                <tr>
                    <td width="100%" align="left" style="line-height:30px;font-size:12pt;">'.$row1['title'].'</td>
                </tr>
                ';
            }else{
                $tbl4 = $tbl4.'
                <tr>'.$marginTop.'
                    <td width="35%" align="left" style="line-height:30px;font-size:12pt;">'.$row1['title'].'</td>
                    <td width="25%" align="left" style="line-height:30px;" >'.$row1['notes'].'</td>
                    <td width="13%" align="left" style="line-height:30px;">'.$row1['units'].'</td>
                    <td width="27%" align="left" style="line-height:30px;">'.$row1['normal_value'].'</td>
                </tr>
                ';
            }

            while ($rowMTP = $db->sql_fetchrow($resultMTP)) {
                $medVisitParaRec = $fn->getRecordByCondition('medical_visit_parameter', "medical_test_parameter_id = '{$rowMTP['medical_test_parameter_id']}' AND medical_test_id='{$rowMTP['medical_test_id']}' AND in_patient_id = {$in_patient_id} AND test_repeat = {$row1['test_repeat']}");
                $tbl4 = $tbl4.'<tr>
                            <td width="35%" align="left">'.strtoupper($rowMTP['title']).'</td>
                            <td width="25%" align="left">'.$medVisitParaRec['notes'].'</td>
                            <td width="19%" align="left" style="font-size:9pt;">'.$rowMTP['units'].'</td>
                            <td width="21%" align="left" style="font-size:9pt;">'.$rowMTP['normal_value'].'</td>
                        </tr>
                        ';
            }
        }

        $tbl4 = $tbl4.'</table>';

        $pdf->writeHTML($tbl1, false, false, false, false, '');
        //$pdf->writeHTML($tbl2, true, false, false, false, '');
        //$pdf->writeHTML($tblHead, true, false, false, false, '');
        //$pdf->writeHTML($tbl3, false, false, false, false, '');
        $pdf->ln(5);
        $pdf->writeHTML($tbl4, true, false, false, false, '');
        $download_title = 'Lab_Report.pdf';
        ob_start();
        $pdf->Output($download_title, 'I');
    }

    /**
     *
     */
    function getPrintLabRequestForm() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        ini_set('memory_limit', '512M');
        set_time_limit(50000);

        include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
        include_once(CP_LOCAL_PATH.'lib/headfoot1.php');

        $pdf = new MYPDF_Local(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('USS');
        $pdf->SetSubject('Print Prescription');
        $pdf->SetTitle('Print Prescription');

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 04', PDF_HEADER_STRING);
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER,10);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        //$pdf->setPrintFooter(false);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        /*HEADER PART AND FOOTER PART FUNCTIONS HAS BEEN ADDED IN (headfoot.php) PATH INCLUDE: (admin/lib/headfoot.php)*/
        $pdf->AddPage('P', 'A5');

        $in_patient_id = $fn->getReqParam('in_patient_id');

        $SQL = "
        SELECT pv.*
              ,p.name AS patient_name
              ,p.age_year
              ,p.age_month
              ,p.age_day
              ,p.gender
              ,e.first_name
              ,m.title AS medicine
              ,m.instruction
              ,m.dosage
              ,m.route
              ,m.qty
            FROM in_patient pv
            LEFT JOIN medicines_in_patient m ON (m.in_patient_id = pv.in_patient_id)
            LEFT JOIN (patient_information p) ON (p.patient_information_id = pv.patient_information_id)
            LEFT JOIN (employee_in_patient ev) ON (ev.in_patient_id = pv.in_patient_id)
            LEFT JOIN (employee e) ON (e.employee_id = ev.employee_id)
            WHERE pv.in_patient_id = '{$in_patient_id}'
            ORDER BY m.medicines_in_patient_id
        ";
        $result = $db->sql_query($SQL);
        $result2 = $db->sql_query($SQL);
        $company = $db->sql_fetchrow($result2);
        //============================================================================= //

        $pdf->SetFont('Courier','B', 10);

        $today = date("d-m-Y");
        $gender = '';
        if($company['gender'] == 'Female'){
            $gender = 'F';
        }else if($company['gender'] == 'Male'){
            $gender = 'M';            
        }

        $currentDate   = date("d-m-Y");

        $visit_code = '';
        if($company['code'] != ''){
            $visit_code = 'VST-'.$company['code'];
        }

        $age = '';

        if($company['age_year'] != ''){
            $age = $company['age_year'].' Yrs';
        } elseif($company['age_month'] != ''){
            $age = $company['age_month'].' Months';
        } elseif($company['age_day'] != ''){
            $age = $company['age_day'].' Days';
        }

        $doctorName = '';
        if($company['first_name'] == 'DR.SHEIK ABDUL KHADER'){
            $doctorName = $company['first_name'];
        }
        
        $tbl1 = '
        <table border="0" width="100%" style="border-bottom:2px solid #000000; padding-bottom:3px;">
            <tr>
                <td width="70%">Pt Name:'.$company['patient_name'].'('.$gender .'/'.$age.')</td>
                <td width="30%" align="left">Date:'.$currentDate.'</td>
            </tr>
            <tr>
                <td width="70%" align="">Ref By:Dr.SHEIK ABDUL KHADER</td>
                <td width="30%" align="left">Code:'.$visit_code.'</td>
            </tr>
        </table>
        <table border="0" width="120%">
            <tr>
                <td align="right">Page '.$pdf->getAliasNumPage().'/'.$pdf->getAliasNbPages().'</td>
            </tr>
        </table>
        ';

        $SQL1 = "
        SELECT mt.*
              ,m.normal_value
              ,mt.fees AS amount
        FROM medical_test_in_patient mt
        LEFT JOIN medical_test m ON (m.medical_test_id = mt.medical_test_id)
        WHERE mt.in_patient_id = '{$in_patient_id}'
        ";
        $result1 = $db->sql_query($SQL1);
        $total_amount = 0;
        $tbl4 = '
        <table cellpadding="4" border="0" width="100%">
            <thead>
            <tr><th colspan="2" style="text-decoration:underline;">Lab Test Required:</th></tr>
            <tr bgcolor="#D3D3D3">
                <th style="border-left:1px solid #9A9A93;border-top:1px solid #9A9A93;border-bottom:1px solid #9A9A93;" width="70%">Test Name</th>
                <th align="right" style="border-right:1px solid #9A9A93;border-top:1px solid #9A9A93;border-bottom:1px solid #9A9A93;" width="30%">Fees (Rs)</th>
            </tr>
            </thead>
        ';
        while ($row1 = $db->sql_fetchrow($result1)) {

            $tbl4 = $tbl4.'<tr>
                                <td align="left" width="70%">'.$row1['title'].'</td>
                                <td align="right" width="30%">'.$row1['amount'].'</td>
                            </tr>
                            ';

            /*$SQLMTP = "
            SELECT m.medical_test_parameter_id
                  ,m.title
                  ,m.normal_value
                  ,m.medical_test_id
            FROM medical_test_parameter m
            WHERE m.medical_test_id = '{$row1['medical_test_id']}'
            ORDER BY m.title
            ";

            $resultMTP   = $db->sql_query($SQLMTP);
            $numRowsMTP = $db->sql_numrows($resultMTP);
            while ($rowMTP = $db->sql_fetchrow($resultMTP)) {
                $tbl4 = $tbl4.'
                        <tr>
                            <td align="left" style="font-size:9px;" width="70%">'.strtoupper($rowMTP['title']).'</td>
                            <td align="right"  width="30%"></td>
                        </tr>
                        ';
            }*/

            $total_amount += $row1['amount'];
        }

        $tbl4 = $tbl4.'<tr>
                            <td align="right" width="75%">Total Amount : </td>
                            <td align="right"  width="25%" style="border-top:1px solid #000000; border-bottom:1px solid #000000;">'.$total_amount.'</td>
                        </tr>
                    </table>';

        $pdf->writeHTML($tbl1, false, false, false, false, '');
        //$pdf->writeHTML($tbl2, true, false, false, false, '');
        //$pdf->writeHTML($tblHead, true, false, false, false, '');
        //$pdf->writeHTML($tbl3, false, false, false, false, '');
        $pdf->ln(5);
        $pdf->writeHTML($tbl4, true, false, false, false, '');
        $download_title = 'Lab_Report.pdf';
        ob_start();
        $pdf->Output($download_title, 'I');
    }

    /**
     *
     */
    function getDoctorPortalDisplay($in_patient_id=''){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        if($in_patient_id == ''){
            $in_patient_id = $fn->getReqParam('in_patient_id');
        }

        $formAction = '';
        $rows  = "";
        $rowsPvt  = "";

        $SQL = "
        SELECT ev.*
              ,CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
              ,e.category
        FROM employee_in_patient ev
        LEFT JOIN (employee e) ON (e.employee_id = ev.employee_id)
        WHERE ev.in_patient_id = {$in_patient_id}
        ORDER BY ev.employee_in_patient_id
        ";
        $result   = $db->sql_query($SQL);

        while ($rowEV = $db->sql_fetchrow($result)) {

            if($rowEV['employee_id'] == ""){
                $rowEV['employee_name'] = "Nurse";
                $rowEV['category']      = "Nurse";
            }

            $editURL = "index.php?_topRm=main&module=hms_inPatient&_spAction=editDoctorRecord&showHTML=0&in_patient_id={$rowEV['in_patient_id']}&employee_in_patient_id={$rowEV['employee_in_patient_id']}";
            $editRow = "<td><a href='{$editURL}' id='editDoctorRecord' in_patient_id={$rowEV['in_patient_id']}><u>Edit</u></a></td>";
            $rows .= "
            <tr>
                <td>{$rowEV['category']}</td>
                <td>{$rowEV['employee_name']}</td>
                <td>{$rowEV['consultation_fees']}</td>
                <td>{$rowEV['notes']}</td>
                {$editRow}
                <td><a href='#' class='deleteDoctorRecord' employee_in_patient_id='{$rowEV['employee_in_patient_id']}' in_patient_id={$rowEV['in_patient_id']}><u>Delete</u></a></td>
            </tr>
            ";
        }

        $header ="
        <tr style='background-color:#EAEAE8;'>
        <th>Dr/Nurse</th>
        <th>Name</th>
        <th>Consulting Fees</th>
        <th>Notes</th>
        <th>Edit</th>
        <th>Delete</th>
        </tr>
        ";

        $text = "
        <div id='' class='doctorDisplay'>
            <form id='' class='' method='post' action='{$formAction}'>
                <div id='doctorPortalOuter'>
                    <table class='thinlist'>
                        {$header}
                        {$rows}
                    </table>
                </div>
            </form>
        </div>
        ";

        return $text;
    }

    /**
     *
     */
     function getAddDoctorRecord() {
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $rows = '';

        $formAction = "index.php?_topRm=main&module=hms_inPatient&_spAction=addDoctorRecordSubmit&showHTML=0";
        $in_patient_id = $fn->getReqParam('in_patient_id');

        $appendSqlEmp = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlEmp = "AND e.site_id = {$cpSiteIdSession}";
        }

        $sqlDoctor = "
        SELECT e.employee_id
              ,CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
        FROM employee e
        WHERE e.position IN ('Doctor', 'Nurse')
        {$appendSqlEmp}
        ORDER BY e.first_name
        ";

        $text = "
        <form id='portalForm' class='yform columnar invoiceForm' method='post' action='{$formAction}'>
            {$formObj->getDDRowBySQL('Doctor', 'employee_id', $sqlDoctor)}
            {$formObj->getTBRow('Consulting Fees', 'consultation_fees')}
            {$formObj->getTARow('Notes', 'notes')}
            <input type='hidden' name='in_patient_id' value='{$in_patient_id}' />
        </form>
        ";

        return $text;
    }

    /**
     *
     */
     function getEditDoctorRecord() {
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $rows = '';

        $formAction = "index.php?_topRm=main&module=hms_inPatient&_spAction=editDoctorRecordSubmit&showHTML=0";
        $in_patient_id = $fn->getReqParam('in_patient_id');
        $employee_in_patient_id = $fn->getReqParam('employee_in_patient_id');

        $appendSqlEmp = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlEmp = "AND e.site_id = {$cpSiteIdSession}";
        }

        $sqlDoctor = "
        SELECT e.employee_id
              ,CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
        FROM employee e
        WHERE e.position IN ('Doctor', 'Nurse')
        {$appendSqlEmp}
        ORDER BY e.employee_id
        ";

        $SQL = "
        SELECT ev.*
        FROM employee_in_patient ev
        WHERE ev.employee_in_patient_id = {$employee_in_patient_id}
        ";
        $result   = $db->sql_query($SQL);
        $rowEV = $db->sql_fetchrow($result);

        $text = "
        <form id='portalForm' class='yform columnar invoiceForm' method='post' action='{$formAction}'>
            {$formObj->getDDRowBySQL('Doctor', 'employee_id', $sqlDoctor, $rowEV['employee_id'])}
            {$formObj->getTBRow('Consulting Fees', 'consultation_fees', $rowEV['consultation_fees'])}
            {$formObj->getTARow('Notes', 'notes', $rowEV['notes'])}
            <input type='hidden' name='employee_in_patient_id' value='{$employee_in_patient_id}' />
        </form>
        ";

        return $text;
    }

    /**
     *
     */
    function getSummaryInOrder() {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $rows  = "";
        $order_id = $fn->getReqParam('order_id');

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $appendSql = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "AND o.site_id = {$cpSiteIdSession}";
        }

        $SQL = "
        SELECT o.*
              ,(SELECT SUM(round((oi.unit_price * oi.qty),2))
               FROM order_item oi
               WHERE oi.order_id = o.order_id
               ) AS order_amount
              ,(SELECT SUM(i.invoice_amount) FROM invoice i
                WHERE i.order_id = o.order_id
                AND i.status != 'Cancelled'
                ) AS invoice_amount
              ,(SELECT SUM(r.amount)
                FROM receipt r
                WHERE o.order_id = r.order_id
                AND r.receipt_status != 'Cancelled'
                )AS receipt_amount
              ,(SELECT  SUM(oi.unit_price)
                FROM order_item oi
                WHERE oi.order_id = o.order_id
                AND oi.record_type = 'Doctor/Nurse'
                )AS consultation_fees
              ,(SELECT  SUM(oi.unit_price) AS Amount
                FROM order_item oi
                WHERE oi.order_id = o.order_id
                AND oi.record_type != ''
                )AS Total_Amount
              ,(SELECT SUM(invHist.amount) AS prev_sum
                FROM invoice_receipt_history invHist
                LEFT JOIN receipt r ON (r.receipt_id = invHist.receipt_id)
                LEFT JOIN `invoice` i ON (i.order_id = {$order_id})
                WHERE invHist.related_invoice_id =  i.invoice_id
                AND r.receipt_status != 'Cancelled'
                AND i.status != 'Cancelled'
                ) as Amount_Paid
        FROM `order`o
        WHERE o.order_id = {$order_id}
        {$appendSql}
        ";

        $result = $db->sql_query($SQL);
        $row  = $db->sql_fetchrow($result);

        $orderAmt   = number_format(round($row['order_amount']), 2);
        $invoiceAmt = number_format($row['invoice_amount'] ,2);
        $receiptAmt = number_format($row['receipt_amount'] ,2);

        $total_invoice_amount = 0;
        if($row['invoice_amount'] != ''){
            $total_invoice_amount = $row['invoice_amount'] - $row['discount'];
            $balance_Amount = $total_invoice_amount - $row['Amount_Paid'];
            $balance_Amount = number_format($balance_Amount, 2);
            $invoiced_Paid_Amount = number_format($row['Amount_Paid'], 2);
        }else{
            $total_invoice_amount = $row['invoice_amount'];
            $invoiced_Paid_Amount = number_format($row['Amount_Paid'], 2);
            $balance_Amount = $total_invoice_amount - $row['Amount_Paid'];
            $balance_Amount = number_format($balance_Amount, 2);
        }

        $total_invoice_amount = number_format($total_invoice_amount, 2);

        $outstandingInvoiceAmt = number_format($row['invoice_amount'] - $row['receipt_amount'], 2);
        $overallBalanceAmt     = number_format($row['order_amount'] - $row['receipt_amount'], 2);

        $order_items_Details = '';

        $appendSqlRecord = '';
        if($row['order_type'] == 'IP'){
            $appendSqlRecord = "AND record_type NOT IN ('Medical Test')";
        }

        $Lab = '';
        $SQLOrderItem = "
        SELECT  record_type
               ,SUM(unit_price) AS Amount
               ,SUM(unit_price*qty) AS QTY_AMOUNT
        FROM order_item
        WHERE order_id = {$row['order_id']}
        AND record_type != ''
        {$appendSqlRecord}
        GROUP BY record_type
        ORDER BY record_type ASC
        ";
        $resultOrderItem = $db->sql_query($SQLOrderItem);
        $numRowsOrderItem = $db->sql_numrows($resultOrderItem);

        $Sub_Total = 0;
        if($numRowsOrderItem > 0){
            $count = 1;
            while($rowOrderItem  = $db->sql_fetchrow($resultOrderItem)){
                $SQLOrderItemList = "
                SELECT  item_title
                        ,unit_price
                        ,order_item_id
                FROM order_item
                WHERE order_id = {$row['order_id']}
                AND record_type = '{$rowOrderItem['record_type']}'
                ";
                $resultList = $db->sql_query($SQLOrderItemList);
                $numRowsList = $db->sql_numrows($resultList);

                if($rowOrderItem['record_type'] == 'Doctor/Nurse'){
                    $rowOrderItem['record_type'] = 'Consultation Fees';
                }


                if($rowOrderItem['record_type'] == 'Inventory'){
                    $rowOrderItem['record_type'] = 'Medicines and Other Charges';
                    $rowOrderItem['Amount'] = $rowOrderItem['QTY_AMOUNT'];
                }

                $Lab .= "<tr>
                            <td><b>{$rowOrderItem['record_type']}</b>
                            <ol>
                        ";


                if($numRowsList > 0){
                    while($rowList    = $db->sql_fetchrow($resultList)){
                        if($rowOrderItem['record_type'] != 'Consultation Fees'){
                            $Lab .= "<li>{$rowList['item_title']}</li>";
                        }
                    }
                }

                $Lab .="</ol></td>
                                <td class='txtRight'>{$rowOrderItem['Amount']}</td>
                            </tr>";

                $Sub_Total += $rowOrderItem['Amount'];

                $count++;
            }
        }

        $order_items_Details .="{$Lab}";
        $total_amount = number_format($Sub_Total - $row['discount'], 2);
        $Sub_Total    = number_format($Sub_Total, 2);
        $discount     = number_format($row['discount'], 2);

        $rows = "
        <div class='linkPortalWrapper'>
            <div expanded='1' class='header'>
                <div class='floatbox'>
                    <table class='thinlist'>
                        <tr>
                            <th>Total Amount: {$total_invoice_amount}</th>
                            <th>Amount Paid: {$invoiced_Paid_Amount}</th>
                            <th>Amount Due: {$balance_Amount}</th>
                        </tr>
                    </table>
                </div>
            </div>
            <div>
                <div class='linkPortalDataWrapper'>
                    <table class='thinlist'>
                        <tr>
                            <th>Description</th>
                            <th class='txtRight'>Amount</th>
                        </tr>
                        {$order_items_Details}
                        <tr>
                            <th>Sub Total</th>
                            <th class='txtRight'>{$Sub_Total}</th>
                        </tr>
                        <tr>
                            <th>Discount</th>
                            <th class='txtRight'>{$discount}</th>
                        </tr>
                        <tr>
                            <th>Total Amount</th>
                            <th class='txtRight'>{$total_amount}</th>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        ";

        $text = "
        {$rows}
        ";

        return $text;

    }

    /**
     *
     */
    function getPrintDischargeCard() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        ini_set('memory_limit', '512M');
        set_time_limit(50000);

        include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
        include_once(CP_LOCAL_PATH.'lib/headfoot3.php');

        $pdf = new MYPDF_Local(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('USS');
        $pdf->SetSubject('Print Discharge Card');
        $pdf->SetTitle('Print Discharge Card');

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 04', PDF_HEADER_STRING);
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // set margins
        $pdf->SetMargins(5, PDF_MARGIN_TOP, 5);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER, 1);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 5);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        //$pdf->setPrintFooter(false);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        /*HEADER PART AND FOOTER PART FUNCTIONS HAS BEEN ADDED IN (headfoot.php) PATH INCLUDE: (admin/lib/headfoot.php)*/
        $pdf->AddPage('P', 'A5');

        $in_patient_id = $fn->getReqParam('in_patient_id');

        $SQLIp = "
        SELECT ip.*
              ,p.name AS patient_name
              ,p.age_year
              ,p.age_month
              ,p.age_day
              ,p.gender
              ,p.address_area
        FROM in_patient ip
        LEFT JOIN (patient_information p) ON (p.patient_information_id = ip.patient_information_id)
        WHERE ip.in_patient_id = '{$in_patient_id}'
        ";
        $resultIp = $db->sql_query($SQLIp);
        $result2  = $db->sql_query($SQLIp);
        $rowIp    = $db->sql_fetchrow($result2);

        $date_admitted  = $fn->getCPDate($rowIp['date_admitted'], "d-m-Y");
        $date_discharge = $fn->getCPDate($rowIp['date_discharge'], "d-m-Y");
        $date_surgery   = $fn->getCPDate($rowIp['date_surgery'], "d-m-Y");
        $date_review    = $fn->getCPDate($rowIp['date_review'], "d-m-Y");

        $age = '';

        if($rowIp['age_year'] != ''){
            $age .= $rowIp['age_year'].' Yrs';
        } elseif($rowIp['age_month'] != ''){
            $age .= $rowIp['age_month'].' Months';
        } elseif($rowIp['age_day'] != ''){
            $age .= $rowIp['age_day'].' Days';
        }

        $gender = '';
        if($rowIp['gender'] == 'Female'){
            $gender = 'F';
        }else if($rowIp['gender'] == 'Male'){
            $gender = 'M';            
        }

        $siteRec = $fn->getRecordRowByID('site', 'site_id', $fn->getSessionParam('cp_site_id'));
        if($siteRec['site_id'] == 1){
            $clinicname = $cpCfg['cp.clinicName'] ;
            $drspecialist = $cpCfg['cp.doctorSpecialistPDF'];
            $phone  =  $cpCfg['cp.phonePDF'];
        }
        else if($siteRec['site_id'] == 2){
            $clinicname = $cpCfg['cp.clinicName2'] ;
            $drspecialist = $cpCfg['cp.doctorSpecialistPDF2'] ; 
            $phone  =   $cpCfg['cp.addressPdf1'] . ' |' .$cpCfg['cp.footerCellPdf'];
         }
        else if($siteRec['site_id'] == 3){
            $clinicname = 'HABIBIA CLINIC' ;
            $drspecialist = 'Child Specialist' . ' (Morning 7.00 AM to 9:00 AM)' ;  
            $phone  =  $phone  =   'Eppodum Venran / Mobile : 9655897448';
         }
         else{
            $clinicname = 'HABIBIA CLINIC' ;
            $drspecialist = 'Child Specialist' . ' (Morning 7.00 AM to 9:00 AM)' ;  
            $phone  =  $phone  =   'Mobile : 9655897448 / 6385859549';
        }

        $SQLEV = "
        SELECT CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
              ,e.category
        FROM employee_in_patient ev
        LEFT JOIN (employee e) ON (e.employee_id = ev.employee_id)
        WHERE ev.in_patient_id = {$in_patient_id}
        ORDER BY ev.employee_in_patient_id
        ";
        $resultEV = $db->sql_query($SQLEV);
        $ConsultantDr = "";
        while ($rowEV = $db->sql_fetchrow($resultEV)) {
            $ConsultantDr .= $rowEV['employee_name'].", ";
        }
        $ConsultantDr = rtrim($ConsultantDr, ", ");

        $sqlEmployeeRev = "
        SELECT CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
              ,e.category
        FROM in_patient i
        LEFT JOIN (employee e) ON (e.employee_id = i.review_doctor)
        WHERE i.in_patient_id = {$in_patient_id}
        ";
        $resultEmployeeRev = $db->sql_query($sqlEmployeeRev);
        $rowEmployeeRev = $db->sql_fetchrow($resultEmployeeRev);

        $sqlEmployeeConsult = "
        SELECT CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
              ,e.category
        FROM in_patient i
        LEFT JOIN (employee e) ON (e.employee_id = i.consultant_doctor)
        WHERE i.in_patient_id = {$in_patient_id}
        ";
        $resultEmployeeConsult = $db->sql_query($sqlEmployeeConsult);
        $rowEmployeeConsult = $db->sql_fetchrow($resultEmployeeConsult);

        $sqlEmployeeRef = "
        SELECT CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
              ,e.category
        FROM in_patient i
        LEFT JOIN (employee e) ON (e.employee_id = i.ref_doctor)
        WHERE i.in_patient_id = {$in_patient_id}
        ";
        $resultEmployeeRef = $db->sql_query($sqlEmployeeRef);
        $rowEmployeeRef = $db->sql_fetchrow($resultEmployeeRef);

        $tbl4 ='<table border="0" width="100%" cellpadding="4">';

        //AND (mv.continue_medicine = 0 OR mv.continue_medicine IS NULL)
        $SQLMV = "
        SELECT mv.*
        FROM medicines_in_patient mv
        WHERE mv.in_patient_id = {$in_patient_id}
        AND mv.treatment_given = 1
        ORDER BY mv.medicines_in_patient_id
        ";
        $resultMV = $db->sql_query($SQLMV);
        $count = 0;
        while ($rowMV = $db->sql_fetchrow($resultMV)) {
            if($rowMV['title'] != '' && $rowMV['instruction'] != ''){
                $instruction = explode(", ", $rowMV['instruction']);
                $instructionLen = count($instruction);

                $morning = 0;
                $noon = 0;
                $night = 0;

                if($rowMV['dosage'] == ''){
                    $rowMV['dosage'] = 1;
                }

                for($i=0;$i<$instructionLen;$i++){
                    if($instruction[$i] == 'Morning'){
                        $morning = $rowMV['dosage'];
                    }
                    if($instruction[$i] == 'Noon'){
                        $noon = $rowMV['dosage'];
                    }
                    if($instruction[$i] == 'Night'){
                        $night = $rowMV['dosage'];
                    }
                }

                if($rowMV['qty'] == 0 || $rowMV['qty'] == ''){
                    $qty = "{$rowMV['qty']}";
                }else {
                    $qty = "({$rowMV['qty']})";                    
                }

                $route = substr($rowMV['route'], 0, 3);
                $tbl4 = $tbl4.'<tr>
                                    <td align="left" width="35%">'.$rowMV['title'].'</td>
                                    <td align="left" width="65%">'.$route.'</td>
                                </tr>
                                ';

            }

            $count++;
        }

        $tbl4 = $tbl4.'</table>';

        $tbl3 ='<table border="0" width="100%" cellpadding="4">';

        $SQLMV = "
        SELECT mv.*
        FROM medicines_in_patient mv
        WHERE mv.in_patient_id = {$in_patient_id}
        AND mv.continue_medicine = 1
        ORDER BY mv.medicines_in_patient_id
        ";
        $resultMV = $db->sql_query($SQLMV);
        $count = 0;
        while ($rowMV = $db->sql_fetchrow($resultMV)) {
            if($rowMV['title'] != '' && $rowMV['instruction'] != ''){
                $instruction = explode(", ", $rowMV['instruction']);
                $instructionLen = count($instruction);

                $morning = 0;
                $noon = 0;
                $night = 0;

                if($rowMV['dosage'] == ''){
                    $rowMV['dosage'] = 1;
                }

                for($i=0;$i<$instructionLen;$i++){
                    if($instruction[$i] == 'Morning'){
                        $morning = $rowMV['dosage'];
                    }
                    if($instruction[$i] == 'Noon'){
                        $noon = $rowMV['dosage'];
                    }
                    if($instruction[$i] == 'Night'){
                        $night = $rowMV['dosage'];
                    }
                }

                if($rowMV['qty'] == 0 || $rowMV['qty'] == ''){
                    $qty = "{$rowMV['qty']}";
                }else {
                    $qty = "({$rowMV['qty']})";                    
                }

                $route = substr($rowMV['route'], 0, 3);
                if($rowMV['instruction'] == 'STAT' || $rowMV['instruction'] == 'SOS'){
                    $tbl3 = $tbl3.'<tr>
                                        <td align="left" width="35%">'.$rowMV['title'].'</td>
                                        <td align="left" width="10%">'.$qty.'</td>
                                        <td align="left" width="8%">'.$route.'</td>
                                        <td align="center" width="47%">'.$rowMV['instruction'].'</td>
                                    </tr>
                                    ';
                } else {
                    $tbl3 = $tbl3.'<tr>
                                        <td align="left" width="35%">'.$rowMV['title'].'</td>
                                        <td align="left" width="10%">'.$qty.'</td>
                                        <td align="left" width="8%">'.$route.'</td>
                                        <td align="center" width="13%">'.$morning.'</td>
                                        <td align="center" width="4%">-</td>
                                        <td align="center" width="13%">'.$noon.'</td>
                                        <td align="center" width="4%">-</td>
                                        <td align="center" width="13%">'.$night.'</td>
                                    </tr>
                                    ';
                }

            }

            $count++;
        }

        $tbl3 = $tbl3.'</table>';

        $SQLRDr = "
        SELECT CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
              ,e.category
        FROM employee e
        WHERE e.employee_id = {$rowIp['employee_id']}
        ";
        $resultRDr   = $db->sql_query($SQLRDr);
        $rowRDr      = $db->sql_fetchrow($resultRDr);
        $ReferenceDr = $rowRDr['employee_name'];

        $weightKg = "";
        if($rowIp['weight'] != ""){
            $weightKg = $rowIp['weight'].' KGS';
        }
        
        $tbl1 = '
        <table border="0" cellpadding="3" width="100%" style="font-size:12px;">
            <tr>
                <td width="100%" align="center" colspan="6" style="border-top:1px solid #000000;border-bottom:1px solid #000000;border-left:1px solid #000000;border-right:1px solid #000000;"><br/>
                    <span style="font-size:18px;"><b>HABIBIA HOSPITAL</b></span><br/>
                    <span style="font-size:10px;"><b>KURUKKUSALAI</b></span><br/>
                    <span style="font-size:12px;"><b>DISCHARGE SUMMARY</b></span>
                </td>
            </tr>
            <tr>
                <td width="11%" style="border-left:1px solid #000000;">Name</td>
                <td width="64%">: '.$rowIp['patient_name'].'</td>
                <td width="10%">D.O.A:</td>
                <td width="15%" style="border-right:1px solid #000000;">'.$date_admitted.'</td>
            </tr>
            <tr>
                <td width="11%" style="border-left:1px solid #000000;">IP. No.</td>
                <td width="10%">: '.$rowIp['code'].'</td>
                <td width="13%">Age / Sex:</td>
                <td width="19%">'.$age.' / '.$gender.'</td>
                <td width="10%">Weight:</td>
                <td width="12%">'.$weightKg.'</td>
                <td width="10%">D.O.S:</td>
                <td width="15%" style="border-right:1px solid #000000;">'.$date_surgery.'</td>
            </tr>
            <tr>
                <td width="11%" style="border-left:1px solid #000000;">Address</td>
                <td width="64%">: '.$rowIp['address_area'].'</td>
                <td width="10%">D.O.D:</td>
                <td width="15%" style="border-right:1px solid #000000;">'.$date_discharge.'</td>
            </tr>
            <tr>
                <td width="15%" style="height:20px;border-left:1px solid #000000;border-top:1px solid #000000;">Contsultant:</td>
                <td width="85%" style="height:20px;border-top:1px solid #000000;border-right:1px solid #000000;">'.$rowEmployeeConsult['employee_name'].'</td>
            </tr>
            <tr>    
                <td width="7%"  style="height:20px;border-left:1px solid #000000;">Ref:</td>
                <td width="93%" style="height:20px;border-right:1px solid #000000;">'.$rowEmployeeRef['employee_name'].'</td>
            </tr>
            <tr>
                <td width="17%" style="height:20px;border-top:1px solid #000000;border-left:1px solid #000000;border-bottom:1px solid #000000;">Admitted for :</td>
                <td width="83%"  colspan="5" style="border-top:1px solid #000000;border-right:1px solid #000000;border-bottom:1px solid #000000;">'.$rowIp['complain'].'</td>
            </tr>
            <tr>
                <td width="15%" style="height:20px;border-left:1px solid #000000;border-bottom:1px solid #000000;">Diagnosis :</td>
                <td width="85%"  colspan="5" style="border-right:1px solid #000000;border-bottom:1px solid #000000;">'.$rowIp['diagnosis'].'</td>
            </tr>
            <tr>
                <td width="21%"  style="height:20px;border-left:1px solid #000000;border-bottom:1px solid #000000;">Procedure Done :</td>
                <td width="79%"  colspan="5" style="border-right:1px solid #000000;border-bottom:1px solid #000000;">'.$rowIp['in_patient_procedure'].'</td>
            </tr>
            <tr>
                <td width="30%" style="border-left:1px solid #000000;">Treatment Given :</td>
                <td width="70%"  colspan="5" style="border-right:1px solid #000000;"></td>
            </tr>
            <tr>
                <td width="100%"  colspan="6" style="height:158px;vertical-align:top;border-left:1px solid #000000;border-right:1px solid #000000;border-bottom:1px solid #000000;">'.$tbl4.'</td>
            </tr>
            <tr>
                <td width="30%" style="border-left:1px solid #000000;">Drugs to Continue :</td>
                <td width="70%"  colspan="5" style="border-right:1px solid #000000;"></td>
            </tr>
            <tr>
                <td width="100%"  colspan="6" style="height:158px;vertical-align:top;border-left:1px solid #000000;border-right:1px solid #000000;border-bottom:1px solid #000000;">'.$tbl3.'</td>
            </tr>
            <tr>
                <td width="70%" style="border-left:1px solid #000000;">Advice on discharge / Plan :</td>
                <td width="30%"  colspan="5" style="border-right:1px solid #000000;"></td>
            </tr>
            <tr>
                <td width="100%"  colspan="6" style="height:40px;border-left:1px solid #000000;border-right:1px solid #000000;border-bottom:1px solid #000000;">'.$rowIp['advice_discharge'].'</td>
            </tr>
            <tr>
                <td width="19%" align="left" style="border-left:1px solid #000000;">Review with Dr.</td>
                <td width="81%" align="left" colspan="5" style="border-right:1px solid #000000;">'.$rowEmployeeRev['employee_name'].'</td>
            </tr>
            <tr>
                <td width="30%" style="border-left:1px solid #000000;"></td>
                <td width="70%"  colspan="5" style="border-right:1px solid #000000;"></td>
            </tr>
            <tr>
                <td width="50%" style="border-left:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">Review Date : '.$date_review.'</td>
                <td width="50%" align="right" colspan="5" style="border-right:1px solid #000000;font-weight:bold;border-bottom:1px solid #000000;">Signature of Medical Officer</td>
            </tr>
        </table>
        ';
            
        $pdf->ln(-22);
        $pdf->writeHTML($tbl1, false, false, false, false, '');
        $download_title = 'DischargeCard.pdf';
        ob_start();
        $pdf->Output($download_title, 'I');
    }

    /**
     *
     */
     function getConditionForInvoiceItem($order_id, $record_type) {
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

        $rows = '';

        $appendSql = "AND ii.item_title = '{$record_type}'";
        if($record_type == "Doctor/Nurse") {
            $appendSql = "AND ii.record_type = '{$record_type}'";
        }

        $SQLInvItem = "
        SELECT ii.*
        FROM invoice_item ii
        LEFT JOIN invoice i ON (i.invoice_id = ii.invoice_id)
        WHERE i.order_id = {$order_id}
        AND i.status != 'Cancelled'
        {$appendSql}
        ";
        $resultInv = $db->sql_query($SQLInvItem);
        $rowInv    = $db->sql_fetchrow($resultInv);

        return $rowInv['invoice_item_id'];
    }
}