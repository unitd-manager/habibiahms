<?
class CPL_Admin_Widgets_Hms_AttendanceReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_attendanceReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Attendance Report'
        ));
    }
}
