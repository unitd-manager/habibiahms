<?
class CPL_Admin_Widgets_Hms_BalanceSheetReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_balanceSheetReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Balance Sheet Visit Report'
        ));
    }
}
