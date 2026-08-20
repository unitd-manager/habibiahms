<?
class CPL_Admin_Widgets_Hms_DrPaymentReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_drPaymentReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Doctor Payment Report'
        ));
    }
}
