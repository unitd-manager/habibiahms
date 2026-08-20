<?
class CPL_Admin_Widgets_Hms_OverallYearlyAnalysis_View extends CP_Common_Lib_WidgetViewAbstract
{

    /**
     *
     */
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');
        $dbUtil = Zend_Registry::get('dbUtil');
        $year  = $fn->getReqParam('year');
        if ($year == '') {
            $year = date('Y');
        }
        

        $heading = "Overall Yearly Analysis";
        $dates = '';

        $dates_arr = array (
                '2018'
               ,'2019'
               ,'2020'
               ,'2021'
               ,'2022'
               ,'2023'
               );

        foreach($dates_arr as $x => $x_value) {
            $dates .= "<th class='txtCenter'>{$x_value}</th>";
        }

        $text = "
        <h2>
            {$heading}
            <a class='btnRefreshColorPanels1'>
                <span class='refreshIcon'></span>
            </a>
        </h2>
        <div class = 'tableOuter scroll-pane overallMonthlyAnalysis'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th></th>
                        {$dates}
                    </tr>
                </thead>
                <tbody>
                    {$this->getRowsHTML()}
                </tbody>
            </table>
        </div>
        ";
        return $text;
    }

    /**
     *
     */

    function getRowsHTMLBackup() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cp_site_id = $fn->getSessionParam('cp_site_id');
        $rows = '';
        $Name  = '';
        $totalOvelall = '';
        $totalOverallSum = '';
        $totalOverallPreSum = '';

        $yearVal  = $fn->getReqParam('year');
        if ($yearVal == '') {
            $yearVal = date('Y');
        }

        $list_arr = array (
                '01' => 'OP'
               ,'02' => 'IP'
               ,'03' => 'Lab'
               ,'04' => 'Pre Total'
               ,'05' => 'Phar'
               ,'06' => 'Total'
               ,'07' => 'Expense'
               );

        $dates = '';

        $dates_arr = array (
                '2018'
               ,'2019'
               ,'2020'
               ,'2021'
               ,'2022'
               );
        $monthWiseOPTotal   = 0;
        $monthWiseIPTotal   = 0;
        $monthWiseLabTotal  = 0;
        $monthWisePharTotal = 0;
        $monthWiseExpTotal  = 0;
        $case_count_ip      = 0;
        $monthWiseOPTd      = '';
        $monthWiseIPTd      = '';
        $monthWiseLabTd     = '';
        $monthWisePharTd    = '';
        $monthWiseExpTd     = '';

        foreach($dates_arr as $x => $x_value) {
            $yearVal = $x_value;

            //$monthValAppendSql = "AND DATE_FORMAT(pv.check_up_date, '%m') = '{$monthVal}'" ;
            $yearValAppendSql  = "AND DATE_FORMAT(pv.check_up_date, '%Y') = '{$yearVal}'" ;

            /*Pat visit starts here*/
            $appendSql = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSql = "AND pv.site_id = {$cp_site_id}";
            }
            $SQLSub = "
            SELECT COUNT(ev.patient_visit_id) AS patient_count
                  ,SUM(ev.consultation_fees) AS fees_count
            FROM employee_visit ev
            LEFT JOIN employee e ON (e.employee_id = ev.employee_id)
            LEFT JOIN patient_visit pv ON (pv.patient_visit_id = ev.patient_visit_id)
            WHERE e.first_name != ''
              AND pv.status != 'Cancelled'
              {$yearValAppendSql}
              {$appendSql}
            ";
            $resultSub = $db->sql_query($SQLSub);
            $sum_amount = 0;
            $case_count = 0;
            while ($rowSub = $db->sql_fetchrow($resultSub)) {
                $sum_amount += $rowSub['fees_count'];
                $case_count += $rowSub['patient_count'];
            }
            $monthWiseOPTotal = $sum_amount;
            $monthWiseOPTotalAvg = number_format($monthWiseOPTotal/12);
            $monthWiseOPTotal = number_format($monthWiseOPTotal);
            $monthWiseOPTd .= "<td align='right'><b>{$monthWiseOPTotal}<div class='colorGreen'>({$monthWiseOPTotalAvg})</div></b></td>";


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
            while ($rowCat = $db->sql_fetchrow($resultCat)) {
                $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
                $appendSqlSite = '';
                if ($cpCfg['cp.hasMultiUniqueSites']) {
                    $appendSqlSite = "AND pv.site_id = {$cpSiteIdSession}";
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
                  {$yearValAppendSql}
                  {$appendSqlSite}
                ";
                $result = $db->sql_query($SQL);
                $numRows = $db->sql_numrows($result);
                $case_count_split = 0;
                while ($row = $db->sql_fetchrow($result)) {
                    $case_count_split = $row['patient_count'];
                }

                /*$case_count_overview .= "
                <div class='col-md-4 noPadding thinBorder'>{$case_count_split}</div>
                ";*/                
                $case_count_overview .= "
                {$case_count_split} -";                
            }
            $case_count_overview = rtrim($case_count_overview, '-');
            if($case_count == ''){
                $case_count_overview = '';
            }

            /*Pat visit ends here*/

            /*In Pat visit starts here*/
            $appendSqlSite = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSqlSite = "AND inp.site_id = {$cp_site_id}";
            }
            //$monthValAppendSql = "AND DATE_FORMAT(inp.date_admitted, '%m') = '{$monthVal}'" ;
            $yearValAppendSql  = "AND DATE_FORMAT(inp.date_admitted, '%Y') = '{$yearVal}'" ;
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
            {$appendSqlSite}
            {$yearValAppendSql}
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

                $sum_amount_IP += $rowEmpVisit['consultation_fees'] + $rowIP['amount'] + $rowIP['nursing_fees'] + $rowIP['other_fees'] + $rowIP['theatre_charges'];

                $case_count_ip ++;
            }
            //$monthWiseIPTotal += $sum_amount_IP;
            $monthWiseIPTotal = $sum_amount_IP;
            $monthWiseIPTotalAvg = number_format($monthWiseIPTotal/12);
            $monthWiseIPTotal = number_format($monthWiseIPTotal);
            $monthWiseIPTd .= "<td align='right'><b>{$monthWiseIPTotal}<div class='colorGreen'>({$monthWiseIPTotalAvg})</div></b></td>";

           
            
            /*In Pat visit ends here*/

            /*Lab starts here*/
            $appendSql = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSql = "AND m.site_id = {$cp_site_id}";
            }
            //$monthValAppendSql = "AND DATE_FORMAT(m.creation_date, '%m') = '{$monthVal}'" ;
            $yearValAppendSql  = "AND DATE_FORMAT(m.creation_date, '%Y') = '{$yearVal}'" ;
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
              {$appendSql}
              {$yearValAppendSql}
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
            {$appendSql}
              {$yearValAppendSql}
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
            {$appendSql}
              {$yearValAppendSql}
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

            //$monthWiseLabTotal += $totalAllLabtest;
            $monthWiseLabTotal = $totalAllLabtest;
            $monthWiseLabTotalAvg = number_format($monthWiseLabTotal/12);
            $monthWiseLabTotal = number_format($monthWiseLabTotal);
            $monthWiseLabTd .= "<td align='right'><b>{$monthWiseLabTotal}<div class='colorGreen'>({$monthWiseLabTotalAvg})</div></b></td>";

            
            /*Lab ends here*/

            /*Phar starts here*/
            $appendSql = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSql = "p.site_id = {$cp_site_id}";
            }
            //$monthValAppendSql = "AND DATE_FORMAT(p.date, '%m') = '{$monthVal}'" ;
            $yearValAppendSql  = "AND DATE_FORMAT(p.date, '%Y') = '{$yearVal}'" ;
            $SQLSub = "
            SELECT p.*
            FROM pharma_daily_sales p
            WHERE {$appendSql}
            {$yearValAppendSql}  
            ";
            $resultSub = $db->sql_query($SQLSub);
            $sum_amount_pos = 0;
            while ($rowSub = $db->sql_fetchrow($resultSub)) {
                $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
                $appendSqlCollection = '';
                if ($cpCfg['cp.hasMultiUniqueSites']) {
                    $appendSqlCollection = "AND site_id = {$cpSiteIdSession}";
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
                    $appendSqlInvoice = "AND inv.site_id = {$cpSiteIdSession}";
                }

                $SQLSalesReturn = "
                SELECT 
                    SUM(srh.qty_return * srh.price) as sales_return_amount 
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
                $salesReturn       =  $recSalesReturn['sales_return_amount'];

                if($rowSub['date'] < '2019-04-05'){
                    $totalCollection = $rowSub['sales_amount'];
                } else {
                    $totalCollection = $totalCollection;            
                }
                
                $totalAmountCollection = $totalCollection + $rowSub['excess_amount'] - $salesReturn;

                $sum_amount_pos += $totalAmountCollection;
            }
            //$monthWisePharTotal += $sum_amount_pos;
            $monthWisePharTotal = $sum_amount_pos;
            $monthWisePharTotalAvg = number_format($monthWisePharTotal/12);
            $monthWisePharTotal = number_format($monthWisePharTotal);
            $monthWisePharTd .= "<td align='right'><b>{$monthWisePharTotal}<div class='colorGreen'>({$monthWisePharTotalAvg})</div></b></td>";

            
            /*Phar ends here*/

            /*Expense starts here*/
            $appendSqlSite = '';
            $AppendSource  = '';
            $source        = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSqlSite = "AND e.site_id = {$cp_site_id}";

                if($cp_site_id == 1){
                    $source = 'Hab Pharm Income';
                    $AppendSource = "AND e.source = '{$source}'";
                }
                if($cp_site_id == 2){
                    $source = 'Cres Pharm Income';
                    $AppendSource = "AND e.source = '{$source}'";
                }
                if($cp_site_id == 3){
                    $source = '';
                    $AppendSource = "";
                }
            }
            //$monthValAppendSql = "AND DATE_FORMAT(e.date, '%m') = '{$monthVal}'" ;
            $yearValAppendSql  = "AND DATE_FORMAT(e.date, '%Y') = '{$yearVal}'" ;

            $sqlexp = "
            SELECT SUM(e.amount) AS amount
                  ,e.group
                  ,e.source
                  ,es.title AS sub_title
            FROM expense e
            LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
            WHERE e.amount > 0
            {$appendSqlSite}
            {$yearValAppendSql}
            ";
            $resultexp = $db->sql_query($sqlexp);
            $amount = 0;
            while ($rowexp = $db->sql_fetchrow($resultexp)) {
                $amount += $rowexp['amount'];
            }
            //$monthWiseExpTotal += $amount;
            $monthWiseExpTotal = $amount;
            $monthWiseExpTotalAvg = number_format($monthWiseExpTotal/12);
            $monthWiseExpTotal = number_format($monthWiseExpTotal);
            $monthWiseExpTd .= "<td align='right'><b>{$monthWiseExpTotal}<div class='colorGreen'>({$monthWiseExpTotalAvg})</div></b></td>";

           
            /*Expense ends here*/

            $totalOvelallSum = number_format($sum_amount_pos + $totalAllLabtest + $sum_amount_IP + $sum_amount - $amount);
            if($totalOvelallSum == 0){
                $totalOvelallSum = '';
            }

            $totalSum = number_format($sum_amount_pos + $totalAllLabtest + $sum_amount_IP + $sum_amount);
            if($totalSum == 0){
                $totalSum = '';
            }
            $totalPreSum = number_format($totalAllLabtest + $sum_amount_IP + $sum_amount);
            if($totalPreSum == 0){
                $totalPreSum = '';
            }

            $totalOvelall .= "<td align='right'><b>{$totalOvelallSum}</b></td>";
            $totalOverallSum .= "<td align='right'><b>{$totalSum}</b></td>";
            $totalOverallPreSum .= "<td align='right'><b>{$totalPreSum}</b></td>";
        }


        foreach($list_arr as $l => $l_value) {
            if($l_value == 'OP'){
                $Name .= "
                <tr>
                  <td width='5%'><div><b>{$l_value}</b></div>
                  <div class=''>DR - ST</div>
                  </td>
                  {$monthWiseOPTd}
                </tr>";
            }
            if($l_value == 'IP'){
                $Name .= "
                <tr>
                  <td><b>{$l_value}</b></td>
                  {$monthWiseIPTd}
                </tr>";
            }
            if($l_value == 'Lab'){
                $Name .= "
                <tr>
                  <td><b>{$l_value}</b></td>
                  {$monthWiseLabTd}
                </tr>";
            }
            if($l_value == 'Pre Total'){
                $Name .= "
                <tr class='' style='background:lightblue;'>
                  <td><b>Total</b></td>
                  {$totalOverallPreSum}
                </tr>";
            }
            if($l_value == 'Phar'){
                $Name .= "
                <tr>
                  <td><b>{$l_value}</b></td>
                  {$monthWisePharTd}
                </tr>";
            }
            if($l_value == 'Total'){
                $Name .= "
                <tr class='lastRowBgColor'>
                  <td><b>{$l_value}</b></td>
                  {$totalOverallSum}
                </tr>";
            }
            /*if($l_value == 'Expense'){
                $Name .= "
                <tr>
                  <td><b>{$l_value}</b></td>
                  {$monthWiseExpTd}
                </tr>
                <tr>
                    <td colspan='2'>
                        <div class='expenseRecDetails'>
                            <table class='thinlist tableInvRecDetail' width='90%'>
                            </table>
                        </div>
                    </td>
                </tr>
                ";
            }*/
        }
        /*$Name .= "
        <tr>
          <td><b>Profit</b></td>
          {$totalOvelall}
        </tr>";*/

        $rows .= "
                {$Name}
                ";
        
        $text = "
        {$rows}
        ";

        return $text;
    }
    
    /**
     *
     */

    function getRowsHTML() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cp_site_id = $fn->getSessionParam('cp_site_id');
        $rows = '';
        $Name  = '';
        $totalOvelall = '';
        $totalOverallSum = '';
        $totalOverallPreSum = '';

        $yearVal  = $fn->getReqParam('year');
        if ($yearVal == '') {
            $yearVal = date('Y');
        }

         if ($cp_site_id == 2) {

        $list_arr = array (
                '01' => 'Total'
               ,'02' => 'Pre Total'
               ,'03' => 'OP'
               ,'05' => 'Lab'
               ,'06' => 'Phar'
               ,'07' => 'Expense'
               );
    }else if ($cp_site_id == 3) {

        $list_arr = array (
                '01' => 'Total'
               ,'02' => 'Pre Total'
               ,'03' => 'OP'
               ,'05' => 'Lab'
               ,'07' => 'Expense'
               );
    } else{

        $list_arr = array (
                '01' => 'Total'
               ,'02' => 'Pre Total'
               ,'03' => 'OP'
               ,'04' => 'IP'
               ,'05' => 'Lab'
               ,'06' => 'Phar'
               ,'07' => 'Expense'
               );
    }


        $dates = '';

        $dates_arr = array (
                '2018'
               ,'2019'
               ,'2020'
               ,'2021'
               ,'2022'
               ,'2023'
               );
        $monthWiseOPTotal   = 0;
        $monthWiseIPTotal   = 0;
        $monthWiseLabTotal  = 0;
        $monthWisePharTotal = 0;
        $monthWiseExpTotal  = 0;
        $case_count_ip      = 0;
        $monthWiseOPTd      = '';
        $monthWiseIPTd      = '';
        $monthWiseLabTd     = '';
        $monthWisePharTd    = '';
        $monthWiseExpTd     = '';

        foreach($dates_arr as $x => $x_value) {
            $yearVal = $x_value;

            $SQLOS = "
            SELECT *
            FROM overall_stats
            WHERE year = '{$yearVal}'
            AND site_id = {$cp_site_id}
            ";
            $resultOS = $db->sql_query($SQLOS);
            $sum_op_cs = 0;
            $sum_ip_cs = 0;
            $sum_amount = 0;
            $sum_amount_IP = 0;
            $sum_amount_IPT = 0;
            $totalAllLabtest = 0;
            $sum_amount_pos = 0;
            $amount = 0;
            while ($rowOS = $db->sql_fetchrow($resultOS)) {
                $sum_op_cs += $rowOS['op_cs'];
                $sum_ip_cs += $rowOS['ip_cs'];
                $sum_amount += $rowOS['op_amt'];
                $sum_amount_IP += $rowOS['ip_amt'];
                $sum_amount_IPT += $rowOS['theatre_amt'];
                $totalAllLabtest += $rowOS['lab_amt'];
                $sum_amount_pos += $rowOS['pharm_amt'];
                $amount += $rowOS['exp_total'];
            }
            $monthWiseOPTotal = $sum_amount;
            $monthWiseOPTotalAvg = number_format($monthWiseOPTotal/12);
            $monthWiseOPTotal = number_format($monthWiseOPTotal);
            $sum_op_cs_avg = number_format($sum_op_cs/12);
            $monthWiseOPTd .= "<td align='right'><b>{$monthWiseOPTotal}<div class='colorGreen'><span style='color:red;'>Avg/Month</span> - ({$monthWiseOPTotalAvg})</div>Pat Count - {$sum_op_cs}<div class='colorGreen'><span style='color:red;'>Avg Pat/Month</span> - ({$sum_op_cs_avg})</div></b></td>";
            /*Pat visit ends here*/

            /*In Pat visit starts here*/
            $monthWiseIPTotal = $sum_amount_IP + $sum_amount_IPT;
            $monthWiseIPTotalAvg = number_format($monthWiseIPTotal/12);
            $monthWiseIPTotal = number_format($monthWiseIPTotal);
            $sum_ip_cs_avg = number_format($sum_ip_cs/12);
            $monthWiseIPTd .= "<td align='right'><b>{$monthWiseIPTotal}<div class='colorGreen'><span style='color:red;'>Avg/Month</span> - ({$monthWiseIPTotalAvg})</div>Pat Count - {$sum_ip_cs}<div class='colorGreen'><span style='color:red;'>Avg Pat/Month</span> - ({$sum_ip_cs_avg})</div></b></td>";                    
            /*In Pat visit ends here*/

            /*Lab starts here*/
            $monthWiseLabTotal = $totalAllLabtest;
            $monthWiseLabTotalAvg = number_format($monthWiseLabTotal/12);
            $monthWiseLabTotal = number_format($monthWiseLabTotal);
            $monthWiseLabTd .= "<td align='right'><b>{$monthWiseLabTotal}<div class='colorGreen'><span style='color:red;'>Avg/Month</span> - ({$monthWiseLabTotalAvg})</div></b></td>";            
            /*Lab ends here*/

            /*Phar starts here*/                
            $monthWisePharTotal = $sum_amount_pos;
            $monthWisePharTotalAvg = number_format($monthWisePharTotal/12);
            $monthWisePharTotal = number_format($monthWisePharTotal);
            $monthWisePharTd .= "<td align='right'><b>{$monthWisePharTotal}<div class='colorGreen'><span style='color:red;'>Avg/Month</span> - ({$monthWisePharTotalAvg})</div></b></td>";            
            /*Phar ends here*/

            /*Expense starts here*/
            $monthWiseExpTotal = $amount;
            $monthWiseExpTotalAvg = number_format($monthWiseExpTotal/12);
            $monthWiseExpTotal = number_format($monthWiseExpTotal);
            $monthWiseExpTd .= "<td align='right'><b>{$monthWiseExpTotal}<div class='colorGreen'><span style='color:red;'>Avg/Month</span> - ({$monthWiseExpTotalAvg})</div></b></td>";           
            /*Expense ends here*/

            $totalOvelallSum = number_format($sum_amount_pos + $totalAllLabtest + $sum_amount_IP + $sum_amount_IPT + $sum_amount - $amount);
            if($totalOvelallSum == 0){
                $totalOvelallSum = '';
            }

            setlocale(LC_MONETARY, 'en_IN');

            $totalSum1 = money_format('%!i', $sum_amount_pos + $totalAllLabtest + $sum_amount_IP + $sum_amount_IPT + $sum_amount);
            $totalSumWithDecimal = explode('.', $totalSum1);
            $totalSum = $totalSumWithDecimal[0];

            if($totalSum == 0){
                $totalSum = '';
            }

            $totalPreSum1 = money_format('%!i', $totalAllLabtest + $sum_amount_IP + $sum_amount_IPT + $sum_amount);
            $totalPreSumWithDecimal = explode('.', $totalPreSum1);
            $totalPreSum = $totalPreSumWithDecimal[0];

            if($totalPreSum == 0){
                $totalPreSum = '';
            }

            $totalOvelall .= "<td align='right'><b>{$totalOvelallSum}</b></td>";
            $totalOverallSum .= "<td align='right'><b>{$totalSum}</b></td>";
            $totalOverallPreSum .= "<td align='right'><b>{$totalPreSum}</b></td>";
        }

        foreach($list_arr as $l => $l_value) {
            if($l_value == 'OP'){
                $Name .= "
                <tr>
                  <td width='7%'><div><b>{$l_value}</b></div>
                  <div class=''>DR - ST</div>
                  </td>
                  {$monthWiseOPTd}
                </tr>";
            }
            if($l_value == 'IP'){
                $Name .= "
                <tr>
                  <td><b>{$l_value}</b></td>
                  {$monthWiseIPTd}
                </tr>";
            }
            if($l_value == 'Lab'){
                $Name .= "
                <tr>
                  <td><b>{$l_value}</b></td>
                  {$monthWiseLabTd}
                </tr>";
            }
            if($l_value == 'Pre Total'){
                $Name .= "
                <tr class='' style='background:lightblue;'>
                  <td><b>Total</b></td>
                  {$totalOverallPreSum}
                </tr>";
            }
            if($l_value == 'Phar'){
                $Name .= "
                <tr>
                  <td><b>{$l_value}</b></td>
                  {$monthWisePharTd}
                </tr>";
            }
            if($l_value == 'Total'){
                $Name .= "
                <tr class='lastRowBgColor'>
                  <td><b>Net Total</b></td>
                  {$totalOverallSum}
                </tr>";
            }
            /*if($l_value == 'Expense'){
                $Name .= "
                <tr>
                  <td><b>{$l_value}</b></td>
                  {$monthWiseExpTd}
                </tr>
                <tr>
                    <td colspan='2'>
                        <div class='expenseRecDetails'>
                            <table class='thinlist tableInvRecDetail' width='90%'>
                            </table>
                        </div>
                    </td>
                </tr>
                ";
            }*/
        }
        /*$Name .= "
        <tr>
          <td><b>Profit</b></td>
          {$totalOvelall}
        </tr>";*/

        $rows .= "
                {$Name}
                ";
        
        $text = "
        {$rows}
        ";

        return $text;
    }

    
}