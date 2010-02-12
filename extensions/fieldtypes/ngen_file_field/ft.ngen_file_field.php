<?php

error_reporting(1);
ini_set('display_errors', '0');

//error_reporting(E_ALL);
//ini_set('display_errors', '1');

if ( ! defined('EXT')) exit('Invalid file request');

//
// In case exif is not enabled for this PHP installation
//
if ( ! function_exists( 'exif_imagetype' ) ) {
	function exif_imagetype ( $filename ) {
	  if ( ( list($width, $height, $type, $attr) = getimagesize( $filename ) ) !== false ) {
		  return $type;
	  }
	return false;
	}
}
//

/**
 * File Field Class
 *
 * @package   FieldFrame
 * @author    Fred Boyle @ nGen Works
 */
class Ngen_file_field extends Fieldframe_Fieldtype {

	/**
	 * Fieldtype Info
	 * @var array
	 */
	var $info = array(
		'name'     => 'nGen File Field',
		'version'  => '1.0',
		'desc'     => 'Provides a file fieldtype',
		'docs_url' => 'http://www.ngenworks.com/software/ee/',
		'versions_xml_url' => 'http://ngenworks.com/software/version-check/versions.xml'
	);
	
	var $hooks = array(
		// Field Manager
		'show_full_control_panel_end',
		'weblog_standalone_insert_entry'
	);

	var $default_field_settings = array(
		'options' => ''
	);

	var $default_cell_settings = array(
		'options' => ''
	);
	
	//
	var $postpone_saves = FALSE;
	
	/**
	 * class constructor
	 */
	function __construct()
	{
		global $FF, $PREFS;
		// Set db_prefix
		$this->db_prefix = $PREFS->ini('db_prefix');
	}

	/**
	 * Display - Show Full Control Panel - End
	 *
	 * - Rewrite CP's HTML
	 * - Find/Replace stuff, etc.
	 *
	 * @param  string  $end  The content of the admin page to be outputted
	 * @return string  The modified $out
	 * @see    http://expressionengine.com/developers/extension_hooks/show_full_control_panel_end/
	 */
	function show_full_control_panel_end($out) {
		global $SESS, $DSP;
		@session_start();
	
		$out = $this->get_last_call($out);
		
		//
		// Display any errors		
		if( !empty($_SESSION['ngen']['ngen-file-errors']) ) {
			$error_output = "<div class='ngen-file-errors'><ul>\n";
			foreach($_SESSION['ngen']['ngen-file-errors'] as $key => $error) {
				$error_output .= "<li";
				
				end($_SESSION['ngen']['ngen-file-errors']);
				
				if( $key == key($_SESSION['ngen']['ngen-file-errors']) ) {
					$error_output .= " class='last'";
				}
				$error_output .= ">" . $error . "</li>\n";
			}
			$error_output .= "</div>\n";
			
			if( preg_match("/\<div id=('|\")?contentNB('|\")?\>/", $out, $error_matches) ) {
				$out = str_replace($error_matches[0], $error_matches[0] . $error_output, $out);
			}
		}
		//
		
		//
		// Display any messages	
		if( !empty($_SESSION['ngen']['ngen-file-messages']) ) {
			$msg_output = "<div class='ngen-file-messages'><ul>\n";
			foreach($_SESSION['ngen']['ngen-file-messages'] as $error) {
				$msg_output .= "<li>" . $error . "</li>\n";
			}
			$msg_output .= "</div>\n";
			
			if( preg_match("/\<div id=('|\")?contentNB('|\")?\>/", $out, $msg_matches) ) {
				$out = str_replace($msg_matches[0], $msg_matches[0] . $msg_output, $out);
			}
		}
		//
								
		//
		// Make Publish Table multipart/form-data
		if(preg_match("/name=.entryform./", $out, $matches))
		{
			// Check to see if we already have the multipart stuff in the tag, add it if we don't
			if(! preg_match("/enctype=\"multipart\/form-data\"/", $matches[0]) ) {
				$out = str_replace($matches[0], $matches[0] . " enctype=\"multipart/form-data\"", $out);
			}
		}
		//
		
		if (isset($_SESSION) AND isset($_SESSION['ngen']))
		{
			if (isset($_SESSION['ngen']['ngen-file-errors'])) unset($_SESSION['ngen']['ngen-file-errors']);
			if (isset($_SESSION['ngen']['ngen-file-messages'])) unset($_SESSION['ngen']['ngen-file-messages']);
		}
		
		
		//exit('dead');

		return $out;
	}
	//
	//
	//
	
	/**
	 * Display Field Settings
	 * 
	 * @param  array  $field_settings  The field's settings
	 * @return array  Settings HTML (cell1, cell2, rows)
	 */
	function display_field_settings($field_settings)
	{
		global $DSP, $LANG;
		
		// Existing file show/hide option
		$hide_existing_selected = '';
		if(isset($field_settings['hide_existing']) && $field_settings['hide_existing'] == 'y') { $hide_existing_selected = 1; }
		       
		// Upload location select 
		$cell2 = $DSP->qdiv('defaultBold', $LANG->line('file_options_label'))
		       . $this->_select_upload_locations($field_settings['options'])
					 . $DSP->qdiv('defaultBold', $LANG->line('file_hide_existing_label'))
					 . $DSP->input_checkbox('hide_existing', 'y', $hide_existing_selected);
					 
		$cell2 = $DSP->qdiv('rel_block', $cell2);

		return array('cell2' => $cell2);
	}
	//

