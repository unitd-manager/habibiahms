<?
class CPL_Admin_Widgets_Hms_DiabetesReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_diabetesReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Diabetes Report'
        ));
    }
}
