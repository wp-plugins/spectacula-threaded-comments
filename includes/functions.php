<?php
/*
 In order to support older versions of WP the following functions will duplicate
 some of the newer WP function. Commenting works as expected in older versions
 but if you want/need support for the newer capabilities that WP offers then
 you'll need to upgrade to the latest version. This file can be extracted from
 this plug-in and can replace the one in your theme without too much fuss.

 These were originally included in the template file to allow me to just drop it
 in to a theme and not have to worry about it. Moved them out here so I could
 access them from ajaxy stuff. If I need to include these with a theme now I can
 just drop the plug-in folder into a subfolder of the theme and define the URL
 with an include pointing to the commenting.php and I'm done. :D
*/

/*
 There is a slight problem with wpmu 2.7 missing a class on the reply link this
 just adds it back in.
*/
global $wpmu_version;
if( function_exists( 'comment_reply_link' ) && version_compare( $wpmu_version, '2.7', 'eq' ) && ! function_exists( 'fix_comment_reply_link' ) ){
	add_filter( 'comment_reply_link', 'fix_comment_reply_link', 10, 2 );

	function fix_comment_reply_link( $link ){
		if ( stripos( $link, 'class' ) === false )
			$link = preg_replace( '/(<a\s[^>]*)(>)/', '\1 class="comment-reply-link"\2', $link );
		return $link;
	}
}

/*
 Needed for <= WP27
 Very crude version of WP28's esc_attr function. All I need really so good
 enough.
*/
if ( ! function_exists ( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		$output = htmlspecialchars_decode( $text, ENT_QUOTES );
		$output = htmlspecialchars( $output, ENT_QUOTES, get_bloginfo( 'charset' ) );
		return $text != $output ? $output : $text;
	}
}

/*
 Quickly check that we're running with separated comments. Comment by type is
 set at the call to comments_template and there seems to be no way to force it
 so we need to check for it and render the comments differently for each case.

 @return bool True if we habe a comment_by_type array set otherwise false.
*/

if ( ! function_exists( 'commenting_by_type' ) ) {
	function commenting_by_type( ) {
		global $wp_query;
		//
		if ( property_exists( $wp_query, 'comments_by_type' ) && $wp_query->comments_by_type )
			return true;
		else
			return false;
	}
}

/*
 Looks through the plug-in folder called style for any CSS files that can be
 used with this, and checks the theme folder for comments.css

 Collect the stylesheet array. If I wasn't targetting wp27 I'd store the content
 of this function in a transient to save doing the search twice in such a short
 period of time. Ah well, I'll kill wp2.7 support in a version or two so can do
 that then.

 @return array each file found has its own array containing title, url, basename
 and full filename.
*/
if ( ! function_exists( 'spec_find_stylesheets' ) ) {
	function spec_stylesheet_find( ) {

		$root = SPEC_COMMENT_PTH . '/style';

		// Open the DIR if we can
		$dir = @opendir( $root );
		if ( ! $dir )
			return false;

		$stylesheets = array( );

		while ( ( $dir_content = readdir( $dir ) ) !== false ) {
			$filename = $root . '/' . $dir_content;
			if ( is_dir( $filename ) || !is_readable( $filename ) || strtolower( substr( $dir_content, -3 ) ) != 'css' )
				continue;

			if ( file_exists( $filename ) && is_file( $filename ) ) {
				$file = @fopen( $filename, 'r' );
				$file_content = fread( $file, filesize( $filename ) );
				@fclose( $file );
			}

			// Find the first forward-slash asterix comment in the file.
			preg_match( '/(?:\/\*.*?\*\/)/s', $file_content, $comment );
			if ( $comment[ 0 ] && preg_match( '/comment style:(.*)$/mi', $comment[ 0 ], $title ) ) {
				// This key will need changing to avoid conflicts with two very sim filenames
				$key = sanitize_title( $dir_content );

				// Lets populate the array now.
				$stylesheets[ $key ][ 'filename' ] = $filename;
				$stylesheets[ $key ][ 'url'] = trailingslashit( SPEC_COMMENT_URL ) . 'style/' . basename( $filename );
				$stylesheets[ $key ][ 'basename' ] = basename( $filename );
				$stylesheets[ $key ][ 'title' ] = trim( preg_replace( "/\s*(?:\*\/|\?>).*/", '', $title[ 1 ] ) );

				/*
				 # @todo: Maybe at some point I'll use this.
				 if ( preg_match( '/Description:(.*)$/mi', $comment[ 0 ], $description ) )
					$stylesheets[ $key ][ 'description' ] = _cleanup_header_comment( $desc[ 1 ] );
				*/

			}
		}
		@closedir( $root );


		/*
		 Add css for the stuff found in the theme, check first with any child
		 theme then the parent.
		*/
		if ( file_exists( trailingslashit( STYLESHEETPATH ) . 'comments.css' ) ) {
			$stylesheets[ 'theme/template' ] = array(
											   'filename' => trailingslashit( STYLESHEETPATH ) . 'comments.css',
											   'basename' => 'comments.css',
											   'title' => __( 'Theme styling', SPEC_COMMENT_DOM ),
											   'url' => trailingslashit( get_stylesheet_directory_uri( ) ) . 'comments.css'
											);
		} else if ( file_exists( trailingslashit( TEMPLATEPATH ) . 'comments.css' ) ) {
			$stylesheets[ 'theme/template' ] = array(
											   'filename' => trailingslashit( TEMPLATEPATH ) . 'comments.css',
											   'basename' => 'comments.css',
											   'title' => __( 'Theme styling', SPEC_COMMENT_DOM ),
											   'url' => trailingslashit( get_stylesheet_directory_uri( ) ) . 'comments.css'
											);
		}

		return $stylesheets;
	}
}