	/**
	 * Display Cell Settings
	 * 
	 * @param  array  $cell_settings  The cell's settings
	 * @return string  Settings HTML
	 */
	function display_cell_settings($cell_settings)
	{
		global $DSP, $LANG;
		
		// Existing file show/hide option
		$hide_existing_selected = '';
		if(isset($cell_settings['hide_existing']) && $cell_settings['hide_existing'] == 'y') { $hide_existing_selected = 1; }
		
		$r = '<label class="itemWrapper">'
		   . $DSP->qdiv('defaultBold', $LANG->line('file_options_label'))
		   . $this->_select_upload_locations($cell_settings['options'])
		   . '</label>'
			 . '<label class="itemWrapper">'
 			 . $DSP->qdiv('defaultBold', $LANG->line('file_hide_existing_label'))
			 . $DSP->input_checkbox('hide_existing', 'y', $hide_existing_selected)
			 . '</label>';

		return $r;
	}	
	//

	/**
	 * Display Field
	 * 
	 * @param  mixed  $field_name
	 * @param  mixed  $field_data
	 * @param  array  $field_settings  The field's settings
	 * @return string  Field HTML
	 */
	function display_field($field_name, $field_data, $field_settings)
	{
		global $IN, $LANG, $PREFS;
		
		$LANG->fetch_language_file('ngen_file_field');
	
		$this->include_css('styles/ngen_file_field.css');
		$this->include_js('scripts/jquery.livequery.js');
		$this->include_js('scripts/jquery.ngen_file_field.js');
		
		$del_field_name = $field_name . "[delete]";
		$existing_field_name = $field_name . "[existing]";
		
		//
		$this->field_settings = $field_settings;
		
		$hide_choose_existing = @$field_settings['hide_existing'];
		
		// Check if field_data is an array or not
		if( !is_array($field_data) ) {
			$file_name = $field_data;
		} else {
			$file_name = array_key_exists('file_name', $field_data) ? $field_data['file_name'] : '';
		}
		
		//
		// Make sure we're not on the edit field screen
		// this helps us avoid issues when trying to retrieve upload settings and file lists
		//
		$this->edit_field = false;
		
		if($IN->GBL('M', 'GET') == 'blog_admin' AND $IN->GBL('P', 'GET') == 'edit_field') {
			$this->edit_field = true;
		}
		//
		
		//
		if(!$this->edit_field) {
			$this->_get_upload_prefs($field_settings['options']);
			
			$file_path = $this->upload_prefs['server_path'] . $file_name;
			$file_uri = $this->upload_prefs['server_uri'] . $file_name;
			$file_url = $this->upload_prefs['server_url'] . $file_name;
		}
		//
			
		$file_field = "<div class='ngen-file-field-block'>";
 	
		if( isset($file_name) AND $file_name ) {
			
			$file_field .= "<div class='ngen-file-field-data'>";
			
			$file_field .= "<a href='$file_url' target='_blank' class='ngen-file-link'>";
			
			$file_kind_text = $LANG->line('file_kind');
			
			// Is file an image?
			if($this->_is_image($file_path)) {
				$file_kind_text = $LANG->line('image_kind');
			
				// if thumbnail doesn't exist, create it.
				$img_info = $this->_image_info($file_path);
				
				$thumbnail = $this->upload_prefs['server_url'] . $img_info['thumbnail'];
				
				// Legacy for existing files
				if(!file_exists($this->upload_prefs['server_path'] . $img_info['thumbnail'])) {
					$thumbnail = $this->upload_prefs['server_url'] . $this->_create_thumbnail($file_path);
				} 
				
				$file_field .= "<img src='$thumbnail' class='ngen-file-thumbnail' alt='" . $file_name . "' />\n";
			}
			
			$file_field .= $file_name . "</a>\n";
			$file_field .= "<div class='ngen-ff-delete'>\n";
			$file_field .= "<a href='#' class='ngen-file-delete-button'>" . str_replace('%{file_name}', $file_name, $LANG->line('delete_file_link')) . "</a>\n";
			
			// Confirm whether to change, delete, or cancel
			$file_field .= "<div class='ngen-ff-choice' style='display: none;'>\n";
			$file_field .= "<ul>\n";
			// Hide remove option
			if(!$hide_choose_existing) {
				$file_field .= "<li class='ngen-ff-choice-remove'><a href='#'>" . str_replace('%{file_kind}', $file_kind_text, $LANG->line('choice_remove')) . "</li>";
			}
			//
			$file_field .= "<li class='ngen-ff-choice-delete'><a href='#'>" . $LANG->line('choice_delete') . "</li>";
			$file_field .= "</ul>\n";
			$file_field .= "<div class='ngen-ff-choice-bottom'></div>\n";
			$file_field .= "<a href='#' class='ngen-ff-choice-cancel'>Cancel</a>\n";
			$file_field .= "</div> <!-- close .ngen-ff-choice -->\n";
			//
			
			$file_field .= "</div> <!-- close .ngen-ff-delete -->\n";
			
			$file_field .= "</div>\n";
			
			$file_field .= "<div class='ngen-file-field-new' style='display: none;'>\n";
			$file_field .= "<input type='file' name='$field_name' class='ngen-file-input' />\n";
			$file_field .= "<input type='hidden' name='" . $field_name . "[file_name]' value='" . $file_name  . "' />\n";
			$file_field .= "<input type='hidden' name='$del_field_name' />\n";
			
			// Existing file select
			if(!$hide_choose_existing) {
				$file_field .= "<div class='ngen-file-existing' style='display: none;'>\n";
				$file_field .= $this->_get_existing_select($existing_field_name);
				$file_field .= "</div>\n";
			
				$file_field .= "<div class='ngen-file-choose-existing'>" . $LANG->line('use_existing') . "</div>\n";
			}
			//
			
			$file_field .= "</div>\n";
			
		} else {
			// Empty field
			$file_field .= "<input type='file' name='$field_name' class='ngen-file-input' />\n";
			$file_field .= "<input type='hidden' name='" . $field_name . "[file_name]' />\n";
			
			// Existing file select
			if(!$hide_choose_existing) {
				$file_field .= "<div class='ngen-file-existing' style='display: none;'>\n";
				$file_field .= $this->_get_existing_select($existing_field_name);
				$file_field .= "</div>\n";
			
				$file_field .= "<div class='ngen-file-choose-existing'>" . $LANG->line('use_existing') . "</div>\n";
			}
			//
		}
		
		$file_field .= "</div>";
		
		//$js = 'nGenFile.lang.confirmDeleteFile = "'.$LANG->line('confirm_delete_file').'";';
		//$js .= 'nGenFile.lang.confirmRemoveFile = "'.$LANG->line('confirm_remove_file').'";';
		
		preg_match("~(.*?)(\[.+\]\[.+\])?$~", $field_name, $fn_matches);
		$field_name_js = $fn_matches[1];
		
		$js = '';
		$js .= 'nGenFile.lang.use_existing = "'.$LANG->line('use_existing').'";';
		$js .= 'nGenFile.lang.use_existing_cancel = "'.$LANG->line('use_existing_cancel').'";';
		$js .= 'nGenFile.lang.uploading = "'.$LANG->line('uploading').'";';
		$js .= 'nGenFile.thumbpaths["' . $field_name_js . '"] = "' . @$this->upload_prefs['server_url'] . '";';
		
		$this->insert_js($js);
		
		return $file_field;
	}
	//
	
