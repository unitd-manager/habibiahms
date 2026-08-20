<?
class CPL_Admin_Modules_Tradingsg_Pos_Model extends CP_Admin_Modules_Tradingsg_Pos_Model
{
    /**
     *
     */
    function getSearchProductTitle() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        $title = $fn->getReqParam('term', '', true);
        $extractor = explode(" **** ", $title);

        $productTitle = $extractor[0];

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        
        $appendSqlMSSite = '';
        $leftJoinMSite   = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlMSSite = "AND ms.site_id = {$cpSiteIdSession}";
            $leftJoinMSite   = "LEFT JOIN medicine_site ms ON (ms.product_id = p.product_id)";
        }

        $leftJoinMedicineSite = "";
        $appendSqlStk   = "";
        $appendSqlInv   = "";
        if($cpCfg['cp.hasMultiUniqueSites']  == true){
            $siteId = $fn->getSessionParam('cp_site_id');
            $appendSqlStk = "AND ibs.site_id = '{$siteId}'";
            $appendSqlInv = "AND i.site_id = '{$siteId}'";
            $leftJoinMedicineSite = "
            LEFT JOIN (medicine_site ms) ON (i.product_id = ms.product_id)
            ";
        }

        $SQL = "
        SELECT p.title AS value
              ,p.title AS label
              ,p.product_id AS id
              ,p.not_add_in_stock
              ,CONCAT_WS(' ', p.title,
                    IF(p.not_add_in_stock = '1', '', 
                    CONCAT('(',
                     REPLACE((SELECT actual_stock{$cpSiteIdSession} AS stock
                      FROM inventory i
                      WHERE i.product_id = p.product_id), '.00', '')
                    , ')'))
                ) AS label
                
              ,(REPLACE((SELECT actual_stock{$cpSiteIdSession} AS stock
                  FROM inventory i
                  WHERE i.product_id = p.product_id), '.00', '')) AS stock
              ,(SELECT COUNT(ibs.product_id)
                FROM inventory_batchwise_stock ibs
                LEFT JOIN (po_product pop) ON (pop.po_product_id = ibs.po_product_id)
                LEFT JOIN (purchase_order po) ON (po.purchase_order_id = ibs.purchase_order_id)
                WHERE ibs.product_id = p.product_id
                 AND po.status != 'Cancelled'
                 {$appendSqlStk}) AS batchCount
              ,(SELECT COUNT(ibs.product_id)
                FROM inventory_batchwise_stock ibs
                LEFT JOIN (po_product pop) ON (pop.po_product_id = ibs.po_product_id)
                LEFT JOIN (purchase_order po) ON (po.purchase_order_id = ibs.purchase_order_id)
                WHERE ibs.product_id = p.product_id
                 AND po.status != 'Cancelled'
                 AND pop.expiry_date >= CURDATE()
                 {$appendSqlStk}) AS expiryCount
        FROM product p
        {$leftJoinMSite}
        LEFT JOIN product_company pc ON (pc.product_id = p.product_id)
        WHERE p.title LIKE '{$productTitle}%'
          {$appendSqlMSSite}
          AND (p.product_type != 'Purchasing Product' 
          OR p.product_type IS NULL)
          HAVING IF(p.not_add_in_stock = '1', batchCount > 0, batchCount > 0)
          AND IF(p.not_add_in_stock = '1', (expiryCount <= 0 OR expiryCount >= 0), expiryCount > 0)
          AND IF(p.not_add_in_stock = '1', (stock <= 0 OR stock >= 0), stock > 0)
        ORDER BY p.title ASC
        ";
        $result = $db->sql_query($SQL);

        $dataArray = $dbUtil->getResultsetAsArray($result);
        $arr = json_encode($dataArray);
        
        return $arr;
    }

   /**
     *
     */
    function getSearchProductTitle1() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        $title = $fn->getReqParam('term', '', true);
        $extractor = explode(" **** ", $title);

        $productTitle = $extractor[0];

        $cpSiteIdSession  = $fn->getSessionParam('cp_site_id');
        
        $appendSqlMSSite = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlMSSite = "AND ms.site_id = {$cpSiteIdSession}";;
        }

        $SupplierNameAlias = "Supplier: ";
        //,CONCAT_WS(' | | ', p.item_code, pg.title, p.title, p.price, CONCAT('{$SupplierNameAlias}', c.company_name)) AS label


        /**** For checking locationwise stock qty need to add this left join and condition in below sql ****/
        /*
        LEFT JOIN (SELECT bp.product_id
             ,COUNT(DISTINCT bp.po_product_id) po_product_count 
        FROM po_product bp 
        LEFT JOIN purchase_order po ON (po.purchase_order_id = bp.purchase_order_id)
        WHERE po.status != 'Cancelled'
        {$appendSql} 
        GROUP BY bp.product_id) AS po
        ON (po.product_id = p.product_id)
        
        AND po.po_product_count > 0
        LEFT JOIN company c ON (c.company_id = pc.company_id)
        AND p.published = 1
        */

        $SQL = "
        SELECT p.title AS value
              ,p.title AS label
              ,CONCAT_WS(' | | ', p.tag_no, p.title, FORMAT(p.price, 2)) AS label
              ,p.product_id AS id
        FROM product p
        LEFT JOIN medicine_site ms ON (ms.product_id = p.product_id)
        LEFT JOIN product_company pc ON (pc.product_id = p.product_id)
        WHERE p.title LIKE '{$productTitle}%'
        AND p.published = 1
        {$appendSqlMSSite}
        AND (p.product_type != 'Purchasing Product' OR p.product_type IS NULL)
        ORDER BY p.title ASC
        ";
        $result = $db->sql_query($SQL);

        $dataArray = $dbUtil->getResultsetAsArray($result);
        $arr = json_encode($dataArray);
        
        return $arr;
    }

    /**
     *
     */
     function getUpdateOrderLineItems() {
        $fn     = Zend_Registry::get('fn');
        $db     = Zend_Registry::get('db');
        $tv     = Zend_Registry::get('tv');
        $cpCfg  = Zend_Registry::get('cpCfg');
        $dbUtil = Zend_Registry::get('dbUtil');

        $site_id          = $fn->getSessionParam('cp_site_id');
        $product_id       = $fn->getReqParam('product_id');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $SQLOrder = "
        SELECT order_date
        FROM `order`
        WHERE order_id = '{$session_order_id}'
        ";
        $resultOrder = $db->sql_query($SQLOrder);
        $rowOrder = $db->sql_fetchrow($resultOrder);

        $appendSql   = '';
        $stockTransferSQLForMultiSite = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql   = "AND ibs.site_id = {$site_id}";
        }

        $SQLCheckStock = "
        SELECT SUM(ibs.current_stock) AS stock
        FROM inventory_batchwise_stock ibs
        LEFT JOIN (po_product pop) ON (pop.po_product_id = ibs.po_product_id)
        LEFT JOIN (purchase_order po) ON (po.purchase_order_id = ibs.purchase_order_id)
        WHERE ibs.product_id = '{$product_id}'
          AND po.status != 'Cancelled'
          {$appendSql}
        ";
        $resultCheckStock = $db->sql_query($SQLCheckStock);
        $rowCheckStock    = $db->sql_fetchrow($resultCheckStock);

        $appendSQLStockIbs = "";
        if($rowCheckStock['stock'] > 0) {
            $appendSQLStockIbs = " AND ibs.current_stock > 0";
        }
        
        $SQL = "
        SELECT p.title
              ,p.item_code
              ,p.model
              ,p.part_number
              ,pop.selling_price AS price
              ,p.gst
              ,p.vat
              ,p.discount_type
              ,p.discount_percentage
              ,p.discount_amount
              ,p.tag_no
              ,p.discount_from_date
              ,p.discount_to_date
              ,p.not_add_in_stock
              ,p.add_syringe_in_pos
              ,pop.cost_price
              ,pop.pack_size
              ,pop.batch_no
              ,pop.expiry_date
              ,pop.po_product_id
              ,ibs.current_stock
        FROM inventory_batchwise_stock ibs
        LEFT JOIN (po_product pop) ON (pop.po_product_id = ibs.po_product_id)
        LEFT JOIN (product p) ON (p.product_id = ibs.product_id)
        WHERE ibs.product_id = '{$product_id}'
          AND pop.expiry_date >= CURDATE()
          {$appendSql}
          {$appendSQLStockIbs}
        ORDER BY CONVERT(ibs.current_stock,UNSIGNED INTEGER) DESC
        ";
        $result  = $db->sql_query($SQL);
        $row     = $db->sql_fetchrow($result);
        $numRows = $db->sql_numrows($result);

        if($numRows == 0) {
            $SQLProduct  = "
            SELECT p.title
                  ,p.item_code
                  ,p.not_add_in_stock
                  ,p.price
            FROM product p
            WHERE p.product_id = '{$product_id}'
            ";
            $resultProduct = $db->sql_query($SQLProduct);
            $rowProduct    = $db->sql_fetchrow($resultProduct);

            if($rowProduct['not_add_in_stock'] == '') {
                $rowProduct['not_add_in_stock'] = 0;
            }
            
            $qty = 1;

            $fa = array();
            $fa['order_id']         = $session_order_id;
            $fa['record_id']        = $product_id;
            $fa['item_title']       = $rowProduct['title'];
            $fa['item_code']        = $rowProduct['item_code'];
            $fa['not_add_in_stock'] = $rowProduct['not_add_in_stock'];
            $fa['qty']              = $qty;
            $fa['unit_price']       = $rowProduct['price'];

            $SQLOrderItem = "
            SELECT *
            FROM `order_item`
            WHERE order_id = '{$session_order_id}'
              AND record_id = {$product_id}
            ";
            $resultOrderItem = $db->sql_query($SQLOrderItem);
            $rec = $db->sql_fetchrow($resultOrderItem);

            if($rec['order_item_id'] != ''){
                $SQLUpdate = "UPDATE order_item SET qty = ({$rec['qty']} + 1)
                              WHERE order_id = '{$session_order_id}' AND record_id = {$product_id}";
                $resultUpdate = $db->sql_query($SQLUpdate);
            } else {
                $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'order_item');
                $db->sql_query($SQL);
                $order_item_id = $db->sql_nextid();
            }
        }

        else {
            $qty = 1;

            if($row['gst'] == ''){
                $row['gst'] = 0;
            }

            $gst = $row['gst'] * $row['price'] / 100;

            if($row['vat'] == ''){
                $row['vat'] = 0;
            }

            $vat = $row['vat'] * $row['price'] / 100;

            if($row['discount_percentage'] == ''){
                $row['discount_percentage'] = 0;
            }

            if($row['discount_amount'] == ''){
                $row['discount_amount'] = 0;
            }

            if($row['not_add_in_stock'] == '') {
                $row['not_add_in_stock'] = 0;
            }

            // This if condition used to check the product discount date range and update the discount on order item
            if($rowOrder['order_date'] >= $row['discount_from_date'] && $rowOrder['order_date'] <= $row['discount_to_date']){
            }
            else{
                $row['discount_amount']     = 0;
                $row['discount_percentage'] = 0;
                $row['discount_type']       = "";
            }

            $discount_value_for_one_qty = 0;
            $discountValue = 0;
            if($row['discount_type'] == '%'){
                if($row['discount_percentage'] > 0){
                    $discount_value_for_one_qty  =  $row['unit_price'] * ($row['discount_percentage']/100);
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                }
            }
            else if($row['discount_type']  == 'Value'){
                if($row['discount_amount'] > 0){
                    $discount_value_for_one_qty  =  $row['discount_amount'];
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                }
            }

            $totalAmount = $row['price'] - $discountValue;

            if($row['discount_type'] == ""){
                $row['discount_type'] = $cpCfg['cp.posDefaultDiscountType'];
            }

            if($row['cost_price'] == ""){
                $row['cost_price'] = 0;
            }

            if($row['price'] == ""){
                $row['price'] = 0;
            }

            if($row['expiry_date'] == '0000-00-00'){
                $row['expiry_date'] = '';
            }

            if(is_numeric($row['pack_size'])){
                $price = $row['price'] / $row['pack_size'];
            } else {
                $price = $row['price'];
            }

            if(is_numeric($row['pack_size'])){
                $cost_price = $row['cost_price'] / $row['pack_size'];
            } else {
                $cost_price = $row['cost_price'];
            }

            $fa = array();
            $fa['order_id']            = $session_order_id;
            $fa['record_id']           = $product_id;
            $fa['item_title']          = $row['title'];
            $fa['item_code']           = $row['item_code'];
            $fa['model']               = $row['model'];
            $fa['unit_price']          = $price;
            $fa['cost_price']          = $cost_price;
            $fa['discount_type']       = $row['discount_type'];
            $fa['discount_percentage'] = $row['discount_percentage'];
            $fa['discount_amount']     = $row['discount_amount'];
            $fa['qty']                 = $qty;
            $fa['vat']                 = $row['vat'];
            $fa['gst']                 = $row['gst'];
            $fa['tag_no']              = $row['tag_no'];
            $fa['expiry_date']         = $row['expiry_date'];
            $fa['pack_size']           = $row['pack_size'];
            $fa['batch_no']            = $row['batch_no'];
            $fa['po_product_id']       = $row['po_product_id'];
            $fa['not_add_in_stock']    = $row['not_add_in_stock'];
            $fa['discounted_amount']   = $discountValue;
            $fa['total_amount']        = $totalAmount;

            $SQLOrderItem = "
            SELECT *
            FROM `order_item`
            WHERE order_id = '{$session_order_id}'
              AND record_id = {$product_id}
            ";
            $resultOrderItem = $db->sql_query($SQLOrderItem);
            $rec = $db->sql_fetchrow($resultOrderItem);

            if($rec['order_item_id'] != ''){
                $SQLUpdate = "UPDATE order_item SET qty = ({$rec['qty']} + 1)
                              WHERE order_id = '{$session_order_id}' 
                              AND record_id = {$product_id}
                              AND po_product_id = {$row['po_product_id']}";
                $resultUpdate = $db->sql_query($SQLUpdate);
            } else {
                $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'order_item');
                $db->sql_query($SQL);
                $order_item_id = $db->sql_nextid();
            }
        }
    }

    /**
     *
     */
     function getUpdateOrderLineItems1() {
        $fn     = Zend_Registry::get('fn');
        $db     = Zend_Registry::get('db');
        $tv     = Zend_Registry::get('tv');
        $cpCfg  = Zend_Registry::get('cpCfg');
        $dbUtil = Zend_Registry::get('dbUtil');

        $site_id = $fn->getSessionParam('cp_site_id');
        $product_id = $fn->getReqParam('product_id');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $SQLOrder = "
        SELECT order_date
        FROM `order`
        WHERE order_id = '{$session_order_id}'
        ";
        $resultOrder = $db->sql_query($SQLOrder);
        $rowOrder = $db->sql_fetchrow($resultOrder);

        $appendSql   = '';
        $sqlAppendSt = '';
        $stockTransferSQLForMultiSite = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql   = "AND po.site_id = {$site_id}";
            $sqlAppendSt = "AND st.to_location = {$site_id}";

            $stockTransferSQLForMultiSite = "
            UNION
            SELECT  p.product_id AS product_id
                   ,p.title
                   ,p.not_add_in_stock
            FROM stock_transfer_history sth
            LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
            LEFT JOIN product p ON (p.product_id = sth.product_id)
            WHERE sth.product_id = {$product_id}
            AND st.status = 'Delivered'
            {$sqlAppendSt}
            GROUP BY product_id
            ";
        }

        $SQLPO  = "
        SELECT  p.product_id AS product_id
               ,p.title
               ,p.not_add_in_stock
        FROM product p
        LEFT JOIN po_product pp ON (pp.product_id = p.product_id)
        LEFT JOIN purchase_order po ON (po.purchase_order_id = pp.purchase_order_id)
        WHERE pp.product_id = {$product_id}
        AND po.status != 'Cancelled'
        {$appendSql}
        {$stockTransferSQLForMultiSite}
        ";
        $resultPO  = $db->sql_query($SQLPO);
        $rowPO     = $db->sql_fetchrow($resultPO);
        $numRowsPO = $db->sql_numrows($resultPO);

        if($numRowsPO == 0) {
            $SQLProduct  = "
            SELECT p.title
                  ,p.item_code
                  ,p.not_add_in_stock
                  ,p.price
            FROM product p
            WHERE p.product_id = '{$product_id}'
            ";
            $resultProduct = $db->sql_query($SQLProduct);
            $rowProduct    = $db->sql_fetchrow($resultProduct);

            if($rowProduct['not_add_in_stock'] == '') {
                $rowProduct['not_add_in_stock'] = 0;
            }
            
            $qty = 1;

            $fa = array();
            $fa['order_id']         = $session_order_id;
            $fa['record_id']        = $product_id;
            $fa['item_title']       = $rowProduct['title'];
            $fa['item_code']        = $rowProduct['item_code'];
            $fa['not_add_in_stock'] = $rowProduct['not_add_in_stock'];
            $fa['qty']              = $qty;
            $fa['unit_price']       = $rowProduct['price'];

            $SQLOrderItem = "
            SELECT *
            FROM `order_item`
            WHERE order_id = '{$session_order_id}'
              AND record_id = {$product_id}
            ";
            $resultOrderItem = $db->sql_query($SQLOrderItem);
            $rec = $db->sql_fetchrow($resultOrderItem);

            if($rec['order_item_id'] != ''){
                $SQLUpdate = "UPDATE order_item SET qty = ({$rec['qty']} + 1)
                              WHERE order_id = '{$session_order_id}' AND record_id = {$product_id}";
                $resultUpdate = $db->sql_query($SQLUpdate);
            } else {
                $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'order_item');
                $db->sql_query($SQL);
                $order_item_id = $db->sql_nextid();
            }
        }

        else {
            $SQL    = "
            SELECT p.title
                  ,p.item_code
                  ,p.model
                  ,p.part_number
                  ,p.price
                  ,p.gst
                  ,p.vat
                  ,p.discount_type
                  ,p.discount_percentage
                  ,p.discount_amount
                  ,p.tag_no
                  ,p.discount_from_date
                  ,p.discount_to_date
                  ,p.not_add_in_stock
                  ,p.add_syringe_in_pos
                  ,pop.cost_price
                  ,pop.pack_size
                  ,pop.batch_no
                  ,pop.expiry_date
            FROM product p
            LEFT JOIN (po_product pop) ON (pop.product_id = p.product_id)
            LEFT JOIN (purchase_order po) ON (po.purchase_order_id = pop.purchase_order_id)
            WHERE p.product_id = '{$product_id}'
            AND po.status != 'Cancelled'
            {$appendSql}
            ";
            $result = $db->sql_query($SQL);
            $row = $db->sql_fetchrow($result);

            $qty = 1;

            if($row['gst'] == ''){
                $row['gst'] = 0;
            }

            $gst = $row['gst'] * $row['price'] / 100;

            if($row['vat'] == ''){
                $row['vat'] = 0;
            }

            $vat = $row['vat'] * $row['price'] / 100;

            if($row['discount_percentage'] == ''){
                $row['discount_percentage'] = 0;
            }

            if($row['discount_amount'] == ''){
                $row['discount_amount'] = 0;
            }

            if($row['not_add_in_stock'] == '') {
                $row['not_add_in_stock'] = 0;
            }

            // This if condition used to check the product discount date range and update the discount on order item
            if($rowOrder['order_date'] >= $row['discount_from_date'] && $rowOrder['order_date'] <= $row['discount_to_date']){
            }
            else{
                $row['discount_amount']     = 0;
                $row['discount_percentage'] = 0;
                $row['discount_type']       = "";
            }

            $discount_value_for_one_qty = 0;
            $discountValue = 0;
            if($row['discount_type'] == '%'){
                if($row['discount_percentage'] > 0){
                    $discount_value_for_one_qty  =  $row['unit_price'] * ($row['discount_percentage']/100);
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                }
            }
            else if($row['discount_type']  == 'Value'){
                if($row['discount_amount'] > 0){
                    $discount_value_for_one_qty  =  $row['discount_amount'];
                    $discountValue = $discount_value_for_one_qty * $row['qty'];
                }
            }

            $totalAmount = $row['price'] - $discountValue;

            if($row['discount_type'] == ""){
                $row['discount_type'] = $cpCfg['cp.posDefaultDiscountType'];
            }

            if($row['cost_price'] == ""){
                $row['cost_price'] = 0;
            }

            if($row['price'] == ""){
                $row['price'] = 0;
            }

            if($row['expiry_date'] == '0000-00-00'){
                $row['expiry_date'] = '';
            }

            if(is_numeric($row['pack_size'])){
                $price = $row['price'] / $row['pack_size'];
            } else {
                $price = $row['price'];
            }

            if(is_numeric($row['pack_size'])){
                $cost_price = $row['cost_price'] / $row['pack_size'];
            } else {
                $cost_price = $row['cost_price'];
            }

            $fa = array();
            $fa['order_id']            = $session_order_id;
            $fa['record_id']           = $product_id;
            $fa['item_title']          = $row['title'];
            $fa['item_code']           = $row['item_code'];
            $fa['model']               = $row['model'];
            $fa['unit_price']          = $price;
            $fa['cost_price']          = $cost_price;
            //$fa['ref_code']            = $row['part_number'];
            $fa['discount_type']       = $row['discount_type'];
            $fa['discount_percentage'] = $row['discount_percentage'];
            $fa['discount_amount']     = $row['discount_amount'];
            $fa['qty']                 = $qty;
            $fa['vat']                 = $row['vat'];
            $fa['gst']                 = $row['gst'];
            $fa['tag_no']              = $row['tag_no'];
            $fa['expiry_date']         = $row['expiry_date'];
            $fa['pack_size']           = $row['pack_size'];
            $fa['batch_no']            = $row['batch_no'];
            $fa['not_add_in_stock']    = $row['not_add_in_stock'];
            $fa['discounted_amount']   = $discountValue;
            $fa['total_amount']        = $totalAmount;

            $SQLOrderItem = "
            SELECT *
            FROM `order_item`
            WHERE order_id = '{$session_order_id}'
              AND record_id = {$product_id}
            ";
            $resultOrderItem = $db->sql_query($SQLOrderItem);
            $rec = $db->sql_fetchrow($resultOrderItem);

            if($rec['order_item_id'] != ''){
                $SQLUpdate = "UPDATE order_item SET qty = ({$rec['qty']} + 1)
                              WHERE order_id = '{$session_order_id}' AND record_id = {$product_id}";
                $resultUpdate = $db->sql_query($SQLUpdate);
            } else {
                $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'order_item');
                $db->sql_query($SQL);
                $order_item_id = $db->sql_nextid();
            }

            if($row['add_syringe_in_pos'] == 1){
                $SQLSyrng = "
                SELECT p.title
                      ,p.item_code
                      ,p.model
                      ,p.part_number
                      ,p.price
                      ,p.gst
                      ,p.vat
                      ,p.discount_type
                      ,p.discount_percentage
                      ,p.discount_amount
                      ,p.tag_no
                      ,p.discount_from_date
                      ,p.discount_to_date
                      ,p.not_add_in_stock
                      ,p.add_syringe_in_pos
                      ,pop.cost_price
                      ,pop.pack_size
                      ,pop.batch_no
                      ,pop.expiry_date
                FROM product p
                LEFT JOIN (po_product pop) ON (pop.product_id = p.product_id)
                LEFT JOIN (purchase_order po) ON (po.purchase_order_id = pop.purchase_order_id)
                WHERE p.product_id = '17'
                AND po.status != 'Cancelled'
                {$appendSql}
                ";
                $resultSyrng = $db->sql_query($SQLSyrng);
                $rowSyrng = $db->sql_fetchrow($resultSyrng);

                $qty = 1;

                if($rowSyrng['gst'] == ''){
                    $rowSyrng['gst'] = 0;
                }

                $gst = $rowSyrng['gst'] * $rowSyrng['price'] / 100;

                if($rowSyrng['vat'] == ''){
                    $rowSyrng['vat'] = 0;
                }

                $vat = $rowSyrng['vat'] * $rowSyrng['price'] / 100;

                if($rowSyrng['discount_percentage'] == ''){
                    $rowSyrng['discount_percentage'] = 0;
                }

                if($rowSyrng['discount_amount'] == ''){
                    $rowSyrng['discount_amount'] = 0;
                }

                if($rowSyrng['not_add_in_stock'] == '') {
                    $rowSyrng['not_add_in_stock'] = 0;
                }

                // This if condition used to check the product discount date range and update the discount on order item
                if($rowOrder['order_date'] >= $rowSyrng['discount_from_date'] && $rowOrder['order_date'] <= $rowSyrng['discount_to_date']){
                }
                else{
                    $rowSyrng['discount_amount']     = 0;
                    $rowSyrng['discount_percentage'] = 0;
                    $rowSyrng['discount_type']       = "";
                }

                $discount_value_for_one_qty = 0;
                $discountValue = 0;
                if($rowSyrng['discount_type'] == '%'){
                    if($rowSyrng['discount_percentage'] > 0){
                        $discount_value_for_one_qty  =  $rowSyrng['unit_price'] * ($rowSyrng['discount_percentage']/100);
                        $discountValue = $discount_value_for_one_qty * $rowSyrng['qty'];
                    }
                }
                else if($rowSyrng['discount_type']  == 'Value'){
                    if($rowSyrng['discount_amount'] > 0){
                        $discount_value_for_one_qty  =  $rowSyrng['discount_amount'];
                        $discountValue = $discount_value_for_one_qty * $rowSyrng['qty'];
                    }
                }

                $totalAmount = $rowSyrng['price'] - $discountValue;

                if($rowSyrng['discount_type'] == ""){
                    $rowSyrng['discount_type'] = $cpCfg['cp.posDefaultDiscountType'];
                }

                if($rowSyrng['cost_price'] == ""){
                    $rowSyrng['cost_price'] = 0;
                }

                if($rowSyrng['price'] == ""){
                    $rowSyrng['price'] = 0;
                }

                if($rowSyrng['expiry_date'] == '0000-00-00'){
                    $rowSyrng['expiry_date'] = '';
                }

                if(is_numeric($rowSyrng['pack_size'])){
                    $price = $rowSyrng['price'] / $rowSyrng['pack_size'];
                } else {
                    $price = $rowSyrng['price'];
                }

                if(is_numeric($rowSyrng['pack_size'])){
                    $cost_price = $rowSyrng['cost_price'] / $rowSyrng['pack_size'];
                } else {
                    $cost_price = $rowSyrng['cost_price'];
                }

                $faSyrng = array();
                $faSyrng['order_id']            = $session_order_id;
                $faSyrng['record_id']           = 17;
                $faSyrng['item_title']          = $rowSyrng['title'];
                $faSyrng['item_code']           = $rowSyrng['item_code'];
                $faSyrng['model']               = $rowSyrng['model'];
                $faSyrng['unit_price']          = $price;
                $faSyrng['cost_price']          = $cost_price;
                $faSyrng['discount_type']       = $rowSyrng['discount_type'];
                $faSyrng['discount_percentage'] = $rowSyrng['discount_percentage'];
                $faSyrng['discount_amount']     = $rowSyrng['discount_amount'];
                $faSyrng['qty']                 = $qty;
                $faSyrng['vat']                 = $rowSyrng['vat'];
                $faSyrng['gst']                 = $rowSyrng['gst'];
                $faSyrng['tag_no']              = $rowSyrng['tag_no'];
                $faSyrng['expiry_date']         = $rowSyrng['expiry_date'];
                $faSyrng['pack_size']           = $rowSyrng['pack_size'];
                $faSyrng['batch_no']            = $rowSyrng['batch_no'];
                $faSyrng['not_add_in_stock']    = $rowSyrng['not_add_in_stock'];
                $faSyrng['discounted_amount']   = $discountValue;
                $faSyrng['total_amount']        = $totalAmount;

                $SQLOrderItem2 = "
                SELECT *
                FROM `order_item`
                WHERE order_id = '{$session_order_id}'
                  AND record_id = 17
                ";
                $resultOrderItem2 = $db->sql_query($SQLOrderItem2);
                $rec2 = $db->sql_fetchrow($resultOrderItem2);

                if($rec2['order_item_id'] != ''){
                    $SQLUpdate2 = "UPDATE order_item SET qty = ({$rec2['qty']} + 1)
                                  WHERE order_id = '{$session_order_id}' AND record_id = 17";
                    $resultUpdate2 = $db->sql_query($SQLUpdate2);
                } else {
                    $SQL2 = $dbUtil->getInsertSQLStringFromArray($faSyrng, 'order_item');
                    $db->sql_query($SQL2);
                    //$order_item_id = $db->sql_nextid();
                }
            }
        }
    }

    /**
     *
     */
     function getUpdateOrderLineItemsVisit() {
        $fn     = Zend_Registry::get('fn');
        $db     = Zend_Registry::get('db');
        $tv     = Zend_Registry::get('tv');
        $cpCfg  = Zend_Registry::get('cpCfg');
        $dbUtil = Zend_Registry::get('dbUtil');

        $patient_visit_id = $fn->getReqParam('patient_visit_id');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
        $site_id          = $fn->getSessionParam('cp_site_id');

        $appendSql = '';
        $stockTransferSQLForMultiSite = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "AND ibs.site_id = {$site_id}";
        }

        $SQLPatientVisit = "
        SELECT pv.employee_id
              ,pv.visit_code
              ,CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
              ,p.name AS patient_name
        FROM patient_visit pv
        LEFT JOIN employee e ON (e.employee_id = pv.employee_id)
        LEFT JOIN patient_information p ON (p.patient_information_id = pv.patient_information_id)
        WHERE pv.patient_visit_id = {$patient_visit_id}
        ";
        $resultPatientVisit = $db->sql_query($SQLPatientVisit);
        $rowPatientVisit = $db->sql_fetchrow($resultPatientVisit);

        $SQLUpdateOrder = "
        UPDATE `order` 
        SET ref_by_dr  = '{$rowPatientVisit['employee_name']}'
           ,visit_code = '{$rowPatientVisit['visit_code']}'
           ,cust_first_name = '{$rowPatientVisit['patient_name']}'
        WHERE order_id = '{$session_order_id}'
        ";
        $resultUpdateOrder = $db->sql_query($SQLUpdateOrder);

        $SQLOrder = "
        SELECT order_date
        FROM `order`
        WHERE order_id = '{$session_order_id}'
        ";
        $resultOrder = $db->sql_query($SQLOrder);
        $rowOrder = $db->sql_fetchrow($resultOrder);

        $SQLPatientVisit = "
        SELECT product_id
              ,qty
        FROM `medicines_visit`
        WHERE patient_visit_id = '{$patient_visit_id}'
        ";
        $resultPatientVisit = $db->sql_query($SQLPatientVisit);
        while($rowPatientVisit = $db->sql_fetchrow($resultPatientVisit)){            
            $SQL = "
            SELECT p.title
                  ,p.item_code
                  ,p.model
                  ,p.part_number
                  ,pop.selling_price AS price
                  ,p.gst
                  ,p.vat
                  ,p.discount_type
                  ,p.discount_percentage
                  ,p.discount_amount
                  ,p.tag_no
                  ,p.discount_from_date
                  ,p.discount_to_date
                  ,p.not_add_in_stock
                  ,p.add_syringe_in_pos
                  ,pop.cost_price
                  ,pop.pack_size
                  ,pop.batch_no
                  ,pop.expiry_date
                  ,pop.po_product_id
                  ,ibs.current_stock
            FROM inventory_batchwise_stock ibs
            LEFT JOIN (po_product pop) ON (pop.po_product_id = ibs.po_product_id)
            LEFT JOIN (product p) ON (p.product_id = ibs.product_id)
            WHERE ibs.product_id = '{$rowPatientVisit['product_id']}'
            {$appendSql}
            ";
            $result  = $db->sql_query($SQL);
            $row     = $db->sql_fetchrow($result);
            $numRows = $db->sql_numrows($result);

            if($numRows == 0) {
                $SQLProduct  = "
                SELECT p.title
                      ,p.item_code
                      ,p.not_add_in_stock
                      ,p.price
                FROM product p
                WHERE p.product_id = '{$rowPatientVisit['product_id']}'
                ";
                $resultProduct = $db->sql_query($SQLProduct);
                $rowProduct    = $db->sql_fetchrow($resultProduct);

                if($rowProduct['not_add_in_stock'] == ''){
                    $rowProduct['not_add_in_stock'] = 0;
                }
                
                $qty = 1;

                $fa = array();
                $fa['order_id']          = $session_order_id;
                $fa['record_id']         = $rowPatientVisit['product_id'];
                $fa['item_title']        = $rowProduct['title'];
                $fa['item_code']         = $rowProduct['item_code'];
                $fa['not_add_in_stock']  = $rowProduct['not_add_in_stock'];
                $fa['qty']               = $qty;
                $fa['unit_price']        = $rowProduct['price'];

                $SQLOrderItem = "
                SELECT *
                FROM `order_item`
                WHERE order_id = '{$session_order_id}'
                  AND record_id = '{$rowPatientVisit['product_id']}'
                ";
                $resultOrderItem = $db->sql_query($SQLOrderItem);
                $rec = $db->sql_fetchrow($resultOrderItem);

                if($rec['order_item_id'] != ''){
                    $SQLUpdate = "UPDATE order_item SET qty = ({$rec['qty']} + 1)
                                  WHERE order_id = '{$session_order_id}' AND record_id = '{$rowPatientVisit['product_id']}'";
                    $resultUpdate = $db->sql_query($SQLUpdate);
                } else {
                    $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'order_item');
                    $db->sql_query($SQL);
                    $order_item_id = $db->sql_nextid();
                }
            }
            else {
                $qty = $rowPatientVisit['qty'];

                if($qty == ""){
                    $qty = 1;
                }

                if($row['gst'] == ''){
                    $row['gst'] = 0;
                }

                $gst = $row['gst'] * $row['price'] / 100;

                if($row['vat'] == ''){
                    $row['vat'] = 0;
                }

                $vat = $row['vat'] * $row['price'] / 100;

                if($row['discount_percentage'] == ''){
                    $row['discount_percentage'] = 0;
                }

                if($row['discount_amount'] == ''){
                    $row['discount_amount'] = 0;
                }

                if($row['not_add_in_stock'] == ''){
                    $row['not_add_in_stock'] = 0;
                }

                // This if condition used to check the product discount date range and update the discount on order item
                if($rowOrder['order_date'] >= $row['discount_from_date'] && $rowOrder['order_date'] <= $row['discount_to_date']){
                }
                else{
                    $row['discount_amount']     = 0;
                    $row['discount_percentage'] = 0;
                    $row['discount_type']       = "";
                }

                $discount_value_for_one_qty = 0;
                $discountValue = 0;
                if($row['discount_type'] == '%'){
                    if($row['discount_percentage'] > 0){
                        $discount_value_for_one_qty  =  $row['unit_price'] * ($row['discount_percentage']/100);
                        $discountValue = $discount_value_for_one_qty * $row['qty'];
                    }
                }
                else if($row['discount_type']  == 'Value'){
                    if($row['discount_amount'] > 0){
                        $discount_value_for_one_qty  =  $row['discount_amount'];
                        $discountValue = $discount_value_for_one_qty * $row['qty'];
                    }
                }

                $totalAmount = $row['price'] - $discountValue;

                if($row['discount_type'] == ""){
                    $row['discount_type'] = $cpCfg['cp.posDefaultDiscountType'];
                }

                if($row['cost_price'] == ""){
                    $row['cost_price'] = 0;
                }

                if($row['price'] == ""){
                    $row['price'] = 0;
                }

                if($row['expiry_date'] == '0000-00-00'){
                    $row['expiry_date'] = '';
                }

                if(is_numeric($row['pack_size'])){
                    $price = $row['price'] / $row['pack_size'];
                } else {
                    $price = $row['price'];
                }

                if(is_numeric($row['pack_size'])){
                    $cost_price = $row['cost_price'] / $row['pack_size'];
                } else {
                    $cost_price = $row['cost_price'];
                }

                $fa = array();
                $fa['order_id']            = $session_order_id;
                $fa['record_id']           = $rowPatientVisit['product_id'];
                $fa['item_title']          = $row['title'];
                $fa['item_code']           = $row['item_code'];
                $fa['model']               = $row['model'];
                $fa['unit_price']          = $price;
                $fa['cost_price']          = $cost_price;
                $fa['ref_code']            = $row['part_number'];
                $fa['discount_type']       = $row['discount_type'];
                $fa['discount_percentage'] = $row['discount_percentage'];
                $fa['discount_amount']     = $row['discount_amount'];
                $fa['qty']                 = $qty;
                $fa['vat']                 = $row['vat'];
                $fa['gst']                 = $row['gst'];
                $fa['tag_no']              = $row['tag_no'];
                $fa['expiry_date']         = $row['expiry_date'];
                $fa['pack_size']           = $row['pack_size'];
                $fa['batch_no']            = $row['batch_no'];
                $fa['po_product_id']       = $row['po_product_id'];
                $fa['not_add_in_stock']    = $row['not_add_in_stock'];
                $fa['discounted_amount']   = $discountValue;
                $fa['total_amount']        = $totalAmount;

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'order_item');
                $db->sql_query($SQL);
                $order_item_id = $db->sql_nextid();

                if($row['add_syringe_in_pos'] == 1){
                    $SQLSyrng = "
                    SELECT p.title
                          ,p.item_code
                          ,p.model
                          ,p.part_number
                          ,p.price
                          ,p.gst
                          ,p.vat
                          ,p.discount_type
                          ,p.discount_percentage
                          ,p.discount_amount
                          ,p.tag_no
                          ,p.discount_from_date
                          ,p.discount_to_date
                          ,p.not_add_in_stock
                          ,p.add_syringe_in_pos
                          ,pop.cost_price
                          ,pop.pack_size
                          ,pop.batch_no
                          ,pop.expiry_date
                          ,pop.po_product_id
                          ,ibs.current_stock
                    FROM inventory_batchwise_stock ibs
                    LEFT JOIN (po_product pop) ON (pop.po_product_id = ibs.po_product_id)
                    LEFT JOIN (product p) ON (p.product_id = ibs.product_id)
                    WHERE ibs.product_id = '17'
                    {$appendSql}
                    ";
                    $resultSyrng = $db->sql_query($SQLSyrng);
                    $rowSyrng = $db->sql_fetchrow($resultSyrng);

                    $qty = 1;

                    if($rowSyrng['gst'] == ''){
                        $rowSyrng['gst'] = 0;
                    }

                    $gst = $rowSyrng['gst'] * $rowSyrng['price'] / 100;

                    if($rowSyrng['vat'] == ''){
                        $rowSyrng['vat'] = 0;
                    }

                    $vat = $rowSyrng['vat'] * $rowSyrng['price'] / 100;

                    if($rowSyrng['discount_percentage'] == ''){
                        $rowSyrng['discount_percentage'] = 0;
                    }

                    if($rowSyrng['discount_amount'] == ''){
                        $rowSyrng['discount_amount'] = 0;
                    }

                    if($rowSyrng['not_add_in_stock'] == '') {
                        $rowSyrng['not_add_in_stock'] = 0;
                    }

                    // This if condition used to check the product discount date range and update the discount on order item
                    if($rowOrder['order_date'] >= $rowSyrng['discount_from_date'] && $rowOrder['order_date'] <= $rowSyrng['discount_to_date']){
                    }
                    else{
                        $rowSyrng['discount_amount']     = 0;
                        $rowSyrng['discount_percentage'] = 0;
                        $rowSyrng['discount_type']       = "";
                    }

                    $discount_value_for_one_qty = 0;
                    $discountValue = 0;
                    if($rowSyrng['discount_type'] == '%'){
                        if($rowSyrng['discount_percentage'] > 0){
                            $discount_value_for_one_qty  =  $rowSyrng['unit_price'] * ($rowSyrng['discount_percentage']/100);
                            $discountValue = $discount_value_for_one_qty * $rowSyrng['qty'];
                        }
                    }
                    else if($rowSyrng['discount_type']  == 'Value'){
                        if($rowSyrng['discount_amount'] > 0){
                            $discount_value_for_one_qty  =  $rowSyrng['discount_amount'];
                            $discountValue = $discount_value_for_one_qty * $rowSyrng['qty'];
                        }
                    }

                    $totalAmount = $rowSyrng['price'] - $discountValue;

                    if($rowSyrng['discount_type'] == ""){
                        $rowSyrng['discount_type'] = $cpCfg['cp.posDefaultDiscountType'];
                    }

                    if($rowSyrng['cost_price'] == ""){
                        $rowSyrng['cost_price'] = 0;
                    }

                    if($rowSyrng['price'] == ""){
                        $rowSyrng['price'] = 0;
                    }

                    if($rowSyrng['expiry_date'] == '0000-00-00'){
                        $rowSyrng['expiry_date'] = '';
                    }

                    if(is_numeric($rowSyrng['pack_size'])){
                        $price = $rowSyrng['price'] / $rowSyrng['pack_size'];
                    } else {
                        $price = $rowSyrng['price'];
                    }

                    if(is_numeric($rowSyrng['pack_size'])){
                        $cost_price = $rowSyrng['cost_price'] / $rowSyrng['pack_size'];
                    } else {
                        $cost_price = $rowSyrng['cost_price'];
                    }

                    $faSyrng = array();
                    $faSyrng['order_id']            = $session_order_id;
                    $faSyrng['record_id']           = 17;
                    $faSyrng['item_title']          = $rowSyrng['title'];
                    $faSyrng['item_code']           = $rowSyrng['item_code'];
                    $faSyrng['model']               = $rowSyrng['model'];
                    $faSyrng['unit_price']          = $price;
                    $faSyrng['cost_price']          = $cost_price;
                    $faSyrng['discount_type']       = $rowSyrng['discount_type'];
                    $faSyrng['discount_percentage'] = $rowSyrng['discount_percentage'];
                    $faSyrng['discount_amount']     = $rowSyrng['discount_amount'];
                    $faSyrng['qty']                 = $qty;
                    $faSyrng['vat']                 = $rowSyrng['vat'];
                    $faSyrng['gst']                 = $rowSyrng['gst'];
                    $faSyrng['tag_no']              = $rowSyrng['tag_no'];
                    $faSyrng['expiry_date']         = $rowSyrng['expiry_date'];
                    $faSyrng['pack_size']           = $rowSyrng['pack_size'];
                    $faSyrng['batch_no']            = $rowSyrng['batch_no'];
                    $faSyrng['po_product_id']       = $rowSyrng['po_product_id'];
                    $faSyrng['not_add_in_stock']    = $rowSyrng['not_add_in_stock'];
                    $faSyrng['discounted_amount']   = $discountValue;
                    $faSyrng['total_amount']        = $totalAmount;

                    $SQLOrderItem2 = "
                    SELECT *
                    FROM `order_item`
                    WHERE order_id = '{$session_order_id}'
                      AND record_id = 17
                    ";
                    $resultOrderItem2 = $db->sql_query($SQLOrderItem2);
                    $rec2 = $db->sql_fetchrow($resultOrderItem2);

                    if($rec2['order_item_id'] != ''){
                        $SQLUpdate2 = "UPDATE order_item SET qty = ({$rec2['qty']} + 1)
                                       WHERE order_id = '{$session_order_id}' 
                                       AND record_id = 17
                                       AND po_product_id = {$rowSyrng['po_product_id']}";
                        $resultUpdate2 = $db->sql_query($SQLUpdate2);
                    } else {
                        $SQL2 = $dbUtil->getInsertSQLStringFromArray($faSyrng, 'order_item');
                        $db->sql_query($SQL2);
                        //$order_item_id = $db->sql_nextid();
                    }
                }
            }
        }

        $SQLUpdateOrderBill = "
        UPDATE `order` SET patient_visit_id = '{$patient_visit_id}'
        WHERE order_id = '{$session_order_id}'
        ";
        $resultUpdateOrderBill = $db->sql_query($SQLUpdateOrderBill);
    }

    /**
     *
     */
     function getUpdateOrderLineItemsVisit1() {
        $fn     = Zend_Registry::get('fn');
        $db     = Zend_Registry::get('db');
        $tv     = Zend_Registry::get('tv');
        $cpCfg  = Zend_Registry::get('cpCfg');
        $dbUtil = Zend_Registry::get('dbUtil');

        $patient_visit_id = $fn->getReqParam('patient_visit_id');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
        $cpSiteIdSession  = $fn->getSessionParam('cp_site_id');
        
        $appendSql   = '';
        $sqlAppendSt = '';
        $stockTransferSQLForMultiSite = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql   = "AND po.site_id = {$cpSiteIdSession}";
            $sqlAppendSt = "AND st.to_location = {$cpSiteIdSession}";

            $stockTransferSQLForMultiSite = "
            UNION
            SELECT  p.product_id AS product_id
                   ,p.title
                   ,p.not_add_in_stock
            FROM stock_transfer_history sth
            LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
            LEFT JOIN product p ON (p.product_id = sth.product_id)
            WHERE sth.product_id = '{$rowPatientVisit['product_id']}'
            AND st.status = 'Delivered'
            {$sqlAppendSt}
            GROUP BY product_id
            ";
        }

        $SQLPatientVisit = "
        SELECT pv.employee_id
              ,pv.visit_code
              ,CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
              ,p.name AS patient_name
        FROM patient_visit pv
        LEFT JOIN employee e ON (e.employee_id = pv.employee_id)
        LEFT JOIN patient_information p ON (p.patient_information_id = pv.patient_information_id)
        WHERE pv.patient_visit_id = {$patient_visit_id}
        ";
        $resultPatientVisit = $db->sql_query($SQLPatientVisit);
        $rowPatientVisit = $db->sql_fetchrow($resultPatientVisit);

        $SQLUpdateOrder = "
        UPDATE `order` 
        SET ref_by_dr  = '{$rowPatientVisit['employee_name']}'
           ,visit_code = '{$rowPatientVisit['visit_code']}'
           ,cust_first_name = '{$rowPatientVisit['patient_name']}'
        WHERE order_id = '{$session_order_id}'
        ";
        $resultUpdateOrder = $db->sql_query($SQLUpdateOrder);

        $SQLOrder = "
        SELECT order_date
        FROM `order`
        WHERE order_id = '{$session_order_id}'
        ";
        $resultOrder = $db->sql_query($SQLOrder);
        $rowOrder = $db->sql_fetchrow($resultOrder);

        $SQLPatientVisit = "
        SELECT product_id
              ,qty
        FROM `medicines_visit`
        WHERE patient_visit_id = '{$patient_visit_id}'
        ";
        $resultPatientVisit = $db->sql_query($SQLPatientVisit);
        while($rowPatientVisit = $db->sql_fetchrow($resultPatientVisit)){
            $SQLPO  = "
            SELECT  p.product_id AS product_id
                   ,p.title
                   ,p.not_add_in_stock
            FROM product p
            LEFT JOIN (po_product pop) ON (pop.product_id = p.product_id)
            LEFT JOIN (purchase_order po) ON (po.purchase_order_id = pop.purchase_order_id)
            WHERE p.product_id = '{$rowPatientVisit['product_id']}'
            AND po.status != 'Cancelled'
            {$appendSql}
            {$stockTransferSQLForMultiSite}
            ";
            $resultPO  = $db->sql_query($SQLPO);
            $rowPO     = $db->sql_fetchrow($resultPO);
            $numRowsPO = $db->sql_numrows($resultPO);

            if($numRowsPO == 0) {
                $SQLProduct  = "
                SELECT p.title
                      ,p.item_code
                      ,p.not_add_in_stock
                      ,p.price
                FROM product p
                WHERE p.product_id = '{$rowPatientVisit['product_id']}'
                ";
                $resultProduct = $db->sql_query($SQLProduct);
                $rowProduct    = $db->sql_fetchrow($resultProduct);

                if($rowProduct['not_add_in_stock'] == ''){
                    $rowProduct['not_add_in_stock'] = 0;
                }
                
                $qty = 1;

                $fa = array();
                $fa['order_id']          = $session_order_id;
                $fa['record_id']         = $rowPatientVisit['product_id'];
                $fa['item_title']        = $rowProduct['title'];
                $fa['item_code']         = $rowProduct['item_code'];
                $fa['not_add_in_stock']  = $rowProduct['not_add_in_stock'];
                $fa['qty']               = $qty;
                $fa['unit_price']        = $rowProduct['price'];

                $SQLOrderItem = "
                SELECT *
                FROM `order_item`
                WHERE order_id = '{$session_order_id}'
                  AND record_id = '{$rowPatientVisit['product_id']}'
                ";
                $resultOrderItem = $db->sql_query($SQLOrderItem);
                $rec = $db->sql_fetchrow($resultOrderItem);

                if($rec['order_item_id'] != ''){
                    $SQLUpdate = "UPDATE order_item SET qty = ({$rec['qty']} + 1)
                                  WHERE order_id = '{$session_order_id}' AND record_id = '{$rowPatientVisit['product_id']}'";
                    $resultUpdate = $db->sql_query($SQLUpdate);
                } else {
                    $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'order_item');
                    $db->sql_query($SQL);
                    $order_item_id = $db->sql_nextid();
                }
            }
            else {
                $SQL    = "
                SELECT p.title
                      ,p.item_code
                      ,p.model
                      ,p.part_number
                      ,p.price
                      ,p.gst
                      ,p.vat
                      ,p.discount_type
                      ,p.discount_percentage
                      ,p.discount_amount
                      ,p.tag_no
                      ,p.discount_from_date
                      ,p.discount_to_date
                      ,p.not_add_in_stock
                      ,p.add_syringe_in_pos
                      ,pop.cost_price
                      ,pop.pack_size
                      ,pop.batch_no
                      ,pop.expiry_date
                FROM product p
                LEFT JOIN (po_product pop) ON (pop.product_id = p.product_id)
                LEFT JOIN (purchase_order po) ON (po.purchase_order_id = pop.purchase_order_id)
                WHERE p.product_id = '{$rowPatientVisit['product_id']}'
                AND po.status != 'Cancelled'
                {$appendSql}
                ";
                $result = $db->sql_query($SQL);
                $row = $db->sql_fetchrow($result);

                $qty = $rowPatientVisit['qty'];

                if($qty == ""){
                    $qty = 1;
                }

                if($row['gst'] == ''){
                    $row['gst'] = 0;
                }

                $gst = $row['gst'] * $row['price'] / 100;

                if($row['vat'] == ''){
                    $row['vat'] = 0;
                }

                $vat = $row['vat'] * $row['price'] / 100;

                if($row['discount_percentage'] == ''){
                    $row['discount_percentage'] = 0;
                }

                if($row['discount_amount'] == ''){
                    $row['discount_amount'] = 0;
                }

                if($row['not_add_in_stock'] == ''){
                    $row['not_add_in_stock'] = 0;
                }

                // This if condition used to check the product discount date range and update the discount on order item
                if($rowOrder['order_date'] >= $row['discount_from_date'] && $rowOrder['order_date'] <= $row['discount_to_date']){
                }
                else{
                    $row['discount_amount']     = 0;
                    $row['discount_percentage'] = 0;
                    $row['discount_type']       = "";
                }

                $discount_value_for_one_qty = 0;
                $discountValue = 0;
                if($row['discount_type'] == '%'){
                    if($row['discount_percentage'] > 0){
                        $discount_value_for_one_qty  =  $row['unit_price'] * ($row['discount_percentage']/100);
                        $discountValue = $discount_value_for_one_qty * $row['qty'];
                    }
                }
                else if($row['discount_type']  == 'Value'){
                    if($row['discount_amount'] > 0){
                        $discount_value_for_one_qty  =  $row['discount_amount'];
                        $discountValue = $discount_value_for_one_qty * $row['qty'];
                    }
                }

                $totalAmount = $row['price'] - $discountValue;

                if($row['discount_type'] == ""){
                    $row['discount_type'] = $cpCfg['cp.posDefaultDiscountType'];
                }

                if($row['cost_price'] == ""){
                    $row['cost_price'] = 0;
                }

                if($row['price'] == ""){
                    $row['price'] = 0;
                }

                if($row['expiry_date'] == '0000-00-00'){
                    $row['expiry_date'] = '';
                }

                if(is_numeric($row['pack_size'])){
                    $price = $row['price'] / $row['pack_size'];
                } else {
                    $price = $row['price'];
                }

                if(is_numeric($row['pack_size'])){
                    $cost_price = $row['cost_price'] / $row['pack_size'];
                } else {
                    $cost_price = $row['cost_price'];
                }

                $fa = array();
                $fa['order_id']            = $session_order_id;
                $fa['record_id']           = $rowPatientVisit['product_id'];
                $fa['item_title']          = $row['title'];
                $fa['item_code']           = $row['item_code'];
                $fa['model']               = $row['model'];
                $fa['unit_price']          = $price;
                $fa['cost_price']          = $cost_price;
                $fa['ref_code']            = $row['part_number'];
                $fa['discount_type']       = $row['discount_type'];
                $fa['discount_percentage'] = $row['discount_percentage'];
                $fa['discount_amount']     = $row['discount_amount'];
                $fa['qty']                 = $qty;
                $fa['vat']                 = $row['vat'];
                $fa['gst']                 = $row['gst'];
                $fa['tag_no']              = $row['tag_no'];
                $fa['expiry_date']         = $row['expiry_date'];
                $fa['pack_size']           = $row['pack_size'];
                $fa['batch_no']            = $row['batch_no'];
                $fa['not_add_in_stock']    = $row['not_add_in_stock'];
                $fa['discounted_amount']   = $discountValue;
                $fa['total_amount']        = $totalAmount;

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'order_item');
                $db->sql_query($SQL);
                $order_item_id = $db->sql_nextid();

                if($row['add_syringe_in_pos'] == 1){
                    $SQLSyrng = "
                    SELECT p.title
                          ,p.item_code
                          ,p.model
                          ,p.part_number
                          ,p.price
                          ,p.gst
                          ,p.vat
                          ,p.discount_type
                          ,p.discount_percentage
                          ,p.discount_amount
                          ,p.tag_no
                          ,p.discount_from_date
                          ,p.discount_to_date
                          ,p.not_add_in_stock
                          ,p.add_syringe_in_pos
                          ,pop.cost_price
                          ,pop.pack_size
                          ,pop.batch_no
                          ,pop.expiry_date
                    FROM product p
                    LEFT JOIN (po_product pop) ON (pop.product_id = p.product_id)
                    LEFT JOIN (purchase_order po) ON (po.purchase_order_id = pop.purchase_order_id)
                    WHERE p.product_id = '17'
                    AND po.status != 'Cancelled'
                    {$appendSql}
                    ";
                    $resultSyrng = $db->sql_query($SQLSyrng);
                    $rowSyrng = $db->sql_fetchrow($resultSyrng);

                    $qty = 1;

                    if($rowSyrng['gst'] == ''){
                        $rowSyrng['gst'] = 0;
                    }

                    $gst = $rowSyrng['gst'] * $rowSyrng['price'] / 100;

                    if($rowSyrng['vat'] == ''){
                        $rowSyrng['vat'] = 0;
                    }

                    $vat = $rowSyrng['vat'] * $rowSyrng['price'] / 100;

                    if($rowSyrng['discount_percentage'] == ''){
                        $rowSyrng['discount_percentage'] = 0;
                    }

                    if($rowSyrng['discount_amount'] == ''){
                        $rowSyrng['discount_amount'] = 0;
                    }

                    if($rowSyrng['not_add_in_stock'] == '') {
                        $rowSyrng['not_add_in_stock'] = 0;
                    }

                    // This if condition used to check the product discount date range and update the discount on order item
                    if($rowOrder['order_date'] >= $rowSyrng['discount_from_date'] && $rowOrder['order_date'] <= $rowSyrng['discount_to_date']){
                    }
                    else{
                        $rowSyrng['discount_amount']     = 0;
                        $rowSyrng['discount_percentage'] = 0;
                        $rowSyrng['discount_type']       = "";
                    }

                    $discount_value_for_one_qty = 0;
                    $discountValue = 0;
                    if($rowSyrng['discount_type'] == '%'){
                        if($rowSyrng['discount_percentage'] > 0){
                            $discount_value_for_one_qty  =  $rowSyrng['unit_price'] * ($rowSyrng['discount_percentage']/100);
                            $discountValue = $discount_value_for_one_qty * $rowSyrng['qty'];
                        }
                    }
                    else if($rowSyrng['discount_type']  == 'Value'){
                        if($rowSyrng['discount_amount'] > 0){
                            $discount_value_for_one_qty  =  $rowSyrng['discount_amount'];
                            $discountValue = $discount_value_for_one_qty * $rowSyrng['qty'];
                        }
                    }

                    $totalAmount = $rowSyrng['price'] - $discountValue;

                    if($rowSyrng['discount_type'] == ""){
                        $rowSyrng['discount_type'] = $cpCfg['cp.posDefaultDiscountType'];
                    }

                    if($rowSyrng['cost_price'] == ""){
                        $rowSyrng['cost_price'] = 0;
                    }

                    if($rowSyrng['price'] == ""){
                        $rowSyrng['price'] = 0;
                    }

                    if($rowSyrng['expiry_date'] == '0000-00-00'){
                        $rowSyrng['expiry_date'] = '';
                    }

                    if(is_numeric($rowSyrng['pack_size'])){
                        $price = $rowSyrng['price'] / $rowSyrng['pack_size'];
                    } else {
                        $price = $rowSyrng['price'];
                    }

                    if(is_numeric($rowSyrng['pack_size'])){
                        $cost_price = $rowSyrng['cost_price'] / $rowSyrng['pack_size'];
                    } else {
                        $cost_price = $rowSyrng['cost_price'];
                    }

                    $faSyrng = array();
                    $faSyrng['order_id']            = $session_order_id;
                    $faSyrng['record_id']           = 17;
                    $faSyrng['item_title']          = $rowSyrng['title'];
                    $faSyrng['item_code']           = $rowSyrng['item_code'];
                    $faSyrng['model']               = $rowSyrng['model'];
                    $faSyrng['unit_price']          = $price;
                    $faSyrng['cost_price']          = $cost_price;
                    $faSyrng['discount_type']       = $rowSyrng['discount_type'];
                    $faSyrng['discount_percentage'] = $rowSyrng['discount_percentage'];
                    $faSyrng['discount_amount']     = $rowSyrng['discount_amount'];
                    $faSyrng['qty']                 = $qty;
                    $faSyrng['vat']                 = $rowSyrng['vat'];
                    $faSyrng['gst']                 = $rowSyrng['gst'];
                    $faSyrng['tag_no']              = $rowSyrng['tag_no'];
                    $faSyrng['expiry_date']         = $rowSyrng['expiry_date'];
                    $faSyrng['pack_size']           = $rowSyrng['pack_size'];
                    $faSyrng['batch_no']            = $rowSyrng['batch_no'];
                    $faSyrng['not_add_in_stock']    = $rowSyrng['not_add_in_stock'];
                    $faSyrng['discounted_amount']   = $discountValue;
                    $faSyrng['total_amount']        = $totalAmount;

                    $SQLOrderItem2 = "
                    SELECT *
                    FROM `order_item`
                    WHERE order_id = '{$session_order_id}'
                      AND record_id = 17
                    ";
                    $resultOrderItem2 = $db->sql_query($SQLOrderItem2);
                    $rec2 = $db->sql_fetchrow($resultOrderItem2);

                    if($rec2['order_item_id'] != ''){
                        $SQLUpdate2 = "UPDATE order_item SET qty = ({$rec2['qty']} + 1)
                                      WHERE order_id = '{$session_order_id}' AND record_id = 17";
                        $resultUpdate2 = $db->sql_query($SQLUpdate2);
                    } else {
                        $SQL2 = $dbUtil->getInsertSQLStringFromArray($faSyrng, 'order_item');
                        $db->sql_query($SQL2);
                        //$order_item_id = $db->sql_nextid();
                    }
                }
            }
        }

        $SQLUpdateOrderBill = "
        UPDATE `order` SET patient_visit_id = '{$patient_visit_id}'
        WHERE order_id = '{$session_order_id}'
        ";
        $resultUpdateOrderBill = $db->sql_query($SQLUpdateOrderBill);
    }

    /**
     *
     */
    function getCreateNewOrder() {
        $fn     = Zend_Registry::get('fn');
        $db     = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg  = Zend_Registry::get('cpCfg');

        $staff_id        = $fn->getSessionParam('staff_id');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $checkLastOrder  = $fn->getReqParam('checkLastOrder');
        $today = date('Y-m-d');

        $SQLOrderChk = "
        SELECT order_id
        FROM `order`
        WHERE order_status = 'New'
        AND order_type = 'POS'
        AND staff_id = {$staff_id}
        AND order_date != '{$today}'
        ORDER BY order_id DESC
        LIMIT 1
        ";
        $resultOrderChk  = $db->sql_query($SQLOrderChk);
        $numRowsOrderChk = $db->sql_numrows($resultOrderChk);
        $rowOrderChk     = $db->sql_fetchrow($resultOrderChk);
        
        if($checkLastOrder == 1 && $numRowsOrderChk > 0) {
            $sqlDeleteOrder = "
            DELETE FROM `order`
            WHERE order_id = {$rowOrderChk['order_id']}
            ";
            $resultDeleteOrder = $db->sql_query($sqlDeleteOrder);

            $sqlDeleteOrderItem = "
            DELETE FROM `order_item`
            WHERE order_id = {$rowOrderChk['order_id']}
            ";
            $resultDeleteOrderItem = $db->sql_query($sqlDeleteOrderItem);
        }

        $SQLOrder = "
        SELECT order_id
        FROM `order`
        WHERE order_status = 'New'
        AND order_type = 'POS'
        AND staff_id = {$staff_id}
        ORDER BY order_id DESC
        LIMIT 1
        ";
        $resultOrder  = $db->sql_query($SQLOrder);
        $numRowsOrder = $db->sql_numrows($resultOrder);
        $rowOrder     = $db->sql_fetchrow($resultOrder);
        
        if($checkLastOrder == 1 && $numRowsOrder > 0) {
            $faOrder = array();
            $faOrder['order_date'] = date('Y-m-d');
            $faOrder = $fn->addCreationDetailsToFieldsArray($faOrder, 'order');
            $faOrder = $fn->addModificationDetailsToFieldsArray($faOrder, 'order');
            $whereCondition = "WHERE order_id = {$rowOrder['order_id']}";
            $SQLUpdateOrder = $dbUtil->getUpdateSQLStringFromArray($faOrder, 'order', $whereCondition);
            $db->sql_query($SQLUpdateOrder);

            $_SESSION['order_id'] = $rowOrder['order_id'];
        } else {
            if ($cpCfg['showGstInBill'] == 1) {
                $gst_status = 'OFF';
            } else {
                $gst_status = 'ON';            
            }

            $appendSql = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSql = "WHERE site_id = {$cpSiteIdSession}";
            }

            $SQLOrderCode = "
            SELECT MAX(order_code) + 1 AS order_code
            FROM `order`
            {$appendSql}
            ";
            $resultOrderCode = $db->sql_query($SQLOrderCode);
            $rowOrderCode    = $db->sql_fetchrow($resultOrderCode);

            if($rowOrderCode['order_code'] != ""){
                $order_code = $rowOrderCode['order_code'];
            }
            else{
                $order_code = "1000";
            }

            $fa = array();
            $fa['order_status']    = 'New';
            $fa['record_type']     = 'POS';
            $fa['order_code']      = $order_code;
            $fa['order_date']      = date('Y-m-d');
            $fa['name_of_company'] = 'POS';
            $fa['order_type']      = 'POS';
            $fa['gst_status']      = $gst_status;
            $fa['vat']             = 1;
            $fa['link_stock']      = 1;
            $fa['staff_id']        = $staff_id;
            $fa['invoice_terms']   = $cpCfg['invoiceTermsForPrint'];
            $fa['bill_by']   = $cpCfg['billname'];
            
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $fa['site_id']     = $cpSiteIdSession;
            }
            $fa = $fn->addCreationDetailsToFieldsArray($fa, 'order');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'order');
            $db->sql_query($SQL);
            $order_id = $db->sql_nextid();

            $currentDateTime = date('Y-m-d H:i:s');
            $currentDate = date('Y-m-d');

            $SQLSetting ="
            SELECT DATE_FORMAT(modification_date, '%Y-%m-%d')AS modification_date
                  ,value
            FROM setting
            WHERE key_text = 'nextBillNumber'
            ";
            $resultSetting = $db->sql_query($SQLSetting);
            $rowSetting    = $db->sql_fetchrow($resultSetting);

            if($cpCfg['cp.posBillNoContinuity'] == 0){
                if($currentDate != $rowSetting['modification_date']){
                    $bill_number   = 1;
                } else {
                    $bill_number   = $rowSetting['value'] + 1;
                }
            } else {
                $SQLOrder = "
                SELECT MAX(CONVERT(bill_number, UNSIGNED INTEGER)) AS bill_number
                FROM `order`
                WHERE order_status != 'Cancelled'
                ";
                $resultOrder   = $db->sql_query($SQLOrder);
                $recOrder      = $db->sql_fetchrow($resultOrder);
                $bill_number   = $recOrder['bill_number'] + 1;
            }

            $SQLUpdate = "
            UPDATE setting SET value = '{$bill_number}', modification_date = '{$currentDateTime}'
            WHERE key_text = 'nextBillNumber'
            ";
            $resultUpdate = $db->sql_query($SQLUpdate);

            $SQLUpdateOrderBill = "
            UPDATE `order` SET bill_number = '{$bill_number}'
            WHERE order_id = '{$order_id}'
            ";
            $resultUpdateOrderBill = $db->sql_query($SQLUpdateOrderBill);

            $_SESSION['order_id'] = $order_id;
        }
    }

    /**
     *
     */
    function getApplyDiscountSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        if (!$this->getApplyDiscountValidate()){
            return $validate->getErrorMessageXML();
        }

        $discount_value= $fn->getPostParam('discount_value');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $sqlUpdate = "
        UPDATE `order` SET discount = '{$discount_value}'
        WHERE order_id = {$session_order_id}
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);


        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getApplyDiscountValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');

        $validate->resetErrorArray();
        $validate->validateData('discount_value', 'Please enter discount amount');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
        $discount_value = $fn->getPostParam('discount_value');
        
        $OrderTotalAmount = $this->view->getTotalAmount($session_order_id);
        
        $discountApplied = $fn->getReqParam('discountApplied');
        if($discountApplied != ""){
            $OrderTotalAmount = $OrderTotalAmount + $discountApplied;
        }

        $OrderTotalAmountFormatted = number_format($OrderTotalAmount, 2);

        if($discount_value != "" && $discount_value > 0){
            if($OrderTotalAmount == 0){
                $validate->errorArray['discount_value']['name'] = "discount_value";
                $validate->errorArray['discount_value']['msg']  = "Please select some items before apply discount";
            }
        }

        if($OrderTotalAmount > 0){
            if($discount_value >= $OrderTotalAmount){
                $validate->errorArray['discount_value']['name'] = "discount_value";
                $validate->errorArray['discount_value']['msg']  = "Please enter discount amount as lesser than order amount: {$OrderTotalAmountFormatted}";
            }
        }

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getAddClientSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        if (!$this->getAddClientValidate()){
            return $validate->getErrorMessageXML();
        }

        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $company_name    = $fn->getReqParam('company_name');
        $mobile          = $fn->getReqParam('mobile');
        $email           = $fn->getReqParam('email');
        $address_flat    = $fn->getReqParam('address_flat');
        $address_street  = $fn->getReqParam('address_street');
        $address_town    = $fn->getReqParam('address_town');
        $address_state   = $fn->getReqParam('address_state');
        $address_country = $fn->getReqParam('address_country');
        $gst_no          = $fn->getReqParam('gst_no');
        $address_po_code = $fn->getReqParam('address_po_code');

        $fa = array();
        $fa['company_name']    = $company_name;
        $fa['mobile']          = $mobile;
        $fa['email']           = $email;
        $fa['address_flat']    = $address_flat;
        $fa['address_street']  = $address_street;
        $fa['address_town']    = $address_town;
        $fa['address_state']   = $address_state;
        $fa['address_country'] = $address_country;
        $fa['address_po_code'] = $address_po_code;
        $fa['gst_no']          = $gst_no;
        $fa['category']        = 'Client';
        $id = $fn->addRecord($fa, 'company');

        $fa1 = array();
        $fa1['cust_company_name']         = $company_name;
        $fa1['company_id']                = $id;
        $fa1['cust_phone']                = $mobile;
        $fa1['cust_email']                = $email;
        $fa1['cust_address1']             = $address_flat;
        $fa1['cust_address2']             = $address_street;
        $fa1['cust_address_city']         = $address_town;
        $fa1['cust_address_state']        = $address_state;
        $fa1['cust_address_country_code'] = $address_country;
        $fa1['cust_gst_no']               = $gst_no;

        $fa1['shipping_first_name']           = $company_name;
        $fa1['shipping_phone']                = $mobile;
        $fa1['shipping_email']                = $email;
        $fa1['shipping_address1']             = $address_flat;
        $fa1['shipping_address2']             = $address_street;
        $fa1['shipping_address_city']         = $address_town;
        $fa1['shipping_address_state']        = $address_state;
        $fa1['shipping_address_country_code'] = $address_country;
        $fa1['shipping_gst_no']               = $gst_no;

        $whereCondition = "WHERE order_id = {$session_order_id}";
        $SQL = $dbUtil->getUpdateSQLStringFromArray($fa1, 'order', $whereCondition);
        $db->sql_query($SQL);

        return $validate->getSuccessMessageXML();
    }


    /**
     *
     */
    function getAddClientValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $validate->resetErrorArray();
        $company_name= $fn->getReqParam('company_name');

        $validate->validateData('company_name', 'Please enter the company name');

        if($company_name != ''){
            $SQL = "
            SELECT c.*
            FROM company c
            WHERE c.company_name = '{$company_name}'
            ";
            $result = $db->sql_query($SQL);
            $numRows  = $db->sql_numrows($result);
            if($numRows > 0){
                $validate->errorArray['company_name']['name'] = "company_name";
                $validate->errorArray['company_name']['msg']  = "Company name already exist";
            }
        }

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }


    /**
     *
     */
    function getCancelOrderNotesSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        if (!$this->getCancelOrderNotesValidate()){
            return $validate->getErrorMessageXML();
        }

        $order_id = $fn->getReqParam('order_id');
        $notes    = $fn->getPostParam('notes');

        $fa = array();
        $fa['notes'] = $notes;

        $whereCondition = "WHERE order_id = {$order_id}";
        $SQL = $dbUtil->getUpdateSQLStringFromArray($fa, 'order', $whereCondition);
        $db->sql_query($SQL);

        $this->getCancelOrderCurrent();

        return $validate->getSuccessMessageXML();
    }


    /**
     *
     */
    function getCancelOrderNotesValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $validate->resetErrorArray();
        $validate->validateData('notes', 'Please enter notes');

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }


    /**
     *
     */
    function getSearchCustomerDetails() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $title = $fn->getReqParam('term', '', true);
        $extractor = explode(" **** ", $title);

        $companyDetail = $extractor[0];

        $SQL = "
        SELECT c.company_name AS value
              ,c.company_name AS label
              ,c.company_id AS id
              ,c.company_name
        FROM company c
        WHERE (c.company_id LIKE '%{$companyDetail}%'
        OR c.company_name LIKE '%{$companyDetail}%'
        OR c.mobile LIKE '%{$companyDetail}%'
        OR c.email LIKE '%{$companyDetail}%')
        ORDER BY c.company_name
        ";
        $result = $db->sql_query($SQL);

        $dataArray = $dbUtil->getResultsetAsArray($result);
        $arr = json_encode($dataArray);
        return $arr;
    }

    /**
     *
     */
    function getDisplayCustomerDetails() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $company_id= $fn->getReqParam('company_id');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
        $loyaltypoint = '';

        $SQL = "
        SELECT c.*
        FROM company c
        WHERE c.company_id = {$company_id}
        ";
        $result = $db->sql_query($SQL);
        $row    = $db->sql_fetchrow($result);

        if($row['loyalty_point_linked'] != ''){
            $loyaltypoint ="Loyalty point linked";
        }else {
            $loyaltypoint ="
                <div class='btn btn-info'>
                    <a company_name ='{$row['company_name']}' href='#' class='loyaltypoint'>Link Loyalty Point</a>
                </div>
            ";
        }

        $text = "
        <div class='mt10'>
            <div>Company Name: {$row['company_name']}</div>
            <div>Mobile: {$row['mobile']}</div>
            <div>Email : {$row['email']}</div>
            <div>Address: {$row['address_flat']} ,{$row['address_street']} ,{$row['address_town']} {$row['address_state']}</div>
            {$loyaltypoint}
        </div>

        <div class='btn btn-info float_left mt10'>
            <a href='javascript:void(0);' id='removeClient'><span class='removeClientIcon'></span>Remove Client</a>
        </div>
        ";

        $fa1 = array();
        $fa1['cust_company_name']             = $row['company_name'];
        $fa1['company_id']                    = $row['company_id'];
        $fa1['cust_phone']                    = $row['mobile'];
        $fa1['cust_email']                    = $row['email'];
        $fa1['cust_address1']                 = $row['address_flat'];
        $fa1['cust_address2']                 = $row['address_street'];
        $fa1['cust_address_city']             = $row['address_town'];
        $fa1['cust_address_state']            = $row['address_state'];
        $fa1['cust_address_country_code']     = $row['address_country'];
        $fa1['cust_gst_no']                   = $row['gst_no'];

        $fa1['shipping_first_name']           = $row['company_name'];
        $fa1['shipping_phone']                = $row['mobile'];
        $fa1['shipping_email']                = $row['email'];
        $fa1['shipping_address1']             = $row['address_flat'];
        $fa1['shipping_address2']             = $row['address_street'];
        $fa1['shipping_address_city']         = $row['address_town'];
        $fa1['shipping_address_state']        = $row['address_state'];
        $fa1['shipping_address_country_code'] = $row['address_country'];
        $fa1['shipping_gst_no']               = $row['gst_no'];

        $whereCondition = "WHERE order_id = {$session_order_id}";
        $SQL = $dbUtil->getUpdateSQLStringFromArray($fa1, 'order', $whereCondition);
        $db->sql_query($SQL);

        return $text;
    }

    /**
     *
     */
    function getRemoveClient() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $fa1 = array();
        $fa1['cust_company_name'] = '';
        $fa1['company_id'] = '';
        $fa1['cust_phone'] = '';
        $fa1['cust_email'] = '';
        $fa1['cust_address1'] = '';
        $fa1['cust_address2'] = '';
        $fa1['cust_address_city'] = '';
        $fa1['cust_address_state'] = '';
        $fa1['cust_address_country_code'] = '';

        $whereCondition = "WHERE order_id = {$session_order_id}";
        $SQL = $dbUtil->getUpdateSQLStringFromArray($fa1, 'order', $whereCondition);
        $db->sql_query($SQL);

        return $text;
    }

    /**
     *
     */
    function getUpdateQtyOrderItem() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_item_id = $fn->getReqParam('order_item_id');
        $qty = $fn->getReqParam('qty');

        $SQL    = "
        UPDATE order_item
        set qty = {$qty}
        WHERE order_item_id = {$order_item_id}
        ";
        $result = $db->sql_query($SQL);

    }

    /**
     *
     */
    function getUpdateWeightOrderItem() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_item_id = $fn->getReqParam('order_item_id');
        $weight = $fn->getReqParam('weight');
        if($weight == ''){
            $weight = 0;
        }

        $SQL    = "
        UPDATE order_item
        set weight = {$weight}, qty = 0
        WHERE order_item_id = {$order_item_id}
        ";
        $result = $db->sql_query($SQL);

    }

    /**
     *
     */
    function getUpdatediscountType() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_item_id       = $fn->getReqParam('order_item_id');
        $discount_type       = $fn->getReqParam('discount_type');
        $discount_percentage = $fn->getReqParam('discount_percentage');

        $order_item_percentage = 0;

        if ($discount_type !=''){
            $order_item_percentage = $discount_type;
            //$discount_type ='%';
        }

        if($discount_type == "%"){
            $SQL    = "
            UPDATE order_item
            set discount_percentage = '{$discount_percentage}', discount_type ='%', discount_amount = '0.00'
            WHERE order_item_id = {$order_item_id}
            ";
            $result = $db->sql_query($SQL);
        }else if($discount_type == "Value"){
            $SQL    = "
            UPDATE order_item
            set discount_amount = '{$discount_percentage}', discount_type ='Value', discount_percentage = '0.00'
            WHERE order_item_id = {$order_item_id}
            ";
            $result = $db->sql_query($SQL);
        }else{
            $SQL    = "
            UPDATE order_item
            set discount_amount = '0.00', discount_type ='', discount_percentage = '0.00'
            WHERE order_item_id = {$order_item_id}
            ";
            $result = $db->sql_query($SQL);
        }

    }

    /**
     *
     */
    function getUpdateDiscountPercentOrderItem() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_item_id = $fn->getReqParam('order_item_id');
        $discount_percentage = $fn->getReqParam('discount_percentage');
        $discount_type = $fn->getReqParam('discount_type');

        if($discount_type == "%"){
            $SQL    = "
            UPDATE order_item
            set discount_percentage = '{$discount_percentage}', discount_type ='%', discount_amount = '0.00'
            WHERE order_item_id = {$order_item_id}
            ";
            $result = $db->sql_query($SQL);
        }else{
            $SQL    = "
            UPDATE order_item
            set discount_amount = '{$discount_percentage}', discount_type ='Value', discount_percentage = '0.00'
            WHERE order_item_id = {$order_item_id}
            ";
            $result = $db->sql_query($SQL);
        }

    }

    /**
     *
     */
    function getUpdatePiecesOrderItem() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_item_id = $fn->getReqParam('order_item_id');
        $pieces = $fn->getReqParam('pieces');

        $SQL    = "
        UPDATE order_item
        set pieces = '{$pieces}'
        WHERE order_item_id = {$order_item_id}
        ";
        $result = $db->sql_query($SQL);

    }

    /**
     *
     */
    function getUpdateDiscountOrder() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_id = $fn->getReqParam('order_id');
        $discount = $fn->getReqParam('discount');

        $SQL    = "
        UPDATE `order`
        set discount = {$discount}
        WHERE order_id = {$order_id}
        ";
        $result = $db->sql_query($SQL);

    }

    /**
     *
     */
    function getUpdateBalance() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $amount_given = $fn->getReqParam('amount_given');
        $netTotal = $fn->getReqParam('netTotal');

        $balance = $amount_given - $netTotal;
        $balance = number_format($balance, 2);
        return $balance;

    }

    /**
     *
     */
    function getCancelOrder() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $SQL    = "
        UPDATE `order`
        set order_status = 'Cancelled'
        WHERE order_id = {$session_order_id}
        ";
        $result = $db->sql_query($SQL);

        $SQL    = "
        UPDATE `invoice`
        set status = 'Cancelled'
        WHERE order_id = {$session_order_id}
        ";
        $result = $db->sql_query($SQL);

        $SQL    = "
        UPDATE `receipt`
        set receipt_status = 'Cancelled'
        WHERE order_id = {$session_order_id}
        ";
        $result = $db->sql_query($SQL);

        $_SESSION['order_id'] = '';

    }

    /**
     *
     */
    function getCancelOrderCurrent() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $SQL    = "
        UPDATE `order`
        set order_status = 'Cancelled'
        WHERE order_id = {$session_order_id}
        ";
        $result = $db->sql_query($SQL);

        $SQL    = "
        UPDATE `invoice`
        set status = 'Cancelled'
        WHERE order_id = {$session_order_id}
        ";
        $result = $db->sql_query($SQL);

        $SQL    = "
        UPDATE `receipt`
        set receipt_status = 'Cancelled'
        WHERE order_id = {$session_order_id}
        ";
        $result = $db->sql_query($SQL);

        $this->getCreateNewOrder();

    }

    /**
     *
     */
    function getCloseOrder() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $_SESSION['order_id'] = '';

    }

    /**
     *
     */
    function getDeleteItem(){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $formObj = Zend_Registry::get('formObj');
        $cpCfg = Zend_Registry::get('cpCfg');

        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : false;
        $order_item_id    = $fn->getReqParam('order_item_id');

        if($session_order_id){
            $deleteSQL    = "
            DELETE FROM order_item
            WHERE order_id = {$session_order_id}
            AND order_item_id = {$order_item_id}
            ";
            $result = $db->sql_query($deleteSQL);
        }
        return;
    }

    /**
     *
     */
    function getCreditCardSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        if (!$this->getCreditCardValidate()){
            return $validate->getErrorMessageXML();
        }

        $credit_card_no = $fn->getPostParam('credit_card_no');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $sqlUpdate = "
        UPDATE `order` SET credit_card_no = '{$credit_card_no}'
        WHERE order_id = {$session_order_id}
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getCreditCardValidate() {
        $validate = Zend_Registry::get('validate');

        $validate->resetErrorArray();
        $validate->validateData('credit_card_no', 'Please enter the card number');

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getSaleByNameSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        if (!$this->getSaleByNameValidate()){
            return $validate->getErrorMessageXML();
        }

        $name = $fn->getPostParam('name');

        $sqlUpdate = "
        UPDATE `setting` SET value = '{$name}' WHERE key_text = 'billname'
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getSaleByNameValidate() {
        $validate = Zend_Registry::get('validate');

        $validate->resetErrorArray();
        $validate->validateData('name', 'Please enter the name');

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getModeOfPaymentUpdate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        $mode_of_payment = $fn->getReqParam('mode_of_payment');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $sqlUpdate = "
        UPDATE `order` SET mode_of_payment = '{$mode_of_payment}'
        WHERE order_id = {$session_order_id}
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);
    }

    /**
     *
     */
    function getGSTStatusUpdate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        $gst_status = $fn->getReqParam('gst_status');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $sqlUpdate = "
        UPDATE `order` SET gst_status = '{$gst_status}'
        WHERE order_id = {$session_order_id}
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);
    }

    /**
     *
     */
    function getLoyaltyUpdate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        $cust_company_name = $fn->getReqParam('cust_company_name');

        $sqlUpdate = "
        UPDATE `company` SET loyalty_point_linked = '1'
        WHERE company_name = '{$cust_company_name}'
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);
    }

    function getApplyShippingChargesSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        if (!$this->getApplyShippingChargesValidate()){
            return $validate->getErrorMessageXML();
        }

        $shipping_charge  = $fn->getPostParam('shipping_charge');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $sqlUpdate = "
        UPDATE `order` SET shipping_charge = '{$shipping_charge}'
        WHERE order_id = {$session_order_id}
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);


        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getApplyShippingChargesValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');

        $validate->resetErrorArray();
        $validate->validateData('shipping_charge', 'Please enter shipping charge amount');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
        $shipping_charge = $fn->getPostParam('shipping_charge');
        
        $OrderTotalAmount = $this->view->getTotalAmount($session_order_id);
        
        if($shipping_charge != ""){
            $OrderTotalAmount = $OrderTotalAmount;
        }

        $OrderTotalAmountFormatted = number_format($OrderTotalAmount, 2);

        if($shipping_charge != "" && $shipping_charge > 0){
            if($OrderTotalAmount == 0){
                $validate->errorArray['shipping_charge']['name'] = "shipping_charge";
                $validate->errorArray['shipping_charge']['msg']  = "Please select some items before apply shipping charge";
            }
        }

        if($OrderTotalAmount > 0){
            if($shipping_charge >= $OrderTotalAmount){
                $validate->errorArray['shipping_charge']['name'] = "shipping_charge";
                $validate->errorArray['shipping_charge']['msg']  = "Please enter shipping charge amount as lesser than order amount: {$OrderTotalAmountFormatted}";
            }
        }

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getRemoveShippingChargeOrder() {
        $fn    = Zend_Registry::get('fn');
        $db    = Zend_Registry::get('db');
        
        $order_id  = $fn->getReqParam('order_id');
        
        $SQLOrder = "
        UPDATE `order`
        SET shipping_charge = '0.00'
        WHERE order_id = {$order_id}
        ";
        $resultOrder = $db->sql_query($SQLOrder);
    }

    /**
     *
     */
    function getUpdateOrderDate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        $dateChanged = $fn->getReqParam('dateChanged');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $sqlUpdate = "
        UPDATE `order` SET order_date = '{$dateChanged}'
        WHERE order_id = {$session_order_id}
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);
    }

    /**
     *
     */
    function getUpdateRefNoOrderItem() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_item_id = $fn->getReqParam('order_item_id');
        $ref_no        = $fn->getReqParam('ref_no');

        $SQL    = "
        UPDATE order_item
        SET ref_no = {$ref_no}
        WHERE order_item_id = {$order_item_id}
        ";
        $result = $db->sql_query($SQL);
    }

    /**
     *
     */
    function getAddDefaultDiscountTypeSubmit(){
        $validate = Zend_Registry::get('validate');
        $fn       = Zend_Registry::get('fn');
        $dbUtil   = Zend_Registry::get('dbUtil');
        $db       = Zend_Registry::get('db');

        if (!$this->getAddDefaultDiscountTypeValidate()){
            return $validate->getErrorMessageXML();
        }

        $discount_type    = $fn->getPostParam('discount_type_default');

        $sqlUpdate = "
        UPDATE `setting` SET value = '{$discount_type}'
        WHERE key_text = 'cp.posDefaultDiscountType'
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getAddDefaultDiscountTypeValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');

        $validate->resetErrorArray();
        $validate->validateData('discount_type_default', 'Please select discount_type');

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getUpdateUnitPriceOrderItem() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_item_id  = $fn->getReqParam('order_item_id');
        $unit_price     = $fn->getReqParam('unit_price');

        $SQL    = "
        UPDATE order_item
        SET unit_price = '{$unit_price}'
        WHERE order_item_id = {$order_item_id}
        ";
        $result = $db->sql_query($SQL);

    }

    /**
     *
     */
    function getAddBatchProductForPos() {
        $fn     = Zend_Registry::get('fn');
        $db     = Zend_Registry::get('db');
        $tv     = Zend_Registry::get('tv');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg  = Zend_Registry::get('cpCfg');

        $product_id       = $fn->getReqParam('product_id');
        $batch_no         = $fn->getReqParam('batch_no');
        $po_product_id    = $fn->getReqParam('po_product_id');
        $site_id          = $fn->getSessionParam('cp_site_id');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $appendSql   = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql   = "AND ibs.site_id = {$site_id}";
        }

        $SQL    = "
        SELECT p.title
              ,p.unit
              ,p.item_code
              ,pp.cost_price
              ,pp.pack_size
              ,pp.selling_price
              ,pp.qty_requested AS qty
              ,pp.gst
              ,pp.expiry_date
              ,p.hsn AS hsn_code
              ,p.product_id AS product_id
              ,p.title AS main_product_title
              ,p.item_code AS main_product_code
              ,ibs.batch_no
              ,ibs.current_stock
              ,ibs.po_product_id
        FROM inventory_batchwise_stock ibs
        LEFT JOIN po_product pp ON (pp.po_product_id = ibs.po_product_id)
        LEFT JOIN product p ON (p.product_id = ibs.product_id)
        WHERE ibs.product_id = {$product_id}
        AND ibs.batch_no = '{$batch_no}'
        AND ibs.po_product_id = '{$po_product_id}'
        {$appendSql}
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);

        if($row['gst'] == ''){
            $row['gst'] = 0;
        }

        $gst = $row['gst'] * $row['selling_price'] / 100;

        if($row['expiry_date'] == "0000-00-00") {
            $row['expiry_date'] = "";
        }

        if(is_numeric($row['pack_size'])){
            $price = $row['selling_price'] / $row['pack_size'];
        } else {
            $price = $row['selling_price'];
        }

        if(is_numeric($row['pack_size'])){
            $cost_price = $row['cost_price'] / $row['pack_size'];
        } else {
            $cost_price = $row['cost_price'];
        }

        $fa = array();
        $fa['order_id']      = $session_order_id;
        $fa['record_id']     = $product_id;
        $fa['item_title']    = $row['title'];
        $fa['item_code']     = $row['item_code'];
        $fa['unit_price']    = $price;
        $fa['cost_price']    = $cost_price;
        $fa['qty']           = 1;
        $fa['gst']           = $row['gst'];
        $fa['batch_no']      = $row['batch_no'];
        $fa['po_product_id'] = $row['po_product_id'];
        $fa['expiry_date']   = $row['expiry_date'];
        $fa['pack_size']     = $row['pack_size'];

        $SQLOrderItem = "
        SELECT *
        FROM `order_item`
        WHERE order_id = '{$session_order_id}'
          AND batch_no = '{$batch_no}'
          AND po_product_id = '{$po_product_id}'
        ";
        $resultOrderItem = $db->sql_query($SQLOrderItem);
        $rec = $db->sql_fetchrow($resultOrderItem);

        if($rec['order_item_id'] != ''){
            $SQLUpdate = "UPDATE order_item SET qty = ({$rec['qty']} + 1)
                          WHERE order_id = '{$session_order_id}'
                          AND batch_no   = '{$batch_no}'
                          AND po_product_id = '{$po_product_id}'";
            $resultUpdate = $db->sql_query($SQLUpdate);

            $order_item_id = $rec['order_item_id'];
        } else {
            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'order_item');
            $db->sql_query($SQL);
            $order_item_id = $db->sql_nextid();
        }

        if($row['add_syringe_in_pos'] == 1){
            $SQLSyrng = "
            SELECT p.title
                  ,p.item_code
                  ,p.model
                  ,p.part_number
                  ,pop.selling_price AS price
                  ,p.gst
                  ,p.vat
                  ,p.discount_type
                  ,p.discount_percentage
                  ,p.discount_amount
                  ,p.tag_no
                  ,p.discount_from_date
                  ,p.discount_to_date
                  ,p.not_add_in_stock
                  ,p.add_syringe_in_pos
                  ,pop.cost_price
                  ,pop.pack_size
                  ,pop.batch_no
                  ,pop.expiry_date
                  ,pop.po_product_id
                  ,ibs.current_stock
            FROM inventory_batchwise_stock ibs
            LEFT JOIN (po_product pop) ON (pop.po_product_id = ibs.po_product_id)
            LEFT JOIN (product p) ON (p.product_id = ibs.product_id)
            WHERE ibs.product_id = '17'
            {$appendSql}
            ";
            $resultSyrng = $db->sql_query($SQLSyrng);
            $rowSyrng = $db->sql_fetchrow($resultSyrng);

            $qty = 1;

            if($rowSyrng['gst'] == ''){
                $rowSyrng['gst'] = 0;
            }

            $gst = $rowSyrng['gst'] * $rowSyrng['price'] / 100;

            if($rowSyrng['vat'] == ''){
                $rowSyrng['vat'] = 0;
            }

            $vat = $rowSyrng['vat'] * $rowSyrng['price'] / 100;

            if($rowSyrng['discount_percentage'] == ''){
                $rowSyrng['discount_percentage'] = 0;
            }

            if($rowSyrng['discount_amount'] == ''){
                $rowSyrng['discount_amount'] = 0;
            }

            if($rowSyrng['not_add_in_stock'] == '') {
                $rowSyrng['not_add_in_stock'] = 0;
            }

            // This if condition used to check the product discount date range and update the discount on order item
            if($rowOrder['order_date'] >= $rowSyrng['discount_from_date'] && $rowOrder['order_date'] <= $rowSyrng['discount_to_date']){
            }
            else{
                $rowSyrng['discount_amount']     = 0;
                $rowSyrng['discount_percentage'] = 0;
                $rowSyrng['discount_type']       = "";
            }

            $discount_value_for_one_qty = 0;
            $discountValue = 0;
            if($rowSyrng['discount_type'] == '%'){
                if($rowSyrng['discount_percentage'] > 0){
                    $discount_value_for_one_qty  =  $rowSyrng['unit_price'] * ($rowSyrng['discount_percentage']/100);
                    $discountValue = $discount_value_for_one_qty * $rowSyrng['qty'];
                }
            }
            else if($rowSyrng['discount_type']  == 'Value'){
                if($rowSyrng['discount_amount'] > 0){
                    $discount_value_for_one_qty  =  $rowSyrng['discount_amount'];
                    $discountValue = $discount_value_for_one_qty * $rowSyrng['qty'];
                }
            }

            $totalAmount = $rowSyrng['price'] - $discountValue;

            if($rowSyrng['discount_type'] == ""){
                $rowSyrng['discount_type'] = $cpCfg['cp.posDefaultDiscountType'];
            }

            if($rowSyrng['cost_price'] == ""){
                $rowSyrng['cost_price'] = 0;
            }

            if($rowSyrng['price'] == ""){
                $rowSyrng['price'] = 0;
            }

            if($rowSyrng['expiry_date'] == '0000-00-00'){
                $rowSyrng['expiry_date'] = '';
            }

            if(is_numeric($rowSyrng['pack_size'])){
                $price = $rowSyrng['price'] / $rowSyrng['pack_size'];
            } else {
                $price = $rowSyrng['price'];
            }

            if(is_numeric($rowSyrng['pack_size'])){
                $cost_price = $rowSyrng['cost_price'] / $rowSyrng['pack_size'];
            } else {
                $cost_price = $rowSyrng['cost_price'];
            }

            $faSyrng = array();
            $faSyrng['order_id']            = $session_order_id;
            $faSyrng['record_id']           = 17;
            $faSyrng['item_title']          = $rowSyrng['title'];
            $faSyrng['item_code']           = $rowSyrng['item_code'];
            $faSyrng['model']               = $rowSyrng['model'];
            $faSyrng['unit_price']          = $price;
            $faSyrng['cost_price']          = $cost_price;
            $faSyrng['discount_type']       = $rowSyrng['discount_type'];
            $faSyrng['discount_percentage'] = $rowSyrng['discount_percentage'];
            $faSyrng['discount_amount']     = $rowSyrng['discount_amount'];
            $faSyrng['qty']                 = $qty;
            $faSyrng['vat']                 = $rowSyrng['vat'];
            $faSyrng['gst']                 = $rowSyrng['gst'];
            $faSyrng['tag_no']              = $rowSyrng['tag_no'];
            $faSyrng['expiry_date']         = $rowSyrng['expiry_date'];
            $faSyrng['pack_size']           = $rowSyrng['pack_size'];
            $faSyrng['batch_no']            = $rowSyrng['batch_no'];
            $faSyrng['po_product_id']       = $rowSyrng['po_product_id'];
            $faSyrng['not_add_in_stock']    = $rowSyrng['not_add_in_stock'];
            $faSyrng['discounted_amount']   = $discountValue;
            $faSyrng['total_amount']        = $totalAmount;

            $SQLOrderItem2 = "
            SELECT *
            FROM `order_item`
            WHERE order_id = '{$session_order_id}'
              AND record_id = 17
            ";
            $resultOrderItem2 = $db->sql_query($SQLOrderItem2);
            $rec2 = $db->sql_fetchrow($resultOrderItem2);

            if($rec2['order_item_id'] != ''){
                $SQLUpdate2 = "UPDATE order_item SET qty = ({$rec2['qty']} + 1)
                               WHERE order_id = '{$session_order_id}' 
                               AND record_id = 17";
                $resultUpdate2 = $db->sql_query($SQLUpdate2);
            } else {
                $SQL2 = $dbUtil->getInsertSQLStringFromArray($faSyrng, 'order_item');
                $db->sql_query($SQL2);
                //$order_item_id = $db->sql_nextid();
            }
        }

        return $order_item_id;
    }

    /**
     *
     */
    function getAddBatchProductForPos1() {
        $fn     = Zend_Registry::get('fn');
        $db     = Zend_Registry::get('db');
        $tv     = Zend_Registry::get('tv');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg  = Zend_Registry::get('cpCfg');

        $product_id       = $fn->getReqParam('product_id');
        $batch_no         = $fn->getReqParam('batch_no');
        $site_id          = $fn->getSessionParam('cp_site_id');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $appendSql   = '';
        $sqlAppendSt = '';
        $stockTransferSQLForMultiSite = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql   = "AND po.site_id = {$site_id}";
            $sqlAppendSt = "AND st.to_location = {$site_id}";

            $stockTransferSQLForMultiSite = "
            UNION
            SELECT  p.title
                   ,p.unit
                   ,p.item_code
                   ,st.stock_transfer_id AS po_code
                   ,st.stock_transfer_id AS purchase_order_id
                   ,pp.cost_price
                   ,pp.pack_size
                   ,pp.selling_price
                   ,pp.qty_requested AS qty
                   ,pp.gst
                   ,pp.batch_no AS batch_no
                   ,pp.expiry_date
                   ,p.hsn AS hsn_code
                   ,p.product_id AS product_id
                   ,p.title AS main_product_title
                   ,p.item_code AS main_product_code
                   ,p.add_syringe_in_pos
                   ,'STOCK TRANSFER' AS stock_from
            FROM stock_transfer_history sth
            LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
            LEFT JOIN po_product pp ON (pp.po_product_id = sth.po_product_id)
            LEFT JOIN product p ON (p.product_id = pp.product_id)
            WHERE sth.product_id = {$product_id}
              {$sqlAppendSt}
            AND st.status = 'Delivered'
            AND pp.batch_no = '{$batch_no}'
            GROUP BY batch_no
            ";
        }

        $SQL    = "
        SELECT p.title
              ,p.unit
              ,p.item_code
              ,po.po_code AS po_code
              ,po.purchase_order_id AS purchase_order_id
              ,pp.cost_price
              ,pp.pack_size
              ,pp.selling_price
              ,pp.qty_requested AS qty
              ,pp.gst
              ,pp.batch_no AS batch_no
              ,pp.expiry_date
              ,p.hsn AS hsn_code
              ,p.product_id AS product_id
              ,p.title AS main_product_title
              ,p.item_code AS main_product_code
              ,p.add_syringe_in_pos
              ,'PURCHASE ORDER' AS stock_from
        FROM product p
        LEFT JOIN po_product pp ON (pp.product_id = p.product_id)
        LEFT JOIN (purchase_order po) ON (po.purchase_order_id = pp.purchase_order_id)
        WHERE pp.product_id = {$product_id}
        AND po.status != 'Cancelled'
        AND pp.batch_no = '{$batch_no}'
        {$appendSql}
        GROUP BY pp.batch_no
        {$stockTransferSQLForMultiSite}
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);

        if($row['gst'] == ''){
            $row['gst'] = 0;
        }

        $gst = $row['gst'] * $row['selling_price'] / 100;

        if($row['expiry_date'] == "0000-00-00") {
            $row['expiry_date'] = "";
        }

        if(is_numeric($row['pack_size'])){
            $price = $row['selling_price'] / $row['pack_size'];
        } else {
            $price = $row['selling_price'];
        }

        if(is_numeric($row['pack_size'])){
            $cost_price = $row['cost_price'] / $row['pack_size'];
        } else {
            $cost_price = $row['cost_price'];
        }

        $fa = array();
        $fa['order_id']    = $session_order_id;
        $fa['record_id']   = $product_id;
        $fa['item_title']  = $row['title'];
        $fa['item_code']   = $row['item_code'];
        $fa['unit_price']  = $price;
        $fa['cost_price']  = $cost_price;
        $fa['qty']         = 1;
        $fa['gst']         = $row['gst'];
        $fa['batch_no']    = $row['batch_no'];
        $fa['expiry_date'] = $row['expiry_date'];
        $fa['pack_size']   = $row['pack_size'];

        $SQLOrderItem = "
        SELECT *
        FROM `order_item`
        WHERE order_id = '{$session_order_id}'
          AND batch_no = '{$batch_no}'
        ";
        $resultOrderItem = $db->sql_query($SQLOrderItem);
        $rec = $db->sql_fetchrow($resultOrderItem);

        if($rec['order_item_id'] != ''){
            $SQLUpdate = "UPDATE order_item SET qty = ({$rec['qty']} + 1)
                          WHERE order_id = '{$session_order_id}' AND batch_no = '{$batch_no}'";
            $resultUpdate = $db->sql_query($SQLUpdate);

            $order_item_id = $rec['order_item_id'];
        } else {
            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'order_item');
            $db->sql_query($SQL);
            $order_item_id = $db->sql_nextid();
        }

        if($row['add_syringe_in_pos'] == 1){
            $SQLSyrng = "
            SELECT p.title
                  ,p.item_code
                  ,p.model
                  ,p.part_number
                  ,pop.selling_price AS price
                  ,p.gst
                  ,p.vat
                  ,p.discount_type
                  ,p.discount_percentage
                  ,p.discount_amount
                  ,p.tag_no
                  ,p.discount_from_date
                  ,p.discount_to_date
                  ,p.not_add_in_stock
                  ,p.add_syringe_in_pos
                  ,pop.cost_price
                  ,pop.pack_size
                  ,pop.batch_no
                  ,pop.expiry_date
            FROM product p
            LEFT JOIN (po_product pop) ON (pop.product_id = p.product_id)
            LEFT JOIN (purchase_order po) ON (po.purchase_order_id = pop.purchase_order_id)
            WHERE p.product_id = '17'
            AND po.status != 'Cancelled'
            {$appendSql}
            ";
            $resultSyrng = $db->sql_query($SQLSyrng);
            $rowSyrng = $db->sql_fetchrow($resultSyrng);

            $qty = 1;

            if($rowSyrng['gst'] == ''){
                $rowSyrng['gst'] = 0;
            }

            $gst = $rowSyrng['gst'] * $rowSyrng['price'] / 100;

            if($rowSyrng['vat'] == ''){
                $rowSyrng['vat'] = 0;
            }

            $vat = $rowSyrng['vat'] * $rowSyrng['price'] / 100;

            if($rowSyrng['discount_percentage'] == ''){
                $rowSyrng['discount_percentage'] = 0;
            }

            if($rowSyrng['discount_amount'] == ''){
                $rowSyrng['discount_amount'] = 0;
            }

            if($rowSyrng['not_add_in_stock'] == '') {
                $rowSyrng['not_add_in_stock'] = 0;
            }

            // This if condition used to check the product discount date range and update the discount on order item
            if($rowOrder['order_date'] >= $rowSyrng['discount_from_date'] && $rowOrder['order_date'] <= $rowSyrng['discount_to_date']){
            }
            else{
                $rowSyrng['discount_amount']     = 0;
                $rowSyrng['discount_percentage'] = 0;
                $rowSyrng['discount_type']       = "";
            }

            $discount_value_for_one_qty = 0;
            $discountValue = 0;
            if($rowSyrng['discount_type'] == '%'){
                if($rowSyrng['discount_percentage'] > 0){
                    $discount_value_for_one_qty  =  $rowSyrng['unit_price'] * ($rowSyrng['discount_percentage']/100);
                    $discountValue = $discount_value_for_one_qty * $rowSyrng['qty'];
                }
            }
            else if($rowSyrng['discount_type']  == 'Value'){
                if($rowSyrng['discount_amount'] > 0){
                    $discount_value_for_one_qty  =  $rowSyrng['discount_amount'];
                    $discountValue = $discount_value_for_one_qty * $rowSyrng['qty'];
                }
            }

            $totalAmount = $rowSyrng['price'] - $discountValue;

            if($rowSyrng['discount_type'] == ""){
                $rowSyrng['discount_type'] = $cpCfg['cp.posDefaultDiscountType'];
            }

            if($rowSyrng['cost_price'] == ""){
                $rowSyrng['cost_price'] = 0;
            }

            if($rowSyrng['price'] == ""){
                $rowSyrng['price'] = 0;
            }

            if($rowSyrng['expiry_date'] == '0000-00-00'){
                $rowSyrng['expiry_date'] = '';
            }

            if(is_numeric($rowSyrng['pack_size'])){
                $price = $rowSyrng['price'] / $rowSyrng['pack_size'];
            } else {
                $price = $rowSyrng['price'];
            }

            if(is_numeric($rowSyrng['pack_size'])){
                $cost_price = $rowSyrng['cost_price'] / $rowSyrng['pack_size'];
            } else {
                $cost_price = $rowSyrng['cost_price'];
            }

            $faSyrng = array();
            $faSyrng['order_id']            = $session_order_id;
            $faSyrng['record_id']           = 17;
            $faSyrng['item_title']          = $rowSyrng['title'];
            $faSyrng['item_code']           = $rowSyrng['item_code'];
            $faSyrng['model']               = $rowSyrng['model'];
            $faSyrng['unit_price']          = $price;
            $faSyrng['cost_price']          = $cost_price;
            $faSyrng['discount_type']       = $rowSyrng['discount_type'];
            $faSyrng['discount_percentage'] = $rowSyrng['discount_percentage'];
            $faSyrng['discount_amount']     = $rowSyrng['discount_amount'];
            $faSyrng['qty']                 = $qty;
            $faSyrng['vat']                 = $rowSyrng['vat'];
            $faSyrng['gst']                 = $rowSyrng['gst'];
            $faSyrng['tag_no']              = $rowSyrng['tag_no'];
            $faSyrng['expiry_date']         = $rowSyrng['expiry_date'];
            $faSyrng['pack_size']           = $rowSyrng['pack_size'];
            $faSyrng['batch_no']            = $rowSyrng['batch_no'];
            $faSyrng['not_add_in_stock']    = $rowSyrng['not_add_in_stock'];
            $faSyrng['discounted_amount']   = $discountValue;
            $faSyrng['total_amount']        = $totalAmount;

            $SQLOrderItem2 = "
            SELECT *
            FROM `order_item`
            WHERE order_id = '{$session_order_id}'
              AND record_id = 17
            ";
            $resultOrderItem2 = $db->sql_query($SQLOrderItem2);
            $rec2 = $db->sql_fetchrow($resultOrderItem2);

            if($rec2['order_item_id'] != ''){
                $SQLUpdate2 = "UPDATE order_item SET qty = ({$rec2['qty']} + 1)
                              WHERE order_id = '{$session_order_id}' AND record_id = 17";
                $resultUpdate2 = $db->sql_query($SQLUpdate2);
            } else {
                $SQL2 = $dbUtil->getInsertSQLStringFromArray($faSyrng, 'order_item');
                $db->sql_query($SQL2);
                //$order_item_id = $db->sql_nextid();
            }
        }

        return $order_item_id;
    }

    /**
     *
     */
    function getSearchVisitDetails() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        $title = $fn->getReqParam('term', '', true);
        $extractor = explode(" **** ", $title);
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $visitDetail = $extractor[0];
        $appendSql = '';

        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "AND p.site_id = {$cpSiteIdSession}";
        }

        $SQL = "
        SELECT p.visit_code AS value
              ,CONCAT_WS(' / ', p.visit_code, pi.name) AS label             
              ,p.patient_visit_id AS id
              ,pi.name
        FROM patient_visit p
        LEFT JOIN (patient_information pi) ON (pi.patient_information_id = p.patient_information_id)

        WHERE (p.visit_code LIKE '%{$visitDetail}%')
        {$appendSql}
        ORDER BY p.visit_code
        ";
        $result = $db->sql_query($SQL);

        $dataArray = $dbUtil->getResultsetAsArray($result);
        $arr = json_encode($dataArray);
        return $arr;
    }

    /**
     *
     */
    function getDeleteAllOrderItem(){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $formObj = Zend_Registry::get('formObj');
        $cpCfg = Zend_Registry::get('cpCfg');

        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : false;

        if($session_order_id){
            $deleteSQL    = "
            DELETE FROM order_item
            WHERE order_id = {$session_order_id}
            ";
            $result = $db->sql_query($deleteSQL);
        }

        return;
    }

    /**
     *
     */
    function getBatchProductCountCheck(){
        $cpCfg   = Zend_Registry::get('cpCfg');
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil  = Zend_Registry::get('dbUtil');

        $product_id = $fn->getReqParam('product_id');
        $site_id    = $fn->getSessionParam('cp_site_id');

        $sqlAppendSt = "";
        $stockTransferSQLForMultiSite = "";
        if($cpCfg['cp.hasMultiUniqueSites']  == true){
            $sqlAppendSt = "AND ibs.site_id = {$site_id}";
        }

        $SQLBatchNo = "
        SELECT  ibs.batch_no AS batch_no
               ,ibs.product_id
               ,ibs.current_stock
        FROM inventory_batchwise_stock ibs
        LEFT JOIN (purchase_order po) ON (po.purchase_order_id = ibs.purchase_order_id)
        WHERE ibs.product_id = {$product_id}
        AND po.status != 'Cancelled'
        AND ibs.current_stock > 0
        {$sqlAppendSt}
        ";
        $resultBatchNo  = $db->sql_query($SQLBatchNo);
        $numRowsBatchNo = $db->sql_numrows($resultBatchNo);

        print $numRowsBatchNo;
    }

    /**
     *
     */
    function getBatchProductCountCheck1(){
        $cpCfg   = Zend_Registry::get('cpCfg');
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $dbUtil  = Zend_Registry::get('dbUtil');

        $product_id = $fn->getReqParam('product_id');
        $site_id    = $fn->getSessionParam('cp_site_id');

        $thForSiteId   = "";
        $tdForSiteId   = "";
        $thForSiteId   = "";
        $rows          = "";
        $sqlAppend     = "";
        $sqlAppendSt   = "";
        $appendSqlStk2 = "";
        $stockTransferSQLForMultiSite = "";
        if($cpCfg['cp.hasMultiUniqueSites']  == true){
            $sqlAppend = "AND po.site_id = {$site_id}";
            $sqlAppendSt = "AND st.to_location = {$site_id}";

            $stockTransferSQLForMultiSite = "
            UNION
            SELECT  sth.batch_no AS batch_no
                   ,sth.product_id
                   ,pp.expiry_date
            FROM stock_transfer_history sth
            LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
            LEFT JOIN po_product pp ON (pp.po_product_id = sth.po_product_id)
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
        FROM po_product pp
        LEFT JOIN purchase_order po ON (po.purchase_order_id = pp.purchase_order_id)
        LEFT JOIN product p ON (p.product_id = pp.product_id)
        WHERE pp.product_id = {$product_id}
          {$sqlAppend}
        AND po.status != 'Cancelled'
        GROUP BY pp.batch_no
        {$stockTransferSQLForMultiSite}
        ";
        $resultBatchNo  = $db->sql_query($SQLBatchNo);
        $numRowsBatchNo = $db->sql_numrows($resultBatchNo);

        $count = 0;
        while ($rowBatchNo    = $db->sql_fetchrow($resultBatchNo)) {
            $appendSqlOrd = "AND o.site_id = {$site_id}";
            $appendSqlPur = "AND po.site_id = {$site_id}";
            $appendSqlInv = "AND inv.site_id = {$site_id}";
            $appendSqlStk = "AND st.from_location = '{$site_id}'";
            $appendSqlStk2 = "AND st.to_location = '{$site_id}'";

            $SQLOthersite = "
            SELECT
                (SELECT SUM(qty) FROM po_product pp
                 LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                 WHERE pp.product_id = {$rowBatchNo['product_id']}
                 AND po.status != 'Cancelled'
                 {$appendSqlPur}
                 AND pp.batch_no = '{$rowBatchNo['batch_no']}') as product_qty_purchased

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

                ,(SELECT SUM(CASE 
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

                ,(SELECT SUM(CASE 
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

            if($stock > 0) {
                $count++;
            }
        }

        print $count;
    }

    /**
     *
     */
    function getUpdateNotAddInStock() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $order_item_id = $fn->getReqParam('order_item_id');
        $checkedVal    = $fn->getReqParam('checkedVal');

        if($checkedVal == 1) {
            $notAddInStock = 1;
        } else {
            $notAddInStock = 0;
        }

        $sqlUpdate = "
        UPDATE order_item SET not_add_in_stock = {$notAddInStock}
        WHERE order_item_id = {$order_item_id}
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);
    }

    /**
     *
     */
    function getUpdatePatientNameOnOrder() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $patient_name = $fn->getReqParam('patient_name');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $sqlUpdate = "
        UPDATE `order` SET cust_first_name = '{$patient_name}'
        WHERE order_id = {$session_order_id}
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);
    }

    /**
     *
     */
    function getUpdateInpatCodeOnOrder() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $code = $fn->getReqParam('code');
        $in_patient_id = $fn->getReqParam('in_patient_id');

        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
        $ipRec = $fn->getRecordByCondition('in_patient', "code = '{$code}'");

        $pvRec = $fn->getRecordRowByID('patient_visit', 'patient_visit_id', $ipRec['patient_visit_id']);
        $piRec = $fn->getRecordRowByID('patient_information', 'patient_information_id', $ipRec['patient_information_id']);

        if($code == ''){
            $ipRec['in_patient_id'] = '';
        }

        if($ipRec['patient_visit_id'] != ''){
            $sqlUpdate1 = "
        UPDATE `patient_visit` SET pur_medicine = 'Yes'
        WHERE patient_visit_id = '{$pvRec['patient_visit_id']}'
        ";
        $resultUpdate1 = $db->sql_query($sqlUpdate1);
        }

        if($code != ''){
            $sqlUpdate = "
            UPDATE `order` SET in_patient_id = '{$ipRec['in_patient_id']}',  cust_first_name = '{$piRec['name']}'
            WHERE order_id = {$session_order_id}
            ";
            $resultUpdate = $db->sql_query($sqlUpdate);
        }
        return $piRec['name'];

    }

    /**
     *
     */
    function getUpdateVisitCodeOnOrder() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $patient_visit_id = $fn->getReqParam('patient_visit_id');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';
        $pvRec = $fn->getRecordRowByID('patient_visit', 'patient_visit_id', $patient_visit_id);
        $piRec = $fn->getRecordRowByID('patient_information', 'patient_information_id', $pvRec['patient_information_id']);

        $sqlUpdate = "
        UPDATE `order` SET patient_visit_id = '{$patient_visit_id}', visit_code = '{$pvRec['visit_code']}', cust_first_name = '{$piRec['name']}'
        WHERE order_id = {$session_order_id}
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);

        $sqlUpdate1 = "
        UPDATE `patient_visit` SET pur_medicine = 'Yes'
        WHERE patient_visit_id = '{$pvRec['patient_visit_id']}'
        ";
        $resultUpdate1 = $db->sql_query($sqlUpdate1);

        return $piRec['name'];
    }

     /**
     *
     */
    function getUpdateCounterSaleOnOrder() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $counter_sale = $fn->getReqParam('counter_sale');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $sqlUpdate = "
        UPDATE `order` SET counter = '{$counter_sale}'
        WHERE order_id = {$session_order_id}
        ";
        $resultUpdate = $db->sql_query($sqlUpdate);
    }

    /**
     *
     */
    function getCheckBatchProductForPosExists() {
        $fn     = Zend_Registry::get('fn');
        $db     = Zend_Registry::get('db');
        $tv     = Zend_Registry::get('tv');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg  = Zend_Registry::get('cpCfg');

        $product_id       = $fn->getReqParam('product_id');
        $batch_no         = $fn->getReqParam('batch_no');
        $po_product_id    = $fn->getReqParam('po_product_id');
        $session_order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id']  : '';

        $SQLOrderItem = "
        SELECT *
        FROM `order_item`
        WHERE order_id = '{$session_order_id}'
          AND batch_no = '{$batch_no}'
          AND po_product_id = '{$po_product_id}'
        ";
        $resultOrderItem = $db->sql_query($SQLOrderItem);
        $rec = $db->sql_fetchrow($resultOrderItem);

        if($rec['order_item_id'] != '') {
            return 1;   
        }
    }
}
