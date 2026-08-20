<?
class CPL_Admin_Widgets_Hms_BalanceSheetPharmacyReport_View extends CP_Common_Lib_WidgetViewAbstract
{
    //==================================================================//
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');

        $month        = date('m');
        $year         = date('Y');
        $start_date = '01-'.$month.'-'.$year;
        $end_date = date("d-m-Y", strtotime("yesterday"));
        
        $balTillYesterday = $this->getBalTillYesterday();
        $yesterdaySales = $this->getYesterdaySales();

        $text = "
        <div class='float_left mr20'><h2>Balance Sheet Pharmacy Report</h2></div>
        <div class='float_left ml20'><h2>Balance Till Yesterday ({$start_date} to {$end_date}) - <b>{$balTillYesterday}</b></h2></div>
        <div class='float_left ml20'><h2>Yesterday Sales - <b>{$yesterdaySales}</b></h2></div>
		<div class = 'tableOuter scroll-pane'>
			<table class='thinlist' width='100%'>
				<thead>
					<tr>
						<th>Income</th>
						<th>Expense</th>
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

    function getRowsHTML() {
        $fn = Zend_Registry::get('fn');
        $db    = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $rows = '';

        $total_amount_pos       = 0;
        $totaltestamount          = 0;
        $totaltestamount1         = 0;
        $totaltestamount2         = 0;
        $totalOverAllLabtest      = 0;
        $totalOverAllinPatient    = 0;
        $totalAllIPAdminCharges   = 0;
        $totalAllIPTheatreCharges = 0;

        $monthValAppendSql  = '';
        $yearValAppendSql   = '';
        $startDateAppendSql = '';

        $start_date   = $fn->getReqParam('start_date');
        $end_date     = $fn->getReqParam('end_date');
        $monthVal     = $fn->getReqParam('month');
        $yearVal      = $fn->getReqParam('year');
        $month        = date('m');
        $year         = date('Y');
        $current_date = date('Y-m-d');

        if ($start_date != '' && $end_date != '') {
            $startDateAppendSql = "AND p.date >= '{$start_date}' AND p.date <= '{$end_date}'";
        } else if ($monthVal == '' && $yearVal == ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $end_date   = $year . '-' . $month . '-' . '31';
            $startDateAppendSql = "AND p.date >= '{$start_date}' AND p.date <= '{$end_date}'";
        } else if ($monthVal != '' && $yearVal != ''){
            $monthValAppendSql = "AND DATE_FORMAT(p.date, '%m') = '{$monthVal}'" ;
            $yearValAppendSql  = "AND DATE_FORMAT(p.date, '%Y') = '{$yearVal}'" ;
        }

        $appendSql = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "p.site_id = {$cpSiteIdSession}";
        }

        /*
        $SQLSub = "
        SELECT SUM(inv.invoice_amount) AS invoice_amount
        FROM invoice inv
        WHERE inv.status != 'Cancelled'
          AND inv.invoice_type = 'POS'
          {$startDateAppendSql}
          {$monthValAppendSql}
          {$yearValAppendSql}
          {$appendSql}
        ";
        */
        $SQLSub = "
        SELECT p.*
        FROM pharma_daily_sales p
        WHERE {$appendSql}
          {$startDateAppendSql}
          {$monthValAppendSql}
          {$yearValAppendSql}
        ";
        $resultSub = $db->sql_query($SQLSub);
        while ($rowSub = $db->sql_fetchrow($resultSub)) {
            $appendSqlInvM = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSqlInvM = "AND site_id = {$cpSiteIdSession}";
            }

            $SQLCollection = "
            SELECT SUM(invoice_amount) AS total_amount
            FROM `invoice`
            WHERE status != 'Cancelled'
            AND invoice_type = 'POS'
            AND invoice_date = '{$rowSub['date']}'
            {$appendSqlInvM}
            ";
            $resultCollection = $db->sql_query($SQLCollection);
            $recCollection    = $db->sql_fetchrow($resultCollection);

            $appendSqlInv = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSqlInv = "AND inv.site_id = {$cpSiteIdSession}";
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
            {$appendSqlInv}
            ";
            $resultSalesReturn = $db->sql_query($SQLSalesReturn);
            $recSalesReturn    = $db->sql_fetchrow($resultSalesReturn);
            $salesReturn       =  $recSalesReturn['sales_return_amount'];

