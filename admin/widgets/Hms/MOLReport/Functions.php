<?
class CPL_Admin_Widgets_Hms_MOLReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_mOLReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'MOL Report'
        ));
    }
}
