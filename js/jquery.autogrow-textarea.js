(function($) {

    /*
     * Auto-growing textareas; technique ripped from Facebook
     */
    $.fn.autogrow = function( options ) {

        this.filter('textarea').each(function() {

            var $this       = $( this ),
                minHeight   = $this.height(),
				shadow = $('<div></div>').css({ // The box we'll drop the text in to measure it.
					top:			'-1000em',
					left:			'-1000em',
					zIndex:			200,
					width:			$(this).width() - parseInt($this.css('paddingLeft')) - parseInt($this.css('paddingRight')),
					resize:			'none',
					position:		'absolute',
					wordWrap:		'break-word',
					fontSize:		$this.css('fontSize'),
					fontFamily:		$this.css('fontFamily'),
					lineHeight:		$this.css('lineHeight'),
					lineHeight: 	$this.css('lineHeight'),
					letterSpacing:	$this.css('letterSpacing')
				}).appendTo( document.body ),
				update = function( ) {

					var times = function(string, number) {
						for (var i = 0, r = ''; i < number; i ++) r += string;
						return r;
					},
					// Clean up the string
						val = this.value.replace(/</g, '&lt;')
										.replace(/>/g, '&gt;')
										.replace(/&/g, '&amp;')
										.replace(/\n$/, '<br/>&nbsp;')
										.replace(/\n/g, '<br/>')
										.replace(/ {2,}/g, function(space) { return times('&nbsp;', space.length -1) + ' ' });

					shadow.html(val);
					$(this).css('height', Math.max(shadow.height() + 20, minHeight));

				}

            $(this).change(update).keyup(update).keydown(update);

            update.apply(this);

        });

        return this;

    }

})(jQuery);
