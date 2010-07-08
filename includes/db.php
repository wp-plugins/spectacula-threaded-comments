<?php

if ( ! class_exists( 'spec_comment_log' ) ) {
	define( 'SPEC_COMMENT_DBV', 1 );
	define( 'SPEC_COMMENT_LOG_TBL', 'spec_comment_log' );

	class spec_comment_log {

		var $actions = array( 'delete', 'approve', 'unknown' );

		/*
		 Constructor, creates the table if needed.
		*/
		function spec_comment_log( ) {
			$this->create_tables( );

			// Log status changes
			add_action( 'wp_set_comment_status', array( &$this, 'set_comment_status' ), 10, 2 );
			// Log new comment insertion
			add_action( 'wp_insert_comment', array( &$this, 'insert_comment' ), 10, 2 );
		}

		function insert_comment( $comment_id = 0, $comment = '' ) {
			if ( $comment_id == 0 || $comment == '' )
				return false;

			$this->add_row( $comment_id, $comment->comment_post_ID, $comment->comment_approved == 1 ? 'approve' : 'delete' );
		}


		function set_comment_status( $comment_id, $status ) {

			$comment = get_comment( $comment_id );

			if ( ! isset( $comment->comment_post_ID ) )
				return false;

			switch( $status ) {
				case 1:
				case 'approve':
					$this->add_row( $comment_id, $comment->comment_post_ID, 'approve' );
					break;
				case 0:
				case 'hold':
				case 'unapprove':
				case 'spam':
				case 'trash':
				case 'delete':
					$this->add_row( $comment_id, $comment->comment_post_ID, 'delete' );
					break;
				default:
					$this->add_row( $comment_id, $comment->comment_post_ID, 'unknown' );
					break;
			}
		}


		/*
		 Return the name of the table(s) with their correct prefix.
		*/
		function get_table_names( ) {
			global $wpdb;

			// Define the table names with the wp prefix.
			$comment_log = $wpdb->prefix . ( defined( 'SPEC_COMMENT_LOG_TBL' ) ? SPEC_COMMENT_LOG_TBL : 'spec_comment_log' );

			return $comment_log;
		}

		/*
		 A very simple table just to keep a log of comment actions. Deletes and
		 approvals don't have timestamps against them in the db and I'd like to
		 know which comments to remove from the screen, after all better to get
		 rid of spammers an morons as quickly as possible. :D Also doing it this
		 way I can keep the complexity of queries right down which should make
		 this about as light as I can. The table is memory only and will be
		 flushed of old rows every time a new row is created.
		*/
		function create_tables( ) {
			global $wpdb;
			$comment_log = $this->get_table_names( );
			$dbv = get_option( 'spec_comment_dbv' );

			// If the table changes in the future this'll get a little more work
			if ( version_compare( $dbv, SPEC_COMMENT_DBV, 'ge' ) )
				return true;

			// Make sure maybe_create_table is to hand.
			if ( ( ! function_exists( 'maybe_create_table' ) || ! function_exists( 'check_column' ) )&& file_exists( ABSPATH . '/wp-admin/install-helper.php' ) )
				require_once( ABSPATH . '/wp-admin/install-helper.php' );

			if( $wpdb->supports_collation( ) ) {
				if( !empty( $wpdb->charset ) ) {
					$collation = "DEFAULT CHARACTER SET $wpdb->charset";
				}

				if( !empty( $wpdb->collate ) ) {
					$collation .= " COLLATE $wpdb->collate";
				}
			}

			$table = "CREATE TABLE $comment_log  (
						id BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT, INDEX USING BTREE ( id ), PRIMARY KEY ( id ),
						date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX USING BTREE ( date ),
						post_id BIGINT( 20 ) UNSIGNED NOT NULL, INDEX USING BTREE ( post_id ),
						comment_id BIGINT( 20 ) UNSIGNED NOT NULL, INDEX USING BTREE ( comment_id ),
						action_taken varchar( 64 )
					  ) ENGINE = MEMORY $collation;";

			// Create the tables if needed.
			maybe_create_table( $comment_log, $table );

			if ( ! update_option( 'spec_comment_dbv', SPEC_COMMENT_DBV ) )
				add_option( 'spec_comment_dbv', SPEC_COMMENT_DBV );
		}


		/*
		 Adds a new row to the comment log with the time being NOW, at least it
		 should be now if the table was created right. :D

		 @param int $comment_id The id of the comment that is being logged.
		 @param int $post_id The id of the post the comment is attached to.
		 @param string $action The action taken on the comment.

		 @return bool True on success
		*/
		function add_row( $comment_id, $post_id, $action ) {
			global $wpdb;
			$table = $this->get_table_names( );

			$comment_id = intval( $comment_id );
			$post_id = intval( $post_id );

			if ( ! ( $post_id && $comment_id && in_array( $action, $this->actions ) ) )
				return false;

			$data = array( 'comment_id' => $comment_id, 'post_id' => $post_id, 'action_taken' => $action, 'date' => current_time( 'mysql', false ) );

			// Delete old rows before we add any new.
			$this->tidy_up( );

			$result = $wpdb->insert( $table, $data );
			$new_id = $wpdb->get_results( 'SELECT LAST_INSERT_ID( );', ARRAY_N );
			if ( is_array( $new_id ) )
				$new_id = intval( $new_id[ 0 ][ 0 ] );

			return $result ? true : false;
		}


		/*
		 Delete all rows that are more than half an hour old. If you have your
		 ajax set to refresh once every 30 mins then you may as well not bother.
		*/
		function tidy_up( ) {
			global $wpdb;
			$table = $this->get_table_names( );

			$query = $wpdb->prepare( "DELETE FROM $table WHERE date < %s - INTERVAL 30 MINUTE;", current_time( 'mysql', false ) );
			$result = $wpdb->query( $query );

			return !empty( $result ) ? $result : false;
		}


		/*
		 Find comments on post_id since date passed.
		*/
		function find( $post_id = 0, $since = '' ) {
			global $wpdb;
			$table = $this->get_table_names( );

			$post_id = intval( $post_id );
			if ( ! $post_id || ! strtotime( $since ) )
				return false;

			$query = $wpdb->prepare( "SELECT * FROM $table WHERE post_id = %d AND date > %s ORDER BY date DESC LIMIT 10;", $post_id, $since );
			$results = $wpdb->get_results( $query );

			return ! empty( $results ) ? $results : false;
		}


		/*
		 Simply take an array of comment ids and collect the comments associated
		 with said IDs.
		*/
		function collect_comments( $comment_ids = array( ) ) {
			global $wpdb;

			if ( isset( $comment_ids ) && ! empty( $comment_ids ) && is_array( $comment_ids ) ) {
				$comment_ids = array_filter( array_map( 'intval', $comment_ids ) );

				$in = ( implode( ',', $comment_ids ) );
				// We order by comment parent so as to get all root comments in first.
				$query = "SELECT * FROM $wpdb->comments WHERE comment_ID IN( $in ) AND comment_approved = '1' ORDER BY comment_parent ASC, comment_date_gmt DESC";
				$comments = $wpdb->get_results( $query );
			}

			return $comments;
		}
	}
}

if ( ! isset( $spec_comment_log ) || ! is_object( $spec_comment_log ) )
	$spec_comment_log = new spec_comment_log( );
?>
