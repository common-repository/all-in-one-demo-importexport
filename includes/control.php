
<!-- script for the hide and show dropdown on checked box -->

<script type="text/javascript">
 	jQuery(document).ready(function($){

 		$('.export-filter-content').hide();
 		
 		$('.aiodie-export-all-content').change(function() {
                    var ischecked= $(this).is(':checked');
                    if(ischecked){
                      $('.export-filter-content').show();
                    } else{
                    	$('.export-filter-content').hide();
                    }
                });

  		var form = $('#export-filters'),
  			filters = form.find('.export-filters');
  		filters.hide();
  		form.find('input:radio').change(function() {
 			filters.slideUp('fast');
 			switch ( $(this).val() ) {
 				case 'attachment': $('#attachment-filters').slideDown(); break;
 				case 'posts': $('#post-filters').slideDown(); break;
 				case 'pages': $('#page-filters').slideDown(); break;
 			}
  		});  		
 	});	
  </script>

<div class="aiodie-export-controls">
<label class="aiodie-export-customize">
		<input type="checkbox" id="aiodie-export-customize" name="aiodie-export-customize"  /> <?php _e( 'Customizer Download Export File', 'all-in-one-demo-import-export' ); ?><br>
	</label>
	<label class="aiodie-export-content">
		<input type="checkbox" class="aiodie-export-all-content" name="aiodie-export-content"  /> <?php _e( 'All Content Download Export File', 'all-in-one-demo-import-export' ); ?>
	</label>