	/**
	 * Display Cell
	 * 
	 * @param  mixed  $cell_name
	 * @param  array  $cell_data
	 * @param  array  $cell_settings  The cell's settings
	 * @return string  Cell HTML
	 */
	function display_cell($cell_name, $cell_data, $cell_settings)
	{
		return $this->display_field($cell_name, $cell_data, $cell_settings);
	}
	//
	
	//
	/**
	 * Save Field
	 * 
	 * @param  mixed  $field_data      The field's current value
	 * @param  array  $field_settings  The field's settings
	 * @return mixed	Modified or same $field_data
	 */
	function save_field($field_data, $field_settings)
	{
		global $FF, $IN, $SESS;
		
		@session_start();
		
		$field_name = $FF->field_name;
		
		// If delete field has value delete the file + thumbnail
		if(is_array($field_data) && isset($field_data['delete']) && $field_data['delete'] != '')
		{
			$this->_get_upload_prefs($field_settings['options']);
			
			if($this->_is_image($this->upload_prefs['server_path'] . $field_data['delete'])) {
				$image_info = $this->_image_info($this->upload_prefs['server_path'] . $field_data['delete']);
				@unlink($this->upload_prefs['server_path'] . $image_info['thumbnail']);
			}
				
			unlink($this->upload_prefs['server_path'] . $field_data['delete']);
			//$_SESSION['ngen']['ff-file-messages'][] = "File <em>" . $_POST[$field_name . "_delete"] . "</em> deleted.";

			// Remove delete variables to avoid saving issues
			unset($field_data['delete']);
		}
		
		//if(empty($field_data['file_name']) && ( $_FILES[$field_name]['name'] != "" || $field_data['existing'] != "" ) ) {
			
		// update by Brandon Kelly for SAEF compatibility
		if(is_array($field_data) && empty($field_data['file_name']) && ( ( isset($_FILES[$field_name]) && $_FILES[$field_name]['name'] ) || (isset($field_data['existing']) && $field_data['existing']) ) ) {
		
			//unset($field_data['file_name']);
			$existing_file = (isset($field_data['existing'])) ? $field_data['existing'] : NULL;
			unset($field_data);
			unset($_POST[$field_name]);

			if($existing_file) {
				// If using an existing file
				$file_name = $existing_file;
			} else {
				// If uploading a new file
				$file_info['name'] = $_FILES[$field_name]['name'];
				$file_info['type'] = $_FILES[$field_name]['type'];
				$file_info['tmp_name'] = $_FILES[$field_name]['tmp_name'];
				$file_info['error'] = $_FILES[$field_name]['error'];
				$file_info['size'] = $_FILES[$field_name]['size'];
				
				$file_name = $this->upload_file($file_info, $field_settings['options']);
			}
			
			$return = $file_name;
			
		} else {
			// Unset to make sure field is actually empty if no data is stored
			if(!$field_data['file_name']) {
				//unset($field_data['file_name']);
				unset($field_data);
				unset($_POST[$field_name]);
				
				$return = NULL;
				
			} else {		
				$return = (is_array($field_data)) ? $field_data['file_name'] : $field_data;
			}
			
		}
	
		// IF SAEF - show errors 
		//
		if($IN->GBL('C', 'GET') == '') {
			return $this->_display_errors_SAEF();
		} else {
			return $return;
		}
		//
		
		//return $return;
	}
	//
	
