<?
class CPL_Admin_Modules_Tradingin_Inventory_Controller extends CP_Admin_Modules_Tradingin_Inventory_Controller
{
    function getPurchaseOrderDisplay() {
        return $this->view->getPurchaseOrderDisplay();
    }

    function getOrderDisplay() {
        return $this->view->getOrderDisplay();
    }

    function getBatchWiseStockDisplay() {
        return $this->view->getBatchWiseStockDisplay();
    }

    function getStockTransferDisplay() {
        return $this->view->getStockTransferDisplay();
    }

    function getBatchProductUpdateCurrentStock() {
        return $this->model->getBatchProductUpdateCurrentStock();
    }

    function getCreateUpdateChangedStockRecord() {
        return $this->model->getCreateUpdateChangedStockRecord();
    }
    
    function getUpdateStockInInventoryAndProduct() {
        return $this->model->getUpdateStockInInventoryAndProduct();
    }

    function getUpdatedAdjustStockHistory() {
        return $this->view->getUpdatedAdjustStockHistory();
    }

    function getManualStockDisplay() {
        return $this->view->getManualStockDisplay();
    }

    function getCreateManualStockRecord() {
        return $this->model->getCreateManualStockRecord();
    }

    function getManualStockDisplayDetail() {
        return $this->view->getManualStockDisplayDetail();
    }

    function getCreateUpdateExpiredStockRecord() {
        return $this->model->getCreateUpdateExpiredStockRecord();
    }

    function getUpdatedExpiryStockHistory() {
        return $this->view->getUpdatedExpiryStockHistory();
    }

    function getUpdateCurrentStockInventoryBatchRecord() {
        return $this->model->getUpdateCurrentStockInventoryBatchRecord();
    }

    function getOrderDisplayAfterManualStock() {
        return $this->view->getOrderDisplayAfterManualStock();
    }

    function getPrintFlaggedMedicine() {
        return $this->view->getPrintFlaggedMedicine();
    }

}