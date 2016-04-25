<?php

/**
 * Adapted from WP Metadata API UI
 */
class GFP_Stripe_Loader {

	private static $_autoload_classes = array(
		'GFP_Stripe'            => 'class-gfp-stripe.php',
		'GFP_Stripe_Data'       => 'class-gfp-stripe-data.php',
		'GFP_Stripe_List_Table' => 'class-gfp-stripe-list-table.php',
	);

	static function load () {
		spl_autoload_register( array( __CLASS__, '_autoloader' ) );
	}

	/**
	 * @param string $class_name
	 * @param string $class_filepath
	 *
	 * @return bool Return true if it was registered, false if not.
	 */
	static function register_autoload_class ( $class_name, $class_filepath ) {

		if ( ! isset( self::$_autoload_classes[$class_name] ) ) {

			self::$_autoload_classes[$class_name] = $class_filepath;

			return true;

		}

		return false;

	}

	/**
	 * @param string $class_name
	 */
	static function _autoloader ( $class_name ) {

		if ( isset( self::$_autoload_classes[$class_name] ) ) {

			$filepath = self::$_autoload_classes[$class_name];

			/**
			 * @todo This needs to be made to work for Windows...
			 */
			if ( '/' == $filepath[0] ) {

				require_once( $filepath );

			}
			else {

				require_once( dirname( __FILE__ ) . "/{$filepath}" );

			}

		}

	}
}