	//
	/**
	 * Save Cell
	 * 
	 * @param  mixed  $cell_data      The cell's current value
	 * @param  array  $cell_settings  The cell's settings
	 * @return mixed	$cell_data
	 */
	function save_cell($cell_data, $cell_settings)
	{
		global $FF, $FFM, $IN, $SESS;
		
		@session_start();
			
		$field_name = $FF->field_name;
		$row_count = $FFM->row_count;
		$col_id = $FFM->col_id;
		
		//print_r($cell_data);
		//print_r($_FILES);
		
		// If delete field has value delete the file + thumbnail
		if (isset($cell_data['delete']))
		{
			if($cell_data['delete'])
			{
				$this->_get_upload_prefs($cell_settings['options']);
				
				$file_info = pathinfo($cell_data['delete']);
			
				if($this->_is_image($this->upload_prefs['server_path'] . $cell_data['delete'])) {
					$image_info = $this->_image_info($this->upload_prefs['server_path'] . $cell_data['delete']);
					@unlink($this->upload_prefs['server_path'] . $image_info['thumbnail']);
				}
				
				unlink($this->upload_prefs['server_path'] . $cell_data['delete']);
				//$_SESSION['ngen']['ff-file-messages'][] = "File <em>" . $_POST[$field_name . "_delete"][$row_count][$col_id] . "</em> deleted.";
			}

			// Remove delete variables to avoid saving issues
			unset($cell_data['delete']);
		}
	
		//if(empty($cell_data['file_name']) && ($_FILES[$field_name]['name'][$row_count][$col_id] != "" || $cell_data['existing'] != "") ) {

		// update by Brandon Kelly for SAEF compatibility
		//if(empty($cell_data['file_name']) && ( ( isset($_FILES[$field_name]) && $_FILES[$field_name]['name'][$row_count][$col_id] ) || (isset($cell_data['existing']) && !empty($cell_data['existing']) ) ) ) {
		
		if(
			$cell_data['file_name'] == '' &&
			(
				( isset($_FILES[$field_name]) && $_FILES[$field_name]['name'][$row_count][$col_id] != '' ) ||
				( $cell_data['existing'] != '' )
			)
		) {
		
			$existing_file = (isset($cell_data['existing']) && !empty($cell_data['existing']) ) ? $cell_data['existing'] : FALSE;
			
			if($existing_file) {
				// If using existing file
				$file_name = $cell_data['existing'];
			} else {
			
				// If uploading new file
				$file_info = array();
				$file_info['name'] = $_FILES[$field_name]['name'][$row_count][$col_id];
				$file_info['type'] = $_FILES[$field_name]['type'][$row_count][$col_id];
				$file_info['tmp_name'] = $_FILES[$field_name]['tmp_name'][$row_count][$col_id];
				$file_info['error'] = $_FILES[$field_name]['error'][$row_count][$col_id];
				$file_info['size'] = $_FILES[$field_name]['size'][$row_count][$col_id];
					
				$file_name = $this->upload_file($file_info, $cell_settings['options']);
			}
			
			$return = $file_name;	
		
		} else {
		
			// Unset to make sure cell is actually empty if no data is stored
			if(!$cell_data['file_name']) {
				//unset($cell_data['file_name']);
				unset($_POST[$field_name . "_" . $row_count . "_" . $col_id]);
				
				$return = NULL;
			} else {
				$return = $cell_data['file_name'];
			}
		}
		//
		
		// IF SAEF - show errors 
		//
		if($IN->GBL('C', 'GET') == '') {
			return $this->_display_errors_SAEF();
		} else {
			return $return;
		}
		//
		
		//return $return;
	}
	//
	
