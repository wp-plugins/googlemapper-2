<?php
/*
Plugin Name: Google Mapper 2
Plugin URI: http://www.publipage.com/wordpress/plugins/google-mapper2
Description: Use Google Maps to plot locations on your Wordpress Site. Users can find the closest location and get directions on the map. You need a <a href="http://code.google.com/apis/maps/signup.html">Google Maps API key</a> to use it. 
Version: 2.0.3
Author: Sylvain Saucier
Author URI: http://www.open-source-ideas.com/
*/

/*  Copyright 2008  Sylvain Saucier  (email : ssaucier@publipage.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// this is the function that runs to install the plugin
$GLOBALS['gm_api_version'] = "2.0.3";
$GLOBALS['gm_table'] = "google_mapper";

function gm_install ()
{
	global $wpdb;

   	$table_name = $wpdb->prefix . $GLOBALS['gm_table'];
   	if($wpdb->get_var("show tables like '$table_name'") != $table_name) 
	{
		$sql = "CREATE TABLE " . $table_name . " (
	  		gm_id BIGINT( 20 ) NOT NULL AUTO_INCREMENT  PRIMARY KEY  ,
	  		gm_name VARCHAR( 100 ) NOT NULL,
	  		gm_address VARCHAR( 255 ) NOT NULL,
	  		gm_address2 VARCHAR( 255 ) NOT NULL,
	  		gm_city VARCHAR( 75 ) NOT NULL,
			gm_state CHAR( 64 ) NOT NULL,
			gm_country CHAR( 255 ) NOT NULL,
			gm_zip VARCHAR( 10 ) NOT NULL,
			gm_lat DOUBLE NOT NULL,
			gm_lon DOUBLE NOT NULL,
			gm_description VARCHAR( 255 ) NOT NULL,
			gm_publish VARCHAR( 3 ) NOT NULL,
			gm_stamp TIMESTAMP NOT NULL
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		add_option("gm_api_version", $GLOBALS['gm_api_version']);
		add_option("gm_new_database", $sql);
	}
}


// upgrade check
/*
$installed_ver = get_option("gm_api_version");
if( $installed_ver != $GLOBALS['gm_api_version'] ) {
	$sql = "CREATE TABLE " . $table_name . " (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time bigint(11) DEFAULT '0' NOT NULL,
		name tinytext NOT NULL,
		text text NOT NULL,
		url VARCHAR(100) NOT NULL,
		UNIQUE KEY id (id)
	);";

     require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
     dbDelta($sql);

     update_option( "jal_db_version", $jal_db_version );
}
*/


// this adds the plugin name into the top level menus on the wordpress admin
add_action('admin_menu', 'gm_add_pages');
//add_action('activate_google-mapper.php', 'gm_install');
register_activation_hook(__FILE__, "gm_install");

// action function for above hook
function gm_add_pages() {
    // Add a new top-level menu (('Page title', 'Top-level menu title', 8, __FILE__, 'my_magic_function');):
    add_menu_page('Configuration', 'Google Mapper', 8, __FILE__, 'gm_toplevel_page');

    // Add a submenu to the custom top-level menu (add_submenu_page(__FILE__, 'Page title', 'Sub-menu title', 8, __FILE__, 'my_magic_function');):
    add_submenu_page(__FILE__, 'Add Location', 'Add Location', 8, 'add_locations', 'gm_add_location');
	add_submenu_page(__FILE__, 'Manage Locations', 'Manage Locations', 8, 'manage_locations', 'gm_manage_locations');
}

// mysql timestamp to date
function showTime($var){
	$date = date('Y-m-d', strtotime($var));
	$time = date('h:i:s a', strtotime($var));
	return $date . '<br />' .$time;
}

