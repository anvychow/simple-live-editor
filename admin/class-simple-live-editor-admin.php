<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://htmltowordpress.io/
 * @since      1.0.0
 *
 * @package    Simple_Live_Editor
 * @subpackage Simple_Live_Editor/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Simple_Live_Editor
 * @subpackage Simple_Live_Editor/admin
 * @author     Harri Heljala <harri@htmltowordpress.io>
 */
class Simple_Live_Editor_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The DOM of current template
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $dom    The DOM of current template.
	 */
	private $dom;

	/**
	 * HTML purifier config
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      HTMLPurifier_Config    $purifier_config    HTML purifier config
	 */
	private $purifier_config;

	/**
	 * HTML purifier
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      HTMLPurifier    $purifier    HTML purifier
	 */
	private $purifier;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->purifier_config = HTMLPurifier_Config::createDefault();
		$this->purifier = new HTMLPurifier( $this->purifier_config );

	}

	/**
	 * Register the stylesheets for the customize view
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * The function gets loaded in the public scope,
		 * so we'll need to check that we are acutally in the customize view
		 */
		global $wp_customize;

		if ( isset( $wp_customize ) ) {

			// remodal.js
			wp_enqueue_style( 'remodal', Helpers::get_dir_url( __FILE__ ) . '../node_modules/remodal/dist/remodal.css' );
			wp_enqueue_style( 'remodal-theme', Helpers::get_dir_url( __FILE__ ) . '../node_modules/remodal/dist/remodal-default-theme.css', array( 'remodal' ) );

			// The plugin stylesheet
			wp_enqueue_style( $this->plugin_name, Helpers::get_dir_url( __FILE__ ) . 'css/simple-live-editor-admin.css', array( 'dashicons', 'remodal', 'remodal-theme' ), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the customize view
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * The function gets loaded in the public scope,
		 * so we'll need to check that we are acutally in the customize view
		 */
		global $wp_customize;

		if ( isset( $wp_customize ) ) {

			// TinyMCE
			wp_enqueue_script( 'editor' );

			// remodal.js
			wp_enqueue_script( 'remodal', Helpers::get_dir_url( __FILE__ ) . '../node_modules/remodal/dist/remodal.js' );

			// Sortable.js
			wp_enqueue_script( 'sortable', Helpers::get_dir_url( __FILE__ ) . '../node_modules/sortablejs/Sortable.js' );

			// The plugin javascript
			wp_enqueue_script( $this->plugin_name, Helpers::get_dir_url( __FILE__ ) . 'js/simple-live-editor-admin.js', array( 'jquery', 'remodal', 'editor', 'sortable' ), $this->version, false );

			/**
			 * Pass settings to javascript
			 */

			global $post;

			$sle_settings = array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'page_template' => get_page_template(),
				'page_id'       => $post->ID,
			);

			if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
				$sle_settings['language_code'] = ICL_LANGUAGE_CODE;
			}

			wp_localize_script( $this->plugin_name, 'sleSettings', $sle_settings );
		}
	}

	public function add_customize_controls( $wp_customize ) {

		/**
		 * Add the setting
		 */
		$wp_customize->add_section( 'sle_settings_section', array(
			'title' => 'Simple Live Editor'
		 ));

		/**
		 * Language selector
		 */
		if ( function_exists( 'icl_get_languages' ) ) {

			$languages = icl_get_languages( 'skip_missing=0&orderby=KEY&order=DIR&link_empty_to=str' );

			if ( $languages ) {

				$choices = array();

				// Create the choices list
				foreach ( $languages as $language ) {
					$choices[$language['code']] = $language['translated_name']; 
				}

				$wp_customize->add_setting( 'sle_language_setting', array(
					'default' => defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : '',
				));

				$wp_customize->add_control( 'sle_language_setting', array(
					'label'   => 'Language',
					'section' => 'sle_settings_section',
					'type'    => 'select',
					'choices' => $choices,
				));
			}
		}

		/**
		 * Page selector
		 */
		
		// Get the wp pages
		$pages = get_pages();

		// If we have pages
		if ( $pages ) {

			$choices = array();

			// Create the choices list
			foreach ( $pages as $page ) {
				$choices[$page->ID] = $page->post_title; 
			}

			$wp_customize->add_setting( 'sle_page_setting', array(
				'default' => 72
			));

			$wp_customize->add_control( 'sle_page_setting', array(
				'label'   => 'Page',
				'section' => 'sle_settings_section',
				'type'    => 'select',
				'choices' => $choices,
			));
		}

		/**
		 * Sections
		 */
		$files = Helpers::glob_recursive( get_stylesheet_directory() . '/simple-live-editor/sections/*.php' );

		if ( $files ) {

			$choices = array();

			foreach ( $files as $file ) {
				$choices[ Helpers::replace_first_occurrence( $file, Helpers::get_sections_directory(), '' ) ] = Helpers::get_title_from_slug( basename( $file, '.php' ) );
			}

			sle_init_wp_customize_control_sle_section();

			$wp_customize->add_setting( 'sle_section_setting' );

			$wp_customize->add_control( new WP_Customize_Control_Sle_Section(
				$wp_customize,
				'sle_section_setting',
				array(
					'label'	  => 'Sections',
					'section' => 'sle_settings_section',
					'choices' => $choices
				)
			));
		}
	}

	/**
	 * Adds the necessary styles for the page editing view notice
	 *
	 * @since    1.0.1
	 */
	public function enqueue_sle_notice_styles() {
		wp_enqueue_style( $this->plugin_name . '-notify', Helpers::get_dir_url( __FILE__ ) . 'css/simple-live-editor-notify.css' );
	}

	/**
	 * Prints notice in page editing view to let the user know about the availability of the plugin and editin capability
	 *
	 * @since    1.0.1
	 */
	public function show_sle_notice() {

		$message = esc_html__( 'Want to edit text and images? Use Live Editing in the Customize view.', $this->plugin_name );
		$cta = esc_html__( 'Launch Customizer', $this->plugin_name );
		$cta_url = admin_url( 'customize.php' );

		if ( isset( $_GET['post'] ) ) {
			$post = get_post( $_GET['post'] );
			if ( ! empty( $post ) && isset( $post->ID ) ) {
				$cta_url = admin_url( 'customize.php?url=' . rawurlencode( get_permalink( $post->ID ) ) );
			}
		}
		
		echo '<div class="notice notice-info sle-notice"><p><span class="dashicons dashicons-edit sle-notice-edit"></span>' . $message . '<a href="' . $cta_url . '" class="btn">' . $cta . '&rarr;</a></p></div>';

	}

	public function add_editor_modal() {

		/**
		 * The function gets loaded in the public scope,
		 * so we'll need to check that we are acutally in the customize view
		 */
		global $wp_customize;

		if ( isset( $wp_customize ) ) {

			$settings = array(
				'wpautop'       => false,
				'media_buttons' => false,
			);

			echo '<div id="sle-editor-modal" class="sle-modal">';
			wp_editor( '', 'sle-editor', $settings );
			echo '</div>';
		}
	}

	public function add_link_edit_modal() {

		/**
		 * The function gets loaded in the public scope,
		 * so we'll need to check that we are acutally in the customize view
		 */
		global $wp_customize;

		if ( isset( $wp_customize ) ) {
			echo '<div id="sle-link-modal" class="sle-modal"><input type="text" class="sle-input sle-link-editor"></div>';
		}
	}

	/**
	 * Override the template output
	 *
	 * @since    1.0.0
	 */
	public function serve_template( $template ) {

		global $post;

		// Get the currently relevant template
		$current_template = $this->get_current_template( $template, $post );

		/**
		 * The function gets loaded in the public scope,
		 * so we'll need to check if we should serve the edited template
		 * or prepare the template for editing in the customizer view
		 */
		global $wp_customize;

		// Outside of customizer get the current template
		if ( ! isset( $wp_customize ) ) {
			return $current_template;
		}

		// In customizer prepare the template for editing
		$this->prepare_template_for_editing( $current_template );

		// No need to return a template name anymore
		return;

	}

	public function prepare_template_for_editing( $template, $key_prefix = '', $post = false ) {

		// Get the document
		$this->dom = $this->get_document( $template, $key_prefix, $post );

		/**
		 * Output the document
		 */
		ob_start();
		eval( '?>' . $this->dom->php() );
		$this_string = ob_get_contents();
		ob_end_flush();

	}

	public function get_current_template( $template, $post ) {

		// Get the start and end part of the path
		$path_prefix = get_stylesheet_directory() . '/simple-live-editor';
		$path_suffix = Helpers::replace_first_occurrence( $template, get_stylesheet_directory(), '' );
		
		// If wpml language defined
		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			$path_language_part = '/' . ICL_LANGUAGE_CODE;
		} else {
			$path_language_part = '';
		}

		// Check that the page exists
		// And append page ID
		if ( is_object( $post ) && $post->post_type === 'page' ) {
			$path_id_part = '/page-' . $post->ID;
		} else {
			$path_id_part = '';
		}

		// The possible paths to templates
		$path_options = array(
			$path_prefix . $path_language_part . $path_id_part . $path_suffix,
			$path_prefix . $path_language_part . $path_suffix,
			$path_prefix . $path_id_part . $path_suffix,
			$path_prefix . $path_suffix,
			$template
		);

		// Decide which template exists and serve it
		foreach ( $path_options as $path ) {
			if ( file_exists( $path ) ) {
				return $path;
			}
		}
	}

	/**
	 * Get content to be edited
	 *
	 * @since    2.0.0
	 */
	public function get_content() {

		// Check that we have the necessary fields
		if ( ! isset( $_GET['template'] ) ) {
			wp_die();
		}

		// Output template
		$this->prepare_template_for_editing( Helpers::get_sections_directory() . sanitize_file_name( $_GET['template'] ), true );

		wp_die();
	}

	/**
	 * Save the content that has been edited
	 *
	 * @since    1.0.0
	 */
	public function save_content() {

		// Check that we have the necessary fields
		if ( ! isset( $_POST['page_template'] ) || ! isset( $_POST['page_id'] ) || ! isset( $_POST['content'] ) ) {
			wp_die();
		}

		// Sanitize
		$page_id = sanitize_key( $_POST['page_id'] );

		if ( empty( $_POST['page_template'] ) ) {
			$page_template = get_stylesheet_directory() . '/index.php';
		} else {
			$page_template = $_POST['page_template'];
		}

		// Get the document
		$this->dom = $this->get_document( $page_template, '', get_post( $page_id ) );

		/**
		 * Arrange the sections
		 */
		if ( isset( $_POST['content']['sections'] ) ) {

			// Loop through the JSON of edited sections and re-arrange them
			foreach ( $_POST['content']['sections'] as $section_area_index => $section_dom_indexes ) {
				foreach ( $section_dom_indexes as $section_index => $section_dom_index ) {

					// Add the new section in if section_dom_index cand be found from the new_sections array
					// where the structure is: section_dom_prefix => section_template.
					// The section_dom_index in this case will be a index created with uniqid during the section loading
					// to identify the template and its children.
					// Otherwise just use the existing section.
					if ( isset( $_POST['content']['newSections'] ) && array_key_exists( $section_dom_index, $_POST['content']['newSections'] ) ) {
						$section = $this->get_document( Helpers::get_sections_directory() . sanitize_file_name( $_POST['content']['newSections'][ $section_dom_index ] ), $section_dom_index );
					} else {
						$section = $this->dom->find( "[data-sle-dom-index=$section_area_index] > [data-sle-dom-index=$section_dom_index]" );
					}

					// Re-arrange the sections
					if ( $section ) {
						$this->dom->find( "[data-sle-dom-index=$section_area_index]" )->append( $section );
					}
				}
			}
		}

		/**
		 * Save the text content
		 */
		if ( isset( $_POST['content']['texts'] ) ) {

			// Loop through the JSON of edited texts and change the value to document
			foreach ( $_POST['content']['texts'] as $index => $html ) {
				$this->dom->find( ".sle-editable-text[data-sle-dom-index=$index]" )->html( $this->purifier->purify( stripslashes( $html ) ) );
			}
		}

		/**
		 * Save the links
		 */
		if ( isset( $_POST['content']['links'] ) ) {

			// Loop through the JSON of edited link and change the value to document
			foreach ( $_POST['content']['links'] as $index => $href ) {
				$this->dom->find( ".sle-editable-link[data-sle-dom-index=$index]" )->attr( 'href', esc_url( stripslashes( $href ) ) );
			}
		}

		/**
		 * Save the images
		 */
		if ( isset( $_POST['content']['images'] ) ) {

			// Loop through the JSON of edited images and change the values to document
			foreach ( $_POST['content']['images'] as $index => $src ) {

				$src = esc_url( stripslashes( $src ) );
				$element = $this->dom->find( ".sle-editable-image[data-sle-dom-index=$index]" )->get(0);

				// If we have a data-src attribute use that
				if ( pq( $element )->is( '[data-src]' ) ) {
					pq( $element )->attr( 'data-src', $src );
				} else {
					pq( $element )->attr( 'src', $src );
				}
			}
		}

		/**
		 * Save the background images
		 */
		if ( isset( $_POST['content']['bgImages'] ) ) {

			// Loop through the JSON of edited background images and change the value to document
			foreach ( $_POST['content']['bgImages'] as $index => $background_image ) {
				$this->dom->find( "[data-sle-dom-index=$index]" )->attr( 'style', $this->purifier->purify( stripslashes( $background_image ) ) );
			}
		}

		/**
		 * Save the background videos
		 */
		if ( isset( $_POST['content']['bgVideos'] ) ) {

			// Loop through the JSON of edited background videos and change the value to document
			foreach ( $_POST['content']['bgVideos'] as $index => $background_video ) {
				$this->dom->find( "[data-sle-dom-index=$index]" )->attr( 'data-video-urls', esc_url( $background_video ) );
			}
		}

		/**
		 * Remove elements
		 */
		if ( isset( $_POST['content']['removals'] ) ) {

			// Loop through the JSON of removed elements and remove them
			foreach ( array_filter( $_POST['content']['removals'] ) as $index ) {
				$this->dom->find( "[data-sle-dom-index=$index]" )->remove();
			}
		}

		// Save document
		if ( isset( $_POST['language_code'] ) ) {
			$this->save_document( $page_template, $page_id, $_POST['language_code'] );
		} else {
			$this->save_document( $page_template, $page_id );
		}

		wp_die();

	}

	/**
	 * Load the document to DOM and mark the elements
	 *
	 * @since    1.0.0
	 */
	private function get_document( $template, $key_prefix = '', $post = false ) {

		if ( empty( $key_prefix ) ) {
			$dom = phpQuery::newDocumentFilePHP( $this->get_current_template( $template, $post ) );
		} else {
			$dom = phpQuery::newDocumentFilePHP( $template );
		}

		$dom->find( '.sle-text' )->addClass( 'sle-editable-text' );

		/**
		 * Find all text nodes and mark their parents as editable elements
		 */
		foreach ( $dom->find( '*:not(php)' )->contents() as $key => $element ) {

			// Don't do anything if parent already an editable field
			if ( count( pq( $element )->parents( '.sle-editable-text' ) ) > 0 ) {
				continue;
			}

			// Don't do anything if any of the siblings are php tags
			if ( count( pq( $element )->siblings( 'php' ) ) > 0 ) {
				continue;
			}

			// Mark text nodes parents as editable elements unless, text node empty
			if ( $element->nodeType === XML_TEXT_NODE && preg_match( '/\S/', $element->nodeValue ) ) {

				// If the parent is not 'Phrasing content or headings or paragraph' and the acutal element has siblings, wrap the element with div
				/*if ( count( pq( $element )->parent()->not( SLE_PHRASING_CONTENT )->not( SLE_HEADING_CONTENT )->not( 'p' ) ) > 0 && count( pq( $element )->siblings() ) > 0 ) {
					pq( $element )->wrap( '<div class="sle-wrapper-element"></div>' );
				}*/

				// Mark as editable
				// TODO: stop at body / sle-section
				pq( $element )->parent()->parent()->addClass( 'sle-editable-text' );
			}
		}

		/**
		 * Find all images and mark them as editable elements
		 */
		foreach ( $dom->find( 'img' ) as $key => $element ) {

			// Don't do anything if parent already an editable field
			if ( count( pq( $element )->parents( '.sle-editable-text' ) ) > 0 ) {
				continue;
			}

			pq( $element )->addClass( 'sle-editable-image' );

		}

		/**
		 * Find all links and mark them as editable elements
		 */
		foreach ( $dom->find( 'a:not(.sle-editable-text)' ) as $key => $element ) {

			// Don't do anything if parent already an editable field
			if ( count( pq( $element )->parents( '.sle-editable-text' ) ) > 0 ) {
				continue;
			}

			pq( $element )->addClass( 'sle-editable-link' );

		}

		/**
		 * Create our indexing for all HTML elements
		 */
		
		// If new key prefix required, create it
		if ( $key_prefix === true ) {
			$key_prefix = uniqid();
			$dom->find( '*:not(php)' )->eq( 0 )->attr( 'data-sle-dom-index-prefix', $key_prefix );
		}

		foreach ( $dom->find( '*:not(php)' ) as $key => $element ) {

			// Don't do anything if parent already an editable field
			if ( count( pq( $element )->parents( '.sle-editable-text' ) ) > 0 ) {
				continue;
			}

			pq( $element )->attr( 'data-sle-dom-index', empty( $key_prefix ) ? $key : $key_prefix . '_' . $key );
		}

		return $dom;
	}

	/**
	 * Save the document
	 *
	 * @since    1.0.0
	 */
	private function save_document( $template, $id, $language = false ) {

		/**
		 * Remove tmp attributes and classes
		 */
		$this->dom->find( '*:not(php)' )
			->removeAttr( 'data-sle-dom-index' )
			->removeClass( 'sle-editable-text' )
			->removeClass( 'sle-editable-image' )
			->removeClass( 'sle-editable-link' )
			->removeClass( 'sle-editable-bg-image' );

		/**
		 * Build the path to save the template
		 */

		// Get the end part of the path
		$path = Helpers::replace_first_occurrence( $template, get_stylesheet_directory(), '' );

		// Check that the page exists
		$page = get_post( $id );

		// Append page ID
		if ( is_object( $page ) && $page->post_type === 'page' ) {
			$path = '/page-' . $id . $path;
		}

		// If wpml language defined
		if ( ! empty( $language ) ) {
			$path = '/' . $language . $path;
		}

		$path = get_stylesheet_directory() . '/simple-live-editor' . $path;

		// Create the folders
		$dir = dirname( $path );
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0764, true );
		}

		/**
		 * Save the document to template
		 */
		file_put_contents( $path, $this->dom->php() );

	}
}