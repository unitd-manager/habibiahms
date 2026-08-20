<?
ini_set('max_execution_time', 300); //300 seconds = 5 minutes
class CPL_Admin_Modules_Common_Dashboard_View extends CP_Admin_Modules_Common_Dashboard_View
{
    function getList($dataArray) {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $arr = $cpCfg['cp.dashboardArr'];
        $cpUtil = Zend_Registry::get('cpUtil');
        $location_type  = $fn->getReqParam('location_type');

        $hook = getCPModuleHook('common_dashboard', 'list', $dataArray, $this);
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

        $pharSalesYesHab = $this->getPharmacyDailySales('Yesterday', 1);
        $pharSalesTodayHab = $this->getPharmacyDailySales('Today', 1);
        $pharSalesYesCre = $this->getPharmacyDailySales('Yesterday', 2);
        $pharSalesTodayCre = $this->getPharmacyDailySales('Today', 2);
        if($location_type == 'Show All'){
            $totalPharSalesYes = $pharSalesYesHab + $pharSalesYesCre;
            $totalPharSalesToday = $pharSalesTodayHab + $pharSalesTodayCre;
        } else {
            $totalPharSalesYes = $pharSalesYesHab;
            $totalPharSalesToday = $pharSalesTodayHab;            
        }

        if(is_numeric ($pharSalesYesHab) && $pharSalesYesHab > 0){
            $pharSalesYesHab = number_format($pharSalesYesHab, 0);            
        }
        if(is_numeric ($pharSalesYesHab) && $pharSalesTodayHab > 0){
            $pharSalesTodayHab = number_format($pharSalesTodayHab, 0);            
        }
        if(is_numeric ($pharSalesYesHab) && $pharSalesYesCre > 0){
            $pharSalesYesCre = number_format($pharSalesYesCre, 0);            
        }
        if(is_numeric ($pharSalesYesHab) && $pharSalesTodayCre > 0){
            $pharSalesTodayCre = number_format($pharSalesTodayCre, 0);            
        }
        if(is_numeric ($pharSalesYesHab) && $totalPharSalesYes > 0){
            $totalPharSalesYes = number_format($totalPharSalesYes, 0);            
        }
        if(is_numeric ($pharSalesYesHab) && $totalPharSalesToday > 0){
            $totalPharSalesToday = number_format($totalPharSalesToday, 0);            
        }

        $start_date = date('Y-m-d', strtotime('-90 days'));
        $end_date   = date("Y-m-d", strtotime("yesterday"));
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $SQLProduct = "
        SELECT i.product_id
              ,i.actual_stock{$cpSiteIdSession} AS stock
        FROM inventory i
        LEFT JOIN (product p) ON (p.product_id = i.product_id)
        LEFT JOIN (medicine_site ms) ON (i.product_id = ms.product_id)
        WHERE ms.site_id = {$cpSiteIdSession}
          AND p.published = 1
          HAVING stock <= (((SELECT SUM(it.qty) AS qty
                                            FROM invoice inv
                                            LEFT JOIN (invoice_item it) ON (it.invoice_id = inv.invoice_id)
                                            WHERE inv.invoice_date >= '{$start_date}' AND inv.invoice_date <= '{$end_date}'
                                              AND inv.status != 'Cancelled'
                                              AND inv.site_id = 1
                                              AND it.qty != 0
                                              AND it.record_id = i.product_id
                                            GROUP BY it.record_id)/3)/2)
        ";
        $resultProduct = $db->sql_query($SQLProduct);
        $numRows = $db->sql_numrows($resultProduct);

        $countMol = $numRows;

        $siteId         = $fn->getSessionParam('cp_site_id');
        $SQLInv = "
        SELECT i.*
              ,p.product_id AS productId
              ,p.title AS product_name
              ,i.actual_stock{$siteId} AS stock
        FROM inventory i
        LEFT JOIN (product p) ON (p.product_id = i.product_id)
        LEFT JOIN (medicine_site ms) ON (i.product_id = ms.product_id AND ms.site_id = {$siteId})
        WHERE ms.site_id = {$siteId}
          AND p.published = 1
        ";
        $resultInv = $db->sql_query($SQLInv);
        $total_mrp = 0;
        $total_rate = 0;
        while($rowInv = $db->sql_fetchrow($resultInv)){
            list($intStockOverall, $decStockOverall) = explode('.', $rowInv['actual_stock'.$siteId]);
            $stock = $intStockOverall;
            $stock = number_format($stock);

            $SQLPP = "
            SELECT pack_size, selling_price, cost_price
            FROM po_product
            WHERE product_id = {$rowInv['product_id']}
            ORDER BY po_product_id DESC
            ";
            $resultPP = $db->sql_query($SQLPP);
            $rowPP = $db->sql_fetchrow($resultPP);
            if(is_numeric($rowPP['pack_size'])){
                $mrp = $rowPP['selling_price'] / $rowPP['pack_size'];
                $rate = $rowPP['cost_price'] / $rowPP['pack_size'];
            } else {
                $mrp = $rowPP['selling_price'];
                $rate = $rowPP['cost_price'];
            }

            $mrp_amount = $mrp * $stock;
            $rate_amount = $rate * $stock;
            $total_mrp += $mrp_amount;
            $total_rate += $rate_amount;
        }
        $total_mrp = number_format($total_mrp, 2);
        $total_rate = number_format($total_rate, 2);

        $siteTypeArr = array (
             'Show All'
             ,'Habibia'
        );

        $PharDailySale = '';

        if($location_type != 'Habibia'){
            $PharDailySale = "
              <table class='list'>
             <tr>
                <td><u>Phar Daily Sales</u></td>
                <td><u>Yesterday</u></td>
                <td><u>Today</u></td>
            </tr>
            <tr>
                <td>HABIBIA</td>
                <td>{$pharSalesYesHab}</td>
                <td>{$pharSalesTodayHab}</td>
            </tr>
            <tr>
                <td>CRESCENT</td>
                <td>{$pharSalesYesCre}</td>
                <td>{$pharSalesTodayCre}</td>
            </tr>
             <tr>
                <td style='line-height:30px;border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;'>Total</td>
                <td style='line-height:30px;border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;'>{$totalPharSalesYes}</td>
                <td style='line-height:30px;border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;'>{$totalPharSalesToday}</td>
            </tr>
                </table>

                            <table class='list'>
                                <tr>
                                    <td>Total Rate : {$total_rate}</td>
                                    <td>Total MRP : {$total_mrp}</td>
                                    <td>MOL Medicines : {$countMol}</td>
                                </tr>
                            </table>
            ";
        }

        $text = "
        <select name='location_type' class='locationType'>
            {$cpUtil->getDropDown1($siteTypeArr, $location_type)}
        </select>
        <div id='dashboard' class='subcolumns'>
            <div class='dashboardSummary floatbox'>
                <!--<div class='c33l txtCenter revenueSummary'>
                    <div>TODAY REVENUE</div>
                    {$this->getSummaryDisplay('Today')}
                    <hr>
                    <div class='floatbox'>
                        <div class='float_left'>
                            <div>Current Month</div>
                            {$this->getSummaryDisplay('Yesterday')}
                        </div>
                        <div class='float_right'>
                            <div>Last Month</div>
                            {$this->getSummaryDisplay('Last Week')}
                        </div>
                    </div>
                </div>-->
                <!--<div class='c33l txtCenter patientVisitSummary'>
                    <div>TODAY PATIENT VISIT BY APPOINTMENT</div>
                    {$this->getPatientVisitDisplay('Today', 'By Appointment')}
                    <hr>
                    <div class='floatbox'>
                        <div class='float_left'>
                            <div>Yesterday</div>
                            {$this->getPatientVisitDisplay('Yesterday', 'By Appointment')}
                        </div>
                        <div class='float_right'>
                            <div>This Week</div>
                            {$this->getPatientVisitDisplay('Last Week', 'By Appointment')}
                        </div>
                    </div>
                </div>-->
                <div class='col-md-8 paddingNone'>
                    <div class='col-md-6 colDash'>
                        <div class='patientVisitSummaryWalkIn'>
                            <table class='list'>
                                <tr>
                                    <td>TODAY PATIENT VISIT (NEW)</td>
                                    <td>{$this->getPatientVisitDisplay('Today', 'Walk In')}</td>
                                </tr>
                                <tr>
                                    <td>Current Month</td>
                                    <td>{$this->getPatientVisitDisplay('Yesterday', 'Walk In')}</td>
                                </tr>
                                <tr>
                                    <td>Last Month</td>
                                    <td>{$this->getPatientVisitDisplay('Last Week', 'Walk In')}</td>
                                </tr>
                            </table>
                          
                               
                                {$PharDailySale}
                               
                        
                        </div>
                    </div>

                    <div class='col-md-6 colDash'>
                        <div class='patientVisitSummary'>
                            <a class='btn btn-default btnRefreshColorPanels'><span class='refreshIcon'></span>Refresh</a>
                            <div class='txtCenter'>PATIENT VISITS DR WISE</div>
                            <div id='patientVisitSummaryDiv'>{$this->getPatientDoctorWise()}</div>
                        </div>
                    </div>

                    <div class='col-md-6 colDash'>
                        <div class='patientVisitSummarySiteWise'>
                            <div class='txtCenter'>OVERALL PATIENT VISITS</div>
                            <div id='patientVisitSummarySiteWiseDiv'>{$this->getPatientVisitSiteWise()}</div>
                        </div>
                    </div>

                    <div class='col-md-6 colDash'>
                        <div class='labReportSummaryYesterday'>
                            <div class='txtCenter'>Lab Report</div>
                            <hr>
                            <div id='labReportSummaryYesterdayDiv'>{$this->getLabReportSummary()}</div>
                        </div>
                    </div>
                </div>
                <div class='col-md-4 paddingNone'>
                    <div class='col-md-12 colDash'>
                        <div class='overallRevenue'>
                            <div class='floatbox'>
                            <div class='float_left'>
                                <a href='#' class='overallToday' data-location-type='{$location_type}'>Today</a>
                            </div>
                            <div class='ml10 float_right'>
                                <a href='#' class='overallYesterday' data-location-type='{$location_type}'>Yesterday</a>
                            </div>
                            <div class='txtCenter mr10 overallTitle'>Today Revenue</div>
                            </div>
                            <div id='overallRevenueDiv'>{$this->getOverallRevenueSummary()}</div>
                        </div>
                    </div>
                   <!-- <div class='col-md-12 colDash'>
                        <div class='attendanceReportSummary'>
                            <div class='txtCenter'>Attendance Report</div>
                            <hr>
                            <div id='attendanceReportSummaryDiv'>{$this->getAttendanceReportSummary()}</div>
                        </div>
                    </div>-->
                    <div class='col-md-12 colDash'>
                    <div class='patientVisitSummarySiteWise'>
                        <div class='txtCenter'>CONSULTANT REPORT</div>
                        <div id='patientVisitSummarySiteWiseDiv'>{$this->getPatientVisitConsultantWise()}</div>
                    </div>
                </div>

                </div>
            </div>
            {$rows}
        </div>
        ";

        return $text;
    }

