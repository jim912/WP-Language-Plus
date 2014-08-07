jQuery(document).ready( function($) {
	$('#add-lang-form').submit( function() {
		var langs = new Array;
		$( ':checkbox[name="add-langs[]"]:checked' ).each(function( index, checkbox ) {
			langs.push( $(checkbox).val() );
		});
		if ( langs.length > 0 ) {
			$.post(
				ajaxurl,
				data = {
					'action'   : 'language_plus',
					'add-langs': langs,
					'nonce'    : languagePlus.nonce
				},
				function( response ) {
					if ( response ) {
						var added = new Array();
						$.each( response, function( i, val ){
							$( '#list-lang-' + i ).remove();
							added.push( '<strong>' + val + '</strong>' );
						});
						added = added.join( ', ' );
						$( '#added-languages' ).empty();
						$( '#added-languages' ).append( '<p>' + languagePlus.installed.replace( '{%installed_languages%}', added ) + '</p>' );
						$( '#added-languages' ).show();
					}
				}
			);
		}
		return false;
	});
});