	//
	/**
	 * Upload File
	 * 
	 * @param  array  $upload_info    array built from instance's $_FILES data
	 * @param  array  $settings  			The cell/field's settings
	 * @return mixed	$file_name 			or false if an error
	 */
	function upload_file($upload_info, $settings) {
		global $LANG, $SESS, $IN, $DSP, $FNS, $EE;
		@session_start();
		
		$LANG->fetch_language_file('ngen_file_field');
		
		// Build File Pieces
		$file_name = $upload_info['name'];
		$file_type = $upload_info['type'];
		$file_tmp_name = $upload_info['tmp_name'];
		$file_error = $upload_info['error'];
		$file_size = $upload_info['size'];
				
		if($file_name) {
				
			// Clean file name
			$file = $this->_pieces($file_name);
			$file_name = preg_replace(array('/(?:^[^a-zA-Z0-9_-]+|[^a-zA-Z0-9_-]+$)/', '/\s+/', '/[^a-zA-Z0-9_-]+/'), array('', '_', ''), $file['name']) . $file['ext'];
			$file = $this->_pieces($file_name);
			//
			
			//
			$this->_get_upload_prefs($settings);
			
			$upload_path = $this->upload_prefs['server_path'];
			$max_file_size = $this->upload_prefs['max_file_size'];
			$allowed_types = $this->upload_prefs['allowed_types'];
			
			// Are any of the file sizes too big?
			if($max_file_size != '')
			{
				if($file_size > $max_file_size)
				{
					$file_size_error = str_replace(array('%{file_name}', '%{max_size}'), array($file_name, $this->_size_readable($max_file_size, 'GB')), $LANG->line('error_file_size'));
				
					$_SESSION['ngen']['ngen-file-errors'][] = $file_size_error;
					
					$this->_error_message();
					
					return false;
				}
			}
			//
			
			// Is file of valid type? Applies only if limited to image type
			$is_image = exif_imagetype($file_tmp_name);
			
			if($allowed_types == 'img' && $is_image == false) {
				$_SESSION['ngen']['ngen-file-errors'][] = str_replace('%{file_name}', $file_name, $LANG->line('error_file_not_image'));
				
				$this->_error_message();
				
				return false;
			}
			//
			
			//
			// Check if file exists already, if it does add an increment to the file name
			if( file_exists($upload_path . $file_name) ) {
				$matching_files = glob($upload_path . $file['name'] . "*" . $file['ext']);
	
				// Find highest number, add 1 and set new $file_name
				natsort($matching_files);
				preg_match("/" . $file['name'] . "_(\d+)\." . substr($file['ext'], 1) . "/", basename(end($matching_files)), $matches);
				
				if( isset($matches[1]) && $matches[1] ) {
					$increment = "_" . ($matches[1] + 1);
				} else {
					$increment = "_1";
				}
				
				$file_name = $file['name'] . $increment . $file['ext'];
				//
			}
			//
			
			//die("Uploading to: $upload_path");
			
			// Do the Upload
			if(@move_uploaded_file($file_tmp_name, $upload_path . $file_name) === FALSE)
			{
				$_SESSION['ngen']['ngen-file-errors'][] = str_replace('%{file_name}', $file_name, $LANG->line('error_file_upload'));
				
				$this->_error_message();
				
				return false;
			} else {
				chmod($upload_path . $file_name, 0777);
				if( $this->_is_image($upload_path . $file_name) ) {
					$this->_create_thumbnail($upload_path . $file_name);
				}
				//$_SESSION['ngen']['ff-file-messages'][] = "File <em>$file_name</em> was successfully uploaded!";
			}
			//
			
			return $file_name;
		}
		
		return NULL;
	}
	//
	
	//
	/**
	 * Display Tag
	 *
	 * @param  array   $params          Name/value pairs from the opening tag
	 * @param  string  $tagdata         Chunk of tagdata between field tag pairs
	 * @param  string  $field_data      Currently saved field value
	 * @param  array   $field_settings  The field's settings
	 * @return string  relationship references
	 */
	function display_tag($params, $tagdata, $field_data, $field_settings)
	{
		global $TMPL;
	
		$r = '';
		
		if($field_settings['options'] && $field_data) {
			
			// Added by Fred Boyle - 2009.04.21
			// Check if field_data is an array or not
			if( !is_array($field_data) ) {
				$file_name = $field_data;
			} else {
				//$file_name = array_key_exists('file_name', $field_data) ? $field_data['file_name'] : '';
				$file_name = ( isset($field_data['file_name']) ) ? $field_data['file_name'] : '';
			}
			
			// If show param is set to filename, only show file name
			if(isset($params['show']) && strtolower($params['show']) == 'filename') {
				$r = $file_name;
				return $r;
			}
			
			if( !empty($file_name) ) {
				$this->_get_upload_prefs($field_settings['options']);
				
				// If full_url param is set to yes, return full URL w/ hostname etc
				if(isset($params['full_url']) && strtolower($params['full_url']) == 'yes') {
					$file_url = $this->upload_prefs['server_url'] . $file_name;
					$r = $file_url;
				} else {
					//$full_file_path = $upload_prefs['server_path'] . $file_name;
					$file_uri = $this->upload_prefs['server_uri'] . $file_name;
					$r = $file_uri;
				}
					
			}
		}
		
		return trim($r);
	}
	//
	
