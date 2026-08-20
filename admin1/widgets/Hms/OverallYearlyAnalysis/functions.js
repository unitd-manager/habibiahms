/* Filtering year with respect to values chosen */
$('#wd_hms_overallYearlyAnalysis select[name=year]').livequery('change', function(){
    var year = $(this).val();

    var url = 'index.php?widget=hms_overallYearlyAnalysis&_spAction=rowsHTML';
    Util.showProgressInd();
    $.get(url,{year: year, change: 1}, function(html){
        $('#wd_hms_overallYearlyAnalysis tbody').html(html);
        Util.hideProgressInd();
    });
});

