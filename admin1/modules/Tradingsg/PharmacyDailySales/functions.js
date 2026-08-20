Util.createCPObject('cpm.tradingsg.pharmacyDailySales');

cpm.tradingsg.pharmacyDailySales = {
    init: function(){
    	$('input[name=sales_amount]').livequery('keyup', function(){
            cpm.tradingsg.pharmacyDailySales.reloadTotalAmount();
        });

        $('input[name=excess_amount]').livequery('keyup', function(){
            cpm.tradingsg.pharmacyDailySales.reloadTotalAmount();
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