<?php
/**
 * Plugin Name: OFG Code Editor
 * Description: Adds a reusable code display block, shortcode, and classic editor button with copy support and line numbers.
 * Version: 1.0.3
 * Author: weblogbaz
 * Text Domain: ofg-code-editor
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OFGCODEEDITOR_Code_Editor {
	const VERSION = '1.0.3';

	/**
	 * Boots the plugin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_block_editor_assets' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'media_buttons', array( __CLASS__, 'render_classic_editor_button' ) );
	}

	/**
	 * Returns the supported language map.
	 *
	 * @return array<string,string>
	 */
	public static function get_languages() {
		return array(
			'markup'     => 'HTML',
			'css'        => 'CSS',
			'javascript' => 'JavaScript',
			'php'        => 'PHP',
			'json'       => 'JSON',
			'bash'       => 'Bash',
			'sql'        => 'SQL',
			'plaintext'  => __( 'Text', 'ofg-code-editor' ),
		);
	}

	/**
	 * Registers shortcode aliases for the code box renderer.
	 *
	 * @return void
	 */
	public static function register_shortcodes() {
		add_shortcode( 'ofgcodeeditor_code', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * Registers the server-rendered Gutenberg block.
	 *
	 * @return void
	 */
	public static function register_blocks() {
		register_block_type(
			'ofgcodeeditor/code-block',
			array(
				'attributes'      => array(
					'code'     => array(
						'type'    => 'string',
						'default' => '',
					),
					'language' => array(
						'type'    => 'string',
						'default' => 'markup',
					),
				),
				'render_callback' => array( __CLASS__, 'render_block' ),
			)
		);
	}

	/**
	 * Enqueues frontend assets used by the rendered code box.
	 *
	 * @return void
	 */
	public static function enqueue_frontend_assets() {
		wp_enqueue_style(
			'ofgcodeeditor-code-editor',
			plugin_dir_url( __FILE__ ) . 'assets/css/ofg-code-editor.css',
			array(),
			self::asset_version( 'assets/css/ofg-code-editor.css' )
		);

		wp_enqueue_script(
			'ofgcodeeditor-code-editor',
			plugin_dir_url( __FILE__ ) . 'assets/js/ofg-code-editor.js',
			array(),
			self::asset_version( 'assets/js/ofg-code-editor.js' ),
			true
		);
	}

	/**
	 * Enqueues the Gutenberg block integration.
	 *
	 * @return void
	 */
	public static function enqueue_block_editor_assets() {
		wp_enqueue_style(
			'ofgcodeeditor-code-editor',
			plugin_dir_url( __FILE__ ) . 'assets/css/ofg-code-editor.css',
			array(),
			self::asset_version( 'assets/css/ofg-code-editor.css' )
		);

		wp_enqueue_script(
			'ofgcodeeditor-code-editor-block',
			plugin_dir_url( __FILE__ ) . 'assets/js/ofg-code-editor-block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			self::asset_version( 'assets/js/ofg-code-editor-block.js' ),
			true
		);

		self::set_script_translations( 'ofgcodeeditor-code-editor-block' );
	}

	/**
	 * Enqueues classic editor helpers on post editing screens.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'ofgcodeeditor-code-editor-admin',
			plugin_dir_url( __FILE__ ) . 'assets/js/ofg-code-editor-admin.js',
			array( 'jquery', 'wp-i18n' ),
			self::asset_version( 'assets/js/ofg-code-editor-admin.js' ),
			true
		);

		self::set_script_translations( 'ofgcodeeditor-code-editor-admin' );
	}

	/**
	 * Registers translation files for JavaScript assets.
	 *
	 * @return void
	 */
	private static function set_script_translations( $handle ) {
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( $handle, 'ofg-code-editor', plugin_dir_path( __FILE__ ) . 'languages' );
		}
	}

	/**
	 * Renders the quick insert button above the classic editor.
	 *
	 * @param string $editor_id Editor instance identifier.
	 * @return void
	 */
	public static function render_classic_editor_button( $editor_id ) {
		if ( 'content' !== $editor_id ) {
			return;
		}

		echo '<button type="button" class="button ofgcodeeditor-insert-code-shortcode" data-editor="' . esc_attr( $editor_id ) . '">' . esc_html__( 'OFG Code', 'ofg-code-editor' ) . '</button>';
	}

	/**
	 * Renders the shortcode output.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @param string       $content Shortcode body.
	 * @return string
	 */
	public static function render_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'language' => 'plaintext',
			),
			$atts,
			'ofgcodeeditor_code'
		);

		$content = html_entity_decode( shortcode_unautop( (string) $content ), ENT_QUOTES, get_bloginfo( 'charset' ) );
		$content = str_replace( '<!-- your code here -->', '', $content );

		return self::render_code_box(
			$content,
			array(
				'language' => sanitize_key( $atts['language'] ),
			)
		);
	}

	/**
	 * Renders the dynamic block output.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @param string              $content Saved block content.
	 * @return string
	 */
	public static function render_block( $attributes, $content = '' ) {
		$attributes = is_array( $attributes ) ? $attributes : array();
		$code       = isset( $attributes['code'] ) ? (string) $attributes['code'] : '';
		$language   = isset( $attributes['language'] ) ? sanitize_key( $attributes['language'] ) : 'plaintext';

		if ( '' === trim( $code ) && '' !== trim( $content ) ) {
			$code = self::get_code_from_legacy_block_content( $content );
		}

		return self::render_code_box(
			$code,
			array(
				'language' => $language,
			)
		);
	}

	/**
	 * Reads code from markup saved by earlier static block versions.
	 *
	 * @param string $content Saved block content.
	 * @return string
	 */
	private static function get_code_from_legacy_block_content( $content ) {
		if ( preg_match( '#<pre[^>]*class=(["\'])[^"\']*ofgcodeeditor-code-block__code-source[^"\']*\1[^>]*>(.*?)</pre>#is', $content, $matches ) ) {
			return wp_specialchars_decode( $matches[2], ENT_QUOTES );
		}

		if ( preg_match_all( '#<span[^>]*class=(["\'])[^"\']*ofgcodeeditor-code-block__line-text[^"\']*\1[^>]*>(.*?)</span>#is', $content, $matches ) ) {
			$lines = array_map(
				static function ( $line ) {
					$line = wp_strip_all_tags( $line );
					$line = wp_specialchars_decode( $line, ENT_QUOTES );

					return "\xC2\xA0" === $line ? '' : $line;
				},
				$matches[2]
			);

			return implode( "\n", $lines );
		}

		return '';
	}

	/**
	 * Generates the code box markup.
	 *
	 * @param string $code Raw code body.
	 * @param array  $args Rendering arguments.
	 * @return string
	 */
	public static function render_code_box( $code, $args = array() ) {
		$defaults = array(
			'language' => 'plaintext',
		);
		$args     = wp_parse_args( $args, $defaults );
		$code     = str_replace( array( "\r\n", "\r" ), "\n", (string) $code );
		$code     = trim( $code, "\n" );

		if ( '' === trim( $code ) ) {
			return '';
		}

		$language       = self::normalize_language( $args['language'] );
		$languages      = self::get_languages();
		$language_label = $languages[ $language ];
		$lines          = explode( "\n", $code );

		ob_start();
		?>
		<div class="ofgcodeeditor-code-block" data-language="<?php echo esc_attr( $language ); ?>">
			<div class="ofgcodeeditor-code-block__header">
				<span class="ofgcodeeditor-code-block__title"><?php echo esc_html__( 'OFG Code Editor Plugin', 'ofg-code-editor' ); ?></span>
				<span class="ofgcodeeditor-code-block__language"><?php echo esc_html( $language_label ); ?></span>
				<button
					type="button"
					class="ofgcodeeditor-code-block__copy"
					data-copy-label="<?php echo esc_attr__( 'Copy code', 'ofg-code-editor' ); ?>"
					data-copied-label="<?php echo esc_attr__( 'Copied', 'ofg-code-editor' ); ?>"
				>
					<?php echo esc_html__( 'Copy code', 'ofg-code-editor' ); ?>
				</button>
			</div>
			<div class="ofgcodeeditor-code-block__body">
				<ol class="ofgcodeeditor-code-block__lines">
					<?php foreach ( $lines as $line ) : ?>
						<li class="ofgcodeeditor-code-block__line"><span class="ofgcodeeditor-code-block__line-text"><?php echo '' === $line ? '&nbsp;' : esc_html( $line ); ?></span></li>
					<?php endforeach; ?>
				</ol>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Normalizes the requested language identifier.
	 *
	 * @param string $language Input language.
	 * @return string
	 */
	private static function normalize_language( $language ) {
		$language = sanitize_key( (string) $language );

		if ( 'html' === $language ) {
			$language = 'markup';
		}

		$languages = self::get_languages();

		if ( isset( $languages[ $language ] ) ) {
			return $language;
		}

		return 'plaintext';
	}

	/**
	 * Resolves a version string for cache busting.
	 *
	 * @param string $relative_path Relative asset path.
	 * @return string
	 */
	private static function asset_version( $relative_path ) {
		$file = plugin_dir_path( __FILE__ ) . ltrim( $relative_path, '/' );

		if ( file_exists( $file ) ) {
			return self::VERSION . '.' . filemtime( $file );
		}

		return self::VERSION;
	}
}

OFGCODEEDITOR_Code_Editor::init();