<?php
// code for the get the date option 
function export_date_options( $post_type = 'post' ) {
	global $wpdb, $wp_locale;

	$months = $wpdb->get_results( $wpdb->prepare( "
		SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
		FROM $wpdb->posts
		WHERE post_type = %s AND post_status != 'auto-draft'
		ORDER BY post_date DESC
	", $post_type ) );

	$month_count = count( $months );
	if ( !$month_count || ( 1 == $month_count && 0 == $months[0]->month ) )
		return;

	foreach ( $months as $date ) {
		if ( 0 == $date->year )
			continue;

		$month = zeroise( $date->month, 2 );
		echo '<option value="' . $date->year . '-' . $month . '">' . $wp_locale->get_month( $month ) . ' ' . $date->year . '</option>';
	}
}
?>

<div class="wrap">
<div class="export-filter-content">
<h2><?php _e( 'Choose what to export' ); ?></h2>
<form method="GET" id="export-filters" action="<?php bloginfo('url'); ?>">
<fieldset>
<legend class="screen-reader-text"><?php _e( 'Content to export' ); ?></legend>
<input type="hidden" name="download" value="true" />
<p><label><input type="radio" name="content" class="aiodie-content-export" id="aiodie-export-content" value="all" checked="checked" aria-describedby="all-content-desc" /> <?php _e( 'All content' ); ?></label></p>
<p class="description" id="all-content-desc"><?php _e( 'This will contain all of your posts, pages, comments, custom fields, terms, navigation menus, and custom posts.' ); ?></p>

<p><label><input type="radio" name="content" class="aiodie-post-export" id="content-post" value="posts"  /> <?php _e( 'Posts' ); ?></label></p>
<ul id="post-filters" class="export-filters">
	<li>
		<label><span id="cat-label" class="label-responsive"><?php _e( 'Categories:' ); ?></span>
		<?php wp_dropdown_categories( array( 'show_option_all' => __('All') ) ); ?>
		</label>
	</li>
	<li>
		<label><span class="label-responsive"><?php _e( 'Authors:' ); ?></span>
		<?php
		//$authors = $wpdb->get_results( "SELECT DISTINCT post_author FROM $wpdb->posts WHERE post_type = 'post'" );
		wp_dropdown_users( array(
			'include' => '',
			'name' => 'post_author',
			'multi' => true,
			'show_option_all' => __( 'All' ),
			'show' => 'display_name_with_login',
			'id' => 'post_author',
			'class' => 'post-author',
		) ); ?>
		</label>
	</li>
	<li>
		<fieldset>
		<legend class="screen-reader-text"><?php _e( 'Date range:' ); ?></legend>
		<label for="post-start-date" class="label-responsive"><?php _e( 'Start date:' ); ?></label>
		<select name="post_start_date" id="post-start-date" class="post_str_date">
			<option value="0"><?php _e( '&mdash; Select &mdash;' ); ?></option>
			<?php export_date_options(); ?>
		</select>
		<label for="post-end-date" class="label-responsive"><?php _e( 'End date:' ); ?></label>
		<select name="post_end_date" id="post-end-date" class="post_en_date">
			<option value="0"><?php _e( '&mdash; Select &mdash;' ); ?></option>
			<?php export_date_options(); ?>
		</select>
		</fieldset>
	</li>
	<li>
		<label for="post-status" class="label-responsive"><?php _e( 'Status:' ); ?></label>
		<select name="post_status" id="post-status" class="post_sts">
			<option value="0"><?php _e( 'All' ); ?></option>
			<?php $post_stati = get_post_stati( array( 'internal' => false ), 'objects' );
			foreach ( $post_stati as $status ) : ?>
			<option value="<?php echo esc_attr( $status->name);  ?>"><?php echo esc_html( $status->label ); ?></option>
			<?php endforeach; ?>
		</select>
	</li>
</ul>

<p><label><input type="radio" name="content" class="aiodie-page-export" id="page-content" value="pages" /> <?php _e( 'Pages' ); ?></label></p>
<ul id="page-filters" class="export-filters">
	<li>
		<label><span class="label-responsive"><?php _e( 'Authors:' ); ?></span>
		<?php
		//$authors = $wpdb->get_col( "SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_type = 'page'" );
		wp_dropdown_users( array(
			'include' => '',
			'name' => 'page_author',
			'multi' => true,
			'show_option_all' => __( 'All' ),
			'show' => 'display_name_with_login',
			'id' => 'page_author',
			'class' => 'page-author',
		) ); ?>
		</label>
	</li>
	<li>
		<fieldset>
		<legend class="screen-reader-text"><?php _e( 'Date range:' ); ?></legend>
		<label for="page-start-date" class="label-responsive"><?php _e( 'Start date:' ); ?></label>
		<select name="page_start_date" id="page-start-date" class="page_str_date">
			<option value="0"><?php _e( '&mdash; Select &mdash;' ); ?></option>
			<?php export_date_options( 'page' ); ?>
		</select>
		<label for="page-end-date" class="label-responsive"><?php _e( 'End date:' ); ?></label>
		<select name="page_end_date" id="page-end-date" class="page_en_date">
			<option value="0"><?php _e( '&mdash; Select &mdash;' ); ?></option>
			<?php export_date_options( 'page' ); ?>
		</select>
		</fieldset>
	</li>
	<li>
		<label for="page-status" class="label-responsive"><?php _e( 'Status:' ); ?></label>
		<select name="page_status" id="page-status" class="page_sts">
			<option value="0"><?php _e( 'All' ); ?></option>
			<?php foreach ( $post_stati as $status ) : ?>
			<option value="<?php echo esc_attr( $status->name ); ?>"><?php echo esc_html( $status->label ); ?></option>
			<?php endforeach; ?>
		</select>
	</li>
</ul>

<?php foreach ( get_post_types( array( '_builtin' => false, 'can_export' => true ), 'objects' ) as $post_type ) : ?>
<p><label><input type="radio" name="content" class="aiodie-others-export"  value="<?php echo esc_attr( $post_type->name ); ?>" /> <?php echo esc_html( $post_type->label ); ?></label></p>
<?php endforeach; ?>

<p><label><input type="radio" name="content" class="aiodie-attachment-export" id="content-attachment" value="attachment" /> <?php _e( 'Media' ); ?></label></p>
<ul id="attachment-filters" class="export-filters">
	<li>
		<fieldset>
		<legend class="screen-reader-text"><?php _e( 'Date range:' ); ?></legend>
		<label for="attachment-start-date" class="label-responsive"><?php _e( 'Start date:' ); ?></label>
		<select name="attachment_start_date" id="attachment-start-date" class="attach-start-date">
			<option value="0"><?php _e( '&mdash; Select &mdash;' ); ?></option>
			<?php export_date_options( 'attachment' ); ?>
		</select>
		<label for="attachment-end-date" class="label-responsive"><?php _e( 'End date:' ); ?></label>
		<select name="attachment_end_date" id="attachment-end-date" class="attach-end-date">
			<option value="0"><?php _e( '&mdash; Select &mdash;' ); ?></option>
			<?php export_date_options( 'attachment' ); ?>
		</select>
		</fieldset>
	</li>
</ul>

</fieldset>
</form>
</div>

<input type="button" class="button" name="aiodie-export-button" value="<?php esc_attr_e( 'Export', 'all-in-one-demo-import-export' ); ?>" />
</div>

<hr class="aiodie-hr" />
<span class="customize-control-title">
	<?php _e( 'Import', 'all-in-one-demo-import-export'); ?>
</span>
<span class="description customize-control-description">
	<?php _e( 'Upload a file to import customization settings for this theme.', 'all-in-one-demo-import-export' ); ?>
</span>
<div class="aiodie-import-controls">
	<input type="file" name="aiodie-import-file" class="aiodie-import-file" />
	<label class="aiodie-import-images">
		<input type="checkbox" name="aiodie-import-images" value="1" /> <?php _e( 'Download and import image files?', 'all-in-one-demo-import-export' ); ?>
	</label>
	<?php wp_nonce_field( 'aiodie-importing', 'aiodie-import' ); ?>
</div>
<div class="aiodie-uploading"><?php _e( 'Uploading...', 'all-in-one-demo-import-export' ); ?></div>
<input type="button" class="button" name="aiodie-import-button" value="<?php esc_attr_e( 'Import', 'all-in-one-demo-import-export' ); ?>" />