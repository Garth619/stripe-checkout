<?php
/**
 *
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class GFP_More_Stripe_Currency_Field
 */
class GFP_More_Stripe_Currency_Field {
	/**
	 * Instance of this class.
	 *
	 * @since    1.8.2
	 *
	 * @var      object
	 */
	private static $_this = null;

	private $field_type = '';
	private $field_label = '';

	private $form_currency = '';

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.8.2
	 *
	 * @uses      wp_die()
	 * @uses      __()
	 * @uses      register_activation_hook()
	 * @uses      add_action()
	 *
	 */
	function __construct () {

		if ( isset( self::$_this ) ) {
			wp_die( sprintf( __( 'There is already an instance of %s.',
								 'gravityforms-stripe-more' ), get_class( $this ) ) );
		}

		self::$_this       = $this;
		$this->field_type  = 'currency';
		$this->field_label = __( 'Currency', 'gravityforms-stripe-more' );

		add_action( 'init', array( $this, 'init' ) );

	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone () {
	}

	/**
	 * Private unserialize method to prevent unserializing of the *Singleton*
	 * instance.
	 *
	 * @return void
	 */
	private function __wakeup () {
	}

	/**
	 * @return GFP_More_Stripe_Currency_Field|null|object
	 */
	static function this () {
		return self::$_this;
	}

	public function init () {
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		add_filter( 'gform_field_input', array( $this, 'gform_field_input' ), 10, 5 );

		add_action( 'wp_ajax_gfp_more_stripe_get_currency_values', array( $this, 'get_currency_values' ) );
		add_action( 'wp_ajax_nopriv_gfp_more_stripe_get_currency_values', array( $this, 'get_currency_values' ) );
	}

	public function admin_init () {
		if ( 'gf_edit_forms' == RGForms::get( 'page' ) ) {
			add_filter( 'gform_add_field_buttons', array( $this, 'gform_add_field_buttons' ) );
			add_action( 'gform_editor_js_set_default_values', array( $this, 'gform_editor_js_set_default_values' ) );
			add_action( 'gform_field_css_class', array( $this, 'gform_field_css_class' ), 10, 3 );
			add_filter( 'gform_field_type_title', array( $this, 'gform_field_type_title' ) );
			add_action( 'gform_field_standard_settings', array( $this, 'gform_field_standard_settings' ), 10, 2 );
			add_action( 'gform_editor_js', array( $this, 'gform_editor_js' ) );

			wp_enqueue_style( 'gfp_more_stripe_form_editor_currency_css', trailingslashit( GFP_MORE_STRIPE_URL ) . 'includes/currency/css/form_editor-currency.css', array(), GFPMoreStripe::get_version() );
			add_filter( 'gform_noconflict_styles', array( $this, 'gform_noconflict_styles' ) );
			add_filter( 'gform_noconflict_scripts', array( $this, 'gform_noconflict_scripts' ) );
		}

		if ( RGForms::get_page() ) {
			add_filter( 'gform_tooltips', array( $this, 'gform_tooltips' ) );
		}
	}

	/**
	 * @param $field_groups
	 *
	 * @return mixed
	 */
	public function gform_add_field_buttons ( $field_groups ) {
		foreach ( $field_groups as &$field_group ) {
			if ( 'pricing_fields' == $field_group['name'] ) {
				array_push( $field_group['fields'], $this->currency_button() );
			}
		}

		return $field_groups;
	}

	public function gform_editor_js_set_default_values () {

		$js = "case 'currency':
			field.label = '{$this->field_label}';
            field.inputs = null;
            field.choices = new Array();
            field.displayAllCurrencies = true;
            field.inputType = 'select';
			break;";

		echo $js;
	}

	/**
	 * @param $css_class
	 * @param $field
	 * @param $form
	 *
	 * @return string
	 */
	public function gform_field_css_class ( $css_class, $field, $form ) {
		if ( $this->is_this_field_type( $field ) ) {
			$css_class .= " gfield_currency gfield_currency_{$form['id']}_{$field['id']}";
		}

		return $css_class;
	}

	/**
	 * @param $type
	 *
	 * @return string|void
	 */
	public function gform_field_type_title ( $type ) {
		if ( $type == $this->field_type ) {
			$title = $this->field_label;

			return $title;
		}

		return $type;
	}

	/**
	 * @param $field_input
	 * @param $field
	 * @param $value
	 * @param $lead_id
	 * @param $form_id
	 *
	 * @return string
	 */
	public function gform_field_input ( $field_input, $field, $value, $lead_id, $form_id ) {
		if ( rgar( $field, 'type' ) == $this->field_type ) {
			$form             = RGFormsModel::get_form_meta( $form_id );
			$default_currency = ( ! empty( $form['currency'] ) ) ? $form['currency'] : GFCommon::get_currency();
			$field            = $this->add_currencies_as_choices( $field, $value, $default_currency );

			if ( 1 == count( $field['choices'] ) && ! empty( $field['choices'][0]['value'] ) ) {
				$this->form_currency = $field['choices'][0]['value'];
			}

			$id            = $field['id'];
			$field_id      = ( IS_ADMIN || $form_id == 0 ) ? "input_{$id}" : "input_{$form_id}_{$id}";
			$class_suffix  = RG_CURRENT_VIEW == 'entry' ? '_admin' : '';
			$class         = rgar( $field, 'size' ) . $class_suffix;
			$disabled_text = ( IS_ADMIN && RG_CURRENT_VIEW != 'entry' ) ? "disabled='disabled'" : '';

			if ( 'entry' == RG_CURRENT_VIEW ) {
				$lead      = RGFormsModel::get_lead( $lead_id );
				$post_id   = $lead['post_id'];
				$post_link = '';
				if ( is_numeric( $post_id ) && GFCommon::is_post_field( $field ) ) {
					$post_link = "You can <a href='post.php?action=edit&post=$post_id'>edit this post</a> from the post page.";
				}
			}

			switch ( RGFormsModel::get_input_type( $field ) ) {
				case 'currency':
					if ( ! empty( $post_link ) ) {
						return $post_link;
					}

					if ( rgget( 'displayAllCurrencies', $field ) && ! IS_ADMIN ) {
						$default_currency = rgget( 'currencyInitialItemEnabled', $field ) ? '-1' : $default_currency;
						$selected         = empty( $value ) ? $default_currency : $value;

						$args = array( 'echo' => 0, 'selected' => $selected, 'class' => esc_attr( $class ) . ' gfield_currency gfield_select', 'name' => "input_{$id}" );
						if ( GFCommon::$tab_index > 0 ) {
							$args['tab_index'] = GFCommon::$tab_index ++;
						}
						if ( rgget( 'currencyInitialItemEnabled', $field ) ) {
							$args['show_option_none'] = empty( $field['currencyInitialItem'] ) ? ' ' : $field['currencyInitialItem'];
						}

						return "<div class='ginput_container'>" . GFP_More_Stripe_Currency::dropdown_currencies( $args ) . '</div>';
					}
					else {
						$tabindex = GFCommon::get_tabindex();
						if ( is_array( rgar( $field, 'choices' ) ) ) {
							usort( $field['choices'], create_function( '$a,$b', 'return strcmp($a["text"], $b["text"]);' ) );
						}

						$choices = GFCommon::get_select_choices( $field, $value );

						if ( rgget( 'currencyInitialItemEnabled', $field ) ) {
							$selected = empty( $value ) ? "selected='selected'" : '';
							$choices  = "<option value='-1' {$selected}>{$field['currencyInitialItem']}</option>" . $choices;
						}

						return sprintf( "<div class='ginput_container'><select name='input_%d' id='%s' class='%s gfield_currency gfield_select' {$tabindex} %s>%s</select></div>", $id, $field_id, esc_attr( $class ), $disabled_text, $choices );
					}
					break;
				case 'select' :
					if ( ! empty( $post_link ) ) {
						return $post_link;
					}

					$logic_event = $this->get_logic_event( $field, 'change' );
					$css_class   = trim( esc_attr( $class ) . ' gfield_select' );
					$tabindex    = GFCommon::get_tabindex();

					$choices = GFCommon::get_select_choices( $field, $value );

					if ( rgget( 'currencyInitialItemEnabled', $field ) ) {
						$selected = empty( $value ) ? "selected='selected'" : '';
						$choices  = "<option value='' {$selected}>{$field['currencyInitialItem']}</option>" . $choices;
					}

					return sprintf( "<div class='ginput_container'><select name='input_%d' id='%s' $logic_event class='%s' $tabindex %s>%s</select></div>", $id, $field_id, $css_class, $disabled_text, $choices );
					break;
				case "radio" :
					if ( ! empty( $post_link ) ) {
						return $post_link;
					}

					return sprintf( "<div class='ginput_container'><ul class='gfield_radio' id='%s'>%s</ul></div>", $field_id, GFCommon::get_radio_choices( $field, $value, $disabled_text ) );
					break;
			}
		}

		return $field_input;
	}

	/**
	 * @param $position
	 * @param $form_id
	 */
	public function gform_field_standard_settings ( $position, $form_id ) {
		if ( 25 == $position ) {
			$form     = RGFormsModel::get_form_meta( $form_id );
			$currency = ( ! empty( $form['currency'] ) ) ? $form['currency'] : GFCommon::get_currency();
			require_once( GFP_MORE_STRIPE_PATH . '/includes/currency/views/field-setting-currency-settings.php' );
		}
	}

	public function gform_editor_js () {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'gfp_more_stripe_form_editor_currency-field', GFP_MORE_STRIPE_URL . "/includes/currency/js/form_editor-currency-field{$suffix}.js", array( 'gform_form_editor' ), GFPMoreStripe::get_version() );
		$currency_field_vars = array( 'select_currency_text' => __( 'Select a currency', 'gravityforms-stripe-more' ) );
		wp_localize_script( 'gfp_more_stripe_form_editor_currency-field', 'currency_field_vars', $currency_field_vars );
	}