    function getPatientVisitConsultantWise() {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn    = Zend_Registry::get('fn');
        $db    = Zend_Registry::get('db');
        $location_type  = $fn->getReqParam('location_type');
        
        $rows      = '';
        $overallConsultantCountYesterday = 0;
        $overallConsultantAmountYesterday = 0;
        $overallConsultantCountToday = 0;
        $overallConsultantAmountToday = 0;
    
        $today     = date('Y-m-d');
        $yesterday = date("Y-m-d", strtotime("yesterday"));
        $siteIdCon = '';
        if($location_type == 'Habibia'){
            $siteIdCon = "AND site_id = 1";
        }
    
        $SQLSite = "
        SELECT title, site_id
        FROM site
        WHERE published = 1
        {$siteIdCon}
        ";
        $resultSite = $db->sql_query($SQLSite);
    
        while($rowSite = $db->sql_fetchrow($resultSite)){
    
            $SQLSub = "
            SELECT COUNT(ev.patient_visit_id) AS patient_count
                  ,SUM(ev.consultation_fees) AS fees_count
                  ,SUM(ev.consultation_fees - ev.fees_commission) AS fees_commission_count
                  ,SUM(ev.fees_commission) AS fees_commission_amount
                  ,e.first_name
            FROM employee_visit ev
            LEFT JOIN employee e ON (e.employee_id = ev.employee_id)
            LEFT JOIN patient_visit pv ON (pv.patient_visit_id = ev.patient_visit_id)
            WHERE DATE_FORMAT(pv.check_up_date, '%Y-%m-%d') = '{$yesterday}'
              AND e.first_name != ''
              AND pv.status != 'Cancelled'
              AND pv.site_id = {$rowSite['site_id']}
              AND e.category = 'Consultant'
              GROUP BY e.first_name
            ";
            $resultSub = $db->sql_query($SQLSub);
    
            $consultantRow = '';
            $consultantCountYesterday = 0;
            $consultantAmountYesterday = 0;
            while ($rowSub = $db->sql_fetchrow($resultSub)) {
                $consultantRow .= '<tr>
                                        <td >' . $rowSub['first_name'] . '</td> 
                                        <td >'.$rowSub['patient_count'].'/' . $rowSub['fees_commission_count'] . '</td>
                                   </tr>';
    
                $consultantCountYesterday += $rowSub['patient_count'];
                $consultantAmountYesterday += $rowSub['fees_commission_count'];
            }
    
            $overallConsultantCountYesterday += $consultantCountYesterday;
            $overallConsultantAmountYesterday += $consultantAmountYesterday;
    
            $SQLSub2 = "
            SELECT COUNT(ev.patient_visit_id) AS patient_count
                  ,SUM(ev.consultation_fees) AS fees_count
                  ,SUM(ev.consultation_fees - ev.fees_commission) AS fees_commission_count
                  ,SUM(ev.fees_commission) AS fees_commission_amount
                  ,e.first_name
            FROM employee_visit ev
            LEFT JOIN employee e ON (e.employee_id = ev.employee_id)
            LEFT JOIN patient_visit pv ON (pv.patient_visit_id = ev.patient_visit_id)
            WHERE DATE_FORMAT(pv.check_up_date, '%Y-%m-%d') = '{$today}'
              AND e.first_name != ''
              AND pv.status != 'Cancelled'
              AND pv.site_id = {$rowSite['site_id']}
              AND e.category = 'Consultant'
              GROUP BY e.first_name
            ";
            $resultSub2 = $db->sql_query($SQLSub2);
    
            $consultantCountToday = 0;
            $consultantAmountToday = 0;
            while ($rowSub2 = $db->sql_fetchrow($resultSub2)) {
                $consultantRow .= '<tr>
                                        <td >' . $rowSub2['first_name'] . '</td> 
                                        <td></td>
                                        <td >'.$rowSub2['patient_count'].'/' . $rowSub2['fees_commission_count'] . '</td>
                                   </tr>';
    
                $consultantCountToday += $rowSub2['patient_count'];
                $consultantAmountToday += $rowSub2['fees_commission_count'];
            }
    
            $overallConsultantCountToday += $consultantCountToday;
            $overallConsultantAmountToday += $consultantAmountToday;
    
            $rows .= $consultantRow;
        }
    
        $text = "
        <table cellpadding='5' width='100%'>
            <tr>
                <td style='border-top:1px solid #FFFFFF;'></td>
                <td style='border-top:1px solid #FFFFFF;'><u>Yesterday</u></td>
                <td style='border-top:1px solid #FFFFFF;'><u>Today</u></td>
            </tr>
            {$rows}
            <tr>
                <td colspan='3'><br/></td>
            </tr>
            <tr>
                <td style='line-height:30px;border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;'>Total</td>
                <td style='line-height:30px;border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;'>{$overallConsultantCountYesterday}CS - {$overallConsultantAmountYesterday}RS</td>
                <td style='line-height:30px;border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;'>{$overallConsultantCountToday}CS - {$overallConsultantAmountToday}RS</td>
            </tr>
        </table>
        ";
    
        return $text;
    }
    
    function getPatientVisitSiteWise() {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn    = Zend_Registry::get('fn');
        $db    = Zend_Registry::get('db');
        $location_type  = $fn->getReqParam('location_type');
        
        $rows      = '';
        $appendSql = '';

        $today     = date('Y-m-d');
        $yesterday = date("Y-m-d", strtotime("yesterday"));
        $siteIdCon = '';
        if($location_type == 'Habibia'){
            $siteIdCon = "AND site_id = 1";
        }

        $SQLSite = "
        SELECT title 
              ,site_id
        FROM site
        WHERE published = 1
        {$siteIdCon}
        ";
        $resultSite = $db->sql_query($SQLSite);

        $sum_amount                       = 0;
        $case_count                       = 0;
        $sum_amountYesterDay              = 0;
        $case_countYesterDay              = 0;
        $overallCaseCountYesterday        = 0;
        $overallCaseAmountYesterday       = 0;
        $overallCaseCountToday            = 0;
        $overallCaseAmountToday           = 0;
        $overallNoConsCaseCountYesterday  = 0;
        $overallNoConsCaseAmountYesterday = 0;
        $overallNoConsCaseCountToday      = 0;
        $overallNoConsCaseAmountToday     = 0;
        $consultantRow = "";
        $procedureRow = "";
        while($rowSite = $db->sql_fetchrow($resultSite)){

            $SQLSub = "
            SELECT COUNT(ev.patient_visit_id) AS patient_count
                  ,SUM(ev.consultation_fees) AS fees_count
                   ,SUM(ev.procedure_fees) AS procedure_fees
                  ,SUM(ev.consultation_fees - ev.fees_commission) AS fees_commission_count
                  ,SUM(ev.fees_commission) AS fees_commission_amount
                  ,e.category
                ,IF(ev.procedure_fees != '', COUNT(ev.patient_visit_id), 0) AS ProcedureCount
                  ,IF(e.category = 'Consultant', COUNT(ev.patient_visit_id), 0) AS ConsultantCount
                  ,IF(e.category = 'Consultant', SUM(ev.consultation_fees), 0) AS ConsultantAmount
            FROM employee_visit ev
            LEFT JOIN employee e ON (e.employee_id = ev.employee_id)
            LEFT JOIN patient_visit pv ON (pv.patient_visit_id = ev.patient_visit_id)
            WHERE DATE_FORMAT(pv.check_up_date, '%Y-%m-%d') = '{$yesterday}'
              AND e.first_name != ''
              AND pv.status != 'Cancelled'
              AND pv.site_id = {$rowSite['site_id']}
              GROUP BY e.category
            ";
            $resultSub = $db->sql_query($SQLSub);
            $sum_amountYesterDay = 0;
            $sum_amountYesterDay_comm = 0;
            $case_countYesterDay = 0;
            $consultantAmountYesterDay = 0;
            $consultantCountYesterDay  = 0;
            $consultantComsnYesterday  = 0;
            $procedureAmtYesterDay=0;
            while ($rowSub = $db->sql_fetchrow($resultSub)) {
                if($rowSub['category'] == 'Consultant'){
                    $sum_amountYesterDay_comm += $rowSub['fees_commission_count'];
                    $consultantComsnYesterday += $rowSub['fees_commission_amount'];
                } else {
                    $sum_amountYesterDay += $rowSub['fees_count'];                    
                    $case_countYesterDay += $rowSub['patient_count'];
                }

                $consultantCountYesterDay  += $rowSub['ConsultantCount'];
                $consultantAmountYesterDay += $rowSub['ConsultantAmount'];

                $procedureAmtYesterDay += $rowSub['procedure_fees'];
            }

            $overallCaseCountYesterday  += $case_countYesterDay + $consultantCountYesterDay;
            $overallCaseAmountYesterday += $sum_amountYesterDay + $consultantComsnYesterday + $procedureAmtYesterDay;

            if($sum_amountYesterDay_comm > 0){
                $sum_amountYesterDay_comm = "({$sum_amountYesterDay_comm})";
            } else {
                $sum_amountYesterDay_comm = '';
            }

            $SQLSub2 = "
            SELECT COUNT(ev.patient_visit_id) AS patient_count
                  ,SUM(ev.consultation_fees) AS fees_count
                  ,SUM(ev.procedure_fees) AS procedure_fees
                  ,SUM(ev.fees_commission) AS fees_commission_amount
                  ,SUM(ev.consultation_fees - ev.fees_commission) AS fees_commission_count
                  ,e.category
                ,IF(ev.procedure_fees != '', COUNT(ev.patient_visit_id), 0) AS ProcedureCount
                  ,IF(e.category = 'Consultant', COUNT(ev.patient_visit_id), 0) AS ConsultantCount
                  ,IF(e.category = 'Consultant', SUM(ev.consultation_fees), 0) AS ConsultantAmount
            FROM employee_visit ev
            LEFT JOIN employee e ON (e.employee_id = ev.employee_id)
            LEFT JOIN patient_visit pv ON (pv.patient_visit_id = ev.patient_visit_id)
            WHERE DATE_FORMAT(pv.check_up_date, '%Y-%m-%d') = '{$today}'
              AND e.first_name != ''
              AND pv.status != 'Cancelled'
              AND pv.site_id = {$rowSite['site_id']}
              GROUP BY e.category
            ";
            $resultSub2 = $db->sql_query($SQLSub2);
            $sum_amount = 0;
            $sum_amount_comm = 0;
            $case_count = 0;
            $consultantAmount = 0;
            $consultantCount  = 0;
            $consultantComsn  = 0;
            $procedureAmount=0;
            while ($rowSub2 = $db->sql_fetchrow($resultSub2)) {
                if($rowSub2['category'] == 'Consultant'){
                    $sum_amount_comm += $rowSub2['fees_commission_count'];
                    $consultantComsn += $rowSub2['fees_commission_amount'];
                } else {
                    $sum_amount += $rowSub2['fees_count'];                    
                    $case_count += $rowSub2['patient_count'];
                }
                
                $consultantCount  += $rowSub2['ConsultantCount'];
                $consultantAmount += $rowSub2['ConsultantAmount'];
                $procedureAmount += $rowSub2['procedure_fees'];

            }

            $overallCaseCountToday  += $case_count + $consultantCount;
            $overallCaseAmountToday += $sum_amount + $consultantComsn + $procedureAmount;

            if($sum_amount_comm > 0){
                $sum_amount_comm = "({$sum_amount_comm})";
            } else {
                $sum_amount_comm = '';
            }

            $overallNoConsCaseCountYesterday  += $case_countYesterDay - $consultantCountYesterDay;
            $overallNoConsCaseAmountYesterday += $sum_amountYesterDay - $consultantAmountYesterDay + $procedureAmtYesterDay;
            $overallNoConsCaseCountToday      += $case_count - $consultantCount;
            $overallNoConsCaseAmountToday     += $sum_amount - $consultantAmount + $procedureAmount;

            if($rowSite['site_id'] == 1) {
                $consultantRow .= "
                <tr>
                    <td width='25%'>CONS</td>
                    <td width='35%'>{$consultantCountYesterDay} / {$consultantComsnYesterday}</td>
                    <td width='40%'>{$consultantCount} / {$consultantComsn}</td>
                </tr
                ";
                $procedureRow .= "
                <tr>
                    <td width='25%'>PROCEURE FEES</td>
                    <td width='35%'> {$procedureAmtYesterDay}</td>
                    <td width='40%'> {$procedureAmount}</td>
                </tr
                ";
            }

            

            $rows .= '<tr>
                        <td width="25%">'.substr($rowSite['title'],0, 8).'</td> 
                        <td width="35%">'.$case_countYesterDay.' / '.$sum_amountYesterDay.'</td>
                        <td width="40%">'.$case_count.' / '.$sum_amount.'</td>
                     </tr>
                     ';
        }

        $text = "
        <table cellpadding='5' width='100%'>
            <tr>
                <td style='border-top:1px solid #FFFFFF;'></td>
                <td style='border-top:1px solid #FFFFFF;'><u>Yesterday</u></td>
                <td style='border-top:1px solid #FFFFFF;'><u>Today</u></td>
            </tr>
            {$rows}
            {$consultantRow}
            {$procedureRow}
            <tr>
                <td colspan='3'><br/></td>
            </tr>
            <tr>
                <td style='line-height:30px;border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;'>Total</td>
                <td style='line-height:30px;border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;'>{$overallCaseCountYesterday}CS - {$overallCaseAmountYesterday}RS</td>
                <td style='line-height:30px;border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;'>{$overallCaseCountToday}CS - {$overallCaseAmountToday}RS</td>
            </tr>
        </table>
        ";

        return $text;
    }

