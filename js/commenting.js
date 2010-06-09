
/*
 jQuery Replacement for the normal WP comment reply JS.
 As I'm using jQuery anyway may as well include an easier to read/maintin
 comment reply code. Also alows me to animate things should I want to.
*/

addComment = {

	replying: 	0,
	showOne: 	commentingL10n.replyShowOne.replace(  '%name%', '<span class="poster-name"></span>' ).replace( '%count%', '<span class="post-count">&nbsp;</span>' ),
	hideOne: 	commentingL10n.replyHideOne.replace(  '%name%', '<span class="poster-name"></span>' ).replace( '%count%', '<span class="post-count">&nbsp;</span>' ),
	showMany:	commentingL10n.replyShowMany.replace( '%name%', '<span class="poster-name"></span>' ).replace( '%count%', '<span class="post-count">&nbsp;</span>' ),
	hideMany:	commentingL10n.replyHideMany.replace( '%name%', '<span class="poster-name"></span>' ).replace( '%count%', '<span class="post-count">&nbsp;</span>' ),

	// We won't move the form to under the comment, it's messy and I don't like
	// it. Instead we'll take some content from the comment we're replying to
	// And show that next to the form.
	moveForm: function( belowID, commentID, formID, postID ) {
		var str = jQuery( '#' + belowID + ' > .comment-body .comment-meta' ).next( ).text( ),
			to = jQuery( '#' + belowID + ' > .comment-body cite.fn' ).text( );

		if ( addComment.replying )
			addComment.cancelReply( );

		addComment.replying = commentID;

		jQuery( '#comment-form #comment' ).before( '<blockquote class="reply-quote"><cite>' + to + '</cite><p>' + ( str.length > 130 ? str.substring( 0, 129 ) + '&hellip;' : str ) + '</p></blockquote>' );
		jQuery( '#comment-form input#comment_parent' ).attr( { value: commentID } );
		jQuery( '#' + belowID + ' > .comment-body ' ).find( '.comment-reply-link' ).hide( );
		jQuery( '#comment-form #cancel-comment-reply-link' ).show( );

		return true;
	},

	cancelReply: function( ) {
		if ( addComment.replying !== 0 ) {
			addComment.replying = 0;
			jQuery( '#comment-form .reply-quote' ).remove( );
			jQuery( '#comment-form input#comment_parent' ).attr( { value: 0 } );
			jQuery( '#comment-form #cancel-comment-reply-link' ).hide( );
			jQuery( '#commentlist' ).find( '.comment-reply-link' ).show( );
		}
		// Make sure the submit button is still around.
		jQuery( '#comment-form .submit' ).attr( { disabled: '' } ).removeClass( 'disabled' );
	},

	clearReplyLink: function( ) {
		// Change the reply anchor to point to just #respond.
		jQuery( '.comment-reply-link' ).attr( { href: '#respond' } );
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

		jQuery( '#comment-form .textarea-border' ).after( jQuery( '<div class="error">' + msg + '</div>' ).hide( ) ).next( '.error' ).fadeTo( 'slow', 1, function( ) {
			var a = jQuery( this );
			// Kill the filter for IE as it makes the text unreadable.
			jQuery( this ).css( 'FILTER', '' );
			jQuery( this )[ 0 ].style.filter = '';

			setTimeout( function( ) {
				jQuery( a ).slideUp( 'slow', function( ) {
					jQuery( this ).remove( );
				} );
			}, 5000 );

		} );

		return true;
	},

	// Send the comment to WP for processing
	submit: function( v ) {
		var blankFields = false;

		jQuery( v ).find( '.vital' ).each( function( ){
			var value = jQuery( this ).attr( 'value' );
			if ( value === undefined || value ===  '' ) {
				blankFields = true;
				jQuery( this ).css( { borderColor: '#f00' } ).fadeOut( 250 ).fadeIn( 250 );
				setTimeout( function( ) {
					jQuery( '#comment-form .vital' ).css( { borderColor: '#ccc' } );
				}, 10000 );

			} else {
				jQuery( this ).css( { borderColor: '#ccc' } );
			}
		} );

		// Form not filled out then no point going on.
		if ( blankFields ) {
			addComment.error( 'Missing some fields' ); // Add to translation
			return false;
		}

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
			//dataType: 'json', // Not 100% it will always be so need to parse it myself.

			error: function( r, e ) {
				var msg;

				if ( r.status === 500 ) { // Likely a wp wp_die message
					msg = jQuery( r.responseText ).text( ).replace( /(\s+|\t|\r\n|\n|\r)+/g, ' ' );
					if ( msg === '' || msg === undefined ) {
						msg = r.status + ' ' + r.statusText;
					}
					addComment.error( msg );
				} else {
					addComment.error( e );
				}

				addComment.cancelReply( );
			},

			success: function( r ) {
				var d;
				try {
					d = jQuery.parseJSON( r );
				} catch ( e ) {
					addComment.error( e );
					addComment.cancelReply( );
					return;
				}

				if ( addComment.replying ) {
					if ( ! jQuery( 'ul#commentlist li#comment-' + addComment.replying + ' > ul.children' ).length )
						jQuery( 'ul#commentlist li#comment-' + addComment.replying + ' > div.comment-body' ).after( '<ul class="children"></ul>' );

					jQuery( 'ul#commentlist li#comment-' + addComment.replying + ' > ul.children' ).append( jQuery( d.html ).hide( ).addClass( 'rolledup' ) );
				} else {
					if ( commentingL10n.order === 'desc'  )
						jQuery( 'li#respond' ).after( jQuery( d.html ).hide( ).addClass( 'rolledup' ) );
					else
						jQuery( 'li#respond' ).before( jQuery( d.html ).hide( ).addClass( 'rolledup' ) );
				}

				//if ( typeof console == 'object' ) {
				//	console.log( d );
				//}

				addComment.cancelReply( );
				addComment.clearReplyLink( );
				addComment.addToggles( );

				jQuery( '#comment-form #comment' ).val( '' ); // Blank the comment field
				jQuery( 'ul#commentlist' ).find( '.rolledup' ).slideDown( ).removeClass( 'rolledup' );
			}
		} );

		return false;
	},

	// Scan through looking for missing toggles and add them.
	addToggles: function( hidden ){
		jQuery( '#commentlist li.depth-' + commentingL10n.nestDepth + ' > ul.children' ).each( function( ) {

			if( ! jQuery( this ).prev( 'div.toggle' ).length ) {
				jQuery( this ).before( jQuery( '<div class="toggle"></div>' ).hide( ) );
				jQuery( this ).prev( 'div.toggle' ).fadeTo( 'slow', 1, function( ){
					// Kill the filter for IE as it makes the text unreadable.
					jQuery( this ).css( 'FILTER', '' );
					jQuery( this )[ 0 ].style.filter = '';
				} );
			}

			if ( hidden === true )
				jQuery( this ).hide( ).prev( 'div.toggle' ).addClass( 'hidden' );

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

			if ( jQuery( this ).hasClass( 'hidden' ) ) {
				jQuery( this ).html( reply_quant == 1 ? addComment.showOne : addComment.showMany );
			} else {
				jQuery( this ).html( reply_quant == 1 ? addComment.hideOne : addComment.hideMany );
			}

			jQuery( this ).find( '.post-count' ).text( reply_quant );
			jQuery( this ).find( '.poster-name' ).text( poster_name );
		} );

		return true;
	},

	_init: function( ) {
		jQuery( document ).ready( function( $ ) {

			// Add the submit action
			$( '#comment-form' ).live( 'submit', function( ) {
				addComment.submit( this );
				return false;
			} );

			$( '#cancel-comment-reply-link' ).live( 'click', function( ){
				addComment.cancelReply( );
				return false;
			} );

			addComment.addToggles( true );

			addComment.clearReplyLink( );

			$( '#commentlist div.toggle' ).live( 'click', function( ) {
				if ( $( this ).hasClass( 'hidden' ) ) {
					$( this ).removeClass( 'hidden' ).next( 'ul.children' ).slideDown( 'fast', function( ) {
						// For some reason, don't ask me why, this stops IE8 from messing around with the margins on slide up/down.
						$( this ).prev( 'div.toggle' ).css( { backgroundPosition: 'bottom right' } );
					} );
				} else {
					$( this ).addClass( 'hidden' ).next( 'ul.children' ).slideUp( 'fast', function( ) {
						$( this ).prev( 'div.toggle' ).css( { backgroundPosition: 'top left' } )
					} );
				}

				addComment.toggleToggleText( jQuery( this ) );
			} );

			// Hide trackbacks from view if they take up too much space. Too much is 400px in my opinion but then I don't really like them. :P
			if ( $( '#trackback-list' ).height( ) > 400 ) {
				$( '#trackback-list' )
					//.css( { height: $( '#trackback-list' ).height( ) } )
					.hide( )
					.before( '<div class="trackback-toggle"><span class="toggle-text">' + commentingL10n.trackbackShowText + '</span></div>' )
					.prev( '.trackback-toggle' )
					.click( function( ){
						$( this ).toggleClass( 'active' ).next( '#trackback-list' ).slideToggle( 'fast', function( ){
							$( this )
								.prev( '.trackback-toggle' )
								.children( '.toggle-text' )
								.text( $( this ).css( 'display' ) === 'none' ? commentingL10n.trackbackShowText : commentingL10n.trackbackHideText );
						} );
					} );
			}

			// Hide trackbacks that show up in the comment stream.
			// This is done as a one shot deal at load time as I'll not be collecting them after first load unlike comments.
			$( '#commentlist li.pingback > .comment-body, #commentlist li.trackback > .comment-body' ).each( function( ){
				var from = 'Trackback from %s'.replace( '%s', $( this ).find( 'cite.fn' ).text( ) ); // Translatify
				$( this )
					.hide( )
					.before( '<div class="trackback-toggle"></div>' )
					.prev( '.trackback-toggle' )
					.text( from )
					.click( function( ){
						$( this ).next( '.comment-body' ).slideToggle( 'fast' );
					} );
			} );

			// Change the link button to a pop up element that has the link in it. WIP.
			//$( '#commentlist .comment-link' ).live( 'click', function( ) {
			//	var val = $( this ).attr( 'href' ),
			//		box = $( '<div class="comment-link-display"><input type="text" value="' + val + '" /></div>' )
			//			.css( {
			//				position: 'absolute',
			//				top: $(this).offset( ).top + 'px',
			//				left: $(this).offset( ).left + 'px',
			//				zIndex: 100
			//			} )
			//			.hide( );
			//
			//	$( 'body' ).append( box ).find( '.comment-link-display' ).fadeTo( 'slow', 1 );
			//	return false;
			//} );

		} );
	}
};

addComment._init( );
