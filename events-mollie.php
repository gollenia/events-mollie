<?php
/************************************************************
* Plugin Name:			Events - Mollie Payments
* Description:			Adds 18 payment methods and 31 currencies to Events
* Version:				2.7
* Author:  				Stonehenge Creations
* Author URI: 			https://www.stonehengecreations.nl/
* Plugin URI: 			https://www.stonehengecreations.nl/creations/stonehenge-em-mollie/
* License URI: 			https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain: 			stonehenge-em-mollie
* Domain Path: 			/languages
* Requires at least: 	5.3
* Tested up to: 		5.5
* Requires PHP:			7.3
* Network:				false
************************************************************/

if( !defined('ABSPATH') ) exit;
include_once(ABSPATH.'wp-admin/includes/plugin.php');


Class Stonehenge_EM_Mollie {

	public function __construct() {
		include('includes/class-functions.php');
		if( class_exists('EM_Gateway') ) {
			include('includes/class-gateway.php' );
		}
		add_action('init', array($this, 'load_translations'));
		add_action('admin_enqueue_scripts', array($this, 'register_assets'));
		add_filter('plugin_action_links', array($this, 'add_settings_link'), 10, 2);
		add_filter('plugin_row_meta', array($this, 'add_plugin_links'), 10, 2);
	}

	public static function get_plugin_data() {
		$wp 	= get_plugin_data( __FILE__ );
		$plugin = array(
			'name' 		=> $wp['Name'],
			'version' 	=> $wp['Version'],
			'text' 		=> $wp['TextDomain'],
			'slug' 		=> $wp['TextDomain'],
			'class' 	=> __CLASS__,
			'base' 		=> plugin_basename(__DIR__),
			'url' 		=> admin_url().'edit.php?post_type=event&page=events-manager-gateways&action=edit&gateway=mollie',
		);
		return $plugin;
	}

	public function load_translations() {
		$plugin = self::get_plugin_data();
		$text 	= $plugin['text'];
		$locale = apply_filters( 'plugin_locale', function_exists( 'determine_locale' ) ? determine_locale() : get_locale(), $text );
		$mofile = dirname( __FILE__ ) . '/languages/'. $text . '-' . $locale . '.mo';
		$loaded = load_textdomain( $text, $mofile );
		if( !$loaded ) { $loaded = load_plugin_textdomain( $text, false, '/languages/' ); }
		if( !$loaded ) { $loaded = load_muplugin_textdomain( $text, '/languages/' ); }
	}

	public function add_settings_link( $links, $file ) {
		$plugin = self::get_plugin_data();
		$base 	= $plugin['base'];
		if( $file != plugin_basename("{$base}/{$base}.php")) {
			return $links;
		}
		else {
			$settings_link = sprintf( '<a href="%s">%s</a>', $plugin['url'], __('Settings') );
			array_unshift($links, $settings_link);
			return $links;
		}
	}

	public function add_plugin_links( $links, $file ) {
		$plugin = self::get_plugin_data();
		$base 	= $plugin['base'];
		if( $file != plugin_basename( "{$base}/{$base}.php")) {
			return $links;
		}

		$author			= 'DuisterDenHaag';
		$donate			= 'https://useplink.com/payment/VRR7Ty32FJ5mSJe8nFSx';
		$donate_link  	= array('&#127873; <a href="'.$donate.'" target="_blank">'.__('Donate', $base).'</a>');
		$rate_url 		= 'https://wordpress.org/support/plugin/'. $base .'/reviews/?rate=5#new-post';
		$rate_link 		= array(' &#11088; <a href="'.$rate_url.'" target="_blank">'. __('Rate this plugin', $base) .'</a>' );
		$support 		= __('Support');
		$support_link 	= array("<a href='https://wordpress.org/support/plugin/{$base}/' target='_blank'>{$support}</a>");
		return array_merge($links, $support_link);
		
	}

	public function register_assets() {
		wp_register_script('em-mollie', plugins_url('/assets/stonehenge-em-mollie.min.js', __FILE__), array('jquery'), '2.4.3', true);
		wp_register_style('em-mollie', plugins_url('/assets/stonehenge-em-mollie.min.css', __FILE__), array(), '2.4.3', 'screen' );
	}


}


add_action('plugins_loaded', function() {
	if( is_plugin_active('events/events.php') ) {
		new Stonehenge_EM_Mollie();
	}
}, 13);
