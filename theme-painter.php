<?php
/**
 * Theme Painter
 *
 * A simple library for handling color customization in a theme. It adds the
 * customizer controls and prints styles as defined by the theme.
 *
 * @version 0.1
 */
if ( !function_exists( 'theme_painter_get_settings' ) ) {
	/**
	 * Retrieve the defined color values
	 *
	 * @since 0.1
	 * @return array
	 */
	function theme_painter_get_settings() {

		$config = get_theme_support( 'theme-painter' );

		if ( empty( $config ) || empty( $config[0] || empty( $config[0] ) ) ) {
			return array();
		}

		return $config[0];
	}
}

if ( !function_exists( 'theme_painter_customize_register' ) ) {
	/**
	 * Register controls for the theme customizer
	 *
	 * @since 0.1
	 */
	function theme_painter_customize_register( $wp_customize ) {

		$config = theme_painter_get_settings();

		if ( empty( $config ) ) {
			return;
		}

		$capability = empty( $config['capability'] ) ? 'edit_theme_options' : $config['capability'];

		// Add panels
		if ( !empty( $config['panels'] ) && is_array( $config['panels'] ) ) {
			theme_painter_register_panels( $config['panels'], $capability );
		}

		// Add sections without panels
		if ( !empty( $config['sections']) && is_array( $config['sections'] ) ) {
			theme_painter_register_sections( $config['sections'] );
		}

		// Add color controls to the core `colors` section
		if ( !empty( $config['colors']) && is_array( $config['colors'] ) ) {
			theme_painter_register_colors( $config['colors'] );
		}
	}
	add_action( 'customize_register', 'theme_painter_customize_register' );
}

if ( !function_exists( 'theme_painter_register_panels' ) ) {
	/**
	 * Register customizer panels
	 *
	 * @param $panels array Panels
	 * @param $capability string Default capability to use
	 * @since 0.1
	 */
	function theme_painter_register_panels( $panels, $capability = 'edit_theme_options' ) {

		global $wp_customize;

		foreach( $panels as $panel_id => $panel ) {

			if ( empty( $panel['sections'] ) ) {
				continue;
			}

			if ( empty( $panel['capability'] ) ) {
				$panel['capability'] = $capability;
			}

			$panel = apply_filters( 'theme_painter_panel', $panel );

			$panel_args = $panel;
			unset( $panel_args['sections'] );

			$wp_customize->add_panel( 'theme_painter_panel_' . sanitize_key( $panel_id ), $panel_args );

			theme_painter_register_sections( $panel['sections'], $panel_id, $panel['capability'] );
		}
	}
}

if ( !function_exists( 'theme_painter_register_sections' ) ) {
	/**
	 * Register customizer sections
	 *
	 * @param $sections array Sections
	 * @param $panel_id string Panel this section should be aassigned to
	 * @param $capability string Default capability to use
	 * @since 0.1
	 */
	function theme_painter_register_sections( $sections, $panel_id = '', $capability = 'edit_theme_options' ) {

		global $wp_customize;

		foreach( $sections as $section_id => $section ) {

			if ( empty( $section['colors'] ) ) {
				continue;
			}

			if ( empty( $section['panel'] ) && !empty( $panel_id ) ) {
				$section['panel'] = 'theme_painter_panel_' . sanitize_key( $panel_id );
			}

			if ( empty( $section['capability'] ) ) {
				$section['capability'] = $capability;
			}

			$section = apply_filters( 'theme_painter_section', $section );

			$section_args = $section;
			unset( $section_args['colors'] );

			$wp_customize->add_section( 'theme_painter_section_' . sanitize_key( $section_id ), $section_args );

			if ( !empty( $section['colors'] ) ) {
				theme_painter_register_colors( $section['colors'], $section_id, $section['capability'] );
			}
		}
	}
}