	/**
	 * @param $tooltips
	 *
	 * @return array
	 */
	public function gform_tooltips ( $tooltips ) {

		$currency_field_tooltips = array( 'form_field_currency'              => '<h6>' . __( 'Currency', 'gravityforms-stripe-more' ) . '</h6>' . __( 'Select the currency that will be used for the payments submitted by this form.', 'gravityforms-stripe-more' ),
										  'form_field_currency_selection'    => '<h6>' . __( 'Currency', 'gravityforms-stripe-more' ) . '</h6>' . __( 'Select which currencies are displayed. You can choose to display all of them or select individual ones.', 'gravityforms-stripe-more' ),
										  'stripe_override_default_currency' => '<h6>' . __( 'Override Default Currency', 'gravityforms-stripe-more' ) . '</h6>' . __( 'When the currency override is enabled, the charges for this form will be submitted with the currency in the selected field. **Note** You and your customer may be charged an <a href="https://support.stripe.com/questions/which-currencies-does-stripe-support" target="_blank">extra fee for conversions & foreign transactions</a>.', 'gravityforms-stripe-more' )
		);

		return array_merge( $tooltips, $currency_field_tooltips );
	}

	/**
	 * @param $noconflict_styles
	 *
	 * @return array
	 */
	public function gform_noconflict_styles ( $noconflict_styles ) {
		return array_merge( $noconflict_styles, array( 'gfp_more_stripe_form_editor_currency_css' ) );
	}

