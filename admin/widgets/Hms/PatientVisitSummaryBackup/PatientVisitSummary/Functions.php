<?
class CPL_Admin_Widgets_Hms_PatientVisitSummary_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_patientVisitSummary');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Patient Visit Summary'
        ));
    }
}
