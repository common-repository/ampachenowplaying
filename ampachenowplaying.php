<?php
/*
Plugin Name: Ampache Now Playing
Plugin URI: http://viridian.daveeddy.com/other/wordpressdrupal
Description: Ampache Now Playing will display the current playing song from an Ampache server (see http://ampache.org) on wordpress.
Version: 1.2
Author: Dave Eddy
Author URI: http://www.daveeddy.com
Credits: Michael Zeller
License: GPLv3
 */

### BEGIN LICENSE
## Copyright (C) 2010 Dave Eddy <dave@daveeddy.com>
## This program is free software: you can redistribute it and/or modify it
## under the terms of the GNU General Public License version 3, as published
## by the Free Software Foundation.
##
## This program is distributed in the hope that it will be useful, but
## WITHOUT ANY WARRANTY; without even the implied warranties of
## MERCHANTABILITY, SATISFACTORY QUALITY, or FITNESS FOR A PARTICULAR
## PURPOSE.  See the GNU General Public License for more details.
##
## You should have received a copy of the GNU General Public License along
## with this program.  If not, see <http://www.gnu.org/licenses/>.
#### END LICENSE
#

// load the ampache API functions
require_once('ampachenowplaying-api.php');

function widget_ampache_now_playing($args) {
	extract($args);
	global $wpdb;
	_create_initial_tables();
		
	$autoupdate = get_option('ampachenowplaying_autoupdate', false);

	echo $before_widget;
	echo $before_title;
	_e("Ampache Now Playing");
	echo $after_title;
	echo '<div id="ampachenowplaying_block_ajax"><img src="data:image/gif;base64,R0lGODlhEAAQAPIAAP///wAAAMLCwkJCQgAAAGJiYoKCgpKSkiH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAADMwi63P4wyklrE2MIOggZnAdOmGYJRbExwroUmcG2LmDEwnHQLVsYOd2mBzkYDAdKa+dIAAAh+QQJCgAAACwAAAAAEAAQAAADNAi63P5OjCEgG4QMu7DmikRxQlFUYDEZIGBMRVsaqHwctXXf7WEYB4Ag1xjihkMZsiUkKhIAIfkECQoAAAAsAAAAABAAEAAAAzYIujIjK8pByJDMlFYvBoVjHA70GU7xSUJhmKtwHPAKzLO9HMaoKwJZ7Rf8AYPDDzKpZBqfvwQAIfkECQoAAAAsAAAAABAAEAAAAzMIumIlK8oyhpHsnFZfhYumCYUhDAQxRIdhHBGqRoKw0R8DYlJd8z0fMDgsGo/IpHI5TAAAIfkECQoAAAAsAAAAABAAEAAAAzIIunInK0rnZBTwGPNMgQwmdsNgXGJUlIWEuR5oWUIpz8pAEAMe6TwfwyYsGo/IpFKSAAAh+QQJCgAAACwAAAAAEAAQAAADMwi6IMKQORfjdOe82p4wGccc4CEuQradylesojEMBgsUc2G7sDX3lQGBMLAJibufbSlKAAAh+QQJCgAAACwAAAAAEAAQAAADMgi63P7wCRHZnFVdmgHu2nFwlWCI3WGc3TSWhUFGxTAUkGCbtgENBMJAEJsxgMLWzpEAACH5BAkKAAAALAAAAAAQABAAAAMyCLrc/jDKSatlQtScKdceCAjDII7HcQ4EMTCpyrCuUBjCYRgHVtqlAiB1YhiCnlsRkAAAOwAAAAAAAAAAAA==" alt="Ajax" width="18" height="18" />Loading...</div>'; // base64 encoded ajax gif
?>

	<script type="text/javascript">
	var $j = jQuery.noConflict();
	var $ampachenowplaying_div = $j('#ampachenowplaying_block_ajax');
	var ampachenowplaying_div_html = '';
	function ampachenowplaying_getdata(is_first) {
		data = {
			action: 'ampachenowplaying',
			ampachenowplaying: 'True'
		};
		$j.post('<?php echo str_replace('https://','http://',admin_url('admin-ajax.php'));?>', data, function(response) {
			if (is_first == true) { // this is the first time the page has loaded
				$ampachenowplaying_div.slideUp('slow', function() {
					$ampachenowplaying_div.html(response).slideDown('slow');
					ampachenowplaying_div_html = response;
				});
			} else { // the loop is checking for new songs
				if (response == ampachenowplaying_div_html) {
					// do nothing
				} else {
					$ampachenowplaying_div.animate({'opacity' : '0'}, 'slow' , function() {
						$ampachenowplaying_div.html(response).animate({'opacity' : '1'}, 2000);
						ampachenowplaying_div_html = response;
					});
				}
			}
		});
	}

	$j(document).ready(function($) {
		ampachenowplaying_getdata(true);
<?php
	if ($autoupdate == true) {
		echo "\t\tsetInterval(ampachenowplaying_getdata, 30000, false);\n";
	}
?>
	});