	/**
	 * @param $noconflict_scripts
	 *
	 * @return array
	 */
	public function gform_noconflict_scripts ( $noconflict_scripts ) {
		return array_merge( $noconflict_scripts, array( 'gfp_more_stripe_form_editor_currency-field' ) );
	}


	/**
	 * @return array
	 */
	private function currency_button () {

		$button = array(
			'group'   => 'pricing_fields',
			'class'   => 'button',
			'value'   => __( 'Currency', 'gravityforms-stripe-more' ),
			'onclick' => "StartAddCurrencyField()"
		);

		return $button;
	}

	public function get_currency_values () {
		$has_input_name = strtolower( rgpost( 'inputName' ) ) != 'false';

		$id       = ! $has_input_name ? rgpost( 'objectType' ) . '_rule_value_' . rgpost( 'ruleIndex' ) : rgpost( 'inputName' );
		$selected = rgempty( 'selectedValue' ) ? 0 : rgpost( 'selectedValue' );

		$dropdown = GFP_More_Stripe_Currency::dropdown_currencies( array( 'class' => 'gfield_rule_select gfield_rule_value_dropdown gfield_currency_dropdown', 'id' => $id, 'name' => $id, 'selected' => $selected, 'echo' => false ) );
		wp_send_json_success( $dropdown );
	}

