<?php
/**
 * Registering for the WordPress Customizer
 * Block-wise structured OOP version - to easier detect possible bugs
 *
 * @author Konrad Sroka
 * @author Fabian Wolf
 * @package cc2
 * @since 2.0-rc1
 */




/**
 * cc2 Customizer Handler Class
 * As the name indicates, its handling all stuff with initalizations, set up and assets enqueuing for cc2
 * Partial rewrite / combining procedural code into one class.
 * 
 * @author Fabian Wolf
 * @since 2.0-rc1
 * @package cc2
 */
 

if( !class_exists( 'cc2_CustomizerLoader' ) ) {
	
	class cc2_CustomizerLoader {
		
		static $customizer_section_priority,
			$customizer_section_priority_call = array();
		
		function __construct() {
			
			$this->init_customizer_hooks();
			
		}
		
		public function init_customizer_hooks() {
			//__debug::log( __METHOD__ . ' fires ');
			
			// scripts
			add_action( 'customize_controls_enqueue_scripts', array( $this, 'load_customizer_scripts' ) );
			
			
			// set up actions
			add_action( 'customize_preview_init', array( $this, 'customizer_preview_init' ) );	
		}
		
		
		public static function get_customizer_section_priority( $section = false ) {
			$return = self::$customizer_section_priority;
			
			if( !empty( $section )) {
				if( isset( self::$customizer_section_priority_call[ $section ] ) ) {
					$return = self::$customizer_section_priority_call[ $section ];
				} else {				
					// missing prefix
					if( substr( $section, 0, 8 ) !== 'section_' && isset( self::$customizer_section_priority_call[ 'section_'. $section ] ) ) {
						$return = self::$customizer_section_priority_call[ 'section_'. $section ];
					}
				}
			} 
			
			return $return;
		}
		
		function load_customizer_scripts() {
			
			$customizer_data = self::prepare_preloaded_data();
			
			
			wp_enqueue_script(
				'cc2-customizer-helper', get_template_directory_uri() . '/includes/admin/js/customizer-helper.js', array('jquery', 'wp-color-picker')
			);
			wp_localize_script( 'cc2-customizer-helper', 'customizer_data', $customizer_data );
			
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );
	
		}
	
		public static function prepare_preloaded_data() {
			$return = array();
			
			
			// defaults for the dark and light navbar skin
			
			
			// light skin
			$return['navbar']['light'] = apply_filters('cc2_customizer_navbar_light_colors', array(
				'top_nav' => array(
					'top_nav_background_color' => 'fbfbfb',
					'top_nav_text_color' => 'F2694B',
					'top_nav_hover_text_color' => 'ffffff',
					
				),
				
				'bottom_nav' => array(
					'secondary_nav_background_color' => 'transparent',
					'secondary_nav_text_color' => 'F2694B',
					'secondary_nav_hover_text_color' => 'F2854B',
				),
			) );
			
			// dark skin
			$return['navbar']['dark'] = apply_filters('cc2_customizer_navbar_dark_colors', array(
				'top_nav' => array(
					'top_nav_background_color' => '101010',
					'top_nav_text_color' => 'a9a9a9',
					'top_nav_hover_text_color' => 'ffffff',
				),
				
				'bottom_nav' => array(
				
					'secondary_nav_background_color' => 'F2854B',
					'secondary_nav_text_color' => 'A9A9A9',
					'secondary_nav_hover_text_color' => 'ffffff',
				),
			) );
			
			// labels
			$return['button']['reset'] = apply_filters('cc2_customizer_button_reset_text', __('Reset settings', 'cc2') );
			
			// color schemes
			if( function_exists( 'cc2_get_color_schemes' ) ) {
				$return['color_schemes'] = cc2_get_color_schemes( true );
			}

		
			return $return;
		}
	

		/**
		 * Load and prepare all required variables and settings
		 */
		 
		function prepare_variables() {
			$return = array();
			$wp_date_format = trim( get_option('date_format', 'Y-m-d' ) ) . ' ' . trim( get_option('time_format', 'H:i:s' ) );
			
			// load base theme config
			if( defined( 'CC2_THEME_CONFIG' ) ) {
				$config = maybe_unserialize( CC2_THEME_CONFIG );
				
				$return['config'] = $config;
				
				
				
				$arrColorSchemes = cc2_get_color_schemes();
				$current_scheme = cc2_get_current_color_scheme();
				
				if( !empty( $arrColorSchemes ) ) {
					foreach( $arrColorSchemes as $strSchemeSlug => $arrSchemeData ) {
						$strSchemeTitle = $arrSchemeData['title'];
						
						if( isset( $arrSchemeData['_modified'] ) ) {
							$strSchemeTitle .= ' (' . date( $wp_date_format, $arrSchemeData['_modified'] ) . ')';	
						}
						$return['color_schemes'][$strSchemeSlug] = $strSchemeTitle;
					}
				}
				//new __debug( $current_scheme, __METHOD__ . ': current scheme' );
				
				$return['current_color_scheme'] = $current_scheme['slug'];
				
				/**
				 * TODO: Use cc2_ColorSchemes instead
				 */
				
				/*
				if( isset( $config['color_schemes'] ) && is_array( $config['color_schemes'] ) ) {
				
					foreach( $config['color_schemes'] as $strSchemeSlug => $arrSchemeParams ) {
				
						$return['color_schemes'][ $strSchemeSlug ] = $arrSchemeParams['title'];
						
						if( !empty($arrSchemeParams['scheme'] ) ) { // some may be just rudimentary added
							$return['color_scheme_previews'][ $strSchemeSlug ] = $arrSchemeParams['scheme'];
						} else {
							$return['color_scheme_previews'][ $strSchemeSlug ] = false;
						}
					}
				}*/
			}
			
			
			
			// Load Loop Templates
			$return['cc_loop_templates'] = array(
				'blog-style'		=> 'Blog style',
			);

			// If TK Loop Designer is loaded load the loop templates!
			$tk_loop_designer_options = get_option('tk_loop_designer_options', false);  // so??

			// Merge TK Loop Designer Templates with the CC Loop Templates array, if there are any
			if(defined('TK_LOOP_DESIGNER') && !empty( $tk_loop_designer_options) ) {
				// should now work ONLY _IF_ there were settings by the tk loop designer available
				if( !empty( $tk_loop_designer_options) && isset($tk_loop_designer_options['templates'])) {
					foreach ($tk_loop_designer_options['templates'] as $template_name => $loop_designer_option) {
						$return['cc_loop_templates'][$template_name] = $loop_designer_option;
					}
				}
			}

			// Load Fonts
			$return['cc2_font_family'] = apply_filters('cc2_customizer_load_font_family', cc2_customizer_load_fonts() );

			// Load Slideshow Positions
			$return['slider_positions'] = array(
				'cc_before_header'						=> 'before header',
				'cc_after_header'						=> 'after header',
				'cc_first_inside_main_content'			=> 'in main content',
				'cc_first_inside_main_content_inner'	=> 'in main content inner'
			);

			// Load Horizontal Positions
			$return['cc2_h_positions'] = array(
				'left'			=> 'left',
				/*'center'		=> 'center',*/ /** NOTE: Obsolete */
				'right'			=> 'right',
			);

			// Load Width Array
			$return['boxed'] = array(
				'boxed'			=> 'boxed',
				'fullwidth'		=> 'fullwidth',
			);





			// Load Effects for Incoming Animations
			$return['cc2_animatecss_start_moves'] = array(
				'hide'					=> 'hide',
				'no-effect' 			=> 'display, but no effect',
				'bounceInDown' 			=> 'bounce in down',
				'bounceInLeft' 			=> 'bounce in left',
				'bounceInRight' 		=> 'bounce in right',
				'bounceInUp' 			=> 'bounce in up',
				'bounceIn' 				=> 'bounce in',
				'fadeInDown' 			=> 'fade in down',
				'fadeInLeft' 			=> 'fade in left',
				'fadeInRight' 			=> 'fade in right',
				'fadeInUp' 				=> 'fade in up',
				'fadeIn' 				=> 'fade in',
				'lightSpeedIn' 			=> 'lightspeed in',
				'rollIn' 				=> 'roll in',
				'flipInX' 				=> 'flip in X',
				'flipInY' 				=> 'flip in Y'
			);


			$cc_slider_options = get_option('cc_slider_options');
			$return['cc_slider_options'] = $cc_slider_options;
			
			$return['cc_slideshow_template']['none'] = __('None', 'cc2');
			
			// Load slideshow templates
			if(isset($cc_slider_options) && is_array($cc_slider_options) ){
				foreach($cc_slider_options as $key => $slider_data) {
					/**
					 * Compatiblity layer for old beta 1 - 2 releases
					 */
					
					$strSlideshowID = $key;
					
					// old versions: key = title, newer versions: key = key, [key][title] = title
					if( !isset( $slider_data['title'] ) || empty( $slider_data['title']) ) {
						$strSlideshowName = $key;
					} else {
						$strSlideshowName = $slider_data['title'];
					}
					
					// check if type is set
					if( !empty( $slider_data['meta-data']['slideshow_type'] ) ) {
						$strSlideshowName .= ' (' . ucwords( str_replace('-', ' ', $slider_data['meta-data']['slideshow_type']) ) . ')';
					}
					
					$return['cc_slideshow_template'][ $strSlideshowID ] = $strSlideshowName;
					
					//$cc_slideshow_template[ $key ] = $slider_data['title'] . ' (' . ucwords( str_replace('-', ' ', $slider_data['meta-data']['slideshow_type']) ). ')';
				}
			}


			// Load Text Align Array
			$return['cc2_text_align'] = array(
				'left'		=> 'left',
				'center'	=> 'center',
				'right'		=> 'right'
			);

			// Set up Bootstrap columns
			$return['bootstrap_cols'] = array( 
				1 => '1', 
				2 => '2', 
				3 => '3',
				4 => '4',
				5 => '5',
				6 => '6',
				7 => '7',
				8 => '8',
				9 => '9',
				10 => '10',
				11 => '11',
				12 => '12',
			);	
			
			
			return apply_filters( 'cc2_customizer_prepare_variables',  $return );
		}
		
	
		function customizer_preview_init() {
		
			$tk_customizer_options = get_option('tk_customizer_options');
			
			if(isset( $tk_customizer_options['customizer_disabled'])) {
				return;
			}
			wp_enqueue_script('consoledummy');
			
			wp_enqueue_script('jquery');

			// load animate.css
			wp_enqueue_style( 'cc-animate-css');
			wp_enqueue_script( 'tk_customizer_preview_js',	get_template_directory_uri() . '/includes/admin/js/customizer.js', array( 'jquery', 'customize-preview' ), '', true );
		}
	
	
	
		function customizer_sanitizer( $data, $wp_instance = false ) {
			$return = $data;
			
			
			if( class_exists('Pasteur') ) {
				$return = Pasteur::sanitize_value( $data, $wp_instance );
			}
			
			return $return;
		}
		
	
	
		/**
		 * Does not sanitize anything, basically.
		 */
	
		function sanitize_default( $data ) {
			$return = $data;
			
			
			return $return;
		}
		
	}
	
	class cc2_CustomizerTheme extends cc2_CustomizerLoader {
		function __construct() {
			
			
			
			add_action( 'init', array( $this, 'customizer_init' ) );
			
			// add global sanitizer
			add_action('sanitize_option_theme_mods_cc2', array( $this, 'customizer_sanitizer' ) );
			// its unclear whether this is a filter or an action
			add_filter('sanitize_option_theme_mods_cc2', array( $this, 'customizer_sanitizer' ) );
			
		}
		
		function customizer_init() {
			
			$this->init_customizer_hooks();
			
			/**
			 * NOTE: Originally was required to stay with the mega-function call .. cause else its getting pretty .. ugly. But: This might be the cause for multitudinous bugs. So I restructured into several function blocks. Still looking pretty ugly.
			 */
			
			// add global sanitizer .. althought that doesnt seem to work .. which is NASTY.
			add_action('sanitize_option_theme_mods_cc2', array( $this, 'customizer_sanitizer' ) );
			// its unclear whether this is a filter or an action
			add_filter('sanitize_option_theme_mods_cc2', array( $this, 'customizer_sanitizer' ) );
			
			
			// initial function call. don't change if you don't know what you're doing!
			add_action('customizer_register', array( $this, 'customize_default_sections' ),10 );
			
		
			
			// rest of the calls, ordered by priority (somewhat)
			/*$arrCustomizerSections = array(
				'section_color_schemes',
				'section_background',
				'section_static_frontpage',
				'section_title_tagline',
				'section_header',
				'section_nav',
				'section_branding',
				'section_content',
				'section_layouts',
				'section_widgets',
				'section_typography',
				'section_footer',
				'section_blog',
				'section_slider',
				'section_customize_bootstrap',
				
			);
			
			foreach( $arrCustomizerSections as $strMethod ) {
			
				add_action('customizer_register', array( $this,  ) );
			}*/
			
			self::$customizer_section_priority = 10;
			self::$customizer_section_priority_call['section_color_schemes'] = self::$customizer_section_priority;
			add_action( 'customize_register', array( $this, 'section_color_schemes' ),11 );
			
			
			self::$customizer_section_priority += 20;
			self::$customizer_section_priority_call['section_title_tagline'] = self::$customizer_section_priority;
			add_action( 'customize_register', array( $this, 'section_title_tagline' ));
	
			self::$customizer_section_priority += 20;
			self::$customizer_section_priority_call['section_typography'] = self::$customizer_section_priority;
			add_action( 'customize_register', array( $this, 'section_typography' ));
			
			self::$customizer_section_priority += 20;
			self::$customizer_section_priority_call['section_background'] = self::$customizer_section_priority;
			add_action( 'customize_register', array( $this, 'section_background' ));
			
			self::$customizer_section_priority += 20;
			self::$customizer_section_priority_call['section_header'] = self::$customizer_section_priority;
			add_action( 'customize_register', array( $this, 'section_header' ));
			
			self::$customizer_section_priority += 20;
			self::$customizer_section_priority_call['section_nav'] = self::$customizer_section_priority;
			add_action( 'customize_register', array( $this, 'section_nav' ));
			
			self::$customizer_section_priority += 20;
			self::$customizer_section_priority_call['section_branding'] = self::$customizer_section_priority;
			add_action( 'customize_register', array( $this, 'section_branding' ));
			
			self::$customizer_section_priority += 20;
			self::$customizer_section_priority_call['section_static_frontpage'] = self::$customizer_section_priority;
			add_action( 'customize_register', array( $this, 'section_static_frontpage' ));
			
			self::$customizer_section_priority += 20;
			self::$customizer_section_priority_call['section_content'] = self::$customizer_section_priority;
			add_action( 'customize_register', array( $this, 'section_content' ));

			self::$customizer_section_priority += 20;
			self::$customizer_section_priority_call['section_blog'] = self::$customizer_section_priority;
			add_action( 'customize_register', array( $this, 'section_blog' ));
			
			self::$customizer_section_priority += 20;
			self::$customizer_section_priority_call['section_slider'] = self::$customizer_section_priority;
			add_action( 'customize_register', array( $this, 'section_slider' ));
			
			self::$customizer_section_priority += 20;
			self::$customizer_section_priority_call['section_layouts'] = self::$customizer_section_priority;
			add_action( 'customize_register', array( $this, 'section_layouts' ));
			
			self::$customizer_section_priority += 20;
			self::$customizer_section_priority_call['section_widgets'] = self::$customizer_section_priority;
			add_action( 'customize_register', array( $this, 'section_widgets' ));

			self::$customizer_section_priority += 20;
			self::$customizer_section_priority_call['section_footer'] = self::$customizer_section_priority;
			add_action( 'customize_register', array( $this, 'section_footer' ));
			
			
			self::$customizer_section_priority += 20;
			self::$customizer_section_priority_call['section_customize_bootstrap'] = self::$customizer_section_priority;
			add_action( 'customize_register', array( $this, 'section_customize_bootstrap' ));
		
			
			//add_action( 'customize_register', 'tk_customizer_support' );
		}
		
		/**
		 * Goes first
		 */
		
		function customize_default_sections( $wp_customize ) {
			// Changing some section titles, ordering and updating
			$wp_customize->remove_section( 'background_image' );
			$wp_customize->get_section( 'colors' 			) -> title 		= 'Color Scheme';
			//$wp_customize->get_section( 'colors' 			) -> priority	= 30;
			//$wp_customize->get_section( 'static_front_page' ) -> priority 	= 200;
			$wp_customize->get_section( 'static_front_page' ) -> title 		= 'Homepage';
			
			/**
			 * FIXME: doesnt seem to work thou .. :(
			 */
			$wp_customize->get_setting( 'background_color' 	)->transport = 'postMessage';
			$wp_customize->get_setting( 'background_image' 	)->transport = 'postMessage';
			
			
			$wp_customize->get_control( 'background_color' 	) -> section 	= 'background';
			$wp_customize->get_control( 'background_image' 	) -> section 	= 'background';
			
			$wp_customize->get_control( 'header_image' 		) -> section 	= 'header';
			$wp_customize->get_control( 'header_textcolor' 	) -> section 	= 'title_tagline';
				
		}
		
		/**
		 * Regular sections
		 */
		
	
		
		/**
		 * Switch color schemes
		 */
		
		function section_color_schemes( $wp_customize ) {
			extract ( $this->prepare_variables() );
			
			// customize from default WP
			//self::$customizer_section_priority
			//$wp_customize->get_section( 'colors') -> priority = 30;
			
			
			
			$wp_customize->get_section( 'colors')->priority = self::$customizer_section_priority;
			
			if( !empty( $color_schemes ) ) {
				// mind the test scheme
				if( !defined('CC2_THEME_DEBUG' ) && isset( $color_schemes['_test'] ) ) {
					unset( $color_schemes['_test'] );
				}
				
			
				// Color Scheme
				 $wp_customize->add_setting( 'color_scheme', array(
					'default'      => 'default',
					'capability'   => 'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' 	=> 'sanitize_key',
				 ) );
				 
				 $wp_customize->add_control( 'color_scheme', array(
					'label'   		=> 	__('Choose a scheme', 'cc2'),
					'section' 		=> 	'colors',
					'priority'		=> 	5,
					'type'    		=> 	'radio',
					'choices'    	=> 	$color_schemes,
				) );
				
				// Color Scheme Notice
				 $wp_customize->add_setting( 'notice_color_scheme', array(
					 'capability'   => 'edit_theme_options',
					 'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
				 ) );
				 
				$wp_customize->add_control( 
					new Description( $wp_customize, 'notice_color_scheme', array(
						'label' 		=> 	__('Note: Switching is also going to change quite a lot of other settings, including Font, Link and Link Hover Color. Better to save your current changes before-hand.', 'cc2'),
						'type' 			=> 	'description',
						'section' 		=> 	'colors',
						'priority'		=> 	6,
						
					) 
				) );
			}
		}
		
		/**
		 * Background section
		 */


		function section_background( $wp_customize ) {
			extract( $this->prepare_variables() );		
			// Background Section
			$wp_customize->add_section( 'background', array(
				'title'         => 	'Background',
				'priority'      => 	45,
			) );
		}
		
		/**
		 * static_front_page aka Home Page
		 * NOTE: Built-in section
		 */
		
		
		function section_static_frontpage( $wp_customize ) {
			extract( $this->prepare_variables() );
			
			// change default WP priority
			$wp_customize->get_section( 'static_front_page' )->priority = self::$customizer_section_priority;
			
			
			 // Hide all Content on Frontpage
			$wp_customize->add_setting( 'hide_front_page_content', array(
				'default'       =>  false,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('hide_front_page_content', array(
				'label'    		=> 	__('Hide all content on the front page', 'cc2'),
				'section'  		=> 	'static_front_page',
				'type'     		=> 	'checkbox',
				'priority'		=> 	261,
			) );

		}
		
		/**
		 * Site Title & Tagline
		 */
		function section_title_tagline( $wp_customize ) {
			extract( $this->prepare_variables() );
			
			
			// here we need to add some extra options first..

			// Site Title Font Family
			$wp_customize->add_setting( 'site_title_font_family', array(
				'default'       => 	'Pacifico',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_text'),
			) );
			$wp_customize->add_control( 'site_title_font_family', array(
				'label'   		=> 	__('Site Title Font Family', 'cc2'),
				'section' 		=> 	'title_tagline',
				'priority'		=> 	180,
				'type'    		=> 	'select',
				'choices'    	=> 	$cc2_font_family
			) );

			// TK Google Fonts Ready! - A Quick Note
			$wp_customize->add_setting( 'google_fonts_note_site_title', array(
				'capability'    => 	'edit_theme_options',
				'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control( new Description( $wp_customize, 'google_fonts_note_site_title', array(
				'label' 		=> 	__('Add Google Fonts and make them available in the theme options with <a href="http://themekraft.com/store/tk-google-fonts-wordpress-plugin/" target="_blank">TK Google Fonts</a>.', 'cc2'),
				'type' 			=> 	'description',
				'section' 		=> 	'title_tagline',
				'priority'		=> 	181,
			) ) );


			// Site Title Position
			$wp_customize->add_setting( 'site_title_position', array(
				'default'       => 	'left',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_text'),
			) );
			$wp_customize->add_control( 'site_title_position', array(
				'label'   		=> 	__('Site Title Position', 'cc2'),
				'section' 		=> 	'title_tagline',
				'priority'		=> 	200,
				'type'    		=> 	'select',
				'choices'    	=> 	$cc2_h_positions
			) );


			// Tagline font family
			 $wp_customize->add_setting( 'tagline_font_family', array(
					'default'       => 	'inherit',
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_text'),
				) );
			$wp_customize->add_control( 'tagline_font_family', array(
				'label'   		=> 	__('Tagline Font Family', 'cc2'),
				'section' 		=> 	'title_tagline',
				'priority'		=> 	200,
				'type'    		=> 	'select',
				'choices'    	=> 	$cc2_font_family
			) );

			// Tagline color
			$wp_customize->add_setting('tagline_text_color', array(
				'default'           	=> '#a9a9a9',
				'capability'        	=> 'edit_theme_options',
				'transport'   			=> 'refresh',
				
				'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
				'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
				) );
				
			$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'tagline_text_color', array(
				'label'    				=> __('Tagline Color', 'cc2'),
				'section'  				=> 'title_tagline',
				'priority'				=> 201,
			) ) );

		}
		
		function section_header( $wp_customize ) {
			extract( $this->prepare_variables() );
			
		// Header Section
			$has_static_frontpage = ( get_option( 'show_on_front') == 'page' ? true : false );
			
			$wp_customize->add_section( 'header', array(
				'title'         => 	'Header',
				//'priority'      => 	60,
				'priority' => self::$customizer_section_priority,
			) );

				// Display Header
				$wp_customize->add_setting( 'display_header_heading', array(
					'capability'    => 	'edit_theme_options',
					'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
				) );
				$wp_customize->add_control( new Label( $wp_customize, 'display_header_heading', array(
					'label' 		=> 	__('Display Header', 'cc2'),
					'type' 			=> 	'label',
					'section' 		=> 	'header',
					'priority'		=> 	20,
				) ) );

				/**
				 * Static frontpage is set, ie. home != blog
				 * 
				 * @see http://codex.wordpress.org/Function_Reference/is_home#Blog_Posts_Index_vs._Site_Front_Page
				 */
				if( $has_static_frontpage != false ) { // static front page is set
					// Display Header on Home
					$wp_customize->add_setting( 'display_header_home', array(
						'default'		=>	false,
						'capability'    => 	'edit_theme_options',
						'transport'   	=> 	'refresh',
						'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
					) );
					$wp_customize->add_control('display_header_home', array(
						'label'    		=> 	__('on blog', 'cc2'),
						'section'  		=> 	'header',
						'type'     		=> 	'checkbox',
						'priority'		=> 	40,
					) );
					
					
					// Display Header on Frontpage
					$wp_customize->add_setting( 'display_header_static_frontpage', array(
						'default'		=>	true,
						'capability'    => 	'edit_theme_options',
						'transport'   	=> 	'refresh',
						'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
					) );
					$wp_customize->add_control('display_header_static_frontpage', array(
						'label'    		=> 	__('on static frontpage', 'cc2'),
						'section'  		=> 	'header',
						'type'     		=> 	'checkbox',
						'priority'		=> 	41,
					) );
				} else { // no static frontpage; home == blog
					// Display Header on Home
					$wp_customize->add_setting( 'display_header_home', array(
						'default'		=>	true,
						'capability'    => 	'edit_theme_options',
						'transport'   	=> 	'refresh',
						'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
					) );
					$wp_customize->add_control('display_header_home', array(
						'label'    		=> 	__('on homepage', 'cc2'),
						'section'  		=> 	'header',
						'type'     		=> 	'checkbox',
						'priority'		=> 	40,
					) );
					
					
				}

				// Display Header on Posts
				$wp_customize->add_setting( 'display_header_posts', array(
					'default'		=>	true,
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
				) );
				$wp_customize->add_control('display_header_posts', array(
					'label'    		=> 	__('on posts', 'cc2'),
					'section'  		=> 	'header',
					'type'     		=> 	'checkbox',
					'priority'		=> 	50,
				) );

				// Display Header on Pages
				$wp_customize->add_setting( 'display_header_pages', array(
					'default'		=>	true,
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
				) );
				$wp_customize->add_control('display_header_pages', array(
					'label'    		=> 	__('on pages', 'cc2'),
					'section'  		=> 	'header',
					'type'     		=> 	'checkbox',
					'priority'		=> 	60,
				) );

				// Display Header on Archive
				$wp_customize->add_setting( 'display_header_archive', array(
					'default'		=>	true,
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
				) );
				$wp_customize->add_control('display_header_archive', array(
					'label'    		=> 	__('on archive', 'cc2'),
					'section'  		=> 	'header',
					'type'     		=> 	'checkbox',
					'priority'		=> 	70,
				) );

				// Display Header on Search
				$wp_customize->add_setting( 'display_header_search', array(
					'default'		=>	true,
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
				) );
				$wp_customize->add_control('display_header_search', array(
					'label'    		=> 	__('on search', 'cc2'),
					'section'  		=> 	'header',
					'type'     		=> 	'checkbox',
					'priority'		=> 	80,
				) );

				// Display Header on 404
				$wp_customize->add_setting( 'display_header_404', array(
					'default'		=>	true,
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
				) );
				$wp_customize->add_control('display_header_404', array(
					'label'    		=> 	__('on 404: not-found', 'cc2'),
					'section'  		=> 	'header',
					'type'     		=> 	'checkbox',
					'priority'		=> 	90,
				) );

				// Header Height
				$wp_customize->add_setting('header_height', array(
					'default' 		=> 'auto',
					'capability'    => 'edit_theme_options',
					'transport'   	=> 'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				) );
				$wp_customize->add_control('header_height', array(
					'label'      	=> __('Header Height', 'cc2'),
					'section'    	=> 'header',
					'priority'   	=> 120,
				) );

				// Notice on Header Height
				$wp_customize->add_setting( 'header_height_note', array(
					'capability'    => 	'edit_theme_options',
					'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
				) );
				$wp_customize->add_control( new Description( $wp_customize, 'header_height_note', array(
					'label' 		=> 	__('<small><em>Write "auto" or in px, like "200px"</em></small>', 'cc2'),
					'type' 			=> 	'description',
					'section' 		=> 	'header',
					'priority'		=> 	121,
				) ) );

				// differentiate between home and blog (ie. static frontpage IS set)

				if( $has_static_frontpage != false ) { // blog != home
					// Header Height on Homepage
					$wp_customize->add_setting('header_height_blog', array(
						'default' 		=> 'auto',
						'capability'    => 'edit_theme_options',
						'transport'   	=> 'refresh',
						'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
					) );
					$wp_customize->add_control('header_height_blog', array(
						'label'      	=> __('Header Height on Blog', 'cc2'),
						'section'    	=> 'header',
						'priority'   	=> 140,
					) );

					// Notice on Header Height Homepage
					$wp_customize->add_setting( 'header_height_blog_note', array(
						'capability'    => 	'edit_theme_options',
						'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
					) );
					$wp_customize->add_control( new Description( $wp_customize, 'header_height_blog_note', array(
						'label' 		=> 	sprintf( __('<small><em>Just for the %s, also &quot;auto&quot; or in px</em></small>', 'cc2'), __('blog', 'cc2') ),
						'type' 			=> 	'description',
						'section' 		=> 	'header',
						'priority'		=> 	141,
					) ) );

					// Header Height on Homepage
					$wp_customize->add_setting('header_height_home', array(
						'default' 		=> 'auto',
						'capability'    => 'edit_theme_options',
						'transport'   	=> 'refresh',
						'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
					) );
					$wp_customize->add_control('header_height_home', array(
						'label'      	=> __('Header Height on Homepage', 'cc2'),
						'section'    	=> 'header',
						'priority'   	=> 145,
					) );

					// Notice on Header Height Homepage
					$wp_customize->add_setting( 'header_height_home_note', array(
						'capability'    => 	'edit_theme_options',
						'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
					) );
					$wp_customize->add_control( new Description( $wp_customize, 'header_height_home_note', array(
						'label' 		=> 	sprintf( __('<small><em>Just for the %s, also &quot;auto&quot; or in px</em></small>', 'cc2'), __('Homepage', 'cc2') ),
						'type' 			=> 	'description',
						'section' 		=> 	'header',
						'priority'		=> 	146,
					) ) );
					
					
				
				} else { // blog == home
					// Header Height on Homepage
					$wp_customize->add_setting('header_height_home', array(
						'default' 		=> 'auto',
						'capability'    => 'edit_theme_options',
						'transport'   	=> 'refresh',
						'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
					) );
					$wp_customize->add_control('header_height_home', array(
						'label'      	=> __('Header Height on Homepage', 'cc2'),
						'section'    	=> 'header',
						'priority'   	=> 140,
					) );

					// Notice on Header Height Homepage
					$wp_customize->add_setting( 'header_height_home_note', array(
						'capability'    => 	'edit_theme_options',
						'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
					) );
					$wp_customize->add_control( new Description( $wp_customize, 'header_height_home_note', array(
						'label' 		=> 	__('<small><em>Just for the homepage, also "auto" or in px</em></small>', 'cc2'),
						'type' 			=> 	'description',
						'section' 		=> 	'header',
						'priority'		=> 	141,
					) ) );
				}
			

				// Header Background Color
				$wp_customize->add_setting( 'header_background_color', array(
					'default'           	=> 'fff',
					'capability'        	=> 'edit_theme_options',
					'transport'   			=> 'refresh',
					'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
					'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
				) );
				$wp_customize->add_control( new cc2_Customize_Color_Control($wp_customize, 'header_background_color', array(
					'label'    				=> __('Header Background Color', 'cc2'),
					'section'  				=> 'header',
					'priority'				=> 220,
				) ) );
				
				 // Header Background Image (if you want the site title still being displayed, over the header)
				$wp_customize->add_setting( 'header_background_image', array(
					'default'           	=> '',
					'capability'        	=> 'edit_theme_options',
					'transport'   			=> 'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				) );
				$wp_customize->add_control( new WP_Customize_Image_Control($wp_customize, 'header_background_image', array(
					'label'    				=> __('Header Background Image', 'cc2'),
					'section'  				=> 'header',
					'priority'				=> 221,
				) ) );
	
			
		}
		
		// Adding to Navigation Section (Nav)

		function section_nav( $wp_customize ) {
			extract( $this->prepare_variables() );
			
			
			// Header Top Nav - Fix to top
			$wp_customize->add_setting( 'fixed_top_nav', array(
				'default'		=>	true,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('fixed_top_nav', array(
				'label'    		=> 	__('Top nav fixed to top?', 'cc2'),
				'section'  		=> 	'nav',
				'type'     		=> 	'checkbox',
				'priority'		=> 	40,
			) );
			
			// Top Nav Position
			$wp_customize->add_setting( 'top_nav_position', array(
				'default'       => 	'left',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				
			) );
			$wp_customize->add_control( 'top_nav_position', array(
				'label'   		=> 	__('Top Nav Position', 'cc2'),
				'section' 		=> 	'nav',
				'priority'		=> 	50,
				'type'    		=> 	'select',
				'choices'    	=> $cc2_h_positions
			) );
			
			// Secondary Nav Position
			$wp_customize->add_setting( 'secondary_nav_position', array(
				'default'       => 	'left',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control( 'secondary_nav_position', array(
				'label'   		=> 	__('Secondary Nav Position', 'cc2'),
				'section' 		=> 	'nav',
				'priority'		=> 	55,
				'type'    		=> 	'select',
				'choices'    	=> $cc2_h_positions
			) );
			
			

			// Use dark colors - a small Heading
			$wp_customize->add_setting( 'heading_nav_use_dark_colors', array(
				'capability'    => 	'edit_theme_options',
				'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control( new Label( $wp_customize, 'heading_nav_use_dark_colors', array(
				'label' 		=> 	__('Use dark colors?', 'cc2'),
				'type' 			=> 	'label',
				'section' 		=> 	'nav',
				'priority'		=> 	60,
			) ) );

			// Header Top Nav - Dark Colors

			
			// Header Top Nav - Dark Colors
			$wp_customize->add_setting( 'color_scheme_top_nav', array(
				'default'		=>	'light',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control('color_scheme_top_nav', array(
				'label'    		=> 	__('Top nav color scheme', 'cc2'),
				'section'  		=> 	'nav',
				'type'     		=> 	'select',
				'choices'		=> array(
					'dark' => 'Dark',
					'light' => 'Light (Default)',
					'custom' => 'Custom',
				),
				'priority'		=> 	81,
			) );
			
			 // Header Top Nav - Dark Colors
			$wp_customize->add_setting( 'color_scheme_bottom_nav', array(
				'default'		=>	'light',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control('color_scheme_bottom_nav', array(
				'label'    		=> 	__('Bottom nav color scheme', 'cc2'),
				'section'  		=> 	'nav',
				'type'     		=> 	'select',
				'choices'		=> array(
					'dark' => 'Dark',
					'light' => 'Light (Default)',
					'custom' => 'Custom',
				),
				'priority'		=> 	83,
			) );

			// Header Bottom Nav - Dark Colors
			$wp_customize->add_setting( 'info_dark_nav', array(
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control(new Description( $wp_customize, 'info_dark_nav', array(
				'label'    		=> 	sprintf( __('<strong>Warning:</strong> Changing any of the above options is likely going to <strong>reset</strong> your current navigation color settings. It\'s suggested to either save the current customizer setup or <a href="%s">backup your current settings</a> before-hand.', 'cc2'), admin_url( apply_filters('cc2_tab_admin_url', 'themes.php?page=cc2-settings&tab=backup') ) ),
				'section'  		=> 	'nav',
				'priority'		=> 	101,
			) ) );
	 
			


		/**
		 * Top nav color settings
		 * 
		 * TODO: Add some kind of additional sectioning / partitioning .. or maybe an "Advanced Settings" or "Quick / Advanced Settings" switch
		 */

		$nav_section_priority = 170;

			// top nav background color
			$wp_customize->add_setting('top_nav_background_color', array(
			'default'           	=> '#2f2f2f',
			'capability'        	=> 'edit_theme_options',
			'transport'   			=> 'refresh',
			'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
			'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
			) );
			
			$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'top_nav_background_color', array(
			'label'    				=> __('Top Nav Background Color', 'cc2'),
			'section'  				=> 'nav',
			'priority'				=> $nav_section_priority,
			) ) );
			$nav_section_priority++;
			
			// top nav text color
			$wp_customize->add_setting('top_nav_text_color', array(
			'default'           	=> '#a9a9a9',
			'capability'        	=> 'edit_theme_options',
			'transport'   			=> 'refresh',
			'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
			'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
			) );
			
			$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'top_nav_text_color', array(
			'label'    				=> __('Top Nav Font Color', 'cc2'),
			'section'  				=> 'nav',
			'priority'				=> $nav_section_priority,
			) ) );
			$nav_section_priority++;
			
			
			// top nav hover text color
			$wp_customize->add_setting('top_nav_hover_text_color', array(
			'default'           	=> '#fff',
			'capability'        	=> 'edit_theme_options',
			'transport'   			=> 'refresh',
			'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
			'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
			) );
			
			$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'top_nav_hover_text_color', array(
			'label'    				=> __('Top Nav Hover Font Color', 'cc2'),
			'section'  				=> 'nav',
			'priority'				=> $nav_section_priority,
			) ) );
			
			$nav_section_priority+=10;
			
		/**
		 * Secondary nav color settings
		 */
			// secondary nav background color
			$wp_customize->add_setting('secondary_nav_background_color', array(
			'default'           	=> '#2f2f2f',
			'capability'        	=> 'edit_theme_options',
			'transport'   			=> 'refresh',
			'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
			'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
			) );
			
			$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'secondary_nav_background_color', array(
			'label'    				=> __('Secondary Nav Background Color', 'cc2'),
			'section'  				=> 'nav',
			'priority'				=> $nav_section_priority,
			) ) );
			$nav_section_priority++;
			
			// secondary nav text color
			$wp_customize->add_setting('secondary_nav_text_color', array(
			'default'           	=> '#a9a9a9',
			'capability'        	=> 'edit_theme_options',
			'transport'   			=> 'refresh',
			'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
			'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
			) );
			
			$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'secondary_nav_text_color', array(
			'label'    				=> __('Secondary Nav Font Color', 'cc2'),
			'section'  				=> 'nav',
			'priority'				=> $nav_section_priority,
			) ) );
			$nav_section_priority++;
			
			// secondary nav hover text color
			$wp_customize->add_setting('secondary_nav_hover_text_color', array(
			'default'           	=> '#fff',
			'capability'        	=> 'edit_theme_options',
			'transport'   			=> 'refresh',
			'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
			'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
			) );
			
			$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'secondary_nav_hover_text_color', array(
			'label'    				=> __('Secondary Nav Hover Font Color', 'cc2'),
			'section'  				=> 'nav',
			'priority'				=> $nav_section_priority,
			) ) );
			$nav_section_priority++;

			
		}
		
		/**
		 * Seperate Branding section (before navigation, below header section)
		 */
		
		function section_branding( $wp_customize ) {
			extract( $this->prepare_variables() );

			//$branding_section_priority = 70;
			$branding_section_priority = self::$customizer_section_priority;
		 
			$wp_customize->add_section( 'branding', array(
				'title' =>	__( 'Branding', 'cc2' ),
				'priority' => $branding_section_priority,
			) );
			
			$branding_section_priority+=5;
		
		
		
			// Add Branding - A small Heading
			$wp_customize->add_setting( 'add_nav_brand', array(
				'capability'    => 	'edit_theme_options',
				'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control( new Label( $wp_customize, 'add_nav_brand', array(
				'label' 		=> 	__('Add your branding?', 'cc2'),
				'type' 			=> 	'label',
				'section' 		=> 	'branding',
				'priority'		=> 	$branding_section_priority++,
			) ) );
			//$branding_section_priority++;  
			
			// Header Top Nav - Add Branding
			/**
			 * NOTE: Missing default value!
			 */
			$wp_customize->add_setting( 'top_nav_brand', array(
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'default'		=> false,
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('top_nav_brand', array(
				'label'    		=> 	__('for top nav', 'cc2'),
				'section'  		=> 	'branding',
				'type'     		=> 	'checkbox',
				'priority'		=> 	$branding_section_priority++,
			) );
			//$branding_section_priority++;

			// Header Top Nav - Branding: Color
			$wp_customize->add_setting( 'top_nav_brand_text_color', array(
				'default'           	=> '#a9a9a9',
				'capability'        	=> 'edit_theme_options',
				'transport'   			=> 'refresh',
				'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
				'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
			) );
			$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'top_nav_brand_text_color', array(
				'label'    		=> 	__('Top nav branding Font Color', 'cc2'),
				'section'  		=> 	'branding',
				'priority'		=> 	$branding_section_priority++,
			) ) );
			//$branding_section_priority++;
			
			
			// Header Top Nav - Branding: Image instead text
			/**
			 * FIXME: default value seems to be wrong / impossible / bug-prone
			 */
			
			$wp_customize->add_setting('top_nav_brand_image', array(
				'default'           	=> '',
				'capability'        	=> 'edit_theme_options',
				'transport'   			=> 'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			
			$wp_customize->add_control( new  WP_Customize_Image_Control($wp_customize, 'top_nav_brand_image', array(
				'label'    				=> __('Top nav brand image', 'cc2'),
				'section'  				=> 'branding',
				'priority'				=> $branding_section_priority++,
			) ) );
			//$branding_section_priority++;
			
			
			// Branding: Header Bottom Nav
			
			$branding_section_priority+=5;
			
			/**
			 * NOTE: missing default value
			 */

			// Header Bottom Nav - Add Branding
			$wp_customize->add_setting( 'bottom_nav_brand', array(
				'default'		=> true,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('bottom_nav_brand', array(
				'label'    		=> 	__('for bottom nav', 'cc2'),
				'section'  		=> 	'branding',
				'type'     		=> 	'checkbox',
				'priority'		=> 	$branding_section_priority++,
			) );
			//$branding_section_priority++;
		   
			// Header Top Nav - Branding: Color
			$wp_customize->add_setting( 'bottom_nav_brand_text_color', array(
				'default'           	=> '#a9a9a9',
				'capability'        	=> 'edit_theme_options',
				'transport'   			=> 'refresh',
				'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
				'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
			) );
			$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'bottom_nav_brand_text_color', array(
				'label'    		=> 	__('Bottom nav branding Font Color', 'cc2'),
				'section'  		=> 	'branding',
				'priority'		=> 	$branding_section_priority++,
			) ) );
			//$branding_section_priority++;
			
			/**
			 * NOTE: default value seems to be wrong as well
			 */
			
			$wp_customize->add_setting('bottom_nav_brand_image', array(
				'default'           	=> '',
				'capability'        	=> 'edit_theme_options',
				'transport'   			=> 'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			
			$wp_customize->add_control( new  WP_Customize_Image_Control($wp_customize, 'bottom_nav_brand_image', array(
				'label'    				=> __('Bottom nav brand image', 'cc2'),
				'section'  				=> 'branding',
				'priority'				=> $branding_section_priority++,
			) ) );

		}
		
		
		/**
		 * Content Section
		 */
		
		function section_content( $wp_customize ) {
			extract( $this->prepare_variables() );


			$wp_customize->add_section( 'content', array(
				'title' =>	__( 'Content', 'cc2' ),
				/*'priority' => 260,*/
				'priority' => self::$customizer_section_priority,
			) );
			

				/*
				 * NOTE: The theme_check plugin is too dumb programmed to recognize commented out code.
				 // Hide all Content
				$wp_customize->add_setting( 'hide_page_content', array(
					'default'       =>  false,
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				) );
				$wp_customize->add_control('hide_page_content', array(
					'label'    		=> 	__('Hide all content on all (!) pages', 'cc2'),
					'section'  		=> 	'content',
					'type'     		=> 	'checkbox',
					'priority'		=> 	261,
				) );*/


			/**
			 * 
					- Hide Page Titles (checkboxes, "hide on...": All, Homepage, Archives, Post, Page, Attachment ) --> CSS output  => NOPE. php-side!
					- Center Content Titles (checkboxes, "center on... ": All, Homepage, Archives, Post, Page, Attachment ) --> CSS output
			*/
			$display_page_title_priority = 262;
			// Hide selected page titles
			
			// Display Header
			/**
			 * NOTE: Missing default
			 */
			$wp_customize->add_setting( 'display_page_title_heading', array(
				'capability'    => 	'edit_theme_options',
				'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control( new Label( $wp_customize, 'display_page_title_heading', array(
				'label' 		=> 	__('Display Page Title ...', 'cc2'),
				'type' 			=> 	'label',
				'section' 		=> 	'content',
				'priority'		=> 	$display_page_title_priority,
			) ) );
			$display_page_title_priority++;

			// Display Header on Home
			$wp_customize->add_setting( 'display_page_title[home]', array(
				'default'		=>	true,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('display_page_title[home]', array(
				'label'    		=> 	__('on homepage', 'cc2'),
				'section'  		=> 	'content',
				'type'     		=> 	'checkbox',
				'priority'		=> 	$display_page_title_priority,
			) );
			$display_page_title_priority++;

			// Display Header on Posts
			$wp_customize->add_setting( 'display_page_title[posts]', array(
				'default'		=>	true,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('display_page_title[posts]', array(
				'label'    		=> 	__('on posts', 'cc2'),
				'section'  		=> 	'content',
				'type'     		=> 	'checkbox',
				'priority'		=> 	$display_page_title_priority,
			) );
			$display_page_title_priority++;

			// Display Header on Pages
			$wp_customize->add_setting( 'display_page_title[pages]', array(
				'default'		=>	true,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('display_page_title[pages]', array(
				'label'    		=> 	__('on pages', 'cc2'),
				'section'  		=> 	'content',
				'type'     		=> 	'checkbox',
				'priority'		=> 	$display_page_title_priority,
			) );
			$display_page_title_priority++;
			

			// Display Header on Archive
			$wp_customize->add_setting( 'display_page_title[archive]', array(
				'default'		=>	true,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('display_page_title[archive]', array(
				'label'    		=> 	__('on archive', 'cc2'),
				'section'  		=> 	'content',
				'type'     		=> 	'checkbox',
				'priority'		=> 	$display_page_title_priority,
			) );
			$display_page_title_priority++;

			// Display Header on Search
			$wp_customize->add_setting( 'display_page_title[search]', array(
				'default'		=>	true,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('display_page_title[search]', array(
				'label'    		=> 	__('on search', 'cc2'),
				'section'  		=> 	'content',
				'type'     		=> 	'checkbox',
				'priority'		=> 	$display_page_title_priority,
			) );
			$display_page_title_priority++;
			

			// Display Header on 404
			$wp_customize->add_setting( 'display_page_title[error]', array(
				'default'		=>	true,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('display_page_title[error]', array(
				'label'    		=> 	__('on 404: not-found', 'cc2'),
				'section'  		=> 	'content',
				'type'     		=> 	'checkbox',
				'priority'		=> 	$display_page_title_priority,
			) );
			
			$display_page_title_priority++;
		
		// Center titles
			$center_title_priority = $display_page_title_priority + 1;
		
		
			$wp_customize->add_setting( 'center_title_heading', array(
				'capability'    => 	'edit_theme_options',
				'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control( new Label( $wp_customize, 'center_title_heading', array(
				'label' 		=> 	__('Center Page Title ...', 'cc2'),
				'type' 			=> 	'label',
				'section' 		=> 	'content',
				'priority'		=> 	$center_title_priority,
			) ) );
			$center_title_priority++;

			// center Header on Home
			$wp_customize->add_setting( 'center_title[global]', array(
				'default'		=>	false,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('center_title[global]', array(
				'label'    		=> 	__('everywhere', 'cc2'),
				'section'  		=> 	'content',
				'type'     		=> 	'checkbox',
				'priority'		=> 	$center_title_priority,
			) );
			$center_title_priority++;

			// center Header on Home
			$wp_customize->add_setting( 'center_title[home]', array(
				'default'		=>	false,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('center_title[home]', array(
				'label'    		=> 	__('on homepage', 'cc2'),
				'section'  		=> 	'content',
				'type'     		=> 	'checkbox',
				'priority'		=> 	$center_title_priority,
			) );
			$center_title_priority++;

			// center Header on Posts
			$wp_customize->add_setting( 'center_title[posts]', array(
				'default'		=>	false,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('center_title[posts]', array(
				'label'    		=> 	__('on posts', 'cc2'),
				'section'  		=> 	'content',
				'type'     		=> 	'checkbox',
				'priority'		=> 	$center_title_priority,
			) );
			$center_title_priority++;

			// center Header on Pages
			$wp_customize->add_setting( 'center_title[pages]', array(
				'default'		=>	false,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('center_title[pages]', array(
				'label'    		=> 	__('on pages', 'cc2'),
				'section'  		=> 	'content',
				'type'     		=> 	'checkbox',
				'priority'		=> 	$center_title_priority,
			) );
			$center_title_priority++;
			

			// center Header on Archive
			$wp_customize->add_setting( 'center_title[archive]', array(
				'default'		=>	false,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('center_title[archive]', array(
				'label'    		=> 	__('on archive', 'cc2'),
				'section'  		=> 	'content',
				'type'     		=> 	'checkbox',
				'priority'		=> 	$center_title_priority,
			) );
			$center_title_priority++;

			// center Header on Search
			$wp_customize->add_setting( 'center_title[search]', array(
				'default'		=>	false,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('center_title[search]', array(
				'label'    		=> 	__('on search', 'cc2'),
				'section'  		=> 	'content',
				'type'     		=> 	'checkbox',
				'priority'		=> 	$center_title_priority,
			) );
			$center_title_priority++;
			

			// center Header on 404
			$wp_customize->add_setting( 'center_title[error]', array(
				'default'		=>	false,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('center_title[error]', array(
				'label'    		=> 	__('on 404: not-found', 'cc2'),
				'section'  		=> 	'content',
				'type'     		=> 	'checkbox',
				'priority'		=> 	$center_title_priority,
			) );
			
			$center_title_priority++;
		
		}
		
		/**
		 * Sidebars Section
		 */
		
		function section_layouts( $wp_customize ) {
			extract( $this->prepare_variables() );
			
			$layout_choices = array(
				'right' 		=> 'Sidebar right',
				'left' 	        => 'Sidebar left',
				'left-right'    => 'Sidebar left and right',
				'fullwidth'     => 'Fullwidth'
			);
			
			$layout_choices_all = array('default' => 'Default' ) + $layout_choices;
			
			/*
			array(
				'default' 	    => 'Default',
				'right' 		=> 'Sidebar right',
				'left' 	        => 'Sidebar left',
				'left-right'    => 'Sidebar left and right',
				'fullwidth'     => 'Fullwidth'
			);*/


			$wp_customize->add_section( 'layouts', array(
				'title'         => 	__( 'Sidebar Layouts', 'cc2' ),
				'priority'      => 	120,
			) );

			// Layouts Description - A quick note
			$wp_customize->add_setting( 'layouts_note', array(
				'capability'    => 	'edit_theme_options',
				'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control( new Description( $wp_customize, 'layouts_note', array(
				'label' 		=> 	__('Where do you like your sidebars? *Collapse&nbsp;options or zoom out if your display is too small*', 'cc2'),
				'type' 			=> 	'description',
				'section' 		=> 	'layouts',
				'priority'		=> 	10,
			) ) );

			// Default Layout
			$wp_customize->add_setting( 'default_layout', array(
				'default'       => 	'left',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control( 'default_layout', array(
				'label'   		=> 	__('Default Layout', 'cc2'),
				'section' 		=> 	'layouts',
				'priority'		=> 	20,
				'type'    		=> 	'select',
				'choices'    	=> 	$layout_choices,
			) );
			
		

			// Default Page Layout
			$wp_customize->add_setting( 'default_page_layout', array(
				'default'       => 	'default',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control( 'default_page_layout', array(
				'label'   		=> 	__('Page Layout', 'cc2'),
				'section' 		=> 	'layouts',
				'priority'		=> 	40,
				'type'    		=> 	'select',
				'choices'    	=> 	$layout_choices_all,
			) );

			// Default Post Layout
			$wp_customize->add_setting( 'default_post_layout', array(
				'default'       => 	'default',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control( 'default_post_layout', array(
				'label'   		=> 	__('Post Layout', 'cc2'),
				'section' 		=> 	'layouts',
				'priority'		=> 	60,
				'type'    		=> 	'select',
				'choices'    	=> 	$layout_choices_all,
			) );

			// Default Archive Layout
			$wp_customize->add_setting( 'default_archive_layout', array(
				'default'       => 	'default',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control( 'default_archive_layout', array(
				'label'   		=> 	__('Archive Layout', 'cc2'),
				'section' 		=> 	'layouts',
				'priority'		=> 	80,
				'type'    		=> 	'select',
				'choices'    	=> 	$layout_choices_all,
			) );
			
			// change sidebar columns (default: 4)
			
			

			// Hide Left Sidebar On Phones?
			$wp_customize->add_setting( 'hide_left_sidebar_on_phones', array(
				'default'       =>  true,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('hide_left_sidebar_on_phones', array(
				'label'    		=> 	sprintf( __('Hide %s sidebar on phones?', 'cc2'), __('left', 'cc2') ),
				'section'  		=> 	'layouts',
				'type'     		=> 	'checkbox',
				'priority'		=> 	140,
			) );

			// Hide Right Sidebar On Phones?
			$wp_customize->add_setting( 'hide_right_sidebar_on_phones', array(
				'default'       =>  false,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('hide_right_sidebar_on_phones', array(
				'label'    		=> 	sprintf( __('Hide %s sidebar on phones?', 'cc2'), __('right', 'cc2') ),
				'section'  		=> 	'layouts',
				'type'     		=> 	'checkbox',
				'priority'		=> 	120,
			) );
			
		}
		
		/**
		 * Widget section
		 */
		
		function section_widgets( $wp_customize ) {
	
			extract( $this->prepare_variables() );
	
			$widget_section_priority = 140;

			$wp_customize->add_section( 'widgets', array(
				'title'         => 	'Widgets',
				'priority'      => 	$widget_section_priority,
			) );
			

			$widget_section_priority+=2;
		
			// The widgets Title attributes - A Quick Note
			
			$wp_customize->add_setting( 'widget_title_attributes_note', array(
				'capability'    => 	'edit_theme_options',
				'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control( new Description( $wp_customize, 'widget_title_attributes_note', array(
				'label' 		=> 	__('Get more options to style your header and footer widgets with the CC2 Premium Pack', 'cc2'),
				'type' 			=> 	'description',
				'section' 		=> 	'widgets',
				'priority'		=> 	$widget_section_priority,
			) ) );
			$widget_section_priority+=2; // 144
			
			// widget title Font Color
			$wp_customize->add_setting('widget_title_text_color', array(
				'default'           	=> '',
				'capability'        	=> 'edit_theme_options',
				'transport'   			=> 'refresh',
				'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
				'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
			) );
			$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'widget_title_text_color', array(
				'label'    				=> __('Title Font Color', 'cc2'),
				'section'  				=> 'widgets',
				'priority'				=> $widget_section_priority,
			) ) );
			$widget_section_priority+=2; // 146
			
			// widget title background color
			$wp_customize->add_setting('widget_title_background_color', array(
				'default'           	=> '',
				'capability'        	=> 'edit_theme_options',
				'transport'   			=> 'refresh',
				'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
				'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
			) );
			$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'widget_title_background_color', array(
				'label'    				=> __('Title Background Color', 'cc2'),
				'section'  				=> 'widgets',
				'priority'				=> $widget_section_priority,
			) ) );
			$widget_section_priority+=2; // 148

			// Widget title Font Size
			
			$wp_customize->add_setting('widget_title_font_size', array(
				'default' 		=> '',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control('widget_title_font_size', array(
				'label'      	=> __('Title Font Size', 'cc2'),
				'section'    	=> 'widgets',
				'priority'   	=> $widget_section_priority,
			) );
			$widget_section_priority++;
			
			
			// widget container attributes:  background color, link color, link color hover 
			$widget_section_priority = 155;
			
			// widget background color 
			$wp_customize->add_setting('widget_background_color', array(
				'default'           	=> '',
				'capability'        	=> 'edit_theme_options',
				'transport'   			=> 'refresh',
				'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
				'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
			) );
			$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'widget_background_color', array(
				'label'    				=> __('Widget Background Color', 'cc2'),
				'section'  				=> 'widgets',
				'priority'				=> $widget_section_priority,
			) ) );
			$widget_section_priority++;
			

			// widget link color 
			$wp_customize->add_setting('widget_link_color', array(
				'default'           	=> '',
				'capability'        	=> 'edit_theme_options',
				'transport'   			=> 'refresh',
				'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
				'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
			) );
			$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'widget_link_color', array(
				'label'    				=> __('Widget Link Color', 'cc2'),
				'section'  				=> 'widgets',
				'priority'				=> $widget_section_priority,
			) ) );
			$widget_section_priority++;

			
			// widget link hover color
			$wp_customize->add_setting('widget_link_text_hover_color', array(
				'default'           	=> '',
				'capability'        	=> 'edit_theme_options',
				'transport'   			=> 'refresh',
				'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
				'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
			) );
			$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'widget_link_text_hover_color', array(
				'label'    				=> __('Widget Link Text Hover Color', 'cc2'),
				'section'  				=> 'widgets',
				'priority'				=> $widget_section_priority,
			) ) );
			$widget_section_priority++;
			
		}
	
		/**
		 * Typography Section
		 */
		function section_typography( $wp_customize ) {
			extract( $this->prepare_variables() );
		

			$wp_customize->add_section( 'typography', array(
				'title'         => 	'Typography',
				'priority'      => 	110,
			) );

			if( ! defined( 'CC2_LESSPHP' ) ) {

				// A Quick Note on Bootstrap Variables
				$wp_customize->add_setting( 'bootstrap_typography_note', array(
					'capability'    => 	'edit_theme_options',
					'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
				) );
				$wp_customize->add_control( new Description( $wp_customize, 'bootstrap_typography_note', array(
					'label' 		=> 	sprintf( __('Most Typography just work with Bootstrap Variables, which cannot be compiled within the theme, as this is plugin territory. Get all typography options with the <a href="%s" target="_blank">premium extension.</a>', 'cc2'), 'http://themekraft.com/store/custom-community-2-premium-pack/' ),
					'type' 			=> 	'description',
					'section' 		=> 	'typography',
					'priority'		=> 	115,
				) ) );

			}

			// Headline Font Family
			$wp_customize->add_setting( 'title_font_family', array(
				'default'       => 	'Ubuntu Condensed',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'postMessage',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control( 'title_font_family', array(
				'label'   		=> 	__('Headline Font Family', 'cc2'),
				'section' 		=> 	'typography',
				'priority'		=> 	120,
				'type'    		=> 	'select',
				'choices'    	=> 	$cc2_font_family
			) );

			// Title Font Weight
			$wp_customize->add_setting( 'title_font_weight', array(
				'default'       =>  false,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('title_font_weight', array(
				'label'    		=> 	__('Bold', 'cc2'),
				'section'  		=> 	'typography',
				'type'     		=> 	'checkbox',
				'priority'		=> 	140,
			) );

			// Title Font Style
			$wp_customize->add_setting( 'title_font_style', array(
				'default'       =>  false,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('title_font_style', array(
				'label'    		=> 	__('Italic', 'cc2'),
				'section'  		=> 	'typography',
				'type'     		=> 	'checkbox',
				'priority'		=> 	160,
			) );

			// Headline Font Color
			$wp_customize->add_setting('title_font_color', array(
				'default'           	=> '',
				'capability'        	=> 'edit_theme_options',
				'transport'   			=> 'refresh',
				'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
				'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
			) );
			$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'title_font_color', array(
				'label'    				=> __('Headline Font Color', 'cc2'),
				'section'  				=> 'typography',
				'priority'				=> 180,
			) ) );

			// The Headline Font Sizes - Small Heading
			$wp_customize->add_setting( 'titles_font_sizes', array(
				'capability'    => 	'edit_theme_options',
				'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control( new Label( $wp_customize, 'titles_font_sizes', array(
				'label' 		=> 	__('Headline Font Sizes', 'cc2'),
				'type' 			=> 	'label',
				'section' 		=> 	'typography',
				'priority'		=> 	200,
			) ) );

			// The Titles Font Sizes - A Quick Note
			$wp_customize->add_setting( 'titles_font_sizes_note', array(
				'capability'    => 	'edit_theme_options',
				'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control( new Description( $wp_customize, 'titles_font_sizes_note', array(
				'label' 		=> 	__('For displays from 768px and up', 'cc2'),
				'type' 			=> 	'description',
				'section' 		=> 	'typography',
				'priority'		=> 	210,
			) ) );

			// H1 Font Size
			$wp_customize->add_setting('h1_font_size', array(
				'default' 		=> '48px',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control('h1_font_size', array(
				'label'      	=> __('H1', 'cc2'),
				'section'    	=> 'typography',
				'priority'   	=> 220,
			) );

			// H2 Font Size
			$wp_customize->add_setting('h2_font_size', array(
				'default' 		=> '32px',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control('h2_font_size', array(
				'label'      	=> __('H2', 'cc2'),
				'section'    	=> 'typography',
				'priority'   	=> 240,
			) );

			// H3 Font Size
			$wp_customize->add_setting('h3_font_size', array(
				'default' 		=> '28px',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control('h3_font_size', array(
				'label'      	=> __('H3', 'cc2'),
				'section'    	=> 'typography',
				'priority'   	=> 260,
			) );

			// H4 Font Size
			$wp_customize->add_setting('h4_font_size', array(
				'default' 		=> '24px',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control('h4_font_size', array(
				'label'      	=> __('H4', 'cc2'),
				'section'    	=> 'typography',
				'priority'   	=> 280,
			) );

			// H5 Font Size
			$wp_customize->add_setting('h5_font_size', array(
				'default' 		=> '22px',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control('h5_font_size', array(
				'label'      	=> __('H5', 'cc2'),
				'section'    	=> 'typography',
				'priority'   	=> 300,
			) );

			// H6 Font Size
			$wp_customize->add_setting('h6_font_size', array(
				'default' 		=> 	'20px',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control('h6_font_size', array(
				'label'      	=> __('H6', 'cc2'),
				'section'    	=> 'typography',
				'priority'   	=> 320,
			) );

			
		}
		

	/**
	 * Footer Section
	 */
	
			
		function section_footer( $wp_customize ) {
			extract( $this->prepare_variables() );

			$footer_section_priority = 340;
		 
			$wp_customize->add_section( 'footer', array(
				'title'         => 	'Footer',
				'priority'      => 	$footer_section_priority,
			) );
			$footer_section_priority++;

			// fullwidth footer
			/*
			 * - footer fullwidth background image (for the wrap!)  
				- footer fullwidth background color (with possibility for transparency) 
				- footer fullwidth border top color (with possibility for transparency) 
				- footer fullwidth border bottom color (with possibility for transparency) 
			*/
				
				
				
			// A Quick Note on Bootstrap Variables
			$wp_customize->add_setting( 'footer_fullwidth_note', array(
				'capability'    => 	'edit_theme_options',
				'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control( new Labeled_Description( $wp_customize, 'footer_fullwidth_note', array(
				'label' 		=> 	 array(
					'title' 		=> __('Fullwidth Footer', 'cc2'), 
					'description' 	=> __('Attributes of the fullwidth footer', 'cc2'),
				),
				'type' 			=> 	'description',
				'section' 		=> 	'footer',
				'priority'		=> 	340,
			) ) );
			$footer_section_priority++;
		
			// footer fullwidth background image (footer fullwidth-wrap)
			$wp_customize->add_setting('footer_fullwidth_background_image', array(
				'default'           	=> '',
				'capability'        	=> 'edit_theme_options',
				'transport'   			=> 'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			
			$wp_customize->add_control( new  WP_Customize_Image_Control($wp_customize, 'footer_fullwidth_background_image', array(
				'label'    				=> __('Background Image', 'cc2'),
				'section'  				=> 'footer',
				'priority'				=> $footer_section_priority,
			) ) );
			$footer_section_priority++;
			
		
			// footer fullwidth background color
			$wp_customize->add_setting('footer_fullwidth_background_color', array(
				'default'           	=> '#eee',
				'capability'        	=> 'edit_theme_options',
				'transport'   			=> 'refresh',
				'sanitize_callback' 	=> array('Pasteur', 'sanitize_hex_with_transparency' ),
				/*'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',*/
				'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
			) );
			$wp_customize->add_control( new cc2_Customize_Color_Control($wp_customize, 'footer_fullwidth_background_color', array(
				'label'    				=> __('Background Color', 'cc2'),
				'section'  				=> 'footer',
				'priority'				=> $footer_section_priority,
			) ) );
			$footer_section_priority++;
			
		
			// footer fullwidth border top color
			$wp_customize->add_setting('footer_fullwidth_border_top_color', array(
				'default'           	=> '#ddd',
				'capability'        	=> 'edit_theme_options',
				'transport'   			=> 'refresh',
				'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
				'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
			) );
			$wp_customize->add_control( new cc2_Customize_Color_Control($wp_customize, 'footer_fullwidth_border_top_color', array(
				'label'    				=> __('Color of upper border', 'cc2'),
				'section'  				=> 'footer',
				'priority'				=> $footer_section_priority,
			) ) );
			$footer_section_priority++;
			
			
			// footer fullwidth border bottom color (it's actually the branding top color ^_^)
			$wp_customize->add_setting('footer_fullwidth_border_bottom_color', array(
				'default'           	=> '#333',
				'capability'        	=> 'edit_theme_options',
				'transport'   			=> 'refresh',
				'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
				'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
			) );
			$wp_customize->add_control( new cc2_Customize_Color_Control ($wp_customize, 'footer_fullwidth_border_bottom_color', array(
				'label'    				=> __('Color of lower border', 'cc2'),
				'section'  				=> 'footer',
				'priority'				=> $footer_section_priority,
			) ) );
			$footer_section_priority++;
		

		}
		
		function section_blog( $wp_customize ) {
			extract( $this->prepare_variables() );

			// Blog Section
			$wp_customize->add_section( 'blog', array(
				'title'         => 	'Blog',
				'priority'      => 	380,
			) );

			// Blog Archive Loop Template
			$wp_customize->add_setting( 'cc_list_post_style', array(
				'default'       => 	'blog-style',
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control( 'cc_list_post_style', array(
				'label'   		=> 	__('Blog Archive View - List Post Style', 'cc2'),
				'section' 		=> 	'blog',
				'priority'		=> 	20,
				'type'    		=> 	'select',
				'choices'    	=> 	$cc_loop_templates
			) );

			// Loop Designer Ready! - A Quick Note
			$wp_customize->add_setting( 'loop_designer_note', array(
				'capability'    => 	'edit_theme_options',
				'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control( new Description( $wp_customize, 'loop_designer_note', array(
				'label' 		=> 	__('Loop-Designer-Ready! Get more loop templates available here, which you can easily customize, or simply create new ones. Get full control of how your post listings look with the <a href="http://themekraft.com/store/customize-wordpress-loop-with-tk-loop-designer/" target="_blank">TK Loop Designer Plugin</a>.', 'cc2'),
				'type' 			=> 	'description',
				'section' 		=> 	'blog',
				'priority'		=> 	40,
			) ) );

			// Blog Archive Post Meta - Small Heading
			$wp_customize->add_setting( 'blog_archive_post_meta', array(
				'capability'    => 	'edit_theme_options',
				'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control( new Label( $wp_customize, 'blog_archive_post_meta', array(
				'label' 		=> 	__('Blog Archive View - Display Post Meta', 'cc2'),
				'type' 			=> 	'label',
				'section' 		=> 	'blog',
				'priority'		=> 	60,
			) ) );

			// Blog Archive View - Show date
			$wp_customize->add_setting( 'show_date', array(
				'default'		=>	true,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('show_date', array(
				'label'    		=> 	__('show date', 'cc2'),
				'section'  		=> 	'blog',
				'type'     		=> 	'checkbox',
				'priority'		=> 	80,
			) );

			// Blog Archive View - Show category
			$wp_customize->add_setting( 'show_category', array(
				'default'		=>	true,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('show_category', array(
				'label'    		=> 	__('show category', 'cc2'),
				'section'  		=> 	'blog',
				'type'     		=> 	'checkbox',
				'priority'		=> 	100,
			) );

			// Blog Archive View - Show author
			$wp_customize->add_setting( 'show_author', array(
				'default'		=>	true,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('show_author', array(
				'label'    		=> 	__('show author', 'cc2'),
				'section'  		=> 	'blog',
				'type'     		=> 	'checkbox',
				'priority'		=> 	120,
			) );

			// Blog Archive View - Show author avatar
			$wp_customize->add_setting( 'show_author_image[archive]', array(
				'default'		=>	false,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('show_author_image[archive]', array(
				'label'    		=> 	__('show author image / avatar', 'cc2'),
				'section'  		=> 	'blog',
				'type'     		=> 	'checkbox',
				'priority'		=> 	130,
			) );


			// Blog Single Post Meta - Small Heading
			$wp_customize->add_setting( 'blog_single_post_meta', array(
				'capability'    => 	'edit_theme_options',
				'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control( new Label( $wp_customize, 'blog_single_post_meta', array(
				'label' 		=> 	__('Blog Single View - Display Post Meta', 'cc2'),
				'type' 			=> 	'label',
				'section' 		=> 	'blog',
				'priority'		=> 	160,
			) ) );

			// Blog Single View - Show date
			$wp_customize->add_setting( 'single_show_date', array(
				'default'		=>	true,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('single_show_date', array(
				'label'    		=> 	__('show date', 'cc2'),
				'section'  		=> 	'blog',
				'type'     		=> 	'checkbox',
				'priority'		=> 	180,
			) );

			// Blog Single View - Show category
			$wp_customize->add_setting( 'single_show_category', array(
				'default'		=>	true,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('single_show_category', array(
				'label'    		=> 	__('show category', 'cc2'),
				'section'  		=> 	'blog',
				'type'     		=> 	'checkbox',
				'priority'		=> 	200,
			) );

			// Blog Single View - Show author
			$wp_customize->add_setting( 'single_show_author', array(
				'default'		=>	true,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('single_show_author', array(
				'label'    		=> 	__('show author', 'cc2'),
				'section'  		=> 	'blog',
				'type'     		=> 	'checkbox',
				'priority'		=> 	220,
			) );
			
			// single post / page 
			$wp_customize->add_setting( 'show_author_image[single_post]', array(
				'default'		=>	false,
				'capability'    => 	'edit_theme_options',
				'transport'   	=> 	'refresh',
				'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
			) );
			$wp_customize->add_control('show_author_image[single_post]', array(
				'label'    		=> 	__('show author image / avatar', 'cc2'),
				'section'  		=> 	'blog',
				'type'     		=> 	'checkbox',
				'priority'		=> 	240,
			) );

		}
		
		// Slider Section
		
		function section_slider( $wp_customize ) {
			extract( $this->prepare_variables() );
			
			$wp_customize->add_section( 'cc_slider', array(
				'title'         => 	'Slideshow',
				'priority'      => 	400,
			) );

				// Create A Slideshow Note
				$wp_customize->add_setting( 'slider_create_note', array(
					'capability'    => 	'edit_theme_options',
					'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
				) );
				$wp_customize->add_control( new Description( $wp_customize, 'slider_create_note', array(
					'label' 		=> 	'<a href="'.admin_url('admin.php?page=cc2-settings&tab=slider-options') . '" target="_blank">Create a new slideshow </a>, or ..',
					'type' 			=> 	'description',
					'section' 		=> 	'cc_slider',
					'priority'		=> 	6,
				) ) );

				// Slider Template
				$wp_customize->add_setting( 'cc_slideshow_template', array(
					'default'       => 	'none',
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				) );
				$wp_customize->add_control( 'cc_slideshow_template', array(
					'label'   		=> 	__('Select A Slideshow', 'cc2'),
					'section' 		=> 	'cc_slider',
					'priority'		=> 	8,
					'type'    		=> 	'select',
					'choices'    	=> 	$cc_slideshow_template
				) );

				// Slider Style
				$wp_customize->add_setting( 'cc2_slideshow_style', array(
					'default'       => 	'slides-only',
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				) );
				$wp_customize->add_control( 'cc2_slideshow_style', array(
					'label'   		=> 	__('Slideshow Style', 'cc2'),
					'section' 		=> 	'cc_slider',
					'priority'		=> 	10,
					'type'    		=> 	'select',
					'choices'    	=> 	array(
						'slides-only'       => 'Slides only',
						'bubble-preview'    => 'Bubble Preview',
						'side-preview'  => 'Side Preview'
					)
				) );

				// Slider Position
				$wp_customize->add_setting( 'cc_slider_display', array(
					'default'       => 	'home',
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				) );
				$wp_customize->add_control( 'cc_slider_display', array(
					'label'   		=> 	__('Display Slideshow', 'cc2'),
					'section' 		=> 	'cc_slider',
					'priority'		=> 	20,
					'type'    		=> 	'radio',
					'choices'    	=> 	array(
						'home' 			=> 'display on home',
						'bloghome' 		=> 'display on blog home',
						'always'		=> 'display always',
						'off'			=> 'turn off'
					)
				) );

				// Slider Position
				$wp_customize->add_setting( 'cc_slider_position', array(
					'default'       => 	'cc_after_header',
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				) );
				$wp_customize->add_control( 'cc_slider_position', array(
					'label'   		=> 	__('Slideshow Position', 'cc2'),
					'section' 		=> 	'cc_slider',
					'priority'		=> 	40,
					'type'    		=> 	'select',
					'choices'    	=> 	$slider_positions
				) );

				// Effect on title
				$wp_customize->add_setting( 'slider_effect_title', array(
					'default'       => 	'bounceInLeft',
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				) );
				$wp_customize->add_control( 'slider_effect_title', array(
					'label'   		=> 	__('Animation Effect on Caption Title', 'cc2'),
					'section' 		=> 	'cc_slider',
					'priority'		=> 	60,
					'type'    		=> 	'select',
					'choices'    	=> 	$cc2_animatecss_start_moves
				) );

				// Effect on excerpt
				$wp_customize->add_setting( 'slider_effect_excerpt', array(
					'default'       => 	'bounceInRight',
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				) );
				$wp_customize->add_control( 'slider_effect_excerpt', array(
					'label'   		=> 	__('Animation Effect on Caption Text', 'cc2'),
					'section' 		=> 	'cc_slider',
					'priority'		=> 	80,
					'type'    		=> 	'select',
					'choices'    	=> 	$cc2_animatecss_start_moves
				) );

				// Text Align
				$wp_customize->add_setting( 'cc_slider_text_align', array(
					'default'       => 	'center',
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				) );
				$wp_customize->add_control( 'cc_slider_text_align', array(
					'label'   		=> 	__('Text Align', 'cc2'),
					'section' 		=> 	'cc_slider',
					'priority'		=> 	120,
					'type'    		=> 	'select',
					'choices'    	=> 	$cc2_text_align
				) );

				// Caption Title Background Color
				$wp_customize->add_setting('caption_title_bg_color', array(
					'default'           	=> 'f2694b',
					'capability'        	=> 'edit_theme_options',
					'transport'   			=> 'refresh',
					'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
					'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
				) );
				$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'caption_title_bg_color', array(
					'label'    				=> __('Caption Title Background Color', 'cc2'),
					'section'  				=> 'cc_slider',
					'priority'				=> 160,
				) ) );

				// Caption Title Font Color
				$wp_customize->add_setting('caption_title_font_color', array(
					'default'           	=> 'fff',
					'capability'        	=> 'edit_theme_options',
					'transport'   			=> 'refresh',
					'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
					'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
				) );
				$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'caption_title_font_color', array(
					'label'    				=> __('Caption Title Font Color', 'cc2'),
					'section'  				=> 'cc_slider',
					'priority'				=> 180,
				) ) );

				// Caption Title Font Family
				$wp_customize->add_setting( 'caption_title_font_family', array(
					'default'       => 	'Ubuntu Condensed',
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				) );
				$wp_customize->add_control( 'caption_title_font_family', array(
					'label'   		=> 	__('Caption Title Font Family', 'cc2'),
					'section' 		=> 	'cc_slider',
					'priority'		=> 	200,
					'type'    		=> 	'select',
					'choices'    	=> 	$cc2_font_family
				) );

				// Caption Title Font Weight
				/**
				 * NOTE: Missing default value
				 */
				$wp_customize->add_setting( 'caption_title_font_weight', array(
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_bool' ),
				) );
				$wp_customize->add_control('caption_title_font_weight', array(
					'label'    		=> 	__('Bold', 'cc2'),
					'section'  		=> 	'cc_slider',
					'type'     		=> 	'checkbox',
					'priority'		=> 	220,
				) );

				// Caption Title Font Style
				$wp_customize->add_setting( 'caption_title_font_style', array(
					'default'		=>	true,
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
				) );
				$wp_customize->add_control('caption_title_font_style', array(
					'label'    		=> 	__('Italic', 'cc2'),
					'section'  		=> 	'cc_slider',
					'type'     		=> 	'checkbox',
					'priority'		=> 	240,
				) );

				// Caption Title Text Shadow
				$wp_customize->add_setting( 'caption_title_shadow', array(
					'default'		=>	true,
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
				) );
				$wp_customize->add_control('caption_title_shadow', array(
					'label'    		=> 	__('Text Shadow', 'cc2'),
					'section'  		=> 	'cc_slider',
					'type'     		=> 	'checkbox',
					'priority'		=> 	250,
				) );

				// Caption Title Opacity
				$wp_customize->add_setting( 'caption_title_opacity', array(
					'default'        => '0.9',
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				) );

				$wp_customize->add_control( 'caption_title_opacity', array(
					'label'   		=> __( 'Caption Title Opacity', 'cc2' ),
					'section' 		=> 'cc_slider',
					'priority'		=> 	260,
					'type'   		 => 'text',
				) );

				// Caption Text Background Color
				$wp_customize->add_setting('caption_text_bg_color', array(
					'default'           	=> 'FBFBFB',
					'capability'        	=> 'edit_theme_options',
					'transport'   			=> 'refresh',
					'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
					'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
				) );
				$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'caption_text_bg_color', array(
					'label'    				=> __('Caption Text Background Color', 'cc2'),
					'section'  				=> 'cc_slider',
					'priority'				=> 300,
				) ) );

				// Caption Text Font Color
				$wp_customize->add_setting('caption_text_font_color', array(
					'default'           	=> '333',
					'capability'        	=> 'edit_theme_options',
					'transport'   			=> 'refresh',
					'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
					'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
				) );
				$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'caption_text_font_color', array(
					'label'    				=> __('Caption Text Font Color', 'cc2'),
					'section'  				=> 'cc_slider',
					'priority'				=> 320,
				) ) );

				// Caption Text Font Family
				$wp_customize->add_setting( 'caption_text_font_family', array(
					'default'       => 	'',
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				) );
				$wp_customize->add_control( 'caption_text_font_family', array(
					'label'   		=> 	__('Caption Text Font Family', 'cc2'),
					'section' 		=> 	'cc_slider',
					'priority'		=> 	340,
					'type'    		=> 	'select',
					'choices'    	=> 	$cc2_font_family
				) );

				// Caption Text Font Weight
				$wp_customize->add_setting( 'caption_text_font_weight', array(
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_bool' ),
				) );
				$wp_customize->add_control('caption_text_font_weight', array(
					'label'    		=> 	__('Bold', 'cc2'),
					'section'  		=> 	'cc_slider',
					'type'     		=> 	'checkbox',
					'priority'		=> 	360,
				) );

				// Caption Text Font Style
				$wp_customize->add_setting( 'caption_text_font_style', array(
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_bool' ),
				) );
				$wp_customize->add_control('caption_text_font_style', array(
					'label'    		=> 	__('Italic', 'cc2'),
					'section'  		=> 	'cc_slider',
					'type'     		=> 	'checkbox',
					'priority'		=> 	380,
				) );

				// Caption Text Shadow
				$wp_customize->add_setting( 'caption_text_shadow', array(
					'default'		=>	false,
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' 	=>  array( 'cc2_Pasteur', 'sanitize_bool'),
				) );
				$wp_customize->add_control('caption_text_shadow', array(
					'label'    		=> 	__('Text Shadow', 'cc2'),
					'section'  		=> 	'cc_slider',
					'type'     		=> 	'checkbox',
					'priority'		=> 	385,
				) );

				// Caption Text Opacity
				$wp_customize->add_setting( 'caption_text_opacity', array(
					'default'        => '0.8',
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				) );

				$wp_customize->add_control( 'caption_text_opacity', array(
					'label'   		=> __( 'Caption Text Opacity', 'cc2' ),
					'section' 		=> 'cc_slider',
					'priority'		=> 	390,
					'type'   		 => 'text',
				) );




				// Prev/Next color

				/*
				$wp_customize->add_setting('slider_controls_color', array(
					'default'           	=> '#fff',
					'capability'        	=> 'edit_theme_options',
					'transport'   			=> 'refresh',
					'sanitize_callback' 	=> 'sanitize_hex_color_no_hash',
					'sanitize_js_callback' 	=> 'maybe_hash_hex_color',
				) );
				$wp_customize->add_control( new WP_Customize_Color_Control($wp_customize, 'slider_controls_color', array(
					'label'    				=> __('Prev/Next Controls Color', 'cc2'),
					'section'  				=> 'cc_slider',
					'priority'				=> 391,
				) ) );*/




				/*
				 * 
				 * 	// Caption Title Font Style
				$wp_customize->add_setting( 'caption_title_font_style', array(
					'default'		=>	true,
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					* 'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_bool' ),
				) );
				$wp_customize->add_control('caption_title_font_style', array(
					'label'    		=> 	__('Italic'),
					'section'  		=> 	'cc_slider',
					'type'     		=> 	'checkbox',
					'priority'		=> 	240,
				) );
				// Prev/Next disable
				$wp_customize->add_setting( 'slider_controls_show', array(
					'default'		=>	false,
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_bool' ),
				) );
				$wp_customize->add_control('slider_controls_show', array(
					'label'    		=> 	__('Enable Prev/Next Controls', 'cc2'),
					'section'  		=> 	'cc_slider',
					'type'     		=> 	'checkbox',
					'priority'		=> 	392,
				) );
				*/
				

				// Sliding Time
				$wp_customize->add_setting( 'cc_sliding_time', array(
					'default'        => '5000',
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				) );

				$wp_customize->add_control( 'cc_sliding_time', array(
					'label'   		=> __( 'Sliding Time in ms', 'cc2' ),
					'section' 		=> 'cc_slider',
					'priority'		=> 	420,
					'type'   		 => 'text',
				) );

				// Sub Heading for Slider Dimensions
				$wp_customize->add_setting( 'slider_dimensions_heading', array(
					'capability'    => 	'edit_theme_options',
					'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
				) );
				$wp_customize->add_control( new Label( $wp_customize, 'slider_dimensions_heading', array(
					'label' 		=> 	__('Slideshow Dimensions', 'cc2'),
					'type' 			=> 	'label',
					'section' 		=> 	'cc_slider',
					'priority'		=> 	470,
				) ) );

				// Note for Slider Height and Width
				$wp_customize->add_setting( 'slider_dimensions_note', array(
					'capability'    => 	'edit_theme_options',
					'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
				) );
				$wp_customize->add_control( new Description( $wp_customize, 'slider_dimensions_note', array(
					'label' 		=> 	__('You don\'t need to set the width and height of the slider: just make all your images the size you want to have as your slideshow size. You can still define a width and max height here, but we recommend to leave it automatic. <a href="https://themekraft.zendesk.com/hc/en-us/articles/200270762" target="_blank">Read more.</a>', 'cc2'),
					'type' 			=> 	'description',
					'section' 		=> 	'cc_slider',
					'priority'		=> 	475,
				) ) );

				// Slider Width
				$wp_customize->add_setting( 'cc_slider_width', array(
					'default'        => 'auto',
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				) );

				$wp_customize->add_control( 'cc_slider_width', array(
					'label'   		=> __( 'Slider width', 'cc2' ),
					'section' 		=> 'cc_slider',
					'priority'		=> 	480,
					'type'   		 => 'text',
				) );

				// Slider Height
				$wp_customize->add_setting( 'cc_slider_height', array(
					'default'        => 'none',
					'capability'    => 	'edit_theme_options',
					'transport'   	=> 	'refresh',
					'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
				) );

				$wp_customize->add_control( 'cc_slider_height', array(
					'label'   		=> __( 'Slider max height', 'cc2' ),
					'section' 		=> 'cc_slider',
					'priority'		=> 	490,
					'type'   		 => 'text',
				) );			
		}
		
				
		/**
		 * Advanced bootstrap settings:
		 * - container sizes (small, medium, large)
		 * - sidebar / content col grid customization
		 */
		
		function section_customize_bootstrap( $wp_customize ) {
			extract( $this->prepare_variables() );

		// Slider Section
			$wp_customize->add_section( 'cc2_customize_bootstrap', array(
				'title'         => 	'Advanced Bootstrap Settings',
				'priority'      => 	500,
			) ); 
			
			$customize_bootstrap_priority = 510;

			// heading
			// Sub Heading for Container Width
			$wp_customize->add_setting( 'heading_bootstrap_container_width', array(
				'capability'    => 	'edit_theme_options',
				'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control( new Label( $wp_customize, 'heading_bootstrap_container_width', array(
				'label' 		=> 	__('Bootstrap Container Width', 'cc2'),
				'type' 			=> 	'label',
				'section' 		=> 	'cc2_customize_bootstrap',
				'priority'		=> 	501,
			) ) );

			// Note for Container Width
			$wp_customize->add_setting( 'note_bootstrap_container_width', array(
				'capability'    => 	'edit_theme_options',
				'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control( new Description( $wp_customize, 'note_bootstrap_container_width', array(
				'label' 		=> 	sprintf( __('Customize the width values of the .container class, for each different screen size. Leave the field empty for default width.<br /><a href="%s">Screen size info</a>', 'cc2'), 'http://getbootstrap.com/css/#grid-options' ),
				'type' 			=> 	'description',
				'section' 		=> 	'cc2_customize_bootstrap',
				'priority'		=> 	502,
			) ) );

		

			// Container Width (small screen) => default: 750px
			$wp_customize->add_setting('bootstrap_container_width[small]', array(
				'default' 		=> '750px',
				'capability'    => 'edit_theme_options',
				'transport'   	=> 'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control('bootstrap_container_width[small]', array(
				'label'      	=> __('Container Width (Small Screen)', 'cc2'),
				'section'    	=> 'cc2_customize_bootstrap',
				'priority'   	=> $customize_bootstrap_priority,
			) );
			$customize_bootstrap_priority++;
	 
			
			// Container Width (medium screen) => default: 970px
			$wp_customize->add_setting('bootstrap_container_width[medium]', array(
				'default' 		=> '970px',
				'capability'    => 'edit_theme_options',
				'transport'   	=> 'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control('bootstrap_container_width[medium]', array(
				'label'      	=> __('Container Width (Medium Screen)', 'cc2'),
				'section'    	=> 'cc2_customize_bootstrap',
				'priority'   	=> $customize_bootstrap_priority,
			) );
			$customize_bootstrap_priority++;
			
			// Container Width (large screen) => default: 1170px
			$wp_customize->add_setting('bootstrap_container_width[large]', array(
				'default' 		=> '1170px',
				'capability'    => 'edit_theme_options',
				'transport'   	=> 'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control('bootstrap_container_width[large]', array(
				'label'      	=> __('Container Width (large Screen)', 'cc2'),
				'section'    	=> 'cc2_customize_bootstrap',
				'priority'   	=> $customize_bootstrap_priority,
			) );
			$customize_bootstrap_priority++;


			
			$customize_bootstrap_priority = 520;
		
			// Sub Heading for Custom Sidebar Columns (ie. 1 - 12)
			$wp_customize->add_setting( 'heading_bootstrap_custom_sidebar_cols', array(
			'capability'    => 	'edit_theme_options',
			'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control( new Label( $wp_customize, 'heading_bootstrap_custom_sidebar_cols', array(
			'label' 		=> 	__('Custom Sidebar Size', 'cc2'),
			'type' 			=> 	'label',
			'section' 		=> 	'cc2_customize_bootstrap',
			'priority'		=> 	$customize_bootstrap_priority,
			) ) );
			
			$customize_bootstrap_priority+=5;

			// Note for Container Width
			$wp_customize->add_setting( 'note_bootstrap_custom_sidebar_cols', array(
			'capability'    => 	'edit_theme_options',
			'sanitize_callback' => array( 'cc2_Pasteur', 'none' ),
			) );
			$wp_customize->add_control( new Description( $wp_customize, 'note_bootstrap_custom_sidebar_cols', array(
			'label' 		=> 	__('Adjust the <strong>column numbers</strong>, which are used for setting the size of the sidebars. <a href="http://getbootstrap.com/css/#grid">Read about the Bootstrap Grid system</a>', 'cc2'),
			'type' 			=> 	'description',
			'section' 		=> 	'cc2_customize_bootstrap',
			'priority'		=> 	$customize_bootstrap_priority,
			) ) );

			$customize_bootstrap_priority+=5;
			
			// Left Sidebar: Custom Columns
			$default_sidebar_cols = $bootstrap_cols; 
			$default_sidebar_cols[4] = __('4 (default)', 'cc2');
			
			$wp_customize->add_setting('bootstrap_custom_sidebar_cols[left]', array(
				'default' 		=> '4',
				'capability'    => 'edit_theme_options',
				'transport'   	=> 'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control('bootstrap_custom_sidebar_cols[left]', array(
				'label'      	=> __('Left sidebar', 'cc2'),
				'section'    	=> 'cc2_customize_bootstrap',
				'type'			=> 'select',
				'choices'		=> $default_sidebar_cols,
				'priority'   	=> $customize_bootstrap_priority,
			) );
			$customize_bootstrap_priority++;
			
			// Right Sidebar: Custom Columns
			$wp_customize->add_setting('bootstrap_custom_sidebar_cols[right]', array(
				'default' 		=> '4',
				'capability'    => 'edit_theme_options',
				'transport'   	=> 'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control('bootstrap_custom_sidebar_cols[right]', array(
				'label'      	=> __('Right sidebar', 'cc2'),
				'section'    	=> 'cc2_customize_bootstrap',
				'type'			=> 'select',
				'choices'		=> $default_sidebar_cols,
				'priority'   	=> $customize_bootstrap_priority,
			) );
			$customize_bootstrap_priority++;
			
			$customize_bootstrap_priority+=5;

			 $wp_customize->add_setting('cc2_comment_form_orientation', array(
				'default' 		=> 'vertical',
				'capability'    => 'edit_theme_options',
				'transport'   	=> 'refresh',
				'sanitize_callback' => array( 'cc2_Pasteur', 'sanitize_text' ),
			) );
			$wp_customize->add_control('cc2_comment_form_orientation', array(
				'label'      	=> __('Comment form orientation', 'cc2'),
				'section'    	=> 'cc2_customize_bootstrap',
				
				'type'			=> 'select',
				'choices'		=> array('vertical' => __('Vertical (default)', 'cc2'), 'horizontal' => __('Horizontal', 'cc2') ),
				'priority'   	=> $customize_bootstrap_priority,
			) );
			$customize_bootstrap_priority++;
			
		}
		
	
	} // end of class
	
	
	
	if( !isset( $cc2_customizer ) ) {
		$cc2_customizer = new cc2_CustomizerTheme(); // intentionally NOT using the Simpleton pattern .. ;)
	}
	
}

/**
 * A try to improve the font loading situation
 */

if( !function_exists('cc2_customizer_load_fonts') ) :
	function cc2_customizer_load_fonts( $fonts = array() ) {
		global $cc2_font_family;
		
		$cc2_font_family = array(
			'inherit' => 'inherit',
			'"Lato", "Droid Sans", "Helvetica Neue", Tahoma, Arial, sans-serif' => 'Lato',
			'"Ubuntu Condensed", "Droid Sans", "Helvetica Neue", Tahoma, Arial, sans-serif' => 'Ubuntu Condensed',
			'"Pacifico", "Helvetica Neue", Arial, sans-serif' => 'Pacifico',
			'"Helvetica Neue", Tahoma, Arial, sans-serif' => 'Helvetica Neue',
			'Garamond, "Times New Roman", Times, serif' => 'Garamond',
			'Georgia, "Times New Roman", Times, serif' => 'Georgia',
			'Impact, Arial, sans-serif' => 'Impact',
			'Arial, sans-serif'	=> 'Arial',
			'Arial Black, Arial, sans-serif' => 'Arial Black',
			'Verdana, Arial, sans-serif' => 'Verdana',
			'Tahoma, Arial, sans-serif' => 'Tahoma',
			'"Century Gothic", "Avant Garde", Arial, sans-serif' => 'Century Gothic',
			'"Times New Roman", Times, serif' => 'Times New Roman',
		);

		// If TK Google Fonts is activated get the loaded Google fonts!
		$tk_google_fonts_options = get_option('tk_google_fonts_options', false);

		// Merge Google fonts with the font family array, if there are any
		if( !empty( $tk_google_fonts_options) && isset($tk_google_fonts_options['selected_fonts']) ) {

			foreach ($tk_google_fonts_options['selected_fonts'] as $key => $selected_font) {
				$selected_font = str_replace('+', ' ', $selected_font);
				$cc2_font_family[$selected_font] = $selected_font;

			}

		}
		
		
		$return = $cc2_font_family;
		
		return $return;
	}

endif;

/**
 * Load custom controls
 */

// loads base controls: Description, Heading and Label
include_once( get_template_directory() . '/includes/admin/customizer/base-controls.php' );

// implements a slightly modified color control WITH transparency option
include_once( get_template_directory() . '/includes/admin/customizer/cc2-color-control.php' );