=== Kalin's PDF Creation Station ===
Contributors: kalinbooks
Tags: PDF, document, export, print, pdf, creation, widget, TCPDF, backup
Requires at least: 3.0
Tested up to: 4.1
Stable tag: trunk

Build highly customizable PDF documents from any combination of pages and posts, or add a link to any page to download a PDF of that post.
== Description ==

<p>
Build highly customizable PDF documents from any combination of pages and posts, or add a link to any page to download a PDF of that post.                  
</p>
<p>
Kalin's PDF Creation Station will add two menus to your WordPress admin. One under tools and one under settings. 
</p>
<p>In the tools menu you will be able to build PDF (or .html and .txt) documents from any combination of pages and posts. Select any or all pages and posts from your site, then add a custom title page, end page and custom headers. Adjust font sizes, file names, or insert information such as timestamps, excerpts and urls through the use of shortcodes. Save your document as a template for future use or create your PDF, TXT or HTML documents. All created files will display in a convenient list for you to delete, download or link to.
</p>
<p>
In the settings menu you will be able to setup options for a link that can be automatically added to some or all pages and posts. This link will point to an automatically generated PDF version of that page. Most of the same customization options are available here that are available in the creation tool, like title page and font size, as well as the option to fully customize the link itself. On individual page/post edit pages you will be able to override the default link placement so you can show links on some pages and not on others. PDF files are saved to your server so they only need to be created once, reducing server load compared to other PDF generation plugins that create a new PDF every time the link is clicked. The PDF file is automatically deleted when a page or post is edited, so the PDF always matches the page.
</p>
<p>
Plugin by Kalin Ringkvist at http://kalinbooks.com/
</p>
<p>
Plugin URL: http://kalinbooks.com/pdf-creation-station/
</p>
<p>
Bugs: http://kalinbooks.com/pdf-creation-station/known-bugs/ If you have any problems please comment on this page or email Kalin at kalin@kalinflash.com and I'll do my best to figure out your issues.
</p>
<p>
Future features: http://kalinbooks.com/pdf-creation-station/pdf-creation-possible-features/ If you have feature requests or are interested in my plans for PDF Creation Station
</p>