	/**
	 * @param $field
	 * @param $value
	 *
	 * @return mixed
	 */
	private function add_currencies_as_choices ( $field, $value, $default_currency ) {
		$choices         = $inputs = array();
		$is_post         = isset( $_POST['gform_submit'] );
		$has_placeholder = rgar( $field, 'currencyInitialItemEnabled' ) && RGFormsModel::get_input_type( $field ) == 'select';

		$currencies = GFP_More_Stripe_Currency::get_currencies();

		$display_all = rgar( $field, 'displayAllCurrencies' );

		if ( ! $display_all ) {
			foreach ( $field['choices'] as $field_choice_to_include ) {
				$included_currencies[] = $field_choice_to_include['value'];
			}
			$currencies = array_intersect( $currencies, $included_currencies );
		}


		foreach ( $currencies as $currency ) {
			if ( $display_all ) {
				$selected  = ( $value == $currency ) ||
					( empty( $value ) &&
						$default_currency == $currency &&
						RGFormsModel::get_input_type( $field ) == 'select' &&
						! $is_post &&
						! $has_placeholder );
				$choices[] = array( 'text' => $currency, 'value' => $currency, 'isSelected' => $selected );
			}
			else {
				foreach ( $field['choices'] as $field_choice ) {
					if ( $field_choice['value'] == $currency ) {
						$choices[] = array( 'text' => $currency, 'value' => $currency );
						break;
					}
				}
			}
		}

		if ( empty( $choices ) ) {
			$choices[] = array( 'text' => 'You must select at least one currency.', 'value' => '' );
		}

		$field['choices'] = $choices;

		return $field;
	}

	/**
	 * @param $currencies
	 * @param $count
	 * @param $currency_rows
	 *
	 * @return string
	 */
	private function currency_rows ( $currencies, $count, $currency_rows ) {
		$output = '';
		foreach ( $currencies as $currency ) {
			$output .= "
		        <tr valign='top'>
		            <th scope='row' class='check-column'><input type='checkbox' class='gfield_currency_checkbox' value='$currency' name='" . esc_attr( $currency ) . "' onclick='SetSelectedCurrencies();' /></th>
		            <td class='gfield_currency_cell'>$currency</td>
		        </tr>";
		}

		return $output;
	}

	/**
	 * @param $field
	 * @param $event
	 *
	 * @return string
	 */
	private function get_logic_event ( $field, $event ) {
		if ( empty( $field["conditionalLogicFields"] ) || IS_ADMIN ) {
			return "";
		}

		switch ( $event ) {
			case "keyup" :
				return "onchange='gf_apply_rules(" . $field["formId"] . "," . GFCommon::json_encode( $field["conditionalLogicFields"] ) . ");' onkeyup='clearTimeout(__gf_timeout_handle); __gf_timeout_handle = setTimeout(\"gf_apply_rules(" . $field["formId"] . "," . GFCommon::json_encode( $field["conditionalLogicFields"] ) . ")\", 300);'";
				break;

			case "click" :
				return "onclick='gf_apply_rules(" . $field["formId"] . "," . GFCommon::json_encode( $field["conditionalLogicFields"] ) . ");'";
				break;

			case "change" :
				return "onchange='gf_apply_rules(" . $field["formId"] . "," . GFCommon::json_encode( $field["conditionalLogicFields"] ) . ");'";
				break;
		}
	}

