<?
class CPL_Admin_Widgets_Hms_ReferenceDoctorAppointmentReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_referenceDoctorAppointmentReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Reference Doctor Appointment Report'
        ));
    }
}
 