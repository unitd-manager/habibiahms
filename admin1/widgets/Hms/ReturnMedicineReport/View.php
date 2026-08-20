<?
class CPL_Admin_Widgets_Hms_ReturnMedicineReport_View extends CP_Common_Lib_WidgetViewAbstract
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
                    <th colspan='7'>Return Medicine Report</th>
                </thead>
            </table>
            <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        
                        <th>Medicine Name</th>
                        <th>Return Qty</th> 
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

        $rows = '';
        $counter = 1;
       
        foreach($this->model->dataArray as $row){
            $product_link = "index.php?_topRm=pharmacy&module=tradingsg_purchaseOrder&_action=edit&record_id={$row['purchase_order_id']}";
            $product_name = "<a href='{$product_link}' target='_blank'><u>{$row['product_name']}</u></a>";
            
            $rows .= "  
            <tr> 
                <td>{$product_name}</td>
               <td>{$row['qty_return']}</td>   
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