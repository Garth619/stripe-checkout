<?php

GFForms::include_feed_addon_framework();

if ( class_exists( 'GF_Field' ) ) {
	require_once( 'class-gf-field-coupon.php' );
}

class GFCoupons extends GFFeedAddOn {

	protected $_version = GF_COUPONS_VERSION;
	protected $_min_gravityforms_version = '1.9.5';
	protected $_slug = 'gravityformscoupons';
	protected $_path = 'gravityformscoupons/coupons.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Coupons Add-On';
	protected $_short_title = 'Coupons';
	protected $_coupon_feed_id = '';

	// Members plugin integration
	protected $_capabilities = array(
		'gravityforms_coupons',
		'gravityforms_coupons_uninstall',
		'gravityforms_coupons_plugin_page',
	);

	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_coupons';
	protected $_capabilities_form_settings = 'gravityforms_coupons';
	protected $_capabilities_uninstall = 'gravityforms_coupons_uninstall';
	protected $_capabilities_plugin_page = 'gravityforms_coupons_plugin_page';
	protected $_enable_rg_autoupgrade = true;

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GFCoupons
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFCoupons();
		}

		return self::$_instance;
	}

	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @return array
	 */
	public function scripts() {
		$min     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';
		$scripts = array(
			array(
				'handle'  => 'gform_coupon_script',
				'src'     => $this->get_base_url() . "/js/coupons{$min}.js",
				'version' => $this->_version,
				'deps'    => array( 'jquery', 'gform_json', 'gform_gravityforms' ),
				'enqueue' => array( array( 'field_types' => array( 'coupon' ) ) ),
				'strings' => array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) )
			),
			array(
				'handle'  => 'gform_form_admin',
				'enqueue' => array( array( 'admin_page' => array( 'plugin_page' ) ) )
			),
			array(
				'handle'  => 'gform_gravityforms',
				'enqueue' => array( array( 'admin_page' => array( 'plugin_page' ) ) )
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}

	/**
	 * Return the stylesheets which should be enqueued.
	 *
	 * @return array
	 */
	public function styles() {
		$min    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';
		$styles = array(
			array(
				'handle'  => 'gform_coupon_style',
				'src'     => $this->get_base_url() . "/css/gcoupons{$min}.css",
				'version' => $this->_version,
				'enqueue' => array( array( 'field_types' => array( 'coupon' ) ) )
			),
			array(
				'handle'  => 'gform_admin',
				'src'     => GFCommon::get_base_url() . "/css/admin{$min}.css",
				'version' => $this->_version,
				'enqueue' => array( array( 'admin_page' => array( 'plugin_page' ) ) )
			),
		);

		return array_merge( parent::styles(), $styles );
	}


	// # UPDATE PRODUCT INFO -------------------------------------------------------------------------------------------

	/**
	 * Plugin starting point. Handles hooks and loading of language files.
	 */
	public function init() {

		parent::init();
		add_filter( 'gform_product_info', array( $this, 'add_discounts' ), 5, 3 );

	}

	/**
	 * Maybe add coupon discounts to the product info array.
	 *
	 * @param array $product_info Contains the selected product and shipping details.
	 * @param array $form The form object currently being processed.
	 * @param array $entry The entry object currently being processed.
	 *
	 * @return array
	 */
	public function add_discounts( $product_info, $form, $entry ) {

		$coupon_codes = $this->get_submitted_coupon_codes( $form, $entry );
		if ( ! $coupon_codes ) {
			return $product_info;
		}

		$total = GFCommon::get_total( $product_info );

		$coupons   = $this->get_coupons_by_codes( $coupon_codes, $form );
		$discounts = $this->get_discounts( $coupons, $total, $discount_total );

		foreach ( $coupons as $coupon ) {

			$price                                       = GFCommon::to_number( $discounts[ $coupon['code'] ]['discount'] );
			$product_info['products'][ $coupon['code'] ] = array(
				'name'     => $coupon['name'],
				'price'    => - $price,
				'quantity' => 1,
				'options'  => array(
					array(
						'option_name'  => $coupon['name'],
						'option_label' => esc_html__( 'Coupon Code:', 'gravityformscoupons' ) . ' ' . $coupon['code'],
						'price'        => 0,
					),
				)
			);
		}

		return $product_info;
	}


	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Determines if any coupons were used so their feeds can be processed.
	 *
	 * @param array $entry The entry object currently being processed.
	 * @param array $form The form object currently being processed.
	 *
	 * @return array
	 */
	public function maybe_process_feed( $entry, $form ) {

		if ( $entry['status'] == 'spam' ) {
			$this->log_debug( __METHOD__ . '(): Entry #' . $entry['id'] . ' is marked as spam.' );

			return $entry;
		}

		$coupon_codes = $this->get_submitted_coupon_codes( $form, $entry );
		if ( ! $coupon_codes ) {
			$this->log_debug( __METHOD__ . "(): No coupons submitted for entry #{$entry['id']}." );

			return $entry;
		}

		$coupons = $this->get_coupons_by_codes( $coupon_codes, $form );
		if ( is_array( $coupons ) ) {
			$processed_feeds = array();
			foreach ( $coupons as $coupon ) {
				$feed = $this->get_config( $form, $coupon['code'] );
				$this->log_debug( __METHOD__ . "(): Starting to process feed (#{$feed['id']} - {$feed['meta']['couponName']}) for entry #{$entry['id']}." );
				$this->process_feed( $feed, $entry, $form );
				$processed_feeds[] = $feed['id'];
			}

			//Saving processed feeds
			if ( ! empty( $processed_feeds ) ) {
				$meta = gform_get_meta( $entry['id'], 'processed_feeds' );
				if ( empty( $meta ) ) {
					$meta = array();
				}

				$meta[ $this->_slug ] = $processed_feeds;

				gform_update_meta( $entry['id'], 'processed_feeds', $meta );
			}
		}

		return $entry;
	}

	/**
	 * Handles updating the coupon usageCount.
	 *
	 * @param array $feed The coupon feed currently being processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form The form object currently being processed.
	 */
	public function process_feed( $feed, $entry, $form ) {
		$meta               = $feed['meta'];
		$starting_count     = empty( $meta['usageCount'] ) ? 0 : $meta['usageCount'];
		$meta['usageCount'] = $starting_count + 1;

		$this->update_feed_meta( $feed['id'], $meta );
		$this->log_debug( __METHOD__ . "(): Updating usage count from {$starting_count} to {$meta['usageCount']}." );
	}


	// # AJAX FUNCTIONS ------------------------------------------------------------------------------------------------

	/**
	 * Initialize the AJAX hooks.
	 */
	public function init_ajax() {

		parent::init_ajax();
		add_action( 'wp_ajax_gf_apply_coupon_code', array( $this, 'apply_coupon_code' ) );
		add_action( 'wp_ajax_nopriv_gf_apply_coupon_code', array( $this, 'apply_coupon_code' ) );

	}

	/**
	 * Handler for the gf_apply_coupon_code AJAX request.
	 * Returns the json encoded result for processing by coupon.js.
	 */
	public function apply_coupon_code() {

		$coupon_code    = strtoupper( $_POST['couponCode'] );
		$result         = '';
		$invalid_reason = '';
		if ( empty( $coupon_code ) ) {
			$invalid_reason = esc_html__( 'You must enter a value for coupon code.', 'gravityformscoupons' );
			$result         = array( 'is_valid' => false, 'invalid_reason' => $invalid_reason );
			die( GFCommon::json_encode( $result ) );
		}

		$form_id               = intval( $_POST['formId'] );
		$existing_coupon_codes = $_POST['existing_coupons'];
		$total                 = $_POST['total'];

		//fields meta
		$form = RGFormsModel::get_form_meta( $form_id );
		$feed = $this->get_config( $form, $coupon_code );

		if ( ! $feed || ! $feed['is_active'] ) {
			$invalid_reason = esc_html__( 'Invalid coupon.', 'gravityformscoupons' );
			$result         = array( 'is_valid' => false, 'invalid_reason' => $invalid_reason );
			die( GFCommon::json_encode( $result ) );
		}

		$can_apply = $this->can_apply_coupon( $coupon_code, $existing_coupon_codes, $feed, $invalid_reason, $form );

		if ( $can_apply ) {
			$coupon_codes = empty( $existing_coupon_codes ) ? $coupon_code : $coupon_code . ',' . $existing_coupon_codes;
			$coupons      = $this->get_coupons_by_codes( explode( ',', $coupon_codes ), $form );

			$coupons = $this->sort_coupons( $coupons );
			foreach ( $coupons as $c ) {
				$couponss[ $c['code'] ] = array(
					'amount'      => $c['amount'],
					'name'        => $c['name'],
					'type'        => $c['type'],
					'code'        => $c['code'],
					'can_stack'   => $c['can_stack'],
					'usage_count' => $c['usage_count'],
				);
			}

			$result = array(
				'is_valid'       => $can_apply,
				'coupons'        => $couponss,
				'invalid_reason' => $invalid_reason,
				'coupon_code'    => $coupon_code,
			);

			die( GFCommon::json_encode( $result ) );
		} else {
			$result = array( 'is_valid' => false, 'invalid_reason' => $invalid_reason );
			die( GFCommon::json_encode( $result ) );
		}

	}


	// # ADMIN FUNCTIONS -----------------------------------------------------------------------------------------------

	/**
	 * Initialize the admin specific hooks.
	 */
	public function init_admin() {

		parent::init_admin();
		add_action( 'gform_editor_js_set_default_values', array( $this, 'set_defaults' ) );
		add_filter( $this->_slug . '_feed_actions', array( $this, 'set_action_links' ), 10, 3 );

		// don't duplicate feeds with the form, feed duplication not currently supported.
		remove_action( 'gform_post_form_duplicated', array( $this, 'post_form_duplicated' ) );

	}

	/**
	 * Sets the fields default label in the form editor.
	 */
	public function set_defaults() {
		?>
		case "coupon" :
		field.label = <?php echo json_encode( esc_html__( 'Coupon', 'gravityformscoupons' ) ); ?>;
		break;
		<?php
	}

	/**
	 * Add the settings tab with the uninstall button.
	 */
	public function plugin_settings() {

		if ( $this->maybe_uninstall() ) {
			?>
			<div class="push-alert-gold" style="border-left: 1px solid #E6DB55; border-right: 1px solid #E6DB55;">
				<?php printf( esc_html__( 'The %s has been successfully uninstalled. It can be re-activated from the %splugins page%s.', 'gravityformscoupons' ), $this->_title, "<a href='plugins.php'>", '</a>' ); ?>
			</div>
			<?php
		} else {
			//renders uninstall section
			$this->render_uninstall();
		}
	}

	/**
	 * Prevent coupons being added to the Form Settings menu list.
	 *
	 * @param array $tabs Contains the properties for each tab: name, label and query.
	 * @param integer $form_id The ID of the current form.
	 *
	 * @return array
	 */
	public function add_form_settings_menu( $tabs, $form_id ) {
		if ( $this->_slug != 'gravityformscoupons' ) {
			return parent::add_form_settings_menu( $tabs, $form_id );
		} else {
			return $tabs;
		}
	}

	/**
	 * Initializes the coupons feeds page, includes tooltip functionality.
	 */
	public function plugin_page_init() {
		parent::plugin_page_init();

		require_once( GFCommon::get_base_path() . '/tooltips.php' );
	}

	/**
	 * Creates the coupons feeds page.
	 */
	public function plugin_page() {
		$fid = $this->get_current_feed_id();

		if ( ! empty( $fid ) || $fid == '0' ) {
			$form_id = rgget( 'id' );
			$this->coupon_edit_page( $fid, $form_id );
		} else {
			parent::feed_list_page();
		}

	}

	/**
	 * Retrieves the current feed ID.
	 *
	 * @return integer
	 */
	public function get_current_feed_id() {
		if ( $this->_coupon_feed_id ) {
			return $this->_coupon_feed_id;
		} elseif ( ! rgempty( 'gf_feed_id' ) ) {
			return rgpost( 'gf_feed_id' );
		} else {
			return rgget( 'fid' );
		}
	}

	/**
	 * Handle rendering/saving the settings on the feed (coupon) edit page.
	 *
	 * @param integer $feed_id The current feed ID.
	 * @param integer $form_id The form ID the coupon applies to or Zero for all forms.
	 */
	public function coupon_edit_page( $feed_id, $form_id ) {
		$messages = '';
		// Save feed if appropriate
		$feed_fields = $this->get_feed_settings_fields();

		$feed_id = $this->maybe_save_feed_settings( $feed_id, '' );

		$this->_coupon_feed_id = $feed_id;

		//update the form_id on the feed
		$feed = $this->get_feed( $feed_id );
		if ( is_array( $feed ) ) {
			$this->update_feed_form_id( $feed_id, rgar( $feed['meta'], 'gravityForm' ) );
		}

		?>
		<h3><span><?php echo $this->feed_settings_title() ?></span></h3>
		<input type="hidden" name="gf_feed_id" value="<?php echo $feed_id ?>"/>

		<?php
		$this->set_settings( $feed['meta'] );

		GFCommon::display_admin_message( '', $messages );

		$this->render_settings( $feed_fields );
	}

	/**
	 * Update the feeds form ID in the database.
	 *
	 * @param integer $id The current feed ID.
	 * @param integer $form_id The form ID the coupon applies to or Zero for all forms.
	 *
	 * @return bool
	 */
	public function update_feed_form_id( $id, $form_id ) {
		global $wpdb;

		$wpdb->update( "{$wpdb->prefix}gf_addon_feed", array( 'form_id' => $form_id ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );

		return $wpdb->rows_affected > 0;
	}

	/**
	 * Target of the SLUG_feed_actions filter. Adds the edit feed action link for each feed on the feeds list page.
	 *
	 * @param array $action_links
	 * @param array $item
	 * @param string $column
	 *
	 * @return array
	 */
	public function set_action_links( $action_links, $item, $column ) {
		if ( is_array( $action_links ) ) {
			//change array
			$feed_id              = '_id_';
			$form_id              = rgar( $item, 'form_id' );
			$edit_url             = add_query_arg( array( 'id' => $form_id, 'fid' => $feed_id ) );
			$action_links['edit'] = '<a title="' . esc_attr__( 'Edit this feed', 'gravityformscoupons' ) . '" href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'gravityformscoupons' ) . '</a>';

			// feed duplication not currently supported.
			unset( $action_links['duplicate'] );

		}

		return $action_links;
	}

	/**
	 * Defines choices available in the bulk actions menu on the coupon feeds page.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$bulk_actions = array(
				'delete'      => esc_html__( 'Delete', 'gravityforms' ),
				'reset_count' => esc_html__( 'Reset Usage Count', 'gravityformscoupons' )
		);

		return $bulk_actions;
	}

	/**
	 * Handles resetting the coupon usage counts when the reset_count bulk action is selected.
	 *
	 * @param string $action The name of the action to be performed.
	 */
	public function process_bulk_action( $action ) {
		if ( $action == 'reset_count' ) {
			$feeds = rgpost( 'feed_ids' );
			if ( is_array( $feeds ) ) {
				foreach ( $feeds as $feed_id ) {
					$feed = $this->get_feed( $feed_id );
					if ( isset( $feed['meta']['usageCount'] ) ) {
						$feed['meta']['usageCount'] = 0;
						$this->update_feed_meta( $feed_id, $feed['meta'] );
					}
				}
			}
		} else {
			parent::process_bulk_action( $action );
		}
	}

	/**
	 * Configures which columns should be displayed on the coupon feeds page.
	 *
	 * @return array
	 */
	public function feed_list_columns() {
		return array(
			'couponTitle'  => esc_html__( 'Title', 'gravityformscoupons' ),
			'gravityForm'  => esc_html__( 'Form', 'gravityformscoupons' ),
			'couponAmount' => esc_html__( 'Amount', 'gravityformscoupons' ),
			'usageLimit'   => esc_html__( 'Usage Limit', 'gravityformscoupons' ),
			'usageCount'   => esc_html__( 'Usage Count', 'gravityformscoupons' ),
			'endDate'      => esc_html__( 'Expires', 'gravityformscoupons' ),
			'isStackable'  => esc_html__( 'Is Stackable', 'gravityformscoupons' ),
		);
	}

	/**
	 * Returns the value to be displayed in the Title column.
	 *
	 * @param array $feed The coupon feed being included in the feed list.
	 *
	 * @return string
	 */
	public function get_column_value_couponTitle( $feed ) {
		return $feed['meta']['couponName'] . ' (' . $feed['meta']['couponCode'] . ')';
	}

	/**
	 * Returns the value to be displayed in the Form column.
	 *
	 * @param array $feed The coupon feed being included in the feed list.
	 *
	 * @return string
	 */
	public function get_column_value_gravityForm( $feed ) {
		return $this->get_form_name( $feed['meta']['gravityForm'] );
	}

	/**
	 * Helper for getting the Form column value.
	 *
	 * @param integer $formid The ID the coupon is assigned to or zero for all forms.
	 *
	 * @return string
	 */
	public function get_form_name( $formid ) {
		if ( $formid == '0' ) {
			return esc_html__( 'Any Form', 'gravityformscoupons' );
		}

		$form = RGFormsModel::get_form( $formid );
		if ( ! $form ) {
			return esc_html__( 'Invalid Form', 'gravityformscoupons' );
		}

		return $form->title;

	}

	/**
	 * Returns the value to be displayed in the Amount column.
	 *
	 * @param array $feed The coupon feed being included in the feed list.
	 *
	 * @return string
	 */
	public function get_column_value_couponAmount( $feed ) {
		if ( $feed['meta']['couponAmountType'] == 'flat' ) {
			$couponAmount = GFCommon::to_money( $feed['meta']['couponAmount'] );
		} else {
			$couponAmount = GFCommon::to_number( $feed['meta']['couponAmount'] ) . '%';
		}

		return $couponAmount;
	}

	/**
	 * Returns the value to be displayed in the Usage Limit column.
	 *
	 * @param array $feed The coupon feed being included in the feed list.
	 *
	 * @return string
	 */
	public function get_column_value_usageLimit( $feed ) {
		return $feed['meta']['usageLimit'] == '' ? 'Unlimited' : $feed['meta']['usageLimit'];
	}

	/**
	 * Returns the value to be displayed in the Usage count column.
	 *
	 * @param array $feed The coupon feed being included in the feed list.
	 *
	 * @return integer
	 */
	public function get_column_value_usageCount( $feed ) {
		$usage_count = rgar( $feed['meta'], 'usageCount' );

		return $usage_count == '' ? '0' : $usage_count;
	}

	/**
	 * Returns the value to be displayed in the Expires column.
	 *
	 * @param array $feed The coupon feed being included in the feed list.
	 *
	 * @return string
	 */
	public function get_column_value_endDate( $feed ) {
		return $feed['meta']['endDate'] == '' ? 'Never Expires' : $feed['meta']['endDate'];
	}

	/**
	 * Returns the value to be displayed in the Is Stackable column.
	 *
	 * @param array $feed The coupon feed being included in the feed list.
	 *
	 * @return string
	 */
	public function get_column_value_isStackable( $feed ) {
		if ( $feed['meta']['isStackable'] ) {
			return 'Yes';
		}
	}

	/**
	 * Configures the settings which should be rendered on the feed (coupon) edit page.
	 *
	 * @return array The feed settings.
	 */
	public function feed_settings_fields() {
		return array(
			array(
				'title'       => esc_html__( 'Applies to Which Form?', 'gravityformscoupons' ),
				'description' => '',
				'fields'      => array(
					array(
						'name'     => 'gravityForm',
						'label'    => esc_html__( 'Gravity Form', 'gravityformscoupons' ),
						'type'     => 'select',
						'onchange' => 'jQuery(this).parents("form").submit();',
						'choices'  => $this->get_gravity_forms(),
						'tooltip'  => '<h6>' . esc_html__( 'Gravity Form', 'gravityformscoupons' ) . '</h6>' . esc_html__( 'Select the Gravity Form you would like to integrate with Coupons.', 'gravityformscoupons' )
					),
				)
			),
			array(
				'title'       => esc_html__( 'Coupon Basics', 'gravityformscoupons' ),
				'description' => '',
				'dependency'  => 'gravityForm',
				'fields'      => array(
					array(
						'name'     => 'couponName',
						'label'    => esc_html__( 'Coupon Name', 'gravityformscoupons' ),
						'type'     => 'text',
						'required' => true,
						'tooltip'  => '<h6>' . esc_html__( 'Coupon Name', 'gravityformscoupons' ) . '</h6>' . esc_html__( 'Enter coupon name.', 'gravityformscoupons' ),
					),
					array(
						'name'                => 'couponCode',
						'label'               => esc_html__( 'Coupon Code', 'gravityformscoupons' ),
						'type'                => 'text',
						'required'            => true,
						'validation_callback' => array( $this, 'check_if_duplicate_coupon_code' ),
						'tooltip'             => '<h6>' . esc_html__( 'Coupon Code', 'gravityformscoupons' ) . '</h6>' . esc_html__( 'Enter the value users should enter to apply this coupon to the form total.', 'gravityformscoupons' )
					),
					array(
						'name'                => 'couponAmountType',
						'label'               => esc_html__( 'Coupon Amount', 'gravityformscoupons' ),
						'type'                => 'coupon_amount_type',
						'required'            => true,
						'validation_callback' => array( $this, 'validate_coupon_amount' ),
						'tooltip'             => '<h6>' . esc_html__( 'Coupon Amount', 'gravityformscoupons' ) . '</h6>' . esc_html__( 'Enter the amount to be deducted from the form total.', 'gravityformscoupons' )
					),
				)
			),
			array(
				'title'       => esc_html__( 'Coupon Options', 'gravityformscoupons' ),
				'description' => '',
				'dependency'  => 'gravityForm',
				'fields'      => array(
					array(
						'name'    => 'startDate',
						'label'   => esc_html__( 'Start Date', 'gravityformscoupons' ),
						'type'    => 'text',
						'tooltip' => '<h6>' . esc_html__( 'Start Date', 'gravityformscoupons' ) . '</h6>' . esc_html__( 'Enter the date when the coupon should start.', 'gravityformscoupons' ),
						'class'   => 'datepicker',
					),
					array(
						'name'    => 'endDate',
						'label'   => esc_html__( 'End Date', 'gravityformscoupons' ),
						'type'    => 'text',
						'tooltip' => '<h6>' . esc_html__( 'End Date', 'gravityformscoupons' ) . '</h6>' . esc_html__( 'Enter the date when the coupon should expire.', 'gravityformscoupons' ),
						'class'   => 'datepicker',
					),
					array(
						'name'    => 'usageLimit',
						'label'   => __( 'Usage Limit', 'gravityformscoupons' ),
						'type'    => 'text',
						'tooltip' => '<h6>' . esc_html__( 'Usage Limit', 'gravityformscoupons' ) . '</h6>' . esc_html__( 'Enter the number of times coupon code can be used.', 'gravityformscoupons' )
					),
					array(
						'name'    => 'isStackable',
						'label'   => __( 'Is Stackable', 'gravityformscoupons' ),
						'type'    => 'checkbox',
						'tooltip' => '<h6>' . esc_html__( 'Is Stackable', 'gravityformscoupons' ) . '</h6>' . esc_html__( 'When the "Is Stackable" option is selected, this coupon code will be allowed to be used in conjunction with another coupon code.', 'gravityformscoupons' ),
						'choices' => array(
							array(
								'label' => esc_html__( 'Is Stackable', 'gravityformscoupons' ),
								'name'  => 'isStackable',
							),
						)
					),
					array(
						'name'  => 'usageCount',
						'label' => esc_html__( 'Usage Count', 'gravityformscoupons' ),
						'type'  => 'hidden',
					),
				)
			),
		);
	}

	/**
	 * Creates an array of forms for the gravityForm setting.
	 *
	 * @return array
	 */
	public function get_gravity_forms() {

		$forms = RGFormsModel::get_forms();

		$forms_dropdown = array(
			array( 'label' => esc_html__( 'Select a Form', 'gravityformscoupons' ), 'value' => '' ),
			array( 'label' => esc_html__( 'Any Form', 'gravityformscoupons' ), 'value' => '0' ),
		);

		foreach ( $forms as $form ) {
			$forms_dropdown[] = array(
				'label' => $form->title,
				'value' => $form->id,
			);
		}

		return $forms_dropdown;
	}

	/**
	 * Renders the couponAmountType setting.
	 *
	 * @param array $field The setting properties.
	 * @param bool|true $echo
	 *
	 * @return string
	 */
	public function settings_coupon_amount_type( $field, $echo = true ) {

		require_once( GFCommon::get_base_path() . '/currency.php' );
		$currency        = RGCurrency::get_currency( GFCommon::get_currency() );
		$currency_symbol = ! empty ( $currency['symbol_left'] ) ? $currency['symbol_left'] : $currency['symbol_right'];

		wp_enqueue_script( array( 'jquery-ui-datepicker' ) );

		$styles = '<style type="text/css">
						td img.ui-datepicker-trigger {
						position: relative;
						top: 4px;
						}
					</style>';

		$js_script = '<script type="text/javascript">
							var currency_config = ' . json_encode( RGCurrency::get_currency( GFCommon::get_currency() ) ) . ';
							var form = Array();
								jQuery(document).on(\'change\', \'.gf_format_money\', function(){
									var cur = new Currency(currency_config)
									jQuery(this).val(cur.toMoney(jQuery(this).val()));
								});
								jQuery(document).on(\'change\', \'.gf_format_percentage\', function(event){
									var cur = new Currency(currency_config)
									var value = cur.toNumber(jQuery(this).val()) ? cur.toNumber(jQuery(this).val()) + \'%\' : \'\';
									jQuery(this).val( value );
								});

							function SetCouponType(elem) {
								var type = elem.val();
								var formatClass = type == \'flat\' ? \'gf_format_money\' : \'gf_format_percentage\';
								jQuery(\'#couponAmount\').removeClass(\'gf_format_money gf_format_percentage\').addClass(formatClass).trigger(\'change\');
								var placeholderText = type == \'flat\' ? \'' . html_entity_decode( GFCommon::to_money( 1 ) ) . '\' : \'1%\';
								jQuery(\'#couponAmount\').attr("placeholder",placeholderText);
							}

							jQuery(document).ready(function($){
								//set placeholder text for initial load
								var type = jQuery(\'#couponAmountType\').val();
								var placeholderText = type == \'flat\' ? \'' . html_entity_decode( GFCommon::to_money( 1 ) ) . '\' : \'1%\';
								jQuery(\'#couponAmount\').attr("placeholder",placeholderText);

								//format initial coupon amount value when there is one and it is currency
								var currency_config = ' . json_encode( RGCurrency::get_currency( GFCommon::get_currency() ) ) . ';
								var cur = new Currency(currency_config);
								couponAmount = jQuery(\'#couponAmount\').val();
								if ( couponAmount ){
									if (type == \'flat\'){
										couponAmount = cur.toMoney(couponAmount);
									}
									else{
										couponAmount = cur.toNumber(couponAmount) + \'%\';
									}
									jQuery(\'#couponAmount\').val(couponAmount);
								}

								jQuery(\'.datepicker\').each(
									function (){
										var image = "' . $this->get_base_url() . '/images/calendar.png";
										jQuery(this).datepicker({showOn: "both", buttonImage: image, buttonImageOnly: true, dateFormat: "mm/dd/yy" });
									}
								);

							});

						</script>';

		$field['type']     = 'select';
		$field['choices']  = array(
			array(
				'label' => esc_html__( 'Flat', 'gravityformscoupons' ) . '(' . $currency_symbol . ')',
				'name'  => 'flat',
				'value' => 'flat'
			),
			array(
				'label' => esc_html__( 'Percentage(%)', 'gravityformscoupons' ),
				'name'  => 'percentage',
				'value' => 'percentage'
			),
		);
		$field['onchange'] = 'SetCouponType(jQuery(this))';
		$html              = $this->settings_select( $field, false );

		$field2             = array();
		$field2['type']     = 'text';
		$field2['name']     = 'couponAmount';
		$field2['required'] = true;
		$field2['class']    = $this->get_setting( 'couponAmountType' ) == 'percentage' ? 'gf_format_percentage' : 'gf_format_money';

		$html2 = $this->settings_text( $field2, false );

		if ( $echo ) {
			echo $styles . $js_script . $html . $html2;
		}

		return $styles . $js_script . $html . $html2;

	}

	/**
	 * Validates the couponAmount setting to ensure a value was entered.
	 *
	 * @param array $field The setting properties.
	 */
	public function validate_coupon_amount( $field ) {
		$settings = $this->get_posted_settings();

		if ( empty( $settings['couponAmount'] ) ) {
			$this->set_field_error( array( 'name' => 'couponAmount' ), esc_html__( 'This field is required.', 'gravityformscoupons' ) );
		}
	}

	/**
	 * Validates the couponCode setting to ensure the entered coupon code is valid and unique.
	 *
	 * @param array $field The setting properties.
	 */
	public function check_if_duplicate_coupon_code( $field ) {
		$settings = $this->get_posted_settings();

		if ( ! ctype_alnum( $settings['couponCode'] ) ) {
			$this->set_field_error( $field, esc_html__( 'Please enter a valid Coupon Code. The Coupon Code can only contain alphanumeric characters.', 'gravityformscoupons' ) );

			return;
		}

		$feed['id']                 = $this->get_current_feed_id();
		$feed['form_id']            = $settings['gravityForm'];
		$feed['meta']['couponCode'] = $settings['couponCode'];

		$is_duplicate_coupon_code = $this->is_duplicate_coupon( $feed, $this->get_feeds() );

		if ( $is_duplicate_coupon_code ) {
			$this->set_field_error( $field, esc_html__( 'The Coupon Code entered is already in use. Please enter a unique Coupon Code and try again.', 'gravityformscoupons' ) );
		}
	}

	/**
	 * Retrieves the settings from the $_POST and reformat the couponAmount and couponCode before they are saved.
	 *
	 * @return array The post data containing the updated coupon feed settings.
	 */
	public function get_posted_settings() {
		$post_data = parent::get_posted_settings();

		if ( ! empty( $post_data ) ) {
			if ( isset( $post_data['couponAmount'] ) ) {
				$post_data['couponAmount'] = GFCommon::to_number( $post_data['couponAmount'] );
			}
			if ( isset( $post_data['couponCode'] ) ) {
				$post_data['couponCode'] = strtoupper( $post_data['couponCode'] );
			}
		}

		return $post_data;
	}

	/**
	 * Checks if the couponCode can't be used again.
	 *
	 * @param array $current_feed The feed object for the coupon currently being saved.
	 * @param array $feeds All existing feed objects.
	 *
	 * @return bool
	 */
	public static function is_duplicate_coupon( $current_feed, $feeds ) {
		if ( ! is_array( $feeds ) ) {
			return false;
		}

		foreach ( $feeds as $feed ) {

			if ( strtoupper( $feed['meta']['couponCode'] ) != $current_feed['meta']['couponCode'] ) {
				continue;
			}

			$not_current_feed = $feed['id'] != $current_feed['id'];

			// current feed is for any form & feed being checked is not current feed
			if ( $current_feed['form_id'] == 0 && $not_current_feed ) {

				// return true if coupon code is already associated with any form
				if ( empty( $feed['form_id'] ) || $feed['form_id'] == 0 ) {
					return true;
				}

				// return true if coupon code is already associated with a specific form
				if ( ! empty( $feed['form_id'] ) && $feed['form_id'] != 0 ) {
					return true;
				}

			}

			// return true if coupon code is already associated with another specific form
			if ( $feed['form_id'] == $current_feed['form_id'] && $not_current_feed ) {
				return true;
			}

		}

		return false;
	}


	// # HELPERS -------------------------------------------------------------------------------------------------------

	/**
	 * Retrieves the feed object for the requested coupon code.
	 *
	 * @param array $form The form currently being processed.
	 * @param string $coupon_code The coupon code.
	 *
	 * @return mixed Returns an array containing the feed object for the requested coupon code or false if invalid.
	 */
	public function get_config( $form, $coupon_code ) {
		$coupon_code = trim( $coupon_code );

		$feeds = $this->get_feeds();

		if ( ! $feeds ) {
			return false;
		}

		foreach ( $feeds as $feed ) {
			//form must match or be zero for any form
			if ( strtoupper( $feed['meta']['couponCode'] ) == $coupon_code && ( $feed['form_id'] == '0' || $feed['form_id'] == $form['id'] ) ) {
				return $feed;
			}
		}

		return false;
	}

	/**
	 * Retrieves the coupon feeds from the database.
	 *
	 * @param null|integer $form_id
	 *
	 * @return mixed An array containing the feed objects or null.
	 */
	public function get_feeds( $form_id = null ) {
		global $wpdb;

		$form_filter     = is_numeric( $form_id ) ? $wpdb->prepare( 'AND form_id=%d', absint( $form_id ) ) : '';
		$form_table_name = RGFormsModel::get_form_table_name();

		//only get coupons associated with active forms (is_trash = 0) per discussion with alex/dave
		//use is_trash is null to get the coupons associated with the "Any form" option because form id will be zero and the join will not include the coupon without this
		$sql = $wpdb->prepare(
			"SELECT af.* FROM {$wpdb->prefix}gf_addon_feed af LEFT JOIN {$form_table_name} f ON af.form_id = f.id
                               WHERE addon_slug=%s {$form_filter} AND (is_trash = 0 OR is_trash is null)", $this->_slug
		);

		$results = $wpdb->get_results( $sql, ARRAY_A );
		foreach ( $results as &$result ) {
			$result['meta'] = json_decode( $result['meta'], true );
		}

		return $results;
	}

	/**
	 * Checks if a coupon code can be applied.
	 *
	 * @param string $coupon_code The coupon code to be validated.
	 * @param string $existing_coupon_codes The coupon codes which have already been applied.
	 * @param array $feed The coupon feed object currently being processed.
	 * @param string $invalid_reason The reason the coupon code is invalid.
	 * @param array $form The form object currently being processed.
	 *
	 * @return bool
	 */
	public function can_apply_coupon( $coupon_code, $existing_coupon_codes, $feed, &$invalid_reason = '', $form ) {

		$coupon = $this->get_coupon_by_code( $feed );
		if ( ! $coupon ) {
			$invalid_reason = esc_html__( 'Invalid coupon.', 'gravityformscoupons' );

			return false;
		}

		if ( ! $this->is_valid( $feed, $invalid_reason ) ) {
			return false;
		}

		//see if coupon code has already been applied, a code can only be applied once
		if ( in_array( $coupon_code, explode( ',', $existing_coupon_codes ) ) ) {
			$invalid_reason = esc_html__( "This coupon can't be applied more than once.", 'gravityformscoupons' );

			return false;
		}

		//checking if coupon can be stacked
		if ( ! is_array( $existing_coupon_codes ) ) {
			$existing_coupons = empty( $existing_coupon_codes ) ? array() : $this->get_coupons_by_codes( explode( ',', $existing_coupon_codes ), $form );
		}
		foreach ( $existing_coupons as $existing_coupon ) {
			if ( ! $existing_coupon['can_stack'] || ! $coupon['can_stack'] ) {
				$invalid_reason = esc_html__( "This coupon can't be used in conjunction with other coupons you have already entered.", 'gravityformscoupons' );

				return false;
			}
		}

		return true;
	}

	/**
	 * Retrieves the coupon properties from the coupon feed meta.
	 *
	 * @param array $feed The coupon feed.
	 *
	 * @return array|bool
	 */
	public function get_coupon_by_code( $feed ) {

		if ( empty( $feed ) ) {
			return false;
		}

		$coupon = array(
			'amount'      => GFCommon::to_number( rgars( $feed, 'meta/couponAmount' ) ),
			'name'        => rgars( $feed, 'meta/couponName' ),
			'type'        => rgars( $feed, 'meta/couponAmountType' ),
			'code'        => strtoupper( rgars( $feed, 'meta/couponCode' ) ),
			'can_stack'   => rgars( $feed, 'meta/isStackable' ) == 1 ? true : false,
			'usage_count' => empty( $feed['meta']['usageCount'] ) ? 0 : $feed['meta']['usageCount'],
		);

		return $coupon;
	}

	/**
	 * Retrieves an array of coupon details for the specified coupons, or false if no coupon feeds were found.
	 *
	 * @param string|array $codes The codes for the coupons to be retrieved.
	 * @param array $form The form object currently being processed.
	 *
	 * @return array|bool
	 */
	public function get_coupons_by_codes( $codes, $form ) {

		if ( ! is_array( $codes ) ) {
			$codes = explode( ',', $codes );
		}

		$coupons = array();
		foreach ( $codes as $coupon_code ) {
			$coupon_code = strtoupper( trim( $coupon_code ) );
			$feed        = $this->get_config( $form, $coupon_code );
			if ( $feed ) {
				$coupons[ $coupon_code ] = $this->get_coupon_by_code( $feed );
			}
		}

		if ( empty( $coupons ) ) {
			return false;
		}

		return $coupons;
	}

	/**
	 * Check if a coupon is active, not expired and not exceeded it's usage limit.
	 *
	 * @param array $config The coupon to be validated.
	 * @param string $invalid_reason The reason the coupon is invalid.
	 *
	 * @return bool
	 */
	public function is_valid( $config, &$invalid_reason = '' ) {

		if ( ! $config['is_active'] ) {
			$invalid_reason = esc_html__( 'This coupon is currently inactive.', 'gravityformscoupons' );

			return false;
		}

		$start_date = strtotime( $config['meta']['startDate'] ); //start of the day
		$end_date   = strtotime( $config['meta']['endDate'] . ' 23:59:59' ); //end of the day

		$now = GFCommon::get_local_timestamp();

		//validating start date
		if ( $config['meta']['startDate'] && $now < $start_date ) {
			$invalid_reason = esc_html__( 'Invalid coupon.', 'gravityformscoupons' );

			return false;
		}

		//validating end date
		if ( $config['meta']['endDate'] && $now > $end_date ) {
			$invalid_reason = esc_html__( 'This coupon has expired.', 'gravityformscoupons' );

			return false;
		}

		//validating usage limit
		$is_under_limit = false;
		$coupon_usage   = empty( $config['meta']['usageCount'] ) ? 0 : intval( $config['meta']['usageCount'] );
		if ( empty( $config['meta']['usageLimit'] ) || $coupon_usage < intval( $config['meta']['usageLimit'] ) ) {
			$is_under_limit = true;
		}
		if ( ! $is_under_limit ) {
			$invalid_reason = esc_html__( 'This coupon has reached its usage limit.', 'gravityformscoupons' );

			return false;
		}

		//coupon is valid
		return true;
	}

	/**
	 * Retrieves the coupon field object or false.
	 *
	 * @param array $form The form object currently being processed.
	 *
	 * @return object|false
	 */
	public function get_coupon_field( $form ) {
		$coupons = GFCommon::get_fields_by_type( $form, array( 'coupon' ) );

		return count( $coupons ) > 0 ? $coupons[0] : false;
	}

	/**
	 * Retrieves the submitted coupon codes from the entry object.
	 *
	 * @param array $form The form object currently being processed.
	 * @param array $entry The entry object currently being processed.
	 *
	 * @return array|bool|string
	 */
	public function get_submitted_coupon_codes( $form, $entry = array() ) {
		$coupon_field = $this->get_coupon_field( $form );

		if ( ! is_object( $coupon_field ) ) {
			return false;
		}

		$coupons = rgar( $entry, $coupon_field->id );

		if ( empty( $coupons ) ) {
			return false;
		}

		$coupons = array_map( 'trim', explode( ',', $coupons ) );

		return $coupons;
	}

	/**
	 * Generates an array containing the details of the applied coupons, including the discount amounts.
	 *
	 * @param array $coupons The coupons to be applied to the form total.
	 * @param float|integer $total The form total.
	 * @param float $discount_total The total discount.
	 *
	 * @return array
	 */
	public function get_discounts( $coupons, &$total = 0, &$discount_total ) {
		$coupons = $this->sort_coupons( $coupons );

		$discount_total = 0;
		$discounts      = array();

		foreach ( $coupons as $coupon ) {

			$discount = 0;

			$discount = $this->get_discount( $coupon, $total );

			$discount_total += $discount;

			$total -= $discount;

			$discounts[ $coupon['code'] ]['code']     = $coupon['code'];
			$discounts[ $coupon['code'] ]['name']     = $coupon['name'];
			$discounts[ $coupon['code'] ]['discount'] = GFCommon::to_money( $discount );
			$discounts[ $coupon['code'] ]['amount']   = $coupon['amount'];
			$discounts[ $coupon['code'] ]['type']     = $coupon['type'];

		}

		return $discounts;
	}

	/**
	 * Calculates the current coupons discount amount.
	 *
	 * @param array $coupon The coupon config.
	 * @param float $price The form total.
	 *
	 * @return float
	 */
	public function get_discount( $coupon, $price ) {
		if ( $coupon['type'] == 'flat' ) {
			$discount = GFCommon::to_number( $coupon['amount'] );
		} else {
			$discount = $price * ( $coupon['amount'] / 100 );
		}

		$discount = $price - $discount >= 0 ? $discount : $price;
		$discount = apply_filters( 'gform_coupons_discount_amount', $discount, $coupon, $price );

		return $discount;
	}

	/**
	 * Sorts the coupons so flat rate coupons are ordered before percentage based coupons.
	 *
	 * @param array $coupons The coupons to be sorted.
	 *
	 * @return array
	 */
	public function sort_coupons( $coupons ) {

		$sorted = array( 'cart_flat' => array(), 'cart_percentage' => array() );

		foreach ( $coupons as $coupon ) {
			$sorted[ 'cart_' . $coupon['type'] ][ $coupon['code'] ] = $coupon;
		}

		if ( ! empty( $sorted['cart_percentage'] ) && count( $sorted['cart_percentage'] ) > 0 ) {
			usort( $sorted['cart_percentage'], array( 'GFCoupons', 'array_cmp' ) );
		}

		return array_merge( $sorted['cart_flat'], $sorted['cart_percentage'] );
	}

	/**
	 * Helper for sorting the percentage based coupons.
	 *
	 * @param array $a The first coupons config.
	 * @param array $b The second coupons config.
	 *
	 * @return integer The result of the coupons amount comparison.
	 */
	public function array_cmp( $a, $b ) {
		return strcmp( $a['amount'], $b['amount'] );
	}


	// # DEPRECATED ----------------------------------------------------------------------------------------------------

	/**
	 * @deprecated No longer used.
	 */
	public function is_coupon_visible( $form ) {
		_deprecated_function( __FUNCTION__, '2.2' );
		$is_visible = true;
		foreach ( $form['fields'] as $field ) {
			if ( $field->type == 'coupon' ) {
				// if conditional is enabled, but the field is hidden, ignore conditional
				$is_visible = ! RGFormsModel::is_field_hidden( $form, $field, array() );
				break;
			}
		}

		return $is_visible;

	}


	// # TO FRAMEWORK MIGRATION ----------------------------------------------------------------------------------------

	/**
	 * Checks if a previous version was installed and if the feeds need migrating to the framework structure.
	 *
	 * @param string $previous_version The version number of the previously installed version.
	 */
	public function upgrade( $previous_version ) {
		$this->log_debug( __METHOD__ . '(): Checking to see if feeds need to be migrated.' );
		if ( empty( $previous_version ) ) {
			$previous_version = get_option( 'gf_coupons_version' );
		}
		$previous_is_pre_addon_framework = ! empty( $previous_version ) && version_compare( $previous_version, '2.0.dev1', '<' );

		if ( $previous_is_pre_addon_framework ) {
			$this->log_debug( __METHOD__ . '(): Upgrading feeds.' );
			$old_feeds = $this->get_old_feeds();

			if ( ! $old_feeds ) {
				return;
			}

			foreach ( $old_feeds as $old_feed ) {

				$form_id = $old_feed['form_id'];
				if ( rgblank( $form_id ) ) {
					$form_id = 0;
				}

				$is_active = $old_feed['is_active'];

				$couponAmount = rgar( $old_feed['meta'], 'coupon_amount' );
				if ( ! rgblank( $couponAmount ) ) {
					$couponAmount = GFCommon::to_number( $couponAmount );
				}

				$new_meta = array(
					'couponName'       => rgar( $old_feed['meta'], 'coupon_name' ),
					'gravityForm'      => $form_id,
					'couponCode'       => rgar( $old_feed['meta'], 'coupon_code' ),
					'couponAmountType' => rgar( $old_feed['meta'], 'coupon_type' ),
					'couponAmount'     => $couponAmount,
					'startDate'        => rgar( $old_feed['meta'], 'coupon_start' ),
					'endDate'          => rgar( $old_feed['meta'], 'coupon_expiration' ),
					'usageLimit'       => rgar( $old_feed['meta'], 'coupon_limit' ),
					'isStackable'      => rgar( $old_feed['meta'], 'coupon_stackable' ),
					'usageCount'       => rgar( $old_feed['meta'], 'coupon_usage' ),
				);
				$this->log_debug( __METHOD__ . '(): Inserting coupon ' . $new_meta['couponName'] . ' into new table.' );
				$this->insert_feed( $form_id, $is_active, $new_meta );

			}
			update_option( 'gf_coupons_upgrade', 1 );

			$this->log_debug( __METHOD__ . '(): Feed migration completed.' );
		} else {
			$this->log_debug( __METHOD__ . '(): The existing version of coupons is already on the new framework, no need to upgrade old feeds.' );
		}

	}

	/**
	 * Retrieve any old feeds which need migrating to the framework,
	 *
	 * @return bool|array
	 */
	public function get_old_feeds() {
		$this->log_debug( __METHOD__ . '(): Getting old feeds to migrate.' );
		global $wpdb;
		$table_name = $wpdb->prefix . 'rg_coupons';

		if ( ! $this->table_exists( $table_name ) ) {
			return false;
		}

		$form_table_name = RGFormsModel::get_form_table_name();

		//do not copy over the coupons that are associated with a form in the trash, include is_trash is null to get the coupons not associated with a form
		$sql = "SELECT c.* FROM $table_name c LEFT JOIN $form_table_name f ON c.form_id = f.id
				WHERE is_trash = 0 OR is_trash is null";
		$wpdb->hide_errors(); //in case the user did not have the previous version of coupons and the table does not exist
		$results = $wpdb->get_results( $sql, ARRAY_A );

		$count = sizeof( $results );
		$this->log_debug( __METHOD__ . '(): ' . $count . ' records found.' );
		for ( $i = 0; $i < $count; $i ++ ) {
			$results[ $i ]['meta'] = maybe_unserialize( $results[ $i ]['meta'] );
		}

		return $results;
	}

}