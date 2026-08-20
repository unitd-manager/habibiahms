Util.createCPObject('cpm.tradingin.inventory');

cpm.tradingin.inventory = {
    init: function(){
       $('#frmEdit select#fld_category_id').livequery('change', function(){
           Util.loadSubCategoryDropdown.call(this);
        });
        $(".poLinked").livequery('click', function (e){
            var product_id = $(this).attr('product_id');
            var site_id = $(this).attr('site_id');
            Util.showProgressInd();

            var url = "index.php?module=tradingin_inventory&_spAction=purchaseOrderDisplay&product_id="+product_id+"&site_id="+site_id+"&showHTML=0";
            var exp = {
                url: url
            };

            Util.openDialogForLink('Purchase Orders Linked', 700, 410, 0, exp);
        });

        $(".billsLinked").livequery('click', function (e){
            var product_id = $(this).attr('product_id');
            var site_id    = $(this).attr('site_id');
            Util.showProgressInd();

            var url = "index.php?module=tradingin_inventory&_spAction=orderDisplay&product_id="+product_id+"&site_id="+site_id+"&showHTML=0";
            var exp = {
                url: url
            };

            Util.openDialogForLink('Bills Linked', 700, 410, 0, exp);
        });

        $(".billsLinkedAfterManualStock").livequery('click', function (e){
            var product_id = $(this).attr('product_id');
            var site_id    = $(this).attr('site_id');
            var type       = $(this).attr('type');
            Util.showProgressInd();

            var url = "index.php?module=tradingin_inventory&_spAction=orderDisplayAfterManualStock&product_id="+product_id+"&site_id="+site_id+"&type="+type+"&showHTML=0";
            var exp = {
                url: url
            };

            Util.openDialogForLink('Bills Linked', 700, 410, 0, exp);
        });

        $(".batchStock").livequery('click', function (e){
            var product_id   = $(this).attr('product_id');
            var site_id      = $(this).attr('site_id');
            var inventory_id = $(this).attr('inventory_id');
            Util.showProgressInd();

            var url = "index.php?module=tradingin_inventory&_spAction=batchWiseStockDisplay&product_id="+product_id+"&site_id="+site_id+"&inventory_id="+inventory_id+"&showHTML=0";
            var exp = {
                 url: url
                ,wrapperId: 'inventoryAdjustBathWiseStockPopup'
            };

            Util.openDialogForLink('Batch Wise Stock', 950, 410, 0, exp);
        });

        $(".manualStock").livequery('click', function (e){
            var product_id = $(this).attr('product_id');
            var site_id = $(this).attr('site_id');
            var actual_stock = $(this).attr('actual_stock');
            Util.showProgressInd();

            var url = "index.php?module=tradingin_inventory&_spAction=manualStockDisplay&product_id="+product_id+"&site_id="+site_id+"&actual_stock="+actual_stock+"&showHTML=0";
            var exp = {
                 url: url
                ,wrapperId: 'inventoryManualStockPopup'
            };

            Util.openDialogForLink('Manual Stock', 900, 410, 0, exp);
        });

        $(".stockTransferredIn").livequery('click', function (e){
            var product_id = $(this).attr('product_id');
            var site_id = $(this).attr('site_id');
            Util.showProgressInd();

            var url = "index.php?module=tradingin_inventory&_spAction=stockTransferDisplay&product_id="+product_id+"&site_id="+site_id+"&type=stockIn&showHTML=0";
            var exp = {
                url: url
            };

            Util.openDialogForLink('Stack Transfer(In) Linked', 700, 410, 0, exp);
        });

        $(".stockTransferredOut").livequery('click', function (e){
            var product_id = $(this).attr('product_id');
            var site_id = $(this).attr('site_id');
            Util.showProgressInd();

            var url = "index.php?module=tradingin_inventory&_spAction=stockTransferDisplay&product_id="+product_id+"&site_id="+site_id+"&type=stockOut&showHTML=0";
            var exp = {
                url: url
            };

            Util.openDialogForLink('Stack Transfer(Out) Linked', 700, 410, 0, exp);
        });

        $('.viewAllUpdatedAdjustStockHistory').livequery('click', function (){
            Util.showProgressInd();
            var inventory_batchwise_stock_id = $(this).attr('inventory_batchwise_stock_id');

            if(inventory_batchwise_stock_id != "") {
                var url = "index.php?_topRm=inventory&module=tradingin_inventory&_spAction=updatedAdjustStockHistory&inventory_batchwise_stock_id="+inventory_batchwise_stock_id+"&showHTML=0";
                var exp = {
                    url: url
                };

                Util.openDialogForLink('Adjust Stock History',  550, 300, 0, exp);
            } else {
                Util.hideProgressInd();
                Util.alert("There is no history records found!");
            }
        });

        $('.AdjustStockInventoryEditSaveBtn').livequery('click', function(){
            var mgsalert = 'Adjust Stock Updated Successfully!';
            var n = noty({
                text: mgsalert,
                type: 'confirm',
                dismissQueue: true,
                layout: 'topCenter',
                theme: 'defaultTheme',
                timeout: 5000,
            });
        });

        $('.DeductStockInventoryEditSaveBtn').livequery('click', function(){
            var mgsalert = 'Adjust Stock Updated Successfully!';
            var n = noty({
                text: mgsalert,
                type: 'confirm',
                dismissQueue: true,
                layout: 'topCenter',
                theme: 'defaultTheme',
                timeout: 5000,
            });
        });

        $('.ManualStockInventorySaveBtn').livequery('click', function(){
            var mgsalert = 'Manual Stock Updated Successfully!';
            var n = noty({
                text: mgsalert,
                type: 'confirm',
                dismissQueue: true,
                layout: 'topCenter',
                theme: 'defaultTheme',
                timeout: 5000,
            });
        });

        $('input[name=adjust_stock].AdjustStockInventoryEdit').livequery('change', function(){
            var adjust_stock     = $(this).val();
            var batch_no         = $(this).attr('batch_no');
            var product_id       = $(this).attr('product_id');
            var inventory_id     = $(this).attr('inventory_id');
            var po_product_id    = $(this).attr('po_product_id');
            var stock            = $(this).attr('stock');
            var site_id          = $(this).attr('site_id');
            var parent           = $(this).closest('tr');
            var current_stock    = $('.overallStockForLocationWise input[name=current_stock]', parent).val();
            var not_add_in_stock = $("input[name='not_add_in_stock']").val();
            
            if(adjust_stock == "") {
                adjust_stock = 0;
            }

            Util.showProgressInd();
            var url = 'index.php?module=tradingin_inventory&_spAction=createUpdateChangedStockRecord&showHTML=0';
            $.get(url, {batch_no: batch_no, po_product_id: po_product_id, product_id: product_id, inventory_id:inventory_id, site_id:site_id, adjust_stock:adjust_stock}, function(html){
                var overallStock = parseInt(0);
                overallStock = parseInt(stock) + parseInt(adjust_stock);
                $('.overallStockForLocationWise', parent).html(overallStock);
                $("span.stockUpdatePopup_"+inventory_id).html(parseInt(html));
                
                if(not_add_in_stock == 0 && not_add_in_stock == "") {
                    $("span.stockUpdateList_"+inventory_id).html(parseInt(html));
                }

                $("span.stockUpdate_"+site_id+"_"+inventory_id).html(parseInt(html));
                $('input[name=adjust_stock].AdjustStockInventoryEdit', parent).val('');
                $('.overallStockForLocationWise input[name=current_stock]', parent).val(parseInt(current_stock) + parseInt(adjust_stock));
                Util.hideProgressInd();
            });
        });

        $('input[name=adjust_stock].DeductStockInventoryEdit').livequery('change', function(){
            var adjust_stock     = $(this).val();
            var batch_no         = $(this).attr('batch_no');
            var product_id       = $(this).attr('product_id');
            var inventory_id     = $(this).attr('inventory_id');
            var po_product_id    = $(this).attr('po_product_id');
            var stock            = $(this).attr('stock');
            var site_id          = $(this).attr('site_id');
            var parent           = $(this).closest('tr');
            var current_stock    = $('.overallStockForLocationWise input[name=current_stock]', parent).val();
            var not_add_in_stock = $("input[name='not_add_in_stock']").val();

            adjust_stock = -Math.abs(adjust_stock);

            if(adjust_stock == "") {
                adjust_stock = 0;
            }

            Util.showProgressInd();
            var url = 'index.php?module=tradingin_inventory&_spAction=createUpdateChangedStockRecord&showHTML=0';
            $.get(url, {batch_no: batch_no, po_product_id: po_product_id, product_id: product_id, inventory_id:inventory_id, site_id:site_id, adjust_stock:adjust_stock}, function(html){
                var overallStock = parseInt(0);
                overallStock = parseInt(stock) + parseInt(adjust_stock);
                $('.overallStockForLocationWise', parent).html(overallStock);
                $("span.stockUpdatePopup_"+inventory_id).html(parseInt(html));
                
                if(not_add_in_stock == 0 && not_add_in_stock == "") {
                    $("span.stockUpdateList_"+inventory_id).html(parseInt(html));
                }

                $("span.stockUpdate_"+site_id+"_"+inventory_id).html(parseInt(html));
                $('input[name=adjust_stock].DeductStockInventoryEdit', parent).val('');
                $('.overallStockForLocationWise input[name=current_stock]', parent).val(parseInt(current_stock) + parseInt(adjust_stock));
                Util.hideProgressInd();
            });
        });

        $('input[name=expired_stock].ExpiredStockInventoryEdit').livequery('change', function(){
            var expired_stock    = $(this).val();
            var product_id       = $(this).attr('product_id');
            var inventory_id     = $(this).attr('inventory_id');
            var stock            = $(this).attr('stock');
            var site_id          = $(this).attr('site_id');
            var not_add_in_stock = $("input[name='not_add_in_stock']").val();

            if(expired_stock == "") {
                expired_stock = 0;
            }

            Util.showProgressInd();
            var url = 'index.php?module=tradingin_inventory&_spAction=createUpdateExpiredStockRecord&showHTML=0';
            $.get(url, {product_id: product_id, inventory_id:inventory_id, site_id:site_id, expired_stock:expired_stock}, function(html){
                var overallStock = parseInt(0);
                overallStock = parseInt(stock) - parseInt(expired_stock);
                $("span.stockUpdatePopup_"+inventory_id).html(parseInt(html));
                
                if(not_add_in_stock == 0 && not_add_in_stock == "") {
                    $("span.stockUpdateList_"+inventory_id).html(parseInt(html));
                }
                
                $("span.stockUpdate_"+site_id+"_"+inventory_id).html(parseInt(html));
                $('input[name=expired_stock].ExpiredStockInventoryEdit').val('');
                Util.hideProgressInd();
            });
        });

        $('.viewAllUpdatedExpiredHistory').livequery('click', function (){
            Util.showProgressInd();
            var inventory_id = $(this).attr('inventory_id');
            var site_id      = $(this).attr('site_id');

            if(inventory_id != "") {
                var url = "index.php?_topRm=inventory&module=tradingin_inventory&_spAction=updatedExpiryStockHistory&inventory_id="+inventory_id+"&site_id="+site_id+"&showHTML=0";
                var exp = {
                    url: url
                };

                Util.openDialogForLink('Expired Stock History',  400, 300, 0, exp);
            } else {
                Util.hideProgressInd();
                Util.alert("There is no history records found!");
            }
        });

        $('.ExpiredStockInventoryEditSaveBtn').livequery('click', function(){
            var mgsalert = 'Expired Stock Updated Successfully!';
            var n = noty({
                text: mgsalert,
                type: 'confirm',
                dismissQueue: true,
                layout: 'topCenter',
                theme: 'defaultTheme',
                timeout: 5000,
            });
        });

        $('input[name=manual_stock].ManualStockInventory').livequery('change', function(){
            var manual_stock  = $(this).val();
            var product_id    = $(this).attr('product_id');
            var inventory_id    = $(this).attr('inventory_id');
            var actual_stock    = $(this).attr('actual_stock');
            var site_id       = $(this).attr('site_id');
            var parent        = $(this).closest('tr');

            if(manual_stock == "") {
                manual_stock = 0;
            }

            var url = 'index.php?module=tradingin_inventory&_spAction=createManualStockRecord&showHTML=0';
            $.get(url, {product_id: product_id, manual_stock:manual_stock, actual_stock:actual_stock, inventory_id:inventory_id, site_id:site_id}, function(html){
                cpm.tradingin.inventory.reloadManualStock(product_id, site_id);
            });
        });

        $('input[name=med_purch_qty]').livequery('change', function(){
            var med_purch_qty  = $(this).val();
            var parent         = $(this).closest('tr');
            var inventory_id   = $('.saveMedPurchQty', parent).attr('inventory_id');

            if(med_purch_qty == "") {
                med_purch_qty = 0;
            }

            var url = 'index.php?module=tradingin_inventory&_spAction=updateMedPurchasedQty&showHTML=0';
            $.get(url, {med_purch_qty:med_purch_qty, inventory_id:inventory_id}, function(html){
            });
        });

        $('input[name=exp_qty]').livequery('change', function(){
            var exp_qty  = $(this).val();
            var parent         = $(this).closest('tr');
            var inventory_id   = $('.saveExpQty', parent).attr('inventory_id');

            if(exp_qty == "") {
                exp_qty = 0;
            }

            var url = 'index.php?module=tradingin_inventory&_spAction=updateExpectedQty&showHTML=0';
            $.get(url, {exp_qty:exp_qty, inventory_id:inventory_id}, function(html){
            });
        });

        $('.addStockInventory').livequery('click', function (e){
            var parent = $(this).closest('tr');
            $('.addStockInventory', parent).hide();
            $('.deductStockInventory', parent).show();
            $('.AdjustStockInventoryEdit', parent).show();
            $('.AdjustStockInventoryEditSaveBtn', parent).show();
            $('.DeductStockInventoryEdit', parent).hide();
            $('.DeductStockInventoryEditSaveBtn', parent).hide();
        });

        $('.deductStockInventory').livequery('click', function (e){
            var parent = $(this).closest('tr');
            $('.deductStockInventory', parent).hide();
            $('.addStockInventory', parent).show();
            $('.DeductStockInventoryEdit', parent).show();
            $('.DeductStockInventoryEditSaveBtn', parent).show();
            $('.AdjustStockInventoryEdit', parent).hide();
            $('.AdjustStockInventoryEditSaveBtn', parent).hide();
        });

        $('.overallStockForLocationWise .saveCurrentStock').livequery('click', function(){
            var mgsalert = 'Stock Updated Successfully!';
            var n = noty({
                text: mgsalert,
                type: 'confirm',
                dismissQueue: true,
                layout: 'topCenter',
                theme: 'defaultTheme',
                timeout: 5000,
            });
        });

        $('.saveMedPurchQty').livequery('click', function(){
            var mgsalert = 'Purchased Qty Updated Successfully!';
            var n = noty({
                text: mgsalert,
                type: 'confirm',
                dismissQueue: true,
                layout: 'topCenter',
                theme: 'defaultTheme',
                timeout: 5000,
            });
        });

        $('.saveExpQty').livequery('click', function(){
            var mgsalert = 'Expected Qty Updated Successfully!';
            var n = noty({
                text: mgsalert,
                type: 'confirm',
                dismissQueue: true,
                layout: 'topCenter',
                theme: 'defaultTheme',
                timeout: 5000,
            });
        });

        $('.overallStockForLocationWise input[name=current_stock]').livequery('change', function(){
            var current_stock                = $(this).val();
            var parent                       = $(this).closest('tr');
            var inventory_batchwise_stock_id = $('.overallStockForLocationWise .saveCurrentStock', parent).attr('inventory_batchwise_stock_id');
            var product_id                   = $('.overallStockForLocationWise .saveCurrentStock', parent).attr('product_id');
            var inventory_id                 = $('.overallStockForLocationWise .saveCurrentStock', parent).attr('inventory_id');
            var site_id                      = $('.overallStockForLocationWise .saveCurrentStock', parent).attr('site_id');
            var purchasedQty                 = $("td.purchasedQtyValue", parent).html();
            var not_add_in_stock             = $("input[name='not_add_in_stock']").val();
            var current_stock_hidden         = $("input[name=current_stock_hidden]", parent).val();

            if(current_stock == "") {
                current_stock = 0;
            }

            if(parseInt(current_stock) > parseInt(purchasedQty)) {
                Util.alert("Please Enter Stock Qty Lesser or Equal to "+purchasedQty);
                $('.overallStockForLocationWise input[name=current_stock]', parent).val(current_stock_hidden);
            } else {
                Util.showProgressInd();
                var url = 'index.php?module=tradingin_inventory&_spAction=updateCurrentStockInventoryBatchRecord&showHTML=0';
                $.get(url, {product_id: product_id, inventory_id:inventory_id, site_id:site_id, current_stock:current_stock, inventory_batchwise_stock_id:inventory_batchwise_stock_id}, function(html){
                    $("span.stockUpdatePopup_"+inventory_id).html(parseInt(html));
                    $("input[name=current_stock_hidden]", parent).val(current_stock);
                    
                    if(not_add_in_stock == 0 && not_add_in_stock == "") {
                        $("span.stockUpdateList_"+inventory_id).html(parseInt(html));
                    }
                    
                    $("span.stockUpdate_"+site_id+"_"+inventory_id).html(parseInt(html));
                    Util.hideProgressInd();
                });
            }
        });

        $('.m-tradingin_inventory.v-list #inventoryAdjustBathWiseStockPopup .ui-dialog-titlebar-close').livequery('click', function (e){
            window.location.reload(true);
        });

        $('.m-tradingin_inventory.v-list #inventoryManualStockPopup .ui-dialog-titlebar-close').livequery('click', function (e){
            window.location.reload(true);
        });

        $('.inv-list-flag-all,.inv-list-unflag-all').livequery('click', cpm.tradingin.inventory.flagUnflagAllRecordsFromList);
    
        $('.inv-list-flag').livequery('click', function(e){
            cpm.tradingin.inventory.flagRecordFromList.call(this, e);
        });
    },

    reloadManualStock: function(product_id, site_id){
        var url = 'index.php?module=tradingin_inventory&_spAction=manualStockDisplayDetail&showHTML=0';
        $.get(url, {product_id: product_id, site_id:site_id}, function(html){
            $('#manualStockDetail').html(html);
            $("input[name='manual_stock']").val('');
            Util.hideProgressInd();
        });
    },

    flagUnflagAllRecordsFromList: function(e){
        e.preventDefault();
        var lnkObj = $(this);

        var action = 'flagAll';
        var actionText = 'flag all';
        var progressText = 'Flagging records';
        if (lnkObj.hasClass('inv-list-unflag-all')) {
            action = 'unflagAll';
            actionText = 'un-flag all';
            progressText = 'Un-flagging records';
        }

        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        const keyword = urlParams.get('keyword');
        const category_id = urlParams.get('category_id');
        const special_search = urlParams.get('special_search');
        const minimum_order_level = urlParams.get('minimum_order_level');
        const expiry_date = urlParams.get('expiry_date');

        var color = lnkObj.attr('color');

        var msg = "Are you sure to " + actionText + " the records in the list?";
        if (!confirm(msg)){
            return false;
        }

        var idsArr = [];
        $('#bodyList a[record_id]').each(function(ind, obj) {
            idsArr[idsArr.length] = $(obj).attr('record_id');
        });
        var ids = idsArr.join(',');
        var room = $('#cpRoom').val();
        var data = {record_ids: ids, color: color, action: action, keyword:keyword, category_id:category_id, special_search:special_search, minimum_order_level:minimum_order_level, expiry_date:expiry_date};
        var url = 'index.php?module=tradingin_inventory&_spAction=flagUnflagAllRecords&showHTML=0';

        Util.showProgressInd(progressText);
        $.get(url, data, function() {
            window.location.reload(true);
        });
    },

    flagRecordFromList: function(e){
        e.preventDefault();
        var lnkObj = $(this);
        lnkObj.html('');
        var room = lnkObj.attr('module');
        var record_id = lnkObj.attr('record_id');
        var currentValue = lnkObj.attr('currentValue');
        var color = lnkObj.attr('color');

        var data = {record_id: record_id, color: color, currentValue: currentValue};
        var url = 'index.php?module=tradingin_inventory&_spAction=flagRecordByID&showHTML=0';

        $.get(url, data, function (text) {
            $(lnkObj).parent().html(text);
        });
    },

}