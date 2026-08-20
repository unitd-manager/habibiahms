<?
class CPL_Admin_Modules_Tradingin_Inventory_View extends CP_Admin_Modules_Tradingin_Inventory_View
{
    /**
     *
     */
    function getList($dataArray){
        $listObj = Zend_Registry::get('listObj');
        $db      = Zend_Registry::get('db');
        $tv      = Zend_Registry::get('tv');
        $fn      = Zend_Registry::get('fn');
        $cpCfg   = Zend_Registry::get('cpCfg');
        
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $site_id =  '';

        $count = 0;
        $rows  = '';
        $siteIdForField = "";
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $site_id        = $cpSiteIdSession;
            $siteIdForField = $site_id;
        }

        //TO CREATE INVENTORY RECORDS FROM PRODUCT RECORD
        //$this->getCreateInventoryRecords();
        $stock = '';
        $adjust_stock = '';
        foreach ($dataArray as $row){
            $weight       = '';

            /*$SQLUpdate = "
            update inventory set actual_stock = {$stock}
            WHERE inventory_id = {$row['inventory_id']}
            ";
            $result1 = $db->sql_query($SQLUpdate);*/

            $SQLPO = "
            SELECT po.cost_price
                  ,po.selling_price
            FROM po_product po
            WHERE po.product_id = {$row['product_id']}
            ORDER BY po.po_product_id DESC
            ";

            $resultPO = $db->sql_query($SQLPO);
            $rowPO = $db->sql_fetchrow($resultPO);

            $priceCal = $rowPO['selling_price'] - $rowPO['cost_price'];

            $percentageCal = $priceCal / $rowPO['selling_price'] * 100;
            $percentageCal = round($percentageCal);

            $weightColumnValue = "";
            if($cpCfg['showWeightInPos'] == 1){
                $weightColumnValue = $listObj->getListDataCell($weight, 'center');
            }

            $recMS = $fn->getRecordByCondition("medicine_site", "product_id = '{$row['product_id']}' AND site_id = '{$site_id}'");

            $rack_qty = '';
            if($recMS['rack_qty'] > 0){
                $rack_qty = "{$recMS['rack_qty']}";
            }

            $item_code     = "<a href='index.php?_topRm=pharmacy&module=hms_product&_action=edit&product_id={$row['product_id']}'>PROD-{$row['item_code']}</a>";
            $productCodeTd = $listObj->getListDataCell($item_code, 'center');
            list($intStockOverall, $decStockOverall) = explode('.', $row['actual_stock'.$siteIdForField]);
            $stock = $intStockOverall;
            $actual_stock = $stock;
            $stock = number_format($stock);

            $stock = "
            <span class='stockUpdateList_{$row['inventory_id']} pull-right'>{$stock}</span>
            ";

            /*$SQLIBS = "
            SELECT ibs.product_id
                  ,ibs.po_product_id
                  ,ibs.current_stock
                  ,DATEDIFF(po.expiry_date, Now()) AS days
            FROM inventory_batchwise_stock ibs
            LEFT JOIN po_product po ON (po.po_product_id = ibs.po_product_id)
            WHERE ibs.product_id = {$row['product_id']}
              AND ibs.site_id = {$site_id}
              AND ibs.current_stock > 0
              AND DATEDIFF(po.expiry_date, Now()) < 100
            ";

            $resultIBS = $db->sql_query($SQLIBS);
            $rowIBS = $db->sql_fetchrow($resultIBS);
            $numRowsIBS = $db->sql_numrows($resultIBS);
            if($numRowsIBS > 0){
                 $hightlightDueTasks = $listObj->getListRowHeader($row, $count, 'invExpColor');
            }

            $SQLIBS1 = "
            SELECT ibs.product_id
                  ,ibs.po_product_id
                  ,ibs.current_stock
                  ,DATEDIFF(po.expiry_date, Now()) AS days
            FROM inventory_batchwise_stock ibs
            LEFT JOIN po_product po ON (po.po_product_id = ibs.po_product_id)
            WHERE ibs.product_id = {$row['product_id']}
              AND ibs.site_id = {$site_id}
              AND ibs.current_stock > 0
              AND DATEDIFF(po.expiry_date, Now()) < 30
            ";

            $resultIBS1 = $db->sql_query($SQLIBS1);
            $rowIBS1 = $db->sql_fetchrow($resultIBS1);
            $numRowsIBS1 = $db->sql_numrows($resultIBS1);
            if($numRowsIBS1 > 0){
                 $hightlightDueTasks = $listObj->getListRowHeader($row, $count, 'invExpColorRed');
            }
            */

            $hightlightDueTasks = $listObj->getListRowHeader($row, $count);

            $start_date = date('Y-m-d', strtotime('-90 days'));
            $end_date   = date("Y-m-d", strtotime("yesterday"));

            $SQLSales = "
            SELECT SUM(it.qty) AS qty
            FROM invoice i
            LEFT JOIN (invoice_item it) ON (it.invoice_id = i.invoice_id)
            WHERE i.invoice_date >= '{$start_date}' AND i.invoice_date <= '{$end_date}'
              AND i.status != 'Cancelled'
              AND i.site_id = {$site_id}
              AND it.qty != 0
              AND it.record_id = {$row['product_id']}
            GROUP BY it.record_id  
            ";
            $resultSales = $db->sql_query($SQLSales);
            $rowSales = $db->sql_fetchrow($resultSales);

            $avgQty = round($rowSales['qty'] / 3);

            $adjustStockColumn = "";
            $stockColumn = "";
            $avgColumn = "";
            if ($_SESSION['userGroupName'] == "Super Administrator" || $_SESSION['userGroupName'] == "Administrator") {
                $adjust_stock = "
                <a class='batchStock ml10' inventory_id='{$row['inventory_id']}' product_id='{$row['product_id']}' site_id='{$site_id}'>
                    Adjust Stock
                </a>
                ";

                $adjustStockColumn = $listObj->getListDataCell($adjust_stock);
                if($row['not_add_in_stock'] == 1) {
                    $stock = "";
                }

                $stockColumn = "{$listObj->getListDataCell($stock)}";
                $avgColumn   = "{$listObj->getListDataCell($avgQty, 'right')}";
            }

            $manual_stock = "
            <a class='manualStock ml10' product_id='{$row['product_id']}' actual_stock='{$actual_stock}' site_id='{$site_id}' >
                Manual Stock
            </a>
            ";
            $currentDate = date("Y-m-d");

            $SQLMS = "
            SELECT ms.stock, ms.actual_stock, ms.date
            FROM manual_stock ms
            WHERE ms.product_id = {$row['product_id']}
              AND ms.site_id = {$site_id}
              ORDER BY ms.manual_stock_id DESC
            ";
            $resultMS   = $db->sql_query($SQLMS);
            $rowMS = $db->sql_fetchrow($resultMS);

            $manualStockToday = '';
            $manualStockDate = '';
            if ($rowMS['stock'] != ''){
                $date = $fn->getCPDate($rowMS['date'], 'd-m-Y');
                //$manualStockToday = $rowMS['stock'].'('. $rowMS['actual_stock'] .')';
                $manualStockToday = $rowMS['stock'];
                $manualStockDate = $date;
            }

            $manualStockTodayDisplay = '';
            $manualStockDateDisplay = '';
            if ($tv['special_search'] == "Stock Difference") {
                $manualStockTodayDisplay = $listObj->getListDataCell($manualStockToday, 'right');
                $manualStockDateDisplay = $listObj->getListDataCell($manualStockDate);
            }

            $rows .= "
            {$hightlightDueTasks}
            {$listObj->getListDataCell('STK-'.$row['inventory_code'])}
            {$productCodeTd}
            {$listObj->getGoToDetailText($count, $row['product_name'] . " ({$percentageCal}%)")}
            {$stockColumn}
            {$manualStockTodayDisplay}
            {$manualStockDateDisplay}
            {$listObj->getListDataCell($manual_stock)}
            {$avgColumn}
            {$listObj->getListDataCell($rack_qty, 'right')}
            {$adjustStockColumn}
            {$listObj->getListDataCell($row['minimum_order_level'])}
            {$listObj->getListRowEnd($row['inventory_id'])}
            ";

            $count++ ;
        }

        $weightColumn = "";
        if($cpCfg['showWeightInPos'] == 1){
            $weightColumn = $listObj->getListHeaderCell('weight', '', 'txtCenter');
        }

        $productCodeTh = $listObj->getListHeaderCell('Item Code', 'item_code' , 'txtCenter');

        $adjustStockColumnHeading = "";
        $stockColumnHeading = "";
        $avgColumnHeading = "";
        if ($_SESSION['userGroupName'] == "Super Administrator" || $_SESSION['userGroupName'] == "Administrator") {
            $adjustStockColumnHeading = $listObj->getListHeaderCell('Adjust Stock', '');
            $stockColumnHeading = $listObj->getListHeaderCell('Stock', 'actual_stock'.$siteIdForField);
            $avgColumnHeading = $listObj->getListHeaderCell('Last 3 Months(Avg)', '');
        }

        $msTodayLblDisplay = '';
        $msDateLblDisplay = '';
        if ($tv['special_search'] == "Stock Difference") {
            $msTodayLblDisplay = $listObj->getListHeaderCell('MS', '');
            $msDateLblDisplay = $listObj->getListHeaderCell('MS Date', '');
        }

        $text = "
        {$listObj->getListHeader()}
        {$listObj->getListHeaderCell('Inventory Code', 'inventory_code')}
        {$productCodeTh}
        {$listObj->getListHeaderCell('Name', 'product_name')}
        {$stockColumnHeading}
        {$msTodayLblDisplay}
        {$msDateLblDisplay}
        {$listObj->getListHeaderCell('Manual Stock', '')}
        {$avgColumnHeading}
        {$listObj->getListHeaderCell('Rack Qty', 'rack_qty')}
        {$adjustStockColumnHeading}
        {$listObj->getListHeaderCell('MOL', 'i.minimum_order_level' )}
        {$listObj->getListHeaderEnd()}
        {$rows}
        {$listObj->getListFooter()}
        ";

