<?
class CPL_Admin_Widgets_Hms_OverallYearlyAnalysis_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_overallYearlyAnalysis');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Overall Yearly Analysis'
        ));
    }
}
