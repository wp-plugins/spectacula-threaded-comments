jQuery( document ).ready( function( $ ) {

	function spec_get_selected_text( ) {
		var txt;

		if ( window.getSelection ) {
			txt = window.getSelection( );
		} else if ( document.getSelection ) {
			txt = document.getSelection( );
		} else if ( document.selection ) {
			txt = document.selection.createRange( ).text;
		}

		return txt.toString( ) != '' ? txt.toString( ) : false;
	}


	// Is the current box our floating hover?
	function is_quote_hover( e ) {
		return e.id != null && ( e.id === 'quote-float' || is_quote_hover( e.parentNode ) );
	}


	// Add the quote button to each comment.
	function spec_add_quote_button( e ) {
		$( e ).find( '.comment-buttons' ).append( $( '<a href="#" class="comment-button">' + specQuoteLn.button_text + '</a>' ).click( function( ) {
			spec_quote_comment( this );
			return false;
		} ) );
	}


	function spec_quote_comment( e ) {
		var text = $( e ).parents( '.comment-buttons' ).prev( '.comment-text' ).html( ),
			auth = $( e ).parents( '.comment-buttons' ).siblings( '.comment-meta' ).find( 'cite.fn' ).text( ),
			resu = '';

		resu = '<blockquote>\n<cite>' + $.trim( auth ) + '</cite>\n' + $.trim( text ) + '\n</blockquote>\n';
		insert_at_carret( 'comment', resu );
	}


	function ie_carret_pos( e ) {
		e.focus( );
		var r = document.selection.createRange( ),
			re = e.createTextRange( ),
			rc = re.duplicate( );

		re.moveToBookmark( r.getBookmark( ) );
		rc.setEndPoint( 'EndToStart', re );

		return r == null ? 0 : rc.text.length;
	}


	function insert_at_carret( id, txt ) {
		var e = document.getElementById( id ),
			t = e.value,
			p = e.selectionStart !== undefined ? e.selectionStart : ie_carret_pos( e );

		$( '#' + id ).val( t.substring( 0, p ) + txt + t.substring( p, t.length ) ).trigger( 'keyup' ).focus( );
		$.scrollTo( '#' + id, { duration: 500, axis: 'y' } );
	}


	if ( specQuoteLn.quote_button ) {
		// Bind to my custom new comment event
		$( '.comment' ).each( function( ) {
			spec_add_quote_button( this );
		} ).live( 'newComment', function( ){
			spec_add_quote_button( this );
		} );
	}


	if ( specQuoteLn.quote_select ) {
		$( specQuoteLn.quote_target ).mouseup( function( e ) {
			var txt = spec_get_selected_text( ),
				a = $( e.currentTarget ), // Or originalTarget for the element clicked.
				y = e.pageY,
				sx = e.pageX,
				ex = a.offset( ).left + a.width( );

			if ( txt ) {
				$( '<div><span>' + specQuoteLn.button_text + '</span></div>' )
					.attr( { id: 'quote-float' } )
					.appendTo( 'body' )
					.css( {
						position: 'absolute',
						left: sx + 1, // + 1 px to avoid an IE 6,7,8 & 9 bug with the redraw
						top: y - ( $( '#quote-float' ).height( ) / 2 ) ,
						zIndex: 1000
					} )
					.animate( { left: ex + 10 }, 500 )
					.click( function( ) {
						insert_at_carret( 'comment', '<blockquote>\n' + $.trim( txt ) + '\n</blockquote>\n' );
						$( '#quote-float' ).remove( );
					} );
			}
		} );


		// Kill the quote hover button when someone clicks elsewhere.
		$( document ).mousedown( function( e ) {
			var trgt = e.target || window.event.srcElement;
			if ( ! is_quote_hover( trgt ) )
				$( '#quote-float' ).remove( );
		} );
	}

} );
