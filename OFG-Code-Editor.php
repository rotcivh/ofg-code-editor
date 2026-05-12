<?php
/**
 * Plugin Name: OFG Code Editor
 * Plugin URI: https://ofoghweb.com/product/ofg-code-editor
 * Description: Adds a reusable code display block, shortcode, and classic editor button with copy support and line numbers.
 * Version: 1.0.0
 * Author: ofoghweb.com
 * Author URI: https://ofoghweb.com
 * Text Domain: ofg-code-editor
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'OFG_Code_Editor' ) ) {
	final class OFG_Code_Editor {
		const VERSION = '1.0.0';

		/**
		 * Boots the plugin hooks.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
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
				'plaintext'  => 'Text',
			);
		}

		/**
		 * Registers shortcode aliases for the code box renderer.
		 *
		 * @return void
		 */
		public static function register_shortcodes() {
			add_shortcode( 'ofg_code', array( __CLASS__, 'render_shortcode' ) );
			add_shortcode( 'ofogh_code', array( __CLASS__, 'render_shortcode' ) );
		}

		/**
		 * Enqueues frontend assets used by the rendered code box.
		 *
		 * @return void
		 */
		public static function enqueue_frontend_assets() {
			wp_enqueue_style(
				'ofg-code-editor',
				plugin_dir_url( __FILE__ ) . 'assets/css/ofg-code-editor.css',
				array(),
				self::asset_version( 'assets/css/ofg-code-editor.css' )
			);

			wp_enqueue_script(
				'ofg-code-editor',
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
				'ofg-code-editor',
				plugin_dir_url( __FILE__ ) . 'assets/css/ofg-code-editor.css',
				array(),
				self::asset_version( 'assets/css/ofg-code-editor.css' )
			);

			wp_enqueue_script(
				'ofg-code-editor-block',
				plugin_dir_url( __FILE__ ) . 'assets/js/ofg-code-editor-block.js',
				array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
				self::asset_version( 'assets/js/ofg-code-editor-block.js' ),
				true
			);
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
				'ofg-code-editor-admin',
				plugin_dir_url( __FILE__ ) . 'assets/js/ofg-code-editor-admin.js',
				array( 'jquery' ),
				self::asset_version( 'assets/js/ofg-code-editor-admin.js' ),
				true
			);
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

			echo '<button type="button" class="button ofg-insert-code-shortcode" data-editor="' . esc_attr( $editor_id ) . '">OFG Code</button>';
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
				'ofg_code'
			);

			$content = html_entity_decode( shortcode_unautop( (string) $content ), ENT_QUOTES, get_bloginfo( 'charset' ) );

			return self::render_code_box(
				$content,
				array(
					'language' => $atts['language'],
				)
			);
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
			<div class="ofg-code-block" data-language="<?php echo esc_attr( $language ); ?>">
				<div class="ofg-code-block__header">
					<span class="ofg-code-block__title"><?php echo esc_html( 'OFG Code Editor Plugin' ); ?></span>
					<span class="ofg-code-block__language"><?php echo esc_html( $language_label ); ?></span>
					<button
						type="button"
						class="ofg-code-block__copy"
						data-copy-label="<?php echo esc_attr__( 'Copy code', 'ofg-code-editor' ); ?>"
						data-copied-label="<?php echo esc_attr__( 'Copied', 'ofg-code-editor' ); ?>"
					>
						<?php echo esc_html__( 'Copy code', 'ofg-code-editor' ); ?>
					</button>
				</div>
				<div class="ofg-code-block__body">
					<ol class="ofg-code-block__lines">
						<?php foreach ( $lines as $line ) : ?>
							<li class="ofg-code-block__line"><span class="ofg-code-block__line-text"><?php echo '' === $line ? '&nbsp;' : esc_html( $line ); ?></span></li>
						<?php endforeach; ?>
					</ol>
				</div>
				<pre class="ofg-code-block__code-source" hidden><?php echo esc_html( $code ); ?></pre>
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

	OFG_Code_Editor::init();
}
