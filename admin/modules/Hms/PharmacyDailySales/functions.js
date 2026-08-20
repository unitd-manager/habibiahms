Util.createCPObject('cpm.hms.pharmacyDailySales');

cpm.hms.pharmacyDailySales = {
    init: function(){
    	$('input[name=sales_amount]').livequery('keyup', function(){
            cpm.hms.pharmacyDailySales.reloadTotalAmount();
        });

        $('input[name=excess_amount]').livequery('keyup', function(){
            cpm.hms.pharmacyDailySales.reloadTotalAmount();
        });
    
    /* Add Dosage AgeWise */
        $('#AddPharmacyDailySalesHistory').live('click', function (e){
                var title = "Add Pharmacy Daily Sales History";
                e.preventDefault();
                var pharma_daily_sales_id = $(this).attr('pharma_daily_sales_id');
                $("#pharmacyDailySalesHistoryPortalForm input[name='amount']").focus();

                var expObj = {
                    validate: true
                   ,callbackOnSuccess: function(html){
                        var msg = 'Sales Added Successfully';
                        Util.alert(msg, function(){
                            Util.closeAllDialogs();
                            /*var excess_amount  = html.returnUrl;
                            var url = 'index.php?module=hms_pharmacyDailySales&_spAction=updateExcessAmount&showHTML=0&pharma_daily_sales_id=' + pharma_daily_sales_id;
                            $.get(url, {pharma_daily_sales_id: pharma_daily_sales_id, excess_amount:excess_amount}, function(html2){
                                $("#frmEdit input[name='excess_amount']").val(html2);
                            });*/

                            cpm.hms.pharmacyDailySales.reloadpharmacyhistory(pharma_daily_sales_id);
                            //window.location.reload(true);
                        });
                    }
                }
                Util.openFormInDialog.call(this, 'pharmacyDailySalesHistoryPortalForm', title, 600, 400, expObj);
        });

            /* Edit Dosage AgeWise */
        $('.EditPharmacyDailySalesHistory').live('click', function (e){
            var title = "Edit Pharmacy Daily Sales History";
            var pharma_daily_sales_id = $(this).attr('pharma_daily_sales_id');
            e.preventDefault();

            var expObj = {
                validate: true
               ,callbackOnSuccess: function(html){
                    Util.closeAllDialogs();
                    Util.alert('Sales Updated Successfully');
                    /*var excess_amount  = html.returnUrl;
                    var url = 'index.php?module=hms_pharmacyDailySales&_spAction=updateExcessAmount&showHTML=0&pharma_daily_sales_id=' + pharma_daily_sales_id;
                    $.get(url, {pharma_daily_sales_id: pharma_daily_sales_id, excess_amount:excess_amount}, function(html2){
                        $("#frmEdit input[name='excess_amount']").val(html2);
                    });*/
                    cpm.hms.pharmacyDailySales.reloadpharmacyhistory(pharma_daily_sales_id);
                }
            }
            Util.openFormInDialog.call(this, 'pharmacyDailySalesHistoryPortalForm', title, 600, 500, expObj);
        });

        $('.updateBillNos').live('click', function (){
            Util.showProgressInd();
            var url      = 'index.php?module=hms_pharmacyDailySales&_spAction=updateBillNos&showHTML=0';
            var time_in  = $("input[name='time_in']").val();
            var time_out = $("input[name='time_out']").val();
            var amount   = $("input[name='amount']").val();
            var pharma_daily_sales_id = $(this).attr('pharma_daily_sales_id');
            //alert(group_name);
            $.get(url,  {time_in:time_in, time_out:time_out, amount:amount, pharma_daily_sales_id:pharma_daily_sales_id}, function(html){
                var linkArr = html.split("_");
                var bill_no = linkArr[0];
                var excess_amount = linkArr[1];
                $('#fld_bill_no span').html(bill_no);
                $("#pharmacyDailySalesHistoryPortalForm input[name='excess_amount']").val(parseFloat(excess_amount).toFixed(2));
                Util.hideProgressInd();
            });
        });

        $('.deletePharmacyDailySalesHistory').live('click', function (e){
            msg = "Do you like to delete this record?";
            if (!confirm(msg)){
                return false;
            }
            else{
                Util.showProgressInd();
                var pharmacy_daily_sales_history_id = $(this).attr('pharmacy_daily_sales_history_id');
                var pharma_daily_sales_id = $(this).attr('pharma_daily_sales_id');

                var url = 'index.php?module=hms_pharmacyDailySales&_spAction=deletePharmacyDailySalesHistory&showHTML=0&pharmacy_daily_sales_history_id=' + pharmacy_daily_sales_history_id;
                $.get(url, {pharmacy_daily_sales_history_id: pharmacy_daily_sales_history_id}, function(html){
                    Util.hideProgressInd();
                    cpm.hms.pharmacyDailySales.reloadpharmacyhistory(pharma_daily_sales_id);
                });
            }
        });
    },

    reloadpharmacyhistory: function(pharma_daily_sales_id){
        var url = 'index.php?module=hms_pharmacyDailySales&_spAction=AddPharmacyDailySalesHistory&showHTML=0';
        $.get(url,{pharma_daily_sales_id:pharma_daily_sales_id}, function(html){
            $('#PharmacyDailySalesHistoryLinkPortal').html(html);
            //Util.hideProgressInd();
        });
    },

    reloadTotalAmount: function(){
        var sales_amount  = $('input[name=sales_amount]').val();
        var excess_amount = $('input[name=excess_amount]').val();
        var total         = parseFloat(0);

        if(sales_amount == ''){
            sales_amount = 0;
        }

        if(excess_amount == ''){
            excess_amount = 0;
        }

        total = parseFloat(sales_amount) + parseFloat(excess_amount);
        total = total.toFixed(2);

        $('.pharmacyTotalAmount').html(total);
    },
}