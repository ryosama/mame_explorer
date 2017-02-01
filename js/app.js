var timer;

$(document).ready(function() {

	// search
	$('#submit-search').on('click', function(e){
		document.rom_search.submit();
	});

	// open manufacturer choice
	$('#manufacturer').on('keyup',function(e){
		clearTimeout(timer);

		if ($(this).val().length > 0) { // min 1 caractere
			timer = setTimeout("suggest_manufacturer()",400);
		} else {
			$('#close-suggest-manufacturer').hide();
		}
	});

	// close manufacturer choice
	$('#close-suggest-manufacturer').click(function(){
		$('#suggest-manufacturer').slideUp('fast');
	});

	// click on manufacturer
	$('body').delegate('#suggest-manufacturer .suggest-container li','click',function(e){
		$('#manufacturer').val( $(this).text() );
		$('#suggest-manufacturer').slideUp('fast');
	});

	// adaptive table on mobile
	$('#display_info table').stacktable();
	$('#input_info table').stacktable();
	$('#rom_info table').stacktable();
	$('#chip_info table').stacktable();
	$('#adjuster_info table').stacktable();
	$('#biosset_list table').stacktable();
	$('#sample_info table').stacktable();
	$('#disk_info table').stacktable();

}); // end on ready

function goToGame(game) {
	document.location.href='index.php?name='+game;
}

function change_page(select_obj) {
	document.location.href='?pageno=' + select_obj[select_obj.selectedIndex].value ;
}


function suggest_manufacturer() {
	$('#suggest-manufacturer .suggest-container').html( '<i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>' );
	
	$.ajax({
		url 	: 'ajax.php',
		dataType: 'json',
		type 	: 'get',
		data 	: 'what=get_manufacturer&val='+escape( $('#manufacturer').val() ),
		success: function(data){
			var html;
			html = '<ul>';
			for (var i in data['response'].manufacturers)
				html += '<li>'+data['response'].manufacturers[i]+'</li>';
				
			html += '</ul>';
			$('#suggest-manufacturer .suggest-container').html(html);

			// calculate box height
			var box_top = parseInt($('#suggest-manufacturer').css('top'));
			var window_height 	= window.innerHeight;
			var box_height = window_height - box_top - 10;
			$('#suggest-manufacturer').css('height', box_height + 'px');

			// display the box
			$('#suggest-manufacturer').slideDown('fast');		
		}
	});
} // fin function