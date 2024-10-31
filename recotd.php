<?php
/*
Plugin Name: Real Estate Chart of the Day Widget
Plugin URI: http://wordpress.org/extend/plugins/real-estate-chart-of-the-day/
Description: Randomized real estate and mortgage charts for your WordPress blog
Version: 4.0
Author: Dan Green
Author URI: http://themortgagereports.com
*/
// ------------------------------------------------------------- //
/**
 * Copyright 2010-2014 | The Mortgage Reports, LLC
 * Released under the GNU General Public License (GPL)
 * 
 * @link http://themortgagereports.com/real-estate-charts-for-wordpress
 * @license http://www.gnu.org/licenses/gpl.txt
 */
// ------------------------------------------------------------- //
/**
 * Block direct access to this file.
 */
if(!function_exists('add_action')):
	header("{$_SERVER['SERVER_PROTOCOL']} 403 Forbidden");
	header("Status: 403 Forbidden");
	die('Direct Access Denied.');
endif;
// ------------------------------------------------------------- //
define('BTB_RECOTD_PATH', realpath(dirname(__FILE__))); // Current Folder Path
// The URL to the current Directory where the Widget and dependencies reside
define('BTB_RECOTD_RELPATH', str_replace('\\', '/', substr(BTB_RECOTD_PATH, strlen(realpath(ABSPATH)))));
define('BTB_RECOTD_URL', site_url(BTB_RECOTD_RELPATH));
define('BTB_RECOTD_RENDERER', 'http://themortgagereports.com/recotd/render.php'); // Base URL (DO NOT CHANGE)
// ------------------------------------------------------------- //
/**
 * The Real-Estate Chart of the Day Widget.
 */
class BTB_RECOTD_Widget extends WP_Widget{
	// Easily change Widget name in Widget List
	const WidgetName = 'Real Estate Chart of the Day';
	// Easily change Widget description in Widget List
	const WidgetDescription = 'Real estate charts for your blog, courtesy of The Mortgage Reports';
	/**
	 * Widget init code.
	 */
	public function __construct(){
		// Register the Widget (name and description can be easily changed using the consts above)
		parent::__construct('widget_btb_RECOTD', self::WidgetName, array(
			'description' => self::WidgetDescription,
		));
	}
	/**
	 * Reusable cURL GET function to download the FORM HTML.
	 * $arguments is the QueryString added after ?.
	 * 
	 * @internal
	 * @param string $url
	 * @param array $arguments
	 * @return string|null
	 */
	public function download_url($url, $arguments = null, $verb = 'GET'){
		// Unless POST is explicit, we use GET
		if(is_object($arguments)) $arguments = get_object_vars($arguments);
		if(is_array($arguments) and !empty($arguments)){
			$arguments = http_build_query($arguments);
		}
		$curl_handle = curl_init();
		$verb = (is_string($verb) and !strcasecmp(trim($verb), 'POST')) ? 'POST' : 'GET';
		// If we have GET we add the $arguments to the QueryString
		if(!strcasecmp($verb, 'GET')){
			$glue = (strpos($url, '?') !== false) ? '&' : '?';
			$url = "{$url}{$glue}{$arguments}";
		}else{
			// Otherwise we prepare them for posting
			curl_setopt_array($curl_handle, array(
				CURLOPT_POST		=> true,
				CURLOPT_POSTFIELDS	=> $arguments,
			));
		}
		curl_setopt_array($curl_handle, array(
			CURLOPT_CONNECTTIMEOUT		=> 30,
			CURLOPT_TIMEOUT				=> 60,
			CURLOPT_HTTP_VERSION		=> CURL_HTTP_VERSION_1_0,
			CURLOPT_URL					=> $url,
			CURLOPT_FOLLOWLOCATION		=> true,
			CURLOPT_MAXREDIRS			=> 2,
			CURLOPT_AUTOREFERER			=> true,
			CURLOPT_BINARYTRANSFER		=> true,
			CURLOPT_HEADER				=> false,
			CURLOPT_RETURNTRANSFER		=> true,
			CURLOPT_FAILONERROR			=> false,
			CURLOPT_SSL_VERIFYPEER		=> false,
			CURLOPT_SSL_VERIFYHOST		=> false,
			CURLOPT_USERAGENT			=> !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
			CURLOPT_REFERER				=> !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null,
			CURLOPT_HTTPHEADER			=> array(
				'Expect:',
				'Connection: close',
			), // Keep-Alives not good here
		));
		$html = curl_exec($curl_handle);
		$status = (int)curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
		curl_close($curl_handle);
		return ($status == 200) ? $html : null;
	}
	/**
	 * Widget output.
	 */
	public function widget($args, $instance){
		// Allow plugins to hook into 'btbrecotd_show_in_sidebar' and block sidebar showing
		if(!apply_filters('btbrecotd_show_in_sidebar', true)) return;
		// Carry on, business as usual
		extract($args, EXTR_OVERWRITE|EXTR_PREFIX_ALL, 'arg');
		extract($instance, EXTR_OVERWRITE|EXTR_PREFIX_ALL, 'inst');
		// If we can't get the HTML, we don't show the Widget at all.
		$html = $this->download_url(BTB_RECOTD_RENDERER, array(
			'color'		=> get_option('btbrecotd_color'),
			'width'		=> $instance['width'],
			'show_link'	=> ((bool)get_option('btbrecotd_show_link') ? 'on' : 'off'), // 'off'
		), 'GET');
		// And now we output the Widget.
		echo $arg_before_widget, $html, $arg_after_widget;
	}
	/**
	 * Nothing happens here.
	 */
	function update($new_instance, $old_instance){
		$new_instance['width'] = intval(round($new_instance['width'] / 10) * 10);
		$new_instance['width'] = max(150, min(350, $new_instance['width']));
		return $new_instance;
	}
	/**
	 * The settings: none.
	 * But we show a link to the actual Settings Page:
	 * /wp-admin/options-general.php?page=RateQuoteWidget
	 */
	public function form($instance){ ?>
		<p><strong>Width</strong><br />
			<label>
				<input id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>"
					value="<?php echo intval($instance['width']); ?>" style="text-align: right;" type="text" size="6" maxlength="7" /> px
				<em>Between 150px &amp; 350px.</em>
			</label>
		</p>
		<p>More settings available at:<br />
			<a href="<?php echo admin_url('options-general.php?page=RECOTD'); ?>" target="_blank" style="text-decoration: none;">
				<strong>Settings &raquo; Real Estate Charts</strong></a></p>
	<?php return; }
};
// ------------------------------------------------------------- //
/**
 * Main class containing actions/filters.
 */
