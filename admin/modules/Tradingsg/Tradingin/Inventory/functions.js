Util.createCPObject('cpm.tradingin.inventory');

cpm.tradingin.inventory = {
    init: function(){
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
    },

    reloadManualStock: function(product_id, site_id){
        var url = 'index.php?module=tradingin_inventory&_spAction=manualStockDisplayDetail&showHTML=0';
        $.get(url, {product_id: product_id, site_id:site_id}, function(html){
            $('#manualStockDetail').html(html);
            $("input[name='manual_stock']").val('');
            Util.hideProgressInd();
        });
    },
}