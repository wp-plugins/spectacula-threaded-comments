
/*
 jQuery Replacement for the normal WP comment reply JS.
 As I'm using jQuery anyway may as well include an easier to read/maintin
 comment reply code. Also alows me to animate things should I want to.
*/

addComment = {

	interval:	0,
	replying: 	0,	// The ID of the comment we're replying to.
	action_id:	0,
	showOne: 	commentingL10n.rpl_show_1.replace( '%name%', '<span class="poster-name"></span>' ).replace( '%count%', '<span class="post-count">&nbsp;</span>' ),
	hideOne: 	commentingL10n.rpl_hide_1.replace( '%name%', '<span class="poster-name"></span>' ).replace( '%count%', '<span class="post-count">&nbsp;</span>' ),
	showMany:	commentingL10n.rpl_show_2.replace( '%name%', '<span class="poster-name"></span>' ).replace( '%count%', '<span class="post-count">&nbsp;</span>' ),
	hideMany:	commentingL10n.rpl_hide_2.replace( '%name%', '<span class="poster-name"></span>' ).replace( '%count%', '<span class="post-count">&nbsp;</span>' ),

	// We won't move the form to under the comment, it's messy and I don't like
	// it. Instead we'll take some content from the comment we're replying to
	// and show that next to the form. This is called moveForm to match WP's
	// function.
	moveForm: function( belowID, commentID, formID, postID ) {
		var str = jQuery( '#' + belowID + ' > .comment-body .comment-meta' ).next( ).text( ),
			to = jQuery( '#' + belowID + ' > .comment-body cite.fn' ).text( );

		if ( addComment.replying )
			addComment.cancelReply( );

		addComment.replying = commentID;

		jQuery( '#comment-form #comment' ).before( jQuery( '<blockquote class="reply-quote"><cite>' + to + '</cite><p>' + ( str.length > 130 ? str.substring( 0, 129 ) + '&hellip;' : str ) + '</p></blockquote>' ).hide( ) ).prev( '.reply-quote' ).slideDown( );
		jQuery( '#comment-form input#comment_parent' ).attr( { value: commentID } );
		jQuery( '#' + belowID + ' > .comment-body ' ).find( '.comment-reply-link' ).hide( );
		jQuery( '#comment-form #cancel-comment-reply-link' ).show( );

		if ( typeof jQuery.scrollTo == 'function' ) {
			jQuery.scrollTo( '#respond', { duration: 500, axis: 'y', onAfter: function( e ){
				jQuery( '#comment' ).focus( );
			} } );
		}

		return false;
	},

	cancelReply: function( ) {
		if ( addComment.replying !== 0 ) {
			addComment.replying = 0;
			jQuery( '#comment-form .reply-quote' ).slideUp( 500, function( ) {
				jQuery( this ).remove( );
			} );
			jQuery( '#comment-form input#comment_parent' ).attr( { value: 0 } );
			jQuery( '#comment-form #cancel-comment-reply-link' ).hide( );
			jQuery( '#commentlist' ).find( '.comment-reply-link' ).show( );
		}
		// Make sure the submit button is still around.
		jQuery( '#comment-form .submit' ).removeAttr( 'disabled' ).removeClass( 'disabled' );
		addComment.startInterval( true );
	},

	// Take the depth class assigned to the comment and turn into an int.
	commentDepth: function( id ) {
		var c;
		if ( id ) {
			c = jQuery( 'li#comment-' + id ).attr( 'class' ).match( /\bdepth-(\d+)\b/i );
			return [ 1 ] !== undefined && parseInt( c[ 1 ], 10 ) > 0 ? parseInt( c[ 1 ], 10 ) : 1;
		} else {
			// No id passed then we have no comment so 0 is good.
			return 0;
		}
	},

	error: function( msg ) {
		if ( typeof msg !== 'string' )
			return false;

		jQuery( '#comment-form .textarea-border' ).after( jQuery( '<div class="error">' + msg + '</div>' ).hide( ) ).next( '.error' ).slideDown( 'slow', function( ) {
			var a = jQuery( this );
			// Kill the filter for IE as it makes the text unreadable.
			if ( undefined  !== jQuery( this )[ 0 ].style ) {
				jQuery( this ).css( 'FILTER', '' );
				jQuery( this )[ 0 ].style.filter = '';
			}

			setTimeout( function( ) {
				jQuery( a ).slideUp( 'slow', function( ) {
					jQuery( this ).remove( );
				} );
			}, 5000 );

		} );

		return true;
	},

	// Rather than remove all the mark up for a deleted comment we'll just fold it up and empty it.
	deleteComment: function( comment_ID, action ) {
		var comment_exists = jQuery( 'ul#commentlist li#comment-' + comment_ID + ', #trackback-list li#comment-' + comment_ID  ).length;

		if ( ! comment_exists )
			return true;

		if ( action == 'unapprove' && jQuery( 'ul#commentlist li#comment-' + comment_ID + ' > .comment-body:has( .moderation )' ) )
			return true;

		jQuery( 'li#comment-' + comment_ID + ' > .comment-body' ).slideUp( 'slow', function( ) {
			jQuery( this ).text( 'Deleted comment' ).slideDown( ).parent( 'li#comment-' + comment_ID ).addClass( 'deleted' );
		} );

		return true;
	},

	newComment: function( html, comment_ID, parent_ID, scroll_to, comment_type ) {
		var comment_exists = jQuery( 'ul#commentlist li#comment-' + comment_ID + ', #trackback-list li#comment-' + comment_ID ).length, depth_class;

		// If comment is already there then lets remove the needs approval message.
		if ( comment_exists ) {
			// Remove the approval messaqe
			jQuery( 'ul#commentlist li#comment-' + comment_ID ).find( '.moderation' ).slideUp( 'slow', function( ) {
				jQuery( this ).remove( );
			} );
			// Undelete a comment
			if ( jQuery( 'li#comment-' + comment_ID ).hasClass( 'deleted' ) ) {
				jQuery( 'li#comment-' + comment_ID + ' > .comment-body' ).slideUp( 'slow', function( ) {
					jQuery( this ).html( jQuery( html ).find( '.comment-body' ).html( ).trigger( 'newComment' ) ).slideDown( 'slow' );
				} ).removeClass( 'deleted' );

			}
			return true;
		}

		// We're replying so we have to do something different
		if ( comment_type == 'comment' && parent_ID > 0 ) {
			// If the comment we're replying to isn't here we just skip most of this
			if( jQuery( 'ul#commentlist li#comment-' + parent_ID ).length ) {

				// Check there is a child UL to attach stuff to.
				if ( ! jQuery( 'ul#commentlist li#comment-' + parent_ID + ' > ul.children' ).length )
					jQuery( 'ul#commentlist li#comment-' + parent_ID + ' > div.comment-body' ).after( '<ul class="children"></ul>' );

				// Attach the comment.
				jQuery( 'ul#commentlist li#comment-' + parent_ID + ' > ul.children' ).append( jQuery( html ).hide( ).addClass( 'rolledup' ).trigger( 'newComment' ) );

				// Don't trust the depth on the html of replies
				depth_class = jQuery( 'li#comment-' + parent_ID ).attr( 'class' ).match( /(?:\s|^)depth-(\d+)\s?/i );

				if ( depth_class[1] !== null && depth_class[1].match( /\d+/ ) ) {
					jQuery( 'ul#commentlist li#comment-' + comment_ID ).removeClass( 'depth-1 depth-2 depth-3 depth-4 depth-5 depth-6 depth-7 depth-8 depth-9 depth-10').addClass( 'depth-' + ( parseInt( depth_class[1], 10 ) + 1 ) );

					// If the reply turns up to be too deep then we'll kill the reply button
					if ( ( parseInt( depth_class[1], 10 ) + 1 ) >= commentingL10n.max_depth )
						jQuery( 'ul#commentlist li#comment-' + comment_ID + ' > div.comment-body .comment-reply-link' ).remove( );
				}

				// Check to see if our comment has been added to a rolled up UL
				if ( jQuery( '#comment-' + comment_ID ).closest( 'li:has(div.toggle)' ).children( '.toggle' ).hasClass( 'hidden' ) ) {
					// If it has roll it down
					jQuery( '#comment-' + comment_ID ).closest( 'li:has(div.toggle)' ).children( '.toggle' ).removeClass( 'hidden' ).next( 'ul.children' ).slideDown( 'fast', function( ) {
						jQuery( this ).prev( 'div.toggle' ).css( { backgroundPosition: 'bottom right' } ); // FIXES IE8.
					} );
				}

				// Change the toggle text to the correct
				addComment.toggleToggleText( jQuery( '#comment-' + comment_ID ).closest( 'li:has(div.toggle)' ).children( '.toggle' ) );

				addComment.addToggles( );
			}	// else the thing we're replying to isn't here. Not going to do anything with this for the moment

		} else {
			if ( comment_type !== 'comment' && jQuery( '#trackback-list' ).length ) {
				jQuery( '#trackback-list' ).append( jQuery( html ).hide( ).addClass( 'rolledup' ).trigger( 'newComment' ) );
			} else {
				if ( commentingL10n.order === 'desc' )
					jQuery( 'li#response-cont' ).after( jQuery( html ).hide( ).addClass( 'rolledup' ).trigger( 'newComment' ) );
				else
					jQuery( 'li#response-cont' ).before( jQuery( html ).hide( ).addClass( 'rolledup' ).trigger( 'newComment' ) );
			}

			addComment.addToggles( );
		}

		if ( ! scroll_to ) {
			jQuery( '#comment-' + comment_ID  ).addClass( 'new' ).children( '#div-comment-' + comment_ID ).find( '.comment-meta' ).prepend( '<span class="new-comment"></span>' );
			jQuery( '#div-comment-' + comment_ID ).bind( 'mouseover', function( ) {
				jQuery( this ).find( '.comment-meta span.new-comment').fadeTo( 1000, 0, function( ) {
					jQuery( this ).closest( 'li' ).removeClass( 'new' );
					jQuery( this ).remove( );
				} );
			} );
		}

		jQuery( 'ul#commentlist, ul#trackback-list' ).find( '.rolledup' ).slideDown( 500, function( ){
			// Our comment is in place, now let us scroll to it once unrolled.
			if ( scroll_to )
				jQuery.scrollTo( '#comment-' + comment_ID, { duration: 500, axis: 'y' } );
		} ).removeClass( 'rolledup' );

		if ( comment_type !== 'comment' )
			addComment.trackbackToggle( 400 )

		return true;
	},

	// Send the comment to WP for processing
	submit: function( v ) {
		var blankFields = false;

		jQuery( v ).find( '.vital' ).each( function( ){
			var value = jQuery( this ).attr( 'value' );
			if ( value === undefined || value ===  '' ) {
				blankFields = true;
				jQuery( this ).addClass( 'oops' );
				setTimeout( function( ) {
					jQuery( '#comment-form .vital' ).removeClass( 'oops' );
				}, 6000 );

			} else {
				jQuery( this ).removeClass( 'oops' );
			}
		} );

		// Form not filled out then no point going on.
		if ( blankFields ) {
			addComment.error( commentingL10n.err_txt_mis );
			return false;
		}

		addComment.startInterval( false );

		jQuery( v ).ajaxSubmit( {

			beforeSubmit: function( r ) {
				jQuery( '#comment-form .submit' ).attr( { disabled: 'disabled' } ).addClass( 'disabled' );
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
					msg = jQuery( r.responseText ).text( ).replace( /(\s+|\t|\r\n|\n|\r)+/g, ' ' );
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
				jQuery( '#comment-form #comment' ).val( '' ).trigger( 'keyup' );
			}
		} );

		return false;
	},

	// Scan through looking for missing toggles and add them.
	addToggles: function( hidden ) {
		jQuery( '#commentlist li.depth-' + commentingL10n.nest_depth + ' > ul.children' ).each( function( ) {

			if( ! jQuery( this ).prev( 'div.toggle' ).length ) {
				jQuery( this ).before( jQuery( '<div class="toggle"></div>' ).hide( ).css( { opacity: 0 } ) );
				jQuery( this ).prev( 'div.toggle' ).show().fadeTo( 'slow', 1, function( ){
					// Kill the filter for IE as it makes the text unreadable.
					if ( undefined !== jQuery( this )[ 0 ].style ) {
						jQuery( this ).css( 'FILTER', '' );
						jQuery( this )[ 0 ].style.filter = '';
					}
				} );
			}

			if ( hidden === true )
				jQuery( this ).hide( ).prev( 'div.toggle' ).addClass( 'kqofn-hidden' );

			addComment.toggleToggleText( jQuery( this ).prev( 'div.toggle' ) );
		} );
	},

	// Change the text between the 4 possible states based on the position of the toggle passed
	toggleToggleText: function( obj ) {
		if ( typeof obj !== 'object' && typeof obj === 'string' )
			obj = jQuery( obj );

		obj.each( function( ) {
			var poster_name = jQuery( this ).prev( 'div.comment-body' ).find( 'cite.fn' ).text( ),
				reply_quant = jQuery( this ).next( 'ul.children' ).find( 'li.comment' ).length;

			if ( jQuery( this ).hasClass( 'kqofn-hidden' ) ) {
				jQuery( this ).html( reply_quant == 1 ? addComment.showOne : addComment.showMany );
			} else {
				jQuery( this ).html( reply_quant == 1 ? addComment.hideOne : addComment.hideMany );
			}

			jQuery( this ).find( '.post-count' ).text( reply_quant );
			jQuery( this ).find( '.poster-name' ).text( poster_name != '' ? poster_name : commentingL10n.unknown );
		} );

		return true;
	},

	startInterval: function( on ) {

		if ( on === true ) {
			if ( undefined !== commentingL10n.update && commentingL10n.update == 1 ) {
				addComment.interval = setInterval( function( ) {
					addComment.getCommentUpdates( );
				}, parseInt( commentingL10n.polling, 10 ) >= 10 ? commentingL10n.polling * 1000 : 10000 );
			}
		} else {
			clearInterval( addComment.interval );
		}
	},

	getCommentUpdates: function( ) {
		var data = {
			_spec_ajax: 'Why is everyone looking at me', // As you can guess this can be anything at all just need to be there. :D
			action: 'get_comment_changes',
			action_id: addComment.action_id,
			time: commentingL10n.time,
			post_id: commentingL10n.post_id
		}

		jQuery.post( commentingL10n.ajax_url, data, function( r ) {
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
					commentingL10n.time = d[i].log_date !== null ? d[i].log_date : d[i].comment_date;
					addComment.action_id = d[i].action_id !== null ? d[i].action_id : 0;

					if ( d[i].action === 'approve' && d[i].html !== undefined && d[i].html !== null && d[i].html !== '' ) {
						addComment.newComment( d[i].html, d[i].comment_ID, d[i].comment_parent, false, d[i].comment_type );
					} else {
						addComment.deleteComment( d[i].comment_ID, d[i].action );
					}
				}
			}
		} );
	},

	trackbackToggle: function( max_height ) {

		if ( jQuery( '#trackback-list' ).height( ) > ( max_height > 0 ? max_height : 400 ) && ! jQuery( '#trackback-list' ).prev( '.trackback-toggle' ).length && jQuery( '#trackback-list' ).length ) {
			jQuery( '#trackback-list' )
				.hide( )
				.before( '<div class="trackback-toggle"><span class="toggle-text">' + commentingL10n.tb_show + '</span></div>' )
				.prev( '.trackback-toggle' )
				.click( function( ){
					jQuery( this ).toggleClass( 'active' ).next( '#trackback-list' ).slideToggle( 'fast', function( ){
						jQuery( this )
							.prev( '.trackback-toggle' )
							.children( '.toggle-text' )
							.text( jQuery( this ).css( 'display' ) === 'none' ? commentingL10n.tb_show : commentingL10n.tb_hide );
					} );
				} );
		}

		// Hide trackbacks that show up in the comment stream.
		jQuery( '#commentlist li.pingback > .comment-body .comment-content, #commentlist li.trackback > .comment-body .comment-content' ).each( function( ){
			// If this already has a toggle then jump ship.
			if ( jQuery( this ).prev( '.trackback-toggle' ).length )
				return true;

			var from = commentingL10n.tb_from.replace( '%s', '<span class="tb-from">' + jQuery( this ).find( 'cite.fn' ).text( ) + '</span>' );
			jQuery( this )
				.hide( )
				.addClass( 'with-toggle' )
				.before( '<div class="trackback-toggle"></div>' )
				.prev( '.trackback-toggle' )
				.html( from )
				.click( function( ){
					jQuery( this ).next( '.comment-content' ).slideToggle( 'fast' );
				} );

			return true;
		} );
	},

	_init: function( ) {

		jQuery( document ).ready( function( $ ) {

			addComment.startInterval( true );

			$.ajaxSetup( {
				cache: false,
				timeout: ( commentingL10n.polling * 1000 ) - 2000 // Give myself 2 seconds before the next run to get everything in place.
			} );

			// Add the submit action
			$( 'form#comment-form' ).submit( function( ) {
				addComment.submit( this );
				return false;
			} );

			// Make sure the cancel comment button does what it should
			$( '#cancel-comment-reply-link' ).click( function( ){
				addComment.cancelReply( );
				return false;
			} );

			// Add some toggles to hide comments
			addComment.addToggles( true );

			// Add some code to the toggles added above.
			$( '#commentlist div.toggle' ).live( 'click', function( ) {
				if ( $( this ).hasClass( 'kqofn-hidden' ) ) {
					$( this ).removeClass( 'kqofn-hidden' ).next( 'ul.children' ).slideDown( 'fast', function( ) {
						// For some reason, don't ask me why, this stops IE8 from messing around with the margins on slide up/down.
						$( this ).prev( 'div.toggle' ).css( { backgroundPosition: 'bottom right' } );
					} );
				} else {
					$( this ).addClass( 'kqofn-hidden' ).next( 'ul.children' ).slideUp( 'fast', function( ) {
						$( this ).prev( 'div.toggle' ).css( { backgroundPosition: 'top left' } )
					} );
				}

				addComment.toggleToggleText( jQuery( this ) );
			} );

			// Hide trackbacks from view if they take up too much space.
			// Too much is 400px in my opinion but then I don't really like them. :P
			// Or make trackbacks toggleible in the comment stream
			addComment.trackbackToggle( 400 );

			// Change the link button to a pop up element that has the link in it.
			$( '#commentlist .comment-link' ).live( 'click', function( ) {
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

			$( '.comment-link-display input' ).live( 'focus', function( ){
				this.select( );
			} );

//			 Add some tags to the body to target ie6 - 9
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
