=== Plugin Name ===
Contributors: interconnectit, spectacula
Donate link: https://spectacu.la/signup/signup.php
Tags: comments, jQuery, AJAX
Requires at least: 2.7.0
Tested up to: 3.0
Stable tag: 1.0.3

Spectacu.la Discussion adds threaded commenting with live AJAX comments 
to almost any WordPress Theme.

== Description ==

Ever found your theme doesn't support threaded comments?  Would you like to to be
able to have P2 style live commenting on your site?  Spectacu.la Discussion is a
plugin that replaces the commenting code in your theme with a fully Ajaxed comments
engine.

The plugin uses memory tables for fast cacheing within the database (no pesky
permission setting for you to worry about here) and adjustible polling times
to suit a wide range of server performance.

The plugin is also developer friendly, meaning that you can easily add styles
to your theme so that should a user implement the plugin your meticulous design
can be carried through.  By default there are two styles that should work in most
cases.  Read below for further instructions.

Once installed this plug-in will replace your theme's comments template with its
own fully Ajax comment template. This allows both submission and update of
comments without the page refreshing letting your visitors use it more as a chat
room than the more traditional comment system. You can also roll up replied
which can be told to trigger at any depth you feel is best using the settings
page. Also available on the plug-ins admin page is the option to use another or
no stylesheet, toggle the live updating of comments and change the refresh
period for live update.

As of version 2 you can now easily add new stylesheets to the available list by
either copying them into the plug-in's style folder with a comment at the top of
the sheet that looks like this /* comment style: Stylesheet name */ or adding a
commenting.css to your theme or child theme. Once added they will then be listed
in the dropdown menu that shows on the admin page.

The plugin is also, of course, Multisites compatible.

=The filters=

There are a few filters available for developers to intercept the javascript,
paramerers passed to the javascript and the CSS file location

1.	**spec_comment_css**
	Passes the URL of the stylesheet through to your function to replace
	with your own file.
2.	**spec_comment_js**
	Passes the URL to the jQuery file that controls the roll up and a few
	other elements.
3.	**spec_comment_local_js**
	Passes in an array of localisation strings that are passed to the
	jQuery code.

To replace the CSS file you could add something like the following to your
functions.php that would point to a comments.css file in the folder of your
current theme. This will then override any choice made by the user on the admin
page.

`<?php
	add_filter('spec_comment_css', 'my_css_file');

	function my_css_file() {
		return get_bloginfo('template_directory') . '/comments.css';
	}

?>`

= Warning =

Every effort has been made to make this work with as wide a variety of themes as
possible but we can't cover every eventuality so some themes out there will
cause problems with this plugin without you doing something to either the
plug-in or the theme first. The most likely cause of problems is that some of
the CSS in the theme conflicts with the CSS in the comments. There are various
other areas where problems could arise, such as if the theme doesn't call the
comments.php using the `comments_template();` template tag or if your theme
deals with comments in an unusual way, such as placing them in a sidebar or
calling them in using Ajax that conflicts with our own. A missing or unusual
DOCTYPE could cause problems too, in fact there are lots of things that could
cause strangeness. However with most of the themes I've tested this with it has
worked without issue straight out of the gate and even if it doesn't look right
you need only disable the plug-in to go back to how things were, so nothing's lost.

== Installation ==

= The install =

You can either install the plugin using the WordPress auto-installer, or manually:

1.	Upload `commenting.php` and all sub folders to
	`/wp-content/plugins/spec-comments/` or `/wp-content/mu-plugins/` directory.
	If the directory doesn't exist then create it.
2.	Activate the plugin through the 'Plugins' menu in WordPress.
3.	You should now see an extra menu show up under the settings menu in the main
	admin sidebar.
4.	Check a page on your site with comments and see that everything is as you'd
	hope. If it's not then proceed to the config menu and see if what you want
	can be set from there.

= The config =

1.  The first option with this plug-in is the option to define at which point
	the	comments roll up. Default is set so that all replies are hidden behind a
	click but with the drop down you could specify that replies to replies are
	hidden or replies to replies of replies and so on...
2.	The next block is for controlling the titles that show above the comment
	block and the trackback block if your theme separates them out.
3.	Choose the stylesheet you want to use with your theme. At the moment there
	are two, the default style for use with light coloured themes and the dark
	style for use with dark themes. You can also disable the CSS here if you'd
	rather roll it into your theme's stylesheet. If you add more CSS files with
	the special CSS comment to the style folder in the plug-in or add a
	commenting.css to your theme's folder then that'll show up in this list too.
4.	The comment update block lets you control the frequency of comment update
	and whether they're enabled or not.	The default status for "Auto update" is
	off, if you want to enable live	commenting then check this box and set a
	time interval in the box below that	is appropriate to your server/traffic
	levels. The minimum amount of time you can set for the auto update is 10
	seconds any attempt to get it quicker than that will result in it returning
	to the default value of 30 seconds.
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

= 2.0.0 =
*	There is now a completely new theme for the comments which has been updated
	for both the dark an light versions and should work with more themes.
	Comments are now ajaxed for both submit and update. You have control over
	update frequency and the code will respect your choices for comment order
	depth and all the other settings in the discussion area of the settings.
	Made sure it works with all versions of WP from 2.7 up, including WP 3.0.
	New stylesheet handling code that allows us to add more styles quickly. You
	can now add stylesheet to your theme/child theme directory and that'll be
	picked up by the plug-in and offered as an option on the plug-in's admin
	page.
	

= 1.0.3 =
*	Moved the javascript to the footer and removed an unneeded script.

= 1.0.2 =
*	Added the reply link text to the translation fields for this plug-in rather
	than use WP's own reply to make it a little easier for people to translate
	every element.

= 1.0.1 =
*	Very minor fix for ie6. Seems I was a little over zealous trying to fix a
	hasLayout bug. Should be good now.

= 1.0 =
* Initial release.

= 0.9 =
* Internal version.

== Upgrade Notice ==

= 1.0.3 =
* Moved the javascript to the footer and removed an unneeded script. Nothing
serious, just tweaks.

= 1.0.2 =
* Not really required that you upgrade. Just added a single element to make it
easier to translate this plug-in.

= 1.0 =
* Not many changes from the internal version no urgency in the upgrade.
