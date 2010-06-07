<?php
if ( __FILE__ == basename( $_SERVER[ 'SCRIPT_FILENAME' ] ) )
	die ( "Please don't do that." );

function spec_comments_form( ) {
	global $post, $id, $comment, $user_ID, $current_user, $user_identity;

	if ( comments_open( ) ) {

		$commenter = wp_get_current_commenter( );

		if ( function_exists( 'get_avatar' ) && get_option( 'show_avatars' ) ) {
			$avatar = get_avatar( $current_user->user_email ? $current_user->user_email : $commenter[ 'comment_author_email' ] , 64 );
		} ?>

		<li class="depth-1<?php echo $avatar ? ' with-avatar' : ''?>" id="respond">
			<div class="comment-body"><?php
				// Not logged in, then you're not getting the form.
				if ( get_option( 'comment_registration' ) && ! $user_ID ) {

					_e( 'You must be logged in to comment.', SPEC_COMMENT_DOM );
					echo ' '; // Adding this here rather than the string above so translations will still work from the old version
					wp_loginout( get_permalink( ) );

				} else {?>
					<div class="comment-author-avatar">
						<?php echo $current_user->user_email || $commenter[ 'comment_author_email' ] ? '<a href="http://gravatar.com/site/login" title="' . __( 'Change Your Avatar', SPEC_COMMENT_DOM ) . '">' . $avatar . '</a>' : $avatar; ?>
					</div>

					<form action="<?php echo get_option( 'siteurl' )?>/wp-comments-post.php" method="post" id="comment-form">
						<fieldset><?php

						if ( $user_ID ) {?>
							<div class="comment-meta">
								<cite class="fn"><?php echo $user_identity; ?></cite>
							</div>

							<?php
						} else {
							$req = get_option( 'require_name_email' ); ?>

							<div>
								<input type="text" name="author" id="author" value="<?php echo esc_attr( $commenter[ 'comment_author' ] ); ?>" size="30" tabindex="1"<?php echo ( $req ? ' class="vital"' : '' )?>/>
								<label for="author">
									<small><?php _e( 'Name', SPEC_COMMENT_DOM )?> <?php if ( $req ) _e( '(required)', SPEC_COMMENT_DOM )?></small>
								</label>
							</div>
							<div>
								<input type="text" name="email" id="email" value="<?php echo esc_attr( $commenter[ 'comment_author_email' ] ); ?>" size="30" tabindex="2"<?php echo ( $req ? ' class="vital"' : '' )?>/>
								<label for="email">
									<small><?php _e( 'Mail (will not be published)', SPEC_COMMENT_DOM )?> <?php if ( $req ) _e( '(required)', SPEC_COMMENT_DOM )?></small>
								</label>
							</div>
							<div>
								<input type="text" name="url" id="url" value="<?php echo esc_attr( $commenter[ 'comment_author_url' ] ); ?>" size="30" tabindex="3" />
								<label for="url">
									<small><?php _e( 'Website', SPEC_COMMENT_DOM )?> </small>
								</label>
							</div><?php
						}?>
							<div class="textarea-border">
								<textarea name="comment" id="comment" cols="50" rows="3" tabindex="4" class="vital"></textarea>
							</div>

							<div class="comment-buttons">
								<input name="submit" type="submit" tabindex="5" value="<?php _e( 'Post your comment', SPEC_COMMENT_DOM ); ?>" class="submit" />
								<?php cancel_comment_reply_link( __( 'Cancel reply', SPEC_COMMENT_DOM ) );

								if ( $user_ID ) { ?>
								<a class="comment-button" href="<?php echo admin_url( 'profile.php' ); ?>"><?php _e( 'Edit profile' );?> </a>
								<a class="comment-button" href="<?php echo wp_logout_url( $_SERVER[ 'REQUEST_URI' ] );?>" title="<?php _e( 'Log out of this account', SPEC_COMMENT_DOM ) ?>"><?php _e( 'Log Out', SPEC_COMMENT_DOM )?></a>

								<?php } ?>

							</div>

							<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" /><?php
							if ( function_exists( 'comment_id_fields' ) ) {
								comment_id_fields( );
							}
							do_action( 'comment_form', $post->ID );?>
						</fieldset>
					</form><?php
				}?>
			</div>
		</li><?php
	}
}

/*
 If we have no comments and comments are closed we drop out of here without
 doing anything at all, no point telling the user that something isn't available.
*/

if ( ( comments_open( ) || get_comments_number( ) > 0 ) && ( is_single( ) || is_page( ) ) && ! post_password_required( ) ) { ?>
	<div id="comments"<?php echo $section_class;?>>
		<?php
		if ( have_comments( ) || comments_open( ) ) {

			// Trackbacks if apart from comments.
			if ( commenting_by_type( ) && ( $comments_by_type[ 'pingback' ] || $comments_by_type[ 'trackback' ] ) ) { ?>
				<strong class="comment-title"><?php _e( 'Trackbacks', SPEC_COMMENT_DOM )?></strong>
				<ul id="trackback-list">
					<?php wp_list_comments( array( 'max_depth' => 0, type => 'pings' ) );?>
				</ul>
				<?php
			}?>

			<strong class="comment-title"><?php _e( 'Comments', SPEC_COMMENT_DOM )?></strong>
			<ul id="commentlist">
				<?php
				$order = get_option( 'comment_order' );

				if ( $order == 'desc' )
					spec_comments_form( );

				wp_list_comments( array( 'type' => ( commenting_by_type( ) ? 'comment' : 'all' ), 'callback' => 'spec_comment_layout' ) );

				if ( $order != 'desc' )
					spec_comments_form( );

				?>
			</ul>
			<div id="comment-pagination">
				<?php paginate_comments_links( array( 'next_text'=> '&raquo;', 'prev_text' => '&laquo;' ) );?>
			</div>
			<?php
		}?>
	</div><?php
}?>
