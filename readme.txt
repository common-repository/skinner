=== Skinner ===
Contributors: tompahoward
Donate link: http://windyroad.org/software/wordpress/skinner-plugin/#donate
Tags: theme, skin, admin, Windy Road
Requires at least: 2.3
Tested up to: 2.3
Stable tag: 0.1.4

The Skinner plugin adds skin selection and editing to WordPress for Skinner compatible themes.

== Description ==

The Skinner plugin adds skin selection and editing to [WordPress](http://wordpress.org) 
for [Skinner compatible themes](http://windyroad.org/software/wordpress/skinner-plugin/#skinner-themes).
The Skinner plugin is based on the Theme Selector and Theme Editor that are built into
[WordPress](http://wordpress.org), giving them the the same level of functionality, look and feel.

== Installation ==

1. copy the 'skinner' directory to your wp-contents/plugins directory.
1. Activate Skinner in your plugins administration page.
1. You will now see the 'Skins' and a 'Skin Editor' (if the theme has skins to edit) entrie in the 'Presentation' menu.
1. You will also see a 'Skin Switcher' widget in the 'Widgets' menu.

== Frequently Asked Questions ==

= I'm a theme user. Why would I create skins? =

Using skins is more maintainable that editing the theme directly.

If you are editing the theme, then when a new version of the theme comes out, you need to remember all the changes you made
previously and apply them to the upgraded theme.  Tracking down the changes can be a real pain.

If instead of editing the theme, you create a skin, then when a new version of the theme comes out, you can
just copy your skins into the new theme.  A few tweeks of your skin and the upgrade should be complete.  Nice! 

= Can I skin a skin? =

Yes you can. Just create a skin for the same theme and in the header of the 'style.css' file add
`Parent Skin: skinname`

= How do I put a skin switcher on my page, without using widgets? =
Edit the theme you are using and place `<?php skinner_skin_switcher() ?>` where
you would like the skin switcher to appear.

= I'm a theme maintainer. Why would I create skins? =

Using skins for your themes allows you maintain a single code base for the theme, but have a number of variations to it's
look and feel. e.g., your theme might be blue by default, but you could have a green, yellow and pink skin.
By providing skins for your theme, you can make it more applealing to a larger group of people, with very little extra work.
[Vistered Little](http://windyroad.org/software/wordpress/vistered-little-theme) and [JSBox](http://jsbox.net/dl)
are both great examples of skinned themes. 

= How do I find out what CSS selectors to use to modify a specific element on the page? =

Go and grab Firebug is you are using Firefox, of IE Developer Toolbar
if you are using IE.  Both of these plugins will allow you to inspect
an element to find out what `id` and/or `class` it has and what CSS
is currently applied to the element.

== Screenshots ==

1. Picking a skin
2. Editing a skin

== Creating Skins ==
To create a skin for a theme:

1. Select the theme you would like to skin.
1. Select 'Skins' on the presentation menu.
1. Click on the 'Create Skin' button (down the bottom).
1. Enter a name for your skin and click on the 'Create Skin' button.
1. Your skin has been created.  Click on the edit link.
1. Edit your skin.
1. When you are done, go back to the 'Skins' page and activate your skin.
1. If the theme has a skin archive, go to it and let them know that you have created a new skin for the theme.

= Release Notes =
* 0.1.4
	* Fixed fatal error when using the Default theme.
	* Upgraded to WordPress 2.3
* 0.1.3
	* Fixed defect in getting options from the presentation toolkit is used.
* 0.1.2
	* Fixed defect in styles sent to Real WYSIWG when the presentation toolkit is used. If you are using the presentation toolkit, please upgrade to 0.0.9.
* 0.1.1
	* Added support for the [Real WYSIWYG plugin] (http://windyroad.org/software/wordpress/real-wysiwyg-plugin/)
	* Fixed a defect that displayed a blank page when switching skins, which was caused by the browser not sending referrer information.
* 0.1.0
	* Skinner now supports all themes. Themes without any skins will have a 'Default' skin to represent the theme's unskinned look.
	* Skinner now supports enabled and disabled skins.  This allows you to control which skins are displayed in the skin switcher.
	* Skinner can now create skins.  Activate your favourite theme, go the skins page and click on 'Create Skin'.  Skinner will create the required directories and files for you.
* 0.0.5
	* Skinner is now compatible with the [Theme Switcher plugin](http://wordpress.org/extend/plugins/theme-switcher/ ).
	* You can now allow your readers to switch skins.  Code based on [Ryan Boren's](http://boren.nu/ ) [Theme Switcher plugin](http://wordpress.org/extend/plugins/theme-switcher/ ).
* 0.0.4
	* Added support for old WordPress 2.0 installations
* 0.0.3
	* Added [BeNice](http://wordpress.org/extend/plugins/be-nice/ ) support.
	* Removed nonce generation and referrer checking as it was not working. Will re-introduce later.
* 0.0.2
	* Fixed some validation issues.
	* Fixed order of style sheet includes.
* 0.0.1
	* Fixed output of the footer in the skin selector
	* The 'Get More Skins' title is no longer displayed if a skins archive is not specified
* 0.0.0
	* Initial release