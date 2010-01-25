<?php
if (__FILE__ == basename($_SERVER['SCRIPT_FILENAME']))
	die ("Please don't do that.");

/*
 In order to support older versions of WP the following functions will duplicate
 some of the newer WP function. Commenting works as expected in older versions
 but if you want/need support for the newer capabilities that WP offers then
 you'll need to upgrade to the latest version. This file can be extracted from
 this plug-in and can replace the one in your theme without too much fuss.
*/

/*
 Quick check to see if the post is password protected. For <= WP26.
 @return bool
*/
if (!function_exists('post_password_required')) {
	function post_password_required(){
		return !empty($post->post_password) && $_COOKIE['wp-postpass_'.COOKIEHASH] != $post->post_password;
	}
}

/*
 Assembles the log out URL for WP26 and older.
 @param $redirect A URL to redirect to after log out has completed.
 @return string Link to logout URL with an appropriate redirect parameter.
*/
if (!function_exists('wp_logout_url')) { // For <= WP26
	function wp_logout_url($redirect = ''){
		$redirect =  strlen($redirect) ? "&redirect_to=$redirect" : 'redirect_to='.urlencode(get_permalink());
		return get_option('siteurl')."/wp-login.php?action=logout$redirect";
	}
}

/*
 Simple check to see if there are comments or not. Needed for <= WP21
 @return bool
*/
if (!function_exists('have_comments')) {
	function have_comments(){
		return (get_comments_number() > 0 ? true : false);
	}
}

/*
 There is a slight problem with wpmu 2.7 missing a class on the reply link this
 just adds it back in.
*/
global $wpmu_version;
if(function_exists('comment_reply_link') && version_compare($wpmu_version, '2.7', 'eq') && !function_exists('fix_comment_reply_link')){
	add_filter('comment_reply_link', 'fix_comment_reply_link', 10, 2);

	function fix_comment_reply_link($link){
		if (stripos($link, 'class') === false)
			$link = preg_replace('/(<a\s[^>]*)(>)/', '\1 class="comment-reply-link"\2', $link);
		return $link;
	}
}

/*
 Quick interpretation of the WP27 function comment_class for <= WP26
 @param $class array of strings to be added to the returned class
 @param $ignored As the name implies this param is ignored
 @param $echo bool Choose to echo or return
 @return string standard html class attribute
*/
if (!function_exists('comment_class')) { //
	function comment_class($class = array(), $ignored = null, $ignored = null, $echo = true ){
		global $comment, $comment_count, $post;
		$comment_count ++;

		// Set up the class for this comment.
		$class[] = get_comment_type();
		$class[] = 'depth-1';
		$class[] = $comment->comment_approved == 0 ? 'unapproved' : 'approved';
		$class[] = $comment_count % 2 ? 'odd' : 'even';

		if ($comment_count == 1)
			$class[] = 'first';

		if ($comment->user_id == $post->post_author)
			$class[] = 'bypostauthor';

		if (is_array($class) && count($class) > 0)
			$commentClass = ' class="'.implode(' ', $class).'"';
		else
			unset ($commentClass);

		if ($echo)
			echo $commentClass;
		else
			return $commentClass;
	}
}

/*
 Quickly check that we're running with separated comments. Comment by type is
 set at the call to comments_template and there seems to be no way to force it
 so we need to check for it and render the comments differently for each case.

 @return bool True if we habe a comment_by_type array set otherwise false.
*/
if (!function_exists('commenting_by_type')) {
	function commenting_by_type() {
		global $wp_query;
		//
		if (property_exists($wp_query, 'comments_by_type') && $wp_query->comments_by_type)
			return true;
		else
			return false;
	}
}


/*
 Comment layout function used by WP27 walker .
 @return null
*/

if (!function_exists('comment_layout')) {
	function comment_layout($comment, $args = array(), $depth = null){
		$GLOBALS['comment'] = $comment;
		extract($args, EXTR_SKIP);

		if ( 'div' == $style ) {
			$tag = 'div';
			$add_below = 'comment';
		} else {
			$tag = 'li';
			$add_below = 'div-comment';
		}

		echo "<$tag id=\"comment-".get_comment_ID().'" ' . comment_class(empty($has_children) ? '' : 'parent', get_comment_ID(), null, false).'>';?>
		<div class="comment-body">
			<div id="div-comment-<?php comment_ID() ?>">
				<div class="comment-author vcard">
					<?php echo function_exists('get_avatar') && $avatar_size != 0 ? get_avatar( $comment, $avatar_size ) : ''; ?>
					<?php printf('<cite class="fn">%s</cite>', get_comment_author_link()) ?>
				</div>

				<?php comment_text();?>

				<div class="comment-meta commentmetadata">
					<?php function_exists('comment_reply_link') ? comment_reply_link(array_merge($args, array('add_below' => $add_below, 'depth' => $depth, 'max_depth' => $max_depth))) : '';?>
					<?php //comment_type(__('comment', SPEC_COMMENT_DOM), __('trackback', SPEC_COMMENT_DOM), __('trackback', SPEC_COMMENT_DOM)) ?>
					<a href="<?php echo htmlspecialchars( get_comment_link( $comment->comment_ID ) ) ?>"><?php printf(__('%1$s at %2$s', SPEC_COMMENT_DOM), get_comment_date(),  get_comment_time()) ?></a>
					<?php $comment->comment_approved == 0 ? printf('<em>|&nbsp;%s</em>', __('Comment in moderation.', SPEC_COMMENT_DOM)) : ''; ?>
					<?php edit_comment_link(__('Edit', SPEC_COMMENT_DOM), '|&nbsp;', '') ?>
				</div>
			</div>
		</div>
		<?php
	}
}

