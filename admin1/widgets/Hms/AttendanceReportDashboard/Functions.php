<?
class CPL_Admin_Widgets_Hms_AttendanceReportDashboard_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_attendanceReportDashboard');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Attendance Report Dashboard'
        ));
    }
}
