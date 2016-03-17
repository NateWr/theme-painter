/**
 * Handle live preview of color changes
 */
var theme_painter;
jQuery( function ( $ ) {

	theme_painter = theme_painter || {};

	/**
	 * Inject style element onto the page
	 * @link http://css-tricks.com/snippets/javascript/inject-new-css-rules/
	 */
	theme_painter.inject_style = function( styles, to ) {
		var div = $( '<div />', {
			html: '&shy;<style>' + styles.replace( /%value%/g, to ) + '</style>'
		}).appendTo( 'body' );
	};

	/**
	 * Check if the passed color is dark
	 *
	 * This is a utility function in case you need to make any adjustments
	 * based on the brightness or darkness of a color. You can specify the
	 * brightness threshold by passing in a `limit` value. Higher values are
	 * brighter.
	 *
	 * Based on: http://stackoverflow.com/a/8468448/1723499
	 * with some help from: http://stackoverflow.com/a/11508164/1723499
	 *
	 * @since 0.1
	 */
	theme_painter.is_color_dark = function( color, limit ) {
		limit = limit || 130;
		bigint = parseInt( color.replace( '#', '' ), 16 );
		var r = (bigint >> 16) & 255;
		var g = (bigint >> 8) & 255;
		var b = bigint & 255;


		var contrast = Math.sqrt(
			r * r * .241 +
			g * g * .691 +
			b * b * .068
		);

		return contrast < limit;
	};

	var settings = theme_painter_live_preview_settings;

	for ( var key in settings ) {

		wp.customize( key, function( value ) {
			var theme_painter_key = key.slice(0);
			value.bind( function( to ) {
				theme_painter.inject_style( theme_painter_live_preview_settings[theme_painter_key], to );
			} );
		} );
	}

} );
