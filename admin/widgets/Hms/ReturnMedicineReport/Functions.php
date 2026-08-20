<?
class CPL_Admin_Widgets_Hms_ReturnMedicineReport_Functions
{
    //==================================================================//
    function setWidgetArray($widgets){
        $cpCfg 	   = Zend_Registry::get('cpCfg');
        $widgetObj = $widgets->getWidgetObj('hms_returnMedicineReport');

        $widgets->registerWidget($widgetObj, array(
        	'title' => 'Return Medicine Report'
        ));
    }
}
