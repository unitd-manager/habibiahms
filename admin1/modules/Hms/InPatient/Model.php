<?
class CPL_Admin_Modules_Hms_InPatient_Model extends CP_Common_Lib_ModuleModelAbstract
{
    /**
     *
     */
    function getSQL() {

        $SQL = "
        SELECT ip.*
              ,p.name AS patient_name
              ,p.first_name
              ,p.middle_name
              ,p.last_name
              ,p.name
              ,p.nric
              ,p.email
              ,p.mobile
              ,p.dob
              ,p.patient_code
              ,p.father_name
              ,p.spuse_name
              ,p.address_area
              ,p.age_year
              ,p.age_month
              ,p.age_day
              ,p.gender
              ,p.phone AS patient_phone
              ,CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
        FROM in_patient ip
        LEFT JOIN (patient_information p) ON (p.patient_information_id = ip.patient_information_id)
        LEFT JOIN (employee e) ON (e.employee_id = ip.employee_id)
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar($linkRecType = '') {
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $searchVar = Zend_Registry::get('searchVar');
        $searchVar->mainTableAlias = 'ip';

        $status            = $fn->getReqParam('status');
        $in_patient_id   = $fn->getReqParam('in_patient_id');
        $admission_type  = $fn->getReqParam('admission_type');
        $currentMonth    = $fn->getReqParam('currentMonth');
         $date_admitted1     = $fn->getReqParam('date_admitted_1');
        $date_admitted2     = $fn->getReqParam('date_admitted_2');
        $month          = date('m');
        $year         = date('Y');

        if ($in_patient_id != "") {
            $searchVar->sqlSearchVar[] = "ip.in_patient_id = '{$in_patient_id}'";
        } else if ($tv['record_id'] != '') {
            $searchVar->sqlSearchVar[] = "ip.in_patient_id = '{$tv['record_id']}'";
        } else {
            //$fn->setSearchVarForLinkData($searchVar, $linkRecType, 'pv.in_patient_id');

            if ($status != "") {
                $searchVar->sqlSearchVar[] = "ip.status = '{$status}'";
            }
            else {
                $searchVar->sqlSearchVar[] = "ip.status != 'Cancelled'";
            }

            if ($admission_type != "") {
                $searchVar->sqlSearchVar[] = "ip.admission_type = '{$admission_type}'";
            }

            if ($currentMonth == "CurrentMonth" || $currentMonth == "") {
                $searchVar->sqlSearchVar[] = "DATE_FORMAT(ip.date_admitted, '%Y-%m') = '{$year}-{$month}'" ;
            }

             if ($date_admitted1 != "" && $date_admitted2 != "") {
                $searchVar->sqlSearchVar[] = "(ip.date_admitted BETWEEN '{$date_admitted1}' AND '{$date_admitted2}')";
            }

            if ($tv['keyword'] != "") {
                $searchVar->sqlSearchVar[] = "(
                       p.name         LIKE '%{$tv['keyword']}%'
                    OR p.father_name  LIKE '%{$tv['keyword']}%'
                    OR p.address_area LIKE '%{$tv['keyword']}%'
                    OR p.spuse_name   LIKE '%{$tv['keyword']}%'
                    OR p.phone        LIKE '%{$tv['keyword']}%'
                    OR p.mobile       LIKE '%{$tv['keyword']}%'
                )";
            }

            //------------------------------------------------------------------------//
            if ($tv['special_search'] == "Flagged") {
                $searchVar->sqlSearchVar[] = "c.flag = 1";
            }

            if ($tv['special_search'] == "Not-Flagged") {
                $searchVar->sqlSearchVar[] = "(c.flag != 1 OR c.flag IS null)";
            }

            $searchVar->sortOrder = "ip.in_patient_id DESC";
        }
    }

    /**
     *
     */
    function getNewValidate() {
        $validate = Zend_Registry::get('validate');

        $validate->resetErrorArray();
        $validate->validateData('date_admitted', 'Please select date');

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getAdd(){
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');

        if (!$this->getNewValidate()){
            return $validate->getErrorMessageXML();
        }

        //$visit_code = $fn->getSettingsValueByKey("nextPatientvisitCode");
        $fa = $this->getFields();
        //$fa['visit_code'] = $visit_code;

        $id = $fn->addRecord($fa);
        //To update patient visit code
        //$SQLUpdate = "UPDATE setting SET value = (value+1) WHERE key_text = 'nextPatientvisitCode'";
        //$resultUpdate = $db->sql_query($SQLUpdate);

        $fn->returnAfterNewSave($id);

    }

    /**
     *
     */
    function getEditValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg   = Zend_Registry::get('cpCfg');

        $validate->resetErrorArray();

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }
    /**
     *
     */
    function getSave1(){
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $cpCfg = Zend_Registry::get('cpCfg');

        if (!$this->getEditValidate()){
            return $validate->getErrorMessageXML();
        }

        $id = $fn->saveRecord($fa);
        $fn->returnAfterNewSave($id, $cpCfg['cp.pagetoReturnAfterSave']);
    }
    /**
     *
     */
    function getSave(){
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $dbUtil = Zend_Registry::get('dbUtil');
        $in_patient_id = $fn->getReqParam('in_patient_id');
        $cpSiteIdSession  = $fn->getSessionParam('cp_site_id');

        if (!$this->getEditValidate()){
            return $validate->getErrorMessageXML();
        }

        $nursing_fees    = $fn->getPostParam('nursing_fees');
        $consulting_fees = $fn->getPostParam('consulting_fees');
        $other_fees      = $fn->getPostParam('other_fees');
        $dr_consultant_fees      = $fn->getPostParam('dr_consultant_fees');
        $oxygen_fees      = $fn->getPostParam('oxygen_fees');
        $medicine_fees      = $fn->getPostParam('medicine_fees');

        $fa = $this->getFields();

        if($nursing_fees == ""){
            $nursing_fees = 0;
        }

        if($consulting_fees == ""){
            $consulting_fees = 0;
        }

        if($other_fees == ""){
            $other_fees = 0;
        }

        if($dr_consultant_fees == ""){
            $dr_consultant_fees = 0;
        }

        if($oxygen_fees == ""){
            $oxygen_fees = 0;
        }

        if($medicine_fees == ""){
            $medicine_fees = 0;
        }

        if($fa['anesthetic_fees'] == ""){
            $fa['anesthetic_fees'] = 0;
        }

        if($fa['advance_payment'] == ""){
            $fa['advance_payment'] = 0;
        }

        if($fa['theater_assistant_fees'] == ""){
            $fa['theater_assistant_fees'] = 0;
        }

        if($fa['theatre_charges'] == ""){
            $fa['theatre_charges'] = 0;
        }

        if($fa['referral_charges'] == ""){
            $fa['referral_charges'] = 0;
        }

        if($fa['surgeon_fees'] == ""){
            $fa['surgeon_fees'] = 0;
        }

        if($fa['ot_charges'] == ""){
            $fa['ot_charges'] = 0;
        }

        if($fa['asst_surgeon_fees'] == ""){
            $fa['asst_surgeon_fees'] = 0;
        }

        if($fa['amount'] == ""){
            $fa['amount'] = 0;
        }

        $fa['nursing_fees']    = $nursing_fees;
        $fa['consulting_fees'] = $consulting_fees;
        $fa['other_fees']      = $other_fees;
        $fa['dr_consultant_fees']      = $dr_consultant_fees;
        $fa['oxygen_fees']     = $oxygen_fees;
        $fa['medicine_fees']   = $medicine_fees;
        
        $id = $fn->saveRecord($fa);

        $patientVisitRec = $fn->getRecordRowByID('in_patient', 'in_patient_id', $in_patient_id);
        //$employeeVisitRec = $fn->getRecordRowByID('employee_visit', 'in_patient_id', $in_patient_id);

        /*$SQLEmpVisit = "
        SELECT  eip.consultation_fees
               ,eip.employee_in_patient_id
               ,e.fees_commission_type
               ,e.fees_commission
        FROM employee_in_patient eip
        LEFT JOIN `employee` e ON (e.employee_id = eip.employee_id)
        WHERE eip.employee_in_patient_id = {$employee_in_patient_id}
        ORDER BY eip.employee_in_patient_id ASC
        ";
        $resultEmpVisit = $db->sql_query($SQLEmpVisit);
        $rowEmpVisit    = $db->sql_fetchrow($resultEmpVisit);

        $fees_commission = 0;
        if($consultation_fees > 0){
            if($rowEmpVisit['fees_commission_type'] == "%"){
                $fees_commission = ($rowEmpVisit['consultation_fees'] * $rowEmpVisit['fees_commission']) / 100;
            }else{
                $fees_commission = $rowEmpVisit['fees_commission'];
            }
        }*/
        $consultation_fees      = $fn->getPostParam('consultation_fees');
        $employee_in_patient_id = $fn->getReqParam('employee_in_patient_id');

        $SQLEmpVisit = "
        SELECT  eip.consultation_fees
               ,eip.employee_in_patient_id
               ,eip.employee_id
               ,e.fees_commission_type
               ,e.fees_commission
        FROM employee_in_patient eip
        LEFT JOIN `employee` e ON (e.employee_id = eip.employee_id)
        WHERE eip.employee_in_patient_id = {$employee_in_patient_id}
        ORDER BY eip.employee_in_patient_id ASC
        ";
        $resultEmpVisit = $db->sql_query($SQLEmpVisit);
        $rowEmpVisit    = $db->sql_fetchrow($resultEmpVisit);
        
        $faEV['consultation_fees'] = $consultation_fees;
        $faEV['employee_id']       = $rowEmpVisit['employee_id'];
        
        $whereCondition = "
        WHERE in_patient_id = {$in_patient_id}
        AND employee_in_patient_id = {$employee_in_patient_id}
        ";
        
        $SQLEV = $dbUtil->getUpdateSQLStringFromArray($faEV, 'employee_in_patient', $whereCondition);
        $db->sql_query($SQLEV);

        $SQLOrderCheck = "
        SELECT order_id
        FROM `order`
        WHERE in_patient_id = {$patientVisitRec['in_patient_id']}
        ";
        $resultOrderCheck  = $db->sql_query($SQLOrderCheck);
        $numRowsOrderCheck = $db->sql_numrows($resultOrderCheck);
        $rowOrderCheck     = $db->sql_fetchrow($resultOrderCheck);

        if($numRowsOrderCheck > 0){
            $updateOrder = "
            UPDATE `order` SET company_id = '' , company_name = '', cust_address1 = '', cust_address2 = '', cust_address_city = '', cust_address_state = '', cust_address_country_code = '', cust_phone = ''
            WHERE order_id = {$rowOrderCheck['order_id']}
            ";
            $resultOrder = $db->sql_query($updateOrder);
        }

        /*TO UPDATE PATIENT INFO RECORD*/
        if ($_SESSION['userGroupName'] == 'Administrator' || $_SESSION['userGroupName'] == 'Super Administrator'){
            $fa4 = array();
            $fa4['name']  = $fn->getPostParam('name');
            $fa4['phone']  = $fn->getPostParam('patient_phone');
            $fa4['gender']  = $fn->getPostParam('gender');
            $fa4['father_name']  = $fn->getPostParam('father_name');
            $fa4['spuse_name']  = $fn->getPostParam('spuse_name');
            $fa4['address_area']  = $fn->getPostParam('address_area');
            $fa4['age_year']  = $fn->getPostParam('age_year');
            $fa4['age_month']  = $fn->getPostParam('age_month');
            $fa4['age_day']  = $fn->getPostParam('age_day');

            $whereCondition = "
            WHERE patient_information_id = {$patientVisitRec['patient_information_id']}
            ";
            $updatePI = $dbUtil->getUpdateSQLStringFromArray($fa4, 'patient_information', $whereCondition);
            $resultPI = $db->sql_query($updatePI);
        }

        $fn->returnAfterNewSave($id, $cpCfg['cp.pagetoReturnAfterSave']);
    }

