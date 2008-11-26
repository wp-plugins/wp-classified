=== wp-classified ===

name: wpClassified Wordpress plugins version 1.3.0-h
Contributors: Mohammad Forgani
Requires at least: 2.5
Tested up to: 2.5
Stable tag: 1.1.0
Tags: ads, adsense, classifieds, classified, wpclassifieds, wpclassified, wp-classified
Donate link: http://forgani.com/index.php/tools/wpclassified-plugins/

== Description ==

This plugin allows you to add a simple classified page in to your wordpress blog. 

The plugin has been create and successfully tested on Wordpress version 2.5.1 with 
default and unchanged Permalink structure.


== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Extract files
2. Upload 'wp-classified/' folder to your './wp-content/plugins/' directory
3. Login to the administration and go to "admin panel/plugins" and activate the plugin
4. Go to the "Manage" tab of your WP. 
You have now there a new tab called "wpClassified" to configure the plugin.


You will need to make the following folders writeable (chmod 777) :

/wp-classified/images
/wp-classified/images/topic
/wp-classified/images/cpcc



== Upgrade Instructions  ==

   1. Deactivate the plugin from your Wordpress admin panel.
   2. Backup Database tables *_wpClassified_* and files
   2. Upload the files to your /wp-content/plugins/ folder and overwrite the existing file.
   3. Activate the plugin from your Wordpress admin panel.
   4. Please Note: in 'Classified Options' page in admin interface please check that all the required fields are filled in and save!

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

Changelog:

Changes 1.3.0-g - Nov 26/11/2008
Bugfix release

Changes 1.3.0-f - Nov 05/11/2008
- fixed the login problem for wmpu and buddypress

Changes 1.3.0-e - Nov 03/11/2008
- include the links of photo to the last ads's list
- NEW: You can now place the last ads history on the sidebar as a widget
- extending the Administration Interface


Changes 1.3.0-b - Oct 10/10/2008
- Modify to expanded and collapses the Categories
- Modify to show the last post in footer


Changes 1.3.0 - Sep 10/09/2008

- Modify to display-style classified ads in one column
- Added the ad images viewer
- Allowed more images per ad
- All the pages using templates
- Added style sheet for page layout 


Changes 1.2.0-f - Sep 04/09/2008
add Google AdSense for Classifieds
fixed language


Changes 1.2.0-e - August 10/2008

User Side
- implement the conformation code (captcha)
- added sent to his friend's button
- added language file (The work is Not Finished!) 
- users can send an Ad to a friend.
- fixed search problem
- fixed to image showing by editing and setting.
- added two directory within the “/images” directory cpcc and topic. You will need to make the folders writable (chmod 777).

Admin Side
- expiration notice reminders to users (The work is Not Finished!) 
- implement the maximum character limit


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

Please edit the .htaccess file in the root folder of your Wordpress.
You can edit the .htaccess file by FTP.
You use the default .htaccess file and modify the file as follow:
The redirect should look something like this

# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
##########
#RewriteRule . /index.php [L]
##########
RewriteRule !classified/ /index.php [L]
RewriteRule !(classified|odlinks)/ /index.php [L]
RewriteRule ^classified/([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/? /index.php?pagename=classified [QSA,L,R,NS]
</IfModule>
# END WordPress





have fun
Regards from Isfahan ;-)
Mohammad Forgani, Oh Jung Su
Juli 03/2008

