Util.createCPObject('cpm.hms.renewal');

cpm.hms.renewal = {
    init: function(){
        $('.addNewValue').livequery('click', function (e){
        var title = "Add New Value";
        e.preventDefault();
        e.stopImmediatePropagation();

        var valuelist_name = $(this).attr('valuelist_name');
        var field_name = $(this).attr('field_name') || 'valuelist_id';

        var expObj = {
            validate: true
           ,callbackOnSuccess: function(json){
                Util.closeAllDialogs();
                var selectedValue = '';
                if (json.extraParam) {
                    if (valuelist_name == 'Payment Status For Renewal' || valuelist_name == 'Renewal Status For Renewal') {
                        selectedValue = json.extraParam.valuelist_value || json.extraParam.valuelist_id || '';
                    } else {
                        selectedValue = json.extraParam.valuelist_id || '';
                    }
                }
                cpm.hms.renewal.refreshValueListOptions(valuelist_name, field_name, selectedValue);
            }
        }
        Util.openFormInDialog.call(this, 'portalForm', title, 400, 300, expObj);
        });

        $('.editSelectedValue').livequery('click', function (e){
            var title = "Edit Selected Category";
            e.preventDefault();

            var valuelist_name = $(this).attr('valuelist_name');
            var selectedId = $('select[name=valuelist_id]').val();

            if (!selectedId) {
                alert('Please select a category before editing.');
                return false;
            }

            var url = 'index.php?module=hms_renewal&_spAction=editValuelistForm&showHTML=0&valuelist_id=' + selectedId + '&valuelist_name=' + encodeURIComponent(valuelist_name);
            var $tempLink = $('<a/>');

            Util.openFormInDialog.call($tempLink, 'portalForm', title, 400, 300, {
                url: url,
                validate: true,
                callbackOnSuccess: function(){
                    Util.closeAllDialogs();
                    cpm.hms.renewal.refreshCategoryOptions(valuelist_name, selectedId);
                }
            });
        });

        $('.deleteSelectedValue').livequery('click', function (e){
            e.preventDefault();
            var valuelist_name = $(this).attr('valuelist_name');
            var selectedId = $('select[name=valuelist_id]').val();

            if (!selectedId) {
                alert('Please select a category before deleting.');
                return false;
            }

            if (!confirm('Do you want to delete the selected category?')) {
                return false;
            }

            Util.showProgressInd();
            var url = 'index.php?module=hms_renewal&_spAction=deleteValuelist&showHTML=0&valuelist_id=' + selectedId;
            $.get(url, function(){
                Util.hideProgressInd();
                cpm.hms.renewal.refreshCategoryOptions(valuelist_name);
            });
        });



        /* Add Medical Parameters */
        $('#AddMedicalParameters').live('click', function (e){
                var title = "Add Medical Parameters";
                e.preventDefault();
                var medical_test_id = $(this).attr('medical_test_id');

                var expObj = {
                    validate: true
                   ,callbackOnSuccess: function(){
                        var msg = 'Medical Parameters Added Successfully';
                        Util.alert(msg, function(){
                            Util.closeAllDialogs();
                            cpm.hms.renewal.reloadParameters(medical_test_id);
                            //window.location.reload(true);
                        });
                    }
                }
                Util.openFormInDialog.call(this, 'medicalParametersPortalForm', title, 600, 400, expObj);
        });

            /* Edit Medical Parameters */
        $('.EditMedicalParameters').live('click', function (e){
            var title = "Edit Medical Parameters";
            var medical_test_id = $(this).attr('medical_test_id');
            e.preventDefault();

            var expObj = {
                validate: true
               ,callbackOnSuccess: function(){
                    Util.closeAllDialogs();
                    Util.alert('Medical Parameters Updated Successfully');
                    Util.hideProgressInd();
                    cpm.hms.renewal.reloadParameters(medical_test_id);
                }
            }
            Util.openFormInDialog.call(this, 'portalForm', title, 600, 500, expObj);
        });


        /* Delete Medical Parameters */
        $('.deleteMedicalParameters').live('click', function (e){
            msg = "Do you like to delete the Medical Parameters?";
            if (!confirm(msg)){
                return false;
            }
            else{
                Util.showProgressInd();
                var medical_test_parameter_id = $(this).attr('medical_test_parameter_id');
                var medical_test_id = $(this).attr('medical_test_id');

                var url = 'index.php?module=hms_renewal&_spAction=DeleteMedicalParameters&showHTML=0&medical_test_parameter_id=' + medical_test_parameter_id;
                $.get(url, {medical_test_parameter_id: medical_test_parameter_id}, function(html){
                    Util.hideProgressInd();
                    cpm.hms.renewal.reloadParameters(medical_test_id);
                });
            }
        });

        /* Add Medical Test Group */
        $('#AddMedicalTestGroup').live('click', function (e){
            var title = "Add Group Name";
            e.preventDefault();
            var medical_test_id = $(this).attr('medical_test_id');

            var expObj = {
                validate: true
               ,callbackOnSuccess: function(){
                    var msg = 'Group Added Successfully';
                    Util.alert(msg, function(){
                        Util.closeAllDialogs();
                        cpm.hms.renewal.reloadMedicalTestGroup(medical_test_id);
                        //window.location.reload(true);
                    });
                }
            }

            Util.openFormInDialog.call(this, 'medicalTestGroupPortalForm', title, 500, 250, expObj);
        });

        
        /* Edit Medical Test Group */
        $('.EditMedicalTestGroup').live('click', function (e){
            var title = "Edit Group";
            var medical_test_id = $(this).attr('medical_test_id');
            e.preventDefault();

            var expObj = {
                validate: true
               ,callbackOnSuccess: function(){
                    Util.closeAllDialogs();
                    Util.alert('Group Updated Successfully');
                    Util.hideProgressInd();
                    cpm.hms.renewal.reloadMedicalTestGroup(medical_test_id);
                }
            }
            Util.openFormInDialog.call(this, 'medicalTestGroupEditPortalForm', title, 500, 250, expObj);
        });

        /* Delete Medical Test Group */
        $('.deleteMedicalTestGroup').live('click', function (e){
            msg = "Do you like to delete the Group?";
            if (!confirm(msg)){
                return false;
            }
            else{
                Util.showProgressInd();
                var medical_test_group_id = $(this).attr('medical_test_group_id');
                var medical_test_id = $(this).attr('medical_test_id');

                var url = 'index.php?module=hms_renewal&_spAction=DeleteMedicalTestGroup&showHTML=0&medical_test_group_id=' + medical_test_group_id;
                $.get(url, {medical_test_group_id: medical_test_group_id}, function(html){
                    Util.hideProgressInd();
                    cpm.hms.renewal.reloadMedicalTestGroup(medical_test_id);
                    //window.location.reload(true);
                });
            }
        });
        
    },


    refreshValueListOptions: function(valuelistName, fieldName, selectedValue){
        var url = 'index.php?module=hms_renewal&_spAction=valueByValuelistJSON&showHTML=0';
        $.get(url, {valuelist_name: valuelistName}, function (data) {
            var selector = 'select[name="' + fieldName + '"]';
            $(selector).each(function(){
                var $select = $(this);
                $select.cp_loadSelect(data);
                if (selectedValue) {
                    var matched = false;
                    var normalizedSelected = $.trim(selectedValue.toString()).toLowerCase();
                    $select.find('option').each(function(){
                        var optionValue = $(this).val();
                        var optionText = $(this).text();
                        var normalizedValue = $.trim(optionValue.toString()).toLowerCase();
                        var normalizedText = $.trim(optionText.toString()).toLowerCase();
                        if (normalizedValue === normalizedSelected || normalizedText === normalizedSelected) {
                            $select[0].value = optionValue;
                            $(this).prop('selected', true);
                            matched = true;
                            return false;
                        }
                    });
                    if (!matched) {
                        $select[0].value = selectedValue;
                    }
                    $select.trigger('change');
                }
            });
        }, 'json');
    },

    refreshCategoryOptions: function(valuelistName, selectedId){
        var url = 'index.php?module=hms_renewal&_spAction=valueByValuelistJSON&showHTML=0';
        $.get(url, {valuelist_name: valuelistName}, function (data) {
            var selector = 'select[name="valuelist_id"]';
            $(selector).each(function(){
                var $select = $(this);
                $select.cp_loadSelect(data);
                if (selectedId) {
                    var matched = false;
                    $select.find('option').each(function(){
                        var optionValue = $(this).val();
                        var optionText = $(this).text();
                        if (optionValue === selectedId || optionText === selectedId) {
                            $select.val(optionValue).trigger('change');
                            matched = true;
                            return false;
                        }
                    });
                    if (!matched) {
                        $select.val(selectedId).trigger('change');
                    }
                }
            });
        }, 'json');
    },

    reloadParameters: function(medical_test_id){
        var url = 'index.php?module=hms_renewal&_spAction=medicalTestParameter&showHTML=0';
        $.get(url,{medical_test_id:medical_test_id}, function(html){
            $('#medicalTestLinkPortal').html(html);
            //Util.hideProgressInd();
        });
    },

    reloadMedicalTestGroup: function(medical_test_id){
        var url = 'index.php?module=hms_renewal&_spAction=medicalTestGroupLink&showHTML=0';
        $.get(url,{medical_test_id:medical_test_id}, function(html){
            $('#medicalTestGroupLinkPortal').html(html);
            //Util.hideProgressInd();
        });
    },
}