<?
class CPL_Admin_Modules_Hms_Renewal_View extends CP_Common_Lib_ModuleViewAbstract
{

    /*
    |--------------------------------------------------------------------------
    | LIST PAGE
    |--------------------------------------------------------------------------
    */

 function getList($dataArray){

    $listObj = Zend_Registry::get('listObj');

    $count = 0;
    $rows  = '';

    foreach ($dataArray as $row){

        $expiry_date = '';

        if(!empty($row['expiry_date']) && $row['expiry_date'] != '0000-00-00'){
            $expiry_date = date('d-M-Y', strtotime($row['expiry_date']));
        }

        $renewal_cost = '';

        if($row['renewal_cost'] != ''){
            $renewal_cost = '₹ '.number_format($row['renewal_cost'],2);
        }

        $rows .= "
        {$listObj->getListRowHeader($row, $count)}
        {$listObj->getListDataCell($row['equipment_name'])}
        {$listObj->getListDataCell($row['service_name'])}
          {$listObj->getListDataCell($row['warranty_end'])}
        {$listObj->getListDataCell($renewal_cost)}
        {$listObj->getListDataCell($row['renewal_status'])}
        {$listObj->getListRowEnd($row['renewals_id'])}
        ";

        $count++;
    }

    $text = "
    {$listObj->getListHeader()}
    {$listObj->getListHeaderCell('Equipment','equipment_name')}
    {$listObj->getListHeaderCell('Service','service_name')}
    {$listObj->getListHeaderCell('Expiry Date','expiry_date')}
    {$listObj->getListHeaderCell('Renewal Cost','renewal_cost')}
    {$listObj->getListHeaderCell('Status','renewal_status')}
    {$listObj->getListHeaderEnd()}
    {$rows}
    {$listObj->getListFooter()}
    ";

    return $text;
}


    /*
    |--------------------------------------------------------------------------
    | NEW PAGE
    |--------------------------------------------------------------------------
    */

    function getNew()
{
    $formObj = Zend_Registry::get('formObj');

    $SQLEquipment = "
    SELECT
        ym.yearly_maintenance_records_id,
        CONCAT_WS(
            ' - ',
            ym.equipment_code,
            vl.value
        ) AS equipment_name
    FROM yearly_maintenance_records ym
    LEFT JOIN valuelist vl
        ON vl.valuelist_id = ym.valuelist_id
    WHERE ym.flag = '1'
    ORDER BY ym.equipment_code
    ";

    $fieldset = "
    {$formObj->getDDRowBySQL(
        'Equipment Name',
        'yearly_maintenance_records_id',
        $SQLEquipment
    )}

    {$formObj->getTBRow(
        'Service Name',
        'service_name'
    )}
    ";

    return $formObj->getFieldSetWrapped(
        'Renewal Details',
        $fieldset
    );
}

    function getAddNewValuelistForm() {
        $tv      = Zend_Registry::get('tv');
        $fn      = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');

        $valuelist_name = $fn->getReqParam('valuelist_name');
        $formAction = "index.php?_topRm={$tv['topRm']}&module=hms_renewal&_spAction=addNewValuelistFormSubmit&showHTML=0";

        $text = "
        <form id='portalForm' class='yform columnar addNewDropdownValueForm' method='post' action='{$formAction}'>
            {$formObj->getTBRow('Value', 'valuelist_value')}
            <input type='hidden' name='valuelist_name' value='{$valuelist_name}' />
        </form>
        ";

        return $text;
    }

