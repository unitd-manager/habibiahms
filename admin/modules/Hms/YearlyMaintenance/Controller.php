<?
class CPL_Admin_Modules_Hms_YearlyMaintenance_Controller extends CP_Common_Lib_ModuleControllerAbstract
{
    function getValueByValuelistJSON() {
        return $this->model->getValueByValuelistJSON();
    }

    function getAddNewValuelistForm() {
        return $this->view->getAddNewValuelistForm();
    }

    function getAddNewValuelistFormSubmit() {
        return $this->model->getAddNewValuelistFormSubmit();
    }

    function getEditValuelistForm() {
        return $this->view->getEditValuelistForm();
    }

    function getEditValuelistFormSubmit() {
        return $this->model->getEditValuelistFormSubmit();
    }

    function getDeleteValuelist() {
        return $this->model->getDeleteValuelist();
    }
}