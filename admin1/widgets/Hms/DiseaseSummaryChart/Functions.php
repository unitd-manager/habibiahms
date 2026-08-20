<?
class CPL_Admin_Widgets_Hms_DiseaseSummaryChart_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_diseaseSummaryChart');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Disease Summary Chart'
        ));
    }
}
