<?
class CPL_Admin_Widgets_Hms_DrugUsageReport_View extends CP_Common_Lib_WidgetViewAbstract
{
    //==================================================================//
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');

        $text = "
        <h2>Drug Usage Report</h2>
		<div class = 'tableOuter scroll-pane'>
			<table class='thinlist'>
				<thead>
					<tr>
						<th>Name</th>
						<th>Medicine</th>
						<th>Qty</th>
                        <th>Date</th>
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
        $site_id        = $fn->getReqParam('site_id');
        $rows = '';
        $totalOverAllCase = 0;
        $totalOverAll = 0;

        foreach($this->model->dataArray as $row){
		    $rows .= "
			<tr>
				<td>{$row['cust_first_name']}</td>
				<td>{$row['item_title']}</td>
				<td>{$row['qty']}</td>
                <td>{$row['invoice_date']}</td>
			</tr>
			";
        }

        $text = "
        {$rows}
        ";

        return $text;
    }

}