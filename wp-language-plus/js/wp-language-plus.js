jQuery(document).ready( function($) {
	$('#add-lang-form').submit( function() {
		var langs = new Array;
		$( ':checkbox[name="add-langs[]"]:checked' ).each(function( index, checkbox ) {
			langs.push( $(checkbox).val() );
		});
		$.post(
			ajaxurl,
			data = {
				'action'   : 'language_plus',
				'add-langs': langs,
				'nonce'    : languagePlusNonce.nonce
			},
			function( response ) {
				if ( response ) {
					var added = '';
					$.each( response, function( i, val ){
						$( '#list-lang-' + i ).remove();
						added = added + ' ' + val;
					});
					$( '#added-languages' ).empty();
					$( '#added-languages' ).append( '<p>' + added + ' installed.</p>' );
					$( '#added-languages' ).show();
				}
		});
		return false;
	});
});
