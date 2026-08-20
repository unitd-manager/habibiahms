<?
class CPL_Admin_Modules_Hms_Attendance_View extends CP_Common_Lib_ModuleViewAbstract
{


function getList() {
    $fn = Zend_Registry::get('fn');
    $db = Zend_Registry::get('db');

    $searchMonth = $fn->getReqParam('search_month') ?: date('m');
    $searchYear  = $fn->getReqParam('search_year') ?: date('Y');
    $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
    $userGroup = $fn->getSessionParam('userGroupName');
    $employeeCategory = $fn->getReqParam('employee_category'); // New filter param

    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $searchMonth, $searchYear);
    $currentMonth = date('F', mktime(0, 0, 0, $searchMonth, 10)) . ' ' . $searchYear;

    // Format date filter
    $monthStr = str_pad($searchMonth, 2, '0', STR_PAD_LEFT);
    $dateFilter = "{$searchYear}-{$monthStr}";
    
    // Get employee condition based on user group
    $employeeCondition = '';
    if (!($userGroup === 'Super Administrator' || $userGroup === 'Administrator' || $userGroup === 'Duty Doctor Admin')) {
        // Get the current employee ID based on the logged-in user
        $sqlCurrentEmployee = "SELECT employee_id FROM employee WHERE staff_id = '" . $fn->getSessionParam('staff_id') . "'";
        $resultCurrentEmployee = $db->sql_query($sqlCurrentEmployee);
        $rowCurrentEmployee = $db->sql_fetchrow($resultCurrentEmployee);
        if ($rowCurrentEmployee) {
            $employeeCondition = "AND a.employee_id = '" . $rowCurrentEmployee['employee_id'] . "'";
        }
    }

    // Add employee category filter condition if provided
    $categoryCondition = '';
    if (!empty($employeeCategory)) {
        // Use LIKE for partial matching or exact match depending on data
$categoryCondition = "AND a.position LIKE '%" . addslashes($employeeCategory) . "%'";
    }

    // SQL embedded here with additional total attendance counts
    $sql = "
        SELECT a.employee_id, 
               a.first_name, 
               a.position, 
               at.record_date, 
               at.attendance_status, 
               at.shift,
               at.daily_attendance_id,
               (SELECT COUNT(*) FROM daily_attendance da 
                WHERE da.employee_id = a.employee_id 
                AND DATE_FORMAT(da.record_date, '%Y-%m') = '{$dateFilter}'
                AND da.attendance_status = 'P') as count_present,
               (SELECT COUNT(*) FROM daily_attendance da 
                WHERE da.employee_id = a.employee_id 
                AND DATE_FORMAT(da.record_date, '%Y-%m') = '{$dateFilter}'
                AND da.attendance_status = 'A') as count_absent,
               (SELECT COUNT(*) FROM daily_attendance da 
                WHERE da.employee_id = a.employee_id 
                AND DATE_FORMAT(da.record_date, '%Y-%m') = '{$dateFilter}'
                AND da.attendance_status = 'H1') as count_halfday_absent,
               (SELECT COUNT(*) FROM daily_attendance da 
                WHERE da.employee_id = a.employee_id 
                AND DATE_FORMAT(da.record_date, '%Y-%m') = '{$dateFilter}'
                AND da.attendance_status = 'H2') as count_halfday_present
        FROM employee a
        LEFT JOIN daily_attendance at 
          ON at.employee_id = a.employee_id 
         AND DATE_FORMAT(at.record_date, '%Y-%m') = '{$dateFilter}'
        WHERE a.position != 'Doctor' 
        AND a.add_in_payroll = 1
          AND a.site_id = '{$cpSiteIdSession}'
           {$employeeCondition}
           {$categoryCondition}
        ORDER BY a.employee_id DESC
    ";

    $result = $db->sql_query($sql);

    // Build data array
    $employees = [];
    while ($row = $db->sql_fetchrow($result)) {
        $empId = $row['employee_id'];
        $date  = $row['record_date'];
        $shift = $row['shift'] ?: 'day'; // Default to day if not specified

            if (!isset($employees[$empId])) {
            // Get position abbreviation
            $position_abbr = '';
            switch(strtoupper($row['position'])) {
                case 'NURSE':
                    $position_abbr = 'HOSPITAL';
                    break;
                    case 'LAB TECHNICIAN':
                        $position_abbr = 'LAB';
                        break;
                case 'PHARMACY':
                    $position_abbr = 'PHARM';
                    break;
                    case 'SANITARY WORKER':
                        $position_abbr = 'SW';
                        break;
                default:
                    $position_abbr = substr($row['position'], 0, 6);
            }
            
            $total_present = $row['count_present'] + 0.5 * $row['count_halfday_present'];
            $total_absent = $row['count_absent'] + 0.5 * $row['count_halfday_absent'];
            
            $employees[$empId] = [
                'first_name' => $row['first_name'] . ' (' . $position_abbr . ')',
                'employee_id' => $empId,
                'day_attendance' => [],
                'night_attendance' => [],
                'day_attendance_ids' => [],
                'night_attendance_ids' => [],
                'total_present' => $total_present,
                'total_absent' => $total_absent,
                'total_halfday' => 0
            ];
        }

        if ($date) {
            if ($shift == 'night') {
                $employees[$empId]['night_attendance'][$date] = $row['attendance_status'];
                $employees[$empId]['night_attendance_ids'][$date] = $row['daily_attendance_id'];
            } else {
                $employees[$empId]['day_attendance'][$date] = $row['attendance_status'];
                $employees[$empId]['day_attendance_ids'][$date] = $row['daily_attendance_id'];
            }
        }
    }


        // Add userGroup information to the table data attributes
    $userGroup = $fn->getSessionParam('userGroupName');
    $isAdmin = ($userGroup === 'Super Administrator'  || $userGroup === 'Administrator') ? 'true' : 'false';
    $DeleteButton ='';
 if ($isAdmin === 'true') {
        $DeleteButton = '<button class="delete deleteAttendance" onclick="deleteAttendance()">Delete Record</button>';
    }

    // Only show Halfday Present and Halfday Absent buttons for Super Administrator and Administrator
   $showHalfdayButtons = (
    $userGroup === 'Super Administrator' ||
    $userGroup === 'Administrator' ||
    $userGroup === 'Duty Doctor Admin'
);

    // Start HTML output
    $text = '';
    $text .= "<h2>Attendance - {$currentMonth}</h2>";
    $text .= '<table class="attendance-table" border="1" cellpadding="5" cellspacing="0" ';
    $text .= 'data-year="' . $searchYear . '" ';
    $text .= 'data-month="' . $searchMonth . '">';
    $text .= '<thead><tr><th rowspan="2">Employee Name</th> ';
    $text .= '<th rowspan="2">Shift</th>';

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $text .= "<th>{$day}</th>";
    }
