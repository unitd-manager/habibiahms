<?
class CPL_Admin_Widgets_Hms_PatientVisitByMonth_View extends CP_Common_Lib_WidgetViewAbstract
{
    //==================================================================//
    function getWidget() {
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $dateUtil = Zend_Registry::get('dateUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        $jsonArray =  $this->getRowsHTML();
       
        $text = "
        <div class='float_left mr20 '><h1>Patient Visit By Month</h1></div>
        <div class='float_left ml20'><h1> Total : <b>{$jsonArray['overalltotal']}</b> | No of Village : <b>{$jsonArray['count']}</b></h1>
        </div>
		<div class = 'tableOuter scroll-pane'>
			<table class='thinlist'>
				<thead>
					<tr>
                        <th>S.No</th>
						<th>Location</th>
                        <th>Month</th>
                        <th>No.of Visit</th>
                        
					</tr>
				</thead>
				<tbody>
                    {$jsonArray['rows']}
				</tbody>
			</table>
		</div>
        ";
        return $text;
    }

    function getRowsHTML() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $text = '';
        $rows = '';
        $siteTitle = '' ;
        $totalOverAllCount = 0;
        $count = 1;

        foreach($this->model->dataArray as $row){
            if($row['address_area'] == ''){
                $row['address_area'] = 'No location mentioned';
            }

            $rows .= "
            <tr>
                <td>{$count}</td>
                <td>{$row['address_area']}</td>
                <td>{$row['Month']}</td>
                <td class='txtRight'>{$row['no_of_visit']}</td>
            </tr>
            ";
            $totalOverAllCount += $row['no_of_visit']; 

            $count++;
        }

        $rows = "
        {$rows}
        <tr class=''>
            <td class='txtRight lastRowBgColor' colspan='3'>Total</td>
            <td class='txtRight lastRowBgColor'>{$totalOverAllCount}</td>
        </tr>
        ";
        
        return array('rows' => $rows, 'overalltotal' => $totalOverAllCount, 'count' => $count-1);

        //return $rows;
    }

 }