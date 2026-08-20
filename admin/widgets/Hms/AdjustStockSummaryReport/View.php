<?
class CPL_Admin_Widgets_Hms_AdjustStockSummaryReport_View extends CP_Common_Lib_WidgetViewAbstract
{

    /**
     *
     */
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');
        
        $current_date  = date('Y-m-d');
        $current_year  = date('Y');
        $current_month = date('m');

        //$start_date   = $fn->getReqParam('start_date');
        //$end_date     = $fn->getReqParam('end_date');
        $monthVal     = $fn->getReqParam('month');
        $yearVal      = $fn->getReqParam('year');

         $heading = "Adjust Stock Report";
        $dates = '';

        

        $text = "
        <h2>{$heading}</h2>
        <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                         <th>Month</th>
                        <th>Excess</th>
                      <th>Negative</th>
                      <th>Total</th>
                      
                    
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
     function getRowsHTML() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cp_site_id = $fn->getSessionParam('cp_site_id');
        $rows = '';
        $month  = date('m');
        $year   = date('Y');
          
        $monthVal     = $fn->getReqParam('month');
        $yearVal      = $fn->getReqParam('year');
        if($monthVal != ''){
            $month = $monthVal;
        }

        if($yearVal != ''){
            $year = $yearVal;
        }


        $dates='';
        $end_date='';
          
        // jcheck = 1(day shift), 2(night), 3(double shift)
        $count = 1;
        $dates .= date('M').'<br>';
        $dates_arr = array (
            '01' => 'Jan'
           ,'02' => 'Feb'
           ,'03' => 'Mar'
           ,'04' => 'Apr'
           ,'05' => 'May'
           ,'06' => 'Jun'
           ,'07' => 'Jul'
           ,'08' => 'Aug'
           ,'09' => 'Sep'
           ,'10' => 'Oct'
           ,'11' => 'Nov'
           ,'12' => 'Dec'
           );
        $total=0;
        $total_amount=0;
        $total_excess=0;
        $total_negative=0;

        $adjust_stock_log_id = $fn->getReqParam('adjust_stock_log_id');

        foreach($dates_arr as $x => $x_value) {
            $mrp_amount = 0;
            $mrp_amount_negative = 0;
            $mrp='';
            $start_date = $year . '-' . $x . '-' . '01';
            $end_date   = date("Y-m-t", strtotime($start_date));

            for($i=$start_date; $i <= $end_date; $i++){
                $datesAtt = date('Y-m-d', strtotime($i));

                foreach($this->model->dataArray as $row){
                    $SQL = "
                    SELECT SUM(a.actual_stock - a.adjust_stock) as adjust_stock
                    FROM `adjust_stock_log` a
                    WHERE DATE_FORMAT(a.creation_date, '%Y-%m-%d') = '{$datesAtt}'
                    AND a.product_id = {$row['product_id']}
                    ";
                    $result1 = $db->sql_query($SQL);
                    $row1 = $db->sql_fetchrow($result1);
                    $numRows = $db->sql_numrows($result1);

                    $SQLPP = "
                    SELECT pack_size, selling_price, cost_price
                    FROM po_product
                    WHERE product_id = {$row['product_id']}
                    ORDER BY po_product_id DESC
                    ";
                    $resultPP = $db->sql_query($SQLPP);
                    $rowPP = $db->sql_fetchrow($resultPP);
                    if(is_numeric($rowPP['pack_size'])){
                        $mrp = $rowPP['selling_price'] / $rowPP['pack_size'];
                    } else {
                        $mrp = $rowPP['selling_price'];
                    }

                    if($row1['adjust_stock'] < 0){
                        $mrp_amount_negative += $mrp * $row1['adjust_stock'];                        
                    } else {
                        $mrp_amount += $mrp * $row1['adjust_stock'];
                    }
                }
            }

            $total=$mrp_amount+$mrp_amount_negative;

            $total_amount += $total;
            $total_excess += $mrp_amount;
            $total_negative += $mrp_amount_negative;

            $mrp_amount = number_format($mrp_amount, 2);
            $mrp_amount_negative = number_format($mrp_amount_negative, 2);
            $total = number_format($total, 2);

            $rows .= "
            <tr>
            <td>{$x_value}</td>
            <td align='right'>{$mrp_amount}</td>
            <td align='right'>{$mrp_amount_negative}</td>
            <td align='right'>{$total}</td>  
            </tr>
            ";
        }

        $total_amount = number_format($total_amount, 2);
        $total_excess = number_format($total_excess, 2);
        $total_negative = number_format($total_negative, 2);        

        $text = "
        {$rows}
        <tr bgcolor=\"#A9A9A9\">
            <th>TOTAL</th>
            <td align='right' class='totalValue lastRowBgColor'>{$total_excess}</td>
            <td align='right' class='totalValue lastRowBgColor'>{$total_negative}</td>
            <td align='right' class='totalValue lastRowBgColor'>{$total_amount}</td>
        </tr>
        ";

        return $text;
    }

    /**
     *
     */

     function getCalculateTotalAmountForDays() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cp_site_id = $fn->getSessionParam('cp_site_id');
        $rows = '';
        $month  = date('m');
        $year   = date('Y');
          
        $monthVal     = $fn->getReqParam('month');
        $yearVal      = $fn->getReqParam('year');
        if($monthVal != ''){
            $month = $monthVal;
        }

        if($yearVal != ''){
            $year = $yearVal;
        }

        $count = 1;
        $totalDisplay = '';
        $current_year  = date('Y');
        $current_month = date('m');

        if($monthVal != ''){
            $current_month = $monthVal;
        }

        if($yearVal != ''){
            $current_year = $yearVal;
        }

        $start_date = $current_year . '-' . $current_month . '-' . '01';
        //$end_date   = $current_year . '-' . $current_month . '-' . '31';
        $end_date   = date("Y-m-t", strtotime($start_date));
        $datesAttDay    = 0;

        for($i=$start_date; $i <= $end_date; $i++){
            $mrp_amount = 0;
            $on_leave = '';
            $datesAttDay = date('d', strtotime($i));
            $datesAtt    = date('Y-m-d', strtotime($i));

            foreach($this->model->dataArray as $row){
                $SQL = "
                SELECT SUM(a.actual_stock - a.adjust_stock) as adjust_stock
                FROM `adjust_stock_log` a
                WHERE DATE_FORMAT(a.creation_date, '%Y-%m-%d') = '{$datesAtt}'
                AND a.product_id = {$row['product_id']}
                ";
                $result1 = $db->sql_query($SQL);
                $row1 = $db->sql_fetchrow($result1);
                $numRows = $db->sql_numrows($result1);

                $SQLPP = "
                SELECT pack_size, selling_price, cost_price
                FROM po_product
                WHERE product_id = {$row['product_id']}
                ORDER BY po_product_id DESC
                ";
                $resultPP = $db->sql_query($SQLPP);
                $rowPP = $db->sql_fetchrow($resultPP);
                if(is_numeric($rowPP['pack_size'])){
                    $mrp = $rowPP['selling_price'] / $rowPP['pack_size'];
                } else {
                    $mrp = $rowPP['selling_price'];
                }
                $mrp_amount += $mrp * $row1['adjust_stock'];
            }

            $mrpAmount = number_format($mrp_amount);

            $totalDisplay .= "<td style='border:1px solid grey;' align='center'>
                            {$mrpAmount}
                        </td>";
        }

        $text = "
        {$totalDisplay}
        ";

        return $text;
    }

    function sum_the_two_time($time1, $time2) {
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

    function sum_the_three_time($time1, $time2, $time3) {
        $times = array($time1, $time2, $time3);
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
}