$text .= '<th rowspan="2">P</th>';
    $text .= '<th rowspan="2">A</th>';
    // $text .= '<th rowspan="2">H</th>';
    $text .= '</tr></thead><tbody>';

        foreach ($employees as $row) {
            // Employee name cell that spans both rows
            $text .= '<tr>';
            $text .= '<td rowspan="2">' . htmlspecialchars($row['first_name']) . '</td>';
            $text .= '<td>M</td>'; // Morning shift abbreviated as M

            // Rest of the day cells for day shift
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = "{$searchYear}-" . str_pad($searchMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                $status = isset($row['day_attendance'][$date]) ? $row['day_attendance'][$date] : '';

                $className = ($status === 'H') ? 'attendance-cell half-day-row' : 'attendance-cell';
                $attendanceId = isset($row['day_attendance_ids'][$date]) ? $row['day_attendance_ids'][$date] : '';
                $today = date('Y-m-d');
                $clickable = false;
                if ($isAdmin === 'true') {
                    $clickable = true;
                } else {
                    // if ($date === $today) {
                    //     $clickable = true;
                    // }
                  if (empty($attendanceId)) {
                        $clickable = true;
                    }
                }
                $onclickAttr = $clickable ? "onclick='openAttendancePopup(this)'" : "";
                $statusClass = '';
                switch ($status) {
                    case 'P':
                        $statusClass = 'present';
                        break;
                    case 'A':
                        $statusClass = 'absent';
                        break;
                    case 'H1':
                        $statusClass = 'halfday1';
                        break;
                    case 'H2':
                        $statusClass = 'halfday2';
                        break;
                    default:
                        $statusClass = '';
                }
                $text .= "<td class='{$className}' 
                              data-day='{$day}' 
                              data-employee-id='{$row['employee_id']}' 
                              data-shift='day'
                              data-daily-attendance-id='{$attendanceId}'
                              {$onclickAttr}>
                            <div class='status-display {$statusClass}'>{$status}</div>
                        </td>";
            }
             $text .= '<td rowspan="2">' . ($row['total_present'] ?: 0) . '</td>';
            $text .= '<td rowspan="2">' . ($row['total_absent'] ?: 0) . '</td>';
            // $text .= '<td rowspan="2">' . ($row['total_halfday'] ?: 0) . '</td>';
            $text .= '</tr>';
            
            // Night shift row
            $text .= '<tr>';
            $text .= '<td>N</td>'; // Night shift abbreviated as N
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = "{$searchYear}-" . str_pad($searchMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                $status = isset($row['night_attendance'][$date]) ? $row['night_attendance'][$date] : '';

                $className = ($status === 'H') ? 'attendance-cell half-day-row' : 'attendance-cell';
                $attendanceId = isset($row['night_attendance_ids'][$date]) ? $row['night_attendance_ids'][$date] : '';
                $today = date('Y-m-d');
                $clickable = false;
                if ($isAdmin === 'true') {
                    $clickable = true;
                } else {
                    // if ($date === $today) {
                    //     $clickable = true;
                    // }
                  if (empty($attendanceId)) {
                        $clickable = true;
                    }
                }
                $onclickAttr = $clickable ? "onclick='openAttendancePopup(this)'" : "";
                $statusClass = '';
                switch ($status) {
                    case 'P':
                        $statusClass = 'present';
                        break;
                    case 'A':
                        $statusClass = 'absent';
                        break;
                    case 'H1':
                        $statusClass = 'halfday1';
                        break;
                    case 'H2':
                        $statusClass = 'halfday2';
                        break;
                    default:
                        $statusClass = '';
                }
                $text .= "<td class='{$className}' 
                              data-day='{$day}' 
                              data-employee-id='{$row['employee_id']}' 
                              data-shift='night'
                              data-daily-attendance-id='{$attendanceId}'
                              {$onclickAttr}>
                            <div class='status-display {$statusClass}'>{$status}</div>
                        </td>";
            }
            $text .= '</tr>';
            // Add gap row after each employee's attendance rows
            $colspan = $daysInMonth + 4; // Employee name + Shift + days + P + A columns
            $text .= "<tr class='attendance-gap-row'><td colspan='{$colspan}' style='height: 15px; background-color: transparent; border: none;'></td></tr>";
    }

    $text .= '</tbody></table>';

 // Add CSS
    $text .= '
    <style>
    /* Main container styling */
    body {
        font-family: Arial, sans-serif;
    }
    
    h2 {
        color: #2c3e50;
        text-align: center;
        margin: 20px 0;
        font-size: 24px;
    }
    
    /* Table styling */
    .attendance-table {
        width: 100%;
        border-collapse: collapse;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        background: white;
        margin-bottom: 30px;
        table-layout: fixed;
    }
    
    .attendance-table th, .attendance-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: center;
    }
    
    .attendance-table thead tr {
        background-color: #3498db;
        color: white;
    }
    
    .attendance-table th {
        font-weight: bold;
    }
    
    .attendance-table th:first-child {
        width: 150px;
        text-align: left;
    }
    
    .attendance-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    
    .attendance-table tr:hover {
        background-color: #f1f1f1;
    }
    
    .attendance-cell {
        width: 40px;
        height: 40px;
        text-align: center;
        cursor: pointer;
        position: relative;
        transition: all 0.2s;
    }
    
    .attendance-cell:hover {
        background-color: #e3f2fd;
        transform: scale(1.05);
    }
    
    .status-display {
        font-weight: bold;
        width: 25px;
        height: 25px;
        line-height: 25px;
        border-radius: 50%;
        margin: 0 auto;
    }
    
    td[bgcolor="#F06292"] {
        background-color: #3498db !important;
        color: white;
        font-weight: bold;
    }

    /* Status indicators */
    .status-display.present {
        background-color: #4CAF50;
        color: white;
    }
    
    .status-display.absent {
        background-color: #f44336;
        color: white;
    }
    
    .status-display.half-day  {
        background-color: #ff0000;  /* Changed to red */
        color: white;
    }
    /* New styles for halfday 1 and halfday 2 */
    .status-display.halfday1 {
        background-color: #f44336; /* red */
        color: white;
    }
    .status-display.halfday2 {
        background-color: #4CAF50; /* green */
        color: white;
    }
    
    .half-day-row {
        background-color: #ffebee !important; /* Light red background */
    }
    
    
    
    .loading {
        display: inline-block;
        animation: pulse 1s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 0.5; }
        50% { opacity: 1; }
        100% { opacity: 0.5; }
    }

    /* Modal Styling */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .popup-modal {
        background: white;
        border-radius: 10px;
        padding: 25px;
        max-width: 350px;
        width: 90%;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        text-align: center;
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .popup-modal h3 {
        margin-bottom: 15px;
        color: #2c3e50;
        font-size: 18px;
    }

    .popup-modal button {
        margin: 8px;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .popup-modal button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .popup-modal .present { background-color: #4CAF50; color: white; }
    .popup-modal .absent { background-color: #f44336; color: white; }
    .popup-modal .half1 { background-color: #f44336; color: white; }
    .popup-modal .half2 { background-color: #4CAF50; color: white; }
    .popup-modal .close { 
        background-color: #9E9E9E; 
        color: white;
        width: 100%;
        margin-top: 15px;
    }

    /* Status popup styles */
    .attendance-popup {
        display: none;
        position: fixed;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        z-index: 1000;
        min-width: 300px;
    }
    
    .attendance-popup h3 {
        margin-top: 0;
        color: #2c3e50;
        border-bottom: 2px solid #3498db;
        padding-bottom: 10px;
    }
    
    .attendance-popup .status-options {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin: 20px 0;
    }
    
    .attendance-popup button {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.2s;
    }
    
    .attendance-popup button:hover {
        transform: translateY(-2px);
    }
    
    .attendance-popup button.present {
        background-color: #4CAF50;
        color: white;
    }
    
    .attendance-popup button.absent {
        background-color: #f44336;
        color: white;
    }
    
    .attendance-popup button.half1 {
        background-color: #f44336;
        color: white;
    }

     .attendance-popup button.half2 {
        background-color: #4CAF50;
        color: white;
    }
    
    .attendance-popup button.delete {
        background-color: #dc3545;
        color: white;
        margin-top: 10px;
        display: block;
        width: 100%;
    }
    
    .attendance-popup button.cancel {
        background-color: #6c757d;
        color: white;
        margin-top: 10px;
        display: block;
        width: 100%;
    }

    .attendance-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 999;
    }
    </style>

    <!-- Attendance popup dialog -->
    <div class="attendance-overlay"></div>
    <div class="attendance-popup">
        <h3>Update Attendance</h3>
        <div class="status-options">
           <button class="present" onclick="updateAttendance(\'P\')">Present</button>
            <button class="absent" onclick="updateAttendance(\'A\')">Absent</button>
            ' . ($showHalfdayButtons ? '<button class="half2" onclick="updateAttendance(\'H2\')">Halfday Present</button>' : '') . '
            ' . ($showHalfdayButtons ? '<button class="half1" onclick="updateAttendance(\'H1\')">Halfday Absent</button>' : '') . '
        </div>
      '.$DeleteButton.'     
   <button class="cancel" onclick="closePopup()">Cancel</button>
    </div>

    <!-- JavaScript for handling the popup -->
    <script>
    let currentCell = null;

    function openAttendancePopup(cell) {
        currentCell = cell;
        const overlay = document.querySelector(".attendance-overlay");
        const popup = document.querySelector(".attendance-popup");
        
        overlay.style.display = "block";
        popup.style.display = "block";
        
        const day = cell.getAttribute("data-day");
        const employeeId = cell.getAttribute("data-employee-id");
        const shift = cell.getAttribute("data-shift");
        const dailyAttendanceId = cell.getAttribute("data-daily-attendance-id");
        
        const deleteBtn = popup.querySelector(".deleteAttendance");
        if (dailyAttendanceId) {
            deleteBtn.setAttribute("data-daily-attendance-id", dailyAttendanceId);
            deleteBtn.style.display = "block"; // Only show delete button if we have an ID
        } else {
            deleteBtn.style.display = "none"; // Hide delete button if no attendance record exists
        }
        
        // For debugging
        console.log("Opening popup with attendance ID:", dailyAttendanceId);
    }

    function deleteAttendance() {
        const deleteBtn = document.querySelector(".deleteAttendance");
        const dailyAttendanceId = deleteBtn.getAttribute("data-daily-attendance-id");
        
        if (!dailyAttendanceId) {
            alert("Cannot delete: No attendance record found.");
            return;
        }

        if (!confirm("Are you sure you want to delete this attendance record?")) {
            return;
        }

        // Make an AJAX call to delete the record
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "index.php?module=hms_attendance&_spAction=DeleteEmployeSubmit&showHTML=0", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        if (currentCell) {
                            currentCell.querySelector(".status-display").textContent = "";
                            currentCell.classList.remove("half-day-row");
                            currentCell.setAttribute("data-daily-attendance-id", "");
                        }
                        closePopup();
                        alert(response.message);
                        window.location.reload(); // Refresh the page to show updated data
                    } else {
                        alert(response.message || "Failed to delete attendance record");
                    }
                } catch (e) {
                    alert("Error deleting attendance record.");
                }
            }
        };
        xhr.send("module=hms_attendance&action=delete&daily_attendance_id=" + encodeURIComponent(dailyAttendanceId));
    }

    function closePopup() {
        document.querySelector(".attendance-overlay").style.display = "none";
        document.querySelector(".attendance-popup").style.display = "none";
        currentCell = null;
    }

    function updateAttendance(status) {
        if (currentCell) {
            const day = currentCell.getAttribute("data-day");
            const employeeId = currentCell.getAttribute("data-employee-id");
            const shift = currentCell.getAttribute("data-shift");
            
            // Get the selected year and month from the table data attributes
            const table = document.querySelector(".attendance-table");
            const year = table.getAttribute("data-year");
            const month = table.getAttribute("data-month");
            
            if (!year || !month) {
                alert("Error: Could not determine selected month and year");
                return;
            }
            
            // Format date as YYYY-MM-DD
            const paddedMonth = month.toString().padStart(2, "0");
            const paddedDay = day.toString().padStart(2, "0");
            const date = year + "-" + paddedMonth + "-" + paddedDay;
            
            // Make AJAX call to save attendance
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "index.php?module=hms_attendance&_spAction=AddEmployeSubmit&showHTML=0", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Update UI
                            const statusDisplay = currentCell.querySelector(".status-display");
                            statusDisplay.textContent = status;
                            
                            if (status === "H1" || status === "H2") {
                                currentCell.classList.add("half-day-row");
                            } else {
                                currentCell.classList.remove("half-day-row");
                            }
                            
                            // Update the attendance ID attribute
                            if (response.daily_attendance_id) {
                                currentCell.setAttribute("data-daily-attendance-id", response.daily_attendance_id);
                            }
                            
                            closePopup();
                            window.location.reload(); // Refresh the page to show updated data
                        } else {
                            alert("Failed to save attendance: " + (response.message || "Unknown error"));
                        }
                    } catch (e) {
                        alert("Error saving attendance record");
                        console.error(e);
                    }
                }
            };
            
            const data = "employee_id=" + encodeURIComponent(employeeId) + 
                        "&date=" + encodeURIComponent(date) +
                        "&shift=" + encodeURIComponent(shift) +
                        "&status=" + encodeURIComponent(status);
            
            xhr.send(data);
        }
    }
    </script>
    ';


    return $text;
}


    function getListOld($dataArray) {
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');
        $db = Zend_Registry::get('db');
        $listObj = Zend_Registry::get('listObj');

        $rowCounter = 0;
        $rows       = "";
        $task       = '';
        $timeInOutShiftDay = '';
        $timeInOutShiftNight = '';
        foreach ($dataArray as $row){
            $time_in = substr($row['time_in_day_shift'], 0, 5);
            $leave_time = substr($row['leave_time_day_shift'], 0, 5);

            if ($cpCfg['m.hms.attendance.hasMultipleSessions'] == 1) {

                $timeInOutData = "
                {$listObj->getListDataCell($row['time_in_morning'])}
                {$listObj->getListDataCell($row['leave_time_morning'])}
                {$listObj->getListDataCell($row['time_in_evening'])}
                {$listObj->getListDataCell($row['leave_time_evening'])}
                ";

                /*$timeIn = '';
                if ($row['time_in_morning'] == '' || $row['time_in_morning'] == '00:00:00') {
                    $timeIn = $listObj->getListDataCell($row['time_in_evening']);
                } else {
                    $timeIn = $listObj->getListDataCell($row['time_in_morning']);
                }

                $timeOut = '';
                if ($row['leave_time_evening'] == '' || $row['leave_time_evening'] == '00:00:00') {
                    $timeOut = $listObj->getListDataCell($row['leave_time_morning']);
                } else {
                    $timeOut = $listObj->getListDataCell($row['leave_time_evening']);
                }

                $timeInOutData = "
                {$timeIn}
                {$timeOut}
                ";*/

            } else {
                $timeInOutShiftDay = $row['time_in_day_shift_formatted'].' / '.$row['leave_time_day_shift_formatted'];
                $timeInOutShiftDay = rtrim($timeInOutShiftDay, ' / ');
                $timeInOutDataDayShift = "
                {$listObj->getListDataCell($timeInOutShiftDay)}
                ";

                $timeInOutShiftNight = $row['time_in_night_shift_formatted'].' / '.$row['leave_time_night_shift_formatted'];
                $timeInOutShiftNight = rtrim($timeInOutShiftNight, ' / ');
                $timeInOutDataNightShift = "
                {$listObj->getListDataCell($timeInOutShiftNight)}
                ";

                $timeInOutShiftExtra = $row['time_in_extra_shift_formatted'].' / '.$row['leave_time_extra_shift_formatted'];
                $timeInOutShiftExtra = rtrim($timeInOutShiftExtra, ' / ');
                $timeInOutDataExtraShift = "
                {$listObj->getListDataCell($timeInOutShiftExtra)}
                ";

                $timeInOutShiftDSMorning = $row['time_in_double_shift_morning_formatted'].' / '.$row['leave_time_double_shift_morning_formatted'];
                $timeInOutShiftDSMorning = rtrim($timeInOutShiftDSMorning, ' / ');
                $timeInOutDataShiftDSMorning = "
                {$listObj->getListDataCell($timeInOutShiftDSMorning)}
                ";

                $timeInOutShiftDSEvening = $row['time_in_double_shift_evening_formatted'].' / '.$row['leave_time_double_shift_evening_formatted'];
                $timeInOutShiftDSEvening = rtrim($timeInOutShiftDSEvening, ' / ');
                $timeInOutDataShiftDSEvening = "
                {$listObj->getListDataCell($timeInOutShiftDSEvening)}
                ";

                $sqlEmployee = "
                SELECT employee_type
                FROM employee
                WHERE employee_id = {$row['employee_id']}
                ";
                $resultEmployee = $db->sql_query($sqlEmployee);
                $rowEmployee    = $db->sql_fetchrow($resultEmployee);
            }
            
            $createdModifiedBy = $row['created_by'].' / '.$row['modified_by'];
            $createdModifiedBy = rtrim($createdModifiedBy, ' / ');

            $record_date = $fn->getCPDate($row['record_date'],"d-m-Y");

            $record_sign_in        = $row['time_in_day_shift'];
            $record_sign_out       = $row['leave_time_day_shift'];
            $record_created        = $row['record_date'];
            $time1                 = date("H:i", strtotime($record_sign_in));
            $time2                 = date("H:i", strtotime($record_sign_out));
            $record_created        = date("l", strtotime($record_created));
            $day                   = $record_created;
            list($hours, $minutes) = explode(':', $time1);
            $startTimestamp        = mktime($hours, $minutes);
            list($hours, $minutes) = explode(':', $time2);
            $endTimestamp          = mktime($hours, $minutes);
            $seconds               = $endTimestamp - $startTimestamp;
            $minutes               = ($seconds / 60) % 60;
            $hours                 = floor($seconds / (60 * 60));
            
            if($row['time_in_day_shift'] != '00:00:00' && $row['time_in_day_shift'] != '' && $row['leave_time_day_shift'] != '00:00:00' && $row['leave_time_day_shift'] != ''){
                $total_time = sprintf("%02d", $hours). ":" .sprintf("%02d", $minutes);
            } else {
                $total_time = '';
            }

            $record_sign_in_day2  = $row['time_in_double_shift_morning'];
            $record_sign_out_day2 = $row['leave_time_double_shift_morning'];
            $record_created_day2  = $row['record_date'];
            $time1_day2           = date("H:i", strtotime($record_sign_in_day2));
            $time2_day2           = date("H:i", strtotime($record_sign_out_day2));
            $record_created_day2  = date("l", strtotime($record_created_day2));
            $day_day2             = $record_created_day2;
            list($hours_day2, $minutes_day2) = explode(':', $time1_day2);
            $startTimestamp_day2 = mktime($hours_day2, $minutes_day2);
            list($hours_day2, $minutes_day2) = explode(':', $time2_day2);
            $endTimestamp_day2 = mktime($hours_day2, $minutes_day2);
            $seconds_day2      = $endTimestamp_day2 - $startTimestamp_day2;
            $minutes_day2      = ($seconds_day2 / 60) % 60;
            $hours_day2        = floor($seconds_day2 / (60 * 60));
            
            if($row['time_in_double_shift_morning'] != '00:00:00' && $row['time_in_double_shift_morning'] != '' && $row['leave_time_double_shift_morning'] != '00:00:00' && $row['leave_time_double_shift_morning'] != ''){
                $total_time_day2 = sprintf("%02d", $hours_day2). ":" .sprintf("%02d", $minutes_day2);
            } else {
                $total_time_day2 = '';
            }

            $record_sign_in_day3  = $row['time_in_double_shift_evening'];
            $record_sign_out_day3 = $row['leave_time_double_shift_evening'];
            $record_created_day3  = $row['record_date'];
            $time1_day3           = date("H:i", strtotime($record_sign_in_day3));
            $time2_day3           = date("H:i", strtotime($record_sign_out_day3));
            $record_created_day3  = date("l", strtotime($record_created_day3));
            $day_day3             = $record_created_day3;
            list($hours_day3, $minutes_day3) = explode(':', $time1_day3);
            $startTimestamp_day3 = mktime($hours_day3, $minutes_day3);
            list($hours_day3, $minutes_day3) = explode(':', $time2_day3);
            $endTimestamp_day3 = mktime($hours_day3, $minutes_day3);
            $seconds_day3      = $endTimestamp_day3 - $startTimestamp_day3;
            $minutes_day3      = ($seconds_day3 / 60) % 60;
            $hours_day3        = floor($seconds_day3 / (60 * 60));
            
            if($row['time_in_double_shift_evening'] != '00:00:00' && $row['time_in_double_shift_evening'] != '' && $row['leave_time_double_shift_evening'] != '00:00:00' && $row['leave_time_double_shift_evening'] != ''){
                $total_time_day3 = sprintf("%02d", $hours_day3). ":" .sprintf("%02d", $minutes_day3);
            } else {
                $total_time_day3 = '';
            }

            $record_sign_in_night  = $row['time_in_night_shift'];
            $record_sign_out_night = $row['leave_time_night_shift'];
            $record_created_night  = $row['record_date'];
            $time1_night           = date("H:i", strtotime($record_sign_in_night));
            $time2_night           = date("H:i", strtotime($record_sign_out_night));
            $record_created_night  = date("l", strtotime($record_created_night));
            $day_night             = $record_created_night;
            list($hours_night, $minutes_night) = explode(':', $time1_night);
            $startTimestamp_night  = mktime($hours_night, $minutes_night);
            list($hours_night, $minutes_night) = explode(':', $time2_night);
            $endTimestamp_night    = mktime($hours_night, $minutes_night);
            $seconds_night         = $endTimestamp_night - $startTimestamp_night;
            $minutes_night         = ($seconds_night / 60) % 60;
            $hours_night           = floor($seconds_night / (60 * 60));
            
            if($row['time_in_night_shift'] != '00:00:00' && $row['time_in_night_shift'] != '' && $row['leave_time_night_shift'] != '00:00:00' && $row['leave_time_night_shift'] != ''){
                $total_time_night = sprintf("%02d", $hours_night). ":" .sprintf("%02d", $minutes_night);
            } else {
                $total_time_night = '';
            }

            $total_time = $this->sum_the_time($total_time, $total_time_day2, $total_time_day3, $total_time_night);

            $on_leave = ($row['on_leave'] == 1) ? "Absent" : "Present";

            if($row['shoes'] == 'Yes'){
                $shoes = "<div class='shoesVal' style='margin-left:20%;'>Yes</div>";
            } else {
                $shoes = "<div class='shoesVal'><input class='check' type='checkbox' value='' name='shoes' attendance_id='{$row['attendance_id']}'></div>";
            }

            if($row['badge'] == 'Yes'){
                $badge = "<div class='badgeVal' style='margin-left:20%;'>Yes</div>";
            } else {
                $badge = "<div class='badgeVal'><input class='check' type='checkbox' value='' name='badge' attendance_id='{$row['attendance_id']}'></div>";
            }

            if($row['dress'] == 'Yes'){
                $dress = "<div class='dressVal' style='margin-left:20%;'>Yes</div>";
            } else {
                $dress = "<div class='dressVal'><input class='check' type='checkbox' value='' name='dress' attendance_id='{$row['attendance_id']}'></div>";
            }

            $rows .="
            {$listObj->getListRowHeader($row, $rowCounter)}
            {$listObj->getGoToDetailText($rowCounter, $row['employee_name'])}
            {$listObj->getListDateCell($row['record_date'])}
            {$listObj->getListDataCell($on_leave)}
            {$listObj->getListDataCell($shoes)}
            {$listObj->getListDataCell($badge)}
            {$listObj->getListDataCell($dress)}
            {$timeInOutDataDayShift}
            {$timeInOutDataShiftDSMorning}
            {$timeInOutDataShiftDSEvening}
            {$timeInOutDataNightShift}
            {$timeInOutDataExtraShift}
            {$listObj->getListRowEnd($row['attendance_id'])}
            ";

            $rowCounter++;
        }

        if ($cpCfg['m.hms.attendance.hasMultipleSessions'] == 1) {
            $timeInOutHeader = "
            {$listObj->getListHeaderCell('Time In (Morning)', 'a.time_in_morning')}
            {$listObj->getListHeaderCell('Time Out (Morning)', 'a.leave_time_morning')}
            {$listObj->getListHeaderCell('Time In (Evening)', 'a.time_in_evening')}
            {$listObj->getListHeaderCell('Time Out (Evening)', 'a.leave_time_evening')}
            ";

            /*$timeInOutHeader = "
            {$listObj->getListHeaderCell('Time In', 'a.time_in_morning')}
            {$listObj->getListHeaderCell('Time Out', 'a.leave_time_evening')}
            ";*/
        } else {
            $timeInOutHeaderDayShift = "
            {$listObj->getListHeaderCell('Day TI/TO')}
            ";

            $timeInOutHeaderNightShift = "
            {$listObj->getListHeaderCell('Night TI/TO')}
            ";

            $timeInOutHeaderExtraShift = "
            {$listObj->getListHeaderCell('Extra TI/TO')}
            ";

            $timeInOutHeaderDShiftMorning = "
            {$listObj->getListHeaderCell('DSM TI/TO')}
            ";

            $timeInOutHeaderDShiftEventing = "
            {$listObj->getListHeaderCell('DSE TI/TO')}
            ";
        }

        $text = "
        {$listObj->getListHeader()}
        {$listObj->getListHeaderCell($cpCfg['m.project.staffFieldLabel'], 'b.staff_id')}
        {$listObj->getListHeaderCell('Date', 'a.record_date')}
        {$listObj->getListHeaderCell('Status', 'a.on_leave')}
        {$listObj->getListHeaderCell('Shoes')}
        {$listObj->getListHeaderCell('Badge')}
        {$listObj->getListHeaderCell('Dress')}
        {$timeInOutHeaderDayShift}
        {$timeInOutHeaderDShiftMorning}
        {$timeInOutHeaderDShiftEventing}
        {$timeInOutHeaderNightShift}
        {$timeInOutHeaderExtraShift}
        {$listObj->getListHeaderEnd()}
        {$rows}
        {$listObj->getListFooter()}
        ";
        return $text;
    }

    /**
     *
     */
    function getNew(){
        $formObj = Zend_Registry::get('formObj');
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');
        $sqlStaff = "
        SELECT s.staff_id
              ,CONCAT_WS(' ', s.first_name, s.last_name) AS staff_name
        FROM staff s
        ORDER BY staff_name
        ";
        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlEmp = "AND e.site_id = {$cpSiteIdSession}";
        }

        $sqlStaff = "
        SELECT distinct e.employee_id
              ,CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
        FROM `employee` e
        WHERE e.status = 'Active' 
        AND e.add_in_payroll = 1
        {$appendSqlEmp}
        ORDER BY employee_name
        ";

        $fieldset = "
        {$formObj->getDDRowBySQL('Employee',  'employee_id', $sqlStaff, '')}
        ";

        $text = "
        {$formObj->getFieldSetWrapped('Key Details', $fieldset)}
        ";

        return $text;
    }

    /**
     *
     */
    function getEdit($row) {
        $cpCfg = Zend_Registry::get('cpCfg');
        $formObj = Zend_Registry::get('formObj');
        $ln = Zend_Registry::get('ln');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $leave = '';
        
        $sqlStaff = "
        SELECT e.employee_id
              ,e.first_name AS employee_name
        FROM employee e
        ORDER BY employee_name
        ";

        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlEmp = "AND e.site_id = {$cpSiteIdSession}";
        }

        $sqlStaff = "
        SELECT distinct a.employee_id
              ,CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
        FROM `attendance` a
        LEFT JOIN (`employee` e) ON (e.employee_id = a.employee_id) 
        WHERE e.status = 'Active' 
        AND e.add_in_payroll = 1
        {$appendSqlEmp}
        ORDER BY employee_name
        ";

        $expStf = array('detailValue' => $row['employee_name']);
        $expNoEdit  = array('isEditable' => 0);

        if ($_SESSION['userGroupName'] == "Super Administrator" || 
            $_SESSION['userGroupName'] == "Administrator") {
            $expNoEdit  = array('isEditable' => 1);
        }

        if ($_SESSION['userGroupName'] == "Super Administrator" || 
            $_SESSION['userGroupName'] == "Administrator") {
            $leave = $formObj->getYesNoRRow('Absent Today', 'on_leave', $row['on_leave'], $expNoEdit);
        }

        $time = "
        {$formObj->getTimeRow('Time in (HH:MM)', 'time_in_day_shift', $row['time_in_day_shift'], $expNoEdit)}
        {$formObj->getTimeRow('Time out (HH:MM)', 'leave_time_day_shift', $row['leave_time_day_shift'], $expNoEdit)}
        ";

        $timeDSM = "
        {$formObj->getTimeRow('Time in (HH:MM)', 'time_in_double_shift_morning', $row['time_in_double_shift_morning'], $expNoEdit)}
        {$formObj->getTimeRow('Time out (HH:MM)', 'leave_time_double_shift_morning', $row['leave_time_double_shift_morning'], $expNoEdit)}
        ";

        $timeDSE = "
        {$formObj->getTimeRow('Time in (HH:MM)', 'time_in_double_shift_evening', $row['time_in_double_shift_evening'], $expNoEdit)}
        {$formObj->getTimeRow('Time out (HH:MM)', 'leave_time_double_shift_evening', $row['leave_time_double_shift_evening'], $expNoEdit)}
        ";

        $timeNight = "
        {$formObj->getTimeRow('Time in (HH:MM)', 'time_in_night_shift', $row['time_in_night_shift'], $expNoEdit)}
        {$formObj->getTimeRow('Time out (HH:MM)', 'leave_time_night_shift', $row['leave_time_night_shift'], $expNoEdit)}
        ";

        $timeExtra = "
        {$formObj->getTimeRow('Time in (HH:MM)', 'time_in_extra_shift', $row['time_in_extra_shift'], $expNoEdit)}
        {$formObj->getTimeRow('Time out (HH:MM)', 'leave_time_extra_shift', $row['leave_time_extra_shift'], $expNoEdit)}
        ";

        $sqlEmployee = "
        SELECT employee_type
        FROM employee
        WHERE employee_id = {$row['employee_id']}
        ";
        $resultEmployee = $db->sql_query($sqlEmployee);
        $rowEmployee    = $db->sql_fetchrow($resultEmployee);

        $inOut = "
        {$formObj->getFieldSetWrapped('Day Timing', $time)}
        {$formObj->getFieldSetWrapped('Night Timing', $timeNight)}
        {$formObj->getFieldSetWrapped('Extra Timing', $timeExtra)}
        ";
        $shift = array(
              "Day"
             ,"Night"
        );

        if($rowEmployee['employee_type'] == "Double Shift") {
            $inOut = "
            {$formObj->getFieldSetWrapped('Double Shift Morning Timing', $timeDSM)}
            {$formObj->getFieldSetWrapped('Double Shift Evening Timing', $timeDSE)}
            {$formObj->getFieldSetWrapped('Night Timing', $timeNight)}
            {$formObj->getFieldSetWrapped('Extra Timing', $timeExtra)}
            ";

            $shift = array(
                  "DSM"
                 ,"DSE"
                 ,"Night"
            );
        }

        $sqlTypeOfLeave = $fn->getValueListSQL('typeOfLeave');
        $expVl     = array('sqlType' => 'OneField');

        $fielset1  = "
        {$formObj->getDDRowBySQL('Employee',  'employee_id', $sqlStaff, $row['employee_id'], $expStf)}
        {$formObj->getDateRow('Date', 'record_date', $row['record_date'])}
        ";

        if($row['shoes'] == 'Yes'){
            $shoes = "<div class='shoesVal' style='display: inline;margin-left:20%;'>Yes</div>";
        } else {
            $shoes = "<div class='shoesVal' style='display: inline;'><input  class='check' type='checkbox' value='{$row['shoes']}' name='shoes' attendance_id='{$row['attendance_id']}'></div>";
        }

        if($row['badge'] == 'Yes'){
            $badge = "<div class='badgeVal' style='display: inline;margin-left:20%;'>Yes</div>";
        } else {
            $badge = "<div class='badgeVal' style='display: inline;'><input  class='check' type='checkbox' value='{$row['badge']}' name='badge' attendance_id='{$row['attendance_id']}'></div>";
        }

        if($row['dress'] == 'Yes'){
            $dress = "<div class='dressVal' style='display: inline;margin-left:20%;'>Yes</div>";
        } else {
            $dress = "<div class='dressVal' style='display: inline;'><input  class='check' type='checkbox' value='{$row['dress']}' name='dress' attendance_id='{$row['attendance_id']}'></div>";
        }

        $text = "
        {$formObj->getFieldSetWrapped('Details', $fielset1)}
        {$leave}
        {$formObj->getDDRowByArr('Shift', 'shift', $shift, $row['shift'])}
        {$inOut}
        <div class='shoes type-check'>
            <label for='fld_shoes'>Shoes</label>
            {$shoes}
        </div>
        <div class='badgeFld type-check'>
            <label for='fld_badge'>Badge</label>
            {$badge}
        </div>
        <div class='dress type-check'>
            <label for='fld_dress'>Dress</label>
            {$dress}
        </div>

        {$formObj->getCreationModificationText($row)}
        ";

        return $text;
    }


