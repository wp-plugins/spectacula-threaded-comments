
/*
 jQuery Replacement for the normal WP comment reply JS.
 As I'm using jQuery anyway may as well include an easier to read/maintin
 comment reply code. Also alows me to animate things should I want to.
*/

addComment = {
	moveForm:function(belowID,commentID,replyID,postID){
		jQuery(function($){
			var replyForm = $('#'+replyID).clone(true),
				replyFormParent = $('#'+replyID).parent();
			$('#'+replyID).remove();

			replyForm.insertAfter('#'+belowID).find('#cancel-comment-reply-link').css({display:'inline'}).click(function(){
				$(this).parents('#'+replyID).remove();
				replyFormParent.append(replyForm).find('#cancel-comment-reply-link').css({display:'none'});
				$('input#comment_parent').attr({value:0});
				return false;
			});

			$('input#comment_parent').attr({value:commentID});
		});
		return false;
	}
}

jQuery(document).ready(function($){
	var trackbackShowText	= commentingL10n.trackbackShowText,
		trackbackHideText	= commentingL10n.trackbackHideText,
		replyHideMany		= commentingL10n.replyHideMany,
		replyShowMany		= commentingL10n.replyShowMany,
		replyHideOne		= commentingL10n.replyHideOne,
		replyShowOne		= commentingL10n.replyShowOne,
		trackbackHeight 	= $('#trackbackList').height(),
		depth				= commentingL10n.nestDepth;

	// Hide trackbacks from view if they take up too much space. Too much is 250px in my opinion but then I don't really like them. :P
	if (trackbackHeight > 250) {
		$('#trackbackList').css({height:trackbackHeight}).hide().after('<strong class="trackbackToggle"><span class="switch"></span><span class="toggleText">'+trackbackShowText+'</span></strong>').next('.trackbackToggle').click(function(){
			$(this).toggleClass('active').prev('#trackbackList').slideToggle('500',function(){
				if ($(this).css('display') === 'none'){
					$(this).next('.trackbackToggle').children('.toggleText').html(trackbackShowText);
				} else {
					$(this).next('.trackbackToggle').children('.toggleText').html(trackbackHideText);
				}
			});
		});
	}

	/*
	 * We quickly run through adding a height to each element that'll be
	 * squished. We do this before we run though the process of squishing
	 * for obvious reasons. Without this the animation can get a little
	 * messed up is some browsers.
	*/
	$('.with-collapse .depth-'+depth+' ul.children').find('*[id^=div-comment-]').each(function(){
		var $height = $(this).height();
		$(this).css({height:$height});
	});

	// Collapse comments greter than depth-1 can be changed to any depth if you want to show some of the replies without having to click.
	$('.with-collapse .depth-'+depth+' ul.children').css({marginTop:0}).each(function(){
		var posterName = $(this).prev('div.comment-body').find('div.comment-author').children('cite.fn').text(),
			// replyQuant = $(this).find('li.comment').length, // Use to count all subordinate comments
			replyQuant = $(this).children('li.comment').length, // Use to count just those on the next level every reply in the tree
			replyText,
			replyTextHide;

		if (replyQuant == 1) {
			replyText 		= '<span class="switch"></span><span class="toggleText">'+replyShowOne.replace('%name%','<span class="posterName">'+posterName+"'s</span>").replace('%count%',replyQuant)+'</span>';
			replyTextHide 	= '<span class="switch"></span><span class="toggleText">'+replyHideOne.replace('%name%','<span class="posterName">'+posterName+"'s</span>").replace('%count%',replyQuant)+'</span>';
		} else {
			replyText 		= '<span class="switch"></span><span class="toggleText">'+replyShowMany.replace('%name%','<span class="posterName">'+posterName+"'s</span>").replace('%count%',replyQuant)+'</span>';
			replyTextHide 	= '<span class="switch"></span><span class="toggleText">'+replyHideMany.replace('%name%','<span class="posterName">'+posterName+"'s</span>").replace('%count%',replyQuant)+'</span>';
		}

		$(this).hide().before('<div class="toggle">'+replyText+'</div>').parent('li').addClass('with-replies').children('div.toggle').click(function(){
			if ($(this).next('ul.children').css('display') === 'none') {
				$(this).html(replyTextHide)
			} else {
				$(this).html(replyText)
			}
			$(this).toggleClass('active').next('ul.children').slideToggle();
		});
	});

	// Stop you from hitting submit on comments until all important fields are filled.
	$('#commentForm').submit(function(){
		var blankFields = false;
		$(this).find('.vital').each(function(){
			var value = $(this).attr('value');
			if (value === undefined || value ===  '') {
				blankFields = true;
				$(this).css({borderColor:'#f00'}).fadeOut(250).fadeIn(250);
			} else {
				$(this).css({borderColor:'#ccc'});
			}
		});

		if (blankFields) {
			return false;
		} else {
			return true;
		}
	});

	// Fix some IE 6 problems. The sooner ie6 dies the better
	$.each($.browser, function(i, val) {
		if(i=='msie' && val === true && $.browser.version.substr(0,1) == 6){
			// Add IE6 specific stuff here.
			$('#commentlist li:first-child').addClass('first-child');
			$('#commentlist li.bypostauthor > div.comment-body').addClass('bypostauthor');
			//$('body').addClass('ie6');
		}
	});

});
