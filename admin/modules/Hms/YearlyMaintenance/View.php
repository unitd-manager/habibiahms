<?
class CPL_Admin_Modules_Hms_YearlyMaintenance_View extends CP_Common_Lib_ModuleViewAbstract
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

        $warranty_end = '';

        if (!empty($row['warranty_end']) && $row['warranty_end'] != '0000-00-00') {
            $warranty_end = date('d-M-Y', strtotime($row['warranty_end']));
        }

        $category_name = isset($row['category_name'])
            ? $row['category_name']
            : '';
            $equipment_code = !empty($row['equipment_code'])
    ? 'EQ-' . $row['equipment_code']
    : '';

        $rows .= "
        {$listObj->getListRowHeader($row, $count)}
        {$listObj->getListDataCell($category_name)}
        {$listObj->getListDataCell($equipment_code)}
        {$listObj->getListDataCell($row['location'])}
        {$listObj->getListDataCell($warranty_end)}
        {$listObj->getListDataCell($row['maintenance_frequency'])}
        {$listObj->getListDataCell($row['vendor'])}
        {$listObj->getListRowEnd($row['yearly_maintenance_records_id'])}
        ";

        $count++;
    }

    $text = "
    {$listObj->getListHeader()}
    {$listObj->getListHeaderCell('Equipment Name','category_name')}
    {$listObj->getListHeaderCell('Equipment Code','equipment_code')}
    {$listObj->getListHeaderCell('Location','location')}
    {$listObj->getListHeaderCell('Warranty End','warranty_end')}
    {$listObj->getListHeaderCell('Frequency','maintenance_frequency')}
    {$listObj->getListHeaderCell('Vendor','vendor')}
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
    $tv = Zend_Registry::get('tv');

    $SQLCategory = "
    SELECT valuelist_id, value
    FROM valuelist
    WHERE key_text = 'Yearly Maintenance Category'
    ORDER BY value
    ";

    $formAddCategory = "index.php?_topRm={$tv['topRm']}&module=hms_yearlyMaintenance&_spAction=addNewValuelistForm&valuelist_name=Yearly Maintenance Category&showHTML=0";

    $expCategory = array(
        'notesRight' => "<a href='{$formAddCategory}' class='mr20 addNewValue' valuelist_name='Yearly Maintenance Category'>Add</a>"
    );

    $fieldset = "
    {$formObj->getDDRowBySQL(
        'Equipment',
        'valuelist_id',
        $SQLCategory,
        '',
        $expCategory
    )}
    ";

    $text = "
    {$formObj->getFieldSetWrapped(
        'Yearly Maintenance Details',
        $fieldset
    )}
    ";

    return $text;
}

    function getAddNewValuelistForm() {
        $tv      = Zend_Registry::get('tv');
        $fn      = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');

        $valuelist_name = $fn->getReqParam('valuelist_name');
        $formAction = "index.php?_topRm={$tv['topRm']}&module=hms_yearlyMaintenance&_spAction=addNewValuelistFormSubmit&showHTML=0";

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
        $formAction = "index.php?_topRm={$tv['topRm']}&module=hms_yearlyMaintenance&_spAction=editValuelistFormSubmit&showHTML=0&valuelist_id={$valuelist_id}";

        $SQL = "
        SELECT value
        FROM valuelist
        WHERE valuelist_id = '{$valuelist_id}'
        ";
        $result = $db->sql_query($SQL);
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
    $tv      = Zend_Registry::get('tv');

    $formObj->mode = $tv['action'];
    // Add here
    $equipment_code = '';

    if ($row['equipment_code'] != '') {
        $equipment_code = 'EQ-' . $row['equipment_code'];
    }

    $SQLCategory = "
    SELECT valuelist_id, value
    FROM valuelist
    WHERE key_text = 'Yearly Maintenance Category'
    ORDER BY value
    ";

    $formAddCategory = "index.php?_topRm={$tv['topRm']}&module=hms_yearlyMaintenance&_spAction=addNewValuelistForm&valuelist_name=Yearly Maintenance Category&showHTML=0";

    $expVl = array(
        'notesRight' => "<a href='{$formAddCategory}' class='mr20 addNewValue' valuelist_name='Yearly Maintenance Category'>Add</a>"
            . "<a href='javascript:void(0);' class='mr20 editSelectedValue' valuelist_name='Yearly Maintenance Category'>Edit</a>"
            . "<a href='javascript:void(0);' class='deleteSelectedValue' valuelist_name='Yearly Maintenance Category'>Delete</a>"
    );

    $text = "
    <div class='linkPortalWrapper'>
        <div expanded='1' class='header'>
            <div class='floatbox'>
                <div class='float_left'>Yearly Maintenance Details</div>
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
                                'valuelist_id',
                                $SQLCategory,
                                $row['valuelist_id'],
                                $expVl
                            )}
                            </td>
                            <td>

                           {$formObj->getTBRow(
    'Equipment Code',
    'equipment_code',
    $equipment_code,
    array(
        'extra' => 'readonly'
    )
)}
    </td>

                            <td>
                            {$formObj->getTBRow(
                                'Brand',
                                'brand',
                                $row['brand']
                            )}
                            </td>
                        </tr>

                        <tr>
                            <td>
                            {$formObj->getTBRow(
                                'Model',
                                'model',
                                $row['model']
                            )}
                            </td>

                            <td>
                            {$formObj->getTBRow(
                                'Serial Number',
                                'serial_number',
                                $row['serial_number']
                            )}
                            </td>

                            <td>
                            {$formObj->getTBRow(
                                'Location',
                                'location',
                                $row['location']
                            )}
                            </td>
                        </tr>

                        <tr>
                            <td>
                            {$formObj->getDateRow(
                                'Purchase Date',
                                'purchase_date',
                                $row['purchase_date'],
                                array('dateFormat' => 'yy-mm-dd')
                            )}
                            </td>

                            <td>
                            {$formObj->getDateRow(
                                'Warranty End',
                                'warranty_end',
                                $row['warranty_end'],
                                array('dateFormat' => 'yy-mm-dd')
                            )}
                            </td>

                            <td>
                            {$formObj->getTBRow(
                                'Maintenance Frequency',
                                'maintenance_frequency',
                                $row['maintenance_frequency']
                            )}
                            </td>
                        </tr>

                        <tr>
                            <td>
                            {$formObj->getTBRow(
                                'Vendor',
                                'vendor',
                                $row['vendor']
                            )}
                            </td>

                            <td>
                            {$formObj->getTBRow(
                                'Engineer Name',
                                'engineer_name',
                                $row['engineer_name']
                            )}
                            </td>

                            <td>
                            {$formObj->getTBRow(
                                'Phone',
                                'phone',
                                $row['phone']
                            )}
                            </td>
                        </tr>

                        <tr>
                            <td colspan='2'>
                            {$formObj->getTBRow(
                                'Email',
                                'email',
                                $row['email']
                            )}
                            </td>
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
        return $media->getRightPanelMediaDisplay('Attachments', 'hms_yearlyMaintenance', 'attachment', $row);
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

        $SQLCategory = "
        SELECT valuelist_id, value
        FROM valuelist
        WHERE key_text = 'Yearly Maintenance Category'
        ORDER BY value
        ";

        $expSearch = array();
        $spArray = array(
            'Flagged',
            'Not-Flagged'
        );

        $fieldset = "
        {$formObj->getTBRow('Service Name','service_name', $tv['service_name'])}
        {$formObj->getDDRowBySQL('Category','valuelist_id',$SQLCategory,$tv['valuelist_id'],$expSearch)}
        {$formObj->getDDRowByVL('Renewal Status','renewal_status','Renewal Status For Renewal',$tv['renewal_status'])}
        {$formObj->getDDRowByArr('Special Search','special_search',$spArray,$tv['special_search'])}
        ";

        return "
        {$formObj->getFieldSetWrapped(
            'Yearly Maintenance Search',
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

        $SQLCategory = "
        SELECT valuelist_id, value
        FROM valuelist
        WHERE key_text = 'Yearly Maintenance Category'
        ORDER BY value
        ";

        $spArray = array(
            'Flagged',
            'Not-Flagged'
        );

        $text = "
        <td>
            <select name='valuelist_id'>
                <option value=''>Category</option>
                {$dbUtil->getDropDownFromSQLCols2($db, $SQLCategory, $valuelist_id)}
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