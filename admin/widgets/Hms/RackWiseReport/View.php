<?
class CPL_Admin_Widgets_Hms_RackWiseReport_View extends CP_Common_Lib_WidgetViewAbstract
{
    //==================================================================//
     function getWidget() {
        $fn = Zend_Registry::get('fn');
        $c = &$this->controller;

        $rowsHTML = $this->getRowsHTML();
        $text = '';

        if ($rowsHTML != ""){
            $text = "
            <table class='thinlist summaryTable'>
                <thead>
                    <th colspan='7'>Rack Wise Report</th>
                   

                </thead>
            </table>
            <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>S NO</th>
                        <th>Product Name</th>
                        <th>Rack</th> 
                        <th>Rack Qty</th>
                      
                    </tr>
                </thead>
                <tbody>
                   {$this->getRowsHTML()}
                </tbody>
            </table>
            </div>
            ";
        }

        return $text;
    }
    
    /**
     *
     */
    function getRowsHTML() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg            = Zend_Registry::get('cpCfg');

        $rows = '';
        $counter = 1;
       
        foreach($this->model->dataArray as $row){
               $site_id         = $fn->getSessionParam('cp_site_id');

     $recMS = $fn->getRecordByCondition("medicine_site", "product_id = '{$row['product_id']}' AND site_id = '{$site_id}'");
 $rack_qty = '';
            if($recMS['rack_qty'] > 0){
                $rack_qty = "{$recMS['rack_qty']}";
            }
            
            $rows .= "  
            <tr> 
                <td>{$counter}</td>
                <td>{$row['product_name']}</td> 
                <td>{$row['rake']}</td>
                <td>{$rack_qty}</td> 
            </tr>

            ";

            $counter++;
           
        }
        
        $text = "
        {$rows}
        ";

        return $text;
    }
}