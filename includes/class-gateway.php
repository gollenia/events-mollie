<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;


#===============================================
# 	Configure Mollie Gateway for Events Manager Pro.
#===============================================
Class EM_Gateway_Mollie extends EM_Gateway {

	var $gateway 		= 'mollie';
	var $title 			= 'Mollie';
	var $status 		= 4;
	var $status_txt 	= 'Awaiting Mollie Payment';
	var $button_enabled = true;
	var $payment_return = true;
	var $supports_multiple_bookings = true;
	var $transaction_detail = array(
			'Mollie Dashboard',
			'https://www.mollie.com/dashboard/payments/%s',
			'https://www.mollie.com/dashboard/payments/%s'
		);

	#===============================================
	public function __construct() {
		parent::__construct();

		$this->plugin 	= Stonehenge_EM_Mollie::get_plugin_data();
		$this->text 	= $this->plugin['text'];

		global $EM_Mollie;
		$mollie = $EM_Mollie->start_mollie();

		if( is_object($mollie) ) {
			$this->mollie = $mollie;
		}

		// Check if the gateway is activated (= toggled).
		if( parent::is_active() ) {
			add_action('em_gateway_js', array($this, 'em_gateway_js'));
			add_filter('em_booking_validate', array($this, 'booking_validate'), 2, 2);
		}
		add_filter('the_content', array($this, 'handle_mollie_customer_return'));

	}


	#===============================================
	# 	Add JavaScript to prevent WordPress error "Header already sent in..."
	#===============================================
	function em_gateway_js() {
		include dirname( __FILE__ ) . '/gateway.mollie.js';
	}


	#===============================================
	# 	Booking Interception - functions that modify booking object behaviour
	#===============================================
	function booking_form() {
		global $EM_Booking, $EM_Event, $EM_Ticket, $EM_Mollie;

		echo get_option('em_mollie_form');

		// Show Active Payment Methods?
		if( get_option('em_mollie_show_methods') == "yes" && is_object($EM_Mollie->start_mollie()) ) {
			echo '[mollie_methods]'; // Output the shortcode.
		}
		return;
	}


	#===============================================
	# 	Hook into booking validation and check validate payment type if present.
	#===============================================
	function booking_validate($result, $EM_Booking) {
		if (isset( $_POST['paymentType'] ) && empty( $_POST['paymentType'] )) {
			$EM_Booking->add_error( __('Please select a payment method.', $this->text) );
			$result = false;
		}

		$api_key = get_option('em_mollie_api_key');
		if( !isset($api_key) || empty($api_key) ) {
			$EM_Booking->add_error( __('Mollie API Key is not found.', $this->text) );
			$result = false;
		}

		return $result;
	}


	#===============================================
	# 	After form submission by user, add Mollie vars and show feedback message.
	#===============================================
	function booking_form_feedback( $return, $EM_Booking = true ) {
		if( !empty($return['errors']) ) {
			return $return;
		}

		global $wpdb, $wp_rewrite, $EM_Notices, $EM_Booking, $EM_Event, $EM_Mollie;
		$timestamp   		= time();
		$booking_id  		= $EM_Booking->booking_id;
		$description		= get_option('em_mollie_description') ?? sprintf( esc_html__('%s tickets for %s', $this->text), '#_BOOKINGSPACES', '#_EVENTNAME');

		// Multiple Booking. (Since 2.2)
		if( (get_class($EM_Booking) === 'EM_Multiple_Booking') ) {
			// If only one event is booked, process the description as a single booking.
			if( count( $EM_Booking->get_bookings() ) === 1 ) {
				foreach($EM_Booking->get_bookings() as $booking) {
					$description = $booking->output($description);
					$description = apply_filters( 'em_mollie_wildcards', $description );
				}
			}
			// Replace Single event wildcards (= fallback).
			if( strpos('%event_', $description) ) {
				$description =  __('%spaces% tickets for %blog_name%', $this->text);
				$description = apply_filters( 'em_mollie_wildcards', $description );
			}

			$description = $EM_Booking->output($description);
			$description = apply_filters( 'em_mollie_wildcards', $description );
		}

		// Single booking.
		else {
			$description = $EM_Booking->output($description);
			$description = apply_filters( 'em_mollie_wildcards', $description );
		}

		// Double check $EM_Booking is an EM_Booking object and that we have a booking awaiting payment.
		if (is_object($EM_Booking) && $this->uses_gateway($EM_Booking) ) {
			if (!empty($return['result']) && $EM_Booking->get_price() > 0 && $EM_Booking->booking_status == $this->status ) {

				$mollie 	= $EM_Mollie->start_mollie();
				$request 	= $mollie->payments->create( array(
					'amount'  		=> array(
						'currency' 		=> strtoupper( get_option('dbem_bookings_currency') ),
						'value'   		=> number_format( $EM_Booking->get_price(), 2)
					),
					'description' 	=> $description,
					'redirectUrl' 	=> $this->get_mollie_return_url() . "?em_mollie_return={$EM_Booking->booking_id}",
					'webhookUrl' 	=> $this->get_payment_return_url(),
					'locale'   		=> get_locale(),		// Set checkout page to blog's locale.
					'sequenceType'  => 'oneoff',  			// Default for single payment.
					'metadata'  	=> array(
						'booking_id' 	=> $EM_Booking->booking_id,
						'name'    		=> $EM_Booking->get_person()->get_name(),
						'email'   		=> $EM_Booking->get_person()->user_email,
					),
				));

				$return['message']  = get_option('em_mollie_message_redirect');
				$mollie_return  	= array(
					'mollie_url'   		=> $request->getCheckoutUrl(),
					'mollie_vars'   	=> $request
				);
				$return = array_merge( $return, $mollie_return );

			} else {
				// Return a free message and do not redirect to Mollie.
				$return['message'] 	= get_option('em_mollie_message_free');
				$mollie_return 		= array(
					'mollie_url' 		=> $this->get_mollie_return_url() ."?em_mollie_free={$EM_Booking->booking_id}"
				);
				$return = array_merge( $return, $mollie_return );
			}
		}
		return $return;
	}


	#===============================================
	# 	Determine the redirect url after Mollie payment.
	#===============================================
	function get_mollie_return_url() {
		global $EM_Event;

		 if( get_option('em_mollie_return_page') ){
			 $redirect_url = get_permalink(get_option( 'em_mollie_return_page') );
		 }
		 else {
			 if( get_option('dbem_multiple_bookings') )	{
				 $redirect_url = get_permalink( get_option( 'dbem_events_page' ) );
			 }
			 else {
				 $redirect_url = $EM_Event->output("#_EVENTURL");
			 }
		 }
		return $redirect_url;
	}


	#===============================================
	# 	Handle content when a user returns from Mollie after payment.
	#===============================================
	function handle_mollie_customer_return( $content ) {
	 	if( strpos($_SERVER['REQUEST_URI'], 'em_mollie_free') !== false ) {
			$content = sprintf( '<p><div class="em-booking-message em-booking-message-success">%s</div></p>', get_option('em_mollie_message_free'));
			return $content;
		}

		if( strpos($_SERVER['REQUEST_URI'], 'em_mollie_return') !== false ) {
			// Changed in version 2.3
			global $EM_Mollie;
			$style 			= null;
			$class 			= null;
			$feedback 		= null;
			$result 		= null;
			$booking_id 	= $_REQUEST['em_mollie_return'];
			$EM_Booking 	= em_get_booking($booking_id);
			$status 		= (int) $EM_Booking->status;

			$payment_status = array(
				0 => __('pending', $this->text),
				1 => __('paid', $this->text),
				2 => __('failed', $this->text),
				3 => __('canceled', $this->text),
				4 => __('pending', $this->text),
				5 => __('pending', $this->text),
			);

			switch( $status ) {
				case 1: 	// Approved
					$class 		= 'success';
					$feedback 	= get_option('dbem_booking_feedback');
				break;
				case 3:		// Cancelled
				case 2: 	// Reject = fallback.
					$class 		= 'error';
					$feedback 	= get_option('dbem_booking_feedback_error');
				break;
				case 0: 	// Pending/Open
				case 4: 	// Awaiting Online Payment.
				case 5: 	// Awaiting Payment.
					$class 		= 'info';
					$feedback 	= get_option('dbem_booking_feedback_pending');
					// Add styling for this status only - use EM css for the others.
					$style		= '<style>.em-booking-message-info { background-color: #d1ecf1; border: 1px solid #0c5460;}</style>';
				break;
			}
			$status_string 	= get_option('em_mollie_status_text') ?? __('The status of your payment is', $this->text);
			$status_text 	= sprintf('%s: <strong>%s</strong><br>', $status_string, strtoupper($payment_status[$status]) );
			$status_text 	= get_option('em_mollie_show_status') != 'no' ? $status_text : null;
			$feedback_text 	= get_option('em_mollie_show_feedback') != 'no' ? $feedback	: null;
			$button 		= sprintf('<p><a href=%s><input type="button" value=%s class="button mollie-transaction"></a></p>',
				esc_url(get_permalink(get_option('dbem_events_page'))), esc_attr__('Continue', $this->text)	);

			$result 	= $style;
			$result 	.= sprintf('<div class="em-booking-message em-booking-message-%s">', $class);
			$result 	.= $status_text . $feedback_text;
			$result		.= '</div>';
			$result		.= $button;

			$content = apply_filters('em_mollie_payment_feedback', $result);
			return $content;
		}
		return $content;
	}


	#===============================================
	# 	When Mollie calls the webhook, update database, update Booking Status & send emails.
	#===============================================
	function handle_payment_return() {
		if( !isset($_REQUEST['em_payment_gateway']) || $_REQUEST['em_payment_gateway'] != 'mollie' || !isset($_REQUEST['id']) ) {
			return;
		}

		$mollie_id = trim( $_REQUEST['id'] );

		// Fetch all transaction info from Mollie.
		global $EM_Mollie;
		if( !is_object($EM_Mollie->start_mollie()) ) {
			return;
		}

		$mollie 	= $EM_Mollie->start_mollie();
		$payment 	= $mollie->payments->get($mollie_id);
		$timestamp  = date('Y-m-d H:i:s', strtotime($payment->createdAt));
		$booking_id = $payment->metadata->booking_id;
		$EM_Booking	= em_get_booking($booking_id);
		$note 		= ' ';

		if (!empty( $EM_Booking->booking_id )) {
			$EM_Booking->manage_override = true;
			$user_id 	= $EM_Booking->person_id;

			if ($payment->isPaid() && !$payment->hasRefunds() && !$payment->hasChargebacks()) {
				$this->record_transaction( $EM_Booking, $payment->amount->value, strtoupper($payment->amount->currency), $timestamp, $mollie_id, ucwords($EM_Mollie->translate($payment->status)), $note );
				$EM_Booking->approve(true, true);
			}

			elseif ($payment->isOpen() || $payment->isPending()) {
				$EM_Booking->set_status(4);
			}

			elseif ($payment->isCanceled() || $payment->isFailed() || $payment->isExpired()) {
				// Mollie uses US spelling.
				$payment->status = ($payment->status != 'canceled') ? $payment->status : 'cancelled';
				$this->record_transaction( $EM_Booking, $payment->amount->value, strtoupper($payment->amount->currency), $timestamp, $mollie_id, ucwords($EM_Mollie->translate($payment->status)), $note );
				$send_mail = get_option('em_mollie_send_cancel_mail') != 'yes' ? false : true;
				$EM_Booking->set_status(3, $send_mail);
			}

			elseif ($payment->hasChargebacks()) {
				$note = __('Charged back', $this->text);
				$this->record_transaction( $EM_Booking, $payment->amount->value, strtoupper($payment->amount->currency), $timestamp, $mollie_id, ucwords($EM_Mollie->translate('charged back')), $note);
				$EM_Booking->set_status(3);
			}
			elseif ($payment->hasRefunds()) {
				// Fetch detailed info for refund from Mollie.
				foreach( $payment->refunds() as $refund ) {
					$date 		= $EM_Mollie->get_localized_time($refund->createdAt);
					$note 		= sprintf( __('Refunded on %s', $this->text), $date );
					$this->record_transaction( $EM_Booking, $payment->amountRefunded->value, strtoupper($payment->amount->currency), $timestamp, $mollie_id, ucwords($EM_Mollie->translate('refunded')), $note);
				}
			}

			do_action('em_payment_processed', $EM_Booking, $this);
		}
		return;
	}


	#===============================================
	# 	Gateway Settings Functions
	#===============================================
	function define_settings_fields() {
		global $EM_Mollie;
		$text 			= $this->text;
		$dashboard_url 	= 'https://www.mollie.com/dashboard/developers/api-keys';
		$description 	=  sprintf( esc_html__('%s tickets for %s', $text), '#_BOOKINGSPACES', '#_EVENTNAME');
		$force_reload 	= admin_url('edit.php?post_type=event&page=events-manager-gateways&action=edit&gateway=mollie&em_mollie_action=refresh_methods');

		$fields_array = array(
			array(
				'id' 		=> 'api_key',
				'label' 	=> __('Molie API Key', $text),
				'type' 		=> 'text',
				'default' 	=> '',
				'help' 		=> sprintf( __('Obtain your Live or Test API Key from your <a href=%s target="_blank">Mollie Dashboard</a>.', $text), $dashboard_url ),
			),
			array(
				'id'		=> 'show_methods',
				'label'  	=> __('Display Payment Methods', $text),
				'type' 		=> 'toggle',
				'default' 	=> 'yes',
				'help' 		=> __('Display small images of the activated payment methods on your booking form?', $text) .'<br>'. sprintf( __( 'You can activate/deactivate each payment method individually in your <a href=%s target="_blank">Mollie Dashboard</a>.', $text), $dashboard_url ),
			),
			array(
				'id'		=> 'refresh_methods',
				'label'		=> __('Active Payment Methods', $text),
				'type'		=> 'button',
				'default' 	=> $EM_Mollie->mollie_methods() .'<a href="'.$force_reload.'"><button type="button" class="button-secondary" title="'. __('Click here to refresh the cache if you have changed your active payment methods in the Mollie Dashboard.', $text) .'">'. __('Clear cache', $text) .'</button></a>',
			),
			array(
				'id' 		=> 'message_free',
				'label' 	=> __('Free Booking Message', $text),
				'type' 		=> 'text',
				'default' 	=> __('Thank you for your booking.<br>You will receive a confirmation email soon.', $text),
				'help' 		=> __('This message will be shown if the total booking price = 0.00. Your customer will <u>not</u> be redirected to Mollie.', $text),
			),
			array(
				'id' 		=> 'message_redirect',
				'label' 	=> __('Redirect Message', $text),
				'type' 		=> 'text',
				'default' 	=> __('Redirecting to complete your online payment...', $text),
				'help' 		=> __('This message will be shown right before your customer is redirected to Mollie.', $text),
			),
			array(
				'id'		=> 'return_page',
				'label' 	=> __('Return Page', $text),
				'type'  	=> 'page',
				'default'  	=> '',
				'help'   	=> __('Your customer will be redirected back to this page after the payment.', $text).'<br>'. __('Leave blank to use the Single Event Page (in Single Bookings Mode) or the Events Page (in Multiple Bookings Mode).', $text),
			),
			array(
				'id' 		=> 'show_status',
				'label' 	=> __('Display Payment Status', $text),
				'type' 		=> 'toggle',
				'default' 	=> 'yes',
				'help' 		=> __('Display the payment status on the Return Page?', $text) .'<br><code>'. __('The status of your payment is:', $text) .' [status]</code>',
			),
			array(
				'id' 		=> 'status_text',
				'label' 	=> __('Payment Status Text', $text),
				'type' 		=> 'text',
				'default'	=>  __('The status of your payment is', $text),
				'help'		=> __('This will change the output of the setting above.', $text) .'<br>'. __('Default') .': <code>'. __('The status of your payment is', $text) .'</code>',
			),
			array(
				'id' 		=> 'show_feedback',
				'label' 	=> __('Display Feedback Messages', $text),
				'type' 		=> 'toggle',
				'default' 	=> 'yes',
				'help' 		=> sprintf( __('Display the booking feedback messages as set in your <a href=%s target="_blank">Events Manager Settings</a>?', $text), admin_url('edit.php?post_type=event&page=events-manager-options#bookings') ),
			),
			array(
				'id' 		=> 'description',
				'label' 	=> __('Payment Description', $text),
				'type'		=> 'text',
				'default' 	=> sprintf( esc_html__('%s tickets for %s', $text), '#_BOOKINGSPACES', '#_EVENTNAME'),
				'help' 		=> sprintf( esc_html__('All %s are allowed. HTML is not.', $text), '<a href='. admin_url('edit.php?post_type=event&page=events-manager-help') .' target="_blank">Events Manager Placeholders</a>' ) .'<br><strong>' . __('Additional Placeholder', $text) .':</strong> '. sprintf( __('You can use %s to show the name of this blog.', $text), '<code>#_SITENAME</code>') .'<br>&nbsp;<br><strong>'. __('Multiple Bookings Mode', 'events-manager') .':</strong><br>' . __('If Multiple Bookings Mode is enabled and a user books for only one event, the payment description will be processed as a single booking.', $text) .' '. sprintf( __('Otherwise Events Manager will replace %s with "%s".', $text), '<code>#_EVENTNAME</code>', __('Multiple Events', 'events-manager') ) .'&nbsp;'. sprintf( __('The Transactions Table wil still show "%s", though.', $text), __('Multiple Events', 'events-manager') ) . '<br>&nbsp;<br>'. sprintf( __('The deprecated wildcards from previous versions, like %s and %s, can still be used for now.', $text), '%event_name%', '%blog_name%') ,
			),
			array(
				'id'		=> 'send_cancel_mail',
				'label'  	=> __('Send email on failed / cancelled payment?', $text),
				'type' 		=> 'toggle',
				'default' 	=> 'yes',
				'help'		=> __('By default Events Manager will send the Booking Cancelled Email if a payment had failed or is incomplete. This can lead to confusion if the user rebooks right after with a successful payment. This option lets you disable sending the automatic Booking Cancelled Email. (Setting this option to "no" will not affect the email if you change the booking status manually.)', $text),
			),
		);
		return $fields_array;
	}


	#===============================================
	# 	Create the Gateway Settings Page.
	#===============================================
	function mysettings() {
		global $EM_options;
		wp_enqueue_script('em-mollie');
		wp_enqueue_style('em-mollie');

		echo '<table class="form-table stonehenge-table">';
		foreach($this->define_settings_fields() as $fields => $field) {
			$field['id'] 	= strtolower($field['id']);							// Just in case.
			$field['name'] 	= 'em_mollie_'. $field['id'];
			$field['value'] = !empty(get_option( $field['name'])) ? get_option($field['name']) : @$field['default'];
			$helper    		= !empty($field['help']) ? '<p class="description">'.$field['help'].'</p>' : '';
			$class 			= str_replace('_', '-', 'mollie-' . $field['id']);

			echo "<tr class='{$class}'>";
			echo '<th scope="row">'.$field['label'].'</th>';
			echo '<td>';

			switch($field['type']) {
				case 'page':
					if($field['value']) { $idSelectPage = $field['value']; } else { $idSelectPage = 0; }
					$none = __('None');
					$args = array(
						'name'    			=> $field['name'],
						'selected'     		=> $idSelectPage,
						'show_option_none' 	=> "[ {$none} ]",
					);
					wp_dropdown_pages($args);
				break;
				case 'select':
					echo '<select name="'.$field['name'].'">';
					if(!empty($field['choices'])) {
						foreach($field['choices'] as $v => $l) {
							$selected = $v === $field['value'] ? 'selected="selected"' : '';
							printf('<option %s value="%s">%s</option>', $selected, $v, $l);
						}
					} else {
						$selected = $field['value'];
						?>
						<option value="no" <?php echo ($selected == 'no') ? 'selected="selected"' : ''; ?>><?php _e('No'); ?></option>
						<option value="yes" <?php echo ($selected == 'yes') ? 'selected="selected"' : ''; ?>><?php _e('Yes'); ?></option>
						<?php ;
					}
					echo '</select>';
				break;

				case 'toggle':
					echo '<div class="switch-toggle switch-holo" style="width: 120px; height:1.85em;">';
					$name = $field['name'];
					$id = $field['id'];

					$choices = array('no' => __('No'), 'yes' => __('Yes') );
					foreach( $choices as $v => $l) {
						$checked = ($v === $field['value']) ? 'checked=checked' : '';
						echo sprintf( '<input type="radio" name="%s" id="%s" value="%s" %s ><label for="%s">%s</label>',
							$name, "{$id}_{$v}", $v, $checked, "{$id}_{$v}", $l
						);
					}
					echo '<a></a></div>';
					break;

				case 'button':
					echo $field['default'];
				break;
				default:
					echo '<input type="text" name="'.$field['name'].'" value="'. __($field['value'], $this->text) .'" class="regular-text" required="required">';
				break;
			}
			echo $helper;
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
	}


	#===============================================
	# 	Save or update the Gateway Settings Page options.
	#===============================================
	function update() {
		// Hook into function of Events Manager ->handles sanitation for all inputs.
		parent::update();

		$settings_fields = $this->define_settings_fields();
		unset( $settings_fields[2] ); // Prevent unidentified index.
		foreach( $settings_fields as $fields => $field ) {
			$field['id'] = 'em_'.$this->gateway.'_'. $field['id'];
			update_option( $field['id'], stripslashes($_POST[$field['id']]) );
		}
		return true;
	}

} // End class.

EM_Gateways::register_gateway('mollie', 'EM_Gateway_Mollie');