        return $text;
    }

    /**
     *
     */
    function getNew(){
        $formObj = Zend_Registry::get('formObj');

        $text = "
        ";

        return $text;
    }

    /**
     *
     */
    function getEdit($row){
        $formObj = Zend_Registry::get('formObj');
        $cpCfg   = Zend_Registry::get('cpCfg');
        $tv      = Zend_Registry::get('tv');
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $db      = Zend_Registry::get('db');

        $formObj->mode = $tv['action'];
        $expNoEdit = array('isEditable' => 0);
        $site_id   = $fn->getSessionParam('cp_site_id');

            /*,(SELECT SUM(ordItem.qty) FROM order_item ordItem
            LEFT JOIN (`order` o) ON (o.order_id = ordItem.order_id)
            WHERE ordItem.record_id = {$row['product_id']}
            AND (o.order_status = 'Paid' || o.order_status = 'Due')
            ) as product_qty_sold_from_quote*/
        /*$stockArray   = $fn->getStockForProduct($row['product_id']);
        $stock        = $stockArray['OverallStock'];
        $purchasedQty = number_format($stockArray['PurchasedQty'], 0);
        $soldQty      = $stockArray['SoldQty'];*/

        /*Begin Stock Details For The Location */

        $appendSQLDeveloper = "";
        $LimitSite = "";
        //$LimitSite = "LIMIT 2";
        if(!$_SESSION['isDeveloper']) {
            $LimitSite = "";
            $appendSQLDeveloper = "WHERE site_id = {$site_id}";
        }

        $SQLsitedetail="
        SELECT site_id
               ,title
        FROM site
        {$appendSQLDeveloper}
        {$LimitSite}
        ";
        $resultsitedetail = $db->sql_query($SQLsitedetail);

        $PurchasedWeight = '';
        $SoldWeight      = '';
        $StockWeight     = '';
        $StockRows       = '';

        while($rowsitedetail = $db->sql_fetchrow($resultsitedetail)) {
            $SQLOthersite = "
            SELECT
                 (SELECT SUM(CASE 
                            WHEN pp.pack_size REGEXP '^[+-]?[0-9]+$'
                            THEN pp.qty * pp.pack_size
                            ELSE pp.qty END) AS purchased_qty
                 FROM po_product pp
                 LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                 WHERE pp.product_id = {$row['product_id']}
                 AND po.status != 'Cancelled'
                 AND po.site_id = {$rowsitedetail['site_id']}
                 ) as product_qty_purchased

               ,(SELECT SUM(damage_qty) FROM po_product pp
                 LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                 WHERE pp.product_id = {$row['product_id']}
                 AND po.site_id = {$rowsitedetail['site_id']}) as damage_qty

                ,(SELECT SUM(inItm.qty) FROM invoice_item inItm
                LEFT JOIN (`invoice` inv) ON (inv.invoice_id = inItm.invoice_id)
                WHERE inItm.record_id = {$row['product_id']}
                AND inItm.not_add_in_stock != 1
                AND inv.status  = 'Paid'
                AND inv.invoice_type = 'POS'
                AND inv.site_id = {$rowsitedetail['site_id']}
                ) as product_qty_sold

                ,(SELECT SUM(CASE 
                                WHEN sth.pack_size REGEXP '^[+-]?[0-9]+$'
                                THEN sth.qty * sth.pack_size
                                ELSE sth.qty END)
                FROM stock_transfer_history sth
                LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
                WHERE sth.product_id = {$row['product_id']}
                AND st.from_location = '{$rowsitedetail['site_id']}'
                AND st.status = 'Delivered'
                ) as product_qty_sold_from_stock

                ,(SELECT SUM(CASE 
                                WHEN sth.pack_size REGEXP '^[+-]?[0-9]+$'
                                THEN sth.qty * sth.pack_size
                                ELSE sth.qty END)
                FROM stock_transfer_history sth
                LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
                WHERE sth.product_id = {$row['product_id']}
                AND st.to_location = '{$rowsitedetail['site_id']}'
                AND st.status = 'Delivered'
                ) as product_qty_got_from_stock

                ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                WHERE ini.record_id = {$row['product_id']}
                  AND srh.status = 'Approved'
                  AND inv.site_id = {$rowsitedetail['site_id']}
                ) as sales_return_qty

                ,(SELECT changed_stock 
                  FROM inventory
                  WHERE product_id = {$row['product_id']}
                  AND site_id = {$rowsitedetail['site_id']}
                ) AS adjust_stock
            ";
            $resultothersite = $db->sql_query($SQLOthersite);
            $rowothersite    = $db->sql_fetchrow($resultothersite);

            $stock        = $rowothersite['product_qty_purchased'] - $rowothersite['product_qty_sold_from_stock'] - $rowothersite['product_qty_sold'] + $rowothersite['sales_return_qty'] - $rowothersite['damage_qty'] + $rowothersite['product_qty_got_from_stock'] + $rowothersite['adjust_stock'];
            $purchasedQty = number_format($rowothersite['product_qty_purchased'], 0);
            $soldQty      = number_format($rowothersite['product_qty_sold'], 0);
            $stockTransferredIn  = number_format($rowothersite['product_qty_got_from_stock'], 0);
            $stockTransferredOut = number_format($rowothersite['product_qty_sold_from_stock'], 0);

            if($soldQty == "") {
                $soldQty = 0;
            }

            $siteIdForField = "";
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $siteId         = $rowsitedetail['site_id'];
                $siteIdForField = $siteId;
            }
            
            list($intStockOverall, $decStockOverall) = explode('.', $row['actual_stock'.$siteIdForField]);
            $stock = $intStockOverall;
            $stock = number_format($stock);

            if($cpCfg['showWeightInPos'] == 1){

                if($PurchasedWeight != "" && $PurchasedWeight > 0){
                    $PurchasedWeight = " | Weight ({$PurchasedWeight})";
                }
                else{
                    $PurchasedWeight = "";
                }

                if($SoldWeight != "" && $SoldWeight > 0){
                    $SoldWeight = " | Weight ({$SoldWeight})";
                }
                else{
                    $SoldWeight = "";
                }

                if($StockWeight != "" && $StockWeight > 0){
                    $StockWeight = " | Weight ({$StockWeight})";
                }
                else{
                    $StockWeight = "";
                }

                $StockRows .= "
                <tr>
                    <td>{$rowsitedetail['title']}</td>
                    <td>{$purchasedQty}</td>
                    <td>{$stockTransferredIn}</td>
                    <td>{$soldQty}</td>
                    <td>{$stockTransferredOut}</td>
                    <td>{$stock}</td>
                </tr>
                ";
            }
            else{
                $adjustStock = "";
                if ($_SESSION['userGroupName'] == "Super Administrator" || $_SESSION['userGroupName'] == "Administrator") {
                    $adjustStock = "
                    <a class='batchStock ml20' inventory_id='{$row['inventory_id']}' product_id='{$row['product_id']}' site_id='{$rowsitedetail['site_id']}'>
                        Adjust Stock
                    </a>";
                }

                $StockRows .= "
                <tr>
                    <td>{$rowsitedetail['title']}</td>
                    <td>
                        {$purchasedQty}
                        <a class='poLinked ml20' product_id='{$row['product_id']}' site_id='{$rowsitedetail['site_id']}'>
                            View
                        </a>
                    </td>
                    <td>
                        {$stockTransferredIn}
                        <a class='stockTransferredIn ml20' product_id='{$row['product_id']}' site_id='{$rowsitedetail['site_id']}'>
                            View
                        </a>
                    </td>
                    <td>
                        {$soldQty}
                        <a class='billsLinked ml20' product_id='{$row['product_id']}' site_id='{$rowsitedetail['site_id']}'>
                            View
                        </a>
                    </td>
                    <td>
                        {$stockTransferredOut}
                        <a class='stockTransferredOut ml20' product_id='{$row['product_id']}' site_id='{$rowsitedetail['site_id']}'>
                            View
                        </a>
                    </td>
                    <td>
                        <span class='stockUpdate_{$rowsitedetail['site_id']}_{$row['inventory_id']} text-size-18px'>{$stock}</span>
                        {$adjustStock}
                    </td>
                </tr>
                ";
            }
        }

        $creation_date     = $fn->getCPDate($row['creation_date'], 'd-m-Y H:i:s');
        $modification_date = $fn->getCPDate($row['modification_date'], 'd-m-Y H:i:s');


        $item_code  = "<a href='index.php?_topRm=pharmacy&module=hms_product&_action=edit&product_id={$row['product_id']}'>PROD-{$row['item_code']}</a>";

        $text = "
        <div class='linkPortalWrapper'>
            <div expanded='1' class='header'>
                <div class='floatbox'>
                    <div class='float_left'>Product Details</div>
                    <div class='toggle'></div>
                    <div class='float_right'>Creation : {$row['created_by']} on {$creation_date} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Modified : {$row['modified_by']} {$modification_date}</div>
                </div>
            </div>
            <div>
                <div class='linkPortalDataWrapper'>
                    <table class='thinlist'>
                        <tbody>
                            <tr>
                                <td width='25%'>{$formObj->getTBRow('Name', 'product_name', $row['product_name'], $expNoEdit)}</td>
                                <td width='25%'>{$formObj->getTBRow('Item Code', 'item_code', $item_code, $expNoEdit)}</td>
                                <td width='25%'>{$formObj->getTBRow('MOL', 'minimum_order_level', $row['minimum_order_level'])}</td>
                                <td width='25%'>{$formObj->getTARow('Notes', 'notes', $row['notes'])}</td>
                            </tr>
                        </tbody>
                    </table>
                    <table class='thinlist stockDetailsTable'>
                        <thead>
                            <tr>
                                <th>Location</th>
                                <th>Total Purchased Qty</th>
                                <th>Total Stock Transferred(In)</th>
                                <th>Total Sold Qty</th>
                                <th>Total Stock Transferred(Out)</th>
                                <th>Total Available Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$StockRows}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        ";

        return $text;
    }

    /**
     *
     */
    function getPrintDetail($row){
        $db = Zend_Registry::get('db');
        return $this->getDetail($row);
    }

    /**
     *
     */
    function getRightPanel($row){
        $fn = Zend_Registry::get('fn');
        $displayLinkData = Zend_Registry::get('displayLinkData');
        $media = Zend_Registry::get('media');

        /*$text = "
        {$this->getBatchWiseStockDisplay($row)}
        {$this->getPurchaseOrderDisplay($row)}
        {$this->getOrderDisplay($row)}
        ";*/
        $text = "";

        return $text;
    }

    /**
     *
     */
    function getQuickSearch() {
        $db     = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpUtil = Zend_Registry::get('cpUtil');
        $tv     = Zend_Registry::get('tv');
        $fn     = Zend_Registry::get('fn');

        $supplier_id         = $fn->getReqParam('supplier_id');
        $category_id         = $fn->getReqParam('category_id');
        $minimum_order_level = $fn->getReqParam('minimum_order_level');
        $expiry_date         = $fn->getReqParam('expiry_date');

        $spArray = array(
            ""
           ,"Stock Difference"
           ,"Flagged"
        );
//           ,"Past Stock Difference"

        $spArray1 = array(
            ""
           ,"MOL Products"
        );

        $spArray2 = array(
            ""
           ,"Expiry Date < 100"
           ,"Expiry Date < 30"
        );

        $sqlSupplier = "
        SELECT c.company_id
              ,c.company_name
        FROM company c
        WHERE c.category = 'Supplier'
        ORDER BY c.company_name
        ";

        $sqlCategory = "
        SELECT c.category_id
              ,c.title
        FROM category c
        ";

        $urlFlaggedMedicine = "index.php?module=tradingin_inventory&_spAction=printFlaggedMedicine&showHTML=0";

        $printFlaggedMedicine = "<a href='{$urlFlaggedMedicine}' id='printFlaggedMedicine' class='btn btn-info' target='_blank'>Print PO Medicine</a>";

        $text = "
        <td class='fieldValue'>
            <select name='category_id'>
                <option value=''>Category</option>
                {$dbUtil->getDropDownFromSQLCols2($db, $sqlCategory, $category_id)}
            </select>
        </td>
        <td>
            <select name='special_search'>
                <option value=''>Special Search</option
                {$cpUtil->getDropDown1($spArray, $tv['special_search'])}
           </select>
        </td>
        <td>
            <select name='minimum_order_level'>
                <option value=''>Minimum Order Level</option
                {$cpUtil->getDropDown1($spArray1, $minimum_order_level)}
           </select>
        </td>
        <td>
            <select name='expiry_date'>
                <option value=''>Expiry</option
                {$cpUtil->getDropDown1($spArray2, $expiry_date)}
           </select>
        </td>
        <td>{$printFlaggedMedicine}</td>
        ";

        return $text;
    }
    /**
     *
     */
    function getCreateInventoryRecords() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');

        /*$SQL = "
        SELECT p.product_id
        FROM product p
        WHERE p.product_id NOT IN(
            SELECT invent.product_id
            FROM inventory invent
        )
        ORDER BY p.product_id
       ";*/

       $SQL = "
        SELECT p.product_id
        FROM product p
        LEFT JOIN inventory inv ON inv.product_id = p.product_id
        WHERE inv.product_id IS NULL
       ";

        $result = $db->sql_query($SQL);

        while ($row = $db->sql_fetchrow($result)) {

            $SQLInvCode = "
            SELECT MAX(inventory_code) + 1 AS inv_code
            FROM `inventory`
            ";
            $resultInvCode = $db->sql_query($SQLInvCode);
            $rowInvCode    = $db->sql_fetchrow($resultInvCode);

            if($rowInvCode['inv_code'] != ""){
                $inv_code = $rowInvCode['inv_code'];
            }
            else{
                $inv_code = "1001";
            }

            $fa = array();

            $fa['product_id']     = $row['product_id'];
            $fa['creation_date']  = date('Y-m-d H:i:s');
            $fa['inventory_code'] = $inv_code;

            $inventory_id = $fn->addRecord($fa, 'inventory');
        }
    }

    /**
     *
     */
    function getUpdateInventoryCode() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        /* Updation of Inventory order Code */
        $inventoryCode = $fn->getSettingsValueByKey("inventoryCode");

        $SQL    = "UPDATE setting SET value = (value+1) WHERE key_text = 'inventoryCode'";
        $result = $db->sql_query($SQL);

        return $inventoryCode;
    }

    /**
     */
    function getOrderDisplay(){
        $cpCfg   = Zend_Registry::get('cpCfg');
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil  = Zend_Registry::get('dbUtil');

        $product_id = $fn->getReqParam('product_id');
        $site_id    = $fn->getReqParam('site_id');

        $formAction = '';

        $text = "
        <div class='linkPortalWrapper panel tradingin_inventory_orderLink'>
            <div class='panel panel-primary'>
                <div class='panel-heading'>
                    <div expanded='1'>
                        <div class='floatbox'>
                            <div class='float_left RightPanelHeading'>Bills Linked</div>
                            <div class='txtRight'>
                              <div class='toggle'></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <div class='linkPortalDataWrapper'>
                        <form id='orderItemPrint' class='' method='post' action='{$formAction}'>
                            <div id='invoicePortalOuter'>
                                {$this->getOrderDisplayDetail($product_id, $site_id)}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        ";

        return $text;
    }

    /**
     *
     */
    function getOrderDisplayDetail($product_id, $site_id){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $rows  = "";
        $rowsPvt  = "";
        $links = "";
        $leftJoin  = "";
        $sqlAppend = "";
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $SQL = "
        SELECT DISTINCT o.order_id
              ,oi.order_item_id
              ,oi.item_title
              ,oi.unit_price
              ,oi.qty
              ,oi.qty * oi.unit_price
              ,o.order_date
              ,o.record_type
              ,o.creation_date AS orderCreation
              ,com.company_name
        FROM `order_item` oi
        LEFT JOIN (`order` o) ON (o.order_id = oi.order_id)
        LEFT JOIN (`invoice` inv) ON (o.order_id = inv.order_id)
        LEFT JOIN company com ON com.company_id = o.company_id
        WHERE oi.record_id = {$product_id}
          AND o.site_id = {$site_id}
          AND (o.order_status = 'Paid' || o.order_status = 'Due' || o.order_status = 'Partial Payment')
          AND (inv.status = 'Paid' || inv.status = 'Due' || inv.status = 'Partial Payment')
        ORDER BY o.creation_date DESC
        LIMIT 25
        ";

        $result   = $db->sql_query($SQL);
        $client = '';
        while ($rowOI = $db->sql_fetchrow($result)) {
            if($rowOI['record_type'] == 'POS'){
                $client = 'POS';
            }
            else{
                $client = $rowOI['company_name'];
            }

            /*$SQLINV = "
            SELECT DISTINCT i.invoice_id
                  ,it.unit_price
                  ,it.qty
                  ,i.invoice_date
                  ,i.invoice_code
            FROM `invoice_item` it
            LEFT JOIN (`invoice` i) ON (i.invoice_id = it.invoice_id)
            WHERE it.record_id = {$product_id}
            AND i.order_id = {$rowOI['order_id']}
            AND (i.status = 'Paid' || i.status = 'Due' || i.status = 'Partial Payment')
            ";

            $resultINV   = $db->sql_query($SQLINV);
            $rowsInv = '';
            while ($rowINV = $db->sql_fetchrow($resultINV)) {
                $urlPrint = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=printInvoiceRecord&orderNo={$rowOI['order_id']}&showHTML=0";

                if($rowINV['invoice_code'] == ""){
                    $invoice_code = 'INV - '.$rowINV['invoice_id']; 
                }
                else{
                    $invoice_code = $rowINV['invoice_code'];
                }
                
                $rowsInv .="
                <tr>
                    <td>
                        <a href='{$urlPrint}' target='_blank'>
                            <u>{$invoice_code}</u>
                        </a>
                    </td>
                    <td>{$fn->getCPDate($rowINV['invoice_date'], 'd-m-Y')}</td>
                    <td>{$rowINV['qty']}</td>
                    <td class='txtRight'>{$rowINV['unit_price']}</td>
                </tr>
                ";
            }*/

            if($rowOI['order_id'] < 10){
                $orderNo = '0000' . $rowOI['order_id'];
            }
            else if($rowOI['order_id'] <= 99){
                $orderNo = '000' . $rowOI['order_id'];
            }
            else if($rowOI['order_id'] <= 999){
                $orderNo = '00' . $rowOI['order_id'];
            }
            else if($rowOI['order_id'] <= 9999){
                $orderNo = '0' . $rowOI['order_id'];
            }
            else{
                $orderNo = $rowOI['order_id'];
            }

            $OrderLink = $orderNo;
            if($cpSiteIdSession == $site_id) {
                $OrderEditLink = "index.php?_topRm=pharmacy&module=hms_order&_action=edit&order_id={$rowOI['order_id']}";
                $OrderLink = "  <a href='{$OrderEditLink}' target='_blank'>
                                    <u>{$orderNo}</u>
                                </a>";
            }

            $rows .= "
            <tr class='orderRightPanelTr'>
                <td>
                    {$OrderLink}
                </td>
                <td>{$fn->getCPDate($rowOI['order_date'], 'd-m-Y')}</td>
                <td>{$fn->getCPDate($rowOI['orderCreation'], 'H:i:s')}</td>
                <td class='txtRight'>{$rowOI['unit_price']}</td>
                <td>{$rowOI['qty']}</td>
                <td>{$client}</td>
            </tr>
            ";

            /*<tr>
                <td></td>
                <td colspan='4'>
                    <table class='thinlist'>
                        <tr>
                            <th>Invoice Code</th>
                            <th>Date</th>
                            <th>PCS</th>
                            <th class='txtRight'>Amount</th>
                        </tr>
                        {$rowsInv}
                    </table>
                </td>
            </tr>*/
        }

        //style='background-color:#0F9191; color:#ffffff'

        $header ="
        <tr style='background-color:#EAEAE8;'>
            <th>Order Id</th>
            <th>Date</th>
            <th>Time</th>
            <th class='txtRight'>Amount</th>
            <th>PCS</th>
            <th>Client</th>
        </tr>
        ";

        $text = "
        <table class='thinlist' width='100%'>
            {$header}
            {$rows}
        </table>
        ";

        return $text;
    }

    /**
     */
    function getPurchaseOrderDisplay(){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $product_id = $fn->getReqParam('product_id');
        $site_id = $fn->getReqParam('site_id');

        $formAction = '';

        $text = "
        <div class='linkPortalWrapper panel tradingin_inventory_purchaseOrderLink'>
            <div class='panel panel-primary'>
                <div class='panel-heading'>
                    <div expanded='1'>
                        <div class='floatbox'>
                            <div class='float_left RightPanelHeading'>Purchase Orders Linked</div>
                            <div class='txtRight'>
                              <div class='toggle'></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <div class='linkPortalDataWrapper'>
                        <form id='poPrint' class='' method='post' action='{$formAction}'>
                            <div id='invoicePortalOuter'>
                                {$this->getPurchaseOrderDisplayDetail($product_id, $site_id)}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        ";

        return $text;
    }

    /**
     */
    function getPurchaseOrderDisplayDetail($product_id, $site_id){
        $cpCfg   = Zend_Registry::get('cpCfg');
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil  = Zend_Registry::get('dbUtil');

        $rows         = "";
        $rowsPvt      = "";
        $links        = "";
        $leftJoin     = "";
        $sqlAppend    = "";
        $tdForSiteId  = "";
        $thForSiteId  = "";
        $leftjnAppend = "";

        if($cpCfg['cp.hasMultiUniqueSites']  == true){
            $sqlAppend = ",st.title as site_title";
            $leftjnAppend = "
            LEFT JOIN site st ON st.site_id = po.site_id";
        }

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $SQL = "
        SELECT pop.cost_price
              ,pop.qty
              ,com.company_name AS supplier_name
              ,po.po_code
              ,po.purchase_order_date
              ,po.purchase_order_id
              ,po.creation_date
              ,p.title AS product_name
              ,pop.pack_size
              ,pop.selling_price
              ,pop.batch_no
              {$sqlAppend}
        FROM po_product pop
        LEFT JOIN purchase_order po ON po.purchase_order_id = pop.purchase_order_id
        LEFT JOIN product p ON p.product_id = pop.product_id
        LEFT JOIN supplier com ON pop.supplier_id = com.supplier_id
        {$leftjnAppend}
        WHERE pop.product_id = {$product_id}
          AND po.site_id = {$site_id}
        ORDER BY po.purchase_order_date DESC
        LIMIT 25
        ";
        $result   = $db->sql_query($SQL);

        $product_name = "";
        while ($rowPo = $db->sql_fetchrow($result)) {
            /*if($cpCfg['cp.hasMultiUniqueSites']  == true){
                $tdForSiteId = "<td>{$rowPo['site_title']}</td>";
            }*/

            if($rowPo['purchase_order_date'] == '' || $rowPo['purchase_order_date'] == 0){
                $purchase_order_date = $fn->getCPDate($rowPo['creation_date'], 'd-m-Y');
            }
            else{
                $purchase_order_date = $fn->getCPDate($rowPo['purchase_order_date'], 'd-m-Y');
            }

            $SQLSite = "
            SELECT title
            FROM site
            WHERE site_id = '{$site_id}'
            ";
            $resultSite = $db->sql_query($SQLSite);
            $rowSite    = $db->sql_fetchrow($resultSite);
            $siteArr    = explode(" ", $rowSite['title']);
            $sitePrefix = "";
            foreach($siteArr as $site) {
               $sitePrefix .= substr($site,0,1);
            }

            $po_code = $sitePrefix.' - '.$rowPo['po_code'];
            if($cpSiteIdSession == $site_id) {
                $po_code = "<a href='index.php?_topRm=pharmacy&module=tradingsg_purchaseOrder&_action=edit&record_id={$rowPo['purchase_order_id']}' target='_blank'><u>{$sitePrefix} - {$rowPo['po_code']}</u></a>";
            }

            $rows .= "
            <tr>
                <td>{$po_code}</td>
                <td>{$purchase_order_date}</td>
                <td>{$rowPo['batch_no']}</td>
                <td>{$rowPo['selling_price']}</td>
                <td>{$rowPo['pack_size']}</td>
                <td>{$rowPo['qty']}</td>
                <td>{$rowPo['supplier_name']}</td>
            </tr>
            ";

            $product_name = $rowPo['product_name'];
        }

        if($cpCfg['cp.hasMultiUniqueSites']  == true){
            $thForSiteId = "<th>Location</th>";
        }

        $header ="
        <tr style='background-color:#EAEAE8;'>
        <th>PO Code</th>
        <th>Date</th>
        <th>Batch No</th>
        <th>Amount</th>
        <th>Pack Size</th>
        <th>Qty</th>
        <th>Supplier</th>
        </tr>
        ";

        $text = "
        <table class='thinlist'>
            <tr>
                <th>{$product_name}</th>
            </tr>
        </table>
        <table class='thinlist'>
            {$header}
            {$rows}
        </table>
        ";

        return $text;
    }

    /**
     */
    function getBatchWiseStockDisplay1(){
        $cpCfg   = Zend_Registry::get('cpCfg');
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil  = Zend_Registry::get('dbUtil');

        $product_id = $fn->getReqParam('product_id');
        $site_id    = $fn->getReqParam('site_id');

        $thForSiteId   = "";
        $sqlAppend     = "";
        $tdForSiteId   = "";
        $thForSiteId   = "";
        $leftjnAppend  = "";
        $leftjnAppend2 = "";
        $rows          = "";

        if($cpCfg['cp.hasMultiUniqueSites']  == true){
            $sqlAppend = ",s.title as site_title";
            $leftjnAppend = "
            LEFT JOIN site s ON s.site_id = po.site_id";
            $leftjnAppend2 = "
            LEFT JOIN site s ON s.site_id = st.to_location";
        }

        $appendSql   = '';
        $sqlAppendSt = '';
        $stockTransferSQLForMultiSite = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql   = "AND po.site_id = {$site_id}";
            $sqlAppendSt = "AND st.to_location = {$site_id}";

            $stockTransferSQLForMultiSite = "
            UNION
            SELECT  sth.batch_no AS batch_no
                   ,sth.product_id
                   ,pp.expiry_date
                   {$sqlAppend}
            FROM stock_transfer_history sth
            LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
            LEFT JOIN po_product pp ON (pp.po_product_id = sth.po_product_id)
            {$leftjnAppend2}
            WHERE sth.product_id = {$product_id}
            AND st.status = 'Delivered'
              {$sqlAppendSt}
            GROUP BY batch_no
            ";
        }

        $SQLsitedetail="
        SELECT site_id
               ,title
        FROM site
        WHERE site_id = {$site_id}
        ";
        $resultsitedetail = $db->sql_query($SQLsitedetail);
        $rowsitedetail = $db->sql_fetchrow($resultsitedetail);

        $SQLBatchNo = "
        SELECT  pp.batch_no AS batch_no
               ,pp.product_id
               ,pp.expiry_date
               {$sqlAppend}
        FROM po_product pp
        LEFT JOIN product p ON (p.product_id = pp.product_id)
        LEFT JOIN purchase_order po ON (po.purchase_order_id = pp.purchase_order_id)
        {$leftjnAppend}
        WHERE pp.product_id = {$product_id}
          AND po.site_id = {$site_id}
        GROUP BY pp.batch_no
        {$stockTransferSQLForMultiSite}
        ";
        $resultBatchNo = $db->sql_query($SQLBatchNo);
        while ($rowBatchNo    = $db->sql_fetchrow($resultBatchNo)) {
            $appendSqlOrd  = "AND o.site_id = {$site_id}";
            $appendSqlPur  = "AND po.site_id = {$site_id}";
            $appendSqlInv  = "AND inv.site_id = {$site_id}";
            $appendSqlStk  = "AND st.from_location = '{$site_id}'";
            $appendSqlStk2 = "AND st.to_location = '{$site_id}'";

            $SQLOthersite = "
            SELECT
                (SELECT SUM(CASE 
                            WHEN pp.pack_size REGEXP '^[+-]?[0-9]+$'
                            THEN pp.qty * pp.pack_size
                            ELSE pp.qty END) AS purchased_qty
                FROM po_product pp
                LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                WHERE pp.product_id = {$rowBatchNo['product_id']}
                AND po.status != 'Cancelled'
                AND pp.batch_no = '{$rowBatchNo['batch_no']}'
                {$appendSqlPur}
                ) as product_qty_purchased

               ,(SELECT SUM(damage_qty) FROM po_product pp
                 LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                 WHERE pp.product_id = {$rowBatchNo['product_id']}
                 {$appendSqlPur}
                 AND pp.batch_no = '{$rowBatchNo['batch_no']}') as damage_qty

                ,(SELECT SUM(inItm.qty) FROM invoice_item inItm
                LEFT JOIN (`invoice` inv) ON (inv.invoice_id = inItm.invoice_id)
                WHERE inItm.record_id = {$rowBatchNo['product_id']}
                AND inItm.not_add_in_stock != 1
                AND inv.status = 'Paid'
                AND inv.invoice_type = 'POS'
                AND inItm.batch_no = '{$rowBatchNo['batch_no']}'
                {$appendSqlInv}
                ) as product_qty_sold

                ,(SELECT  SUM(CASE 
                            WHEN sth.pack_size REGEXP '^[+-]?[0-9]+$'
                            THEN sth.qty * sth.pack_size
                            ELSE sth.qty END)
                  FROM stock_transfer_history sth
                  LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
                  WHERE sth.product_id = {$rowBatchNo['product_id']}
                  AND sth.batch_no = '{$rowBatchNo['batch_no']}'
                  AND st.status = 'Delivered'
                  {$appendSqlStk}
                ) as product_qty_sold_from_stock

                ,(SELECT  SUM(CASE 
                            WHEN sth.pack_size REGEXP '^[+-]?[0-9]+$'
                            THEN sth.qty * sth.pack_size
                            ELSE sth.qty END)
                  FROM stock_transfer_history sth
                  LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
                  WHERE sth.product_id = {$rowBatchNo['product_id']}
                  AND sth.batch_no = '{$rowBatchNo['batch_no']}'
                  AND st.status = 'Delivered'
                  {$appendSqlStk2}
                ) as product_qty_sold_to_stock

                ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                WHERE ini.record_id = {$rowBatchNo['product_id']}
                  AND srh.status = 'Approved'
                  AND ini.batch_no = '{$rowBatchNo['batch_no']}'
                  {$appendSqlInv}
                ) as sales_return_qty
            ";
            $resultothersite = $db->sql_query($SQLOthersite);
            $rowothersite = $db->sql_fetchrow($resultothersite);

            $stock = $rowothersite['product_qty_purchased'] - $rowothersite['product_qty_sold_from_stock'] + $rowothersite['product_qty_sold_to_stock'] - $rowothersite['product_qty_sold'] + $rowothersite['sales_return_qty'] - $rowothersite['damage_qty'];

            $OverallStock   = $stock;
            $PurchasedQty   = $rowothersite['product_qty_purchased'];
            $SoldQty        = $rowothersite['product_qty_sold'] + $rowothersite['product_qty_sold_from_stock'];
            $SalesReturnQty = $rowothersite['sales_return_qty'];
            $DamagedQty     = $rowothersite['damage_qty'];
            $expiry_date    = $fn->getCPDate($rowBatchNo['expiry_date'], 'd-m-Y');

            $rows .= "
            <tr>
                <td>{$rowBatchNo['batch_no']}</td>
                <td>{$expiry_date}</td>
                <td>{$OverallStock}</td>
            </tr>
            ";

        }

        if($cpCfg['cp.hasMultiUniqueSites']  == true){
            $thForSiteId = "<th>Location</th>";
        }

        $header = "
        <tr style='background-color:#EAEAE8;'>
        <th>Batch No</th>
        <th>Expiry Date</th>
        <th>Total Available Qty</th>
        </tr>
        ";

        $table = "
        <table class='thinlist'>
            {$header}
            {$rows}
        </table>
        ";

        $formAction = '';

        $text = "
        <div class='linkPortalWrapper panel tradingin_inventory_batchWiseStockLink'>
            <div class='panel panel-primary'>
                <div class='panel-heading'>
                    <div expanded='1'>
                        <div class='floatbox'>
                            <div class='float_left RightPanelHeading'>Batch Wise Stock</div>
                            <div class='txtRight'>
                              <div class='toggle'></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <div class='linkPortalDataWrapper'>
                        <form id='batchwisestock' class='' method='post' action='{$formAction}'>
                            <div id='batchStockPortalOuter'>
                                {$table}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        ";

        return $text;
    }

    /**
     */
    function getBatchWiseStockDisplay(){
        $cpCfg   = Zend_Registry::get('cpCfg');
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil  = Zend_Registry::get('dbUtil');

        $product_id   = $fn->getReqParam('product_id');
        $site_id      = $fn->getReqParam('site_id');
        $inventory_id = $fn->getReqParam('inventory_id');

        $formAction = '';

        $text = "
        <div class='linkPortalWrapper panel tradingin_inventory_purchaseOrderLink'>
            <div class='panel panel-primary'>
                <div class='panel-heading'>
                    <div expanded='1'>
                        <div class='floatbox'>
                            <div class='float_left RightPanelHeading'>Batch Wise Stock Linked</div>
                            <div class='txtRight'></div>
                        </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <div class='linkPortalDataWrapper'>
                        <form id='poPrint' class='' method='post' action='{$formAction}'>
                            <div id='invoicePortalOuter'>
                                {$this->getBatchWiseStockDisplayDetail($inventory_id, $product_id, $site_id)}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        ";

        return $text;
    }

    /**
     */
    function getBatchWiseStockDisplayDetail($inventory_id, $product_id, $site_id){
        $cpCfg   = Zend_Registry::get('cpCfg');
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil  = Zend_Registry::get('dbUtil');

        $rows  = "";
        $rowsPvt  = "";
        $links = "";
        $leftJoin  = "";
        $sqlAppend = "";
        $tdForSiteId = "";
        $thForSiteId = "";
        $leftjnAppend = "";
        $sqlSite = "";

        if($cpCfg['cp.hasMultiUniqueSites']  == true){
            $sqlAppend = ",po.site_id, st.title as site_title";
            $leftjnAppend = "
            LEFT JOIN site st ON st.site_id = po.site_id";
            $sqlSite = "AND po.site_id = {$site_id}";
        }

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $SQL = "
        SELECT pop.cost_price
              ,pop.selling_price
              ,pop.qty
              ,(CASE 
               WHEN pop.pack_size REGEXP '^[+-]?[0-9]+$'
               THEN pop.free_items * pop.pack_size
               ELSE pop.free_items END) AS freeQty
              ,pop.free_items
              ,com.company_name AS supplier_name
              ,po.po_code
              ,po.purchase_order_date
              ,po.purchase_order_id
              ,po.creation_date
              ,p.title AS product_name
              ,p.not_add_in_stock
              ,pop.pack_size
              ,pop.batch_no
              ,pop.product_id
              ,pop.po_product_id
              ,pop.expiry_date
              ,pop.creation_date AS poCreationDate
              ,pop.modification_date AS poModificationDate
              ,ibs.current_stock
              ,ibs.inventory_batchwise_stock_id
              ,ibs.creation_date AS ibsCreationDate
              ,ibs.modification_date AS ibsModificationDate
              ,(CASE 
               WHEN pop.pack_size REGEXP '^[+-]?[0-9]+$'
               THEN pop.qty * pop.pack_size
               ELSE pop.qty END) AS purchased_qty
              {$sqlAppend}
        FROM inventory_batchwise_stock ibs
        LEFT JOIN po_product pop ON (pop.po_product_id = ibs.po_product_id)
        LEFT JOIN purchase_order po ON (po.purchase_order_id = pop.purchase_order_id)
        LEFT JOIN supplier com ON (pop.supplier_id = com.supplier_id)
        LEFT JOIN product p ON (p.product_id = ibs.product_id)
        {$leftjnAppend}
        WHERE ibs.product_id = {$product_id}
        AND ibs.site_id = {$site_id}
        AND ibs.current_stock > 0
        AND po.status != 'Cancelled'
        ORDER BY ibs.po_product_id
        ";
        $result   = $db->sql_query($SQL);
        $product_name         = "";
        $not_add_in_stock     = "";
        $batchwiseStock       = 0;
        $batchwiseAdjustStock = 0;
        while ($rowPo = $db->sql_fetchrow($result)) {

            if($rowPo['purchase_order_date'] == '' || $rowPo['purchase_order_date'] == 0){
                $purchase_order_date = $fn->getCPDate($rowPo['creation_date'], 'd-m-Y');
            }
            else{
                $purchase_order_date = $fn->getCPDate($rowPo['purchase_order_date'], 'd-m-Y');
            }

            $SQLSite = "
            SELECT title
            FROM site
            WHERE site_id = '{$site_id}'
            ";
            $resultSite = $db->sql_query($SQLSite);
            $rowSite    = $db->sql_fetchrow($resultSite);
            $siteArr    = explode(" ", $rowSite['title']);
            $sitePrefix = "";
            foreach($siteArr as $site) {
               $sitePrefix .= substr($site,0,1);
            }

            if($rowPo['poModificationDate'] != "") {
                $poCodeDate = $rowPo['poModificationDate'];
                $poCodeDate = $fn->getCPDate($poCodeDate, 'd-M-Y');
            } else {
                $poCodeDate = $rowPo['poCreationDate'];
                $poCodeDate = $fn->getCPDate($poCodeDate, 'd-M-Y');
            }

            //$po_code = 'PO - '.$rowPo['po_code'];
            $po_code = $poCodeDate;
            if($cpSiteIdSession == $rowPo['site_id']) {
                $po_code = "<a href='index.php?_topRm=pharmacy&module=tradingsg_purchaseOrder&_action=edit&record_id={$rowPo['purchase_order_id']}' target='_blank'><u>{$poCodeDate}</u></a>";
            }

            $appendSqlSiteStock = '';
            if($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSqlSiteStock = "AND site_id = {$site_id}";
            }

            $SQLStock = "
            SELECT current_stock
                  ,adjust_stock
                  ,inventory_batchwise_stock_id
            FROM inventory_batchwise_stock
            WHERE po_product_id = {$rowPo['po_product_id']}
            {$appendSqlSiteStock}
            ";
            $resultStock = $db->sql_query($SQLStock);
            $rowStock    = $db->sql_fetchrow($resultStock);
            $stock       = $rowStock['current_stock'];
            $stockExcludeAdjust = $rowStock['current_stock'] - $rowStock['adjust_stock'];

            $SQLInventory = "
            SELECT inventory_id
            FROM inventory
            WHERE product_id = '{$rowPo['product_id']}'
            ";
            $resultInventory = $db->sql_query($SQLInventory);
            $rowInventory    = $db->sql_fetchrow($resultInventory);

            $stock = "
            {$formObj->getTBRow('', 'current_stock', $stock)}
            <input type='hidden' name='current_stock_hidden' value='{$stock}'/>
            <a class='btn btn-success saveCurrentStock' site_id='{$site_id}' inventory_id='{$inventory_id}' product_id='{$product_id}' inventory_batchwise_stock_id='{$rowPo['inventory_batchwise_stock_id']}'>
                <span class='glyphicon glyphicon-floppy-disk'></span>
            </a>
            ";
            
            if($rowPo['expiry_date'] != '') {
                if($rowPo['expiry_date'] < date("Y-m-d")) {
                    //$stock        = $rowStock['current_stock'];
                    $expiredInput = "disabled='disabled'";
                    $expiry_date  = "<span class='blinking'><b>".$fn->getCPDate($rowPo['expiry_date'], 'd-m-Y')."</b></span>";
                } else {
                    $expiredInput = "";
                    $expiry_date = $fn->getCPDate($rowPo['expiry_date'], 'd-m-Y');
                }
            } else {
                $expiredInput = "disabled='disabled'";
                $expiry_date  = "";
            }

            $view_stock = "
            <a inventory_batchwise_stock_id='{$rowStock['inventory_batchwise_stock_id']}' stock='{$stockExcludeAdjust}' class='viewAllUpdatedAdjustStockHistory'><u>View</u>
            </a>
            ";

            /*$adjust_stock = "
            <a class='addStockInventory'>
                <u>Add Stock</u>
            </a>
            <input name='adjust_stock' value='' class='txt AdjustStockInventoryEdit displayNone' stock='{$stockExcludeAdjust}' batch_no='{$rowPo['batch_no']}' product_id='{$rowPo['product_id']}' po_product_id='{$rowPo['po_product_id']}' inventory_id='{$rowInventory['inventory_id']}' site_id='{$site_id}' {$expiredInput}/>
            <a class='btn btn-success AdjustStockInventoryEditSaveBtn displayNone'>
                Save
            </a>
            ";

            $adjust_stock_deduct = "
            <a class='deductStockInventory'>
                <u>Deduct Stock</u>
            </a>
            <input name='adjust_stock' value='' class='txt DeductStockInventoryEdit displayNone' stock='{$stockExcludeAdjust}' batch_no='{$rowPo['batch_no']}' product_id='{$rowPo['product_id']}' po_product_id='{$rowPo['po_product_id']}' inventory_id='{$rowInventory['inventory_id']}' site_id='{$site_id}' {$expiredInput}/>
            <a class='btn btn-success DeductStockInventoryEditSaveBtn displayNone'>
                Save
            </a>
            <td class='addStockWidth'>{$adjust_stock}</td>
            <td>{$adjust_stock_deduct}</td>
            ";*/

            $bgColorChange = "";
            /*if($stock <= 0) {
                $bgColorChange = "class='bg-darkred'";
            }*/

            $adjust_stock = "";
            $adjust_stock_deduct = "";

            if($rowPo['freeQty'] > 0) {
                $rowPo['purchased_qty'] = $rowPo['purchased_qty'] + $rowPo['freeQty'];
            }

            $rows .= "
            <tr {$bgColorChange}> 
                <td>{$po_code}</td>
                <td>{$expiry_date}</td>
                <td>{$rowPo['batch_no']}</td> 
                <td>{$rowPo['cost_price']}</td>
                <td>{$rowPo['selling_price']}</td>
                <td>{$rowPo['pack_size']}</td>
                <td>{$rowPo['qty']}</td>
                <td class='purchasedQtyValue'>{$rowPo['purchased_qty']}</td>
                <td>{$view_stock}</td>
                <td class='overallStockForLocationWise'>
                    {$stock}
                </td>
            </tr>
            ";

            $product_name     = $rowPo['product_name'];
            $not_add_in_stock = $rowPo['not_add_in_stock'];
        }

        if($cpCfg['cp.hasMultiUniqueSites']  == true){
            $thForSiteId = "<th>Location</th>";
        }

        $siteIdForField = "";
        if($site_id != ""){ 
            $siteIdForField = $site_id;
        }

        $SQLInventory = "
        SELECT actual_stock{$siteIdForField}
              ,inventory_id
        FROM inventory
        WHERE product_id = '{$product_id}'
        ";
        $resultInventory = $db->sql_query($SQLInventory);
        $rowInventory    = $db->sql_fetchrow($resultInventory);
        list($intStockOverall, $decStockOverall) = explode('.', $rowInventory['actual_stock'.$siteIdForField]);
        $stockOverall    = $intStockOverall;

        $expired_stock = "
        <div class='float_left'>
            <a inventory_id='{$inventory_id}' product_id='{$product_id}' site_id='{$site_id}' class='float_left viewAllUpdatedExpiredHistory'>
                <u>View</u>
            </a>
            <input name='expired_stock' value='' class='txt float_left ExpiredStockInventoryEdit' inventory_id='{$inventory_id}' product_id='{$product_id}' site_id='{$site_id}' stock='{$stockOverall}'/>
            <a class='btn btn-success float_left ExpiredStockInventoryEditSaveBtn'>
                Save
            </a>
        </div>
        ";
        
        $stockOverall = number_format($stockOverall);

        /*<th>Add Stock</th>
        <th>Deduct Stock</th>
        <th colspan='6'><div class='float_left mt5'>Expiry Stock:</div> {$expired_stock}</th>
        */

        $stockTransferIn = "
        <a class='stockTransferredIn' product_id='{$product_id}' site_id='{$site_id}'>
            IN
        </a>
        ";

        $stockTransferOut = "
        <a class='stockTransferredOut ml20' product_id='{$product_id}' site_id='{$site_id}'>
            OUT
        </a>
        ";

        $header ="
        <tr>
            <th colspan='5'>{$product_name} - [Stock: <span class='stockUpdatePopup_{$rowInventory['inventory_id']}'>{$stockOverall}</span>]</th>
            <th colspan='6'><div class='float_left'>Stock Transfer:</div> {$stockTransferIn} {$stockTransferOut}</th>
        </tr>
        <tr style='background-color:#EAEAE8;'>
            <th>PO Code</th>
            <th>Exp Date</th>
            <th>Batch No</th>
            <th>Rate</th>
            <th>MRP</th>
            <th>Pack Size</th>
            <th>Qty</th>
            <th>PO Qty</th>
            <th>View</th>
            <th>Stock Qty</th>
        </tr>
        ";

        $text = "
        <table class='thinlist'>
            {$header}
            {$rows}
            <input type='hidden' name='not_add_in_stock' value='{$not_add_in_stock}'/>
        </table>
        ";

        return $text;
    }

    /**
     */
    function getStockTransferDisplay(){
        $cpCfg   = Zend_Registry::get('cpCfg');
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil  = Zend_Registry::get('dbUtil');

        $product_id = $fn->getReqParam('product_id');
        $site_id    = $fn->getReqParam('site_id');
        $type       = $fn->getReqParam('type');

        $formAction = '';

        $text = "
        <div class='linkPortalWrapper panel tradingin_inventory_stockTransferLink'>
            <div class='panel panel-primary'>
                <div class='panel-heading'>
                    <div expanded='1'>
                        <div class='floatbox'>
                            <div class='float_left RightPanelHeading'>Stock Transfer Linked</div>
                            <div class='txtRight'>
                              <div class='toggle'></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <div class='linkPortalDataWrapper'>
                        <form id='stockTransfer' class='' method='post' action='{$formAction}'>
                            <div id='invoicePortalOuter'>
                                {$this->getStockTransferDisplayDetail($product_id, $site_id, $type)}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        ";

        return $text;
    }

    /**
     *
     */
    function getStockTransferDisplayDetail($product_id, $site_id, $type){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $rows  = "";
        $rowsPvt  = "";
        $links = "";
        $leftJoin  = "";
        $sqlAppend = "";
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $appendSql = "AND (st.to_location = '{$site_id}' OR st.from_location = '{$site_id}')";
        if($type == "stockIn") {
            $appendSql = "AND st.to_location = '{$site_id}'";
        }
        else if($type == "stockOut") {
            $appendSql = "AND st.from_location = '{$site_id}'";
        }

        $SQL = "
        SELECT  st.from_location
               ,st.to_location
               ,st.date
               ,sth.batch_no
               ,(CASE 
                WHEN sth.pack_size REGEXP '^[+-]?[0-9]+$'
                THEN sth.qty * sth.pack_size
                ELSE sth.qty END) AS qty
        FROM stock_transfer_history sth
        LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
        WHERE sth.product_id = {$product_id}
        {$appendSql}
        AND st.stock_deducted = 1
        ORDER BY st.date DESC
        LIMIT 25
        ";
        $result   = $db->sql_query($SQL);

        while ($rowST = $db->sql_fetchrow($result)) {

            $Sqlfrom = "
            SELECT title
            FROM site
            WHERE site_id = {$rowST['from_location']}
            ";
            $resultfrom = $db->sql_query($Sqlfrom);
            $from = $db->sql_fetchrow($resultfrom);

            $Sqlto = "
            SELECT title
            FROM site
            WHERE site_id = {$rowST['to_location']}
            ";
            $resultto = $db->sql_query($Sqlto);
            $to = $db->sql_fetchrow($resultto);

            $rows .= "
            <tr class='stockTransferRightPanelTr'>
                <td>{$fn->getCPDate($rowST['date'], 'd-m-Y')}</td>
                <td>{$from['title']}</td>
                <td>{$to['title']}</td>
                <td>{$rowST['batch_no']}</td>
                <td>{$rowST['qty']}</td>
            </tr>
            ";
        }

        $header ="
        <tr style='background-color:#EAEAE8;'>
            <th>Date</th>
            <th>From Location</th>
            <th>To Location</th>
            <th>Batch No</th>
            <th>Qty</th>
        </tr>
        ";

        $text = "
        <table class='thinlist' width='100%'>
            {$header}
            {$rows}
        </table>
        ";

        return $text;
    }

    /**
     */
    function getUpdatedAdjustStockHistory(){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil = Zend_Registry::get('dbUtil');

        $inventory_batchwise_stock_id = $fn->getReqParam('inventory_batchwise_stock_id');
        
        $rows = "";

        $SQL = "
        SELECT *
        FROM adjust_stock_log
        WHERE inventory_batchwise_stock_id = {$inventory_batchwise_stock_id}
          AND (current_stock IS NOT NULL OR current_stock = '')
        ORDER BY adjust_stock_log_id DESC
        ";
        $result = $db->sql_query($SQL);
        while ($row = $db->sql_fetchrow($result)) {
            $rows .= "
            <tr>
                <td>{$row['adjust_stock']}</td>
                <td>{$row['current_stock']}</td>
                <td><i>{$row['created_by']} - {$row['creation_date']}</i></td>
            </tr>
            ";
        }

        $header ="
        <tr style='background-color:#EAEAE8;'>
            <th>Adjust Stock</th>
            <th>Actual Stock</th>
            <th>Created/Updated By</th>
        </tr>
        ";

        $text = "
        <table class='thinlist'>
            {$header}
            {$rows}
        </table>
        ";

        return $text;
    }

    /**
     */
    function getUpdatedExpiryStockHistory(){
        $cpCfg   = Zend_Registry::get('cpCfg');
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil  = Zend_Registry::get('dbUtil');

        $inventory_id = $fn->getReqParam('inventory_id');
        $site_id      = $fn->getReqParam('site_id');
        
        $rows = "";

        $SQL = "
        SELECT *
        FROM expired_stock_log
        WHERE inventory_id = {$inventory_id}
          AND site_id = {$site_id}
        ORDER BY expired_stock_log_id DESC
        ";
        $result = $db->sql_query($SQL);
        while ($row = $db->sql_fetchrow($result)) {
            $rows .= "
            <tr>
                <td>{$row['expired_stock']}</td>
                <td><i>{$row['created_by']} - {$row['creation_date']}</i></td>
            </tr>
            ";
        }

        $header ="
        <tr style='background-color:#EAEAE8;'>
            <th>Expired Stock</th>
            <th>Created/Updated By</th>
        </tr>
        ";

        $text = "
        <table class='thinlist'>
            {$header}
            {$rows}
        </table>
        ";

        return $text;
    }

    /**
     */
    function getManualStockDisplay(){
        $cpCfg   = Zend_Registry::get('cpCfg');
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil  = Zend_Registry::get('dbUtil');

        $product_id   = $fn->getReqParam('product_id');
        $site_id      = $fn->getReqParam('site_id');
        $actual_stock = $fn->getReqParam('actual_stock');

        $formAction = '';
        $proRec = $fn->getRecordRowByID('product', 'product_id', $product_id);

        $SQLInventory = "
        SELECT inventory_id
        FROM inventory
        WHERE product_id = '{$product_id}'
        ";
        $resultInventory = $db->sql_query($SQLInventory);
        $rowInventory    = $db->sql_fetchrow($resultInventory);

        $manual_stock = "
        <div class='float_left'>{$proRec['title']}</div>
        <input name='manual_stock' value='' class='txt float_left ManualStockInventory' product_id='{$product_id}' inventory_id='{$rowInventory['inventory_id']}' site_id='{$site_id}' actual_stock='{$actual_stock}'/>
        <a class='btn btn-success float_left ManualStockInventorySaveBtn'>
            Save
        </a>
        ";

        $stockTransferIn = "
        <a class='stockTransferredIn ml10' product_id='{$product_id}' site_id='{$site_id}'>
            IN
        </a>
        ";

        $stockTransferOut = "
        <a class='stockTransferredOut ml20' product_id='{$product_id}' site_id='{$site_id}'>
            OUT
        </a>
        ";

        $SQLMS = "
        SELECT ms.date
              ,ms.time 
        FROM manual_stock ms 
        WHERE ms.product_id = '{$product_id}'
          AND ms.site_id    = '{$site_id}'
          AND ms.product_id NOT IN (332, 338, 785, 861, 1312)
        ORDER BY ms.manual_stock_id DESC
        LIMIT 1
        ";
        $resultMS      = $db->sql_query($SQLMS);
        $numRowsMS = $db->sql_numrows($resultMS);
        $rowMS     = $db->sql_fetchrow($resultMS);

        $soldQty = 0;
        if($numRowsMS > 0) {
            $dateTime = $rowMS['date'].' '.$rowMS['time'];

            $SQL = "
            SELECT DISTINCT o.order_id
                  ,oi.order_item_id
                  ,oi.item_title
                  ,oi.unit_price
                  ,oi.qty
                  ,oi.qty * oi.unit_price
                  ,o.order_date
                  ,o.record_type
                  ,o.creation_date AS orderCreation
                  ,com.company_name
            FROM `order_item` oi
            LEFT JOIN (`order` o) ON (o.order_id = oi.order_id)
            LEFT JOIN (`invoice` inv) ON (o.order_id = inv.order_id)
            LEFT JOIN company com ON com.company_id = o.company_id
            WHERE oi.record_id = {$product_id}
              AND o.site_id = {$site_id}
              AND (o.order_status = 'Paid' || o.order_status = 'Due' || o.order_status = 'Partial Payment')
              AND (inv.status = 'Paid' || inv.status = 'Due' || inv.status = 'Partial Payment')
              AND o.creation_date > '{$dateTime}'
            ORDER BY o.creation_date DESC
            ";

            $result  = $db->sql_query($SQL);
            $client  = '';
            $soldQty = 0;
            while ($rowOI = $db->sql_fetchrow($result)) {
                $soldQty += $rowOI['qty'];
            }
        }

        //to get sales from previous manual stock records
        $resultPrevMS = 0;
        $SQLPrevMS = "
        SELECT ms.date
              ,ms.time 
        FROM manual_stock ms 
        WHERE ms.product_id = '{$product_id}'
          AND ms.site_id    = '{$site_id}'
          AND ms.product_id NOT IN (332, 338, 785, 861, 1312)
          AND ms.date < '{$rowMS['date']}'
        ORDER BY ms.manual_stock_id DESC
        LIMIT 1
        ";
        $resultPrevMS  = $db->sql_query($SQLPrevMS);
        $numRowsPrevMS = $db->sql_numrows($resultPrevMS);

        $soldPrevQty = '';
        $count = 1;
        if($numRowsPrevMS > 0) {
            while ($rowPrevMS = $db->sql_fetchrow($resultPrevMS)) {
                $dateTime2 = $rowPrevMS['date'].' '.$rowPrevMS['time'];

                $SQL = "
                SELECT DISTINCT o.order_id
                      ,oi.order_item_id
                      ,oi.item_title
                      ,oi.unit_price
                      ,oi.qty
                      ,oi.qty * oi.unit_price
                      ,o.order_date
                      ,o.record_type
                      ,o.creation_date AS orderCreation
                      ,com.company_name
                FROM `order_item` oi
                LEFT JOIN (`order` o) ON (o.order_id = oi.order_id)
                LEFT JOIN (`invoice` inv) ON (o.order_id = inv.order_id)
                LEFT JOIN company com ON com.company_id = o.company_id
                WHERE oi.record_id = {$product_id}
                  AND o.site_id = {$site_id}
                  AND (o.order_status = 'Paid' || o.order_status = 'Due' || o.order_status = 'Partial Payment')
                  AND (inv.status = 'Paid' || inv.status = 'Due' || inv.status = 'Partial Payment')
                  AND o.creation_date > '{$dateTime2}'
                  AND o.creation_date < '{$dateTime}'
                ORDER BY o.creation_date DESC
                ";
                $result  = $db->sql_query($SQL);
                $soldPrevQty = 0;
                while ($rowOI = $db->sql_fetchrow($result)) {
                    $soldPrevQty += $rowOI['qty'];
                }
                
                $count++;
            }
        }   
        //<label>Stock Transfer:</label>
        //{$stockTransferIn} {$stockTransferOut}

        $text = "
        <div class='linkPortalWrapper panel tradingin_inventory_purchaseOrderLink'>
            <div class='panel panel-primary'>
                <div class='panel-heading'>
                    <div expanded='1'>
                        <div class='floatbox'>
                            <div class='float_left RightPanelHeading'>Manual Stock Linked</div>
                            <div class='txtRight'></div>
                        </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <div class='linkPortalDataWrapper'>
                        <form id='poPrint' class='' method='post' action='{$formAction}'>
                            <div class='mt20 mb20 ml10 floatbox'>
                                {$manual_stock}
                                <label>Latest Sales:</label>
                                <a class='billsLinkedAfterManualStock ml10 mr20' product_id='{$product_id}' site_id='{$site_id}' type='last'>
                                    <b>{$soldQty}</b>
                                </a>
                                <label>Sales After Last MS:</label>
                                <a class='billsLinkedAfterManualStock ml10 mr20' product_id='{$product_id}' site_id='{$site_id}' type='lastPrev'>
                                    <b>{$soldPrevQty}</b>
                                </a>
                            </div>
                            <div id='manualStockDetail'>
                                {$this->getManualStockDisplayDetail($product_id, $site_id)}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        ";

        return $text;
    }

    /**
     */
    function getManualStockDisplayDetail($product_id='', $site_id='') {
        $cpCfg   = Zend_Registry::get('cpCfg');
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil  = Zend_Registry::get('dbUtil');

        $rows  = "";
        $rowsPvt  = "";
        $links = "";
        $leftJoin  = "";
        $sqlAppend = "";
        $tdForSiteId = "";
        $thForSiteId = "";
        $leftjnAppend = "";
        $sqlSite = "";

        if($product_id == '') {
            $product_id  = $fn->getReqParam('product_id');            
        }

        if($site_id == '') {
            $site_id  = $fn->getReqParam('site_id');            
        }

        $SQL = "
        SELECT ms.*
        FROM manual_stock ms
        LEFT JOIN product p ON (p.product_id = ms.product_id)
        WHERE ms.product_id = {$product_id}
          AND ms.site_id = {$site_id}
        ORDER BY ms.manual_stock_id DESC
        LIMIT 0,10
        ";
        $result   = $db->sql_query($SQL);
        $product_name         = "";
        $batchwiseStock       = 0;
        $batchwiseAdjustStock = 0;
        while ($rowPo = $db->sql_fetchrow($result)) {
            $date = $fn->getCPDate($rowPo['date'], 'd-m-Y');
            $actualStock = '';
            if ($_SESSION['userGroupName'] == "Super Administrator" || $_SESSION['userGroupName'] == "Administrator") {
                $actualStock = "<td>{$rowPo['actual_stock']}</td>";
            }

            $rows .= "
            <tr>
                <td>{$date}</td>
                <td>{$rowPo['time']}</td>
                <td>{$rowPo['stock']}</td>
                {$actualStock}
                <td>{$rowPo['created_by']}</td>
            </tr>
            ";
        }

        $actualStockHeader = '';
        if ($_SESSION['userGroupName'] == "Super Administrator" || $_SESSION['userGroupName'] == "Administrator") {
            $actualStockHeader = "<th>Actual Stock</th>";
        }
        $header ="
        <tr style='background-color:#EAEAE8;'>
            <th>Date</th>
            <th>Time</th>
            <th>Manual Stock</th>
            {$actualStockHeader}
            <th>Updated By</th>
        </tr>
        ";

        $text = "
        <table class='thinlist'>
            {$header}
            {$rows}
        </table>
        ";

        return $text;
    }

    /**
     */
    function getOrderDisplayAfterManualStock(){
        $cpCfg   = Zend_Registry::get('cpCfg');
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil  = Zend_Registry::get('dbUtil');

        $product_id = $fn->getReqParam('product_id');
        $site_id    = $fn->getReqParam('site_id');
        $type       = $fn->getReqParam('type');

        $formAction = '';

        $text = "
        <div class='linkPortalWrapper panel tradingin_inventory_orderLink'>
            <div class='panel panel-primary'>
                <div class='panel-heading'>
                    <div expanded='1'>
                        <div class='floatbox'>
                            <div class='float_left RightPanelHeading'>Bills Linked</div>
                            <div class='txtRight'>
                              <div class='toggle'></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <div class='linkPortalDataWrapper'>
                        <form id='orderItemPrint' class='' method='post' action='{$formAction}'>
                            <div id='invoicePortalOuter'>
                                {$this->getOrderDisplayAfterManualStockDetail($product_id, $site_id, $type)}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        ";

        return $text;
    }

    /**
     *
     */
    function getOrderDisplayAfterManualStockDetail($product_id, $site_id, $type){
        $cpCfg   = Zend_Registry::get('cpCfg');
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil  = Zend_Registry::get('dbUtil');

        $rows      = "";
        $text      = "";
        $rowsPvt   = "";
        $links     = "";
        $leftJoin  = "";
        $sqlAppend = "";

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $SQLMS = "
        SELECT ms.date
              ,ms.time 
        FROM manual_stock ms 
        WHERE ms.product_id = '{$product_id}'
          AND ms.site_id    = '{$site_id}'
          AND ms.product_id NOT IN (332, 338, 785, 861, 1312)
        ORDER BY ms.manual_stock_id DESC
        LIMIT 1
        ";
        $resultMS  = $db->sql_query($SQLMS);
        $numRowsMS = $db->sql_numrows($resultMS);
        $rowMS     = $db->sql_fetchrow($resultMS);

        if($numRowsMS > 0) {
            $dateTime = $rowMS['date'].' '.$rowMS['time'];

            $SQLPrevMS = "
            SELECT ms.date
                  ,ms.time 
            FROM manual_stock ms 
            WHERE ms.product_id = '{$product_id}'
              AND ms.site_id    = '{$site_id}'
              AND ms.product_id NOT IN (332, 338, 785, 861, 1312)
              AND ms.date < '{$rowMS['date']}'
            ORDER BY ms.manual_stock_id DESC
            LIMIT 1
            ";
            $resultPrevMS  = $db->sql_query($SQLPrevMS);
            $numRowsPrevMS = $db->sql_numrows($resultPrevMS);

            if($type == "lastPrev" && $numRowsPrevMS > 0) {
                $rowPrevMS = $db->sql_fetchrow($resultPrevMS);
                $dateTime2 = $rowPrevMS['date'].' '.$rowPrevMS['time'];

                $SQL = "
                SELECT DISTINCT o.order_id
                      ,oi.order_item_id
                      ,oi.item_title
                      ,oi.unit_price
                      ,oi.qty
                      ,oi.qty * oi.unit_price
                      ,o.order_date
                      ,o.record_type
                      ,o.creation_date AS orderCreation
                      ,com.company_name
                FROM `order_item` oi
                LEFT JOIN (`order` o) ON (o.order_id = oi.order_id)
                LEFT JOIN (`invoice` inv) ON (o.order_id = inv.order_id)
                LEFT JOIN company com ON com.company_id = o.company_id
                WHERE oi.record_id = {$product_id}
                  AND o.site_id = {$site_id}
                  AND (o.order_status = 'Paid' || o.order_status = 'Due' || o.order_status = 'Partial Payment')
                  AND (inv.status = 'Paid' || inv.status = 'Due' || inv.status = 'Partial Payment')
                  AND o.creation_date > '{$dateTime2}'
                  AND o.creation_date < '{$dateTime}'
                ORDER BY o.creation_date DESC
                ";
            } else {
                $SQL = "
                SELECT DISTINCT o.order_id
                      ,oi.order_item_id
                      ,oi.item_title
                      ,oi.unit_price
                      ,oi.qty
                      ,oi.qty * oi.unit_price
                      ,o.order_date
                      ,o.record_type
                      ,o.creation_date AS orderCreation
                      ,com.company_name
                FROM `order_item` oi
                LEFT JOIN (`order` o) ON (o.order_id = oi.order_id)
                LEFT JOIN (`invoice` inv) ON (o.order_id = inv.order_id)
                LEFT JOIN company com ON com.company_id = o.company_id
                WHERE oi.record_id = {$product_id}
                  AND o.site_id = {$site_id}
                  AND (o.order_status = 'Paid' || o.order_status = 'Due' || o.order_status = 'Partial Payment')
                  AND (inv.status = 'Paid' || inv.status = 'Due' || inv.status = 'Partial Payment')
                  AND o.creation_date > '{$dateTime}'
                ORDER BY o.creation_date DESC
                ";
            }

            $result = $db->sql_query($SQL);
            $client = '';
            while ($rowOI = $db->sql_fetchrow($result)) {
                if($rowOI['record_type'] == 'POS'){
                    $client = 'POS';
                }

                else {
                    $client = $rowOI['company_name'];
                }

                if($rowOI['order_id'] < 10){
                    $orderNo = '0000' . $rowOI['order_id'];
                }
                else if($rowOI['order_id'] <= 99){
                    $orderNo = '000' . $rowOI['order_id'];
                }
                else if($rowOI['order_id'] <= 999){
                    $orderNo = '00' . $rowOI['order_id'];
                }
                else if($rowOI['order_id'] <= 9999){
                    $orderNo = '0' . $rowOI['order_id'];
                }
                else{
                    $orderNo = $rowOI['order_id'];
                }

                $OrderLink = $orderNo;
                if($cpSiteIdSession == $site_id) {
                    $OrderEditLink = "index.php?_topRm=pharmacy&module=hms_order&_action=edit&order_id={$rowOI['order_id']}";
                    $OrderLink = "  <a href='{$OrderEditLink}' target='_blank'>
                                        <u>{$orderNo}</u>
                                    </a>";
                }

                $rows .= "
                <tr class='orderRightPanelTr'>
                    <td>
                        {$OrderLink}
                    </td>
                    <td>{$fn->getCPDate($rowOI['order_date'], 'd-m-Y')}</td>
                    <td>{$fn->getCPDate($rowOI['orderCreation'], 'H:i:s')}</td>
                    <td class='txtRight'>{$rowOI['unit_price']}</td>
                    <td>{$rowOI['qty']}</td>
                    <td>{$client}</td>
                </tr>
                ";
            }

            $header ="
            <tr style='background-color:#EAEAE8;'>
                <th>Order Id</th>
                <th>Date</th>
                <th>Time</th>
                <th class='txtRight'>Amount</th>
                <th>PCS</th>
                <th>Client</th>
            </tr>
            ";

            $text = "
            <table class='thinlist' width='100%'>
                {$header}
                {$rows}
            </table>
            ";
        }

        return $text;
    }
    /**
     *
     */
    function getPrintFlaggedMedicine() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        ini_set('memory_limit', '512M');
        set_time_limit(50000);

        include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
        include_once(CP_LOCAL_PATH.'lib/headfoot3.php');

        $pdf = new MYPDF_Local(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('USS');
        $pdf->SetSubject('Print Prescription');
        $pdf->SetTitle('Print Prescription');

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 04', PDF_HEADER_STRING);
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER,10);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 13);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        //$pdf->setPrintFooter(false);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        /*HEADER PART AND FOOTER PART FUNCTIONS HAS BEEN ADDED IN (headfoot.php) PATH INCLUDE: (admin/lib/headfoot.php)*/
        $pdf->AddPage('P', 'A5');

        $in_patient_id = $fn->getReqParam('in_patient_id');

        $SQL = "
        SELECT i.*
              ,p.product_id AS productId
              ,p.title AS product_name
              ,p.item_code
              ,p.unit
              ,p.product_code
              ,p.category_id
              ,p.not_add_in_stock
              ,p.exclude_stock_difference
              ,i.actual_stock{$siteIdForField} AS stock
        FROM inventory i
        LEFT JOIN (product p) ON (p.product_id = i.product_id)
            WHERE i.flag = 1
        ";
        $result = $db->sql_query($SQL);
        //============================================================================= //

        $pdf->SetFont('Courier','B',10);

        $today = date("d-m-Y");
        /*$tblHead ='
        <table border="1" width="100%" cellpadding="5">
            <tr>
                <td width="13%">BILL NO :</td>
                <td width="37%">'.$orderNo.'</td>
                <td width="9%">DATE :</td>
                <td width="41%">'.$company['invoice_creation_date'].'</td>
            </tr>
        </table>
        ';*/

        $tbl3 = '';

        $tbl3 ='<table border="1" width="100%" cellpadding="4" style="border-top:1px solid #000000;">';

        $count = 1;
        $tbl3 = $tbl3.
        '<tr>
            <td align="left" width="45%">Name</td>
            <td align="left" width="35%">Supplier</td>
            <td align="left" width="20%">Free Items</td>
        </tr>';

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        while ($row = $db->sql_fetchrow($result)) {
            $SQLPo = "
            SELECT pop.po_product_id
                  ,MAX(pop.free_items) AS free_items
                  ,pop.purchase_order_id
            FROM po_product pop
            LEFT JOIN (purchase_order po) ON (po.purchase_order_id = pop.purchase_order_id)
            WHERE pop.product_id = {$row['product_id']}
              AND po.site_id = '{$cpSiteIdSession}'
            ORDER BY pop.free_items desc
            ";
            $resultPo = $db->sql_query($SQLPo);
            while ($rowPo = $db->sql_fetchrow($resultPo)) {
                $PoRec = $fn->getRecordByCondition('purchase_order',
                 "purchase_order_id = {$rowPo['purchase_order_id']}");
                $SuplrRec = $fn->getRecordByCondition('supplier',
                 "supplier_id = '{$PoRec['company_id_supplier']}'");
                
                $tbl3 = $tbl3.
                '<tr>
                    <td align="left" width="45%">'.$row['product_name'].'</td>
                    <td align="left" width="35%">'.$PoRec['purchase_order_id'].'</td>
                    <td align="center" width="20%">'.$rowPo['free_items'].'</td>
                </tr>';
            }
        }

        $tbl3 = $tbl3.'</table>';

        $pdf->writeHTML($tbl2, true, false, false, false, '');
        $pdf->writeHTML($tbl3, false, false, false, false, '');
        $download_title = 'Print PO Medicines.pdf';
        ob_end_clean();
        $pdf->Output($download_title, 'I');
    }

}