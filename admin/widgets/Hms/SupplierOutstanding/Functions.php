<?
class CPL_Admin_Widgets_Hms_SupplierOutstanding_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_supplierOutstanding');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Supplier Outstanding'
        ));
    }
}