    function getPatientDoctorWise() {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $location_type  = $fn->getReqParam('location_type');

        $rows = '';
        $currentYear = date('Y');
        $currentMonth = date('m');
        $start_date  = $currentYear . '-' . $currentMonth . '-' . '01';
        $end_date = date("Y-m-d");
        //$date = "DATE_FORMAT(ev.creation_date, '%Y-%m-%d') >= '{$start_date}' AND DATE_FORMAT(ev.creation_date, '%Y-%m-%d') <= '{$end_date}'";
        $check_up_date = date("Y-m-d");
        $yesterday     = date("Y-m-d", strtotime("yesterday"));
        $Yesterdaydate = "DATE_FORMAT(pv.check_up_date, '%Y-%m-%d') = '{$yesterday}'";
        $date          = "DATE_FORMAT(pv.check_up_date, '%Y-%m-%d') = '{$check_up_date}'";

        $sum_amount = 0;
        $case_count = 0;

        $sqlCategory = $fn->getValueListSQL('employeeCategory');
        $sqlCategory = "
        SELECT value
        FROM valuelist
        WHERE key_text = 'employeeCategory'
          AND value != 'Anaesthetist'
          AND value != 'Surgeon'
          AND value != 'Theater Assistant'
          AND value != 'Lab Technician'
          AND value != 'Pharmacy Staff'
        ";
        $resultCat   = $db->sql_query($sqlCategory);

        $overallCaseCountYesterday  = 0;
        $overallCaseAmountYesterday = 0;
        $overallCaseCountToday      = 0;
        $overallCaseAmountToday     = 0;
        $overallNoConsCaseCountYesterday  = 0;
        $overallNoConsCaseAmountYesterday = 0;
        $overallNoConsCaseCountToday      = 0;
        $overallNoConsCaseAmountToday     = 0;

        while ($rowCat = $db->sql_fetchrow($resultCat)) {

            if($rowCat['value'] != "Student"){
                $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
                $appendSqlSite = '';
                if ($cpCfg['cp.hasMultiUniqueSites']) {
                    $appendSqlSite = "AND pv.site_id = {$cpSiteIdSession}";
                }

                $SQL = "
                SELECT ev.*
                      ,COUNT(ev.patient_visit_id) AS patient_count
                      ,SUM(ev.consultation_fees) AS fees_count
                      ,SUM(ev.fees_commission) AS fees_commission_count
                      ,SUM(ev.consultation_fees) AS fees_bal_count
                      ,e.category
                FROM employee_visit ev
                LEFT JOIN employee e ON (e.employee_id = ev.employee_id)
                LEFT JOIN patient_visit pv ON (pv.patient_visit_id = ev.patient_visit_id)
                WHERE {$Yesterdaydate}
                  AND e.first_name != ''
                  AND pv.status != 'Cancelled'
                  AND e.category = '{$rowCat['value']}'
                  {$appendSqlSite}
                ";
                $result = $db->sql_query($SQL);
                $sum_amountYesterDay = 0;
                $case_countYesterDay = 0;
                $sum_balYesterDay = 0;
                while ($row = $db->sql_fetchrow($result)) {
                    if($row['category'] == 'Consultant'){
                        $sum_amountYesterDay = round($row['fees_commission_count']);
                    } else {
                        $sum_amountYesterDay = $row['fees_count'];
                    }
                    $case_countYesterDay = $row['patient_count'];
                    $sum_balYesterDay = round($row['fees_bal_count']);
                }

                if($sum_amountYesterDay == ""){
                    $sum_amountYesterDay = 0;
                }

                if($case_countYesterDay == ""){
                    $case_countYesterDay = 0;
                }

                if($sum_balYesterDay > 0 && $rowCat['value'] == "Consultant"){
                    $sum_balYesterDay = "({$sum_balYesterDay})";
                } else {
                    $sum_balYesterDay = "";                    
                }

                $overallCaseCountYesterday  += $case_countYesterDay;
                $overallCaseAmountYesterday += $sum_amountYesterDay;

                $SQL2 = "
                SELECT ev.*
                      ,COUNT(ev.patient_visit_id) AS patient_count
                      ,SUM(ev.consultation_fees) AS fees_count
                      ,SUM(ev.fees_commission) AS fees_commission_count
                      ,SUM(ev.consultation_fees) AS fees_bal_count
                      ,e.category
                FROM employee_visit ev
                LEFT JOIN employee e ON (e.employee_id = ev.employee_id)
                LEFT JOIN patient_visit pv ON (pv.patient_visit_id = ev.patient_visit_id)
                WHERE {$date}
                  AND e.first_name != ''
                  AND pv.status != 'Cancelled'
                  AND e.category = '{$rowCat['value']}'
                  {$appendSqlSite}
                ";
                $result2 = $db->sql_query($SQL2);
                
                $sum_amount = 0;
                $sum_bal = 0;
                $case_count = 0;
                while ($row2 = $db->sql_fetchrow($result2)) {
                    if($row2['category'] == 'Consultant'){
                        $sum_amount = round($row2['fees_commission_count']);
                    } else {
                        $sum_amount = $row2['fees_count'];
                    }
                    $case_count = $row2['patient_count'];
                    $sum_bal = round($row2['fees_bal_count']);
                }

                if($sum_amount == ""){
                    $sum_amount = 0;
                }

                if($sum_bal > 0 && $rowCat['value'] == "Consultant"){
                    $sum_bal = "({$sum_bal})";
                } else {
                    $sum_bal = "";                    
                }

                if($case_count == ""){
                    $case_count = 0;
                }

                $overallCaseCountToday  += $case_count;
                $overallCaseAmountToday += $sum_amount;

                if($rowCat['value'] != "Consultant") {
                    $overallNoConsCaseCountYesterday  += $case_countYesterDay;
                    $overallNoConsCaseAmountYesterday += $sum_amountYesterDay;
                    $overallNoConsCaseCountToday      += $case_count;
                    $overallNoConsCaseAmountToday     += $sum_amount;
                }

                $rows .= "
                <tr>
                    <td width='40%'>{$rowCat['value']}</td>
                    <td width='30%'>{$case_countYesterDay} / {$sum_amountYesterDay}{$sum_balYesterDay}</td>
                    <td width='30%'>{$case_count} / {$sum_amount}{$sum_bal}</td>
                </tr>
                ";
            }
        }

        $text = "
        <table border='0' width='100%'>
            <tr>
                <td style='border-top:1px solid #FFFFFF;'></td>
                <td style='border-top:1px solid #FFFFFF;'><u>Yesterday</u></td>
                <td style='border-top:1px solid #FFFFFF;'><u>Today</u></td>
            </tr>
            {$rows}
            <tr>
                <td style='border-top:1px solid #FFFFFF;'>Total</td>
                <td style='border-top:1px solid #FFFFFF;'>{$overallCaseCountYesterday} - {$overallCaseAmountYesterday}</td>
                <td style='border-top:1px solid #FFFFFF;'>{$overallCaseCountToday} - {$overallCaseAmountToday}</td>
            </tr>
            <tr>
                <td  style='border-bottom:1px solid #FFFFFF;'>Total - Cons</td>
                <td  style='border-bottom:1px solid #FFFFFF;'>{$overallNoConsCaseCountYesterday} - {$overallNoConsCaseAmountYesterday}</td>
                <td  style='border-bottom:1px solid #FFFFFF;'>{$overallNoConsCaseCountToday} - {$overallNoConsCaseAmountToday}</td>
            </tr>
        </table>
        ";

        return $text;
    }

    function getSummaryDisplay($day) {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $rows = 0;

        if($day == 'Today'){
            $creation_date = date("Y-m-d");
            $date = "AND r.date = '{$creation_date}'";
        }else if($day == 'Yesterday'){
            $currentYear = date('Y');
            $currentMonth = date('m');
            //$creation_date = date('Y-m-d', mktime (0,0,0,date("m"), date("d")-1, date("Y")));
            $start_date  = $currentYear . '-' . $currentMonth . '-' . '01';
            $end_date = date("Y-m-d");
            //$date = "AND r.date = '{$creation_date}'";
            $date = "AND r.date >= '{$start_date}' AND r.date <= '{$end_date}'";
        }else if($day == 'Last Week'){
            /*$currentYear = date('Y');
            $lastMonth = date('m') - 1;
            $start_date = $currentYear . '-' . $lastMonth . '-' . '01';
            $end_date = $currentYear . '-' . $lastMonth . '-' . '31';*/
            $start_date = date('Y-m-d', strtotime('first day of last month'));
            $end_date = date('Y-m-d', strtotime('last day of last month'));
            $date = "AND r.date >= '{$start_date}' AND r.date <= '{$end_date}'";
        }

        $SQL = "
        SELECT r.*
        FROM receipt r
        WHERE r.receipt_status != 'Cancelled'
          {$date}
        ";
        $result = $db->sql_query($SQL);

        while ($row = $db->sql_fetchrow($result)) {
            $rows += "
            {$row['amount']}
            ";
        }
        $rows = number_format($rows, 2);

        return $rows;
    }

    function getPatientVisitDisplay($day = '', $type = '') {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $location_type  = $fn->getReqParam('location_type');

        if($day == ""){
            $day = $fn->getReqparam('day');
        }

        if($type == ""){
            $type = $fn->getReqparam('type');
        }

        $rows = '';

        if($day == 'Today'){
            $creation_date = date("Y-m-d");
            $date = "AND pv.check_up_date = '{$creation_date}' AND pv.record_type='{$type}'";
            $datePatient = "AND DATE_FORMAT(p.creation_date, '%Y-%m-%d') = '{$creation_date}'";
        }else if($day == 'Yesterday'){
            $currentYear = date('Y');
            $currentMonth = date('m');
            $start_date  = $currentYear . '-' . $currentMonth . '-' . '01';
            $end_date = date("Y-m-d");
            $date = "AND pv.check_up_date >= '{$start_date}' AND pv.check_up_date <= '{$end_date}' AND pv.record_type='{$type}'";
            $datePatient = "AND DATE_FORMAT(p.creation_date, '%Y-%m-%d') >= '{$start_date}' AND DATE_FORMAT(p.creation_date, '%Y-%m-%d') <= '{$end_date}'";
        }else if($day == 'Last Week'){
            $start_date = date('Y-m-d', strtotime('first day of last month'));
            $end_date = date('Y-m-d', strtotime('last day of last month'));
            $date = "AND pv.check_up_date >= '{$start_date}' AND pv.check_up_date <= '{$end_date}' AND pv.record_type='{$type}'";
            $datePatient = "AND DATE_FORMAT(p.creation_date, '%Y-%m-%d') >= '{$start_date}' AND DATE_FORMAT(p.creation_date, '%Y-%m-%d') <= '{$end_date}'";
        }

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $appendSqlSite = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlSite = "AND pv.site_id = {$cpSiteIdSession}";
        }

