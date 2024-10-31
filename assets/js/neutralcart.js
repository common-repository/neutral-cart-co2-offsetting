var $ =jQuery.noConflict();
$(document).ready(function() {
    show_hide_cndtn()
});

function show_hide_cndtn(){
	$(".cndtn_mode").hide();
    var id = $('#neutralcart_apply_cndtn').val();
    $("#"+id).show();
}