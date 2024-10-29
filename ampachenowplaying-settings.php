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
 * the settings for Ampache Now Playing
 **/

if ($_POST['ampachenowplaying_hidden'] == 'Y' && $_POST['Submit'] == 'Save Settings' ) { // data was sent
	// data
	update_option('ampachenowplaying_show_agent',       $_POST['ampachenowplaying_show_agent']);
	update_option('ampachenowplaying_show_date',        $_POST['ampachenowplaying_show_date']);
	update_option('ampachenowplaying_show_album_art',   $_POST['ampachenowplaying_show_album_art']);
	update_option('ampachenowplaying_show_now_playing', $_POST['ampachenowplaying_show_now_playing']);
	update_option('ampachenowplaying_autoupdate',       $_POST['ampachenowplaying_autoupdate']);
	// validate user input
	if ( _ampachenowplaying_validate_input($_POST) == TRUE) {
		_print_updated('Settings saved');
	}
	// validate username and password by authenticating
	if (isset($_POST['ampachenowplaying_username']) && isset($_POST['ampachenowplaying_password']) &&
		$_POST['ampachenowplaying_username'] != '' && $_POST['ampachenowplaying_password'] != '') {
		if (_ampachenowplaying_validate_account($_POST) == TRUE) {
			update_option('ampachenowplaying_username', $_POST['ampachenowplaying_username']);
			update_option('ampachenowplaying_password', $_POST['ampachenowplaying_password']);
			_print_updated('Authenticated to Ampache succesfully!' );
		} else {
			_print_updated('Authentication to Ampache failed! Invalid username/password or ACL error.' );
		}
	}

}
if ($_POST['Submit'] == 'Clear Album Art') {
	_ampachenowplaying_clear_album_art();
} elseif ($_POST['Submit'] == 'Clear Album ID Table') {
	_ampachenowplaying_clear_album_ids();
}

