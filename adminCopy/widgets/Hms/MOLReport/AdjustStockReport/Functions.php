<?
class CPL_Admin_Widgets_Hms_AdjustStockReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_adjustStockReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Adjust Stock Report'
        ));
    }
}
