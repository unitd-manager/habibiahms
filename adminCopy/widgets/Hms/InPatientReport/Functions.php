<?
class CPL_Admin_Widgets_Hms_InPatientReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_inPatientReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'In-Patient Report'
        ));
    }
}
