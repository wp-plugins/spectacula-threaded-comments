=== Plugin Name ===
Contributors: interconnectit, spectacula
Donate link: https://spectacu.la/signup/signup.php
Tags: comments, jQuery,
Requires at least: 2.7.0
Tested up to: 2.8.6
Stable tag: 1.0.0

This plug-in will add threaded comments to most themes without any need for you
to dig into that themes code.

== Description ==

Once installed this plug-in will replace your theme's comments template with its
own. Giving you threading, pagination and jQuery based roll up of subordinate
comments this allows you to keep things tidy. The roll up can be told to trigger
at any depth you feel is best using the comments page under appearance. Also
available on the plug-ins admin page is the option to use the other included
stylesheet that's more appropriate for dark hued themes. You can also tell it to
use no stylesheet at all. This is handy if you want to roll it into your themes
stylesheet to cut down the number of HTTP requests a visitor makes, useful on
busy sites.

This could also be useful for theme builders as you'd not have to worry about
your comments.php again, Comments.php is one of the more complicated elements of
any theme. And don't worry about only being able to use the stylesheets provided
or having to hack the plug-in after every update, I've included three filers to
allow you to replace the jQuery and the stylesheet with something of your own.

The filters

1.	**spec_comment_css**
	This will pass the URL of the stylesheet through to your function to replace
	with your own file.
2.	**spec_comment_js**
	The will pass the URL to the jQuery file that controls the roll up and a few
	other elements.
3.	**spec_comment_local_js**
	This will pass in an array of localisation strings that are passed to the
	jQuery code.

To replace the CSS file you could add something like the following to your
functions.php that would point to a comments.css file in the folder of your
current theme.

`<?php
	add_filter('spec_comment_css', 'my_css_file');

	function my_css_file() {
		return get_bloginfo('template_directory') . '/comments.css';
	}

?>`

= Warning =
Some themes will fail to work with this without you doing something to them
first. Such as if the theme doesn't call the comments.php using the
`comments_template();` template tag or if your theme deals with comments in an
unusual way, such as placing them in a sidebar or calling them in using Ajax. A
missing or unusual DOCTYPE could cause problems too, in fact there are lots of
things that could cause strangeness. However with most of the themes I've tested
this with it has worked without issue straight out of the gate and even if it
doesn't look right you need only disable the plug-in to go back to how things
were so nothing lost.

== Installation ==

= The install =

1.	Upload `commenting.php` and all sub folders to
	`/wp-content/plugins/spec-comments/` or `/wp-content/mu-plugins/` directory.
	If the directory doesn't exist then create it.
2.	Activate the plugin through the 'Plugins' menu in WordPress.
3.	You should now see an extra menu called comments show up under the
	appearance menu in the main admin sidebar.
4.	Check a page on your site with comments and see that everything is as you'd
	hope. If it's not then proceed to the config menu and see if what you want
	can be set from there.

= The config =
1.  The main option with this plug-in is the option to define at which point the
	comments roll up. Default is set so that all replies are hidden behind a
	click but with the drop down you could specify that replies to replies are
	hidden or replies to replies of replies and so on...
2.  The next group of options are some simple toggles and the first of these
	lets you turn off the plug-in's CSS file. This can be handy if you want to
	keep all CSS in the one stylesheet thus reducing the number of HTTP requests
	or if you just want to style it up in your own way. If you turn off the
	style anything attached to the spec_comment_css filter will fail to run
	also.
3.  As an extra to the one above we have the option to turn on the dark version
	of the comments. This is handy if your theme is, well err.., dark.
4.  If you'd rather drop all the javaScript then the next option is for you. Be
	warned however that this will cause some styling issues in IE6 as jQuery is
	used to add some extra classes to items that would otherwise be inaccessible
	to IE6's limited understanding of CSS. All other browsers are unaffected and
	IE6 will still be in a perfectly acceptable state it just won't look the
	same as the other real browsers. Much the same as for the CSS, if you turn
	it off anything attached to the spec_comment_js or spec_comment_local_js
	filter will not be run.
5.	The final option is to hide our credit link and is one we'd rather you
	didn't do anything with but we've given you it anyway as we're nice like
	that. We do understand that sometimes clients want things like that gone
	we'd just like to ask in return for that is that you think about signing up
	with us

== Frequently Asked Questions ==

= Why doesn't it quite look/work right? =

Unfortunately it's not possible for us to check this code against every theme
out there so from time to time you may run into some incompatibilities. It's
possible that some CSS in your theme's style.css is causing issues. You could
try stripping out all commenting related CSS from it and see if that makes any
difference. If it's not that then it might be that your theme isn't calling
comments_template() to add the comments code, if that's the case then you're out
of luck unless you know where to look in the theme code or you can get your
theme developer to change their theme.

== Screenshots ==

1.	Will work on both dark and light themes thanks to alternative stylesheet
	accessible from the control interface.
2.	The control interface for this plug-in.

== Changelog ==

= 1.0 =
* Initial release.

= 0.9 =
* Internal version.

== Upgrade Notice ==

= 1.0 =
* Not many changes from the internal version no urgency in the upgrade.
