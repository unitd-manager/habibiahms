<?
class CPL_Admin_Modules_Hms_YearlyMaintenance_Model extends CP_Common_Lib_ModuleModelAbstract
{
    /**
     * Main List SQL
     */
   function getSQL()
{
    $SQL = "
    SELECT
        ym.*,
        vl.value AS category_name
    FROM yearly_maintenance_records ym
    LEFT JOIN valuelist vl
        ON vl.valuelist_id = ym.valuelist_id
    ";

    return $SQL;
}

    /**
     * Search Conditions
     */
    function setSearchVar($linkRecType = '')
{
    $tv        = Zend_Registry::get('tv');
    $fn        = Zend_Registry::get('fn');
    $searchVar = Zend_Registry::get('searchVar');

    $searchVar->mainTableAlias = 'ym';

    $yearlyMaintenanceId = $fn->getReqParam('yearly_maintenance_records_id');
    $valuelist_id        = $fn->getReqParam('valuelist_id');
    $special_search      = $fn->getReqParam('special_search');
    $keyword             = trim($tv['keyword']);

    // Edit Page
    if ($yearlyMaintenanceId != '') {
        $searchVar->sqlSearchVar[] =
            "ym.yearly_maintenance_records_id = '{$yearlyMaintenanceId}'";
    }
    elseif ($tv['record_id'] != '') {
        $searchVar->sqlSearchVar[] =
            "ym.yearly_maintenance_records_id = '{$tv['record_id']}'";
    }
    else {

        // Category Filter
        if ($valuelist_id != '') {
            $searchVar->sqlSearchVar[] =
                "ym.valuelist_id = '{$valuelist_id}'";
        }

        // Flag Filter
        if ($special_search == 'Flagged') {
            $searchVar->sqlSearchVar[] = "ym.flag = 1";
        }
        elseif ($special_search == 'Not-Flagged') {
            $searchVar->sqlSearchVar[] =
                "(ym.flag <> 1 OR ym.flag IS NULL)";
        }

        // Keyword Search
        if ($keyword != '') {

            $searchVar->sqlSearchVar[] = "
            (
                ym.equipment_code LIKE '%{$keyword}%'
                OR ym.brand LIKE '%{$keyword}%'
                OR ym.model LIKE '%{$keyword}%'
                OR ym.serial_number LIKE '%{$keyword}%'
                OR ym.location LIKE '%{$keyword}%'
                OR ym.vendor LIKE '%{$keyword}%'
                OR ym.engineer_name LIKE '%{$keyword}%'
                OR ym.phone LIKE '%{$keyword}%'
                OR ym.email LIKE '%{$keyword}%'
            )";
        }

        // Default Sort
        $searchVar->sortOrder = "ym.yearly_maintenance_records_id DESC";
    }
}
    /**
     * Validation before Add
     */
    function getNewValidate()
{
    $validate = Zend_Registry::get('validate');

    $validate->resetErrorArray();

    $validate->validateData(
        'valuelist_id',
        'Please select Equipment Category'
    );

    if(count($validate->errorArray) == 0){
        return true;
    }

    return false;
}

    /**
     * Add Record
     */
    function getAdd()
{
    $fn = Zend_Registry::get('fn');
    $db = Zend_Registry::get('db');
    $validate = Zend_Registry::get('validate');

    if (!$this->getNewValidate()) {
        return $validate->getErrorMessageXML();
    }

    // Temporary: Use Product Code
    $equi = $fn->getSettingsValueByKey("nextEquipmentCode");

    $fa = $this->getFields();

    // Store Product Code in Equipment Code field
    $fa['equipment_code'] = $equi;

    $fa['creation_date'] = date('Y-m-d H:i:s');
    $fa['created_by'] = $fn->getSessionParam('userName');

    $id = $fn->addRecord($fa);

    // Increment Product Code
   $SQLUpdate = "
    UPDATE setting
    SET value = value + 1
    WHERE key_text = 'nextEquipmentCode'
    ";
    $db->sql_query($SQLUpdate);

    $fn->returnAfterNewSave($id);
}

    /**
     * Validation before Save
     */
    function getEditValidate()
    {
        return $this->getNewValidate();
    }

    /**
     * Save Record
     */
    function getSave()
    {
        $fn       = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $cpCfg    = Zend_Registry::get('cpCfg');

        if (!$this->getEditValidate()) {
            return $validate->getErrorMessageXML();
        }

        $fa = $this->getFields();

        $fa['modification_date'] = date('Y-m-d H:i:s');
        $fa['modified_by'] = $fn->getSessionParam('userName');

        $id = $fn->saveRecord($fa);

        $fn->returnAfterNewSave(
            $id,
            $cpCfg['cp.pagetoReturnAfterSave']
        );
    }

    /**
     * Save List Sort Order
     */
    function getSaveList()
    {
        $fn = Zend_Registry::get('fn');
        $fn->getSaveList();
    }