function getQuickSearch() {
    $fn = Zend_Registry::get('fn');
    $db = Zend_Registry::get('db');
    $tv = Zend_Registry::get('tv');
    $dbUtil = Zend_Registry::get('dbUtil');
    $cpUtil = Zend_Registry::get('cpUtil');

    $userGroupID = $fn->getSessionParam('userGroupID');
    $special_search = $fn->getReqParam('special_search');
    $attendanceDate1 = $fn->getReqParam('attendanceDate1');
    $attendanceDate2 = $fn->getReqParam('attendanceDate2');
    $employee_id = $fn->getReqParam('employee_id');
    $searchMonth = $fn->getReqParam('search_month');
    $searchYear = $fn->getReqParam('search_year');
    $employeeCategory = $fn->getReqParam('employee_category'); // New filter param

    // Add fallback defaults here
if (empty($searchMonth)) {
    $searchMonth = date('m');
}
if (empty($searchYear)) {
    $searchYear = date('Y');
}

    $yearEnd = date('Y') + 10;
    $employeeText = '';
    $cpCfg = Zend_Registry::get('cpCfg');
    $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

    if ($cpCfg['cp.hasMultiUniqueSites']) {
        $appendSqlEmp = "AND e.site_id = {$cpSiteIdSession}";
    }

    // Get distinct employee categories (positions) for dropdown
    $sqlCategories = "
        SELECT DISTINCT position 
        FROM employee 
        WHERE position != 'Doctor' 
          AND add_in_payroll = 1
          AND site_id = '{$cpSiteIdSession}'
        ORDER BY position
    ";
    $resultCategories = $db->sql_query($sqlCategories);
    $categoryOptions = "<option value=''>Category</option>";
    while ($rowCat = $db->sql_fetchrow($resultCategories)) {
        $selected = ($employeeCategory == $rowCat['position']) ? "selected" : "";
        $categoryOptions .= "<option value='" . htmlspecialchars($rowCat['position']) . "' $selected>" . htmlspecialchars($rowCat['position']) . "</option>";
    }

    if ($userGroupID == 1 || $userGroupID == 2) {
        $SQLEmp = "
            SELECT DISTINCT a.employee_id,
                            CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
            FROM `attendance` a
            LEFT JOIN `employee` e ON e.employee_id = a.employee_id 
            WHERE e.position = 'Nurse' 
           
            {$appendSqlEmp}
            ORDER BY employee_name
        ";

        $employeeText ="
        <td>
            <select name='employee_id'>
                <option value=''>Employee</option>
                {$dbUtil->getDropDownFromSQLCols2($db, $SQLEmp, $employee_id)}
            </select>
        </td>
        ";
    }

    // Generate month options
    $monthOptions = "<option value=''>Month</option>";
    for ($m = 1; $m <= 12; $m++) {
        $monthValue = str_pad($m, 2, '0', STR_PAD_LEFT);
        $monthName = date("F", mktime(0, 0, 0, $m, 1));
        $selected = ($searchMonth == $monthValue) ? "selected" : "";
        $monthOptions .= "<option value='$monthValue' $selected>$monthName</option>";
    }

    // Generate year options
    $currentYear = date('Y');
    $startYear = $currentYear - 10;
    $endYear = $currentYear + 10;

    $yearOptions = "<option value=''>Year</option>";
    for ($y = $startYear; $y <= $endYear; $y++) {
        $selected = ($searchYear == $y) ? "selected" : "";
        $yearOptions .= "<option value='$y' $selected>$y</option>";
    }

    $olArray = array("Present", "Absent");

    $text = "
    <td>
        <select name='employee_category'>$categoryOptions</select>
    </td>
    <td>
        <select name='search_month'>$monthOptions</select>
    </td>
    <td>
        <select name='search_year'>$yearOptions</select>
    </td>
    
    ";

    return $text;
}


    /**
     *
     */
    function getOldQuickSearch() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpUtil = Zend_Registry::get('cpUtil');

        $userGroupID 		= $fn->getSessionParam('userGroupID');
        $special_search     = $fn->getReqParam('special_search');
        $special_search     = $fn->getReqParam('special_search');
        $attendanceDate1    = $fn->getReqParam('attendanceDate1');
        $attendanceDate2    = $fn->getReqParam('attendanceDate2');
        $yearEnd = date('Y') + 10;
        $SQL = '';
        $employeeText = '';
        $employee_id        = $fn->getReqParam('employee_id');

        $cpCfg = Zend_Registry::get('cpCfg');
        $cpSiteIdSession = $fn->getSessionParam('cp_site_id');

        if ($cpCfg['cp.hasMultiUniqueSites']) {
            $appendSqlEmp = "AND e.site_id = {$cpSiteIdSession}";
        }

        if ($userGroupID == 1 || $userGroupID == 2){

            $SQLEmp = "
            SELECT distinct a.employee_id
                  ,CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name
            FROM `attendance` a
            LEFT JOIN (`employee` e) ON (e.employee_id = a.employee_id) 
            WHERE e.status = 'Active' 
            AND e.add_in_payroll = 1
            {$appendSqlEmp}
            ORDER BY employee_name
            ";

            $employeeText ="
            <td>
                <select name='employee_id' >
                    <option value=''>Employee</option>
                    {$dbUtil->getDropDownFromSQLCols2($db, $SQLEmp, $employee_id)}
                </select>
            </td>
            ";

        }

        $olArray = array(
            "Present"
            ,"Absent"
        );

        
        $text = "
        {$employeeText}
        <td>
            <select name='special_search'>
                <option value=''>Present / Absent</option>
                {$cpUtil->getDropDown1($olArray, $tv['special_search'])}
            </select>
        </td>
        <td class='dateRange'>
            <b class='float_left'>Attendance Date:</b>
            <input type='text' allowEdit='1' name='attendanceDate1' class='fld_date'
                   id='fld_quoteDate1' value='{$attendanceDate1 }' yearEnd='{$yearEnd}' />
            <input type='text' allowEdit='1' name='attendanceDate2' class='fld_date'
                   id='fld_quoteDate2' value='{$attendanceDate2}' yearEnd='{$yearEnd}' />
        </td>
        ";

        return $text;
    }

    /**
     *
     */
   function getSendAttendanceReportToPM() {
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        $appendSQL = '';
        $yesterday = date("Y-m-d", strtotime("yesterday"));
        //$yesterday = "2011-08-14";
        $rowCounter = 0;

        $SQL = "
        SELECT s.staff_id
              ,CONCAT_WS(' ', s.first_name, s.last_name) AS staff_name
              ,s.name AS display_name
              ,s.email
        FROM staff s
        WHERE s.staff_id NOT IN
              (SELECT a.staff_id
                FROM attendance a
                WHERE a.staff_id = s.staff_id
                AND a.record_date = '{$yesterday}'
              )
        AND s.position IN ('Programmer SG', 'Programmer HK', 'Programmer', 'Trainee')
        AND s.published = 1
        ORDER BY staff_name
        ";
        $result  = $db->sql_query($SQL);
        $numRows = $db->sql_numrows($result);
        $staff_name1 = '';
        $rows = '';
        $comma = '';

        $data = array();
        while ($row = $db->sql_fetchrow($result, MYSQL_ASSOC)) {

            $staff_name   = $row['display_name'];

            if ($staff_name != $staff_name1){
                $data[$staff_name] = array();
                $staff_name1 = $staff_name;
            }

            $data[$staff_name][] = $row;
        }

        $staff = "";
        $link = "";

        foreach ($data as $staff_name => $rows){

        if($rowCounter > 0){
            $comma = ",";
        }
            $staff_name_disp = "";
            foreach ($rows as $key => $row ){
                $staff_name_disp   = $row['display_name'];
                $staff_id   = $row['staff_id'];
            }

            $staff .= "{$comma} {$staff_name_disp}";
            $link .= "
            <p>
                <p><a href='http://studiouss.usoftsolutions.com/admin/index.php?_topRm=admin&module=hms_attendance&_spAction=list&searchDone=1&staff_id={$staff_id}'>{$staff_name_disp}</a></p>
            </p>
            ";

            if($numRows > 0) {
               $fa['creation_date'] = date("Y-m-d H:i:s");
               $fa['staff_id']      = $row['staff_id'];
               $fa['record_date']   = $yesterday;
               $fa['on_leave']      = 1;
               $fa['leave_time']    = '00:00:00';
               $fa['time_in']       = '00:00:00';
               $fa['type_of_leave'] = 'Personal Leave';
               $SQL                 = $dbUtil->getInsertSQLStringFromArray($fa, "attendance");
               $result              = $db->sql_query($SQL);
            }
            $rowCounter++;

        }

        $s = '';
        if ($rowCounter > 1){
            $s = 's';
        }

        $text = "
        <table border='0'>
            <tbody>
            <p>Dear Syed / Moin</p>
            <p>The below mentioned staff{$s} seems to be not present yesterday / Not marked the attendance. </p>
            <p>They have marked as not present. </p>
            <p>Please update the attendance if any changes</p>
            {$link}
            <p>Thanks for your help.</p>
            <p>Regards<br>
            Admin</p>
            </tbody>
        </table>
        ";

        $message     = $text;

        $subject     = "USS Attendance" ." - " . $staff . " - " . $yesterday;
        $fromName    = "Admin";
        $fromEmail   = "usstech@usoftsolutions.com";

        $toName      = "Syed, Moin";
        $toEmail     = "shafeeq@usoftsolutions.com";
        $toEmail1    = "";

        $args = array(
             'toName'    => $toName
            ,'toEmail'   => $toEmail
            ,'toEmail1'  => $toEmail1
            ,'subject'   => $subject
            ,'message'   => $message
            ,'fromName'  => $fromName
            ,'fromEmail' => $fromEmail
        );

        if ($rowCounter > 0){
            $emailMsg = includeCPClass('Lib', 'EmailTemplate', 'EmailTemplate', true, array('args' => $args));
            $emailMsg->sendEmail();
        }

        /*if ($rowCounter > 0){
            $smtp  = includeCPClass('libLocal', 'smtp', 'CPSMTP');
            $error = $smtp->sendEmail($toName, $toEmail, $fromName, $fromEmail, $subject, $message);
            $error = $smtp->sendEmail($toName, $toEmail1, $fromName, $fromEmail, $subject, $message);
        } */

        return $text;
    }

    /**
     *
     */
   function getCreateAttendanceForAbsent() {
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        //admin/index.php?_spAction=createAttendanceForAbsent&showHTML=0&module=hms_attendance

        $appendSQL = '';
        $yesterday = date("Y-m-d", strtotime("yesterday"));
        //$yesterday = "2011-08-14";
        $rowCounter = 0;

        $SQL = "
        SELECT e.*
        FROM `employee` e
        WHERE e.staff_id != ''
          AND e.status = 'Active'
          AND e.add_in_payroll = 1
          AND e.employee_id NOT IN (
              SELECT employee_id
              FROM attendance
              WHERE record_date = CURDATE()
          )
        ";
        $result  = $db->sql_query($SQL);
        $numRows = $db->sql_numrows($result);
        $rows = '';

        $data = array();
        while ($row = $db->sql_fetchrow($result)) {
            $today = date("Y-m-d");
            $fa = array();
            $fa['staff_id']      = $row['staff_id'];
            $fa['employee_id']   = $row['employee_id'];
            $fa['record_date']   = $today;
            $fa['on_leave']      = 1;
            $fa['site_id']       = $row['site_id'];
            $fa['creation_date'] = date('Y-m-d H:i:s');
            $fa['created_by']    = 'Admin';

            $SQLInsert      = $dbUtil->getInsertSQLStringFromArray($fa, 'attendance');
            $resultInsert   = $db->sql_query($SQLInsert);
        }

        return 'success';
    }

    function sum_the_time($time1, $time2, $time3, $time4) {
        $times = array($time1, $time2, $time3, $time4);
        $seconds = 0;

        foreach ($times as $time) {

            if($time != ""){
                list($hour,$minute) = explode(':', $time);
                $seconds += $hour*3600;
                $seconds += $minute*60;
            }
        }

        $hours    = floor($seconds/3600);
        $seconds -= $hours*3600;
        $minutes  = floor($seconds/60);

        if($minutes <= 9) {
            $minutes = "0".$minutes;
        }

        if($hours <= 9) {
            $hours = "0".$hours;
        }

        $total_hrs = $hours.':'.$minutes;

        if($hours == "00" && $minutes == "00"){
            $total_hrs = "";
        }

        return "{$total_hrs}";
    }

    /**
     *
     */
   function getDeleteDuplicateAttendanceForAbsent() {
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        //admin/index.php?_spAction=deleteDuplicateAttendanceForAbsent&showHTML=0&module=hms_attendance

        $appendSQL = '';
        $rowCounter = 0;

        /*for($i=1; $i < 6; $i++){
            $datesAtt  = '2018-08-'.$i;

            $SQL = "
            SELECT employee_id, record_date 
            FROM attendance 
            WHERE record_date = '{$datesAtt}' 
            GROUP BY employee_id 
            HAVING COUNT(employee_id) > 1 
            ORDER BY `attendance`.`record_date` DESC
            ";
            $result  = $db->sql_query($SQL);

            $numRows = $db->sql_numrows($result);
            $rows = '';

            $data = array();
            while ($row = $db->sql_fetchrow($result)) {
                $sqlDelete ="
                DELETE FROM attendance WHERE employee_id = {$row['employee_id']} AND on_leave = 1 AND record_date = '{$row['record_date']}'
                ";
                $resultDelete  = $db->sql_query($sqlDelete);
            }
        }*/

        return 'success';
    }

    /**
     *
     */
    function getDoubleShiftTimingUpdate() {
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        //http://habibiahms.localhost/admin/index.php?module=hms_attendance&_spAction=doubleShiftTimingUpdate&showHTML=0
    
        $SQLAttendance = "
        SELECT a.attendance_id
              ,CONCAT_WS('', e.first_name, e.last_name) AS employee_name
              ,a.time_in_day_shift
              ,a.leave_time_day_shift
        FROM attendance a
        LEFT JOIN employee e ON (e.employee_id = a.employee_id)
        WHERE e.employee_type = 'Double Shift'
        AND (a.on_leave IS NULL OR a.on_leave = 0)
        AND a.time_in_day_shift != ''
        AND a.leave_time_day_shift != ''
        ORDER BY a.attendance_id DESC
        ";
        $resultAttendance = $db->sql_query($SQLAttendance);
        while ($rowAttendance    = $db->sql_fetchrow($resultAttendance)) {
            $SQLUpdateAttendance = "
            UPDATE `attendance` SET time_in_double_shift_morning = '{$rowAttendance['time_in_day_shift']}'
                                ,leave_time_double_shift_morning = '{$rowAttendance['leave_time_day_shift']}'
            WHERE attendance_id = {$rowAttendance['attendance_id']} 
            ";
            $resultUpdateAttendance = $db->sql_query($SQLUpdateAttendance);

            $SQLUpdateAttendanceDay = "
            UPDATE `attendance` SET time_in_day_shift = NULL
                                ,leave_time_day_shift = NULL
            WHERE attendance_id = {$rowAttendance['attendance_id']} 
            ";
            $resultUpdateAttendanceDay = $db->sql_query($SQLUpdateAttendanceDay);

            print($rowAttendance['employee_name'].' --- '.$rowAttendance['attendance_id'].'<br/>');
        }
    }
}