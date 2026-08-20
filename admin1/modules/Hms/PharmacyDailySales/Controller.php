<?
class CPL_Admin_Modules_Hms_PharmacyDailySales_Controller extends CP_Common_Lib_ModuleControllerAbstract
{
	function getCreateCurrentDayPharmacyRecord() {
        return $this->model->getCreateCurrentDayPharmacyRecord();
    }

    function getPharmacyDailySalesHistory() {
        return $this->view->getPharmacyDailySalesHistory();
    }

    function getPharmacyDailySalesHistoryValidate() {
        return $this->model->getPharmacyDailySalesHistoryValidate();
    }

    function getPharmacyDailySalesHistoryFormSubmit() {
        return $this->model->getPharmacyDailySalesHistoryFormSubmit();
    }

    function getAddPharmacyDailySalesHistory() {
        return $this->view->getAddPharmacyDailySalesHistory();
    }

    function getEditPharmacyDailySalesHistory() {
        return $this->view->getEditPharmacyDailySalesHistory();
    }

    function getEditPharmacyDailySalesHistoryFormSubmit() {
        return $this->model->getEditPharmacyDailySalesHistoryFormSubmit();
    }

    function getUpdateBillNos() {
        return $this->model->getUpdateBillNos();
    }

    function getDeletePharmacyDailySalesHistory() {
        return $this->model->getDeletePharmacyDailySalesHistory();
    }

    function getUpdateExcessAmount() {
        return $this->model->getUpdateExcessAmount();
    }
}