<?
class CPL_Admin_Modules_Tradingsg_StockTransfer_View extends CP_Common_Lib_ModuleViewAbstract
{
   function getList($dataArray){
        $listObj = Zend_Registry::get('listObj');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $text = '';
        $rows = '';
        $readonly = '';
        $OrderItems = '';

        $rowCounter = 0;

        $SQLdeleteHistory ="
        DELETE FROM stock_transfer_history
        WHERE stock_transfer_id NOT IN (SELECT stock_transfer_id FROM stock_transfer)
        ";
        $resultdelhis  = $db->sql_query($SQLdeleteHistory);
        $deletehistory = $db->sql_fetchrow($resultdelhis);
        
        //--------------------------------------------------------------------------//
        foreach ($dataArray as $row){

            if($row['transfer_type'] == "internal") {
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
            } else {
                $Sqlfrom = "
                select title as from_location
                FROM site
                WHERE site_id = '{$row['from_location']}'
                ";
                $resultfrom = $db->sql_query($Sqlfrom);
                $from = $db->sql_fetchrow($resultfrom);

                $SqlTo = "
                select title as to_location
                FROM site
                WHERE site_id = '{$row['to_location']}'
                ";
                $resultTo = $db->sql_query($SqlTo);
                $To = $db->sql_fetchrow($resultTo);
            }

            $stock_transfer_date = $fn->getCPDate($row['date'],"d-m-Y");

            $rows .= "
            {$listObj->getListRowHeader($row, $rowCounter)}
            {$listObj->getGoToDetailText($rowCounter, $stock_transfer_date)}
            {$listObj->getListDataCell($from['from_location'])}
            {$listObj->getListDataCell($To['to_location'])}
            {$listObj->getListDataCell($row['status'])}
            ";
            $rowCounter++ ;
        }

        $text = "
        {$listObj->getListHeader()}
        {$listObj->getListHeaderCell('Date', 'date')}
        {$listObj->getListHeaderCell('From Location', 'location_name')}
        {$listObj->getListHeaderCell('To Location', '')}
        {$listObj->getListHeaderCell('Status', 'status')}
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
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $expNoEdit  = array('isEditable' => 0);
        
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $siteRec = $fn->getRecordRowByID('site', 'site_id', $cpSiteIdSession);

        $newLocationUrl = 'index.php?_spAction=newLocation&lnkRoom=tradingsg_stockTransfer&showHTML=0';
        $newLocationUrl = "<a class='jqui-dialog-form' formId='portalForm' title='New Location' 
            w=600 h=560 href='' link='{$newLocationUrl}' callback='cpm.tradingsg.stockTransfer.afterNewLocation'>Add Location</a>";

        $sqlStockTrans = "
        SELECT site_id
              ,title 
        FROM site 
        WHERE published = 1
        ";

        $sqlStockTransFrom = "
        SELECT internal_location_id
              ,title 
        FROM internal_location 
        WHERE internal_location_id != 4 
        ";

        $sqlStockTransTo = "
        SELECT internal_location_id
              ,title 
        FROM internal_location 
        ";

        $expVl = array('sqlType' => 'OneField');
        $transferTypeArr = array("External", "internal");
        
        $fieldset = "
        {$formObj->getRadioArrRow('Type', 'transfer_type', 'External', $transferTypeArr, '')}
        <div class='ExternalLocationFromAndToLocation'>
            {$formObj->getDDRowBySQL('External From Location', 'from_location', $sqlStockTrans, '')}
            {$formObj->getDDRowBySQL('External To Location', 'to_location', $sqlStockTrans, '')}
        </div>
        <div class='InternalLocationFromAndToLocation displayNone'>
            {$formObj->getDDRowBySQL('Internal From Location', 'from_location_internal', $sqlStockTransFrom, '')}
            {$formObj->getDDRowBySQL('Internal To Location', 'to_location_internal', $sqlStockTransTo, '')}
        </div>
        ";

        $text = "
        {$formObj->getFieldSetWrapped('Select Site', $fieldset)}
        ";

        return $text;
    }

    //==================================================================//
    function getRightPanel($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $displayLinkData = Zend_Registry::get('displayLinkData');
        $db = Zend_Registry::get('db');
        $comment = getCPPluginObj('common_comment');
        $media = Zend_Registry::get('media');
        $text = '';

        $record_id = $fn->getIssetParam($row, 'stock_transfer_id');

        $text .="
        {$comment->getView(array(
             'roomName' => 'tradingsg_stockTransfer'
            ,'recordId' => $record_id
        ))}
        ";

        return $text;
    }

    /**
     *
     */
    function getEdit($row){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $dbUtil = Zend_Registry::get('dbUtil');
        $formObj = Zend_Registry::get('formObj');
        $current_date = date('Y-m-d');       

        $text = '';
                  
        $text = "
        <div id='editDisplayLoad'>{$this->getEditDisplay($row['stock_transfer_id'], $row['from_location'])}</div>
        ";

        return $text;
    }