    function getEditValuelistForm() {
        $fn      = Zend_Registry::get('fn');
        $db      = Zend_Registry::get('db');
        $formObj = Zend_Registry::get('formObj');
        $tv      = Zend_Registry::get('tv');

        $valuelist_id = $fn->getReqParam('valuelist_id');
        $valuelist_name = $fn->getReqParam('valuelist_name');
        $formAction = "index.php?_topRm={$tv['topRm']}&module=hms_renewal&_spAction=editValuelistFormSubmit&showHTML=0&valuelist_id={$valuelist_id}";

        $SQL = "
        SELECT value
        FROM valuelist
        WHERE valuelist_id = '{$valuelist_id}'
        ";
       $SQLDetails = "
SELECT
    vendor,
    serial_number,
    purchase_date,
    warranty_end
FROM yearly_maintenance_records
WHERE yearly_maintenance_records_id='{$row['yearly_maintenance_records_id']}'
";

$result = $db->sql_query($SQLDetails);
$equipmentRow = $db->sql_fetchrow($result);
        $row = $db->sql_fetchrow($result);

        $text = "
        <form id='portalForm' class='yform columnar addNewDropdownValueForm' method='post' action='{$formAction}'>
            {$formObj->getTBRow('Value', 'valuelist_value', $row['value'])}
            <input type='hidden' name='valuelist_name' value='{$valuelist_name}' />
            <input type='hidden' name='valuelist_id' value='{$valuelist_id}' />
        </form>
        ";

        return $text;
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT PAGE
    |--------------------------------------------------------------------------
    */

 function getEdit($row){

    $formObj = Zend_Registry::get('formObj');
    $db      = Zend_Registry::get('db');
    $tv      = Zend_Registry::get('tv');

    $formObj->mode = $tv['action'];

    // Equipment Dropdown
    $SQLEquipment = "
    SELECT
        ym.yearly_maintenance_records_id,
        CONCAT(
            ym.equipment_code,
            ' - ',
            vl.value
        ) AS equipment_name
    FROM yearly_maintenance_records ym
    LEFT JOIN valuelist vl
        ON vl.valuelist_id = ym.valuelist_id
    WHERE ym.flag='1'
    ORDER BY ym.equipment_code
    ";

    // Fetch Equipment Details
    $SQLDetails = "
    SELECT
        vendor,
        serial_number,
        purchase_date,
        warranty_end
    FROM yearly_maintenance_records
    WHERE yearly_maintenance_records_id='{$row['yearly_maintenance_records_id']}'
    ";

    $result = $db->sql_query($SQLDetails);
    $equipmentRow = $db->sql_fetchrow($result);

    $formAddPaymentStatus = "index.php?_topRm={$tv['topRm']}&module=hms_renewal&_spAction=addNewValuelistForm&valuelist_name=Payment Status For Renewal&showHTML=0";

    $formAddRenewalStatus = "index.php?_topRm={$tv['topRm']}&module=hms_renewal&_spAction=addNewValuelistForm&valuelist_name=Renewal Status For Renewal&showHTML=0";

    $expPaymentStatus = array(
        'notesRight' => "<a href='{$formAddPaymentStatus}' class='mr20 addNewValue' valuelist_name='Payment Status For Renewal' field_name='payment_status'>Add</a>"
    );

    $expRenewalStatus = array(
        'notesRight' => "<a href='{$formAddRenewalStatus}' class='mr20 addNewValue' valuelist_name='Renewal Status For Renewal' field_name='renewal_status'>Add</a>"
    );

    $text = "

    <div class='linkPortalWrapper'>

        <div expanded='1' class='header'>
            <div class='floatbox'>
                <div class='float_left'>Renewal Details</div>
                <div class='toggle'></div>
            </div>
        </div>

        <div>
            <div class='linkPortalDataWrapper'>

                <table class='thinlist'>
                    <tbody>

                        <tr>

                            <td>

                            {$formObj->getDDRowBySQL(
                                'Equipment',
                                'yearly_maintenance_records_id',
                                $SQLEquipment,
                                $row['yearly_maintenance_records_id']
                            )}

                            </td>

                            <td>

                            {$formObj->getTBRow(
                                'Service Name',
                                'service_name',
                                $row['service_name']
                            )}

                            </td>

                            <td>

                            {$formObj->getTBRow(
                                'Vendor Name',
                                'vendor_name',
                                $equipmentRow['vendor'],
                                array('readonly'=>1)
                            )}

                            </td>

                        </tr>

                        <tr>

                            <td>

                            {$formObj->getTBRow(
                                'License Number',
                                'license_number',
                                $equipmentRow['serial_number'],
                                array('readonly'=>1)
                            )}

                            </td>

                            <td>

                            {$formObj->getTBRow(
                                'Renewal Cost',
                                'renewal_cost',
                                $row['renewal_cost']
                            )}

                            </td>

                            <td>

                            {$formObj->getTBRow(
                                'Reminder Days',
                                'reminder_days',
                                $row['reminder_days']
                            )}

                            </td>

                        </tr>

                        <tr>

                            <td>

                            {$formObj->getDateRow(
                                'Start Date',
                                'start_date',
                                $equipmentRow['purchase_date'],
                                array(
                                    'dateFormat'=>'yy-mm-dd',
                                    'readonly'=>1
                                )
                            )}

                            </td>

                            <td>

                            {$formObj->getDateRow(
                                'Expiry Date',
                                'expiry_date',
                                $equipmentRow['warranty_end'],
                                array(
                                    'dateFormat'=>'yy-mm-dd',
                                    'readonly'=>1
                                )
                            )}

                            </td>

                            <td></td>

                        </tr>

                        <tr>

                            <td>

                            {$formObj->getDDRowByVL(
                                'Payment Status',
                                'payment_status',
                                'Payment Status For Renewal',
                                $row['payment_status'],
                                $expPaymentStatus
                            )}

                            </td>

                            <td>

                            {$formObj->getDDRowByVL(
                                'Renewal Status',
                                'renewal_status',
                                'Renewal Status For Renewal',
                                $row['renewal_status'],
                                $expRenewalStatus
                            )}

                            </td>

                            <td></td>

                        </tr>

                        <tr>

                            <td colspan='3'>

                            {$formObj->getTARow(
                                'Notes',
                                'notes',
                                $row['notes']
                            )}

                            </td>

                        </tr>

                        <tr>

                            <td colspan='3' class='creModdate'>

                            {$formObj->getCreationModificationText($row)}

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>
        </div>

    </div>

    ";

    return $text;
}


    /*
    |--------------------------------------------------------------------------
    | DETAIL PAGE
    |--------------------------------------------------------------------------
    */

    function getDetail($row){
        return $this->getEdit($row);
    }

    function getRightPanel($row){
        $media = Zend_Registry::get('media');
        return $media->getRightPanelMediaDisplay('Attachments', 'hms_renewal', 'attachment', $row);
    }


    /*
    |--------------------------------------------------------------------------
    | PRINT PAGE
    |--------------------------------------------------------------------------
    */

    function getPrintDetail($row){
        return $this->getDetail($row);
    }


    /*
    |--------------------------------------------------------------------------
    | SEARCH PAGE
    |--------------------------------------------------------------------------
    */

    function getSearch(){
        $formObj = Zend_Registry::get('formObj');
        $tv = Zend_Registry::get('tv');
$$SQLEquipment = "
SELECT
    ym.yearly_maintenance_records_id,
    CONCAT_WS(
        ' - ',
        ym.equipment_code,
        vl.value
    ) AS equipment_name
FROM yearly_maintenance_records ym
LEFT JOIN valuelist vl
    ON vl.valuelist_id = ym.valuelist_id
WHERE ym.flag = 1
ORDER BY ym.equipment_code
";

        $expSearch = array();
        $spArray = array(
            'Flagged',
            'Not-Flagged'
        );

        $fieldset = "
        {$formObj->getTBRow('Service Name','service_name', $tv['service_name'])}
       {$formObj->getDDRowBySQL(
    'Equipment',
    'yearly_maintenance_records_id',
    $SQLEquipment,
    $tv['yearly_maintenance_records_id'],
    $expSearch
)}
        {$formObj->getDDRowByVL('Renewal Status','renewal_status','Renewal Status For Renewal',$tv['renewal_status'])}
        {$formObj->getDDRowByArr('Special Search','special_search',$spArray,$tv['special_search'])}
        ";

        return "
        {$formObj->getFieldSetWrapped(
            'Renewal Search',
            $fieldset
        )}
        ";
    }

    function getQuickSearch(){
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpUtil = Zend_Registry::get('cpUtil');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');

        $valuelist_id = $fn->getReqParam('valuelist_id');
        $special_search = $fn->getReqParam('special_search');

       $SQLEquipment = "
SELECT
    ym.yearly_maintenance_records_id,
    CONCAT_WS(
        ' - ',
        ym.equipment_code,
        vl.value
    ) AS equipment_name
FROM yearly_maintenance_records ym
LEFT JOIN valuelist vl
    ON vl.valuelist_id = ym.valuelist_id
WHERE ym.flag = 1
ORDER BY ym.equipment_code
";

        $spArray = array(
            'Flagged',
            'Not-Flagged'
        );

        $text = "
        <td>
            <select name='yearly_maintenance_records_id'>
    <option value=''>Equipment</option>
    {$dbUtil->getDropDownFromSQLCols2($db, $SQLEquipment, $fn->getReqParam('yearly_maintenance_records_id'))}
</select>
        </td>
        <td>
            <select name='special_search'>
                <option value=''>Special Search</option>
                {$cpUtil->getDropDown1($spArray, $special_search)}
            </select>
        </td>
        ";

        return $text;
    }

}