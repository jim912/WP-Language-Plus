<?php
/*
Plugin Name: WP Language Plus
Plugin URI: https://github.com/jim912/WP-Language-Plus
Description: Add language packs from Dashboard. 
Author: jim912
Version: 0.4.1
Author URI: http://www.warna.info/
Domain Path: /languages/
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

require_once( dirname( __FILE__ ) . '/includes/activation_hooks.php' );
register_activation_hook( __FILE__, 'activation_wp_language_plus' );
register_deactivation_hook( __FILE__, 'deactivation_wp_language_plus' );

class WP_Language_Plus {
	private $page_hook;
	private $translations = array();
	private $installed_languages = array();
	private $add_languages = array();
	private $version;
	
	public function __construct() {
		$plugin_data = get_file_data( __FILE__, array( 'version' => 'Version' ) );
		$this->version = $plugin_data['version'];
		add_action( 'plugins_loaded'          , array( $this, 'load_textdomain' ) );
		add_action( 'admin_menu'              , array( $this, 'add_settings_field' ) );
		add_action( 'personal_options'        , array( $this, 'user_language_setting' ) );
		add_action( 'personal_options_update' , array( $this, 'update_language_setting' ) );
		add_action( 'edit_user_profile_update', array( $this, 'update_language_setting' ) );
		add_action( 'wp_ajax_language_plus'   , array( $this, 'ajax_languages_install' ) );
		if ( is_admin() ) {
			add_action( 'plugins_loaded'         , array( $this, 'add_locale_hook' ), 0 );
		}
	}


	public function load_textdomain() {
		load_plugin_textdomain( 'wp-language-plus', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
	}
	
	
	public function add_settings_field() {
		$this->page_hook = add_management_page( __( 'Languages', 'wp-language-plus' ), __( 'Languages', 'wp-language-plus' ), 'manage_options', basename( __FILE__ ), array( $this, 'lang_page' ) );
		add_action( 'load-' . $this->page_hook, array( $this, 'load_language_packs' ) );
		add_action( 'load-profile.php'        , array( $this, 'load_language_packs' ) );
		add_action( 'load-user-edit.php'      , array( $this, 'load_language_packs' ) );
		add_action( 'load-' . $this->page_hook, array( $this, 'enqueue' ) );
	}


	public function lang_page() {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		$language_upgrader = new Language_Pack_Upgrader;
		ob_start();
		$ret = $language_upgrader->fs_connect( array( WP_CONTENT_DIR, WP_LANG_DIR ) );
		ob_end_clean();
		if ( $ret ) {
			include ( dirname( __FILE__ ) . '/admin/admin.php' );
		} else {
			include ( dirname( __FILE__ ) . '/admin/fs_error.php' );
		}
	}


	public function load_language_packs() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$this->translations = wp_get_available_translations_from_api();
		$this->installed_languages = get_available_languages();
	}


	public function enqueue() {
		wp_enqueue_script( 'admin-language-plus', plugin_dir_url( __FILE__ ) . 'js/wp-language-plus.js', array( 'jquery' ), $this->version, true );
		wp_localize_script( 'admin-language-plus', 'languagePlus', array(
			'nonce'     => wp_create_nonce( 'admin-language-plus' ),
			'installed' => __( '{%installed_languages%} installed.', 'wp-language-plus' ),
		) );
		wp_enqueue_style( 'admin-language-plus', plugin_dir_url( __FILE__ ) . 'css/wp-language-plus.css', array(), $this->version );
	}


	public function ajax_languages_install() {
		check_ajax_referer( 'admin-language-plus', 'nonce' );
		require_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$this->translations = wp_get_available_translations_from_api();
		$this->installed_languages = get_available_languages();
		$langs = stripslashes_deep( $_POST['add-langs'] );
		$installs = array();
		foreach ( $langs as $lang ) {
			if ( ! in_array( $lang, $this->installed_languages ) && in_array( $lang, array_keys( $this->translations ) ) ) {
				$translation = (object)$this->translations[$lang];
				$translation->type = 'core';
				$installs[] = $translation;
			}
		}

		if ( $installs ) {
			require_once ( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
			$skin = new Automatic_Upgrader_Skin;
			$upgrader = new Language_Pack_Upgrader( $skin );
			$results = $upgrader->bulk_upgrade( $installs, array( 'clear_update_cache' => false ) );
			$return = array();
			foreach ( $results as $key => $result ) {
				if ( $result ) {
					$return[$installs[$key]->language] = $installs[$key]->native_name;
				}
			}
			header( 'Content-Type: application/json; charset=utf-8' );
			echo json_encode( $return );
			exit;
		}
	}


	public function user_language_setting( $profileuser ) {
		if ( ! $this->installed_languages ) { return; }
		$selected = count( get_user_meta( $profileuser->ID, 'user_language' ) ) == 0 ? get_option( 'WPLANG' ) : get_user_meta( $profileuser->ID, 'user_language', true );
?>
	<tr>
		<th><?php _e( 'Dashboard Language', 'wp-language-plus' ); ?></th>
		<td>
			<?php wp_dropdown_languages( array(
				'name'      => 'user_language',
				'id'        => 'user-language',
				'selected'  => $selected,
				'languages' => $this->installed_languages,
			) ); ?>
		</td>
	</tr>
<?php
	}


	public function update_language_setting( $user_id ) {
		if ( isset( $_POST['user_language'] ) ) {
			$post_user_language = stripslashes_deep( $_POST['user_language'] );
			if ( in_array( $post_user_language, $this->installed_languages ) || $post_user_language === '' ) {
				update_user_meta( $user_id, 'user_language', $post_user_language );
			}
		}
	}


	public function add_locale_hook() {
		add_filter( 'locale', array( $this, 'user_locale' ) );
	}


	public function user_locale( $locale ) {
		$user = wp_get_current_user();
		if ( count( get_user_meta( $user->ID, 'user_language' ) ) != 0 ) {
			$locale = get_user_meta( $user->ID, 'user_language', true );
			if ( ! $locale ) {
				$locale = 'en_US';
			}
		}
		return $locale;
	}
} // class end.
new WP_Language_Plus;
