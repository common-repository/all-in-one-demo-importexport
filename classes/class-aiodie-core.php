<?php

/**
 * The main export/import class.
 *
 * @since 0.1
 */
final class AIODIE_Core {

	/**
	 * An array of core options that shouldn't be imported.
	 *
	 * @since 0.3
	 * @access private
	 * @var array $core_options
	 */
	static private $core_options = array(
		'blogname',
		'blogdescription',
		'show_on_front',
		'page_on_front',
		'page_for_posts',
	);

	/**
	 * Load a translation for this plugin.
	 *
	 * @since 0.1
	 * @return void
	 */	 
	static public function load_plugin_textdomain() 
	{
		load_plugin_textdomain( 'all-in-one-demo-import-export', false, basename( AIODIE_PLUGIN_DIR ) . '/lang/' );
	}
	
	/**
	 * Check to see if we need to do an export or import.
	 * This should be called by the customize_register action.
	 *
	 * @since 0.1
	 * @since 0.3 Passing $wp_customize to the export and import methods.
	 * @param object $wp_customize An instance of WP_Customize_Manager.
	 * @return void
	 */
	static public function init( $wp_customize ) 
	{
		if ( current_user_can( 'edit_theme_options' ) ) {
			
			if ( isset( $_REQUEST['aiodie-export'] ) ) {
				self::_export( $wp_customize );
			}
			if ( isset( $_REQUEST['aiodie-import'] ) && isset( $_FILES['aiodie-import-file'] ) ) {
				self::_import( $wp_customize );
			}
		}
	}
	
	/**
	 * Prints scripts for the control.
	 *
	 * @since 0.1
	 * @return void
	 */
	static public function controls_print_scripts() 
	{
		global $cei_error;
		
		if ( $cei_error ) {
			echo '<script> alert("' . $cei_error . '"); </script>';
		}
	}
	
	/**
	 * Enqueues scripts for the control.
	 *
	 * @since 0.1
	 * @return void
	 */
	static public function controls_enqueue_scripts() 
	{
		// Register
		wp_register_style( 'aiodie-css', AIODIE_PLUGIN_URL . '/css/customizer.css', array(), AIODIE_VERSION );
		wp_register_script( 'aiodie-js', AIODIE_PLUGIN_URL . '/js/customizer.js', array( 'jquery' ), AIODIE_VERSION, true );

		// Localize
		wp_localize_script( 'aiodie-js', 'AIODIEl10n', array(
			'emptyImport'	=> __( 'Please choose a file to import.', 'all-in-one-demo-import-export' )
		));
		
		// Config
		wp_localize_script( 'aiodie-js', 'AIODIEConfig', array(
			'customizerURL'	  => admin_url( 'customize.php' ),
			'exportNonce'	  => wp_create_nonce( 'aiodie-exporting' )
		));

		// Enqueue
		wp_enqueue_style( 'aiodie-css' );
		wp_enqueue_script( 'aiodie-js' );
	}
	
	/**
	 * Registers the control with the customizer.
	 *
	 * @since 0.1
	 * @param object $wp_customize An instance of WP_Customize_Manager.
	 * @return void
	 */
	static public function register( $wp_customize ) 
	{
		require_once AIODIE_PLUGIN_DIR . 'classes/class-aiodie-control.php';

		// Add the export/import section.
		$wp_customize->add_section( 'aiodie-section', array(
			'title'	   => __( 'Export/Import', 'all-in-one-demo-import-export' ),
			'priority' => 10000000
		));
		
		// Add the export/import setting.
		$wp_customize->add_setting( 'aiodie-setting', array(
			'default' => '',
			'type'	  => 'none'
		));		
		// Add the export/import control.
		$wp_customize->add_control( new AIODIE_Control( 
			$wp_customize, 
			'aiodie-setting', 
			array(
				'section'	=> 'aiodie-section',
				'priority'	=> 1
			)
		));
	}	
	/**
	 * Export customizer settings.		
	 *
	 * @since 0.1
	 * @since 0.3 Added $wp_customize param and exporting of options.
	 * @access private
	 * @param object $wp_customize An instance of WP_Customize_Manager.
	 * @return void
	 */


	static private function _export( $wp_customize ) 
	{
		if ( ! wp_verify_nonce( $_REQUEST['aiodie-export'], 'aiodie-exporting' ) ) {
			return;
		}
		
		$theme		= get_stylesheet();
		$template	= get_template();
		$taxonomies = get_taxonomies();

		$charset = get_option( 'blog_charset' );
		$mods = get_theme_mods();
		$category = (array) get_categories( array( 'get' => 'all' ) );	
		$posts = get_posts( array('numberposts' => -1, 'get' => 'all' ));	
		$postcategory = array();
		$termlist = array();
		$customfields = array();
		$customfieldspage = array();
		$postthumbnail_url = array();

		foreach($posts as $post){

			$custom = get_post_custom($post->ID);			
			$customfields[$post->ID] = $custom;			
			$post_categories = wp_get_post_categories($post->ID);
			$postcategory[$post->ID] = $post_categories;
			$thumbnailpost_url = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), array('220','220'), true );
			$postthumbnail_url[$post->ID] = $thumbnailpost_url[0];
			$term_list = wp_get_post_tags( $post->ID);

