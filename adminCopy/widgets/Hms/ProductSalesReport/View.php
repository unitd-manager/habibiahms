<?
class CPL_Admin_Widgets_Hms_ProductSalesReport_View extends CP_Common_Lib_WidgetViewAbstract
{
    /**
     *
     */
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $dateUtil = Zend_Registry::get('dateUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        $start_date   = $fn->getReqParam('start_date');
        $end_date     = $fn->getReqParam('end_date');
        $monthVal     = $fn->getReqParam('month');
        $yearVal      = $fn->getReqParam('year');
        $time_in      = $fn->getReqParam('time_in');
        $time_out     = $fn->getReqParam('time_out');
        $current_date = date('Y-m-d');
        $month        = date('m');
        $year         = date('Y');

        $jsonArray =  $this->getRowsHTML();

        if ($start_date != '') {
            $start_date = $start_date;
        }

        if ($monthVal == '' && $yearVal == ''){
            $start_date = $start_date;
            $end_date = $start_date;
        }

        $timeSummary = '';
        $dateSummary = '';
        if($start_date != '' && $end_date != '') {
            $start_date_formatted = $dateUtil->formatDate($start_date, 'DD/MM/YYYY');
            $end_date_formatted = $dateUtil->formatDate($end_date, 'DD/MM/YYYY');
            
            if($start_date == $end_date) {
                $dateRange = "
                <th>Date : {$start_date_formatted}</th>
                ";
            }else {
                $dateRange = "
                <th>Start Date : {$start_date_formatted}</th>
                <th>End Date : {$end_date_formatted}</th>
                ";
            }
            
            if($time_in != '' && $time_out != '') {
                $timeSummary = "
                <th>From : {$time_in}</th>
                <th>To : {$time_out}</th>
                ";
            }

            $dateSummary = "
            <table class='thinlist summaryTable mb20'>
                <thead>
                    <th colspan='6'>Summary</th>
                </thead>
                <tr>
                    {$dateRange}
                    {$timeSummary}
                </tr>
            </table>
            ";
        }
        $stockColumn = '';
        if ($_SESSION['userGroupName'] == 'Administrator' || $_SESSION['userGroupName'] == 'Super Administrator' || $_SESSION['userGroupName'] == 'Admin Nurse'){
            $stockColumn = "<th class='txtRight'>Stock</th>";
        }

        $salesColumn = '';
        if ($_SESSION['userGroupName'] == 'Administrator' || $_SESSION['userGroupName'] == 'Super Administrator'){
            $salesColumn = "<th>Sales Qty</th>";
        }
        $amountlog = '';
        $salesreturnlog = '';
        $totalamountlog = '';

        if ($_SESSION['userGroupName'] != "Nurse") {
            $amountlog=  " <th class='txtRight'>Amount</th>";
            $salesreturnlog=  "<th class='txtRight'>Sales Return</th>";
            $totalamountlog=  " <th class='txtRight'>Total Amount</th>";
        }

        $text = "
        <div class='float_left mr20 '><h1>Product Sales Report</h1></div>
        <div class='float_left ml20'><h1> Total : <b>{$jsonArray['overalltotal']}</b></h1>
                {$dateSummary}
        </div>
        <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Medicine Name</th>
                        {$salesColumn}
                        <th>Counter Qty</th>
                        {$amountlog}
                        {$salesreturnlog}
                        {$totalamountlog}               
                        {$stockColumn}
                        <th>Manual Stock</th>
                    </tr>
                </thead>
                <tbody>
                    {$jsonArray['text']}
                </tbody>
            </table>
        </div>
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
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $month        = date('m');
        $year         = date('Y');
        $monthVal     = $fn->getReqParam('month');
        $yearVal      = $fn->getReqParam('year');
        $invoice_date = $fn->getReqParam('invoice_date');
        $time_in      = $fn->getReqParam('time_in');
        $time_out     = $fn->getReqParam('time_out');
        $start_date   = $fn->getReqParam('start_date');
        $end_date     = $fn->getReqParam('end_date');
        $end_date     = $fn->getReqParam('end_date');
        $counterSales = $fn->getReqParam('counterSales');
        $totalCounter = '';
        $current_date = date('Y-m-d');

        $monthValAppendSql = '';
        $yearValAppendSql = '';
        if ($monthVal != '') {
            $monthValAppendSql .= "AND DATE_FORMAT(inv.invoice_date, '%m') = '{$monthVal}'" ;
        }

        if ($yearVal != '') {
            $yearValAppendSql .= "AND DATE_FORMAT(inv.invoice_date, '%Y') = '{$yearVal}'";
        }

        $timeAppendSql = "";
        if ($time_in != '' && $time_out != '') {
            $timeAppendSql = "AND DATE_FORMAT(inv.creation_date, '%H:%i:%s') BETWEEN '{$time_in}' AND '{$time_out}'";
        }

        $appendSqlDate = "";
        if ($start_date != '') {
            $appendSqlDate .= "AND inv.invoice_date = '{$start_date}'";
        } 

        if ($start_date == '') {
            if ($monthVal != '') {
                $appendSqlDate .= "AND DATE_FORMAT(inv.invoice_date, '%m') = '{$monthVal}'";
            }

            if ($yearVal != '') {
                $appendSqlDate .= "AND DATE_FORMAT(inv.invoice_date, '%Y') = '{$yearVal}'";
            }

            if ($monthVal == '' && $yearVal == ''){
                $start_date = $year . '-' . $month . '-' . '01';
                $end_date = $year . '-' . $month . '-' . '31';
                $appendSqlDate .= "AND inv.invoice_date >= '{$start_date}' AND inv.invoice_date <= '{$end_date}'";
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

        $totalOverAll = 0;

        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $site_id = $cpSiteIdSession;
        }

        $appendSqlSiteStock = '';
        if($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlSiteStock = "AND ibs.site_id = {$site_id}";
        }

        $appendSql = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "AND inv.site_id = {$site_id}";
        }

         $appendSqlCounter = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "AND o.site_id = {$site_id}";
        }

