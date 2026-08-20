<?
class CPL_Admin_Widgets_Hms_ReferenceDoctorAppointmentReport_View extends CP_Common_Lib_WidgetViewAbstract
{
    //==================================================================//
     function getWidget() {
        $fn = Zend_Registry::get('fn');
        $c = &$this->controller;

        $rowsHTML = $this->getRowsHTML();
        $text = '';

        if ($rowsHTML != ""){
            $rowCount = substr_count($rowsHTML, "<tr>"); 
            $text = "
            <table class='thinlist summaryTable'>
                <thead>
                    <th colspan='7'>Reference Doctor Appointment Report</th>
                    <th colspan='7'>Patient Count - $rowCount</th>

                </thead>
            </table>
            <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>S NO</th>
                        <th>Patient Name</th>
                        <th>Visit Code</th> 
                        <th>Date</th>
                        <th>Doctor Name</th>
                    </tr>
                </thead>
                <tbody>
                   {$this->getRowsHTML()}
                </tbody>
            </table>
            </div>
            ";
        }

        return $text;
    }
    
    /**
     *
     */
    function getRowsHTML() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $rows = '';
        $counter = 1;
       
        foreach($this->model->dataArray as $row){
            $product_link = "index.php?_topRm=main&module=hms_patientVisit&_action=edit&patient_visit_id={$row['patient_visit_id']}";
            $patVisit = $fn->getRecordByCondition('patient_visit', "patient_visit_id = '{$row['patient_visit_id']}'");
            $visit_code = "<a href='{$product_link}' target='_blank'><u>{$patVisit['visit_code']}</u></a>";
            $patientName = $fn->getRecordByCondition('patient_information', "patient_information_id = '{$row['patient_information_id']}'");
            $employeeName = $fn->getRecordByCondition('employee', "employee_id = '{$row['employee_id']}'");
            $appointment_date = $fn->getCPDate($row['appointment_date'], "d-m-Y");

            $rows .= "  
            <tr> 
                <td>{$counter}</td>
                <td>{$patientName['name']}({$patientName['phone']})</td> 
                <td>{$visit_code}</td>
                <td>{$appointment_date}</td> 
               <td>{$employeeName['first_name']}</td>   
            </tr>

            ";

            $counter++;
           
        }
        
        $text = "
        {$rows}
        ";

        return $text;
    }
}