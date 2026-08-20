<?
class CPL_Admin_Modules_Hms_YearlyMaintenance_Functions {

    /**
     *
     */
    function setModuleArray($modules){

        $modObj = $modules->getModuleObj('hms_yearlyMaintenance');
        $modObj['tableName'] = 'yearly_maintenance_records';
        $modObj['keyField']  = 'yearly_maintenance_records_id';
        $modules->registerModule($modObj, array(
            'actBtnsList'   => array('new')
           ,'actBtnsDetail' => array('edit', 'delete')
           ,'actBtnsEdit'   => array('save', 'apply', 'delete')
           ,'relatedTables' => array('media')
           ,'title'         => 'Yearly Maintenance'
        ));
    }

    /**
     *
     */

    function setMediaArray($mediaArr) {

        $mediaObj = $mediaArr->getMediaObj('hms_yearlyMaintenance', 'attachment', 'attachment');

        $mediaArr->registerMedia($mediaObj, array(
        ));
    }

    
}
