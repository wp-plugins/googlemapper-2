=== GoogleMapper ===
Contributors: Hank Pantier, Sylvain Saucier
Donate link: http://open-source-ideas.com/googlemapper
Tags: store locator, google maps
Requires at least: 2.6
Tested up to: 2.7
Stable tag: 2.0.3

This plugin allows a Wordpress Site Admin to enter locations of stores etc into the db.  The information is then displayed on the front end
using the google maps interface. You can enter a starting location to get the closest office and directions to get there.

== Description ==

This plugin allows a Wordpress Site Admin to enter locations of stores etc into the db.  The information is then displayed on the front end
using the google maps interface.  The user can enter a starting location in the search field, the system will then find the closest location from 
it's starting point and display the path and steps necessary to get to that location. If many locations are available in a similar range range, 
the plugin will request the driving distance between the two closest locations. It help to avoid the pitfall of getting an office across a river 
which may take a lot of driving to get to.

This plugin requires a valid Google Maps API key (free) from Google.  If you do not have an API key you may get one here.
http://code.google.com/apis/maps/signup.html

The actual google maps functionality is based on GoogleMapAPI - A library used for creating google maps. Which can be found here
http://www.phpinsider.com/php/code/GoogleMapAPI/

== Installation ==
1. Unzip and upload "googlemapper-2" to the "/wp-content/plugins/" directory
1. Activate the plugin through the "Plugins" menu in WordPress
1. Place `<?php if(function_exists('gm_show_map')) gm_show_map(); ?>` in your templates

== Frequently Asked Questions ==

= Do I have to know the coordinates of the locations I am adding =

No. The system will ask Google for the coordinates before it's added to the database.

= What features will be included in the next version =

1. Multilingual support
2. Creation of pages for each location
3. Capability of creating custom maps with different datasets, size and features
4. Embedding custom maps in pages and posts with : [gmapper class="defined_in_admin_panel" data="defined_value_in_your_script"]

= I want to add new features, what should I do =

You can download and modify the plugin as you wish, as long as you respect the terms of the GPL licence.
Please contact the developpers to ensure nobody else is working on the features you want to work on.

== Screenshots ==

1. Locator usage in a web page
2. First page of administration panel
3. Locations management
