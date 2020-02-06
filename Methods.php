<?php

// TODO: Check try - catch in all methods

namespace PluginSDK;

use WellnessLiving\SDK;
use WellnessLiving\WlAssertException;
use WellnessLiving\WlDebugException;
use WellnessLiving\WlUserException;
use WellnessLiving\Wl\Business\DataModel;

/**
 * Class Methods for WP
 * @package PluginSDK
 */
class Methods {
	public static $o_config = null;// WL SDK config
	public static $o_config_confidential_information = null;// WL SDK config for confidential information
	protected $errors = [];

	/**
	 * Methods constructor.
	 */
	public function __construct() {
		// Set config
		if ( 'production' == SDK::get_setting( SDK::SETTINGS_ENVIRONMENT ) ) {
			self::$o_config                          = new ConfigProduction();
			self::$o_config_confidential_information = new ConfigProductionConfidentialInformation();
		} else {
			self::$o_config                          = new ConfigStaging();
			self::$o_config_confidential_information = new ConfigStagingConfidentialInformation();
		}
	}

	/**
	 * Get config
	 *
	 * @param bool $confidential_information
	 *
	 * @return ConfigStaging|null
	 */
	private function get_config( $confidential_information = false ) {
		if ( $confidential_information ) {
			return self::$o_config_confidential_information;
		}

		return self::$o_config;
	}

	/**
	 * Send mail
	 *
	 * @param string $to
	 * @param string $subject
	 * @param string $message
	 *
	 * @return string|void|\WellnessLiving\Wl\Mail\SendMailModel
	 */
	public function send_mail( $to = '', $subject = '', $message = '' ) {
		if ( empty( $to ) || empty( $subject ) || empty( $message ) ) {
			return;
		}
		try {
			$o_config = $this->get_config();

			$o_report                   = new \WellnessLiving\Wl\Mail\SendMailModel( $o_config );
			$o_report->s_business_mail  = SDK::get_setting( SDK::SETTINGS_BUSINESS_MAIL );
			$o_report->s_business_name  = SDK::get_setting( SDK::SETTINGS_BUSINESS_NAME );
			$o_report->s_business_reply = SDK::get_setting( SDK::SETTINGS_BUSINESS_REPLY );
			$o_report->s_campaign       = SDK::get_setting( SDK::SETTINGS_CAMPAIGN );
			$o_report->k_business       = SDK::get_setting( SDK::SETTINGS_BUSINESS_ID );
			$o_report->s_mail           = $to;
			$o_report->s_subject        = html_entity_decode( $subject, ENT_QUOTES | ENT_HTML401 );
			$o_report->z_html           = gzcompress( $message );

			$o_report->post();

			\Log::add_mail( $to, $subject, $message );

			return $o_report;
		} catch ( WlAssertException $e ) {
			error_log( $e->getMessage() );

			return $e->getMessage();
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() );

			return $e->getMessage();
		} catch ( WlDebugException $e ) {
			error_log( $e->getMessage() );

			return $e->getMessage();
		} catch ( WlUserException $e ) {
			error_log( $e->getMessage() );

			return $e->getMessage();
		} catch ( RsMailException $e ) {
			error_log( $e->getMessage() );

			return $e->getMessage();
		} catch ( ASmsException $e ) {
			error_log( $e->getMessage() );

			return $e->getMessage();
		}
	}

	/**
	 * Get Business Name
	 *
	 * @param $business_id
	 *
	 * @return bool|DataModel
	 */
	public function get_business_data( $business_id ) {
		try {
			$model             = new DataModel( $this->get_config() );
			$model->k_business = $business_id;
			$model->get();

			return $model;
		} catch ( WlAssertException $e ) {
			$this->errors[] = $e->getMessage();

			return false;
		} catch ( \Exception $e ) {
			$this->errors[] = $e->getMessage();

			return false;
		} catch ( WlDebugException $e ) {
			$this->errors[] = $e->getMessage();

			return false;
		} catch ( WlUserException $e ) {
			$this->errors[] = $e->getMessage();

			return false;
		}
	}

	/**
	 * Check an existing user by bid + email
	 *
	 * @param $bid
	 * @param $email
	 *
	 * @return bool
	 */
	public function check_existing_user_by_bid_email( $bid, $email ) {
		try {
			$o_config = $this->get_config( true );

			$o_use             = new \WellnessLiving\Wl\Login\Mail\MailUseModel( $o_config );
			$o_use->k_business = $bid;
			$o_use->text_mail  = $email;
			$o_use->get();

			return ( empty( $o_use->is_exists ) ) ? false : true;
		} catch ( WlAssertException $e ) {
			$this->errors[] = $e->getMessage();

			return false;
		} catch ( WlUserException $e ) {
			$this->errors[] = $e->getMessage();

			return false;
		}
	}
}
