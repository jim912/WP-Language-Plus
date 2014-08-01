<?php if ( ! defined( 'ABSPATH' ) ) { die(); } ?>
	<div class="wrap">
		<h2>WP Language Plus</h2>
		<div id="added-languages" class="updated">
		</div>
<?php if ( $this->translations ) : ?>
		<h3>Avilable Languages</h3>
		<form action="" method="post" id="add-lang-form">
			<?php wp_nonce_field( 'lang-plus', 'lang-plus-nonce' ); ?>
			<ul id="avilable-languages">
<?php foreach ( $this->translations as $translation ) :
	if ( in_array( $translation['language'], $this->installed_languages ) ) { continue; }
?>
				<li id="list-lang-<?php echo esc_attr( $translation['language'] ); ?>">
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
		<p>No languages avilable.</p>
<?php endif; ?>
	</div><!-- wrap end. -->