$rss_feed         = get_option('ampachenowplaying_rss_feed', 'http://example.com/ampache/rss.php?type=now_playing');
$rss_threshold    = get_option('ampachenowplaying_rss_threshold', 10);
$show_agent       = get_option('ampachenowplaying_show_agent', FALSE);
$show_date        = get_option('ampachenowplaying_show_date', FALSE);
$show_album_art   = get_option('ampachenowplaying_show_album_art', FALSE);
$show_now_playing = get_option('ampachenowplaying_show_now_playing', 'Not Playing');
$username         = get_option('ampachenowplaying_username', NULL);
$password         = get_option('ampachenowplaying_password', NULL);
$autoupdate       = get_option('ampachenowplaying_autoupdate', FALSE);
?>
<div class="wrap">
<h2><?php _e( 'Ampache Now Playing'); ?></h2>
<form name="ampachenowplaying_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<input type="hidden" name="ampachenowplaying_hidden" value="Y">
	<p>
		<b><?php _e("Ampache RSS url: " ); ?></b><br />
		<input type="text" name="ampachenowplaying_rss_feed" value="<?php echo $rss_feed; ?>" size="100"><br />
		<small<em><?php _e("<b>Example:</b> http://example.com/ampache/rss.php?type=now_playing" ); ?></em></small>
	</p>
	<p>
		<b><?php _e("Cache max time: " ); ?></b><br />
		<input type="text" name="ampachenowplaying_rss_threshold" value="<?php echo $rss_threshold; ?>" size="3"><br />
		<small><em><?php _e("The minimum time, in seconds, between checking the now playing rss.<br />
					Enabling this limits the amount of connections Wordpress will make to Ampache.<br />
					Set to 0 to disable (NOT RECOMMENDED).  Default: 10" ); ?></em></small>
	</p>

	<hr />

	<h3><?php _e( 'Display Settings' ); ?></h3>
	<p>
		<?php _e('The display settings are used to customize the now playing widget.<br />
		Note: changes to this might take a couple seconds to take effect due to the cache max time.'); ?>
	</p>
	<p>
		<input type="checkbox" name="ampachenowplaying_autoupdate" value="1" <?php if ($autoupdate == TRUE) echo 'checked'; ?> /> Update block using Ajax
		<br />
		<small><em><?php _e('This will cause the plugin to check every 30 seconds when the page is loaded to see if a new song is played.  If a new song is played the plugin will fade out the old song information and fade in the new information.'); ?></em></small>
	</p>
	<p>
		<input type="checkbox" name="ampachenowplaying_show_agent" value="1" <?php if ($show_agent == TRUE) echo 'checked'; ?> /> Show the user agent
	</p>
	<p>
		<input type="checkbox" name="ampachenowplaying_show_date" value="1" <?php if ($show_date == TRUE) echo 'checked'; ?> /> Show the time song was played
	</p>
	<p>
		<input type="checkbox" name="ampachenowplaying_show_album_art" value="1" <?php if ($show_album_art == TRUE) echo 'checked'; ?> /> Show the album art
		<br />
		<small><em><?php _e('This requires your accoount information for Ampache.'); ?></em></small>
	</p>
	<h4><?php _e( 'Action when no song is currently playing:' ); ?></h4>
	<p>
		<input type="radio" name="ampachenowplaying_show_now_playing" value="Last Song" <?php if ($show_now_playing == 'Last Song') echo "checked"; ?>> Show the last song that was playing
	</p>
	<p>
		<input type="radio" name="ampachenowplaying_show_now_playing" value="Not Playing" <?php if ($show_now_playing == 'Not Playing') echo "checked"; ?>> Show "Not Playing"
	</p>

	<hr />

	<h3><?php _e( 'Account Settings'); ?></h3>
	<p>
		<?php _e('The account settings are required if you want this module to pull the album art for the current playing song. <br />
		These fields are optional otherwise.') ?>
	</p>
	<p>
		<b><?php _e("Username: " ); ?></b><br />
		<input type="text" name="ampachenowplaying_username" value="<?php echo $username; ?>"><br />
	</p>
	<p>
		<b><?php _e("Password: "); ?></b><br />
		<input type="password" name="ampachenowplaying_password"><br />
		<small><em>(<?php _e('Password field kept blank for security purposes.'); ?>)</em></small>
	</p>

	<p class="submit">
		<input type="submit" name="Submit" value="<?php _e('Save Settings') ?>" />
	</p>
	<p>
		<?php _e("Album art is cached locally on drupal, so if you update the album art on Ampache and are wondering why it isn't changing on drupal, try clearing the Album Art cache.
		<br />Both should be cleared if you re-catalog your music, or reinstall Ampache -- or if things are just acting weird."); ?>
	</p>
	<p class="submit">
		<input type="submit" name="Submit" value="<?php _e('Clear Album Art') ?>" />
		<input type="submit" name="Submit" value="<?php _e('Clear Album ID Table') ?>" />
	</p>
</div>


<?php
/**
 * This function clears the cached album art on the server
 * It scans the ampache directory that was created at installation
 * for any files matching a glob for *.jpg and deletes them.
 *
 * @param none
 * @return none
 */
function _ampachenowplaying_clear_album_art() {
	$uploads_dir = WP_CONTENT_DIR . '/uploads/';
	if (! file_exists($uploads_dir) ) {
		mkdir($uploads_dir);
	}
	$full_ampache_dir = $uploads_dir . 'ampachenowplaying';
	if (! file_exists($full_ampache_dir) ) { // if the folder doesn't exist just exit
		_print_updated('Album Art Cleared');
	}
	$content = null;
	$i = 0;
	if ( ($count = count($glob = glob($full_ampache_dir."/*.jpg")) ) > 0 ){
		foreach($glob as $v) {
			if (file_exists($v)) { // delete the pictures
				unlink($v);
				#echo $v;
				$i += 1;
			}
		}
	}
	_print_updated("Album art cleared. $i Pictures deleted.");
}

/**
 * This function clears the album_ids and song_ids from the ampachenowplaying
 * table created in the wordpress database.  This table links song_ids to album_ids
 * to cut down on the number of authentications drupal will need to make to Ampache
 *
 * @param none
 * @return none
 */
function _ampachenowplaying_clear_album_ids() {
	global $wpdb, $table_prefix;
        $ampache_table = $table_prefix . "ampachenowplaying";
	$wpdb->query("DELETE FROM $ampache_table");
	_print_updated('Ampache Album ID table cleared.');
}

/**
 * Validate input
 */
function _ampachenowplaying_validate_input($new) {
	// check the rss threshold to make sure it's numeric and between 0 and 300 seconds
	$result = FALSE;
	$failed = FALSE;
	$max_cache_age = $new['ampachenowplaying_rss_threshold'];
	if (!isset($max_cache_age)) {
		// do nothing
	} elseif (!is_numeric($max_cache_age)) { // not numeric
		_print_updated('The max cache age value must be numeric.');
		$failed = TRUE;
	} elseif ($max_cache_age <= 0 || $max_cache_age > 300) { // invalid number
		_print_updated('The max cache age value must be between 0 and 300.');
		$failed = TRUE;
	} else { // successful
		update_option('ampachenowplaying_rss_threshold', $max_cache_age);	
		$result = TRUE;
	}

	// check the rss feed url for validity 
	$rss_feed = $new['ampachenowplaying_rss_feed'];
	if (!isset($rss_feed)) {
		// do nothing
	} elseif (!_ampachenowplaying_valid_url($rss_feed)) {
		_print_updated('The RSS feed must be a valid URL.');
		$failed = TRUE;
	} else { // successful
		update_option('ampachenowplaying_rss_feed', $rss_feed);
		$result = TRUE;
	}
	if ($failed == FALSE) {
		return $result;
	}
	return FALSE;
}

function _ampachenowplaying_validate_account($new) {
	$user = $new['ampachenowplaying_username'];
	$pass = $new['ampachenowplaying_password'];
	$ampache_base = preg_replace('/rss\.php.*/', '', get_option('ampachenowplaying_rss_feed', NULL));
	if ($ampache_base) { // the rss feed was saved before
		require_once('ampachenowplaying-api.php');
		$auth = _ampachenowplaying_authenticate($user, $pass, $ampache_base);
		if ($auth) { // authentication successful
			return TRUE;
		}
	}
	return FALSE;
}

/**
 * Helper function to make sure a given URL is valid
 *
 * @param $url
 * A string with a URL in it
 * @return BOOL
 * True or False
 */
function _ampachenowplaying_valid_url($url) {
	return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
}


function _print_updated($string) {
	echo '<div class="updated"><p><strong>';
	_e($string);
	echo '</strong></p></div>';
}
?>
