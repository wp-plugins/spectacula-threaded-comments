<?php

if ( ! class_exists( 'spec_options_page' ) ) {

	class spec_options_page {

		/*
		 Set your defaults.
		 %1$s == template_url,
		 %2$s == template_path,
		 %3$s == plugin_url,
		 %4$s == plugin_path.
		*/
		var $defaults = array(
							'comments_nest_depth' => 1, // The depth we start to roll up the comments from.
							'stylesheet' => '%3$s/style/comments.css',
							'credit' => true,
							'title' => 'Comments',
							'trackback' => 'Trackbacks',
							'polling' => 30, // Frequency to poll the server for new comments in seconds.
							'update' => true, // Do we want to auto update or not.
							);


		var $stuff_boxes = array( );

		function init( ) {
			$this->add_stuff_box( array( 'title' => __( 'Rollup Depth', SPEC_COMMENT_DOM ), 'callback' => 'comment_nest_depth' ) );
			$this->add_stuff_box( array( 'title' => __( 'Titles', SPEC_COMMENT_DOM ), 'callback' => 'titles' ) );
			$this->add_stuff_box( array( 'title' => __( 'Stylesheet', SPEC_COMMENT_DOM ), 'callback' => 'stylesheet' ) );
			$this->add_stuff_box( array( 'title' => __( 'Comment update', SPEC_COMMENT_DOM ), 'callback' => 'polling' ) );
			$this->add_stuff_box( array( 'title' => __( 'Our credit', SPEC_COMMENT_DOM ), 'callback' => 'credit' ) );
		}

		function polling( $options = '' ) { ?>
			<p>
				<label for="<?php $this->item_attrib( 'update' ); ?>"><?php _e( 'Auto update comments', SPEC_COMMENT_DOM ); ?></label>
				<input type="checkbox" value="1" <?php checked( $options[ 'update' ] );?> name="<?php $this->item_attrib( 'update', true ); ?>" id="<?php $this->item_attrib( 'update' ); ?>" />
			</p>

			<p>
				<label for="<?php $this->item_attrib( 'polling' ); ?>"><?php _e( 'Frequency of comment update in seconds ( minimum 5 seconds )', SPEC_COMMENT_DOM ); ?></label>
				<input style="vertical-align:middle" class="regular-text" size="3" maxlength="3" type="text" value="<?php echo esc_attr( $options[ 'polling' ] );?>" name="<?php $this->item_attrib( 'polling', true ); ?>" id="<?php $this->item_attrib( 'polling' ); ?>" />
			</p>

			<?php
		}


		function titles( $options = '' ) { ?>
			<p><label for="<?php $this->item_attrib( 'title' ); ?>"><?php _e( 'Comments title', SPEC_COMMENT_DOM ); ?></label></p>
			<p><input style="width:98%" class="regular-text" type="text" value="<?php echo esc_attr( $options[ 'title' ] );?>" name="<?php $this->item_attrib( 'title', true ); ?>" id="<?php $this->item_attrib( 'title' ); ?>" /></p>

			<p><label for="<?php $this->item_attrib( 'trackback' ); ?>"><?php _e( 'Trackback title', SPEC_COMMENT_DOM ); ?></label></p>
			<p><input style="width:98%" class="regular-text" type="text" value="<?php echo esc_attr( $options[ 'trackback' ] );?>" name="<?php $this->item_attrib( 'trackback', true ); ?>" id="<?php $this->item_attrib( 'trackback' ); ?>" /></p>
			<?php
		}


		function stylesheet( $options = '' ) { ?>

			<p><label for="<?php $this->item_attrib( 'stylesheet', true ); ?>"><?php _e( 'Choose the stylesheet you want to use for the comments.', SPEC_COMMENT_DOM ) ?></label></p>
			<p>
				<select name="<?php $this->item_attrib( 'stylesheet', true ); ?>" id="<?php $this->item_attrib( 'stylesheet', true ); ?>" style="width:200px;">
					<option value="0"<?php selected( $options[ 'stylesheet' ], '' ) ?>><?php _e( 'Disable', SPEC_COMMENT_DOM );?></option><?php

					$stylesheets = spec_stylesheet_find( );
					foreach( ( array ) $stylesheets as $name => $stylesheet ) {?>
						<option value="<?php echo $name ?>" <?php selected( $stylesheet[ 'url' ], $options[ 'stylesheet' ] ); ?>><?php echo $stylesheet[ 'title' ] ?></option><?php
					} ?>

				</select>
			</p>
			<p><?php _e( 'If you want to customise the look of the comments you can do, however avoid making changes to the original CSS files as changes could be wiped out by any update.
						You can copy the comments.css from the style folder within this plug-in to your theme folder, make changes to it there then select the "theme style" option from the
						drop down above. You can also make a copy of it in the style folder, change the comment "comment style: xxxxxx" at the top of the file and then select that name from
						the drop down. You can also disable the inbuilt style system if you want to roll your comment CSS into your theme CSS.', SPEC_COMMENT_DOM ); ?></p><?php
		}


		function credit( $options = '' ) { ?>
			<p>
				<label for="<?php $this->item_attrib( 'credit' ); ?>"><?php _e( 'Show our credit link at the bottom of the comments form.', SPEC_COMMENT_DOM ); ?></label>
				<input type="checkbox" value="1" name="<?php $this->item_attrib( 'credit', true ); ?>" id="<?php $this->item_attrib( 'credit' ); ?>"<?php checked( intval( $options[ 'credit' ] ), 1 )?>/>
			</p>
			<p><?php _e( 'If you choose to hide our credit link &lsquo;please&rsquo; think about signing up at our site otherwise we get no rewards for our good work.' );?> <img src="<?php bloginfo( 'home' )?>/wp-includes/images/smilies/icon_cry.gif" alt=":( "/><br/><a href="https://spectacu.la/signup/signup.php">Spectacu.la</a></p><?php
		}


		function comment_nest_depth( $options = '' ) { ?>
			<p>
			<select name="<?php $this->item_attrib( 'comments_nest_depth', true ); ?>" id="<?php $this->item_attrib( 'comments_nest_depth', true ); ?>" style="width:200px;">
				<option value="0"<?php selected( intval( $options[ 'comments_nest_depth' ] ), 0 ) ?>><?php _e( 'Disable', SPEC_COMMENT_DOM );?></option><?php
				for ( $i = 1; $i  <= 10; $i++ ) {?>
					<option value="<?php echo $i; ?>"<?php selected( intval( $options[ 'comments_nest_depth' ] ), $i ) ?>><?php echo $i; ?></option><?php
				}?>
			</select>
			</p>
			<p>
				<label for="<?php $this->item_attrib( 'comments_nest_depth', true ); ?>">
				<?php _e( 'This is the depth at which comments require a click to see replies. JavaScript is used to hide comments greater than this depth and replaces them with a toggle to click on to show them.', SPEC_COMMENT_DOM );  ?>
				</label>
			</p><?php
		}


		function validate_options( $options = '' ) {

			do_action( 'spec_options_page_update' );

			// Return the defaults
			if ( isset( $options[ 'reset' ] ) && intval( $options[ 'reset' ] ) == 1 ) {
				return $this->defaults;
			} else {
				// My options
				$output[ 'title' ] = html_entity_decode( stripcslashes( $options[ 'title' ] ) );
				$output[ 'trackback' ] = html_entity_decode( stripcslashes( $options[ 'trackback' ] ) );
				$output[ 'credit' ] = intval( $options[ 'credit' ] ) == 1 ? true : false;
				$output[ 'comments_nest_depth' ] = intval( $options[ 'comments_nest_depth' ] ) >= 0 && intval( $options[ 'comments_nest_depth' ] ) <= 10 ? intval( $options[ 'comments_nest_depth' ] ) : $this->defaults[ 'comments_nest_depth' ];
				$output[ 'polling' ] = intval( $options[ 'polling' ] ) >= 5 && intval( $options[ 'polling' ] ) <= 999 ? intval( $options[ 'polling' ] ) : $this->defaults[ 'polling' ];
				$output[ 'update' ] = intval( $options[ 'update' ] ) == 1 ? true : false;

				$stylesheets = spec_stylesheet_find( );

				if ( $options[ 'stylesheet' ] === 0 ) {
					$output[ 'stylesheet' ] = '';
				} else if ( isset( $stylesheets[ $options[ 'stylesheet' ] ][ 'url' ] ) ) {
					$output[ 'stylesheet' ] = $stylesheets[ $options[ 'stylesheet' ] ][ 'url' ];
				} else {
					$output[ 'stylesheet' ] = $defaults[ 'stylesheet' ];
				}

				return $output;
			}
		}

		/* ++++++++++++++++++++++ Shouldn't need to edit beyond here ++++++++ */

		/*
		 Output the that goes in the name or ID field just for the sake of lazy.
		*/
		function item_attrib( $value, $name = false) {
			if ( $name )
				echo esc_attr( SPEC_COMMENT_OPT . "[{$value}]" );
			else
				echo esc_attr( SPEC_COMMENT_OPT . "_{$value}" );
		}


		function add_options_pages( ) {
			register_setting( SPEC_COMMENT_OPT, SPEC_COMMENT_OPT, array( &$this, 'validate_options' ) );
			add_options_page( __( 'Spectacu.la Comments', SPEC_COMMENT_DOM ), __( 'Ajax comments', SPEC_COMMENT_DOM ), 'manage_options', SPEC_COMMENT_OPT, array( &$this, 'options_page' ) );
		}


		function spec_options_page( ) {
			global $wp_version;

			if ( !defined( 'SPEC_COMMENT_OPT' ) )
				define ( 'SPEC_COMMENT_OPT', 'spectacula_threaded_comments' );

			if ( !defined( 'SPEC_COMMENT_DOM' ) )
				define ( 'SPEC_COMMENT_DOM', 'spectacula-threaded-comments' );

			foreach( $this->defaults as $key => $value ) {
				if ( is_string( $value ) )
					$this->defaults[ $key ] = sprintf( $value, get_bloginfo( 'template_url' ), TEMPLATEPATH, SPEC_COMMENT_URL, SPEC_COMMENT_PTH );
			}

			$this->options = wp_parse_args( get_option( SPEC_COMMENT_OPT ), $this->defaults );

			add_action( 'init', array( &$this, 'init' ) );
			add_action( 'admin_menu', array( &$this, 'add_options_pages' ) );
		}


		function retrive_comment_option( $option_name ) {
			if ( isset( $this->options[ $option_name ] ) ) {
				return $this->options[ $option_name ];
			} else {
				return false;
			}
		}


		function do_stuff_boxes( ) {
			foreach( ( array ) $this->stuff_boxes as $box ) {
				if ( isset( $box[ 'callback' ] ) && is_callable( array( &$this, $box[ 'callback' ] ) ) ) { ?>
					<div class="stuffbox">
						<h3><?php echo htmlentities( $box[ 'title' ], ENT_QUOTES, get_bloginfo( 'charset' ) ); ?></h3>
						<div class="inside">
							<?php call_user_func_array( array( &$this, $box[ 'callback' ] ), array( $this->options, $box[ 'title' ] ) );?>
						</div>
					</div>
					<?php
				}
			}
		}


		function add_stuff_box( $args = '' ) {
			$defaults = array( 'title' => 'Stuff Box', 'callback' => '' );
			$r = wp_parse_args( $args, $defaults );

			if ( isset( $r[ 'callback' ] ) ) {
				$this->stuff_boxes[] = $r;
			}
		}

		function options_page( ) { ?>

			<div class="wrap">
				<h2><?php _e( 'Comment Options', SPEC_COMMENT_DOM )?></h2>
				<form method="post" action="options.php" enctype="multipart/form-data">
					<?php settings_fields( SPEC_COMMENT_OPT ); ?>

					<div id="poststuff" class="metabox-holder has-right-sidebar has-sidebar">
						<div id="side-info-column" class="inner-sidebar">
							<div class="postbox">
								<h3><?php _e( 'Save settings', SPEC_COMMENT_DOM ); ?></label></h3>
								<div class="inside">
									<p><label for="<?php $this->item_attrib( 'reset' ); ?>"><?php _e( 'This will reset all options to their to defaults if checked when saved.', SPEC_COMMENT_DOM ); ?>
										<input type="checkbox" value="1" name="<?php $this->item_attrib( 'reset', true ); ?>" id="<?php $this->item_attrib( 'reset' ); ?>" />
									</label></p>
								</div>
								<div id="major-publishing-actions">
									<div id="publishing-action">
									<input type="submit" class="button-primary" value="<?php _e( 'Save', SPEC_COMMENT_DOM ) ?>" /></div>
									<div class="clear"></div>
								</div>
							</div>

						</div>
						<div id="post-body-content" class="has-sidebar-content">

							<?php $this->do_stuff_boxes(  ); ?>

						</div>
					</div>

				</form>
			</div>
			<?php
		}
	}

	$spec_options_page  = new spec_options_page ;


	if ( !function_exists( 'spec_comment_option' ) ) {
		function spec_comment_option( $option_name ) {
			global $spec_options_page;
			return $spec_options_page->retrive_comment_option( $option_name );
		}
	}

}?>
