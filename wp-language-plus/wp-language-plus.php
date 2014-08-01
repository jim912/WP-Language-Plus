<?php
/*
Plugin Name: WP Language Plus
Plugin URI: https://github.com/jim912/WP-Language-Plus
Description: Add language packs from Dashboard. 
Author: Hitoshi Omagari
Version: 0.2
Author URI: http://www.warna.info/
*/

class WP_Language_Plus {
	private $page_hook;
	private $translations = array();
	private $installed_languages = array();
	private $add_languages = array();
	private $version;
	
	public function __construct() {
		$plugin_data = get_file_data( __FILE__, array( 'version' => 'Version' ) );
		$this->version = $plugin_data['version'];
		add_action( 'admin_menu'           , array( $this, 'add_settings_field' ) );
		add_action( 'wp_ajax_language_plus', array( $this, 'ajax_languages_install' ) );
	}
	
	
	public function add_settings_field() {
		$this->page_hook = add_management_page( 'WP Language Plus', 'WP Language Plus', 'manage_options', basename( __FILE__ ), array( $this, 'lang_page' ) );
		add_action( 'load-' . $this->page_hook, array( $this, 'install_language_packs' ) );
		add_action( 'load-' . $this->page_hook, array( $this, 'enqueue' ) );
	}
	
	
	public function lang_page() {
//		var_dump( get_filesystem_method() );
		include ( dirname( __FILE__ ) . '/admin/admin.php' );
	}
	
	
	public function install_language_packs() {
		require_once( ABSPATH ) . '/wp-admin/includes/upgrade.php';
		$current_languages = get_available_languages();
		$this->add_languages = array();
		if ( isset( $_POST['add-langs'] ) && is_array( $_POST['add-langs'] ) ) {
			check_admin_referer( 'lang-plus', 'lang-plus-nonce' );
			$langs = stripslashes_deep( $_POST['add-langs'] );
			foreach ( $langs as $lang ) {
				$lang = wp_install_download_language_pack( $lang );
				if ( $lang && ! in_array( $lang, $current_languages ) ) {
					$this->add_languages[] = $lang;
				}
			}
		}
		$this->translations = wp_get_available_translations_from_api();
		$this->installed_languages = get_available_languages();
	}


	public function enqueue() {
		wp_enqueue_script( 'admin-language-plus', plugin_dir_url( __FILE__ ) . 'js/wp-language-plus.js', array( 'jquery' ), $this->version, true );
		wp_localize_script( 'admin-language-plus', 'languagePlusNonce', array( 'nonce' => wp_create_nonce( 'admin-language-plus' ) ) );
		wp_enqueue_style( 'admin-language-plus', plugin_dir_url( __FILE__ ) . 'css/wp-language-plus.css', array(), $this->version );
	}

	public function ajax_languages_install() {
		check_ajax_referer( 'admin-language-plus', 'nonce' );
		require_once( ABSPATH ) . '/wp-admin/includes/upgrade.php';
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
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			$skin = new Automatic_Upgrader_Skin;
			$upgrader = new Language_Pack_Upgrader( $skin );
			$results = $upgrader->bulk_upgrade( $installs, array( 'clear_update_cache' => false ) );
			$return = array();
			foreach ( $results as $key => $result ) {
				if ( $result ) {
					$return[$installs[$key]->language] = $installs[$key]->native_name;
				}
			}
			debug_log( json_encode( $return ) );
			header( 'Content-Type: application/json; charset=utf-8' );
			echo json_encode( $return );
			exit;
		}
	}
	
	
} // class end.
new WP_Language_Plus;
