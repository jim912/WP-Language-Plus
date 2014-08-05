<?php
function activation_wp_language_plus() {
}

function deactivation_wp_language_plus() {
	delete_metadata( 'user', null, 'user_language', '', true );
}