            if($rowSub['date'] < '2019-04-05'){
                $totalCollection = $rowSub['sales_amount'];
            } else {
                $totalCollection = $recCollection['total_amount'];            
            }

            $totalAmount = $totalCollection + $rowSub['excess_amount'] - $salesReturn;

            $total_amount_pos += $totalAmount;
        }

        //Expense related codes// 
        $sqlgroup = "
        SELECT expense_group_id 
              ,title
        FROM expense_group
        WHERE title = 'PHARMACY'
        ";
        $resultgroup = $db->sql_query($sqlgroup);
        $expense_group = '';
        $amount = 0;
        $expense_amount ='';
        $overAllExpense1 = 0;
        while ($rowgroup    = $db->sql_fetchrow($resultgroup)) {
            $appendSqlSite = '';
            $AppendSource  = '';
            $source        = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSqlSite = "AND e.site_id = {$cpSiteIdSession}";

                if($cpSiteIdSession == 1){
                    $source = 'Hab Pharm Income';
                    $AppendSource = "AND e.source = '{$source}'";
                }
                if($cpSiteIdSession == 2){
                    $source = 'Cres Pharm Income';
                    $AppendSource = "AND e.source = '{$source}'";
                }
                if($cpSiteIdSession == 3){
                    $source = '';
                    $AppendSource = "";
                }
            }

            $startDateAppendSql = '';
            $monthValAppendSql = '';
            $yearValAppendSql = '';

            if ($start_date != '' && $end_date != '') {
                $startDateAppendSql = "AND e.date >= '{$start_date}' AND e.date <= '{$current_date}'";
            } else if ($monthVal == '' && $yearVal == ''){
                $start_date = $year . '-' . $month . '-' . '01';
                $end_date = $year . '-' . $month . '-' . '31';
                $startDateAppendSql = "AND e.date >= '{$start_date}' AND e.date <= '{$end_date}'";
            } else if ($monthVal != '' && $yearVal != ''){
                $monthValAppendSql = "AND DATE_FORMAT(e.date, '%m') = '{$monthVal}'" ;
                $yearValAppendSql  = "AND DATE_FORMAT(e.date, '%Y') = '{$yearVal}'" ;
            }

            $sqlexp = "
            SELECT SUM(e.amount) AS amount
                  ,e.group
                  ,e.source
                  ,es.title AS sub_title
            FROM expense e
            LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
            WHERE e.group = {$rowgroup['expense_group_id']}
            {$AppendSource}
            {$appendSqlSite}
            {$startDateAppendSql}
            {$monthValAppendSql}
            {$yearValAppendSql}
            GROUP BY e.group
            ";
            $resultexp = $db->sql_query($sqlexp);
            $amount = 0;
            while ($rowexp = $db->sql_fetchrow($resultexp)) {
                $amount += $rowexp['amount'];
            }
            $amountFormat = number_format($amount, 2);

            $sqlexp1 = "
            SELECT SUM(e.amount) AS amount
                  ,e.group
                  ,es.title AS sub_title
                  ,e.source
            FROM expense e
            LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
            WHERE e.group = {$rowgroup['expense_group_id']}
            {$AppendSource}
            {$appendSqlSite}
            {$startDateAppendSql}
            {$monthValAppendSql}
            {$yearValAppendSql}
            GROUP BY es.expense_sub_group_id
            ORDER BY es.title ASC
            ";
            $resultexp1 = $db->sql_query($sqlexp1);
            $subtitle = '';
            while ($rowexp1 = $db->sql_fetchrow($resultexp1)) {
                $sqlexpOverall = "
                SELECT SUM(e.amount) AS amount
                      ,e.group
                      ,e.source
                      ,es.title AS sub_title
                FROM expense e
                LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
                WHERE e.group = {$rowexp1['group']}
                {$appendSqlSite}
                {$startDateAppendSql}
                {$monthValAppendSql}
                {$yearValAppendSql}
                AND es.title = '{$rowexp1['sub_title']}'
                ";
                $resultexpOverall    = $db->sql_query($sqlexpOverall);
                $amountOverall       = 0;
                $rowexpOverall       = $db->sql_fetchrow($resultexpOverall);
                $amountOverall       = $rowexpOverall['amount'];
                $amountOverallFormat = number_format($amountOverall, 2);

                $sqlexpSubDetail = "
                SELECT e.amount
                      ,e.group
                      ,es.title AS sub_title
                      ,e.description
                      ,e.source
                      ,e.date
                FROM expense e
                LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
                WHERE e.group = {$rowexp1['group']}
                AND e.source = '{$rowexp1['source']}'
                {$appendSqlSite}
                {$startDateAppendSql}
                {$monthValAppendSql}
                {$yearValAppendSql}
                AND es.title = '{$rowexp1['sub_title']}'
                ";
                $resultexpSubDetail = $db->sql_query($sqlexpSubDetail);
                $subHeadtitle = '';
                while ($rowexpSubDetail = $db->sql_fetchrow($resultexpSubDetail)) {
                    $date = $fn->getCPDate($rowexpSubDetail['date'],"d-m-Y");

                    $subHeadtitle .= "
                    <tr>
                        <td>{$date}</td>
                        <td>{$rowexpSubDetail['sub_title']}</td>
                        <td>{$rowexpSubDetail['description']}</td>
                        <td>{$rowexpSubDetail['source']}</td>
                        <td align='right'>{$rowexpSubDetail['amount']}</td>
                    </tr>";
                }

                $subtitle .= "
                <tr>
                    <td class='expenseDetailsSubHead'>
                        <div class='expenseSubHeadDetails'>+ {$rowexp1['sub_title']}</div>
                        <div class='subTitlesWithoutGroup'><table>{$subHeadtitle}</table></div>
                    </td>
                    <td align='right'>{$rowexp1['amount']}</td>
                    <td>[Overall Amount: {$amountOverallFormat}]</td>
                </tr>";
            }
            $expense_group .= "
            <table width=100%>
                <tr>
                    <td width = 85% class='expenseDetailsHead'>
                    <div class='expenseDetails'>+ {$rowgroup['title']}</div>
                    <div class='subTitles'><table>{$subtitle}</table></div>
                    </td>
                    <td width = 15% align='right'>
                    {$amountFormat}
                    </td>
                </tr>
            </table>
            "; 

            $overAllExpense1 += $amount;
        }

        /* EXPENSE RECORDS FROM PHARMACY SOURCE */
        $source = '';
        if($cpSiteIdSession == 1){
            $source = 'Hab Pharm Income';
        } else if($cpSiteIdSession == 2){
            $source = 'Cres Pharm Income';
        }
        $overAllExpense2 = 0;
        if($source != ''){
            $appendSqlSite = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSqlSite = "AND e.site_id = {$cpSiteIdSession}";
            }

            $startDateAppendSql = '';
            $monthValAppendSql = '';
            $yearValAppendSql = '';

            if ($start_date != '' && $end_date != '') {
                $startDateAppendSql = "AND e.date >= '{$start_date}' AND e.date <= '{$current_date}'";
            } else if ($monthVal == '' && $yearVal == ''){
                $start_date = $year . '-' . $month . '-' . '01';
                $end_date = $year . '-' . $month . '-' . '31';
                $startDateAppendSql = "AND e.date >= '{$start_date}' AND e.date <= '{$end_date}'";
            } else if ($monthVal != '' && $yearVal != ''){
                $monthValAppendSql = "AND DATE_FORMAT(e.date, '%m') = '{$monthVal}'" ;
                $yearValAppendSql  = "AND DATE_FORMAT(e.date, '%Y') = '{$yearVal}'" ;
            }

            $sqlexpHead = "
            SELECT e.source
                  ,eg.title AS group_title
                  ,eg.expense_group_id
            FROM expense e
            LEFT JOIN expense_group eg ON (eg.expense_group_id = e.group)
            WHERE e.source = '{$source}'
              AND eg.title != 'PHARMACY'
            {$appendSqlSite}
            {$startDateAppendSql}
            {$monthValAppendSql}
            {$yearValAppendSql}
            GROUP BY e.group
            ";
            $resultexpHead = $db->sql_query($sqlexpHead);
            while ($rowexpHead = $db->sql_fetchrow($resultexpHead)) {
                $sqlexp = "
                SELECT SUM(e.amount) AS amount
                      ,e.group
                      ,es.title AS sub_title
                FROM expense e
                LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
                WHERE e.group = {$rowexpHead['expense_group_id']}
                  AND e.source = '{$source}'
                {$appendSqlSite}
                {$startDateAppendSql}
                {$monthValAppendSql}
                {$yearValAppendSql}
                GROUP BY e.group
                ";
                $resultexp = $db->sql_query($sqlexp);
                $amount = 0;
                while ($rowexp = $db->sql_fetchrow($resultexp)) {
                    $amount += $rowexp['amount'];
                }
                $amountFormat = number_format($amount, 2);

                $sqlexp1 = "
                SELECT SUM(e.amount) AS amount
                      ,e.group
                      ,es.title AS sub_title
                      ,e.source
                FROM expense e
                LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
                WHERE e.group = {$rowexpHead['expense_group_id']}
                  AND e.source = '{$source}'
                {$appendSqlSite}
                {$startDateAppendSql}
                {$monthValAppendSql}
                {$yearValAppendSql}
                GROUP BY es.expense_sub_group_id
                ORDER BY es.title ASC
                ";
                $resultexp1 = $db->sql_query($sqlexp1);
                $subtitle = '';
                while ($rowexp1 = $db->sql_fetchrow($resultexp1)) {
                    $sqlexpOverall = "
                    SELECT SUM(e.amount) AS amount
                          ,e.group
                          ,e.source
                          ,es.title AS sub_title
                    FROM expense e
                    LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
                    WHERE e.group = {$rowexp1['group']}
                    {$appendSqlSite}
                    {$startDateAppendSql}
                    {$monthValAppendSql}
                    {$yearValAppendSql}
                    AND es.title = '{$rowexp1['sub_title']}'
                    ";
                    $resultexpOverall    = $db->sql_query($sqlexpOverall);
                    $amountOverall       = 0;
                    $rowexpOverall       = $db->sql_fetchrow($resultexpOverall);
                    $amountOverall       = $rowexpOverall['amount'];
                    $amountOverallFormat = number_format($amountOverall, 2);

                    $sqlexpSubDetail = "
                    SELECT e.amount
                          ,e.group
                          ,es.title AS sub_title
                          ,e.description
                          ,e.source
                          ,e.date
                    FROM expense e
                    LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
                    WHERE e.group = {$rowexp1['group']}
                    AND e.source = '{$rowexp1['source']}'
                    {$appendSqlSite}
                    {$startDateAppendSql}
                    {$monthValAppendSql}
                    {$yearValAppendSql}
                    AND es.title = '{$rowexp1['sub_title']}'
                    ";
                    $resultexpSubDetail = $db->sql_query($sqlexpSubDetail);
                    $subHeadtitle = '';
                    while ($rowexpSubDetail = $db->sql_fetchrow($resultexpSubDetail)) {
                        $date = $fn->getCPDate($rowexpSubDetail['date'],"d-m-Y");

                        $subHeadtitle .= "
                        <tr>
                            <td>{$date}</td>
                            <td>{$rowexpSubDetail['sub_title']}</td>
                            <td>{$rowexpSubDetail['description']}</td>
                            <td>{$rowexpSubDetail['source']}</td>
                            <td align='right'>{$rowexpSubDetail['amount']}</td>
                        </tr>";
                    }

                    $subtitle .= "
                    <tr>
                        <td class='expenseDetailsSubHead'>
                            <div class='expenseSubHeadDetails'>+ {$rowexp1['sub_title']}</div>
                            <div class='subTitlesWithoutGroup'><table>{$subHeadtitle}</table></div>
                        </td>
                        <td align='right'>{$rowexp1['amount']}</td>
                        <td>[Overall Amount: {$amountOverallFormat}]</td>
                    </tr>";
                }
                $expense_group .= "
                <table width=100%>
                    <tr>
                        <td width = 85% class='expenseDetailsHead'>
                        <div class='expenseDetails'>+ {$rowexpHead['group_title']}</div>
                        <div class='subTitles'><table>{$subtitle}</table></div>
                        </td>
                        <td width = 15% align='right'>
                        {$amountFormat}
                        </td>
                    </tr>
                </table>
                "; 
                $overAllExpense2 += $amount;
            }
        }

        $overAllExpense = $overAllExpense1 + $overAllExpense2;
 
        $admissionChargesTr = '';
        $theatreChargesTr   = '';

        if ($cpCfg['cp.hasMultiUniqueSites']) {
            if($cpSiteIdSession == 1){
                $admissionChargesTr = "
                    <tr>
                        <td width = '70%'>Admission Charges</td>
                        <td width = '30%' align='right'>{$totalAllIPAdminCharges}</td>
                    </tr>
                ";

                $theatreChargesTr = "
                    <tr>
                        <td width = '70%'>Theater Charges</td>
                        <td width = '30%' align='right'>{$totalAllIPTheatreCharges}</td>
                    </tr>
                ";
            }
            else{
                $totalOverAllinPatient = 0;
            }
        }

        $overAllIncome    = $total_amount_pos;
        $overAllProfit    = $overAllIncome - $overAllExpense;
        $overAllIncome    = number_format($overAllIncome, 2);
        $overAllExpense   = number_format($overAllExpense, 2);
        $overAllProfit    = number_format($overAllProfit, 2);
        $total_amount_pos = number_format($total_amount_pos, 2);


        $text = "
        <tr>
            <td class='incomeReport' width='30%'>
                <table width=100%>
                    <tr>
                        <td width = '70%'><span>Pharmacy Sales</span></td>
                        <td width = '30%' align='right'>{$total_amount_pos}</td>
                    </tr>
                </table>
            </td>
            <td class='incomeReport' width='70%'>
                {$expense_group}
            </td>
        </tr>
        <tr>
            <td class='totalValue'>
                <div class='float_left'>Total</div> <div class='float_right'>{$overAllIncome}</div>
            </td>
            <td class='totalValue' align='right'>
                <div class='float_left'>Total</div> <div class='float_right'>{$overAllExpense}</div>
            </td>
        </tr>
        <tr>
            <td class='totalValue lastRowBgColor'>
                <div class='float_left '>Balance</div> <div class='float_right'>{$overAllProfit}</div>
            </td>
            <td class='' align='right'>
            </td>
        </tr>
        ";

        return $text;
    }

    function getBalTillYesterday() {
        $fn = Zend_Registry::get('fn');
        $db    = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $rows = '';

        $total_amount_pos       = 0;
        $totaltestamount          = 0;
        $totaltestamount1         = 0;
        $totaltestamount2         = 0;
        $totalOverAllLabtest      = 0;
        $totalOverAllinPatient    = 0;
        $totalAllIPAdminCharges   = 0;
        $totalAllIPTheatreCharges = 0;

        $startDateAppendSql = '';
        $month        = date('m');
        $year         = date('Y');

        $start_date = $year . '-' . $month . '-' . '01';
        $end_date = date("Y-m-d", strtotime("yesterday"));
        $monthVal     = $fn->getReqParam('month');
        $yearVal      = $fn->getReqParam('year');
        $current_date = date('Y-m-d');

        if ($start_date != '' && $end_date != '') {
            $startDateAppendSql = "AND p.date >= '{$start_date}' AND p.date <= '{$end_date}'";
        }

        $appendSql = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "p.site_id = {$cpSiteIdSession}";
        }

        $SQLSub = "
        SELECT p.*
        FROM pharma_daily_sales p
        WHERE {$appendSql}
          {$startDateAppendSql}
        ";
        $resultSub = $db->sql_query($SQLSub);
        while ($rowSub = $db->sql_fetchrow($resultSub)) {
            $appendSqlInvM = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSqlInvM = "AND site_id = {$cpSiteIdSession}";
            }

            $SQLCollection = "
            SELECT SUM(invoice_amount) AS total_amount
            FROM `invoice`
            WHERE status != 'Cancelled'
            AND invoice_type = 'POS'
            AND invoice_date = '{$rowSub['date']}'
            {$appendSqlInvM}
            ";
            $resultCollection = $db->sql_query($SQLCollection);
            $recCollection    = $db->sql_fetchrow($resultCollection);

            $appendSqlInv = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSqlInv = "AND inv.site_id = {$cpSiteIdSession}";
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
            {$appendSqlInv}
            ";
            $resultSalesReturn = $db->sql_query($SQLSalesReturn);
            $recSalesReturn    = $db->sql_fetchrow($resultSalesReturn);
            $salesReturn       =  $recSalesReturn['sales_return_amount'];

            if($rowSub['date'] < '2019-04-05'){
                $totalCollection = $rowSub['sales_amount'];
            } else {
                $totalCollection = $recCollection['total_amount'];            
            }

            $totalAmount = $totalCollection + $rowSub['excess_amount'] - $salesReturn;

            $total_amount_pos += $totalAmount;
        }

        //Expense related codes// 
        $sqlgroup = "
        SELECT expense_group_id 
              ,title
        FROM expense_group
        WHERE title = 'PHARMACY'
        ";
        $resultgroup = $db->sql_query($sqlgroup);
        $expense_group = '';
        $amount = 0;
        $expense_amount ='';
        $overAllExpense1 = 0;
        while ($rowgroup    = $db->sql_fetchrow($resultgroup)) {
            $appendSqlSite = '';
            $AppendSource  = '';
            $source        = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSqlSite = "AND e.site_id = {$cpSiteIdSession}";

                if($cpSiteIdSession == 1){
                    $source = 'Hab Pharm Income';
                    $AppendSource = "AND e.source = '{$source}'";
                }
                if($cpSiteIdSession == 2){
                    $source = 'Cres Pharm Income';
                    $AppendSource = "AND e.source = '{$source}'";
                }
                if($cpSiteIdSession == 3){
                    $source = '';
                    $AppendSource = "";
                }
            }

            $startDateAppendSql = '';
            $monthValAppendSql = '';
            $yearValAppendSql = '';

            if ($start_date != '' && $end_date != '') {
                $startDateAppendSql = "AND e.date >= '{$start_date}' AND e.date <= '{$current_date}'";
            }

            $sqlexp = "
            SELECT SUM(e.amount) AS amount
                  ,e.group
                  ,e.source
                  ,es.title AS sub_title
            FROM expense e
            LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
            WHERE e.group = {$rowgroup['expense_group_id']}
            {$AppendSource}
            {$appendSqlSite}
            {$startDateAppendSql}
            GROUP BY e.group
            ";
            $resultexp = $db->sql_query($sqlexp);
            $amount = 0;
            while ($rowexp = $db->sql_fetchrow($resultexp)) {
                $amount += $rowexp['amount'];
            }
            $amountFormat = number_format($amount, 2);

            $sqlexp1 = "
            SELECT SUM(e.amount) AS amount
                  ,e.group
                  ,es.title AS sub_title
                  ,e.source
            FROM expense e
            LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
            WHERE e.group = {$rowgroup['expense_group_id']}
            {$AppendSource}
            {$appendSqlSite}
            {$startDateAppendSql}
            GROUP BY es.expense_sub_group_id
            ORDER BY es.title ASC
            ";
            $resultexp1 = $db->sql_query($sqlexp1);
            $subtitle = '';
            while ($rowexp1 = $db->sql_fetchrow($resultexp1)) {
                $sqlexpOverall = "
                SELECT SUM(e.amount) AS amount
                      ,e.group
                      ,e.source
                      ,es.title AS sub_title
                FROM expense e
                LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
                WHERE e.group = {$rowexp1['group']}
                {$appendSqlSite}
                {$startDateAppendSql}
                AND es.title = '{$rowexp1['sub_title']}'
                ";
                $resultexpOverall    = $db->sql_query($sqlexpOverall);
                $amountOverall       = 0;
                $rowexpOverall       = $db->sql_fetchrow($resultexpOverall);
                $amountOverall       = $rowexpOverall['amount'];
                $amountOverallFormat = number_format($amountOverall, 2);

                $sqlexpSubDetail = "
                SELECT e.amount
                      ,e.group
                      ,es.title AS sub_title
                      ,e.description
                      ,e.source
                      ,e.date
                FROM expense e
                LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
                WHERE e.group = {$rowexp1['group']}
                AND e.source = '{$rowexp1['source']}'
                {$appendSqlSite}
                {$startDateAppendSql}
                AND es.title = '{$rowexp1['sub_title']}'
                ";
                $resultexpSubDetail = $db->sql_query($sqlexpSubDetail);
                $subHeadtitle = '';
                while ($rowexpSubDetail = $db->sql_fetchrow($resultexpSubDetail)) {
                    $date = $fn->getCPDate($rowexpSubDetail['date'],"d-m-Y");

                    $subHeadtitle .= "
                    <tr>
                        <td>{$date}</td>
                        <td>{$rowexpSubDetail['sub_title']}</td>
                        <td>{$rowexpSubDetail['description']}</td>
                        <td>{$rowexpSubDetail['source']}</td>
                        <td align='right'>{$rowexpSubDetail['amount']}</td>
                    </tr>";
                }

                $subtitle .= "
                <tr>
                    <td class='expenseDetailsSubHead'>
                        <div class='expenseSubHeadDetails'>+ {$rowexp1['sub_title']}</div>
                        <div class='subTitlesWithoutGroup'><table>{$subHeadtitle}</table></div>
                    </td>
                    <td align='right'>{$rowexp1['amount']}</td>
                    <td>[Overall Amount: {$amountOverallFormat}]</td>
                </tr>";
            }
            $expense_group .= "
            <table width=100%>
                <tr>
                    <td width = 85% class='expenseDetailsHead'>
                    <div class='expenseDetails'>+ {$rowgroup['title']}</div>
                    <div class='subTitles'><table>{$subtitle}</table></div>
                    </td>
                    <td width = 15% align='right'>
                    {$amountFormat}
                    </td>
                </tr>
            </table>
            "; 

            $overAllExpense1 += $amount;
        }

        /* EXPENSE RECORDS FROM PHARMACY SOURCE */
        $source = '';
        if($cpSiteIdSession == 1){
            $source = 'Hab Pharm Income';
        } else if($cpSiteIdSession == 2){
            $source = 'Cres Pharm Income';
        }
        $overAllExpense2 = 0;
        if($source != ''){
            $appendSqlSite = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSqlSite = "AND e.site_id = {$cpSiteIdSession}";
            }

            $startDateAppendSql = '';

            if ($start_date != '' && $end_date != '') {
                $startDateAppendSql = "AND e.date >= '{$start_date}' AND e.date <= '{$current_date}'";
            }

            $sqlexpHead = "
            SELECT e.source
                  ,eg.title AS group_title
                  ,eg.expense_group_id
            FROM expense e
            LEFT JOIN expense_group eg ON (eg.expense_group_id = e.group)
            WHERE e.source = '{$source}'
              AND eg.title != 'PHARMACY'
            {$appendSqlSite}
            {$startDateAppendSql}
            GROUP BY e.group
            ";
            $resultexpHead = $db->sql_query($sqlexpHead);
            while ($rowexpHead = $db->sql_fetchrow($resultexpHead)) {
                $sqlexp = "
                SELECT SUM(e.amount) AS amount
                      ,e.group
                      ,es.title AS sub_title
                FROM expense e
                LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
                WHERE e.group = {$rowexpHead['expense_group_id']}
                  AND e.source = '{$source}'
                {$appendSqlSite}
                {$startDateAppendSql}
                GROUP BY e.group
                ";
                $resultexp = $db->sql_query($sqlexp);
                $amount = 0;
                while ($rowexp = $db->sql_fetchrow($resultexp)) {
                    $amount += $rowexp['amount'];
                }
                $amountFormat = number_format($amount, 2);

                $sqlexp1 = "
                SELECT SUM(e.amount) AS amount
                      ,e.group
                      ,es.title AS sub_title
                      ,e.source
                FROM expense e
                LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
                WHERE e.group = {$rowexpHead['expense_group_id']}
                  AND e.source = '{$source}'
                {$appendSqlSite}
                {$startDateAppendSql}
                GROUP BY es.expense_sub_group_id
                ORDER BY es.title ASC
                ";
                $resultexp1 = $db->sql_query($sqlexp1);
                $subtitle = '';
                while ($rowexp1 = $db->sql_fetchrow($resultexp1)) {
                    $sqlexpOverall = "
                    SELECT SUM(e.amount) AS amount
                          ,e.group
                          ,e.source
                          ,es.title AS sub_title
                    FROM expense e
                    LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
                    WHERE e.group = {$rowexp1['group']}
                    {$appendSqlSite}
                    {$startDateAppendSql}
                    AND es.title = '{$rowexp1['sub_title']}'
                    ";
                    $resultexpOverall    = $db->sql_query($sqlexpOverall);
                    $amountOverall       = 0;
                    $rowexpOverall       = $db->sql_fetchrow($resultexpOverall);
                    $amountOverall       = $rowexpOverall['amount'];
                    $amountOverallFormat = number_format($amountOverall, 2);

                    $sqlexpSubDetail = "
                    SELECT e.amount
                          ,e.group
                          ,es.title AS sub_title
                          ,e.description
                          ,e.source
                          ,e.date
                    FROM expense e
                    LEFT JOIN expense_sub_group es ON (es.expense_sub_group_id = e.sub_group)
                    WHERE e.group = {$rowexp1['group']}
                    AND e.source = '{$rowexp1['source']}'
                    {$appendSqlSite}
                    {$startDateAppendSql}
                    AND es.title = '{$rowexp1['sub_title']}'
                    ";
                    $resultexpSubDetail = $db->sql_query($sqlexpSubDetail);
                    $subHeadtitle = '';
                    while ($rowexpSubDetail = $db->sql_fetchrow($resultexpSubDetail)) {
                        $date = $fn->getCPDate($rowexpSubDetail['date'],"d-m-Y");

                        $subHeadtitle .= "
                        <tr>
                            <td>{$date}</td>
                            <td>{$rowexpSubDetail['sub_title']}</td>
                            <td>{$rowexpSubDetail['description']}</td>
                            <td>{$rowexpSubDetail['source']}</td>
                            <td align='right'>{$rowexpSubDetail['amount']}</td>
                        </tr>";
                    }

                    $subtitle .= "
                    <tr>
                        <td class='expenseDetailsSubHead'>
                            <div class='expenseSubHeadDetails'>+ {$rowexp1['sub_title']}</div>
                            <div class='subTitlesWithoutGroup'><table>{$subHeadtitle}</table></div>
                        </td>
                        <td align='right'>{$rowexp1['amount']}</td>
                        <td>[Overall Amount: {$amountOverallFormat}]</td>
                    </tr>";
                }
                $expense_group .= "
                <table width=100%>
                    <tr>
                        <td width = 85% class='expenseDetailsHead'>
                        <div class='expenseDetails'>+ {$rowexpHead['group_title']}</div>
                        <div class='subTitles'><table>{$subtitle}</table></div>
                        </td>
                        <td width = 15% align='right'>
                        {$amountFormat}
                        </td>
                    </tr>
                </table>
                "; 
                $overAllExpense2 += $amount;
            }
        }

        $overAllExpense = $overAllExpense1 + $overAllExpense2;
 
        $admissionChargesTr = '';
        $theatreChargesTr   = '';

        if ($cpCfg['cp.hasMultiUniqueSites']) {
            if($cpSiteIdSession == 1){
                $admissionChargesTr = "
                    <tr>
                        <td width = '70%'>Admission Charges</td>
                        <td width = '30%' align='right'>{$totalAllIPAdminCharges}</td>
                    </tr>
                ";

                $theatreChargesTr = "
                    <tr>
                        <td width = '70%'>Theater Charges</td>
                        <td width = '30%' align='right'>{$totalAllIPTheatreCharges}</td>
                    </tr>
                ";
            }
            else{
                $totalOverAllinPatient = 0;
            }
        }

        $overAllIncome    = $total_amount_pos;
        $overAllProfit    = $overAllIncome - $overAllExpense;
        $overAllProfit    = number_format($overAllProfit, 2);


        $text = "
        {$overAllProfit}
        ";

        return $text;
    }

    function getYesterdaySales() {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $rows = '';
        $appendSql = '';

        $yesterday     = date("Y-m-d", strtotime("yesterday"));
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $sum_amount = 0;
        $overallVisitAmount = 0;
        $overallLabAmount     = 0;
        $overallPharAmount     = 0;

        $month      = date('m');
        $year       = date('Y');
        $start_date = $yesterday;
        $end_date = $yesterday;                

        /*******************************PHARMACY***********************************/
        $SQLPd = "
        SELECT *
        FROM pharma_daily_sales
        WHERE date != ''
        AND (date >= '{$start_date}' AND date <= '{$end_date}')            
        AND site_id = {$cpSiteIdSession}
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
        AND inv.site_id = {$cpSiteIdSession}
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
        AND site_id = {$cpSiteIdSession}
        ";
        $resultCollection = $db->sql_query($SQLCollection);
        $recCollection    = $db->sql_fetchrow($resultCollection);

        $totalCollection = $recCollection['total_amount'];
        $totalAmount = round($totalCollection + $rowPd['excess_amount'] - $salesReturn);

        $totalAmount = number_format($totalAmount, 0);

        $rows .= $totalAmount;

        $text = "
        {$rows}
        ";

        return $text;
    }
}