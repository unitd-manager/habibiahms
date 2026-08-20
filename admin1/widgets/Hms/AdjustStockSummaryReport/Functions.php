<?
class CPL_Admin_Widgets_Hms_AdjustStockSummaryReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_adjustStockSummaryReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Adjust Stock Summary Report'
        ));
    }
}
