<?php
/*
 Plugin Name: Spectacu.la Discussion
 Plugin URI: http://spectacu.la/
 Description: Make it easy to add fully ajax threaded comments to any theme.
 Version: 2.2
 Author: James R Whitehead, Tom J Nowell
 Author URI: http://www.interconnectit.com/
*/


if ( ! class_exists( 'spec_commenting' ) && ! defined( 'SPEC_COMMENT_DON' ) ) {
	/*
	 Define the url at the top of your functions.php before a require/include
	 pointing to this file located in a subfolder of your theme and you can
	 easily integrate this with your theme.
	*/
	if ( ! defined( 'SPEC_COMMENT_URL' ) ) {
		if ( version_compare( $GLOBALS[ 'wp_version' ], '2.8', 'ge' ) ) {
			define( 'SPEC_COMMENT_URL', plugins_url( '', __FILE__ ) );
		} else {
			define( 'SPEC_COMMENT_URL', plugins_url( 'spectacula-threaded-comments' ) );
		}
	}

	define( 'SPEC_COMMENT_PTH', dirname( __FILE__ ) );
	define( 'SPEC_COMMENT_DOM', 'spectacula-threaded-comments' ); // Translation domain
	define( 'SPEC_COMMENT_VER', '3.0' ); // Min version of wordpress this will work with.
	define( 'SPEC_COMMENT_OPT', 'spectacula_threaded_comments' );
	define( 'SPEC_COMMENT_TMP', SPEC_COMMENT_PTH . '/includes/template.php' );
	define( 'SPEC_COMMENT_DON', true ); // Set a toggle so we don't come back

	//delete_option( SPEC_COMMENT_OPT );

	// Load the translation stuff
	//load_plugin_textdomain( SPEC_COMMENT_DOM, false, '/lang/' );
	$locale = get_locale( );
	if ( file_exists( SPEC_COMMENT_PTH . '/lang/' . SPEC_COMMENT_DOM . '-' . $locale . '.mo' ) )
		load_textdomain( SPEC_COMMENT_DOM, SPEC_COMMENT_PTH . '/lang/' . SPEC_COMMENT_DOM . '-' . $locale . '.mo' );

	if ( ! function_exists ( 'json_encode' ) )
		require_once( SPEC_COMMENT_PTH . '/includes/JSON.php' ); // PHP <= 5.2

	require_once( SPEC_COMMENT_PTH . '/includes/functions.php' );
	require_once( SPEC_COMMENT_PTH . '/includes/options-page.php' );
	require_once( SPEC_COMMENT_PTH . '/includes/db.php' );
	require_once( SPEC_COMMENT_PTH . '/includes/spec-ajax.php' );

	class spec_commenting {

		/*
		 Check to see if we've been here before, if not set the toggle to show
		 any other calls we have then check the WordPress and PHP versions to
		 make sure we can go on.
		*/

		function spec_commenting( ) {

			if ( version_compare( $GLOBALS[ 'wp_version' ], SPEC_COMMENT_VER, 'ge' ) && version_compare( PHP_VERSION, '5.0.0', 'ge' ) )
				add_action( 'init', array( & $this, '_init' ), 1 );
		}

		function _init ( ) {
			// Something has changed the template, I'm guessing an alternative theme for mobile scenarios.
			if ( get_template( ) != get_option( 'template' ) )
				return;

			$this->add_moderator_role();

			// If we're requesting ajax stuff we'll hand over control to spec ajax then die.
			if ( isset( $_REQUEST[ '_spec_ajax' ] ) ) {
				new spectacula_ajax( );
			}

			$this->comment_moderation_calls();

			add_filter( 'body_class', array( &$this, 'get_agent_body_class' ) );
			add_action( 'wp_head', array( &$this, 'css' ) );
			add_action( 'wp', array( &$this, 'before_headers' ) );



			add_action( 'comment_form', array( &$this, 'our_credit' ) );
			add_filter( 'comments_template', array( &$this, 'comment_template_hijack' ) );

			// The live comment toggle metabox. Lets you toggle live comments on
			// a post by post basis.
			add_action( 'admin_init', array( &$this, 'add_meta_boxes' ) );
			add_action( 'save_post', array( &$this, 'save_metabox_toggle_status' ), 100, 2 );

			add_filter( 'comments_array', array( &$this, 'comment_query_hijack'));
		}

		function add_moderator_role(){
			$result = add_role('spec_comment_moderator', 'Comment Moderator', array(
			    'read' => true, // True allows that capability
			    'moderate_comments' => true
			));
		}


		/*
		 This should execute before headers are sent but after WP has set up the
		 post data allowing us to check to see if we're on a page that would
		 require comments. This will simply add the JavaScript and JS
		 localisations to the header if we're on a post that needs them.

		 @uses apply_filters( ) Calls 'spec_comment_js' hook on the path to the
		 commenting jQuery js file.

		 @uses apply_filters( ) Calls 'spec_comment_local_js' hook on the array
		 of the localisation data. Allows you to change/add more text if you
		 choose to roll your own JS file.

		 @return null;
		*/
		function before_headers( ) {
			global $wp_scripts, $post;

			// Quick check that this post_type supports comments.
			$post_type_supports = function_exists( 'post_type_supports' ) && is_object( $post ) ? post_type_supports( $post->post_type, 'comments' ) : true;

			if( function_exists( 'wp_list_comments' ) && is_singular( ) && $post_type_supports && comments_open( $post->ID ) ) {

				$prefix = ! defined( 'SCRIPT_DEBUG' ) || ( defined( 'SCRIPT_DEBUG' ) && ! SCRIPT_DEBUG ) ? '.min' : '';

				wp_register_script( 'json2', SPEC_COMMENT_URL . "/js/json2.js", array( ), '20090817', true );
				wp_register_script( 'autogrow', SPEC_COMMENT_URL . "/js/jquery.autogrow-textarea$prefix.js", array( 'jquery' ), 1.04, true );
				wp_register_script( 'scrollto', SPEC_COMMENT_URL . "/js/jquery.scrollTo-1.4.2-min.js", array( 'jquery' ), '1.4.2', true );

				// Make sure we have jQuery version 1.3.2 or better
				if ( isset( $wp_scripts->registered[ 'jquery' ]->ver ) && version_compare( $wp_scripts->registered[ 'jquery' ]->ver, '1.3.2', '<' ) ){
					wp_deregister_script( 'jquery' );
					wp_register_script( 'jquery', SPEC_COMMENT_URL . "/js/jquery.js", array( ), '1.3.2', true );
				}

				wp_deregister_script( 'comment-reply' ); // dealt with by the included jQuery

				global $post;

				$localisation = array(
					'tb_show' => __( 'Show trackbacks', SPEC_COMMENT_DOM ),
					'tb_hide' => __( 'Hide trackbacks', SPEC_COMMENT_DOM ),
					'rpl_hide_2' => __( "Hide %count% replies to %name%'s comment", SPEC_COMMENT_DOM ),
					'rpl_show_2' => __( "View %count% replies to %name%'s comment", SPEC_COMMENT_DOM ),
					'rpl_hide_1' => __( "Hide the reply to %name%'s comment", SPEC_COMMENT_DOM ),
					'rpl_show_1' => __( "View the reply to %name%'s comment", SPEC_COMMENT_DOM ),
					'tb_from' => __( 'Trackback from: %s', SPEC_COMMENT_DOM ),
					'err_txt_mis' => __( 'Missing some fields', SPEC_COMMENT_DOM ),
					'unknown' => __( 'Unknown commenter', SPEC_COMMENT_DOM ),
					'order' => get_option( 'comment_order' ),
					'polling' => spec_comment_option( 'polling' ),
					'update' => $this->check_live( $post->ID ) && comments_open( $post->ID ) ? 1 : 0,
					'time' => current_time( 'mysql', false ),
					'post_id' => $post->ID,
					'ajax_url' => trailingslashit( home_url( ) ),
					'nest_depth' => spec_comment_option( 'comments_nest_depth' ),
					'max_depth' => get_option( 'thread_comments_depth' )
				);


				if ( spec_comment_option( 'quote_button' ) || spec_comment_option( 'quote_select' ) ) {
					wp_enqueue_script( 'spec_quote_button', SPEC_COMMENT_URL . "/js/quote$prefix.js", array( 'jquery' ), '1.0.3', true );
					$quote_options = array(
										   'button_text' => __( 'Quote', SPEC_COMMENT_DOM ),
										   'quote_button' => spec_comment_option( 'quote_button' ),
										   'quote_select' => spec_comment_option( 'quote_select' ),
										   'quote_target' => spec_comment_option( 'quote_target' )
										   );
					wp_localize_script( 'spec_quote_button', 'specQuoteLn', $quote_options );
				}

				wp_enqueue_script( 'commenting', apply_filters( 'spec_comment_js', SPEC_COMMENT_URL . "/js/commenting$prefix.js" ), array( 'jquery', 'autogrow', 'jquery-form', 'json2', 'scrollto' ), '1.0.3', true );
				wp_localize_script( 'commenting', 'commentingL10n', apply_filters( 'spec_comment_local_js', $localisation ) );
			}
		}

		/*
		 Change the path to the comments.php to point to our new comments.php
		 file.

		 @param string $passed passed in by comments_template apply_filters call
		 and would normally contain the path to the theme's comments.php

		 @return string the new comments.php if it exists otherwise just return
		 the one we were given before.
		*/
		function comment_template_hijack( $passed ){
			if ( file_exists( SPEC_COMMENT_TMP ) )
				return SPEC_COMMENT_TMP;
			else
				return $passed;
		}

		/**
		 * Hijack the comments and redo it if the current user can moderate comments so they can see them all
		 **/
		function comment_query_hijack($comments){
			if(current_user_can('moderate_comments')){
				global $post;
				$comments = get_comments( array('post_id' => $post->ID, 'status' => '', 'order' => 'ASC') );
			}
			return $comments;
		}

		function comment_moderation_calls(){
			if(current_user_can('moderate_comments')){
				if(isset($_GET['p']) && (
					isset($_GET['approvecomment'])
					|| isset($_GET['spamcomment'])
					|| isset($_GET['deletecomment'])
				)){
					$post_id = $_GET['p'];
					if(isset($_GET['approvecomment'])){
						$comment_id = $_GET['approvecomment'];
						spec_comment_approve_comment($comment_id);
					} else if(isset($_GET['spamcomment'])){
						$comment_id = $_GET['spamcomment'];
						spec_comment_spam_comment($comment_id);
					} else if(isset($_GET['deletecomment'])){
						$comment_id = $_GET['deletecomment'];
						spec_comment_delete_comment($comment_id);
					}
				}
			}
		}


		/*
		 Add the comments.css file to the head of pages that'll need comments.

		 @uses apply_filters( ) Calls 'spec_comment_css' hook on the the path to
		 the stylesheet so that you can roll your own should you need/want to.

		 @return null;
		*/
		function css( ){
			if ( is_singular( ) && strtolower( spec_comment_option( 'stylesheet' ) ) != 'disabled' ) { ?>
				<link rel="stylesheet" href="<?php echo apply_filters( 'spec_comment_css', spec_comment_option( 'stylesheet' ) . '?ver=2.0.0' ); ?>" type="text/css" media="screen" /><?php
			}
		}


		/*
		 Sniffs the browser sig and add useful stuff to the array if not already
		 in there. Really simple sniffer only need it to ident IE really and
		 even then nothing critical should be hanging off of a class attached to
		 the body.
		 Adds .geko for mozila based browsers .webkit for, well err, webkit
		 based browsers and .ie + .ie( new|9|8|7|6|55|5|old ) for the various
		 hellish incarnations of IE. As you can see IE is the main target of
		 this all other browsers shouldn't need too much in the way of specific
		 CSS and I only add geko and webkit because I can.

		 @param array $class Called by apply_filters('body_class') which should
		 pass in an array of classes.
		 @return array Our changed version of the array that should now contain
		 something to ident the browser.
		*/
		function get_agent_body_class( $class = array( ) ) {

			// Add a constant that we can check when we call the comments so we
			// can add the classes at the head of the comments section if the
			// theme hasn't called body_class( ).
			if ( ! defined( 'SPEC_COMMENT_BODY_CLASS' ) )
				define( 'SPEC_COMMENT_BODY_CLASS', true );
			else
				return $class;

			$useragent = getenv( 'HTTP_USER_AGENT' );

			if( preg_match( '!gecko/\d+!i', $useragent ) && ! in_array( 'gecko', $class ) )
				 $class[ ] = 'gecko';

			elseif( preg_match( '!(applewebkit|konqueror)/[\d\.]+!i', $useragent ) && ! in_array( 'webkit', $class ) )
				$class[ ] = 'webkit';

			elseif ( preg_match( '!msie\s+(\d+\.\d+)!i', $useragent, $match ) && ! in_array( 'ie', $class ) ) {
				$version = floatval( $match[ 1 ] );

				/* Add an identifier for IE versions. */
				if ( ! in_array( 'ie9', $class ) && $version >= 9 && $version < 10 ) {
					$class[ ] = 'ie';
					$class[ ] = 'ie9'; // Look forward to the day when this is the standard IE, shouldn't need this anymore then. :D
				} elseif ( ! in_array( 'ie8', $class ) && $version >= 8 && $version < 9 ) {
					$class[ ] = 'ie';
					$class[ ] = 'ie8';
				} elseif ( ! in_array( 'ie7', $class ) && $version >= 7 && $version < 8 ) {
					$class[ ] = 'ie';
					$class[ ] = 'ie7';
				} elseif ( ! in_array( 'ie6', $class ) && $version >= 6 && $version < 7 ) {
					$class[ ] = 'ie';
					$class[ ] = 'ie6';
				} elseif ( ! in_array( 'ie55', $class ) && $version >= 5.5 && $version < 6 ) {
					$class[ ] = 'ie';
					$class[ ] = 'ie55';
				} elseif ( ! in_array( 'ie5', $class ) && $version >= 5 && $version < 5.5 ) {
					$class[ ] = 'ie';
					$class[ ] = 'ie5';
				} elseif ( ! in_array( 'ie-old', $class ) && $version < 5 ) {
					$class[ ] = 'ie';
					$class[ ] = 'ie-old';
				} else { // Unknown IE.
					$class[ ] = 'ie';
					$class[ ] = 'iexx';
				}
			}
			return $class;
		}


		/*
		 This is attached to the comment_form action and just echos out our credit
		 link. If you don't want to give us credit for this :( then you can hide the
		 link from the admin page.
		*/
		function our_credit( ){

			if ( spec_comment_option( 'credit' ) ) {
				echo '<p class="spectacula-credit"><small>' . sprintf( __( 'Threaded commenting powered by %s code.', SPEC_COMMENT_DOM ), '<a href="http://spectacu.la/">Spectacu.la</a>' ) . '</small></p>';
			} else {
				// If you've unticked the show our credit link we'll just have it as an HTML comment instead.
				// Nothing to stop you removing this line too. But please don't.
				echo "\n<!-- " . sprintf( __( 'Threaded commenting powered by %s code.', SPEC_COMMENT_DOM ), 'http://spectacu.la/' ) . " -->\n";
			}
		}


		/*
		 Add the metaboxes to all post_types available or post and pages for
		 WordPress 2.9 and older.
		*/
		function add_meta_boxes( ) {
			global $wp_version;
			if ( version_compare( $wp_version, '3.0', 'ge' ) ) {
				// WordPress 3.0 and above.
				foreach( ( array ) get_post_types( array( 'show_ui' => 1 ) ) as $post_type ) {
					if( post_type_supports( $post_type, 'comments' ) )
						add_meta_box( 'spec_live_toggle', __( 'Live Discussion', SPEC_COMMENT_DOM ), array( &$this, 'metabox_live_toggle' ), $post_type, 'advanced', 'default' );
				}
			} else {
				// Add to post and page for older versions of WordPress.
				add_meta_box( 'spec_live_toggle', __( 'Live Discussion', SPEC_COMMENT_DOM ), array( &$this, 'metabox_live_toggle' ), 'post', 'advanced', 'default' );
				add_meta_box( 'spec_live_toggle', __( 'Live Discussion', SPEC_COMMENT_DOM ), array( &$this, 'metabox_live_toggle' ), 'page', 'advanced', 'default' );
			}
		}


		/*
		 This is the metabox that'll let you toggle the default status of live
		 commenting on a post by post basis.

		 @param obj $post The post object as passed in by add_meta_box.
		*/
		function metabox_live_toggle( $post = '' ) {
			if ( ! is_object( $post ) )
				return false; ?>

			<label for="<?php echo SPEC_COMMENT_OPT ?>_live">
				<input type="checkbox" value="1" name="<?php echo SPEC_COMMENT_OPT ?>_live" id="<?php echo SPEC_COMMENT_OPT ?>_live" <?php checked( $this->check_live( $post->ID ), true ) ?>/>
				<?php _e( 'Allow Live Discussion', SPEC_COMMENT_DOM ); ?>
			</label> <?php
		}


		/*
		 Check the post_save for our toggle and set the post_meta to reflect the
		 choice.
		 @return null
		*/
		function save_metabox_toggle_status( ) {
			if ( isset( $_POST[ 'post_ID' ] ) && isset( $_POST[ '_wpnonce' ] ) && isset( $_POST[ 'post_type' ] ) ) {
				$post_ID =  intval( $_POST[ 'post_ID' ] );
				$wpnonce = $_POST[ '_wpnonce' ];
				$post_type = $_POST[ 'post_type' ];
			} else
				return;

			if ( current_user_can( 'edit_post', $post_ID ) && wp_verify_nonce( $wpnonce, 'update-' . $post_type . '_' . $post_ID ) ) {
				$toggle = isset( $_POST[ SPEC_COMMENT_OPT . '_live' ] ) && intval( $_POST[ SPEC_COMMENT_OPT . '_live' ] ) == 1 ? 20 : 10;
				if ( ! update_post_meta( $post_ID, '_' . SPEC_COMMENT_OPT . '_live', $toggle ) ) {
					add_post_meta( $post_ID, '_' . SPEC_COMMENT_OPT . '_live', $toggle );
				}
			}
		}


		/*
		 Combine the post_meta with the global live discussion status to give us
		 the status for the post_id passed in. If the post hasn't had the option
		 set it will resort to the global setting and if has it'll use it's data
		 in preference.

		 @param in $post_ID The post id we want to check the status for.
		 @return bool True if we want live comments false if we don't.
		*/
		function check_live( $post_ID = 0 ) {
			$post_ID = intval( $post_ID );
			$post_meta = intval( get_post_meta( $post_ID, '_' . SPEC_COMMENT_OPT . '_live', true ) );

			$post_status = $post_meta === 10 ? false : ( $post_meta === 20 ? true : null );
			$glob_status = spec_comment_option( 'update' );

			return is_bool( $post_status ) ? $post_status : $glob_status;
		}
	}
}

new spec_commenting( );
?>
