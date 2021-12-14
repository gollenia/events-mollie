<?php
if (!defined('ABSPATH')) exit;
include_once(ABSPATH.'wp-admin/includes/plugin.php');

if( class_exists('Stonehenge_EM_Mollie') ) {
	global $EM_Mollie;
	$EM_Mollie = new Stonehenge_EM_Mollie_Functions();
}


Class Stonehenge_EM_Mollie_Functions {

	#===============================================
	public function __construct() {
		$this->plugin = $this->get_plugin_data();

		add_action('admin_notices', array($this, 'create_admin_notices'));
		add_filter('em_mollie_wildcards', array($this, 'wildcards'), 10, 1);
		add_filter('em_event_output_placeholder', array($this, 'placeholder_sitename'), 10, 3);

		if( !shortcode_exists( 'mollie_methods') ) {
			add_shortcode('mollie_methods', array($this, 'mollie_methods'));
			add_shortcode('mollie-methods', array($this, 'mollie_methods'));
			add_action('init', array($this,'refresh_methods'));
		}
	}


	#===============================================
	function get_plugin_data() {
		$plugin = Stonehenge_EM_Mollie::get_plugin_data();
		return $plugin;
	}


	#===============================================
	function start_mollie() {
		require_once( plugin_dir_path(__DIR__). 'vendor/autoload.php' );

		// Set the right API key => Live or Test Mode.
		$api_key = get_option('em_mollie_api_key');
		if( isset($api_key) && !empty($api_key) ) {
			$mollie = new \Mollie\Api\MollieApiClient();
			$mollie->setApiKey( $api_key );
			return $mollie;
		}
		return false;
	}


	#===============================================
	function get_localized_time( $input ) {
		$UTC 	= new DateTimeZone("UTC");
		$newTZ 	= new DateTimeZone( get_option('timezone_string') );
		$date 	= new DateTime( date("Y-m-d H:i:s", strtotime($input)), $UTC );
		$date->setTimezone( $newTZ );
		$result = $date->format('Y-m-d H:i:s');
		return $result;
	}


	#===============================================
	public static function translate( $string ) {
		global $EM_Mollie;
		$plugin 	= $EM_Mollie->get_plugin_data();
		$text 		= $plugin['text'];
		$translate 	= array(
			// Statusses
			'open'			=> __('open', $text),
			'pending' 		=> __('pending', $text),
			'paid' 			=> __('paid', $text),
			'canceled' 		=> __('canceled', $text),
			'expired' 		=> __('expired', $text),
			'failed' 		=> __('failed', $text),
			'refunded' 		=> __('refunded', $text),
			'chargeback' 	=> __('chargeback', $text),

			// Extras
			'refund' 		=> __('refund', $text),
			'charged back' 	=> __('charged back', $text),

			// Methods
			'bancontact' 	=> __('Bancontact', $text),
			'banktransfer' 	=> __('Bank Transfer', $text),
			'belfius' 		=> __('Belfius Pay Button', $text),
			'bitcoin'		=> __('Bitcoin', $text),
			'creditcard' 	=> __('Credit Card', $text),
			'directdebit' 	=> __('Direct Debit', $text),
			'eps' 			=> __('EPS', $text),
			'giftcard' 		=> __('Gift Card', $text),
			'giropay' 		=> __('Giropay', $text),
			'ideal' 		=> __('iDEAL', $text),
			'inghomepay' 	=> __('ING Home\'Pay', $text),
			'kbc' 			=> __('KBC/CBC Payment Button', $text),
			'paypal' 		=> __('PayPal', $text),
			'paysafecard' 	=> __('paysafecard', $text),
			'sofort' 		=> __('SOFORT Banking', $text),

			// Descriptions
			'Bancontact'				=> __('Bancontact', $text),
			'Bank transfer' 			=> __('Bank Transfer', $text),
			'Belfius Pay Button'		=> __('Belfius Pay Button', $text),
			'Bitcoin'					=> __('Bitcoin', $text),
			'Credit card' 				=> __('Credit Card', $text),
			'Direct Debit' 				=> __('Direct Debit', $text),
			'eps' 						=> __('EPS', $text),
			'Gift card' 				=> __('Gift Card', $text),
			'Giropay' 					=> __('Giropay', $text),
			'iDEAL' 					=> __('iDEAL', $text),
			'ING Home\'Pay' 			=> __('ING Home\'Pay', $text),
			'KBC/CBC Payment Button' 	=> __('KBC/CBC Payment Button', $text),
			'PayPal' 					=> __('PayPal', $text),
			'paysafecard' 				=> __('paysafecard', $text),
			'SOFORT Banking' 			=> __('SOFORT Banking', $text),
		);

		if( !array_key_exists($string, $translate) ) {
			return $string;
		}

		return $translate[$string];
	}


	#===============================================
	function wildcards( $string ) {
		global $wp_rewrite, $EM_Notices, $EM_Booking, $EM_Event;

		foreach( $EM_Booking->get_tickets_bookings()->tickets_bookings as $EM_Ticket_Booking ) {
			$ticket_name = wp_kses_data( $EM_Ticket_Booking->get_ticket()->name );
		}
		$blog_name = get_bloginfo('name');

		if( get_option('dbem_multiple_bookings') ) 	{
			$event_name = $event_date = $blog_name;
		}
		else {
			$event_date = date( get_option('dbem_date_format'), strtotime($EM_Event->start_date) ); // Return formatted date.
			$event_name = $EM_Booking->get_event()->event_name;
		}

		$wildcards = array(
			'%blog_name%' 		=> $blog_name,
			'%booking_id%'		=> '#' . $EM_Booking->booking_id,
			'%event_date%' 		=> $event_date,
			'%event_name%' 		=> $event_name,
			'%spaces%'			=> $EM_Booking->get_spaces(),
			'%ticket_name%'		=> $ticket_name,
		);

		foreach( $wildcards as $wildcard => $value ) {
			$string = str_replace( $wildcard, $value, $string );
		}
		return $string;
	}


	#===============================================
	function update_plugin() {
		$plugin 		= $this->plugin;
		$old_version 	= get_option( $plugin['slug'] .'_version' );
		$new_version	= $plugin['version'];

		if( $old_version < $new_version ) {
			// Do stuff.

			// Prevent loop.
			update_option( $plugin['slug'] .'_version', $new_version, 'no' );
		}
	}


	#===============================================
	# 	Create Admin Notices.
	#===============================================
	function create_admin_notices() {
		$settingsUrl 	= esc_url_raw( admin_url() .'edit.php?post_type=event&page=events-manager-gateways&action=edit&gateway=mollie' );
		$message 		= '';

		$description = get_option('em_mollie_description');
		if( strpos( $description, '%event' ) ) {
			echo sprintf('<div class="notice notice-info is-dismissible"><p>%s<br>%s</p></div>',
				'<strong>EM - Mollie Payments now supports Events Manager Placeholders in the Payment Description.</strong>', sprintf('Please <a href=%s>update your settings</a> accordingly.', $settingsUrl ) );
		}
	}


	#===============================================
	function placeholder_sitename( $replace, $EM_Event, $result ) {
		if( $result === "#_SITENAME" ) {
			$replace = esc_html( get_bloginfo( 'name' ) );
		}
		return $replace;
	}


	#===============================================
	# 	Create shortcode [mollie_methods] to show a sprite image of all activted payment methods.
	#===============================================
	function mollie_methods() {
		global $EM_Mollie;
		$methods = $EM_Mollie->get_methods();

		if( is_array($methods) ) {
			wp_enqueue_style( 'mollie-methods', plugins_url('assets/mollie-methods.min.css', __DIR__) );
			$shortcode = '<div id="mollie-payment-methods">';
			foreach( $methods as $id => $value ) {
				$shortcode .= sprintf( '<div class="mollie-method icon %1$s" title="%2$s"></div>', $id, $this->translate($value));
			}
			$shortcode .= '</div>';
			return $shortcode;
		}
		return;
	}


	#===============================================
	function get_methods() {
		global $EM_Mollie;
		if( !$EM_Mollie->start_mollie() ) {
			return false;
		}

		$methods = get_option('mollie_activated_methods');
		if( !$methods ) {
			$methods	= array();
			$mollie 	= $EM_Mollie->start_mollie();
			$all 		= $mollie->methods->all();
			foreach( $all as $method ) {
				$methods[$method->id] = $method->description;
			}
			update_option('mollie_activated_methods', $methods);
		}
		return $methods;
	}


	#===============================================
	function refresh_methods() {
		if( isset( $_GET['em_mollie_action'] )  && ( $_GET['em_mollie_action']  === 'refresh_methods' ) ) {
			delete_option('mollie_activated_methods');
			$this->get_methods();
		}
		return;
	}


} // End class.
