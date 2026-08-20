<?
class CPL_Admin_Modules_Tradingsg_PurchaseOrder_Model extends CP_Common_Lib_ModuleModelAbstract
{

    /**
     *
     */
    function getSQL() {
        $cpCfg = Zend_Registry::get('cpCfg');
        $appendSQL = '';

        $SQL = "
        SELECT po.*
              ,su.company_name
              ,su.company_name AS supplier_name_substr
              ,CONCAT_WS(' ', c.first_name, c.last_name) AS contact_name_supplier
              ,CONCAT_WS(' ', s.first_name, s.last_name) AS staff_name
        FROM purchase_order po
        LEFT JOIN contact c ON po.contact_id_supplier = c.contact_id
        LEFT JOIN staff s ON po.staff_id = s.staff_id
        LEFT JOIN supplier su ON po.company_id_supplier = su.supplier_id
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar($linkRecType = '') {
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');
        $searchVar = Zend_Registry::get('searchVar');
        $fn = Zend_Registry::get('fn');
        $searchVar->mainTableAlias = 'po';

        $status            = $fn->getReqParam('status');
        $supplier_id       = $fn->getReqParam('supplier_id');
        $purchase_order_id = $fn->getReqParam('purchase_order_id');
        $cpSiteIdSession   = $fn->getSessionParam('cp_site_id');
        $current_month     = $fn->getReqParam('current_month');
        $yearVal           = $fn->getReqParam('year');

        if ($purchase_order_id != "") {
            $searchVar->sqlSearchVar[] = "po.purchase_order_id = '{$purchase_order_id}'";
        } else if ($tv['record_id'] != '') {
            $searchVar->sqlSearchVar[] = "po.purchase_order_id = '{$tv['record_id']}'";
        } else {

            if ($status != "") {
                $searchVar->sqlSearchVar[] = "po.status = '{$status}'";
            } else{
                $searchVar->sqlSearchVar[] = "po.status != 'Cancelled'";
            }
            if ($supplier_id != "") {
                $searchVar->sqlSearchVar[] = "po.company_id_supplier = '{$supplier_id}'";
            }

            if ($tv['special_search'] == "Flagged") {
                $searchVar->sqlSearchVar[] = "po.flag = 1";
            }
            if ($tv['special_search'] == "Not-Flagged") {
                $searchVar->sqlSearchVar[] = "(po.flag != 1 OR po.flag IS null)";
            }

            if ($tv['special_search'] == "Pack Size") {
                $searchVar->sqlSearchVar[] = "po.price_varied = 1";
            }


            if ($tv['special_search'] == "Retuned Medicines") {
                //$searchVar->sqlHavingVar[] = "stock_actual != manual_stock";
                $searchVar->sqlSearchVar[] = "po.purchase_order_id IN (
                    SELECT pop.purchase_order_id 
                    FROM  po_product pop 
                    WHERE pop.purchase_order_id = po.purchase_order_id 
                      AND po.site_id    = {$cpSiteIdSession}
                      AND (return_qty_ns > 0 || return_qty > 0)
                      AND (return_qty_ns_paid is NULL ||  return_qty_ns_paid = 0) 
                  )
                ";
            }

            $appendSql = '';
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSql = "AND po.site_id = {$cpSiteIdSession}";
            }

            /*if ($current_month != "") {
                $search_form = date('Y-'.$current_month.'-01');
                $search_to   = date("Y-m-t", strtotime($search_form));
                $searchVar->sqlSearchVar[] = "(po.purchase_order_date BETWEEN '{$search_form}' AND '{$search_to}')";
            }else {
                $search_form = date('Y-m-01');
                $search_to   = date('Y-m-t');
                $searchVar->sqlSearchVar[] = "(po.purchase_order_date BETWEEN '{$search_form}' AND '{$search_to}')";
            }*/

            if ($tv['keyword'] == "" && $tv['special_search'] != "Retuned Medicines") {
                if ($current_month != '') {
                    $searchVar->sqlSearchVar[] = "DATE_FORMAT(po.purchase_order_date, '%m') = '{$current_month}'" ;
                }

                if ($yearVal != '') {
                    $searchVar->sqlSearchVar[] = "DATE_FORMAT(po.purchase_order_date, '%Y') = '{$yearVal}'" ;
                }
            }

            if ($tv['keyword'] != "") {
                $searchVar->sqlSearchVar[] = "(
                    po.po_code  LIKE '%{$tv['keyword']}%'
                    OR po.title LIKE '%{$tv['keyword']}%'
                    OR s.first_name LIKE '%{$tv['keyword']}%'
                    OR s.last_name LIKE '%{$tv['keyword']}%'
                    OR po.status LIKE '%{$tv['keyword']}%'
                    OR po.supplier_inv_code LIKE '%{$tv['keyword']}%'
                    OR po.purchase_order_id IN (
                        SELECT po.purchase_order_id
                        FROM product p
                        LEFT JOIN po_product pop ON (pop.product_id = p.product_id)
                        LEFT JOIN purchase_order po ON (po.purchase_order_id = pop.purchase_order_id)
                        WHERE (p.title LIKE '%{$tv['keyword']}%' OR pop.batch_no LIKE '%{$tv['keyword']}%')
                        AND po.purchase_order_id != ''
                        {$appendSql}
                    )
                )";
            }
        }

        $searchVar->sortOrder = "po.invoice_date DESC";

    }

    /**
     *
     */
    function getNewValidate() {
        $ln = Zend_Registry::get('ln');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $validate = Zend_Registry::get('validate');

        $validate->resetErrorArray();

        $validate->validateData('company_id_supplier', 'Please choose the Supplier');

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getAdd(){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $validate = Zend_Registry::get('validate');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        if (!$this->getNewValidate()){
            return $validate->getErrorMessageXML();
        }

        $po_code = $this->getUpdatePOCode();

        $supplier_id = $fn->getPostParam('company_id_supplier');

        $SQLsupplier = "
        SELECT company_name
        FROM `supplier`
        WHERE supplier_id = '{$supplier_id}'
        ";
        $resultsupplier = $db->sql_query($SQLsupplier);
        $rowsupplier    = $db->sql_fetchrow($resultsupplier);

        $fa = $this->getFields();
        $fa['purchase_order_date'] = date('Y-m-d');
        $fa['status']  = 'In progress';
        $fa['payment_status']  = 'Due';
        $fa['title']   = 'Purchase From '.$rowsupplier['company_name'];
        $fa['po_code'] = $po_code;
        $id = $fn->addRecord($fa);

        $appendSql = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "AND site_id = {$cpSiteIdSession}";
        }

        $fn->returnAfterNewSave($id);
    }

    /**
     *
     */
    function getEditValidate() {
        $ln = Zend_Registry::get('ln');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $validate = Zend_Registry::get('validate');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');

        $validate->resetErrorArray();

        //$validate->validateData('buy_currency', 'Please choose buy currency');
        $supplier_inv_code = $fn->getPostParam('supplier_inv_code', '', true);
        $purchase_order_id = $fn->getPostParam('purchase_order_id', '', true);

        if ($supplier_inv_code != ''){
            $rec = $fn->getRecordByCondition('purchase_order', "supplier_inv_code = '{$supplier_inv_code}' AND 
                purchase_order_id != {$purchase_order_id} AND status != 'Cancelled'");

            if (is_array($rec)){
                $validate->errorArray['supplier_inv_code']['name'] = "supplier_inv_code";
                $validate->errorArray['supplier_inv_code']['msg']  = "Invoice No Already Exists";
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
    function getSave(){
        $fn       = Zend_Registry::get('fn');
        $cpCfg    = Zend_Registry::get('cpCfg');
        $db       = Zend_Registry::get('db');
        $dbUtil   = Zend_Registry::get('dbUtil');
        $validate = Zend_Registry::get('validate');
        
        $purchase_order_id = $fn->getPostParam('purchase_order_id');

        if (!$this->getEditValidate()){
            return $validate->getErrorMessageXML();
        }

        $fa = $this->getFields();

        $SQLPo = "
        SELECT status
        FROM purchase_order
        WHERE purchase_order_id = {$purchase_order_id}
        ";
        $resultPo  = $db->sql_query($SQLPo);
        $numRowsPo = $db->sql_numrows($resultPo);
        $rowPo     = $db->sql_fetchrow($resultPo);

        if($rowPo['status'] != "Cancelled" && $fa['status'] == "Cancelled") {
            $SQLPOProduct = "
            SELECT po_product_id
                  ,purchase_order_id
                  ,product_id
                  ,(CASE 
                    WHEN pack_size REGEXP '^[+-]?[0-9]+$'
                    THEN qty * pack_size
                    ELSE qty END) AS qty
            FROM po_product
            WHERE purchase_order_id = {$purchase_order_id} 
            ";
            $resultPOProduct = $db->sql_query($SQLPOProduct);
            while ($rowPOProduct = $db->sql_fetchrow($resultPOProduct)) {
                $SQLBatchWiseStock = "
                SELECT po_product_id
                      ,cancelled_qty
                      ,purchased_qty
                      ,current_stock
                      ,site_id
                FROM inventory_batchwise_stock
                WHERE po_product_id = '{$rowPOProduct['po_product_id']}'
                AND product_id = '{$rowPOProduct['product_id']}'
                ";
                $resultBatchWiseStock  = $db->sql_query($SQLBatchWiseStock);
                $BatchWiseStock        = $db->sql_fetchrow($resultBatchWiseStock);
                $numRowsBatchWiseStock = $db->sql_numrows($resultBatchWiseStock);
                if($numRowsBatchWiseStock > 0) {
                    $faStock = array();
                    $faStock['purchased_qty'] = $BatchWiseStock['purchased_qty'] - $rowPOProduct['qty'];
                    $faStock['cancelled_qty'] = $BatchWiseStock['cancelled_qty'] + $rowPOProduct['qty'];
                    $faStock['current_stock'] = $BatchWiseStock['current_stock'] - $rowPOProduct['qty'];
                    $faStock['status']        = "Cancelled";
                    $faStock        = $fn->addModificationDetailsToFieldsArray($faStock, 'inventory_batchwise_stock');
                    $whereCondition = "WHERE po_product_id = '{$rowPOProduct['po_product_id']}'
                                         AND product_id    = '{$rowPOProduct['product_id']}'";
                    $SQLStock       = $dbUtil->getUpdateSQLStringFromArray($faStock, "inventory_batchwise_stock", $whereCondition);
                    $resultStock    = $db->sql_query($SQLStock);

                    $siteIdForField = "";
                    if ($cpCfg['cp.hasMultiUniqueSites']) {
                        $siteIdForField = $BatchWiseStock['site_id'];
                    }

                    $SQLUpdateProduct = "
                    UPDATE product SET qty_in_stock{$siteIdForField} = IFNULL(qty_in_stock{$siteIdForField}, 0)-{$rowPOProduct['qty']}
                    WHERE product_id = '{$rowPOProduct['product_id']}'
                    ";
                    $resultUpdateProduct  = $db->sql_query($SQLUpdateProduct);
                    
                    $SQLUpdateInventory = "
                    UPDATE inventory SET actual_stock{$siteIdForField} = IFNULL(actual_stock{$siteIdForField}, 0)-{$rowPOProduct['qty']}
                    WHERE product_id = '{$rowPOProduct['product_id']}'
                    ";
                    $resultUpdateInventory  = $db->sql_query($SQLUpdateInventory);
                }
            }
        }

        $id = $fn->saveRecord($fa);

        $SQLPoCheck = "
        SELECT price_varied
        FROM purchase_order
        WHERE purchase_order_id = {$purchase_order_id}
        ";
        $resultPoCheck  = $db->sql_query($SQLPoCheck);
        $numRowsPoCheck = $db->sql_numrows($resultPoCheck);
        $rowPoCheck     = $db->sql_fetchrow($resultPoCheck);
        if($rowPoCheck['price_varied'] != 1) {
            $SQLUpdatePoProd = "
            UPDATE po_product SET price_varied = NULL
            WHERE purchase_order_id = '{$purchase_order_id}'
            ";
            $resultUpdatePoProd  = $db->sql_query($SQLUpdatePoProd);
        }

        $fn->returnAfterNewSave($id);
    }

    /**
     *
     */
    function getSaveList(){
        $fn = Zend_Registry::get('fn');
        $fn->getSaveList();
    }

    /**
     *
     */
    function getFields() {
        $fn = Zend_Registry::get('fn');
        $fn = Zend_Registry::get('fn');

        $fa = array();
        $fa = $fn->addToFieldsArray($fa, 'po_code');
        $fa = $fn->addToFieldsArray($fa, 'title');
        $fa = $fn->addToFieldsArray($fa, 'company_id_supplier');
        $fa = $fn->addToFieldsArray($fa, 'contact_id_supplier');
        $fa = $fn->addToFieldsArray($fa, 'payment_terms');
        $fa = $fn->addToFieldsArray($fa, 'status');
        $fa = $fn->addToFieldsArray($fa, 'notes');
        $fa = $fn->addToFieldsArray($fa, 'purchase_order_date');
        $fa = $fn->addToFieldsArray($fa, 'buy_currency');
        $fa = $fn->addToFieldsArray($fa, 'staff_id');
        $fa = $fn->addToFieldsArray($fa, 'priority');
        $fa = $fn->addToFieldsArray($fa, 'delivery_terms');
        $fa = $fn->addToFieldsArray($fa, 'follow_up_date');
        $fa = $fn->addToFieldsArray($fa, 'payment_status');
        $fa = $fn->addToFieldsArray($fa, 'invoice_date'); 
        $fa = $fn->addToFieldsArray($fa, 'supplier_inv_code');
        $fa = $fn->addToFieldsArray($fa, 'price_varied');

        return $fa;
    }

    /**
     *
     */
    function getAddMultipleLineItemValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $purchase_order_id  = $fn->getPostParam('purchase_order_id');
        $supplier_id        = $fn->getPostParam('supplier_id');
        $product_arr        = $fn->getReqParam('product_id', array());
        $price_arr          = $fn->getPostParam('price', array());
        $qty_arr            = $fn->getPostParam('qty', array());

        $validate->resetErrorArray();

        $filterArray3 = array_filter($qty_arr);
        if (count($filterArray3) == 0){
            $validate->errorArray['error_box']['name'] = "error_box1";
            $validate->errorArray['error_box']['msg']  = "Please Enter Qty";
        }

        /*$filterArray2 = array_filter($price_arr);
        if (count($filterArray2) == 0){
            $validate->errorArray['error_box']['name'] = "error_box1";
            $validate->errorArray['error_box']['msg']  = "Please Enter Price";
        }
        */


        $filterArray = array_filter($product_arr);
        if (count($filterArray) == 0){
            $validate->errorArray['error_box']['name'] = "error_box1";
            $validate->errorArray['error_box']['msg']  = "Please select product";
        }

        $SQLproduct = "
        SELECT product_id
        FROM po_product
        WHERE purchase_order_id = {$purchase_order_id}
        ";
        $resultproduct = $db->sql_query($SQLproduct);
        $product_id_db_arr = array();
        while($rowproduct    = $db->sql_fetchrow($resultproduct)){
            if($rowproduct['product_id'] != ''){
                $product_id_db_arr[] = $rowproduct['product_id'];
            }
        }

        $product_id_arr = array();
        foreach($product_arr as $key => $value){
            if($value != ''){
                if(in_array($value, $product_id_arr)){
                    $validate->errorArray['error_box']['name'] = "error_box1";
                    $validate->errorArray['error_box']['msg']  = "Please select different product";
                }

                if(!in_array($value, $product_id_arr)){
                    $product_id_arr[] = $value;
                }

                if(in_array($value, $product_id_db_arr)){
                    $validate->errorArray['error_box']['name'] = "error_box1";
                    $validate->errorArray['error_box']['msg']  = "Selected product already exists.";
                }
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
    function getAddMultipleLineItemSubmit() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $validate = Zend_Registry::get('validate');

        if (!$this->getAddMultipleLineItemValidate()){
            return $validate->getErrorMessageXML();
        }

        $purchase_order_id  = $fn->getPostParam('purchase_order_id');
        $supplier_id        = $fn->getPostParam('supplier_id');
        $product_arr        = $fn->getReqParam('product_id', array());
        $qty_arr            = $fn->getPostParam('qty', array());

        $count = count($product_arr);
        for ($i= 0; $i < $count; $i++) {
            $product_id         = $product_arr[$i];
            $qty                = $qty_arr[$i];

            if ($product_id) {
                $SQLPO ="
                SELECT pp.cost_price
                      ,pp.selling_price
                      ,pp.gst
                      ,pp.tag_no
                FROM po_product pp
                WHERE pp.product_id = {$product_id}
                ORDER BY pp.po_product_id desc
                ";
                $resultPo = $db->sql_query($SQLPO);
                $rowPo    = $db->sql_fetchrow($resultPo);

                if($rowPo['cost_price'] == ""){
                    $rowPo['cost_price'] = "0.00";
                }

                if($rowPo['selling_price'] == ""){
                    $rowPo['selling_price'] = "0.00";
                }

                if($rowPo['gst'] == ""){
                    $rowPo['gst'] = "0.00";
                }


                $fa = array();

                $fa['product_id']           = $product_id;
                $fa['selling_price']        = $rowPo['selling_price'];
                $fa['cost_price']           = $rowPo['cost_price'];
                $fa['qty_requested']        = $qty;
                $fa['gst']                  = $rowPo['gst'];
                $fa['tag_no']               = $rowPo['tag_no'];
                $fa['purchase_order_id']    = $purchase_order_id;
                $fa['supplier_id']          = $supplier_id;
                $fa['status']               = 'In progress';
                $fa['creation_date']        = date("Y-m-d H:i:s");
                $fa['created_by']           = $fn->getSessionParam('userName');

                $insert = $dbUtil->getInsertSQLStringFromArray($fa, 'po_product');
                $result = $db->sql_query($insert);
                $po_product_id = $db->sql_nextid();

                $fa1 = array();
                $fa1['price']   = $rowPo['selling_price'];
                //$fa1['gst']   = $gst;

                $whereCondition = "WHERE product_id = {$product_id}";
                $SQL1    = $dbUtil->getUpdateSQLStringFromArray($fa1, "product", $whereCondition);
                $result1 = $db->sql_query($SQL1);

                /*$fa2 = array();
                $fa2['product_id']       = $product_id;
                $fa2['price']            = $price;
                $fa2['gst']              = $gst;
                $fa2['creation_date']    = date("Y-m-d H:i:s");
                $fa2['created_by']       = $fn->getSessionParam('userName');

                $insert1 = $dbUtil->getInsertSQLStringFromArray($fa2, 'product_price');
                $result2 = $db->sql_query($insert1);*/
            }
        }

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getAddMultipleLineItemListValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $product_arr        = $fn->getReqParam('product_id', array());
        $price_arr          = $fn->getPostParam('price', array());
        $qty_arr            = $fn->getPostParam('qty', array());

        $validate->resetErrorArray();

        $filterArray3 = array_filter($qty_arr);
        if (count($filterArray3) == 0){
            $validate->errorArray['error_box']['name'] = "error_box1";
            $validate->errorArray['error_box']['msg']  = "Please Enter Qty";
        }

        /*$filterArray2 = array_filter($price_arr);
        if (count($filterArray2) == 0){
            $validate->errorArray['error_box']['name'] = "error_box1";
            $validate->errorArray['error_box']['msg']  = "Please Enter Price";
        }
        */


        $filterArray = array_filter($product_arr);
        if (count($filterArray) == 0){
            $validate->errorArray['error_box']['name'] = "error_box1";
            $validate->errorArray['error_box']['msg']  = "Please select product";
        }

        $SQLproduct = "
        SELECT product_id
        FROM po_product
        ";
        $resultproduct = $db->sql_query($SQLproduct);
        $product_id_db_arr = array();
        while($rowproduct    = $db->sql_fetchrow($resultproduct)){
            if($rowproduct['product_id'] != ''){
                $product_id_db_arr[] = $rowproduct['product_id'];
            }
        }

        $product_id_arr = array();
        foreach($product_arr as $key => $value){
            if($value != ''){
                if(in_array($value, $product_id_arr)){
                    $validate->errorArray['error_box']['name'] = "error_box1";
                    $validate->errorArray['error_box']['msg']  = "Please select different product";
                }

                if(!in_array($value, $product_id_arr)){
                    $product_id_arr[] = $value;
                }

                /*if(in_array($value, $product_id_db_arr)){
                    $validate->errorArray['error_box']['name'] = "error_box1";
                    $validate->errorArray['error_box']['msg']  = "Selected product already exists.";
                }*/
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
    function getAddMultipleLineItemListSubmit() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $validate = Zend_Registry::get('validate');

        if (!$this->getAddMultipleLineItemListValidate()){
            return $validate->getErrorMessageXML();
        }

        $supplier_id_arr = $fn->getPostParam('supplier_id', array());
        $product_arr     = $fn->getReqParam('product_id', array());
        $qty_arr         = $fn->getPostParam('qty', array());

        $count = count($product_arr);
        for ($i= 0; $i < $count; $i++) {
            $product_id  = $product_arr[$i];
            $supplier_id = $supplier_id_arr[$i];
            $qty         = $qty_arr[$i];

            if ($product_id) {
                $SQLPO ="
                SELECT pp.cost_price
                      ,pp.selling_price
                      ,pp.gst
                FROM po_product pp
                WHERE pp.product_id = {$product_id}
                ORDER BY pp.po_product_id desc
                ";
                $resultPo = $db->sql_query($SQLPO);
                $rowPo    = $db->sql_fetchrow($resultPo);

                if($rowPo['cost_price'] == ""){
                    $rowPo['cost_price'] = "0.00";
                }

                if($rowPo['selling_price'] == ""){
                    $rowPo['selling_price'] = "0.00";
                }

                if($rowPo['gst'] == ""){
                    $rowPo['gst'] = "0.00";
                }

                $fa = array();

                $fa['product_id']    = $product_id;
                $fa['selling_price'] = $rowPo['selling_price'];
                $fa['gst']           = $rowPo['gst'];
                $fa['cost_price']    = $rowPo['cost_price'];
                $fa['qty_requested'] = $qty;
                $fa['supplier_id']   = $supplier_id;

                $insert3 = $dbUtil->getInsertSQLStringFromArray($fa, 'product_supplier_temp');
                $result3 = $db->sql_query($insert3);

                $SQL = "
                SELECT ps.*
                      ,s.company_name
                FROM product_supplier_temp ps
                LEFT JOIN supplier s ON (s.supplier_id = ps.supplier_id)
                WHERE ps.supplier_id != ''
                  AND ps.product_id > 0
                GROUP BY s.company_name
                ";
                $result = $db->sql_query($SQL);
                while ($row = $db->sql_fetchrow($result)) {
                    $fa = array();
                    $fa['company_id_supplier'] = $row['supplier_id'];
                    $fa['creation_date'] = date('Y-m-d');
                    $fa['purchase_order_date'] = date('Y-m-d');
                    $fa['po_code'] = $this->getUpdatePOCode();
                    $fa['buy_currency'] = 'INR';

                    $SQLInsert = $dbUtil->getInsertSQLStringFromArray($fa, 'purchase_order');
                    $resultInsert = $db->sql_query($SQLInsert);
                    $purchase_order_id = $db->sql_nextid();

                    $SQLSelect = "
                    SELECT ps.*
                          ,s.company_name
                    FROM product_supplier_temp ps
                    LEFT JOIN supplier s ON (s.supplier_id = ps.supplier_id)
                    WHERE ps.supplier_id = {$row['supplier_id']}
                      AND ps.product_id > 0
                    GROUP BY ps.product_id
                    ";
                    $resultSelect = $db->sql_query($SQLSelect);
                    while ($rowPS = $db->sql_fetchrow($resultSelect)) {
                        $fa = array();
                        $fa['product_id']           = $rowPS['product_id'];
                        $fa['selling_price']        = $rowPS['selling_price'];
                        $fa['cost_price']           = $rowPS['cost_price'];
                        $fa['qty_requested']        = $rowPS['qty_requested'];
                        $fa['purchase_order_id']    = $purchase_order_id;
                        $fa['supplier_id']          = $rowPS['supplier_id'];
                        $fa['status']               = 'In progress';
                        $fa['creation_date']        = date("Y-m-d H:i:s");
                        $fa['created_by']           = $fn->getSessionParam('userName');

                        $insert = $dbUtil->getInsertSQLStringFromArray($fa, 'po_product');
                        $result = $db->sql_query($insert);
                        $po_product_id = $db->sql_nextid();

                        $fa1 = array();
                        $fa1['price']   = $rowPS['selling_price'];
                        //$fa1['gst']   = $gst;

                        $whereCondition = "WHERE product_id = {$product_id}";
                        $SQL1    = $dbUtil->getUpdateSQLStringFromArray($fa1, "product", $whereCondition);
                        $result1 = $db->sql_query($SQL1);

                        /*$fa2 = array();
                        $fa2['product_id']       = $product_id;
                        $fa2['price']            = $price;
                        $fa2['gst']              = $gst;
                        $fa2['creation_date']    = date("Y-m-d H:i:s");
                        $fa2['created_by']       = $fn->getSessionParam('userName');

                        $insert1 = $dbUtil->getInsertSQLStringFromArray($fa2, 'product_price');
                        $result2 = $db->sql_query($insert1);*/
                    }
                }
                $SQLDELETE="TRUNCATE product_supplier_temp";
                $resultDelete = $db->sql_query($SQLDELETE);
            }
        }

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getAddNewProductValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $purchase_order_id = $fn->getPostParam('purchase_order_id');
        $supplier_id       = $fn->getPostParam('supplier_id');
        $product_id_arr    = $fn->getReqParam('product_id', array());
        $price_arr         = $fn->getPostParam('price', array());
        $qty_arr           = $fn->getPostParam('qty', array());
        $qty_arr           = $fn->getPostParam('qty', array());
        $batch_no_arr      = $fn->getPostParam('batch_no', array());
        $pack_size_arr     = $fn->getPostParam('pack_size', array());
        $cost_price_arr    = $fn->getPostParam('cost_price', array());

        $validate->resetErrorArray();

        $filterArray3 = array_filter($qty_arr);
        if (count($filterArray3) == 0){
            $validate->errorArray['error_box1']['name'] = "error_box1";
            $validate->errorArray['error_box1']['msg']  = "Please Enter Qty";
        }

        /*$filterArray2 = array_filter($price_arr);
        if (count($filterArray2) == 0){
            $validate->errorArray['error_box']['name'] = "error_box1";
            $validate->errorArray['error_box']['msg']  = "Please Enter Price";
        }
        */

        $filterArray = array_filter($product_id_arr);
        if (count($filterArray) == 0){
            $validate->errorArray['error_box1']['name'] = "error_box1";
            $validate->errorArray['error_box1']['msg']  = "Please enter product";
        }

        $SQLproduct = "
        SELECT product_id
        FROM po_product
        WHERE purchase_order_id = {$purchase_order_id}
        ";
        $resultproduct = $db->sql_query($SQLproduct);
        $product_id_db_arr = array();
        while($rowproduct    = $db->sql_fetchrow($resultproduct)){
            if($rowproduct['product_id'] != ''){
                $product_id_db_arr[] = $rowproduct['product_id'];
            }
        }

        $countProd = count($product_id_arr);
        $countCheck = 1;
        $product_idCheck = '';
        $batch_noCheck   = '';
        for ($i= 0; $i < $countProd; $i++) {
            $product_id = $product_id_arr[$i];
            $batch_no   = $batch_no_arr[$i];
            $pack_size  = $pack_size_arr[$i];
            $price      = $price_arr[$i];
            $cost_price = $cost_price_arr[$i];

            if($product_id != ""){
                $SQLProductCheck = "
                SELECT product_id
                FROM po_product
                WHERE purchase_order_id = {$purchase_order_id}
                AND product_id = {$product_id}
                AND batch_no = '{$batch_no}'
                ";
                $resultProductCheck  = $db->sql_query($SQLProductCheck);
                $numRowsProductCheck = $db->sql_numrows($resultProductCheck);

                if($numRowsProductCheck >= 1){
                    $validate->errorArray['error_box1']['name'] = "error_box1";
                    $validate->errorArray['error_box1']['msg']  = "Selected product already exists.";
                }

                if($product_id == $product_idCheck && $batch_no == $batch_noCheck) {
                    $countCheck++;
                }

                $product_idCheck = $product_id;
                $batch_noCheck   = $batch_no;

                $expiry_date = $fn->getPostParam('expiry_date_'.$i);

                if($expiry_date == "") {
                    $validate->errorArray['error_box1']['name'] = "error_box1";
                    $validate->errorArray['error_box1']['msg']  = "Select expiry date";
                    
                    $validate->errorArray['expiry_date_'.$i]['name'] = "expiry_date_".$i;
                    $validate->errorArray['expiry_date_'.$i]['msg']  = "!";
                }

                if($batch_no == "") {
                    $validate->errorArray['error_box1']['name'] = "error_box1";
                    $validate->errorArray['error_box1']['msg']  = "Please enter batch no";
                }

                if($pack_size == "") {
                    $validate->errorArray['error_box1']['name'] = "error_box1";
                    $validate->errorArray['error_box1']['msg']  = "Please enter pack size";
                }

                if($price == $cost_price) {
                    $validate->errorArray['error_box1']['name'] = "error_box1";
                    $validate->errorArray['error_box1']['msg']  = "Please note RATE and MRP must not be same";
                }

                $addedRatePercentage = ($cost_price * 15) / 100;
                $chkPriceRate = $cost_price + $addedRatePercentage;

                if($price < $chkPriceRate) {
                    $validate->errorArray['error_box1']['name'] = "error_box1";
                    $validate->errorArray['error_box1']['msg']  = "Please note MRP must not be less for this RATE";
                }

                /*if($price != "") {
                    $SQLPriceCheck = "
                    SELECT selling_price, pack_size
                    FROM po_product
                    WHERE product_id = {$product_id}
                    ORDER BY po_product_id DESC
                    ";
                    $resultPriceCheck  = $db->sql_query($SQLPriceCheck);
                    $numRowsPriceCheck = $db->sql_numrows($resultPriceCheck);
                    $rowPriceChk = $db->sql_fetchrow($resultPriceCheck);

                    if($numRowsPriceCheck > 0){
                        if(is_numeric($rowPriceChk['pack_size'])){
                            $chkPricePre = $rowPriceChk['selling_price'] / $rowPriceChk['pack_size'];
                        } else {
                            $chkPricePre = $rowPriceChk['selling_price'];
                        }

                        $addedPercentage = ($chkPricePre * 15) / 100;

                        $chkPricePre = $chkPricePre + $addedPercentage;

                        if(is_numeric($pack_size)){
                            $chkPrice = $price / $pack_size;
                        } else {
                            $chkPrice = $price;
                        }

                        if($chkPrice > $chkPricePre){
                            //$validate->errorArray['error_box1']['name'] = "error_box1";
                            //$validate->errorArray['error_box1']['msg']  = "Please check the price and pack size";
                        }
                    }
                }*/
            }
        }

        if($countCheck > 1){
            $validate->errorArray['error_box1']['name'] = "error_box1";
            $validate->errorArray['error_box1']['msg']  = "Selected product already exists.";
        }

        /*$product_id_arr = array();
        foreach($product_id_arr as $key => $value){
            if($value != ''){
                if(in_array($value, $product_id_arr)){
                    $validate->errorArray['error_box1']['name'] = "error_box1";
                    $validate->errorArray['error_box1']['msg']  = "Please select different product";
                }

                if(!in_array($value, $product_id_arr)){
                    $product_id_arr[] = $value;
                }

                if(in_array($value, $product_id_db_arr)){
                    $validate->errorArray['error_box1']['name'] = "error_box1";
                    $validate->errorArray['error_box1']['msg']  = "Selected product already exists.";
                }
            }

        }*/

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getAddNewProductSubmit() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $validate = Zend_Registry::get('validate');

        if (!$this->getAddNewProductValidate()){
            return $validate->getErrorMessageXML();
        }

        $product_id_arr          = $fn->getReqParam('product_id', array());
        $purchase_order_id       = $fn->getPostParam('purchase_order_id');
        $supplier_id             = $fn->getPostParam('supplier_id');
        $price_arr               = $fn->getPostParam('price', array());
        $cost_price_arr          = $fn->getPostParam('cost_price', array());
        $qty_arr                 = $fn->getPostParam('qty', array());
        $gst_arr                 = $fn->getPostParam('gst', array());
        $hsn_arr                 = $fn->getPostParam('hsn', array());
        $pack_size_arr           = $fn->getPostParam('pack_size', array());
        $medicine_company_arr    = $fn->getPostParam('medicine_company', array());
        $batch_no_arr            = $fn->getPostParam('batch_no', array());
        $free_items_arr          = $fn->getPostParam('free_items', array());
        //$expiry_date_arr         = $fn->getPostParam('expiry_date', array());
        $discount_percentage_arr = $fn->getPostParam('discount_percentage', array());
        $category_arr    = $fn->getPostParam('category', array());

        $priceVariedCheck = '';
        $costPriceVariedCheck = '';
        $count = count($product_id_arr);
        for ($i= 0; $i < $count; $i++) {
            $product_id          = $product_id_arr[$i];
            $price               = $price_arr[$i];
            $cost_price          = $cost_price_arr[$i];
            $qty                 = $qty_arr[$i];
            $gst                 = $gst_arr[$i];
            $hsn                 = $hsn_arr[$i];
            $pack_size           = $pack_size_arr[$i];
            $medicine_company    = $medicine_company_arr[$i];
            $batch_no            = $batch_no_arr[$i];
            $free_items          = $free_items_arr[$i];
            $discount_percentage = $discount_percentage_arr[$i];
            //$expiry_date         = $expiry_date_arr[$i];
            $expiry_date = $fn->getPostParam('expiry_date_'.$i);
            $category    = $category_arr[$i];

            if ($product_id) {
                $fa = array();

                if($price == ''){
                    $price = 0;
                }
                
                if($cost_price == ''){
                    $cost_price = 0;
                }

                if($gst == ''){
                    $gst = 0;
                }

                if($free_items == ''){
                    $free_items = 0;
                }

                if($discount_percentage == ''){
                    $discount_percentage = 0;
                }

                if($qty == ''){
                    $qty = 0;
                }

                $SQLUpdateProduct = "
                UPDATE product SET gst = '{$gst}', price = '{$price}', category_id = '{$category}'
                WHERE product_id = '{$product_id}'
                ";
                $resultUpdateProduct  = $db->sql_query($SQLUpdateProduct);

                $SQLPriceCheck = "
                SELECT selling_price, pack_size, cost_price
                FROM po_product
                WHERE product_id = {$product_id}
                ORDER BY po_product_id DESC
                ";
                $resultPriceCheck  = $db->sql_query($SQLPriceCheck);
                $numRowsPriceCheck = $db->sql_numrows($resultPriceCheck);
                $rowPriceChk = $db->sql_fetchrow($resultPriceCheck);

                if($numRowsPriceCheck > 0){
                    if(is_numeric($rowPriceChk['pack_size'])){
                        $chkPricePre = $rowPriceChk['selling_price'] / $rowPriceChk['pack_size'];
                        $chkCostPricePre = $rowPriceChk['cost_price'] / $rowPriceChk['pack_size'];
                    } else {
                        $chkPricePre = $rowPriceChk['selling_price'];
                        $chkCostPricePre = $rowPriceChk['cost_price'];
                    }

                    $addedPercentage = ($chkPricePre * 15) / 100;
                    $addedCostPercentage = ($chkCostPricePre * 15) / 100;

                    $chkPricePre = $chkPricePre + $addedPercentage;
                    $chkCostPricePre = $chkCostPricePre + $addedCostPercentage;

                    if(is_numeric($pack_size)){
                        $chkPrice = $price / $pack_size;
                        $chkCostPrice = $cost_price / $pack_size;
                    } else {
                        $chkPrice = $price;
                        $chkCostPrice = $cost_price;
                    }

                    if($chkPrice > $chkPricePre){
                        $fa['price_varied'] = 1;
                        $priceVariedCheck   = 1;
                    }

                    if($chkCostPrice > $chkCostPricePre){
                        $fa['cost_price_varied'] = 1;
                        $costPriceVariedCheck   = 1;
                    }
                }
                
                $fa['product_id']          = $product_id;
                $fa['selling_price']       = $price;
                $fa['cost_price']          = $cost_price;
                $fa['qty_requested']       = $qty;
                $fa['status']              = 'New';
                $fa['purchase_order_id']   = $purchase_order_id;
                $fa['supplier_id']         = $supplier_id;
                $fa['gst']                 = $gst;
                $fa['hsn']                 = $hsn;
                $fa['status']              = 'In progress';
                $fa['pack_size']           = $pack_size;
                $fa['medicine_company_id'] = $medicine_company;
                $fa['batch_no']            = $batch_no;
                $fa['discount_type']       = '%';
                $fa['discount_percentage'] = $discount_percentage;
                $fa['free_items']          = $free_items;
                $fa['expiry_date']         = $expiry_date;
                $fa['created_by']          = $fn->getSessionParam('userName');
                $fa['creation_date']       = date("Y-m-d H:i:s");

                $insert        = $dbUtil->getInsertSQLStringFromArray($fa, 'po_product');
                $result        = $db->sql_query($insert);
                $po_product_id = $db->sql_nextid();
            }
        }

        if($priceVariedCheck != '') {
            $SQLUpdatePurOrd = "
            UPDATE purchase_order SET price_varied = 1
            WHERE purchase_order_id = '{$purchase_order_id}'
            ";
            $resultUpdatePurOrd  = $db->sql_query($SQLUpdatePurOrd);
        }

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getAddNewProductListValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $product_arr        = $fn->getPostParam('product', array());
        $price_arr          = $fn->getPostParam('price', array());
        $qty_arr            = $fn->getPostParam('qty', array());

        $validate->resetErrorArray();

        $filterArray3 = array_filter($qty_arr);
        if (count($filterArray3) == 0){
            $validate->errorArray['error_box']['name'] = "error_box1";
            $validate->errorArray['error_box']['msg']  = "Please Enter Qty";
        }

        /*$filterArray2 = array_filter($price_arr);
        if (count($filterArray2) == 0){
            $validate->errorArray['error_box']['name'] = "error_box1";
            $validate->errorArray['error_box']['msg']  = "Please Enter Price";
        }
        */

        $filterArray = array_filter($product_arr);
        if (count($filterArray) == 0){
            $validate->errorArray['error_box']['name'] = "error_box1";
            $validate->errorArray['error_box']['msg']  = "Please enter product";
        }

        /*$SQLproduct = "
        SELECT product_id
        FROM po_product
        ";
        $resultproduct = $db->sql_query($SQLproduct);
        $product_id_db_arr = array();
        while($rowproduct    = $db->sql_fetchrow($resultproduct)){
            if($rowproduct['product_id'] != ''){
                $product_id_db_arr[] = $rowproduct['product_id'];
            }
        }

        $product_id_arr = array();
        foreach($product_arr as $key => $value){
            if($value != ''){
                if(in_array($value, $product_id_arr)){
                    $validate->errorArray['error_box']['name'] = "error_box1";
                    $validate->errorArray['error_box']['msg']  = "Please select different product";
                }

                if(!in_array($value, $product_id_arr)){
                    $product_id_arr[] = $value;
                }

                if(in_array($value, $product_id_db_arr)){
                    $validate->errorArray['error_box']['name'] = "error_box1";
                    $validate->errorArray['error_box']['msg']  = "Selected product already exists.";
                }
            }

        }*/

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getAddNewProductListSubmit() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $validate = Zend_Registry::get('validate');

        if (!$this->getAddNewProductListValidate()){
            return $validate->getErrorMessageXML();
        }

        $supplier_id_arr    = $fn->getPostParam('supplier_id', array());
        $product_arr        = $fn->getPostParam('product', array());
        $price_arr          = $fn->getPostParam('price', array());
        $cost_price_arr     = $fn->getPostParam('cost_price', array());
        $qty_arr            = $fn->getPostParam('qty', array());
        $gst_arr            = $fn->getPostParam('gst', array());
        $unit_arr           = $fn->getPostParam('unit', array());
        $product_weight_arr = $fn->getPostParam('product_weight', array());
        $hsn_arr            = $fn->getPostParam('hsn', array());
        $category_id_arr    = $fn->getPostParam('category', array());
        $type_arr           = $fn->getPostParam('type', array());

        $count = count($product_arr);
        for ($i= 0; $i < $count; $i++) {
            $product         = $product_arr[$i];
            $price              = $price_arr[$i];
            $cost_price         = $cost_price_arr[$i];
            $qty                = $qty_arr[$i];
            $gst                = $gst_arr[$i];
            $unit               = $unit_arr[$i];
            $product_weight     = $product_weight_arr[$i];
            $hsn                = $hsn_arr[$i];
            $category_id        = $category_id_arr[$i];
            $supplier_id        = $supplier_id_arr[$i];
            $type               = $type_arr[$i];

            if ($product) {
                $fa = array();
                $fa['title']            = $product;
                $fa['selling_price']    = $price;
                $fa['cost_price']       = $cost_price;
                $fa['qty_requested']    = $qty;
                $fa['gst']              = $gst;
                $fa['unit']             = $unit;
                $fa['product_weight']   = $product_weight;
                $fa['hsn']              = $hsn;
                $fa['category_id']      = $category_id;
                $fa['supplier_id']      = $supplier_id;
                $fa['product_type']      = $type;

                $insert3 = $dbUtil->getInsertSQLStringFromArray($fa, 'product_supplier_temp');
                $result3 = $db->sql_query($insert3);

                $SQL = "
                SELECT ps.*
                      ,s.company_name
                      ,c.title AS category_title
                FROM product_supplier_temp ps
                LEFT JOIN supplier s ON (s.supplier_id = ps.supplier_id)
                LEFT JOIN category c ON (c.category_id = ps.category_id)
                WHERE ps.supplier_id != ''
                  AND ps.title != ''
                GROUP BY s.company_name
                ";
                $result = $db->sql_query($SQL);
                while ($row = $db->sql_fetchrow($result)) {
                    $fa = array();
                    $fa['company_id_supplier'] = $row['supplier_id'];
                    $fa['creation_date'] = date('Y-m-d');
                    $fa['po_code'] = $this->getUpdatePOCode();
                    $fa['buy_currency'] = 'INR';
                    $fa['status']  = 'In progress';
                    $fa['payment_status']  = 'Due';
                    $fa['purchase_order_date'] = date('Y-m-d');

                    $SQLInsert = $dbUtil->getInsertSQLStringFromArray($fa, 'purchase_order');
                    $resultInsert = $db->sql_query($SQLInsert);
                    $purchase_order_id = $db->sql_nextid();

                    $SQLSelect = "
                    SELECT ps.*
                          ,s.company_name
                          ,c.title AS category_title
                    FROM product_supplier_temp ps
                    LEFT JOIN supplier s ON (s.supplier_id = ps.supplier_id)
                    LEFT JOIN category c ON (c.category_id = ps.category_id)
                    WHERE ps.supplier_id = {$row['supplier_id']}
                      AND ps.title != ''
                    GROUP BY ps.title
                    ";
                    $resultSelect = $db->sql_query($SQLSelect);
                    while ($rowPS = $db->sql_fetchrow($resultSelect)) {
                        $fa1 = array();
                        $fa1['title'] = $rowPS['title'];
                        $fa1['price'] = $rowPS['selling_price'];
                        $fa1['gst']   = $rowPS['gst'];
                        $fa1['published']  = 1;
                        $fa1['product_weight']  = $rowPS['product_weight'];
                        $fa1['hsn']  = $rowPS['hsn'];
                        $fa1['unit']  = $rowPS['unit'];
                        $fa1['category_id']   = $rowPS['category_id'];
                        $fa1['product_type'] = 'Purchasing and Selling';
                        $fa1['item_code'] = $this->getUpdateProductCode();
                        $fa1['product_type']  = $rowPS['product_type'];

                        $insert1 = $dbUtil->getInsertSQLStringFromArray($fa1, 'product');
                        $result1 = $db->sql_query($insert1);
                        $product_id = $db->sql_nextid();

                        $fa = array();
                        $fa['product_id']           = $product_id;
                        $fa['selling_price']        = $rowPS['selling_price'];
                        $fa['cost_price']           = $rowPS['cost_price'];
                        $fa['qty_requested']        = $rowPS['qty_requested'];
                        $fa['status']               = 'New';
                        $fa['purchase_order_id']    = $purchase_order_id;
                        $fa['supplier_id']          = $rowPS['supplier_id'];
                        $fa['status']               = 'In progress';
                        $fa['creation_date']        = date("Y-m-d H:i:s");
                        $fa['created_by']           = $fn->getSessionParam('userName');

                        $insert = $dbUtil->getInsertSQLStringFromArray($fa, 'po_product');
                        $result = $db->sql_query($insert);
                        $po_product_id = $db->sql_nextid();

                        $fa1 = array();
                        $fa1['company_id']      = $rowPS['supplier_id'];
                        $fa1['product_id']      = $product_id;
                        $fa1['creation_date']   = date('Y-m-d H:i:s');

                        $insert4 = $dbUtil->getInsertSQLStringFromArray($fa1, 'product_company');
                        $result4 = $db->sql_query($insert4);
                    }
                }
                $SQLDELETE="TRUNCATE product_supplier_temp";
                $resultDelete = $db->sql_query($SQLDELETE);
            }
        }

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */

    function getProductFormSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        if (!$this->getProductValidate()){
            return $validate->getErrorMessageXML();
        }

        $product_id        = $fn->getPostParam('product_id');
        $purchase_order_id = $fn->getPostParam('purchase_order_id');
        $price             = $fn->getPostParam('price');
        $qty               = $fn->getPostParam('qty');
        $qty_delivered     = $fn->getPostParam('qty_delivered');
        $status            = $fn->getPostParam('status');

        $fa = array();

        $fa['product_id']       = $product_id;
        $fa['price']            = $price;
        $fa['qty_requested']    = $qty;
        $fa['qty']              = $qty_delivered;
        $fa['status']           = $status;
        $fa['purchase_order_id']= $purchase_order_id;
        $fa['creation_date']    = date("Y-m-d H:i:s");
        $fa['created_by']       = $fn->getSessionParam('userName');

        $fn->addRecord($fa, 'po_product');

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */

    function getProductValidate() {
        $validate = Zend_Registry::get('validate');

        $validate->resetErrorArray();
        //$validate->validateData('product_group_id', 'Please select the product group');
        //$validate->validateData('product_id', 'Please enter Medicine Name');

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getEditPoProductRecordValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');

        $validate->resetErrorArray();
        $validate->validateData('qty_delivered', 'Please enter the qty');
        if($_SESSION['userGroupName'] == "Super Administrator") {
            $validate->validateData('status', 'Please select status');
        }

        $po_product_id  = $fn->getPostParam('po_product_id');
        $qty_delivered  = $fn->getPostParam('qty_delivered');
        $qty            = $fn->getPostParam('qty');
        $damage_qty     = $fn->getPostParam('damage_qty');

        $status = $fn->getPostParam('status');

        if($_SESSION['userGroupName'] == "Super Administrator" && $status == ""){
            $validate->errorArray['error_box1']['name'] = "error_box1";
            $validate->errorArray['error_box1']['msg']  = "Please select status";
        }

        if($qty_delivered == ""){
            $validate->errorArray['error_box1']['name'] = "error_box1";
            $validate->errorArray['error_box1']['msg']  = "Please enter the qty";
        }
        //$po_productRec  = $fn->getRecordRowByID('po_product', 'po_product_id', $po_product_id);

        if($qty_delivered > $qty){
            $validate->errorArray['error_box1']['name'] = "error_box1";
            $validate->errorArray['error_box1']['msg']  = "Please enter qty less than or equal to {$qty}.";

            $validate->errorArray['qty_delivered']['name'] = "qty_delivered";
            $validate->errorArray['qty_delivered']['msg']  = "Please enter qty less than or equal to {$qty}.";
        }

        if($damage_qty > $qty){
            $validate->errorArray['damage_qty']['name'] = "damage_qty";
            $validate->errorArray['damage_qty']['msg']  = "Please enter qty less than or equal to {$qty}.";

            $validate->errorArray['damage_qty']['name'] = "damage_qty";
            $validate->errorArray['damage_qty']['msg']  = "Please enter qty less than or equal to {$qty}.";
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
    function getEditPoProductRecordSubmit() {
        $fn       = Zend_Registry::get('fn');
        $dbUtil   = Zend_Registry::get('dbUtil');
        $db       = Zend_Registry::get('db');
        $cpCfg    = Zend_Registry::get('cpCfg');
        $validate = Zend_Registry::get('validate');

        if (!$this->getEditPoProductRecordValidate()){
            return $validate->getErrorMessageXML();
        }

        $po_product_id       = $fn->getPostParam('po_product_id');
        $qty_delivered       = $fn->getPostParam('qty_delivered');
        $qty                 = $fn->getPostParam('qty');
        $status              = $fn->getPostParam('status');
        $gst                 = $fn->getPostParam('gst');
        $damage_qty          = $fn->getPostParam('damage_qty');
        $pack_size           = $fn->getPostParam('pack_size');
        $batch_no            = $fn->getPostParam('batch_no');
        $expiry_date         = $fn->getPostParam('expiry_date');
        $free_items          = $fn->getPostParam('free_items');
        $cost_price          = $fn->getPostParam('cost_price');
        $selling_price       = $fn->getPostParam('selling_price');
        $medicine_company    = $fn->getPostParam('medicine_company');
        $discount_percentage = $fn->getPostParam('discount_percentage');
        $gst                 = $fn->getPostParam('gst');
        $hsn                 = $fn->getPostParam('hsn');
        $product_id          = $fn->getPostParam('product_id_edit_hidden');
        $site_id             = $fn->getSessionParam('cp_site_id');

        $SQLPoProdPrev = "
        SELECT product_id
              ,qty
              ,pack_size
              ,damage_qty
              ,purchase_order_id
              ,batch_no
              ,po_product_id
              ,free_items
              ,qty_requested
        FROM po_product
        WHERE po_product_id = '{$po_product_id}'
        ";
        $resultPoProdPrev = $db->sql_query($SQLPoProdPrev);
        $PoProdPrev = $db->sql_fetchrow($resultPoProdPrev);

        if($damage_qty == ''){
            $damage_qty = 0;
        }

        if($discount_percentage == ''){
            $discount_percentage = 0;
        }

        $fa = array();
        $fa['pack_size']           = $pack_size;
        $fa['batch_no']            = $batch_no;
        $fa['expiry_date']         = $expiry_date;
        $fa['free_items']          = $free_items;
        
        //if ($_SESSION['userGroupName'] == "Super Administrator") {
            $fa['selling_price']       = $selling_price;
            $fa['cost_price']          = $cost_price;
            $fa['gst']                 = $gst;
            $fa['status']              = $status;
            $fa['discount_percentage'] = $discount_percentage;
        //}
        
        $fa['qty_requested']       = $qty;
        $fa['qty']                 = $qty_delivered;
        $fa['damage_qty']          = $damage_qty;
        $fa['product_id']          = $product_id;
        $fa['medicine_company_id'] = $medicine_company;
        $fa['hsn']                 = $hsn;
        $fa['modification_date']   = date("Y-m-d H:i:s");
        $fa['modified_by']         = $fn->getSessionParam('userName');            

        $whereCondition = "WHERE po_product_id = {$po_product_id}";
        $SQL    = $dbUtil->getUpdateSQLStringFromArray($fa, "po_product", $whereCondition);
        $result = $db->sql_query($SQL);

        $SQLUpdateBatchInventory = "
        UPDATE inventory_batchwise_stock SET batch_no = '{$batch_no}'
        WHERE po_product_id = '{$po_product_id}'
        AND site_id = '{$site_id}'
        ";
        $resultUpdateBatchInventory  = $db->sql_query($SQLUpdateBatchInventory);

        $SQLPoProd = "
        SELECT product_id
              ,qty
              ,pack_size
              ,batch_no
              ,damage_qty
              ,po_product_id
              ,purchase_order_id
              ,free_items
        FROM po_product
        WHERE po_product_id = '{$po_product_id}'
        ";
        $resultPoProd  = $db->sql_query($SQLPoProd);
        $PoProd = $db->sql_fetchrow($resultPoProd);

        /*Creating record in stock history when editing po product record and changing qty*/
        $SQLSH = "
        SELECT stock_history_id
              ,po_product_id
              ,SUM(qty) AS qty
              ,SUM(damage_qty) AS damage_qty
        FROM stock_history
        WHERE po_product_id = '{$po_product_id}'
        ORDER BY stock_history_id desc
        ";
        $resultSH  = $db->sql_query($SQLSH);
        $rowSH = $db->sql_fetchrow($resultSH);

        $qty = $PoProd['qty'] - $rowSH['qty']; 
        $damage_qty = $PoProd['damage_qty'] - $rowSH['damage_qty']; 
        if($PoProd['qty'] != $rowSH['qty'] || $PoProd['damage_qty'] != $rowSH['damage_qty']){
            $fa = array();
            $fa['po_product_id'] = $po_product_id;
            $fa['product_id']  = $PoProd['product_id'];
            $fa['purchase_order_id']  = $PoProd['purchase_order_id'];
            $fa['qty']  = $qty;
            $fa['damage_qty']  = $damage_qty;
            $fa['creation_date']  = date("Y-m-d H:i:s");
            $SQLInsert = $dbUtil->getInsertSQLStringFromArray($fa, 'stock_history');
            $resultInsert = $db->sql_query($SQLInsert); 
        }           

        /*$SQLOthersite = "
        SELECT
            (SELECT SUM(qty) FROM po_product pp
             LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
             WHERE pp.product_id = {$PoProd['product_id']}
             AND po.status != 'Cancelled') as product_qty_purchased
           
           ,(SELECT SUM(damage_qty) FROM po_product pp
             LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
             WHERE pp.product_id = {$PoProd['product_id']}) as product_qty_damaged

           ,(SELECT SUM(invItem.qty) FROM invoice_item invItem
            LEFT JOIN (invoice inv) ON (inv.invoice_id = invItem.invoice_id)
            WHERE invItem.record_id = {$PoProd['product_id']}
            AND invItem.not_add_in_stock != 1
            AND inv.status != 'Cancelled'
            ) as product_qty_sold

            ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
            LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
            LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
            WHERE ini.record_id = {$PoProd['product_id']}
              AND srh.status IS NULL
            ) as sales_return_qty
        ";
        $resultothersite = $db->sql_query($SQLOthersite);
        $rowothersite = $db->sql_fetchrow($resultothersite);

        $stock = $rowothersite['product_qty_purchased'] - $rowothersite['product_qty_sold'] + $rowothersite['sales_return_qty'] - $rowothersite['product_qty_damaged'];
        */

        if($_SESSION['userGroupName'] == "Super Administrator" && $selling_price != ''){
            $fa2 = array();
            $fa2['product_id']       = $PoProd['product_id'];
            $fa2['price']            = $selling_price;
            $fa2['creation_date']    = date("Y-m-d H:i:s");
            $fa2['created_by']       = $fn->getSessionParam('userName');

            $insert1 = $dbUtil->getInsertSQLStringFromArray($fa2, 'product_price');
            $result2 = $db->sql_query($insert1);

            $fa5 = array();
            $fa5['price']    = $selling_price;
            $fa5             = $fn->addModificationDetailsToFieldsArray($fa5, 'product');
            $whereCondition5 = "WHERE product_id = {$PoProd['product_id']}";
            $SQLProd5    = $dbUtil->getUpdateSQLStringFromArray($fa5, "product", $whereCondition5);
            $resultProd5 = $db->sql_query($SQLProd5);

            /*$SQLUpdateProduct = "
            UPDATE product SET qty_in_stock = {$stock}, gst = {$gst}, price = {$selling_price}
            WHERE product_id = '{$PoProd['product_id']}'
            ";
            $resultUpdateProduct  = $db->sql_query($SQLUpdateProduct);*/        
        } /*else {
            $SQLUpdateProduct = "
            UPDATE product SET qty_in_stock = {$stock}
            WHERE product_id = '{$PoProd['product_id']}'
            ";
            $resultUpdateProduct  = $db->sql_query($SQLUpdateProduct);
        }*/

        /*$appendSqlSite  = "";
        $siteIdForField = "";
        if($site_id != ""){ 
            $appendSqlSite  = "AND site_id = {$site_id}";
            $siteIdForField = $site_id;
        }

        $SQLBatchWiseStock = "
        SELECT po_product_id
              ,purchased_qty
              ,damaged_qty
              ,free_qty
              ,current_stock
        FROM inventory_batchwise_stock
        WHERE po_product_id = '{$po_product_id}'
        AND product_id = '{$PoProd['product_id']}'
        {$appendSqlSite}
        ";
        $resultBatchWiseStock  = $db->sql_query($SQLBatchWiseStock);
        $BatchWiseStock        = $db->sql_fetchrow($resultBatchWiseStock);
        $numRowsBatchWiseStock = $db->sql_numrows($resultBatchWiseStock);

        if($PoProdPrev['qty'] == $PoProdPrev['qty_requested'] && $numRowsBatchWiseStock == 0) {
        } else {
            if(is_numeric($PoProd['pack_size'])){
                $poQty = $PoProd['qty'] * $PoProd['pack_size'];
            } else {
                $poQty = $PoProd['qty'];
            }

            if(is_numeric($PoProd['pack_size'])){
                $freeQty = $PoProd['free_items'] * $PoProd['pack_size'];
            } else {
                $freeQty = $PoProd['free_items'];
            }

            if($numRowsBatchWiseStock == 0) {
                $stockUpdate = $poQty + $freeQty - $PoProd['damage_qty'];
                
                $faStock = array();
                $faStock['product_id']        = $PoProd['product_id'];
                $faStock['po_product_id']     = $PoProd['po_product_id'];
                $faStock['purchase_order_id'] = $PoProd['purchase_order_id'];
                $faStock['batch_no']          = $PoProd['batch_no'];
                $faStock['purchased_qty']     = $poQty;
                $faStock['damaged_qty']       = $PoProd['damage_qty'];
                $faStock['free_qty']          = $freeQty;
                $faStock['current_stock']     = $stockUpdate;
                
                if($cpCfg['cp.hasMultiUniqueSites']  == true){
                    $faStock['site_id'] = $site_id;
                }

                $faStock     = $fn->addCreationDetailsToFieldsArray($faStock, 'inventory_batchwise_stock');
                $SQLStock    = $dbUtil->getInsertSQLStringFromArray($faStock, 'inventory_batchwise_stock');
                $resultStock = $db->sql_query($SQLStock);

                $SQLUpdateProduct = "
                UPDATE product SET qty_in_stock{$siteIdForField} = IFNULL(qty_in_stock{$siteIdForField}, 0)+{$stockUpdate}
                WHERE product_id = '{$PoProd['product_id']}'
                ";
                $resultUpdateProduct  = $db->sql_query($SQLUpdateProduct);

                $SQLInventory = "
                SELECT product_id
                FROM inventory
                WHERE product_id = {$PoProd['product_id']}
                ";
                $resultInventory  = $db->sql_query($SQLInventory);
                $numRowsInventory = $db->sql_numrows($resultInventory);

                if($numRowsInventory == 0) {
                    $fa = array();
                    $fa['product_id']    = $PoProd['product_id'];
                    $fa['actual_stock'.$siteIdForField] = $stockUpdate;
                    $fa['creation_date'] = date('Y-m-d H:i:s');

                    $inventory_id = $fn->addRecord($fa, 'inventory');
                } else {
                    $SQLUpdateInventory = "
                    UPDATE inventory SET actual_stock{$siteIdForField} = IFNULL(actual_stock{$siteIdForField}, 0)+{$stockUpdate}
                    WHERE product_id = '{$PoProd['product_id']}'
                    ";
                    $resultUpdateInventory  = $db->sql_query($SQLUpdateInventory);
                }

            } else {
                $qty_batchwise        = $poQty - $BatchWiseStock['purchased_qty']; 
                $damage_qty_batchwise = $PoProd['damage_qty'] - $BatchWiseStock['damaged_qty']; 
                $free_qty_batchwise   = $freeQty - $BatchWiseStock['free_qty']; 
                $current_stock        = $BatchWiseStock['current_stock'] + $qty_batchwise + $free_qty_batchwise - $damage_qty_batchwise;
                $stockCalc            = $qty_batchwise + $free_qty_batchwise - $damage_qty_batchwise;

                $faStock = array();
                $faStock['purchased_qty'] = $BatchWiseStock['purchased_qty'] + $qty_batchwise;
                $faStock['damaged_qty']   = $BatchWiseStock['damaged_qty'] + $damage_qty_batchwise;
                $faStock['free_qty']      = $BatchWiseStock['free_qty'] + $free_qty_batchwise;
                $faStock['current_stock'] = $current_stock;
                
                $faStock        = $fn->addModificationDetailsToFieldsArray($faStock, 'inventory_batchwise_stock');
                $whereCondition = "WHERE po_product_id = {$po_product_id}
                                   AND product_id = '{$PoProd['product_id']}'
                                   {$appendSqlSite}";
                $SQLStock    = $dbUtil->getUpdateSQLStringFromArray($faStock, "inventory_batchwise_stock", $whereCondition);
                $resultStock = $db->sql_query($SQLStock);

                $SQLUpdateProduct = "
                UPDATE product SET qty_in_stock{$siteIdForField} = IFNULL(qty_in_stock{$siteIdForField}, 0)+{$stockCalc}
                WHERE product_id = '{$PoProd['product_id']}'
                ";
                $resultUpdateProduct  = $db->sql_query($SQLUpdateProduct);

                $SQLUpdateInventory = "
                UPDATE inventory SET actual_stock{$siteIdForField} = IFNULL(actual_stock{$siteIdForField}, 0)+{$stockCalc}
                WHERE product_id = '{$PoProd['product_id']}'
                ";
                $resultUpdateInventory  = $db->sql_query($SQLUpdateInventory);
            }
        }*/

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getTradingsgPurchaseOrderTradingsgProductLinkSQL($id) {

        $SQL = "
        SELECT po.po_product_id
              ,p.title AS product_name
              ,po.price
              ,po.qty
              ,po.qty_delivered
              ,po.status
        FROM po_product po
        JOIN product p ON (p.product_id = po.product_id)
        WHERE po.purchase_order_id = {$id}
        ";

        return $SQL;
    }
    /**
     *
     */
    function getExportData($dataArray){
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $cpUtil = Zend_Registry::get('cpUtil');
        $dateUtil = Zend_Registry::get('dateUtil');
        $fn = Zend_Registry::get('fn');


        $rows = '';

        set_time_limit(50000);
        ini_set('memory_limit', '512M');

        require_once("PHPExcel.php");
        include 'PHPExcel/IOFactory.php';

        $file_name = "Purchaseorder_Export_" . date("d-m-Y") . ".xls";

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename={$file_name}");
        header("Content-Transfer-Encoding: binary ");

        $objPHPExcel = new PHPExcel();

        //--------------------------------------------------//
        $rowc = 1;
        $colc = 0;
        $appendSql = '';
        $actSheet = &$objPHPExcel->getActiveSheet();

        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Product Code');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Product Title');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Cost Price');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Quantity');

        /*if ($cpCfg['cp.hasMultiUniqueSites']){
            $SQLSite = "
            SELECT title
            FROM site
            ";
            $resultSite = $db->sql_query($SQLSite);
            while ($rowSite = $db->sql_fetchrow($resultSite)) {
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $rowSite['title']);
            }
        }*/

        /******************** FORMAT HEADER *******************/
        $headStyle = array(
            'font' => array('bold' => true)
        );

        $lastCol    = $actSheet->getHighestColumn();
        $lastColInd = PHPExcel_Cell::columnIndexFromString($lastCol);
        $actSheet->getStyle("A1:{$lastCol}1")->applyFromArray($headStyle);

        for ($i=0; $i < $lastColInd; $i++){
            $colAlphabet = PHPExcel_Cell::stringFromColumnIndex($i);
            $actSheet->getColumnDimension($colAlphabet)->setAutoSize(true);
        }

        //foreach ($dataArray as $row){
            //$colc = 0;
            //$rowc++;

            $SQLProduct = "
                SELECT title
                       ,product_code
                       ,price
                       ,qty_in_stock
                FROM product
                ";
                $resultProduct = $db->sql_query($SQLProduct);
                while ($rowProduct    = $db->sql_fetchrow($resultProduct)) {

                $colc = 0;
                $rowc++;

                    $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $rowProduct['product_code']);
                    $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $rowProduct['title']);
                    $actSheet->setCellValueByColumnAndRow($colc++, $rowc);
                    $actSheet->setCellValueByColumnAndRow($colc++, $rowc);
                }

            /*if ($cpCfg['cp.hasMultiUniqueSites']){
                $SQLSite = "
                SELECT title
                       ,site_id
                FROM site
                ";
                $resultSite = $db->sql_query($SQLSite);
                while ($rowSite = $db->sql_fetchrow($resultSite)) {
                    $SQLProdPrice = "
                    SELECT price AS prod_price
                    FROM product_price
                    WHERE product_id = {$row['product_id']}
                    AND site_id = {$rowSite['site_id']}
                    ";
                    $resultProdPrice = $db->sql_query($SQLProdPrice);
                    while ($rowProdPrice = $db->sql_fetchrow($resultProdPrice)) {
                        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $rowProdPrice['prod_price']);
                    }
                }
            }*/

        //}

        $colc = 0;
        $rowc++;

        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');

        $rowc++;

        $actSheet->getStyle("A{$rowc}:F{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        ob_end_clean();
        $objWriter->save('php://output');
    }

    /**
     *
     */
    function getImportData(){
        $phpExcel = includeCPClass('Lib', 'PhpExcelImportWrapper');
        $db = Zend_Registry::get('db');

        /*
        STOCK - qty_in_stock - qty
        PRODUCT - title
        Item Code - item_code
        Category - category_id
        FC from China - fc_price
        Purchase Cost from BLOSSOMS - price
        Product Weight - product_weight
        VAT% - vat_percentage
        Price per KG - weight_per_kg
        Product Display Price - selling_price
        Add Shipping Cost - logistics
        Comission Calculation Price (Less VAT & Logistics) - agent_price
        TC Comsn % ( 5% - 20%) - commission
        */

        $fa = array(
              'title' => $phpExcel->getImportFldObj('PRODUCT NAME')
             ,'purchase_order_date' => $phpExcel->getImportFldObj('Purchase Date')
             ,'hsn'            => $phpExcel->getImportFldObj('HSN CODE')
             ,'qty'            => $phpExcel->getImportFldObj('Qty')
             ,'unit'           => $phpExcel->getImportFldObj('Units')
             ,'gst'            => $phpExcel->getImportFldObj('GST%')
             ,'selling_price'  => $phpExcel->getImportFldObj('Selling Price')
             ,'cost_price'     => $phpExcel->getImportFldObj('Cost Price')
             ,'category'       => $phpExcel->getImportFldObj('Category')
             ,'supplier'       => $phpExcel->getImportFldObj('Supplier')
             //,'discount'       => $phpExcel->getImportFldObj('Discount%')
             ,'product_weight' => $phpExcel->getImportFldObj('Weight')
             ,'bar_code' => $phpExcel->getImportFldObj('BAR CODE NO')
             
             //,'item_code' => $phpExcel->getImportFldObj('ITEM CODE')
             //,'price'          => $phpExcel->getImportFldObj('COST')
             //,'weight_per_kg'  => $phpExcel->getImportFldObj('WT / KG')
        );

        //$fa['po_code']['defaultValue'] = $this->getUpdatePOCode();
        //$fa['item_code']['refOnly'] = true;
        //$fa['price']['refOnly'] = true;
        //$fa['weight_per_kg']['refOnly'] = true;
        //$fa['supplier']['refOnly'] = true;
        /*$fa['color_size_qty1']['refOnly'] = true;
        $fa['color_size_qty2']['refOnly'] = true;*/

        $fa['title']['refOnly']         = true;
        $fa['gst']['refOnly']           = true;
        $fa['selling_price']['refOnly'] = true;
        $fa['cost_price']['refOnly']    = true;
        $fa['category']['refOnly']      = true;
        $fa['qty']['refOnly']           = true;
        $fa['hsn']['refOnly']           = true;
        $fa['product_weight']['refOnly'] = true;
        $fa['unit']['refOnly']      = true;
        $fa['bar_code']['refOnly']      = true;

        /****************************************/
        $config = array(
             'module'              => 'tradingsg_purchaseOrder'
            ,'matchFieldArr'       => array('purchase_order_date','supplier')
            ,'fldsArr'             => $fa
            ,'callbackAfterInsert' => 'importDataRowCallback'
        );

        return $phpExcel->importData($config);
    }

    /**
     *
     */
    function importDataRowCallback($purchase_order_id, $fa) {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        //$price = $fa['price'];
        //$item_code = $fa['item_code'];
        //$weight_per_kg = $fa['weight_per_kg'];
        //$supplier = $fa['supplier'];
        //$description = $fa['description'];
        /*$color_size_qty1 = $fa['color_size_qty1'];
        $color_size_qty2 = $fa['color_size_qty2'];*/

        $product_weight = $fa['product_weight'];
        $gst             = $fa['gst'];
        $selling_price   = $fa['selling_price'];
        $cost_price      = $fa['cost_price'];
        $title           = $fa['title'];
        $category        = $fa['category'];
        $qty             = $fa['qty'];
        $hsn             = $fa['hsn'];
        $unit            = $fa['unit'];
        $bar_code        = $fa['bar_code'];
        $color_size_qty1 = '';
        $color_size_qty2 = '';

        if($gst == ''){
            $gst = '0.00';
        }
        if($cost_price == ''){
            $cost_price = '0.00';
        }
        if($selling_price == ''){
            $selling_price = '0.00';
        }
        if($qty == ''){
            $qty = '0';
        }

        $poRec    = $fn->getRecordRowById('purchase_order', 'purchase_order_id', $purchase_order_id);
        $supplier = $poRec['supplier'];

        /*$sqlcount1 = "
        SELECT COUNT(*)
        FROM `section`
        WHERE title = '{$section}'
        ";
        $resultcount1 = $db->sql_query($sqlcount1);
        $secRecCount    = $db->sql_fetchrow($resultcount1);

        if ($secRecCount == 0 && $section != '') {
            $fa1 = array();
            $fa1['title'] = $section;
            $fa1['published'] = 1;
            $fa1['section_type'] = 'Product';

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa1, 'section');
            $result = $db->sql_query($SQL);
            $section_id  = $db->sql_nextid();
        } else {
            $sqlsec = "
            SELECT section_id
                   ,title FROM section
                   WHERE title = '{$section}'
            ";
            $resultsec = $db->sql_query($sqlsec);
            $secRec    = $db->sql_fetchrow($resultsec);

            $section_id  = $secRec['section_id'];
        }*/

        $sqlcount3 = "
        SELECT title
        FROM `category`
        WHERE title = '{$category}'
        ";
        $resultcount3 = $db->sql_query($sqlcount3);
        $catRecCount    = $db->sql_fetchrow($resultcount3);

        if ($catRecCount['title'] == '' && $category != '') {
            $fa1 = array();
            $fa1['title']         = $category;
            $fa1['published']     = 1;
            $fa1['section_id']    = 13;
            $fa1['category_type'] = 'Product';

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa1, 'category');
            $result = $db->sql_query($SQL);
            $category_id  = $db->sql_nextid();
        } else {
            $sqlcat = "
            SELECT category_id
                   ,title FROM category
                   WHERE title = '{$category}'
            ";
            $resultcat = $db->sql_query($sqlcat);
            $catRec    = $db->sql_fetchrow($resultcat);

            $category_id  = $catRec['category_id'];
        }

        $sqlcount2 = "
        SELECT COUNT(*) AS supplier_count
        FROM `supplier`
        WHERE company_name = '{$supplier}'
        ";
        $resultcount2 = $db->sql_query($sqlcount2);
        $supRecCount    = $db->sql_fetchrow($resultcount2);

        if ($supRecCount['supplier_count'] == 0 && $supplier != '') {
            $fa1 = array();
            $fa1['company_name'] = $supplier;

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa1, 'supplier');
            $result = $db->sql_query($SQL);
            $supplier_id  = $db->sql_nextid();
        } else {
            $sqlsup = "
            SELECT supplier_id
                   ,company_name FROM supplier
                   WHERE company_name = '{$supplier}'
            ";
            $resultsup = $db->sql_query($sqlsup);
            $supRec    = $db->sql_fetchrow($resultsup);

            $supplier_id  = $supRec['supplier_id'];
        }

        //$fa2['product_code'] = $image_code;
        //$fa2['weight_per_kg']  = $weight_per_kg;
        //$fa2['description']  = $description;
        //$fa2['actual_price']  = $actual_price;
        $fa2 = array();
        $fa2['qty_in_stock'] = $qty;
        $fa2['product_weight']  = $product_weight;
        $fa2['title']        = $title;
        $fa2['price']        = $selling_price;
        $fa2['category_id']  = $category_id;
        $fa2['supplier_id']  = $supplier_id;
        $fa2['gst']          = $gst;
        $fa2['published']    = 1;
        $fa2['hsn']          = $hsn;
        $fa2['unit']         = $unit;
        $fa2['bar_code']     = $bar_code;
        $fa2['item_code']    = $this->getUpdateProductCode();
        $fa2['product_type'] = 'Purchasing and Selling';
        if($color_size_qty1 != ''){
            $fa2['color']    = 1;
            $fa2['product_size']  = 1;
            $fa2['model']    = 1;
        }

        //$fa2['discount_type']       = '%'; 
        //$fa2['discount_percentage'] = $discount;
        $fa2 = $fn->addCreationDetailsToFieldsArray($fa2, 'product');

        $SQL = $dbUtil->getInsertSQLStringFromArray($fa2, 'product');
        $result = $db->sql_query($SQL);
        $product_id  = $db->sql_nextid();

        $poCode = $this->getUpdatePOCode();

        $SQLPOUpdate = "UPDATE purchase_order set company_id_supplier = {$supplier_id}, po_code = '{$poCode}' WHERE purchase_order_id = {$purchase_order_id}";
        $resultPOUpdate = $db->sql_query($SQLPOUpdate);

        $fa1 = array();
        $fa1['company_id']      = $supplier_id;
        $fa1['product_id']      = $product_id;
        $fa1['creation_date']   = date('Y-m-d H:i:s');

        $insert = $dbUtil->getInsertSQLStringFromArray($fa1, 'product_company');
        $result1 = $db->sql_query($insert);

        if($color_size_qty1 != ''){
            $this->getCreateColorAndSizeRecords($color_size_qty1, $product_id);
        }
        if($color_size_qty2 != ''){
            $this->getCreateColorAndSizeRecords($color_size_qty2, $product_id);
        }

        $SQLpcs = "
        SELECT *
        FROM product_color_by_size
        WHERE product_id = {$product_id}
        ";
        $resultpcs = $db->sql_query($SQLpcs);
        $numRowspcs = $db->sql_numrows($resultpcs);

        $SQLpc = "
        SELECT *
        FROM product_color
        WHERE product_id = {$product_id}
        ";
        $resultpc = $db->sql_query($SQLpc);
        $numRowspc = $db->sql_numrows($resultpc);

        $SQLps = "
        SELECT *
        FROM product_size
        WHERE product_id = {$product_id}
        ";
        $resultps = $db->sql_query($SQLps);
        $numRowsps = $db->sql_numrows($resultps);

        $SQLpm = "
        SELECT *
        FROM product_model
        WHERE product_id = {$product_id}
        ";
        $resultpm = $db->sql_query($SQLpm);
        $numRowspm = $db->sql_numrows($resultpm);

        while ($rowpm = $db->sql_fetchrow($resultpm)) {
            //$fa3['price']  = $price;
            //$fa3['product_weight']  = $product_weight;
            //$fa3['weight_per_kg']  = $weight_per_kg;
            $fa3 = array();
            $fa3['product_id']          = $product_id;
            $fa3['purchase_order_id']   = $purchase_order_id;
            $fa3['qty']                 = $rowpm['qty'];
            $fa3['qty_requested']       = $rowpm['qty'];
            $fa3['gst']                 = $gst;
            $fa3['logistics']           = $logistics;
            $fa3['selling_price']       = $selling_price;
            $fa3['agent_price']         = $agent_price;
            $fa3['commission']          = $commission;
            $fa3['color_size_code']     = $rowpm['code'];
            //$fa3['discount_type']       = '%'; 
            //$fa3['discount_percentage'] = $discount;
            $fa3 = $fn->addCreationDetailsToFieldsArray($fa3, 'po_product');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa3, 'po_product');
            $result = $db->sql_query($SQL);
        }

        if($numRowspcs > 0){
            while ($rowpcs = $db->sql_fetchrow($resultpcs)) {
                //$fa3['price']  = $price;
                //$fa3['product_weight']  = $product_weight;
                //$fa3['weight_per_kg']  = $weight_per_kg;
                $fa3 = array();
                $fa3['product_id']          = $product_id;
                $fa3['purchase_order_id']   = $purchase_order_id;
                $fa3['qty']                 = $rowpcs['qty'];
                $fa3['qty_requested']       = $rowpcs['qty'];
                $fa3['gst']                 = $gst;
                $fa3['logistics']           = $logistics;
                $fa3['selling_price']       = $selling_price;
                $fa3['agent_price']         = $agent_price;
                $fa3['commission']          = $commission;
                $fa3['color_size_code']     = $rowpcs['code'];
                //$fa3['discount_type']       = '%'; 
                //$fa3['discount_percentage'] = $discount;
                $fa3 = $fn->addCreationDetailsToFieldsArray($fa3, 'po_product');

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa3, 'po_product');
                $result = $db->sql_query($SQL);
            }
        } else if($numRowspc > 0 || $numRowsps > 0){
            while ($rowpc = $db->sql_fetchrow($resultpc)) {
                //$fa3['price']  = $price;
                //$fa3['product_weight']  = $product_weight;
                //$fa3['weight_per_kg']  = $weight_per_kg;
                $fa3 = array();
                $fa3['product_id']          = $product_id;
                $fa3['purchase_order_id']   = $purchase_order_id;
                $fa3['qty']                 = $rowpc['qty'];
                $fa3['qty_requested']       = $rowpc['qty'];
                $fa3['gst']                 = $gst;
                $fa3['logistics']           = $logistics;
                $fa3['selling_price']       = $selling_price;
                $fa3['agent_price']         = $agent_price;
                $fa3['commission']          = $commission;
                $fa3['color_size_code']     = $rowpc['code'];
                //$fa3['discount_type']       = '%'; 
                //$fa3['discount_percentage'] = $discount;
                $fa3 = $fn->addCreationDetailsToFieldsArray($fa3, 'po_product');

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa3, 'po_product');
                $result = $db->sql_query($SQL);
            }

            while ($rowps = $db->sql_fetchrow($resultps)) {
                //$fa3['price']  = $price;
                //$fa3['product_weight']  = $product_weight;
                //$fa3['weight_per_kg']  = $weight_per_kg;
                $fa3 = array();
                $fa3['product_id']          = $product_id;
                $fa3['purchase_order_id']   = $purchase_order_id;
                $fa3['qty']                 = $rowps['qty'];
                $fa3['qty_requested']       = $rowps['qty'];
                $fa3['gst']                 = $gst;
                $fa3['logistics']           = $logistics;
                $fa3['selling_price']       = $selling_price;
                $fa3['agent_price']         = $agent_price;
                $fa3['commission']          = $commission;
                $fa3['color_size_code']     = $rowps['code'];
                //$fa3['discount_type']       = '%'; 
                //$fa3['discount_percentage'] = $discount;
                $fa3 = $fn->addCreationDetailsToFieldsArray($fa3, 'po_product');

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa3, 'po_product');
                $result = $db->sql_query($SQL);
            }

        } else {
            //$fa3['price']  = $price;
            //$fa3['weight_per_kg']  = $weight_per_kg;
            $fa3 = array();
            $fa3['product_id']          = $product_id;
            $fa3['purchase_order_id']   = $purchase_order_id;
            $fa3['qty']                 = $qty;
            $fa3['qty_requested']       = $qty;
            $fa3['gst']                 = $gst;
            $fa3['selling_price']       = $selling_price;
            $fa3['cost_price']          = $cost_price;
            $fa3['product_weight']  = $product_weight;
            //$fa3['discount_type']       = '%'; 
            //$fa3['discount_percentage'] = $discount;
            $fa3 = $fn->addCreationDetailsToFieldsArray($fa3, 'po_product');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa3, 'po_product');
            $result = $db->sql_query($SQL);
        }

            /*$invRec = $fn->getRecordByCondition('inventory', "product_id = '{$product_id}'");
            $fa4 = array();
            $fa4['product_id'] = $product_id;
            $fa4['actual_stock'] = $invRec['actual_stock'] + $qty;
            $fa4 = $fn->addCreationDetailsToFieldsArray($fa4, 'inventory');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa4, 'inventory');
            $result = $db->sql_query($SQL);*/
    }

    /**
     *
     */
    function getCreateColorAndSizeRecords($color_size_qty, $product_id) {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $csq_arr = explode('-', $color_size_qty);
        $color = $csq_arr[0];
        $size = $csq_arr[1];
        $model = $csq_arr[2];
        $csqty = $csq_arr[3];

        $colorVal = ltrim($color,"C");
        $sizeVal = ltrim($size,"S");
        $modelVal = ltrim($model,"M");
        $csqtyVal = ltrim($csqty,"Q");

        if($colorVal != ''){
            $fa = array();
            $fa['product_id'] = $product_id;
            $fa['color'] = $colorVal;
            $fa['qty']  = $csqtyVal;
            $fa['code']  = $this->getUpdateColorCode();
            $fa = $fn->addCreationDetailsToFieldsArray($fa, 'product_color');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'product_color');
            $result = $db->sql_query($SQL);
            $product_color_id = $db->sql_nextid();
        }

        if($sizeVal != ''){
            $fa = array();
            $fa['product_id'] = $product_id;
            $fa['size'] = $sizeVal;
            $fa['qty']  = $csqtyVal;
            $fa['code']  = $this->getUpdateSizeCode();
            $fa = $fn->addCreationDetailsToFieldsArray($fa, 'product_size');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'product_size');
            $result = $db->sql_query($SQL);
            $product_size_id = $db->sql_nextid();
        }

        if($modelVal != ''){
            $fa = array();
            $fa['product_id'] = $product_id;
            $fa['model'] = $modelVal;
            $fa['qty']  = $csqtyVal;
            $fa['code']  = $this->getUpdateModelCode();
            $fa = $fn->addCreationDetailsToFieldsArray($fa, 'product_model');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'product_model');
            $result = $db->sql_query($SQL);
            $product_model_id = $db->sql_nextid();
        }

        if($colorVal != '' && $sizeVal != ''){
            $fa = array();
            $fa['product_id'] = $product_id;
            $fa['product_color_id'] = $product_color_id;
            $fa['product_size_id']  = $product_size_id;
            $fa['qty']  = $csqtyVal;
            $fa['code']  = $this->getUpdateColorSizeCode();
            $fa = $fn->addCreationDetailsToFieldsArray($fa, 'product_color_by_size');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'product_color_by_size');
            $result = $db->sql_query($SQL);
        }
    }

    /**
     *
     */
    function getImportDataUpdate(){
        $phpExcel = includeCPClass('Lib', 'PhpExcelImportWrapper');
        $db = Zend_Registry::get('db');

        /*
        STOCK - qty_in_stock - qty
        PRODUCT - title
        Item Code - item_code
        Category - category_id
        FC from China - fc_price
        Purchase Cost from BLOSSOMS - price
        Product Weight - product_weight
        VAT% - vat_percentage
        Price per KG - weight_per_kg
        Product Display Price - selling_price
        Add Shipping Cost - logistics
        Comission Calculation Price (Less VAT & Logistics) - agent_price
        TC Comsn % ( 5% - 20%) - commission
        */

        $fa = array(
              'title' => $phpExcel->getImportFldObj('PRODUCT')
             ,'purchase_order_date' => $phpExcel->getImportFldObj('Purchase Date')
             ,'item_code' => $phpExcel->getImportFldObj('ITEM CODE')
             ,'inventory_code' => $phpExcel->getImportFldObj('Inventory Code')
             ,'inventory_id' => $phpExcel->getImportFldObj('Inventory Id')
             ,'code' => $phpExcel->getImportFldObj('Code')
             ,'color' => $phpExcel->getImportFldObj('Color')
             ,'size'  => $phpExcel->getImportFldObj('Size')
             ,'model'  => $phpExcel->getImportFldObj('Model')
             ,'stock' => $phpExcel->getImportFldObj('Stock')
             ,'product_id' => $phpExcel->getImportFldObj('Product Id')
        );

        $fa['po_code']['defaultValue'] = $this->getUpdatePOCode();
        $fa['title']['refOnly'] = true;
        $fa['item_code']['refOnly'] = true;
        $fa['inventory_code']['refOnly'] = true;
        $fa['inventory_id']['refOnly'] = true;
        $fa['color']['refOnly'] = true;
        $fa['size']['refOnly'] = true;
        $fa['model']['refOnly'] = true;
        $fa['stock']['refOnly'] = true;
        $fa['product_id']['refOnly'] = true;
        $fa['code']['refOnly'] = true;

        /****************************************/
        $config = array(
             'module'              => 'tradingsg_purchaseOrder'
            ,'matchFieldArr'       => array('purchase_order_date')
            ,'fldsArr'             => $fa
            ,'callbackAfterInsert' => 'importDataRowCallbackForStock'
        );

        return $phpExcel->importData($config);
    }

    /**
     *
     */
    function importDataRowCallbackForStock($purchase_order_id, $fa) {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        $stock = $fa['stock'];
        $item_code = $fa['item_code'];
        $title = $fa['title'];
        $inventory_code = $fa['inventory_code'];
        $code = $fa['code'];
        $color = $fa['color'];
        $size = $fa['size'];
        $model = $fa['model'];
        $product_id = $fa['product_id'];
        $inventory_id = $fa['inventory_id'];

        //$productRec  = $fn->getRecordRowByID('product', 'product_id', $product_id);


        //if (is_numeric ($product_id) && $recCount == 0) {
            $fa3 = array();
            $fa3['product_id'] = $product_id;
            $fa3['inventory_id'] = $inventory_id;
            $fa3['purchase_order_id']  = $purchase_order_id;
            $fa3['qty']  = $stock;
            $fa3['qty_requested']  = $stock;
            $fa3['color_size_code']  = $code;
            $fa3 = $fn->addCreationDetailsToFieldsArray($fa3, 'po_product');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa3, 'po_product');
            $result = $db->sql_query($SQL);
        //}

        if($color != ''){
            $productColorChk = $fn->getRecordByCondition('product_color', "color = '{$color}' AND product_id = '{$product_id}'");
            if($productColorChk){
                //$productColorRec = $fn->getRecordByCondition('product_color', "code = '{$code}'");
                $qty = $productColorChk['qty'] + $stock;
                $SQLUpdate ="
                UPDATE product_color SET qty = {$qty}
                WHERE product_color_id = {$productColorChk['product_color_id']}";
                $resultUpdate = $db->sql_query($SQLUpdate);
                $product_color_id = $productColorChk['product_color_id'];
            } else {
                $fa = array();
                $fa['product_id'] = $product_id;
                $fa['color'] = $color;
                $fa['qty']  = $stock;
                $fa = $fn->addCreationDetailsToFieldsArray($fa, 'product_color');

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'product_color');
                $result = $db->sql_query($SQL);
                $product_color_id = $db->sql_nextid();
            }
        }

        if($size != ''){
            $productSizeChk = $fn->getRecordByCondition('product_size', "size = '{$size}' AND product_id = '{$product_id}'");
            if($productSizeChk){
                //$productSizeRec = $fn->getRecordByCondition('product_size', "code = '{$code}'");
                $qty = $productSizeChk['qty'] + $stock;
                $SQLUpdate ="
                UPDATE product_size SET qty = {$qty}
                WHERE product_size_id = {$productSizeChk['product_size_id']}";
                $resultUpdate = $db->sql_query($SQLUpdate);
                $product_size_id = $productSizeChk['product_size_id'];
            } else {
                $fa = array();
                $fa['product_id'] = $product_id;
                $fa['size'] = $size;
                $fa['qty']  = $stock;
                $fa = $fn->addCreationDetailsToFieldsArray($fa, 'product_size');

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'product_size');
                $result = $db->sql_query($SQL);
                $product_size_id = $db->sql_nextid();
            }

        }

        if($model != ''){
            $productModelChk = $fn->getRecordByCondition('product_model', "model = '{$model}' AND product_id = '{$product_id}'");
            if($productModelChk){
                $qty = $productModelChk['qty'] + $stock;
                $SQLUpdate ="
                UPDATE product_model SET qty = {$qty}
                WHERE product_model_id = {$productModelChk['product_model_id']}";
                $resultUpdate = $db->sql_query($SQLUpdate);
                $product_model_id = $productModelChk['product_model_id'];
            } else {
                $fa = array();
                $fa['product_id'] = $product_id;
                $fa['model'] = $model;
                $fa['qty']  = $stock;
                $fa = $fn->addCreationDetailsToFieldsArray($fa, 'product_model');

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'product_model');
                $result = $db->sql_query($SQL);
                $product_model_id = $db->sql_nextid();
            }

        }

        if($color != '' && $size != ''){
            $productColorSizeChk = $fn->getRecordByCondition('product_color_by_size', "product_color_id = '{$product_color_id}' AND product_size_id = '{$product_size_id}' AND product_id = '{$product_id}'");
            if($productColorSizeChk){
                //$productColorSizeRec = $fn->getRecordByCondition('product_color_by_size', "code = '{$code}'");
                $qty = $productColorSizeChk['qty'] + $stock;
                $SQLUpdate ="
                UPDATE product_color_by_size SET qty = {$qty}
                WHERE product_color_by_size_id = {$productColorSizeChk['product_color_by_size_id']}";
                $resultUpdate = $db->sql_query($SQLUpdate);
            } else {
                $fa = array();
                $fa['product_id'] = $product_id;
                $fa['product_color_id'] = $product_color_id;
                $fa['product_size_id']  = $product_size_id;
                $fa['qty']  = $stock;
                $fa = $fn->addCreationDetailsToFieldsArray($fa, 'product_color_by_size');

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'product_color_by_size');
                $result = $db->sql_query($SQL);
            }
        }
            /*$invRec = $fn->getRecordByCondition('inventory', "product_id = '{$product_id}'");
            $fa4 = array();
            $fa4['product_id'] = $product_id;
            $fa4['actual_stock'] = $invRec['actual_stock'] + $qty;
            $fa4 = $fn->addCreationDetailsToFieldsArray($fa4, 'inventory');

            $SQL = $dbUtil->getInsertSQLStringFromArray($fa4, 'inventory');
            $result = $db->sql_query($SQL);*/
    }

    /**
     *
     */
    function getUpdateColorCode() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $SQL = "
        SELECT code
        FROM product_color
        ORDER BY product_color_id DESC
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);
        $count = 1;

        $color_code = ltrim($row['code'],"C");
        $colorCode = 'C'.$color_code + 1;

        return $colorCode;
    }

    /**
     *
     */
    function getUpdateSizeCode() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $SQL = "
        SELECT code
        FROM product_size
        ORDER BY product_size_id DESC
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);
        $count = 1;

        $size_code = ltrim($row['code'],"S");
        $sizeCode = 'S'.$size_code + 1;

        return $sizeCode;
    }

    /**
     *
     */
    function getUpdateModelCode() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $SQL = "
        SELECT code
        FROM product_model
        ORDER BY product_model_id DESC
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);
        $count = 1;

        $model_code = ltrim($row['code'],"M");
        $modelCode = 'M'.$model_code + 1;

        return $modelCode;
    }

    /**
     *
     */
    function getUpdateColorSizeCode() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $SQL = "
        SELECT code
        FROM product_color_by_size
        ORDER BY product_color_by_size_id DESC
        ";
        $result = $db->sql_query($SQL);
        $row = $db->sql_fetchrow($result);
        $count = 1;

        $size_code = ltrim($row['code'],"CS");
        $sizeCode = 'CS'.$size_code + 1;

        return $sizeCode;
    }

    /**
     *
     */
    function getUpdatePOCodeOld() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        /* Updation of Purchase order Code */

        $poCode = $fn->getSettingsValueByKey("nextPurchaseOrderCode");

        $POCode = $fn->getSettingsValueByKey('poCodePrefix') . $poCode;

        $SQL    = "UPDATE setting SET value = (value+1) WHERE key_text = 'nextPurchaseOrderCode'";
        $result = $db->sql_query($SQL);

        return $POCode;
    }

    /**
     *
     */
    function getUpdatePOCode() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

        /* Updation of Purchase order Code */
        $site_id = $fn->getSessionParam('cp_site_id');

        $appendSql = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "AND site_id = {$site_id}";
        }

        /*$SQLSite = "
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
        }*/

        $SQLPoCode = "
        SELECT MAX(CONVERT(po_code, UNSIGNED INTEGER)) AS po_code
        FROM `purchase_order`
        WHERE status != 'Cancelled'
        {$appendSql}
        ";
        $resultPoCode = $db->sql_query($SQLPoCode);
        $rowPoCode    = $db->sql_fetchrow($resultPoCode);
        $po_code      = $rowPoCode['po_code'] + 1;

        if($rowPoCode['po_code'] == "" || $rowPoCode['po_code'] == 0) {
            $po_code = "1000";
        }
        else {
            $po_code = $po_code;
        }

        return $po_code;
    }

    /**
     *
     */
    function getUpdateProductCode() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        /* Updation of Product Code */
        $nextProductItemCode = $fn->getSettingsValueByKey("nextProductItemCode");

        if($nextProductItemCode < 10){
            $ProCode = '000' . $nextProductItemCode;
        }
        else if($nextProductItemCode < 99){
            $ProCode = '00' . $nextProductItemCode;
        }
        else if($nextProductItemCode < 999){
            $ProCode = '0' . $nextProductItemCode;
        }
        else{
            $ProCode = $nextProductItemCode;
        }

        $SQL    = "UPDATE setting SET value = (value+1) WHERE key_text = 'nextProductItemCode'";
        $result = $db->sql_query($SQL);

        return $ProCode;
    }
    /**
     *
     */

    function getPrintPOtoPDF() {
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
        $pdf->SetSubject('Print Link');
        $pdf->SetTitle('Print Link');

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 04', PDF_HEADER_STRING);
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins('8', PDF_MARGIN_TOP, '8');
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER,10);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        /*HEADER PART AND FOOTER PART FUNCTIONS HAS BEEN ADDED IN (headfoot.php) PATH INCLUDE: (admin/lib/headfoot.php)*/
        $pdf->AddPage('P', 'A5');

        $purchase_order_id = $fn->getReqParam('purchase_order_id');

        $appendSqlSiteStock = '';
            if($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSqlSiteStock = "AND site_id = {$cpSiteIdSession}";
            }

        $SQL = "
        SELECT p.*
               ,pop.qty as po_QTY
               ,pop.qty_delivered
               ,(pop.qty - pop.qty_delivered) AS qty_balance
               ,(pop.qty*pop.cost_price) AS total_price
               ,pop.status
               ,pop.price AS cost_price
               ,pop.cost_price
               ,pop.selling_price
               ,pop.batch_no
               ,pop.expiry_date
               ,pop.pack_size
               ,po.purchase_order_date
               ,po.po_code
               ,ibs.current_stock
               ,po.purchase_order_id
               ,po.supplier_inv_code
               ,m.address_flat
               ,m.address_street
               ,m.address_town
               ,m.address_state
               ,m.address_country
               ,m.company_name
        FROM purchase_order po
        LEFT JOIN po_product pop ON po.purchase_order_id = pop.purchase_order_id
        LEFT JOIN inventory_batchwise_stock ibs ON pop.po_product_id = ibs.po_product_id
        LEFT JOIN product p ON p.product_id = pop.product_id
        LEFT JOIN supplier m ON m.supplier_id = po.company_id_supplier
        WHERE po.purchase_order_id = {$purchase_order_id}
        
        ";
        $result = $db->sql_query($SQL);
        $result2 = $db->sql_query($SQL);
        $company = $db->sql_fetchrow($result2);
        //============================================================================= //

        $pdf->SetFont('Courier','B',9);

        $today = date("d-m-Y");
        $purchase_order_date = $fn->getCPDate($company['purchase_order_date'], 'd/m/Y');

        $po_code = '';
        if($company['po_code'] != ''){
            $po_code = $company['po_code'];
        }

        $tbl1 = '
        <table border="0" width="100%" style="font-size:15px;">
            <tr>
                <td align="center" style="font-weight:bold;">PURCHASE ORDER</td>
            </tr>
        </table>
        <table border="0" width="100%" style="">
            <tr>
                <br/>
                <td width="34%" style="text-align:left;">PO Code : '.$po_code.'
                </td>
                <td width="33%" style="text-align:left;">Invoice No : '.$company['supplier_inv_code'].'
                </td>
                <td width="33%" style="text-align:right;">
                    <span>DATE : '.$purchase_order_date.'</span>
                </td>
            </tr>
        </table>
        ';

        $address_flat    = $company['address_flat'];
        $address_street  = $company['address_street'];
        $address_town    = $company['address_town'];
        $address_state   = $company['address_state'];
        $address_country = $company['address_country'];

        $recCountry['name'] = '';
        if($address_country != ''){
            $recCountry = $fn->getRecordRowByID('geo_country', 'country_code', "'{$address_country}'");
        }

        $tbl2 ='<table border="1" width="100%" cellpadding="6" style="">
                    <tr>
                        <td width="100%" style="line-height:20px;"><br/>
                            <span><b>TO:</b> '.strtoupper($company['company_name']).'</span><br/>
                            <span>&nbsp;&nbsp;&nbsp;&nbsp;'.$address_flat.' '.$address_street.'</span><br/>
                            <span>&nbsp;&nbsp;&nbsp;&nbsp;'.$address_town.' - '.$address_state.'</span><br/>
                            <span>&nbsp;&nbsp;&nbsp;&nbsp;'.$recCountry['name'].'</span>
                        </td>
                    </tr>
                </table>
                ';

        $tbl3 ='<table border="1" width="100%" cellpadding="3" style="font-size:9px;">
                    <thead>
                        <tr>
                            <th width="7%">S.NO</th>
                            <th width="22%" style="text-align:center;">NAME</th>
                            <th width="10%" style="text-align:center;">RATE</th>
                            <th width="10%" style="text-align:center;">MRP</th>
                            <th width="10%" style="text-align:center;">CODE</th>
                            <th width="13%" style="text-align:center;">EXP DATE</th>
                            <th width="8%" style="text-align:center;">PACK</th>
                            <th width="8%" style="text-align:center;">QTY</th>
                            <th width="12%" style="text-align:center;">TOTAL AMT</th>
                        </tr>
                    </thead>
                    <tbody>
        ';

        $count = 1;
        while($row = $db->sql_fetchrow($result)){

           

            $SQLproduct    = "
            SELECT po.*
              po.product_id
                   ,po.price

            FROM  po_product po
            WHERE po.product_id = {$row['product_id']}
            AND po.purchase_order_id < {$row['purchase_order_id']}
            ORDER BY po.product_id DESC
            LIMIT 0,1
            ";

            $resultproduct = $db->sql_query($SQLproduct);
            $rowproduct    = $db->sql_fetchrow($resultproduct);

            $expiry_date = '';
            
            if($row['expiry_date'] != ""){
                $expiry_date = $fn->getCPDate($row['expiry_date'], 'd-m-Y');
            }

            $totalAmount = $row['qty_requested'] * $row['price'];
            $actualTotalAmount = $row['qty'] * $row['price'];

            $totalAmountFormatted       = number_format($totalAmount, 2);
            $actualTotalAmountFormatted = number_format($actualTotalAmount, 2);
            $unitPrice                  = number_format($row['price'], 2);


           

            $tbl3 = $tbl3.'<tr>
                                <td width="7%">'.$count.'</td>
                                <td width="22%">'.$row['title'].'</td>
                                <td width="10%" style="text-align:right;">'.$row['cost_price'].'</td>
                                <td width="10%" style="text-align:right;">'.$row['selling_price'].'</td>
                                <td width="10%" style="text-align:right;">'.$row['batch_no'].'</td>
                                <td width="13%" style="text-align:right;">'.$expiry_date.'</td>
                                <td width="8%"  style="text-align:right;">'.$row['pack_size'].'</td>
                                <td width="8%"  style="text-align:right;">'.$row['po_QTY'].'</td>
                                <td width="12%" style="text-align:right;">'.number_format($row['total_price'], 2).'</td>
                            </tr>
                           ';

            $count++;
        }

        $tbl3 = $tbl3.'</tbody></table>';

        $pdf->writeHTML($tbl1, true, false, false, false, '');
        $pdf->writeHTML($tbl2, true, false, false, false, '');
        $pdf->writeHTML($tbl3, true, false, false, false, '');
        $download_title = $company['po_code'].'-Purchase-Order'.date('Y-m-d').'.pdf';
        ob_start();
        $pdf->Output($download_title, 'I');
    }
    /**
     *
     */

    function getPrintPOwithpricetoPDF() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        ini_set('memory_limit', '512M');
        set_time_limit(50000);

        include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
        include_once(CP_LOCAL_PATH.'lib/headfoot.php');

        $pdf = new MYPDF_Local(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('USS');
        $pdf->SetSubject('Print Link');
        $pdf->SetTitle('Print Link');

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
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        /*HEADER PART AND FOOTER PART FUNCTIONS HAS BEEN ADDED IN (headfoot.php) PATH INCLUDE: (admin/lib/headfoot.php)*/
        $pdf->AddPage();

        $purchase_order_id = $fn->getReqParam('purchase_order_id');

        $SQL = "
        SELECT p.*
               ,pop.qty as po_QTY
               ,pop.qty_delivered
               ,(pop.qty - pop.qty_delivered) AS qty_balance
               ,pop.status
               ,pop.cost_price AS cost_price
               ,(pop.qty*pop.cost_price) AS total_price
               ,po.purchase_order_date
               ,po.po_code
               ,po.purchase_order_id
               ,m.company_name AS supplier_name
               ,m.address_flat
               ,m.address_street
               ,m.address_town
               ,m.address_state
               ,m.address_country
        FROM purchase_order po
        LEFT JOIN po_product pop ON po.purchase_order_id = pop.purchase_order_id
        LEFT JOIN product p ON p.product_id = pop.product_id
        LEFT JOIN supplier m ON m.supplier_id = po.company_id_supplier
        WHERE po.purchase_order_id = {$purchase_order_id}
        ";
        $result = $db->sql_query($SQL);
        $result2 = $db->sql_query($SQL);
        $company = $db->sql_fetchrow($result2);
        //============================================================================= //

        $pdf->SetFont('Courier','B',10);

        $today = date("d-m-Y");
        $purchase_order_date = $fn->getCPDate($company['purchase_order_date'], 'd/m/Y');
        $po_code = '';
        if($company['po_code'] != ''){
            $po_code = $company['po_code'];
        }

        $tbl1 = '
        <table border="0" width="100%" style="font-size:17px;">
            <tr>
                <td align="center" style="font-weight:bold;font-family:andalusb;">PURCHASE ORDER</td>
            </tr>
        </table>
        <table border="1" width="100%" style="font-size:15px;">
            <tr>
                <td width="50%" style="text-align:left;">
                    <span>PO Code : '.$po_code.'</span>
                </td>
                <td width="50%" style="text-align:right;">
                    <span>DATE : '.$purchase_order_date.'</span>
                </td>
            </tr>
        </table>
        ';

        $address_flat        = $company['address_flat'];
        $address_street        = $company['address_street'];
        $address_town    = $company['address_town'];
        $address_state   = $company['address_state'];
        $address_country = $company['address_country'];

        $recCountry['name'] = '';
        if($address_country != ''){
            $recCountry = $fn->getRecordRowByID('geo_country', 'country_code', "'{$address_country}'");
        }

        $tbl2 ='<table border="1" width="100%" cellpadding="6" style="font-size:15px;">
                    <tr>
                        <td width="100%" style="line-height:20px;"><br/>
                            <span><b>TO:</b> '.strtoupper($company['supplier_name']).'</span><br/>
                            <span>&nbsp;&nbsp;&nbsp;&nbsp;'.$address_flat.' '.$address_street.'</span><br/>
                            <span>&nbsp;&nbsp;&nbsp;&nbsp;'.$address_town.' - '.$address_state.'</span><br/>
                            <span>&nbsp;&nbsp;&nbsp;&nbsp;'.$recCountry['name'].'</span>
                        </td>
                    </tr>
                </table>
                ';

        $tbl3 ='<table border="1" width="100%" cellpadding="4" style="font-size:15px;">
                    <thead>
                        <tr>
                            <th width="10%">S.NO</th>
                            <th width="40%" style="text-align:center;">PRODUCT NAME</th>
                            <th width="10%" style="text-align:center;">QTY</th>
                            <th width="20%" style="text-align:center;">COST PRICE</th>
                            <th width="20%" style="text-align:center;">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
        ';

        $count = 1;
        $overallTotal = 0;
        while($row = $db->sql_fetchrow($result)){

            $SQLproduct    = "
            SELECT  po.product_id
                   ,po.price
            FROM  po_product po
            WHERE po.product_id = {$row['product_id']}
            AND po.purchase_order_id < {$row['purchase_order_id']}
            ORDER BY po.product_id DESC
            LIMIT 0,1
            ";

            $resultproduct = $db->sql_query($SQLproduct);
            $rowproduct    = $db->sql_fetchrow($resultproduct);

            $tbl3 = $tbl3.'<tr>
                                <td width="10%">'.$count.'</td>
                                <td width="40%">'.$row['title'].'</td>
                                <td width="10%" style="text-align:center;">'.$row['po_QTY'].'</td>
                                <td width="20%" style="text-align:right;">'.$row['cost_price'].'</td>
                                <td width="20%" style="text-align:right;">'.number_format($row['total_price'], 2).'</td>
                            </tr>
                           ';

            $overallTotal += $row['total_price'];

            $count++;
        }

        $tbl3 = $tbl3.'<tr>
                            <td colspan="4" style="text-align:right;">TOTAL AMOUNT</td>
                            <td style="text-align:right;">'.number_format($overallTotal, 2).'</td>
                        </tr>
                        ';

        $tbl3 = $tbl3.'</tbody></table>';

        $pdf->writeHTML($tbl1, true, false, false, false, '');
        $pdf->writeHTML($tbl2, true, false, false, false, '');
        $pdf->writeHTML($tbl3, true, false, false, false, '');
        $download_title = $company['po_code'].'-Purchase-Order'.date('Y-m-d').'.pdf';
        ob_start();
        $pdf->Output($download_title, 'I');
    }


    /**
     *
     */
    function getPrintPOtoExcel() {
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $media = Zend_Registry::get('media');

        //-----------------------------------------------------------------//
        include_once(CP_LIBRARY_PATH.'lib_php/tbs_us/tbs_class.php');
        include_once(CP_LIBRARY_PATH.'lib_php/tbs_us/plugins/tbs_plugin_opentbs.php');
        include_once(CP_LIBRARY_PATH.'lib_php/tbs_us/plugins/tbs_plugin_html.php');

        $TBS = new clsTinyButStrong;
        $TBS->Plugin(TBS_INSTALL, OPENTBS_PLUGIN);

        $purchase_order_id  = $fn->getReqParam('purchase_order_id');
        $cpSiteIdSession    = $fn->getSessionParam('cp_site_id');
        $template = 'PO-Product-Export.xlsx';
        $templatePath = $cpCfg['cp.localPath'].'lib/template/' . $template;
        $TBS->LoadTemplate($templatePath);
        $rnd_no = mt_rand();
        $file_name = 'Purchase-Order_' . $purchase_order_id . '_' . $rnd_no;
        $file_name = str_replace('.', '_' . date('Y-m-d') . '.', $file_name);

        $path = realpath($cpCfg['cp.mediaFolder']) . '\temp';
        $file_name_save = $path . '\\' . $file_name;
        $sourceFilePath = $file_name_save;
        $today =  date('d/m/Y');

        $SQL = "
        SELECT p.*
               ,pop.qty AS po_QTY
               ,pop.qty AS qty_delivered
               ,(pop.qty_requested - pop.qty) AS qty_balance
               ,pop.status
               ,pop.cost_price AS cost_price
               ,po.purchase_order_date
               ,po.po_code
               ,po.purchase_order_id
               ,m.company_name AS supplier_name
               ,m.address1
               ,m.address2
               ,m.address_town
               ,m.address_state
               ,m.address_country
        FROM purchase_order po
        LEFT JOIN po_product pop ON po.purchase_order_id = pop.purchase_order_id
        LEFT JOIN product p ON p.product_id = pop.product_id
        LEFT JOIN supplier m ON m.supplier_id = po.company_id_supplier
        WHERE po.purchase_order_id = {$purchase_order_id}
        ";
        $result = $db->sql_query($SQL);

        $serialNo       = 1;
        $arr            = array();
        $blkMain        = array();
        $blkProduct     = array();
        $blkQty         = array();
        $qty_delivered  = array();
        $blkSerialNo    = array();
        $qty_balance    = array();
        $blkItemCode    = array();
        $blkLastQPrice  = array();
        $blkcost_price  = array();
        $blktotal_price = array();
        $selling_price  = 0;
        $overallTotal   = 0;
        while ($row = $db->sql_fetchrow($result)) {
            //repeating rows of product values
            $arr1 = array('product_title' => $row['title']);
            $blkProduct[] = $arr1;

            /*$address1        = '';
            $address2        = '';
            $address_town    = '';
            $address_state   = '';
            $address_country = '';*/

            $address1        = $row['address1'];
            $address2        = $row['address2'];
            $address_town    = $row['address_town'];
            $address_state   = $row['address_state'];
            $address_country = $row['address_country'];


            $arr2 = array('qty' => $row['po_QTY']);
            $blkQty[] = $arr2;

            $arr3 = array('serial_no' => $serialNo);
            $blkSerialNo[] = $arr3;

            $arr4 = array('qty_delivered' => $row['qty_delivered']);
            $qty_delivered[] = $arr4;

            $arr5 = array('qty_balance' => $row['qty_balance']);
            $qty_balance[] = $arr5;

            $arr6 = array('item_code' => $row['item_code']);
            $blkItemCode[] = $arr6;

            $SQLproduct    = "
            SELECT  po.product_id
                   ,po.price
            FROM  po_product po
            WHERE po.product_id = {$row['product_id']}
            AND po.purchase_order_id < {$row['purchase_order_id']}
            ORDER BY po.product_id DESC
            LIMIT 0,1
            ";

            $resultproduct = $db->sql_query($SQLproduct);
            $rowproduct    = $db->sql_fetchrow($resultproduct);

            $arr7 = array('last_price' => $rowproduct['price']);
            $blkLastQPrice[] = $arr7;

            $arr8 = array('cost_price' => $row['cost_price']);
            $blkcost_price[] = $arr8;

            $arr9 = array('total_price' => number_format(($row['po_QTY'] * $row['cost_price']), 2));
            $blktotal_price[] = $arr9;

            $overallTotal += $row['po_QTY'] * $row['cost_price'];

            $po_date   = $fn->getCPDate($row['purchase_order_date'], 'd-m-Y');

            $recCountry['name'] = '';
            if($address_country != ''){
                $recCountry = $fn->getRecordRowByID('geo_country', 'country_code', "'{$address_country}'");
            }

            $arr['po_code']         = $row['po_code'];
            $arr['supplier_name']   = $row['supplier_name'];
            $arr['address_flat']    = $address1;
            $arr['address_street']  = $address2;
            $arr['address_town']    = $address_town;
            $arr['address_state']   = $address_state;
            $arr['address_country'] = $recCountry['name'];
            $arr['po_date']         = $po_date;
            $blkMain[] = $arr;

            $serialNo++;
        }

        $arr10 = array('overall_Total' => number_format($overallTotal, 2));
        $blkoverallTotal[] = $arr10;

        $TBS->MergeBlock('blkMain', $blkMain);
        $TBS->MergeBlock('blkProduct', $blkProduct);
        $TBS->MergeBlock('blkQty', $blkQty);
        $TBS->MergeBlock('blkcost_price', $blkcost_price);
        $TBS->MergeBlock('qty_delivered', $qty_delivered);
        $TBS->MergeBlock('blkSerialNo', $blkSerialNo);
        $TBS->MergeBlock('qty_balance', $qty_balance);
        $TBS->MergeBlock('blkItemCode', $blkItemCode);
        $TBS->MergeBlock('blkLastQPrice', $blkLastQPrice);
        $TBS->MergeBlock('blktotal_price', $blktotal_price);
        $TBS->MergeBlock('blkoverallTotal', $blkoverallTotal);
        $TBS->Show(OPENTBS_DOWNLOAD, $file_name);
    }

    /**
     *
     */
    function getSearchProductTitle() {
        $db      = Zend_Registry::get('db');
        $fn      = Zend_Registry::get('fn');
        $tv      = Zend_Registry::get('tv');
        $cpCfg   = Zend_Registry::get('cpCfg');
        $dbUtil  = Zend_Registry::get('dbUtil');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $supplier_id     = $fn->getReqParam('supplier_id');

        $title = $fn->getReqParam('term', '', true);
        $extractor = explode(" **** ", $title);

        $productTitle = $extractor[0];
        $suppCondition = '';

        if($supplier_id != ''){
            $suppCondition = "AND pc.company_id = {$supplier_id}";
        }

        /*$SQL = "
        SELECT p.title AS value
              ,p.title AS label
              ,i.inventory_code
              ,s.company_name
              ,s.supplier_id
              ,p.item_code
              ,p.price
              ,p.gst
              ,p.tag_no
              ,p.product_code
              ,(SELECT pp.cost_price FROM po_product pp
                WHERE pp.product_id = i.product_id
                ORDER BY pp.po_product_id desc
                LIMIT 0,1
                ) AS cost_price
              ,p.product_id AS id
              ,CONCAT_WS(' :: ', CONCAT('PROD-',p.item_code), p.title
              ,CONCAT('',p.product_weight, p.unit)
              ,p.price
              ,p.qty_in_stock
              ) AS label
        FROM inventory i
        LEFT JOIN product p ON(p.product_id = i.product_id)
        LEFT JOIN product_company pc ON(pc.product_id = p.product_id)
        LEFT JOIN supplier s ON(s.supplier_id = pc.company_id)
        WHERE (p.title LIKE '%{$productTitle}%'
        OR p.item_code LIKE '%{$productTitle}%')
        AND p.published = 1
        {$suppCondition}
        ORDER BY p.title
        ";*/
        //AND (p.product_type != 'Selling Product' OR p.product_type IS NULL)

        /*$SQL = "
        SELECT p.title AS value
              ,p.title AS label
              ,p.product_id AS id
              ,CONCAT_WS(' ** ', p.item_code, p.title, p.price, p.stock) AS label
        FROM product p
        WHERE (p.title LIKE '%{$productTitle}%'
        OR p.item_code LIKE '%{$productTitle}%')
        AND p.published = 1
        ORDER BY p.title
        ";*/

        $SQL = "
        SELECT p.title AS value
              ,p.title AS label
              ,p.product_id AS id
              ,CONCAT_WS(' **** ', p.title) AS label
              ,p.medicine_company_id AS medicine_company
              ,p.category_id AS category
        FROM product p
        WHERE (p.title LIKE '{$productTitle}%')
          AND p.published = 1
        ORDER BY p.title
        ";

        $result = $db->sql_query($SQL);

        $dataArray = $dbUtil->getResultsetAsArray($result);
        $arr = json_encode($dataArray);
        return $arr;
    }

    /**
     *
     */
    function getSearchMOLProductList() {
        $db      = Zend_Registry::get('db');
        $fn      = Zend_Registry::get('fn');
        $tv      = Zend_Registry::get('tv');
        $cpCfg   = Zend_Registry::get('cpCfg');
        $dbUtil  = Zend_Registry::get('dbUtil');
        $cpUtil  = Zend_Registry::get('cpUtil');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $formObj = Zend_Registry::get('formObj');

        $arr = array();
        $rows = '';

        $SQL = "
        SELECT p.title
              ,i.inventory_code
              ,i.actual_stock
              ,s.company_name
              ,s.supplier_id
              ,p.item_code
              ,p.price
              ,p.gst
              ,(SELECT pp.cost_price FROM po_product pp
                WHERE pp.product_id = i.product_id
                ORDER BY pp.po_product_id desc
                LIMIT 0,1
                ) AS cost_price
              ,p.product_id AS id
        FROM inventory i
        LEFT JOIN product p ON(p.product_id = i.product_id)
        LEFT JOIN product_company pc ON(pc.product_id = p.product_id)
        LEFT JOIN supplier s ON(s.supplier_id = pc.company_id)
        WHERE i.minimum_order_level{$cpSiteIdSession} < 50000
          AND i.minimum_order_level{$cpSiteIdSession} IS NOT NULL
        ORDER BY p.title
        ";
        $result = $db->sql_query($SQL);

        /*$dataArray = $dbUtil->getResultsetAsArray($result);
        $arr = json_encode($dataArray);
        return $arr;*/

        while ($row = $db->sql_fetchrow($result)) {
            $arr['title'] = $row['title'];
            $arr['id']    = $row['id'];
            $arr['price'] = $row['price'];
            $arr['cost_price'] = $row['cost_price'];
            $arr['gst']   = $row['gst'];
            $arr['inventory_code'] = $row['inventory_code'];
            $arr['item_code'] = $row['item_code'];

            $product = "
            <input type='text' value='{$row['title']}' id='poProduct' class='text poProductTitle' name='product_title_list[]'>
            <input type='hidden' name='product_id[]' class='product_id_hidden' value='{$row['id']}'>
            ";
            $price = "<input type='text' value='{$row['price']}' id='price' class='text lineItemDescription' name='price[]' disabled>";
            $costPrice = "<input type='text' value='{$row['cost_price']}' id='costPrice' class='text poCostPrice' name='cost_price[]' disabled>";
            $gst = "<input type='text' value='{$row['gst']}' id='gst' class='text poGst' name='gst[]' disabled>";
            $qty = "<input type='text' value='{$row['actual_stock']}' id='qty' class='text poQuantity' name='qty[]'>";
            $clear           = "<a href='#' class='clearPoProductItem'><u>Clear</u></a>";
            $inventory_code = "<div class='inventoryCode'>{$row['inventory_code']}</div>";
            $item_code = "<div class='itemCode'>{$row['item_code']}</div>";
            $supplier = "<div class='supplier'>{$row['company_name']}</div>
            <input type='hidden' name='supplier_id[]' class='supplier_id_hidden' value='{$row['supplier_id']}'>";

            $rows .= "
            <tr>
                <td class='productSize'>{$product}</td>
                <td>{$inventory_code}</td>
                <td>{$item_code}</td>
                <td>{$supplier}</td>
                <td class='qtySize'>{$qty}</td>
                <td class='priceSize'>{$costPrice}</td>
                <td class='priceSize'>{$price}</td>
                <td class='qtySize'>{$gst}</td>
                <td>{$clear}</td>
            </tr>
            ";
        }
        //return $cpUtil->getJsonFromArray($arr);
        return $rows;
    }

    /**
     *
     */
    function getUpdateQtyDelivered() {
        $db      = Zend_Registry::get('db');
        $fn      = Zend_Registry::get('fn');
        $tv      = Zend_Registry::get('tv');
        $cpCfg   = Zend_Registry::get('cpCfg');
        $dbUtil  = Zend_Registry::get('dbUtil');

        $poProductId = $fn->getReqParam('poProductChecked', array());
        $site_id     = $fn->getSessionParam('cp_site_id');

        foreach($poProductId AS $po_product_id){
            if($po_product_id != ''){
                $SQLPoProdPrev = "
                SELECT product_id
                      ,qty
                      ,pack_size
                      ,damage_qty
                      ,purchase_order_id
                      ,batch_no
                      ,po_product_id
                      ,free_items
                      ,qty_requested
                      ,return_qty
                      ,status
                FROM po_product
                WHERE po_product_id = '{$po_product_id}'
                ";
                $resultPoProdPrev = $db->sql_query($SQLPoProdPrev);
                $PoProdPrev = $db->sql_fetchrow($resultPoProdPrev);

                $appendSqlSite  = "";
                $siteIdForField = "";
                if($site_id != ""){ 
                    $appendSqlSite  = "AND site_id = {$site_id}";
                    $siteIdForField = $site_id;
                }

                $SQLBatchWiseStock = "
                SELECT po_product_id
                      ,purchased_qty
                      ,damaged_qty
                      ,free_qty
                      ,current_stock
                FROM inventory_batchwise_stock
                WHERE po_product_id = '{$po_product_id}'
                AND product_id = '{$PoProdPrev['product_id']}'
                {$appendSqlSite}
                ";
                $resultBatchWiseStock  = $db->sql_query($SQLBatchWiseStock);
                $BatchWiseStock        = $db->sql_fetchrow($resultBatchWiseStock);
                $numRowsBatchWiseStock = $db->sql_numrows($resultBatchWiseStock);

                if($PoProdPrev['qty'] == $PoProdPrev['qty_requested'] && $numRowsBatchWiseStock == 0) {
                } else {
                    if($PoProdPrev['status'] != "Closed") {
                        $SQLPOProduct = "
                        UPDATE po_product SET qty = qty_requested, status = 'Closed'
                        WHERE po_product_id = '{$po_product_id}'
                        ";
                        $resultPOProduct  = $db->sql_query($SQLPOProduct);

                        $SQLPoProd = "
                        SELECT product_id
                              ,qty
                              ,pack_size
                              ,damage_qty
                              ,purchase_order_id
                              ,batch_no
                              ,po_product_id
                              ,free_items
                              ,qty_requested
                              ,return_qty
                        FROM po_product
                        WHERE po_product_id = '{$po_product_id}'
                        ";
                        $resultPoProd  = $db->sql_query($SQLPoProd);
                        $PoProd        = $db->sql_fetchrow($resultPoProd);
                        $poQty = $PoProd['qty'] - $PoProd['return_qty'];
                        $poQtyWithoutReturn = $PoProd['qty'];

                        /*$SQLPoProdQty = "
                        SELECT product_id, SUM(qty) AS qty
                        FROM po_product
                        WHERE product_id = '{$PoProd['product_id']}'
                        ";
                        $resultPoProdQty  = $db->sql_query($SQLPoProdQty);
                        $PoProdQty = $db->sql_fetchrow($resultPoProdQty);

                        $SQLInv = "
                        SELECT product_id
                        FROM inventory
                        WHERE product_id = '{$PoProd['product_id']}'
                        ";
                        $resultInv  = $db->sql_query($SQLInv);
                        $PoInv = $db->sql_fetchrow($resultInv);

                        if($PoInv['actual_stock'] > 0){
                            $stock = $PoInv['actual_stock'] + $PoProd['qty'] - $PoProd['damage_qty'];
                        } else {
                            $stock = $PoProd['qty'];
                        }

                        $SQLUpdateProduct = "
                        UPDATE product SET qty_in_stock = {$stock}
                        WHERE product_id = '{$PoProd['product_id']}'
                        ";
                        $resultUpdateProduct  = $db->sql_query($SQLUpdateProduct);

                        $shRec = $fn->getRecordByCondition('stock_history', "po_product_id = '{$po_product_id}'");
                        if($shRec['stock_history_id'] == ''){
                            $fa = array();
                            $fa['po_product_id'] = $po_product_id;
                            $fa['product_id']  = $PoProd['product_id'];
                            $fa['purchase_order_id']  = $PoProd['purchase_order_id'];
                            $fa['qty']  = $PoProd['qty'];
                            $fa['creation_date']  = date("Y-m-d H:i:s");

                            $SQLInsert = $dbUtil->getInsertSQLStringFromArray($fa, 'stock_history');
                            $resultInsert = $db->sql_query($SQLInsert);
                        }

                        /*$fa = array();
                        $fa['product_id']     = $PoProd['product_id'];
                        $fa['actual_stock']     = $stock;
                        $fa['creation_date']  = date('Y-m-d H:i:s');

                        $inventory_id = $fn->addRecord($fa, 'inventory');*/

                        /*$SQLUpdateInventory = "
                        UPDATE inventory SET actual_stock = {$stock}
                        WHERE product_id = '{$PoProd['product_id']}'
                        ";
                        $resultUpdateInventory  = $db->sql_query($SQLUpdateInventory);*/

                        if(is_numeric($PoProd['pack_size'])){
                            $poQty = $poQty * $PoProd['pack_size'];
                            $poQtyWithoutReturn = $poQtyWithoutReturn * $PoProd['pack_size'];
                        } else {
                            $poQty = $poQty;
                            $poQtyWithoutReturn = $poQtyWithoutReturn;
                        }

                        if(is_numeric($PoProd['pack_size'])){
                            $freeQty = $PoProd['free_items'] * $PoProd['pack_size'];
                        } else {
                            $freeQty = $PoProd['free_items'];
                        }

                        if($numRowsBatchWiseStock == 0) {
                            $stockUpdate = $poQty + $freeQty - $PoProd['damage_qty'];

                            $faStock = array();
                            $faStock['product_id']        = $PoProd['product_id'];
                            $faStock['po_product_id']     = $PoProd['po_product_id'];
                            $faStock['purchase_order_id'] = $PoProd['purchase_order_id'];
                            $faStock['batch_no']          = $PoProd['batch_no'];
                            $faStock['purchased_qty']     = $poQtyWithoutReturn;
                            $faStock['damaged_qty']       = $PoProd['damage_qty'];
                            $faStock['free_qty']          = $freeQty;
                            $faStock['current_stock']     = $stockUpdate;

                            if($cpCfg['cp.hasMultiUniqueSites']  == true){
                                $faStock['site_id'] = $site_id;
                            }

                            $faStock     = $fn->addCreationDetailsToFieldsArray($faStock, 'inventory_batchwise_stock');
                            $SQLStock    = $dbUtil->getInsertSQLStringFromArray($faStock, 'inventory_batchwise_stock');
                            $resultStock = $db->sql_query($SQLStock);

                            $SQLUpdateProduct = "
                            UPDATE product SET qty_in_stock{$siteIdForField} = IFNULL(qty_in_stock{$siteIdForField}, 0)+{$stockUpdate}
                            WHERE product_id = '{$PoProd['product_id']}'
                            ";
                            $resultUpdateProduct  = $db->sql_query($SQLUpdateProduct);

                            $SQLInventory = "
                            SELECT product_id
                            FROM inventory
                            WHERE product_id = {$PoProd['product_id']}
                            ";
                            $resultInventory  = $db->sql_query($SQLInventory);
                            $numRowsInventory = $db->sql_numrows($resultInventory);

                            if($numRowsInventory == 0) {
                                $fa = array();
                                $fa['product_id']    = $PoProd['product_id'];
                                $fa['actual_stock'.$siteIdForField]  = $stockUpdate;
                                $fa['creation_date'] = date('Y-m-d H:i:s');

                                $inventory_id = $fn->addRecord($fa, 'inventory');
                            } else {
                                $SQLUpdateInventory = "
                                UPDATE inventory SET actual_stock{$siteIdForField} = IFNULL(actual_stock{$siteIdForField}, 0)+{$stockUpdate}
                                WHERE product_id = '{$PoProd['product_id']}'
                                ";
                                $resultUpdateInventory  = $db->sql_query($SQLUpdateInventory);
                            }
                        } else {
                            $qty_batchwise        = $poQtyWithoutReturn - $BatchWiseStock['purchased_qty']; 
                            $damage_qty_batchwise = $PoProd['damage_qty'] - $BatchWiseStock['damaged_qty']; 
                            $free_qty_batchwise   = $freeQty - $BatchWiseStock['free_qty']; 
                            $current_stock        = $BatchWiseStock['current_stock'] + $qty_batchwise + $free_qty_batchwise - $damage_qty_batchwise;
                            $stockCalc            = $qty_batchwise + $free_qty_batchwise - $damage_qty_batchwise;

                            $faStock = array();
                            $faStock['purchased_qty'] = $BatchWiseStock['purchased_qty'] + $qty_batchwise;
                            $faStock['damaged_qty']   = $BatchWiseStock['damaged_qty'] + $damage_qty_batchwise;
                            $faStock['free_qty']      = $BatchWiseStock['free_qty'] + $free_qty_batchwise;
                            $faStock['current_stock'] = $current_stock;
                            
                            $faStock        = $fn->addModificationDetailsToFieldsArray($faStock, 'inventory_batchwise_stock');
                            $whereCondition = "WHERE po_product_id = {$po_product_id}
                                               AND product_id = '{$PoProd['product_id']}'
                                               {$appendSqlSite}";
                            $SQLStock    = $dbUtil->getUpdateSQLStringFromArray($faStock, "inventory_batchwise_stock", $whereCondition);
                            $resultStock = $db->sql_query($SQLStock);

                            $SQLUpdateProduct = "
                            UPDATE product SET qty_in_stock{$siteIdForField} = IFNULL(qty_in_stock{$siteIdForField}, 0)+{$stockCalc}
                            WHERE product_id = '{$PoProd['product_id']}'
                            ";
                            $resultUpdateProduct  = $db->sql_query($SQLUpdateProduct);

                            $SQLUpdateInventory = "
                            UPDATE inventory SET actual_stock{$siteIdForField} = IFNULL(actual_stock{$siteIdForField}, 0)+{$stockCalc}
                            WHERE product_id = '{$PoProd['product_id']}'
                            ";
                            $resultUpdateInventory  = $db->sql_query($SQLUpdateInventory);
                        }
                    } else {
                        print("Closed");
                    }
                }
            }
        }
        /*$modObj = getCPModuleObj('tradingin_inventory');
        $modObj->view->getCreateInventoryRecords();*/
    }

    /**
     *
     */
    function getDeletePoProduct() {
        $db      = Zend_Registry::get('db');
        $fn      = Zend_Registry::get('fn');
        $tv      = Zend_Registry::get('tv');
        $cpCfg   = Zend_Registry::get('cpCfg');
        $dbUtil  = Zend_Registry::get('dbUtil');

        $po_product_id     = $fn->getReqParam('po_product_id');
        $purchase_order_id = $fn->getReqParam('purchase_order_id');
        $cpSiteIdSession   = $fn->getSessionParam('cp_site_id');

        $appendSqlSite  = "";
        $siteIdForField = "";
        if($cpSiteIdSession != ""){ 
            $appendSqlSite  = "AND ibs.site_id = {$cpSiteIdSession}";
            $siteIdForField = $cpSiteIdSession;
        }

        $SQLPoProduct = "
        SELECT product_id
              ,(CASE 
                WHEN pack_size REGEXP '^[+-]?[0-9]+$'
                THEN qty * pack_size
                ELSE qty END) AS qty
        FROM po_product
        WHERE po_product_id = '{$po_product_id}'
        ";
        $resultPoProduct = $db->sql_query($SQLPoProduct);
        $rowPoProduct    = $db->sql_fetchrow($resultPoProduct);

        $poQty = number_format($rowPoProduct['qty']);

        $rowInv = $fn->getRecordRowByID('inventory', 'product_id', $rowPoProduct['product_id']);

        if($cpSiteIdSession == 1){
            $actual_stock = $rowInv['actual_stock1'];
        }
        else if($cpSiteIdSession == 2){
            $actual_stock = $rowInv['actual_stock2'];
        }
        $actual_stock =  $actual_stock - $poQty;

        $SQLDeletePOProduct = "
        DELETE FROM po_product
        WHERE po_product_id   = '{$po_product_id}'
        AND purchase_order_id = '{$purchase_order_id}'
        ";
        $resultDeletePOProduct  = $db->sql_query($SQLDeletePOProduct);

        $appendSql = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "AND site_id = {$cpSiteIdSession}";
        }

        $SQLDeleteStock = "
        DELETE FROM inventory_batchwise_stock
        WHERE po_product_id   = '{$po_product_id}'
        AND purchase_order_id = '{$purchase_order_id}'
        {$appendSql}
        ";
        $resultDeleteStock = $db->sql_query($SQLDeleteStock);

        /*$SQLBatchWiseStockOverall = "
        SELECT SUM(ibs.current_stock) AS current_stock
        FROM inventory_batchwise_stock ibs
        LEFT JOIN (purchase_order po) ON (po.purchase_order_id = ibs.purchase_order_id)
        WHERE po.status != 'Cancelled'
          AND ibs.product_id = '{$rowPoProduct['product_id']}'
          {$appendSqlSite}
        GROUP BY ibs.product_id
        ";
        $resultBatchWiseStockOverall = $db->sql_query($SQLBatchWiseStockOverall);
        $rowBatchWiseStockOverall    = $db->sql_fetchrow($resultBatchWiseStockOverall);
            */

        $SQLUpdateProduct = "
        UPDATE product SET qty_in_stock{$siteIdForField} = '{$actual_stock}'
        WHERE product_id = '{$rowPoProduct['product_id']}'
        ";
        $resultUpdateProduct  = $db->sql_query($SQLUpdateProduct);

        $SQLUpdateInventory = "
        UPDATE inventory SET actual_stock{$siteIdForField} = '{$actual_stock}'
        WHERE product_id = '{$rowPoProduct['product_id']}'
        ";
        $resultUpdateInventory  = $db->sql_query($SQLUpdateInventory);
    }

    /**
     *
     */
    function getDuplicatePO() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpUtil = Zend_Registry::get('cpUtil');
        $dbUtil = Zend_Registry::get('dbUtil');
        $tv = Zend_Registry::get('tv');

        $purchase_order_id_source = $fn->getReqParam('purchase_order_id');
        $linkedProduct = $fn->getReqParam('linkedProduct');

        $rowPOSrc = $fn->getRecordRowByID('purchase_order', 'purchase_order_id', $purchase_order_id_source);

        //create header
        $purchase_order_date    = strtotime(date('Y-m-d'));
        $followup_date = strtotime('+7 days', $purchase_order_date);

        $fa = array();
        $fa['po_code']              = $this->getUpdatePOCode();
        $fa['company_id_supplier']  = $rowPOSrc['company_id_supplier'];
        $fa['title']                = $rowPOSrc['title'];
        $fa['payment_terms']        = $rowPOSrc['payment_terms'];
        $fa['delivery_terms']       = $rowPOSrc['delivery_terms'];
        $fa['notes']                = $rowPOSrc['notes'];
        $fa['creation_date']        = date('Y-m-d');
        $fa['contact_id_supplier']  = $rowPOSrc['contact_id_supplier'];
        $fa['purchase_order_date']  = date('Y-m-d', $purchase_order_date);
        $fa['status']               = $rowPOSrc['status'];
        $fa['buy_currency']         = $rowPOSrc['buy_currency'];
        $fa['staff_id']             = $rowPOSrc['staff_id'];
        $fa['priority']             = $rowPOSrc['priority'];
        $fa['quote_id']             = $rowPOSrc['quote_id'];
        $fa['amount']               = $rowPOSrc['amount'];
        $fa['follow_up_date']       = date('Y-m-d', $followup_date);
        $fa['currency']             = $rowPOSrc['currency'];
        $fa['freight_cost']         = $rowPOSrc['freight_cost'];
        $fa['supplier_quote_id']    = $rowPOSrc['supplier_quote_id'];
        $fa['batch_import']         = $rowPOSrc['batch_import'];
        $fa['site_id']              = $rowPOSrc['site_id'];
        $fa = $fn->addCreationDetailsToFieldsArray($fa, 'purchase_order');

        $SQL = $dbUtil->getInsertSQLStringFromArray($fa, 'purchase_order');
        $db->sql_query($SQL);
        $purchase_order_id = $db->sql_nextid();

        if($linkedProduct == 1){
            $SQLPO    = "
            SELECT po.*
            FROM po_product po
            WHERE po.purchase_order_id = '{$purchase_order_id_source}'
            ";
            $resultPO = $db->sql_query($SQLPO);

            //$qty_balance = $row['qty_requested'] - $row['qty'];


            while ($row = $db->sql_fetchrow($resultPO)) {
                $fa1 = array();
                $fa1['purchase_order_id']   = $purchase_order_id;
                $fa1['product_id']          = $row['product_id'];
                //$fa1['qty']                 = $row['qty'];
                $fa1['price']               = $row['price'];
                //$fa1['status']              = $row['status'];
                //$fa1['qty_delivered']       = $row['qty_delivered'];
                $fa1['qty_requested']       = $row['qty_requested'];
                //$fa1['qty_balance']         = $qty_balance
                $fa1['selling_price']       = $row['selling_price'];
                $fa1['cost_price']       = $row['cost_price'];
                $fa1['gst']       = $row['gst'];
                $fa1 = $fn->addCreationDetailsToFieldsArray($fa1, 'po_product');

                $SQL = $dbUtil->getInsertSQLStringFromArray($fa1, 'po_product');
                $db->sql_query($SQL);
            }
        }
    }

    function getHighlightkeyword($str, $search) {
        $highlightcolor = "#daa732";
        $occurrences = substr_count(strtolower($str), strtolower($search));
        $newstring = $str;
        $match = array();

        for ($i=0;$i<$occurrences;$i++) {
            $match[$i] = stripos($str, $search, $i);
            $match[$i] = substr($str, $match[$i], strlen($search));
            $newstring = str_replace($match[$i], '[#]'.$match[$i].'[@]', strip_tags($newstring));
        }

        $newstring = str_replace('[#]', '<span style="color: '.$highlightcolor.';">', $newstring);
        $newstring = str_replace('[@]', '</span>', $newstring);
        return $newstring;

    }

    /**
     *
     */
    function getCreationModificationPO() {
        $db      = Zend_Registry::get('db');
        $fn      = Zend_Registry::get('fn');
        $tv      = Zend_Registry::get('tv');
        $cpCfg   = Zend_Registry::get('cpCfg');
        $dbUtil  = Zend_Registry::get('dbUtil');

        $po_product_id = $fn->getReqParam('po_product_id');

        $header = "
        <thead>
            <tr>
                <td>Created By/Creation Date</td>
                <td>Modified By/Modification Date</td>
            </tr>
        </thead>
        ";

        $SQLPO ="
        SELECT pp.creation_date
              ,pp.created_by
              ,pp.modification_date
              ,pp.modified_by
        FROM po_product pp
        WHERE pp.po_product_id = {$po_product_id}
        ";
        $resultPo = $db->sql_query($SQLPO);
        $rowPo    = $db->sql_fetchrow($resultPo);

        if($rowPo['modified_by'] != ""){
            $modified_by = "{$rowPo['modified_by']}/{$rowPo['modification_date']}";
        }else{
            $modified_by = "";
        }

        if($rowPo['created_by'] != ""){
            $created_by = "{$rowPo['created_by']}/{$rowPo['creation_date']}";
        }else{
            $created_by = "";
        }

        $rows = "
        <tbody>
            <tr>
                <td>{$created_by}</td>
                <td>{$modified_by}</td>
            </tr>
        </tbody>
        ";

        $text = "
        <form id='creationModificationPo' class='creationModificationPo' method='post'>
            <table class='thinlist' id='po_productTable'>
                {$header}
                {$rows}
            </table>
        </form>
        ";

        return $text;
    }

     /**
     *
     */
    function getAddNewProductMasterValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $validate->resetErrorArray();
        $validate->validateData('title', 'Please enter product name');

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getAddNewProductMasterSubmit() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $validate = Zend_Registry::get('validate');

        if (!$this->getAddNewProductMasterValidate()){
            return $validate->getErrorMessageXML();
        }

        $title       = $fn->getPostParam('title');
        $supplier_id = $fn->getReqParam('supplier_id');
        $title  = trim($title);

        $fa = array();
        $fa['title']         = $title;
        $fa['published']     = 1;
        $fa['product_type']  = 'Purchasing and Selling';
        $fa['item_code']     = $this->getUpdateProductCode();
        $fa['created_by']    = $fn->getSessionParam('userName');
        $fa['creation_date'] = date("Y-m-d H:i:s");

        $insert1    = $dbUtil->getInsertSQLStringFromArray($fa, 'product');
        $result1    = $db->sql_query($insert1);
        $product_id = $db->sql_nextid();

        $fa2 = array();
        $fa2['company_id']    = $supplier_id;
        $fa2['product_id']    = $product_id;
        $fa2['creation_date'] = date('Y-m-d H:i:s');

        $insert2 = $dbUtil->getInsertSQLStringFromArray($fa2, 'product_company');
        $result2 = $db->sql_query($insert2);

        $sqlSite = "
        SELECT site_id
        FROM site
        ORDER BY site_id
        ";
        $resultSite   = $db->sql_query($sqlSite);
        
        while ($rowSite = $db->sql_fetchrow($resultSite)) {
            $fa = array();
            $fa['product_id']       = $product_id;
            $fa['site_id']          = $rowSite['site_id'];
            $fa['creation_date']    = date("Y-m-d H:i:s");
            $fa['created_by']       = $fn->getSessionParam('userName');

            $insert3 = $dbUtil->getInsertSQLStringFromArray($fa, 'medicine_site');
            $result3 = $db->sql_query($insert3);
        }

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getUpdateOverallDiscount() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        $purchase_order_id = $fn->getReqParam('purchase_order_id');
        $overall_discount  = $fn->getReqParam('overall_discount');

        $arr = array();

        if($overall_discount == ""){
            $overall_discount = 0;
        }

        $rowPO = $fn->getRecordRowByID('purchase_order', 'purchase_order_id', $purchase_order_id);
        
        if($rowPO['purchase_return'] == ""){
            $purchase_return = 0;
        }
        else{
            $purchase_return = $rowPO['purchase_return'] ;
        }

        $SQLUpdateOverallDisc = "
        UPDATE purchase_order SET overall_discount = '{$overall_discount}'
        WHERE purchase_order_id = {$purchase_order_id}
        ";
        $result = $db->sql_query($SQLUpdateOverallDisc);

        $SQLTotal = "
        SELECT SUM(pop.qty_requested * pop.cost_price) AS total_cost
              ,SUM((((pop.qty_requested * pop.cost_price) * pop.discount_percentage) / 100) + (((pop.qty_requested * pop.cost_price) * {$overall_discount}) / 100)) AS Discount_Total
              ,SUM((((pop.qty_requested * pop.cost_price) - (((pop.qty_requested * pop.cost_price) * pop.discount_percentage) / 100) - (((pop.qty_requested * pop.cost_price) * {$overall_discount}) / 100)) * pop.gst) / 100) AS GST_Total
        FROM po_product pop WHERE pop.purchase_order_id = {$purchase_order_id}
        ";
        $resultTotal   = $db->sql_query($SQLTotal);
        $rowTotal      = $db->sql_fetchrow($resultTotal);
        $totalCost     = $rowTotal['total_cost'] - $rowTotal['Discount_Total'] + $rowTotal['GST_Total'] - $purchase_return;
        $Grand_Total   = number_format(round($totalCost), 2);  
        $discountTotal = number_format(round($rowTotal['Discount_Total']), 2);

        $arr = array("grand_total"   => "Grand Total: ".$Grand_Total, "total_discount" => "Discount: ".$discountTotal);

        return json_encode($arr);
    }

    /**
     *
     */
    function getUpdatePurchaseReturn() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        $purchase_order_id = $fn->getReqParam('purchase_order_id');
        $purchase_return  = $fn->getReqParam('purchase_return');

        $arr = array();

        if($purchase_return == ""){
            $purchase_return = 0;
        }
        
        $rowPO = $fn->getRecordRowByID('purchase_order', 'purchase_order_id', $purchase_order_id);
        
        if($rowPO['overall_discount'] == ""){
            $overall_discount = 0;
        }
        else{
            $overall_discount = $rowPO['overall_discount'] ;

        }

        $SQLUpdateOverallDisc = "
        UPDATE purchase_order SET purchase_return = '{$purchase_return}'
        WHERE purchase_order_id = {$purchase_order_id}
        ";
        $result = $db->sql_query($SQLUpdateOverallDisc);

        $SQLTotal = "
        SELECT SUM(pop.qty_requested * pop.cost_price) AS total_cost
              ,SUM((((pop.qty_requested * pop.cost_price) * pop.discount_percentage) / 100) + (((pop.qty_requested * pop.cost_price) * {$overall_discount}) / 100)) AS Discount_Total
              ,SUM((((pop.qty_requested * pop.cost_price) - (((pop.qty_requested * pop.cost_price) * pop.discount_percentage) / 100) - (((pop.qty_requested * pop.cost_price) * {$overall_discount}) / 100)) * pop.gst) / 100) AS GST_Total
        FROM po_product pop WHERE pop.purchase_order_id = {$purchase_order_id}
        ";
        $resultTotal   = $db->sql_query($SQLTotal);
        $rowTotal      = $db->sql_fetchrow($resultTotal);
        $totalCost     = $rowTotal['total_cost'] - $rowTotal['Discount_Total'] + $rowTotal['GST_Total'] - $purchase_return;
        $Grand_Total   = number_format(round($totalCost), 2);  
        $discountTotal = number_format(round($rowTotal['Discount_Total']), 2);

        $arr = array("grand_total"   => "Grand Total: ".$Grand_Total, "total_discount" => "Discount: ".$discountTotal);

        return json_encode($arr);
    }

    /**
     *
     */
    function getCheckSoldQtyInBatchWiseStock() {
        $fn     = Zend_Registry::get('fn');
        $db     = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg  = Zend_Registry::get('cpCfg');
        
        $site_id           = $fn->getSessionParam('cp_site_id');
        $purchase_order_id = $fn->getReqParam('purchase_order_id');

        /*$SQLStock ="
        SELECT sales_qty
        FROM inventory_batchwise_stock
        WHERE purchase_order_id = {$purchase_order_id}
        AND sales_qty > 0
        "; */

        $SQLStock ="
        SELECT status
        FROM po_product
        WHERE purchase_order_id = {$purchase_order_id}
        AND status = 'Closed'
        ";
        $resultStock  = $db->sql_query($SQLStock);
        $numRowsStock = $db->sql_numrows($resultStock);
        $rowStock     = $db->sql_fetchrow($resultStock);

        return $numRowsStock;
    }

    /**
     *
     */
    function getReturnQtyPoProductValidate() {
        $validate = Zend_Registry::get('validate');
        $fn       = Zend_Registry::get('fn');
        $db       = Zend_Registry::get('db');

        $purchase_order_id = $fn->getPostParam('purchase_order_id');
        $po_product_id     = $fn->getPostParam('po_product_id');
        $product_id        = $fn->getPostParam('product_id');
        $return_qty        = $fn->getPostParam('return_qty');
        $cpSiteIdSession   = $fn->getSessionParam('cp_site_id');

        $validate->resetErrorArray();
        $validate->validateData('return_qty', 'Please enter return qty');

        $SQLBatchWiseStock = "
        SELECT current_stock
        FROM inventory_batchwise_stock
        WHERE po_product_id = '{$po_product_id}'
        AND product_id = '{$product_id}'
        AND site_id    = '{$cpSiteIdSession}'
        ";
        $resultBatchWiseStock  = $db->sql_query($SQLBatchWiseStock);
        $numRowsBatchWiseStock = $db->sql_numrows($resultBatchWiseStock);
        if($return_qty == "") {
            $validate->errorArray['return_qty']['name'] = "return_qty";
            $validate->errorArray['return_qty']['msg']  = "Please enter return qty";
        } else {
            if($numRowsBatchWiseStock > 0) {
                $BatchWiseStock = $db->sql_fetchrow($resultBatchWiseStock);
                if($return_qty > $BatchWiseStock['current_stock']) {
                    $validate->errorArray['return_qty']['name'] = "return_qty";
                    $validate->errorArray['return_qty']['msg']  = "Please enter return qty less than or equal to ".$BatchWiseStock['current_stock'];
                }
            } else {
                $SQLPoProduct = "
                SELECT qty
                FROM po_product
                WHERE po_product_id = '{$po_product_id}'
                ";
                $resultPoProduct = $db->sql_query($SQLPoProduct);
                $rowPoProduct    = $db->sql_fetchrow($resultPoProduct);
                if($return_qty > $rowPoProduct['qty']) {
                    $validate->errorArray['return_qty']['name'] = "return_qty";
                    $validate->errorArray['return_qty']['msg']  = "Please enter return qty less than or equal to ".$rowPoProduct['qty'];
                }
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
    function getReturnQtyPoProductSubmit() {
        $fn       = Zend_Registry::get('fn');
        $db       = Zend_Registry::get('db');
        $dbUtil   = Zend_Registry::get('dbUtil');
        $cpCfg    = Zend_Registry::get('cpCfg');
        $validate = Zend_Registry::get('validate');

        if (!$this->getReturnQtyPoProductValidate()){
            return $validate->getErrorMessageXML();
        }

        $purchase_order_id = $fn->getPostParam('purchase_order_id');
        $po_product_id     = $fn->getPostParam('po_product_id');
        $product_id        = $fn->getPostParam('product_id');
        $return_qty        = $fn->getPostParam('return_qty');
        $cpSiteIdSession   = $fn->getSessionParam('cp_site_id');

        $SQLPoProduct = "
        SELECT qty
              ,return_qty
        FROM po_product
        WHERE po_product_id = '{$po_product_id}'
        ";
        $resultPoProduct = $db->sql_query($SQLPoProduct);
        $rowPoProduct    = $db->sql_fetchrow($resultPoProduct);

        $faPoProd = array();
        $faPoProd['return_qty'] = $return_qty;
        $faPoProd       = $fn->addModificationDetailsToFieldsArray($faPoProd, 'po_product');
        $whereCondition = "WHERE po_product_id='{$po_product_id}' AND product_id='{$product_id}'";
        $SQLPoProd      = $dbUtil->getUpdateSQLStringFromArray($faPoProd, "po_product", $whereCondition);
        $resultPoProd   = $db->sql_query($SQLPoProd);

        $faReturn = array();
        $faReturn['po_product_id']     = $po_product_id;
        $faReturn['product_id']        = $product_id;
        $faReturn['return_qty']        = $return_qty;
        $faReturn['site_id']           = $cpSiteIdSession;
        $faReturn['purchase_order_id'] = $purchase_order_id;
        $faReturn        = $fn->addCreationDetailsToFieldsArray($faReturn, 'return_qty_log');
        $SQLReturnLog    = $dbUtil->getInsertSQLStringFromArray($faReturn, 'return_qty_log');
        $resultReturnLog = $db->sql_query($SQLReturnLog);

        if($return_qty < 0) {
            if($rowPoProduct['return_qty'] < 0) {
                $ReturnStock = abs($return_qty - $rowPoProduct['return_qty']);
                if($rowPoProduct['return_qty'] > $return_qty) {
                    $ReturnStock = -$ReturnStock;
                } else {
                    $ReturnStock = $ReturnStock;
                }
            } else {
                if($rowPoProduct['return_qty'] > 0) {
                    $ReturnStock = $return_qty - $rowPoProduct['return_qty'];
                } else {
                    $ReturnStock = $return_qty + $rowPoProduct['return_qty'];
                }
            }
        } else {
            $ReturnStock = $return_qty - $rowPoProduct['return_qty'];
        }

        $appendSqlSite  = "";
        $siteIdForField = "";
        if($cpSiteIdSession != ""){ 
            $appendSqlSite  = "AND ibs.site_id = {$cpSiteIdSession}";
            $siteIdForField = $cpSiteIdSession;
        }

        $SQLBatchWiseStockCheck = "
        SELECT ibs.current_stock
        FROM inventory_batchwise_stock ibs
        LEFT JOIN (purchase_order po) ON (po.purchase_order_id = ibs.purchase_order_id)
        WHERE po.status != 'Cancelled'
          AND ibs.product_id = '{$product_id}'
          AND ibs.po_product_id = '{$po_product_id}'
          {$appendSqlSite}
        GROUP BY ibs.product_id
        ";
        $resultBatchWiseStockCheck  = $db->sql_query($SQLBatchWiseStockCheck);
        $numRowsBatchWiseStockCheck = $db->sql_numrows($resultBatchWiseStockCheck);

        if($numRowsBatchWiseStockCheck > 0) {
            $BatchWiseStockCheck = $db->sql_fetchrow($resultBatchWiseStockCheck);

            $faIBS = array();
            $faIBS['current_stock'] = $BatchWiseStockCheck['current_stock'] - $ReturnStock;
            $faPoProd               = $fn->addModificationDetailsToFieldsArray($faIBS, 'inventory_batchwise_stock');
            $whereConditionIBS      = "WHERE po_product_id='{$po_product_id}' AND product_id='{$product_id}'";
            $SQLIBS    = $dbUtil->getUpdateSQLStringFromArray($faIBS, "inventory_batchwise_stock", $whereConditionIBS);
            $resultIBS = $db->sql_query($SQLIBS);

            $SQLBatchWiseStock = "
            SELECT SUM(ibs.current_stock) AS current_stock
                  ,ibs.product_id
            FROM inventory_batchwise_stock ibs
            LEFT JOIN (purchase_order po) ON (po.purchase_order_id = ibs.purchase_order_id)
            WHERE po.status != 'Cancelled'
              AND ibs.product_id = '{$product_id}'
              {$appendSqlSite}
            GROUP BY ibs.product_id
            ";
            $resultBatchWiseStock  = $db->sql_query($SQLBatchWiseStock);
            $numRowsBatchWiseStock = $db->sql_numrows($resultBatchWiseStock);
            
            while($BatchWiseStock = $db->sql_fetchrow($resultBatchWiseStock)) {
                $SQLUpdateProduct = "
                UPDATE product SET qty_in_stock{$siteIdForField} = '{$BatchWiseStock['current_stock']}'
                WHERE product_id = '{$BatchWiseStock['product_id']}'
                ";
                $resultUpdateProduct = $db->sql_query($SQLUpdateProduct);

                $SQLUpdateInventory = "
                UPDATE inventory SET actual_stock{$siteIdForField} = '{$BatchWiseStock['current_stock']}'
                WHERE product_id = '{$BatchWiseStock['product_id']}'
                ";
                $resultUpdateInventory = $db->sql_query($SQLUpdateInventory);
            }
        }

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getReturnQtyPoProductNSSubmit() {
        $fn       = Zend_Registry::get('fn');
        $db       = Zend_Registry::get('db');
        $dbUtil   = Zend_Registry::get('dbUtil');
        $cpCfg    = Zend_Registry::get('cpCfg');
        $validate = Zend_Registry::get('validate');

        $po_product_id     = $fn->getPostParam('po_product_id');
        $return_qty_ns     = $fn->getPostParam('return_qty_ns');
        $return_qty_ns_paid= $fn->getPostParam('return_qty_ns_paid');
        $cpSiteIdSession   = $fn->getSessionParam('cp_site_id');

        $faPoProd = array();
        $faPoProd['return_qty_ns']      = $return_qty_ns;
        $faPoProd['return_qty_ns_paid'] = $return_qty_ns_paid;
        $faPoProd       = $fn->addModificationDetailsToFieldsArray($faPoProd, 'po_product');
        $whereCondition = "WHERE po_product_id='{$po_product_id}'";
        $SQLPoProd      = $dbUtil->getUpdateSQLStringFromArray($faPoProd, "po_product", $whereCondition);
        $resultPoProd   = $db->sql_query($SQLPoProd);


        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getCheckPricePercentage() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $product_id = $fn->getReqParam('product_id');
        $pack_size  = $fn->getReqParam('pack_size');
        $price      = $fn->getReqParam('price');

        if($price != "") {
            $SQLPriceCheck = "
            SELECT selling_price, pack_size
            FROM po_product
            WHERE product_id = {$product_id}
            ORDER BY po_product_id DESC
            ";
            $resultPriceCheck  = $db->sql_query($SQLPriceCheck);
            $numRowsPriceCheck = $db->sql_numrows($resultPriceCheck);
            $rowPriceChk = $db->sql_fetchrow($resultPriceCheck);

            if($numRowsPriceCheck > 0){
                if(is_numeric($rowPriceChk['pack_size'])){
                    $chkPricePre = $rowPriceChk['selling_price'] / $rowPriceChk['pack_size'];
                } else {
                    $chkPricePre = $rowPriceChk['selling_price'];
                }

                $addedPercentage = ($chkPricePre * 15) / 100;

                $chkPricePre = $chkPricePre + $addedPercentage;

                if(is_numeric($pack_size)){
                    $chkPrice = $price / $pack_size;
                } else {
                    $chkPrice = $price;
                }

                if($chkPrice > $chkPricePre){
                    print "Please note that earlier selling price(".$rowPriceChk['selling_price']."). Also please see the pack size is correctly entered.";
                }
            }
        }
    }
}
