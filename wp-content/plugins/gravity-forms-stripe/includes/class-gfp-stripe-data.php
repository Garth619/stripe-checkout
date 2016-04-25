<?php

/**
 * Class GFP_Stripe_Data
 */
class GFP_Stripe_Data {

	private static $_current_stripe_form_meta = array();
	private static $_current_stripe_feeds = array();
	private static $_current_stripe_transactions = array();

	public static function flush_current_stripe_form_meta() {
		self::$_current_stripe_form_meta = null;
		self::$_current_stripe_feeds     = null;
	}

	/**
	 * @return string
	 */
	public static function get_stripe_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'rg_stripe';
	}

	/**
	 * @return string
	 */
	public static function get_transaction_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'rg_stripe_transaction';
	}

	/**
	 *
	 * @since 0.1
	 */
	public static function update_table( $current_version ) {
		global $wpdb;
		$stripe_table             = self::get_stripe_table_name();
		$stripe_transaction_table = self::get_transaction_table_name();

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}

		require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );

		if ( ( ! empty( $current_version ) ) && ( version_compare( $current_version, '1.8.2', '<' ) ) ) {
			self::combine_legacy_feeds();
			self::rename_legacy_meta_column( $stripe_table, 'meta', 'rules' );
			self::rename_legacy_meta_column( $stripe_table, 'configurations', 'rules' );
			self::drop_index( $stripe_table, 'form_id' );
			self::drop_legacy_columns( $stripe_table, array( 'id', 'is_active' ) );
			self::drop_legacy_columns( $stripe_transaction_table, array( 'subscription_id', 'is_renewal' ) );
		}

		$sql = "CREATE TABLE $stripe_table (
	              form_id mediumint(8) unsigned not null,
	              form_settings longtext,
	              rules longtext,
	              PRIMARY KEY  (form_id)
	            )$charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE $stripe_transaction_table (
	              id int(10) unsigned not null auto_increment,
	              entry_id int(10) unsigned,
	              user_id int(10) unsigned,
	              transaction_type varchar(30) not null,
	              transaction_id varchar(50),
	              amount decimal(19,2),
	              currency varchar(5),
	              date_created datetime,
	              mode char(4) not null,
	              meta longtext,
	              PRIMARY KEY  (id),
	              KEY entry_id (entry_id),
	              KEY user_id ( user_id ),
	              KEY transaction_type (transaction_type)
	            )$charset_collate;";

		dbDelta( $sql );

		do_action( 'gfp_stripe_data_after_update_table' );

	}

	/**
	 * @param $entry_id
	 * @param $transaction_type
	 * @param $subscription_id
	 * @param $transaction_id
	 * @param $amount
	 *
	 * @return mixed
	 */
	public static function insert_transaction( $entry_id, $user_id = null, $transaction_type, $transaction_id, $amount, $currency, $mode, $meta = '' ) {

		GFP_Stripe::log_debug( 'Inserting transaction into transaction table'  );

		global $wpdb;
		$table_name = self::get_transaction_table_name();

		$sql = $wpdb->prepare( " INSERT INTO $table_name (entry_id, user_id, transaction_type, transaction_id, amount, currency, date_created, mode, meta)
                                values(%d, %d, %s, %s, %f, %s, utc_timestamp(), %s, %s)", $entry_id, $user_id, $transaction_type, $transaction_id, $amount, $currency, $mode, json_encode( $meta ) );
		$wpdb->query( $sql );
		$id = $wpdb->insert_id;

		do_action( 'gform_post_payment_transaction', $id, $entry_id, $transaction_type, $transaction_id, $amount, false );

		return $id;
	}

	public static function update_transaction( $entry_id, $property_name, $property_value ) {
		global $wpdb;
		$table_name = self::get_transaction_table_name();

		$result = $wpdb->update( $table_name, array( $property_name => $property_value ), array( 'entry_id' => $entry_id ) );
	}

	/**
	 * @return mixed
	 */
	public static function get_all_feeds() {
		global $wpdb;

		$table_name = self::get_stripe_table_name();
		$sql        = "SELECT * FROM $table_name";
		$results    = $wpdb->get_results( $sql, ARRAY_A );

		$feeds = array();

		foreach ( $results as $result ) {
			$stripe_form_meta = self::process_stripe_form_meta( $result );
			if ( ( ! empty( $stripe_form_meta ) ) && ( ! empty( $stripe_form_meta[ 'rules' ] ) ) ) {
				$feeds[ ] = $stripe_form_meta;
			}
		}

		return $feeds;
	}

	public static function get_stripe_form_meta( $form_id ) {
		global $wpdb;

		if ( isset( self::$_current_stripe_form_meta[ $form_id ] ) ) {
			return self::$_current_stripe_form_meta[ $form_id ];
		}

		$table_name       = self::get_stripe_table_name();
		$stripe_form_meta = $wpdb->get_row( $wpdb->prepare( "SELECT form_settings, rules FROM {$table_name} WHERE form_id=%d", $form_id ), ARRAY_A );

		$stripe_form_meta = self::process_stripe_form_meta( $stripe_form_meta );

		self::$_current_stripe_form_meta[ $form_id ] = $stripe_form_meta;

		return $stripe_form_meta;
	}

	public static function get_all_form_ids() {
		global $wpdb;
		$table   = GFFormsModel::get_form_table_name();
		$sql     = "SELECT id from $table";
		$results = $wpdb->get_col( $sql );

		return $results;
	}

	/**
	 * @param      $form_id
	 * @param bool $only_active
	 *
	 * @return array
	 */
	public static function get_feed_by_form( $form_id, $only_active = false ) {
		global $wpdb;

		if ( isset( self::$_current_stripe_feeds[ $form_id ] ) ) {
			$feeds = self::$_current_stripe_feeds[ $form_id ];
		} else {
			$table_name      = self::get_stripe_table_name();
			$form_table_name = RGFormsModel::get_form_table_name();

			$sql = $wpdb->prepare( "SELECT s.form_id, s.rules, f.title as form_title
			                FROM $table_name s
			                INNER JOIN $form_table_name f ON s.form_id = f.id
			                WHERE form_id=%d", $form_id );

			$results = $wpdb->get_results( $sql, ARRAY_A );

			if ( empty( $results ) ) {
				return array();
			}

			$rules = GFFormsModel::unserialize( $results[ 0 ][ 'rules' ] );
			$feeds = array();

			foreach ( $rules as $rule ) {
				$feed_items = array(
					'id'         => $rule[ 'id' ],
					'form_id'    => $results[ 0 ][ 'form_id' ],
					'form_title' => $results[ 0 ][ 'form_title' ],
					'meta'       => $rule
				);
				if ( isset( $rule[ 'is_active' ] ) ) {
					$feed_items[ 'is_active' ] = $rule[ 'is_active' ];
				}
				$feeds[ ] = $feed_items;
			}
			self::$_current_stripe_feeds[ $form_id ] = $feeds;
		}


		if ( $only_active ) {
			$active_feeds = array();
			foreach ( $feeds as $feed ) {
				if ( ( isset( $feed[ 'is_active' ] ) ) && ( true == $feed[ 'is_active' ] ) ) {
					$active_feeds[ ] = $feed;
				}
			}
			$feeds = $active_feeds;
		}


		return $feeds;
	}

	/**
	 * @param $feed_id
	 *
	 * @return array
	 */
	public static function get_feed( $form_id, $feed_id ) {
		$form_feeds = self::get_feed_by_form( $form_id );

		foreach ( $form_feeds as $form_feed ) {
			if ( ( $form_feed[ 'id' ] == $feed_id ) || ( ! empty( $form_feed[ 'meta' ][ 'old_id' ] ) && $form_feed[ 'meta' ][ 'old_id' ] == $feed_id ) ) {
				$feed = $form_feed;
				break;
			}
		}

		return $feed;
	}

	public static function get_transaction_by( $type, $value ) {
		global $wpdb;
		$table_name  = self::get_transaction_table_name();
		$transaction = null;

		if ( 'entry' == $type || 'user_id' == $type || 'transaction_id' == $type ) {
			if ( isset( self::$_current_stripe_transactions[ $value ] ) ) {
				$transaction = self::$_current_stripe_transactions[ $value ];
			} else {
				switch ( $type ) {
					case 'entry':
						$sql                                          = $wpdb->prepare( "SELECT * FROM  {$table_name}
						WHERE entry_id=%d", $value );
						$transaction                                  = $wpdb->get_row( $sql, ARRAY_A );
						if ( ! empty( $transaction ) ) {
							$transaction[ 'meta' ] = GFFormsModel::unserialize( $transaction[ 'meta' ] );
						}
						self::$_current_stripe_transactions[ $value ] = $transaction;
						break;
					case 'user_id':
						$sql         = $wpdb->prepare( "SELECT * FROM  {$table_name}
						WHERE user_id=%d", $value );
						$transaction = $wpdb->get_results( $sql, ARRAY_A );
						foreach ( $transaction as $key => $t ) {
							$transaction[ $key ][ 'meta' ] = GFFormsModel::unserialize( $t[ 'meta' ] );
						}
						self::$_current_stripe_transactions[ $value ] = $transaction;
						break;
					case 'transaction_id':
						$sql                                          = $wpdb->prepare( "SELECT * FROM  {$table_name}
						WHERE transaction_id=%s", $value );
						$transaction                                  = $wpdb->get_row( $sql, ARRAY_A );
						if ( ! empty( $transaction ) ) {
							$transaction[ 'meta' ] = GFFormsModel::unserialize( $transaction[ 'meta' ] );
						}
						self::$_current_stripe_transactions[ $value ] = $transaction;
						break;
				}
			}
		}

		return $transaction;
	}

	private static function process_stripe_form_meta( $meta ) {
		$meta[ 'rules' ] = GFFormsModel::unserialize( $meta[ 'rules' ] );

		if ( ! $meta[ 'rules' ] ) {
			return null;
		}

		$meta[ 'form_settings' ] = GFFormsModel::unserialize( $meta[ 'form_settings' ] );

		return $meta;
	}

	public static function save_feeds( $form_id, $feeds ) {
		return self::update_stripe_form_meta( $form_id, $feeds, 'rules' );
	}


	public static function update_stripe_form_meta( $form_id, $meta, $meta_name ) {
		global $wpdb;
		$stripe_table_name = self::get_stripe_table_name();
		$meta              = json_encode( $meta );

		if ( intval( $wpdb->get_var( $wpdb->prepare( "SELECT count(0) FROM $stripe_table_name WHERE form_id=%d", $form_id ) ) ) > 0 ) {
			$result = $wpdb->query( $wpdb->prepare( "UPDATE $stripe_table_name SET $meta_name=%s WHERE form_id=%d", $meta, $form_id ) );
		} else {
			$result = $wpdb->query( $wpdb->prepare( "INSERT INTO $stripe_table_name(form_id, $meta_name) VALUES(%d, %s)", $form_id, $meta ) );
		}

		self::$_current_stripe_form_meta[ $form_id ] = null;
		self::$_current_stripe_feeds[ $form_id ]     = null;

		return $result;
	}

	public static function delete_stripe_form_meta( $form_id ) {
		global $wpdb;
		$stripe_table_name = self::get_stripe_table_name();

		$wpdb->query( $wpdb->prepare( "DELETE FROM $stripe_table_name WHERE form_id=%s", $form_id ) );

		self::flush_current_stripe_form_meta();
	}

	/**
	 * @param $id
	 */
	public static function delete_feed( $feed_id, $form_id ) {

		if ( ! $form_id ) {
			return false;
		}

		$form_meta = self::get_stripe_form_meta( $form_id );
		unset( $form_meta[ 'rules' ][ $feed_id ] );

		self::flush_current_stripe_form_meta();

		return self::save_feeds( $form_id, $form_meta[ 'rules' ] );
	}

	/**
	 *
	 */
	public static function drop_tables() {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS " . self::get_stripe_table_name() );
		$wpdb->query( "DROP TABLE IF EXISTS " . self::get_transaction_table_name() );
	}

	/*--------- LEGACY ---------*/

	public static function update_legacy_feed( $id, $form_id, $is_active, $setting ) {
		global $wpdb;
		$table_name = self::get_stripe_table_name();
		$setting    = json_encode( $setting );

		$wpdb->update( $table_name, array(
			"form_id"   => $form_id,
			"is_active" => $is_active,
			"meta"      => $setting
		), array( "id" => $id ), array( "%d", "%d", "%s" ), array( "%d" ) );

		return $id;
	}

	public static function delete_legacy_feed( $id ) {
		global $wpdb;
		$table_name = self::get_stripe_table_name();
		$wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE id=%s", $id ) );
	}

	private static function combine_legacy_feeds() {

		$form_ids = self::get_all_form_ids();

		foreach ( $form_ids as $form_id ) {
			$stripe_form_meta = self::get_legacy_stripe_form_meta( $form_id );
			if ( empty( $stripe_form_meta ) ) {
				continue;
			} else {
				$rules    = array();
				$id_index = 1;
				foreach ( $stripe_form_meta as $meta_row ) {
					$id                                = "{$form_id}.{$id_index}";
					$meta_row[ 'meta' ][ 'is_active' ] = $meta_row[ 'is_active' ];
					$meta_row[ 'meta' ][ 'old_id' ]    = $meta_row[ 'id' ];
					$meta_row[ 'meta' ][ 'id' ]        = $id;
					$rules[ $id ]                      = $meta_row[ 'meta' ];
					$id_index ++;
				}
				self::update_legacy_feed( $stripe_form_meta[ 0 ][ 'id' ], $stripe_form_meta[ 0 ][ 'form_id' ], $stripe_form_meta[ 0 ][ 'is_active' ], $rules );
				if ( 1 < count( $stripe_form_meta ) ) {
					foreach ( $stripe_form_meta as $key => $meta_row ) {
						if ( 0 !== $key ) {
							self::delete_legacy_feed( $meta_row[ 'id' ] );
						}
					}
				}
			}
		}

	}

	private static function get_legacy_stripe_form_meta( $form_id ) {
		global $wpdb;

		$table   = self::get_stripe_table_name();
		$sql     = $wpdb->prepare( "SELECT * FROM $table WHERE form_id=%d", $form_id );
		$results = $wpdb->get_results( $sql, ARRAY_A );
		if ( empty( $results ) ) {
			$results = array();
		} else {
			if ( array_key_exists( 'meta', $results[ 0 ] ) ) {

				//Deserializing meta
				$count = sizeof( $results );
				for ( $i = 0; $i < $count; $i ++ ) {
					$results[ $i ][ 'meta' ] = maybe_unserialize( $results[ $i ][ 'meta' ] );
				}

				return $results;
			} else {
				$results = array();
			}
		}

		return $results;
	}

	private static function rename_legacy_meta_column( $table, $old_name, $new_name ) {

		global $wpdb;

		if ( self::has_column( $table, $old_name ) ) {
			$sql    = "ALTER TABLE $table CHANGE $old_name $new_name longtext";
			$result = $wpdb->query( $sql );
		}

	}

	private static function drop_index( $table, $index ) {
		global $wpdb;
		$has_index = $wpdb->get_var( "SHOW INDEX FROM {$table} WHERE Key_name='{$index}'" );
		if ( $has_index ) {
			$wpdb->query( "DROP INDEX {$index} ON {$table}" );
		}
	}

	private static function drop_legacy_columns( $table, $columns ) {
		global $wpdb;

		$has_columns = false;

		$drop = '';
		foreach ( $columns as $key => $column ) {
			if ( self::has_column( $table, $column ) ) {
				$has_columns = true;
				if ( 0 == $key ) {
					$drop = "DROP {$column}";
				} else {
					$drop .= ", DROP {$column}";
				}
			}
		}
		if ( $has_columns ) {
			$sql    = "ALTER TABLE $table $drop";
			$result = $wpdb->query( $sql );
		}
	}

	private static function has_column( $table, $column ) {
		global $wpdb;

		$sql        = "SHOW COLUMNS FROM $table LIKE '$column'";
		$has_column = $wpdb->get_var( $sql );

		return $has_column;
	}

}