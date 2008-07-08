=== wp-classified ===

name: wpClassified Wordpress plugins version 1.1.1-a
Contributors: Mohammad Forgani
Requires at least: 2.5
Tested up to: 2.5
Stable tag: 1.1.0
Tags: ads, adsense, classifieds
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


Make sure the folder images have the correct writing permissions.
Set the permission of the directory "wp-content/plugins/wp-classified/images" to 777. 



== Upgrade Instructions  ==

   1. Deactivate the plugin from your Wordpress admin panel.
   2. Backup Database tables *_wpClassified_* and files
   2. Upload the files to your /wp-content/plugins/ folder and overwrite the existing file.
   3. Activate the plugin from your Wordpress admin panel.
   4. Please Note: in ‘Classified Options‘ page in admin interface please check that all the required fields are filled in and save!


== Frequently Asked Questions ==

Uninstalling the plugin:

For uninstalling deactivate the plugin in the WordPress admin menu and 
delete the wpClassified directory from the /wp-content/plugins/ directory and the page and tables, 
which are installed by the plugin with drop table in phpMyAdmin.


== Screenshots ==

demo: http://www.bazarcheh.de/?page_id=92


== History ==

== Changelog ==

Changelog:

Changes in 1.1.1-a - June 20/2008
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
RewriteRule ^classified/([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/? /index.php?pagename=classified&_action=$1&lid=$3&asid=5&aid=$6 [QSA,L,R,NS]
</IfModule>
# END WordPress





have fun
Regards from Isfahan ;-)
Mohammad Forgani
Juli 03/2008

