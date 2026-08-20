<?
class CPL_Admin_Widgets_Hms_SupplierOutstandingReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_supplierOutstandingReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Supplier Outstanding Report'
        ));
    }
}