			$tags = '';
			foreach($term_list as $value){
				$tags .= $value->name.',';
			}
			$termlist[$post->ID] = $tags;
		}

		$page = get_pages( array('get' => 'all') );		
		$pagethumbnail_url = array();
		
		foreach($page as $pagepost){
			$customfil = get_post_custom($pagepost->ID);			
			$customfieldspage[$pagepost->ID] = $customfil;
			$thumbnailpage_url = wp_get_attachment_image_src(get_post_thumbnail_id($pagepost->ID), array('220','220'), true );
			$pagethumbnail_url[$pagepost->ID] = $thumbnailpage_url[0];
		}

		$status = get_post_statuses();
		$comment = get_comments(array('numberposts' => -1));
		$menu = get_registered_nav_menus();
		$tags = get_tags(array('get'=>'all'));
		$users = get_users('fields=all_with_meta');

		
		if(!empty($_GET['aiodie-export-customize']) && !empty($_GET['aiodie-export-content'])){	

			/* Code for Get the customizer and all content data */
			$data = array(
				'template'  => $template,
				'mods'	    => $mods ? $mods : array(),
				'options'   => array(),
				'post'      => $posts,
				'page'      => $page,
				'comment'   => $comment,                
				'category'  => $category,
				'tags'      => $tags,
				'post_category' => $postcategory,
				'tags_input' => $termlist,
				'post_attachment' => $postthumbnail_url,
				'page_attachment' => $pagethumbnail_url,
				'custom_fields' => $customfields,
				'custom_fieldspage' =>  $customfieldspage
			);
			
		}else if(!empty($_GET['aiodie-export-customize']) && !empty($_GET['aiodie-post-export'])){

			/* Code for the customizer and all post data */
			$cat = $_GET['postform'];
			$postauthor = $_GET['post-author'];
			$poststatus = $_GET['post_sts'];
			$poststartdate = date( 'Y-m-d', strtotime($_GET['post_str_date']));
			$postenddate = date( 'Y-m-d', strtotime('+1 month', strtotime($_GET['post_en_date'])) );
			$category_args = array(
				'hide_empty' => 0,
				'orderby'    => 'include',
                'include'    => array( $cat) //exact order id
            );
			$category_terms = get_categories($category_args);

			$postargs = array(
				'numberposts' => -1, 
				'get' => 'all',
				'date_query' => array(
					array(
						'after'     => $poststartdate,
						'before'    => $postenddate,
						'inclusive' => true,
					),
				),
				'cat' => $cat,
				'author' => $postauthor, 
				'post_status' => $poststatus,
				'posts_per_page' => -1,
			);
			$query = new WP_Query( $postargs );
			$postcontent = $query->posts;
			$tags = get_tags();
			$comment = get_comments(array('numberposts' => -1));
			$postcontcategory = array();
			$termcontlist = array();
			$customcontfields = array();
			$customcontfieldspage = array();
			$postcontthumbnail_url = array();

			foreach($postcontent as $postcont){
				$customcont = get_post_custom($postcont->ID);
				$customcontfields[$postcont->ID] = $customcont;
				$postcont_categories = wp_get_post_categories($postcont->ID);
				$postcontcategory[$postcont->ID] = $postcont_categories;
				$thumbnailpostcont_url = wp_get_attachment_image_src(get_post_thumbnail_id($postcont->ID), array('220','220'), true );
				$postcontthumbnail_url[$postcont->ID] = $thumbnailpostcont_url[0];
				$termcont_list = wp_get_post_tags( $postcont->ID);
				$tagscont = '';
				foreach($termcont_list as $valuecont){
					$tagscont .= $valuecont->name.',';
				}
				$termcontlist[$postcont->ID] = $tagscont;
			}

			$data = array(
				'template'  => $template,
				'mods'	    => $mods ? $mods : array(),
				'options'   => array(),
				'post'      => $postcontent,
				'comment'   => $comment,
				'category'  => $category_terms,
				'tags'      => $tags,
				'post_attachment' => $postcontthumbnail_url,
				'post_category' => $postcontcategory,
				'tags_input' => $termcontlist,
				'custom_fields' => $customcontfields,				
			);
			
		}else if(!empty($_GET['aiodie-export-customize']) && !empty($_GET['aiodie-page-export'])){
			/* Code for Get the customizer and all page data */
			$pageauthor = $_GET['page-author'];
			$pagestartdate = date( 'Y-m-d', strtotime($_GET['page_str_date']));
			$pageenddate = date( 'Y-m-d', strtotime('+1 month', strtotime($_GET['page_en_date'])) );
			$pagestatus = $_GET['page_sts'];
			$pageargs = array(
				'post_type' => 'page',
				'numberposts' => -1, 
				'get' => 'all',
				'date_query' => array(
					array(
						'after'     => $pagestartdate,
						'before'    => $pageenddate,
						'inclusive' => true,
					),
				),
				'author' => $pageauthor, 
				'post_status' => $pagestatus,
				'posts_per_page' => -1,
			);
			$query = new WP_Query( $pageargs );
			$pagecontent = $query->posts;

			$customcontentfieldspage = array();
			$pagecontentthumbnail_url = array();

			foreach($pagecontent as $pagecontentpost){
				$customcontentfil = get_post_custom($pagecontentpost->ID);
				$customcontentfieldspage[$pagecontentpost->ID] = $customcontentfil;
				$thumbnailpagecontent_url = wp_get_attachment_image_src(get_post_thumbnail_id($pagecontentpost->ID), array('220','220'), true );
				$pagecontentthumbnail_url[$pagecontentpost->ID] = $thumbnailpagecontent_url[0];
			}
			$data = array(
				'template'  => $template,
				'mods'	    => $mods ? $mods : array(),
				'options'   => array(),
				'page'      => $pagecontent,
				'page_attachment' => $pagecontentthumbnail_url,
				'custom_fieldspage' =>  $customcontentfieldspage,
			);
			
		}else if(!empty($_GET['aiodie-export-customize']) && !empty($_GET['aiodie-attachment-export'])){

			/* Code for Get the customizer and all attachment data */
			global $wpdb, $post;
			$attachstartdate = date( 'Y-m-d', strtotime($_GET['attach-start-date']));
			$attachenddate = date( 'Y-m-d', strtotime('+1 month', strtotime($_GET['attach-end-date'])) );	
			$query_images = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE $wpdb->posts.post_date >='".$attachstartdate."' AND $wpdb->posts.post_date < '".$attachenddate."' AND post_type = 'attachment'");
			$images = array();
			foreach ( $query_images as $image) {
				$images[]= $image->guid;
			}
			$data = array(
				'template'  => $template,
				'mods'	    => $mods ? $mods : array(),
				'options'   => array(),
				'attachment' => $images,
			);
			
		}else if(!empty($_GET['aiodie-export-customize']) && !empty($_GET['aiodie-others-export'])){

			$postty = $_GET['post_type'];
			$otherargs = array(
				'post_type' => $postty,
				'posts_per_page' => -1,
				'orderby' 		=> 'title', // or 'date', 'rand'
  				'order' 		=> 'ASC', // or 'DESC'
  				'post_status'   => 'any',
    		// Several more arguments could go here. Last one without a comma.
  			);
			$query = new WP_Query( $otherargs );
			$othercontent = $query->posts;
			$taxonomies = get_object_taxonomies($postty);
			$comments = get_comments(array('numberposts' => -1));	
			$customotherfields = array();
			$postothercategory = array();
			$postotherthumbnail_url =array();
			$taxo = array();
			$getterms = '';

			foreach($othercontent as $othercont){

				$customother = get_post_custom($othercont->ID);
				$customotherfields[$othercont->ID] = $customother;
				$thumbnailpostother_url = wp_get_attachment_image_src(get_post_thumbnail_id($othercont->ID), array('220','220'), true );
				$postotherthumbnail_url[$othercont->ID] = $thumbnailpostother_url[0];

				foreach ($taxonomies as $taxonomy) {
					$taxo[] = $taxonomy;
					$terms = wp_get_post_terms($othercont->ID, $taxo);
					$getterms = get_terms($taxo);
					$postterm = array();
					foreach($terms as $postcat){
						$postterm[] = $postcat->term_id;
					}
					$postothercategory[$othercont->ID] = $postterm;
				}
			}

			/* Code for Get the all attachment data */
			$data = array(
				'template'  => $template,
				'mods'	    => $mods ? $mods : array(),
				'options'   => array(),
				'post'      => $othercontent,
				'category'  => $getterms,
				'post_attachment' => $postotherthumbnail_url,
				'post_category' => $postothercategory,
				'custom_fields' => $customotherfields,
				'comment'   => $comments,
			);
			
		}else if(!empty($_GET['aiodie-export-customize'])) {
			/* Code for Get the customizer data */
			$data = array(
				'template'  => $template,
				'mods'	    => $mods ? $mods : array(),
				'options'   => array(),
			);		
		} else if(!empty($_GET['aiodie-export-content'])){

			/* Code for Get the all content data */
			$data = array(
				'post'      => $posts,
				'page'      => $page,
				'comment'   => $comment,                
				'category'  => $category,
				'status'    => $status,
				'menu'      => $menu,
				'tags'      => $tags,
				'users'     => $users,
				'post_category' => $postcategory,
				'tags_input' => $termlist,
				'post_attachment' => $postthumbnail_url,
				'page_attachment' => $pagethumbnail_url,
				'custom_fields' => $customfields,
				'custom_fieldspage' =>  $customfieldspage
			);
			
		} else if(!empty($_GET['aiodie-post-export'])){

			/* Code for Get the all post data */
			$cat = $_GET['postform'];
			$postauthor = $_GET['post-author'];
			$poststatus = $_GET['post_sts'];
			$poststartdate = date( 'Y-m-d', strtotime($_GET['post_str_date']));
			$postenddate = date( 'Y-m-d', strtotime('+1 month', strtotime($_GET['post_en_date'])) );

			$category_args = array(
				'hide_empty' => 0,
				'orderby'    => 'include',
                'include'    => array( $cat) //exact order id
            );
			$category_terms = get_categories($category_args);

			$postargs = array(
				'numberposts' => -1, 
				'get' => 'all',
				'date_query' => array(
					array(
						'after'     => $poststartdate,
						'before'    => $postenddate,
						'inclusive' => true,
					),
				),
				'cat' => $cat,
				'author' => $postauthor, 
				'post_status' => $poststatus,
				'posts_per_page' => -1,
			);
			$query = new WP_Query( $postargs );
			$postcontent = $query->posts;
			$tags = get_tags();
			$comment = get_comments(array('numberposts' => -1));
			$postcontcategory = array();
			$termcontlist = array();
			$customcontfields = array();
			$customcontfieldspage = array();
			$postcontthumbnail_url = array();

			foreach($postcontent as $postcont){
				$customcont = get_post_custom($postcont->ID);
				$customcontfields[$postcont->ID] = $customcont;
				$postcont_categories = wp_get_post_categories($postcont->ID);
				$postcontcategory[$postcont->ID] = $postcont_categories;
				$thumbnailpostcont_url = wp_get_attachment_image_src(get_post_thumbnail_id($postcont->ID), array('220','220'), true );
				$postcontthumbnail_url[$postcont->ID] = $thumbnailpostcont_url[0];
				$termcont_list = wp_get_post_tags( $postcont->ID);
				$tagscont = '';
				foreach($termcont_list as $valuecont){
					$tagscont .= $valuecont->name.',';
				}
				$termcontlist[$postcont->ID] = $tagscont;
			}
			$data = array(
				'post'      => $postcontent,
				'comment'   => $comment,
				'category'  => $category_terms,
				'tags'      => $tags,
				'post_attachment' => $postcontthumbnail_url,
				'post_category' => $postcontcategory,
				'tags_input' => $termcontlist,
				'custom_fields' => $customcontfields,				
			);
			
		}else if(!empty($_GET['aiodie-page-export'])){
			/* Code for Get the all page data */
			$pageauthor = $_GET['page-author'];
			$pagestartdate = date( 'Y-m-d', strtotime($_GET['page_str_date']));
			$pageenddate = date( 'Y-m-d', strtotime('+1 month', strtotime($_GET['page_en_date'])) );
			$pagestatus = $_GET['page_sts'];
			$pageargs = array(
				'post_type' => 'page',
				'numberposts' => -1, 
				'get' => 'all',
				'date_query' => array(
					array(
						'after'     => $pagestartdate,
						'before'    => $pageenddate,
						'inclusive' => true,
					),
				),
				'author' => $pageauthor, 
				'post_status' => $pagestatus,
				'posts_per_page' => -1,
			);
			$query = new WP_Query( $pageargs );
			$pagecontent = $query->posts;
			$customcontentfieldspage = array();
			$pagecontentthumbnail_url = array();

			foreach($pagecontent as $pagecontentpost){
				$customcontentfil = get_post_custom($pagecontentpost->ID);
				$customcontentfieldspage[$pagecontentpost->ID] = $customcontentfil;
				$thumbnailpagecontent_url = wp_get_attachment_image_src(get_post_thumbnail_id($pagecontentpost->ID), array('220','220'), true );
				$pagecontentthumbnail_url[$pagecontentpost->ID] = $thumbnailpagecontent_url[0];
			}
			$data = array(
				'page'      => $pagecontent,
				'page_attachment' => $pagecontentthumbnail_url,
				'custom_fieldspage' =>  $customcontentfieldspage,
			);
			
		}else if(!empty($_GET['aiodie-attachment-export'])){
			/* Code for Get the all attachment data */
			global $wpdb, $post;
			$attachstartdate = date( 'Y-m-d', strtotime($_GET['attach-start-date']));
			$attachenddate = date( 'Y-m-d', strtotime('+1 month', strtotime($_GET['attach-end-date'])) );

			$query_images = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE $wpdb->posts.post_date >='".$attachstartdate."' AND $wpdb->posts.post_date < '".$attachenddate."' AND post_type = 'attachment'");
			$images = array();
			foreach ( $query_images as $image) {
				$images[]= $image->guid;
			}
			$data = array(
				'attachment' => $images,
			);
			
		}else if(!empty($_GET['aiodie-others-export'])){

			$postty = $_GET['post_type'];
			$otherargs = array(
				'post_type' => $postty,
				'posts_per_page' => -1,
				'orderby' 		=> 'title', // or 'date', 'rand'
  				'order' 		=> 'ASC', // or 'DESC'
  				'post_status'   => 'any',
    		// Several more arguments could go here. Last one without a comma.
  			);
			$query = new WP_Query( $otherargs );
			//$categories = get_categories($otherargs);
			
			$othercontent = $query->posts;
			$taxonomies = get_object_taxonomies($postty);
			$customotherfields = array();
			$postothercategory = array();
			$postotherthumbnail_url = array();
			$taxo = array();
			$getterms = '';
			$comments = get_comments(array('numberposts' => -1));
			
			foreach($othercontent as $othercont){

				$customother = get_post_custom($othercont->ID);
				$customotherfields[$othercont->ID] = $customother;
				$thumbnailpostother_url = wp_get_attachment_image_src(get_post_thumbnail_id($othercont->ID), array('220','220'), true );
				$postotherthumbnail_url[$othercont->ID] = $thumbnailpostother_url[0];

				foreach ($taxonomies as $taxonomy) {

					$taxo[] = $taxonomy;
					$terms = wp_get_post_terms($othercont->ID, $taxo);
					$getterms = get_terms($taxo);
					$postterm = array();
					foreach($terms as $postcat){
						$postterm[] = $postcat->term_id;
					}
					$postothercategory[$othercont->ID] = $postterm;
				}
			}
			/* Code for Get the all attachment data */
			$data = array(
				'post'      => $othercontent,
				'category'  => $getterms,
				'post_attachment' => $postotherthumbnail_url,
				'post_category' => $postothercategory,
				'custom_fields' => $customotherfields,
				'comment'   => $comments,
			);

		}
		
		// Get options from the Customizer API.
		$settings = $wp_customize->settings();

		foreach ( $settings as $key => $setting ) {
			if ( 'option' == $setting->type ) {

				// Don't save widget data.
				if ( stristr( $key, 'widget_' ) ) {
					continue;
				}
				// Don't save sidebar data.
				if ( stristr( $key, 'sidebars_' ) ) {
					continue;
				}
				// Don't save core options.
				if ( in_array( $key, self::$core_options ) ) {
					continue;
				}
				$data['options'][ $key ] = $setting->value();
			}
		}

		// Plugin developers can specify additional option keys to export.
		$option_keys = apply_filters( 'aiodie_export_option_keys', array() );

		foreach ( $option_keys as $option_key ) {

			$option_value = get_option( $option_key );

			if ( $option_value ) {
				$data['options'][ $option_key ] = $option_value;
			}
		}
		if( function_exists( 'wp_get_custom_css_post' ) ) {
			$data['wp_css'] = wp_get_custom_css();
		}
		// Set the download headers.
		header( 'Content-disposition: attachment; filename=' . $theme . '-export.dat' );
		header( 'Content-Type: application/octet-stream; charset=' . $charset );

		// Serialize the export data.
		echo serialize($data);

		// Start the download.
		die();
	}

	/**
	 * Imports uploaded mods and calls WordPress core customize_save actions so
	 * themes that hook into them can act before mods are saved to the database.
	 *
	 * @since 0.1
	 * @since 0.3 Added $wp_customize param and importing of options.
	 * @access private
	 * @param object $wp_customize An instance of WP_Customize_Manager.
	 * @return void
	 */
	static private function _import( $wp_customize ) 
	{
		// Make sure we have a valid nonce.
		if ( ! wp_verify_nonce( $_REQUEST['aiodie-import'], 'aiodie-importing' ) ) {
			return;
		}		
		// Make sure WordPress upload support is loaded.
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}		
		// Load the export/import option class.
		require_once AIODIE_PLUGIN_DIR . 'classes/class-aiodie-option.php';
		
		// Setup global vars.
		global $wp_customize;
		global $cei_error;
		
		// Setup internal vars.
		$cei_error	 = false;
		$template	 = get_template();
		// for the posts content
		// $category = (array) get_categories( array( 'get' => 'all' ) );
		// $post = get_posts();
		// $page = get_pages();
		// $comment = get_comments();
		// $menu = get_terms('nav_menu');
		// $tags = get_tags(array('get'=>'all'));	
		
		$overrides   = array( 'test_form' => false, 'test_type' => false, 'mimes' => array('dat' => 'text/plain') );
		$file = wp_handle_upload( $_FILES['aiodie-import-file'], $overrides );
		// Make sure we have an uploaded file.
		if ( isset( $file['error'] ) ) {
			$cei_error = $file['error'];
			return;
		}
		if ( ! file_exists( $file['file'] ) ) {
			$cei_error = __( 'Error importing settings! Please try again.', 'all-in-one-demo-import-export' );
			return;
		}		
		// Get the upload data.
		$raw  = file_get_contents( $file['file'] );
		$data = @unserialize( $raw );

		// Loop through the mods.
		// Remove the uploaded file.
		unlink( $file['file'] );
		
		// Data checks.
		if ( 'array' != gettype( $data ) ) {	
			$cei_error = __( 'Error importing settings! Please check that you uploaded a customizer export file.', 'all-in-one-demo-import-export' );		
			return;
		}

		if ( isset( $data['template'] )) {
			
		} 
		if( isset( $data['mods'] )){

		}
		
		// if ( $data['template'] != $template ) {	

		// }	
		//Import images.
		if ( isset( $_REQUEST['aiodie-import-images'] ) ) {
			$data['mods'] = self::_import_images( $data['mods'] );	
		}
		
		if ( isset( $data['post'] ) ) {
			/* code for upload the post */
			$postid = array();
			foreach ($data['post'] as $key => $lesson) {
				$post_id = $lesson->ID;
				$post_title = $lesson->post_title;
				$post_author = $lesson->post_author;
				$post_content = $lesson->post_content;
				$post_type = $lesson->post_type;
				$post_status = $lesson->post_status;	
				$check_title=get_page_by_title($post_title, 'OBJECT', 'post');

				if (empty($check_title) ){
					$my_post = array(
						'post_title'    => $post_title,
						'post_content'  => $post_content,
						'post_status'   => $post_status,
						'post_author'   => $post_author,
						'post_type'     => $post_type,
					);
					$post_insert = wp_insert_post( $my_post );
				}else{
					$my_post = array(
						'ID' =>  $check_title->ID,
						'post_title'    => $post_title,
						'post_content'  => $post_content,
						'post_status'   => $post_status,
						'post_author'   => $post_author,
						'post_type'     => $post_type,
					);
					$post_insert = wp_update_post( $my_post );	
				}

				if (isset($data['tags_input'])){
					wp_set_post_tags($post_insert, $data['tags_input'][$post_id], true);
				}

				if (isset($data['custom_fields'])){

					$customdata = $data['custom_fields'][$post_id];

					foreach($customdata as $metakey => $metavalue){
						foreach($metavalue as $value){
							add_post_meta( $post_insert, $metakey, $value, true );

						}
					}	
				}	

				// Need to require these files
				if ( !function_exists('media_handle_upload') ) {
					require_once(ABSPATH . "wp-admin" . '/includes/image.php');
					require_once(ABSPATH . "wp-admin" . '/includes/file.php');
					require_once(ABSPATH . "wp-admin" . '/includes/media.php');
				}

				$url = $data['post_attachment'][$post_id];
				$tmp = download_url( $url );
				if( is_wp_error( $tmp ) ){
				// download failed, handle error
				}
				$postpid = $post_insert;
				
				$desc = "The WordPress Logo";
				$file_array = array();

				// Set variables for storage
				// fix file filename for query strings
				preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches);
				$file_array['name'] = basename($matches[0]);
				$file_array['tmp_name'] = $tmp;

				// If error storing temporarily, unlink
				if ( is_wp_error( $tmp ) ) {
					@unlink($file_array['tmp_name']);
					$file_array['tmp_name'] = '';
				}

				// do the validation and storage stuff
				$id = media_handle_sideload( $file_array, $postpid, $desc );
				
				// If error storing permanently, unlink
				if ( is_wp_error($id) ) {
					@unlink($file_array['tmp_name']);
					return $id;
				}

				$attachments = get_posts(array('numberposts' => '1', 'post_parent' => $postpid, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC'));

				if(sizeof($attachments) > 0){
    			// set image as the post thumbnail
					set_post_thumbnail($postpid, $attachments[0]->ID);
				}  
				$postid[$post_id] = $post_insert;
			}	
		}

		if ( isset( $data['page'] ) ) {
			/* code for upload the page */
			$pageid = array();
			foreach ($data['page'] as $key => $lesson) {
				$page_id = $lesson->ID;
				$post_title = $lesson->post_title;
				$post_author = $lesson->post_author;
				$post_content = $lesson->post_content;
				$post_type = $lesson->post_type;
				$post_status = $lesson->post_status;
				$check_title=get_page_by_title($post_title, 'OBJECT', 'page');

				//also var_dump($check_title) for testing only
				if (empty($check_title) ){
					$my_post = array(
						'post_title'    => $post_title,
						'post_content'  => $post_content,
						'post_status'   => $post_status,
						'post_type'     => $post_type,
						'post_author'   => $post_author,
					);
					$page_insert = wp_insert_post( $my_post );
				}else{
					$my_post = array(
						'ID' =>  $check_title->ID,
						'post_title'    => $post_title,
						'post_content'  => $post_content,
						'post_status'   => $post_status,
						'post_type'     => $post_type,
						'post_author'   => $post_author,
					);
					$page_insert = wp_update_post( $my_post );
				}
				
				if(isset($data['custom_fieldspage'])){
					$custompagedata = $data['custom_fieldspage'][$page_id];			
					foreach($custompagedata as $metapagekey => $metapagevalue){
						foreach($metapagevalue as $val){
							add_post_meta( $page_insert, $metapagekey, $val, true );		
						}
					}
				}
				// Need to require these files
				if ( !function_exists('media_handle_upload') ) {
					require_once(ABSPATH . "wp-admin" . '/includes/image.php');
					require_once(ABSPATH . "wp-admin" . '/includes/file.php');
					require_once(ABSPATH . "wp-admin" . '/includes/media.php');
				}
				$url = $data['page_attachment'][$page_id];
				$tmp = download_url( $url );
				if( is_wp_error( $tmp ) ){
				// download failed, handle error
				}
				$pagepid = $page_insert;

				$desc = "The WordPress Logo";
				$file_array = array();

				// Set variables for storage
				// fix file filename for query strings
				preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches);
				$file_array['name'] = basename($matches[0]);
				$file_array['tmp_name'] = $tmp;

				// If error storing temporarily, unlink
				if ( is_wp_error( $tmp ) ) {
					@unlink($file_array['tmp_name']);
					$file_array['tmp_name'] = '';
				}

				// do the validation and storage stuff
				$id = media_handle_sideload( $file_array, $pagepid, $desc );

				// If error storing permanently, unlink
				if ( is_wp_error($id) ){
					@unlink($file_array['tmp_name']);
					return $id;
				}

				$attachments = get_posts(array('numberposts' => '1', 'post_parent' => $pagepid, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC'));
				
				if(sizeof($attachments) > 0){
    			// set image as the post thumbnail
					set_post_thumbnail($pagepid, $attachments[0]->ID);
				}  
				
			}

		}

		if ( isset( $data['category'] ) ) {	

			if ($data['category'] != ''){
				/* code for upload the category */
				$category_map = array();
				foreach($data['category'] as $key => $lesson){	
					$category = $lesson->cat_ID;
					$term_id = $lesson->term_id;
					$cat_name = $lesson->name;
					$cat_slug = $lesson->slug;
					$taxonomy = $lesson->taxonomy;

					$term = term_exists($cat_name, $taxonomy);
					if ($term !== 0 && $term !== null){
						$termid = $term['term_id'];
						$term = wp_update_term($termid, $taxonomy );

						$category_map[$term_id] = $term['term_id'];
					}else{
						$term_ins = wp_insert_term($cat_name, $taxonomy, array('slug' => $cat_slug) );
						$category_map[$term_id] = $term_ins['term_id'];
					}
				}

				foreach($postid as $key => $value1){

					$post_categories = $data['post_category'][$key];		
					$newcat = array();
					foreach( $post_categories as $cat){	
						foreach($data['category'] as $key => $lesson){
							$newcat[] = $category_map[$cat];	
							$term_id = $lesson->term_id;
							$taxonomy = $lesson->taxonomy;
							if($taxonomy == 'category'){
								wp_set_post_categories($value1, $newcat);
							}else{
								wp_set_post_terms($value1, $newcat, $taxonomy);
							}
						}	
					}				
				}	
			}		
		}
		
		if ( isset( $data['tags'] ) ){	
			/* code for upload the tags */	
			$tags_map = array();
			foreach($data['tags'] as $key => $lesson){
				$term_id = $lesson->term_id;
				$tag_name = $lesson->name;
				$description = $lesson->description;
				$tag_parent = $lesson->parent;
				$tag_slug = $lesson->slug;
				$term = term_exists($tag_name, 'post_tag');
				if ($term !== 0 && $term !== null) {
					$termid = $term['term_id'];
					$term = wp_update_term($termid, $taxonomy );

					$tags_map[$term_id] = $term['term_id'];
				}else{
					$term_ins = wp_insert_term($tag_name, 'post_tag', array('slug' => $tag_slug) );
					$tags_map[$term_id] = $term_ins['term_id'];
				}
			}
		}
		if ( isset( $data['comment'] ) ){	
			/* code for upload the comment */
			foreach($data['comment'] as $key => $lesson){

				$comment_post_ID = $lesson->comment_post_ID;
				$comment_author = $lesson->comment_author;
				$comment_author_email = $lesson->comment_author_email;
				$comment_author_url = $lesson->comment_author_url;
				$comment_content = $lesson->comment_content;
				$comment_type = $lesson->comment_type;
				$comment_parent = $lesson->comment_parent;
				$user_id = $lesson->user_id;
				$comment_author_IP = $lesson->comment_author_IP;
				$comment_agent = $lesson->comment_agent;
				$comment_approved = $lesson->comment_approved;
				$time = current_time('mysql');

				$comment_data = array(
					'comment_post_ID' => $postid[$comment_post_ID],
					'comment_author' => $comment_author,
					'comment_author_email' => $comment_author_email,
					'comment_author_url' => $comment_author_url,
					'comment_content' => $comment_content,
					'comment_type' => $comment_type,
					'comment_parent' => $comment_parent,
					'user_id' => $user_id,
					'comment_author_IP' => $comment_author_IP,
					'comment_agent' => $comment_agent,
					'comment_date' => $time,
					'comment_approved' => $comment_approved,
				);
				wp_insert_comment($comment_data);
			}
		}

		if ( isset( $data['users'] ) ) {
			/* code for upload the users */
			foreach($data['users'] as $key => $lesson){
				$userlogin = $lesson->user_login;
				$user_id = $lesson->ID;
				if(username_exists( $user_login )){
					//echo '---------';
				    // echo "Username already exists";
				}else{
					$userdata = array(
						'user_login'  =>  $userlogin,
						'user_url'    =>  '',
						'user_pass'   =>  NULL  
					);
					$user_id = wp_insert_user( $userdata );

				    //On success
					if( !is_wp_error($user_id) ) {
						echo "User created : ". $user_id;
					}
				}		    
			}
		}
		if(isset($data['attachment'])){
			/* code for upload the attachment */
			foreach($data['attachment'] as $value){

				if ( !function_exists('media_handle_upload') ) {
					require_once(ABSPATH . "wp-admin" . '/includes/image.php');
					require_once(ABSPATH . "wp-admin" . '/includes/file.php');
					require_once(ABSPATH . "wp-admin" . '/includes/media.php');
				}

				$url = $value;		
				$tmp = download_url( $url );

				if( is_wp_error( $tmp ) ){
				// download failed, handle error
				}
				$pagepid = $page_insert;

				$desc = "The WordPress Logo";
				$file_array = array();

				// Set variables for storage
				// fix file filename for query strings
				preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches);
				$file_array['name'] = basename($matches[0]);
				$file_array['tmp_name'] = $tmp;

				// If error storing temporarily, unlink
				if ( is_wp_error( $tmp ) ) {
					@unlink($file_array['tmp_name']);
					$file_array['tmp_name'] = '';
				}

				// do the validation and storage stuff
				$id = media_handle_sideload( $file_array, $pagepid, $desc );

				// If error storing permanently, unlink
				if ( is_wp_error($id) ) {
					@unlink($file_array['tmp_name']);
					return $id;
				}

				$attachments = get_posts(array('numberposts' => '1', 'post_parent' => $pagepid, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC'));

				if(sizeof($attachments) > 0){
    			// set image as the post thumbnail
					set_post_thumbnail($pagepid, $attachments[0]->ID);
				}
			}
		}

		// Import custom options.
		if ( isset( $data['options'] ) ) {			
			foreach ( $data['options'] as $option_key => $option_value ) {				
				$option = new AIODIE_Option( $wp_customize, $option_key, array(
					'default'		=> '',
					'type'			=> 'option',
					'capability'	=> 'edit_theme_options'
				) );
				$option->import( $option_value );
			}
		}
		// If wp_css is set then import it.
		if( function_exists( 'wp_update_custom_css_post' ) && isset( $data['wp_css'] ) && '' !== $data['wp_css'] ) {
			wp_update_custom_css_post( $data['wp_css'] );
		}

		// Call the customize_save action.
		do_action( 'customize_save', $wp_customize );

		// Loop through the mods.
		if(isset($data['mods'])){
			foreach ( $data['mods'] as $key => $val ) {

			// Call the customize_save_ dynamic action.
				do_action( 'customize_save_' . $key, $wp_customize );
			// Save the mod.
				set_theme_mod( $key, $val );
			}
		}
		// Call the customize_save_after action.
		do_action( 'customize_save_after', $wp_customize );
	}

	/**
	 * Imports images for settings saved as mods.
	 *
	 * @since 0.1
	 * @access private
	 * @param array $mods An array of customizer mods.
	 * @return array The mods array with any new import data.
	 */
	static private function _import_images( $mods ) 
	{
		foreach ( $mods as $key => $val ) {
			
			if ( self::_is_image_url( $val ) ) {
				
				$data = self::_sideload_image( $val );
				
				if ( ! is_wp_error( $data ) ) {
					
					$mods[ $key ] = $data->url;
					
					// Handle header image controls.
					if ( isset( $mods[ $key . '_data' ] ) ) {
						$mods[ $key . '_data' ] = $data;
						update_post_meta( $data->attachment_id, '_wp_attachment_is_custom_header', get_stylesheet() );
					}
				}
			}
		}	
		return $mods;
	}
	
	/**
	 * Taken from the core media_sideload_image function and
	 * modified to return an array of data instead of html.
	 *
	 * @since 0.1
	 * @access private
	 * @param string $file The image file path.
	 * @return array An array of image data.
	 */
	static private function _sideload_image( $file ) 
	{
		$data = new stdClass();

		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}
		if ( ! empty( $file ) ) {
			
			// Set variables for storage, fix file filename for query strings.
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
			$file_array = array();
			$file_array['name'] = basename( $matches[0] );

			// Download file to temp location.
			$file_array['tmp_name'] = download_url( $file );

			// If error storing temporarily, return the error.
			if ( is_wp_error( $file_array['tmp_name'] ) ) {
				return $file_array['tmp_name'];
			}
			// Do the validation and storage stuff.
			$id = media_handle_sideload( $file_array, 0 );

			// If error storing permanently, unlink.
			if ( is_wp_error( $id ) ) {
				@unlink( $file_array['tmp_name'] );
				return $id;
			}
			// Build the object to return.
			$meta					= wp_get_attachment_metadata( $id );
			$data->attachment_id	= $id;
			$data->url				= wp_get_attachment_url( $id );
			$data->thumbnail_url	= wp_get_attachment_thumb_url( $id );
			$data->height			= $meta['height'];
			$data->width			= $meta['width'];
		}
		return $data;
	}
	
	/**
	 * Checks to see whether a string is an image url or not.
	 *
	 * @since 0.1
	 * @access private
	 * @param string $string The string to check.
	 * @return bool Whether the string is an image url or not.
	 */
	static private function _is_image_url( $string = '' ) 
	{
		if ( is_string( $string ) ) {	
			if ( preg_match( '/\.(jpg|jpeg|png|gif)/i', $string ) ) {
				return true;
			}
		}	
		return false;
	}
}