	/**
	 * Split a file to name/suffix
	 *
	 * @access private
	 * @param string, string
	 * @return string
	 */
	function _pieces($file_name)
	{
		$base = preg_replace('/^(.*)\.[^.]+$/', '\\1', $file_name);
		$ext = preg_replace('/^.*(\.[^.]+$)/', '\\1', $file_name);
		
		return array('name'=>$base, 'ext'=>$ext);
	}
	//
	
	//
	// Builds the select for settings
	// returns HTML for select
	//
	function _select_upload_locations($current_option)
	{
		global $DB, $PREFS;
		
		// Add custom drop down for file location
		$block = "<div class='itemWrapper'><select name=\"options\"><option value=\"\"></option>";
		
		$dls = $DB->query("SELECT id, name FROM " . $this->db_prefix . "_upload_prefs WHERE site_id = " . $PREFS->ini('site_id') . " ORDER BY name ASC");
		foreach($dls->result as $dl)
		{
			$selected = ($dl['id'] == $current_option) ? " selected=\"true\"" : "";
			$block .= "<option value=\"{$dl['id']}\"$selected>{$dl['name']}</option>";
		}
		
		$block .= "</select></div></div>";
		
		return $block;
	}
	//
	
	//
	// Retrieves the upload preferences for an upload location
	// returns upload_prefs array
	//
	function _get_upload_prefs($u_id) {
		
		if( !isset($this->upload_prefs['loc_id']) || $this->upload_prefs['loc_id'] != $u_id ) {
			global $DB, $FNS, $PREFS;
			
			$query = $DB->query("SELECT * FROM " . $this->db_prefix . "_upload_prefs WHERE id = $u_id");
			
			$this->upload_prefs['loc_id'] = $u_id;
			$this->upload_prefs['server_path'] = trim($query->row['server_path']); // trim it just to be safe
			
			// Is this a relative path?
			// - check if path starts with ..
			//
			if( substr($this->upload_prefs['server_path'], 0, 2) == '..' ) {
				// found relative path, turn it into a proper absolute one
				// Use the PATH constant since it points to the CP path
				//$this->upload_prefs['server_path'] = PATH . $this->upload_prefs['server_path'];
				$this->upload_prefs['server_path'] = $FNS->remove_double_slashes($_SERVER['DOCUMENT_ROOT'] . substr($this->upload_prefs['server_path'], 2));
			}
			
			//$this->upload_prefs['server_uri'] = parse_url($query->row['url'], PHP_URL_PATH); // req. PHP 5.1.2
			
			// for PHP 4/5+
			$url_bits = parse_url($query->row['url']);
			$this->upload_prefs['server_uri'] = $url_bits['path'];
			
			//$upload_prefs['server_url'] = $FNS->remove_double_slashes( $PREFS->ini('site_url') . $upload_prefs['server_uri'] );
			$this->upload_prefs['server_url'] = $query->row['url'];
			
			// If the server URL is relative instead of a full URL then build a complete URL w/ hostname etc.
			if( strtolower(substr($this->upload_prefs['server_url'], 0, 5)) != 'http:' ) {
				$this->upload_prefs['server_url'] = $FNS->remove_double_slashes( $PREFS->ini('site_url') . $this->upload_prefs['server_url'] );
			}
			
			$this->upload_prefs['allowed_types'] = $query->row['allowed_types'];
			$this->upload_prefs['max_file_size'] = $query->row['max_size'];
		}
			
		//return $upload_prefs;
	}
	//
	
	//
	// Build or retrieve existing file list select/drop-down
	// returns HTML
	//
	function _get_existing_select($field_name) {
		global $LANG, $SESS;
		
		//preg_match("~(.*?)\[.*~", $field_name, $field_matches);
		//$field_id = $field_matches[1];
		
		$loc_id = $this->upload_prefs['loc_id'];
		
		// If the existing file drop down already exists in the session use it, otherwise generate it
		if( isset($_SESSION['ngen']['ngen-file-existing'][$loc_id]) ) {
			
			// Make sure select has the proper field name
			$existing_html = "<select name='$field_name'>\n";
			$existing_html .= $_SESSION['ngen']['ngen-file-existing'][$loc_id];
			//$existing_html .= "<!-- from cache -->\n";
			
		} else {
			
			$existing_html = "<option value=''>" . $LANG->line('option_choose_existing') . "</option>\n";
			
			// No list to fetch if editing custom field
			if(!$this->edit_field) {		
				$existing_html .= $this->_get_file_list($this->upload_prefs['server_path'], true);
			}
			
			$existing_html .= "</select>\n";
			
			$_SESSION['ngen']['ngen-file-existing'][$loc_id] = $existing_html;
			
			// Make sure select has the proper field name
			$existing_html = "<select name='$field_name'>\n" . $existing_html;
			//$existing_html .= "<!-- NOT from cache -->\n";
			
		}
		
		return $existing_html;
	}
	//
	
