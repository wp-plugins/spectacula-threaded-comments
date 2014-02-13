<?php

class spectacula_ajax {

	/*
	 Each method needs to be responsible for it's own death as we have some
	 things kicking off as filters later in the execution where a DIE in this
	 would stop them from running.
	*/
	function spectacula_ajax( ) {
		define( 'DOING_AJAX', true );
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );

		if ( isset( $_REQUEST[ '_spec_ajax' ] ) && isset( $_REQUEST[ 'action' ] ) ) {
			$action = isset( $_GET[ 'action' ] ) ? $_GET[ 'action' ] : $_POST[ 'action' ];
			if ( $action && $action != __FUNCTION__ && method_exists( $this, $action ) && is_callable( array( $this, $action ) ) ) {
				call_user_func( array( $this, $action ) );
			} else {
				die( '-1' );
			}
		}
	}


	/*
	 Simply adds a filter to the new comment redirect so that we can change the
	 returned content into something useful to JS. We should only get here if
	 the comment form was submitted by AjaxSubmit and has two extra bits of data
	 in it ( _spec_ajax & action this function name. )
	*/
	function new_comment_added( ){
		add_filter( 'comment_post_redirect', array( $this, 'redirect_new_comment' ), 10, 2 );
	}


	/*
	 Here we are, we've found out the comment was submitted using ajax so lets
	 not redirect the page as WP would like instead lets return stuff JS can
	 read. We've let WP do all the work with this comment submission lets just
	 do the minimum required. :D
	*/
	function redirect_new_comment( $location = '', $comment = '' ) {
		$GLOBALS[ 'comment_depth' ] = $depth = intval( $_REQUEST[ 'depth' ] ) + 1; // This is a bit of a cheat but it works.
		$post_id = intval( $_REQUEST[ 'comment_post_ID' ] );

		ob_start( );
			// Render the content using the same function as we would normally.
			$args = array( 'avatar_size' => 32, 'tag' => 'li', 'post_id' => $post_id );
			spec_comment_layout( $comment, $args, $depth );
			$html = ob_get_contents( );
		ob_end_clean( );

		$json = array();
		$json[ 'depth' ] = $depth;
		$json[ 'post' ] = $post_id;
		$json[ 'comment_ID' ] = $comment->comment_ID;
		$json[ 'comment_parent' ] = $comment->comment_parent;
		$json[ 'comment_post_ID' ] = $comment->comment_post_ID;
		$json[ 'comment_approved' ] = $comment->comment_approved;
		$json[ 'html' ] = $html;

		die( json_encode( $json ) );
	}

	function get_comment_changes() {
		global $spec_comment_log, $current_user;

		$user_roles = $current_user->roles;
		$role = array_shift( $user_roles );

		$action_id = 0;
		if ( isset( $_POST[ 'action_id' ] ) ) {
			$action_id = absint( $_POST[ 'action_id' ] );
		}

		$time = '';
		if ( isset( $_POST[ 'time' ] ) ) {
			$time = $_POST[ 'time' ];
		}
		$post_id = $_POST[ 'post_id' ];

		// Get the details from cache
		$cachekey = 'icit_spec_discus_' . $post_id . '_' . $action_id . '_' . $role;
		$encoded_json = wp_cache_get( $cachekey, 'spec_discussion' );
		if ( empty( $encoded_json ) ){
			$encoded_json = $this->grab_changedcomments_json( $spec_comment_log, $post_id, $time, $action_id );

			// The details weren't in cache lets add it now.
			wp_cache_set( $cachekey, $encoded_json, 'spec_discussion', 20 );
		}
		echo $encoded_json;
		die;
	}

	private function grab_changedcomments_json( spec_comment_log $spec_comment_log, $post_id, $time, $action_id ) {
		$comment_log = $spec_comment_log->find( $post_id, $time, $action_id );
		$json = array();
		$comment_ids = array();

		if ( isset( $comment_log ) && ! empty( $comment_log ) ) {
			foreach ( $comment_log as $comment ) {
				/*
				 Step through the log creating an array of ids and adding stuff
				 to the json output so we know what to do with the comment once
				 we get to the JS. Some of these things will be overridden by
				 the collect_comments data but this is mostly for the deletion
				 of comments.
				*/
				$comment_ids[] = intval( $comment->comment_id );
				$json[ $comment->comment_id ][ 'action' ] = $comment->action_taken;
				$json[ $comment->comment_id ][ 'action_id' ] = $comment->id;
				$json[ $comment->comment_id ][ 'post' ] = $comment->post_id;
				$json[ $comment->comment_id ][ 'comment_ID' ] = $comment->comment_id;
				$json[ $comment->comment_id ][ 'log_date' ] = $comment->date;
			}

			if ( !empty( $comment_ids ) ) {
				// note: collect_comments only grabs approved comments, so unapproved comments won't have all the extra data
				$comment_data = $spec_comment_log->collect_comments( $comment_ids );

				if ( !empty( $comment_data ) ) {
					foreach ( $comment_data as  $comment ) {
						// We don't know the depth, we'll work that out when we insert it then add the right depth then.
						$depth = $comment->comment_parent > 0 ? 2 : 1;
						$post_id = intval( $comment->comment_post_ID );

						ob_start( );
							// Render the content using the same function as we would normally.
							$args = array( 'avatar_size' => 32, 'tag' => 'li', 'post_id' => $post_id );
							spec_comment_layout( $comment, $args, $depth );
							$html = ob_get_contents( );
						ob_end_clean( );

						$json[ $comment->comment_ID ][ 'depth' ] = $depth;
						$json[ $comment->comment_ID ][ 'post' ] = $post_id;
						$json[ $comment->comment_ID ][ 'comment_date' ] = $comment->comment_date;
						$json[ $comment->comment_ID ][ 'comment_ID' ] = $comment->comment_ID;
						$json[ $comment->comment_ID ][ 'comment_parent' ] = $comment->comment_parent;
						$json[ $comment->comment_ID ][ 'comment_type' ] = empty( $comment->comment_type ) ? 'comment' : $comment->comment_type;
						$json[ $comment->comment_ID ][ 'comment_post_ID' ] = $comment->comment_post_ID;
						$json[ $comment->comment_ID ][ 'comment_approved' ] = $comment->comment_approved;
						$json[ $comment->comment_ID ][ 'html' ] = $html;
					}
				}
			}
		}
		$encoded_json = json_encode( $json );
		return $encoded_json;
	}

	private function can_do_comment_stuff(){
		if ( !current_user_can( 'moderate_comments' ) ) {
			echo "You're not allowed to do that.";
			return false;
		}
		if ( !isset( $_REQUEST['comment_id'] ) ) {
			echo 'No comment ID was specified';
			die;
		}
		if ( !is_numeric( $_REQUEST['comment_id'] ) ) {
			echo 'comment_id must be numeric';
			die;
		}
		return true;
	}

	function approve_comment(){
		if ( !$this->can_do_comment_stuff() ) {
			die;
		}
		$comment_id = intval( $_REQUEST['comment_id'] );
		spec_comment_approve_comment( $comment_id );
		echo 'done';
		die;
	}

	function spam_comment(){
		if ( !$this->can_do_comment_stuff() ) {
			die;
		}
		$comment_id = intval( $_REQUEST['comment_id'] );
		spec_comment_spam_comment( $comment_id );
		echo 'done';
		die;
	}

	function delete_comment(){
		if ( !$this->can_do_comment_stuff() ) {
			die;
		}
		$comment_id = intval( $_REQUEST['comment_id'] );
		spec_comment_delete_comment( $comment_id );
		echo 'done';
		die;
	}
}
