Util.createCPObject('cpm.tradingsg.stockTransfer');

cpm.tradingsg.stockTransfer = {
    init: function(){
        $(".addProduct input[name='product_title']")
        .livequery(cpm.tradingsg.stockTransfer.posProductTitle);
        
        $("#orderItems input[name='qty']").live('change', function(){
            var qty = $(this).val();
            var stock_transfer_id = $(this).attr('stock_transfer_id');
            var stock_transfer_history_id = $(this).attr('stock_transfer_history_id');
            var request_qty = $(this).parents('tr').find("input[name='request_qty']");
            var request_qty = parseInt(request_qty.val(), 10);
            var stock = parseInt($(this).attr('stock'), 10);
            
            if(stock < qty){
                Util.alert('The qty should be less than the stock qty');
                cpm.tradingsg.stockTransfer.reloadOrderItems(stock_transfer_id);
            } else {
                if(request_qty < qty){
                    Util.alert('The qty should be less than the request qty');
                    cpm.tradingsg.stockTransfer.reloadOrderItems(stock_transfer_id);
                }
                else{
                    var url = 'index.php?module=tradingsg_stockTransfer&_spAction=updateQtyOrderItem&showHTML=0';
                    $.get(url, {qty: qty, stock_transfer_history_id: stock_transfer_history_id, stock_transfer_id: stock_transfer_id}, function(html){
                      cpm.tradingsg.stockTransfer.reloadOrderItems(stock_transfer_id); 
                    });
                }
            }
        });

        $("#orderItems input[name='request_qty']").live('change', function(){
            var request_qty = $(this).val();
            var stock_transfer_id = $(this).attr('stock_transfer_id');
            var stock_transfer_history_id = $(this).attr('stock_transfer_history_id');
            var stock =parseInt($(this).attr('stock'), 10);
            if(stock < request_qty){
                Util.alert('The qty should be less than the stock qty');
                cpm.tradingsg.stockTransfer.reloadOrderItems(stock_transfer_id);
            } else {
                var url = 'index.php?module=tradingsg_stockTransfer&_spAction=updateRequestQtyOrderItem&showHTML=0';
                $.get(url, {request_qty: request_qty, stock_transfer_history_id: stock_transfer_history_id, stock_transfer_id: stock_transfer_id}, function(html){
                  cpm.tradingsg.stockTransfer.reloadOrderItems(stock_transfer_id); 
                });
            }
        });

        $('.deleteItem').livequery('click', function (){
            var url = 'index.php?module=tradingsg_stockTransfer&_spAction=deleteItem&showHTML=0';
            var stock_transfer_id = $(this).attr('stock_transfer_id');
            var stock_transfer_history_id = $(this).attr('stock_transfer_history_id');

            var msg = "Are you sure to delete this item?";
            if (confirm(msg)){
                Util.showProgressInd();
                $.get(url,  {stock_transfer_history_id:stock_transfer_history_id, stock_transfer_id: stock_transfer_id}, function(html){
                    cpm.tradingsg.stockTransfer.reloadOrderItems(stock_transfer_id);
                });
            }
        });

        $('.completeTransaction').livequery('click', function (){
            var stock_transfer_id     = $(this).attr('stock_transfer_id');
            var site_id               = $(this).attr('site_id');
            var stockTransfer_product = $('.stockTransfer_product_count').val();
            var stockTransfer_product_qty = $('.stockTransfer_product_qty_count').val();

            if(stockTransfer_product == 0 || stockTransfer_product == undefined){
                alert('Please add some products!');
                $('#fld_product_title').focus();
            } else if(stockTransfer_product_qty > 0){
                alert('Please enter the request qty!');

            }else{

                var msg = "Do you like to complete the transaction?";
                if (confirm(msg)){
                    Util.showProgressInd();
                    var url = 'index.php?module=tradingsg_stockTransfer&_spAction=updateCompleteTransactionProduct&showHTML=0';
                    $.get(url, {stock_transfer_id: stock_transfer_id}, function(html){
                      cpm.tradingsg.stockTransfer.reloadEditDisplay(stock_transfer_id, site_id); 
                    });
                }
            }
        });

        $('.rollbackChanges').livequery('click', function (){
            var stock_transfer_id      = $(this).attr('stock_transfer_id');
            var site_id                = $(this).attr('site_id');

            var msg = "Do you like to rollback the transaction?";
            if (confirm(msg)){
                Util.showProgressInd();
                var url = 'index.php?module=tradingsg_stockTransfer&_spAction=rollbackCompleteTransactionProduct&showHTML=0';
                $.get(url, {stock_transfer_id: stock_transfer_id}, function(html){
                  cpm.tradingsg.stockTransfer.reloadEditDisplay(stock_transfer_id, site_id); 
                });
            }
        });

        $('.deductFromStock').livequery('click', function (){
            var stock_transfer_id = $(this).attr('stock_transfer_id');
            var site_id = $(this).attr('site_id');

            var msg = "This action will deduct item(s) from the stock \n\n Would you like to continue?";
            if (confirm(msg)){
                Util.showProgressInd();
                var url = 'index.php?module=tradingsg_stockTransfer&_spAction=updateDeductStockProduct&showHTML=0';
                $.get(url, {stock_transfer_id: stock_transfer_id}, function(html){
                  cpm.tradingsg.stockTransfer.reloadEditDisplay(stock_transfer_id, site_id);
                });
            }
        });

        $("select[name='status']").live('change', function(){
            var product_status    = $(this).val();
            var stock_transfer_id = $('#record_id').val();
            var site_id           = $("input[name='site_id']").val();
            
            Util.showProgressInd();
            var url = 'index.php?module=tradingsg_stockTransfer&_spAction=updateStatusStockTransfer&showHTML=0';
            $.get(url, {product_status: product_status, stock_transfer_id: stock_transfer_id, site_id: site_id}, function(html){
                cpm.tradingsg.stockTransfer.reloadEditDisplay(stock_transfer_id, site_id); 
            });
            
        });

        /* Add the batch popup product to pos */
        $('.batchProductAdd').livequery('click', function(){
            var product_id        = $(this).attr('product_id');
            var batch_no          = $(this).attr('batch_no');
            var stock_transfer_id = $(this).attr('stock_transfer_id');
            var po_product_id     = $(this).attr('po_product_id');

            var urlBatchProduct = 'index.php?module=tradingsg_stockTransfer&_spAction=addBatchProductForStockTransfer&showHTML=0';
            Util.showProgressInd();
            
            $.get(urlBatchProduct, {stock_transfer_id: stock_transfer_id, product_id: product_id, batch_no: batch_no, po_product_id: po_product_id}, function(html){
                if(html.msg == "Please note the product is already added"){
                    alert(html.msg);
                    Util.hideProgressInd();
                }
                else{
                    $(".addProduct input[name='product_title']").val('');
                    cpm.tradingsg.stockTransfer.reloadOrderItems(stock_transfer_id);
                }
            });
            
        });

        $("select[name='from_location_internal']").live('change', function(){
            var from_location = $(this).val();
            var url = 'index.php?module=tradingsg_stockTransfer&_spAction=toLocationJson&showHTML=0';
            $.get(url, {from_location: from_location}, function(data){
                $("select[name='to_location_internal']").cp_loadSelect(data);
            },'json');
            
        });

        /* Add the batch popup product to pos */
        $('input[name=transfer_type]').livequery('click', function(){
            var transfer_type = $(this).val();

            if(transfer_type == "internal") {
                $(".InternalLocationFromAndToLocation").removeClass('displayNone');
                $(".ExternalLocationFromAndToLocation").addClass('displayNone');
                $("select[name=from_location]").val("");
                $("select[name=to_location]").val("");
            } else {
                $(".InternalLocationFromAndToLocation").addClass('displayNone');
                $(".ExternalLocationFromAndToLocation").removeClass('displayNone');
                $("select[name=from_location_internal]").val("");
                $("select[name=to_location_internal]").val("");
            }
        });

        $(".deductStockForPatient").livequery('click', function (e){
            var product_id                = $(this).attr('product_id');
            var po_product_id             = $(this).attr('po_product_id');
            var batch_no                  = $(this).attr('batch_no');
            var stock_transfer_id         = $(this).attr('stock_transfer_id');
            var stock_transfer_history_id = $(this).attr('stock_transfer_history_id');

            Util.showProgressInd();
            var url = "index.php?module=tradingsg_stockTransfer&_spAction=deductStockForPatientDisplay&product_id="+product_id+"&po_product_id="+po_product_id+"&batch_no="+batch_no+"&stock_transfer_id="+stock_transfer_id+"&stock_transfer_history_id="+stock_transfer_history_id+"&showHTML=0";
            var exp = {
                url: url
            };

            Util.openDialogForLink('Deduct Stock For Patient', 350, 410, 0, exp);
        });

        $(".deductStockPatientView select[name=patient_type]").livequery('change', function (e){
            var patient_type = $(this).val();
            if(patient_type == "OP") {
                $(".visitOrOPCodeSearch").removeClass('displayNone');
                $(".visitOrOPCodeSearch .row_search_code label").html("Search OP Code");
                $(".visitOrOPCodeSearch .row_search_code input").attr("name", "search_op_code");
            } else if(patient_type == "IP") {
                $(".visitOrOPCodeSearch").removeClass('displayNone');
                $(".visitOrOPCodeSearch .row_search_code label").html("Search IP Code");
                $(".visitOrOPCodeSearch .row_search_code input").attr("name", "search_ip_code");
            } else {
                $(".visitOrOPCodeSearch").addClass('displayNone');
                $(".visitOrOPCodeSearch .row_search_code label").html("Search IP/OP Code");
                $(".visitOrOPCodeSearch .row_search_code input").attr("name", "search_code");
            }
        });

        $("input[name='search_op_code']")
        .livequery(cpm.tradingsg.stockTransfer.searchOPIPCode);

        $("input[name='search_ip_code']")
        .livequery(cpm.tradingsg.stockTransfer.searchOPIPCode);

        $(".patientDetails input[name=qty]").live('change', function (){
            var qty                       = $(this).val();
            var type                      = $("form#deductStockPatientView select[name=patient_type]").val();
            var stock_transfer_history_id = $("form#deductStockPatientView input[name=stock_transfer_history_id]").val();
            var in_patient_id             = $("form#deductStockPatientView input[name=in_patient_id]").val();
            var patient_visit_id          = $("form#deductStockPatientView input[name=patient_visit_id]").val();
            var patient_information_id    = $("form#deductStockPatientView input[name=patient_information_id]").val();

            Util.showProgressInd();
            var url = 'index.php?module=tradingsg_stockTransfer&_spAction=updateDeductStockForProduct&showHTML=0';
            $.get(url, {qty: qty, type: type, stock_transfer_history_id: stock_transfer_history_id, in_patient_id: in_patient_id, patient_visit_id: patient_visit_id, patient_information_id: patient_information_id}, function(html) {
                if(html != "") {
                    Util.alert(html);
                    $(".patientDetails input[name=qty]").val('');
                }
                Util.hideProgressInd();
            });
        });
    },

    posProductTitle: function() {
        var titleObj = this;
        var stock_transfer_id = $(this).attr('stock_transfer_id');
        var transfer_type     = $(this).attr('transfer_type');

        if(transfer_type == "internal") {
            var site_id = $(this).attr('from_location_internal');
        } else {
            var site_id = $(this).attr('from_location');
        }

        $(titleObj).autocomplete({
             source : 'index.php?module=tradingsg_stockTransfer&_spAction=searchProductTitle&stock_transfer_id='+stock_transfer_id+'&transfer_type='+transfer_type+'&site_id='+site_id+'&showHTML=0'
            ,minLength : 1
            ,selectFirst: true
            ,autoFocus: true
            ,focus: function(event, ui) {
                var len = $('.ui-autocomplete > li').length;
                if(len === 1){
                    var selectedObj = ui.item;
        			var product_id  = selectedObj.id
                    var stock       = selectedObj.stock

                    var urlBatchProduct = 'index.php?module=tradingsg_stockTransfer&_spAction=batchProductCountCheck&showHTML=0';
                    $.get(urlBatchProduct, {site_id: site_id, product_id: product_id, transfer_type: transfer_type}, function(html){
                        if(html > 1) {
                            Util.showProgressInd();
                            var url = "index.php?_topRm=inventory&module=tradingsg_stockTransfer&_spAction=BatchProductSelectStock&product_id="+product_id+"&stock_transfer_id="+stock_transfer_id+"&site_id="+site_id+"&transfer_type="+transfer_type+"&stock="+stock+"&showHTML=0";
                            var exp = {
                                url: url
                            };

                            Util.openDialogForLink('Batch Product(s)',  850, 350, 0, exp); 
                        }
                        else{
                			$(this).after("<input type='hidden' name='product_id' value=" + product_id + ">");

                            //--------------------------------------------
                            Util.showProgressInd();
            	           	var url = 'index.php?module=tradingsg_stockTransfer&_spAction=updateOrderLineItems&stock_transfer_id='+stock_transfer_id+'&transfer_type='+transfer_type+'&site_id='+site_id+'&showHTML=0';
                            $.get(url, {product_id: product_id, stock_transfer_id: stock_transfer_id}, function(json){
            	                cpm.tradingsg.stockTransfer.reloadOrderItems(stock_transfer_id);
            	                $(".addProduct input[name='product_title']").val('');
                                Util.hideProgressInd();
                            });
                            $(titleObj).autocomplete("close");
                        }
                    });
                }

                barcodeinput = 1;
            }
            ,select: function(event, ui) {
                barcodeinput = 1;
                var selectedObj = ui.item;
                var product_id  = selectedObj.id
                var stock       = selectedObj.stock

                var urlBatchProduct = 'index.php?module=tradingsg_stockTransfer&_spAction=batchProductCountCheck&showHTML=0';
                $.get(urlBatchProduct, {site_id: site_id, product_id: product_id, transfer_type: transfer_type}, function(html){
                    if(html > 1) {
                        Util.showProgressInd();
                        var url = "index.php?_topRm=inventory&module=tradingsg_stockTransfer&_spAction=BatchProductSelectStock&product_id="+product_id+"&stock_transfer_id="+stock_transfer_id+"&site_id="+site_id+"&transfer_type="+transfer_type+"&stock="+stock+"&showHTML=0";
                        var exp = {
                            url: url
                        };

                        Util.openDialogForLink('Batch Product(s)',  850, 350, 0, exp);                         
                    }
                    else{
                        $(this).after("<input type='hidden' name='product_id' value=" + product_id + ">");

                        //--------------------------------------------
                        Util.showProgressInd();
                        var url = 'index.php?module=tradingsg_stockTransfer&_spAction=updateOrderLineItems&stock_transfer_id='+stock_transfer_id+'&transfer_type='+transfer_type+'&site_id='+site_id+'&showHTML=0';
                        $.get(url, {product_id: product_id, stock_transfer_id: stock_transfer_id}, function(json){
                            cpm.tradingsg.stockTransfer.reloadOrderItems(stock_transfer_id);
                            $(".addProduct input[name='product_title']").val('');
                            Util.hideProgressInd();
                        });
                        $(titleObj).autocomplete( "close" );
                    }
                });
    		}
    	});
    },

    reloadOrderItems: function(stock_transfer_id){
        var url = 'index.php?module=tradingsg_stockTransfer&_spAction=orderItems&showHTML=0';
        $.get(url, {stock_transfer_id: stock_transfer_id}, function(html){
            $('#orderItems').html(html);
            Util.hideProgressInd();
        });
    },

    reloadEditDisplay: function(stock_transfer_id, site_id){
        var url = 'index.php?module=tradingsg_stockTransfer&_spAction=editDisplay&showHTML=0';
        $.get(url, {stock_transfer_id: stock_transfer_id, site_id:site_id}, function(html){
            $('#editDisplayLoad').html(html);
            Util.hideProgressInd();
        });
    },

    //Auto select patient details
    searchOPIPCode: function() {
        var titleObj = this;
        var title    = $(this).attr("name");

        if(title == "search_ip_code") {
            var patient_type = "IP";
        } else if(title == "search_op_code") {
            var patient_type = "OP";
        }

        $(titleObj).autocomplete({
            source: function(request, response) {
                $.ajax({
                  url: 'index.php?module=tradingsg_stockTransfer&_spAction=searchPatientDetailsOPOrIP&type='+patient_type+'&showHTML=0',
                  dataType: "json",
                  data: request,                    
                  success: function (data) {
                    // No matching result
                    if (data.length == 0) {
                        response("");
                    }

                    else {
                      response(data);
                    }

                  }});
            },
            minLength : 3,
            electFirst: true,
            autoFocus: true,
            select: function(event, ui) {
                var selectedObj = ui.item;
                if(patient_type == "OP") {
                    Util.showProgressInd();
                    var patient_visit_id          = selectedObj.id;
                    var patient_name              = selectedObj.patient_name;
                    var patient_information_id    = selectedObj.patient_information_id;
                    var stock_transfer_history_id = $("input[name=stock_transfer_history_id]").val();
                    var urlPV = 'index.php?module=tradingsg_stockTransfer&_spAction=patientDetails&showHTML=0';
                    $.get(urlPV, {type: patient_type, patient_visit_id: patient_visit_id, stock_transfer_history_id: stock_transfer_history_id, patient_name: patient_name, patient_information_id: patient_information_id}, function(html){
                        $(".patientDetails").html(html);
                        Util.hideProgressInd();
                    });
                    /*$(".patientDetails").append("<div class='type-text ym-fbox-text row_patient_name'>"
                     +"<label for='fld_patient_name'>Patient Name</label>"
                     +"<div class='txt'>"+patient_name+"</div>"
                     +"</div>"
                     +"<div class='type-text ym-fbox-text row_qty editable'>"
                     +"<label for='fld_qty'>Qty</label>"
                     +"<input type='text' name='qty' class='text' id='fld_qty' value=''>"
                     +"</div>"
                     +"<input type='hidden' name='patient_visit_id' value='"+patient_visit_id+"'>");*/
                } else if(patient_type == "IP") {
                    Util.showProgressInd();
                    var in_patient_id             = selectedObj.id;
                    var patient_name              = selectedObj.patient_name;
                    var patient_information_id    = selectedObj.patient_information_id;
                    var stock_transfer_history_id = $("input[name=stock_transfer_history_id]").val();
                    var urlIP = 'index.php?module=tradingsg_stockTransfer&_spAction=patientDetails&showHTML=0';
                    $.get(urlIP, {type: patient_type, in_patient_id: in_patient_id, stock_transfer_history_id: stock_transfer_history_id, patient_name: patient_name, patient_information_id: patient_information_id}, function(html){
                        $(".patientDetails").html(html); 
                        Util.hideProgressInd();
                    });
                    /*$(".patientDetails").append("<div class='type-text ym-fbox-text row_patient_name'>"
                     +"<label for='fld_patient_name'>Patient Name</label>"
                     +"<div class='txt'>"+patient_name+"</div>"
                     +"</div>"
                     +"<div class='type-text ym-fbox-text row_qty editable'>"
                     +"<label for='fld_qty'>Qty</label>"
                     +"<input type='text' name='qty' class='text' id='fld_qty' value=''>"
                     +"</div>"
                     +"<input type='hidden' name='in_patient_id' value='"+in_patient_id+"'>");*/
                }
            }
        });
    }, 
}

cpm.tradingsg.stockTransfer.afterNewLocation = function(){
    Util.closeAllDialogs();
    Util.alert('New location successfully created.', function(){
        //cpm.tradingsg.purchaseOrder.loadSupplier();
        window.location.reload(true);
    });
}