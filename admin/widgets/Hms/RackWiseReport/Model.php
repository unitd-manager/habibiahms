<?php
class CPL_Admin_Widgets_Hms_RackWiseReport_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
                $fn = Zend_Registry::get('fn');

        $currentDay = date("Y-m-d");
 $siteId         = $fn->getSessionParam('cp_site_id');
          $SQL = "
          SELECT i.*
              ,p.product_id AS productId
              ,p.title AS product_name
              ,p.item_code
              ,p.unit
              ,p.product_code
              ,p.category_id
              ,p.not_add_in_stock
              ,p.published
              ,p.exclude_stock_difference
              ,ms.rake
          FROM inventory i
          LEFT JOIN (product p) ON (p.product_id = i.product_id)
          LEFT JOIN (medicine_site ms) ON (i.product_id = ms.product_id AND ms.site_id = {$siteId})
          ";
          return $SQL;
    }

   /**
 *
 */
function setSearchVar() {
    $fn = Zend_Registry::get('fn');
    $cpCfg = Zend_Registry::get('cpCfg');
    $tv = Zend_Registry::get('tv');
    $searchVar = $this->searchVar;
    $searchVar->mainTableAlias = 'ms';

 
     $searchVar->sqlSearchVar[] = "(i.inventory_id != '')";
    $searchVar->sqlSearchVar[] = "(ms.rake != '')";
 $searchVar->sqlSearchVar[] = "(p.published = 1)";
   // $searchVar->sortOrder = "pv.appointment_date ASC";
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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'hms_rackWiseReport');

        // Apply natural sort on 'rake' (rack) column
        if (is_array($dataArray)) {
            usort($dataArray, function($a, $b) {
                return strnatcasecmp($a['rake'], $b['rake']);
            });
        }

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }
    /**
     */
  function getExportToExcel()
{
    $db = Zend_Registry::get('db');
    $fn = Zend_Registry::get('fn');

    // Clean output buffer
    if (ob_get_length()) {
        ob_end_clean();
    }

    set_time_limit(0);
    ini_set('memory_limit', '512M');

    require_once 'PHPExcel.php';
    require_once 'PHPExcel/IOFactory.php';

    $file_name = "RackWiseReport_" . date("d-m-Y") . ".xls";

    header('Content-Type: application/vnd.ms-excel');
    header("Content-Disposition: attachment; filename=\"$file_name\"");
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $objPHPExcel = new PHPExcel();
    $sheet = $objPHPExcel->getActiveSheet();

    /* ---------- Styles ---------- */
    $headStyle = ['font' => ['bold' => true]];
    $titleStyle = [
        'font' => ['bold' => true, 'size' => 16],
        'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER]
    ];

    /* ---------- Title ---------- */
    $sheet->mergeCells('A1:C1');
    $sheet->getStyle('A1')->applyFromArray($titleStyle);
    $sheet->setCellValue('A1', 'Rack Wise Report');

    /* ---------- Header ---------- */
    $sheet->setCellValue('A2', 'Product Name');
    $sheet->setCellValue('B2', 'Rack');
    $sheet->setCellValue('C2', 'Rack Qty');
    $sheet->getStyle('A2:C2')->applyFromArray($headStyle);

    foreach (['A', 'B', 'C'] as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    /* ---------- Fetch Data (NO ORDER BY) ---------- */
    $siteId = (int)$fn->getSessionParam('cp_site_id');

    $SQL = "
        SELECT 
            i.product_id,
            p.title AS product_name,
            ms.rake,
            ms.rack_qty
        FROM inventory i
        LEFT JOIN product p ON p.product_id = i.product_id
        LEFT JOIN medicine_site ms 
            ON ms.product_id = i.product_id 
            AND ms.site_id = {$siteId}
        WHERE ms.rake IS NOT NULL
          AND ms.rake != ''
          AND p.published = 1
    ";

    $result = $db->sql_query($SQL);

    $data = [];
    while ($row = $db->sql_fetchrow($result)) {
        $data[] = $row;
    }

    /* ---------- NATURAL SORT (SAME AS UI) ---------- */
    usort($data, function ($a, $b) {
        return strnatcasecmp(
            preg_replace('/[^A-Za-z0-9]/', '', $a['rake']),
            preg_replace('/[^A-Za-z0-9]/', '', $b['rake'])
        );
    });

    /* ---------- Write to Excel ---------- */
    $rowc = 3;
    foreach ($data as $row) {

        $rack_qty = ($row['rack_qty'] > 0) ? $row['rack_qty'] : '';

        $sheet->setCellValue("A{$rowc}", $row['product_name']);
        $sheet->setCellValue("B{$rowc}", $row['rake']);
        $sheet->setCellValue("C{$rowc}", $rack_qty);

        $rowc++;
    }

    /* ---------- Output ---------- */
    $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $writer->save('php://output');
    exit;
}


}
