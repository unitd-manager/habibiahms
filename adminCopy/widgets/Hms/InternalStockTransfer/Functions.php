<?
class CPL_Admin_Widgets_Hms_InternalStockTransfer_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_internalStockTransfer');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Stock Transfer Internal Report'
        ));
    }
}
