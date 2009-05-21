<?php

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
		'version'  => '0.9.8',
		'desc'     => 'Provides a file fieldtype',
		'docs_url' => 'http://www.ngenworks.com/software/ee/',
		'versions_xml_url' => 'http://ngenworks.com/software/version-check/versions.xml'
	);
	
	var $hooks = array(
		// Field Manager
		'show_full_control_panel_end'
	);

	var $default_field_settings = array(
		'options' => ''
	);

	var $default_cell_settings = array(
		'options' => ''
	);

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
		global $SESS;
	
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
		       
		$cell2 = $DSP->qdiv('defaultBold', $LANG->line('file_options_label'))
		       . $this->select_upload_locations($field_settings['options']);

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
		
		$r = '<label class="itemWrapper">'
		   . $DSP->qdiv('defaultBold', $LANG->line('file_options_label'))
		   . $this->select_upload_locations($cell_settings['options'])
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
		global $IN, $LANG;
		
		$LANG->fetch_language_file('ngen_file_field');
	
		$this->include_css('styles/ngen_file_field.css');
		$this->include_js('scripts/jquery.livequery.js');
		$this->include_js('scripts/jquery.ngen_file_field.js');
		
		$del_field_name = $field_name . "[delete]";
		$existing_field_name = $field_name . "[existing]";
		
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
		$edit_field = false;
		
		if($IN->GBL('M', 'GET') == 'blog_admin' AND $IN->GBL('P', 'GET') == 'edit_field') {
			$edit_field = true;
		}
		//
		
		//
		if(!$edit_field) {
			$upload_prefs = $this->get_upload_prefs($field_settings['options']);
			$file_path = $upload_prefs['server_path'] . $file_name;
			$file_uri = $upload_prefs['server_uri'] . $file_name;
		}
		//
			
		$file_field = "<div class='ngen-file-field-block'>";
	
		if( isset($file_name) AND $file_name ) {
			
			$file_field .= "<div class='ngen-file-field-data'>";
			
			$file_field .= "<a href='$file_uri' target='_blank' class='ngen-file-link'>";
			
			$file_kind_text = $LANG->line('file_kind');
			
			// Is file an image?
			if($this->_is_image($file_path)) {
				$file_kind_text = $LANG->line('image_kind');
			
				// if thumbnail doesn't exist, create it.
				$img_info = $this->_image_info($file_path);
				
				$thumbnail = $upload_prefs['server_uri'] . $img_info['thumbnail'];
				
				// Legacy for existing files
				if(!file_exists($upload_prefs['server_path'] . $img_info['thumbnail'])) {
					$thumbnail = $upload_prefs['server_uri'] . $this->_create_thumbnail($file_path);
				} 
				
				$file_field .= "<img src='$thumbnail' class='ngen-file-thumbnail' alt='" . $file_name . "' />\n";
			}
			
			$file_field .= $file_name . "</a>\n";
			$file_field .= "<div class='ngen-ff-delete'>\n";
			$file_field .= "<a href='#' class='ngen-file-delete-button'>" . str_replace('%{file_name}', $file_name, $LANG->line('delete_file_link')) . "</a>\n";
			
			// Confirm whether to change, delete, or cancel
			$file_field .= "<div class='ngen-ff-choice' style='display: none;'>\n";
			$file_field .= "<ul>\n";
			$file_field .= "<li class='ngen-ff-choice-remove'><a href='#'>" . str_replace('%{file_kind}', $file_kind_text, $LANG->line('choice_remove')) . "</li>";
			$file_field .= "<li class='ngen-ff-choice-delete'><a href='#'>" . $LANG->line('choice_delete') . "</li>";
			$file_field .= "</ul>\n";
			$file_field .= "<a href='#' class='ngen-ff-choice-cancel'>Cancel</a>\n";
			$file_field .= "</div> <!-- close .ngen-ff-choice -->\n";
			//
			
			$file_field .= "</div> <!-- close .ngen-ff-delete -->\n";
			
			$file_field .= "</div>\n";
			
			$file_field .= "<div class='ngen-file-field-new' style='display: none;'>\n";
			$file_field .= "<input type='file' name='$field_name' class='ngen-file-input' />\n";
			$file_field .= "<input type='hidden' name='" . $field_name . "[file_name]' value='" . $file_name  . "' />\n";
			$file_field .= "<input type='hidden' name='$del_field_name' />\n";
			
			$file_field .= "<select name='$existing_field_name' style='display: none;'>\n";
			$file_field .= "<option value=''>" . $LANG->line('option_choose_existing') . "</option>\n";
			$file_field .= $this->_get_file_list($upload_prefs['server_path'], true);
			$file_field .= "</select>\n";
			
			$file_field .= "<div class='ngen-file-choose-existing'>" . $LANG->line('use_existing') . "</div>\n";
			$file_field .= "</div>\n";
			
		} else {
			// Empty field
			$file_field .= "<input type='file' name='$field_name' class='ngen-file-input' />\n";
			$file_field .= "<input type='hidden' name='" . $field_name . "[file_name]' />\n";
			
			$file_field .= "<select name='$existing_field_name' style='display: none;'>\n";
			$file_field .= "<option value=''>" . $LANG->line('option_choose_existing') . "</option>\n";
			$file_field .= (!$edit_field) ? $this->_get_file_list($upload_prefs['server_path'], true) : '';
			$file_field .= "</select>\n";
			
			$file_field .= "<div class='ngen-file-choose-existing'>" . $LANG->line('use_existing') . "</div>\n";
		}
		
		$file_field .= "</div>";
		
		$js = 'nGenFile.lang.confirmDeleteFile = "'.$LANG->line('confirm_delete_file').'";';
		$js .= 'nGenFile.lang.confirmRemoveFile = "'.$LANG->line('confirm_remove_file').'";';
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
		global $FF, $SESS;
		
		@session_start();
		
		$field_name = $FF->field_name;
		
		// If delete field has value delete the file + thumbnail
		if(isset($field_data['delete']))
		{
			if ($field_data['delete'])
			{
				$upload_prefs = $this->get_upload_prefs($field_settings['options']);
				
				$file_info = pathinfo($field_data['delete']);
			
				if($this->_is_image($upload_prefs['server_path'] . $field_data['delete'])) {
					$image_info = $this->_image_info($upload_prefs['server_path'] . $field_data['delete']);
					@unlink($upload_prefs['server_path'] . $image_info['thumbnail']);
				}
				
				unlink($upload_prefs['server_path'] . $field_data['delete']);
				//$_SESSION['ngen']['ff-file-messages'][] = "File <em>" . $_POST[$field_name . "_delete"] . "</em> deleted.";
			}

			// Remove delete variables to avoid saving issues
			unset($field_data['delete']);
		}
		
		//if(empty($field_data['file_name']) && ( $_FILES[$field_name]['name'] != "" || $field_data['existing'] != "" ) ) {
			
		// update by Brandon Kelly for SAEF compatibility
		if(empty($field_data['file_name']) && ( ( isset($_FILES[$field_name]) && $_FILES[$field_name]['name'] ) || $field_data['existing'] ) ) {
		
			//unset($field_data['file_name']);
			$existing_file = $field_data['existing'];
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
				$return = $field_data['file_name'];
			}
			
		}
	
		return $return;
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
		global $FF, $FFM, $SESS;
		
		@session_start();
			
		$field_name = $FF->field_name;
		$row_count = $FFM->row_count;
		$col_id = $FFM->col_id;
		
		// If delete field has value delete the file + thumbnail
		if (isset($cell_data['delete']))
		{
			if ($cell_data['delete'])
			{
				$upload_prefs = $this->get_upload_prefs($cell_settings['options']);
				
				$file_info = pathinfo($cell_data['delete']);
			
				if($this->_is_image($upload_prefs['server_path'] . $cell_data['delete'])) {
					$image_info = $this->_image_info($upload_prefs['server_path'] . $cell_data['delete']);
					@unlink($upload_prefs['server_path'] . $image_info['thumbnail']);
				}
				
				unlink($upload_prefs['server_path'] . $cell_data['delete']);
				//$_SESSION['ngen']['ff-file-messages'][] = "File <em>" . $_POST[$field_name . "_delete"][$row_count][$col_id] . "</em> deleted.";
			}

			// Remove delete variables to avoid saving issues
			unset($cell_data['delete']);
		}
		
	
		//if(empty($cell_data['file_name']) && ($_FILES[$field_name]['name'][$row_count][$col_id] != "" || $cell_data['existing'] != "") ) {

		// update by Brandon Kelly for SAEF compatibility
		if(empty($cell_data['file_name']) && ( ( isset($_FILES[$field_name]) && $_FILES[$field_name]['name'][$row_count][$col_id] ) || $cell_data['existing'] ) ) {
	
			if($cell_data['existing']) {
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
		
		return $return;
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
		global $LANG, $SESS;
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
			
			$upload_prefs = $this->get_upload_prefs($settings);
			
			$upload_path = $upload_prefs['server_path'];
			$max_file_size = $upload_prefs['max_file_size'];
			$allowed_types = $upload_prefs['allowed_types'];
			
			// Are any of the file sizes too big?
			if($max_file_size != '')
			{
				if($file_size > $max_file_size)
				{
					$_SESSION['ngen']['ngen-file-errors'][] = str_replace(array('%{file_name}', '%{max_size}'), array($file_name, $this->size_readable($max_file_size, 'GB')), $LANG->line('error_file_size'));
					
					return false;
				}
			}
			//
			
			// Is file of valid type? Applies only if limited to image type
			$is_image = exif_imagetype($file_tmp_name);
			
			if($allowed_types == 'img' && $is_image == false) {
				$_SESSION['ngen']['ngen-file-errors'][] = str_replace('%{file_name}', $file_name, $LANG->line('error_file_not_image'));
				
				return false;
			}
			//
			
			//
			// Check if file exists already, if it does add an increment to the file name
			if( file_exists($upload_path . $file_name) ) {
				$matching_files = glob($upload_path . $file['name'] . "*" . $file['ext']);
	
				// Find highest number, add 1 and set new $file_name
				sort($matching_files);
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
			
			
			// Do the Upload
			if(@move_uploaded_file($file_tmp_name, $upload_path . $file_name) === FALSE)
			{
				$_SESSION['ngen']['ngen-file-errors'][] = str_replace('%{file_name}', $file_name, $LANG->line('error_file_upload'));
				return false;
			} else {
				chmod($upload_path . $file_name, 0777);
				if($is_image) {
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
		
		// Added by Fred Boyle - 2009.04.21
		// Check if field_data is an array or not
		if( !is_array($field_data) ) {
			$file_name = $field_data;
		} else {
			$file_name = array_key_exists('file_name', $field_data) ? $field_data['file_name'] : '';
		}
		
		if( !empty($file_name) ) {
			$upload_prefs = $this->get_upload_prefs($field_settings['options']);
			//$full_file_path = $upload_prefs['server_path'] . $file_name;
			$file_uri = $upload_prefs['server_uri'] . $file_name;
			$r = $file_uri;
		}
		
		return $r;
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
	function select_upload_locations($current_option)
	{
		global $DB, $PREFS;
		
		// Add custom drop down for file location
		$block = "<div class='itemWrapper'><select name=\"options\"><option value=\"\"></option>";
		
		$dls = $DB->query("SELECT id, name FROM exp_upload_prefs WHERE site_id = " . $PREFS->ini('site_id') . " ORDER BY name ASC");
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
	function get_upload_prefs($u_id) {
		global $DB;
		
		/*
		if( is_array($u_id) ) {
			$u_id = current($u_id);
		}
		*/
		
		$query = $DB->query("SELECT * FROM exp_upload_prefs WHERE id = $u_id");
		
		$upload_prefs['server_path'] = $query->row['server_path'];
		$upload_prefs['server_uri'] = parse_url($query->row['url'], PHP_URL_PATH);
		$upload_prefs['allowed_types'] = $query->row['allowed_types'];
		$upload_prefs['max_file_size'] = $query->row['max_size'];
		
		return $upload_prefs;
	}
	//
	
	//
	// Retrieve list of files in folder
	// option to return as array or options for a select field
	//
	function _get_file_list($path, $as_options = false) {
		$output = '';
		$file_list = array();
		
		// if path doesn't exist, create it
		if(!file_exists($path)) {  
			mkdir($path, 0777);
			chmod($path, 0777);
		}
		//
		
		$dir = new DirectoryIterator($path);
		
		foreach($dir as $fileinfo) {
			if(!$fileinfo->isDir()) {
				$file_list[] = $fileinfo->getFilename();
			}
		}
		
		natcasesort($file_list);
		
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
		$is_image = false;
		
		// legacy for MH File compatibility
		$file = trim($file);
			
		switch( exif_imagetype($file) ) {
			case IMAGETYPE_GIF:
			case IMAGETYPE_BMP:
			case IMAGETYPE_JPEG:
			case IMAGETYPE_PNG:
				$is_image = true;
				break;
		}
		
		return $is_image;
	}
	//
	
	//
	// Creates a thumbnail and returns the relative path to it
	//
	function _create_thumbnail($file, $width = 50, $height = 50) {
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
	    
	    imagecopyresampled($nm, $im, 0, 0, 0, 0, $adjusted_width, $adjusted_height, $width_old, $height_old); 
	    
	    imagedestroy($im);
	      
	    if(!file_exists($thumb_path)) {  
				mkdir($thumb_path, 0777);
				chmod($thumb_path, 0777);
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
	function size_readable($size, $unit = null, $retstring = null, $si = true)
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