<?
class CPL_Admin_Widgets_Hms_AdjustStockReport_View extends CP_Common_Lib_WidgetViewAbstract
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

        if($monthVal != ''){
            $current_month = $monthVal;
        }

        if($yearVal != ''){
            $current_year = $yearVal;
        }

        $heading = "Adjust Stock Report";
        $dates = '';

        $start_date = $current_year . '-' . $current_month . '-' . '01';
        //$end_date   = $current_year . '-' . $current_month . '-' . '31';
        $end_date   = date("Y-m-t", strtotime($start_date));

        for($i=$start_date; $i <= $end_date; $i++){
            $datesAtt  = date('d', strtotime($i));
            $datesDay  = date('l', strtotime($i));

            if($datesDay == "Sunday") {
                $bgcolor = "style='background-color:#fe4d4d !important'";
            }else {
                $bgcolor = "";
            }

            $dates .= "<th class='txtCenter' {$bgcolor}>{$datesAtt}</th>";
        }

        $text = "
        <h2>{$heading}</h2>
        <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Medicine</th>
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

        // jcheck = 1(day shift), 2(night), 3(double shift)
        $count = 1;
        foreach($this->model->dataArray as $row){
            $jCheck = 1;
            $jvar   = 1;

            $SQLAttendCount = "
            SELECT SUM(a.actual_stock - a.adjust_stock) as adjust_stock
            FROM `adjust_stock_log` a
            WHERE a.product_id = {$row['product_id']}
            AND DATE_FORMAT(a.creation_date, '%m') = '{$month}'
            AND DATE_FORMAT(a.creation_date, '%Y') = '{$year}'
            ";
            $resultAttendCount = $db->sql_query($SQLAttendCount);
            $rowAttendCount    = $db->sql_fetchrow($resultAttendCount);

            for($j = $jvar; $j <= $jCheck; $j++){
                $dates = '';
                $totalDisplay = '';
                $current_date  = date('Y-m-d');
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
                $totalTimeSecs  = '';
                $totalTimeSecs2 = '';
                $datesAttDay    = 0;

                for($i=$start_date; $i <= $end_date; $i++){
                    $on_leave = '';
                    $datesAttDay = date('d', strtotime($i));
                    $datesAtt    = date('Y-m-d', strtotime($i));

                    $SQL = "
                    SELECT SUM(a.actual_stock - a.adjust_stock) as adjust_stock
                    FROM `adjust_stock_log` a
                    WHERE DATE_FORMAT(a.creation_date, '%Y-%m-%d') = '{$datesAtt}'
                    AND a.product_id = {$row['product_id']}
                    ";
                    $result1 = $db->sql_query($SQL);
                    $row1 = $db->sql_fetchrow($result1);
                    $numRows = $db->sql_numrows($result1);

                    //if($numRows > 0){
                    $on_leave = $row1['adjust_stock'];
                    //}
                    $bgcolor = '';

                    $dates .= "<td style='border:1px solid grey;' align='center' {$bgcolor}>
                                    {$on_leave}
                                </td>";
                }

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
                $mrp_amount = number_format($mrp * $rowAttendCount['adjust_stock'],2);
                
                $employee_name = '';
                $days_attended = '';
                if($j == 1) {
                    $employee_name = $row['title'].' ('.$rowAttendCount['adjust_stock'].' | '.$mrp_amount.')';
                }

                $rows .= "
                <tr>
                    <td>{$count}</td>
                    <td>{$employee_name}</td>
                    {$dates}
                </tr>
                ";
            }
            $count++;

            $datesAttDay = $datesAttDay + 2;

            $rows .= "<tr class='seperateAttendanceReportShift'>
                        <td colspan='{$datesAttDay}'></td>
                      </tr>";
        }

        $text = "
        <tr>
            <td></td>
            <td>Total</td>
            {$this->getCalculateTotalAmountForDays()}
        </tr>
        {$rows}
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
            $mrp_amount1 = 0;
            $on_leave = '';
            $werw = 0;
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
            $mrp_amount1 += $mrp_amount;

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
