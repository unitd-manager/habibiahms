<?
class CPL_Admin_Widgets_Hms_LabDetailReport_View extends CP_Common_Lib_WidgetViewAbstract
{
    //==================================================================//
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');

        //<th>X Ray</th>
        //<th>ECG</th>

        if($tv['module'] == 'common_dashboard'){
            $heading = "Lab Report Last 7 Days";
        }else {
            $heading = "Lab Report";
        }

        $summaryRec = $this->model->getSqlForCount();

        $text = "
        <h2>{$heading}</h2>
        <table class='thinlist summaryTable mb20'>
            <thead>
                <th colspan='6'>Summary</th>
            </thead>
            {$summaryRec}
        </table>
		<div class = 'tableOuter scroll-pane'>
			<table class='thinlist'>
				<thead>
					<tr>
						<th>Investigation Name</th>
						<th>Lab Test (Patient Visit)</th>
                        <th>Lab Test (Self)</th>
                        <th>Lab Test (In Patient)</th>
                        <th class='txtRight'>Total</th>
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
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $site_id = $fn->getReqParam('site_id');
        $rows = '';
        $totalOverAll = 0;
        $totalOverAllCount = 0;
        $appendSql = '';
    
        // Initialize an associative array to hold data for each title
        $titleData = [];
    
        foreach ($this->model->dataArray as $row) {
    
            if ($cpCfg['cp.hasMultiUniqueSites']) {
                $appendSql = "AND m.site_id = {$cpSiteIdSession}";
            }
    
            // Queries for each type of test
            $queries = [
                'PatLabTest' => "
                    SELECT COUNT(m.medical_test_id) AS count, m.title, SUM(m.fees) AS fees, SUM(m.lab_supplier_fees) AS lab_supplier_fees
                    FROM medical_test_visit m
                    LEFT JOIN medical_test mt ON mt.medical_test_id = m.medical_test_id
                    LEFT JOIN patient_visit pv ON pv.patient_visit_id = m.patient_visit_id
                    WHERE DATE_FORMAT(m.creation_date, '%Y-%m-%d') = '{$row['creation_date']}'
                      AND pv.status != 'Cancelled'
                      AND mt.category != 'Vaccination'
                      {$appendSql}
                    GROUP BY m.title
                ",
                'LabTest' => "
                    SELECT COUNT(m.medical_test_id) AS count, m.title, SUM(m.fees) AS fees, SUM(m.lab_supplier_fees) AS lab_supplier_fees
                    FROM medical_test_lab m
                    LEFT JOIN medical_test mt ON mt.medical_test_id = m.medical_test_id
                    LEFT JOIN lab_test lt ON lt.lab_test_id = m.lab_test_id
                    WHERE DATE_FORMAT(m.creation_date, '%Y-%m-%d') = '{$row['creation_date']}'
                      AND lt.status != 'Cancelled'
                      {$appendSql}
                    GROUP BY m.title
                ",
                'InPatLabTest' => "
                    SELECT COUNT(m.medical_test_id) AS count, m.title, SUM(m.fees) AS fees, SUM(m.lab_supplier_fees) AS lab_supplier_fees
                    FROM medical_test_in_patient m
                    LEFT JOIN medical_test mt ON mt.medical_test_id = m.medical_test_id
                    LEFT JOIN in_patient ip ON ip.in_patient_id = m.in_patient_id
                    WHERE DATE_FORMAT(m.creation_date, '%Y-%m-%d') = '{$row['creation_date']}'
                      AND ip.status != 'Cancelled'
                      {$appendSql}
                    GROUP BY m.title
                ",
            ];
    
            // Fetch data for each test type
            foreach ($queries as $rowKey => $SQL) {
                $result = $db->sql_query($SQL);
                while ($dataRow = $db->sql_fetchrow($result)) {
                    $title = $dataRow['title'];
                    if (!isset($titleData[$title])) {
                        $titleData[$title] = [
                            'PatLabTestCount' => 0, 'PatLabTestAmount' => 0,
                            'LabTestCount' => 0, 'LabTestAmount' => 0,
                            'InPatLabTestCount' => 0, 'InPatLabTestAmount' => 0
                        ];
                    }
                    $fees = $dataRow['fees'] - $dataRow['lab_supplier_fees'];
                    $titleData[$title][$rowKey . 'Count'] += $dataRow['count'];
                    $titleData[$title][$rowKey . 'Amount'] += $fees;
                }
            }
        }
    
        // Generate rows for each title
        foreach ($titleData as $title => $data) {
            $patLabTestTotal = $data['PatLabTestCount'] . ' - ' . $data['PatLabTestAmount'];
            $labTestTotal = $data['LabTestCount'] . ' - ' . $data['LabTestAmount'];
            $inPatLabTestTotal = $data['InPatLabTestCount'] . ' - ' . $data['InPatLabTestAmount'];
    
            $totalAllTestCount = $data['PatLabTestCount'] + $data['LabTestCount'] + $data['InPatLabTestCount'];
            $totalAllTestAmount = $data['PatLabTestAmount'] + $data['LabTestAmount'] + $data['InPatLabTestAmount'];
    
            $rows .= "
            <tr>
                <td width='26%'>{$title}</td>
                <td width='26%'>{$patLabTestTotal}</td>
                <td width='26%'>{$labTestTotal}</td>
                <td width='26%'>{$inPatLabTestTotal}</td>
                <td width='12%' class='txtRight'>{$totalAllTestCount}({$totalAllTestAmount})</td>
            </tr>
            ";
    
            $totalOverAll += $totalAllTestAmount;
            $totalOverAllCount += $totalAllTestCount;
        }
    
        $totalOverAll = number_format(round($totalOverAll), 2);
    
        $text = "
        {$rows}
        <tr class=''>
            <td class='txtRight lastRowBgColor' colspan='4'>Total</td>
            <td class='txtRight lastRowBgColor'>{$totalOverAllCount}({$totalOverAll})</td>
        </tr>
        ";
    
        return $text;
    }
}