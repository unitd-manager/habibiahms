Util.createCPObject('cpm.hms.employee');

cpm.hms.employee.init = function(){
    $('.m-hms_employee select[name=employee_work_type]').livequery('change', function (e){
        var employee_work_type = $(this).val();

        if(employee_work_type == 'Part time'){
            $('.m-hms_employee .addHourlyRate').show();
        } else {
            $('.m-hms_employee .addHourlyRate').hide();
        }
    });

    $('.m-hms_employee select[name=employee_work_type]').livequery('change', function (e){
        var employee_work_type = $(this).val();

        if(employee_work_type == 'Full Time'){
            $('.m-hms_employee .salaryForFullTime').show();
        } else {
            $('.m-hms_employee .salaryForFullTime').hide();
        }
    });

        $('#deleteEmpRecord').livequery('click', function(e){
            msg = "Do you like to delete Medicine?Are you sure you want to delete this record? You cannot undo this action!";
            var employee_id = $(this).attr('employee_id');
            if (!confirm(msg)){
                return false;
            } else {
                
                var url = 'index.php?module=hms_employee&_spAction=deleteEmployeeRecord&showHTML=0';
                $.get(url, {employee_id: employee_id}, function(html){
                    Util.closeAllDialogs();
                    var urlRedirect = "index.php?_topRm=utils&module=hms_employee";
                    document.location = urlRedirect;
                });
                Util.hideProgressInd();
            }
        });

        $(".row_position select[name=position]").livequery('change', function(){
            var position = $(this).val();
            if (position != 'Nurse' && position != 'LAB TECHNICIAN'){
                $('.feesFlds').removeClass('displayNone');
            } else {
                $('.feesFlds').addClass('displayNone');                
            }
        });

        $(".row_category select[name=category]").livequery('change', function(){
            var category = $(this).val();
            if (category != 'Staff' && category != 'Student'){
                $('.feesFlds').removeClass('displayNone');
            } else {
                $('.feesFlds').addClass('displayNone');                
            }
        });

    $('.addNewValue').livequery('click', function (e){
    var title = "Add New Value";
    e.preventDefault();

    var valuelist_name = $(this).attr('valuelist_name');

    var expObj = {
        validate: true
       ,callbackOnSuccess: function(){
            Util.closeAllDialogs();
            //window.location.reload(true);
            //$(".m-manPower_opportunity select[name='valuelist_value']").val(valuelist_value);

            var url = 'index.php?module=hms_employee&_spAction=valueByValuelistJSON&showHTML=0';
            $.get(url, {valuelist_name: valuelist_name}, function (data) {
                if(valuelist_name == 'positionType'){
                    $('#fld_position').cp_loadSelect(data);
                } 
            }, 'json');
        }
    }
    Util.openFormInDialog.call(this, 'portalForm', title, 400, 300, expObj);
    });



    $('.TimeinButton').livequery('click', function (e){
        var employee_id = $(this).attr('employee_id');
        var parent = $(this).closest('tr');
        //alert(employee_id);
        msg = "Would You Update Time In?";
        if (confirm (msg)){
            Util.showProgressInd();
            var url = 'index.php?module=hms_employee&_spAction=employeeTimeInUpdate';

            $.get(url, {employee_id: employee_id}, function(){
              $('.TimeinButton', parent).hide();
              Util.hideProgressInd();
                var mgsalert='Time In Updated!';
                var n = noty({
                    text: mgsalert,
                    type: 'confirm',
                    dismissQueue: true,
                    layout: 'topCenter',
                    theme: 'defaultTheme',
                    timeout: 5000,
                });
            });
        }

    });


    $('.TimeoutButton').livequery('click', function (e){
        var employee_id = $(this).attr('employee_id');
        var parent = $(this).closest('tr');
        msg = "Would You Update Time Out?";
        if (confirm (msg)){
            Util.showProgressInd();
            var url = 'index.php?module=hms_employee&_spAction=employeeTimeOutUpdate';

            $.get(url, {employee_id: employee_id}, function(){
              $('.TimeoutButton', parent).hide();
              Util.hideProgressInd();
                var mgsalert='Time Out Updated!';
                var n = noty({
                    text: mgsalert,
                    type: 'confirm',
                    dismissQueue: true,
                    layout: 'topCenter',
                    theme: 'defaultTheme',
                    timeout: 5000,
                });
            });
        }
    });

    $('.NightTimeinButton').livequery('click', function (e){
        var employee_id = $(this).attr('employee_id');
        var parent = $(this).closest('tr');
        //alert(employee_id);
        msg = "Would You Update Night Time In?";
        if (confirm (msg)){
            Util.showProgressInd();
            var url = 'index.php?module=hms_employee&_spAction=employeeNightTimeInUpdate';

            $.get(url, {employee_id: employee_id}, function(){
              $('.NightTimeinButton', parent).hide();
              Util.hideProgressInd();
              //alert('Night Time In Updated!');
                var mgsalert='Night Time In Updated!';
                var n = noty({
                    text: mgsalert,
                    type: 'confirm',
                    dismissQueue: true,
                    layout: 'topCenter',
                    theme: 'defaultTheme',
                    timeout: 5000,
                });
            });
        }

    });

    $('.NightTimeoutButton').livequery('click', function (e){
        var employee_id = $(this).attr('employee_id');
        var parent = $(this).closest('tr');
        msg = "Would You Update Night Time Out?";
        if (confirm (msg)){
            Util.showProgressInd();
            var url = 'index.php?module=hms_employee&_spAction=employeeNightTimeOutUpdate';

            $.get(url, {employee_id: employee_id}, function(){
              $('.NightTimeoutButton', parent).hide();
              Util.hideProgressInd();
                var mgsalert='Night Time Out Updated!';
                var n = noty({
                    text: mgsalert,
                    type: 'confirm',
                    dismissQueue: true,
                    layout: 'topCenter',
                    theme: 'defaultTheme',
                    timeout: 5000,
                });
            });
        }
    });

    $('.ExtraTimeinButton').livequery('click', function (e){
        var employee_id = $(this).attr('employee_id');
        var parent = $(this).closest('tr');
        //alert(employee_id);
        msg = "Would You Update Extra Time In?";
        if (confirm (msg)){
            Util.showProgressInd();
            var url = 'index.php?module=hms_employee&_spAction=employeeExtraTimeInUpdate';

            $.get(url, {employee_id: employee_id}, function(){
              $('.ExtraTimeinButton', parent).hide();
              Util.hideProgressInd();
                var mgsalert='Extra Time In Updated!';
                var n = noty({
                    text: mgsalert,
                    type: 'confirm',
                    dismissQueue: true,
                    layout: 'topCenter',
                    theme: 'defaultTheme',
                    timeout: 5000,
                });
            });
        }

    });

    $('.ExtraTimeoutButton').livequery('click', function (e){
        var employee_id = $(this).attr('employee_id');
        var parent = $(this).closest('tr');
        msg = "Would You Update Extra Time Out?";
        if (confirm (msg)){
            Util.showProgressInd();
            var url = 'index.php?module=hms_employee&_spAction=employeeExtraTimeOutUpdate';

            $.get(url, {employee_id: employee_id}, function(){
              $('.ExtraTimeoutButton', parent).hide();
              Util.hideProgressInd();
                var mgsalert='Extra Time Out Updated!';
                var n = noty({
                    text: mgsalert,
                    type: 'confirm',
                    dismissQueue: true,
                    layout: 'topCenter',
                    theme: 'defaultTheme',
                    timeout: 5000,
                });
            });
        }
    });

    /* Add Employee Performance*/
    $('#AddEmployeePerformance').live('click', function (e){
        var title = "Add Employee Performance";
        var employee_id = $(this).attr('employee_id');
        e.preventDefault();
        var expObj = {
            validate: true
           ,callbackOnSuccess: function(){
                var msg = 'Performance Added Successfully';
                Util.alert(msg, function(){
                    Util.closeAllDialogs();
                    //cpm.hms.employee.reloadEmployeePerformanceLink(employee_id);
                    window.location.reload(true);
                });
            }
        }

        Util.openFormInDialog.call(this, 'AddEmployeePerformanceForm', title, 550, 382, expObj);
    });

    /* Edit Employee Performance*/
    $('.EditEmployeePerformance').live('click', function (e){
        var title = "Edit Employee Performance";
        var employee_id = $(this).attr('employee_id');
        e.preventDefault();
        var expObj = {
            validate: true
           ,callbackOnSuccess: function(){
                var msg = 'Performance Update Successfully';
                Util.alert(msg, function(){
                    Util.closeAllDialogs();
                    window.location.reload(true);
                });
            }
        }

        Util.openFormInDialog.call(this, 'EditEmployeePerformanceForm', title, 550, 382, expObj);
    });

    /* Delete Employee Performance */
    $('.deleteEmployeePerformance').live('click', function (e){
        var emp_performance_id = $(this).attr('emp_performance_id');
        var employee_id        = $(this).attr('employee_id');

        var msg = "Are you sure to delete?";
        if (confirm(msg)){
            Util.showProgressInd();
            var url = 'index.php?module=hms_employee&_spAction=deleteEmployeePerformance'
                    + '&showHTML=0';
            $.get(url, {emp_performance_id:emp_performance_id, employee_id:employee_id} ,function(html){
                Util.hideProgressInd();
                alert('Deleted Successfully!');
                window.location.reload(true);
            });
        }
    });

    $("select[name=employee_type]").live('change', function (){
        var employee_type = $(this).val();

        if(employee_type == "Double Shift") {
            $('.doubleShiftTimings').removeClass('displayNone');
            $('.normalTimings').addClass('displayNone');
        } else {
            $('.normalTimings').removeClass('displayNone');
            $('.doubleShiftTimings').addClass('displayNone');
        }
    });
}

cpm.hms.employee.reloadEmployeePerformanceLink = function(employee_id){
    var url = 'index.php?module=hms_employee&_spAction=EmployeePerformance&showHTML=0';
    $.get(url, {employee_id: employee_id}, function(html){
        $('#employeePerformanceLinkPortal').html(html);
    });
}