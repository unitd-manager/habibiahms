<?
class CPL_Admin_Widgets_Hms_LabChartSummary_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_labChartSummary');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Lab Chart Summary'
        ));
    }
}
