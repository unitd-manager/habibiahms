<?
class CPL_Admin_Widgets_Hms_SupplierOutstanding_View extends CP_Common_Lib_WidgetViewAbstract
{
    /**
     *
     */
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $text = "
        <h2>Supplier Outstanding</h2>
        <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Supplier Name</th>
                        <th class='txtRight'>Amount</th>
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
        $cpCfg = Zend_Registry::get('cpCfg');
        
        $rows        = '';
        $count       = 1;
        $amount = 0;
        $month          = date('m');
        $year           = date('Y');
        $start_date = $year . '-' . $month . '-' . '01';
        $end_date = $year . '-' . $month . '-' . '31';
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $appendSql = '';
        $totalOverAll = 0;
        
        foreach($this->model->dataArray as $row){
            $totalAmount = 0;
            $overall_discount = $row['overall_discount'];

            if($overall_discount == ''){
                $overall_discount = 0;
            }

            if($row['purchase_return'] == ""){
                $purchase_return = 0;
            }
            else{
                $purchase_return = $row['purchase_return'] ;
            }

            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSql = "AND p.site_id = {$cpSiteIdSession}";
            }
            $SQLPaid = "
            SELECT SUM(round((pop.qty*pop.cost_price),2)) AS po_amount
            FROM purchase_order p
            LEFT JOIN po_product pop ON (pop.purchase_order_id = p.purchase_order_id)
            WHERE p.company_id_supplier IN ({$row['company_id_supplier']})
            AND p.invoice_date  >= '{$start_date}'
            AND p.invoice_date  <= '{$end_date}'
            AND p.status  != 'Cancelled'
            {$appendSql}
            ";
            $SQLPaid = "
            SELECT SUM(pop.qty * pop.cost_price) AS po_amount
                  ,SUM((((pop.qty * pop.cost_price) * pop.discount_percentage) / 100) + (((pop.qty * pop.cost_price) * {$overall_discount}) / 100)) AS Discount_Total
                  ,SUM((((pop.qty * pop.cost_price) - (((pop.qty * pop.cost_price) * pop.discount_percentage) / 100) - (((pop.qty * pop.cost_price) * {$overall_discount}) / 100)) * pop.gst) / 100) AS GST_Total
            FROM purchase_order p
            LEFT JOIN po_product pop ON (pop.purchase_order_id = p.purchase_order_id)
            WHERE p.company_id_supplier IN ({$row['company_id_supplier']})
            AND p.invoice_date  >= '{$start_date}'  
            AND p.invoice_date  <= '{$end_date}'
            AND p.status  != 'Cancelled'
            {$appendSql}
            ";
            /*$SQLPaid = "
            SELECT SUM(pop.qty * pop.cost_price) AS total_cost
                  ,SUM((((pop.qty * pop.cost_price) * pop.discount_percentage) / 100) + (((pop.qty * pop.cost_price) * {$overall_discount}) / 100)) AS Discount_Total
                  ,SUM((((pop.qty * pop.cost_price) - (((pop.qty * pop.cost_price) * pop.discount_percentage) / 100) - (((pop.qty * pop.cost_price) * {$overall_discount}) / 100)) * pop.gst) / 100) AS GST_Total
            FROM po_product pop 
            WHERE pop.purchase_order_id = {$row['purchase_order_id']}
            ";*/
            $resultPaid = $db->sql_query($SQLPaid);
            $rowPaid    = $db->sql_fetchrow($resultPaid);

            $SQLPartialPayment = "
            SELECT SUM(srh.amount) AS Po_partial_payment
            FROM supplier_receipt_history srh
            LEFT JOIN (purchase_order p) ON (srh.purchase_order_id = p.purchase_order_id)
            LEFT JOIN supplier_receipt sr ON (sr.supplier_receipt_id = srh.supplier_receipt_id)
            WHERE p.company_id_supplier IN ({$row['company_id_supplier']})
              AND sr.receipt_status != 'Cancelled'
              AND p.invoice_date  >= '{$start_date}' 
              AND p.invoice_date  <= '{$end_date}'
            {$appendSql}
            ";
            $resultPartialPayment = $db->sql_query($SQLPartialPayment);
            $rowPartialPayment    = $db->sql_fetchrow($resultPartialPayment);
            $amount   = $rowPaid['po_amount'] - $rowPaid['Discount_Total'] + $rowPaid['GST_Total']- $purchase_return;

            if ($rowPartialPayment['Po_partial_payment'] == 0){
                $totalAmount += $amount;
            } else {
                $totalAmount += $amount - $rowPartialPayment['Po_partial_payment'];
            }

            if($totalAmount > 0){
                $totalOverAll += $totalAmount; 
                $totalAmount = number_format(round($totalAmount));
                $rows .= "
                <tr>
                    <td>{$count}</td>
                    <td>{$row['company_name']}</td>
                    <td class='txtRight'>{$totalAmount}</td>
                </tr>
                ";

                $count++;
            }
        }
        $totalOverAll = number_format(round($totalOverAll));
        
        $text = "
        <tr class=''>
            <td class='lastRowBgColor' colspan='3'>Total: {$totalOverAll}</td>
        </tr>
        {$rows}
        ";

        return $text;
    }
}