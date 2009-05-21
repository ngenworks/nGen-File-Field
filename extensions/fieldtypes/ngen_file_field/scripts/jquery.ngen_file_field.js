/* ---------------------------------------------------------------------------
	
	FieldFrame File Field Javascript (jQuery)
	
	Author: Fred Boyle @ nGen Works
	http://ngenworks.com
	
--------------------------------------------------------------------------- */

(function($) {

	$('form#entryform[enctype!=multipart/form-data]').attr('enctype', 'multipart/form-data');

	$('.ngen-file-delete-button').livequery('click', function() {
		showme = $(this).parent().children(".ngen-ff-choice");
		$(".ngen-ff-choice").not(showme).hide();
		showme.toggle();
		
		return false;
	});
	
	$('.ngen-ff-choice-remove a').livequery('click', function() {
		$(this).parents(".ngen-file-field-data").next().children("input[name*=delete]").val("");
		$(this).parents(".ngen-file-field-data").next().children("input[name*=file_name]").val("");
		$(this).parents(".ngen-file-field-data").next().show();
		$(this).parents(".ngen-file-field-data").remove();
	
		return false;
	});
	
	$('.ngen-ff-choice-delete a').livequery('click', function() {
		$(this).parents(".ngen-file-field-data").next().children("input[name*=delete]").val( $(this).parents(".ngen-file-field-data").next().children("input[name*=delete]").prev().val() );
		$(this).parents(".ngen-file-field-data").next().children("input[name*=file_name]").val("");
		$(this).parents(".ngen-file-field-data").next().show();
		$(this).parents(".ngen-file-field-data").remove();
		
		return false;
	});
	
	$('.ngen-ff-choice-cancel').livequery('click', function() {
		$(this).parent().hide();
	
		return false;
	});
	
	$('.ngen-file-choose-existing a').livequery('click', function() {
		if( $(this).parent().prevAll("input[type=file]").is(":visible") ) {
			$(this).parent().prevAll("input[type=file]").hide();
			$(this).parent().prev().show();
			
			$(this).text('cancel');
			
		} else {
			$(this).parent().prevAll("input[type=file]").show();
			$(this).parent().prev().hide();
			$(this).parent().prev().attr('selectedIndex', 0);
			
			$(this).text('use an existing file');
		}
		
		return false;
	});
	
	nGenFile = {};
	nGenFile.lang = {};
	
})(jQuery);