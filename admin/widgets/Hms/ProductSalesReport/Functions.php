<?
class CPL_Admin_Widgets_Hms_ProductSalesReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_productSalesReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Product Sales Report'
        ));
    }
}
