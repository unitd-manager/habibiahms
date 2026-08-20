<?
class CPL_Admin_Modules_Tradingsg_PharmacyDailySales_Model extends CP_Common_Lib_ModuleModelAbstract
{
    /**
     *
     */
    function getSQL() {

        $SQL = "
        SELECT p.*
        FROM pharma_daily_sales p
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar($linkRecType = '') {
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $searchVar = Zend_Registry::get('searchVar');
        $searchVar->mainTableAlias = 'p';

        $pharma_daily_sales_id   = $fn->getReqParam('pharma_daily_sales_id');
        $currentMonth    = $fn->getReqParam('currentMonth');
        $month          = date('m');

        if ($pharma_daily_sales_id != "") {
            $searchVar->sqlSearchVar[] = "p.pharma_daily_sales_id = '{$pharma_daily_sales_id}'";
        } else if ($tv['record_id'] != '') {
            $searchVar->sqlSearchVar[] = "p.pharma_daily_sales_id = '{$tv['record_id']}'";
        } else {
            $fn->setSearchVarForLinkData($searchVar, $linkRecType, 'p.pharma_daily_sales_id');

            if ($currentMonth == "CurrentMonth") {
                $searchVar->sqlSearchVar[] = "DATE_FORMAT(p.date, '%m') = '{$month}'" ;
            }

            if ($tv['keyword'] != "") {
                $searchVar->sqlSearchVar[] = "(
                )";
            }

            //------------------------------------------------------------------------//
            if ($tv['special_search'] == "Flagged") {
                $searchVar->sqlSearchVar[] = "p.flag = 1";
            }

            if ($tv['special_search'] == "Not-Flagged") {
                $searchVar->sqlSearchVar[] = "(p.flag != 1 OR p.flag IS null)";
            }

            //$searchVar->sortOrder = "p.date";
        }
    }

    /**
     *
     */
    function getNewValidate() {
        $validate = Zend_Registry::get('validate');

        $validate->resetErrorArray();
        $validate->validateData('date', 'Please enter the Date');

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
        $validate = Zend_Registry::get('validate');

        if (!$this->getNewValidate()){
            return $validate->getErrorMessageXML();
        }

        $fa = $this->getFields();
        $id = $fn->addRecord($fa);
        $fn->returnAfterNewSave($id);
    }

    /**
     *
     */
    function getEditValidate() {
        $validate = Zend_Registry::get('validate');

        $validate->resetErrorArray();

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
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $cpCfg = Zend_Registry::get('cpCfg');

        if (!$this->getEditValidate()){
            return $validate->getErrorMessageXML();
        }

        $fa = $this->getFields();
        $id = $fn->saveRecord($fa);
        $fn->returnAfterNewSave($id, $cpCfg['cp.pagetoReturnAfterSave']);
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
    function getFields(){
        $fn = Zend_Registry::get('fn');

        $fa = array();
        $fa = $fn->addToFieldsArray($fa, 'date');
        $fa = $fn->addToFieldsArray($fa, 'sales_amount');
        $fa = $fn->addToFieldsArray($fa, 'excess_amount');
        $fa = $fn->addToFieldsArray($fa, 'site_id');
        $fa = $fn->addToFieldsArray($fa, 'notes');
        $fa = $fn->addToFieldsArray($fa, 'created_by');
        $fa = $fn->addToFieldsArray($fa, 'creation_date');
        $fa = $fn->addToFieldsArray($fa, 'modified_by');
        $fa = $fn->addToFieldsArray($fa, 'modification_date');

        return $fa;
    }

    /**
     *
     */
    
  }
