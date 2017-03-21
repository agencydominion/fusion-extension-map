<?php
/**
 * @package Fusion_Extension_Map
 */
/**
 * Plugin Name: Fusion : Extension - Map
 * Plugin URI: http://www.agencydominion.com/fusion/
 * Description: Map Extension Package for Fusion.
 * Version: 1.1.8
 * Author: Agency Dominion
 * Author URI: http://agencydominion.com
 * License: GPL2
 */
 
/**
 * FusionExtensionMap class.
 *
 * Class for initializing an instance of the Fusion Map Extension.
 *
 * @since 1.0.0
 */


class FusionExtensionMap	{ 
	public function __construct() {
						
		// Initialize the language files
		load_plugin_textdomain( 'fusion-extension-map', false, plugin_dir_url( __FILE__ ) . 'languages' );
		
		// Enqueue admin scripts and styles
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts_styles'));
		
		// Enqueue front end scripts and styles
		add_action('wp_enqueue_scripts', array($this, 'front_enqueue_scripts_styles'));	
		
		// Add Settings
		add_action('admin_init', array($this, 'register_fusion_map_settings'), 11);
		
	}
	
	/**
	 * Enqueue JavaScript and CSS on Admin pages.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	 
	public function admin_enqueue_scripts_styles($hook_suffix) {
		global $post;
		
		$options = get_option('fsn_options');
		$fsn_post_types = !empty($options['fsn_post_types']) ? $options['fsn_post_types'] : '';
		
		// Editor scripts and styles
		if ( ($hook_suffix == 'post.php' || $hook_suffix == 'post-new.php') && (!empty($fsn_post_types) && is_array($fsn_post_types) && in_array($post->post_type, $fsn_post_types)) ) {
			wp_enqueue_script( 'fsn_map_admin', plugin_dir_url( __FILE__ ) . 'includes/js/fusion-extension-map-admin.js', array('jquery'), '1.0.0', true );
			wp_localize_script( 'fsn_map_admin', 'fsnExtMapJS', array(
					'fsnEditMapNonce' => wp_create_nonce('fsn-admin-edit-map')
				)
			);
			//add translation strings to script
			$translation_array = array(
				'error' => __('Oops, something went wrong. Please reload the page and try again.','fusion-extension-mape'),
				'layout_change' => __('Changing the Map Layout will erase the current Map. Continue?','fusion-extension-map')
			);
			wp_localize_script('fsn_map_admin', 'fsnExtMapL10n', $translation_array);
		}
	}
	
	/**
	 * Enqueue JavaScript and CSS on Front End pages.
	 *
	 * @since 1.0.0
	 *
	 */
	 
	public function front_enqueue_scripts_styles() {
		//plugin
		wp_register_script( 'fsn_map', plugin_dir_url( __FILE__ ) . 'includes/js/fusion-extension-map.js', array('jquery'), '1.0.0', true );
		wp_enqueue_style( 'fsn_map', plugin_dir_url( __FILE__ ) . 'includes/css/fusion-extension-map.css', false, '1.0.0' );
	}
	
	/**
	 * Map Settings
	 *
	 * @since 1.0.0
	 *
	 */
	
	public function register_fusion_map_settings() {
		//sections	
		add_settings_section(
			'fsn_map_settings',
			__('Map Settings', 'fusion-extension-map'),
			array($this, 'fsn_output_map_settings'),
			'fsn_settings'
		);
		//fields
		add_settings_field(
			'fsn_map_api_key',
			__('Google Maps API Key', 'fusion-extension-map'),
			array($this, 'fsn_google_maps_api_key'),
			'fsn_settings',
			'fsn_map_settings'
		);
	}
	
	public function fsn_output_map_settings() {
		echo '<p>'. __('Setup the Maps Extension plugin for Fusion.', 'fusion-extension-map') .'</p>';
	}
	
	/**
	 * Google Maps API Key
	 *
	 * @since 1.0.0
	 *
	 */
	
	public function fsn_google_maps_api_key() {
		// get option value from the database
		$options = get_option( 'fsn_options' );
		$google_maps_api_key = !empty($options['google_maps_api_key']) ? $options['google_maps_api_key'] : '';
		
		// echo the fields
		echo '<input id="fsn_google_maps_api_key" name="fsn_options[google_maps_api_key]" type="text" value="'. esc_attr($google_maps_api_key).'"><br/>';
		echo '<p class="description">'. __('Input <a target="_blank" href="https://developers.google.com/maps/documentation/javascript/get-api-key">Google Maps</a> API key.', 'fusion-extension-map') .'</p>';
	}
	
}

$fsn_extension_map = new FusionExtensionMap();

//EXTENSIONS

//map
require_once('includes/extensions/map.php');

?>