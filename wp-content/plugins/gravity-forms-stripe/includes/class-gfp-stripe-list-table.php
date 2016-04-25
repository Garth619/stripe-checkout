<?php
/** @package   GFP_Stripe_List_Table
		 * @copyright 2014 gravity+
		 * @license   GPL-2.0+
		 * @since     1.8.2
		 */

/**
 * Class GFP_Stripe_List_Table
 *
 * @since 1.8.2
 */
class GFP_Stripe_List_Table extends WP_List_Table {

	private $_form_id;

	function __construct ( $form_id ) {

		$this->_form_id = $form_id;

		$this->_column_headers = array(
			array(
				'cb'   => '',
				'name' => __( 'Rule Name', 'gravity-forms-stripe' ),
				'type' => __( 'Transaction Type', 'gravity-forms-stripe' )
			),
			array(),
			array()
		);

		parent::__construct();
	}
    
    public function get_columns(){
        
		return $this->_column_headers[0];
	}

	function prepare_items () {

		$feeds       = GFP_Stripe_Data::get_feed_by_form( $this->_form_id );
		$this->items = $feeds;
	}

	function display () {
		$singular = $this->_args['singular'];
		?>

		<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>

			<tfoot>
			<tr>
				<?php $this->print_column_headers( false ); ?>
			</tr>
			</tfoot>

			<tbody id="the-list"<?php if ( $singular ) {
				echo " class='list:$singular'";
			} ?>>

			<?php $this->display_rows_or_placeholder(); ?>

			</tbody>
		</table>

	<?php
	}

	function single_row ( $item ) {
		static $row_class = '';
		$row_class = ( $row_class == '' ? ' class="alternate"' : '' );

		echo '<tr id="stripe-' . $item['id'] . '" ' . $row_class . '>';
		echo $this->single_row_columns( $item );
		echo '</tr>';
	}

	function column_default ( $item, $column ) {
		echo rgar( $item, $column );
	}

	function column_cb ( $item ) {
		$is_active = isset( $item['is_active'] ) ? $item['is_active'] : true;
		?>
		<img src="<?php echo GFCommon::get_base_url() ?>/images/active<?php echo intval( $is_active ) ?>.png"
			 style="cursor: pointer;"
			 alt="<?php echo $is_active ? __( 'Active', 'gravity-forms-stripe' ) : __( 'Inactive', 'gravity-forms-stripe' ); ?>"
			 title="<?php echo $is_active ? __( 'Active', 'gravity-forms-stripe' ) : __( 'Inactive', 'gravity-forms-stripe' ); ?>"
			 onclick="ToggleStripeFeedActive( this, '<?php echo $item['id'] ?>', '<?php echo $item['form_id'] ?>' ); "/>
	<?php
	}

	function column_name ( $item ) {
		$edit_url = add_query_arg( array( 'sid' => $item['id'] ) );
		$actions  = apply_filters( 'gfp_stripe_feed_actions', array(
			'edit'   => '<a title="' . __( 'Edit this item', 'gravity-forms-stripe' ) . '" href="' . $edit_url . '">' . __( 'Edit', 'gravity-forms-stripe' ) . '</a>',
			'delete' => '<a title="' . __( 'Delete this item', 'gravity-forms-stripe' ) . '" class="submitdelete" onclick="javascript: if(confirm(\'' . __( "WARNING: You are about to delete this Stripe rule.", "gfp-stripe" ) . __( "\'Cancel\' to stop, \'OK\' to delete.", "gfp-stripe" ) . '\')){ DeleteStripeFeed(\'' . $item["id"] . '\'); }" style="cursor:pointer;">' . __( 'Delete', 'gravity-forms-stripe' ) . '</a>'
		) );
		?>

		<strong><?php echo rgars( $item, 'meta/rule_name' ); ?></strong>
		<div class="row-actions">

			<?php
			if ( is_array( $actions ) && ! empty( $actions ) ) {
				$action_keys = array_keys( $actions );
				$last_key = array_pop( $action_keys );
				foreach ( $actions as $key => $html ) {
					$divider = $key == $last_key ? '' : " | ";
					?>
					<span class="<?php echo $key; ?>">
				                        <?php echo $html . $divider; ?>
				                    </span>
				<?php
				}
			}
			?>

		</div>

	<?php
	}

	function column_type ( $item ) {
		if ( has_action( 'gfp_stripe_list_feeds_product_type' ) ) {
			do_action( 'gfp_stripe_list_feeds_product_type', $item );
		}
		else {
			switch ( $item['meta']['type'] ) {
				case 'product' :
					_e( 'One-Time Payment', 'gravity-forms-stripe' );
					break;
			}
		}
	}

	function no_items () {
		$add_new_url = add_query_arg( array( 'sid' => 0 ) );
		printf( __( "You currently don't have any Stripe Rules, let's go %screate one%s", 'gravity-forms-stripe' ), "<a href='{$add_new_url}'>", "</a>" );
	}
} 