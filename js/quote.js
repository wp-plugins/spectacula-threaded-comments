jQuery( document ).ready( function( $ ) {
	//$( 'p' ).mouseup( function( ) {
	//	var text = spec_get_selected_text( );
	//	if ( text )
	//		alert( text );
	//} );

	function spec_get_selected_text( ) {
		var text = '';

		if ( window.getSelection ) {
			text = window.getSelection( );
		} else if ( document.getSelection ) {
			text = document.getSelection( );
		} else if ( document.selection ) {
			text = document.selection.createRange( ).text;
		}

		return text != '' ? text : false;
	}

	function spec_add_quote_button( elem ) {
		$( elem ).find( '.comment-buttons' ).append( $( '<a href="#" class="comment-button">' + specQuoteLn.button_text + '</a>' ).click( function( ) {
			spec_quote_comment( this );
			return false;
		} ) );
	}

	function spec_quote_comment( elem ) {
		var text = $( elem ).parents( '.comment-buttons' ).prev( '.comment-text' ).text( ).trim( ),
			auth = $( elem ).parents( '.comment-buttons' ).siblings( '.comment-meta' ).find( 'cite.fn' ).text( ),
			comm = $( '#comment' ).val( ).trim( ),
			resu = '';

		resu = comm + '<blockquote>\n<cite>' + auth + '</cite>\n' + text + '\n</blockquote>\n';
		$( '#comment' ).val( resu ).trigger( 'keyup' ); // Trigger a the keypress event to expand the box.

		$.scrollTo( $( '#comment' ), { duration: 500, axis: 'y' } );
	}

	// Bind to my custom new comment event
	$( '.comment' ).each( function( ) {
		spec_add_quote_button( this );
	} ).live( 'newComment', function( ){
		spec_add_quote_button( this );
	} );
} );