    /**
     *
     */
    function getEditDisplay($stock_transfer_id='', $site_id=''){
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $dbUtil = Zend_Registry::get('dbUtil');
        $formObj = Zend_Registry::get('formObj');
        $current_date = date('Y-m-d');
        $text = '';
        $rows = '';
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        if($stock_transfer_id == ''){
            $stock_transfer_id = $fn->getReqParam('stock_transfer_id');
        }

        /*if($site_id == ''){
            $site_id = $fn->getReqParam('site_id');
        }*/

 
        $SQLStockTransfer = "
        SELECT st.*
        FROM stock_transfer st
        WHERE st.stock_transfer_id = {$stock_transfer_id}
        ORDER BY st.date DESC
        ";
        $resultStockTransfer = $db->sql_query($SQLStockTransfer);
        $row = $db->sql_fetchrow($resultStockTransfer);

        $stock_transfer_status_arr = array('Request', 'Delivered', 'On Hold', 'Cancelled');
        $stock_transfer_id         = $row['stock_transfer_id'];
        $stock_transfer_date       = $fn->getCPDate($row['date'],"d-m-Y");

        $OrderItems = $this->getOrderItems($stock_transfer_id);

        $reuqestFormPdf   = "index.php?module=tradingsg_stockTransfer&_spAction=printExportAsPdf&id={$row['stock_transfer_id']}&printType=request&showHTML=0";
        $deliveryOrderPdf = "index.php?module=tradingsg_stockTransfer&_spAction=printExportAsPdf&id={$row['stock_transfer_id']}&printType=delivery&showHTML=0";
        

        $editableFalse = '';
        $buttonChange  = '';

        $sqlstocktrans = "
        SELECT site_id
              ,title
        FROM site 
        ";
        
        $expVl = array('sqlType' => 'OneField');

        if($row['lock_record'] == 1){
            $editableFalse = "disabled = '1'";

            $buttonChange .= "
            <a class='btn btn-info rollbackChanges' stock_transfer_id= '{$row['stock_transfer_id']}'>
                <span class='fa-refresh'></span>
                 Rollback Transaction
            </a>";
 if ($_SESSION['userGroupName'] != "Nurse") {
            $buttonChange .= "
            <a class='btn btn-danger deductFromStock' stock_transfer_id= '{$row['stock_transfer_id']}'>
                <span class='fa-check'></span>
                Deduct From Stock
            </a>";
        }
        }else{
            $buttonChange = "
            <a class='btn btn-success completeTransaction' stock_transfer_id= '{$row['stock_transfer_id']}'>
                <span class='fa-lock'></span>
                Complete Transaction
            </a>";
        }

        if($row['status'] == 'Cancelled' || $row['status'] == 'Delivered'){
            $editableFalse = "disabled = '1'";
        }

        if($row['status'] == 'Cancelled'){
            $buttonChange = "<div class='CancelledButton btn-danger'>Cancelled</div>";
        }

        $expNoEdit    = '';

        if($row['transfer_type'] == "internal") {
            $appendSqlstocktrans = "";
            if($row['from_location_internal'] == 1) {
                $appendSqlstocktrans = "WHERE internal_location_id != 4";
            }

            $sqlstocktrans = "
            SELECT internal_location_id
                  ,title 
            FROM internal_location 
            {$appendSqlstocktrans}
            ";

            $Sqlfrom = "
            SELECT title
            FROM internal_location
            WHERE internal_location_id = {$row['from_location_internal']}
            ";
            $resultfrom = $db->sql_query($Sqlfrom);
            $from = $db->sql_fetchrow($resultfrom);

            $fromLocation = "<div class='type-text ym-fbox-text row_from_location'>
                                <label for='fld_from_location'>Internal From Location</label>
                                <div class='txt'>{$from['title']}</div>
                            </div>";

            $toLocation   = $formObj->getDDRowBySQL('Internal To Location', 'to_location_internal', $sqlstocktrans, $row['to_location_internal']);   
        } else {
            //$fromLocation = $formObj->getDDRowBySQL('From Location', 'from_location', $sqlstocktrans, $row['from_location']);
            $Sqlfrom = "
            SELECT title
            FROM site
            WHERE site_id = {$row['from_location']}
            ";
            $resultfrom = $db->sql_query($Sqlfrom);

            $from = $db->sql_fetchrow($resultfrom);
            
            $fromLocation = "<div class='type-text ym-fbox-text row_from_location'>
                                <label for='fld_from_location'>From Location</label>
                                <div class='txt'>{$from['title']}</div>
                            </div>";
            $toLocation   = $formObj->getDDRowBySQL('To Location', 'to_location', $sqlstocktrans, $row['to_location']);
        }

        if($row['stock_deducted'] == 1){
            if($row['transfer_type'] == "internal") {
                $Sqlfrom = "
                SELECT title
                FROM internal_location
                WHERE internal_location_id = {$row['from_location_internal']}
                ";
                $resultfrom = $db->sql_query($Sqlfrom);
                $from = $db->sql_fetchrow($resultfrom);

                $Sqlto = "
                SELECT title
                FROM internal_location
                WHERE internal_location_id = {$row['to_location_internal']}
                ";
                $resultto = $db->sql_query($Sqlto);
                $to = $db->sql_fetchrow($resultto);
            } else {
                $Sqlfrom = "
                SELECT title
                FROM site
                WHERE site_id = {$row['from_location']}
                ";
                $resultfrom = $db->sql_query($Sqlfrom);
                $from = $db->sql_fetchrow($resultfrom);

                $Sqlto = "
                SELECT title
                FROM site
                WHERE site_id = {$row['to_location']}
                ";
                $resultto = $db->sql_query($Sqlto);
                $to = $db->sql_fetchrow($resultto);
            }
            
            $rows = '';
            $expNoEdit    = array('isEditable' => 0);
            $fromLocation = "<div class='type-text ym-fbox-text row_from_location'>
                                <label for='fld_from_location'>From Location</label>
                                <div class='txt'>{$from['title']}</div>
                            </div>";
            $toLocation   = "<div class='type-text ym-fbox-text row_to_location'>
                                <label for='fld_to_location'>To Location</label>
                                <div class='txt'>{$to['title']}</div>
                            </div>";
            $buttonChange = "<div class='DeliveredProducts btn-success'>Stock transferred successfully</div>";
        }

        $expNoEditDefault = array('isEditable' => 0);

        $urlPDF   = "index.php?module=tradingsg_stockTransfer&_spAction=printPDF&stock_transfer_id={$row['stock_transfer_id']}&showHTML=0";
        $printPDF = "<a href='{$urlPDF}' id='printPDF' stock_transfer_id='{$row['stock_transfer_id']}' class='btn btn-info' target='_blank'>Print PDF</a>";
 
        $text = "        
        <!--<div class='float_left btn btn-info mb10'>
             <a href='{$reuqestFormPdf}' target = 'blank' id='exportasPdfStockTransfer'><span class='fa-file-pdf-o'></span>Request Form</a>
        </div>
        <div class='float_left btn btn-info mb10'>
             <a href='{$deliveryOrderPdf}' target = 'blank' id='exportasPdfStockTransfer'><span class='fa-print'></span>Delivery Order</a>
        </div>-->

        <table class='list thinlist topTable'>
            $printPDF 
            <tr>
                <th>
                    {$formObj->getTBRow('Title', 'title', $row['title'], $expNoEditDefault)}
                </th>
                <th>
                    {$formObj->getTBRow('Transfer Type', 'type', $row['transfer_type'], $expNoEditDefault)}
                </th>
                <th>
                    {$fromLocation}
                </th>
                <th>
                    {$toLocation}
                </th>
                <th>
                    {$formObj->getDateRow('Date', 'date', $row['date'], $expNoEdit)}
                </th>
            </tr>
            <tr>
                <th>
                    {$formObj->getDDRowByArr('Status', 'status', $stock_transfer_status_arr, $row['status'], $expNoEdit)}
                </th>
                <th>
                    {$formObj->getTBRow('Notes', 'notes', $row['notes'], $expNoEdit)}
                </th>
                <th>
                    <div class='locationTitle'><label>Created By : </label>{$row['created_by']} {$row['creation_date']}
                    </div>
                </th>
                <th>
                    <div class='locationTitle'><label>Modified By : </label>{$row['modified_by']} {$row['modification_date']}
                    </div>
                </th>
                <th></th>
            </tr>
        </table>

        <div class='addProduct'>
            Search by Product : <input type='text' value='' id='fld_product_title' class='text' name='product_title' stock_transfer_id='{$row['stock_transfer_id']}' from_location='{$row['from_location']}' from_location_internal='{$row['from_location_internal']}' transfer_type='{$row['transfer_type']}' {$editableFalse}>
        </div>

        <input type='hidden' name='site_id' value={$cpSiteIdSession}>

        <div class = 'float_box'>
            <div class = 'float_left actionButtons'>
                {$buttonChange}
            </div>
        </div>

        <table class='list thinlist'>
            <thead>
                <tr>
                    <th>S.NO</th>
                    <th>Product Name</th>
                    <th>Batch No</th>
                    <th>Expiry</th>
                    <th>Stock</th>
                    <th>Request Qty</th>
                    <th>Qty Delivered</th>
                    <th>Created By</th>
                    <th>Modified By</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody id='orderItems'>
                {$OrderItems}
            </tbody>
        </table>
        <input type='hidden' name='from_location' value='{$row['from_location']}'>
        ";

        return $text;
    }

