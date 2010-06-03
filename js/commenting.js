
/*
 jQuery Replacement for the normal WP comment reply JS.
 As I'm using jQuery anyway may as well include an easier to read/maintin
 comment reply code. Also alows me to animate things should I want to.
*/

addComment = {

	replying: false,
	replyForm: '',
	replyFormParent: '',
	belowID: '',

	moveForm: function( belowID, commentID, formID, postID ) {

		jQuery( function( $ ) {
			if ( addComment.replying ) {
				addComment.cancelReply( );
			}

			// Set our toggle
			addComment.replying = true;

			// Set the location of the form so we can get back there later.
			addComment.belowID = belowID;

			// Clone the form
			addComment.replyForm = $( '#' + formID ).clone( true );

			// Make sure we know where to put it back
			addComment.replyFormParent = $( '#' + formID ).parent( );

			// Destroy the original form.
			$( '#' + formID ).remove( );

			// Hide the reply link on this comment
			$( '#' + belowID ).find( '.comment-reply-link' ).hide( );

			// Set the value of the reply to comment
			$( 'input#comment_parent' ).attr( { value:commentID } );

			// Insert the new form and attach a function to the cancel link.
			addComment.replyForm.insertAfter( '#' + belowID ).find( '#cancel-comment-reply-link' ).css( { display:'inline' } ).click( function( ){
				addComment.cancelReply( );
				return false;
			} );
		} );
		return false;
	},

	cancelReply: function( ) {
		// Set our toggle back.
		addComment.replying = false;
		// We've clicked cancel so we need to see the reply link again.
		jQuery( '#commentlist' ).find( '.comment-reply-link' ).show( );
		// Add the form back to where it should be.
		addComment.replyFormParent.append( addComment.replyForm ).find( '#cancel-comment-reply-link' ).css( { display:'none' } );
		// Make sure to set the reply to value to 0;
		jQuery( 'input#comment_parent' ).attr( { value:0 } );

	}
}

jQuery( document ).ready( function( $ ){
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
		$( '#trackbackList' ).css( { height: trackbackHeight } ).hide( ).after( '<strong class="trackbackToggle"><span class="switch"></span><span class="toggleText">' + trackbackShowText + '</span></strong>' ).next( '.trackbackToggle' ).click( function( ){
			$( this ).toggleClass( 'active' ).prev( '#trackbackList' ).slideToggle( '500',function( ){
				if ( $( this ).css( 'display' ) === 'none' ){
					$( this ).next( '.trackbackToggle' ).children( '.toggleText' ).html( trackbackShowText );
				} else {
					$( this ).next( '.trackbackToggle' ).children( '.toggleText' ).html( trackbackHideText );
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


	// Collapse comments greter than depth-1 can be changed to any depth if you want to show some of the replies without having to click.
	$( '.with-collapse .depth-' + depth + ' ul.children' ).css( { marginTop: 0 } ).each( function( ){
		var posterName = $( this ).prev( 'div.comment-body' ).find( 'div.comment-author' ).children( 'cite.fn' ).text( ),
			// replyQuant = $( this ).find( 'li.comment' ).length, // Use to count all subordinate comments
			replyQuant = $( this ).children( 'li.comment' ).length, // Use to count just those on the next level every reply in the tree
			replyText,
			replyTextHide;

		if ( replyQuant == 1 ) {
			replyText 		= '<span class="switch"></span><span class="toggleText">' + replyShowOne.replace( '%name%','<span class="posterName">' + posterName + "'s</span>" ).replace( '%count%',replyQuant ) + '</span>';
			replyTextHide 	= '<span class="switch"></span><span class="toggleText">' + replyHideOne.replace( '%name%','<span class="posterName">' + posterName + "'s</span>" ).replace( '%count%',replyQuant ) + '</span>';
		} else {
			replyText 		= '<span class="switch"></span><span class="toggleText">' + replyShowMany.replace( '%name%','<span class="posterName">' + posterName + "'s</span>" ).replace( '%count%',replyQuant ) + '</span>';
			replyTextHide 	= '<span class="switch"></span><span class="toggleText">' + replyHideMany.replace( '%name%','<span class="posterName">' + posterName + "'s</span>" ).replace( '%count%',replyQuant ) + '</span>';
		}

		$( this ).hide( ).before( '<div class="toggle">' + replyText + '</div>' ).parent( 'li' ).addClass( 'with-replies' ).children( 'div.toggle' ).click( function( ){
			if ( $( this ).next( 'ul.children' ).css( 'display' ) === 'none' ) {
				$( this ).html( replyTextHide )
			} else {
				$( this ).html( replyText )
			}
			$( this ).toggleClass( 'active' ).next( 'ul.children' ).slideToggle( );
		} );
	} );

	// Stop you from hitting submit on comments until all important fields are filled.
	$( '#commentForm' ).submit( function( ){
		var blankFields = false;
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
			return true;
		}
	} );

	// Fix some IE 6 problems. The sooner ie6 dies the better
	$.each( $.browser, function( i, val ) {
		if( i=='msie' && val === true && $.browser.version.substr( 0,1 ) == 6 ){
			// Add IE6 specific stuff here.
			$( '#commentlist li:first-child' ).addClass( 'first-child' );
			$( '#commentlist li.bypostauthor > div.comment-body' ).addClass( 'bypostauthor' );
			//$( 'body' ).addClass( 'ie6' );
		}
	} );

} );
