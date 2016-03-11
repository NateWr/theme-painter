/**
 * Handle live preview of color changes
 */
jQuery( function ( $ ) {

	/**
	 * Inject style element onto the page
	 * @link http://css-tricks.com/snippets/javascript/inject-new-css-rules/
	 */
	function theme_painter_inject_style( styles, to ) {
		var div = $( '<div />', {
			html: '&shy;<style>' + styles.replace( /%value%/g, to ) + '</style>'
		}).appendTo( 'body' );
	}

	var settings = theme_painter_live_preview_settings;

	for ( var key in settings ) {

		wp.customize( key, function( value ) {
			var theme_painter_key = key.slice(0);
			value.bind( function( to ) {
				theme_painter_inject_style( theme_painter_live_preview_settings[theme_painter_key], to );
			} );
		} );
	}

} );
