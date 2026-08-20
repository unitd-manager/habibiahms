<?
class CPL_Admin_Widgets_Hms_OverallAnalysis_Model extends CP_Common_Lib_WidgetModelAbstract
{
    
    function getSQL(){
        
        $SQL = "
        SELECT a.*
              ,e.first_name
              ,e.status
              ,e.employee_type
              ,e.time_in
              ,e.time_out
              ,e.time_in_night
              ,e.time_out_night
              ,e.time_in_morning
              ,e.time_out_morning
              ,e.time_in_evening
              ,e.time_out_evening
        FROM `attendance` a
        LEFT JOIN (`employee` e) ON (e.employee_id = a.employee_id) 
        ";

        return $SQL;
    }
    
    /**
     *
     */
    function setSearchVar() {
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'a';


    }

    /**
     *
     * @param <type> $SQL
     * @return <type>
     */
    function getDataArray() {
        $ln = Zend_Registry::get('ln');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');

        $modelHelper = Zend_Registry::get('modelHelper');
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'hms_overallAnalysis');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }
    

}