/*
 The comment form. Simple as that really. In here rather than the template as it
 can be called in different places depending on comment order.

 @return null
*/
if ( ! function_exists( 'spec_comments_form' ) ) {
	function spec_comments_form( ) {
		global $post, $id, $comment, $user_ID, $current_user, $user_identity;

		if ( comments_open( ) ) {

			if ( ! is_user_logged_in( ) )
				$commenter = wp_get_current_commenter( );

			if ( spec_comment_option( 'form_avatar' ) )
				$avatar = get_avatar( isset( $current_user->user_email ) ? $current_user->user_email : $commenter[ 'comment_author_email' ], 64 ); ?>

			<li class="depth-1<?php echo isset( $avatar ) && $avatar ? ' with-avatar' : ''?>" id="respond">

			<?php
				$form_title = spec_comment_option( 'form_title' );
				if ( $form_title != '' )
					echo '<div class="comment-title">' . $form_title . '</div>'; ?>

				<div class="comment-body"><?php
					// Not logged in, then you're not getting the form.
					if ( get_option( 'comment_registration' ) && ! $user_ID ) {

						_e( 'You must be logged in to comment.', SPEC_COMMENT_DOM );
						echo ' '; // Adding this here rather than the string above so translations will still work from the old version
						wp_loginout( get_permalink( ) );

					} else {
						if ( get_option( 'show_avatars' ) && isset( $avatar ) && $avatar != '' ) { ?>
							<div class="comment-author-avatar">
								<?php echo isset( $current_user->user_email ) || isset( $commenter[ 'comment_author_email' ] ) ? '<a href="http://gravatar.com/site/login" title="' . __( 'Change Your Avatar', SPEC_COMMENT_DOM ) . '">' . $avatar . '</a>' : $avatar; ?>
							</div><?php
						} ?>

						<form action="<?php echo get_option( 'siteurl' )?>/wp-comments-post.php" method="post" id="comment-form">
							<fieldset><?php

							if ( $user_ID ) {?>
								<div class="comment-meta">
									<cite class="fn"><?php echo $user_identity; ?></cite>
								</div>

								<?php
							} else {
								$req = get_option( 'require_name_email' ); ?>

								<div class="comment-form-row">
									<label for="author"><?php _e( 'Name', SPEC_COMMENT_DOM ); ?></label>
									<input type="text" name="author" id="author" value="<?php echo esc_attr( $commenter[ 'comment_author' ] ); ?>" size="30" tabindex="1"<?php echo $req ? ' class="vital"' : ''; ?>/>
								</div>

								<div class="comment-form-row">
									<label for="email"><?php _e( 'Email', SPEC_COMMENT_DOM ); ?></label>
									<input type="text" name="email" id="email" value="<?php echo esc_attr( $commenter[ 'comment_author_email' ] ); ?>" size="30" tabindex="2"<?php echo $req ? ' class="vital"' : ''; ?>/>
								</div>

								<div class="comment-form-row">
									<label for="url"><?php _e( 'Website', SPEC_COMMENT_DOM )?></label>
									<input type="text" name="url" id="url" value="<?php echo esc_attr( $commenter[ 'comment_author_url' ] ); ?>" size="30" tabindex="3" />
								</div>
								<?php
							}?>

								<div class="textarea-border">
									<textarea name="comment" id="comment" cols="50" rows="3" tabindex="4" class="vital"></textarea>
								</div>

								<div class="comment-buttons">
									<input name="submit" type="submit" tabindex="5" value="<?php _e( 'Post your comment', SPEC_COMMENT_DOM ); ?>" class="submit" />
									<?php cancel_comment_reply_link( __( 'Cancel reply', SPEC_COMMENT_DOM ) );

									if ( $user_ID ) { ?>

									<a class="comment-button" href="<?php echo admin_url( 'profile.php' ); ?>"><?php _e( 'Edit profile' );?> </a>
									<a class="comment-button" href="<?php echo wp_logout_url( $_SERVER[ 'REQUEST_URI' ] );?>" title="<?php _e( 'Log out of this account', SPEC_COMMENT_DOM ) ?>"><?php _e( 'Log Out', SPEC_COMMENT_DOM )?></a><?php

									} ?>
								</div>

								<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" /><?php
								comment_id_fields( );
								do_action( 'comment_form', $post->ID );?>
							</fieldset>
						</form><?php
					}?>
				</div>
			</li><?php
		}
	}
}

