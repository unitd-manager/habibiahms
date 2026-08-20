<?
class CPL_Admin_Widgets_Hms_MfgCompanyReport_View extends CP_Common_Lib_WidgetViewAbstract
{
    //==================================================================//
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');
        $grandTotal  = 0;
        $incentiveTotal  = 0;
        $month  = $fn->getReqParam('month');
        $year   = $fn->getReqParam('year');
        $medicine_company_id   = $fn->getReqParam('medicine_company_id');
        $monthAppendSql = '';
        $yearAppendSql = '';
        $monthValAppendSql = '';
        $yearValAppendSql = '';
        $month          = date('m');
        $year           = date('Y');
        $current_date   = date('Y-m-d');
        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $monthVal       = $fn->getReqParam('month');
        $yearVal        = $fn->getReqParam('year');
        $site_id        = $fn->getReqParam('site_id');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $appendDate = '';
        $appendMonth = '';
        $appendYear = '';
        $appendCompany = '';
        $appendSite = '';
        if ($start_date != '' && $end_date == '') {
            $appendDate = "AND po.purchase_order_date >= '{$start_date}' AND po.purchase_order_date <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $appendDate = "AND po.purchase_order_date >= '{$start_date}' AND po.purchase_order_date <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $appendDate = "AND po.purchase_order_date >= '{$start_date}' AND po.purchase_order_date <= '{$end_date}'";
        } else if ($monthVal == '' && $yearVal == ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $end_date = $year . '-' . $month . '-' . '31';
            $appendDate = "AND po.purchase_order_date >= '{$start_date}' AND po.purchase_order_date <= '{$end_date}'";
        }

        if ($start_date == '' && $end_date == '') {
            if ($monthVal != '') {
                $appendDate .= "AND DATE_FORMAT(po.purchase_order_date, '%m') = '{$monthVal}'" ;
            }

            if ($yearVal != '') {
                $appendDate .= "AND DATE_FORMAT(po.purchase_order_date, '%Y') = '{$yearVal}'" ;
            }
        }

