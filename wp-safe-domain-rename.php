<?php

/*
 Plugin Name: Safe Domain Rename
 Plugin URI: http://voceconnect.com
 Description: Lets you safely do a find/replace on the domain name in the db without it breaking the serialize options.  Activate just before export/import and deactivate immediately after
 Version: 0.1
 Author: prettyboymp
 */

class Safe_Domain_Rename_Plugin {

	private $renames_array;

	public function __construct() {
		$this->renames_array = array(
			'%^site_url^%' => get_site_url(),
			'%^home_url^%' => get_home_url(),
			'%^network_site_url^%' => network_site_url(),
			'%^network_home_url^%' => network_home_url(),
		);
	}

	public function _on_plugin_activation() {
		global $wpdb;
		//clean all the current options
		$option_names = $wpdb->get_col("SELECT option_name from $wpdb->options where option_value like 'a:%' || 'O:%'");
		foreach($option_names as $option_name) {
			add_filter('pre_update_option_'.$option_name, array($this, '_filter_pre_update_option_clean'));
			$option_value = get_option($option_name);
			update_option($option_name, $option_value);
		}
	}

	public function _on_plugin_deactivation() {
		global $wpdb;
		//clean all the current options
		$option_names = $wpdb->get_col("SELECT option_name from $wpdb->options where option_value like 'a:%' || 'O:%'");
		foreach($option_names as $option_name) {
			add_filter('pre_update_option_'.$option_name, array($this, '_filter_pre_update_option_restore'));
			$option_value = get_option($option_name);
			update_option($option_name, $option_value);
		}
	}

	public function _filter_pre_update_option_clean($value) {
		if(is_array($value) || is_object( $value )) {
			$value = $this->replace_string_values($value, array_values($this->renames_array), array_keys($this->renames_array));
		}
		return $value;
	}

	public function _filter_pre_update_option_restore($value) {
		if(is_array($value) || is_object( $value )) {
			$value = $this->replace_string_values($value, array_keys($this->renames_array), array_values($this->renames_array));
		}
		return $value;
	}

	public function replace_string_values($value, $old_values, $new_values) {
		if( is_array( $value) ) {
			foreach($value as $the_key => $the_value) {
				$value[$the_key] = $this->replace_string_values($the_value, $old_values, $new_values);
			}
			return $value;
		} elseif( is_object( $value ) ) {
			 $reflValue = new ReflectionClass($value);
			 foreach($reflValue->getProperties(ReflectionProperty::IS_PUBLIC) as $property_name) {
				 $value->$property_name = $this->replace_string_values($value->$property_name, $old_values, $new_values);
			 }
			 return $value;
		} elseif(  is_string( $value ) ) {
			$value = str_replace($old_values, $new_values, $value, $count);
			return $value;
		} else {
			return $value;
		}
	}

}
$sfdp = new Safe_Domain_Rename_Plugin();
register_activation_hook( __FILE__, array($sfdp, '_on_plugin_activation') );
register_deactivation_hook( __FILE__, array($sfdp, '_on_plugin_deactivation') );
unset($sfdp); //clear global scope

