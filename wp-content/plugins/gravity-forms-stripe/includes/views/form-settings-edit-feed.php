<form method="post" id="gfp_stripe_edit_feed_form">
<?php wp_nonce_field( 'gfp_stripe_save_feed', 'gfp_stripe_save_feed' ) ?>
<input type="hidden" name="stripe_feed_id" id="stripe_feed_id" value="<?php echo $feed_id ?>"/>
<table class="form-table">
	<tr>
		<th colspan="3">
			<h4 class="gaddon-section-title gf_settings_subgroup_title">
				<?php _e( 'Rule Name', 'gravity-forms-stripe' ); ?>
			</h4>
		</th>
	</tr>
	<tr valign="top">
			<th scope="row">
				<label for="gfp_stripe_rule_name">
					<?php _e( 'Rule Name', 'gravity-forms-stripe' ); ?>
					<?php gform_tooltip( 'stripe_rule_name' ) ?>
				</label>
			</th>
			<td>
				<input type="text" class="medium" name="gfp_stripe_rule_name" value="<?php echo rgars( $feed, 'meta/rule_name' ) ?>" />
			</td>
		</tr>
	<tr>
			<th colspan="3">
				<h4 class="gaddon-section-title gf_settings_subgroup_title">
					<?php _e( 'Transaction Settings', 'gravity-forms-stripe' ); ?>
				</h4>
			</th>
		</tr>
	<?php

	if ( has_filter( 'gfp_stripe_feed_transaction_type' ) ) {
		$feed = apply_filters( 'gfp_stripe_feed_transaction_type', $feed, $settings );
	}
	else {
		$feed['meta']['type'] = 'product' ?>

		<input id="gfp_stripe_type" type="hidden" name="gfp_stripe_type" value="product">


	<?php } ?>
	<?php do_action( 'gfp_stripe_feed_after_transaction_type', $feed, $form ); ?>

	<tr id="stripe_form_container"
		valign="top" <?php echo empty( $feed['meta']['type'] ) ? "style='display:none;'" : '' ?>>
		<th scope="row">
			<label for="gfp_stripe_form">
				<?php _e( 'Gravity Form', 'gravity-forms-stripe' ); ?>
				<span class="gfield_required">*</span>
				<?php gform_tooltip( 'stripe_gravity_form' ) ?>
			</label>
		</th>
		<td>
			<select id="gfp_stripe_form" name="gfp_stripe_form"
					onchange="SelectForm(jQuery('#gfp_stripe_type').val(), jQuery(this).val(), '<?php echo rgar( $feed, 'id' ) ?>');">
				<option value=""><?php _e( 'Select a form', 'gravity-forms-stripe' ); ?> </option>
				<?php $active_form = rgar( $feed, 'form_id' ); ?>
				<?php $selected = absint( $form_id ) == rgar( $feed, 'form_id' ) ? 'selected="selected"' : ''; ?>

				<option
					value="<?php echo $form_id ?>" <?php echo $selected; ?>><?php echo esc_html( $form['title'] ) ?></option>
			</select> &nbsp;&nbsp; <img src="<?php echo GFCommon::get_base_url() ?>/images/spinner.gif" id="stripe_wait"
										style="display: none;"/>

			<div id="gfp_stripe_invalid_product_form" class="gfp_stripe_invalid_form" style="display:none;">
				<?php _e( 'The form selected does not have any Product fields. Please add a Product field to the form and try again.', 'gravity-forms-stripe' ) ?>
			</div>
			<div id="gfp_stripe_invalid_creditcard_form" class="gfp_stripe_invalid_form" style="display:none;">
				<?php _e( 'The form selected does not have a credit card field. Please add a credit card field to the form and try again.', 'gravity-forms-stripe' ) ?>
			</div>
		</td>
	</tr>

	<tr id="stripe_field_group"
		valign="top" <?php echo strlen( rgars( $feed, "meta/type" ) ) == 0 || empty( $feed['form_id'] ) ? "style='display:none;'" : '' ?>>


		<td colspan="3">
			<table class="form-table">

				<?php do_action( 'gfp_stripe_feed_before_billing', $feed, $form ); ?>
				<tr>
						<th colspan="3">
							<h4 class="gaddon-section-title gf_settings_subgroup_title">
								<?php _e( 'Customer Information', 'gravity-forms-stripe' ); ?>
							</h4>
						</th>
					</tr>
				<tr valign="top"
					id="gfp_stripe_billing_info" <?php echo ( false == apply_filters( 'gfp_stripe_display_billing_info', true, $feed ) ) ? "style='display:none;'" : '' ?>>
					<th scope="row">
						<label>
							<?php _e( 'Billing Information', 'gravity-forms-stripe' ); ?>
							<?php gform_tooltip( 'stripe_customer' ) ?>
						</label>
					</th>
					<td id="stripe_customer_fields">
						<?php
						if ( ! $new_feed ) {
							echo $this->get_customer_information( $form, $feed );
						}
						?>
					</td>
				</tr>
				<tr>
						<th colspan="3">
							<h4 class="gaddon-section-title gf_settings_subgroup_title">
								<?php _e( 'Other Transaction Settings', 'gravity-forms-stripe' ); ?>
							</h4>
						</th>
					</tr>

				<?php do_action( 'gfp_stripe_feed_after_billing', $feed, $form ); ?>

				<tr valign="top">
					<th scope="row">
						<label>
							<?php _e( 'Options', 'gravity-forms-stripe' ); ?>
							<?php gform_tooltip( 'stripe_options' ) ?>
						</label>
					</th>
					<td>
						<ul style="overflow:hidden;">

							<?php
							$display_post_fields = ( ! $new_feed ) ? GFCommon::has_post_field( $form['fields'] ) : false;
							?>
							<li id="stripe_post_update_action" <?php echo $display_post_fields && 'subscription' == $feed['meta']['type'] ? '' : "style='display:none;'" ?>>
								<input type="checkbox" name="gfp_stripe_update_post" id="gfp_stripe_update_post"
									   value="1" <?php echo rgar( $feed['meta'], 'update_post_action' ) ? "checked='checked'" : "" ?>
									   onclick="var action = this.checked ? 'draft' : ''; jQuery('#gfp_stripe_update_action').val(action);"/>
								<label class="inline"
									   for="gfp_stripe_update_post"><?php _e( 'Update Post when subscription is canceled.', 'gravity-forms-stripe' ); ?> <?php gform_tooltip( 'stripe_update_post' ) ?></label>
								<select id="gfp_stripe_update_action" name="gfp_stripe_update_action"
										onchange="var checked = jQuery(this).val() ? 'checked' : false; jQuery('#gfp_stripe_update_post').attr('checked', checked);">
									<option value=""></option>
									<option
										value="draft" <?php echo 'draft' == rgar( $feed['meta'], 'update_post_action' ) ? "selected='selected'" : "" ?>><?php _e( 'Mark Post as Draft', 'gravity-forms-stripe' ) ?></option>
									<option
										value="delete" <?php echo 'delete' == rgar( $feed['meta'], 'update_post_action' ) ? "selected='selected'" : "" ?>><?php _e( 'Delete Post', 'gravity-forms-stripe' ) ?></option>
								</select>
							</li>

							<?php do_action( 'gfp_stripe_feed_options', $feed, $form, $settings ) ?>
						</ul>
					</td>
				</tr>

				<?php do_action( 'gfp_stripe_feed_setting', $feed, $form ); ?>
				<tr>
						<th colspan="3">
							<h4 class="gaddon-section-title gf_settings_subgroup_title">
								<?php _e( 'Stripe Condition', 'gravity-forms-stripe' ); ?>
							</h4>
						</th>
					</tr>
				<tr id="gfp_stripe_conditional_section" valign="top">
					<th scope="row">
						<label for="gfp_stripe_conditional_optin">
							<?php _e( 'Stripe Condition', 'gravity-forms-stripe' ); ?>
							<?php gform_tooltip( 'stripe_conditional' ) ?>
						</label>
					</th>
					<td>

						<input type="checkbox" id="gfp_stripe_conditional_enabled" name="gfp_stripe_conditional_enabled"
							   value="1"
							   onclick="if(this.checked){jQuery('#gfp_stripe_conditional_container').fadeIn('fast');} else{ jQuery('#gfp_stripe_conditional_container').fadeOut('fast'); }" <?php echo rgar( $feed['meta'], 'stripe_conditional_enabled' ) ? "checked='checked'" : '' ?>/>
						<label for="gfp_stripe_conditional_enable"><?php _e( 'Enable', 'gravity-forms-stripe' ); ?></label> <br/>

						<div
							id="gfp_stripe_conditional_container" <?php echo ! rgar( $feed['meta'], 'stripe_conditional_enabled' ) ? "style='display:none'" : '' ?>>

							<div id="gfp_stripe_conditional_fields" style="display:none">
								<?php _e( 'Send to Stripe if ', 'gravity-forms-stripe' ) ?>

								<select id="gfp_stripe_conditional_field_id" name="gfp_stripe_conditional_field_id"
										class="optin_select"
										onchange='jQuery("#gfp_stripe_conditional_value_container").html(GetFieldValues(jQuery(this).val(), "", 20));'> </select>
								<select id="gfp_stripe_conditional_operator" name="gfp_stripe_conditional_operator">
									<option
										value="is" <?php selected( 'is', rgar( $feed['meta'], 'stripe_conditional_operator' ), true ); ?>>
										<?php _e( 'is', 'gravity-forms-stripe' ) ?>
									</option>
									<option
										value="isnot" <?php selected( 'isnot', rgar( $feed['meta'], 'stripe_conditional_operator' ), true ); ?>>
										<?php _e( 'is not', 'gravity-forms-stripe' ) ?>
									</option>
									<option
										value=">" <?php selected( '>', rgar( $feed['meta'], 'stripe_conditional_operator' ), true ); ?>>
										<?php _e( 'greater than', 'gravity-forms-stripe' ) ?>
									</option>
									<option
										value="<" <?php selected( '<', rgar( $feed['meta'], 'stripe_conditional_operator' ), true ); ?>>
										<?php _e( 'less than', 'gravity-forms-stripe' ) ?>
									</option>
									<option
										value="contains" <?php selected( 'contains', rgar( $feed['meta'], 'stripe_conditional_operator' ), true ); ?>>
										<?php _e( 'contains', 'gravity-forms-stripe' ) ?>
									</option>
									<option
										value="starts_with" <?php selected( 'starts_with', rgar( $feed['meta'], 'stripe_conditional_operator' ), true ); ?>>
										<?php _e( 'starts with', 'gravity-forms-stripe' ) ?>
									</option>
									<option
										value="ends_with" <?php selected( 'ends_with', rgar( $feed['meta'], 'stripe_conditional_operator' ), true ); ?>>
										<?php _e( 'ends with', 'gravity-forms-stripe' ) ?>
									</option>
								</select>

								<div id="gfp_stripe_conditional_value_container"
									 name="gfp_stripe_conditional_value_container" style="display:inline"></div>

							</div>

							<div id="gfp_stripe_conditional_message" style="display:none">
								<?php _e( 'To create a registration condition, your form must have a field supported by conditional logic', 'gravity-forms-stripe' ) ?>
							</div>

						</div>
					</td>

				</tr>
				<!-- / stripe conditional -->            <!--<tr valign="top" id="stripe_submit_container">
				<td>

				</td>
			</tr>-->
			</table>
		</td>


	</tr>
</table>
<p class="submit">
	<?php
	$button_label = $new_feed ? __( 'Save Stripe Rule', 'gravity-forms-stripe' ) : __( 'Update Stripe Rule', 'gravity-forms-stripe' );
	$stripe_feed_button = '<input class="button-primary" type="submit" value="' . $button_label . '" name="save"/>';
	echo apply_filters( 'gfp_stripe_save_feed_button', $stripe_feed_button );


	?>
	<a href="<?php echo $list_url ?>"><input type="button" class="button-secondary"
											 value="<?php _e( 'Cancel', 'gravity-forms-stripe' ) ?>"/></a>
</p>
</form>