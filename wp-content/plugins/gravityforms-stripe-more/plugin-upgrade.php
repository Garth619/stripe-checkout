<?php

/**
 * Class GFPMoreStripeUpgrade
 */
class GFPMoreStripeUpgrade {

	/**
	 *
	 *
	 * @since 1.7.9.0
	 *
	 * @param $version_info
	 */
	public static function set_version_info ( $version_info ) {
		if ( function_exists( 'set_site_transient' ) ) {
			set_site_transient( 'gfp_more_stripe_version', $version_info, 60 * 60 * 12 );
		}
		else {
			set_transient( 'gfp_more_stripe_version', $version_info, 60 * 60 * 12 );
		}
	}

	/**
	 *
	 *
	 * @since 1.7.9.0
	 *
	 * @param $plugin_path
	 * @param $plugin_slug
	 * @param $plugin_url
	 * @param $product
	 * @param $key
	 * @param $version
	 * @param $option
	 *
	 * @return mixed
	 */
	public static function check_update ( $plugin_path, $plugin_slug, $plugin_url, $product, $key, $version, $early_access, $option ) {

		$version_info = self::get_version_info( $product, $key, $version, $early_access, false );
		self::set_version_info( $version_info );


		if ( $version_info == false ) {
			return $option;
		}

		if ( empty( $option->response[$plugin_path] ) ) {
			$option->response[$plugin_path] = new stdClass();
		}

		if ( ! $version_info['is_valid_key'] || version_compare( $version, $version_info['version'], '>=' ) ) {
			unset( $option->response[$plugin_path] );
		}
		else {
			$option->response[$plugin_path]->url         = $plugin_url;
			$option->response[$plugin_path]->slug        = $plugin_slug;
			$option->response[$plugin_path]->package     = str_replace( "{KEY}", $key, $version_info['package'] );
			$option->response[$plugin_path]->new_version = $version_info['version'];
			$option->response[$plugin_path]->id          = '0';
		}

		return $option;

	}


	/**
	 *
	 *
	 * @since 1.7.9.0
	 *
	 * @param      $message
	 * @param bool $is_error
	 */
	public static function display_plugin_message ( $message, $is_error = false ) {

		$style = '';

		if ( $is_error ) {
			$style = 'style="background-color: #ffebe8;"';
		}

		echo '</tr><tr class="plugin-update-tr"><td colspan="5" class="plugin-update"><div class="update-message" ' . $style . '>' . $message . '</div></td>';
	}

	/**
	 *
	 *
	 * @since 1.7.9.0
	 *
	 * @param $plugin_name
	 * @param $plugin_title
	 * @param $version
	 * @param $message
	 */
	public static function display_upgrade_message ( $plugin_name, $plugin_title, $version, $message ) {
		$upgrade_message = $message . ' <a class="thickbox" title="' . $plugin_title . '" href="plugin-install.php?tab=plugin-information&plugin=' . $plugin_name . '&TB_iframe=true&width=640&height=808">' . sprintf( __( 'View version %s Details', 'gravityforms-stripe-more' ), $version ) . '</a>. ';
		self::display_plugin_message( $upgrade_message );
	}

	/**
	 * Displays current version details on Plugin's page
	 *
	 * @since 1.7.9.0
	 *
	 * @param $product
	 * @param $key
	 * @param $version
	 */
	public static function get_version_details ( $product, $key, $version, $early_access ) {

		$version_info = self::get_version_info( $product, $key, $version, $early_access, false );
		if ( ( $version_info == false ) || ( ! array_key_exists( 'version_details', $version_info ) || ( empty( $version_info['version_details'] ) ) ) ) {
			return WP_Error( 'no_version_info', __( 'An unexpected error occurred. Unable to find version details for this plugin. Please contact gravity+ Support.' ) );
		}

		$response = new stdClass();

		$response->name          = $version_info['version_details']['name'];
		$response->slug          = $version_info['version_details']['slug'];
		$response->version       = $version_info['version'];
		$response->download_link = str_replace( "{KEY}", $key, $version_info['package'] );
		$response->author        = $version_info['version_details']['author'];
		$response->requires      = $version_info['version_details']['requires'];
		$response->tested        = $version_info['version_details']['tested'];
		$response->last_updated  = $version_info['version_details']['last_updated'];
		$response->homepage      = $version_info['version_details']['homepage'];
		$response->sections      = $version_info['version_details']['sections'];

		return $response;

	}


	/**
	 *
	 *
	 * @since 1.7.9.0
	 *
	 * @param      $product
	 * @param      $key
	 * @param      $version
	 * @param bool $use_cache
	 *
	 * @return array|int|mixed
	 */
	public static function get_version_info ( $product, $key, $version, $early_access, $use_cache = true ) {

		$version_info = function_exists( 'get_site_transient' ) ? get_site_transient( 'gfp_more_stripe_version' ) : get_transient( 'gfp_more_stripe_version' );
		if ( ! $version_info || ! $use_cache || ( false == $version_info ) ) {
			$body               = "key=$key";
			$options            = array( 'method' => 'POST', 'timeout' => 3, 'body' => $body );
			$options['headers'] = array(
				'Content-Type'   => 'application/x-www-form-urlencoded; charset=' . get_option( 'blog_charset' ),
				'Content-Length' => strlen( $body ),
				'User-Agent'     => 'WordPress/' . get_bloginfo( 'version' ),
				'Referer'        => get_bloginfo( 'url' )
			);
			$url                = GFPMoreStripe::get_url() . '/' . self::get_remote_request_params( $product, $key, $version, $early_access );
			$raw_response       = wp_remote_post( $url, $options );

			if ( is_wp_error( $raw_response ) || ( 200 != wp_remote_retrieve_response_code( $raw_response ) ) ) {
				$version_info = false;
			}
			else {
				$response     = (array) unserialize( wp_remote_retrieve_body( $raw_response ) );
				$version_info = array(
					'is_valid_key'    => $response['is_valid_key'],
					'version'         => $response['version'],
					'package'         => urldecode( $response['package'] ),
					'version_details' => $response['version_details'],
				);
			}
			self::set_version_info( $version_info );
		}

		return $version_info;
	}

	/**
	 *
	 *
	 * @since 1.7.9.0
	 *
	 * @param $product
	 * @param $key
	 * @param $version
	 *
	 * @return string
	 */
	public static function get_remote_request_params ( $product, $key, $version, $early_access ) {
		global $wpdb;

		return sprintf( "%s&key=%s&v=%s&wp=%s&php=%s&mysql=%s&earlyaccess=%s", urlencode( $product ), urlencode( $key ), urlencode( $version ), urlencode( get_bloginfo( 'version' ) ), urlencode( phpversion() ), urlencode( $wpdb->db_version() ), urlencode( $early_access ) );
	}

}