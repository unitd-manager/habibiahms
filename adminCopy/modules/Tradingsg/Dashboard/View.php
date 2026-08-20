<?
class CPL_Admin_Modules_Tradingsg_Dashboard_View extends CP_Common_Lib_ModuleViewAbstract
{
    function getList($dataArray) {
        $cpCfg = Zend_Registry::get('cpCfg');
        $arr = $cpCfg['cp.dashboardArr'];

        $hook = getCPModuleHook('tradingsg_dashboard', 'list', $dataArray, $this);
        if($hook['status']){
            return $hook['html'];
        }

        $rows = '';
        foreach($arr as $widgetArr){
            $widget   = $widgetArr['name'];
            $subClass = $widgetArr['subClass'];
            $cssClass = $widgetArr['cssClass'];

            $clsInst = getCPWidgetObj($widget);

            $rows .= "
            <div class='{$cssClass}'>
                <div class='{$subClass} widget' id='wd_{$widget}'>
                    {$clsInst->getWidget()}
                </div>
            </div>
            ";
        }

        $text = "
        <div id='dashboard' class='subcolumns'>
            {$rows}
        </div>
        ";

        return $text;
    }

    function getUpdateOverallStats(){
        $db     = Zend_Registry::get('db');
        $cpCfg  = Zend_Registry::get('cpCfg');
        $dbUtil = Zend_Registry::get('dbUtil');
        $fn     = Zend_Registry::get('fn');

        set_time_limit(50000);
        
        //admin/index.php?_spAction=updateOverallStats&showHTML=0&module=tradingsg_dashboard&monthVal=&yearVal=
        
        $monthVal = $fn->getReqParam('monthVal');
        $yearVal  = $fn->getReqParam('yearVal');

        if($monthVal == ''){
            $monthVal = date("m", strtotime("first day of previous month"));
        }

        if($yearVal == ''){
            $yearVal = date("Y", strtotime("first day of previous month"));
        }

        $current_date = date('Y-m-d');

        $SQLsitedetail="
        SELECT site_id
               ,title
        FROM site
        ";
        $resultsitedetail = $db->sql_query($SQLsitedetail);
        while ($rowSite    = $db->sql_fetchrow($resultsitedetail)) {
            $cp_site_id = $rowSite['site_id'];

            $SQLSub = "
            SELECT COUNT(ev.patient_visit_id) AS patient_count
                  ,SUM(ev.consultation_fees) AS fees_count
            FROM employee_visit ev
            LEFT JOIN employee e ON (e.employee_id = ev.employee_id)
            LEFT JOIN patient_visit pv ON (pv.patient_visit_id = ev.patient_visit_id)
            WHERE e.first_name != ''
              AND pv.status != 'Cancelled'
              AND DATE_FORMAT(pv.check_up_date, '%m') = '{$monthVal}'
              AND DATE_FORMAT(pv.check_up_date, '%Y') = '{$yearVal}'
              AND pv.site_id = {$cp_site_id}
            ";
            $resultSub = $db->sql_query($SQLSub);
            $rowSub = $db->sql_fetchrow($resultSub);
            $sum_amount = $rowSub['fees_count'];
            $case_count = $rowSub['patient_count'];

            $sqlCategory = "
            SELECT value
            FROM valuelist
            WHERE key_text = 'employeeCategory'
              AND value != 'Anaesthetist'
              AND value != 'Surgeon'
              AND value != 'Theater Assistant'
              AND value != 'Lab Technician'
              AND value != 'Student'
              AND value != 'Consultant'
              AND value != 'Duty Doctor'
            ";
            $resultCat   = $db->sql_query($sqlCategory);
            $overallCaseCount  = 0;
            $case_count_overview = '';
            $case_count_dr = 0;
            $case_count_staff = 0;
            while ($rowCat = $db->sql_fetchrow($resultCat)) {
                $appendSqlSite = '';
                if ($cpCfg['cp.hasMultiUniqueSites']) {
                    $appendSqlSite = "AND pv.site_id = {$cp_site_id}";
                }

                $SQL = "
                SELECT ev.*
                      ,COUNT(ev.patient_visit_id) AS patient_count
                FROM employee_visit ev
                LEFT JOIN employee e ON (e.employee_id = ev.employee_id)
                LEFT JOIN patient_visit pv ON (pv.patient_visit_id = ev.patient_visit_id)
                WHERE e.first_name != ''
                  AND pv.status != 'Cancelled'
                  AND e.category = '{$rowCat['value']}'
                  AND DATE_FORMAT(pv.check_up_date, '%m') = '{$monthVal}'
                  AND DATE_FORMAT(pv.check_up_date, '%Y') = '{$yearVal}'
                  {$appendSqlSite}
                ";
                $result = $db->sql_query($SQL);
                $numRows = $db->sql_numrows($result);
                $case_count_split = 0;
                while ($row = $db->sql_fetchrow($result)) {
                    if($rowCat['value'] == 'Doctor'){
                        $case_count_dr += $row['patient_count'];
                    } else {
                        $case_count_staff += $row['patient_count'];
                    }
                    $case_count_split++;
                }
            }

            $SQLIP = "
            SELECT inp.date_admitted 
                  ,inp.amount 
                  ,inp.nursing_fees 
                  ,inp.other_fees 
                  ,inp.in_patient_id
                  ,inp.surgeon_fees
                  ,inp.theatre_charges
                  ,inp.anesthetic_fees
                  ,inp.theater_assistant_fees
            FROM in_patient inp
            WHERE inp.in_patient_id > 0
              AND inp.status != 'Cancelled'
              AND inp.site_id = {$cp_site_id}
              AND DATE_FORMAT(inp.date_admitted, '%m') = '{$monthVal}'
              AND DATE_FORMAT(inp.date_admitted, '%Y') = '{$yearVal}'
             ";
            $resultIP = $db->sql_query($SQLIP);
            $sum_amount_IP = 0;
            $case_count_ip = 0;
            while ($rowIP    = $db->sql_fetchrow($resultIP)) {
                $SQLEmpVisit = "
                SELECT SUM(consultation_fees) AS consultation_fees
                       ,employee_in_patient_id
                FROM employee_in_patient
                WHERE in_patient_id = {$rowIP['in_patient_id']}
                ORDER BY employee_in_patient_id ASC
                ";
                $resultEmpVisit = $db->sql_query($SQLEmpVisit);
                $rowEmpVisit    = $db->sql_fetchrow($resultEmpVisit);

                $sum_amount_IP += $rowEmpVisit['consultation_fees'] + $rowIP['amount'] + $rowIP['nursing_fees'] + $rowIP['other_fees'];

                $case_count_ip ++;
            }

            $SQLIPT = "
            SELECT inp.date_admitted 
                  ,inp.amount 
                  ,inp.nursing_fees 
                  ,inp.other_fees 
                  ,inp.in_patient_id
                  ,inp.surgeon_fees
                  ,inp.theatre_charges
                  ,inp.anesthetic_fees
                  ,inp.theater_assistant_fees
            FROM in_patient inp
            WHERE inp.in_patient_id > 0
              AND inp.status != 'Cancelled'
              AND inp.site_id = {$cp_site_id}
              AND DATE_FORMAT(inp.date_admitted, '%m') = '{$monthVal}'
              AND DATE_FORMAT(inp.date_admitted, '%Y') = '{$yearVal}'
             ";
            $resultIPT = $db->sql_query($SQLIP);
            $sum_amount_IPT = 0;
            $case_count_ipt = 0;
            while ($rowIPT    = $db->sql_fetchrow($resultIPT)) {
                $sum_amount_IPT += $rowIPT['theatre_charges'];

                if($rowIPT['theatre_charges'] > 0){
                  $case_count_ipt ++;
                }
            }

            $totaltestamount = 0;
            $totaltestamount1 = 0;
            $totaltestamount2 = 0;

            $SQLLabTestPV = "
            SELECT m.title
                   ,SUM(m.fees) AS fees
                   ,SUM(m.lab_supplier_fees) AS lab_supplier_fees
            FROM medical_test_visit m
            LEFT JOIN (medical_test mt) ON (mt.medical_test_id = m.medical_test_id)
            LEFT JOIN (patient_visit pv) ON (pv.patient_visit_id = m.patient_visit_id)
            WHERE m.title != ''
              AND pv.status != 'Cancelled'
              AND mt.category != 'Vaccination'
              AND m.site_id = {$cp_site_id}
              AND DATE_FORMAT(m.creation_date, '%m') = '{$monthVal}'
              AND DATE_FORMAT(m.creation_date, '%Y') = '{$yearVal}'
            GROUP BY m.title
            ";
            $resultLabTestPV = $db->sql_query($SQLLabTestPV);
            while ($rowLabTestPV    = $db->sql_fetchrow($resultLabTestPV)) {
                if($rowLabTestPV['fees'] == ""){
                    $rowLabTestPV['fees'] = 0;
                }
                if($rowLabTestPV['lab_supplier_fees'] == ""){
                    $rowLabTestPV['lab_supplier_fees'] = 0;
                }
                $fees = $rowLabTestPV['fees'] - $rowLabTestPV['lab_supplier_fees'];

                $totaltestamount += $fees;
            }

            $SQLLabTestLab = "
            SELECT m.title
                   ,SUM(m.fees) AS fees
                   ,SUM(m.lab_supplier_fees) AS lab_supplier_fees
            FROM medical_test_lab m
            LEFT JOIN (lab_test lt) ON (lt.lab_test_id = m.lab_test_id)
            WHERE m.title != ''
              AND lt.status != 'Cancelled'
            AND m.site_id = {$cp_site_id}
              AND DATE_FORMAT(m.creation_date, '%m') = '{$monthVal}'
              AND DATE_FORMAT(m.creation_date, '%Y') = '{$yearVal}'
            GROUP BY m.title
            ";
            $resultLabTestLab = $db->sql_query($SQLLabTestLab);
            while ($rowLabTestLab = $db->sql_fetchrow($resultLabTestLab)) {
                if($rowLabTestLab['fees'] == ""){
                    $rowLabTestLab['fees'] = 0;
                }
                if($rowLabTestLab['lab_supplier_fees'] == ""){
                    $rowLabTestLab['lab_supplier_fees'] = 0;
                }
                $fees = $rowLabTestLab['fees'] - $rowLabTestLab['lab_supplier_fees'];

                $totaltestamount1 += $fees;
            }

            $SQLLabTestIP = "
            SELECT m.title
                   ,SUM(m.fees) AS fees
                   ,SUM(m.lab_supplier_fees) AS lab_supplier_fees
            FROM medical_test_in_patient m
            LEFT JOIN (in_patient ip) ON (ip.in_patient_id = m.in_patient_id)
            WHERE m.title != ''
              AND ip.status != 'Cancelled'
            AND m.site_id = {$cp_site_id}
              AND DATE_FORMAT(m.creation_date, '%m') = '{$monthVal}'
              AND DATE_FORMAT(m.creation_date, '%Y') = '{$yearVal}'
            GROUP BY m.title
            ";
            $resultLabTestIP = $db->sql_query($SQLLabTestIP);
            while ($rowLabTestIP = $db->sql_fetchrow($resultLabTestIP)) {
                if($rowLabTestIP['fees'] == ""){
                    $rowLabTestIP['fees'] = 0;
                }
                if($rowLabTestIP['lab_supplier_fees'] == ""){
                    $rowLabTestIP['lab_supplier_fees'] = 0;
                }
                $fees = $rowLabTestIP['fees'] - $rowLabTestIP['lab_supplier_fees'];

                $totaltestamount2 += $fees;
            }
            
            $totalAllLabtest  = $totaltestamount + $totaltestamount1 + $totaltestamount2; 

            $SQLSub = "
            SELECT p.*
            FROM pharma_daily_sales p
            WHERE p.site_id = {$cp_site_id}
            AND DATE_FORMAT(p.date, '%m') = '{$monthVal}'  
            AND DATE_FORMAT(p.date, '%Y') = '{$yearVal}'
            ";
            $resultSub = $db->sql_query($SQLSub);
            $sum_amount_pos = 0;
            while ($rowSub = $db->sql_fetchrow($resultSub)) {
                $appendSqlCollection = '';
                if ($cpCfg['cp.hasMultiUniqueSites']) {
                    $appendSqlCollection = "AND site_id = {$cp_site_id}";
                }

                $SQLCollection = "
                SELECT SUM(invoice_amount) AS total_amount
                FROM `invoice`
                WHERE status != 'Cancelled'
                AND invoice_type = 'POS'
                AND invoice_date = '{$rowSub['date']}'
                {$appendSqlCollection}
                ";
                $resultCollection = $db->sql_query($SQLCollection);
                $recCollection    = $db->sql_fetchrow($resultCollection);

                $totalCollection = $recCollection['total_amount'];

                $appendSqlInvoice = '';
                if ($cpCfg['cp.hasMultiUniqueSites']) {
                    $appendSqlInvoice = "AND inv.site_id = {$cp_site_id}";
                }

                $SQLSalesReturn = "
                SELECT SUM(srh.qty_return * srh.price) as sales_return_amount 
                FROM sales_return_history srh
                LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                LEFT JOIN (`order` o) ON (o.order_id = srh.order_id)
                WHERE o.order_status != 'Cancelled'
                AND o.order_type = 'POS'
                AND srh.date = '{$rowSub['date']}'
                {$appendSqlInvoice}
                ";
                $resultSalesReturn = $db->sql_query($SQLSalesReturn);
                $recSalesReturn    = $db->sql_fetchrow($resultSalesReturn);
                $salesReturn       = $recSalesReturn['sales_return_amount'];

                if($rowSub['date'] < '2019-04-05'){
                    $totalCollection = $rowSub['sales_amount'];
                } else {
                    $totalCollection = $totalCollection;            
                }
                
                $totalAmountCollection = $totalCollection + $rowSub['excess_amount'] - $salesReturn;
                $sum_amount_pos += $totalAmountCollection;
            }

            $sqlexp = "
            SELECT SUM(e.amount) AS amount
                  ,e.group
                  ,e.source
                  ,es.title AS sub_title
            FROM expense e
            LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
            WHERE e.amount > 0
            AND e.site_id = {$cp_site_id}
            AND DATE_FORMAT(e.date, '%m') = '{$monthVal}'
            AND DATE_FORMAT(e.date, '%Y') = '{$yearVal}'
            ";
            $resultexp = $db->sql_query($sqlexp);
            $amount = 0;
            while ($rowexp = $db->sql_fetchrow($resultexp)) {
                $amount += $rowexp['amount'];
            }
            $totalSum = $sum_amount_pos + $totalAllLabtest + $sum_amount_IP + $sum_amount_IPT + $sum_amount;
            //$totalOvelallSum = $sum_amount_pos + $totalAllLabtest + $sum_amount_IP + $sum_amount_IPT + $sum_amount;

            $fa = array();
            $fa['op_cs']     = $case_count;
            $fa['op_amt']    = $sum_amount;
            $fa['ip_cs']     = $case_count_ip;
            $fa['ip_amt']    = $sum_amount_IP;
            $fa['dr_case']   = $case_count_dr;
            $fa['staff_cs']  = $case_count_staff;
            $fa['theatre_amt'] = $sum_amount_IPT;
            $fa['theatre_cs'] = $case_count_ipt;
            $fa['lab_amt'] = $totalAllLabtest;
            $fa['pharm_amt'] = $sum_amount_pos;
            $fa['exp_total'] = $amount;
            $fa['total'] = $totalSum;
            //$fa['profit'] = $totalOvelallSum;
            $fa['month']     = $monthVal;
            $fa['year']      = $yearVal;
            $fa['site_id']   = $cp_site_id;
            $fa['creation_date'] = $current_date;

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'overall_stats');
            $result = $db->sql_query($SQL);
            $overall_stats_id = $db->sql_nextid();
        }
    }
}
