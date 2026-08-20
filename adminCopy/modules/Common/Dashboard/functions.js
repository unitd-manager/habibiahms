Util.createCPObject('cpm.common.dashboard');

cpm.common.dashboard.init = function(){
    $('.m-common_dashboard .tableOuter table.list tr:odd').addClass('odd');
    $('.m-common_dashboard .tableOuter table.list tr:even').addClass('even');

    $('.m-common_dashboard select[name=location_type]').change(this.changeLocationType);

    $(".m-common_dashboard .widget").sortable({
    	connectWith: '.widget',
    	// We make the .portlet-header to act as a handle for moving portlets //
    	handle: 'h2'
    });

    $(".btnRefreshColorPanels span.refreshIcon").livequery('click', function() {
        var className        = $(this).closest('div').attr('class');
        var classNameReplace = className.replace("w-", "");
        var widgetName       = classNameReplace.replace("-", "_");

        Util.showProgressInd();
        var url = 'index.php?widget='+widgetName+'&_spAction=widget&showHTML=0';
        $.get(url, function(html){
            $("div#wd_"+widgetName).html(html);
            $("div#wd_"+widgetName).addClass("ui-widget ui-widget-content ui-helper-clearfix ui-corner-all")
            .find("h2")
            .addClass("ui-widget-header ui-corner-top")
            .prepend('<span class="ui-icon ui-icon-triangle-1-n"></span>')
            .end()
            .find(".portlet-content");
            Util.hideProgressInd();
        });
    });

    // We create the protlets and style them accordingly by script //
    $(".m-common_dashboard .widget").addClass("ui-widget ui-widget-content ui-helper-clearfix ui-corner-all")
    	.find("h2")
    		.addClass("ui-widget-header ui-corner-top")
    		.prepend('<span class="ui-icon ui-icon-triangle-1-n"></span>')
    		.end()
    	.find(".portlet-content");
    // We make arrow button on any portlet header to act as a switch for sliding up and down the portlet content //
    $("h2 .ui-icon").click(function() {
    	$(this).parents(".widget:first").find(".tableOuter").slideToggle("fast");
    	$(this).toggleClass("ui-icon-triangle-1-s");
    	return false;
    });

    $("a.btnRefreshColorPanels").livequery('click', function (e){
        Util.showProgressInd();
        cpm.common.dashboard.reloadPatientVisitDisplay1();
        cpm.common.dashboard.reloadPatientVisitDisplay2();
        cpm.common.dashboard.reloadPatientVisitDisplay3();
        cpm.common.dashboard.reloadPatientVisitSummary();
        cpm.common.dashboard.reloadPatientVisitSiteWise();
        cpm.common.dashboard.reloadLabReportSummary();
        cpm.common.dashboard.reloadAttendanceReportSummary();
        cpm.common.dashboard.reloadOverallRevenue();
    });

    $("a.overallToday").livequery('click', function (e){
        Util.showProgressInd();
        var location_type = $(this).data('location-type');
        var url = 'index.php?module=common_dashboard&_spAction=overallRevenueSummary&showHTML=0';
        $.get(url, {overallDay:'Today',location_type:location_type}, function(html){
            $('#overallRevenueDiv').html(html);
            $('.overallTitle').html('Today Revenue');
            Util.hideProgressInd();
        });
    });

    $("a.overallYesterday").livequery('click', function (e){
        Util.showProgressInd();
        var location_type = $(this).data('location-type');
        var url = 'index.php?module=common_dashboard&_spAction=overallRevenueSummary&showHTML=0';
        $.get(url, {overallDay:'Yesterday',location_type:location_type}, function(html){
            $('#overallRevenueDiv').html(html);
            $('.overallTitle').html('Yesterday Revenue');
            Util.hideProgressInd();
        });
    });

    $("a.showOverallRevenue").livequery('click', function (e){
        Util.showProgressInd();
        var location_type = $(this).data('location-type');

        var url = 'index.php?module=common_dashboard&_spAction=overallRevenueSummary&showHTML=0';
        $.get(url, {overallDay:'Overall',location_type:location_type}, function(html){
            $('#overallRevenueDiv').html(html);
            $('.overallTitle').html('Overall Revenue');
            Util.hideProgressInd();
        });
    });
}

cpm.common.dashboard.changeLocationType = function() {
    var location_type = $(this).val();
    var urlRedirect = "index.php?_topRm=main&module=common_dashboard&location_type=" + location_type;
    document.location = urlRedirect;
}

cpm.common.dashboard.reloadPatientVisitSummary = function(){
    var url = 'index.php?module=common_dashboard&_spAction=patientDoctorWise&showHTML=0';
    $.get(url, function(html){
        $('#patientVisitSummaryDiv').html(html);
    });
}

cpm.common.dashboard.reloadPatientVisitSiteWise = function(){
    var url = 'index.php?module=common_dashboard&_spAction=patientVisitSiteWise&showHTML=0';
    $.get(url, function(html){
        $('#patientVisitSummarySiteWiseDiv').html(html);
    });
}

cpm.common.dashboard.reloadLabReportSummary = function(){
    var url = 'index.php?module=common_dashboard&_spAction=labReportSummary&showHTML=0';
    $.get(url, function(html){
        $('#labReportSummaryYesterdayDiv').html(html);
    });
}

cpm.common.dashboard.reloadPatientVisitDisplay1 = function(){
    var url = 'index.php?module=common_dashboard&_spAction=patientVisitDisplay&showHTML=0';
    var day = 'Today';
    var type = 'Walk In';
    $.get(url, {day:day, type:type}, function(html){
        $('#PatientVisitDisplayDiv1').html(html);
    });
}

cpm.common.dashboard.reloadPatientVisitDisplay2 = function(){
    var url = 'index.php?module=common_dashboard&_spAction=patientVisitDisplay&showHTML=0';
    var day = 'Yesterday';
    var type = 'Walk In';
    $.get(url, {day:day, type:type}, function(html){
        $('#PatientVisitDisplayDiv2').html(html);
    });
}

cpm.common.dashboard.reloadPatientVisitDisplay3 = function(){
    var url = 'index.php?module=common_dashboard&_spAction=patientVisitDisplay&showHTML=0';
    var day = 'Last Week';
    var type = 'Walk In';
    $.get(url, {day:day, type:type}, function(html){
        $('#PatientVisitDisplayDiv3').html(html);
    });
}

cpm.common.dashboard.reloadAttendanceReportSummary = function(){
    var url = 'index.php?module=common_dashboard&_spAction=attendanceReportSummary&showHTML=0';
    $.get(url, function(html){
        $('#attendanceReportSummaryDiv').html(html);
        Util.hideProgressInd();
    });
}

cpm.common.dashboard.reloadOverallRevenue = function(){
    var url = 'index.php?module=common_dashboard&_spAction=overallRevenueSummary&showHTML=0';
    $.get(url, function(html){
        $('#overallRevenueDiv').html(html);
        Util.hideProgressInd();
    });
}