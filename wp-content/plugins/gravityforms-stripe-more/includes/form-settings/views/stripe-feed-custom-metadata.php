<?php
/**
 *
 */
?>
<tr valign="top" id="stripe_field_container_custom_metadata"
	<?php echo ( (rgars( $feed, 'meta/type' ) != 'subscription')&& (rgars( $feed, 'meta/type' ) != 'product') ) ? "style='display:none;'" : '' ?>>
	<th scope="row">
		<label for="gfp_more_stripe_custom_metadata">
			<?php _e( 'Custom Metadata', 'gravityforms-stripe-more' ); ?>
		</label>
	</th>
	<td>
		<input type="checkbox"
					   name="gfp_more_stripe_enable_metadata"
					   id="gfp_more_stripe_enable_metadata"
					   value="1"
					   <?php echo rgars( $feed, "meta/metadata_enabled" ) ? "checked='checked'" : "" ?> />
				<label class="inline" for="gfp_more_stripe_enable_metadata"><?php _e( 'Enable', 'gravityforms-stripe-more' ); ?></label>
		<br />
		<span id="custom_metadata_fields" <?php echo rgars( $feed, 'meta/metadata_enabled' ) ? "" : "style='display:none;'" ?>>
								</span>
	</td>
</tr>