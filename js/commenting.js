;(function($) {
	$( document ).ready( function() {
		/*
		 jQuery Replacement for the normal WP comment reply JS.
		 As I'm using jQuery anyway may as well include an easier to read/maintin
		 comment reply code. Also alows me to animate things should I want to.
		*/

		window.addComment = {

			interval:	0,
			/**
			 * The ID of the comment we're replying to.
			 * @type {Number}
			 */
			replying:	0,
			action_id:	0,
			showOne:	commentingL10n.rpl_show_1.replace( '%name%', '<span class="poster-name"></span>' ).replace( '%count%', '<span class="post-count">&nbsp;</span>' ),
			hideOne:	commentingL10n.rpl_hide_1.replace( '%name%', '<span class="poster-name"></span>' ).replace( '%count%', '<span class="post-count">&nbsp;</span>' ),
			showMany:	commentingL10n.rpl_show_2.replace( '%name%', '<span class="poster-name"></span>' ).replace( '%count%', '<span class="post-count">&nbsp;</span>' ),
			hideMany:	commentingL10n.rpl_hide_2.replace( '%name%', '<span class="poster-name"></span>' ).replace( '%count%', '<span class="post-count">&nbsp;</span>' ),

			/**
			 * We won't move the form to under the comment, it's messy and I don't like
			 * it. Instead we'll take some content from the comment we're replying to
			 * and show that next to the form. This is called moveForm to match WP's
			 * function.
			 */
			moveForm: function( belowID, commentID, formID, postID ) {
				var str = $( '#' + belowID + ' > .comment-body .comment-meta' ).next( ).text( ),
					to = $( '#' + belowID + ' > .comment-body cite.fn' ).text( );

				if ( addComment.replying )
					addComment.cancelReply( );

				addComment.replying = commentID;

				$( '#comment-form #comment' ).before( $( '<blockquote class="reply-quote"><cite>' + to + '</cite><p>' + ( str.length > 130 ? str.substring( 0, 129 ) + '&hellip;' : str ) + '</p></blockquote>' ).hide( ) ).prev( '.reply-quote' ).slideDown( );
				$( '#comment-form input#comment_parent' ).attr( { value: commentID } );
				$( '#' + belowID + ' > .comment-body ' ).find( '.comment-reply-link' ).hide( );
				$( '#comment-form #cancel-comment-reply-link' ).show( );

				if ( typeof $.scrollTo == 'function' ) {
					$.scrollTo( '#respond', { duration: 500, axis: 'y', onAfter: function( e ){
						$( '#comment' ).focus( );
					} } );
				}

				return false;
			},

			/**
			 * [cancelReply description]
			 * @param  {[type]}  [description]
			 * @return {[type]}  [description]
			 */
			cancelReply: function( ) {
				if ( addComment.replying !== 0 ) {
					addComment.replying = 0;
					$( '#comment-form .reply-quote' ).slideUp( 500, function( ) {
						$( this ).remove( );
					} );
					$( '#comment-form input#comment_parent' ).attr( { value: 0 } );
					$( '#comment-form #cancel-comment-reply-link' ).hide( );
					$( '#commentlist' ).find( '.comment-reply-link' ).show( );
				}
				// Make sure the submit button is still around.
				$( '#comment-form .submit' ).removeAttr( 'disabled' ).removeClass( 'disabled' );
				addComment.startInterval( true );
			},

			/**
			 * Take the depth class assigned to the comment and turn into an int.
			 * @param  {[type]} id [description]
			 * @return {[type]}    [description]
			 */
			commentDepth: function( id ) {
				var c;
				if ( id ) {
					c = $( 'li#comment-' + id ).attr( 'class' ).match( /\bdepth-(\d+)\b/i );
					return [ 1 ] !== undefined && parseInt( c[ 1 ], 10 ) > 0 ? parseInt( c[ 1 ], 10 ) : 1;
				} else {
					// No id passed then we have no comment so 0 is good.
					return 0;
				}
			},

			/**
			 * [error description]
			 * @param  {[type]} msg [description]
			 * @return {[type]}     [description]
			 */
			error: function( msg ) {
				if ( typeof msg !== 'string' )
					return false;

				$( '#comment-form .textarea-border' ).after( $( '<div class="error">' + msg + '</div>' ).hide( ) ).next( '.error' ).slideDown( 'slow', function( ) {

					var a = $( this );
					// Kill the filter for IE as it makes the text unreadable.
					if ( undefined  !== $( this )[ 0 ].style ) {
						$( this ).css( 'FILTER', '' );
						$( this )[ 0 ].style.filter = '';
					}

					setTimeout( function( ) {
						$( a ).slideUp( 'slow', function( ) {
							$( this ).remove( );
						} );
					}, 5000 );

				} );

				return true;
			},

			/**
			 * Rather than remove all the mark up for a deleted comment we'll just fold it up and empty it.
			 * @param  {[type]} comment_ID [description]
			 * @param  {[type]} action     [description]
			 * @return {[type]}            [description]
			 */
			deleteComment: function( comment_ID, action ) {
				var comment_exists = $( 'ul#commentlist li#comment-' + comment_ID + ', #trackback-list li#comment-' + comment_ID  ).length;

				if ( ! comment_exists )
					return true;

				if ( action == 'unapprove' && $( 'ul#commentlist li#comment-' + comment_ID + ' > .comment-body:has( .moderation )' ) )
					return true;

				$( 'li#comment-' + comment_ID + ' > .comment-body' ).slideUp( 'slow', function( ) {
					$( this ).text( 'Deleted comment' ).slideDown( ).parent( 'li#comment-' + comment_ID ).addClass( 'deleted' );
				} );

				return true;
			},

			/**
			 * [newComment description]
			 * @param  {[type]} html         [description]
			 * @param  {[type]} comment_ID   [description]
			 * @param  {[type]} parent_ID    [description]
			 * @param  {[type]} scroll_to    [description]
			 * @param  {[type]} comment_type [description]
			 * @return {[type]}              [description]
			 */
			newComment: function( html, comment_ID, parent_ID, scroll_to, comment_type ) {
				var comment_exists = $( 'ul#commentlist li#comment-' + comment_ID + ', #trackback-list li#comment-' + comment_ID ).length, depth_class;

				// If comment is already there then lets remove the needs approval message.
				if ( comment_exists ) {
					// Remove the approval messaqe
					$( 'ul#commentlist li#comment-' + comment_ID ).find( '.moderation' ).slideUp( 'slow', function( ) {
						$( this ).remove( );
					} );
					// Undelete a comment
					if ( $( 'li#comment-' + comment_ID ).hasClass( 'deleted' ) ) {
						$( 'li#comment-' + comment_ID + ' > .comment-body' ).slideUp( 'slow', function( ) {
							$( this ).html( $( html ).find( '.comment-body' ).html( ).trigger( 'newComment' ) ).slideDown( 'slow' );
						} ).removeClass( 'deleted' );

					}
					return true;
				}

				// We're replying so we have to do something different
				if ( comment_type == 'comment' && parent_ID > 0 ) {
					// If the comment we're replying to isn't here we just skip most of this
					if( $( 'ul#commentlist li#comment-' + parent_ID ).length ) {

						// Check there is a child UL to attach stuff to.
						if ( ! $( 'ul#commentlist li#comment-' + parent_ID + ' > ul.children' ).length )
							$( 'ul#commentlist li#comment-' + parent_ID + ' > div.comment-body' ).after( '<ul class="children"></ul>' );

						// Attach the comment.
						$( 'ul#commentlist li#comment-' + parent_ID + ' > ul.children' ).append( $( html ).hide( ).addClass( 'rolledup' ).trigger( 'newComment' ) );

						// Don't trust the depth on the html of replies
						depth_class = $( 'li#comment-' + parent_ID ).attr( 'class' ).match( /(?:\s|^)depth-(\d+)\s?/i );

						if ( depth_class[1] !== null && depth_class[1].match( /\d+/ ) ) {
							$( 'ul#commentlist li#comment-' + comment_ID ).removeClass( 'depth-1 depth-2 depth-3 depth-4 depth-5 depth-6 depth-7 depth-8 depth-9 depth-10').addClass( 'depth-' + ( parseInt( depth_class[1], 10 ) + 1 ) );

							// If the reply turns up to be too deep then we'll kill the reply button
							if ( ( parseInt( depth_class[1], 10 ) + 1 ) >= commentingL10n.max_depth )
								$( 'ul#commentlist li#comment-' + comment_ID + ' > div.comment-body .comment-reply-link' ).remove( );
						}

						// Check to see if our comment has been added to a rolled up UL
						if ( $( '#comment-' + comment_ID )
								.closest( 'li:has(div.toggle)' )
								.children( '.toggle' )
								.hasClass( 'hidden' ) ) {

							// If it has roll it down
							$( '#comment-' + comment_ID ).closest( 'li:has(div.toggle)' ).children( '.toggle' ).removeClass( 'hidden' ).next( 'ul.children' ).slideDown( 'fast', function( ) {
								$( this ).prev( 'div.toggle' ).css( { backgroundPosition: 'bottom right' } ); // FIXES IE8.
							} );
						}

						// Change the toggle text to the correct
						addComment.toggleToggleText( $( '#comment-' + comment_ID ).closest( 'li:has(div.toggle)' ).children( '.toggle' ) );

						addComment.addToggles( );
					}	// else the thing we're replying to isn't here. Not going to do anything with this for the moment

				} else {
					if ( comment_type !== 'comment' && $( '#trackback-list' ).length ) {
						$( '#trackback-list' ).append( $( html ).hide( ).addClass( 'rolledup' ).trigger( 'newComment' ) );
					} else {
						if ( commentingL10n.order === 'desc' )
							$( 'li#response-cont' ).after( $( html ).hide( ).addClass( 'rolledup' ).trigger( 'newComment' ) );
						else
							$( 'li#response-cont' ).before( $( html ).hide( ).addClass( 'rolledup' ).trigger( 'newComment' ) );
					}

					addComment.addToggles( );
				}

				if ( ! scroll_to ) {
					$( '#comment-' + comment_ID  ).addClass( 'new' ).children( '#div-comment-' + comment_ID ).find( '.comment-meta' ).prepend( '<span class="new-comment"></span>' );
					$( '#div-comment-' + comment_ID ).bind( 'mouseover', function( ) {
						$( this ).find( '.comment-meta span.new-comment').fadeTo( 1000, 0, function( ) {
							$( this ).closest( 'li' ).removeClass( 'new' );
							$( this ).remove( );
						} );
					} );
				}

				$( 'ul#commentlist, ul#trackback-list' ).find( '.rolledup' ).slideDown( 500, function( ){
					// Our comment is in place, now let us scroll to it once unrolled.
					if ( scroll_to )
						$.scrollTo( '#comment-' + comment_ID, { duration: 500, axis: 'y' } );
				} ).removeClass( 'rolledup' );

				if ( comment_type !== 'comment' )
					addComment.trackbackToggle( 400 );

				return true;
			},

			/**
			 * Send the comment to WP for processing
			 * @param  {[type]} v [description]
			 * @return {[type]}   [description]
			 */
			submit: function( v ) {
				var blankFields = false;

				$( v ).find( '.vital' ).each( function( ){
					var value = $( this ).attr( 'value' );
					if ( value === undefined || value ===  '' ) {
						blankFields = true;
						$( this ).addClass( 'oops' );
						setTimeout( function( ) {
							$( '#comment-form .vital' ).removeClass( 'oops' );
						}, 6000 );

					} else {
						$( this ).removeClass( 'oops' );
					}
				} );

				// Form not filled out then no point going on.
				if ( blankFields ) {
					addComment.error( commentingL10n.err_txt_mis );
					return false;
				}

				addComment.startInterval( false );

				$( v ).ajaxSubmit( {

					beforeSubmit: function( r ) {
						$( '#comment-form .submit' ).attr( { disabled: 'disabled' } ).addClass( 'disabled' );
					},

					type: 'POST',

					data: {
						// Add a trigger so we know we're doing this ajaxicaly.
						_spec_ajax: 'Why are you looking at this POST data?',
						action: 'new_comment_added',
						depth: addComment.commentDepth( addComment.replying )
					},
					//dataType: 'json', // Not 100% it will always be due to wp_die so need to parse it myself.

					error: function( r, e ) {
						var msg;

						if ( r.status === 500 ) { // Likely a wp wp_die message
							msg = $( r.responseText ).text( ).replace( /(\s+|\t|\r\n|\n|\r)+/g, ' ' );
							if ( msg === '' || msg === undefined ) {
								msg = r.status + ' ' + r.statusText;
							}
							addComment.error( msg );
						} else {
							if ( typeof e === 'string' ) {
								addComment.error( e );
							} else {
								addComment.error( 'Oops!' );
							}
						}

						addComment.cancelReply( );
					},

					success: function( r ) {
						var d;
						try {
							d = JSON.parse( r );
						} catch ( e ) {
							if ( typeof r === 'string' ) {
								addComment.error( r );
							} else {
								addComment.error( 'Oops!' );
							}
							addComment.cancelReply( );
							return;
						}

						//addComment.myComments[] = d.comment_ID;

						addComment.newComment( d.html, d.comment_ID, d.comment_parent, true, 'comment' );
						// Kill anything in the comment form and reset the reply button
						addComment.cancelReply( );
						$( '#comment-form #comment' ).val( '' ).trigger( 'keyup' );
					}
				} );

				return false;
			},

			/**
			 * Scan through looking for missing toggles and add them.
			 * @param {[type]} hidden [description]
			 */
			addToggles: function( hidden ) {
				$( '#commentlist li.depth-' + commentingL10n.nest_depth + ' > ul.children' ).each( function( ) {

					if( ! $( this ).prev( 'div.toggle' ).length ) {
						$( this ).before( $( '<div class="toggle"></div>' ).hide( ).css( { opacity: 0 } ) );
						$( this ).prev( 'div.toggle' ).show().fadeTo( 'slow', 1, function( ){
							// Kill the filter for IE as it makes the text unreadable.
							if ( undefined !== $( this )[ 0 ].style ) {
								$( this ).css( 'FILTER', '' );
								$( this )[ 0 ].style.filter = '';
							}
						} );
					}

					if ( hidden === true )
						$( this ).hide( ).prev( 'div.toggle' ).addClass( 'kqofn-hidden' );

					addComment.toggleToggleText( $( this ).prev( 'div.toggle' ) );
				} );
			},

			/**
			 * Change the text between the 4 possible states based on the position of the toggle passed
			 * @param  {[type]} obj [description]
			 * @return {[type]}     [description]
			 */
			toggleToggleText: function( obj ) {
				if ( typeof obj !== 'object' && typeof obj === 'string' )
					obj = $( obj );

				obj.each( function( ) {
					var poster_name = $( this ).prev( 'div.comment-body' ).find( 'cite.fn' ).text( ),
						reply_quant = $( this ).next( 'ul.children' ).find( 'li.comment' ).length;

					if ( $( this ).hasClass( 'kqofn-hidden' ) ) {
						$( this ).html( reply_quant == 1 ? addComment.showOne : addComment.showMany );
					} else {
						$( this ).html( reply_quant == 1 ? addComment.hideOne : addComment.hideMany );
					}

					$( this ).find( '.post-count' ).text( reply_quant );
					$( this ).find( '.poster-name' ).text( poster_name != '' ? poster_name : commentingL10n.unknown );
				} );

				return true;
			},

			/**
			 * Sets up the AJAX polling by calling the respective functions responsible at polling intervals
			 * @param  boolean on Is AJAX polling turned on
			 */
			startInterval: function( on ) {

				if ( on === true ) {
					if ( undefined !== commentingL10n.update && commentingL10n.update == 1 ) {
						addComment.interval = setInterval( function( ) {
							addComment.getUpdates( );
						}, parseInt( commentingL10n.polling, 10 ) >= 10 ? commentingL10n.polling * 1000 : 10000 );
					}
				} else {
					clearInterval( addComment.interval );
				}
			},

			getUpdates: function(){
				addComment.getCommentUpdates( );
			},

			/**
			 * Retrieves the comment updates via AJAX
			 * @param  {[type]}  [description]
			 * @return {[type]}  [description]
			 */
			getCommentUpdates: function( ) {
				var data = {
					_spec_ajax: 'Why is everyone looking at me', // As you can guess this can be anything at all just need to be there. :D
					action: 'get_comment_changes',
					action_id: addComment.action_id,
					time: commentingL10n.time,
					post_id: commentingL10n.post_id
				};

				$.post( commentingL10n.ajax_url, data, function( r ) {
					var d, i;
					try {
						d = JSON.parse( r );
					} catch ( e ) {
						if ( typeof r === 'string' ) {
							addComment.error( r );
						} else {
							addComment.error( 'Oops!' );
						}
						return;
					}

					if ( d !== null && d !== undefined ) {
						for ( i in d ) {
							comment = d[i];
							if ( comment !== undefined ) {
								var time = comment.log_date !== null ? comment.log_date : comment.comment_date;
								if ( time !== null ) {
									commentingL10n.time = time;
								}
								addComment.action_id = comment.action_id !== null ? comment.action_id : 0;

								if ( /*d[i].action === 'approve' &&*/ comment.html !== undefined && d[i].html !== null && comment.html !== '' ) {
									addComment.newComment( comment.html, comment.comment_ID, comment.comment_parent, false, comment.comment_type );
								} else {
									addComment.deleteComment( comment.comment_ID, comment.action );
								}
							}
						}
					}
				} );
			},

			/**
			 * Similar to getCommentUpdates but with comments requiring moderation, makes an AJAX request calling for new moderated comments
			 */
			getModerationUpdates: function( ) {
				var data = {
					_spec_ajax: 'Why is everyone looking at me', // As you can guess this can be anything at all just need to be there. :D
					action: 'get_moderation_changes',
					action_id: addComment.action_id,
					time: commentingL10n.time,
					post_id: commentingL10n.post_id
				};

				$.post( commentingL10n.ajax_url, data, function( r ) {
					var d, i, comment;
					try {
						d = JSON.parse( r );
					} catch ( e ) {
						if ( typeof r === 'string' ) {
							addComment.error( r );
						} else {
							addComment.error( 'Oops!' );
						}
						return;
					}

					if ( d !== null && d !== undefined ) {
						for ( i in d ) {
							comment = d[i];
							if ( comment !== undefined ) {
								var time = comment.log_date !== null ? comment.log_date : comment.comment_date;
								if ( time !== null ) {
									commentingL10n.time = time;
								}
								addComment.action_id = comment.action_id !== null ? comment.action_id : 0;

								if ( /*d[i].action === 'approve' &&*/ comment.html !== undefined && comment.html !== null && comment.html !== '' ) {
									addComment.newComment( comment.html, comment.comment_ID, comment.comment_parent, false, comment.comment_type );
								}
							}
						}
					}
				} );
			},

			trackbackToggle: function( max_height ) {

				if ( $( '#trackback-list' ).height( ) > ( max_height > 0 ? max_height : 400 ) && ! $( '#trackback-list' ).prev( '.trackback-toggle' ).length && $( '#trackback-list' ).length ) {
					$( '#trackback-list' )
						.hide( )
						.before( '<div class="trackback-toggle"><span class="toggle-text">' + commentingL10n.tb_show + '</span></div>' )
						.prev( '.trackback-toggle' )
						.click( function( ){
							$( this ).toggleClass( 'active' ).next( '#trackback-list' ).slideToggle( 'fast', function( ){
								$( this )
									.prev( '.trackback-toggle' )
									.children( '.toggle-text' )
									.text( $( this ).css( 'display' ) === 'none' ? commentingL10n.tb_show : commentingL10n.tb_hide );
							} );
						} );
				}

				// Hide trackbacks that show up in the comment stream.
				$( '#commentlist li.pingback > .comment-body .comment-content, #commentlist li.trackback > .comment-body .comment-content' ).each( function( ){
					// If this already has a toggle then jump ship.
					if ( $( this ).prev( '.trackback-toggle' ).length )
						return true;

					var from = commentingL10n.tb_from.replace( '%s', '<span class="tb-from">' + $( this ).find( 'cite.fn' ).text( ) + '</span>' );
					$( this )
						.hide( )
						.addClass( 'with-toggle' )
						.before( '<div class="trackback-toggle"></div>' )
						.prev( '.trackback-toggle' )
						.html( from )
						.click( function( ){
							$( this ).next( '.comment-content' ).slideToggle( 'fast' );
						} );

					return true;
				} );
			},

			approveComment: function(commentEl, comment_id){
				//commentingL10n.post_id
				var data = {
					_spec_ajax: 'Why is everyone looking at me', // As you can guess this can be anything at all just need to be there. :D
					action: 'approve_comment',
					action_id: addComment.action_id,
					time: commentingL10n.time,
					post_id: commentingL10n.post_id,
					comment_id: comment_id
				};

				$.post( commentingL10n.ajax_url, data, function( r ) {
					if(r == "done"){
						commentEl.text("Approval sent, verifying...");
						addComment.getCommentUpdates( );
					} else {
						addComment.error(r);
					}
				} );
			},
			spamComment: function(commentEl, comment_id){
				//commentingL10n.post_id
				var data = {
					_spec_ajax: 'Why is everyone looking at me', // As you can guess this can be anything at all just need to be there. :D
					action: 'spam_comment',
					action_id: addComment.action_id,
					time: commentingL10n.time,
					post_id: commentingL10n.post_id,
					comment_id: comment_id
				};

				$.post( commentingL10n.ajax_url, data, function( r ) {
					if(r == "done"){
						commentEl.text("Spam req sent, verifying...");
						addComment.getCommentUpdates( );
					} else {
						addComment.error(r);
					}
				} );
			},
			trashComment: function(commentEl, comment_id){
				//commentingL10n.post_id
				var data = {
					_spec_ajax: 'Why is everyone looking at me', // As you can guess this can be anything at all just need to be there. :D
					action: 'delete_comment',
					action_id: addComment.action_id,
					time: commentingL10n.time,
					post_id: commentingL10n.post_id,
					comment_id: comment_id
				};

				$.post( commentingL10n.ajax_url, data, function( r ) {
					if(r == "done"){
						commentEl.text("Deletion sent, verifying...");
						addComment.getCommentUpdates( );
					} else {
						addComment.error(r);
					}
				} );
			},

			_init: function( ) {
				var form = $( 'form#comment-form' ),
					list = $( '#commentlist' );

				$( document ).ready( function( $ ) {

					addComment.startInterval( true );

					$.ajaxSetup( {
						cache: false,
						timeout: ( commentingL10n.polling * 1000 ) - 2000 // Give myself 2 seconds before the next run to get everything in place.
					} );

					// Add the submit action
					form.submit( function( ) {
						addComment.submit( this );
						return false;
					} );

					// Make sure the cancel comment button does what it should
					form.on( 'click', '#cancel-comment-reply-link', function( ){
						addComment.cancelReply( );
						return false;
					} );

					list.on( 'click', '.spec_moderation_button_approve', function(){
						var comment_id = $(this).attr('data-comment');
						addComment.approveComment($(this),comment_id);
						return false;
					});

					list.on( 'click', '.spec_moderation_button_spam', function(){
						var comment_id = $(this).attr('data-comment');
						addComment.spamComment($(this),comment_id);
						return false;
					});

					list.on( 'click', '.spec_moderation_button_delete', function(){
						var comment_id = $(this).attr('data-comment');
						addComment.trashComment($(this),comment_id);
						return false;
					});

					// Add some toggles to hide comments
					addComment.addToggles( true );

					// Add some code to the toggles added above.
					list.on( 'click', 'div.toggle', function( ) {
						if ( $( this ).hasClass( 'kqofn-hidden' ) ) {
							$( this ).removeClass( 'kqofn-hidden' ).next( 'ul.children' ).slideDown( 'fast', function( ) {
								// For some reason, don't ask me why, this stops IE8 from messing around with the margins on slide up/down.
								$( this ).prev( 'div.toggle' ).css( { backgroundPosition: 'bottom right' } );
							} );
						} else {
							$( this ).addClass( 'kqofn-hidden' ).next( 'ul.children' ).slideUp( 'fast', function( ) {
								$( this ).prev( 'div.toggle' ).css( { backgroundPosition: 'top left' } );
							} );
						}

						addComment.toggleToggleText( $( this ) );
					} );

					// Hide trackbacks from view if they take up too much space.
					// Too much is 400px in my opinion but then I don't really like them. :P
					// Or make trackbacks toggleible in the comment stream
					addComment.trackbackToggle( 400 );

					// Change the link button to a pop up element that has the link in it.
					list.on( 'click', '.comment-link', function( ) {
						var val = $( this ).attr( 'href' ),
							text = $( this ).text( ),
							box = $( '<div class="comment-link-display"><span>' + text + '</span><input type="text" value="' + val + '" /></div>' );

						$( 'body' )
							.append( box )
							.find( '.comment-link-display' )
							.css( {
								position: 'absolute',
								top: ( $( this ).offset( ).top - 27 ) + 'px',
								left: ( $( this ).offset( ).left + 5 ) + 'px',
								zIndex: 100,
								opacity: 0
							} )
							.fadeTo( 'fast', 1, function( ) {

								$( this ).find( 'input' ).blur( function( ) {
									$( this ).parents( '.comment-link-display' ).fadeTo( 'fast', 0, function( ){
										$( this ).remove( );
									} );
								} );

								$( this ).find( 'input' ).focus();
							} );
						return false;

					} );

					list.find( '.comment-link-display' ).on( 'focus', 'input', function( ){
						this.select( );
					} );

		//			Add some tags to the body to target ie6 - 9
					$.each( $.browser, function( i, val ) {
						if( i == 'msie' && val === true ) {
							switch ( parseInt( $.browser.version.substr( 0, 1 ), 10 ) ) {
								case 6:
								case 7:

									// Fix issue with inherited fontFamily on #content, only a problem
									// for ie6 and 7.

									$target = $( '#comment' );
									while( $target.css( 'fontFamily' ) == 'inherit' ) {
										$target = $target.parent( );
									}
									$( '#comment' ).css( {
											fontFamily: $target.css( 'fontFamily' ),
											fontSize: $target.css( 'fontSize' ),
											width: $( '#comment' ).innerWidth( )
										}
									);
									break;
							}
						}
					});

					$( '#comment' ).autogrow( );

				} );

			}
		};

		addComment._init( );
	} );
}(jQuery));