</script>
<?php
	echo $after_widget;
}
add_action("wp_ajax_ampachenowplaying", "_ampachenowplaying_callback");
add_action("wp_ajax_nopriv_ampachenowplaying", "_ampachenowplaying_callback");

function _ampachenowplaying_callback($args = NULL) {
	if (isset($_POST['ampachenowplaying'])){
		$msg = _ampachenowplaying_fetch_nowplaying(get_option('ampachenowplaying_rss_feed', 'http://example.com/ampache/rss.php?type=now_playing'));
		echo $msg;
	}
	die();
}


function ampache_now_playing_init() {
	register_sidebar_widget(__('Ampache Now Playing'), 'widget_ampache_now_playing');
	wp_enqueue_script("jquery", false, false, "1.3.2");
}
add_action('plugins_loaded', "ampache_now_playing_init");
add_action('admin_menu', 'ampachenowplaying_admin_menu');

/**
 * Retrieve the now playing rss from ampache
 *
 * This will grab the rss from an ampache installation
 * and parse out meaningful data to present to visitors
 *
 * @param $rss
 * the URL of the ampache now playing rss
 * @return $msg
 * String containing the final data to display 
 */
function _ampachenowplaying_fetch_nowplaying($rss) {
	global $table_prefix, $wpdb;
	$ampache_base = preg_replace('/rss\.php.*/', '', $rss); // get the base ampache install

	$user = get_option('ampachenowplaying_username', NULL);
	$pass = get_option('ampachenowplaying_password', NULL);

	$show_album_art = get_option('ampachenowplaying_show_album_art', FALSE);

	$msg = ''; // the return string


	// find what song is now playing
	$song_info = _ampachenowplaying_grab_now_playing($rss);
	switch ($song_info) {
		case -1: 
			return 'Cannot connect to Ampache'; // display cannot connect
		case -2:
		case -3:
		case NULL:
			return 'Nothing Playing'; // display nothing playing
			return NULL; // hide block
	}

	// if the code has made it this far, something is playing, or the user is showing the last song that was playing
	#echo "<h3>".WP_CONTENT_DIR."</h3>";

	if ($show_album_art) { // if the user selected to show the album art
		$ampache_table = $table_prefix . "ampachenowplaying";
		$uploads_dir = WP_CONTENT_DIR . '/uploads/';
		if (! file_exists($uploads_dir) ) { 
			mkdir($uploads_dir);
		}
		$full_ampache_dir = $uploads_dir . 'ampachenowplaying';
		if (! file_exists($full_ampache_dir) ) { // create the album_art cache folder if not exists
			mkdir($full_ampache_dir);
		}
		// get the song id and check if we have it in the DB, if showing the last song that was playing it WILL be in the db
		$song_id  = _ampachenowplaying_get_song_id($song_info['link']);
		$album_id = _ampachenowplaying_get_album_id_from_db($song_id);

		if (!$album_id) { // the album ID for the current song is NOT in the db, so authenticate and get it
			$auth = _ampachenowplaying_authenticate($user, $pass, $ampache_base);
			if ($auth) { // authentication was successfull 
				$album_id = _ampachenowplaying_get_album_id($song_id, $ampache_base, $auth);
				if ($album_id) { // if the album ID was returned successfully, save it to the db
					$wpdb->query($wpdb->prepare("INSERT INTO $ampache_table (song_id, album_id)
						VALUES ( %d, %d )", $song_id, $album_id));
				}
			}
		}
		if ($album_id) { // if the album id was found either in the DB or by authenticating, get the album art
			$artwork_file = $full_ampache_dir . DIRECTORY_SEPARATOR . $album_id . '.jpg';
			if (file_exists($artwork_file)) { // the album artwork exists locally on drupal
				$artwork_url  = WP_CONTENT_URL . '/uploads/ampachenowplaying/' . basename($artwork_file);
			} else { // the artwork doesn't exist locally, pull it from ampache
				if (! isset($auth)) { // if not already authenticated, authenticate
					$auth = _ampachenowplaying_authenticate($user, $pass, $ampache_base);
				}
				if (isset($auth)) { // make sure we have an authenticated session
					$artwork_file = _ampachenowplaying_get_album_artwork($album_id, $ampache_base, $auth, $full_ampache_dir);
					$artwork_url  = WP_CONTENT_URL . '/uploads/ampachenowplaying/' . basename($artwork_file);
				}
			}
		} 
	} // END IF statement for show_album_art

	// now print the results
	$show_agent = get_option('ampachenowplaying_show_agent', FALSE);
	$show_time  = get_option('ampachenowplaying_show_date' , FALSE);

	if ($show_album_art && $artwork_url) { // show the album art
		$google_link = 'http://www.google.com/search?q=' . _add_pluses($song_info['title']);
		$msg .= "<div style=\"float:left;\"><p>
			 <a href=\"$google_link\" target=\"_blank\">
			 <img style=\"padding:4px;border-style:solid;
				border-color:#ddd;border-width:1px;\" 
				height=\"150\" width=\"150\" src=\"$artwork_url\" alt=\""._convert_to_html($song_info['title'])."\" />
			 </a>
			 </p></div>";
	}
	$msg .= '<div style="float:left;"><p>';

	$msg .= _convert_to_html($song_info['title']) . "<br />";
	if ($show_agent) { $msg .= "<br />" . _convert_to_html($song_info['comments']); }
	if ($show_time ) { $msg .= "<br />" . _convert_to_html($song_info['date']); }
	$msg .= '</p></div><br style="clear:both;" />';
	return $msg;
}


/**
* Helper Function for _fetch_rss
* This function takes the rss feed, parses it 
*   and returns and array with information about the current song.
*   If the request is within the threshold (default of 10 seconds)
*   this function will return an array constructed with data from
*   the cache, to avoid pulling data from Ampache over and over again.
*
* @param $rss
* A string with the full url of the now_playing rss
* @return $song_info
* An array with song information
* -1 -- can't connect to ampache
* -2 -- nothing playing 
* -3 -- hide block (return null for the block)
*/
function _ampachenowplaying_grab_now_playing($rss) {
	$show_now_playing = get_option('ampachenowplaying_show_now_playing', 'Not Playing');

	// check to see if the RSS was grabbed within the threshold, if it was, return the cache
	$current_time = time();
	$last_rss_time = get_option('ampachenowplaying_last_rss_time', NULL);
	$threshold = get_option('ampachenowplaying_rss_threshold', 10);

	if ($last_rss_time && $threshold != 0) { // this is NOT the first time getting rss, and caching is enabled
		$time_since_last_rss = $current_time - $last_rss_time;
		if ($time_since_last_rss <= $threshold) { // less than threshold -- return the cache
			if (get_option('ampachenowplaying_nothing_playing', FALSE)) { // nothing playing
				switch ($show_now_playing) {
					case 'Not Playing': // show nothing playing
						return -2;
					case 'Hide Block':  // hide the block
						return -3;
				}
			} else { // something playing, or 'show last song' is enabled
				return _ampachenowplaying_grab_from_cache();
			}
		}
	} // END IF for being within the threshold 
	
	// rss is either older than threshold, or this is the first time this module is running- time to update
	if( !class_exists( 'WP_Http' ) )
		include_once( ABSPATH . WPINC. '/class-http.php' );
	$doc = new DOMDocument();
	$request = new WP_Http;
	$msg = $request->request($rss, array( 'method' => 'GET') );
	
	// try to load the DOMdocument, and fail if the URL is invalid
	if ( ! isset($msg->errors) && $msg['response']['code'] == 200 && @$doc->loadXML($msg['body'])) { // http request successful and xml loaded int $dom
		$arrFeeds = array();
		// parse the XML for songs, grab only the first (newest)
		foreach ($doc->getElementsByTagName('item') as $node) { 
			$itemRSS = array ( 
				'title'    => $node->getElementsByTagName('description')->item(0)->nodeValue,
				'comments' => $node->getElementsByTagName('comments')->item(0)->nodeValue,
				'link'     => $node->getElementsByTagName('link')->item(0)->nodeValue,
				'date'     => $node->getElementsByTagName('pubDate')->item(0)->nodeValue
			);
			array_push($arrFeeds, $itemRSS);
		}
		$song_info = $arrFeeds[0];
	} else { // couldn't connect to ampache	
		switch ($show_now_playing) {
			case 'Not Playing':
				return -1;
			case 'Hide Block' :
				return -3;
			case 'Last Song':
				return _ampachenowplaying_grab_from_cache();
		}
	}
	// if the code makes it this far, the RSS was pulled successfully, so set the last access time to now
	update_option('ampachenowplaying_last_rss_time', $current_time);

	if (!$song_info) { // nothing is currently playing
		update_option('ampachenowplaying_nothing_playing', TRUE);
		switch ($show_now_playing) {
			case 'Not Playing':
				return -2;
			case 'Hide Block':
				return -3;
			case 'Last Song':
				return _ampachenowplaying_grab_from_cache();
		}
	} else { // something is playing
		update_option('ampachenowplaying_nothing_playing', FALSE);
		// save the variables from the RSS document 
		update_option('ampachenowplaying_nowplaying_title',    $song_info['title']);
		update_option('ampachenowplaying_nowplaying_comments', $song_info['comments']);
		update_option('ampachenowplaying_nowplaying_link',     $song_info['link']);
		update_option('ampachenowplaying_nowplaying_date',     $song_info['date']);
	}
	return $song_info;
}

/**
 * This function grabs the information from the cache instead of the rss and returns it as an array
 *
 * @param none
 * @return $song_info
 * The same array that gets created from parsing the now_playing rss
 */
function _ampachenowplaying_grab_from_cache() {
	$song_info = array (
		'title'    => get_option('ampachenowplaying_nowplaying_title',    NULL),
		'comments' => get_option('ampachenowplaying_nowplaying_comments', NULL),
		'link'     => get_option('ampachenowplaying_nowplaying_link',     NULL),
		'date'     => get_option('ampachenowplaying_nowplaying_date',     NULL),
	);
	if ($song_info['title']) { // the value isn't null
		return $song_info;
	} 
	// no song has played and been cached yet
	return NULL;
}

/**
 * This function takes the link extracted from the RSS 
 *   and extracts the current playing song_id
 *
 * @param $link
 * A string with the link extracted from the now playing rss
 * @return $song_id
 * An int with the song_id
 */
function _ampachenowplaying_get_song_id($link) {
	// parse a link that looks like this
	// http://example.com/ampache/song.php?action=show_song&song_id=8776
	$song_id = preg_replace('/^.*song_id=/', '', $link);
	$song_id = preg_replace('/&.*/', '', $song_id);
	$song_id = preg_replace('/[^0-9]/', '', $song_id); // sanatize	
	return (int)$song_id;
}

/**
 * This function takes the song_id and checks the database
 *   to see if it is already linked to an album.
 * 
 * @param $song_id
 * An int with the song_id
 * @return $album_id
 * An int with the album_id or NULL if it doesn't exist
 */
function _ampachenowplaying_get_album_id_from_db($song_id) {
	global $table_prefix, $wpdb;
	$ampache_table = $table_prefix . "ampachenowplaying";
	$query = $wpdb->get_results($wpdb->prepare("SELECT album_id FROM $ampache_table 
				WHERE song_id = %d", $song_id), ARRAY_A);
	#$album_id = $album_id['album_id'];
	if ( ! $query) { 
		return NULL;
	} 
	$album_id = $query[0]['album_id'];
	if ( ! $album_id) { 
		return NULL;
	} 
	return (int)$album_id;
}

/**
 * This function checks to see if the ampachenowplaying table exists,
 * and if it doesn't it creates it.
 * This table links song_id's to album_id's
 */
function _create_initial_tables() {
	global $table_prefix, $wpdb;

	# Create the 'name' of our table which is prefixed by the standard WP table prefix (which you specified when you installed WP)
	$ampache_table = $table_prefix . "ampachenowplaying";

	# Check to see if the table exists already, if not, then create it
	if($wpdb->get_var("show tables like '$ampache_table'") != $ampache_table) {
		$sql0  = "CREATE TABLE `". $ampache_table . "` ( ";
		$sql0 .= " `song_id` int NOT NULL,";
		$sql0 .= " `album_id` int NOT NULL,";
		$sql0 .= " PRIMARY KEY (`song_id`) ";
		$sql0 .= ");";

		#We need to include this file so we have access to the dbDelta function below (which is used to create the table)
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		dbDelta($sql0);
	}
}

/**
 * Returns a string fit for a google URL
 *
 * @param string
 * @return string
 */
function _add_pluses($string) {
	return urlencode($string);
}

function _convert_to_html($string) {
	return htmlentities($string);
}


/**
 * This gets called by admin_menu for the admin menu (settings tab)
 **/
function ampachenowplaying_admin_menu() {
	add_options_page('Ampache Now Playing', 'Ampache Now Playing', 'manage_options', 'ampachenowplaying', 'ampachenowplaying_options_page');
}

function ampachenowplaying_options_page() {
	include('ampachenowplaying-settings.php');
}
?>
