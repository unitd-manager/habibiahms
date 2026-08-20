<?
class CPL_Admin_Widgets_Hms_BalanceSheetPharmacyReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_balanceSheetPharmacyReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Balance Sheet Pharmacy Report'
        ));
    }
}
