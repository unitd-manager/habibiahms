<?
class CPL_Admin_Widgets_Hms_BalanceSheetLabReport_Functions
{
	//==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_balanceSheetLabReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Balance Sheet Lab Report'
        ));
    }
}