	//
	// Retrieve list of files in folder
	// option to return as array or options for a select field
	//
	function _get_file_list($path, $as_options = false) {
		global $LANG;
		
		$LANG->fetch_language_file('ngen_file_field');
		
		//echo "Path: $path<br/>\n";
	
		$output = '';
		$file_list = array();
		
		// if path doesn't exist, create it
		if(!file_exists($path)) {  
			$create_dir = mkdir($path, 0777);
			chmod($path, 0777);
			
			// Possible error fix to be tested, testing for failure of directory creation 
			if(!$create_dir) {
			
				$error_line = str_replace(array('%{upload_path}', '%{upload_edit_link}'), array($path, BASE . "&C=admin&M=blog_admin&P=edit_upload_pref&id=" . $this->field_settings['options']), $LANG->line('error_file_path'));
			
				if(!in_array($error_line, $_SESSION['ngen']['ngen-file-errors'])) {
					$_SESSION['ngen']['ngen-file-errors'][] = $error_line;
				}
				return false;
			}
		}
		//
		
		$dir = new DirectoryIterator($path);
		
		foreach($dir as $fileinfo) {
			if(!$fileinfo->isDir()) {
				$filename = $fileinfo->getFilename();
			
				$file_list[] = $filename;
				
				if( $this->_is_image($path . $filename) ) {
					$thumb = $this->_create_thumbnail($path . $filename);
				}
				
			}
		}
		
		natcasesort($file_list);
		
		//$output .= "<option>$path</option>\n";
		
		foreach($file_list as $key => $file) {
		
			if( substr($file, 0, 1) == "." ) {
				continue;
			}
		
			if($as_options) {
				$output .= "<option value='$file'>$file</option>\n";
			} else {
				$output .= $file . "\n";
			}
		}
		
		return $output;
	}
	//
	
	//
	// Returns array containing filename, width, height
	//
	function _image_info($file) {
		// legacy for MH File compatibility
		$file = trim($file);
	
		$data = getimagesize($file);
		
		$file_info = pathinfo($file);
		$filename_noext = basename($file_info['basename'], "." . $file_info['extension']);
		
		$info = array();
		$info['filename'] = basename($file);
		$info['thumbnail'] = "thumbs/" . $filename_noext . "_thumb.jpg";
		$info['width'] = $data[0];
		$info['height'] = $data[1];
		$info['width_height'] = $data[3];
		$info['image_type'] = $data[2];
		
		return $info;
	}
	//
	
	//
	// Checks if a file is an image
	//
	function _is_image($file) {
		global $FNS;
		
		$is_image = false;
		
		// legacy for MH File compatibility
		$file = trim($file);
		
		$file = $FNS->remove_double_slashes($file);
		
		//
		// Check to make sure file is at least 12bytes, otherwise fail as not image - causes issues otherwise
		//
		
		if(file_exists($file) && filesize($file) > 11) {
					
			switch( @exif_imagetype($file) ) {
				case IMAGETYPE_GIF:
				case IMAGETYPE_BMP:
				case IMAGETYPE_JPEG:
				case IMAGETYPE_PNG:
					$is_image = true;
					break;
			}
		
		}
		
		return $is_image;
	}
	//
	
	//
	// Creates a thumbnail and returns the relative path to it
	//
	function _create_thumbnail($file, $width = 50, $height = 50) {
		global $LANG;
		
		$LANG->fetch_language_file('ngen_file_field');
	
		$uri = '';
		
		// legacy for MH File compatibility
		$file = trim($file);
		
		$file_info = pathinfo($file);
		$filename_noext = basename($file_info['basename'], "." . $file_info['extension']);
		
		$server_path = $file_info['dirname'];
		$thumb_path = $server_path . "/thumbs/";
		$filename = $file_info['basename'];
		$thumb_name = $filename_noext . "_thumb.jpg";
		
		// if file is newer than thumb recreate the thumb
		if( !file_exists($thumb_path . $thumb_name) || (filemtime($file) > filemtime($thumb_path . $thumb_name)) ) {
		
			$image_info = $this->_image_info($file);
			
			// Attempt to increase memory as much as possible
			@ini_set("memory_limit","12M");
			@ini_set("memory_limit","16M");
			@ini_set("memory_limit","32M");
			@ini_set("memory_limit","64M");
		
			switch( $image_info['image_type'] ) {
				case IMAGETYPE_GIF:
					$im = imagecreatefromgif($file);
					break;
					
				case IMAGETYPE_JPEG:
					$im = imagecreatefromjpeg($file);
					break;
				
				case IMAGETYPE_PNG:
					$im = imagecreatefrompng($file); 
					break;
			}
			
	    $width_old = $image_info['width'];
	    $height_old = $image_info['height'];
	    
	    // Make sure we don't distort image, crop if needed
			$int_width = 0;
			$int_height = 0;
			
			$adjusted_height = $height;
			$adjusted_width = $width;
			
			$wm = $width_old/$width;
			$hm = $height_old/$height;
			$h_height = $height/2;
			$w_height = $width/2;
				
			$ratio = $width/$height;
			$old_img_ratio = $width_old/$height_old;
				
			if ($old_img_ratio > $ratio) 
			{
				$adjusted_width = $width_old / $hm;
				$half_width = $adjusted_width / 2;
				$int_width = $half_width - $w_height;
			} 
			else if($old_img_ratio <= $ratio) 
			{
				$adjusted_height = $height_old / $wm;
				$half_height = $adjusted_height / 2;
				$int_height = $half_height - $h_height;
			}
	    //
	      
	    $nm = imagecreatetruecolor($width, $height);
	    //
	    // Dirtier thumbnails but less memory usage?
	    //$nm = imagecreate($width, $height);
	    
	    imagecopyresampled($nm, $im, 0, 0, 0, 0, $adjusted_width, $adjusted_height, $width_old, $height_old); 
	    
	    imagedestroy($im);
	      
	    if(!file_exists($thumb_path)) {  
				$create_dir = mkdir($thumb_path, 0777);
				chmod($thumb_path, 0777);
				
				
				// Testing for failure of directory creation 
				if(!$create_dir) {
				
					$error_line = str_replace(array('%{upload_path}', '%{upload_edit_link}'), array($thumb_path, BASE . "&C=admin&M=blog_admin&P=edit_upload_pref&id=" . $this->field_settings['options']), $LANG->line('error_file_path'));
			
					if(!in_array($error_line, $_SESSION['ngen']['ngen-file-errors'])) {
						$_SESSION['ngen']['ngen-file-errors'][] = $error_line;
					}
					
					$this->_error_message();
					return false;
				}
				
			}
			
			
		 	if( !imagejpeg($nm, $thumb_path . $thumb_name, 100) ) {
		 		return false;
		 	}
		 	
		 	imagedestroy($nm);
			
			chmod($thumb_path . $thumb_name, 0777);
			$uri = "thumbs/" . $thumb_name;

		}
		
		return $uri;
	}
	//
	
