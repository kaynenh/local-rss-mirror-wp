<?php
/*
Plugin Name: Local RSS Mirror
Plugin URI: http://www.kaynen.com/
Description: Creates a local version of a remote rss feed (xml) on a cron schedule, to save page load and requests
Author: Kaynen Heikkinen
Version: 1.0
Author URI: http://www.kaynen.com/
 */

/*Helper function*/
function download_file($url, $path) {
	$newfilename = $path;
	$file = fopen ($url, "rb");
	if ($file) {
		$newfile = fopen ($newfilename, "wb");

		if ($newfile) {
			while(!feof($file)) {
		  	fwrite($newfile, fread($file, 1024 * 8 ), 1024 * 8 );
			}
		}
	}

	if ($file) {
		fclose($file);
	}
	if ($newfile) {
		fclose($newfile);
	}
}

register_activation_hook(__FILE__, 'lrm_activation');

function lrm_activation() {
	wp_schedule_event(strtotime('yesterday'), 'daily', 'lrm_daily_event');
	lrm_init();
}

register_deactivation_hook(__FILE__, 'lrm_deactivation');

function lrm_deactivation() {
	wp_clear_scheduled_hook('lrm_daily_event');
}

add_action('lrm_daily_event', 'lrm_init');

function lrm_init() {
	/* Create directory if needed */
	$upload_dir = wp_upload_dir();
	$lrm_dir = $upload_dir['basedir'].'/local-rss-mirror';
	if ( ! file_exists( $lrm_dir ) ) {
	    wp_mkdir_p( $lrm_dir );
	}

	download_file(get_option('xml_feed_url'),$lrm_dir . '/' . get_option('local_mirror_name'));

}

function get_lrm_xml() {
	$upload_dir = wp_upload_dir();
	$lrm_file = $upload_dir['baseurl'] . '/local-rss-mirror/' . get_option('local_mirror_name');
	
	return $lrm_file;
}

/*Admin Area*/

add_action('admin_menu', 'lrm_menu');

function lrm_menu() {
	add_menu_page('Local RSS Mirror', 'LRM Settings', 'administrator', 'lrm-plugin-settings', 'lrm_settings_page', 'dashicons-admin-generic');
}

function lrm_settings_page() {
	//todo: call when saving
	lrm_init();
	?>
  <div class="wrap">
	<h2>Local RSS Mirror Settings</h2>

	<form method="post" action="options.php">
	    <?php settings_fields( 'lrm-plugin-settings' ); ?>
	    <?php do_settings_sections( 'lrm-plugin-settings' ); ?>
	    <table class="form-table">
	        <tr valign="top">
	        <th scope="row">XML Feed URL</th>
	        <td><input type="text" name="xml_feed_url" value="<?php echo esc_attr( get_option('xml_feed_url') ); ?>" /></td>
	        </tr>
	        <tr valign="top">
	        <th scope="row">Name of local file</th>
	        <td><input type="text" name="local_mirror_name" value="<?php echo esc_attr( get_option('local_mirror_name') ); ?>" /></td>
	        </tr>
	    </table>

	    <?php submit_button(); ?>

	</form>
</div>
<?php
}

add_action( 'admin_init', 'lrm_settings' );

function lrm_settings() {
	register_setting( 'lrm-plugin-settings', 'xml_feed_url' );
	register_setting( 'lrm-plugin-settings', 'local_mirror_name' );
}

?>
