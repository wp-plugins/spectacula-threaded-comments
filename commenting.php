<?php
/*
 Plugin Name: Spectacu.la Threaded comments
 Plugin URI: http://spectacu.la/
 Description: Make it easy to add threaded comments to any theme.
 Version: 1.0.0
 Author: James R Whitehead
 Author URI: http://www.interconnectit.com/
*/

if (!class_exists('spec_commenting')) {

	define('SPEC_COMMENT_DOM', 'spectacula-threaded-comments');
	define('SPEC_COMMENT_VER', '2.7'); // Min version of wordpress this will work with.
	define('SPEC_COMMENT_OPT', 'spectacula_threaded_comments');
	define('SPEC_COMMENT_URL', plugins_url(plugin_basename(dirname(__FILE__))));
	define('SPEC_COMMENT_TMP', dirname(__FILE__) . '/template/comments.php');

	//delete_option(SPEC_COMMENT_OPT);

	class spec_commenting {

		// Defaults
		var $default_options = array('comments_nest_depth' => 1, 'load_css' => 1, 'load_js' => 1, 'load_dark' => 0, 'credit' => 1);

		/*
		 PHP4 constructor. Adds the css, js, config menu and the template hijack
		 filters/actions to Wordpress. It also checks that we've not been here
		 already and assigns some variables to the class.

		 @return null;
		*/
		function spec_commenting () {
			// This class was included with some spectacu.la themes
			// this will stop it running twice and causing confusion.
			if (defined('SPEC_COMMENT_DON'))
				return false;
			else
				define('SPEC_COMMENT_DON', true);

			// Load the translation stuff
			$abs_rel_path = trim(trim(str_replace(trim(ABSPATH, '/'), '', dirname(__FILE__)), '/'), '\\') . '/lang/';
			load_plugin_textdomain(SPEC_COMMENT_DOM, $abs_rel_path);

			// Merge the defalts with those chosen.
			$this->options = array_merge($this->default_options, (array)get_option(SPEC_COMMENT_OPT));

			// Add the actions and filters.
			if ($this->options['load_css']) {
				add_filter('body_class', array(&$this, 'get_agent_body_class'));

				add_action('wp_head', array(&$this, 'css'));
				if ($this->options['load_dark']) {
					add_filter('spec_comment_css', array(&$this, 'dark_css'));
				}
			}

			if ($this->options['load_js']) {
				add_action('wp', array(&$this, 'before_headers'));
			}

			add_action('comment_form', array(&$this, 'our_credit'));
			add_action('admin_menu', array(&$this, 'add_options_page'));
			add_filter('comments_template', array(&$this, 'comment_template_hijack'));
		}


		/*
		 This should execute before headers are sent but after WP has set up the
		 post data allowing us to check to see if we're on a page that would
		 require comments. This will simply add the JavaScript and JS
		 localisations to the header if we're on a post that needs them.

		 @uses apply_filters() Calls 'spec_comment_js' hook on the path to the
		 commenting jQuery js file.

		 @uses apply_filters() Calls 'spec_comment_local_js' hook on the array
		 of the localisation data. Allows you to change/add more text if you
		 choose to roll your own JS file.

		 @return null;
		*/
		function before_headers(){
			if(function_exists('wp_list_comments') && is_singular()) {
				$localisation = array(
					'trackbackShowText' => __('Show trackbacks', SPEC_COMMENT_DOM),
					'trackbackHideText' => __('Hide trackbacks', SPEC_COMMENT_DOM),
					'replyHideMany' => __('Hide %count% replies to %name% comment', SPEC_COMMENT_DOM),
					'replyShowMany' => __('View %count% replies to %name% comment', SPEC_COMMENT_DOM),
					'replyHideOne' => __('Hide the reply to %name% comment', SPEC_COMMENT_DOM),
					'replyShowOne' => __('View the reply to %name% comment', SPEC_COMMENT_DOM),
				);

				$localisation = array_merge((array)apply_filters('spec_comment_local_js', $localisation), array('nestDepth' => $this->options['comments_nest_depth']));

				wp_enqueue_script('commenting', apply_filters('spec_comment_js', SPEC_COMMENT_URL . '/js/commenting.min.js'), array('jquery'));
				wp_localize_script('commenting', 'commentingL10n', $localisation );
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
		function comment_template_hijack($passed){
			if (file_exists(SPEC_COMMENT_TMP))
				return SPEC_COMMENT_TMP;
			else
				return $passed;
		}


		/*
		 Add the comments.css file to the head of pages that'll need comments.

		 @uses apply_filters() Calls 'spec_comment_css' hook on the the path to
		 the stylesheet so that you can roll your own should you need/want to.

		 @return null;
		*/
		function css(){
			if (is_singular()) {
				?><link rel="stylesheet" href="<?php echo apply_filters('spec_comment_css', SPEC_COMMENT_URL . '/style/comments.css'); ?>" type="text/css" media="screen" /><?php
			}
		}


		/*
		 Simply replace the stylesheet assigned above if someone wants the dark
		 version of the comments. This is more to show you how to quickly
		 replace the CSS from a plug-in rather than having to edit the plug-in.

		 @param string $incomming The stylesheet url passed by apply_filters.
		 @return string The new stylesheet.
		*/
		function dark_css($incomming) {
			return SPEC_COMMENT_URL . '/style/comments-dark.css';
		}


		/*
		 Sniffs the browser sig and add useful stiff to the array if not already
		 in there. Really simple sniffer only need it to ident IE really and
		 even then nothing critical should be hanging off of a class attached to
		 the body.
		 Adds .geko for mozila based browsers .webkit for, well err, webkit
		 based browsers and .ie + .ie(new|9|8|7|6|55|5|old) for the various
		 hellish incarnations of IE. As you can see IE is the main target of
		 this all other browsers shouldn't need too much in the way of specific
		 CSS and I only add geko and webkit because I can.

		 @param array $class Called by apply_filters('body_class') which should
		 pass in an array of classes.
		 @return array Our changed version of the array that should now contain
		 something to ident the browse.
		*/
		function get_agent_body_class($class = array()) {

			// Add a constant that we can check when we call the comments so we
			// can add the classes at the head of the comments section if the
			// theme hasn't called body_class().
			if (!defined('SPEC_COMMENT_BODY_CLASS'))
				define('SPEC_COMMENT_BODY_CLASS', true);
			else
				return $class;

			$useragent = getenv('HTTP_USER_AGENT');

			if(preg_match('!gecko/\d+!i', $useragent) && !in_array('gecko', $class))
				 $class[] = 'gecko';

			elseif(preg_match('!(applewebkit|konqueror)/[\d\.]+!i',$useragent) && !in_array('webkit', $class))
				$class[] = 'webkit';

			elseif (preg_match('!msie\s+(\d+\.\d+)!i', $useragent, $match)) {
				$version = floatval($match[1]);

				/* Add an identifier for IE versions. */
				if (!in_array('ienew', $class) && $version >= 9 ) {
					$class[] = 'ie';
					$class[] = 'ienew';
				} elseif (!in_array('ie8', $class) && $version >= 8 && $version < 9) {
					$class[] = 'ie';
					$class[] = 'ie8';
				} elseif (!in_array('ie7', $class) && $version >= 7 && $version < 8) {
					$class[] = 'ie';
					$class[] = 'ie7';
				} elseif (!in_array('ie6', $class) && $version >= 6 && $version < 7) {
					$class[] = 'ie';
					$class[] = 'ie6';
				} elseif (!in_array('ie55', $class) && $version >= 5.5 && $version < 6) {
					$class[] = 'ie';
					$class[] = 'ie55';
				} elseif (!in_array('ie5', $class) && $version >= 5 && $version < 5.5) {
					$class[] = 'ie';
					$class[] = 'ie5';
				} elseif (!in_array('ieold', $class) && $version < 5) {
					$class[] = 'ie';
					$class[] = 'ieold';
				} else { // Unknown IE.
					$class[] = 'ie';
					$class[] = 'iexx';
				}
			}
			return $class;
		}


		/*
		 Add the option page to wordpress' theme menu and register the settings
		 @return null;
		*/
		function add_options_page() {
			register_setting(SPEC_COMMENT_OPT, SPEC_COMMENT_OPT, array(&$this, 'validate_option'));
			add_theme_page(__('Comments', SPEC_COMMENT_DOM), __('Comments', SPEC_COMMENT_DOM), 'manage_options', SPEC_COMMENT_DOM, array(&$this, 'options_page'));
		}


		/*
		 The the processed return from the form page is checked here before
		 returning the cleaned up version to WP for saving to our options var.

		 @return array The parameters to save to our options.
		*/
		function validate_option($options) {
			$outgoing['comments_nest_depth'] = intval($options['comments_nest_depth']) > 0 || intval($options['comments_nest_depth']) < 11 ? intval($options['comments_nest_depth']) : 1;

			$outgoing['load_dark'] = $options['load_dark'] == 1 ? 1 : 0;
			$outgoing['load_js'] = $options['load_js'] == 1 ? 1 : 0;
			$outgoing['load_css'] = $options['load_css'] == 1 ? 1 : 0;
			$outgoing['credit'] = $options['credit'] == 1 ? 1 : 0;

			return $outgoing;
		}


		/*
		 The options page.

		 @return null;
		*/
		function options_page() {?>
			<div class="wrap">
				<h2><?php _e('Threaded comment options', SPEC_COMMENT_DOM)?></h2>
				<form method="post" action="options.php">
					<?php settings_fields(SPEC_COMMENT_OPT); ?>

					<?php $options = $this->options; ?>
					<div id="poststuff" class="metabox-holder">
						<div id="post-body-content">
							<div class="stuffbox">
								<h3><?php _e('Rollup Depth', SPEC_COMMENT_DOM);?></h3>
								<div class="inside" id="<?php echo SPEC_COMMENT_OPT;?>_rollup">
									<p>
									<select name="<?php echo SPEC_COMMENT_OPT;?>[comments_nest_depth]" id="<?php echo SPEC_COMMENT_OPT;?>_comments_nest_depth" style="width:200px;"><?php
									for ($i = 1; $i  <= 10; $i++) {
										echo '<option value="' . $i . '"' . (intval($options['comments_nest_depth']) == $i ? ' selected="selected"' : '') . '>' . $i . '</option>';
									}?>
									</select>
									</p>
									<p style="max-width:520px;">
										<label for="<?php echo SPEC_COMMENT_OPT;?>_comments_nest_depth">
										<?php _e('This is the depth at which comments require a click to see replies. JavaScript is used to hide comments greater than this depth and replaces them with a toggle to click on to show them.', SPEC_COMMENT_DOM);  ?>
										</label>
									</p>
								</div>
								<div id="major-publishing-actions">
									<div id="publishing-action">
									<input type="submit" class="button-primary" value="<?php _e('Save', SPEC_COMMENT_DOM) ?>" /></div>
									<div class="clear"></div>
								</div>
							</div>
							<div class="stuffbox">

								<h3><?php _e('Toggles', SPEC_COMMENT_DOM);?></h3>
								<div class="inside">
									<p>
										<label for="<?php echo SPEC_COMMENT_OPT;?>_load_css"><?php _e('Use the stylesheet that came with this plug-in. ', SPEC_COMMENT_DOM);?></label>
										<input onchange="specFieldToggle('#<?php echo SPEC_COMMENT_OPT;?>_load_css', '#<?php echo SPEC_COMMENT_OPT;?>_dark_theme');" type="checkbox" value="1" name="<?php echo SPEC_COMMENT_OPT;?>[load_css]" id="<?php echo SPEC_COMMENT_OPT;?>_load_css"<?php echo $options['load_css'] ? ' checked="checked"' : '';?>/>
									</p>

									<p id="<?php echo SPEC_COMMENT_OPT;?>_dark_theme" style="padding-left:30px;">
										<label for="<?php echo SPEC_COMMENT_OPT;?>_load_dark"><?php _e('Use the dark theme CSS file. ', SPEC_COMMENT_DOM);?></label>
										<input type="checkbox" value="1" name="<?php echo SPEC_COMMENT_OPT;?>[load_dark]" id="<?php echo SPEC_COMMENT_OPT;?>_load_dark"<?php echo $options['load_dark'] ? ' checked="checked"' : '';?>/>
									</p>
									<br/>

									<p>
										<label for="<?php echo SPEC_COMMENT_OPT;?>_load_js"><?php _e('Use the included javaScript. ', SPEC_COMMENT_DOM);?></label>
										<input onchange="specFieldToggle('#<?php echo SPEC_COMMENT_OPT;?>_load_js', '#<?php echo SPEC_COMMENT_OPT;?>_rollup');" type="checkbox" value="1" name="<?php echo SPEC_COMMENT_OPT;?>[load_js]" id="<?php echo SPEC_COMMENT_OPT;?>_load_js"<?php echo $options['load_js'] ? ' checked="checked"' : '';?>/>
										<br/><em>Disabling javaScript will cause problems for ie6 as jQuery is used to add classes to items that would otherwise be inaccessible to the CSS.</em>
									</p>

									<br/>
									<p>
										<label for="<?php echo SPEC_COMMENT_OPT;?>_credit"><?php _e('Show our credit link at the bottom of the comments form.', SPEC_COMMENT_DOM);?></label>
										<input type="checkbox" value="1" name="<?php echo SPEC_COMMENT_OPT;?>[credit]" id="<?php echo SPEC_COMMENT_OPT;?>_credit"<?php echo $options['credit'] ? ' checked="checked"' : '';?>/>
										<br/><?php _e('If you choose to hide our credit link &lsquo;please&rsquo; think about signing up at our site otherwise we get no rewards for our good work.');?> <img src="<?php bloginfo('home')?>/wp-includes/images/smilies/icon_cry.gif" alt=":("/><br/><a href="https://spectacu.la/signup/signup.php">Spectacu.la</a>
									</p>
								</div>
							</div>
						</div>
					</div>

					<script type="text/javascript" language="JavaScript">
						//<![CDATA[
						function specFieldToggle(trigger, target) {
							if(typeof jQuery != "undefined"){
								 if ( jQuery(trigger).attr('checked')){
									jQuery(target).css({color:'#000'}).find('input, select').attr({disabled:''});
								} else {
									jQuery(target).css({color:'#ccc'}).find('input, select').attr({disabled:'disabled'});
								}
							}
						}

						specFieldToggle('#<?php echo SPEC_COMMENT_OPT;?>_load_css', '#<?php echo SPEC_COMMENT_OPT;?>_dark_theme');
						specFieldToggle('#<?php echo SPEC_COMMENT_OPT;?>_load_js', '#<?php echo SPEC_COMMENT_OPT;?>_rollup');
						//]]>
					</script>
				</form>
			</div>
			<?php
		}


		/*
		 This is attached to the comment_form action and just echos out our
		 credit link. If you don't want to give us credit for this :( then you
		 can hide the link from the admin page.
		*/
		function our_credit(){

			if ($this->options['credit']) {
				echo '<p class="spectacula-credit"><small>Threaded commenting powered by <a href="http://spectacu.la/">Spectacu.la</a> code.</small></p>';
			} else {
				// If you've unticked the show our credit link we'll just have it as an HTML comment instead.
				// Nothing to stop you removing this line too. But please don't.
				echo "\n<!-- Threaded commenting powered by http://Spectacu.la/ code. -->\n";
			}
		}
	}

	/*
	 Check that Wordpress is of sufficient newness to run this.
	*/
	if (version_compare($wp_version, SPEC_COMMENT_VER, 'ge'))
		add_action('init', create_function('', 'return new spec_commenting();'), 5);
}?>