	/**
	* Modify any of the POST data for a stand alone entry insert
	*
	* @return	null
	* @since 	Version 1.0.0
	* @see		http://expressionengine.com/developers/extension_hooks/weblog_standalone_insert_entry/
	*/
	function weblog_standalone_insert_entry() {
		//echo "Uploading...";
		return $this->_display_errors_SAEF();
	}
	//
	
	//
	//
	//
	function _error_message() {
		global $IN, $FNS, $SESS, $OUT;
		
		// IF SAEF - show errors 
		//
		if($IN->GBL('C', 'GET') == '') {
			$saef = TRUE;
		} else {
			$saef = FALSE;
		}
		//
		
		//
		if($saef) {
			//
			$this->_display_errors_SAEF();
			
		} else {
			//
			$e_weblog_id = $IN->GBL('weblog_id');
			$e_entry_id = $IN->GBL('entry_id');
		
			$url  = BASE . '&C=edit&M=edit_entry&weblog_id=' . $e_weblog_id;
			$url .= '&entry_id=' . $e_entry_id;
		}
		
		$FNS->redirect($url);
	}
	//
	//
	//
	
	//
	// Inserts error messages in a SAEF
	//
	function _display_errors_SAEF() {
		global $SESS, $OUT;
	
		@session_start();
				
		//
		// Display any errors
		if( !empty($_SESSION['ngen']['ngen-file-errors']) ) {
		
			$error_array = array();
		
			foreach($_SESSION['ngen']['ngen-file-errors'] as $key => $error) {
				$error_array[] = $error;
			}
			
			//
			if (isset($_SESSION) AND isset($_SESSION['ngen']))
			{
				if (isset($_SESSION['ngen']['ngen-file-errors'])) unset($_SESSION['ngen']['ngen-file-errors']);
				if (isset($_SESSION['ngen']['ngen-file-messages'])) unset($_SESSION['ngen']['ngen-file-messages']);
			}
			//
			
			//return $error_output;
			return $OUT->show_user_error('general', $error_array);
		}
		//
		
	}
	//
	
	/**
	 * Return human readable sizes
	 *
	 * @author      Aidan Lister <aidan@php.net>
	 * @version     1.1.0
	 * @link        http://aidanlister.com/repos/v/function.size_readable.php
	 * @param       int    $size        Size
	 * @param       int    $unit        The maximum unit
	 * @param       int    $retstring   The return string format
	 * @param       int    $si          Whether to use SI prefixes
	 */
	function _size_readable($size, $unit = null, $retstring = null, $si = true)
	{
	    // Units
	    if ($si === true) {
	        $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
	        $mod   = 1000;
	    } else {
	        $sizes = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
	        $mod   = 1024;
	    }
	    $ii = count($sizes) - 1;
	 
	    // Max unit
	    $unit = array_search((string) $unit, $sizes);
	    if ($unit === null || $unit === false) {
	        $unit = $ii;
	    }
	 
	    // Return string
	    if ($retstring === null) {
	        $retstring = '%01.2f %s';
	    }
	 
	    // Loop
	    $i = 0;
	    while ($unit != $i && $size >= 1024 && $i < $ii) {
	        $size /= $mod;
	        $i++;
	    }
	 
	    return sprintf($retstring, $size, $sizes[$i]);
	}
	//
}


/* End of file ft.ff_file_field.php */
/* Location: ./system/fieldtypes/ff_file_field/ft.ff_file_field.php */