class BTB_RECOTD{
	/**
	 * Hook into WordPress.
	 */
	static public function Construct(){
		add_action('widgets_init', array(__CLASS__, 'WidgetsInit'));
		add_action('admin_menu', array(__CLASS__, 'AdminMenu'));
		register_activation_hook(__FILE__, array(__CLASS__, 'Activation'));
		register_uninstall_hook(__FILE__, array(__CLASS__, 'Uninstall'));
	}
	/**
	 * Register Widget.
	 */
	static public function WidgetsInit(){
		register_widget('BTB_RECOTD_Widget');
	}
	/**
	 * Removes all Database storage on uninstall.
	 * 
	 * @internal
	 */
	static public function Uninstall(){
		global $wpdb;
		// All option names starting with btbrecotd_* are belong to us
		$wpdb->query("DELETE FROM {$wpdb->options} WHERE `option_name` LIKE 'btbrecotd_%';");
		// All meta names starting with btbrecotd_* or _btbrecotd_* are also belong to us
		$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE (`option_name` LIKE '_btbrecotd_%') OR (`option_name` LIKE 'btbrecotd_%');");
	}
	/**
	 * Rejects Activation for old PHP versions.
	 * Imports older 'btb_BTB_RECOTD_settings' option into new separate elements.
	 * 
	 * @internal
	 */
	static public function Activation(){
		if(version_compare(PHP_VERSION, '5.2', '<')){
			deactivate_plugins(__FILE__, true);
			wp_die('Plugin could not be activated as your '.PHP_VERSION.' PHP Version is incompatible.', 'Outdated PHP Detected');
		}
		// Extract old Option format and update to new settings
		if(($options = get_option('re_COTD_settings', null)) and is_array($options)){
			foreach($options as $name => $value){
				update_option("btbrecotd_{$name}", $value);
			}
			delete_option('re_COTD_settings'); // Remove old Option
		}
	}
	/**
	 * Register the Admin Page in Settings > Real-Estate Chart of the Day Widget.
	 */
	static public function AdminMenu(){
		if(!current_user_can('activate_plugins')) return; // Only Administrators
		$page = add_options_page(
			'Real Estate Chart of the Day from TheMortgageReports.com', 'Real Estate Charts',
			'activate_plugins', 'RECOTD', array(__CLASS__, 'AdminPage')
		);
		// Hook the load to capture posts
		if(!empty($page)){
			add_action("load-{$page}", array(__CLASS__, 'AdminPageLoad'));
		}
	}
	/**
	 * Capture POSTs and update settings.
	 */
	static public function AdminPageLoad(){
		if(strcasecmp($_SERVER['REQUEST_METHOD'], 'POST')) return;
		check_admin_referer('recotd_widget_config');
		$_POST = stripslashes_deep($_POST); // Strip slashes (WP is crazy)
		update_option('btbrecotd_color', trim($_POST['Color']));
		update_option('btbrecotd_show_link', intval($_POST['ShowLink']));
		wp_redirect($_SERVER['REQUEST_URI']);
		die();
	}
	/**
	 * Output the settings screen.
	 */
	static public function AdminPage(){ ?>
		<script type="text/javascript" src="http://themortgagereports.com/chart-of-the-day/jscolor/jscolor.js"></script>
		<div class="wrap">
			<h2 style="font-family: georgia; font-style: italic;">Real Estate Chart of the Day Widget</h2>
			<P>The Real Estate Chart of the Day displays real estate- and mortgage-related charts in your blog's sidebar.</p>
			<p>It's the simple plugin that makes a big impact on your readers.</p>
			<h3>Configure Real Estate Chart of the Day</h3>
			<form method="post">
				<?php wp_nonce_field('recotd_widget_config'); ?>
				<table class="form-table">
					<tr>
						<th><label for="BTB_RECOTD_Color"><strong>Widget Color</strong></label></th>
						<td>
							<input type="text" name="Color" id="BTB_RECOTD_Color" class="color" size="10" maxlength="6"
								value="<?php echo esc_attr(trim(get_option('btbrecotd_color', 'ffffff'))); ?>" /><br />
							<label class="setting-description" for="BTB_RECOTD_Color">
								To learn more about <code><b>RGB</b>(reg, green, blue)</code> web colors,
									visit the <a href="http://colorschemedesigner.com/" target="_blank">Color Scheme Designer</a>.
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="BTB_RECOTD_ShowLink"><strong>Show Courtesy Link to Author</strong></label></th>
						<td>
							<input type="checkbox" <?php checked(true, (bool)get_option('show_link', true)); ?> name="ShowLink" value="1" ID="BTB_RECOTD_ShowLink" />
							<label class="setting-description" for="BTB_RECOTD_ShowLink">
							</label>
						</td>
					</tr>
				</table>
				<p><input type="submit" class="button-primary" value="Update Settings" /></p>
			</form>
			<p>&nbsp;</p>
			<h3>To use the Real Estate Chart of the Day </h3>
			<p>Go to your <code>Appearance &raquo; Widgets</code> screen and drag the
				<strong>Real Estate Chart of the Day widget</strong> where you'd like it to appear in your site's sidebar. That's it!</p>
			
			<h3>This plug-in is fueled by the flux capacitor</h3>
				<div style="float:left; width:300px; margin-right:20px;">
				<p>If you like the Real Estate Chart of the Day and would like to buy Dan a cup of coffee, use the PayPal donation button, please.</p>
			</div>
			
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
				<input type="hidden" name="cmd" value="_s-xclick">
				<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----" />
				<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
				<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
			</form>
			
			<div style="margin: 25px auto; margin-top: 50px; border-top: 1px solid #ddd;">
				<p style="float: right; color: #000; text-shadow: 1px 1px 1px #eee;">&copy; <?php echo date("Y");?>, <a href='http://themortgagereports.com/?recotd' target=_blank>The Mortgage Reports, LLC</a></p>
				<p>If you like Real Estate Chart of the Day, you may also like <a href="http://ratequotewidget.com" target="_blank">Rate Quote Widget</a>.</p>
				<p>The Real Estate Chart of the Day plugin is a free WordPress plugin but its images may not be copied for commercial use.</p>
			</div>
		</div>
	<?php }
};
BTB_RECOTD::Construct(); // Kick in
// ------------------------------------------------------------- //
?>