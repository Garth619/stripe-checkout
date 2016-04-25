<?php
/**
 * @wordpress-plugin
 * Plugin Name: Gravity Forms + Stripe
 * Plugin URI: https://gravityplus.pro/gravity-forms-stripe
 * Description: Use Stripe to process credit card payments on your site, easily and securely, with Gravity Forms
 * Version: 1.9.2.7
 * Author: gravity+
 * Author URI: https://gravityplus.pro
 * Text Domain: gravity-forms-stripe
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package   GFP_Stripe
 * @version   1.9.2.7
 * @author    gravity+ <support@gravityplus.pro>
 * @license   GPL-2.0+
 * @link      https://gravityplus.pro
 * @copyright 2012-2016 gravity+
 *
 * last updated: March 15, 2016
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

//Setup Constants

/**
 *
 */
define( 'GFP_STRIPE_FILE', __FILE__ );

/**
 *
 */
define( 'GFP_STRIPE_PATH', plugin_dir_path( __FILE__ ) );

define( 'GFP_STRIPE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Unique identifier
 *
 * @since 1.9.2.1
 */
define( 'GFP_STRIPE_SLUG', plugin_basename( dirname( __FILE__ ) ) );

//Let's get it started! Load all of the necessary class files for the plugin
require_once( 'includes/class-gfp-stripe-loader.php' );
GFP_Stripe_Loader::load();

require_once( 'includes/gf-utility-functions.php' );

new GFP_Stripe();