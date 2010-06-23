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
 Quick check to see if the post is password protected. For <= WP26.
 @return bool
*/
if ( ! function_exists( 'post_password_required' ) ) {
	function post_password_required( ){
		return ! empty( $post->post_password ) && $_COOKIE[ 'wp-postpass_'.COOKIEHASH ] != $post->post_password;
	}
}

/*
 Assembles the log out URL for WP26 and older.
 @param $redirect A URL to redirect to after log out has completed.
 @return string Link to logout URL with an appropriate redirect parameter.
*/
if ( ! function_exists( 'wp_logout_url' ) ) { // For <= WP26
	function wp_logout_url( $redirect = '' ){
		$redirect =  strlen( $redirect ) ? "&redirect_to=$redirect" : 'redirect_to='.urlencode( get_permalink( ) );
		return get_option( 'siteurl' )."/wp-login.php?action=logout$redirect";
	}
}

/*
 Simple check to see if there are comments or not. Needed for <= WP21
 @return bool
*/
if ( ! function_exists( 'have_comments' ) ) {
	function have_comments( ){
		return ( get_comments_number( ) > 0 ? true : false );
	}
}

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



if ( ! function_exists ( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		$safe_text = wp_check_invalid_utf8( $text );
		//$safe_text = _wp_specialchars( $safe_text, ENT_QUOTES );
		return apply_filters( 'attribute_escape', $safe_text, $text );
	}
}

/*
 Quick interpretation of the WP27 function comment_class for <= WP26
 @param $class array of strings to be added to the returned class
 @param $ignored As the name implies this param is ignored
 @param $echo bool Choose to echo or return
 @return string standard html class attribute
*/
if ( ! function_exists( 'comment_class' ) ) { //
	function comment_class( $class = array( ), $ignored = null, $ignored = null, $echo = true ){
		global $comment, $comment_count, $post;
		$comment_count ++;

		// Set up the class for this comment.
		$class[ ] = get_comment_type( );
		$class[ ] = 'depth-1';
		$class[ ] = $comment->comment_approved == 0 ? 'unapproved' : 'approved';
		$class[ ] = $comment_count % 2 ? 'odd' : 'even';

		if ( $comment_count == 1 )
			$class[ ] = 'first';

		if ( $comment->user_id == $post->post_author )
			$class[ ] = 'bypostauthor';

		if ( is_array( $class ) && count( $class ) > 0 )
			$comment_class = ' class="'.implode( ' ', $class ).'"';
		else
			unset ( $comment_class );

		if ( $echo )
			echo $comment_class;
		else
			return $comment_class;
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
 Comment layout function used by WP27 walker .
 @return null
*/

if ( ! function_exists( 'spec_comment_layout' ) ) {
	function spec_comment_layout( $comment, $args = array( ), $depth = null ){
		$GLOBALS[ 'comment' ] = $comment;

		extract( $args, EXTR_SKIP );

		if ( ! isset( $max_depth ) )
			$max_depth = get_option( 'thread_comments_depth' );

		$add_below = 'comment';
		$tb = $comment->comment_type == 'trackback' || $comment->comment_type == 'pingback';

		// Avatar ( Still in glorious 2D :P )
		if ( function_exists( 'get_avatar' ) && $avatar_size != 0 ) {
			$avatar = get_avatar( $comment, ( $depth == 1 ? 64 : 32 ) );
		} ?>

		<li id="comment-<?php comment_ID( ); ?>" <?php echo comment_class( $avatar ? 'with-avatar' : '', get_comment_ID( ), null, false ); ?>>

			<div id="div-comment-<?php comment_ID( ) ?>" class="comment-body">
				<?php

				echo $avatar && ! $tb ? '<div class="comment-author-avatar">' . $avatar . '</div>' : ''; ?>
				<div class="comment-content">
					<div class="comment-meta">
						<cite class="fn"><?php comment_author_link( ); ?></cite>
						<span class="date"><?php printf( __( '%1$s at %2$s', SPEC_COMMENT_DOM ), get_comment_date( ),  get_comment_time( ) ) ?></span>
					</div>

					<div class="comment-text">
						<?php
						comment_text( );
						$comment->comment_approved == 0 ? printf( '<span class="moderation">%s</span>', __( 'Comment in moderation.', SPEC_COMMENT_DOM ) ) : '';?>
					</div>

					<?php
					if ( ! $tb ) { ?>
					<div class="comment-buttons"><?php
						comment_reply_link( array_merge( $args, array( 'add_below' => $add_below, 'depth' => $depth, 'max_depth' => $max_depth, 'reply_text' => __( 'Reply', SPEC_COMMENT_DOM ) ) ), null, intval( $args[ 'post_id' ] ) ? intval( $args[ 'post_id' ] ) : null );

						edit_comment_link( __( 'Edit', SPEC_COMMENT_DOM ), '', '' ); ?>
						<a class="comment-button comment-link" href="<?php echo htmlspecialchars( get_comment_link( ) ) ?>"><?php _e( 'Link', SPEC_COMMENT_DOM ) ?></a>
					</div><?php
					} ?>

				</div>

			</div>
		<?php
	}
}
?>
