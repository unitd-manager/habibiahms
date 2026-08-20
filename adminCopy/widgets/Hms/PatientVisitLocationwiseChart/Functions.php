<?
class CPL_Admin_Widgets_Hms_PatientVisitLocationwiseChart_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_patientVisitLocatiowiseChart');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Patient Visit Location Wise Chart'
        ));
    }
}
