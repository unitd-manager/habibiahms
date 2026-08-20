<?
class CPL_Admin_Modules_Hms_Renewal_Model extends CP_Common_Lib_ModuleModelAbstract
{
    /**
     * Main List SQL
     */
    function getSQL()
    {
        $SQL = "
        SELECT
    r.*,
    vl.value AS equipment_name,
    ym.vendor,
    ym.serial_number,
    ym.purchase_date,
    ym.warranty_end
FROM renewals r
LEFT JOIN yearly_maintenance_records ym
    ON ym.yearly_maintenance_records_id = r.yearly_maintenance_records_id
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

        $searchVar->mainTableAlias = 'r';

        $renewals_id  = $fn->getReqParam('renewals_id');
        $valuelist_id    = $fn->getReqParam('valuelist_id');
        $status          = $fn->getReqParam('renewal_status');
        $special_search  = $fn->getReqParam('special_search');

        if ($renewals_id != '') {
            $searchVar->sqlSearchVar[] = "r.renewals_id = '{$renewals_id}'";
        }
        else if ($tv['record_id'] != '') {
            $searchVar->sqlSearchVar[] = "r.renewals_id = '{$tv['record_id']}'";
        }
        else {

            if ($valuelist_id != '') {
                $searchVar->sqlSearchVar[] =
                    "r.valuelist_id = '{$valuelist_id}'";
            }

            if ($status != '') {
                $searchVar->sqlSearchVar[] =
                    "r.renewal_status = '{$status}'";
            }

            if ($special_search == 'Flagged') {
                $searchVar->sqlSearchVar[] = "r.flag = 1";
            }

            if ($special_search == 'Not-Flagged') {
                $searchVar->sqlSearchVar[] = "(r.flag != 1 OR r.flag IS NULL)";
            }

            if ($tv['keyword'] != '') {
                $searchVar->sqlSearchVar[] = "
                (
                    r.service_name LIKE '%{$tv['keyword']}%'
                    OR r.vendor_name LIKE '%{$tv['keyword']}%'
                    OR r.account_name LIKE '%{$tv['keyword']}%'
                    OR r.license_number LIKE '%{$tv['keyword']}%'
                )
                ";
            }

            $searchVar->sortOrder = "r.renewals_id DESC";
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
            'service_name',
            'Please enter Service Name'
        );

       $validate->validateData(
    'yearly_maintenance_records_id',
    'Please select Equipment'
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
        $fn       = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');

        if (!$this->getNewValidate()) {
            return $validate->getErrorMessageXML();
        }

        $fa = $this->getFields();

        $fa['creation_date'] = date('Y-m-d H:i:s');
        $fa['created_by'] = $fn->getSessionParam('userName');

        $id = $fn->addRecord($fa);

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

    // Save selected equipment
    $fa = $fn->addToFieldsArray($fa, 'yearly_maintenance_records_id');

    // Keep only if you still use it
    $fa = $fn->addToFieldsArray($fa, 'valuelist_id');

    $fa = $fn->addToFieldsArray($fa, 'service_name');
    $fa = $fn->addToFieldsArray($fa, 'renewal_cost');
    $fa = $fn->addToFieldsArray($fa, 'reminder_days');

    $paymentStatus = $fn->getReqParam('payment_status');
    if ($paymentStatus !== '') {
        $fa['payment_status'] = $paymentStatus;
    }

    $renewalStatus = $fn->getReqParam('renewal_status');
    if ($renewalStatus !== '') {
        $fa['renewal_status'] = $renewalStatus;
    }

    $fa = $fn->addToFieldsArray($fa, 'notes');
    $fa = $fn->addToFieldsArray($fa, 'attachment');

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

        $yearly_maintenance_records_id = $fn->getReqParam('yearly_maintenance_records_id');

if ($yearly_maintenance_records_id != '') {
    $searchVar->sqlSearchVar[] =
        "r.yearly_maintenance_records_id = '{$yearly_maintenance_records_id}'";
}

        if ($valuelist_id != '') {
            $sql = "DELETE FROM valuelist WHERE valuelist_id = '{$valuelist_id}'";
            $db->sql_query($sql);
        }

        return $validate->getSuccessMessageXML('', 'Deleted');
    }
}