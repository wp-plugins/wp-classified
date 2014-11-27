=== wp-classified ===
name: wpClassified Wordpress plugins Version: 1.4.3
Contributors: Mohammad Forgani
Donate link: http://www.forgani.com/root/wpclassified-plugins/
Requires at least: 3.1.x
Tested up to: 4.0.x
Stable tag: 1.2
Tags: ads, adsense, classifieds, classified, wpclassifieds, wpclassified, wp-classified, blackboard


== Description ==

This plugin allows you to add a simple information & advertising blackboard or classified page in to your wordpress blog.

The plugin has been successfully tested on Wordpress version 3.1 with default and unchanged Permalink structure. It may work with earlier versions too, but I have not tested yet.

Please test the plugin with your theme on a test or a local machine, if the test is successful, then install it on the production machine.

Demo link: http://www.forgani.com/classified/


== Installation ==

Please make a backup of files and database and test this plugin before using it in production.
Please test the plugin on your local machine before you install it in your production environment.

Once again, I take no responsibility...

The below section describes how to install the plugin and get it to work.

Please test the plugin with your theme on a test machine or a local machine,
if the test is successful then install it on the production machine.

1. Extract files
2. Upload 'wp-classified/' folder to your './wp-content/plugins/' directory

You also have to create a public directory under wp-content as followed:

wp-content/public/wp-classified/

and chmod the public and cache directories to 777 (writable by the webserver.)
e.g. 
chmod -R 777 wp-content/public

wp-content/public/wp-classified/
wp-content/plugins/wp-classified/cache

3. Login to the administration and go to "admin panel/plugins" and activate the plugin
4. Go to the "Manage" tab of your WP. 
You have now there a new tab called "wpClassified" to configure the plugin.


== Upgrade Instructions  ==

   1. Deactivate the plugin from your Wordpress admin panel.
   2. Backup Database tables *_wpClassified_* and files
   2. Upload the files to your /wp-content/plugins/ folder and overwrite the existing file.
   3. Activate the plugin from your Wordpress admin panel.
   4. Please Note: in 'Classified Options' page in admin interface please check that all the required fields are filled in and save!



== Upgrade Notice ==

Manual Process

You will have to:

* Download the latest plugin version
* Uncompress the file 
* Deactivate the plugin you currently have on your website (Admin Dashboard->Plugins->Deactivate)
* Upload/Replace the plugin in your wp-content->plugins
* Reactivate the plugin


== Database Upgrade ==

Upgrading wpClassified tables from version 1.1.x to 1.2.x

Using phpMyAdmin (or other database manager) run these sql queries on your existing database.

- So first start the phpMyAdmin console...
- Select the right Database
- Click on "Export" tab and choose the file upgrade1_1to1_2.sql and Go



== Frequently Asked Questions ==

Uninstalling the plugin:

For uninstalling deactivate the plugin in the WordPress admin menu and 
delete the wpClassified directory from the /wp-content/plugins/ directory and the page and tables, 
which are installed by the plugin with drop table in phpMyAdmin.


== Screenshots ==

http://forgani.com/index.php?pagename=classified (1.3) 

== History ==

== Changelog ==

= last changes =
Nov 18/11/2014
- Fixed Security Vulnerabilities

Jan 27/03/2011
- fixed for WP version 3.1
- update the Widget Sidebar Menu
- improved categories & forwards links in main page 

= version 1.4 =
Jan 22/01/2010
- fixed the email address checker and added the missing wpcSyncCount function
- reduce the width of a textarea element (to the cols=50).

Jan 17/01/2010
fixed for plugin auto-upgrade 
Note: This bugfix release hove to install Manually.
- fixed for the plugin auto-upgrade. (must test with the next coming version)
- moved directories public resources to wp-content

= version 1.3 =

Changes 1.3.1-a - Jan 20/01/2009
- It covers changes between WordPress Version 2.6 and Version 2.7
- fixed the widget control
- fixed the login problem for wmpu and buddypress
- include the links of photo to the last ads's list
- NEW: You can now place the last ads history on the sidebar as a widget
- extending the Administration Interface
- Modify to expanded and collapses the Categories
- Modify to show the last post in footer
- Modify to display-style classified ads in one column
- Added the ad images viewer
- Allowed more images per ad
- All the pages using templates
- Added style sheet for page layout 
- add Google AdSense for Classifieds
- fixed language

= version 1.2 =

Changes 1.2.0-e - August 10/2008
User Side
- implement the conformation code (captcha)
- added sent to his friend's button
- added language file (The work is Not Finished!) 
- users can send an Ad to a friend.
- fixed search problem
- fixed to image showing by editing and setting.
- added two directory within the “/images” directory cpcc and topic. You will need to make the folders writable (chmod 777).
- expiration notice reminders to users (The work is Not Finished!) 
- implement the maximum character limit

= version 1.1 =

Changes in 1.1.1 - June 20/2008
- fix the search function
- implement RSS Feeds
- admin email notification

Changes in 1.1.0-b - May 15/2008
- add remove ads that are over x days old 
- fix some bugs

Changes in 1.1.0-a - May 12/2008
- update delete/modify ads function .
- added Move ads function to admin interface.
- fixed some issue which are posted to me.
- using Permalinks. Example to update .htaccess Rewrite Rules.

Changes in 1.0.0 - April 01/2008
- fix bugs
- implement a new structure
- update to display the links to ads at the top of page 


To support Permalink structure:
Example for htaccess code to redirect to wpClassified

You need an .htaccess file that is created/modified by wordpress via the Permalink/mod-rewrite option. 

By using the "/%postname%/" or "/%category%/%postname%/" as permalink
structure the plugin must work correctly and you do not need change anything.

For something else please edit the .htaccess file in the root folder of your Wordpress.
You can edit the .htaccess file by FTP.
You use the default .htaccess file and modify the file as follow:
The redirect should look something like this

### DO NOT touch your .htaccess file. ###
### By default, you do not need to touch the .htaccess file. ####

# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
###
#RewriteRule . /index.php [L]
###
RewriteRule !classified/ /index.php [L]
RewriteRule ^classified/([^/\(\)]*)/?  /index.php?pagename=classified [QSA,L,R,NS]
</IfModule>
# END WordPress

have fun

Mohammad Forgani, Oh Jung Su
March 25/2011