	/**
	 * @param $field
	 *
	 * @return bool
	 */
	public function is_this_field_type ( $field ) {
		return rgar( $field, 'type' ) == $this->field_type;
	}

	/**
	 * @param $field_input
	 * @param $field
	 * @param $value
	 * @param $lead_id
	 * @param $form_id
	 * @param $price
	 *
	 * @return string
	 */
	private function original_field_input ( $field_input, $field, $value, $lead_id, $form_id, $price ) {
		$id       = $field['id'];
		$field_id = IS_ADMIN || $form_id == 0 ? "input_{$id}" : "input_{$form_id}_{$id}";
		$form     = RGFormsModel::get_form_meta( $form_id );
		$currency = ( ! empty( $form['currency'] ) ) ? $form['currency'] : '';
		switch ( RGFormsModel::get_input_type( $field ) ) {
			case 'calculation' :
			case 'singleproduct' :
				$product_name = ! is_array( $value ) || empty( $value[$field['id'] . '.1'] ) ? esc_attr( $field['label'] ) : esc_attr( $value[$field['id'] . '.1'] );
				$quantity     = is_array( $value ) ? esc_attr( $value[$field['id'] . '.3'] ) : '';

				$has_quantity = sizeof( GFCommon::get_product_fields_by_type( $form, array( 'quantity' ), $field['id'] ) ) > 0;
				if ( $has_quantity ) {
					$field['disableQuantity'] = true;
				}

				$quantity_field = '';

				$qty_input_type = GFFormsModel::is_html5_enabled() ? 'number' : 'text';

				if ( IS_ADMIN ) {
					$style          = rgget( 'disableQuantity', $field ) ? "style='display:none;'" : '';
					$quantity_field = " <span class='ginput_quantity_label' {$style}>" . apply_filters( "gform_product_quantity_{$form_id}", apply_filters( 'gform_product_quantity', __( "Quantity:", "gravityforms" ), $form_id ), $form_id ) . "</span> <input type='{$qty_input_type}' name='input_{$id}.3' value='{$quantity}' id='ginput_quantity_{$form_id}_{$field["id"]}' class='ginput_quantity' size='10' />";
				}
				else if ( ! rgget( 'disableQuantity', $field ) ) {
					$tabindex = GFCommon::get_tabindex();
					$quantity_field .= " <span class='ginput_quantity_label'>" . apply_filters( "gform_product_quantity_{$form_id}", apply_filters( 'gform_product_quantity', __( "Quantity:", "gravityforms" ), $form_id ), $form_id ) . "</span> <input type='{$qty_input_type}' name='input_{$id}.3' value='{$quantity}' id='ginput_quantity_{$form_id}_{$field["id"]}' class='ginput_quantity' size='10' {$tabindex}/>";
				}
				else {
					if ( ! is_numeric( $quantity ) ) {
						$quantity = 1;
					}

					if ( ! $has_quantity ) {
						$quantity_field .= "<input type='hidden' name='input_{$id}.3' value='{$quantity}' class='ginput_quantity_{$form_id}_{$field["id"]} gform_hidden' />";
					}
				}

				return "<div class='ginput_container'><input type='hidden' name='input_{$id}.1' value='{$product_name}' class='gform_hidden' /><span class='ginput_product_price_label'>" . apply_filters( "gform_product_price_{$form_id}", apply_filters( 'gform_product_price', __( 'Price', 'gravityforms-stripe-more' ), $form_id ), $form_id ) . ":</span> <span class='ginput_product_price' id='{$field_id}'>" . esc_html( GFCommon::to_money( $price, $currency ) ) . "</span><input type='hidden' name='input_{$id}.2' id='ginput_base_price_{$form_id}_{$field['id']}' class='gform_hidden' value='" . esc_attr( $price ) . "' />{$quantity_field}</div>";
				break;
			case 'singleshipping' :
				return "<div class='ginput_container'><input type='hidden' name='input_{$id}' value='{$price}' class='gform_hidden'/><span class='ginput_shipping_price' id='{$field_id}'>" . GFCommon::to_money( $price, $currency ) . "</span></div>";

				break;
		}
	}

} 