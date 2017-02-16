var timer;

// when everything is ready
$(document).ready(function() {
	// click on media and display lightbox
	if ($('#snapshot a').length)
		$('#snapshot a').lightBox({fixedNavigation:true});

	// submit search
	$('#submit-search, #results td a').on('click', function(e){
		// display loading box
		$('#loading').css('display','block');
		document.rom_search.submit();
	});

	$('#rom_search').submit(function( event ) {
		// display loading box
		$('#loading').css('display','block');
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

	// more options in search bar
	$('#search-options').click(function(){
		var show_options = $('#search-clone').css('display') == 'none' ? 1 : 0;

		if (show_options)
			$('#search-clone, #search-manufacturer, #search-year, #search-order, #search-limit').css('display','inline-block');
		else
			$('#search-clone, #search-manufacturer, #search-year, #search-order, #search-limit').css('display','none');
	});

	// click on manufacturer
	$('body').delegate('#suggest-manufacturer .suggest-container li','click',function(e){
		$('#manufacturer').val( $(this).text() );
		$('#suggest-manufacturer').slideUp('fast');
	});


	// click on tablea result header
	$('#results th.name, #results th.description, #results th.year, #results th.console, #results th.manufacturer').click(function(){
		var current_order = $('#order_by').val();
		var current_reverse = $('#reverse_order').is(':checked');
		var new_order = $(this).attr('class');

		if ($(this).attr('class') == current_order) { // keep same order but reverse it
			$('#reverse_order').prop( 'checked', !current_reverse );

		} else { // change order and reset order to ASC
			$('#order_by').val(new_order);
			$('#reverse_order').prop( 'checked', false ); // reset reverse checkbox
		}

		// send new search
		$('#submit-search').click();
	});


	// adaptive table on mobile
	if ($('#display_info table').length)
		$('#display_info table').stacktable();

	if ($('#input_info table').length)
		$('#input_info table').stacktable();

	if ($('#control_info table').length)
		$('#control_info table').stacktable();

	if ($('#rom_info table').length)
		$('#rom_info table').stacktable();

	if ($('#chip_info table').length)
		$('#chip_info table').stacktable();

	if ($('#adjuster_info table').length)
		$('#adjuster_info table').stacktable();

	if ($('#biosset_list table').length)
		$('#biosset_list table').stacktable();

	if ($('#sample_info table').length)
		$('#sample_info table').stacktable();

	if ($('#disk_info table').length)
		$('#disk_info table').stacktable();

}); // end on ready


// change URL to direclty go to a game
function goToGame(game,console) {
	document.location.href='index.php?name='+escape(game)+'&console='+escape(console);
}

// change result page
function change_page(select_obj) {
	document.location.href='?pageno=' + select_obj[select_obj.selectedIndex].value ;
}


// display the manufacturer list starting with the kayboard entry
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
} // end function suggest_manufacturer



// ask via AJAX if a video exists on this game and display a link to view it
function look_for_video(game_name) {
	$.ajax({
		url 	: 'ajax.php',
		dataType: 'json',
		type 	: 'get',
		data 	: 'what=get_video&game='+escape( game_name ),
		success: function(data){
			
			if (data['response'].video_found) { // found a video --> append a media to the media list
				// keep video html for later use in hidden place
				$('#video').html( data['response'].video_html );

				$('#media-list').append( // display a link
					'<li onclick="show_video()">Video</li>'
				);
			}

		}
	});
} // end function look_for_video