    /**
     *
     */
    function getSaveList(){
        $fn = Zend_Registry::get('fn');
        $fn->getSaveList();
    }

    /**
     *
     */
    function getFields(){
        $fn = Zend_Registry::get('fn');

        $fa = array();
        $fa = $fn->addToFieldsArray($fa, 'code');
        $fa = $fn->addToFieldsArray($fa, 'date_admitted');
        $fa = $fn->addToFieldsArray($fa, 'time_admitted');
        $fa = $fn->addToFieldsArray($fa, 'in_patient_id');
        $fa = $fn->addToFieldsArray($fa, 'patient_information_id');
        $fa = $fn->addToFieldsArray($fa, 'date_discharge');
        $fa = $fn->addToFieldsArray($fa, 'time_of_discharge');
        $fa = $fn->addToFieldsArray($fa, 'days_stayed');
        $fa = $fn->addToFieldsArray($fa, 'amount');
        $fa = $fn->addToFieldsArray($fa, 'status');
        $fa = $fn->addToFieldsArray($fa, 'summary');
        $fa = $fn->addToFieldsArray($fa, 'site_id');
        $fa = $fn->addToFieldsArray($fa, 'order_id');
        $fa = $fn->addToFieldsArray($fa, 'complain');
        $fa = $fn->addToFieldsArray($fa, 'weight');
        $fa = $fn->addToFieldsArray($fa, 'temperature');
        $fa = $fn->addToFieldsArray($fa, 'pulse_rate');
        $fa = $fn->addToFieldsArray($fa, 'respiratory_rate');
        $fa = $fn->addToFieldsArray($fa, 'blood_pressure');
        $fa = $fn->addToFieldsArray($fa, 'crt');
        $fa = $fn->addToFieldsArray($fa, 'employee_id');
        $fa = $fn->addToFieldsArray($fa, 'nursing_fees');
        $fa = $fn->addToFieldsArray($fa, 'other_fees');
        $fa = $fn->addToFieldsArray($fa, 'dr_consultant_fees');
        $fa = $fn->addToFieldsArray($fa, 'oxygen_fees');
        $fa = $fn->addToFieldsArray($fa, 'medicine_fees');
        $fa = $fn->addToFieldsArray($fa, 'date_surgery');
        $fa = $fn->addToFieldsArray($fa, 'advice_discharge');
        $fa = $fn->addToFieldsArray($fa, 'date_review');
        $fa = $fn->addToFieldsArray($fa, 'review_doctor');
        $fa = $fn->addToFieldsArray($fa, 'consultant_doctor');
        $fa = $fn->addToFieldsArray($fa, 'ref_doctor');
        $fa = $fn->addToFieldsArray($fa, 'admission_type');
        $fa = $fn->addToFieldsArray($fa, 'theater_assistant');
        $fa = $fn->addToFieldsArray($fa, 'anesthetists');
        $fa = $fn->addToFieldsArray($fa, 'surgeon');
        $fa = $fn->addToFieldsArray($fa, 'surgery_type');
        $fa = $fn->addToFieldsArray($fa, 'surgeon_fees');
        $fa = $fn->addToFieldsArray($fa, 'anesthetic_fees');
        $fa = $fn->addToFieldsArray($fa, 'theater_assistant_fees');
        $fa = $fn->addToFieldsArray($fa, 'duration_of_surgery');
        $fa = $fn->addToFieldsArray($fa, 'name_of_surgery');
        $fa = $fn->addToFieldsArray($fa, 'theatre_charges');
        $fa = $fn->addToFieldsArray($fa, 'start_time');
        $fa = $fn->addToFieldsArray($fa, 'end_time');
        $fa = $fn->addToFieldsArray($fa, 'referral_charges');
        $fa = $fn->addToFieldsArray($fa, 'advance_payment');
        $fa = $fn->addToFieldsArray($fa, 'asst_surgeon_fees');
        $fa = $fn->addToFieldsArray($fa, 'treatment_given');
        $fa = $fn->addToFieldsArray($fa, 'ot_staff');
        $fa = $fn->addToFieldsArray($fa, 'ot_charges');
        
        return $fa;
    }

    /**
     *
     */

    /**
     *
     */
    function getSearchComplainDetails() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $title = $fn->getReqParam('term', '', true);
        $extractor = explode(" **** ", $title);

        $patientDetail = $extractor[0];

        $SQL = "
        SELECT  title AS value
               ,title AS label
               ,complain_id AS id
               ,title AS complain_Name
        FROM complain
        WHERE (complain_id LIKE '{$patientDetail}%'
        OR title LIKE '{$patientDetail}%')
        GROUP BY title
        ORDER BY title
        ";
        $result = $db->sql_query($SQL);

