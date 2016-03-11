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

if ( !function_exists( 'theme_painter_enqueue_scripts' ) ) {
	/**
	 * Print custom color styles on the frontend
	 *
	 * @since 0.1
	 */
	function theme_painter_enqueue_scripts() {

		$colors = theme_painter_get_settings();
		print_r( $colors );
	}
	add_action( 'wp_enqueue_scripts', 'theme_painter_enqueue_scripts' );
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
