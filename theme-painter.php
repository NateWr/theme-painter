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

		$theme_painter = get_theme_support( 'theme-painter' );

		if ( empty( $theme_painter ) || empty( $theme_painter[0] || empty( $theme_painter[0]['sections'] ) ) ) {
			return array();
		}

		return $theme_painter[0];
	}
}

if ( !function_exists( 'theme_painter_customize_register' ) ) {
	/**
	 * Register controls for the theme customizer
	 *
	 * @since 0.1
	 */
	function theme_painter_customize_register( $wp_customize ) {

		$colors = theme_painter_get_settings();

		if ( empty( $colors ) ) {
			return;
		}

		$capability = empty( $colors['capability'] ) ? 'edit_theme_options' : $colors['capability'];

		// Add panels
		if ( !empty( $colors['panels'] ) && is_array( $colors['panels'] ) ) {
			theme_painter_register_panels( $colors['panels'], $capability );
		}

		// Add sections without panels
		if ( !empty( $colors['sections']) && is_array( $colors['sections'] ) ) {
			theme_painter_register_sections( $colors['sections'] );
		}

		// Add color controls to the core `colors` section
		if ( !empty( $colors['colors']) && is_array( $colors['colors'] ) ) {
			theme_painter_register_colors( $colors['colors'] );
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

		// Dump all colors into one array
		$colors = array();

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

		$output = '';

		foreach( $colors as $color_id => $color ) {

			$color['value'] = get_theme_mod( 'theme_painter_setting_' . sanitize_key( $color_id ), $color['default'] );

			if ( $color['value'] == $color['default'] ) {
				continue;
			}

			if ( !is_array( $color['selectors'] ) ) {
				$color['selectors'] = array( $color['selectors'] );
				$color['attributes'] = array( $color['attributes'] );
				if ( !empty( $color['queries'] ) ) {
					$color['queries'] = array( $color['queries'] );
				}
			}

			for( $i = 0; $i < count( $color['selectors'] ); $i++ ) {

				$query = '';
				if ( !empty( $color['queries'] ) && !empty( $color['queries'][$i] ) ) {
					$query = $color['queries'][$i];
				}

				$output .= theme_painter_build_style_rule( $color['selectors'][$i], $color['attributes'][$i], $color['value'], $query );
			}
		}

		set_transient( 'theme_painter_compiled_styles', $output );

		return $output;
	}
}

if ( !function_exists( 'theme_painter_build_style_rule' ) ) {
	/**
	 * Build a style rule from params
	 *
	 * @since 0.1
	 */
	function theme_painter_build_style_rule( $selector, $attribute, $value, $query = '' ) {

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
