Theme Painter
=============

Theme Painter is a simple library for adding color options to your WordPress
theme. Define panels, sections and controls in the customizer and have them
output to pre-defined CSS rules on the frontend.

Inspired by [Colorcase](https://github.com/UpThemes/Colorcase) by [UpThemes](https://upthemes.com/).


## Add a panel with a section with a control
```
$args = array(

	// Panels
	'panels' => array(

		'theme-colors' => array(
			'title' => __( 'Theme Colors', 'theme-slug' ),
			'priority' => 30,

			'sections' => array(

				'general' => array(
					'title' => __( 'General Colors', 'theme-slug' ),
					'priority' => 20,
					'colors' => array(
						'background' => array(
							'label' => __( 'Background Color', 'theme-slug' ),
							'selectors' => 'body',
							'attributes' => 'background',
							'default' => '#fafafa',
						),
						'text' => array(
							'label' => __( 'Text Color', 'theme-slug' ),
							'selectors' => 'body',
							'attributes' => 'color',
							'default' => '#242424',
						),
					),
				),
			),
		),
	),
);

add_theme_support( 'theme-painter', $args );
```

## Add a section with controls
```
$args = array(

	'sections' => array(

		// Add a custom color section
		'custom-color-section' => array(
			'title' => __( 'Custom Color Section', 'theme-slug' ),

			// Add color controls to the section
			'colors' => array(

				'background' => array(
					'label' => __( 'Background Color', 'theme-slug' ),
					'selectors' => 'body',
					'attributes' => 'background',
					'default' => '#fafafa',
				),
			),
		),
	),
);

add_theme_support( 'theme-painter', $args );
```

## Add a control to the core `colors` section
```
$args = array(

	'colors' => array(

		'background' => array(
			'label' => __( 'Background Color', 'theme-slug' ),
			'selectors' => 'body',
			'attributes' => 'background',
			'default' => '#fafafa',
		),
	),
);

add_theme_support( 'theme-painter', $args );
```

## Add a control to a pre-existing section
```
$args = array(

	'colors' => array(

		'background' => array(
			'label' => __( 'Background Color', 'theme-slug' ),
			'selectors' => 'body',
			'attributes' => 'background',
			'default' => '#fafafa',

			// Specify any pre-existing section
			'section' => 'my-custom-section',
		),
	),
);

add_theme_support( 'theme-painter', $args );
```

## Pass CSS selectors and attributes as strings or arrays, and match them up with media queries
```
$args = array(

	'colors' => array(

		'background' => array(
			'label' => __( 'Background Color', 'theme-slug' ),
			'selectors' => array( 'body', 'footer', 'header' ),
			'attributes' => array( 'background', 'background-color', 'background-color' ),
			'queries' => array( '', '', '@mediai(min-width: 768px)' ),
			'default' => '#fafafa',
		),
	),
);

add_theme_support( 'theme-painter', $args );
```


## Use any arguments supported by `add_panel`, `add_section` and `add_control`
```
$args = array(

	'panels' => array(

		// Panel
		// See: https://developer.wordpress.org/reference/classes/wp_customize_manager/add_panel/
		'theme-colors' => array(
			'title' => __( 'Theme Colors', 'theme-slug' ), // required
			'description' => '',                           // defult: null
			'priority' => 30,                              // default: null
			'capability' => 'edit_theme_options',          // default: edit_theme_options
			'theme_supports' => '',                        // default: null
			'sections' => array(

				// Section
				// See: https://developer.wordpress.org/reference/classes/wp_customize_manager/add_section/
				'general' => array(
					'title' => __( 'General Colors', 'theme-slug' ), // required
					'description' => '',                             // default: null
					'priority' => 20,                                // default: null
					'active_callback' => '',                         // default: null
					'colors' => array(

						// Color definition for setting and control
						// See: https://developer.wordpress.org/reference/classes/wp_customize_manager/add_setting/
						// See: https://developer.wordpress.org/reference/classes/wp_customize_manager/add_control/
						'background' => array(
							'label' => __( 'Background Color', 'theme-slug' ), // required
							'description' => '',                               // default: null
							'priority' => '',                                  // default: null
							'selectors' => 'body',                             // required
							'attributes' => 'background',                      // required
							'default' => '#fafafa',                            // required

							// You can set a specific section to override
							// the automatic placement if you really want
							'section' => '',                                   // default: null
						),
					),
				),
			),
		),
	),
);

add_theme_support( 'theme-painter', $args );
```
