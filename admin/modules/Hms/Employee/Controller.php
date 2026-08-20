<?
class CPL_Admin_Modules_Hms_Employee_Controller extends CP_Common_Lib_ModuleControllerAbstract
{
    function getContactByCompanyJSON(){
        return $this->model->getContactByCompanyJSON();
    }

    function getAddNewValuelistForm() {
        return $this->view->getAddNewValuelistForm();
    }

    function getAddNewValuelistFormSubmit() {
        return $this->model->getAddNewValuelistFormSubmit();
    }

    function getValueByValuelistJSON() {
        return $this->model->getValueByValuelistJSON();
    }

    function getEmployeeTimeInUpdate() {
        return $this->view->getEmployeeTimeInUpdate();
    }

    function getEmployeeTimeOutUpdate() {
        return $this->view->getEmployeeTimeOutUpdate();
    }

    function getEmployeeNightTimeInUpdate() {
        return $this->view->getEmployeeNightTimeInUpdate();
    }

     function getprintLeaveExtension() {
        return $this->view->getprintLeaveExtension();
    }

     function getprintLeaveForm() {
        return $this->view->getprintLeaveForm();
    }

    function getEmployeeNightTimeOutUpdate() {
        return $this->view->getEmployeeNightTimeOutUpdate();
    }

    function getEmployeeExtraTimeInUpdate() {
        return $this->view->getEmployeeExtraTimeInUpdate();
    }

    function getEmployeeExtraTimeOutUpdate() {
        return $this->view->getEmployeeExtraTimeOutUpdate();
    }

    function getEmployeePerformance() {
        return $this->view->getEmployeePerformance();
    }

    function getAddEmployeePerformance() {
        return $this->view->getAddEmployeePerformance();
    }

    function getAddEmployeePerformanceSubmit() {
        return $this->model->getAddEmployeePerformanceSubmit();
    }

    function getEditEmployeePerformance() {
        return $this->view->getEditEmployeePerformance();
    }

    function getEditEmployeePerformanceSubmit() {
        return $this->model->getEditEmployeePerformanceSubmit();
    }

    function getDeleteEmployeePerformance() {
        return $this->model->getDeleteEmployeePerformance();
    }

    function getDeleteEmployeeRecord() {
        return $this->model->getDeleteEmployeeRecord();
    }
}