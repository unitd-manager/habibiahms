<?
class CPL_Admin_Widgets_Hms_DrugUsageReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_drugUsageReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Drug Usage Report'
        ));
    }
}