        $rows   = '';
        $count  = 1;
        $stock = '';
        foreach($this->model->dataArray as $row){

            $counter          = '';
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
                LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                LEFT JOIN (`order` o) ON (o.order_id = srh.order_id)
                WHERE o.order_status != 'Cancelled'
                AND inv.invoice_id != 'Cancelled'
                AND o.order_type = 'POS'
                AND ini.record_id = {$row['record_id']}
                {$monthValAppendSql}
                {$yearValAppendSql}
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
               
                $SQLCounter ="
                SELECT SUM(it.qty) AS counter_qty
                      ,SUM(it.qty * it.unit_price) AS counter_amount
                FROM order_item it
                LEFT JOIN (`order` o) ON (o.order_id = it.order_id)
                WHERE o.counter = '1'
                AND it.record_id = {$row['record_id']}
                {$appendSqlCounter}
                ";

                $SQLCounter = "
                SELECT SUM(it.qty) AS counter_qty
                      ,SUM(it.unit_price*it.qty) AS counter_amount
                      ,o.counter
                FROM order_item it
                LEFT JOIN (`order` o) ON (o.order_id = it.order_id)
                LEFT JOIN (`invoice` inv) ON (inv.order_id = o.order_id)
                WHERE o.counter = '1'
                  AND it.record_id = {$row['record_id']}  
                  {$timeAppendSql}
                  {$appendSqlDate}
                  {$appendSql}   
                ";

                $resultCounter    = $db->sql_query($SQLCounter);
                $recCounter       = $db->sql_fetchrow($resultCounter);
                $counter          = $recCounter['counter_qty'];
                $counter_amount   = '';

                if($recCounter['counter_amount']){
                    $counter_amount   = $recCounter['counter_amount'];
                }

                if($counter != 0){
                    $counter          = number_format($counter);
                }
                else{
                    $counter          = '';
                }

                $stockColumn = '';
                if ($_SESSION['userGroupName'] == 'Administrator' || $_SESSION['userGroupName'] == 'Super Administrator'  || $_SESSION['userGroupName'] == 'Admin Nurse'){
                    $stockColumn = "<td class='txtRight'>{$stock}</td>";
                }

                $salesColumn = '';
                if ($_SESSION['userGroupName'] == 'Administrator' || $_SESSION['userGroupName'] == 'Super Administrator'){
                    $salesColumn = "<td>{$row['qty']}</td>";
                }

                $amountlog1 = '';

                if ($_SESSION['userGroupName'] != "Nurse") {
                    $amountlog1=  "<td class='txtRight'>{$Amount}</td>";
                }
                $salesreturn1 = '';

                if ($_SESSION['userGroupName'] != "Nurse") {
                    $salesreturn1=  " <td class='txtRight'>{$salesReturn}</td>";
                }
                $totalamount1 = '';

                if ($_SESSION['userGroupName'] != "Nurse") {
                    $totalamount1=  "<td class='txtRight'>{$totalAmountFormatted}</td>";
                }
         
                $product_link = "index.php?_topRm=pharmacy&module=tradingin_inventory&_action=list&searchDone=1&keyword={$row['product_name']}&category_id=&special_search=&minimum_order_level=&expiry_date=";
                $product_name = "<a href='{$product_link}' target='_blank'><u>{$row['product_name']}</u> ($stock)</a>";

                $SQLMS = "
                SELECT ms.stock, ms.actual_stock, ms.date, ms.time
                FROM manual_stock ms
                WHERE ms.product_id = {$row['record_id']}
                  AND ms.site_id = {$site_id}
                  ORDER BY ms.manual_stock_id DESC
                ";
                $resultMS   = $db->sql_query($SQLMS);
                $rowMS = $db->sql_fetchrow($resultMS);

                $manual_stock_date = $fn->getCPDate($rowMS['date'], 'd-m-y');
                $manual_stock_time = $fn->getCPDate($rowMS['time'], 'H:i');

                $msdt = '';
                if ($manual_stock_date != ''){
                    $msdt = "<td>{$manual_stock_date} / {$manual_stock_time}</td>";
                }

                $rows .= "
                <tr>
                    <td>{$count}</td>
                    <td>{$product_name}</td>
                    {$salesColumn}
                    <td class='txtRight'>{$counter}</td>                   
                    {$amountlog1}
                    {$salesreturn1}
                    {$totalamount1}                                    
                    {$stockColumn}
                    {$msdt}
                </tr>
                ";
                $totalOverAll += $totalAmount; 
                //$totalCounter += $recCounter['counter_amount']; 
                $count++;
            }
        }
        $totalOverAll = number_format(round($totalOverAll), 2);
        //$totalCounter = number_format(round($totalCounter), 2);

        $stockColumnBottom = '';
        if ($_SESSION['userGroupName'] == 'Administrator' || $_SESSION['userGroupName'] == 'Super Administrator'){
            $stockColumnBottom = "<td class='txtRight lastRowBgColor'></td>";
        }
        $amountlg = '';

        if ($_SESSION['userGroupName'] != "Nurse") {
            $amountlg= "<td class='txtRight lastRowBgColor' colspan='2'>Total</td>" ;
        }
        $totalamountlg = '';

        if ($_SESSION['userGroupName'] != "Nurse") {
            $totalamountlg=  "<td class='txtRight lastRowBgColor'>{$totalOverAll}</td>";
        }
         
        $text = "
        {$rows}
        <tr class=''>
            <td class='txtRight lastRowBgColor' colspan='4'>{$totalCounter}</td>
            {$amountlg}
            {$totalamountlg}
            {$stockColumnBottom}
            <td class='txtRight lastRowBgColor'></td>
        </tr>
        ";

        return array('text' => $text, 'overalltotal' => $totalOverAll);
    }
   
        
}