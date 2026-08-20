<?
class CPL_Admin_Widgets_Hms_ImageReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_imageReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Image Report'
        ));
    }
}
