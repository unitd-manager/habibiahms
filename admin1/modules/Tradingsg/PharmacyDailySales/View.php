<?
class CPL_Admin_Modules_Tradingsg_PharmacyDailySales_View extends CP_Common_Lib_ModuleViewAbstract
{
    /**
     *
     */
    function getList($dataArray){
        $listObj = Zend_Registry::get('listObj');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $count   = 0;
        $rows    = '';
        $grandTotal    = 0;

        foreach ($dataArray as $row){
            $date = $fn->getCPDate($row['date'],"d-m-Y");

            $totalAmount = $row['sales_amount'] + $row['excess_amount'];
            $grandTotal += $totalAmount;
            $totalAmount = number_format($totalAmount, 2);

            $rows .= "
            {$listObj->getListRowHeader($row, $count)}
            {$listObj->getGoToDetailText($count, $date)}
            {$listObj->getListDataCell($row['sales_amount'])}
            {$listObj->getListDataCell($row['excess_amount'])}
            {$listObj->getListDataCell($totalAmount, 'right')}
            {$listObj->getListRowEnd($row['pharma_daily_sales_id'])}
            ";

            $count++ ;
        }

        $grandTotal = number_format($grandTotal, 2);

        $text = "
        {$listObj->getListHeader()}
        {$listObj->getListHeaderCell('Date', 'p.date')}
        {$listObj->getListHeaderCell('Sales Amount', 'p.sales_amount')}
        {$listObj->getListHeaderCell('Excess Amount', 'p.excess_amount' )}
        {$listObj->getListHeaderCell('Total', '', 'txtRight')}
        {$listObj->getListHeaderEnd()}
        <tr bgcolor='#CFDBE2'>
            <th colspan='6' class='totalFeesInList'></th>
            <th class='totalFeesInList'>Grand Total: {$grandTotal}</th>
        </tr>
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

        $fielset1 = "
        {$formObj->getDateRow('Date', 'date')}
        ";

        $text = "
        {$formObj->getFieldSetWrapped('Key Details', $fielset1)}
        ";

        return $text;
    }

    /**
     *
     */
    function getEdit($row){
        $formObj = Zend_Registry::get('formObj');
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');

        $formObj->mode = $tv['action'];
        $expVl     = array('sqlType' => 'OneField');
        $expNoEdit = array('isEditable' => 0);

        $totalAmount = $row['sales_amount'] + $row['excess_amount'];
        $totalAmount = number_format($totalAmount, 2);

        $totalAmountTd = "
        <div class='type-text ym-fbox-text row_totalAmount non-editable'>
            <label for='fld_totalAmount'>Total</label>
            <div class='txt' id='fld_totalAmount'>
                <span class='value pharmacyTotalAmount'>{$totalAmount}</span>
            </div>
        </div>";

        $text = "
        <div class='linkPortalWrapper'>
            <div expanded='1' class='header'>
                <div class='floatbox'>
                    <div class='float_left'>Pharmacy Daily Sales Details</div>
                    <div class='toggle'></div>
                </div>
            </div>
            <div>
                <div class='linkPortalDataWrapper'>
                    <table class='thinlist'>
                        <tbody>
                            <tr>
                                <td width='20%'>{$formObj->getDateRow('Date', 'date', $row['date'])}</td>
                                <td width='20%'>{$formObj->getTBRow('Sales Amount', 'sales_amount', $row['sales_amount'])}</td>
                                <td width='20%'>{$formObj->getTBRow('Excess Amount', 'excess_amount', $row['excess_amount'])}</td>
                                <td width='20%'>{$totalAmountTd}</td>
                                <td width='20%'>{$formObj->getTARow('Notes', 'notes', $row['notes'])}</td>
                            </tr>
                            <tr>
                                <td colspan='4' class='creModdate'>{$formObj->getCreationModificationText($row)}</td>
                            </tr>
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
    function getSearch(){
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');

        $expVl = array('sqlType' => 'OneField');

        $spArray = array(
            "Flagged"
           ,"Not-Flagged"
        );

        $fielset = "
        {$formObj->getDDRowByArr('Special Search', 'special_search', $spArray)}
        ";

        $text = "
        {$formObj->getFieldSetWrapped('Company Details', $fielset)}
        ";

        return $text;
    }

    /**
     *
     */
    function getRightPanel($row){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $displayLinkData = Zend_Registry::get('displayLinkData');
        $media = Zend_Registry::get('media');


        $record_id = $fn->getIssetParam($row, 'pharma_daily_sales_id');

        $text = "
        {$media->getRightPanelMediaDisplay('Attachments', 'tradingsg_pharmacyDailySales', 'attachment', $row)}
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
        $fn = Zend_Registry::get('fn');

        $currentMonth    = $fn->getReqParam('currentMonth');

        $spArray = array(
            "Flagged"
           ,"Not-Flagged"
        );
        $currMonthArray = array(
             "All Records"
            ,"CurrentMonth"
        );

        if($currentMonth == ""){
            $currentMonth = 'CurrentMonth';
        }

        $text = "
        <td>
            <select name='currentMonth'>
                {$cpUtil->getDropDown1($currMonthArray, $currentMonth)}
           </select>
        </td>
        <td>
            <select name='special_search'>
                <option value=''>Special Search</option
                {$cpUtil->getDropDown1($spArray, $tv['special_search'])}
           </select>
        </td>
        ";

        return $text;
    }
}