if ( !function_exists( 'theme_painter_register_colors' ) ) {
	/**
	 * Register customizer controls
	 *
	 * @param $colors array Color controls
	 * @param $section string Section this control should be aassigned to
	 * @param $capability string Default capability to use
	 * @since 0.1
	 */
	function theme_painter_register_colors( $colors, $section_id = '', $capability = 'edit_theme_options' ) {

		global $wp_customize;

		foreach( $colors as $color_id => $color ) {

			if ( empty( $color['sanitize_callback'] ) ) {
				$color['sanitize_callback'] = 'theme_painter_sanitize_hex_color';
			}

			if ( empty( $color['section'] ) && !empty( $section_id ) ) {
				$color['section'] = 'theme_painter_section_' . sanitize_key( $section_id );

			// Assign unsorted colors to the core `colors` section
			} elseif ( empty( $color['section'] ) ) {
				$color['section'] = 'colors';
			}

			if ( empty( $section['capability'] ) ) {
				$color['capability'] = $capability;
			}

			$color = apply_filters( 'theme_painter_color', $color );

			$setting_args = array(
				'sanitize_callback' => $color['sanitize_callback'],
				'transport' => empty( $color['transport'] ) ? 'postMessage' : $color['transport'],
				'capability' => $color['capability'],
				'default' => $color['default'],
			);

			$wp_customize->add_setting( 'theme_painter_setting_' . sanitize_key( $color_id ), $setting_args );

			$color_args = $color;
			unset( $color_args['sanitize_callback'] );
			unset( $color_args['transport'] );

			$wp_customize->add_control(
				new WP_Customize_Color_Control(
					$wp_customize,
					'theme_painter_setting_' . sanitize_key( $color_id ),
					$color_args
				)
			);
		}
	}
}

if ( !function_exists( 'theme_painter_sanitize_hex_color' ) ) {
	/**
	 * Sanitizes a hex color.
	 *
	 * Returns either '', a 3 or 6 digit hex color (with #), or null.
	 * For sanitizing values without a #, see sanitize_hex_color_no_hash().
	 *
	 * @link https://github.com/devinsays/customizer-library
	 * @param string $color
	 * @return string|null
	 */
	function theme_painter_sanitize_hex_color( $color ) {

		if ( '' === $color ) {
			return '';
		}

		// 3 or 6 hex digits, or the empty string.
		if ( preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
			return $color;
		}

		return null;
	}
}

if ( !function_exists( 'theme_painter_enqueue_scripts' ) ) {
	/**
	 * Print custom color styles on the frontend
	 *
	 * @since 0.1
	 */
	function theme_painter_enqueue_scripts() {

		$colors = theme_painter_get_settings();

		if ( empty( $colors ) ) {
			return;
		}

		if ( !empty( $colors['stylesheet'] ) ) {
			$styles = theme_painter_compile_styles();
			if ( !empty( $styles ) ) {
				wp_add_inline_style( $colors['stylesheet'], $styles );
			}
		} else {
			add_action( 'wp_head', 'theme_painter_print_style_tag' );
		}
	}
	add_action( 'wp_enqueue_scripts', 'theme_painter_enqueue_scripts', 20 );
}

if ( !function_exists( 'theme_painter_compile_styles' ) ) {
	/**
	 * Compile styles
	 *
	 * @since 0.1
	 * @return string
	 */
	function theme_painter_compile_styles() {

		$config = theme_painter_get_settings();

		if ( empty( $config ) ) {
			return '';
		}

		$transient = get_transient( 'theme_painter_compiled_styles' );
		if ( !empty( $transient ) ) {
			return $transient;
		}

		$colors = theme_painter_get_colors();

		$output = '';

		foreach( $colors as $color_id => $color ) {

			$color['value'] = get_theme_mod( 'theme_painter_setting_' . sanitize_key( $color_id ), $color['default'] );

			if ( $color['value'] == $color['default'] ) {
				continue;
			}

			$output .= theme_painter_get_color_styles( $color );
		}

		set_transient( 'theme_painter_compiled_styles', $output );

		return $output;
	}
}

if ( !function_exists( 'theme_painter_get_colors' ) ) {
	/**
	 * Retrieve one array containing all registered colors
	 *
	 * @since 0.1
	 */
	function theme_painter_get_colors() {

		$colors = array();

		$config = theme_painter_get_settings();

		if ( empty( $config ) ) {
			return $colors;
		}

		if ( !empty( $config['panels'] ) && is_array( $config['panels'] ) ) {
			foreach ( $config['panels'] as $panel ) {
				$config['sections'] = !empty( $config['sections'] ) ? array_merge( $config['sections'], $panel['sections'] ) : $panel['sections'];
			}
		}

		if ( !empty( $config['sections']) && is_array( $config['sections'] ) ) {
			foreach( $config['sections']  as $section ) {
				$config['colors'] = !empty( $config['colors'] ) ? array_merge( $config['colors'], $section['colors'] ) : $section['colors'];
			}
		}

		if ( !empty( $config['colors']) && is_array( $config['colors'] ) ) {
			$colors = $config['colors'];
		}

		return $colors;
	}
}

