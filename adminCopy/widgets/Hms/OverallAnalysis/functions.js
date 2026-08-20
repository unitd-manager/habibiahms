/* Filtering year with respect to values chosen */
$('#wd_hms_overallAnalysis select[name=year]').livequery('change', function(){
    var year = $(this).val();

    var url = 'index.php?widget=hms_overallAnalysis&_spAction=rowsHTML';
    Util.showProgressInd();
    $.get(url,{year: year, change: 1}, function(html){
        $('#wd_hms_overallAnalysis tbody').html(html);
        Util.hideProgressInd();
    });
});