// gm_toplevel_page() displays the page content for the custom Test Toplevel menu - this page asks for the API key and allows a user to edit it
function gm_toplevel_page() {
    // variables for the field and option names     
	$hidden_field_name = 'gm_submit_hidden';

    // Read in existing option value from database
    $gm_api = get_option('gm_api');
	$gm_zoom = get_option('gm_zoom');
	$gm_icon = get_option('gm_icon');
	$gm_shadow = get_option('gm_shadow');
	
	// zoom level array
	$zoomLevel = array("1"=>"1 (world)", "2"=>"2", "3"=>"3", "4"=>"4 (country)", "5"=>"5", "6"=>"6 (state)", "7"=>"7",
	"8"=>"8", "9"=>"9", "10"=>"10", "11"=>"11", "12"=>"12", "13"=>"13", "14"=>"14 (city)", "15"=>"15", "16"=>"16 (location)");

    // See if the user has posted us some information if they did, this hidden field will be set to 'Y'
    if( $_POST[ $hidden_field_name ] == 'Y' ) {
        // Read their posted value
        $gm_api = $_POST['gm_api'];
		$gm_zoom = $_POST['gm_zoom'];
		$gm_icon = $_POST['gm_icon'];
		$gm_shadow = $_POST['gm_shadow'];

        // Save the posted value in the database
        update_option('gm_api', $gm_api );
		update_option('gm_zoom', $gm_zoom );
		update_option('gm_icon', $gm_icon );
		update_option('gm_shadow', $gm_shadow );

        // Put an options updated message on the screen
		?>
		<div class="updated"><p><strong><?php _e('Google Mapper configuration options have been saved.'); ?></strong></p></div>
	<?php
	}

    // Now display the options editing screen
    echo '<div class="wrap">';
    	// header
    	echo "<h2>Google Mapper >> Configuration</h2>";
    	
		// api key form
    	?>
		<form name="form1" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
		<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y" />
		<p>Enter your Google Maps API Key below.  Don't have a Google Maps API Key?  <a href="http://code.google.com/apis/maps/signup.html">Get one here.</a>
		<br /><strong><?php _e("API:"); ?></strong> <input type="text" name="gm_api" value="<?php echo $gm_api; ?>" size="90" /></p>
		
		<p>You can set the default zoom level for your map here
		<br /><strong><?php _e("Zoom Level:"); ?></strong> <select name="gm_zoom">
		<?php 
		foreach($zoomLevel as $k=>$v){
			echo '<option value="' . $k . '"';
			if($gm_zoom==$k) echo ' selected="selected"';
			echo '>' . $v . '</option>' . "\n";
		}
		?>
		</select></p>
		
		<p>To change Google's default icon, upload a new transparent .png to your web server and enter the path to that image here.  <a href="http://econym.googlepages.com/custom.htm">More Info here</a>.
		<br /><strong><?php _e("Icon Image:"); ?></strong> <input type="text" name="gm_icon" value="<?php echo $gm_icon; ?>" size="30" />
		<br /><strong><?php _e("Shadow Image:"); ?></strong> <input type="text" name="gm_shadow" value="<?php echo $gm_shadow; ?>" size="30" /></p>
		<hr />
		<p class="submit"><input type="submit" name="Submit" value="<?php _e('Update Options', 'gm_trans_domain' ) ?>" /></p>
		</form>
	</div>
<?php
}