    /**
     *
     */
     function getPrintPDF() {
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
        $pdf->SetSubject('Print PDF');
        $pdf->SetTitle('Print PDF');

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 04', PDF_HEADER_STRING);
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));

        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // set margins
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER,5);
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

        $stock_transfer_id = $fn->getReqParam('stock_transfer_id');

        $SQL = "
        SELECT p.title
              ,sh.qty
              ,st.date
              ,sh.qty_requested
              ,sh.stock_transfer_history_id
              ,sh.created_by
              ,sh.product_id
              ,sh.modified_by
              ,sh.creation_date
              ,sh.modification_date
              ,sh.batch_no
              ,sh.pack_size
              ,sh.po_product_id
              ,st.stock_transfer_id 
              ,st.from_location
              ,st.to_location
              ,st.from_location_internal
              ,st.to_location_internal
              ,st.status
              ,st.lock_record
              ,st.transfer_type
              ,pop.expiry_date
        FROM `stock_transfer_history` sh
        LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sh.stock_transfer_id)
        LEFT JOIN po_product pop ON (pop.po_product_id = sh.po_product_id)
        LEFT JOIN  `product` p ON (p.product_id = sh.product_id)
        WHERE p.published = '1' 
        AND sh.stock_transfer_id = {$stock_transfer_id}
        ORDER BY sh.stock_transfer_history_id
        ";
        $result = $db->sql_query($SQL);
        $result2 = $db->sql_query($SQL);
        $company = $db->sql_fetchrow($result2);
        //============================================================================= //

        $pdf->SetFont('Courier','B',10);
        //$pdf->SetFont('Arial', 'B', 10);

        $today = date("d-m-Y");
        $stock_transfer_date = $fn->getCPDate($company['date'],"d-m-Y");

        $tbl1 = '
        <table border="0"  width="100%">
            <tr>
                <td width="60%" align="right" style="font-weight:bold;">Delivery Order</td>                
                <td width="40%" align="right" style="font-weight:bold;">Date : '.$stock_transfer_date.'</td>                
            </tr>
        </table>
        ';

        if($company['transfer_type'] == "internal") {
            $Sqlfrom = "
            select title as from_location
            FROM internal_location
            WHERE internal_location_id = '{$company['from_location_internal']}'
            ";
            $resultfrom = $db->sql_query($Sqlfrom);
            $from = $db->sql_fetchrow($resultfrom);

            $SqlTo = "
            select title as to_location
            FROM internal_location
            WHERE internal_location_id = '{$company['to_location_internal']}'
            ";
            $resultTo = $db->sql_query($SqlTo);
            $To = $db->sql_fetchrow($resultTo);
        } else {
            $Sqlfrom = "
            select title as from_location
            FROM site
            WHERE site_id = '{$company['from_location']}'
            ";
            $resultfrom = $db->sql_query($Sqlfrom);
            $from = $db->sql_fetchrow($resultfrom);

            $SqlTo = "
            select title as to_location
            FROM site
            WHERE site_id = '{$company['to_location']}'
            ";
            $resultTo = $db->sql_query($SqlTo);
            $To = $db->sql_fetchrow($resultTo);
        }
           
        $tbl2 = '
        <table border="0" width="100%">
            <tr>
                <td style="font-weight:bold;">From location: '.$from['from_location'].'</td>
            </tr>
            <tr>
                <td style="font-weight:bold;">To location: '.$To['to_location'].'</td>
            </tr>
        </table>
        ';    

        $tbl3 ='
        <table border="1" width="100%" cellpadding="4">
            <thead>
                <tr >
                 <th width="9%" align="center">S.NO</th>
                    <th width="20%" align="center">Medicine</th>
                    <th width="20%" align="center">Batch-No</th>
                    <th width="20%" align="center">Expiry</th>
                    <th width="10%" align="center">QTY</th>
                    <th width="21%" align="center">Staff</th>
                </tr>
            </thead>
            ';

        $count = 1;
        $rows = '';

        while ($row = $db->sql_fetchrow($result)) {
            $expiry_date = $fn->getCPDate($row['expiry_date'],"d-m-Y");          
            $tbl3 = $tbl3.'<tr>
                                <td width="9%" align="center" style="font-size:12px;">'.$count.'</td>
                                <td width="20%" align="center">'.$row['title'].'</td>
                                <td width="20%" align="center">'.$row['batch_no'].'</td>
                                <td width="20%" align="center">'.$expiry_date.'</td>
                                <td width="10%" align="center">'.$row['qty'].'</td>
                                <td width="21%" align="center">'.$row['created_by'].'</td>
                            </tr>
                            ';
            $count++;
        }
        $tbl3 = $tbl3.'</table>';
        $pdf->writeHTML($tbl1, true, false, false, false, '');
        $pdf->writeHTML($tbl2, true, false, false, false, '');
        $pdf->writeHTML($tbl3, true, false, false, false, '');
        $download_title = 'Print.pdf';
        ob_start();
        $pdf->Output($download_title, 'I');
    }

    /**
     *
     */
    function getOrderItems($stock_transfer_id = ''){
        $fn    = Zend_Registry::get('fn');
        $db    = Zend_Registry::get('db');
        $tv    = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        
        $text = '';
        $rows = '';
        $totalquantity = '';

        if ($stock_transfer_id == ''){
            $stock_transfer_id = $fn->getReqParam('stock_transfer_id');
        }

        $SqlStockTransferCount = "
        SELECT stock_transfer_history_id
        FROM stock_transfer_history
        WHERE stock_transfer_id = '{$stock_transfer_id}'
        ";
        $resultStockTransferCount  = $db->sql_query($SqlStockTransferCount);
        $numRowsStockTransferCount = $db->sql_numrows($resultStockTransferCount);

        $SqlStockTransferCount1 = "
        SELECT stock_transfer_history_id
        FROM stock_transfer_history
        WHERE stock_transfer_id = '{$stock_transfer_id}'
          AND (qty_requested = '' OR qty_requested IS NULL)
        ";
        $resultStockTransferCount1  = $db->sql_query($SqlStockTransferCount1);
        $numRowsStockTransferCount1 = $db->sql_numrows($resultStockTransferCount1);

        $StockHistorySql = "
        SELECT p.title
              ,sh.qty
              ,sh.qty_requested
              ,sh.stock_transfer_history_id
              ,sh.created_by
              ,sh.product_id
              ,sh.modified_by
              ,sh.creation_date
              ,sh.modification_date
              ,sh.batch_no
              ,sh.pack_size
              ,sh.po_product_id
              ,pop.expiry_date
              ,st.stock_transfer_id 
              ,st.from_location
              ,st.to_location
              ,st.from_location_internal
              ,st.to_location_internal
              ,st.status
              ,st.lock_record
              ,st.transfer_type
        FROM `stock_transfer_history` sh
        LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sh.stock_transfer_id)
        LEFT JOIN po_product pop ON (pop.po_product_id = sh.po_product_id)
        LEFT JOIN  `product` p ON (p.product_id = sh.product_id)
        WHERE p.published = '1' 
        AND sh.stock_transfer_id = {$stock_transfer_id}
        ORDER BY sh.stock_transfer_history_id DESC
        ";
        $resultHistorySql= $db->sql_query($StockHistorySql);
        $rowCounter = 1;
        while ($rowz = $db->sql_fetchrow($resultHistorySql)) {
            if($rowz['transfer_type'] == 'internal') {
                if($rowz['from_location_internal'] == "1") {
                    $stockField = "current_stock";
                } else {
                    $sqlLocation = "
                    SELECT internal_location_id
                          ,title 
                    FROM internal_location 
                    WHERE internal_location_id = {$rowz['from_location_internal']}
                    ";
                    $resultLocation = $db->sql_query($sqlLocation);
                    $rowLocation    = $db->sql_fetchrow($resultLocation);
                    $toLocation     = strtolower($rowLocation['title']);
                    $toLocation     = str_replace(' ', '_', $toLocation);
                    $stockField     = "{$toLocation}";
                }

                $appendSqlSite = "";
                if ($cpCfg['cp.hasMultiUniqueSites']) { 
                    $appendSqlSite = "AND site_id = {$cpSiteIdSession}";
                }

                $SQLStock ="
                SELECT {$stockField}
                FROM inventory_batchwise_stock
                WHERE po_product_id = {$rowz['po_product_id']}
                {$appendSqlSite}
                ";
                $resultStock = $db->sql_query($SQLStock);
                $rowStock    = $db->sql_fetchrow($resultStock);
                $stock       = $rowStock[$stockField];
            } else {
                $appendSqlSite = "";
                if ($cpCfg['cp.hasMultiUniqueSites']) { 
                    $appendSqlSite = "AND site_id = {$rowz['from_location']}";
                }

                $SQLStock ="
                SELECT current_stock
                FROM inventory_batchwise_stock
                WHERE po_product_id = {$rowz['po_product_id']}
                {$appendSqlSite}
                ";
                $resultStock = $db->sql_query($SQLStock);
                $rowStock    = $db->sql_fetchrow($resultStock);
                $stock       = $rowStock['current_stock'];
            }

            $totalqty = $stock + $rowz['qty'];

            $editableFalse = '';
            $expNoEdit     = '';
            $deleteLink = "<a href='#' class='deleteItem btn btn-danger' stock_transfer_history_id='{$rowz['stock_transfer_history_id']}' stock_transfer_id= '{$rowz['stock_transfer_id']}'>Delete</a>";
            
            if($rowz['status'] == 'Cancelled'){
                $editableFalse  = "disabled = '1'";
                $deleteLink     = "";
                $expNoEdit      = array('isEditable' => 0);
            }

            if($rowz['status'] == 'Delivered'){
                $deleteLink     = "";
                $editableFalse  = "disabled = '1'";
            }

            $editableFalseRequest = '';
            if($rowz['lock_record'] == 1){
                $editableFalseRequest  = "disabled = '1'";
            }

            $editableFalseDelivered = "disabled = '1'";
            if($cpSiteIdSession == 1){
                $editableFalseDelivered = "disabled = '1'";
            }

            if($rowz['lock_record'] == 1 && $rowz['status'] == 'Delivered'){
                $editableFalse  = "disabled = '1'";

                $totalqty   = $stock;
            }

            /*if($rowz['pack_size'] != '' && is_numeric($rowz['pack_size']) > 0) {
                $totalqty = $totalqty / $rowz['pack_size'];
                $totalqty = (int) $totalqty;
            }*/

            $deductStockPatient = "
            <a class='deductStockForPatient' product_id='{$rowz['product_id']}' po_product_id='{$rowz['po_product_id']}' batch_no='{$rowz['batch_no']}' stock_transfer_id='{$rowz['stock_transfer_id']}' stock_transfer_history_id='{$rowz['stock_transfer_history_id']}'>
                <span class='glyphicon glyphicon-minus-sign float_left'></span>
                Deduct
            </a>
            ";

            $expiry_date = $fn->getCPDate($rowz['expiry_date'], 'd-m-Y');

            $rows .= "
            <tr>
                <td>
                    {$rowCounter}
                    <input type='hidden' class='stockTransfer_product_count' name='stockTransfer_product_count' value='{$numRowsStockTransferCount}'/>
                    <input type='hidden' class='stockTransfer_product_qty_count' name='stockTransfer_produc_qty_count' value='{$numRowsStockTransferCount1}'/>
                </td>
                <td class='w25p'>{$rowz['title']}</td>
                <td>{$rowz['batch_no']}</td>
                <td>{$expiry_date}</td>
                <td>{$totalqty}</td>
                <td class='w100'>
                    <input type='text' value='{$rowz['qty_requested']}' id='fld_Request_qty' class='text w100' name='request_qty' stock_transfer_history_id='{$rowz['stock_transfer_history_id']}' stock_transfer_id= '{$rowz['stock_transfer_id']}' stock='{$totalqty}' {$editableFalse} {$editableFalseRequest}>
                </td>
                <td class='w100'>
                    <input type='text' value='{$rowz['qty']}' id='fld_qty' class='text w100' name='qty' stock_transfer_history_id='{$rowz['stock_transfer_history_id']}' stock_transfer_id= '{$rowz['stock_transfer_id']}' stock='{$totalqty}' {$editableFalse} {$editableFalseDelivered}>
                </td>
                <td>{$rowz['created_by']}  {$rowz['creation_date']}</td>
                <td>{$rowz['modified_by']}  {$rowz['modification_date']}</td>
                <td>{$deleteLink}</td>
            </tr>
            ";
                /*<td class='w100'>{$deductStockPatient}</td>*/

            $rowCounter++;        
        }

        $text = "
        {$rows}
        ";

        return $text;
    }

    /**
     *
     */
    function getNewLocation(){
        $formObj = Zend_Registry::get('formObj');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');

        $formAction = "index.php?_spAction=addLocation&lnkRoom=tradingsg_stockTransfer&showHTML=0";
        
        $text = "
        <form id='portalForm' class='yform columnar' method='post' action='{$formAction}'>
            <fieldset>
                {$formObj->getTBRow('Value', 'value')}
            </fieldset>
            
        </form>
        ";

        return $text;
    }

    /**
     *
     */
    function getQuickSearch() {
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpUtil = Zend_Registry::get('cpUtil');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');
        $ln = Zend_Registry::get('ln');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        $date1   = $fn->getReqParam('date_1');
        $date2   = $fn->getReqParam('date_2');
        
        $sqlstocktrans = "
        SELECT site_id,title 
        FROM site 
        ";
        $to_location   = $fn->getReqParam('to_location');
        $from_location = $fn->getReqParam('from_location');
        
        if($from_location == ''){
            $from_location = $cpSiteIdSession;
        }
        
        $text = "
        <td>
            {$formObj->getDateRangeRow('Date:', 'date', $date1, $date2)}
        </td>
        <td>
            <select name='from_location'>
                <option value=''>From Location</option>
                {$dbUtil->getDropDownFromSQLCols2($db, $sqlstocktrans, $from_location)}
            </select>
        </td>    
        <td>
            <select name='to_location'>
                <option value=''>To Location</option>
                {$dbUtil->getDropDownFromSQLCols2($db, $sqlstocktrans, $to_location)}
            </select>
        </td>    
        ";


        return $text;
    }

    /**
     *
     */
    function getBatchProductSelectStock() {
        $db     = Zend_Registry::get('db');
        $fn     = Zend_Registry::get('fn');
        $tv     = Zend_Registry::get('tv');
        $cpCfg  = Zend_Registry::get('cpCfg');
        $dbUtil = Zend_Registry::get('dbUtil');
        
        $product_id        = $fn->getReqParam('product_id');
        $stockMain         = $fn->getReqParam('stock');
        $site_id           = $fn->getReqParam('site_id');
        $transfer_type     = $fn->getReqParam('transfer_type');
        $stock_transfer_id = $fn->getReqParam('stock_transfer_id');
        $siteId            = $fn->getSessionParam('cp_site_id');

        if($transfer_type == "internal") {
            $siteId = $siteId;

            if($site_id == "1") {
                $stockField = "current_stock";
            } else {
                $sqlLocation = "
                SELECT internal_location_id
                      ,title 
                FROM internal_location 
                WHERE internal_location_id = {$site_id}
                ";
                $resultLocation = $db->sql_query($sqlLocation);
                $rowLocation    = $db->sql_fetchrow($resultLocation);
                $toLocation     = strtolower($rowLocation['title']);
                $toLocation     = str_replace(' ', '_', $toLocation);
                $stockField     = "{$toLocation}";
            }
        } else {
            $siteId     = $site_id;
            $stockField = "current_stock";
        }

        $appendSql   = '';
        $sqlAppendSt = '';
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSql = "AND po.site_id = {$siteId}";
        }

        $appendSqlStk   = "";
        if($cpCfg['cp.hasMultiUniqueSites']  == true){
            $appendSqlStk = "AND ibs.site_id = '{$siteId}'";
        }

        $SQLPO ="
        SELECT p.title
              ,p.unit
              ,p.item_code
              ,po.po_code AS po_code
              ,po.purchase_order_id AS purchase_order_id
              ,pp.cost_price
              ,pp.pack_size
              ,pp.selling_price
              ,pp.qty_requested AS qty
              ,pp.qty
              ,pp.gst
              ,pp.expiry_date
              ,p.hsn AS hsn_code
              ,p.product_id AS product_id
              ,p.title AS main_product_title
              ,p.item_code AS main_product_code
              ,ibs.batch_no AS batch_no
              ,ibs.po_product_id
              ,ibs.{$stockField}
              ,ibs.{$stockField} AS stock
        FROM inventory_batchwise_stock ibs
        LEFT JOIN (po_product pp) ON (pp.po_product_id = ibs.po_product_id)
        LEFT JOIN (product p) ON (p.product_id = ibs.product_id)
        LEFT JOIN (purchase_order po) ON (po.purchase_order_id = ibs.purchase_order_id)
        WHERE ibs.product_id = {$product_id}
        AND po.status != 'Cancelled'
        {$appendSqlStk}
        HAVING stock > 0
        ORDER BY ibs.po_product_id
        ";
        $resultPo = $db->sql_query($SQLPO);
        $numRows = $db->sql_numrows($resultPo);

        $rows = "";
        $count = 1;
        $batchwiseStock = 0;
        while ($rowPo = $db->sql_fetchrow($resultPo)){
            $selling_price = $rowPo['selling_price'];
            if($selling_price == ""){
                $selling_price = 0;
            }

            $appendSqlSiteStock = '';
            if($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSqlSiteStock = "AND ibs.site_id = {$siteId}";
            }

            $SQLStock ="
            SELECT ibs.{$stockField} AS stock
            FROM inventory_batchwise_stock ibs
            LEFT JOIN (po_product pp) ON (pp.po_product_id = ibs.po_product_id)
            WHERE ibs.po_product_id = {$rowPo['po_product_id']}
            {$appendSqlSiteStock}
            ";
            $resultStock = $db->sql_query($SQLStock);
            $rowStock    = $db->sql_fetchrow($resultStock);
            $stock       = $rowStock['stock'];
            
            $selling_price  = number_format($selling_price, 2);
            $expiry_date    = $rowPo['expiry_date'];
            $productNameRow = " <a class='batchProductAdd' stock_transfer_id='{$stock_transfer_id}' batch_no='{$rowPo['batch_no']}' po_product_id='{$rowPo['po_product_id']}' product_id='{$rowPo['product_id']}'>
                                    {$rowPo['title']}
                                </a>";

            $poTd = "";
            $po_code = 'PO - '.$rowPo['po_code'];
            $po_code = "<a href='index.php?_topRm=pharmacy&module=tradingsg_purchaseOrder&_action=edit&record_id={$rowPo['purchase_order_id']}' target='_blank'><u>PO - {$rowPo['po_code']}</u></a>";

            $poTd = "<td>{$po_code}</td>";

            $expiry_date = $fn->getCPDate($rowPo['expiry_date'],"d-m-Y");

            $rows .= "
            <tr>
                <td>{$productNameRow}</td>
                <td>{$rowPo['batch_no']}</td>
                <td>{$expiry_date}</td>
                <td class='txtCenter'><b>{$rowPo['stock']}</b></td>
                <td class='txtRight'>{$selling_price}</td>
                {$poTd}
            </tr>
            ";

            $count++;
            $mainProdTitle = $rowPo['main_product_title'];
            $mainProdCode  = $rowPo['main_product_code'];
        }

        $poTh = "<th>PO Code</th>";

        $header = "
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Batch No</th>
                <th>Expiry</th>
                <th class='txtCenter'>Stock</th>
                <th class='txtRight'>Selling Price</th>
                {$poTh}
            </tr>
        </thead>
        ";

        $text = "
        <div class='linkPortalWrapper tradingsg_pos_tradingsg_batchProductLink'>
            <div class='header' expanded='1'>
                <div class='floatbox'>
                    <div class='float_left'>{$mainProdCode} - {$mainProdTitle} - Stock Overall : {$stockMain}</div>
                    <div class='txtRight'>
                        <span class='count'>({$numRows})</span>
                        <div class='toggle'></div>
                    </div>
                </div>
            </div>
            <div class='linkPortalDataWrapper'>
                <form id='batchProductViewPo' class='batchProductViewPo' method='post'>
                    <table class='thinlist' id='batchProductTable'>
                        {$header}
                        <tbody>
                            {$rows}
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
        ";

        return $text;
    }

    /**
     *
     */
    function getBatchProductSelectStock1() {
        $db      = Zend_Registry::get('db');
        $fn      = Zend_Registry::get('fn');
        $tv      = Zend_Registry::get('tv');
        $cpCfg   = Zend_Registry::get('cpCfg');
        $dbUtil  = Zend_Registry::get('dbUtil');
        
        $product_id = $fn->getReqParam('product_id');
        $site_id    = $fn->getReqParam('site_id');
        $stock_transfer_id = $fn->getReqParam('stock_transfer_id');

        $SQLStockTransfer = "
        SELECT st.*
        FROM stock_transfer st
        WHERE st.stock_transfer_id = {$stock_transfer_id}
        ";
        $resultStockTransfer = $db->sql_query($SQLStockTransfer);
        $rowStockTransfer    = $db->sql_fetchrow($resultStockTransfer);

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
                   ,pp.po_product_id
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
                   ,'STOCK TRANSFER' AS stock_from
            FROM stock_transfer_history sth
            LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
            LEFT JOIN po_product pp ON (pp.po_product_id = sth.po_product_id)
            LEFT JOIN product p ON (p.product_id = pp.product_id)
            WHERE sth.product_id = {$product_id}
              {$sqlAppendSt}
            AND st.status = 'Delivered'        
            GROUP BY batch_no
            ORDER BY batch_no
            ";
        }

        $SQLPO ="
        SELECT p.title
              ,p.unit
              ,p.item_code
              ,po.po_code AS po_code
              ,po.purchase_order_id AS purchase_order_id
              ,pp.cost_price
              ,pp.po_product_id
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
              ,'PURCHASE ORDER' AS stock_from
        FROM po_product pp
        LEFT JOIN product p ON (p.product_id = pp.product_id)
        LEFT JOIN (purchase_order po) ON (po.purchase_order_id = pp.purchase_order_id)
        WHERE pp.product_id = {$product_id}
        AND po.status != 'Cancelled'
        {$appendSql}
        GROUP BY pp.batch_no
        {$stockTransferSQLForMultiSite}
        ";
        $resultPo = $db->sql_query($SQLPO);
        $numRows = $db->sql_numrows($resultPo);
        $rows = "";

        $SQLsitedetail="
        SELECT site_id
               ,title
        FROM site
        WHERE site_id = {$rowStockTransfer['from_location']}
        ";
        $resultsitedetail = $db->sql_query($SQLsitedetail);
        $rowsitedetail = $db->sql_fetchrow($resultsitedetail);

        while ($rowPo = $db->sql_fetchrow($resultPo)){
            $selling_price = $rowPo['selling_price'];
            if($selling_price == ""){
                $selling_price = 0;
            }

            $selling_price  = number_format($selling_price, 2);
            $productNameRow = "<input class='batchProductId' type='checkbox' name='batchProductId' product_id='{$rowPo['product_id']}' value='{$rowPo['batch_no']}'>";
            $expiry_date    = $fn->getCPDate($rowPo['expiry_date'], 'd-m-Y');
            $productNameRow = " <a class='batchProductAdd' stock_transfer_id='{$stock_transfer_id}' batch_no='{$rowPo['batch_no']}' product_id='{$rowPo['product_id']}' po_product_id='{$rowPo['po_product_id']}'>
                                    {$rowPo['title']}
                                </a>";


            $StockSql = "
            SELECT
                (SELECT SUM(qty) FROM po_product pp
                 LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                 WHERE pp.product_id = {$rowPo['product_id']}
                 AND pp.batch_no = '{$rowPo['batch_no']}'
                 AND po.status != 'Cancelled'
                 AND po.site_id = {$rowsitedetail['site_id']}) as product_qty_purchased

               ,(SELECT SUM(damage_qty) FROM po_product pp
                 LEFT JOIN purchase_order po ON (po.purchase_order_id=pp.purchase_order_id)
                 WHERE pp.product_id = {$rowPo['product_id']}
                 AND pp.batch_no = '{$rowPo['batch_no']}'
                 AND po.site_id = {$rowsitedetail['site_id']}) as damage_qty

                ,(SELECT SUM(inItm.qty) FROM invoice_item inItm
                LEFT JOIN (`invoice` inv) ON (inv.invoice_id = inItm.invoice_id)
                WHERE inItm.record_id = {$rowPo['product_id']}
                AND inItm.not_add_in_stock != 1
                AND inItm.batch_no = '{$rowPo['batch_no']}'
                AND inv.status = 'Paid'
                AND inv.invoice_type = 'POS'
                AND inv.site_id = {$rowsitedetail['site_id']}
                ) as product_qty_sold

                ,(SELECT SUM(sth.qty)
                FROM stock_transfer_history sth
                LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
                WHERE sth.product_id = {$rowPo['product_id']}
                AND sth.batch_no = '{$rowPo['batch_no']}'
                AND st.from_location = '{$rowsitedetail['site_id']}'
                AND st.status = 'Delivered'
                ) as product_qty_transferred_from_location_stock

                ,(SELECT SUM(sth.qty)
                FROM stock_transfer_history sth
                LEFT JOIN stock_transfer st ON (st.stock_transfer_id = sth.stock_transfer_id)
                WHERE sth.product_id = {$rowPo['product_id']}
                AND sth.batch_no = '{$rowPo['batch_no']}'
                AND st.to_location = '{$rowsitedetail['site_id']}'
                AND st.status = 'Delivered'
                ) as product_qty_transferred_to_location_stock

                ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled' )
                WHERE ini.record_id = {$rowPo['product_id']}
                  AND ini.batch_no = '{$rowPo['batch_no']}'
                  AND srh.status = 'Approved'
                  AND inv.site_id = {$rowsitedetail['site_id']}
                ) as sales_return_qty
            ";
            $resultStockSql = $db->sql_query($StockSql);
            $rowStockSql    = $db->sql_fetchrow($resultStockSql);
            
            $stock = $rowStockSql['product_qty_purchased'] - $rowStockSql['product_qty_transferred_from_location_stock'] + $rowStockSql['product_qty_transferred_to_location_stock'] - $rowStockSql['product_qty_sold'] + $rowStockSql['sales_return_qty'] - $rowStockSql['damage_qty'];

            $rows .= "
            <tr>
                <td>{$productNameRow}</td>
                <td>{$rowPo['batch_no']}</td>
                <td class='txtRight'>{$rowPo['unit']}</td>
                <td class='txtCenter'>{$stock}</td>
                <td class='txtRight'>{$selling_price}</td>
                <td class='txtCenter'>{$rowPo['gst']}</td>
                <td>{$expiry_date}</td>
                <td>{$rowPo['hsn_code']}</td>
            </tr>
            ";

            $mainProdTitle = $rowPo['main_product_title'];
            $mainProdCode  = $rowPo['main_product_code'];
            $stock_from    = $rowPo['stock_from'];
        }

        $header = "
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Batch No</th>
                <th class='txtRight'>UOM</th>
                <th class='txtCenter'>Qty</th>
                <th class='txtRight'>Selling Price</th>
                <th class='txtCenter'>GST(%)</th>
                <th>Expiry Date</th>
                <th>HSN Code</th>
            </tr>
        </thead>
        ";

        $text = "
        <div class='linkPortalWrapper tradingsg_pos_tradingsg_batchProductLink'>
            <div class='header' expanded='1'>
                <div class='floatbox'>
                    <div class='float_left'>{$mainProdCode} - {$mainProdTitle}</div>
                    <div class='txtRight'>
                        <span class='count'>({$numRows})</span>
                        <div class='toggle'></div>
                    </div>
                </div>
            </div>
            <div class='linkPortalDataWrapper'>
                <form id='batchProductViewPo' class='batchProductViewPo' method='post'>
                    <table class='thinlist' id='batchProductTable'>
                        {$header}
                        <tbody>
                            {$rows}
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
        ";

        return $text;
    }

    /**
     *
     */
    function getDeductStockForPatientDisplay() {
        $db      = Zend_Registry::get('db');
        $fn      = Zend_Registry::get('fn');
        $tv      = Zend_Registry::get('tv');
        $cpCfg   = Zend_Registry::get('cpCfg');
        $dbUtil  = Zend_Registry::get('dbUtil');
        $formObj = Zend_Registry::get('formObj');

        $product_id                = $fn->getReqParam('product_id');
        $po_product_id             = $fn->getReqParam('po_product_id');
        $stock_transfer_id         = $fn->getReqParam('stock_transfer_id');
        $stock_transfer_history_id = $fn->getReqParam('stock_transfer_history_id');
        $batch_no                  = $fn->getReqParam('batch_no');
        $siteId                    = $fn->getSessionParam('cp_site_id');

        $SQLProduct = "
        SELECT item_code
              ,title
        FROM product
        WHERE product_id = '{$product_id}'
        ";
        $resultProduct = $db->sql_query($SQLProduct);
        $rowProduct    = $db->sql_fetchrow($resultProduct);

        $TypeArr = array("OP", "IP");

        $text = "
        <div class='linkPortalWrapper tradingsg_stockTransfer_tradingsg_deductStockPatientLink'>
            <div class='header' expanded='1'>
                <div class='floatbox'>
                    <div class='float_left'>{$rowProduct['item_code']} - {$rowProduct['title']}</div>
                </div>
            </div>
            <div class='linkPortalDataWrapper'>
                <form id='deductStockPatientView' class='yform columnar cpJqForm deductStockPatientView' method='post'>
                    {$formObj->getDDRowByArr('Type', 'patient_type', $TypeArr)}
                    <div class='visitOrOPCodeSearch displayNone'>
                        {$formObj->getTBRow('Search IP/OP Code', 'search_code', '')}
                    </div>
                    <div class='patientDetails'>
                    </div>
                    <input name='product_id' type='hidden' value='{$product_id}'>
                    <input name='po_product_id' type='hidden' value='{$po_product_id}'>
                    <input name='stock_transfer_id' type='hidden' value='{$stock_transfer_id}'>
                    <input name='stock_transfer_history_id' type='hidden' value='{$stock_transfer_history_id}'>
                    <input name='batch_no' type='hidden' value='{$batch_no}'>
                </form>
            </div>
        </div>
        ";

        return $text;
    }
}