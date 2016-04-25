<?php
/**
 * @package   Press_Plus
 * @copyright 2015 press+
 * @license   GPL-2.0+
 * @since     1.0.0
 */

/**
 * DB base class
 *
 * Derived from Easy Digital Downloads, Copyright 2014 Pippin Williamson
 * Easy Digital Downloads is distributed under the terms of the GNU GPL 2.0
 *
 * @since       1.0.0
 */
abstract class PPP_DB {

	/**
	 * Plugin name
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $plugin_slug = '';

	/**
	 * The name of our database table
	 *
	 * @since   1.0.0
	 */
	public $table_name;

	/**
	 * The version of our database table
	 *
	 * @since   1.0.0
	 */
	public $version;

	/**
	 * The name of the primary column
	 *
	 * @since   1.0.0
	 */
	public $primary_key;

	/**
	 * Get things started
	 *
	 * @since   1.0.0
	 */
	public function __construct( $args ) {
		global $wpdb;

		$this->plugin_slug = $args[ 'plugin_slug' ];
		$this->table_name  = $wpdb->prefix . $args[ 'table_name' ];
		$this->primary_key = $args[ 'primary_key' ];
		$this->version     = $args[ 'version' ];
	}

	/**
	 * Whitelist of columns
	 *
	 * @since   1.0.0
	 *
	 * @return  array
	 */
	public function get_columns() {
		return array();
	}

	/**
	 * Default column values
	 *
	 * @since   1.0.0
	 *
	 * @return  array
	 */
	public function get_column_defaults() {
		return array();
	}

	/**
	 * Retrieve a row by the primary key
	 *
	 * @since   1.0.0
	 *
	 * @return  object
	 */
	public function get( $row_id ) {
		global $wpdb;

		return $wpdb->get_row( "SELECT * FROM $this->table_name WHERE $this->primary_key = $row_id LIMIT 1;" );
	}

	/**
	 * Retrieve a row by a specific column / value
	 *
	 * @since   1.0.0
	 *
	 * @return  object
	 */
	public function get_by( $column, $row_id ) {
		global $wpdb;

		return $wpdb->get_row( "SELECT * FROM $this->table_name WHERE $column = '$row_id' LIMIT 1;" );
	}

	/**
	 * Retrieve a specific column's value by the primary key
	 *
	 * @since   1.0.0
	 *
	 * @return  string
	 */
	public function get_column( $column, $row_id ) {
		global $wpdb;

		return $wpdb->get_var( "SELECT $column FROM $this->table_name WHERE $this->primary_key = $row_id LIMIT 1;" );
	}

	/**
	 * Retrieve a specific column's value by the the specified column / value
	 *
	 * @since   1.0.0
	 *
	 * @return  string
	 */
	public function get_column_by( $column, $column_where, $column_value ) {
		global $wpdb;

		return $wpdb->get_var( "SELECT $column FROM $this->table_name WHERE $column_where = '$column_value' LIMIT 1;" );
	}

	/**
	 * Insert a new row
	 *
	 * @since   1.0.0
	 *
	 * @return  int
	 */
	public function insert( $data, $type = '' ) {
		global $wpdb;

		// Set default values
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		do_action( $this->plugin_slug . '_pre_insert_' . $type, $data );

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		$wpdb->insert( $this->table_name, $data, $column_formats );

		do_action( $this->plugin_slug . '_post_insert_' . $type, $wpdb->insert_id, $data );

		return $wpdb->insert_id;
	}

	/**
	 * Update a row
	 *
	 * @since   1.0.0
	 *
	 * @return  bool
	 */
	public function update( $row_id, $data = array(), $where = '' ) {

		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );

		if ( empty( $row_id ) ) {
			return false;
		}

		if ( empty( $where ) ) {
			$where = $this->primary_key;
		}

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		if ( false === $wpdb->update( $this->table_name, $data, array( $where => $row_id ), $column_formats ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete a row identified by the primary key
	 *
	 * @since   1.0.0
	 *
	 * @return  bool
	 */
	public function delete( $row_id = 0 ) {

		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );

		if ( empty( $row_id ) ) {
			return false;
		}

		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE $this->primary_key = %d", $row_id ) ) ) {
			return false;
		}

		return true;
	}

}