// gm_sublevel_page() displays the page content for the first submenu of the custom Test Toplevel menu - this function manages locations
function gm_add_location() {

require("inc/GoogleMapAPI.class.php");

    global $wpdb;

	$hidden_field_name = 'gm_location_submit';
	require "inc/states.inc.php";

	// See if the user has posted us some information if they did, this hidden field will be set to 'Y'
    if( $_POST[ $hidden_field_name ] == 'Y' ) {
        // format posted values
        foreach($_POST as $varname=>$value){
			$$varname = $wpdb->escape($value);
		}
		$__formattedAddress = $gm_address . ", " . $gm_address2 . ", " . $gm_city . ", " . $gm_state . ", " . $gm_zip;
		$map = new GoogleMapAPI('map');
		$__geoCodeAddress = $map->geoGetCoords($__formattedAddress, 0);
		$__geoCodeAddressLat = $__geoCodeAddress['lat'];
		$__geoCodeAddressLon = $__geoCodeAddress['lon'];
        // Save the posted value in the database
        if($gm_id){
			$wpdb->query("UPDATE $wpdb->prefix" . $GLOBALS['gm_table'] . " SET gm_name='$gm_name', gm_address='$gm_address', gm_address2='$gm_address2',
			gm_city='$gm_city', gm_state='$gm_state', gm_zip='$gm_zip', gm_lat='$__geoCodeAddressLat', gm_lon='$__geoCodeAddressLon', gm_description='$gm_description', gm_publish='$gm_publish'
			WHERE gm_id=$gm_id");

	    }else{
	    	$wpdb->query("INSERT INTO $wpdb->prefix" . $GLOBALS['gm_table'] . " (
				gm_name, 
				gm_address, 
				gm_address2, 
				gm_city, 
				gm_state, 
				gm_country, 
				gm_zip, 
				gm_lat, 
				gm_lon, 
				gm_description, 
				gm_publish
				)
	    	values (
				'$gm_name', 
				'$gm_address', 
				'$gm_address2', 
				'$gm_city', 
				'$gm_state', 
				'', 
				'$gm_zip', 
				'$__geoCodeAddressLat', 
				'$__geoCodeAddressLon', 
				'$gm_description', 
				'$gm_publish'
				)");
	    }
	
        // Put an options updated message on the screen
		?>
		<div class="updated"><p><strong>
		<?php if($gm_id) _e('Location Updated!'); 
		else  _e('Location Was Added!');
		?>
		</strong></p></div>
	<?php
	}

    // Now display the options editing screen
    echo '<div class="wrap">';
    	// header
    	echo "<h2>Google Mapper >> Locations</h2>";
		echo "<p>Use the form below to manage locations. An asterisk (*) indicates a required field.</p>";
    	
		// add / edit location 
    	?>
		<form name="form1" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
		<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y" />
		<?php if($_GET['location']){ 
			// pull data from db 
			$location = $wpdb->get_row("SELECT * FROM $wpdb->prefix" . $GLOBALS['gm_table'] . " WHERE gm_id=".$_GET['location']);
		?>
			<input type="hidden" name="gm_id" value="<?php echo $_GET['location']; ?>" />
		<?php } ?>
		<table class="optiontable">
		<tr valign="top">
		<th scope="row">Location Name*:</th>
		<td><input name="gm_name" type="text" id="gm_name" value="<?php echo stripslashes($location->gm_name); ?>" size="40" /></td>
		</tr>
		<tr valign="top">
		<th scope="row">Address*:</th>
		<td><input name="gm_address" type="text" id="gm_address" value="<?php echo stripslashes($location->gm_address); ?>" size="40" /></td>
		</tr>
		<tr valign="top">
		<th scope="row">Address Cont:</th>
		<td><input name="gm_address2" type="text" id="gm_address2" value="<?php echo stripslashes($location->gm_address2); ?>" size="40" /></td>
		</tr>
		<tr valign="top">
		<th scope="row">City*:</th>
		<td><input name="gm_city" type="text" id="gm_city" value="<?php echo stripslashes($location->gm_city); ?>" size="40" /></td>
		</tr>
		<tr valign="top">
		<th scope="row">State*:</th>
		<td><select name="gm_state" id="gm_state">
		<?php
		foreach($statesArray as $k=>$v){
			echo '<option value="' . $k .'"';
			if($k == stripslashes($location->gm_state)) echo ' selected="selected"';
			echo '>' . $v . '</option>' . "\n";
		}
		?>
		</select></td>
		</tr>
		<tr valign="top">
		<th scope="row">Zip*:</th>
		<td><input name="gm_zip" type="text" id="gm_zip" value="<?php echo stripslashes($location->gm_zip); ?>" size="40" /></td>
		</tr>
		<tr valign="top">
		<th scope="row">Description:</th>
		<td><textarea name="gm_description" id="gm_description" rows="5" cols="38"><?php echo stripslashes($location->gm_description); ?></textarea></td>
		</tr>
		<tr valign="top">
		<th scope="row">Publish*:</th>
		<td><input name="gm_publish" type="checkbox" id="gm_publish" value="yes" <? if(stripslashes($location->gm_publish) == 'yes' || !isset($location->gm_publish)) echo 'checked="checked"'; ?> /></td>
		</tr>
		</table>
		<hr />
		<p class="submit"><input type="submit" name="Submit" value="<?php _e('Update Options', 'gm_trans_domain' ) ?>" /></p>
		</form>
	</div>
<?php
}





// gm_sublevel_page() displays the page content for the first submenu of the custom Test Toplevel menu - this function manages locations
function gm_manage_locations() {
    global $wpdb;

    // Now display the options editing screen
    echo '<div class="wrap">';
    	// header
    	echo "<h2>Google Mapper >> Manage Locations</h2>";
		
		if($_GET['action']){ // see if delete has been called 
			if($_GET['sure']){ // delete the listing 
				$wpdb->query("DELETE FROM $wpdb->prefix" . $GLOBALS['gm_table'] . " WHERE gm_id=".$_GET['location']);
			?>
				<div class="updated"><p><strong><?php _e('Location Deleted!'); ?></strong></p></div>
			<?php }else{
				$location = $wpdb->get_row("SELECT * FROM $wpdb->prefix" . $GLOBALS['gm_table'] . " WHERE gm_id=".$_GET['location']);
				echo '<p>You are about to delete "<strong>' . stripslashes($location->gm_name) . '</strong>".  Are you sure you want to do that?<br /><a href="admin.php?page=manage_locations&action=delete&sure=yep&location=' . $location->gm_id . '">yes</a> | <a href="admin.php?page=manage_locations">no</a></p>';
			}
		}
		
		
		// manage existing locations
    	?>
		<table class="widefat">
		<thead>
		<tr>
		<th scope="col" style="text-align: center">ID</th>
		<th scope="col">Name</th>
		<th scope="col">Address</th>
		<th scope="col">Description</th>
		<th scope="col">Published</th>
		<th scope="col">Coordinates</th>
		<th scope="col">Updated</th>
		<th scope="col" colspan="2" style="text-align: center;">Action</th>
		</tr>
		</thead>
		<tbody id="the-list">
			
		<?php
		$locations = $wpdb->get_results("SELECT * FROM $wpdb->prefix" . $GLOBALS['gm_table'] . " ORDER BY gm_publish, gm_id"); 

		foreach ($locations as $location) {			
			if($style=='alternate') $style='';
			else $style='alternate';
			?>
			<tr id='location-<?php echo $location->gm_id;?>' class='<?php echo $class;?>' valign="top">
			<th scope="row" style="text-align: center"><?php echo $location->gm_id;?></th>
			<td><?php echo stripslashes($location->gm_name);?></td>
			<td><?php
			echo stripslashes($location->gm_address);
			if(!empty($location->gm_address2)) echo '<br />' . stripslashes($location->gm_address2);
			echo '<br />' . stripslashes($location->gm_city) . ', ' . stripslashes($location->gm_state);
			echo '<br />' . stripslashes($location->gm_zip);
			?></td>
			<td><?php echo stripslashes($location->gm_description);?></td>
			<td><?php echo stripslashes($location->gm_publish);?></td>
			<td><?php echo stripslashes($location->gm_lat) . ", " . stripslashes($location->gm_lon);?></td>			
			<td><?php echo showTime(stripslashes($location->gm_stamp));?></td>			
			<td><a href='admin.php?page=add_locations&action=edit&location=<?php echo $location->gm_id;?>' class='edit'>Edit</a></td>
			<td><a href='admin.php?page=manage_locations&action=delete&location=<?php echo $location->gm_id;?>' class='delete'>Delete</a></td>
			</tr>
			<?php
		}
		?>
		</tbody>
		</table>
	</div>
<?php
}




// function add google maps info to header
function gm_header(){
	global $wpdb;
	
	echo '<!-- Google Mapper -->';
	require('inc/GoogleMapAPI.class.php');
	
	// pull configuration options from database 
    $gm_api = get_option('gm_api');
	$gm_zoom = get_option('gm_zoom');
	$gm_icon = get_option('gm_icon');
	$gm_shadow = get_option('gm_shadow');
	
    $map = new GoogleMapAPI('map');
    
	// enter google map key - set zoom level
    $map->setAPIKey($gm_api);
	if($gm_zoom) $map->setZoomLevel($gm_zoom);
	
	// change marker icons 
	if($gm_icon) $map->setMarkerIcon($gm_icon, $gm_shadow);

	// prepare the JavaScript Array with the informations about the locations
	$theScript = "<script><!--
	var Bureaux = new Array(";

    // query db and create some map markers
	$locations = $wpdb->get_results("SELECT * FROM $wpdb->prefix" . $GLOBALS['gm_table'] . " WHERE gm_publish='yes' ORDER BY gm_id"); 
	foreach ($locations as $location) {			
		//$map->addMarkerByAddress('621 N 48th St # 6 Lincoln NE 68502','PJ Pizza','<b>PJ Pizza</b>');

// Next block not needed anymore, we have the coordinates in cache

		$gm_show_address = stripslashes($location->gm_address);
		if($location->gm_address2) $gm_show_address .= '<br>' . stripslashes($location->gm_address2);
		$gm_show_address .= '<br />' . stripslashes($location->gm_city) . '<br />' . stripslashes($location->gm_state);
		$gm_show_description = stripslashes($location->gm_description);
		
		if( !($location->gm_lat == 0 && $location->gm_lon == 0) )
		{
			$map->addMarkerByCoords(stripslashes($location->gm_lon), stripslashes($location->gm_lat), $gm_show_name, "<b>$gm_show_name</b><br />$gm_show_address");
		}

		$theScript .= "[\"" . stripslashes($location->gm_name) . "\",\"" . stripslashes($location->gm_lat) . "\",\"" . stripslashes($location->gm_lon) . "\",\"" . $gm_show_address . "\", 99999999, 99999999" . "],";
	}   
	
	// end the script tag for the information array
	$theScript[strlen($theScript)-1] = " ";
	$theScript .= ");var locale = ";
	if (function_exists("the_template_locale")) $theScript .= "\"".the_template_locale(true)."\""; else $theScript .= "\"US_en\"";
	$theScript .= ";\n//-->\n</script>
<style>
.wpp_map_search_form
{
	margin-left:0px;
	margin-right:0px;
	margin-top:0px;
	margin-bottom:0px;
	padding-left:0px;
	padding-right:0px;
	padding-top:0px;
	padding-bottom:0px;
}

.wpp_map_search_input
{
	background:none;
	font-family:Verdana, Arial, Helvetica, sans-serif;
	font-size:12px;
	margin:1px;
	padding:0px;
	border:none;
	background:none;
	width:100%;
}

.wpp_map_search_text
{
	font-family:Verdana, Arial, Helvetica, sans-serif;
	font-size:12px;
	margin:1px;
	padding:0px;
	border:none;
	background:none;
}

.wpp_map_search_link
{
	font-family:Verdana, Arial, Helvetica, sans-serif;
	font-size:12px;
	margin:1px;
	padding:0px;
	border:none;
	background:none;
	cursor:pointer;
}

.wpp_map_search_left
{
background: url(/wpp_map_bg_left.gif) top left;	
}

.wpp_map_search_right
{
background: url(/wpp_map_bg_right.gif) top right;	
}

.wpp_map_border
{
background-color: black;	
}
</style>
<script><!-- Googlemapper Javascript support library, (C) 2008 Sylvain Saucier, GPL Licence

/*
 *	SECTION : VARIABLES
 */

var _log = \"Logfile - \" + \"<?PHP echo __FILE__ ?>\" + \"\\n\"; 
var _err = 0;
var _destinationsToTest = 0;	//Number of destinations to tests with google to get routes
var _searchCompleted = 0;		//Used to track the number of requests pending
var _map;						//Google map object
var _start;						//start address, user input
var _startx;					//latitude of _start returned by google maps api
var _starty;					//longitude of _start returned by google maps api
var _directions = new Array();	//array of GDirections objects

/*
 *	SECTION : SUPPORT FUNCTIONS
 */

function wlog(entry) {	//Add an entry to the log
	_log += entry + \"\\n\";
}

function showLog() {	//show raw log
	alert(_log); 
}

function sortByFlyDistance(a, b) {	//pass as argument to sort() the Bureaux Array, use after getFlyDistance populated the Bureaux Array, I should consider building an object.
    var x = a[4];
    var y = b[4];
    return ( (x < y) ? -1 : ( (x > y) ? 1 : 0 ) );
}

function sortByDriveDistance(a, b) {	//pass as argument to sort() the Bureaux Array, use in catchSearches after succelsull retreival of all routes, consider object.
    var x = a[5];
    var y = b[5];
    return ( (x < y) ? -1 : ( (x > y) ? 1 : 0 ) );
}

function debugBureauxFly(){	//append information of bureaux[] fly distance to the log
	for(i = 0; i < Bureaux.length; i++)
		wlog(\"    \" + Bureaux[i][0] + \" : \" + Bureaux[i][4]);
}

function resetBureaux(){	//reset information of bureaux[] array before a new search
	for(i = 0; i < Bureaux.length; i++)
	{
		Bureaux[i][4] = 99999999;
		Bureaux[i][5] = 99999999;
	}
}

function debugBureauxDrive(){	//append information of bureaux[] fly distance to the log
	for(i = 0; i < Bureaux.length; i++)
		if (Bureaux[i][5] < 99999999) //ignore uninitiated entries
			wlog(\"    \" + Bureaux[i][0] + \" : \" + Bureaux[i][5]);
}

function getFlyDistance(st, sg, dt, dg){
//wlog(\"getFlyDistance(\" + st + \", \" + sg + \", \" + dt + \", \" + dg + \")\");
	if (st > dt) var x = st - dt;	else var x = dt - st;
	if (sg > dg) var y = sg - dg;	else var y = dg - sg;
//	wlog(\"    return \" + Math.sqrt((x*x)+(y*y)));
	return Math.sqrt((x*x)+(y*y));
}

function closeDestinations(factor){ //calculate the number of location withing the range, Bureaux should be in a state sorted by fly distance
	var numberOfTests = 0;
	while(Bureaux[numberOfTests][4] < (Bureaux[0][4] * factor) && numberOfTests < 4 )
		numberOfTests++;
	return numberOfTests;
}

/*  SECTION : MAIN PROGRAM - ASYCHRONOUS DESIGN, PLEASE READ SCHEMA BEFORE DOING CHANGES
	startSearch() -> maps.google
	maps.google -> validateAndFind()
	launchSearchs -> maps.google
	maps.google -> catchSearchs()
	...as needed
	maps.google -> catchSearchs() */

function help(){
	alert(\"Veuillez entrer une adresse comprenant ville ou code postal, province et pays.\");
}


function startSearch(__start, __map){	//Initiate the asynchronous search sequence
	resetBureaux();
	document.getElementById('sidebar_destination').innerHTML = '';
	//initialize variables
	_destinationsToTest = 0;
	_searchCompleted = 0;
	_directions = new Array();
	_map = __map;
	_start = __start;
	
	//check user input
	if (!_start || !_map){ //check if objects exists
		if (_start == \"\"){ //check if there is a user input
			wlog(\"    missing address : No input from user.\");
			alert(\"Vous devez d'abord entrer une adresse.\");
		}
		else{ //what happens if jabascript objects are missing
			wlog(\"    bad _start or bad _map. FATAL ERROR\");
			alert(\"Objets invalides. Veuillez contacter le webmestre de ce site pour l'en informer.\");
		}
	}
	else{
		if (_start == \"!debug\"){ //if user input is \"!debug\" show debug infos instead of launching search
			showLog(); //display the log
		}
		else{ //everything is fine, initiate the search sequence
			wlog(\"startSearch(\" + _start + \", \" + _map + \")\");
			var geocoder = new GClientGeocoder();
			geocoder.getLatLng(_start, validateAndFind); //ask maps.google the latitude and longitude of source address, user input. Data is sent to validateAndFind() as a coords object.
		}
	}
	return false;
}

function validateAndFind(coords){ // Validate the coordinates returned by geocoder in startSearch(), find the closest offices, and initiate routes testing
	//check if coordinates are valid
	if (coords == null) { wlog(\"    start address cannot be not found\"); alert(\"Impossible de trouver .\"); }
	else //coordinates are valid
	{
	//write to log and initiate variables
	_startx = coords.lat();
	_starty = coords.lng();
	wlog(\"validateAndFind\" + _startx + ', ' + _starty + \"\");
	b = Bureaux;
	
		for (var i = 0; (b.length) > i; i++){ //populate drive distance
			b[i][4] = getFlyDistance(coords.lat(), coords.lng(), b[i][1], b[i][2]);
			//wlog(\"    \" + b[i][0] + \" : \" + b[i][4]);
		}
		b.sort(sortByFlyDistance); //sort the array by fly distance
		_destinationsToTest = closeDestinations( 3 ); //calculate if many locations should be considered, the parameter represent the radius of the search, as a factor in relation to the closest location found
		//log everything
		wlog(\"Array by fly distance\"); 
		debugBureauxFly();
		wlog(\"    closest office : \" + b[0][0]);
		//pass results to the function responsible launching the searchs
		launchSearches( _destinationsToTest, coords.lat(), coords.lng() , 3);
	}
}

function launchSearches(n, lat, lon, limit){ // [n = number of offices to try, limit = maximum number of searchs]
if (n > limit) {n = limit; _destinationsToTest = limit;} //limit the number of searchs if necessary
wlog(\"launchSearches(\" + n + \", \" + lat + \", \" + lon + \", \" + _map); //log progress
	while ( n-- > 0 ){ //process erevy entries
		//initiate a new GDirections object in the array
		_directions[n] = new GDirections();
		//load the present test directions
		_directions[n].load(\"from: \" + lat + \", \" + lon + \" to: \" + Bureaux[n][1] + \", \" + Bureaux[n][2], { \"locale\":locale, \"getPolyline\":false, \"getSteps\":false });
wlog(\"    from: \" + lat + \", \" + lon + \" to: \" + Bureaux[n][1] + \", \" + Bureaux[n][2]);
		//add listener to catch the results, good and bad
		GEvent.addListener(_directions[n], \"load\", catchSearchs);
		GEvent.addListener(_directions[n], \"error\", function() {
			var code = _directions[n].getStatus().code;
			var reason=\"Code \"+code;
			if (reasons[code]) {
				reason = \"Code \"+code +\" : \"+reasons[code];
			} 
			alert(\"Failed to obtain directions, \"+reason);
		});
	}
}


function catchSearchs(){
	if (++_searchCompleted == _destinationsToTest){ // Test if it is the last result to get
		for(i = 0 ; i < _directions.length ; i++ ){ // Load data to our Array Bureaux[x][5]
			Bureaux[i][5] = _directions[i].getDistance().meters; //the i value works because Bureaux and _directions have the same order
			Bureaux[i][6] = i;
		}
		for(i = 0 ; i < _directions.length ; i++ ){ // Delete unneeded GDirections objects
			_directions[i].clear();
		}
		Bureaux.sort(sortByDriveDistance);			// Sort by driving distance
		// log everything
		wlog(\"  Array by drive distance\");
		debugBureauxDrive();
		wlog(\"  The closest office is : \" + Bureaux[0][0]);
		//load the most optimal route
		var theroute = new GDirections(_map, document.getElementById(\"sidebar_destination\"));
		_map.clearOverlays();
		theroute.load(\"from: \" + _startx + \", \" + _starty + \" to: \" + Bureaux[0][1] + \", \" + Bureaux[0][2], { \"locale\":locale });
		document.getElementById('sidebar_destination').innerHTML += '<p>' + Bureaux[0][3] + '</p>';
	}
}

function HideContent(d) {
	if(d.length < 1) { return; }
	document.getElementById(d).style.display = \"none\";
}

function ShowContent(d) {
	if(d.length < 1) { return; }
	document.getElementById(d).style.display = \"block\";
}

function ReverseContentDisplay(d) {
	if(d.length < 1) { return; }
	if(document.getElementById(d).style.display == \"none\") { document.getElementById(d).style.display = \"block\"; }
	else { document.getElementById(d).style.display = \"none\"; }
}

//--></script>";
	

	// google maps code 
	$map->printHeaderJS();
	$map->printMapJS();
	echo '<!-- necessary for google maps polyline drawing in IE -->';
	echo '<style type="text/css">';
	echo '	v\:* {';
	echo '	behavior:url(#default#VML);';
	echo '}';
	echo '</style>';
	echo $theScript;
}


// function show google maps content 
function gm_show_map()
{
	$map = new GoogleMapAPI('map');
	
	?><form name="sourceAddress" class="wpp_map_search_form" onsubmit="startSearch(document.sourceAddress.address.value, map);return false;"><table border="0" cellpadding="0" cellspacing="0" align="center">
	<tr>
    	<td class="wpp_map_search_left"><nobr>
			<span class="wpp_map_search_text">&nbsp;De :&nbsp;</span><input onFocus="if (document.sourceAddress.address.value == 'Entrez votre adresse de d&eacute;part ici.') document.sourceAddress.address.value = '';" class="wpp_map_search_input" type="text" name="address" value="Entrez votre adresse de d&eacute;part ici." size="30" onfocus="" onblur="">
		</nobr></td>
        <td align="right" class="wpp_map_search_right"><nobr>
        	<a onclick="startSearch(document.sourceAddress.address.value, map);" class="wpp_map_search_link">Trouver</a> | <a onclick="help('map_search')" class="wpp_map_search_link">Aide</a>&nbsp;</nobr>
        </td>
    </tr>
    	<td colspan="2" width="920" class="wpp_map_border" style="margin:0px;padding:0px;"><img src="/null.gif" width="1" height="1" style="margin:0px;padding:0px;" /></td>
    </tr>
    </tr>
    	<td colspan="2" width="920" height="400">
<?PHP
	$map->printMap();

	//$map->printSidebar(); //make it an option
?>	
		</td>
    </tr>
    <tr>
    	<td colspan="2">
        	<div id="sidebar_destination"></div>
        </td>
    </tr>
</table>
</form>
<?PHP
	
}


// stick the gm_show_map info in the header 
add_filter('wp_head','gm_header');
?>