<?php
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
/**
 * @file
 * The functions to communicate to the Ampache API
 **/

/**
 * This function authenticates to Ampache and returns a string with the auth key
 *
 * @param $user
 * The username
 * @param $pass
 * The password
 * @param $ampache_base
 * The full URL to the ampache installation (http://example.com/ampache/)
 * @return $auth
 * A string with the auth key to use for future requests
 */
function _ampachenowplaying_authenticate($user, $pass, $ampache_base) {
	if (!$user || !$pass || !$ampache_base) { // username, password or URL is blank
		return NULL;
	}
	// generate authentication to ampache
	$ampache_rpc = $ampache_base . "server/xml.server.php";

	// build the data query
	$time = time();
	$key = hash('sha256', $pass);
	$passphrase = hash('sha256', $time . $key);
	$authdata = array (
		'action'    => 'handshake',
		'auth'      => "$passphrase",
		'timestamp' => "$time",
		'version'   => '350001',
		'user'      => "$user",
	);
	$header = array ('Content-Type' => 'application/x-www-form-urlencoded');

	// send the request 
	$request = new WP_Http;
	$msg = $request->request($ampache_rpc.'?'.http_build_query($authdata, '', '&'), array( 'method' => 'GET', 'headers' => $header) );
	
	if ( isset($msg->errors) || $msg['response']['code'] != 200 ) { // http request failed
		return NULL;
	}
	// read the xml that is returned
	$doc = new DOMDocument();
	if ( ! @$doc->loadXML($msg['body'])) { // couldn't read the XML returned from ampache
		return NULL;
	}

	$arrFeeds = array();
	// parse the XML auth 
	foreach ($doc->getElementsByTagName('root') as $node) { 
		$itemRSS = array ( 
			'auth' => $node->getElementsByTagName('auth')->item(0)->nodeValue,
		);
		array_push($arrFeeds, $itemRSS);
	}
	$array = $arrFeeds[0];
	$auth = $array['auth'];

	return $auth;
}


/**
 * This function takes the song_id, the ampache_base URL, 
 *  and the authentication string to return the album ID 
 *  of the current playing song by authenticating to Ampache
 *
 * @param $song_id
 * The current song_id
 * @param $ampache_base
 * The full URL to the ampache installation
 * @param $auth
 * A string with the auth key to use for future requests
 * @return $album_id
 * A string with the album_id of the current song
 */
function _ampachenowplaying_get_album_id($song_id, $ampache_base, $auth) {	
	$ampache_rpc = $ampache_base . "server/xml.server.php";
	$header = array ('Content-Type' => 'application/x-www-form-urlencoded');

	$data = array (
		'auth'   => "$auth",
		'action' => 'song',
		'filter' => "$song_id",
	);

	// send the request 
	$request = new WP_Http;
	$msg = $request->request($ampache_rpc.'?'.http_build_query($data, '', '&'), array( 'method' => 'GET', 'headers' => $header) );
	
	if ( isset($msg->errors) || $msg['response']['code'] != 200 ) { // http request failed
		return NULL;
	}

	$doc = new DOMDocument();
	if ( ! @$doc->loadXML($msg['body'])) { // couln't read the xml returned from ampache
		return NULL;
	}
	$arrFeeds = array();
	// parse the XML auth 
	foreach ($doc->getElementsByTagName('root') as $node) { 
		$itemRSS = array ( 
			'album' => $node->getElementsByTagName('album')->item(0)->getAttribute('id'),
		);
		array_push($arrFeeds, $itemRSS);
	}
	$array = $arrFeeds[0];
	$album_id = $array['album'];
	return (int)$album_id;	
}

/**
 * This function takes the album_id,
 * the ampache_base URL, and the authentication string
 * to download the artwork, and return the relative path to it
 *
 * @param $album_id
 * The ID of the currently playing album
 * @param $ampache_base
 * The full URL to the ampache installation
 * @param $auth
 * A string with the auth key to use for future requests
 * @param $full_ampache_dir
 * The local Ampache directory to store album art
 * @return $artwork
 * A string with the relative path of the album art
 */

function _ampachenowplaying_get_album_artwork($album_id, $ampache_base, $auth, $full_ampache_dir) {
	$ampache_rpc = $ampache_base . "server/xml.server.php";
	$header = array ('Content-Type' => 'application/x-www-form-urlencoded');
	$data = array (
		'action' => 'album',
		'auth'   => "$auth",
		'filter' => "$album_id",
	);

	// send the request 
	$request = new WP_Http;
	$msg = $request->request($ampache_rpc.'?'.http_build_query($data, '', '&'), array( 'method' => 'GET', 'headers' => $header) );
	
	if ( isset($msg->errors) || $msg['response']['code'] != 200 ) { // http request failed
		return NULL;
	}
	$doc = new DOMDocument();
	if ( ! @$doc->loadXML($msg['body'])) { // couln't read the xml returned from ampache
		return NULL;
	}

	$arrFeeds = array();
	// parse the XML auth 
	foreach ($doc->getElementsByTagName('root') as $node) { 
		$itemRSS = array ( 
			'url' => $node->getElementsByTagName('art')->item(0)->nodeValue,
		);
		array_push($arrFeeds, $itemRSS);
	}
	$array = $arrFeeds[0];
	$art_url = $array['url'];

	$msg =  $request->request($art_url);
	if ( isset($msg->errors) || $msg['response']['code'] != 200 ) { // http request failed
		return NULL;
	}
	
	$artwork = $msg['body'];

	$full_path_to_artwork = $full_ampache_dir . DIRECTORY_SEPARATOR . $album_id . '.jpg';
	$f = fopen($full_path_to_artwork, 'w');
	fwrite($f, $artwork);
	fclose($f);
	include_once( ABSPATH . WPINC . '/media.php' );
	$new_file = image_resize($full_path_to_artwork, 180, 180);
	rename($new_file, $full_path_to_artwork);
	return $full_path_to_artwork;
}
?>
