<?php
/*
Plugin Name: Multisite Content Copier
Plugin URI: https://premium.wpmudev.org/project/multisite-content-copier/
Description: Copy any content from any site in your network to any other site or group of sites in the same network.
Author: WPMUDEV
Version: 1.2.1
Author URI: http://premium.wpmudev.org/
Text Domain: mcc
Domain Path: lang
Network:true
WDP ID: 855335
Tags: multisiste plugin, multisite
*/

/*
Copyright 2007-2013 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * The main class of the plugin
 */

class Multisite_Content_Copier {

	// The version slug for the DB
	public static $version_option_slug = 'multisite_content_copier_plugin_version';

	// Admin pages. THey could be accesed from other points
	// So they're statics
	static $network_main_menu_page;
	static $network_blog_groups_menu_page;
	static $network_settings_menu_page;

	public $nbt_integrator;

	public function __construct() {

		$this->set_globals();

		if ( ! is_multisite() ) {
			add_action( 'all_admin_notices', array( &$this, 'display_not_multisite_notice' ) );
			return false;
		}

		$this->includes();

		add_action( 'init', array( &$this, 'maybe_upgrade' ) );

		add_action( 'init', array( &$this, 'init_plugin' ) );

		add_action( 'wp_loaded', array( &$this, 'maybe_copy_content' ) );

		add_action( 'plugins_loaded', array( &$this, 'load_text_domain' ) );

		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles' ) );

		add_action( 'delete_blog', array( &$this, 'delete_blog' ) );

		$this->nbt_integrator = new MCC_NBT_Integrator();

		// We don't use the activation hook here
		// As sometimes is not very helpful and
		// we would need to check stuff to install not only when
		// we activate the plugin
		register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );

	}


	public function display_not_multisite_notice() {
		?>
			<div class="error"><p><?php _e( 'Multisite Content Copier is a plugin just for multisites, please deactivate it.', MULTISTE_CC_LANG_DOMAIN ); ?></p></div>
		<?php
	}

	public function enqueue_scripts() {
	}


	public function enqueue_styles() {
		global $wp_version;
		if ( version_compare( $wp_version, '3.8', '>=' ) ) {
			?>
				<style>
					#adminmenu #toplevel_page_mcc_network_page div.wp-menu-image:before { content: "\f325"; }
				</style>
			<?php
		}
		else {
			wp_enqueue_style( 'mcc-icons', MULTISTE_CC_ASSETS_URL . 'css/icons.css' );
		}
	}



	/**
	 * Set the plugin constants
	 */
	private function set_globals() {

		// Basics
		define( 'MULTISTE_CC_VERSION', '1.2.1' );
		define( 'MULTISTE_CC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		define( 'MULTISTE_CC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'MULTISTE_CC_PLUGIN_FILE_DIR', plugin_dir_path( __FILE__ ) . 'multisite-content-copier.php' );

		// Language domain
		define( 'MULTISTE_CC_LANG_DOMAIN', 'mcc' );

		// URLs
		define( 'MULTISTE_CC_ASSETS_URL', MULTISTE_CC_PLUGIN_URL . 'assets/' );

		// Dirs
		define( 'MULTISTE_CC_ADMIN_DIR', MULTISTE_CC_PLUGIN_DIR . 'admin/' );
		define( 'MULTISTE_CC_FRONT_DIR', MULTISTE_CC_PLUGIN_DIR . 'front/' );
		define( 'MULTISTE_CC_MODEL_DIR', MULTISTE_CC_PLUGIN_DIR . 'model/' );
		define( 'MULTISTE_CC_INCLUDES_DIR', MULTISTE_CC_PLUGIN_DIR . 'inc/' );

	}

	/**
	 * Include files needed
	 */
	private function includes() {
		// Model
		require_once( MULTISTE_CC_MODEL_DIR . 'model.php' );
		require_once( MULTISTE_CC_MODEL_DIR . 'copier-model.php' );
		require_once( MULTISTE_CC_MODEL_DIR . 'nbt-model.php' );

		// Libraries
		require_once( MULTISTE_CC_INCLUDES_DIR . 'admin-page.php' );
		require_once( MULTISTE_CC_INCLUDES_DIR . 'errors-handler.php' );
		require_once( MULTISTE_CC_INCLUDES_DIR . 'helpers.php' );


		//Integrations
		require_once( MULTISTE_CC_INCLUDES_DIR . 'integration/nbt-integration.php' );

		// Settings Handler
		require_once( MULTISTE_CC_INCLUDES_DIR . 'settings-handler.php' );

		// Admin Pages
		require_once( MULTISTE_CC_ADMIN_DIR . 'pages/network-main-page.php' );
		require_once( MULTISTE_CC_ADMIN_DIR . 'pages/network-blogs-groups.php' );
		require_once( MULTISTE_CC_ADMIN_DIR . 'pages/network-settings-page.php' );

		if ( is_admin() ) {
			require_once( MULTISTE_CC_ADMIN_DIR . 'edit-post.php' );
			require_once( MULTISTE_CC_INCLUDES_DIR . 'ajax.php' );
		}

		global $wpmudev_notices;
		$wpmudev_notices[] = array( 'id'=> 855335,'name'=> 'Multisite Content Copier', 'screens' => array( 'toplevel_page_mcc_network_page-network', 'content-copier_page_mcc_sites_groups_page-network', 'content-copier_page_mcc_settings_page-network' ) );
		include_once( MULTISTE_CC_INCLUDES_DIR . 'dash-notice/wpmudev-dash-notification.php' );
	}

	public static function include_copier_classes() {
		require_once( MULTISTE_CC_INCLUDES_DIR . 'content-copier/content-copier-factory.php' );
		self::include_integration_files();
	}

	public static function include_integration_files() {
		if ( class_exists( 'Woocommerce' ) ) {
			require_once( MULTISTE_CC_INCLUDES_DIR . 'integration/woocommerce.php' );
		}
	}

	/**
	 * Upgrade the plugin when a new version is uploaded
	 */
	public function maybe_upgrade() {

		$current_version = get_site_option( self::$version_option_slug, '1.0.4' );

		if ( $current_version === MULTISTE_CC_VERSION )
			return;

		if ( version_compare( $current_version, '1.0.4', '<=' ) ) {
			require_once( MULTISTE_CC_INCLUDES_DIR . 'upgrade.php' );
			mcc_upgrade_11();
		}

		if ( version_compare( $current_version, '1.1.1', '<' ) ) {
			require_once( MULTISTE_CC_INCLUDES_DIR . 'upgrade.php' );
			mcc_upgrade_111();
		}

		update_site_option( self::$version_option_slug, MULTISTE_CC_VERSION );
	}



	/**
	 * Actions executed when the plugin is deactivated
	 */
	public function deactivate() {
		$model = mcc_get_model();
		$model->deactivate_model();
		// HEY! Do not delete anything from DB here
		// You better use the uninstall functionality
	}

	/**
	 * Load the plugin text domain and MO files
	 *
	 * These can be uploaded to the main WP Languages folder
	 * or the plugin one
	 */
	public function load_text_domain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), MULTISTE_CC_LANG_DOMAIN );

		load_textdomain( MULTISTE_CC_LANG_DOMAIN, WP_LANG_DIR . '/' . MULTISTE_CC_LANG_DOMAIN . '/' . MULTISTE_CC_LANG_DOMAIN . '-' . $locale . '.mo' );
		load_plugin_textdomain( MULTISTE_CC_LANG_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	/**
	 * Initialize the plugin
	 */
	public function init_plugin() {
		// A network menu
		$args = array(
			'menu_title' => __( 'Content Copier', MULTISTE_CC_LANG_DOMAIN ),
			'page_title' => __( 'Multisite Content Copier', MULTISTE_CC_LANG_DOMAIN ),
			'network_menu' => true,
			'screen_icon_slug' => 'mcc'
		);
		self::$network_main_menu_page = new Multisite_Content_Copier_Network_Main_Menu( 'mcc_network_page', 'manage_network', $args );

		$args = array(
			'menu_title' => __( 'Site Groups', MULTISTE_CC_LANG_DOMAIN ),
			'page_title' => __( 'Site Groups', MULTISTE_CC_LANG_DOMAIN ),
			'network_menu' => true,
			'parent' => 'mcc_network_page',
			'screen_icon_slug' => 'mcc',
			'tabs' => array(
				'groups' => __( 'Groups', MULTISTE_CC_LANG_DOMAIN ),
				'sites' => __( 'Sites', MULTISTE_CC_LANG_DOMAIN )
			)
		);

		$settings = mcc_get_settings();
		if ( $settings['blog_templates_integration'] )
			$args['tabs']['nbt'] = __( 'New Blog Templates', MULTISTE_CC_LANG_DOMAIN );

		self::$network_blog_groups_menu_page = new Multisite_Content_Copier_Network_Blogs_Groups_Menu( 'mcc_sites_groups_page', 'manage_network', $args );

		$args = array(
			'menu_title' => __( 'Settings', MULTISTE_CC_LANG_DOMAIN ),
			'page_title' => __( 'Settings', MULTISTE_CC_LANG_DOMAIN ),
			'network_menu' => true,
			'parent' => 'mcc_network_page',
			'screen_icon_slug' => 'mcc'
		);
		self::$network_settings_menu_page = new Multisite_Content_Copier_Network_Settings_Menu( 'mcc_settings_page', 'manage_network', $args );

		if ( is_admin() && class_exists( 'MCC_Post_Meta_Box' ) )
			new MCC_Post_Meta_Box();

	}


	public function maybe_copy_content() {

		if ( ! is_network_admin() && ! get_transient( 'mcc_copying' ) ) {
			set_transient( 'mcc_copying', true, 900 );
			$queue = mcc_get_queue_for_blog();

			foreach ( $queue as $item ) {
				global $wpdb;

				$model = mcc_get_model();
				$model->delete_queue_item( $item->ID );

				if ( empty( $item->settings['type'] ) )
					continue;

				$type = $item->settings['type'];
				$args = $item->settings['args'];
				$items_ids = $item->settings['items_ids'];
				$source_blog_id = $item->src_blog_id;

				$wpdb->query( "BEGIN;" );

				self::include_copier_classes();
				$copier = Multisite_Content_Copier_Factory::get_copier( $type, $source_blog_id, $items_ids, $args );

				$execute = apply_filters( 'mcc_execute_copier', true, $copier, $source_blog_id, $items_ids, $args );

				if ( $execute ) {
					$copier->execute();
					do_action( 'mcc_after_execute_copier', $type, $source_blog_id, $items_ids, $args );
				}

				$wpdb->query( "COMMIT" );


			}
			delete_transient( 'mcc_copying' );
		}

	}

	/**
	 * Delete the queue for a blog when it is deleted
	 *
	 * @param Integer $blog_id Deleted Blog ID
	 */
	public function delete_blog( $blog_id ) {
		$model = mcc_get_model();
		$model->delete_queue_for_blog( $blog_id );
	}

}

global $multisite_content_copier_plugin;
$multisite_content_copier_plugin = new Multisite_Content_Copier();

