Util.createCPObject('cpm.hms.attendance');

cpm.hms.attendance.init = function(){
    $('.v-edit input[name=shoes]').livequery('click', function(e){
        var attendance_id = $(this).attr('attendance_id');
            
        var url = 'index.php?module=hms_attendance&_spAction=updateAttendanceShoes&showHTML=0';
        $.get(url, {attendance_id: attendance_id}, function(html){
            Util.closeAllDialogs();
            $('.shoesVal').text('Yes');
            $('.shoesVal').css('margin-left', '20%');
        });
        Util.hideProgressInd();
    });

    $('.v-list input[name=shoes]').livequery('click', function(e){
        var attendance_id = $(this).attr('attendance_id');
        var parent = $(this).closest('tr');
            
        var url = 'index.php?module=hms_attendance&_spAction=updateAttendanceShoes&showHTML=0';
        $.get(url, {attendance_id: attendance_id}, function(html){
            Util.closeAllDialogs();
            $('.shoesVal', parent).text('Yes');
            $('.shoesVal', parent).css('margin-left', '20%');
        });
        Util.hideProgressInd();
    });

    $('.v-edit .badgeFld input[name=badge]').livequery('click', function(e){
        var attendance_id = $(this).attr('attendance_id');
            
        var url = 'index.php?module=hms_attendance&_spAction=updateAttendanceBadge&showHTML=0';
        $.get(url, {attendance_id: attendance_id}, function(html){
            Util.closeAllDialogs();
            $('.badgeVal').text('Yes');
            $('.badgeVal').css('margin-left', '20%');
        });
        Util.hideProgressInd();
    });

    $('.v-list input[name=badge]').livequery('click', function(e){
        var attendance_id = $(this).attr('attendance_id');
        var parent = $(this).closest('tr');
            
        var url = 'index.php?module=hms_attendance&_spAction=updateAttendanceBadge&showHTML=0';
        $.get(url, {attendance_id: attendance_id}, function(html){
            Util.closeAllDialogs();
            $('.badgeVal', parent).text('Yes');
            $('.badgeVal', parent).css('margin-left', '20%');
        });
        Util.hideProgressInd();
    });

    $('.v-edit .dress input[name=dress]').livequery('click', function(e){
        var attendance_id = $(this).attr('attendance_id');
            
        var url = 'index.php?module=hms_attendance&_spAction=updateAttendanceDress&showHTML=0';
        $.get(url, {attendance_id: attendance_id}, function(html){
            Util.closeAllDialogs();
            $('.dressVal').text('Yes');
            $('.dressVal').css('margin-left', '20%');
        });
        Util.hideProgressInd();
    });

    $('.v-list input[name=dress]').livequery('click', function(e){
        var attendance_id = $(this).attr('attendance_id');
        var parent = $(this).closest('tr');
            
        var url = 'index.php?module=hms_attendance&_spAction=updateAttendanceDress&showHTML=0';
        $.get(url, {attendance_id: attendance_id}, function(html){
            Util.closeAllDialogs();
            $('.dressVal', parent).text('Yes');
            $('.dressVal', parent).css('margin-left', '20%');
        });
        Util.hideProgressInd();
    });
}