if ( !function_exists( 'theme_painter_get_color_styles' ) ) {
	/**
	 * Get all style rules attached to a color
	 *
	 * @since 0.1
	 */
	function theme_painter_get_color_styles( $color ) {

		if ( !is_array( $color['selectors'] ) ) {
			$color['selectors'] = array( $color['selectors'] );
			$color['attributes'] = array( $color['attributes'] );
			if ( !empty( $color['queries'] ) ) {
				$color['queries'] = array( $color['queries'] );
			}
			if ( !empty( $color['important'] ) ) {
				$color['important'] = array( $color['important'] );
			}
		}

		$output = '';

		for( $i = 0; $i < count( $color['selectors'] ); $i++ ) {

			$query = '';
			if ( !empty( $color['queries'] ) && !empty( $color['queries'][$i] ) ) {
				$query = $color['queries'][$i];
			}

			$important = '';
			if ( !empty( $color['important'] ) && !empty( $color['important'][$i] ) ) {
				$important = $color['important'][$i];
			}

			$value = !empty( $color['set_values'] ) && !empty( $color['set_values'][$i] ) ? $color['set_values'][$i] : $color['value'];

			$output .= theme_painter_build_style_rule( $color['selectors'][$i], $color['attributes'][$i], $value, $query, $important );
		}

		return $output;
	}
}

if ( !function_exists( 'theme_painter_build_style_rule' ) ) {
	/**
	 * Build a single style rule from params
	 *
	 * @since 0.1
	 */
	function theme_painter_build_style_rule( $selector, $attribute, $value, $query = '', $important = '' ) {

		if ( !empty( $important ) ) {
			$value .= '!important';
		}

		$output = '';

		if ( !empty( $query ) ) {
			$output .= $query . '{';
		}

		$output .= $selector . '{' . $attribute . ':' . $value . '}';

		if ( !empty( $query ) ) {
			$output .= '}';
		}

		return $output;
	}
}

if ( !function_exists( 'theme_painter_print_style_tag' ) ) {
	/**
	 * Print custom color styles directly into a <style> tag
	 *
	 * Used to print directly into the <head> if theme painter doesn't have a
	 * stylesheet to attach to.
	 *
	 * @since 0.1
	 */
	function theme_painter_print_style_tag() {
		?>
<script id='theme-painter-styles' type='text/css'>
	<?php echo theme_painter_compile_styles(); ?>
</script>
		<?php
	}
}

if ( !function_exists( 'theme_painter_bust_transient_cache' ) ) {
	/**
	 * Bust the transient cache with the built styles whenever the customizer
	 * is saved.
	 *
	 * @since 0.1
	 */
	function theme_painter_bust_transient_cache( $wp_customize ) {
		delete_transient( 'theme_painter_compiled_styles' );
	}
	add_action( 'customize_save', 'theme_painter_bust_transient_cache' );
}

if ( !function_exists( 'theme_painter_load_live_preview' ) ) {
	/**
	 * Enqueue the live preview scripts
	 *
	 * @since 0.1
	 */
	function theme_painter_load_live_preview() {

		$config = theme_painter_get_settings();

		if ( empty( $config ) || empty( $config['lib_url'] ) ) {
			return;
		}

		wp_enqueue_script( 'theme-painter-live-preview', trailingslashit( $config['lib_url'] ) . 'theme-painter-live-preview.js', array( 'jquery', 'customize-preview' ), '0.1', true );

		$colors = theme_painter_get_colors();

		$settings = array();
		foreach( $colors as $color_id => $color ) {
			$color['value'] = '%value%';
			$settings['theme_painter_setting_' . sanitize_key( $color_id )] = theme_painter_get_color_styles( $color );
		}

		wp_localize_script( 'theme-painter-live-preview', 'theme_painter_live_preview_settings', $settings );
	}
	add_action( 'customize_preview_init' , 'theme_painter_load_live_preview' );
}

if ( !function_exists( 'theme_painter_is_color_dark' ) ) {
	/**
	 * Check if the passed color is dark
	 *
	 * This is a utility function in case you need to print additional styles
	 * based on the brightness or darkness of a color. You can specify the
	 * brightness threshold by passing in a $limit value. Higher values are
	 * brighter.
	 *
	 * Based on: http://stackoverflow.com/a/8468448/1723499
	 *
	 * @since 0.1
	 */
	function theme_painter_is_color_dark( $color, $limit = 130 ) {

		$color = str_replace( '#', '', $color );

		$r = hexdec( substr( $color, 0, 2 ) );
		$g = hexdec( substr( $color, 2, 2 ) );
		$b = hexdec( substr( $color, 4, 2 ) );

		$contrast = sqrt(
			$r * $r * .241 +
			$g * $g * .691 +
			$b * $b * .068
		);

		return $contrast < $limit;
	}
}
