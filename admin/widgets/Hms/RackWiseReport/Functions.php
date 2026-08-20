<?
class CPL_Admin_Widgets_Hms_RackWiseReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_rackWiseReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Rack Wise Report'
        ));
    }
}
 