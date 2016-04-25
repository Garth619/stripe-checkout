<?php
/**
 * @package   GFP_More_Stripe
 * @copyright 2014-2015 press+
 * @license   GPL-2.0+
 * @since     1.9.2.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GFP_Stripe_DB_Events Class
 *
 * This class is for interacting with Stripe events DB
 *
 * @since 1.9.2.1
 */
class GFP_Stripe_DB_Events extends PPP_DB {

	/**
	 * Get things started
	 *
	 * @since   1.9.2.1
	 */
	public function __construct( $args ) {

		parent::__construct( $args );

		add_action( 'init', array( $this, 'init' ) );

		add_action( 'gfp_more_stripe_uninstall', array( $this, 'gfp_more_stripe_uninstall' ) );

	}

	/**
	 * Add hook for updating table
	 *
	 * @since 1.9.2.1
	 */
	public function init() {

		add_action( 'gfp_more_stripe_before_update_version', array( $this, 'gfp_more_stripe_before_update_version' ) );

	}

	public function gfp_more_stripe_uninstall() {

		$this->drop_table();

	}

	/**
	 * Create table when version is updated
	 *
	 * @since 1.9.2.1
	 */
	public function gfp_more_stripe_before_update_version() {

		$this->create_table();

	}

	/**
	 * Get columns and formats
	 *
	 * @since   1.9.2.1
	 */
	public function get_columns() {

		return array(
			'id'           => '%d',
			'event_id'     => '%s',
			'date_created' => '%s',
		);

	}

	/**
	 * Get default column values
	 *
	 * @since   1.9.2.1
	 */
	public function get_column_defaults() {

		return array(
			'id'           => 0,
			'event_id'     => '',
			'date_created' => date( 'Y-m-d H:i:s' ),
		);

	}

	/**
	 * Add an event
	 *
	 * @since   1.9.2.1
	 *
	 * @param array $data
	 *
	 * @return bool|int
	 */
	public function add( $data = array() ) {

		$args = $data;

		if ( empty( $args[ 'event_id' ] ) ) {
			return false;
		}

		$event = $this->get_by( 'event_id', $args[ 'event_id' ] );

		if ( $event ) {

			return $event->id;

		} else {

			return $this->insert( $args, 'event' );

		}

	}

	/**
	 * Checks if an event exists by event ID
	 *
	 * @since   1.9.2.1
	 *
	 * @param string $event_id
	 *
	 * @return bool
	 */
	public function exists( $event_id = '' ) {

		return (bool) $this->get_column_by( 'event_id', 'event_id', $event_id );

	}

	/**
	 * Retrieve events from the database
	 *
	 * @since   1.9.2.1
	 *
	 * @param array $args
	 *
	 * @return bool|mixed
	 */
	public function get_events( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'number'  => 20,
			'offset'  => 0,
			'orderby' => 'date_created',
			'order'   => 'DESC'
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args[ 'number' ] < 1 ) {
			$args[ 'number' ] = 999999999999;
		}

		$where = '';

		// specific events
		if ( ! empty( $args[ 'id' ] ) ) {

			if ( is_array( $args[ 'id' ] ) ) {
				$ids = implode( ',', $args[ 'id' ] );
			} else {
				$ids = intval( $args[ 'id' ] );
			}

			$where .= "WHERE `event_id` IN( {$ids} ) ";

		}

		// Events created for a specific date or in a date range
		if ( ! empty( $args[ 'date' ] ) ) {

			if ( is_array( $args[ 'date' ] ) ) {

				if ( ! empty( $args[ 'date' ][ 'start' ] ) ) {

					$start = date( 'Y-m-d H:i:s', strtotime( $args[ 'date' ][ 'start' ] ) );

					if ( ! empty( $where ) ) {

						$where .= " AND `date_created` >= '{$start}'";

					} else {

						$where .= " WHERE `date_created` >= '{$start}'";

					}

				}

				if ( ! empty( $args[ 'date' ][ 'end' ] ) ) {

					$end = date( 'Y-m-d H:i:s', strtotime( $args[ 'date' ][ 'end' ] ) );

					if ( ! empty( $where ) ) {

						$where .= " AND `date_created` <= '{$end}'";

					} else {

						$where .= " WHERE `date_created` <= '{$end}'";

					}

				}

			} else {

				$year  = date( 'Y', strtotime( $args[ 'date' ] ) );
				$month = date( 'm', strtotime( $args[ 'date' ] ) );
				$day   = date( 'd', strtotime( $args[ 'date' ] ) );

				if ( empty( $where ) ) {
					$where .= " WHERE";
				} else {
					$where .= " AND";
				}

				$where .= " $year = YEAR ( date_created ) AND $month = MONTH ( date_created ) AND $day = DAY ( date_created )";
			}

		}

		$args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? 'date_created' : $args['orderby'];

		$cache_key = md5( $this->table_name . serialize( $args ) );

		$events = wp_cache_get( $cache_key, 'gfp_stripe' );

		if ( $events === false ) {

			$events = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM  $this->table_name $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d,%d;", absint( $args[ 'offset' ] ), absint( $args[ 'number' ] ) ) );

				wp_cache_set( $cache_key, $events, 'gfp_stripe', 3600 );

		}

		return $events;

	}

	/**
	 * Count the total number of transactions in the database
	 *
	 * @since   1.9.2.1
	 */
	public function count( $args = array() ) {

		global $wpdb;

		$where = '';

		if ( ! empty( $args[ 'date' ] ) ) {

			if ( is_array( $args[ 'date' ] ) ) {

				$start = date( 'Y-m-d H:i:s', strtotime( $args[ 'date' ][ 'start' ] ) );
				$end   = date( 'Y-m-d H:i:s', strtotime( $args[ 'date' ][ 'end' ] ) );

				if ( empty( $where ) ) {

					$where .= " WHERE `date_created` >= '{$start}' AND `date_created` <= '{$end}'";

				} else {

					$where .= " AND `date_created` >= '{$start}' AND `date_created` <= '{$end}'";

				}

			} else {

				$year  = date( 'Y', strtotime( $args[ 'date' ] ) );
				$month = date( 'm', strtotime( $args[ 'date' ] ) );
				$day   = date( 'd', strtotime( $args[ 'date' ] ) );

				if ( empty( $where ) ) {
					$where .= " WHERE";
				} else {
					$where .= " AND";
				}

				$where .= " $year = YEAR ( date_created ) AND $month = MONTH ( date_created ) AND $day = DAY ( date_created )";
			}

		}


		$cache_key = md5( $this->plugin_slug . '_events_count' . serialize( $args ) );

		$count = wp_cache_get( $cache_key, 'gfp_stripe' );

		if ( $count === false ) {
			$count = $wpdb->get_var( "SELECT COUNT($this->primary_key) FROM " . $this->table_name . "{$where};" );
			wp_cache_set( $cache_key, $count, 'gfp_stripe', 3600 );
		}

		return absint( $count );

	}

	/**
	 * Create the table
	 *
	 * @since   1.9.2.1
	 */
	public function create_table() {

		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}

		$sql = "CREATE TABLE {$this->table_name} (
		id int(20) unsigned NOT NULL AUTO_INCREMENT,
		event_id varchar(191) NOT NULL,
		date_created datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY event_id (event_id)
		) $charset_collate;";

		dbDelta( $sql );

		update_option( str_replace( $wpdb->prefix, '', $this->table_name ) . '_db_version', $this->version );
	}
}