        $SQL = "
        SELECT COUNT(pv.patient_visit_id) AS patient_count
        FROM patient_visit pv
        WHERE pv.status != 'Cancelled'
        {$date}
        {$appendSqlSite}
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);

        $SQLNewPat = "
        SELECT DISTINCT p.patient_information_id AS patient_count
        FROM patient_information p
        LEFT JOIN patient_visit pv ON (pv.patient_information_id = p.patient_information_id)
        WHERE pv.status != 'Cancelled'
        {$datePatient}
        {$date}
        {$appendSqlSite}
        GROUP BY pv.patient_information_id
        ";
        $resultNewPat    = $db->sql_query($SQLNewPat);
        $newPatientCount = 0;
        while($rowNewPat = $db->sql_fetchrow($resultNewPat)){
            $newPatientCount++;
        }

        $perentCalc = 0;
        if($row['patient_count'] > 0){
            $perentCalc = ($newPatientCount / $row['patient_count']) * 100;
            $perentCalc = number_format($perentCalc, 0, '.', '');
        }

        //while ($row = $db->sql_fetchrow($result)) {
            $rows = "
            {$row['patient_count']} ({$newPatientCount}){$perentCalc}%
            ";
        //}

        return $rows;
    }

    function getLabReportSummary() {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn    = Zend_Registry::get('fn');
        $db    = Zend_Registry::get('db');
        $location_type  = $fn->getReqParam('location_type');

        $yesterday = date("Y-m-d", strtotime("yesterday"));
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $currentDay = date("Y-m-d");

        $SQLYest = "
        SELECT * FROM (
          SELECT site_id, DATE_FORMAT(mtv.creation_date, '%Y-%m-%d') AS creation_date
        FROM medical_test_visit mtv
          UNION  
          SELECT site_id, DATE_FORMAT(mtl.creation_date, '%Y-%m-%d') AS creation_date
        FROM medical_test_lab mtl
          UNION  
          SELECT site_id, DATE_FORMAT(mtip.creation_date, '%Y-%m-%d') AS creation_date
        FROM medical_test_in_patient mtip
        ) A
        WHERE DATE_FORMAT(creation_date, '%Y-%m-%d') = '{$yesterday}'
        GROUP BY DATE_FORMAT(creation_date, '%Y-%m-%d')
        ";

        $totaltestamountYest        = 0;
        $totalPattestamountYest     = 0;
        $totalIpTestAmountYest      = 0;
        $totalPatTestXrayAmountYest = 0;
        $totalPatTestEcgAmountYest  = 0;
        $totalTestXrayAmountYest    = 0;
        $totalTestEcgAmountYest     = 0;
        $totalIpTestXrayAmountYest  = 0;
        $totalIpTestEcgAmountYest   = 0;
        $totalAlltestamountYest     = 0;
        $resultYest = $db->sql_query($SQLYest);
        while($rowYest    = $db->sql_fetchrow($resultYest)){
            $day  = $fn->getCPDate($rowYest['creation_date'], 'D');
            $date = $fn->getCPDate($rowYest['creation_date'], 'd-m-Y');
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSql = "AND m.site_id = {$cpSiteIdSession}";
            }

            $SQLPatLabTestYest = "
            SELECT COUNT(m.medical_test_id) AS count
                   ,m.title
                   ,mt.category
                   ,SUM(m.fees) AS fees
                   ,SUM(m.lab_supplier_fees) AS lab_supplier_fees
            FROM medical_test_visit m
            LEFT JOIN (medical_test mt) ON (mt.medical_test_id = m.medical_test_id)
            LEFT JOIN (patient_visit pv) ON (pv.patient_visit_id = m.patient_visit_id)
            WHERE DATE_FORMAT(m.creation_date, '%Y-%m-%d') = '{$rowYest['creation_date']}'
              AND pv.status != 'Cancelled'
              AND mt.category != 'Vaccination'
            {$appendSql}
            GROUP BY m.title
            ";
            $resultPatLabTestYest = $db->sql_query($SQLPatLabTestYest);
            $totalPattestamountYest     = 0;
            $totalPatTestXrayAmountYest = 0;
            $totalPatTestEcgAmountYest  = 0;
            while ($rowPatLabTestYest = $db->sql_fetchrow($resultPatLabTestYest)) {
                if($rowPatLabTestYest['fees'] == ""){
                    $rowPatLabTestYest['fees'] = 0;
                }
                if($rowPatLabTestYest['lab_supplier_fees'] == ""){
                    $rowPatLabTestYest['lab_supplier_fees'] = 0;
                }

                $fees = $rowPatLabTestYest['fees'] - $rowPatLabTestYest['lab_supplier_fees'];

                if($rowPatLabTestYest['category'] != "radiology" && $rowPatLabTestYest['category'] != "ECG"){
                    $totalPattestamountYest += $fees;
                }

                if($rowPatLabTestYest['category'] == "radiology"){
                    $totalPatTestXrayAmountYest += $fees;
                }

                if($rowPatLabTestYest['category'] == "ECG"){
                    $totalPatTestEcgAmountYest += $fees;
                }

            }

            $SQLLabTestYest = "
            SELECT COUNT(m.medical_test_id) AS count
                   ,m.title
                   ,mt.category
                   ,SUM(m.fees) AS fees
                   ,SUM(m.lab_supplier_fees) AS lab_supplier_fees
            FROM medical_test_lab m
            LEFT JOIN (medical_test mt) ON (mt.medical_test_id = m.medical_test_id)
            LEFT JOIN (lab_test lt) ON (lt.lab_test_id = m.lab_test_id)
            WHERE DATE_FORMAT(m.creation_date, '%Y-%m-%d') = '{$rowYest['creation_date']}'
              AND lt.status != 'Cancelled'
            {$appendSql}
            GROUP BY m.title
            ";
            $resultLabTestYest = $db->sql_query($SQLLabTestYest);
            $totaltestamountYest     = 0;
            $totalTestXrayAmountYest = 0;
            $totalTestEcgAmountYest  = 0;
            while ($rowLabTestYest   = $db->sql_fetchrow($resultLabTestYest)) {
                if($rowLabTestYest['fees'] == ""){
                    $rowLabTestYest['fees'] = 0;
                }
                if($rowLabTestYest['lab_supplier_fees'] == ""){
                    $rowLabTestYest['lab_supplier_fees'] = 0;
                }

                $fees = $rowLabTestYest['fees'] - $rowLabTestYest['lab_supplier_fees'];

                if($rowLabTestYest['category'] != "radiology" && $rowLabTestYest['category'] != "ECG"){
                    $totaltestamountYest += $fees;
                }

                if($rowLabTestYest['category'] == "radiology"){
                    $totalTestXrayAmountYest += $fees;
                }

                if($rowLabTestYest['category'] == "ECG"){
                    $totalTestEcgAmountYest += $fees;
                }

            }

            $SQLInPatLabTestYest = "
            SELECT COUNT(m.medical_test_id) AS count
                   ,m.title
                   ,mt.category
                   ,SUM(m.fees) AS fees
                   ,SUM(m.lab_supplier_fees) AS lab_supplier_fees
            FROM medical_test_in_patient m
            LEFT JOIN (medical_test mt) ON (mt.medical_test_id = m.medical_test_id)
            LEFT JOIN (in_patient ip) ON (ip.in_patient_id = m.in_patient_id)
            WHERE DATE_FORMAT(m.creation_date, '%Y-%m-%d') = '{$rowYest['creation_date']}'
              AND ip.status != 'Cancelled'
            {$appendSql}
            GROUP BY m.title
            ";
            $resultInPatLabTestYest = $db->sql_query($SQLInPatLabTestYest);
            $totalIpTestAmountYest     = 0;
            $totalIpTestXrayAmountYest = 0;
            $totalIpTestEcgAmountYest  = 0;
            while ($rowInPatLabTestYest    = $db->sql_fetchrow($resultInPatLabTestYest)) {
                if($rowInPatLabTestYest['fees'] == ""){
                    $rowInPatLabTestYest['fees'] = 0;
                }
                if($rowInPatLabTestYest['lab_supplier_fees'] == ""){
                    $rowInPatLabTestYest['lab_supplier_fees'] = 0;
                }

                $fees = $rowInPatLabTestYest['fees'] - $rowInPatLabTestYest['lab_supplier_fees'];

                if($rowInPatLabTestYest['category'] != "radiology" && $rowInPatLabTestYest['category'] != "ECG"){
                    $totalIpTestAmountYest += $fees;
                }

                if($rowInPatLabTestYest['category'] == "radiology"){
                    $totalIpTestXrayAmountYest += $fees;
                }

                if($rowInPatLabTestYest['category'] == "ECG"){
                    $totalIpTestEcgAmountYest += $fees;
                }

            }

            $totalAlltestamountYest = $totaltestamountYest + $totalPattestamountYest + $totalIpTestAmountYest + $totalPatTestXrayAmountYest + $totalPatTestEcgAmountYest + $totalTestXrayAmountYest + $totalTestEcgAmountYest + $totalIpTestXrayAmountYest + $totalIpTestEcgAmountYest;
        }

        $SQL = "
        SELECT * FROM (
          SELECT site_id, DATE_FORMAT(mtv.creation_date, '%Y-%m-%d') AS creation_date
        FROM medical_test_visit mtv
          UNION  
          SELECT site_id, DATE_FORMAT(mtl.creation_date, '%Y-%m-%d') AS creation_date
        FROM medical_test_lab mtl
          UNION  
          SELECT site_id, DATE_FORMAT(mtip.creation_date, '%Y-%m-%d') AS creation_date
        FROM medical_test_in_patient mtip
        ) A
        WHERE DATE_FORMAT(creation_date, '%Y-%m-%d') = '{$currentDay}'
        GROUP BY DATE_FORMAT(creation_date, '%Y-%m-%d')
        ";

        $rows = "";
        $totalOverAll           = 0;
        $totalOverAllCount      = 0;
        $totaltestcount         = 0;
        $totalPattestcount      = 0;
        $totalIpTestCount       = 0;
        $totaltestamount        = 0;
        $totalPattestamount     = 0;
        $totalIpTestAmount      = 0;
        $totalPatTestXrayCount  = 0;
        $totalPatTestXrayAmount = 0;
        $totalPatTestEcgCount   = 0;
        $totalPatTestEcgAmount  = 0;
        $totalTestXrayCount     = 0;
        $totalTestXrayAmount    = 0;
        $totalTestEcgCount      = 0;
        $totalTestEcgAmount     = 0;
        $totalIpTestXrayCount   = 0;
        $totalIpTestXrayAmount  = 0;
        $totalIpTestEcgCount    = 0;
        $totalIpTestEcgAmount   = 0;
        $totalAlltestcount      = 0;
        $totalAlltestamount     = 0;
        $totalLabCount          = 0; 
        $totalLabAmount         = 0; 
        $totalEcgCount          = 0; 
        $totalEcgAmount         = 0; 
        $totalXrayCount         = 0; 
        $totalXrayAmount        = 0; 
        $result = $db->sql_query($SQL);
        while($row    = $db->sql_fetchrow($result)){
            $day  = $fn->getCPDate($row['creation_date'], 'D');
            $date = $fn->getCPDate($row['creation_date'], 'd-m-Y');
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSql = "AND m.site_id = {$cpSiteIdSession}";
            }

            $SQLPatLabTest = "
            SELECT COUNT(m.medical_test_id) AS count
                   ,m.title
                   ,mt.category
                   ,SUM(m.fees) AS fees
                   ,SUM(m.lab_supplier_fees) AS lab_supplier_fees
            FROM medical_test_visit m
            LEFT JOIN (medical_test mt) ON (mt.medical_test_id = m.medical_test_id)
            LEFT JOIN (patient_visit pv) ON (pv.patient_visit_id = m.patient_visit_id)
            WHERE DATE_FORMAT(m.creation_date, '%Y-%m-%d') = '{$row['creation_date']}'
              AND pv.status != 'Cancelled'
              AND mt.category != 'Vaccination'
            {$appendSql}
            GROUP BY m.title
            ";
            $resultPatLabTest = $db->sql_query($SQLPatLabTest);
            $patlabtesttotal        = "";
            $totalPattestcount      = 0;
            $totalPattestamount     = 0;
            $totalPatTestXrayCount  = 0;
            $totalPatTestXrayAmount = 0;
            $totalPatTestEcgCount   = 0;
            $totalPatTestEcgAmount  = 0;
            while ($rowPatLabTest = $db->sql_fetchrow($resultPatLabTest)) {
                if($rowPatLabTest['fees'] == ""){
                    $rowPatLabTest['fees'] = 0;
                }
                if($rowPatLabTest['lab_supplier_fees'] == ""){
                    $rowPatLabTest['lab_supplier_fees'] = 0;
                }

                $fees = $rowPatLabTest['fees'] - $rowPatLabTest['lab_supplier_fees'];

                $patlabtesttotal .= $rowPatLabTest['title'].'('.$rowPatLabTest['count'].' - '.$fees.'), ';

                if($rowPatLabTest['category'] != "radiology" && $rowPatLabTest['category'] != "ECG"){
                    $totalPattestcount  += $rowPatLabTest['count'];
                    $totalPattestamount += $fees;
                }

                if($rowPatLabTest['category'] == "radiology"){
                    $totalPatTestXrayCount  += $rowPatLabTest['count'];
                    $totalPatTestXrayAmount += $fees;
                }

                if($rowPatLabTest['category'] == "ECG"){
                    $totalPatTestEcgCount  += $rowPatLabTest['count'];
                    $totalPatTestEcgAmount += $fees;
                }

            }
            $patlabtesttotal = rtrim($patlabtesttotal, ", ");

            $SQLLabTest = "
            SELECT COUNT(m.medical_test_id) AS count
                   ,m.title
                   ,mt.category
                   ,SUM(m.fees) AS fees
                   ,SUM(m.lab_supplier_fees) AS lab_supplier_fees
            FROM medical_test_lab m
            LEFT JOIN (medical_test mt) ON (mt.medical_test_id = m.medical_test_id)
            LEFT JOIN (lab_test lt) ON (lt.lab_test_id = m.lab_test_id)
            WHERE DATE_FORMAT(m.creation_date, '%Y-%m-%d') = '{$row['creation_date']}'
              AND lt.status != 'Cancelled'
            {$appendSql}
            GROUP BY m.title
            ";
            $resultLabTest = $db->sql_query($SQLLabTest);
            $labtesttotal = "";
            $totaltestcount      = 0;
            $totaltestamount     = 0;
            $totalTestXrayCount  = 0;
            $totalTestXrayAmount = 0;
            $totalTestEcgCount   = 0;
            $totalTestEcgAmount  = 0;
            while ($rowLabTest   = $db->sql_fetchrow($resultLabTest)) {
                if($rowLabTest['fees'] == ""){
                    $rowLabTest['fees'] = 0;
                }
                if($rowLabTest['lab_supplier_fees'] == ""){
                    $rowLabTest['lab_supplier_fees'] = 0;
                }

                $fees = $rowLabTest['fees'] - $rowLabTest['lab_supplier_fees'];

                $labtesttotal .= $rowLabTest['title'].'('.$rowLabTest['count'].' - '.$fees.'), ';

                if($rowLabTest['category'] != "radiology" && $rowLabTest['category'] != "ECG"){
                    $totaltestcount  += $rowLabTest['count'];
                    $totaltestamount += $fees;
                }

                if($rowLabTest['category'] == "radiology"){
                    $totalTestXrayCount  += $rowLabTest['count'];
                    $totalTestXrayAmount += $fees;
                }

                if($rowLabTest['category'] == "ECG"){
                    $totalTestEcgCount  += $rowLabTest['count'];
                    $totalTestEcgAmount += $fees;
                }

            }
            $labtesttotal = rtrim($labtesttotal, ", ");

            $SQLInPatLabTest = "
            SELECT COUNT(m.medical_test_id) AS count
                   ,m.title
                   ,mt.category
                   ,SUM(m.fees) AS fees
                   ,SUM(m.lab_supplier_fees) AS lab_supplier_fees
            FROM medical_test_in_patient m
            LEFT JOIN (medical_test mt) ON (mt.medical_test_id = m.medical_test_id)
            LEFT JOIN (in_patient ip) ON (ip.in_patient_id = m.in_patient_id)
            WHERE DATE_FORMAT(m.creation_date, '%Y-%m-%d') = '{$row['creation_date']}'
              AND ip.status != 'Cancelled'
            {$appendSql}
            GROUP BY m.title
            ";
            $resultInPatLabTest = $db->sql_query($SQLInPatLabTest);
            $InPatLabTestTotal = "";
            $totalIpTestCount      = 0;
            $totalIpTestAmount     = 0;
            $totalIpTestXrayCount  = 0;
            $totalIpTestXrayAmount = 0;
            $totalIpTestEcgCount   = 0;
            $totalIpTestEcgAmount  = 0;
            while ($rowInPatLabTest    = $db->sql_fetchrow($resultInPatLabTest)) {
                if($rowInPatLabTest['fees'] == ""){
                    $rowInPatLabTest['fees'] = 0;
                }
                if($rowInPatLabTest['lab_supplier_fees'] == ""){
                    $rowInPatLabTest['lab_supplier_fees'] = 0;
                }

                $fees = $rowInPatLabTest['fees'] - $rowInPatLabTest['lab_supplier_fees'];

                $InPatLabTestTotal .= $rowInPatLabTest['title'].'('.$rowInPatLabTest['count'].' - '.$fees.'), ';

                if($rowInPatLabTest['category'] != "radiology" && $rowInPatLabTest['category'] != "ECG"){
                    $totalIpTestCount  += $rowInPatLabTest['count'];
                    $totalIpTestAmount += $fees;
                }

                if($rowInPatLabTest['category'] == "radiology"){
                    $totalIpTestXrayCount  += $rowInPatLabTest['count'];
                    $totalIpTestXrayAmount += $fees;
                }

                if($rowInPatLabTest['category'] == "ECG"){
                    $totalIpTestEcgCount  += $rowInPatLabTest['count'];
                    $totalIpTestEcgAmount += $fees;
                }

            }

            $InPatLabTestTotal = rtrim($InPatLabTestTotal, ", ");

            $totalAlltestcount  = $totaltestcount + $totalPattestcount + $totalIpTestCount + $totalPatTestXrayCount + $totalPatTestEcgCount + $totalTestXrayCount + $totalTestEcgCount + $totalIpTestXrayCount + $totalIpTestEcgCount;
            $totalAlltestamount = $totaltestamount + $totalPattestamount + $totalIpTestAmount + $totalPatTestXrayAmount + $totalPatTestEcgAmount + $totalTestXrayAmount + $totalTestEcgAmount + $totalIpTestXrayAmount + $totalIpTestEcgAmount;
            
            $totalLabCount   = $totaltestcount + $totalPattestcount + $totalIpTestCount;
            $totalLabAmount  = $totaltestamount + $totalPattestamount + $totalIpTestAmount;
            $totalEcgCount   = $totalPatTestEcgCount + $totalTestEcgCount + $totalIpTestEcgCount;
            $totalEcgAmount  = $totalPatTestEcgAmount + $totalTestEcgAmount + $totalIpTestEcgAmount;
            $totalXrayCount  = $totalPatTestXrayCount + $totalTestXrayCount + $totalIpTestXrayCount;
            $totalXrayAmount = $totalPatTestXrayAmount + $totalTestXrayAmount + $totalIpTestXrayAmount;
        }


        $LabTest = '';

        if($location_type != 'Habibia'){
                 $LabTest = "
                  <tr>
                <td width='22%'>Pat Visit</td>
                <td width='26%'>{$totalPattestcount} / {$totalPattestamount}</td>
                <td width='26%'>{$totalPatTestXrayCount} / {$totalPatTestXrayAmount}</td>
                <td width='26%'>{$totalPatTestEcgCount} / {$totalPatTestEcgAmount}</td>
            </tr>  
                 ";
        }else{
            $LabTest = "
            <tr>
          <td width='22%'>Pat Visit</td>
          <td width='26%'>0 / 0</td>
          <td width='26%'>0 / 0</td>
          <td width='26%'>0 / 0</td>
      </tr> 
           ";
        }


        $LabTest1 = '';

        if($location_type != 'Habibia'){
                 $LabTest1 = "
                <tr>
                <td width='22%'>Lab(Self)</td>
                <td width='26%'>{$totaltestcount} / {$totaltestamount}</td>
                <td width='26%'>{$totalTestXrayCount} / {$totalTestXrayAmount}</td>
                <td width='26%'>{$totalTestEcgCount} / {$totalTestEcgAmount}</td>
            </tr>
             <tr>
                <td width='22%'>Lab(IP)</td>
                <td width='26%'>{$totalIpTestCount} / {$totalIpTestAmount}</td>
                <td width='26%'>{$totalIpTestXrayCount} / {$totalIpTestXrayAmount}</td>
                <td width='26%'>{$totalIpTestEcgCount} / {$totalIpTestEcgAmount}</td>
            </tr>
                <tr>
                <td width='22%' style='border-top:1px solid #FFFFFF;line-height:25px;'>Total</td>
                <td width='26%' style='border-top:1px solid #FFFFFF;line-height:25px;'>{$totalLabCount} / {$totalLabAmount}</td>
                <td width='26%' style='border-top:1px solid #FFFFFF;line-height:25px;'>{$totalXrayCount} / {$totalXrayAmount}</td>
                <td width='26%' style='border-top:1px solid #FFFFFF;line-height:25px;'>{$totalEcgCount} / {$totalEcgAmount}</td>
            </tr>
            <tr>
                <td width='30%' style='border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;line-height:25px;'>Today&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: </td>
                <td width='70%' style='border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;line-height:25px;' colspan='3'>{$totalAlltestamount}RS</td>
            </tr>
            <tr>
                <td width='30%' style='line-height:25px;'>Yesterday : </td>
                <td width='70%' style='line-height:25px;' colspan='3'>{$totalAlltestamountYest}RS</td>
            </tr>
                 ";
        }else{
            $LabTest1 = "
            <tr>
          <td width='22%'>Lab(Self)</td>
          <td width='26%'>0 / 0</td>
          <td width='26%'>0 / 0</td>
          <td width='26%'>0 / 0</td>
      </tr>
       <tr>
          <td width='22%'>Lab(IP)</td>
          <td width='26%'>0 / 0</td>
          <td width='26%'>0 / 0</td>
          <td width='26%'>0 / 0</td>
      </tr> 
       <tr>
        <td width='22%' style='border-top:1px solid #FFFFFF;line-height:25px;'>Total</td>
        <td width='26%' style='border-top:1px solid #FFFFFF;line-height:25px;'>0 / 0</td>
        <td width='26%' style='border-top:1px solid #FFFFFF;line-height:25px;'>0 / 0</td>
        <td width='26%' style='border-top:1px solid #FFFFFF;line-height:25px;'>0 / 0</td>
    </tr>
     <tr>
        <td width='30%' style='border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;line-height:25px;'>Today&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: </td>
        <td width='70%' style='border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;line-height:25px;' colspan='3'>0 RS</td>
    </tr>
    <tr>
        <td width='30%' style='line-height:25px;'>Yesterday : </td>
        <td width='70%' style='line-height:25px;' colspan='3'>0 RS</td>
    </tr>
           ";
        }

        $text = "
        <table width='100%' border='0'>
            <tr>
                <td width='25%'></td>
                <td width='25%'><u>LAB TEST</u></td>
                <td width='25%'><u>X-RAY</u></td>
                <td width='25%'><u>ECG</u></td>
            </tr>
           {$LabTest}
          {$LabTest1}
           
        
        </table>
        ";

        return $text;
    }

    function getAttendanceReportSummaryOld() {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn    = Zend_Registry::get('fn');
        $db    = Zend_Registry::get('db');

        $today    = date("Y-m-d");
        $firstDay = date("Y-m-01");
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $SQL = "
        SELECT e.employee_id
              ,e.first_name AS employee_name
              ,MAX(com.attendance_id) AS attendance_id
        FROM employee e
        LEFT JOIN
        (SELECT attendance_id
                ,employee_id
        FROM attendance
        WHERE record_date = '{$today}'
        ORDER BY attendance_id DESC
        ) AS com ON (com.employee_id = e.employee_id)
        WHERE e.site_id = {$cpSiteIdSession}
        AND (e.position = 'Nurse' OR e.position = 'LAB TECHNICIAN')
        AND e.status = 'Active'
        GROUP BY e.employee_id
        ORDER BY attendance_id DESC
        ";
        $rows = "";
        $totalOverAll = 0;
        $totalOverAllCount = 0;
        $result = $db->sql_query($SQL);
        while($row    = $db->sql_fetchrow($result)){

            $SQLPresent = "
            SELECT  TIME_FORMAT(a.time_in, '%H:%i') AS time_in
                   ,TIME_FORMAT(a.leave_time, '%H:%i') AS leave_time
                   ,TIME_FORMAT(a.time_in_shift2, '%H:%i') AS time_in_shift2
                   ,TIME_FORMAT(a.leave_time_shift2, '%H:%i') AS leave_time_shift2
            FROM `attendance` a
            WHERE a.employee_id = {$row['employee_id']}
            AND a.record_date = '{$today}'
            AND a.site_id = {$cpSiteIdSession}
            ORDER BY a.attendance_id DESC
            ";
            $resultPresent  = $db->sql_query($SQLPresent);
            $numRowsPresent = $db->sql_numrows($resultPresent);
            $rowPresent     = $db->sql_fetchrow($resultPresent);

            $SQLAbsent = "
            SELECT employee_id 
            FROM attendance
            WHERE employee_id = {$row['employee_id']}
            AND site_id = {$cpSiteIdSession}
            AND record_date BETWEEN '{$firstDay}' AND '{$today}'
            AND on_leave = 1
            ";
            $resultAbsent  = $db->sql_query($SQLAbsent);
            $numRowsAbsent = $db->sql_numrows($resultAbsent);

            $DayShiftTimes = "";
            if($rowPresent['time_in'] != "" & $rowPresent['leave_time'] != ""){
                $DayShiftTimes = "[{$rowPresent['time_in']} / {$rowPresent['leave_time']}]";
            }

            if($rowPresent['time_in'] != "" & $rowPresent['leave_time'] == ""){
                $DayShiftTimes = "[{$rowPresent['time_in']}]";
            }

            $NightShiftTimes = "";
            if($rowPresent['time_in_shift2'] != "" & $rowPresent['leave_time_shift2'] != ""){
                $NightShiftTimes = "[{$rowPresent['time_in_shift2']} / {$rowPresent['leave_time_shift2']}]";
            }

            if($rowPresent['time_in_shift2'] != "" & $rowPresent['leave_time_shift2'] == ""){
                $NightShiftTimes = "[{$rowPresent['time_in_shift2']}]";
            }

            $rows .= "
            <tr>
                <td width='34%'>{$row['employee_name']}({$numRowsAbsent})</td>
                <td width='33%'>{$DayShiftTimes}</td>
                <td width='33%'>{$NightShiftTimes}</td>
            </tr>
            ";
        }

        $text = "
        <div class='attendanceWidgetDashboardDiv'>
            <table width='100%' border='0'>
                <tr>
                    <td><u>STAFF</u></td>
                    <td><u>DAY SHIFT</u></td>
                    <td><u>NIGHT SHIFT</u></td>
                </tr>
                {$rows}
            </table>
        </div>
        ";

        return $text;
    }

    function getAttendanceReportSummary() {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn    = Zend_Registry::get('fn');
        $db    = Zend_Registry::get('db');

        $today    = date("Y-m-d");
        //$today    = date("2018-03-29");
        $firstDay = date("Y-m-01");
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        /*$SQL = "
        SELECT e.employee_id
              ,e.first_name AS employee_name
              ,MAX(com.attendance_id) AS attendance_id
        FROM employee e
        LEFT JOIN
        (SELECT attendance_id
                ,employee_id
        FROM attendance
        WHERE record_date = '{$today}'
        AND time_in != ''
        ORDER BY attendance_id DESC
        ) AS com ON (com.employee_id = e.employee_id)
        WHERE e.site_id = {$cpSiteIdSession}
        AND e.position = 'Nurse'
        AND e.status = 'Active'
        GROUP BY e.employee_id
        ORDER BY attendance_id DESC
        ";*/
        $SQL = "
        SELECT a.attendance_id
              ,a.employee_id
              ,e.first_name AS employee_name
        FROM attendance a
        LEFT JOIN (employee e) ON ( a.employee_id = e.employee_id )
        WHERE a.record_date = '{$today}'
        AND a.site_id = {$cpSiteIdSession}
        AND (a.time_in_day_shift != '' || time_in_double_shift_morning != '' 
            || time_in_double_shift_evening != '')
        AND e.position IN ('Nurse', 'LAB TECHNICIAN')
        AND e.status = 'Active'
        ORDER BY e.first_name
        ";
        $rows = "";
        $totalOverAll = 0;
        $totalOverAllCount = 0;
        $result = $db->sql_query($SQL);
        while($row    = $db->sql_fetchrow($result)){

            $SQLPresent = "
            SELECT  TIME_FORMAT(a.time_in_day_shift, '%H:%i') time_in_day_shift_formatted
                    ,TIME_FORMAT(a.leave_time_day_shift, '%H:%i') leave_time_day_shift_formatted
                    ,TIME_FORMAT(a.time_in_night_shift, '%H:%i') time_in_night_shift_formatted
                    ,TIME_FORMAT(a.leave_time_night_shift, '%H:%i') leave_time_night_shift_formatted
                    ,TIME_FORMAT(a.time_in_double_shift_morning, '%H:%i') time_in_double_shift_morning_formatted
                    ,TIME_FORMAT(a.leave_time_double_shift_morning, '%H:%i') leave_time_double_shift_morning_formatted
                    ,TIME_FORMAT(a.time_in_double_shift_evening, '%H:%i') time_in_double_shift_evening_formatted
                    ,TIME_FORMAT(a.leave_time_double_shift_evening, '%H:%i') leave_time_double_shift_evening_formatted
                    ,a.record_date
                    ,a.time_in_day_shift
                    ,a.leave_time_day_shift
                    ,a.time_in_night_shift
                    ,a.leave_time_night_shift
                    ,a.time_in_double_shift_morning
                    ,a.leave_time_double_shift_morning
                    ,a.time_in_double_shift_evening
                    ,a.leave_time_double_shift_evening
            FROM `attendance` a
            WHERE a.employee_id = {$row['employee_id']}
            AND a.record_date = '{$today}'
            AND a.site_id = {$cpSiteIdSession}
            ORDER BY a.attendance_id DESC
            ";
            $resultPresent  = $db->sql_query($SQLPresent);
            $numRowsPresent = $db->sql_numrows($resultPresent);
            $rowPresent     = $db->sql_fetchrow($resultPresent);

            $SQLAbsent = "
            SELECT employee_id 
            FROM attendance
            WHERE employee_id = {$row['employee_id']}
            AND site_id = {$cpSiteIdSession}
            AND record_date BETWEEN '{$firstDay}' AND '{$today}'
            AND on_leave = 1
            ";
            $resultAbsent  = $db->sql_query($SQLAbsent);
            $numRowsAbsent = $db->sql_numrows($resultAbsent);

            $DayShiftTimes = "";
            if($rowPresent['time_in_day_shift'] != "" & $rowPresent['leave_time_day_shift'] != ""){
                $DayShiftTimes = "{$rowPresent['time_in_day_shift_formatted']}-{$rowPresent['leave_time_day_shift_formatted']}";
            }

            if($rowPresent['time_in_day_shift'] != "" & $rowPresent['leave_time_day_shift'] == ""){
                $DayShiftTimes = "{$rowPresent['time_in_day_shift_formatted']}";
            }

            $DayShiftTimesMorn = "";
            $DayShiftTimesEveng = "";
            if($rowPresent['time_in_double_shift_morning'] != "" & $rowPresent['leave_time_double_shift_morning'] != ""){
                $DayShiftTimesMorn = "{$rowPresent['time_in_double_shift_morning_formatted']}";
            }

            if($rowPresent['time_in_double_shift_morning'] == "" & $rowPresent['leave_time_double_shift_morning'] == ""){
                $DayShiftTimesMorn = "{$rowPresent['time_in_double_shift_morning_formatted']}";
            }

            if($rowPresent['time_in_double_shift_evening'] != "" & $rowPresent['time_in_double_shift_evening'] == ""){
                $DayShiftTimesEveng = "/{$rowPresent['time_in_double_shift_evening_formatted']}}";
            }

            $DayShiftTimings = $DayShiftTimes.' '.$DayShiftTimesMorn.' '.$DayShiftTimesEveng;
            $DayShiftTimings = rtrim($DayShiftTimings, ' / ');

            $record_sign_in        = $rowPresent['time_in_day_shift'];
            $record_sign_out       = $rowPresent['leave_time_day_shift'];
            $record_created        = $rowPresent['record_date'];
            $time1                 = date("H:i", strtotime($record_sign_in));
            $time2                 = date("H:i", strtotime($record_sign_out));
            $record_created        = date("l", strtotime($record_created));
            $day                   = $record_created;
            list($hours, $minutes) = explode(':', $time1);
            $startTimestamp        = mktime($hours, $minutes);
            list($hours, $minutes) = explode(':', $time2);
            $endTimestamp          = mktime($hours, $minutes);
            $seconds               = $endTimestamp - $startTimestamp;
            $minutes               = ($seconds / 60) % 60;
            $hours                 = floor($seconds / (60 * 60));
            
            if($rowPresent['leave_time_day_shift'] != '00:00:00' && $rowPresent['leave_time_day_shift'] != ''){
                $total_time = sprintf("%02d", $hours). ":" .sprintf("%02d", $minutes);
            } else {
                $total_time = '';
            }

            $record_sign_in_day2  = $rowPresent['time_in_double_shift_morning'];
            $record_sign_out_day2 = $rowPresent['leave_time_double_shift_morning'];
            $record_created_day2  = $rowPresent['record_date'];
            $time1_day2           = date("H:i", strtotime($record_sign_in_day2));
            $time2_day2           = date("H:i", strtotime($record_sign_out_day2));
            $record_created_day2  = date("l", strtotime($record_created_day2));
            $day_day2             = $record_created_day2;
            list($hours_day2, $minutes_day2) = explode(':', $time1_day2);
            $startTimestamp_day2 = mktime($hours_day2, $minutes_day2);
            list($hours_day2, $minutes_day2) = explode(':', $time2_day2);
            $endTimestamp_day2 = mktime($hours_day2, $minutes_day2);
            $seconds_day2      = $endTimestamp_day2 - $startTimestamp_day2;
            $minutes_day2      = ($seconds_day2 / 60) % 60;
            $hours_day2        = floor($seconds_day2 / (60 * 60));
            
            if($rowPresent['leave_time_double_shift_morning'] != '00:00:00' && $rowPresent['leave_time_double_shift_morning'] != ''){
                $total_time_day2 = sprintf("%02d", $hours_day2). ":" .sprintf("%02d", $minutes_day2);
            } else {
                $total_time_day2 = '';
            }

            $total_time = $this->sum_the_time($total_time, $total_time_day2);

            $rows .= "
            <tr>
                <td width='34%'>{$row['employee_name']}</td>
                <td width='30%'>{$DayShiftTimings}</td>
                <td width='16%'>{$total_time}</td>
                <td class='txtCenter' width='20%'>{$numRowsAbsent}</td>
            </tr>
            ";
        }

        $rowsNight = "";

        /*$SQLNight = "
        SELECT e.employee_id
              ,e.first_name AS employee_name
              ,MAX(com.attendance_id) AS attendance_id
        FROM employee e
        LEFT JOIN
        (SELECT attendance_id
                ,employee_id
        FROM attendance
        WHERE record_date = '{$today}'
        AND time_in_shift2 != ''
        ORDER BY attendance_id DESC
        ) AS com ON (com.employee_id = e.employee_id)
        WHERE e.site_id = {$cpSiteIdSession}
        AND e.position = 'Nurse'
        AND e.status = 'Active'
        AND e.time_in_night > 0
        AND e.time_out_night > 0
        GROUP BY e.employee_id
        ORDER BY attendance_id DESC
        "; */
        $SQLNight = "
        SELECT a.attendance_id
              ,a.employee_id
              ,e.first_name AS employee_name
        FROM attendance a
        LEFT JOIN (employee e) ON ( a.employee_id = e.employee_id )
        WHERE a.record_date = '{$today}'
        AND a.time_in_night_shift != ''
        AND a.site_id = {$cpSiteIdSession}
        AND e.position IN ('Nurse', 'LAB TECHNICIAN')
        AND e.status = 'Active'
        ORDER BY e.first_name
        ";
        $resultNight = $db->sql_query($SQLNight);
        while($rowNight    = $db->sql_fetchrow($resultNight)){

            $SQLPresent = "
            SELECT  TIME_FORMAT(a.time_in_night_shift, '%H:%i') AS time_in_night_shift
                   ,TIME_FORMAT(a.leave_time_night_shift, '%H:%i') AS leave_time_night_shift
                   ,a.record_date
            FROM `attendance` a
            WHERE a.employee_id = {$rowNight['employee_id']}
            AND a.record_date = '{$today}'
            AND a.site_id = {$cpSiteIdSession}
            ORDER BY a.attendance_id DESC
            ";
            $resultPresent  = $db->sql_query($SQLPresent);
            $numRowsPresent = $db->sql_numrows($resultPresent);
            $rowPresent     = $db->sql_fetchrow($resultPresent);

            $SQLAbsent = "
            SELECT employee_id 
            FROM attendance
            WHERE employee_id = {$rowNight['employee_id']}
            AND site_id = {$cpSiteIdSession}
            AND record_date BETWEEN '{$firstDay}' AND '{$today}'
            AND on_leave = 1
            ";
            $resultAbsent  = $db->sql_query($SQLAbsent);
            $numRowsAbsent = $db->sql_numrows($resultAbsent);

            $NightShiftTimes = "";
            if($rowPresent['time_in_night_shift'] != "" & $rowPresent['leave_time_night_shift'] != ""){
                $NightShiftTimes = "{$rowPresent['time_in_night_shift']} / {$rowPresent['leave_time_night_shift']}";
            }

            if($rowPresent['time_in_night_shift'] != "" & $rowPresent['leave_time_night_shift'] == ""){
                $NightShiftTimes = "{$rowPresent['time_in_night_shift']}";
            }

            $record_sign_in        = $rowPresent['time_in_night_shift'];
            $record_sign_out       = $rowPresent['leave_time_night_shift'];
            $record_created        = $rowPresent['record_date'];
            $time1                 = date("H:i", strtotime($record_sign_in));
            $time2                 = date("H:i", strtotime($record_sign_out));
            $record_created        = date("l", strtotime($record_created));
            $day                   = $record_created;
            list($hours, $minutes) = explode(':', $time1);
            $startTimestamp        = mktime($hours, $minutes);
            list($hours, $minutes) = explode(':', $time2);
            $endTimestamp          = mktime($hours, $minutes);
            $seconds               = $endTimestamp - $startTimestamp;
            $minutes               = ($seconds / 60) % 60;
            $hours                 = floor($seconds / (60 * 60));
            
            if($rowPresent['leave_time_night_shift'] != '00:00:00' && $rowPresent['leave_time_night_shift'] != ''){
                $total_time = sprintf("%02d", $hours). ":" .sprintf("%02d", $minutes);
            } else {
                $total_time = '';
            }

            $rowsNight .= "
            <tr>
                <td width='34%'>{$rowNight['employee_name']}</td>
                <td width='30%'>{$NightShiftTimes}</td>
                <td width='16%'>{$total_time}</td>
                <td class='txtCenter' width='20%'>{$numRowsAbsent}</td>
            </tr>
            ";
        }

        $text = "
        <div class='attendanceWidgetDashboardDiv'>
            <table width='100%' border='0'>
                <tr>
                    <td><u>NAME</u></td>
                    <td><u>DAY SHIFT</u></td>
                    <td><u>HRS</u></td>
                    <td><u>ABSENT</u></td>
                </tr>
                {$rows}
                <tr>
                    <td style='border-top:1px solid #FFFFFF;'><u>NAME</u></td>
                    <td style='border-top:1px solid #FFFFFF;'><u>NIGHT SHIFT</u></td>
                    <td style='border-top:1px solid #FFFFFF;'><u>HRS</u></td>
                    <td style='border-top:1px solid #FFFFFF;'><u>LEAVE DAYS</u></td>
                </tr>
                {$rowsNight}
            </table>
        </div>
        ";

        return $text;
    }


    function sum_the_time($time1, $time2) {
        $times = array($time1, $time2);
        $seconds = 0;

        foreach ($times as $time) {

            if($time != ""){
                list($hour,$minute) = explode(':', $time);
                $seconds += $hour*3600;
                $seconds += $minute*60;
            }
        }

        $hours    = floor($seconds/3600);
        $seconds -= $hours*3600;
        $minutes  = floor($seconds/60);

        if($minutes <= 9) {
            $minutes = "0".$minutes;
        }

        if($hours <= 9) {
            $hours = "0".$hours;
        }

        $total_hrs = $hours.':'.$minutes;

        if($hours == "00" && $minutes == "00"){
            $total_hrs = "";
        }

        return "{$total_hrs}";
    }

    function getPharmacyDailySales($day = '', $site_id) {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        if($day == ""){
            $day = $fn->getReqparam('day');
        }

        $rows = '';

        if($day == 'Today'){
            $creation_date = date("Y-m-d");
            $date = "AND date = '{$creation_date}'";
        }else if($day == 'Yesterday'){
            $yesterday     = date("Y-m-d", strtotime("yesterday"));
            $date = "AND date = '{$yesterday}'";
        }

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $appendSqlSite = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlSite = "AND site_id = {$site_id}";
        }

        $SQL = "
        SELECT *
        FROM pharma_daily_sales
        WHERE date != ''
        {$date}
        {$appendSqlSite}
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);

        $appendSql = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "AND inv.site_id = {$site_id}";
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
        AND srh.date = '{$row['date']}'
        {$appendSql}
        ";
        $resultSalesReturn = $db->sql_query($SQLSalesReturn);
        $recSalesReturn    = $db->sql_fetchrow($resultSalesReturn);
        $salesReturn       =  $recSalesReturn['sales_return_amount'];

        $SQLCollection = "
        SELECT SUM(invoice_amount) AS total_amount
        FROM `invoice`
        WHERE status != 'Cancelled'
        AND invoice_type = 'POS'
        AND invoice_date = '{$row['date']}'
        {$appendSqlSite}
        ";
        $resultCollection = $db->sql_query($SQLCollection);
        $recCollection    = $db->sql_fetchrow($resultCollection);

        $totalCollection = $recCollection['total_amount'];
        $totalAmount = $totalCollection + $row['excess_amount'] - $salesReturn;

        $rows = "
        {$totalAmount}
        ";

        return $rows;
    }

    function getOverallRevenueSummary() {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $rows = '';
        $appendSql = '';
        $location_type  = $fn->getReqParam('location_type');

        $today = date('Y-m-d');
        $yesterday     = date("Y-m-d", strtotime("yesterday"));
        $overallDay = $fn->getReqparam('overallDay');
        $siteIdCon = '';
        if($location_type == 'Habibia'){
            $siteIdCon = "AND site_id = 1";
        }

        $SQLSite = "
        SELECT title 
               ,site_id
        FROM site
        WHERE published = 1
        {$siteIdCon}
        ";
        $resultSite = $db->sql_query($SQLSite);
        $sum_amount = 0;
        $overallVisitAmount = 0;
        $overallVisitOpAmount = 0;
        $overallVisitIpAmount = 0;
        $overallLabAmount   = 0;
        $overallTheatreAmount=0;
        $overallPharAmount  = 0;
        while($rowSite    = $db->sql_fetchrow($resultSite)){
            $month          = date('m');
            $year           = date('Y');
            if($overallDay == 'Overall'){
                $start_date = $year . '-' . $month . '-' . '01';
                $end_date = date('Y-m-d');                
            } else if($overallDay == 'Yesterday'){
                $start_date = $yesterday;
                $end_date = $yesterday;                
            } else {
                $start_date = $today;
                $end_date = $today;
            }

            /*******************************PAT VISIT***********************************/

            $SQLSub = "
            SELECT COUNT(ev.patient_visit_id) AS patient_count
                  ,SUM(ev.consultation_fees) AS fees_count
                  ,SUM(ev.consultation_fees - ev.fees_commission) AS fees_commission_count
                  ,SUM(ev.fees_commission) AS fees_commission_amount
                  ,e.category
            FROM employee_visit ev
            LEFT JOIN employee e ON (e.employee_id = ev.employee_id)
            LEFT JOIN patient_visit pv ON (pv.patient_visit_id = ev.patient_visit_id)
            WHERE (DATE_FORMAT(pv.check_up_date, '%Y-%m-%d') >= '{$start_date}' AND DATE_FORMAT(pv.check_up_date, '%Y-%m-%d') <= '{$end_date}')
              AND e.first_name != ''
              AND pv.status != 'Cancelled'
              AND pv.site_id = {$rowSite['site_id']}
              GROUP BY e.category
            ";
            $sum_amount       = 0;
            $sum_amount_comm  = 0;
            $case_count       = 0;
            $consultantComsn  = 0;
            $resultSub = $db->sql_query($SQLSub);
            while ($rowSub = $db->sql_fetchrow($resultSub)) {
                if($rowSub['category'] == 'Consultant'){
                    $consultantComsn += $rowSub['fees_commission_amount'];
                } else {
                    $sum_amount += $rowSub['fees_count'];                    
                }

                $case_count += $rowSub['patient_count'];
            }

            $overallCaseVisitAmount = $sum_amount + $consultantComsn;

            $SQLEmpVisit = "
            SELECT SUM(ev.consultation_fees + pv.amount + pv.nursing_fees + pv.other_fees + pv.theatre_charges) AS fees_count
                  ,e.category
            FROM employee_in_patient ev
            LEFT JOIN employee e ON (e.employee_id = ev.employee_id)
            LEFT JOIN in_patient pv ON (pv.in_patient_id = ev.in_patient_id)
            WHERE e.first_name != ''
              AND (pv.date_admitted >= '{$start_date}' AND pv.date_admitted <= '{$end_date}')            
              AND pv.status != 'Cancelled'
              AND pv.site_id = {$rowSite['site_id']}
              GROUP BY e.category
            ";
            $resultEmpVisit = $db->sql_query($SQLEmpVisit);
            $sum_amount_ip = 0;
            while ($rowEmpVisit = $db->sql_fetchrow($resultEmpVisit)) {
                $sum_amount_ip += $rowEmpVisit['fees_count'];                    
            }

            $sum_amountIp = '';
            if($location_type != 'Habibia'){
                if($sum_amount_ip > 0){
                    $sum_amountIp = number_format($sum_amount_ip, 0);
                    $sum_amountIp = " / {$sum_amountIp}";
                }

            }else{
                $sum_amountIp = number_format($sum_amount_ip, 0);

            }
            

            /*******************************LAB***********************************/
            $SQLPatLabTest = "
            SELECT COUNT(m.medical_test_id) AS count
                   ,m.title
                   ,mt.category
                   ,SUM(m.fees) AS fees
                   ,SUM(m.lab_supplier_fees) AS lab_supplier_fees
            FROM medical_test_visit m
            LEFT JOIN (medical_test mt) ON (mt.medical_test_id = m.medical_test_id)
            LEFT JOIN (patient_visit pv) ON (pv.patient_visit_id = m.patient_visit_id)
            WHERE pv.status != 'Cancelled'
              AND (DATE_FORMAT(m.creation_date, '%Y-%m-%d') >= '{$start_date}' AND DATE_FORMAT(m.creation_date, '%Y-%m-%d') <= '{$end_date}')
              AND mt.category != 'Vaccination'
              AND pv.site_id = {$rowSite['site_id']}
            GROUP BY m.title
            ";
            $resultPatLabTest = $db->sql_query($SQLPatLabTest);
            $sum_amount_lab_pv = 0;
            while ($rowPatLabTest = $db->sql_fetchrow($resultPatLabTest)) {
                $sum_amount_lab_pv += $rowPatLabTest['fees'] - $rowPatLabTest['lab_supplier_fees'];   
            }

            $SQLLabTest = "
            SELECT COUNT(m.medical_test_id) AS count
                   ,m.title
                   ,mt.category
                   ,SUM(m.fees) AS fees
                   ,SUM(m.lab_supplier_fees) AS lab_supplier_fees
            FROM medical_test_lab m
            LEFT JOIN (medical_test mt) ON (mt.medical_test_id = m.medical_test_id)
            LEFT JOIN (lab_test lt) ON (lt.lab_test_id = m.lab_test_id)
            WHERE lt.status != 'Cancelled'
              AND (DATE_FORMAT(m.creation_date, '%Y-%m-%d') >= '{$start_date}' AND DATE_FORMAT(m.creation_date, '%Y-%m-%d') <= '{$end_date}')
              AND lt.site_id = {$rowSite['site_id']}
            GROUP BY m.title
            ";
            $resultLabTest = $db->sql_query($SQLLabTest);
            $sum_amount_lab_test = 0;
            while ($rowLabTest = $db->sql_fetchrow($resultLabTest)) {
                $sum_amount_lab_test += $rowLabTest['fees'] - $rowLabTest['lab_supplier_fees'];       
            }

            $SQLInPatLabTest = "
            SELECT COUNT(m.medical_test_id) AS count
                   ,m.title
                   ,mt.category
                   ,SUM(m.fees) AS fees
                   ,SUM(m.lab_supplier_fees) AS lab_supplier_fees
                   ,ip.theatre_charges
            FROM medical_test_in_patient m
            LEFT JOIN (medical_test mt) ON (mt.medical_test_id = m.medical_test_id)
            LEFT JOIN (in_patient ip) ON (ip.in_patient_id = m.in_patient_id)
            WHERE ip.status != 'Cancelled'
              AND (DATE_FORMAT(m.creation_date, '%Y-%m-%d') >= '{$start_date}' AND DATE_FORMAT(m.creation_date, '%Y-%m-%d') <= '{$end_date}')
              AND ip.site_id = {$rowSite['site_id']}
            GROUP BY m.title
            ";
            $resultInPatLabTest = $db->sql_query($SQLInPatLabTest);
            $sum_amount_lab_ip = 0;
            while ($rowIpLabTest = $db->sql_fetchrow($resultInPatLabTest)) {
                $sum_amount_lab_ip += $rowIpLabTest['fees'] - $rowIpLabTest['lab_supplier_fees'];  
            }


            $sum_amount_lab = $sum_amount_lab_ip + $sum_amount_lab_test + $sum_amount_lab_pv;


            $SQLInPatTheatreTest = "
            SELECT ip.theatre_charges
            FROM in_patient ip
            WHERE ip.status != 'Cancelled'
              AND (DATE_FORMAT(ip.date_admitted, '%Y-%m-%d') >= '{$start_date}' AND DATE_FORMAT(ip.date_admitted, '%Y-%m-%d') <= '{$end_date}')
              AND ip.site_id = {$rowSite['site_id']}
           
            ";
            $resultInPatTheatreTest = $db->sql_query($SQLInPatTheatreTest);
            $theater_charge_ip = 0;
            while ($rowIpTheatreTest = $db->sql_fetchrow($resultInPatTheatreTest)) {
                $theater_charge_ip += $rowIpTheatreTest['theatre_charges'];
            }

            $theater_charge= $theater_charge_ip;
            /*******************************PHARMACY***********************************/
            $SQLPd = "
            SELECT *
            FROM pharma_daily_sales
            WHERE date != ''
            AND (date >= '{$start_date}' AND date <= '{$end_date}')            
            AND site_id = {$rowSite['site_id']}
            ";
            $resultPd = $db->sql_query($SQLPd);
            $rowPd = $db->sql_fetchrow($resultPd);

            $SQLSalesReturn = "
            SELECT 
                SUM(srh.qty_return * srh.price) as sales_return_amount 
            FROM sales_return_history srh
            LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
            LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
            LEFT JOIN (`order` o) ON (o.order_id = srh.order_id)
            WHERE o.order_status != 'Cancelled'
            AND (srh.date >= '{$start_date}' AND srh.date <= '{$end_date}')            
            AND o.order_type = 'POS'
            AND inv.site_id = {$rowSite['site_id']}
            ";
            $resultSalesReturn = $db->sql_query($SQLSalesReturn);
            $recSalesReturn    = $db->sql_fetchrow($resultSalesReturn);
            $salesReturn       =  $recSalesReturn['sales_return_amount'];

            $SQLCollection = "
            SELECT SUM(invoice_amount) AS total_amount
            FROM `invoice`
            WHERE status != 'Cancelled'
            AND (invoice_date >= '{$start_date}' AND invoice_date <= '{$end_date}')            
            AND invoice_type = 'POS'
            AND site_id = {$rowSite['site_id']}
            ";
            $resultCollection = $db->sql_query($SQLCollection);
            $recCollection    = $db->sql_fetchrow($resultCollection);

            $totalCollection = $recCollection['total_amount'];
            $totalAmount = round($totalCollection + $rowPd['excess_amount'] - $salesReturn);

            $overallVisitAmount += $overallCaseVisitAmount + $sum_amount_ip;
            $overallVisitOpAmount += $overallCaseVisitAmount;
            $overallVisitIpAmount += $sum_amount_ip;
            $overallLabAmount   += $sum_amount_lab;
            $overallTheatreAmount   += $theater_charge;
            $overallPharAmount  += $totalAmount;

            $overallCaseVisitAmount = number_format($overallCaseVisitAmount, 0);
            $sum_amount_lab         = number_format($sum_amount_lab, 0);
            $theater_charge         = number_format($theater_charge, 0);
            $totalAmount            = number_format($totalAmount, 0);
            $LabTest1 = '';

            if($location_type != 'Habibia'){
                $LabTest1 = '<td align="right">'.$sum_amount_lab.'</td>
                        <td align="right">'.$totalAmount.'</td>';
            }else{
                $LabTest1 = "";
            }
            $VisitOp = '';
            if($location_type != 'Habibia'){
                $VisitOp = '<td align="right">'.$overallCaseVisitAmount.''.$sum_amountIp.'</td>';
            }else{
                $VisitOp = '<td align="right">'.$overallCaseVisitAmount.'</td>
                <td align="right">'.$sum_amountIp.'</td>';
            }

            $rows .= '<tr>
                        <td width="5%">'.substr($rowSite['title'],0, 3).'</td> 
                        '.$VisitOp.'
                        <td align="right">'.$theater_charge.'</td>
                        '.$LabTest1.'
                     </tr>';
        }

        $todayDate = date('d');
        if($location_type != 'Habibia'){
        $overall = $overallVisitAmount + $overallLabAmount + $overallPharAmount;
        }else{
            $overall = $overallVisitAmount; 
        }
        $avg = $overall / $todayDate;
        $overallVisitAmount = number_format($overallVisitAmount, 0);
        $overallLabAmount = number_format($overallLabAmount, 0);
        $overallTheatreAmount = number_format($overallTheatreAmount, 0);
        $overallPharAmount = number_format($overallPharAmount, 0);
        $overall = number_format($overall, 0);
        $avg = number_format($avg, 0);
        if($overallDay == 'Overall'){
            $showAvg = "AVG / DAY : {$avg}";
        } else{
            $showAvg = "<a href='#' class='showOverallRevenue' data-location-type='{$location_type}' >Overall Revenue</a>";            
        }


        $LabTest = '';

        if($location_type != 'Habibia'){
                 $LabTest = " <td align='right' style='border-top:1px solid #FFFFFF;'><u>Lab</u></td>
                <td align='right' style='border-top:1px solid #FFFFFF;'><u>Pharmacy</u></td>";
        }else{
            $LabTest = " ";
        }


        $LabTest2 = '';

        if($location_type != 'Habibia'){
            $LabTest2 = " <td align='right' style='line-height:30px;border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;'>{$overallLabAmount}</td>
                <td align='right' style='line-height:30px;border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;'>{$overallPharAmount}</td>";

        }else{
            $LabTest2="";
        }

        $VisitIp2 = '';

        if($location_type != 'Habibia'){
            $VisitIp2 = "<td align='right' style='border-top:1px solid #FFFFFF;'><u>Visit (OP/IP)</u></td>";

        }else{
            $VisitIp2="<td align='right' style='border-top:1px solid #FFFFFF;'><u>Visit (OP)</u></td>
            <td align='right' style='border-top:1px solid #FFFFFF;'><u>Visit (IP)</u></td>";
        }

        $TotalVisitIPOP = '';

        if($location_type != 'Habibia'){
            $TotalVisitIPOP = "<td align='right' style='line-height:30px;border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;'>{$overallVisitAmount}</td>";

        }else{
            $TotalVisitIPOP="<td align='right' style='line-height:30px;border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;'>{$overallVisitOpAmount}</td>
            <td align='right' style='line-height:30px;border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;'>{$overallVisitIpAmount}</td>";
        }

        $text = "
        <table cellpadding='5' width='100%'>
            <tr>
                <td style='border-top:1px solid #FFFFFF;'></td>
                {$VisitIp2}
                <td align='right' style='border-top:1px solid #FFFFFF;'><u>Theatre</u></td>
                {$LabTest}
            </tr>
            {$rows}
            <tr>
                <td style='line-height:30px;border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;'>Tot</td>
                {$TotalVisitIPOP}
                <td align='right' style='line-height:30px;border-top:1px solid #FFFFFF;border-bottom:1px solid #FFFFFF;'>{$overallTheatreAmount}</td>
               {$LabTest2}
            </tr>
            <tr>
                <td colspan='2'>OVERALL : {$overall}</td>
                <td colspan='2' align='right'>{$showAvg}</td>
            </tr>
        </table>
        ";

        return $text;
    }
}
