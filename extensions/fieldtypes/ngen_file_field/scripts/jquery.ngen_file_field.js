/* ---------------------------------------------------------------------------
	
	FieldFrame File Field Javascript (jQuery)
	
	Author: Fred Boyle @ nGen Works
	http://ngenworks.com
	
--------------------------------------------------------------------------- */

(function($) {

	$('form#entryform[enctype!=multipart/form-data]').attr('encoding', 'multipart/form-data'); // IE fix
	$('form#entryform[enctype!=multipart/form-data]').attr('enctype', 'multipart/form-data');
	
	$('.ngen-file-input').parents("form").submit( function() {
		
		//disable empty file fields to avoid exceeding max_file_uploads limit
		//alert("number of file fields empty: " + $('.ngen-file-input:not([value!=""])').size());
		$('.ngen-file-input:not([value!=""])').attr("disabled","disabled");
		
		//alert( $('.ngen-file-input').val() );
		$('.ngen-file-input[value!=""]').hide();
		$('.ngen-file-input[value!=""]').nextAll('.ngen-file-choose-existing').hide();
		$('.ngen-file-input[value!=""]').before("<div class='ngen-file-loader'>" + nGenFile.lang.uploading + "</div>");
		
	});

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
			
			$(this).text(nGenFile.lang.use_existing_cancel);
			
			if( $(this).parent().prev().children(".ngen-file-existing-preview").length > 0 ) {
				field_block = $(this).parents(".ngen-file-field-block");
				select_width = $(this).parent().prev().children('select').width();
				
				if( field_block.width() < (select_width + 102) ) {
					field_block.width( select_width + 102 );
				}
				
				field_block.css('padding-bottom', '26px');
			}
			
		} else {
			$(this).parent().prevAll("input[type=file]").show();
			$(this).parent().prev().hide();
			$(this).parent().prev().attr('selectedIndex', 0);
			
			$(this).parents(".ngen-file-field-block").width('auto');
			$(this).parents(".ngen-file-field-block").css('padding-bottom', '0');
			
			$(this).parent().html(nGenFile.lang.use_existing);
		}
		
		return false;
	});
	
	$('.ngen-file-existing select').livequery('change', function() {
		if( /(\.jpg|\.jpeg|\.gif|\.png|\.bmp)$/i.test($(this).val()) ) {
			fieldName = $(this).parent().prevAll('input[type=file]').attr('name');
			fieldName_array = /(.*?)(\[.+\]\[.+\])?$/.exec(fieldName);
			fieldName = fieldName_array[1];
		
			rArray = /(.*)(\.jpg|\.jpeg|\.gif|\.png|\.bmp)$/i.exec($(this).val());
			filename = rArray[1] + "_thumb" + rArray[2];
		
			$(this).next(".ngen-file-existing-preview").remove();
			
			field_block = $(this).parents(".ngen-file-field-block");
			field_block.width( $(this).width() + 102 );
			field_block.css('padding-bottom', '26px');
			
			$(this).after("<div class='ngen-file-existing-preview'><img src='" + nGenFile.thumbpaths[fieldName] + "thumbs/" + filename + "' /></div>");
		} else {
			$(this).next(".ngen-file-existing-preview").remove();
			$(this).parents(".ngen-file-field-block").width('auto');
			$(this).parents(".ngen-file-field-block").css('padding-bottom', '0');
		}
	});
	
	nGenFile = {};
	nGenFile.lang = {};
	nGenFile.thumbpaths = {};
	
})(jQuery);