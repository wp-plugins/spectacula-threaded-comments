<?php
/*
 Plugin Name: Spectacu.la Threaded comments
 Plugin URI: http://spectacu.la/
 Description: Make it easy to add threaded comments to any theme.
 Version: 2.0.0
 Author: James R Whitehead
 Author URI: http://www.interconnectit.com/
*/

/*
 Define this at the top of your functions.php before an include/require pointing
 to this file located in a subfolder of your theme and you can easily integrate
 this with your theme.
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
define( 'SPEC_COMMENT_VER', '2.7' ); // Min version of wordpress this will work with.
define( 'SPEC_COMMENT_OPT', 'spectacula_threaded_comments' );
define( 'SPEC_COMMENT_TMP', SPEC_COMMENT_PTH . '/includes/template.php' );

if ( ! function_exists ( 'json_encode' ) )
	require_once( SPEC_COMMENT_PTH . '/includes/JSON.php' );

require_once( SPEC_COMMENT_PTH . '/includes/functions.php' );
require_once( SPEC_COMMENT_PTH . '/includes/options-page.php' );
require_once( SPEC_COMMENT_PTH . '/includes/spec-ajax.php' );

//delete_option( SPEC_COMMENT_OPT );

if ( ! class_exists( 'spec_commenting' ) ) {
	class spec_commenting {

		/*
		 PHP4 constructor. Adds the css, js, config menu and the template hijack
		 filters/actions to Wordpress. It also checks that we've not been here
		 already and assigns some variables to the class.

		 @return null;
		*/

		function spec_commenting( ) {
			// This class was included with some spectacu.la themes this will
			// stop it running twice and causing confusion.

			if ( defined( 'SPEC_COMMENT_DON' ) )
				return false;
			else
				define( 'SPEC_COMMENT_DON', true );

			if ( version_compare( $GLOBALS[ 'wp_version' ], SPEC_COMMENT_VER, 'ge' ) )
				add_action( 'init', array( & $this, '_init' ), 1 );
		}

		function _init ( ) {

			// Load the translation stuff
			//$abs_rel_path = trim( trim( str_replace( trim( ABSPATH, '/' ), '', dirname( __FILE__ ) ), '/' ), '\\' ) . '/lang/';
			/*
			 @todo: Sort this so we can load from within a theme if we're in an include folder or something like that.
			*/
			load_plugin_textdomain( SPEC_COMMENT_DOM, false, '/lang/' );

			// If we're requesting ajax stuff we'll hand over control to spec ajax then die.
			if ( isset( $_REQUEST[ '_spec_ajax' ] ) || isset( $_POST[ '_spec_ajax' ] ) )
				new spectacula_ajax( );

			add_filter( 'body_class', array( &$this, 'get_agent_body_class' ) );
			add_action( 'wp_head', array( &$this, 'css' ) );
			add_action( 'wp', array( &$this, 'before_headers' ) );

			add_action( 'comment_form', array( &$this, 'our_credit' ) );
			add_filter( 'comments_template', array( &$this, 'comment_template_hijack' ) );
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
			global $wp_scripts;

			if( function_exists( 'wp_list_comments' ) && is_singular( ) ) {

				wp_register_script( 'json2', SPEC_COMMENT_URL . "/js/json2.js", array( ), '20090817', true );
				wp_register_script( 'scrollto', SPEC_COMMENT_URL . "/js/jquery.scrollTo-1.4.2-min.js", array( 'jquery' ), '1.4.2', true );

				// Make sure we have jQuery version 1.3.2 or better
				if ( isset( $wp_scripts->registered[ 'jquery' ]->ver ) && version_compare( $wp_scripts->registered[ 'jquery' ]->ver, '1.3.2', '<' ) ){
					wp_deregister_script( 'jquery' );
					wp_register_script( 'jquery', SPEC_COMMENT_URL . "/js/jquery.js", array( ), '1.3.2', true );
				}

				wp_deregister_script( 'comment-reply' ); // dealt with by the included jQuery

				$localisation = array(
					'trackbackShowText' => __( 'Show trackbacks', SPEC_COMMENT_DOM ),
					'trackbackHideText' => __( 'Hide trackbacks', SPEC_COMMENT_DOM ),
					'replyHideMany' => __( "Hide %count% replies to %name%'s comment", SPEC_COMMENT_DOM ),
					'replyShowMany' => __( "View %count% replies to %name%'s comment", SPEC_COMMENT_DOM ),
					'replyHideOne' => __( "Hide the reply to %name%'s comment", SPEC_COMMENT_DOM ),
					'replyShowOne' => __( "View the reply to %name%'s comment", SPEC_COMMENT_DOM ),
					'order' => get_option( 'comment_order' )
				);

				$localisation = array_merge( ( array ) apply_filters( 'spec_comment_local_js', $localisation ), array( 'nestDepth' => spec_comment_option( 'comments_nest_depth' ) ) );

				$prefix = ! defined( 'SCRIPT_DEBUG' ) || ( defined( 'SCRIPT_DEBUG' ) && ! SCRIPT_DEBUG ) ? '.min' : '';

				wp_enqueue_script( 'commenting', apply_filters( 'spec_comment_js', SPEC_COMMENT_URL . "/js/commenting$prefix.js" ), array( 'jquery', 'jquery-form', 'json2', 'scrollto' ), '1.0.3', true );
				wp_localize_script( 'commenting', 'commentingL10n', $localisation );
			}
		}

		/*
		 Change the path to the comments.php to point to our new comments.php
		 file.

		 @param string $passed passed in by comments_template apply_filters call
		 and would normally contain the path to the themes comments.php

		 @return string the new comments.php if it exists otherwise just return
		 the one we were given before.
		*/
		function comment_template_hijack( $passed ){
			if ( file_exists( SPEC_COMMENT_TMP ) )
				return SPEC_COMMENT_TMP;
			else
				return $passed;
		}


		/*
		 Add the comments.css file to the head of pages that'll need comments.

		 @uses apply_filters( ) Calls 'spec_comment_css' hook on the the path to
		 the stylesheet so that you can roll your own should you need/want to.

		 @return null;
		*/
		function css( ){
			if ( is_singular( ) && spec_comment_option( 'stylesheet' ) != '' ) {
				?><link rel="stylesheet" href="<?php echo apply_filters( 'spec_comment_css', spec_comment_option( 'stylesheet' ) . '?ver=2.0.0' ); ?>" type="text/css" media="screen" /><?php
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

			elseif ( preg_match( '!msie\s+(\d+\.\d+)!i', $useragent, $match ) ) {
				$version = floatval( $match[ 1 ] );

				/* Add an identifier for IE versions. */
				if ( ! in_array( 'ienew', $class ) && $version >= 9 ) {
					$class[ ] = 'ie';
					$class[ ] = 'ienew';
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
				} elseif ( ! in_array( 'ieold', $class ) && $version < 5 ) {
					$class[ ] = 'ie';
					$class[ ] = 'ieold';
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
				echo '<p class="spectacula-credit"><small>Threaded commenting powered by <a href="http://spectacu.la/">Spectacu.la</a> code.</small></p>';
			} else {
				// If you've unticked the show our credit link we'll just have it as an HTML comment instead.
				// Nothing to stop you removing this line too. But please don't.
				echo "\n<!-- Threaded commenting powered by http://Spectacu.la/ code. -->\n";
			}
		}
	}
}


new spec_commenting( );
?>