        if ($medicine_company_id != '') {
            $appendCompany = "AND pop.medicine_company_id = '{$medicine_company_id}'" ;
        }

        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSite ="AND po.site_id = {$cpSiteIdSession}";
        }
        $rows = '';

        $SQL = "
        SELECT p.title AS product, p.product_id
        FROM product p
        WHERE p.medicine_company_id = '{$medicine_company_id}'
        ";
        $result = $db->sql_query($SQL);
        while ($row1 = $db->sql_fetchrow($result)) {

            $SQL2 = "
            SELECT SUM(pop.cost_price * pop.qty) AS total_value
            FROM po_product pop
            LEFT JOIN (purchase_order po) ON (po.purchase_order_id = pop.purchase_order_id)
            WHERE pop.product_id = '{$row1['product_id']}'
            {$appendSite}
            {$appendDate}
            GROUP BY pop.product_id
            ";
            $result2 = $db->sql_query($SQL2);
            $row2 = $db->sql_fetchrow($result2);
            $total_value = number_format($row2['total_value'], 2);

            $grandTotal += $row2['total_value'];
        }

        $grandTotal = number_format($grandTotal, 2);

        if ($month != '') {
            $monthValAppendSql = "AND DATE_FORMAT(mc.incentive_date, '%m') = '{$month}'" ;
        }
        if ($year != '') {
            $yearValAppendSql = "AND DATE_FORMAT(mc.incentive_date, '%Y') = '{$year}'" ;
        }

        $SQLIN = "
        SELECT mc.*
        FROM `mfrcompany_incentive` mc
        WHERE mc.incentive != 0.00
        AND mc.medicine_company_id = '{$medicine_company_id}'
        {$monthValAppendSql}
        {$yearValAppendSql} 
        ";
        $resultIN   = $db->sql_query($SQLIN);
        while ($rowIN = $db->sql_fetchrow($resultIN)) {
            
            $incentiveTotal += $rowIN['incentive'];
        }
        $incentiveTotal = number_format($incentiveTotal, 2);

        $rowMC = $fn->getRecordRowByID('medicine_company', 'medicine_company_id', $medicine_company_id);

        $text = "
        <h2>Mfg Company Report</h2>
        <div class='float_left'>
            Manager Name : <b>{$rowMC['manager_name']}</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            Rep Name : <b>{$rowMC['rep_name']}</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            Contact No. : <b>{$rowMC['phone']}</b>  
        </div>
        <div class='float_right grandTotalProductDisplay'>
            Total Incentive: {$incentiveTotal}
            Grand Total: {$grandTotal}
        </div>
        <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Qty</th>
                        <th>Price</th>
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
        $month          = date('m');
        $year           = date('Y');
        $current_date   = date('Y-m-d');
        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $monthVal       = $fn->getReqParam('month');
        $yearVal        = $fn->getReqParam('year');
        $site_id        = $fn->getReqParam('site_id');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $medicine_company_id   = $fn->getReqParam('medicine_company_id');
        if($medicine_company_id != ''){
        foreach($this->model->dataArray as $row){
            $appendDate = '';
            $appendMonth = '';
            $appendYear = '';
            $appendCompany = '';
            $appendSite = '';
            if ($start_date != '' && $end_date == '') {
                $appendDate = "AND po.purchase_order_date >= '{$start_date}' AND po.purchase_order_date <= '{$current_date}'";
            } else if ($start_date == '' && $end_date != ''){
                $start_date = $year . '-' . $month . '-' . '01';
                $appendDate = "AND po.purchase_order_date >= '{$start_date}' AND po.purchase_order_date <= '{$end_date}'";
            } else if ($start_date != '' && $end_date != '') {
                $appendDate = "AND po.purchase_order_date >= '{$start_date}' AND po.purchase_order_date <= '{$end_date}'";
            } else if ($monthVal == '' && $yearVal == ''){
                $start_date = $year . '-' . $month . '-' . '01';
                $end_date = $year . '-' . $month . '-' . '31';
                $appendDate = "AND po.purchase_order_date >= '{$start_date}' AND po.purchase_order_date <= '{$end_date}'";
            }

            if ($start_date == '' && $end_date == '') {
                if ($monthVal != '') {
                    $appendDate .= "AND DATE_FORMAT(po.purchase_order_date, '%m') = '{$monthVal}'" ;
                }

                if ($yearVal != '') {
                    $appendDate .= "AND DATE_FORMAT(po.purchase_order_date, '%Y') = '{$yearVal}'" ;
                }
            }

            if ($medicine_company_id != '') {
                $appendCompany = "AND pop.medicine_company_id = '{$medicine_company_id}'" ;
            }

            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSite ="AND po.site_id = {$cpSiteIdSession}";
            }
            $rows = '';

            $SQL = "
            SELECT p.title AS product, p.product_id
            FROM product p
            WHERE p.medicine_company_id = '{$medicine_company_id}'
            ";
            $result = $db->sql_query($SQL);
            while ($row1 = $db->sql_fetchrow($result)) {

                $SQL2 = "
                SELECT SUM(pop.cost_price * pop.qty) AS total_value
                      ,SUM(pop.qty) AS StripQty
                FROM po_product pop
                LEFT JOIN (purchase_order po) ON (po.purchase_order_id = pop.purchase_order_id)
                WHERE pop.product_id = '{$row1['product_id']}'
                {$appendSite}
                {$appendDate}
                GROUP BY pop.product_id
                ";
                $result2 = $db->sql_query($SQL2);
                $row2 = $db->sql_fetchrow($result2);
                $total_value = number_format($row2['total_value'], 2);

                $rows .= "
                <tr>
                    <td>{$row1['product']}</td>
                    <td>{$row2['StripQty']}</td>
                    <td class='txtRight'>{$total_value}</td>
                </tr>
                ";
            }
        }
        }

        $text = "
        {$rows}
        ";

        return $text;
    }

}