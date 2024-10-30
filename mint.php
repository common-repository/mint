<?php
/*
Plugin Name: Mint
Plugin URI: http://wordpress.org/extend/plugins/mint/
Description: Integrates the Mint site analytics program with wordpress.
Author: damianzaremba
Author URI: http://damianzaremba.co.uk
Version: 1.0
License: GPL3
*/

/*
 * Copyright 2011 Damian Zaremba <damian@damianzaremba.co.uk>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Register hooks
add_action('admin_menu', 'mint_settings_menu'); # Menu entry
add_action('admin_notices', 'mint_admin_notices'); # Admin notices
add_action('wp_head', 'output_header'); # Pre </head> code

add_action('rss_head','birdfeeder_feed'); # RSS header code
add_action('rss2_head','birdfeeder_feed'); # RSS2 header code
add_action('atom_head','birdfeeder_feed'); # ATOM header code
add_filter('the_permalink_rss', 'birdfeeder_seed'); # Perm link code

// Register the settings menu
function mint_settings_menu() {
	add_options_page('Mint settings', 'Mint settings', 'manage_options', __FILE__, 'mint_settings_page');
}

// Write out the header
function output_header() {
	$mint_url = get_option('mint_url');
	$mint_enabled = get_option('mint_enabled');
	if (empty($mint_url) || empty($mint_enabled) || $mint_enabled !== "yes"){
		return; # Don't do anything if we don't know what to do or we are disabled
	}
?>
<script src="<?php echo $mint_url; ?>?js" type="text/javascript"></script> 
<?php	
}

// Deal with seeds for bird feeder
function birdfeeder_seed($link) {
	global $BirdFeeder;

	if (defined('MINT_EMBEDDED') !== True || !isset($BirdFeeder)){
		return get_permalink(); # Not included birdfeeder_feed() probably failed
	}

	# If we got to here life is good
	return $BirdFeeder->seed(get_the_title_rss(), get_permalink(), true);
}

// Deal with feeds for bird feeder
function birdfeeder_feed() {
	global $wpdb, $Mint;
	$mint_path = get_option('mint_path');
	$birdfeeder_enabled = get_option('mint_birdfeeder_enabled');

	if($birdfeeder_enabled !== "yes") {
		return; # We are disabled
	}

	if(!defined('MINT'))
		define('MINT', true);
		
	$watcher_file = $mint_path . '/pepper/shauninman/birdfeeder/watcher.php';
	if(!is_file($watcher_file))
		return; # No file

	@include_once($watcher_file); # Try an include

	define('BIRDFEED', get_bloginfo('name'));
	if (defined('MINT_EMBEDDED') !== True){
		return; # Failed include
	}
	
	# If we got to here life is good
	$wpdb->select(DB_NAME);

	# Let $BirdFeeder be accessible globally
	$GLOBALS['BirdFeeder'] = $BirdFeeder;
}

// Register the notices
function mint_admin_notices() {
	$mint_url = get_option('mint_url');
	$mint_path = get_option('mint_path');
	$mint_enabled = get_option('mint_enabled');
	$birdfeeder_enabled = get_option('mint_birdfeeder_enabled');
	$errors = array();
	
	if(isset($_GET) && array_key_exists('page', $_GET) && $_GET['page'] === "mint/mint.php"){
		return; # Don't bother showing the notice when we are on the settings page
	}
	if (empty($mint_url)) {
		$errors[] = 'Please configure the install URL or disable the plugin.';
	}

	if (empty($mint_enabled)) {
		$errors[] = 'Please enable or disable mint.';
	}

	if (empty($birdfeeder_enabled)) {
		$errors[] = 'Please enable or disable birdfeeder.';
	}

	if($birdfeeder_enabled == "yes" && (empty($mint_path) || !is_file($mint_path . '/pepper/shauninman/birdfeeder/watcher.php'))){
		$errors[] = 'Please configure a valid mint path for birdfeeder.';
	}
	
	if(count($errors) > 0){
	// Fail
	?>

	<div class='updated' style='background-color:#f66f66;'>
		<p><a href="options-general.php?page=mint/mint.php">Mint</a> needs attention;</p>

	<?php
		foreach($errors as $k => $error) {
			echo "<p>$error</p>";
		}
	?>

	</div>
	
	<?php
	}
}

// Settings page "callback"
function mint_settings_page() {
	if (!current_user_can('manage_options')) {
		wp_die('You do not have sufficient permissions to access this page.');
	}
	
	$mint_url = get_option('mint_url');
	$mint_path = get_option('mint_path');
	$mint_enabled = get_option('mint_enabled');
	$birdfeeder_enabled = get_option('mint_birdfeeder_enabled');

	if(isset($_POST) && count($_POST) >= 4) {
		$errors = array();
		$mint_url = $_POST['mint_url'];
		$mint_path = str_replace("\\", "/", $_POST['mint_path']);
		$mint_enabled = $_POST['mint_enabled'];
		$birdfeeder_enabled = $_POST['birdfeeder_enabled'];

		if(substr($mint_url, strlen($mint_url)-1) !== "/"){
			$mint_url .= "/"; # We take full paths and nothing else
		}

		if(preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?\/$|i', $mint_url) === 0){
			$errors[] = 'Invalid url entered - please enter the pull path ending with a /.';
			$mint_url = $_POST['mint_url'];
		}

		if($mint_enabled !== 'no' && $mint_enabled !== 'yes'){
			$errors[] = 'Invalid option specified for Mint enabled status.';
		}

		if($birdfeeder_enabled !== 'no' && $birdfeeder_enabled !== 'yes'){
			$errors[] = 'Invalid option specified for Birdfeeder enabled status.';
		}

		if($birdfeeder_enabled === 'yes'){
			if(empty($mint_path)){
				$errors[] = 'You must specify the path to mint to enabled birdfeeder.';
			}
			
		// I decided to just make this a warning
		//	if(!is_dir($mint_path)){
		//		$errors[] = 'The path you specified for mint is not a dir or is not readable.';
		//	}
		}

        if(count($errors) > 0){
		// Fail save - errors exist
		?>

		<div class='updated' style='background-color:#f66f66;'>
			<p>Error<?php if(count($errors) > 1){ echo "s"; } ?> occurred while updating the settings:</p>


			<?php
				foreach($errors as $k => $error) {
					echo "<p>$error</p>";
				}
			?>

		</div>

		<?php
				}else{
					// Good - save
					update_option('mint_url', $mint_url);
					update_option('mint_path', $mint_path);
					update_option('mint_enabled', $mint_enabled);
					update_option('mint_birdfeeder_enabled', $birdfeeder_enabled);
		?>

				<div class='updated' style='background-color:#4AB915;'>
				<p>Settings updated successfully</p>
				</div>

		<?php
				}
	}
	// Main form
	?>

	<?php
	if($birdfeeder_enabled == "yes" && (empty($mint_path) || !is_file($mint_path . '/pepper/shauninman/birdfeeder/watcher.php'))){
	?>
	<div class='updated' style='background-color:#FF9900;'>
	<p>Warning could not load <?php echo $mint_path . '/pepper/shauninman/birdfeeder/watcher.php'; ?> - this means birdfeeder will *NOT* function.</p>
	</div>
	<?php
	}
	?>

	<div class="wrap">
		<h2>Mint options</h2>
		<form method="post" action="">
		<p>
			Mint URL: <input type="text" name="mint_url" id="mint_url" value="<?php echo $mint_url; ?>" size="60" />
		</p>

		<p>
			Enable stats: <select name="mint_enabled" id="mint_enabled">
				<option value="yes"<?php if($mint_enabled === 'yes' || empty($mint_enabled)){ echo ' selected="selected"'; } ?>>Yes</option>
				<option value="no"<?php if($mint_enabled === 'no'){ echo ' selected="selected"'; } ?>>No</option>
			</select>
		</p>

		<p>
			Enable birdfeeder: <select name="birdfeeder_enabled" id="birdfeeder_enabled">
				<option value="yes"<?php if($birdfeeder_enabled === 'yes' || empty($birdfeeder_enabled)){ echo ' selected="selected"'; } ?>>Yes</option>
				<option value="no"<?php if($birdfeeder_enabled === 'no'){ echo ' selected="selected"'; } ?>>No</option>
			</select>
		</p>

		<p>
			Mint path: <input type="text" name="mint_path" id="mint_path" value="<?php echo $mint_path; ?>" size="60" /> (required if birdfeeder is enabled)
		</p>

		<p>
			<input type="submit" name="Submit" class="button-primary" value="Save changes" />
		</p>
		</form>
	</div>

	<?php
}

?>