    /**
     * Fields Mapping
     */
    function getFields()
{
    $fn = Zend_Registry::get('fn');

    $fa = array();

    $fa = $fn->addToFieldsArray($fa, 'valuelist_id');
    $fa = $fn->addToFieldsArray($fa, 'brand');
    $fa = $fn->addToFieldsArray($fa, 'model');
    $fa = $fn->addToFieldsArray($fa, 'serial_number');
    $fa = $fn->addToFieldsArray($fa, 'location');
    $fa = $fn->addToFieldsArray($fa, 'purchase_date');
    $fa = $fn->addToFieldsArray($fa, 'warranty_end');
    $fa = $fn->addToFieldsArray($fa, 'maintenance_frequency');
    $fa = $fn->addToFieldsArray($fa, 'vendor');
    $fa = $fn->addToFieldsArray($fa, 'engineer_name');
    $fa = $fn->addToFieldsArray($fa, 'phone');
    $fa = $fn->addToFieldsArray($fa, 'email');
    $fa = $fn->addToFieldsArray($fa, 'notes');
    $fa = $fn->addToFieldsArray($fa, 'attachment');
    $fa = $fn->addToFieldsArray($fa, 'created_by');

    return $fa;
}

    function getValueByValuelistJSON(){
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $valuelist_name = $fn->getReqParam('valuelist_name');
        $json = array();

        if ($valuelist_name == '') {
            return json_encode($json);
        }

        $SQL = "
        SELECT v.valuelist_id, v.value
        FROM valuelist v
        WHERE v.key_text = '{$valuelist_name}'
        ORDER BY v.value
        ";
        $result = $db->sql_query($SQL);
        $json[] = array('value' => '', 'caption' => 'Please Select');
        $useTextValue = in_array($valuelist_name, array('Payment Status For Renewal', 'Renewal Status For Renewal'), true);

        while ($row = $db->sql_fetchrow($result)) {
            $json[] = array(
                'value' => $useTextValue ? $row['value'] : $row['valuelist_id'],
                'caption' => $row['value']
            );
        }

        return json_encode($json);
    }

    function getAddNewValuelistFormSubmit() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $validate = Zend_Registry::get('validate');

        $valuelist_value = $fn->getPostParam('valuelist_value');
        $valuelist_name  = $fn->getReqParam('valuelist_name');

        if (!$this->getAddNewValuelistFormValidate($valuelist_name, $valuelist_value)) {
            return $validate->getErrorMessageXML();
        }

        $fa = array();
        $fa['key_text'] = $valuelist_name;
        $fa['value'] = $valuelist_value;
        $fa['creation_date'] = date('Y-m-d H:i:s');

        $insert = $dbUtil->getInsertSQLStringFromArray($fa, 'valuelist');
        $db->sql_query($insert);
        $id = $db->sql_nextid();

        return $validate->getSuccessMessageXML('', $valuelist_value, array(
            'valuelist_id' => $id,
            'valuelist_value' => $valuelist_value
        ));
    }

    function getAddNewValuelistFormValidate($valuelist_name, $valuelist_value, $exclude_id = null) {
        $db = Zend_Registry::get('db');
        $validate = Zend_Registry::get('validate');

        $validate->resetErrorArray();
        $validate->validateData('valuelist_value', 'Please enter value');

        if ($valuelist_value) {
            $whereExclude = '';
            if ($exclude_id) {
                $whereExclude = " AND valuelist_id != '{$exclude_id}'";
            }

            $sql = "
            SELECT value
            FROM valuelist
            WHERE key_text = '{$valuelist_name}'
              AND value = '{$valuelist_value}'
              {$whereExclude}
            ";
            $result = $db->sql_query($sql);
            $numRows = $db->sql_numrows($result);
            if ($numRows > 0) {
                $validate->errorArray['valuelist_value']['name'] = 'valuelist_value';
                $validate->errorArray['valuelist_value']['msg'] = 'Entered value already exists';
            }
        }

        return count($validate->errorArray) == 0;
    }

    function getEditValuelistFormSubmit() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $validate = Zend_Registry::get('validate');

        $valuelist_value = $fn->getPostParam('valuelist_value');
        $valuelist_name  = $fn->getReqParam('valuelist_name');
        $valuelist_id    = $fn->getReqParam('valuelist_id');

        if (!$this->getAddNewValuelistFormValidate($valuelist_name, $valuelist_value, $valuelist_id)) {
            return $validate->getErrorMessageXML();
        }

        $fa = array();
        $fa['value'] = $valuelist_value;

        $whereCondition = "WHERE valuelist_id = '{$valuelist_id}'";
        $sqlUpdate = $dbUtil->getUpdateSQLStringFromArray($fa, 'valuelist', $whereCondition);
        $db->sql_query($sqlUpdate);

        return $validate->getSuccessMessageXML('', $valuelist_value, array('valuelist_id' => $valuelist_id));
    }

    function getDeleteValuelist() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $validate = Zend_Registry::get('validate');

        $valuelist_id = $fn->getReqParam('valuelist_id');

        if ($valuelist_id != '') {
            $sql = "DELETE FROM valuelist WHERE valuelist_id = '{$valuelist_id}'";
            $db->sql_query($sql);
        }

        return $validate->getSuccessMessageXML('', 'Deleted');
    }
}