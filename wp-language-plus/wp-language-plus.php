<?php
/*
Plugin Name: WP Language Plus
Plugin URI: 
Description: 
Author: Hitoshi Omagari
Version: 0.1
Author URI: 
*/

class WP_Language_Plus {
	private $page_hook;
	private $translations = array();
	private $installed_languages = array();
	private $add_languages = array();
	
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_field' ) );
	}
	
	
	public function add_settings_field() {
		$this->page_hook = add_management_page( 'WP Language Plus', 'WP Language Plus', 'manage_options', basename( __FILE__ ), array( $this, 'lang_page' ) );
		add_action( 'load-' . $this->page_hook, array( $this, 'install_language_packs' ) );
	}
	
	
	public function lang_page() {
?>
		<div class="wrap">
			<h2>WP Language Plus</h2>
<?php if ( $this->add_languages ) :
	$adds = array();
	foreach ( $this->add_languages as $add_lang ) {
		$adds[] = $this->translations[$add_lang]['native_name'];
	}
	$adds = implode( ', ', $adds );
?>
			<div class="updated">
				<p>Added languages : <?php echo esc_html( $adds ); ?></p>
			</div>
<?php endif; ?>
<?php if ( $this->translations ) : ?>
		<form action="" method="post">
			<?php wp_nonce_field( 'lang-plus', 'lang-plus-nonce' ); ?>
			<ul>
<?php foreach ( $this->translations as $translation ) :
	if ( in_array( $translation['language'], $this->installed_languages ) ) { continue; }
?>
				<li>
					<label for="add-lang-<?php echo esc_attr( $translation['language'] ); ?>">
						<input type="checkbox" name="add-langs[]" id="add-lang-<?php echo esc_attr( $translation['language'] ); ?>" value="<?php echo esc_attr( $translation['language'] ); ?>">
						<?php echo esc_html( $translation['native_name'] ); ?>
					</label>
				</li>
<?php endforeach; ?>
			</ul>
			<?php submit_button( 'Install' ); ?>
		</form>
<?php else : ?>
		<p>No languages aviable.</p>
<?php endif; ?>
	</div><!-- wrap end. -->
<?php
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
	
	
} // class end.
new WP_Language_Plus;