=== Plugin Name ===
Contributors: daveeddydotcom
Donate link: http://viridian.daveeddy.com
Tags: music, ampache, widget, jquery, ajax
Requires at least: 3.0
Tested up to: 3.1
Stable tag: trunk

The Ampache Now Playing wordpress plugin displays information about the current playing song on an
Ampache server on wordpress.  The information is loaded with jQuery/Ajax which is beneficial if the Ampache server in question has a slow connection or is run on old hardware.

== Installation ==

1. Download the plugin and unpack to /wp-content/plugins/
2. Enable the plugin in Wordpress
3. Configure the plugin in Wordpress by going to Settings > Ampache Now Playing
4. Enable the Widget and you're good to go

== Frequently Asked Questions ==

= I put my username and password in correctly but it says login failed! =
This is likely an ACL issue on the Ampache side, see http://ampache.org for more details on Ampache ACL's

= I updated Ampache and the album art is wrong. =
Clear the album art from Settings > Ampcahe Now Playing > Clear Album Art

== Screenshots ==

A live demo for this can be seen at http://viridian.daveeddy.com

== Changelog ==

= 1.2 =
Fixed some issues with htmlentities and urlencoding data for use on the site
Added the ability to have jQuery/Ajax update the plugin if a song changes without a page refresh

= 1.1.2 =
Made plugin compatible with sites that use https admin pages

= 1.1.1 =
Added an animated gif for the ajax loader

= 1.1 =
The information is now loaded in using Ajax to not slow down a website if the connection
to the Ampache server is slow or the Ampache server is down

= 1.0.1 =
Updated plugin URI for more info

= 1.0 =
Initial Commit

== Upgrade Notice ==

= 1.2 =
Fixed some issues with htmlentities and urlencoding data for use on the site
Added the ability to have jQuery/Ajax update the plugin if a song changes without a page refresh

= 1.1.2 =
Made plugin compatible with sites that use https admin pages

= 1.1.1 =
Added an animated gif for the ajax loader

= 1.1 =
The information is now loaded in using Ajax to not slow down a website if the connection
to the Ampache server is slow or the Ampache server is down

= 1.0.1 =
Updated plugin URI for more info

= 1.0 =
Initial Commit
