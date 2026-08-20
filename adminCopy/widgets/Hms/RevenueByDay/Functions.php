<?
class CPL_Admin_Widgets_Hms_RevenueByDay_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_revenueByDay');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Revenue By Day'
        ));
    }
}