/*
 Quickly check that we've added the browser sig derived elements to the body. If
 not then we'll call the sniffer, if it exists, and add the classes to our
 comments section instead.
*/
if(!defined('SPEC_COMMENT_BODY_CLASS') && class_exists('spec_commenting')) {
	$classes = spec_commenting::get_agent_body_class(array('with-collapse'));
	$section_class = ' class="' . implode(' ', array_map('sanitize_title', $classes)) . '"';
} else {
	$section_class = ' class="with-collapse"';
}

/*
 If we have no comments and comments are closed we drop out of here without
 doing anything at all, no point telling the user that something isn't available.
*/

if ((comments_open() || get_comments_number() > 0) && (is_single() || is_page()) && !post_password_required()) {?>
	<div id="comments"<?php echo $section_class;?>>
		<?php
		if (have_comments()) {	// New >= 27 comments.
			if (function_exists('wp_list_comments')) {

				if (commenting_by_type() && ($comments_by_type['pingback'] || $comments_by_type['trackback'])) {?>
					<strong class="commentTitle"><?php _e('Trackbacks', SPEC_COMMENT_DOM)?></strong>
					<ul id="trackbackList">
						<?php wp_list_comments(array('max_depth' => 0, type => 'pings'));?>
					</ul>
					<?php
				}?>

				<strong class="commentTitle"><?php _e('Comments', SPEC_COMMENT_DOM)?></strong>
				<ul id="commentlist">
					<?php wp_list_comments(array('type' => (commenting_by_type() ? 'comment' : 'all'), 'callback' => 'comment_layout'));?>
				</ul>
				<div id="commentPagination"><?php paginate_comments_links(array('next_text'=> '&raquo;', 'prev_text' => '&laquo;'));?></div>

				<?php
			} else { // Cover WP all the way back to 2.1 with this.?>
				<strong class="commentTitle"><?php _e('Comments', SPEC_COMMENT_DOM)?></strong>
				<ul id="commentlist"><?php
				foreach ($comments as $count => $comment) {
					$args = array('avatar_size' => 32, 'tag' => 'li');
					comment_layout($comment, $args, $depth);
				}?>
				</ul><?php
			}
		}

		if(comments_open()) {?>

			<div id="respond">
				<div class="commentTitle">
				<?php
				if (function_exists('comment_form_title')) {
					comment_form_title(__('Leave a Comment', SPEC_COMMENT_DOM), __('Leave a Reply to %s', SPEC_COMMENT_DOM), false);
				} else {
					_e('Leave a Comment', SPEC_COMMENT_DOM);
				}?>
				</div>
			<?php
			if (get_option('comment_registration') && !$user_ID ) {?>
				<a href="<?php echo get_option('siteurl')?>/wp-login.php?redirect_to=<?php echo urlencode(get_permalink())?>"><?php _e('You must be logged in to comment.', SPEC_COMMENT_DOM)?></a><?php
			} else {?>

				<form action="<?php echo get_option('siteurl')?>/wp-comments-post.php" method="post" id="commentForm">
				<fieldset><?php

				if ($user_ID) { ?>
					<?php _e('Logged in as', SPEC_COMMENT_DOM)?> <a href="<?php echo get_option('siteurl')?>/wp-admin/profile.php"><?php echo $user_identity?></a>.
					<a href="<?php echo wp_logout_url($_SERVER['REQUEST_URI']);?>" title="<?php _e('Log out of this account', SPEC_COMMENT_DOM) ?>"><?php _e('Log Out', SPEC_COMMENT_DOM)?></a>
					<?php
				} else { ?>
					<div>
						<input type="text" name="author" id="author" value="<?php echo $comment_author?>" size="30" tabindex="1"<?php echo ($req ? ' class="vital"' : '')?>/>
						<label for="author">
							<small><?php _e('Name', SPEC_COMMENT_DOM)?> <?php if ($req) _e('(required)', SPEC_COMMENT_DOM)?></small>
						</label>
					</div>
					<div>
						<input type="text" name="email" id="email" value="<?php echo $comment_author_email?>" size="30" tabindex="2"<?php echo ($req ? ' class="vital"' : '')?>/>
						<label for="email">
							<small><?php _e('Mail (will not be published)', SPEC_COMMENT_DOM)?> <?php if ($req) _e('(required)', SPEC_COMMENT_DOM)?></small>
						</label>
					</div>
					<div>
						<input type="text" name="url" id="url" value="<?php echo $comment_author_url?>" size="30" tabindex="3" />
						<label for="url">
							<small><?php _e('Website', SPEC_COMMENT_DOM)?> </small>
						</label>
					</div><?php
				}?>

				<textarea name="comment" id="comment" cols="56" rows="10" tabindex="4" class="vital"></textarea>

				<div class="commentSubmit">
					<?php if(function_exists('cancel_comment_reply_link')) cancel_comment_reply_link();?>
					<input name="submit" type="submit" tabindex="5" value="<?php _e('Post your comment', SPEC_COMMENT_DOM)?>" class="submit" />
				</div>

				<input type="hidden" name="comment_post_ID" value="<?php echo $id?>" /><?php
				if (function_exists('comment_id_fields')) {
					comment_id_fields();
				}
				do_action('comment_form', $post->ID)?>
				</fieldset>
				</form><?php
			}?>
			</div><?php
		}

	?>
	</div><?php
}?>
