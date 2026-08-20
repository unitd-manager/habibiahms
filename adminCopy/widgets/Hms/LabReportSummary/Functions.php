<?
class CPL_Admin_Widgets_Hms_LabReportSummary_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_labReportSummary');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Lab Report Summary'
        ));
    }
}
