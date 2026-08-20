<?
class CPL_Admin_Widgets_Hms_DiabetesReport_View extends CP_Common_Lib_WidgetViewAbstract
{
    //==================================================================//
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');


        $text = "
        <div class='float_left mr20 '><h1>Diabetes Patient Report</h1></div>
        
        <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>S NO</th>
                        <th>Patient Name</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Phone</th>
                        <th>Town/City</th>
                        <th>Visit Count</th>
                       
                   </tr>
                </thead>
                <tbody>
                   {$this->getRowsHTML()}
                </tbody>
            </table>
        </div>
        ";
        return $text;
    }

    function getRowsHTML() {
        $fn = Zend_Registry::get('fn');
        $db    = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        
        $datas = '';
        $count = 1;   
    
        foreach($this->model->dataArray as $row){
          
            $datas .= "
            <tr>
                <td>{$count}</td>
                <td>{$row['name']}</td>
                <td>{$row['age_year']}</td>
                <td>{$row['gender']}</td>
                <td>{$row['phone']}</td>
                <td>{$row['address_area']}</td>
                <td>{$row['visit_count']}</td>
                     
             </tr>   
            ";
            $count++;
        }
        $text = "
        {$datas}
      
        ";

        return $text;
    }

}