<p>
Tools page demo:
[youtube http://www.youtube.com/watch?v=cPaz3X4RXbQ]
</p>

<p>
Settings page demo:
[youtube http://www.youtube.com/watch?v=OAi1W-77S9g]
</p>

== Installation ==

1. Unzip `kalins-pdf-creation-station.zip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Find the PDF Creation Station menu under 'tools' and begin creating custom PDF documents of your website. Or go into the PDF Creaction Station menu under 'settings' and begin setting up the options for automatic individual page generation.

Note: probably requires PHP 5.2 and WordPress 3.0.

== Frequently Asked Questions ==

= Where do I find instructions and help? =

In both the settings and tool pages you can find help in the built-in wordpress help dropdown menu in the upper right side of the screen. If you continue to have problems, feel free to make a comment at http://kalinbooks.com/pdf-creation-station/. Try to include as much specific information as you can, especially if you think you've found a bug.

= Font, href or align tags don't work in inserted HTML. =

Make sure to use double quotes instead of single quotes when inserting arbitrary HTML attributes because of a bug with the core PDF creation engine (TCPDF).

== Screenshots ==

1. A portion of the creator tool that creates custom PDF documents from multiple pages and posts. This shows the post list section and some collapsed sections.
2. This is the main section of the multi-create tool showing options and the list of your created files.
3. The main section of the settings page where you set up PDF links that can appear on every page and post.
4. A different shot of the settings page.
5. The configuration box for the widget.
6. A shot of the box that is added to the page/post edit page to specifically control link placement for each page.

== Changelog ==

= 0.7 =
*  First version. Beta. Includes basic functionality for tool menu and settings menu including page order, title page, include images, font size, ajaxified interface, shortcodes, etc.

= 0.8 =
*  Added a create now button for someone who had trouble getting the jquery page-ordering popup to work.

= 0.9 =
* Moved some initialization functions into kalins_pdf_init() so that they are only run in the admin.
* Added new security check to make sure the plugin pages are only being run from within wordpress.
* Added 'default' option to page/post edit box so you aren't forced to make a permanent choice when saving a page/post.
* Added checkbox at the bottom of settings page to turn off the plugin's deactivation routine.
* changed default link placement to 'none' so that links are not added to pages/posts until the user authorizes it

= 0.9.1 =
* Changed all code to direct, and/or create the kalins-pdf folder inside the uploads directory instead of placing the PDF files in the plugin directory to squash the bug where files were deleted upon plugin upgrade.

= 0.9.2 =
* Fixed a PHP error thrown on the Menus page when in debug mode. Got rid of warnings for previous upgrade problem.

= 1.0 =
Added [post_permalink] shortcode. Also added "Use post slug for PDF filename" and "Show on home, category, tag and search pages" options on settings page. Changed the clunky character count to word count, which should now function more accurately.

= 1.1 =
Bug fix. I broke the PDF creation popup with v 1.0 and had to make an emergency fix.

= 1.2 =
removed testing alerts

= 2.0 =
* Added support for custom post types
* moved the code identifying the default PDF directory and URL into a few constants at the top of kalins-pdf-creation-station.pdf, so that hackers can easily change them to whatever they want. Added example code that can be un-commented to change the PDF directory to use the base domain of your site instead of the wordpress uploads directory.
* Fixed minor bug where 'reset defaults' on the settings page wasn't refreshing the 'post slug' and 'show on home' checkboxes
* Added "create all" button on settings page
* Added "automatically generate PDFs on publish and update" option on settings page
* changed blockquote code so it uses the 'pre' tag because it was the only way to get TCPDF to actually display anything since it doesn't want to render blockquotes or tables properly
* added post_excerpt code to use "wp_trim_excerpt", which doesn't appear to be functioning anymore -- then changed to manually extract 250 characters from the page content
* added option to run other plugin shortcodes to both settings and tool pages
* added option to convert embedded youtube videos into a link to that video
* added 'format' parameter to all time shortcodes for total custom date/time formatting
* added 'length' parameter to the post_excerpt shortcode to set character count of the excerpt

= 2.0.1 =
* Bug fix. This plugin no longer destroys all other admin help menus.

= 2.0.2 =
* Bug fix. PDFs now properly generate when using 'quick edit' on posts when 'auto generate' is turned on.

= 3.0 =
* upgraded TCPDF engine. This should improve image handling and also fixes the blockquotes issue, so blockquotes no longer need to use a monospaced font
* added option to automatically construct a Table of Contents page in the creator tool
* added post_meta shortcode for post's custom fields
* added option on Tool page to turn off automatic page breaks between posts
* added ability for hackers to translate/change the word 'page' to whatever they want
* added option to run other plugin content filters
* added post category(s) shortcode
* added post tags shortcode
* added option to convert Vimeo videos (both object and iframe style embeds)
* added Ted Talk video link conversion option
* YouTube link conversion now works for iframe style embeds as well as objects
* added ability for hackers to change the order of the post list on the tool page
* added post comments shortcode. Includes easy way for PHP coders to fully customize the display
* added post parent shortcode
* added post thumbnail shortcode

= 3.1 =
* Fixed 'create PDF' popup in Firefox
* Changed default font to Times and default size to 12, which improves overall look/feel of documents

= 3.2 =
* Upgraded TCPDF engine to 6.0.061. PDF compiling should be faster and more reliable now. We may get other bonuses with this upgrade as well.
* Added new options for post author so you are no longer stuck with just the login name
* Expanded functionality for post thumbnail shortcode

= 4.0 =
* Migrated front-end into AngularJS and Bootstrap, away from jQuery.
* Improved appearance of user interface for both tool and settings pages.
* UI should now function much better on mobile devices.
* Added dynamic sorting and filtering to help you find the correct page or post on the tools page.
* Sorting of pages in document on tool page has been improved.
* Added same dynamic sorting and filtering to the list of created documents.
* Added ability to create .txt and .html files as well as .pdfs in the tool page.
* Added a widget to allow you to have the post's link in the sidebar without hacking your theme.
* Added a box in the menu section to allow you to easily link to your files created in the tool page.
* Fixed a minor bug with apostrophes in the filename on the tool page.

= 4.1 =
* Added the ability to save documents on the tool page as templates for future use. Includes your selected pages.
* Added tags and categories to the pages and posts datagrid on the tool page.
* Cleaned up backend handling of AJAX calls, so there is no longer a maximum input length for your options. Should hopefully be less prone to errors.
* Divided contextual help menus on settings and tool pages into two tabs.
* Upgraded the TCPDF library from 6.0.061 to 6.0.099. I think this fixed an issue with transparency in .png images.
* YouTube, Vimeo and Ted video link conversion now works with the standard WordPress embed.
* YouTube, Vimeo and Ted video link conversion should now work with single quotes and urls without the 'www'.

= 4.2.0 =
* Table of Contents is no longer hardcoded to page 2, so multi-page title pages and empty title pages now work correctly.
* Added an Add All button to the tool page's post list.
* Added a Remove All button to tool page's My Document post list.
* Added selector checkboxes so you can choose which columns to display in your post list on the tool page.
* Added 'Author' as a post list column.

= 4.2.1 =
* Now showing the correct YouTube demo video for version 4.2 on the tools page. I thought YouTube would let me overwrite the old video when I released 4.2 but it forced me to create a new one.
* Updated the screenshots for WordPress.org.

= 4.2.2 =
* tool and admin pages are now correctly loading JavaScript and CSS files on sites using https

= 4.2.3 =
* added menu_order to the tool page post list so you can easily order pages the same as you have in your menus.

== Upgrade Notice ==

= 0.7 =
First version. Beta. Use with Caution.

= 0.8 =
No point in upgrading unless you have problems with the Create PDF! button

= 0.9 =
Slight overall blog performance increase. Minor security improvement. New 'Use default' option on page/post edit screen. New feature: disable database cleanup upon plugin deactivation

= 0.9.1 =
Bug fix: After this, your PDF files should not disappear after future plugin upgrades.

= 0.9.2 = 
Not a terribly important release.

= 1.0 =
I broke this release. Move on to next version.

= 1.1 =
Bug fix. Added a couple new little features. Character count is now Word Count. You will need to update your settings.

= 1.2 =
removed testing alerts

= 2.0 =
A few new features. Default formatting on Date/time shortcodes changed a little with the new formatting possibilities.

= 2.0.1 =
My sincerest apologies to everyone who has been wondering what the hell happened to their help menus.

= 2.0.2 =
Bug fix. PDFs now properly generate when using 'quick edit' on posts when 'auto generate' is turned on.

= 3.0 =
Some new shortcodes, features and other improvements. New Table of Contents feature. Better image handling, improved integration with other plugins.

= 3.1 =
Firefox fix and PDF font change to Times so PDFs should look a little better.

= 3.2 =
After all this time, finally releasing a small update. Should work a little faster. New author and thumbnail options.

= 4.0 =
Major user interface redesign. Improved page/post sorting on the tool page. Added custom menu and widget support. Added .txt and .html creation ability.

= 4.1 =
Added ability to save templates for future use. Also: code cleanup; updated TCPDF library; improved YouTube, Vimeo and Ted Talk link conversions, added categories and tags to tool's post list.

= 4.2.0 =
Improved handling of multi-page and empty title pages. Added convenience features for sorting and adding posts on the tool page.

= 4.2.1 =
Nothing important unless you didn't get the 4.2 update. This one is just a fix for the link to the demo YouTube video on the tools page.

= 4.2.2 =
* tool and admin pages are now correctly loading JavaScript and CSS files on sites using https

= 4.2.3 =
* added menu_order to the tool page post list so you can easily order pages the same as you have in your menus.

== About ==

If you find this plugin useful, please pay it forward to the community.

