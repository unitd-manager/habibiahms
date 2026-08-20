<?
class CPL_Admin_Widgets_Hms_StockTransferReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_stockTransferReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Stock Transfer External Report'
        ));
    }
}
