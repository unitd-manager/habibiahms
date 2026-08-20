<?
class CPL_Admin_Widgets_Hms_VaccinationReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_vaccinationReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Vaccination Report'
        ));
    }
}