/*
 Comment layout function used by WP27 walker .
 @return null
*/
if ( ! function_exists( 'spec_comment_layout' ) ) {
	function spec_comment_layout( $comment, $args = array( ), $depth = null ) {
		global $post;

		$GLOBALS[ 'comment' ] = $comment;

		extract( $args, EXTR_SKIP );

		if ( ! ( isset( $post ) && is_object( $post ) ) && ( isset( $post_id ) && intval( $post_id ) ) )
			$post = get_post( $post_id );

		if ( ! isset( $max_depth ) )
			$max_depth = get_option( 'thread_comments_depth' );

		$add_below = 'comment';
		$tb = $comment->comment_type == 'trackback' || $comment->comment_type == 'pingback';

		// Avatar ( Still in glorious 2D :P )
		if ( get_option( 'show_avatars' ) && $avatar_size != 0 ) {
			$avatar = get_avatar( $comment, ( $depth == 1 ? 64 : 32 ) );
		} ?>

		<li id="comment-<?php comment_ID( ); ?>" <?php echo comment_class( $avatar ? 'with-avatar' : '', get_comment_ID( ), null, false ); ?>>

			<div id="div-comment-<?php comment_ID( ) ?>" class="comment-body">
				<?php //echo $comment->comment_date; ?>

				<?php

				echo $avatar && ! $tb ? '<div class="comment-author-avatar">' . $avatar . '<span class="avatar-overlay"></span></div>' : ''; ?>
				<div class="comment-content">
					<div class="comment-meta">
						<cite class="fn"><?php comment_author_link( ); ?></cite>
						<span class="date"><?php printf( __( '%1$s at %2$s', SPEC_COMMENT_DOM ), get_comment_date( ),  get_comment_time( ) ) ?></span>
					</div>

					<div class="comment-text">
						<?php
						comment_text( );
						$comment->comment_approved == 0 ? printf( '<span class="moderation">%s</span>', __( 'Comment awaiting moderation.', SPEC_COMMENT_DOM ) ) : '';?>
					</div>

					<?php
					if ( ! $tb ) { ?>
					<div class="comment-buttons"><?php
						comment_reply_link( array_merge( $args, array( 'add_below' => $add_below, 'depth' => $depth, 'max_depth' => $max_depth, 'reply_text' => __( 'Reply', SPEC_COMMENT_DOM ) ) ), null, isset( $args[ 'post_id' ] ) && intval( $args[ 'post_id' ] ) ? intval( $args[ 'post_id' ] ) : null );

						edit_comment_link( __( 'Edit', SPEC_COMMENT_DOM ), '', '' ); ?>
						<a class="comment-button comment-link" href="<?php echo htmlspecialchars( get_comment_link( ) ) ?>"><?php _e( 'Link', SPEC_COMMENT_DOM ) ?></a>
					</div><?php
					} ?>

				</div>

			</div>
		<?php
	}
}?>
