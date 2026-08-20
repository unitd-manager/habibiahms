<?
class CPL_Admin_Widgets_Hms_PharmacyDailySales_View extends CP_Common_Lib_WidgetViewAbstract
{
    /**
     *
     */
    function getWidget() {
        $db     = Zend_Registry::get('db');
        $fn     = Zend_Registry::get('fn');
        $cpUtil = Zend_Registry::get('cpUtil');
        $tv     = Zend_Registry::get('tv');
        $month  = $fn->getReqParam('month');

        $heading        = '';
        $monthSearch    = '';
        $summaryText    = '';

        $jsonArray =  $this->getRowsHTML();

        $month  = $fn->getReqParam('month');
        if ($month == '') {
            $month = date('m');
        }

        $arr = array (
                '01' => 'January'
               ,'02' => 'February'
               ,'03' => 'March'
               ,'04' => 'April'
               ,'05' => 'May'
               ,'06' => 'June'
               ,'07' => 'July'
               ,'08' => 'August'
               ,'09' => 'September'
               ,'10' => 'October'
               ,'11' => 'November'
               ,'12' => 'December'
               );

        $heading = "Pharmacy Daily Sales";

        if($tv['module'] == 'tradingsg_dashboard'){
            $monthSearch = "
            <select name='month'>
                <option>Select Month</option>
                {$cpUtil->getDropDownFromArr($arr, $month)}
            </select>
            ";
            $summaryText = "
            <h2>
            {$heading} 
            {$monthSearch}
            </h2>";
        }
        else{
            $summaryText = "
            <div class='float_left mr20 '><h1>$heading</h1></div>
            <div class='float_left ml20'><h1> Total : <b>{$jsonArray['overalltotal']}</b></b></h1></div>
            ";
        }

        $text = "
        {$summaryText}
        <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Sales Amount</th>
                        <th>Excess Amount</th>
                        <th>Total Amount</th>
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
        $tv = Zend_Registry::get('tv');
        
        $rows        = '';
        $count       = 1;
        $month       = $fn->getReqParam('month');
        $change      = $fn->getReqParam('change');
        $totalOverAll = '';

        if ($month == '') {
            $month = date('m');
        } else {
            $month = $month;
        }

        if($tv['module'] == 'tradingsg_dashboard'){
            $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
            $appendSql = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSql = "AND p.site_id = {$cpSiteIdSession}";
            }
            
            $SQLPharma = "
                SELECT p.*
                FROM pharma_daily_sales p
                WHERE DATE_FORMAT(p.date, '%m') = '{$month}'
                {$appendSql}
                ORDER BY p.date desc
            ";
            $resultPharma = $db->sql_query($SQLPharma);
            $rows = '';
            $count = 1;
            while ($rowPharma = $db->sql_fetchrow($resultPharma)) {

                $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
                $appendSql = '';
                if ($cpCfg['cp.hasMultiUniqueSites']) {
                    $appendSql = "AND site_id = {$cpSiteIdSession}";
                }

                $SQLCollection = "
                SELECT SUM(invoice_amount) AS total_amount
                FROM `invoice`
                WHERE status != 'Cancelled'
                AND invoice_type = 'POS'
                AND invoice_date = '{$rowPharma['date']}'
                {$appendSql}
                ";
                $resultCollection = $db->sql_query($SQLCollection);
                $recCollection    = $db->sql_fetchrow($resultCollection);

                $totalCollection = $recCollection['total_amount'];

                $appendSqlInvoice = '';
                if ($cpCfg['cp.hasMultiUniqueSites']) {
                    $appendSqlInvoice = "AND inv.site_id = {$cpSiteIdSession}";
                }

                $SQLSalesReturn = "
                SELECT SUM(srh.qty_return * srh.price) as sales_return_amount 
                FROM sales_return_history srh
                LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                LEFT JOIN (`order` o) ON (o.order_id = srh.order_id)
                WHERE o.order_status != 'Cancelled'
                AND o.order_type = 'POS'
                AND srh.date = '{$rowPharma['date']}'
                {$appendSqlInvoice}
                ";
                $resultSalesReturn = $db->sql_query($SQLSalesReturn);
                $recSalesReturn    = $db->sql_fetchrow($resultSalesReturn);
                $salesReturn       =  $recSalesReturn['sales_return_amount'];

                if($rowPharma['date'] < '2019-04-05'){
                    $totalCollection = $rowPharma['sales_amount'];
                } else {
                    $totalCollection = $totalCollection;            
                }

                $date = $fn->getCPDate($rowPharma['date'],"d-m-Y");
                $totalAmount = $totalCollection + $rowPharma['excess_amount'] - $salesReturn;
                $totalAmount = number_format(round($totalAmount));

                $rows .= "
                <tr>
                    <td>{$date}</td>
                    <td class='txtRight'>{$totalCollection}</td>
                    <td class='txtRight'>{$rowPharma['excess_amount']}</td>
                    <td class='txtRight'>{$totalAmount}</td>
                </tr>
                ";
                $totalOverAll += $totalCollection + $rowPharma['excess_amount']; 
                $count++;
            }
        }else {
            foreach($this->model->dataArray as $row){
                $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
                $appendSql = '';
                if ($cpCfg['cp.hasMultiUniqueSites']) {
                    $appendSql = "AND site_id = {$cpSiteIdSession}";
                }

                $SQLCollection = "
                SELECT SUM(invoice_amount) AS total_amount
                FROM `invoice`
                WHERE status != 'Cancelled'
                AND invoice_type = 'POS'
                AND invoice_date = '{$row['date']}'
                {$appendSql}
                ";
                $resultCollection = $db->sql_query($SQLCollection);
                $recCollection    = $db->sql_fetchrow($resultCollection);

                $totalCollection = $recCollection['total_amount'];

                if($row['date'] < '2019-04-05'){
                    $totalCollection = $row['sales_amount'];
                } else {
                    $totalCollection = $totalCollection;            
                }

                $date = $fn->getCPDate($row['date'],"d-m-Y");
                $totalAmount = $totalCollection + $row['excess_amount'] ;
                $totalAmount = number_format(round($totalAmount));

                if($totalAmount > 0){
                    $rows .= "
                    <tr>
                        <td>{$date}</td>
                        <td class='txtRight'>{$totalCollection}</td>
                        <td class='txtRight'>{$row['excess_amount']}</td>
                        <td class='txtRight'>{$totalAmount}</td>
                    </tr>
                    ";

                $totalOverAll += $totalCollection + $row['excess_amount']; 

                $count++;

                }
            }
        }

        $totalOverAll = number_format(round($totalOverAll));

        $rows .= "
        <tr class=''>
            <td class='txtRight lastRowBgColor' colspan='3'>Total</td>
            <td class='txtRight lastRowBgColor'>{$totalOverAll}</td>
        </tr>
        ";

        $text = "
        <tr class=''>
            <td class='lastRowBgColor' colspan='4'>Total: {$totalOverAll}</td>
        </tr>
        {$rows}
        ";

        if($month != "" && $change == 1) {
            print $text;
        }
        else {
            return array('text' => $text, 'overalltotal' => $totalOverAll);
        }
    }
}