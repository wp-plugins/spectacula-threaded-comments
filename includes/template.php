<?php
if ( __FILE__ == basename( $_SERVER[ 'SCRIPT_FILENAME' ] ) )
	die ( "Please don't do that." );

/*
 If we have no comments and comments are closed we drop out of here without
 doing anything at all, no point telling the user that something isn't available.
*/

if ( ( comments_open( ) || get_comments_number( ) > 0 ) && ( is_single( ) || is_page( ) ) && ! post_password_required( ) ) { ?>
	<div id="comments"<?php echo isset( $section_class ) ? $section_class : '';?>>
		<?php
		if ( have_comments( ) || comments_open( ) ) {
			if ( spec_comment_option( 'title' ) != '' && have_comments( ) ) {
				echo '<div class="comment-title">' . spec_comment_option( 'title' ) . '</div>';
			}?>
			<ul id="commentlist">
				<?php
				$order = get_option( 'comment_order' );

				if ( $order == 'desc' )
					spec_comments_form( );

				global $wp_query;

				wp_list_comments( array( 'type' => ( commenting_by_type( ) ? 'comment' : 'all' ), 'callback' => 'spec_comment_layout' ) );

				if ( $order != 'desc' )
					spec_comments_form( );

				?>
			</ul>

			<div id="comment-pagination">
				<?php paginate_comments_links( array( 'next_text'=> '&raquo;', 'prev_text' => '&laquo;' ) );?>
			</div>

			<?php
			// Trackbacks if apart from comments.
			if ( commenting_by_type( ) && ( $comments_by_type[ 'pingback' ] || $comments_by_type[ 'trackback' ] ) ) {
				if ( spec_comment_option( 'trackback' ) != '' ) {
					echo '<div class="comment-title">' . spec_comment_option( 'trackback' ) . '</div>';
				} ?>
				<ul id="trackback-list">
					<?php wp_list_comments( array( 'per_page' => -1, 'max_depth' => 0, type => 'pings', 'callback' => 'spec_comment_layout' ) );?>
				</ul>
				<?php
			}
		}?>
	</div><?php
}