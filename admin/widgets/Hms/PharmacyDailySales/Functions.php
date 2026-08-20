<?
class CPL_Admin_Widgets_Hms_PharmacyDailySales_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_pharmacyDailySales');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Pharmacy Daily Sales Report'
        ));
    }
}
