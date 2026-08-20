<?
class CPL_Admin_Widgets_Hms_LabReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_labReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Lab Report'
        ));
    }
}
