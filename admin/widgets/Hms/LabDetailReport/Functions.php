<?
class CPL_Admin_Widgets_Hms_LabDetailReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_labDetailReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Lab Detail Report'
        ));
    }
}
