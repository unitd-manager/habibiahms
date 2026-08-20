<?
class CPL_Admin_Widgets_Hms_ExpiringMedicineReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_expiringMedicineReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Expiring Medicine Report'
        ));
    }
}
