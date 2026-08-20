<?
class CPL_Admin_Widgets_Hms_BalanceSheetImageReport_Functions
{
	//==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_balanceSheetImageReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Balance Sheet Image Report'
        ));
    }
}
