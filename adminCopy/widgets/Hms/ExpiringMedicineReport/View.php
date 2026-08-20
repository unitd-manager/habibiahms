<?
class CPL_Admin_Widgets_Hms_ExpiringMedicineReport_View extends CP_Common_Lib_WidgetViewAbstract
{
    //==================================================================//
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');

        $text = "
        <h2>Expiring Medicine Report</h2>
		<div class='tableOuter scroll-pane'>
			<table class='thinlist'>
				<thead>
					<tr>
                        <th>Item Code</th>
						<th>Medicine Name</th>
						<th>Stock</th>
						<th>MOL</th>
                        <th>Expiry Date</th>
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
        $fn    = Zend_Registry::get('fn');
        $db    = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        
        $rows = '';
        foreach($this->model->dataArray as $row){
            $expiry_date = "";
            if($row['expiry_date'] != "") {
                $expiry_date = $fn->getCPDate($row['expiry_date'], "d-m-Y");
            }            

		    $rows .= "
			<tr>
				<td>{$row['item_code']}</td>
				<td>{$row['product_name']}</td>
				<td>{$row['stock']}</td>
                <td>{$row['minimum_order_level'.$cpSiteIdSession]}</td>
                <td>{$expiry_date}</td>
			</tr>
			";
        }

        $text = "
        {$rows}
        ";

        return $text;
    }

}