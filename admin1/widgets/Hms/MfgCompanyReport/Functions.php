<?
class CPL_Admin_Widgets_Hms_MfgCompanyReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_mfgCompanyReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Mfg Company Report'
        ));
    }
}
