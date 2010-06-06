
/*
 jQuery Replacement for the normal WP comment reply JS.
 As I'm using jQuery anyway may as well include an easier to read/maintin
 comment reply code. Also alows me to animate things should I want to.
*/

addComment = {

	replying: 0,

	replyForm: '', // Store the form here for dropping in as an when needed.

	replyFormParent: '',

	moveForm: function( belowID, commentID, formID, postID ) {
		if ( addComment.replying ) {
			addComment.cancelReply( );
		}

		// Set the toggle
		addComment.replying = commentID;

		// Set the value of the reply to comment
		addComment.replyForm.find( 'input#comment_parent' ).attr( { value: commentID } );

		// Destroy the original form.
		//jQuery( '#respond' ).remove( );

		// Hide the reply link on this comment
		jQuery( '#' + belowID + ' > .comment-body ' ).find( '.comment-reply-link' ).hide( );

		// Insert a UL if needed
		//if ( ! jQuery( 'ul#commentlist li#comment-' + commentID + ' > ul.children' ).length )
		//	jQuery( 'ul#commentlist li#comment-' + commentID + ' > div.comment-body' ).after( '<ul class="children"></ul>' );

		// Insert the new form.
		//jQuery( 'ul#commentlist li#comment-' + commentID + ' > ul.children' ).prepend( addComment.replyForm )
		//	// Show the cancel link
		//	.find( '#cancel-comment-reply-link' )
		//	.css( { display:'inline' } );

		//jQuery( '#respond' )
		//	// Strip existing depth-?? class from the li, bit hackish but it works; :D
		//	.removeClass( 'depth-1 depth-2 depth-3 depth-4 depth-5 depth-6 depth-7 depth-8 depth-9 depth-10' )
		//	.addClass( 'depth-' + ( addComment.commentDepth( commentID ) + 1 ) ) ;

		return false;
	},

	cancelReply: function( ) {
		var id = addComment.replying;

		if ( id !== 0 ) {
			addComment.replying = 0;
			jQuery( '#comment-form input#comment_parent' ).attr( { value: 0 } );
			jQuery( '#comment-form #cancel-comment-reply-link' ).hide( );
			jQuery( '#commentlist' ).find( '.comment-reply-link' ).show( );

			//// Get rid of any empty children
			//if ( ! jQuery( 'ul#commentlist li#comment-' + id + ' > ul.children' ).children( ).length )
			//	jQuery( 'ul#commentlist li#comment-' + id + ' > ul.children' ).remove( );

			// Show all the reply links again

			//jQuery( '#respond' )
			//	// Strip existing depth-?? class from the li, bit hackish but it works; :D
			//	.removeClass( 'depth-2 depth-3 depth-4 depth-5 depth-6 depth-7 depth-8 depth-9 depth-10' )
			//	.addClass( 'depth-1' ) ;
		}

		jQuery( '#comment-form .submit' ).attr( { disabled: '' } ).removeClass( 'disabled' );
	},

	// Take the depth class assigned to the comment and turn into an int.
	commentDepth: function( id ) {
		var c;
		if ( id ) {
			c = jQuery( 'li#comment-' + id ).attr( 'class' ).match( /\bdepth-(\d+)\b/i );
			return [ 1 ] !== undefined && parseInt( c[ 1 ], 10 ) > 0 ? parseInt( c[ 1 ], 10 ) : 1;
		} else
			return 1;
	},

	submit: function( v ) {
		var blankFields = false, dpth = 0, c, commlist;

		jQuery( v ).find( '.vital' ).each( function( ){
			var value = jQuery( this ).attr( 'value' );
			if ( value === undefined || value ===  '' ) {
				blankFields = true;
				jQuery( this ).css( { borderColor: '#f00' } ).fadeOut( 250 ).fadeIn( 250 );
			} else {
				jQuery( this ).css( { borderColor: '#ccc' } );
			}
		} );

		// Form not filled out then no point going on.
		if ( blankFields )
			return false;

		if ( addComment.replying !== 0 ) {
			c = jQuery( 'li#comment-' + addComment.replying ).attr( 'class' ).match( /depth-(\d+)/i );
			dpth = c[ 1 ] !== undefined || parseInt( c[ 1 ], 10 ) > 0 ? parseInt( c[ 1 ], 10 ) : 0;
		}



//		console.log( v );
		jQuery( v ).ajaxSubmit( {

			beforeSubmit: function( r ) {
				jQuery( '#comment-form .submit' ).attr( { disabled: 'disabled' } ).addClass( 'disabled' );
			},

			type: 'POST',

			data: {
				// Add a trigger so we know we're doing this ajaxicaly.
				_spec_ajax: 'Why are you looking at this POST data?',
				action: 'new_comment_added',
				depth: dpth
			},
			//dataType: 'json', // Not 100% it will always be so need to parse it myself.

			error: function( r ) {
				alert( r );
			},

			success: function( r ) {
				var d;
				try {
					d = jQuery.parseJSON( r );
				} catch ( e ) {
					// @todo: Better error reports.
					alert( r );
					addComment.cancelReply( );
					return;
				}

				if ( addComment.replying ) {
					if ( ! jQuery( 'ul#commentlist li#comment-' + addComment.replying + ' > ul.children' ).length )
						jQuery( 'ul#commentlist li#comment-' + addComment.replying + ' > div.comment-body' ).after( '<ul class="children"></ul>' );

					jQuery( 'ul#commentlist li#comment-' + addComment.replying + ' > ul.children' ).append( jQuery( d.html ).hide( ).addClass( 'rolledup' ) );
				} else {
					jQuery( 'li#respond' ).before( jQuery( d.html ).hide( ).addClass( 'rolledup' ) );
				}

				addComment.cancelReply();
				jQuery( '#comment-form #comment' ).val( '' ); // Blank the comment field
				jQuery( 'ul#commentlist' ).find( '.rolledup' ).slideDown( ).removeClass( 'rolledup' );

				//alert( d.html );

				//var data = jQuery( r ).find( '#commentlist' );
				//
				//jQuery( '#commentlist' ).replaceWith( data );
				////console.log( data );
			}
		} );

		return false;
	},

	addCollapse: function( ){

	},

	_init: function( ) {

		// Make sure we know where to put it back
		addComment.replyFormParent = jQuery( '#respond' ).parent( );

		// Add the submit action
		jQuery( '#comment-form' ).live( 'submit', function( ) {
			addComment.submit( this );
			return false;
		} );

		jQuery( '#cancel-comment-reply-link' ).live( 'click', function( ){
			addComment.cancelReply( );
			return false;
		} );

		// Clone the form
		addComment.replyForm = jQuery( '#respond' ).clone( true );


	}
};