        $dataArray = $dbUtil->getResultsetAsArray($result);
        $arr = json_encode($dataArray);
        return $arr;
    }

    /**
     *
     */
    function getSearchProcedureDetails() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $title = $fn->getReqParam('term', '', true);
        $extractor = explode(" **** ", $title);

        $patientDetail = $extractor[0];

        $SQL = "
        SELECT  in_patient_procedure AS value
               ,in_patient_procedure AS label
               ,in_patient_id AS id
               ,in_patient_procedure AS in_patient_procedure
        FROM in_patient
        WHERE (in_patient_procedure LIKE '{$patientDetail}%')
        ORDER BY in_patient_procedure
        ";
        $result = $db->sql_query($SQL);

        $dataArray = $dbUtil->getResultsetAsArray($result);
        $arr = json_encode($dataArray);
        return $arr;
    }

    /**
     *
     */
    function getSearchDiagnosisDetails() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $title = $fn->getReqParam('term', '', true);
        $extractor = explode(" **** ", $title);

        $patientDetail = $extractor[0];

        $SQL = "
        SELECT  disease_name AS value
               ,disease_name AS label
               ,prescription_id AS id
               ,disease_name AS disease_name
        FROM prescription
        WHERE (prescription_id LIKE '{$patientDetail}%'
        OR disease_name LIKE '{$patientDetail}%')
        ORDER BY disease_name
        ";
        $result = $db->sql_query($SQL);

        $dataArray = $dbUtil->getResultsetAsArray($result);
        $arr = json_encode($dataArray);
        return $arr;
    }

    /**
     *
     */
    function getAddComplain() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $in_patient_id  = $fn->getReqParam('in_patient_id');
        $title  = $fn->getReqParam('title');
        $complain_id  = $fn->getReqParam('complain_id');
        $patient_information_id  = $fn->getReqParam('patient_information_id');

        if($complain_id == ''){
            $fa = array();
            $fa['title']      = $title;
            $complain_id = $fn->addRecord($fa, 'complain');            
        }

        $SQLComplain = "
        SELECT complain
        FROM in_patient
        WHERE in_patient_id = {$in_patient_id}
        ";
        $resultComplain  = $db->sql_query($SQLComplain);
        $rowComplain     = $db->sql_fetchrow($resultComplain);

        $checkComplain = $rowComplain['complain'];

        if($checkComplain != ''){
            $checkComplain = $checkComplain.', '.$title;
        }
        else{
            $checkComplain = $title;
        }

        $SQLUPDATE = "
        UPDATE in_patient
        SET complain = '{$checkComplain}'
        WHERE in_patient_id = {$in_patient_id}
        ";

        $result  = $db->sql_query($SQLUPDATE);
    }

    /**
     *
     */
    function getAddProcedure() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $in_patient_id  = $fn->getReqParam('in_patient_id');
        $in_patient_procedure  = $fn->getReqParam('in_patient_procedure');

        $SQLprocedure = "
        SELECT in_patient_procedure
        FROM in_patient
        WHERE in_patient_id = {$in_patient_id}
        ";
        $resultProcedure  = $db->sql_query($SQLprocedure);
        $rowProcedure     = $db->sql_fetchrow($resultProcedure);

        $checkProcedure = $rowProcedure['in_patient_procedure'];

        if($checkProcedure != ''){
            $checkProcedure = $checkProcedure.', '.$in_patient_procedure;
        }
        else{
            $checkProcedure = $in_patient_procedure;
        }

        $SQLUPDATE = "
        UPDATE in_patient
        SET in_patient_procedure = '{$checkProcedure}'
        WHERE in_patient_id = {$in_patient_id}
        ";
        $result  = $db->sql_query($SQLUPDATE);

    }

    /**
     *
     */
    function getAddDiagnosis() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $in_patient_id  = $fn->getReqParam('in_patient_id');
        $disease_name  = $fn->getReqParam('disease_name');
        $prescription_id  = $fn->getReqParam('prescription_id');
        $patient_information_id  = $fn->getReqParam('patient_information_id');

        if($prescription_id == ''){
            $fa = array();
            $fa['disease_name']      = $disease_name;
            $prescription_id = $fn->addRecord($fa, 'prescription');            
        }

        $SQLComplain = "
        SELECT diagnosis
        FROM in_patient
        WHERE in_patient_id = {$in_patient_id}
        ";
        $resultComplain  = $db->sql_query($SQLComplain);
        $rowComplain     = $db->sql_fetchrow($resultComplain);

        $checkComplain = $rowComplain['diagnosis'];

        if($checkComplain != ''){
            $checkComplain = $checkComplain.', '.$disease_name;
        }
        else{
            $checkComplain = $disease_name;
        }

        $SQLUPDATE = "
        UPDATE in_patient
        SET diagnosis = '{$checkComplain}'
        WHERE in_patient_id = {$in_patient_id}
        ";

        $result  = $db->sql_query($SQLUPDATE);
    }

    /**
     *
     */
    function getSummaryPortalSubmit() {
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        if (!$this->getSummaryPortalValidate()){
            return $validate->getErrorMessageXML();
        }

        $in_patient_id = $fn->getPostParam('in_patient_id');
        $diagnosis = $fn->getPostParam('diagnosis');

        $fa = array();
        $fa['diagnosis']          = $diagnosis;

        $whereCondition = "
        WHERE in_patient_id = {$in_patient_id}
        ";
        $SQLInvoice = $dbUtil->getUpdateSQLStringFromArray($fa, 'in_patient', $whereCondition);
        $db->sql_query($SQLInvoice);

        $patientVisitRec = $fn->getRecordRowByID('in_patient', 'in_patient_id', $in_patient_id);


        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getSummaryPortalValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');

        $validate->resetErrorArray();

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getChiefComplainsSubmit() {
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        if (!$this->getChiefComplainsValidate()){
            return $validate->getErrorMessageXML();
        }

        $in_patient_id = $fn->getPostParam('in_patient_id');
        $complain = $fn->getPostParam('complain');

        $fa = array();
        $fa['complain']          = $complain;

        $whereCondition = "
        WHERE in_patient_id = {$in_patient_id}
        ";
        $SQLInvoice = $dbUtil->getUpdateSQLStringFromArray($fa, 'in_patient', $whereCondition);
        $db->sql_query($SQLInvoice);

        $patientVisitRec = $fn->getRecordRowByID('in_patient', 'in_patient_id', $in_patient_id);

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getChiefComplainsValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');

        $validate->resetErrorArray();

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getProcedurePortalSubmit() {
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        if (!$this->getProcedurePortalValidate()){
            return $validate->getErrorMessageXML();
        }

        $in_patient_id = $fn->getPostParam('in_patient_id');
        $in_patient_procedure = $fn->getPostParam('in_patient_procedure');

        $fa = array();
        $fa['in_patient_procedure']          = $in_patient_procedure;

        $whereCondition = "
        WHERE in_patient_id = {$in_patient_id}
        ";
        $SQLInvoice = $dbUtil->getUpdateSQLStringFromArray($fa, 'in_patient', $whereCondition);
        $db->sql_query($SQLInvoice);

        $patientVisitRec = $fn->getRecordRowByID('in_patient', 'in_patient_id', $in_patient_id);

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getProcedurePortalValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');

        $validate->resetErrorArray();

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    function getAddPrescribeMedicineFormSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        $prescription_id  = $fn->getReqParam('prescription_id');
        $in_patient_id = $fn->getReqParam('in_patient_id');

        $SQLEV = "
        SELECT ev.employee_id
        FROM employee_in_patient ev
        WHERE ev.in_patient_id = {$in_patient_id}
        ORDER BY ev.employee_in_patient_id ASC
        ";
        $resultEV = $db->sql_query($SQLEV);
        $rowEV    = $db->sql_fetchrow($resultEV);

        $SQLPM = "
        SELECT pm.*
        FROM prescribe_medicine pm
        WHERE prescription_id = {$prescription_id}
        ";
        $resultPM   = $db->sql_query($SQLPM);

        while ($rowPM = $db->sql_fetchrow($resultPM)) {

            $fa1 = array();

            $fa1['title']            = $rowPM['medicine_name'];
            $fa1['employee_id']      = $rowEV['employee_id'];
            $fa1['dosage']           = $rowPM['dosage'];
            $fa1['instruction']      = $rowPM['instruction'];
            $fa1['in_patient_id']    = $in_patient_id;
            $fa1['product_id']       = $rowPM['product_id'];
            $fa1['days']             = $rowPM['days'];
            $fa1['creation_date']    = date("Y-m-d H:i:s");
            $fa1['created_by']       = $fn->getSessionParam('userName');

            $insertPrescriptionSQL = $dbUtil->getInsertSQLStringFromArray($fa1, 'medicines_in_patient');
            $resultPrescriptionSQL = $db->sql_query($insertPrescriptionSQL);

        }
    }

    /**
     *
     */
    function getSearchProductTitle() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $dbUtil = Zend_Registry::get('dbUtil');

        $site_id = $fn->getSessionParam('cp_site_id');
        $title = $fn->getReqParam('term', '', true);
        $extractor = explode(" **** ", $title);

        $productTitle = $extractor[0];

        $SQL = "
        SELECT p.title AS value
              ,p.title AS label
              ,p.product_id AS id
              ,CONCAT_WS(' **** ', p.title) AS label
        FROM product p
        WHERE (p.title LIKE '{$productTitle}%')
          AND p.published = 1
        ORDER BY p.title
        ";
        $result = $db->sql_query($SQL);

        $dataArray = $dbUtil->getResultsetAsArray($result);
        $arr = json_encode($dataArray);
        return $arr;
    }

    /**
     *
     */
     function getAddProductAndLineItem() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');

        $title = $fn->getReqParam('title');
        $in_patient_id= $fn->getReqParam('in_patient_id');

        $fa = array();
        $fa['title']      = $title;
        $fa['published']  = 1;
        $fa['product_type']  = 'Purchasing and Selling';
        $fa['item_code']     = $this->getUpdateProductCode();
        $fa['created_by']    = $fn->getSessionParam('userName');
        $fa['creation_date'] = date("Y-m-d H:i:s");
        $product_id = $fn->addRecord($fa, 'product');

        $SQLEV = "
        SELECT ev.employee_id
        FROM employee_in_patient ev
        WHERE ev.in_patient_id = {$in_patient_id}
        ORDER BY ev.employee_in_patient_id ASC
        ";
        $resultEV = $db->sql_query($SQLEV);
        $rowEV    = $db->sql_fetchrow($resultEV);

        $fa = array();
        $fa['qty']              = 0;
        $fa['employee_id']      = $rowEV['employee_id'];
        $fa['in_patient_id'] = $in_patient_id;
        $id = $fn->addRecord($fa, 'medicines_in_patient');

        $arr = array();
        $arr['msg'] = '';

        $SQL    = "
        SELECT   p.title
        FROM product p
        WHERE p.product_id = '{$product_id}'
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);

        if($product_id != ''){
            $SQLUpdate    = "
            UPDATE medicines_in_patient
            set product_id = '{$product_id}'
            ,title = '{$row['title']}'
            WHERE medicines_in_patient_id = {$id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        return $cpUtil->getJsonFromArray($arr);
    }

    /**
     *
     */
    function getUpdateProductCode() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        /* Updation of Product Code */
        $nextProductItemCode = $fn->getSettingsValueByKey("nextProductItemCode");

        if($nextProductItemCode < 10){
            $ProCode = '000' . $nextProductItemCode;
        }
        else if($nextProductItemCode < 99){
            $ProCode = '00' . $nextProductItemCode;
        }
        else if($nextProductItemCode < 999){
            $ProCode = '0' . $nextProductItemCode;
        }
        else{
            $ProCode = $nextProductItemCode;
        }

        $SQL    = "UPDATE setting SET value = (value+1) WHERE key_text = 'nextProductItemCode'";
        $result = $db->sql_query($SQL);

        return $ProCode;
    }

    /**
     *
     */
     function getCreateProductLineItems() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');

        $product_id = $fn->getReqParam('product_id');
        $in_patient_id= $fn->getReqParam('in_patient_id');

        $SQLEV = "
        SELECT ev.employee_id
        FROM employee_in_patient ev
        WHERE ev.in_patient_id = {$in_patient_id}
        ORDER BY ev.employee_in_patient_id ASC
        ";
        $resultEV = $db->sql_query($SQLEV);
        $rowEV    = $db->sql_fetchrow($resultEV);

        $fa = array();
        $fa['qty']              = 0;
        $fa['employee_id']      = $rowEV['employee_id'];
        $fa['in_patient_id'] = $in_patient_id;
        $id = $fn->addRecord($fa, 'medicines_in_patient');

        $arr = array();
        $arr['msg'] = '';

        $patVisitRec = $fn->getRecordRowByID('in_patient', 'in_patient_id', $in_patient_id);
        $patInfoRec = $fn->getRecordRowByID('patient_information', 'patient_information_id', $patVisitRec['patient_information_id']);
        if($patVisitRec['weight'] == ''){
            $patVisitRec['weight'] = 0;
        }

        $SQL    = "
        SELECT   p.title
                ,p.dosage
                ,p.route
                ,p.medicine_qty
                ,p.instruction
                ,p.days
                ,(SELECT dw.dosage FROM dosage_wtwise dw WHERE dw.product_id = '{$product_id}' AND ({$patVisitRec['weight']} BETWEEN dw.wt_from AND dw.wt_to)) AS dosage_weight
        FROM product p
        WHERE p.product_id = '{$product_id}'
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);

        $SQLDW    = "
        SELECT dw.*
        FROM dosage_wtwise dw 
        WHERE dw.product_id = '{$product_id}'       
        ";
        $resultDW = $db->sql_query($SQLDW);
        $dosage_weight = '';
        $instruction_weight = '';
        while ($rowDW = $db->sql_fetchrow($resultDW)) {
            if($rowDW['wt_from'] == $patVisitRec['weight']){
                $dosage_weight = $rowDW['dosage'];
                $instruction_weight = $rowDW['instruction'];
            }elseif($rowDW['wt_from'] <= $patVisitRec['weight'] && $rowDW['wt_to'] >= $patVisitRec['weight']){
                $dosage_weight = $rowDW['dosage'];
                $instruction_weight = $rowDW['instruction'];
            }
        }


        $SQLDA    = "
        SELECT da.*
        FROM dosage_agewise da 
        WHERE da.product_id = '{$product_id}'       
        ";
        $resultDA = $db->sql_query($SQLDA);
        $dosage_age = '';
        $instruction_age = '';
        if($patInfoRec['age_year'] == ''){
            $patInfoRec['age_year'] = 0;
        }
        if($patInfoRec['age_month'] == ''){
            $patInfoRec['age_month'] = 0;
        }
        if($patInfoRec['age_day'] == ''){
            $patInfoRec['age_day'] = 0;
        }
        $age_year = $patInfoRec['age_year'] * 12 * 30;
        $age_month = $patInfoRec['age_month'] * 30;
        $age_day = $patInfoRec['age_day'];
        $age = $age_year + $age_month + $age_day;

        while ($rowDA = $db->sql_fetchrow($resultDA)) {
            if($rowDA['age_from_year'] == ''){
                $rowDA['age_from_year'] = 0;
            }
            if($rowDA['age_from_month'] == ''){
                $rowDA['age_from_month'] = 0;
            }
            if($rowDA['age_from_day'] == ''){
                $rowDA['age_from_day'] = 0;
            }

            $age_from_year = $rowDA['age_from_year'] * 12 * 30;
            $age_from_month = $rowDA['age_from_month'] * 30;
            $age_from_day = $rowDA['age_from_day'];
            $ageDAFrom = $age_from_year + $age_from_month + $age_from_day;

            $age_to_year = $rowDA['age_to_year'] * 12 * 30;
            $age_to_month = $rowDA['age_to_month'] * 30;
            $age_to_day = $rowDA['age_to_day'];
            $ageDATo = $age_to_year + $age_to_month + $age_to_day;

            if($ageDAFrom <= $age && $ageDATo >= $age){
                $dosage_age = $rowDA['dosage'];
                $instruction_age = $rowDA['instruction'];
            }
        }

        if($instruction_weight != ''){
            $instruction = $instruction_weight;
        } else if($instruction_age != '') {
            $instruction = $instruction_age;
        } else {
            $instruction = $row['instruction'];
        }

        if($dosage_weight != ''){
            $dosage = $dosage_weight;
        } else if($dosage_age != '') {
            $dosage = $dosage_age;
        } else {
            $dosage = $row['dosage'];
        }

        if($product_id != ''){
            $SQLUpdate    = "
            UPDATE medicines_in_patient
            set product_id = '{$product_id}'
            ,title = '{$row['title']}'
            ,dosage = '{$dosage}'
            ,route = '{$row['route']}'
            ,qty = '{$row['medicine_qty']}'
            ,instruction = '{$instruction}'
            ,days = '{$row['days']}'
            WHERE medicines_in_patient_id = {$id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        return $cpUtil->getJsonFromArray($arr);
    }

    /**
     *
     */
     function getUpdateProductLineItems() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');

        $product_id = $fn->getReqParam('product_id');
        $rec_id = $fn->getReqParam('rec_id');
        $id = $tv['srcRoomId'];
        $instruction = $fn->getReqParam('instruction');
        $days = $fn->getReqParam('days');
        $dosage = $fn->getReqParam('dosage');
        $qty = $fn->getReqParam('qty');
        $employee_id = $fn->getReqParam('employee_id');
        $selling_price = $fn->getReqParam('selling_price');
        $continue_medicine = $fn->getReqParam('continue_medicine');
        $treatment_given = $fn->getReqParam('treatment_given');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $route = $fn->getReqParam('route');

        $arr = array();
        $arr['msg'] = '';

        $SQL    = "
        SELECT   p.title
                ,p.dosage
        FROM product p
        WHERE p.product_id = '{$product_id}'
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);

        if($product_id != ''){
            $SQLUpdate    = "
            UPDATE medicines_in_patient
            set product_id = '{$product_id}'
            ,title = '{$row['title']}'
            WHERE medicines_in_patient_id = {$rec_id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        if($instruction != ''){
            $SQLUpdate    = "
            UPDATE medicines_in_patient
            set instruction = '{$instruction}'
            WHERE medicines_in_patient_id = {$rec_id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        if($route != ''){
            $SQLUpdate    = "
            UPDATE medicines_in_patient
            set route = '{$route}'
            WHERE medicines_in_patient_id = {$rec_id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        if($days != ''){
            $SQLUpdate    = "
            UPDATE medicines_in_patient
            set days = '{$days}'
            WHERE medicines_in_patient_id = {$rec_id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        if($dosage != ''){
            $SQLUpdate    = "
            UPDATE medicines_in_patient
            set dosage = '{$dosage}'
            WHERE medicines_in_patient_id = {$rec_id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        if($qty != ''){
            $SQLUpdate    = "
            UPDATE medicines_in_patient
            set qty = '{$qty}'
            WHERE medicines_in_patient_id = {$rec_id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        if($employee_id != ''){
            $SQLUpdate    = "
            UPDATE medicines_in_patient
            set employee_id = '{$employee_id}'
            WHERE medicines_in_patient_id = {$rec_id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        if($continue_medicine != ''){
            $SQLUpdate    = "
            UPDATE medicines_in_patient
            set continue_medicine = '{$continue_medicine}'
            WHERE medicines_in_patient_id = {$rec_id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        if($treatment_given != ''){
            $SQLUpdate    = "
            UPDATE medicines_in_patient
            set treatment_given = '{$treatment_given}'
            WHERE medicines_in_patient_id = {$rec_id}
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);
        }

        return $cpUtil->getJsonFromArray($arr);
    }

    /**
     *
     */
    function getMedicalTestRecordSubmit() {
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        if (!$this->getMedicalTestValidate()){
            return $validate->getErrorMessageXML();
        }

        $titles             = $fn->getPostParam('title', array());
        $fees_arr           = $fn->getPostParam('fees', array());
        $medicaltest_id_arr = $fn->getPostParam('medical_test_id', array());
        $in_patient_id   = $fn->getReqParam('in_patient_id');
        $notes_arr          = $fn->getPostParam('notes', array());

        $labRec = $fn->getRecordByCondition('medical_test_in_patient', "in_patient_id = '{$in_patient_id}'");
        if($labRec['medical_test_in_patient_id'] != ''){
            $SQLDelete = "DELETE FROM medical_test_in_patient WHERE in_patient_id = {$in_patient_id}";
            $db->sql_query($SQLDelete);
        }

        $count = count($titles);
        for ($i= 0; $i < $count; $i++) {

            $title          = $titles[$i];
            $title_explode  = explode('_', $title);
            $fees           = $fees_arr[$title_explode[1]];
            $medicaltest_id = $medicaltest_id_arr[$title_explode[1]];
            $notes          = $notes_arr[$title_explode[1]];
            $date = date('Y-m-d');

            if ($title) {
                $recCount = $fn->getRecordCount('medical_test_in_patient', "in_patient_id = '{$in_patient_id}' AND medical_test_id = '{$medicaltest_id}'");
                $rec_count = $recCount + 1;
                
                $fa = array();
                $fa['title']            = $title_explode[0];
                $fa['fees']             = $fees;
                $fa['status']           = 'Current';
                $fa['medical_test_id']  = $medicaltest_id;
                $fa['in_patient_id']    = $in_patient_id;
                $fa['creation_date']    = $date;
                $fa['created_by']       = $fn->getSessionParam('userName');
                $fa['notes']            = $notes;
                $fa['test_repeat']      = $rec_count;

                $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
                if ($cpCfg['cp.hasMultiUniqueSites']) {
                    $fa['site_id'] = $cpSiteIdSession;
                }
                $SQL    = $dbUtil->getInsertSQLStringFromArray($fa, 'medical_test_in_patient');
                $result = $db->sql_query($SQL);
            }
        }

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getMedicalTestValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');

        $validate->resetErrorArray();

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getMedicalTestRecordAgainSubmit() {
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        if (!$this->getMedicalTestValidate()){
            return $validate->getErrorMessageXML();
        }

        $in_patient_id   = $fn->getReqParam('in_patient_id');
        $medical_test_id   = $fn->getReqParam('medical_test_id');
        $medicalTestRec = $fn->getRecordRowByID('medical_test', 'medical_test_id', $medical_test_id);
        $date = date('Y-m-d');

        $recCount = $fn->getRecordCount('medical_test_in_patient', "in_patient_id = '{$in_patient_id}' AND medical_test_id = '{$medical_test_id}'");
        $rec_count = $recCount + 1;

        $fa = array();
        $fa['title']            = $medicalTestRec['title'];
        $fa['fees']             = $medicalTestRec['fees'];
        $fa['status']           = 'Current';
        $fa['medical_test_id']  = $medical_test_id;
        $fa['in_patient_id']    = $in_patient_id;
        $fa['creation_date']    = $date;
        $fa['created_by']       = $fn->getSessionParam('userName');
        $fa['test_repeat']      = $rec_count;

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $fa['site_id'] = $cpSiteIdSession;
        }

        $SQL    = $dbUtil->getInsertSQLStringFromArray($fa, 'medical_test_in_patient');
        $result = $db->sql_query($SQL);

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getMedicalTestRecordUpdate(){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        $lab_supplier_fees = $fn->getReqParam('lab_supplier_fees');
        $medical_test_id = $fn->getReqParam('medical_test_id');
        $supplier_id = $fn->getReqParam('supplier_id');
        $in_patient_id = $fn->getReqParam('in_patient_id');
        $container = $fn->getReqParam('container');

        $SQL = "
        UPDATE medical_test_in_patient SET lab_supplier_fees = '{$lab_supplier_fees}', supplier_id = '{$supplier_id}', container = '{$container}'
        WHERE in_patient_id = {$in_patient_id} 
          AND medical_test_id = {$medical_test_id}
        ";
        $result = $db->sql_query($SQL);
    }

    /**
     *
     */
    function getUpdateMedTestFeesAndNotes() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $dbUtil = Zend_Registry::get('dbUtil');

        $medical_test_id   = $fn->getReqParam('medical_test_id');
        $fees   = $fn->getReqParam('fees');
        $notes   = $fn->getReqParam('notes');
        $in_patient_id   = $fn->getReqParam('in_patient_id');
        $test_repeat   = $fn->getReqParam('test_repeat');
        $investigation_date   = $fn->getReqParam('investigation_date');

        $fa1 = array();
        if($fees != ''){
            $fa1['fees']  = $fees;
        }
        if($notes != ''){
            $fa1['notes'] = $notes;
        }
        if($investigation_date != ''){
            $fa1['creation_date'] = $investigation_date;
        }

        $whereCondition = "
        WHERE in_patient_id = '{$in_patient_id}' AND medical_test_id = '{$medical_test_id}' AND test_repeat = '{$test_repeat}'
        ";
        $SQLUpdate = $dbUtil->getUpdateSQLStringFromArray($fa1, 'medical_test_in_patient', $whereCondition);
        $db->sql_query($SQLUpdate);
    }

    /**
     *
     */
    function getUpdateMedicalVisitParameter() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $dbUtil = Zend_Registry::get('dbUtil');

        $medical_test_parameter_id   = $fn->getReqParam('medical_test_parameter_id');
        $medical_test_id   = $fn->getReqParam('medical_test_id');
        $notes   = $fn->getReqParam('notes');
        $in_patient_id   = $fn->getReqParam('in_patient_id');
        $test_repeat   = $fn->getReqParam('test_repeat');

        $fa1 = array();
        $fa1['medical_test_id']  = $medical_test_id;
        $fa1['medical_test_parameter_id']  = $medical_test_parameter_id;
        $fa1['creation_date']    = date("Y-m-d");
        $fa1['created_by']       = $fn->getSessionParam('userName');
        $fa1['notes']            = $notes;
        $fa1['in_patient_id'] = $in_patient_id;
        $fa1['test_repeat'] = $test_repeat;

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $fa1['site_id'] = $cpSiteIdSession;
        }

        $MVPRec = $fn->getRecordByCondition('medical_visit_parameter', "in_patient_id = '{$in_patient_id}' AND medical_test_id = '{$medical_test_id}' AND medical_test_parameter_id = '{$medical_test_parameter_id}' AND test_repeat = '{$test_repeat}'");
        if($MVPRec['medical_visit_parameter_id'] != ''){
            $whereCondition = "
            WHERE in_patient_id = '{$in_patient_id}' AND medical_test_id = '{$medical_test_id}' AND medical_test_parameter_id = '{$medical_test_parameter_id}' AND test_repeat = '{$test_repeat}'
            ";
            $SQLUpdate = $dbUtil->getUpdateSQLStringFromArray($fa1, 'medical_visit_parameter', $whereCondition);
            $db->sql_query($SQLUpdate);
        } else{
            $SQLPara    = $dbUtil->getInsertSQLStringFromArray($fa1, 'medical_visit_parameter');
            $resultPara = $db->sql_query($SQLPara);
        }
    }

    /**
     *
     */
    function getMedicalTestRecordDelete(){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        $in_patient_id   = $fn->getReqParam('in_patient_id');
        $medical_test_id   = $fn->getReqParam('medical_test_id');
        $test_repeat   = $fn->getReqParam('test_repeat');

        $SQL = "
        DELETE FROM medical_test_in_patient 
        WHERE in_patient_id = {$in_patient_id} 
          AND medical_test_id = {$medical_test_id}
          AND test_repeat = {$test_repeat}
        ";
        $result = $db->sql_query($SQL);
    }

    /**
     *
     */
    function getDeleteDoctorRecord(){
      $fn = Zend_Registry::get('fn');
      $db = Zend_Registry::get('db');
      $dbUtil = Zend_Registry::get('dbUtil');
      $cpCfg = Zend_Registry::get('cpCfg');

      $employee_in_patient_id = $fn->getReqParam('employee_in_patient_id');

      $SQL = "
      DELETE FROM employee_in_patient
      WHERE employee_in_patient_id = {$employee_in_patient_id}
      ";
      $result = $db->sql_query($SQL);
    }

    /**
     *
     */
    function getAddDoctorRecordSubmit() {
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        if (!$this->getAddDoctorRecordValidate()){
            return $validate->getErrorMessageXML();
        }

        $consultation_fees  = $fn->getPostParam('consultation_fees');
        $notes              = $fn->getPostParam('notes');
        $employee_id        = $fn->getReqParam('employee_id');
        $in_patient_id   = $fn->getReqParam('in_patient_id');

        $fa = array();
        $fa['notes']             = $notes;
        $fa['employee_id']       = $employee_id;
        $fa['in_patient_id']  = $in_patient_id;
        $fa['consultation_fees'] = $consultation_fees;
        $fa['creation_date']     = date("Y-m-d H:i:s");
        $fa['created_by']        = $fn->getSessionParam('userName');

        $SQL    = $dbUtil->getInsertSQLStringFromArray($fa, 'employee_in_patient');
        $result = $db->sql_query($SQL);

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getAddDoctorRecordValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');

        $validate->resetErrorArray();
        $employee_id = $fn->getPostParam('employee_id');
        $in_patient_id   = $fn->getPostParam('in_patient_id');

        $recCount = $fn->getRecordCount('employee_in_patient', "employee_id = '{$employee_id}' AND in_patient_id = '{$in_patient_id}'");
        $validate->validateData('employee_id', 'Please select Doctor/Nurse');

        if($recCount > 0){
            $validate->errorArray['employee_id']['name'] = "employee_id";
            $validate->errorArray['employee_id']['msg']  = "Doctor/Nurse already added";
        }

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getEditDoctorRecordSubmit() {
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        if (!$this->getEditDoctorRecordValidate()){
            return $validate->getErrorMessageXML();
        }

        $consultation_fees  = $fn->getPostParam('consultation_fees');
        $notes              = $fn->getPostParam('notes');
        $employee_id        = $fn->getReqParam('employee_id');
        $employee_in_patient_id   = $fn->getReqParam('employee_in_patient_id');

        $fa = array();
        $fa['notes']             = $notes;
        $fa['employee_id']       = $employee_id;
        $fa['consultation_fees'] = $consultation_fees;
        $fa['modification_date']    = date("Y-m-d H:i:s");
        $fa['modified_by']       = $fn->getSessionParam('userName');

        $whereCondition = "
        WHERE employee_in_patient_id = {$employee_in_patient_id}
        ";
        $SQL = $dbUtil->getUpdateSQLStringFromArray($fa, 'employee_in_patient', $whereCondition);
        $db->sql_query($SQL);

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getEditDoctorRecordValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');

        $validate->resetErrorArray();

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getUpdateConsultingFees() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cpUtil = Zend_Registry::get('cpUtil');

        $employee_id     = $fn->getReqParam('employee_id');
        $arr = array();

        if($employee_id != ''){
            $SQL    = "
            SELECT consultation_fees
            FROM employee
            WHERE employee_id = {$employee_id}
            ";
            $result = $db->sql_query($SQL);
            $row = $db->sql_fetchrow($result);

            return $row['consultation_fees'];
        }
    }

    /**
     *
     */
    function getCreateOrder() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg  = Zend_Registry::get('cpCfg');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $in_patient_id  = $fn->getReqParam('in_patient_id');

        $fa = array();

        $patientVisitRec = $fn->getRecordRowByID('in_patient', 'in_patient_id', $in_patient_id);
        $consVisitRec = $fn->getRecordByCondition('employee', "employee_id = '{$patientVisitRec['consultant_doctor']}'");

        $patientInfoRec  = $fn->getRecordRowByID('patient_information', 'patient_information_id', $patientVisitRec['patient_information_id']);
        $patientRow      = $fn->getRecordRowByID('patient_information', 'patient_information_id', $patientInfoRec['patient_information_id']);

        $fa['cust_first_name']               = $patientRow['name'];
        $fa['cust_address1']                 = $patientRow['address_street'];
        $fa['cust_address2']                 = $patientRow['address_area'];
        $fa['cust_address_city']             = $patientRow['address_city'];
        $fa['cust_address_country_code']     = $patientRow['address_country'];
        $fa['cust_address_po_code']          = $patientRow['address_code'];
        $fa['cust_phone']                    = $patientRow['phone'];
        $fa['shipping_first_name']           = $patientRow['name'];
        $fa['shipping_address1']             = $patientRow['address_street'];
        $fa['shipping_address_area']         = $patientRow['address_area'];
        $fa['shipping_address_city']         = $patientRow['address_city'];
        $fa['shipping_address_country_code'] = $patientRow['address_country'];
        $fa['shipping_address_po_code']      = $patientRow['address_code'];
        $fa['shipping_phone']                = $patientRow['phone'];
        $fa['primary_contact']               = $patientRow['primary_contact'];
        $fa['relationship']                  = $patientRow['relationship'];
        $fa['father_name']                   = $patientRow['father_name'];
        $fa['mother_name']                   = $patientRow['mother_name'];
        $fa['spuse_name']                    = $patientRow['spuse_name'];
        $fa['in_patient_id']                 = $in_patient_id;
        $fa['order_type']                    = 'IP';

        $SQLRelation = "
        SELECT b.patient_information_id
              ,CONCAT_WS(' ', b.first_name, b.middle_name, b.last_name) AS patient_name
              ,b.nric
        FROM `patient_relationinfo` a
        LEFT JOIN (patient_information b) ON (b.patient_information_id = a.patient_information_source_id)
        WHERE a.patient_information_id = {$patientInfoRec['patient_information_id']}
        ";
        $resultRelation = $db->sql_query($SQLRelation);
        $relation = '';

        while ($rowRelation = $db->sql_fetchrow($resultRelation)) {
            $relation .= $rowRelation['patient_name'].', ';
        }

        $relation = rtrim($relation, ', ');

        $fa['relationship']           = $relation;
        $fa['patient_information_id'] = $patientInfoRec['patient_information_id'];
        $fa['first_name']             = $patientInfoRec['name'];
        $fa['nric']                   = $patientInfoRec['nric'];
        $fa['bill_type']              = $patientInfoRec['bill_type'];
        $fa['serial_no_of_book']      = $patientInfoRec['serial_no_of_book'];
        $fa['department']             = $patientInfoRec['department'];
        $fa['worker_id']              = $patientInfoRec['worker_id'];

        $orderRec = $fn->getRecordByCondition('order', "in_patient_id = '{$in_patient_id}' AND order_type !='POS'");

        if(is_array($orderRec)){
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $fa['site_id'] = $cpSiteIdSession;
            }

            $fa['modification_date'] = date("Y-m-d H:i:s");
            $fa['modified_by']       = $_SESSION['userName'];

            $whereCondition = "WHERE order_id = {$orderRec['order_id']}";
            $sqlUpdate      = $dbUtil->getUpdateSQLStringFromArray($fa, "order", $whereCondition);
            $resultUpdate   = $db->sql_query($sqlUpdate);
            $order_id       = $orderRec['order_id'];
        } else {
            $appendSql = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSql = "WHERE site_id = {$cpSiteIdSession}";
            }

            $SQLOrderCode = "
            SELECT MAX(CONVERT(order_code, UNSIGNED INTEGER)) + 1 AS order_code
            FROM `order`
            {$appendSql}
            ";
            $resultOrderCode = $db->sql_query($SQLOrderCode);
            $rowOrderCode    = $db->sql_fetchrow($resultOrderCode);

            if($rowOrderCode['order_code'] != ""){
                $order_code = $rowOrderCode['order_code'];
            }
            else{
                $order_code = "1000";
            }

            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $fa['site_id'] = $cpSiteIdSession;
            }

            $fa['order_code']    = $order_code;
            $fa['order_date']    = date('Y-m-d');
            $fa['creation_date'] = date("Y-m-d H:i:s");
            $fa['created_by']    = $_SESSION['userName'];
            $fa['order_status']  = 'New';

            $SQLInsert    = $dbUtil->getInsertSQLStringFromArray($fa, 'order');
            $resultInsert = $db->sql_query($SQLInsert);
            $order_id     = $db->sql_nextid();
        }

        $SQLDoctor = "
        SELECT  CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
               ,ev.consultation_fees
               ,ev.notes
               ,ev.employee_in_patient_id
        FROM employee_in_patient ev
        LEFT JOIN employee e ON (e.employee_id = ev.employee_id)
        WHERE ev.in_patient_id = {$in_patient_id}
        ";
        $resultDoctor  = $db->sql_query($SQLDoctor);
        $numRowsDoctor = $db->sql_numrows($resultDoctor);

        if($numRowsDoctor > 0){

          while ($rowDoctor = $db->sql_fetchrow($resultDoctor)) {
            if($rowDoctor['consultation_fees'] == ''){
                $rowDoctor['consultation_fees'] = 0;
            }

            $fa4['record_id']   = $rowDoctor['employee_in_patient_id'];
            $fa4['order_id']    = $order_id;
            $fa4['record_type'] = 'Doctor/Nurse';
            $fa4['unit_price']  = $rowDoctor['consultation_fees'];
            $fa4['description'] = $rowDoctor['notes'];
            $fa4['item_title']  = $rowDoctor['employee_name'];

            $orderItemRec = $fn->getRecordByCondition('order_item',"record_id = '{$rowDoctor['employee_in_patient_id']}'
                                                                    AND order_id = {$order_id}
                                                                    AND record_type = 'Doctor/Nurse'");
            if(is_array($orderItemRec)){
                $fa4['modification_date']   = date("Y-m-d H:i:s");

                $whereCondition = "WHERE order_item_id = {$orderItemRec['order_item_id']}";
                $sqlOiUpdate    = $dbUtil->getUpdateSQLStringFromArray($fa4, "order_item", $whereCondition);
                $resultOiUpdate = $db->sql_query($sqlOiUpdate);
            } else {
                $fa4['creation_date'] = date("Y-m-d H:i:s");

                $SQLOI    = $dbUtil->getInsertSQLStringFromArray($fa4, 'order_item');
                $resultOI = $db->sql_query($SQLOI);
            }
          }

        }

        $SQLMedicalTestDelete = "
        DELETE FROM order_item
        WHERE order_id = {$order_id}
          AND record_type = 'Medical Test'
        ";
        $db->sql_query($SQLMedicalTestDelete);

        $SQLMedicalTest = "
        SELECT  mtv.title
               ,mtv.fees
               ,mtv.medical_test_in_patient_id
        FROM medical_test_in_patient mtv
        LEFT JOIN medical_test mt ON (mt.medical_test_id = mtv.medical_test_id)
        WHERE mtv.in_patient_id = {$in_patient_id}
        ";

        $resultMedicalTest  = $db->sql_query($SQLMedicalTest);
        $numRowsMedicalTest = $db->sql_numrows($resultMedicalTest);

        if($numRowsMedicalTest > 0){

          while ($rowMedicalTest = $db->sql_fetchrow($resultMedicalTest)) {

            if($rowMedicalTest['fees'] == ""){
                $rowMedicalTest['fees'] = 0;
            }

            $fa1['record_id']   = $rowMedicalTest['medical_test_in_patient_id'];
            $fa1['order_id']    = $order_id;
            $fa1['record_type'] = 'Medical Test';
            $fa1['unit_price']  = $rowMedicalTest['fees'];
            $fa1['item_title']  = $rowMedicalTest['title'];

            $orderItemRec = $fn->getRecordByCondition('order_item',"record_id = '{$rowMedicalTest['medical_test_in_patient_id']}' AND order_id = {$order_id} AND record_type = 'Medical Test'");
            if(is_array($orderItemRec)){
                $fa1['modification_date'] = date("Y-m-d H:i:s");

                $whereCondition = "WHERE order_item_id = {$orderItemRec['order_item_id']}";
                $sqlOiUpdate    = $dbUtil->getUpdateSQLStringFromArray($fa1, "order_item", $whereCondition);
                $resultOiUpdate = $db->sql_query($sqlOiUpdate);
            } else {
                $fa1['creation_date'] = date("Y-m-d H:i:s");

                $SQLOI    = $dbUtil->getInsertSQLStringFromArray($fa1, 'order_item');
                $resultOI = $db->sql_query($SQLOI);
            }
          }

        }

        if($patientVisitRec['advance_payment'] == "") {
            $patientVisitRec['advance_payment'] = 0;
        }

        $fa2 = array();
        $fa2['record_id']   = $in_patient_id;
        $fa2['order_id']    = $order_id;
        $fa2['record_type'] = 'Admission Details';
        $fa2['unit_price']  = $patientVisitRec['advance_payment'];
        $fa2['item_title']  = 'Advance Payment';
        
        $orderItemRec = $fn->getRecordByCondition('order_item',"record_id = '{$in_patient_id}' AND order_id = {$order_id} AND item_title = 'Advance Payment'");
        if(is_array($orderItemRec)){
            $fa2['modification_date'] = date("Y-m-d H:i:s");

            $whereCondition = "WHERE order_item_id = {$orderItemRec['order_item_id']}";
            $sqlOiUpdate    = $dbUtil->getUpdateSQLStringFromArray($fa2, "order_item", $whereCondition);
            $resultOiUpdate = $db->sql_query($sqlOiUpdate);
        } else {
            $fa2['creation_date'] = date("Y-m-d H:i:s");

            $SQLOI    = $dbUtil->getInsertSQLStringFromArray($fa2, 'order_item');
            $resultOI = $db->sql_query($SQLOI);
        }

    

        if($patientVisitRec['nursing_fees'] == "") {
            $patientVisitRec['nursing_fees'] = 0;
        }

        $fa2 = array();
        $fa2['record_id']   = $in_patient_id;
        $fa2['order_id']    = $order_id;
        $fa2['record_type'] = 'Admission Details';
        $fa2['unit_price']  = $patientVisitRec['nursing_fees'];
        $fa2['item_title']  = 'Nursing Fees';
        
        $orderItemRec = $fn->getRecordByCondition('order_item', "record_id = '{$in_patient_id}' AND order_id = {$order_id} AND item_title = 'Nursing Fees'");
        if(is_array($orderItemRec)){
            $fa2['modification_date'] = date("Y-m-d H:i:s");

            $whereCondition = "WHERE order_item_id = {$orderItemRec['order_item_id']}";
            $sqlOiUpdate    = $dbUtil->getUpdateSQLStringFromArray($fa2, "order_item", $whereCondition);
            $resultOiUpdate = $db->sql_query($sqlOiUpdate);
        } else {
            $fa2['creation_date'] = date("Y-m-d H:i:s");

            $SQLOI    = $dbUtil->getInsertSQLStringFromArray($fa2, 'order_item');
            $resultOI = $db->sql_query($SQLOI);
        }


        if($patientVisitRec['dr_consultant_fees'] == "") {
            $patientVisitRec['dr_consultant_fees'] = 0;
        }

        $fa2 = array();
        $fa2['record_id']   = $in_patient_id;
        $fa2['order_id']    = $order_id;
        $fa2['record_type'] = 'Admission Details';
        $fa2['unit_price']  = $patientVisitRec['dr_consultant_fees'];
        $fa2['item_title']  = $consVisitRec['first_name'];
        
        $orderItemRec = $fn->getRecordByCondition('order_item', "record_id = '{$in_patient_id}' AND order_id = {$order_id} AND item_title = '{$consVisitRec['first_name']}'");
        if(is_array($orderItemRec)){
            $fa2['modification_date'] = date("Y-m-d H:i:s");

            $whereCondition = "WHERE order_item_id = {$orderItemRec['order_item_id']}";
            $sqlOiUpdate    = $dbUtil->getUpdateSQLStringFromArray($fa2, "order_item", $whereCondition);
            $resultOiUpdate = $db->sql_query($sqlOiUpdate);
        } else {
            $fa2['creation_date'] = date("Y-m-d H:i:s");

            $SQLOI    = $dbUtil->getInsertSQLStringFromArray($fa2, 'order_item');
            $resultOI = $db->sql_query($SQLOI);
        }


        if($patientVisitRec['amount'] == "") {
            $patientVisitRec['amount'] = 0;
        }

        $fa2 = array();
        $fa2['record_id']   = $in_patient_id;
        $fa2['order_id']    = $order_id;
        $fa2['record_type'] = 'Admission Details';
        $fa2['unit_price']  = $patientVisitRec['amount'];
        $fa2['item_title']  = 'Room Rent';
        
        $orderItemRec = $fn->getRecordByCondition('order_item',"record_id = '{$in_patient_id}' AND order_id = {$order_id} AND item_title = 'Room Rent'");
        if(is_array($orderItemRec)){
            $fa2['modification_date'] = date("Y-m-d H:i:s");

            $whereCondition = "WHERE order_item_id = {$orderItemRec['order_item_id']}";
            $sqlOiUpdate    = $dbUtil->getUpdateSQLStringFromArray($fa2, "order_item", $whereCondition);
            $resultOiUpdate = $db->sql_query($sqlOiUpdate);
        } else {
            $fa2['creation_date'] = date("Y-m-d H:i:s");

            $SQLOI    = $dbUtil->getInsertSQLStringFromArray($fa2, 'order_item');
            $resultOI = $db->sql_query($SQLOI);
        }



        if($patientVisitRec['other_fees'] == "") {
            $patientVisitRec['other_fees'] = 0;
        }

        $fa2 = array();
        $fa2['record_id']   = $in_patient_id;
        $fa2['order_id']    = $order_id;
        $fa2['record_type'] = 'Admission Details';
        $fa2['unit_price']  = $patientVisitRec['other_fees'];
        $fa2['item_title']  = 'Other Fees';
        
        $orderItemRec = $fn->getRecordByCondition('order_item', "record_id = '{$in_patient_id}' AND order_id = {$order_id} AND item_title = 'Other Fees'");
        if(is_array($orderItemRec)){
            $fa2['modification_date'] = date("Y-m-d H:i:s");

            $whereCondition = "WHERE order_item_id = {$orderItemRec['order_item_id']}";
            $sqlOiUpdate    = $dbUtil->getUpdateSQLStringFromArray($fa2, "order_item", $whereCondition);
            $resultOiUpdate = $db->sql_query($sqlOiUpdate);
        } else {
            $fa2['creation_date'] = date("Y-m-d H:i:s");

            $SQLOI    = $dbUtil->getInsertSQLStringFromArray($fa2, 'order_item');
            $resultOI = $db->sql_query($SQLOI);
        }

        if($patientVisitRec['oxygen_fees'] == "") {
            $patientVisitRec['oxygen_fees'] = 0;
        }

        $fa2 = array();
        $fa2['record_id']   = $in_patient_id;
        $fa2['order_id']    = $order_id;
        $fa2['record_type'] = 'Admission Details';
        $fa2['unit_price']  = $patientVisitRec['oxygen_fees'];
        $fa2['item_title']  = 'Oxygen Fees';
        
        $orderItemRec = $fn->getRecordByCondition('order_item', "record_id = '{$in_patient_id}' AND order_id = {$order_id} AND item_title = 'Oxygen Fees'");
        if(is_array($orderItemRec)){
            $fa2['modification_date'] = date("Y-m-d H:i:s");

            $whereCondition = "WHERE order_item_id = {$orderItemRec['order_item_id']}";
            $sqlOiUpdate    = $dbUtil->getUpdateSQLStringFromArray($fa2, "order_item", $whereCondition);
            $resultOiUpdate = $db->sql_query($sqlOiUpdate);
        } else {
            $fa2['creation_date'] = date("Y-m-d H:i:s");

            $SQLOI    = $dbUtil->getInsertSQLStringFromArray($fa2, 'order_item');
            $resultOI = $db->sql_query($SQLOI);
        }

        if($patientVisitRec['medicine_fees'] == "") {
            $patientVisitRec['medicine_fees'] = 0;
        }

        $fa2 = array();
        $fa2['record_id']   = $in_patient_id;
        $fa2['order_id']    = $order_id;
        $fa2['record_type'] = 'Admission Details';
        $fa2['unit_price']  = $patientVisitRec['medicine_fees'];
        $fa2['item_title']  = 'Medicine Fees';
        
        $orderItemRec = $fn->getRecordByCondition('order_item', "record_id = '{$in_patient_id}' AND order_id = {$order_id} AND item_title = 'Medicine Fees'");
        if(is_array($orderItemRec)){
            $fa2['modification_date'] = date("Y-m-d H:i:s");

            $whereCondition = "WHERE order_item_id = {$orderItemRec['order_item_id']}";
            $sqlOiUpdate    = $dbUtil->getUpdateSQLStringFromArray($fa2, "order_item", $whereCondition);
            $resultOiUpdate = $db->sql_query($sqlOiUpdate);
        } else {
            $fa2['creation_date'] = date("Y-m-d H:i:s");

            $SQLOI    = $dbUtil->getInsertSQLStringFromArray($fa2, 'order_item');
            $resultOI = $db->sql_query($SQLOI);
        }

        if($patientVisitRec['surgeon_fees'] == ""){
            $patientVisitRec['surgeon_fees'] = 0;
        } 

        $fa2 = array();
        $fa2['record_id']   = $in_patient_id;
        $fa2['order_id']    = $order_id;
        $fa2['record_type'] = 'Surgery Details';
        $fa2['unit_price']  = $patientVisitRec['surgeon_fees'];
        $fa2['item_title']  = 'Surgeon Fees';
        
        $orderItemRec = $fn->getRecordByCondition('order_item', "record_id = '{$in_patient_id}' AND order_id = {$order_id} AND item_title = 'Surgeon Fees'");
        if(is_array($orderItemRec)){
            $fa2['modification_date'] = date("Y-m-d H:i:s");

            $whereCondition = "WHERE order_item_id = {$orderItemRec['order_item_id']}";
            $sqlOiUpdate    = $dbUtil->getUpdateSQLStringFromArray($fa2, "order_item", $whereCondition);
            $resultOiUpdate = $db->sql_query($sqlOiUpdate);
        } else {
            $fa2['creation_date'] = date("Y-m-d H:i:s");

            $SQLOI    = $dbUtil->getInsertSQLStringFromArray($fa2, 'order_item');
            $resultOI = $db->sql_query($SQLOI);
        }

        if($patientVisitRec['anesthetic_fees'] == "") {
            $patientVisitRec['anesthetic_fees'] = 0;
        }

        $fa2 = array();
        $fa2['record_id']   = $in_patient_id;
        $fa2['order_id']    = $order_id;
        $fa2['record_type'] = 'Surgery Details';
        $fa2['unit_price']  = $patientVisitRec['anesthetic_fees'];
        $fa2['item_title']  = 'Anaesthetic Fees';
        
        $orderItemRec = $fn->getRecordByCondition('order_item', "record_id = '{$in_patient_id}' AND order_id = {$order_id} AND item_title = 'Anaesthetic Fees'");
        if(is_array($orderItemRec)){
            $fa2['modification_date'] = date("Y-m-d H:i:s");

            $whereCondition = "WHERE order_item_id = {$orderItemRec['order_item_id']}";
            $sqlOiUpdate    = $dbUtil->getUpdateSQLStringFromArray($fa2, "order_item", $whereCondition);
            $resultOiUpdate = $db->sql_query($sqlOiUpdate);
        } else {
            $fa2['creation_date'] = date("Y-m-d H:i:s");

            $SQLOI    = $dbUtil->getInsertSQLStringFromArray($fa2, 'order_item');
            $resultOI = $db->sql_query($SQLOI);
        }

        if($patientVisitRec['theater_assistant_fees'] == "") {
            $patientVisitRec['theater_assistant_fees'] = 0;
        }

        $fa2 = array();
        $fa2['record_id']   = $in_patient_id;
        $fa2['order_id']    = $order_id;
        $fa2['record_type'] = 'Surgery Details';
        $fa2['unit_price']  = $patientVisitRec['theater_assistant_fees'];
        $fa2['item_title']  = 'Theatre Assistant Fees';
        
        $orderItemRec = $fn->getRecordByCondition('order_item', "record_id = '{$in_patient_id}' AND order_id = {$order_id} AND item_title = 'Theatre Assistant Fees'");
        if(is_array($orderItemRec)){
            $fa2['modification_date'] = date("Y-m-d H:i:s");

            $whereCondition = "WHERE order_item_id = {$orderItemRec['order_item_id']}";
            $sqlOiUpdate    = $dbUtil->getUpdateSQLStringFromArray($fa2, "order_item", $whereCondition);
            $resultOiUpdate = $db->sql_query($sqlOiUpdate);
        } else {
            $fa2['creation_date'] = date("Y-m-d H:i:s");

            $SQLOI    = $dbUtil->getInsertSQLStringFromArray($fa2, 'order_item');
            $resultOI = $db->sql_query($SQLOI);
        }

        if($patientVisitRec['theatre_charges'] == "") {
            $patientVisitRec['theatre_charges'] = 0;
        }
            
        $fa2 = array();
        $fa2['record_id']   = $in_patient_id;
        $fa2['order_id']    = $order_id;
        $fa2['record_type'] = 'Surgery Details';
        $fa2['unit_price']  = $patientVisitRec['theatre_charges'];
        $fa2['item_title']  = 'Theatre Charges';
        
        $orderItemRec = $fn->getRecordByCondition('order_item', "record_id = '{$in_patient_id}' AND order_id = {$order_id} AND item_title = 'Theatre Charges'");
        if(is_array($orderItemRec)){
            $fa2['modification_date']   = date("Y-m-d H:i:s");

            $whereCondition = "WHERE order_item_id = {$orderItemRec['order_item_id']}";
            $sqlOiUpdate    = $dbUtil->getUpdateSQLStringFromArray($fa2, "order_item", $whereCondition);
            $resultOiUpdate = $db->sql_query($sqlOiUpdate);
        } else {
            $fa2['creation_date'] = date("Y-m-d H:i:s");

            $SQLOI    = $dbUtil->getInsertSQLStringFromArray($fa2, 'order_item');
            $resultOI = $db->sql_query($SQLOI);
        }

        if($patientVisitRec['referral_charges'] == ""){
            $patientVisitRec['referral_charges'] = 0;
        }
            
        $fa2 = array();
        $fa2['record_id']   = $in_patient_id;
        $fa2['order_id']    = $order_id;
        $fa2['record_type'] = 'Surgery Details';
        $fa2['unit_price']  = $patientVisitRec['referral_charges'];
        $fa2['item_title']  = 'Referral Charges';
        
        $orderItemRec = $fn->getRecordByCondition('order_item', "record_id = '{$in_patient_id}' AND order_id = {$order_id} AND item_title = 'Referral Charges'");
        if(is_array($orderItemRec)){
            $fa2['modification_date'] = date("Y-m-d H:i:s");

            $whereCondition = "WHERE order_item_id = {$orderItemRec['order_item_id']}";
            $sqlOiUpdate    = $dbUtil->getUpdateSQLStringFromArray($fa2, "order_item", $whereCondition);
            $resultOiUpdate = $db->sql_query($sqlOiUpdate);
        } else {
            $fa2['creation_date'] = date("Y-m-d H:i:s");

            $SQLOI    = $dbUtil->getInsertSQLStringFromArray($fa2, 'order_item');
            $resultOI = $db->sql_query($SQLOI);
        }

        if($patientVisitRec['asst_surgeon_fees'] == "") {
            $patientVisitRec['asst_surgeon_fees'] = 0;
        }
            
        $fa2 = array();
        $fa2['record_id']  = $in_patient_id;
        $fa2['order_id']   = $order_id;
        $fa2['record_type']= 'Surgery Details';
        $fa2['unit_price'] = $patientVisitRec['asst_surgeon_fees'];
        $fa2['item_title'] = 'Asst Surgeon Fees';
        
        $orderItemRec = $fn->getRecordByCondition('order_item', "record_id = '{$in_patient_id}' AND order_id = {$order_id} AND item_title = 'Asst Surgeon Fees'");
        if(is_array($orderItemRec)){
            $fa2['modification_date'] = date("Y-m-d H:i:s");

            $whereCondition = "WHERE order_item_id = {$orderItemRec['order_item_id']}";
            $sqlOiUpdate    = $dbUtil->getUpdateSQLStringFromArray($fa2, "order_item", $whereCondition);
            $resultOiUpdate = $db->sql_query($sqlOiUpdate);
        } else {
            $fa2['creation_date'] = date("Y-m-d H:i:s");

            $SQLOI    = $dbUtil->getInsertSQLStringFromArray($fa2, 'order_item');
            $resultOI = $db->sql_query($SQLOI);
        }

        if($patientVisitRec['ot_charges'] == "") {
            $patientVisitRec['ot_charges'] = 0;
        }
            
        $fa2 = array();
        $fa2['record_id']   = $in_patient_id;
        $fa2['order_id']    = $order_id;
        $fa2['record_type'] = 'Admission Details';
        $fa2['unit_price']  = $patientVisitRec['ot_charges'];
        $fa2['item_title']  = 'OT Charges';
        
        $orderItemRec = $fn->getRecordByCondition('order_item', "record_id = '{$in_patient_id}' AND order_id = {$order_id} AND item_title = 'OT Charges'");
        if(is_array($orderItemRec)){
            $fa2['modification_date'] = date("Y-m-d H:i:s");

            $whereCondition = "WHERE order_item_id = {$orderItemRec['order_item_id']}";
            $sqlOiUpdate    = $dbUtil->getUpdateSQLStringFromArray($fa2, "order_item", $whereCondition);
            $resultOiUpdate = $db->sql_query($sqlOiUpdate);
        } else {
            $fa2['creation_date'] = date("Y-m-d H:i:s");

            $SQLOI    = $dbUtil->getInsertSQLStringFromArray($fa2, 'order_item');
            $resultOI = $db->sql_query($SQLOI);
        }

        return $order_id;
    }

     

    /**
     *
     */
    function getCancelInPatientRecord(){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cpUtil = Zend_Registry::get('cpUtil');

        $in_patient_id     = $fn->getReqParam('in_patient_id');

        $SQLPatientVisit ="
        UPDATE in_patient SET status = 'Cancelled'
        WHERE in_patient_id = '{$in_patient_id}'
        ";
        $resultPatientVisit = $db->sql_query($SQLPatientVisit);

        $SQLOrder = "
        SELECT order_id
        FROM `order`
        WHERE in_patient_id = '{$in_patient_id}'
        ";
        $resultOrder  = $db->sql_query($SQLOrder);
        $rowOrder     = $db->sql_fetchrow($resultOrder);
        $numRowsOrder = $db->sql_numrows($resultOrder);

        if($numRowsOrder > 0){
            $SQLUpdateOrder = "
            UPDATE `order` SET order_status = 'Cancelled'
            WHERE order_id = '{$rowOrder['order_id']}'
            ";
            $resultUpdateOrder = $db->sql_query($SQLUpdateOrder);

            $SQLInvoice = "
            UPDATE invoice SET status = 'Cancelled'
            WHERE order_id = '{$rowOrder['order_id']}'
            ";
            $resultInvoice = $db->sql_query($SQLInvoice);

            $SQLReceipt = "
            UPDATE receipt SET receipt_status = 'Cancelled'
            WHERE order_id = '{$rowOrder['order_id']}'
            ";
            $resultReceipt = $db->sql_query($SQLReceipt);
        }

    }


    /**
     *
     */
    function getApplyMedicine() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $patient_visit_id= $fn->getReqParam('patient_visit_id');
        $in_patient_id= $fn->getReqParam('in_patient_id');

        $SQLEV = "
        SELECT mt.*
        FROM medicines_visit mt
        WHERE mt.patient_visit_id = '{$patient_visit_id}'
        ";
        $resultEV = $db->sql_query($SQLEV);
        while ($rowEV = $db->sql_fetchrow($resultEV)) {
            $fa = array();
            $fa['qty']              = $rowEV['qty'];
            $fa['title']            = $rowEV['title'];
            $fa['dosage']           = $rowEV['dosage'];
            $fa['route']            = $rowEV['route'];
            $fa['days']             = $rowEV['days'];
            $fa['instruction']      = $rowEV['instruction'];
            $fa['employee_id']      = $rowEV['employee_id'];
            $fa['product_id']       = $rowEV['product_id'];
            $fa['in_patient_id'] = $in_patient_id;
            $id = $fn->addRecord($fa, 'medicines_in_patient');
        }
    }

    /**
     *
     */
    function getDeleteMedicineRecord(){
      $fn = Zend_Registry::get('fn');
      $db = Zend_Registry::get('db');
      $dbUtil = Zend_Registry::get('dbUtil');
      $cpCfg = Zend_Registry::get('cpCfg');

      $medicines_in_patient_id = $fn->getReqParam('medicines_in_patient_id');

      $SQL = "
      DELETE FROM medicines_in_patient
      WHERE medicines_in_patient_id = {$medicines_in_patient_id}
      ";
      $result = $db->sql_query($SQL);
    }

    function getPrintPdfLink() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');
        //http://habibiahms.localhost/admin/index.php?module=hms_inPatient&_spAction=printPdfLink&showHTML=0

        ini_set('memory_limit', '512M');
        set_time_limit(50000);
        include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
        include_once(CP_LOCAL_PATH.'lib/headfoot.php');

        $pdf = new MYPDF_Local(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('USS');
        $pdf->SetSubject('Print Link');
        $pdf->SetTitle('Print Link');

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 04', PDF_HEADER_STRING);
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        //$pdf->SetHeaderMargin(PDF_MARGIN_HEADER,10);
        //$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }


        /*HEADER PART AND FOOTER PART FUNCTIONS HAS BEEN ADDED IN (headfoot.php) PATH INCLUDE: (admin/lib/headfoot.php)*/
        $pdf->AddPage();

        $today = date("d-m-Y");

        $tbl1 = '
        <table border="0" width="100%">
            <tr>
                <td align="center" style="font-size:16px; font-weight:bold">AS-Siddiq Centre for Islamic Studies Pte Ltd</td>
            </tr>
            <tr>
                <td align="center" style="font-size:12px; font-weight:bold">Registration Form-Weekend Islamic School</td>
            </tr>
        </table>
        ';

        $tbl2 ='
        <table border="1" width="100%" cellpadding="4">
            <tr>
                <td style="font-size:12px; font-weight:bold; background-color:#000000; color:#ffffff;">PARENT / LEGAL GUARDIAN INFORMATION</td>
            </tr>
            <tr>
                <td width="25%" style="font-size:10px;">Name</td>
                <td width= "75%" style="font-size:10px;  background-color:#D3D3D3;"></td>
            </tr>
            <tr>
                <td width="25%" style="font-size:10px;">NRIC No.</td>
                <td width= "25%" style="font-size:10px;  background-color:#D3D3D3;"></td>
                <td width="25%" align="right" style="font-size:10px;">Telephone(Res)</td>
                <td width= "25%" style="font-size:10px;  background-color:#D3D3D3;"></td>
            </tr>
            <tr>
                <td width="25%" style="font-size:10px;">Telephone(Mobile)</td>
                <td width= "25%" style="font-size:10px;  background-color:#D3D3D3;"></td>
                <td width="10%" align="right" style="font-size:10px;">Email</td>
                <td width= "40%" style="font-size:10px;  background-color:#D3D3D3;"></td>
            </tr>
            <tr>
                <td width="25%" style="font-size:10px;">Relationship to Student</td>
                <td width= "25%" style="font-size:10px;  background-color:#D3D3D3;"></td>
                <td width="25%" align="right" style="font-size:10px;">Occupation</td>
                <td width= "25%" style="font-size:10px;  background-color:#D3D3D3;"></td>
            </tr>
            <tr>
                <td width="25%" style="font-size:10px;">Emergency Contact</td>
                <td width="10%" style="font-size:10px;">Name</td>
                <td width= "65%" style="font-size:10px;  background-color:#D3D3D3;"></td>
            </tr>
            <tr>
                <td width="25%" style="font-size:10px;"></td>
                <td width="22%" style="font-size:10px;">Relationship to Student</td>
                <td width= "18%" style="font-size:10px;  background-color:#D3D3D3;"></td>
                <td width="15%" align="right" style="font-size:10px;">Contact No.</td>
                <td width= "20%" style="font-size:10px;  background-color:#D3D3D3;"></td>
            </tr>
            <tr>
                <td width="25%" rowspan="2" style="font-size:10px;">Correspondence Address</td>
                <td width= "75%" style="font-size:10px;  background-color:#D3D3D3;"></td>
            </tr>
            <tr>
                <td width= "40%" align="right" style="font-size:10px;  background-color:#D3D3D3;"></td>
                <td width="15%" align="right" style="font-size:10px;">Postal Code</td>
                <td width= "20%" style="font-size:10px;  background-color:#D3D3D3;"></td>
            </tr>
            <tr>
                <td width="25%" style="font-size:10px;">Centre</td>
                <td width= "75%" style="font-size:10px;  background-color:#D3D3D3;">SIMPLY ISLAM HEADQUARTERS <br/>152 Still Road Singapore 423991</td>
            </tr>
        </table>
        ';

        $tbl3 ='
        <table border="1" width="100%" cellpadding="4">
            <tr>
                <td width="5%" rowspan="6" style="font-size:12px; font-weight:bold; background-color:#000000; color:#ffffff;">STUDENT INFO(1)</td>
                <td width="15%" style="font-size:10px;">Student Name</td>
                <td width= "80%" style="font-size:10px;  background-color:#D3D3D3;"></td>
            </tr>
            <tr>
                <td width="15%" style="font-size:10px;">Date of Birth</td>
                <td width= "20%" style="font-size:10px;  background-color:#D3D3D3;"></td>
                <td width="30%" align="right" style="font-size:10px;">Gender</td>
                <td width= "30%" style="font-size:10px;  background-color:#D3D3D3;">Male/Female</td>
            </tr>
            <tr>
                <td width="15%" style="font-size:10px;">BC/NRIC No.</td>
                <td width= "20%" style="font-size:10px;  background-color:#D3D3D3;"></td>
                <td width="30%" align="right" style="font-size:10px;">Other Information <br/>(Allergies, ADHD, Autism, etc.)</td>
                <td width= "30%" style="font-size:10px;  background-color:#D3D3D3;"></td>
            </tr>
            <tr>
                <td width="48%" style="font-size:10px;">Level Applying For(tick one)</td>
                <td width="47%" style="font-size:10px;">Session Applying For(tick one)</td>
            </tr>
            <tr>
                <td width="48%" style="font-size:10px; background-color:#D3D3D3;">
                    <table border="0" width="100%" cellpadding="1">
                        <tr style="margin-top:20px;">
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">K1 (5yrs)</td>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Primary 5 (11yrs)</td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">K2 (6yrs)</td>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Primary 6 (12yrs)</td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Primary 1 (7yrs)</td>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Secondary 1 (13yrs)</td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Primary 2 (8yrs)</td>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Secondary 2 (14yrs)</td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Primary 3 (9yrs)</td>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Secondary 3 (15yrs)</td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Primary 4 (10yrs)</td>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Secondary 4 (16yrs)</td>
                        </tr>
                        <tr style="border-top:1px solid #000000;">
                            <td width="100%"></td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Tertiary Year 1</td>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Tertiary Year 2</td>
                        </tr>
                    </table>
                </td>
                <td width="47%" style="font-size:10px;">
                    <table border="0" width="100%" cellpadding="1">
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="95%">Session 1 (Saturday 9a.m - 1p.m) or</td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="95%">Session 2 (Sunday 9a.m - 1p.m)</td>
                        </tr><br/>
                        <tr>
                            <td width="100%" style="font-weight:bold;">This session is only for Secondary 4</td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="95%">Session 3 (Saturday 1.30p.m - 5.30p.m)</td>
                        </tr><br/>
                        <tr>
                            <td width="104%" style="border-top:1px solid #000000;"></td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="95%">Session 5 (Saturday 1.30p.m - 5.30p.m)</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        ';

        $tbl4 ='
        <table border="1" width="100%" cellpadding="4">
            <tr>
                <td width="5%" rowspan="6" style="font-size:12px; font-weight:bold; background-color:#000000; color:#ffffff;">STUDENT INFO(2)</td>
                <td width="15%" style="font-size:10px;">Student Name</td>
                <td width= "80%" style="font-size:10px;  background-color:#D3D3D3;"></td>
            </tr>
            <tr>
                <td width="15%" style="font-size:10px;">Date of Birth</td>
                <td width= "20%" style="font-size:10px;  background-color:#D3D3D3;"></td>
                <td width="30%" align="right" style="font-size:10px;">Gender</td>
                <td width= "30%" style="font-size:10px;  background-color:#D3D3D3;">Male/Female</td>
            </tr>
            <tr>
                <td width="15%" style="font-size:10px;">BC/NRIC No.</td>
                <td width= "20%" style="font-size:10px;  background-color:#D3D3D3;"></td>
                <td width="30%" align="right" style="font-size:10px;">Other Information <br/>(Allergies, ADHD, Autism, etc.)</td>
                <td width= "30%" style="font-size:10px;  background-color:#D3D3D3;"></td>
            </tr>
            <tr>
                <td width="48%" style="font-size:10px;">Level Applying For(tick one)</td>
                <td width="47%" style="font-size:10px;">Session Applying For(tick one)</td>
            </tr>
            <tr>
                <td width="48%" style="font-size:10px; background-color:#D3D3D3;">
                    <table border="0" width="100%" cellpadding="1">
                        <tr>
                            <td width="5%" style="height:5px;border:1px solid #9A9A93;"></td>
                            <td width="45%">K1 (5yrs)</td>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Primary 5 (11yrs)</td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">K2 (6yrs)</td>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Primary 6 (12yrs)</td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Primary 1 (7yrs)</td>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Secondary 1 (13yrs)</td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Primary 2 (8yrs)</td>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Secondary 2 (14yrs)</td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Primary 3 (9yrs)</td>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Secondary 3 (15yrs)</td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Primary 4 (10yrs)</td>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Secondary 4 (16yrs)</td>
                        </tr>
                        <tr>
                            <td style="border-top:1px solid #000000;"></td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Tertiary Year 1</td>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="45%">Tertiary Year 2</td>
                        </tr>
                    </table>
                </td>
                <td width="47%" style="font-size:10px;">
                    <table border="0" width="100%" cellpadding="1">
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="95%">Session 1 (Saturday 9a.m - 1p.m) or</td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="95%">Session 2 (Sunday 9a.m - 1p.m)</td>
                        </tr><br/>
                        <tr>
                            <td width="100%" style="font-weight:bold;">This session is only for Secondary 4</td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="95%">Session 3 (Saturday 1.30p.m - 5.30p.m)</td>
                        </tr><br/>
                        <tr>
                            <td width="104%" style="border-top:1px solid #000000;"></td>
                        </tr>
                        <tr>
                            <td width="5%" style="border:1px solid #9A9A93;"></td>
                            <td width="95%">Session 5 (Saturday 1.30p.m - 5.30p.m)</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        ';

        $tbl5 ='
        <table border="1" width="100%" cellpadding="4">
            <tr>
                <td width="25%" style="font-size:12px; font-weight:bold; background-color:#000000; color:#ffffff;">FOR OFFICE USE</td>
                <td width="75%" style="font-size:10px;">
                    <table border="0" width="100%" cellpadding="1">
                        <tr>
                            <td width="3%" style="border:1px solid #9A9A93;"></td>
                            <td width="39%">Birth Certificates</td>
                            <td width="3%" style="border:1px solid #9A9A93;"></td>
                            <td width="55%">Parent/Legal Gardian NRIC</td>
                        </tr>
                        <tr>
                            <td width="3%" style="border:1px solid #9A9A93;"></td>
                            <td width="39%">Regn & Fees Payment</td>
                            <td width="3%" style="border:1px solid #9A9A93;"></td>
                            <td width="55%">Receipt Issued:_________ Date:_________</td>
                        </tr>
                        <tr>
                            <td width="3%" style="border:1px solid #9A9A93;"></td>
                            <td width="39%">GIRO Form</td>
                            <td width="3%" style="border:1px solid #9A9A93;"></td>
                            <td width="55%">DDA</td>
                        </tr>
                        <tr>
                            <td width="3%" style="border:1px solid #9A9A93;"></td>
                            <td width="39%">Cash</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        ';

        $tbl6 ='
        <table border="0" width="100%" cellpadding="4" style="font-size:10px;">
            <tr>
                <td width="100%">
                    Declaration: I hereby certify that the above information is true and accurate at the time of registration.
                </td>
            </tr><br/><br/>
            <tr>
                <td width="25%" style="border-top:2px solid black">Date</td>
                <td width="10%"></td>
                <td width="40%" style="border-top:2px solid black">Signature of Parent / Legal Guardian</td>
            </tr><br/><br/>
            <tr>
                <td align="center" width="100%" style="font-size:16px; font-weight:bold; border-top:2px solid black">AS-Siddiq Centre for Islamic Studies Pte Ltd</td>
            </tr>
            <tr>
                <td align="center" style="font-size:12px">152 Still Road Singapure 423991 - Tel:65474407 - Fax:63486023 - Email:info@simplyislam.sg</td>
            </tr>
        </table>
        ';

        $pdf->ln(-30);
        $pdf->writeHTML($tbl1, true, false, false, false, '');
        $pdf->ln(-4);
        $pdf->writeHTML($tbl2, true, false, false, false, '');
        $pdf->ln(-4);
        $pdf->writeHTML($tbl3, true, false, false, false, '');
        $pdf->ln(-4);
        $pdf->writeHTML($tbl4, true, false, false, false, '');
        $pdf->ln(-4);
        $pdf->writeHTML($tbl5, true, false, false, false, '');
        $pdf->ln(-6);
        $pdf->writeHTML($tbl6, true, false, false, false, '');
        $download_title = 'Print.pdf';
        ob_start();
        $pdf->Output($download_title, 'I');
    }
}
