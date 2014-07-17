=== Plugin Name ===
Contributors: cyberws
Donate link: http://www.cyberws.com/cleverwise-plugins/
Tags: downloads, download, download management, download system, files, file, file management, file system
Requires at least: 3.0.1
Tested up to: 3.9.1
Stable tag: 1.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Advanced file download system that allows multiple sections, categories, and download pages, plus advanced access control and anti-bot technology.

== Description ==

<p>This is an advanced download/file sharing management system.  This plugin allows multiple sections with each section being an unique file download area.  This gives you the ability to display files that have nothing to do with each other in different areas on your Wordpress site.  In otherwords you can have multiple download areas.  However if you only want one download area no problem; simply use a single (one) section.</p>

<p>However sections aren't the only organizational structure because in each section you assign files to categories.  So for example you could have a Dogs and Cats download section on your site.  Then have categories assigned to each section like Food Ideas, Exercise Ideas, Medical Ideas, etc.  However to make management easier when you add a category it is available to use in all sections whether that is one or one hundred.  If a section has no files in a specific category that category is simply not displayed.</p>

<p>Oh and if you are wondering files CAN be assigned to MULTIPLE sections.  In addition you are able to write lengthy file descriptions.  Plus the plugin automatically calculates the size of the download file and MD5 hash, for those wanting to verify their download, and displays it with the file information.  You may even add optional author information such as name, email address, and URL.  Individual file records can even be turned off/hidden for those times when you aren't ready to delete the record but don't want visitors accessing the information.</p>

<p>The goodness doesn't stop there, far from it.  The system allows your visitors to run searches and view top downloads.  However only results from that section will be displayed! That's right any searches in the Dog section will only show files in the Dog section.  Pretty awesome! Right?</p>

<p>There are many other advanced features too like anti-bot technology to help prevent bots from downloading your files.  Oh and this anti-bot technology will be unique to your site and not something that is cookie cutter.  This will make it harder for bots to lock on because no two installations of this plugin will be exactly the same.  Also editor level accounts have the ability to manage the system.  I know what you are thinking can I block editors?  Absolutely!  You may block all editors or even better only allow specific editors!</p>

<p>Finally there are other features in the admin panel such as download counts and the ability to search through your files including limiting by category and/or section.  Yup all this download management goodness in your familiar, comfortable, and trusty Wordpress panel.</p>

<p>Language Support: While this plugin supports any language being used for section names, category names, download names, download descriptions, etc English is used in key areas.  It is planned to eventually move these words into a language file/area but for now its English only, sorry.</p>

<p>Live Site Preview: Want to see this plugin in action on a real live site? ArmadaFleetCommand.com has multiple sections and one is at <a href="http://www.armadafleetcommand.com/get-files?ssid=4">http://www.armadafleetcommand.com/get-files?ssid=4</a></p>

<p>Shameless Promotion: See other <a href="http://wordpress.org/plugins/search.php?q=cleverwise">Cleverwise Wordpress Directory Plugins</a></p>

<p>Thanks for looking at the Cleverwise Plugin Series! To help out the community reviews and comments are highly encouraged.  If you can't leave a good review I would greatly appreciate opening a support thread before hand to see if I can address your concern(s).  Enjoy!</p>

== Installation ==

<ol>
<li>Upload the <strong>cleverwise-share-files</strong> directory to your plugins.</li>
<li>In Wordpress management panel activate "<strong>Cleverwise Share Files</strong>" plugin.</li>
<li>A new menu option "<strong>Share Files</strong>" will appear on your main menu (under Comments).</li>
<li>Once you have loaded the main panel for the plugin click on the "<strong>Help Guide</strong>" link which explains in detail how to use the plugin.</li>
</ol>

== Frequently Asked Questions ==

= Is there a file size limit? =

This plugin doesn't limit the size of files.  The only limitations are that of your website infrastructure.

= Is there a limit on the number of files? =

This plugin doesn't limit the numbers of files.  The only limitations are that of your website infrastructure.

= Is there a web browser based uploader? =

No, there is none.  Why not? This system was originally designed for a site that has many files of at least 15MB and several over 200MB.  Since web browser uploading does NOT handle large files (100MB+) very efficiently it was decided to skip the inclusion of one and instead rely on FTP or sFTP.

= How can I handle file uploading? =

One method is to use Wordpress' built in media management system to upload the actual files.  Do keep in mind PHP has a file upload size limit that many web hosts have set to 10MB or less.  Another idea is using the aforementioned FTP/sFTP.  Most hosting plans allow for multiple FTP accounts and the ability to specify which directory/folder an account is able to access.  This way if you had say three trusted users you could create three different FTP accounts which would only allow them to upload to their specific area of your website.

== Screenshots ==

1. screenshot-1.jpg

== Changelog ==

= 1.7 =
Fixed: Editor permission bug

= 1.6 =
Fixed: Shortcode in certain areas would cause incorrect placement

= 1.5 =
Fixed: Support and rating links

= 1.4 =
UI changes

= 1.3 =
Fixed: Anti-bot word/phrase bug causing errors

= 1.2 =
Altered framework code to fit Wordpress Plugin Directory terms

= 1.1 =
Modified to use the Cleverwise Framework<br>
Fixed: Incorrect download code error screen failed when another form was displayed

= 1.0 =
Initial release of plugin

== Upgrade Notice ==

= 1.7 =
Fixed editor permission bug
