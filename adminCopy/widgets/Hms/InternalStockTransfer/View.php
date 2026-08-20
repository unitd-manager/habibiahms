<?
class CPL_Admin_Widgets_Hms_InternalStockTransfer_View extends CP_Common_Lib_WidgetViewAbstract
{
    //==================================================================//
    function getWidget() {
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $dateUtil = Zend_Registry::get('dateUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $start_date = $fn->getReqParam('start_date');
        $end_date   = $fn->getReqParam('end_date');
        $start_date = $fn->getCPDate($start_date,"d-m-Y");
        $end_date = $fn->getCPDate($end_date,"d-m-Y");
        $summaryRec = $this->model->getSqlForCount();
        $monthVal  = $fn->getReqParam('month');
        $monthName = $dateUtil->getLongMonthName($monthVal);
       
        $text = "
        <h1>Internal Stock Transfer</h1>
        <table class='thinlist summaryTable mb20'>
            <thead>
                <th colspan='6'>Summary</th>
            </thead>
            <tr>
                <td>Month : {$monthName}</td>
                <td>From Date : {$start_date}</td>
                <td>To Date : {$end_date}</td>
                <td>Total : {$summaryRec}</td>
            </tr>
        </table>
		<div class = 'tableOuter scroll-pane'>
			<table class='thinlist'>
				<thead>
					<tr>
                        <th>From Location</th>
						<th>To Location</th>
                        <th>Product Name</th>
                        <th>Rate</th>
                        <th>Qty</th>
                        <th>Total (Qty * Rate)</th>
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
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $text = '';
        $rows = '';
		$siteTitle = '' ;
        $totalOverAllCount = 0;
        $grandTotalAmount = 0;
        $count = 1;

        foreach($this->model->dataArray as $row){
            
            $totalAmount = $row['qty'] * $row['cost_price'];
            $TotalAmount = number_format($totalAmount, 2);
            $grandTotalAmount += $totalAmount;

            $Sqlfrom = "
            select title as from_location
            FROM internal_location
            WHERE internal_location_id = '{$row['from_location_internal']}'
            ";
            $resultfrom = $db->sql_query($Sqlfrom);
            $from = $db->sql_fetchrow($resultfrom);

            $SqlTo = "
            select title as to_location
            FROM internal_location
            WHERE internal_location_id = '{$row['to_location_internal']}'
            ";
            $resultTo = $db->sql_query($SqlTo);
            $To = $db->sql_fetchrow($resultTo);

            $rows .= "
			<tr>
                <td>{$from['from_location']}</td>
				<td>{$To['to_location']}</td>
                <td>{$row['product_name']}</td>
                <td class='txtRight'>{$row['cost_price']}</td>
                <td class='txtRight'>{$row['qty']}</td>
                <td class='txtRight'>{$TotalAmount}</td>
			</tr>
			";

            $count++;
        }
        $grandTotalAmount = number_format(round($grandTotalAmount), 2);

        $rows = "
        {$rows}
        <tr class=''>
            <td class='txtRight lastRowBgColor' colspan='5'>Total</td>
            <td class='txtRight lastRowBgColor'>{$grandTotalAmount}</td>
        </tr>
        ";
        

        return $rows;
    }

}