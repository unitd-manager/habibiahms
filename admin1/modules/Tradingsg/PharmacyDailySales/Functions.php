<?
class CPL_Admin_Modules_Tradingsg_PharmacyDailySales_Functions {

    /**
     *
     */
    function setModuleArray($modules){

        $modObj = $modules->getModuleObj('tradingsg_pharmacyDailySales');
        $modules->registerModule($modObj, array(
            'actBtnsList'   => array('new')
           ,'actBtnsDetail' => array('edit', 'delete')
           ,'actBtnsEdit'   => array('save', 'apply', 'delete')
           ,'relatedTables' => array('media')
           ,'tableName'     => 'pharma_daily_sales'
           ,'keyField'      => 'pharma_daily_sales_id'
           ,'title'         => 'Pharmacy Daily Sales'
        ));
    }

    /**
     *
     */

    function setMediaArray($mediaArr) {

        $mediaObj = $mediaArr->getMediaObj('tradingsg_pharmacyDailySales', 'attachment', 'attachment');

        $mediaArr->registerMedia($mediaObj, array(
        ));
    }

    
}