addComment._init();

jQuery( document ).ready( function( $ ) {
	return false;
	var trackbackShowText	= commentingL10n.trackbackShowText,
		trackbackHideText	= commentingL10n.trackbackHideText,
		replyHideMany		= commentingL10n.replyHideMany,
		replyShowMany		= commentingL10n.replyShowMany,
		replyHideOne		= commentingL10n.replyHideOne,
		replyShowOne		= commentingL10n.replyShowOne,
		trackbackHeight 	= $( '#trackbackList' ).height( ),
		depth				= commentingL10n.nestDepth;

	// Hide trackbacks from view if they take up too much space. Too much is 250px in my opinion but then I don't really like them. :P
	if ( trackbackHeight > 250 ) {
		$( '#trackback-list' ).css( { height: trackbackHeight } ).hide( ).after( '<strong class="trackback-toggle"><span class="switch"></span><span class="toggle-text">' + trackbackShowText + '</span></strong>' ).next( '.trackbackToggle' ).live( 'click', function( ){
			$( this ).toggleClass( 'active' ).prev( '#trackback-list' ).slideToggle( '500',function( ){
				if ( $( this ).css( 'display' ) === 'none' ){
					$( this ).next( '.trackback-toggle' ).children( '.toggle-text' ).html( trackbackShowText );
				} else {
					$( this ).next( '.trackback-toggle' ).children( '.toggle-text' ).html( trackbackHideText );
				}
			} );
		} );
	}

	/*
	 * We quickly run through adding a height to each element that'll be
	 * squished. We do this before we run though the process of squishing
	 * for obvious reasons. Without this the animation can get a little
	 * messed up is some browsers.
	*/
	$( '.with-collapse .depth-' + depth + ' ul.children' ).find( '*[ id^=div-comment- ]' ).each( function( ){
		var $height = $( this ).height( );
		$( this ).css( { height: $height } );
	} );


	// Collapse comments greter than depth-n can be changed to any depth if you want to show some of the replies without having to click.
	$( '.with-collapse .depth-' + depth + ' ul.children' ).css( { marginTop: 0 } ).each( function( ){
		var posterName = $( this ).prev( 'div.comment-body' ).find( 'div.comment-author' ).children( 'cite.fn' ).text( ),
			// replyQuant = $( this ).find( 'li.comment' ).length, // Use to count all subordinate comments
			replyQuant = $( this ).children( 'li.comment' ).length, // Use to count just those on the next level every reply in the tree
			replyText,
			replyTextHide;

		if ( replyQuant == 1 ) {
			replyText 		= '<span class="switch"></span><span class="toggle-text">' + replyShowOne.replace( '%name%','<span class="poster-name">' + posterName + "'s</span>" ).replace( '%count%',replyQuant ) + '</span>';
			replyTextHide 	= '<span class="switch"></span><span class="toggle-text">' + replyHideOne.replace( '%name%','<span class="poster-name">' + posterName + "'s</span>" ).replace( '%count%',replyQuant ) + '</span>';
		} else {
			replyText 		= '<span class="switch"></span><span class="toggle-text">' + replyShowMany.replace( '%name%','<span class="poster-name">' + posterName + "'s</span>" ).replace( '%count%',replyQuant ) + '</span>';
			replyTextHide 	= '<span class="switch"></span><span class="toggle-text">' + replyHideMany.replace( '%name%','<span class="poster-name">' + posterName + "'s</span>" ).replace( '%count%',replyQuant ) + '</span>';
		}

		$( this ).hide( ).before( '<div class="toggle">' + replyText + '</div>' ).parent( 'li' ).addClass( 'with-replies' ).children( 'div.toggle' ).live( 'click', function( ){
			if ( $( this ).next( 'ul.children' ).css( 'display' ) === 'none' ) {
				$( this ).html( replyTextHide )
			} else {
				$( this ).html( replyText )
			}
			$( this ).toggleClass( 'active' ).next( 'ul.children' ).slideToggle( );
		} );
	} );

	// Stop you from hitting submit on comments until all important fields are filled.
	$( '#comment-form' ).live( 'submit', function( ) {
		var blankFields = false,
			theButton = this;

		$( this ).find( '.vital' ).each( function( ){
			var value = $( this ).attr( 'value' );
			if ( value === undefined || value ===  '' ) {
				blankFields = true;
				$( this ).css( { borderColor: '#f00' } ).fadeOut( 250 ).fadeIn( 250 );
			} else {
				$( this ).css( { borderColor: '#ccc' } );
			}
		} );

		if ( blankFields ) {
			return false;
		} else {
			$( this ).ajaxSubmit( {
				beforeSubmit: function( r ) {
					$( '#comment-form .submit' ).attr( { disabled: 'disabled' } ).addClass( 'disabled' );
				},
				error: function( r ) {

				},
				success: function( r ) {
					try {
						var data = $( r ).find( '#commentlist' );

						$( '#commentlist' ).replaceWith( data );
						//console.log( data );
						addComment.cancelReply();
						$( '#comment-form .submit' ).attr( { disabled: '' } ).removeClass( 'disabled' );
						$( '#comment-form #comment' ).val( '' );

					} catch ( e ){
						addComment.cancelReply( );
						$( '#comment-form .submit' ).attr( { disabled: '' } ).removeClass( 'disabled' );
						//console.log( e );
					}
				}
			} );
			return false;
		}
	} );

	// Fix some IE 6 problems. The sooner ie6 dies the better
	$.each( $.browser, function( i, val ) {
		if( i=='msie' && val === true && $.browser.version.substr( 0,1 ) == 6 ){
			// Add IE6 specific stuff here.
			$( '#commentlist li:first-child' ).addClass( 'first-child' );
			$( '#commentlist li.bypostauthor > div.comment-body' ).addClass( 'bypostauthor' );
			$( 'body' ).addClass( 'ie6' );
		}
	} );

} );
