<?php
/**
 * EZ_Accessibility_Settings
 * Handles the WordPress admin settings page for EZ Accessibility.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EZ_Accessibility_Settings {

	const SLUG = 'ez-accessibility';
	const TABS = [ 'appearance', 'features', 'visibility', 'languages', 'advanced' ];

	// Panel label keys — must match KEYS in assets/js/eza-i18n.js.
	const STRING_KEYS = [
		'panel_title', 'open_menu', 'close',
		'group_visual', 'group_reading', 'group_motion',
		'font_size', 'high_contrast', 'invert', 'grayscale',
		'highlight_links', 'readable_font', 'letter_spacing',
		'pause_anim', 'large_cursor',
		'decrease', 'increase', 'reset',
	];

	// Languages shipped in assets/js/eza-i18n.js.
	const BUILTIN_LANGS = [
		'en', 'es', 'fr', 'de', 'it', 'pt', 'nl', 'pl', 'sv', 'ro', 'cs', 'tr', 'el', 'ru', 'uk',
		'ar', 'he', 'fa', 'ur', 'hi', 'bn', 'th', 'vi', 'id', 'zh', 'zh-tw', 'ja', 'ko',
	];

	const MAX_CUSTOM_LANGS = 50;

	// Custom button icons: raster images only. SVG is deliberately excluded
	// because an SVG file can carry scripts.
	const ICON_MIMES     = [ 'image/png', 'image/jpeg', 'image/webp' ];
	const ICON_MAX_BYTES = 524288; // 512 KB

	// ── Init ──────────────────────────────────────────────────────────────────
	public static function init(): void {
		add_action( 'admin_menu',            [ __CLASS__, 'add_menu' ] );
		add_action( 'admin_init',            [ __CLASS__, 'handle_save' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	// ── Default settings ──────────────────────────────────────────────────────
	public static function defaults(): array {
		$defaults = [
			// Button appearance
			'btn_bg_color'    => '#2346D3',
			'btn_icon_color'  => '#ffffff',
			'btn_size'        => 'medium',       // small | medium | large
			'btn_icon'        => 'person',       // a key of button_icons(), or "custom"
			'btn_custom_icon' => 0,              // attachment ID of an uploaded image

			// Position
			'btn_position'    => 'bottom-right', // top-left | top-right | bottom-left | bottom-right
			'btn_offset'      => 20,             // px from the screen edges

			// Z-index
			'z_index'         => 99999,

			// Visibility
			'visibility_mode'  => 'all', // all | selected
			'visibility_pages' => '',    // comma-separated post/page IDs (used when mode=selected)

			// Languages
			'fallback_lang' => 'en', // used when the page language has no labels
			'custom_langs'  => [],   // code => [ 'name' => …, 'dir' => ltr|rtl ]
			'strings'       => [],   // code => [ key => label ] — overrides and custom-language labels
		];

		foreach ( array_keys( self::features() ) as $feat ) {
			$defaults[ $feat ] = true;
		}

		return $defaults;
	}

	// ── Feature catalogue (key => meta) ───────────────────────────────────────
	public static function features(): array {
		return [
			'feat_font_size'       => [ 'group' => 'visual',  'icon' => 'text-size' ],
			'feat_high_contrast'   => [ 'group' => 'visual',  'icon' => 'contrast' ],
			'feat_invert'          => [ 'group' => 'visual',  'icon' => 'invert' ],
			'feat_grayscale'       => [ 'group' => 'visual',  'icon' => 'drop' ],
			'feat_highlight_links' => [ 'group' => 'reading', 'icon' => 'link' ],
			'feat_readable_font'   => [ 'group' => 'reading', 'icon' => 'type' ],
			'feat_letter_spacing'  => [ 'group' => 'reading', 'icon' => 'spacing' ],
			'feat_pause_anim'      => [ 'group' => 'motion',  'icon' => 'pause' ],
			'feat_large_cursor'    => [ 'group' => 'motion',  'icon' => 'cursor' ],
		];
	}

	// Built-in button icons: choice key => icon() name. Keep in sync with eza-frontend.js.
	public static function button_icons(): array {
		return [
			'person'        => 'logo',
			'person-circle' => 'person-circle',
			'eye'           => 'eye',
			'text'          => 'text-size',
			'adjust'        => 'adjust',
		];
	}

	private static function button_icon_labels(): array {
		return [
			'person'        => __( 'Figure', 'ez-accessibility' ),
			'person-circle' => __( 'Figure in circle', 'ez-accessibility' ),
			'eye'           => __( 'Eye', 'ez-accessibility' ),
			'text'          => __( 'Text', 'ez-accessibility' ),
			'adjust'        => __( 'Adjust', 'ez-accessibility' ),
		];
	}

	// ── Custom icon checks ────────────────────────────────────────────────────
	// Full check, run when settings are saved: the attachment must be a real
	// PNG/JPEG/WebP file under the size limit. The file's contents are
	// inspected, not just its name, so a renamed script or SVG is rejected.
	public static function is_valid_icon_file( int $id ): bool {
		if ( $id < 1 || ! self::is_icon_attachment( $id ) ) {
			return false;
		}
		$file = get_attached_file( $id );
		if ( ! $file || ! is_file( $file ) || filesize( $file ) > self::ICON_MAX_BYTES ) {
			return false;
		}
		$real = wp_get_image_mime( $file ); // reads the file header
		if ( ! $real || ! in_array( $real, self::ICON_MIMES, true ) ) {
			return false;
		}
		$check = wp_check_filetype_and_ext( $file, wp_basename( $file ) );
		return ! empty( $check['type'] ) && in_array( $check['type'], self::ICON_MIMES, true ) && false !== wp_getimagesize( $file );
	}

	// Light check for page loads: still an image attachment of an allowed type.
	private static function is_icon_attachment( int $id ): bool {
		$post = get_post( $id );
		return $post && 'attachment' === $post->post_type
			&& in_array( get_post_mime_type( $post ), self::ICON_MIMES, true );
	}

	// URL of the saved custom icon, or '' if it is missing or no longer valid.
	public static function custom_icon_url( int $id ): string {
		if ( $id < 1 || ! self::is_icon_attachment( $id ) ) {
			return '';
		}
		$url = wp_get_attachment_image_url( $id, 'medium' ); // falls back to full size for small images
		return $url ? esc_url_raw( $url, [ 'http', 'https' ] ) : '';
	}

	// Translated admin-facing copy for each feature.
	private static function feature_copy(): array {
		return [
			'feat_font_size'       => [ __( 'Text size', 'ez-accessibility' ),        __( 'Scale page text up or down in steps, including page-builder text.', 'ez-accessibility' ) ],
			'feat_high_contrast'   => [ __( 'High contrast', 'ez-accessibility' ),    __( 'White text on black with yellow links for maximum legibility.', 'ez-accessibility' ) ],
			'feat_invert'          => [ __( 'Invert colors', 'ez-accessibility' ),    __( 'Flips the color scheme while keeping photos and video natural.', 'ez-accessibility' ) ],
			'feat_grayscale'       => [ __( 'Grayscale', 'ez-accessibility' ),        __( 'Removes color to reduce visual noise.', 'ez-accessibility' ) ],
			'feat_highlight_links' => [ __( 'Highlight links', 'ez-accessibility' ),  __( 'Outlines and underlines every link on the page.', 'ez-accessibility' ) ],
			'feat_readable_font'   => [ __( 'Readable font', 'ez-accessibility' ),    __( 'Swaps to a highly legible typeface, in every language.', 'ez-accessibility' ) ],
			'feat_letter_spacing'  => [ __( 'Letter spacing', 'ez-accessibility' ),   __( 'Adds space between letters in three steps.', 'ez-accessibility' ) ],
			'feat_pause_anim'      => [ __( 'Pause animations', 'ez-accessibility' ), __( 'Stops animations and transitions across the page.', 'ez-accessibility' ) ],
			'feat_large_cursor'    => [ __( 'Large cursor', 'ez-accessibility' ),     __( 'Shows a bigger, high-contrast mouse pointer.', 'ez-accessibility' ) ],
		];
	}

	// ── Get saved option (merged with defaults) ───────────────────────────────
	// Everything read from the database goes through sanitize_settings(), so
	// a bad or hand-edited value can never reach the page unchecked.
	public static function get(): array {
		$saved = get_option( EZ_ACCESSIBILITY_OPTION, [] );
		$saved = is_array( $saved ) ? self::upgrade_legacy_strings( $saved ) : [];
		return self::sanitize_settings( wp_parse_args( $saved, self::defaults() ) );
	}

	// Settings from the old "Qudra AccessKit" option, cleaned for storage.
	public static function from_legacy( array $legacy ): array {
		return self::sanitize_settings( wp_parse_args( self::upgrade_legacy_strings( $legacy ), self::defaults() ) );
	}

	// Older versions stored labels as flat "lang_{code}_{key}" options.
	// Fold any that differ from the old defaults into the "strings" map.
	private static function upgrade_legacy_strings( array $saved ): array {
		$legacy_defaults = [
			'lang_en_panel_title' => 'Accessibility', 'lang_en_font_size' => 'Font Size', 'lang_en_high_contrast' => 'High Contrast',
			'lang_en_invert' => 'Invert Colors', 'lang_en_grayscale' => 'Grayscale', 'lang_en_highlight_links' => 'Highlight Links',
			'lang_en_readable_font' => 'Readable Font', 'lang_en_letter_spacing' => 'Letter Spacing', 'lang_en_pause_anim' => 'Pause Animations',
			'lang_en_large_cursor' => 'Large Cursor', 'lang_en_reset' => 'Reset All', 'lang_en_open_menu' => 'Open accessibility menu',
			'lang_en_close' => 'Close', 'lang_en_group_visual' => 'Display', 'lang_en_group_reading' => 'Reading',
			'lang_en_group_motion' => 'Motion & pointer', 'lang_en_decrease' => 'Decrease', 'lang_en_increase' => 'Increase',
			'lang_ar_panel_title' => 'إمكانية الوصول', 'lang_ar_font_size' => 'حجم الخط', 'lang_ar_high_contrast' => 'تباين عالٍ',
			'lang_ar_invert' => 'عكس الألوان', 'lang_ar_grayscale' => 'تدرج الرمادي', 'lang_ar_highlight_links' => 'إبراز الروابط',
			'lang_ar_readable_font' => 'خط مقروء', 'lang_ar_letter_spacing' => 'تباعد الأحرف', 'lang_ar_pause_anim' => 'إيقاف الحركة',
			'lang_ar_large_cursor' => 'مؤشر كبير', 'lang_ar_reset' => 'إعادة تعيين',
			'lang_he_panel_title' => 'נגישות', 'lang_he_font_size' => 'גודל גופן', 'lang_he_high_contrast' => 'ניגודיות גבוהה',
			'lang_he_invert' => 'היפוך צבעים', 'lang_he_grayscale' => 'גווני אפור', 'lang_he_highlight_links' => 'הדגשת קישורים',
			'lang_he_readable_font' => 'גופן קריא', 'lang_he_letter_spacing' => 'ריווח אותיות', 'lang_he_pause_anim' => 'השהיית אנימציות',
			'lang_he_large_cursor' => 'סמן גדול', 'lang_he_reset' => 'איפוס הכל',
		];

		foreach ( $saved as $key => $value ) {
			if ( ! preg_match( '/^lang_(en|ar|he)_([a-z_]+)$/', (string) $key, $m ) ) {
				continue;
			}
			unset( $saved[ $key ] );
			if ( ! in_array( $m[2], self::STRING_KEYS, true ) || ! is_string( $value ) || '' === $value ) {
				continue;
			}
			if ( ( $legacy_defaults[ $key ] ?? null ) !== $value ) {
				$saved['strings'][ $m[1] ][ $m[2] ] = $value;
			}
		}
		return $saved;
	}

	// ── Language helpers ──────────────────────────────────────────────────────
	public static function sanitize_lang_code( $code ): string {
		$code = strtolower( str_replace( '_', '-', trim( (string) $code ) ) );
		return preg_match( '/^[a-z]{2,3}(-[a-z0-9]{2,8}){0,2}$/', $code ) ? $code : '';
	}

	private static function is_known_lang( $code, array $custom ): bool {
		return in_array( $code, self::BUILTIN_LANGS, true ) || isset( $custom[ $code ] );
	}

	// ── Admin menu ────────────────────────────────────────────────────────────
	public static function add_menu(): void {
		add_menu_page(
			__( 'EZ Accessibility', 'ez-accessibility' ),
			__( 'EZ Accessibility', 'ez-accessibility' ),
			'manage_options',
			self::SLUG,
			[ __CLASS__, 'render_page' ],
			'dashicons-universal-access-alt',
			80
		);
	}

	// ── Enqueue admin assets ──────────────────────────────────────────────────
	public static function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) {
			return;
		}
		$s = self::get();
		wp_enqueue_style( 'ez-accessibility-admin', EZ_ACCESSIBILITY_URL . 'assets/css/eza-admin.css', [], EZ_ACCESSIBILITY_VERSION );
		wp_enqueue_script( 'ez-accessibility-i18n', EZ_ACCESSIBILITY_URL . 'assets/js/eza-i18n.js', [], EZ_ACCESSIBILITY_VERSION, true );
		// WordPress's own media library handles uploads (capability, nonce and file-type checks).
		wp_enqueue_media();
		wp_enqueue_script( 'ez-accessibility-admin', EZ_ACCESSIBILITY_URL . 'assets/js/eza-admin.js', [ 'ez-accessibility-i18n', 'media-editor' ], EZ_ACCESSIBILITY_VERSION, true );
		wp_localize_script( 'ez-accessibility-admin', 'EZAccessibilityAdmin', [
			'icon' => [
				'mimes'    => self::ICON_MIMES,
				'maxBytes' => self::ICON_MAX_BYTES,
			],
			'langs' => [
				'fallback' => $s['fallback_lang'],
				'custom'   => (object) $s['custom_langs'],
				'strings'  => (object) $s['strings'],
				'site'     => self::sanitize_lang_code( get_bloginfo( 'language' ) ),
			],
			'i18n' => [
				'builtIn'       => __( 'Built-in languages', 'ez-accessibility' ),
				'customGroup'   => __( 'Your languages', 'ez-accessibility' ),
				'edited'        => __( 'edited', 'ez-accessibility' ),
				'ltr'           => __( 'Left to right', 'ez-accessibility' ),
				'rtl'           => __( 'Right to left', 'ez-accessibility' ),
				'codeInvalid'   => __( 'Use a language code like “sw”, “pt-br” or “zh-hk”.', 'ez-accessibility' ),
				'codeExists'    => __( 'That language is already in the list — pick it from the menu to edit its labels.', 'ez-accessibility' ),
				'nameRequired'  => __( 'Give the language a name.', 'ez-accessibility' ),
				'tooMany'       => __( 'You have reached the maximum number of custom languages.', 'ez-accessibility' ),
				'confirmDelete' => __( 'Delete this language and its labels?', 'ez-accessibility' ),
				/* translators: %s: language name */
				'restore'       => __( 'Restore %s defaults', 'ez-accessibility' ),
				/* translators: %s: language name */
				'delete'        => __( 'Delete %s', 'ez-accessibility' ),
				'customHint'    => __( 'Empty fields fall back to the fallback language.', 'ez-accessibility' ),
				'builtinHint'   => __( 'Edit any label to override the built-in translation.', 'ez-accessibility' ),
				'saved'         => __( 'Settings saved', 'ez-accessibility' ),
				'saving'        => __( 'Saving…', 'ez-accessibility' ),
				'unsaved'       => __( 'You have unsaved changes', 'ez-accessibility' ),
				'upToDate'      => __( 'All changes saved', 'ez-accessibility' ),
				'leave'         => __( 'You have unsaved changes. Leave anyway?', 'ez-accessibility' ),
				/* translators: %d: number of selected pages */
				'selected'      => __( '%d selected', 'ez-accessibility' ),
				'contrastOk'    => __( 'Good contrast', 'ez-accessibility' ),
				'contrastLow'   => __( 'Low contrast — the icon may be hard to see', 'ez-accessibility' ),
				/* translators: 1: number of tools turned on, 2: total number of tools */
				'featCount'     => __( '%1$d of %2$d turned on', 'ez-accessibility' ),
				'iconTitle'     => __( 'Choose a button icon', 'ez-accessibility' ),
				'iconUse'       => __( 'Use this image', 'ez-accessibility' ),
				'iconBadType'   => __( 'That file type isn’t allowed. Use a PNG, JPEG or WebP image.', 'ez-accessibility' ),
				'iconTooBig'    => __( 'That image is larger than 512 KB. Please use a smaller file.', 'ez-accessibility' ),
			],
		] );
	}

	// ── Handle form save (POST → redirect → GET) ──────────────────────────────
	public static function handle_save(): void {
		if ( ! isset( $_POST['eza_save_settings'] ) ) {
			return;
		}
		check_admin_referer( 'eza_save_settings_action', 'eza_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'ez-accessibility' ) );
		}

		// Read only the fields this form owns; sanitize_settings() validates each one.
		$input = [];
		foreach ( [ 'btn_bg_color', 'btn_icon_color', 'btn_size', 'btn_icon', 'btn_custom_icon', 'btn_position', 'btn_offset', 'z_index', 'visibility_mode' ] as $key ) {
			$input[ $key ] = self::posted_text( $key );
		}
		foreach ( array_keys( self::features() ) as $feat ) {
			$input[ $feat ] = self::posted_text( $feat );
		}
		$input['visibility_pages'] = isset( $_POST['visibility_pages'] ) && is_array( $_POST['visibility_pages'] )
			? array_map( 'absint', wp_unslash( $_POST['visibility_pages'] ) )
			: [];

		// Languages arrive as one JSON field so large label sets never hit
		// max_input_vars. The JSON is decoded first and every value inside is
		// sanitized in sanitize_languages(); running sanitize_text_field() over
		// the whole JSON string would corrupt labels containing < > or %.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decoded, then each value is sanitized in sanitize_languages().
		$langs = isset( $_POST['eza_languages'] ) && is_string( $_POST['eza_languages'] ) ? json_decode( wp_unslash( $_POST['eza_languages'] ), true ) : null;
		if ( is_array( $langs ) ) {
			$input['fallback_lang'] = $langs['fallback'] ?? '';
			$input['custom_langs']  = $langs['custom'] ?? [];
			$input['strings']       = $langs['strings'] ?? [];
		} else {
			// Malformed request: keep the saved languages rather than wiping them.
			$current                = self::get();
			$input['fallback_lang'] = $current['fallback_lang'];
			$input['custom_langs']  = $current['custom_langs'];
			$input['strings']       = $current['strings'];
		}

		$clean = self::sanitize_settings( $input );

		// Custom icon: only an attachment ID is accepted (never a URL), and the
		// file behind it is fully re-checked here no matter what the browser said.
		$icon_rejected = $clean['btn_custom_icon'] > 0 && ! self::is_valid_icon_file( $clean['btn_custom_icon'] );
		if ( $icon_rejected ) {
			$clean['btn_custom_icon'] = 0;
			if ( 'custom' === $clean['btn_icon'] ) {
				$clean['btn_icon'] = 'person';
			}
		}

		update_option( EZ_ACCESSIBILITY_OPTION, $clean );

		$tab   = self::pick( self::posted_text( 'eza_tab' ), self::TABS, 'appearance' );
		$flags = $icon_rejected ? '&eza-icon-rejected=1' : '';
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&eza-saved=1' . $flags . '#' . $tab ) );
		exit;
	}

	// One sanitized text field from the submitted form ('' when missing or not a string).
	// Only called from handle_save(), after the nonce and capability checks.
	private static function posted_text( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in handle_save().
		if ( ! isset( $_POST[ $key ] ) || ! is_string( $_POST[ $key ] ) ) {
			return '';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in handle_save().
		return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
	}

	// ── The single place settings are validated ───────────────────────────────
	// Used for form saves, every database read and the legacy migration.
	// Accepts loosely-typed input and returns only known keys with safe values.
	public static function sanitize_settings( array $in ): array {
		$d   = self::defaults();
		$out = [];
		$txt = static function ( $value ): string {
			return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
		};
		$num = static function ( $value ) use ( $txt ): ?int {
			$value = $txt( $value );
			return ctype_digit( $value ) ? (int) $value : null; // digits only: absint() would turn "-5" into 5
		};

		// Colors
		$out['btn_bg_color']   = sanitize_hex_color( $txt( $in['btn_bg_color'] ?? '' ) ) ?: $d['btn_bg_color'];
		$out['btn_icon_color'] = sanitize_hex_color( $txt( $in['btn_icon_color'] ?? '' ) ) ?: $d['btn_icon_color'];

		// Size, icon, position
		$out['btn_size']        = self::pick( $txt( $in['btn_size'] ?? '' ), [ 'small', 'medium', 'large' ], 'medium' );
		$out['btn_icon']        = self::pick( $txt( $in['btn_icon'] ?? '' ), array_merge( array_keys( self::button_icons() ), [ 'custom' ] ), 'person' );
		$out['btn_custom_icon'] = $num( $in['btn_custom_icon'] ?? '' ) ?? 0;
		if ( 'custom' === $out['btn_icon'] && ! $out['btn_custom_icon'] ) {
			$out['btn_icon'] = 'person';
		}
		$out['btn_position'] = self::pick( $txt( $in['btn_position'] ?? '' ), [ 'top-left', 'top-right', 'bottom-left', 'bottom-right' ], 'bottom-right' );

		$offset            = $num( $in['btn_offset'] ?? '' );
		$out['btn_offset'] = null === $offset ? $d['btn_offset'] : min( 80, $offset );

		$z              = $num( $in['z_index'] ?? '' );
		$out['z_index'] = ( null === $z || $z < 1 || $z > 2147483647 ) ? $d['z_index'] : $z;

		// Visibility (stored as comma-separated IDs; accepts that or an array)
		$out['visibility_mode'] = self::pick( $txt( $in['visibility_mode'] ?? '' ), [ 'all', 'selected' ], 'all' );
		$pages                  = $in['visibility_pages'] ?? '';
		$pages                  = is_string( $pages ) ? explode( ',', $pages ) : ( is_array( $pages ) ? $pages : [] );
		$pages                  = array_filter( array_map( 'absint', array_filter( $pages, 'is_scalar' ) ) );
		$out['visibility_pages'] = implode( ',', array_unique( $pages ) );

		// Feature toggles (true/1/"1" from storage or the form)
		foreach ( array_keys( self::features() ) as $feat ) {
			$value        = $in[ $feat ] ?? $d[ $feat ];
			$out[ $feat ] = true === $value || 1 === $value || '1' === $value;
		}

		return array_merge( $out, self::sanitize_languages( $in ) );
	}

	private static function sanitize_languages( array $in ): array {
		// Custom languages
		$custom = [];
		foreach ( is_array( $in['custom_langs'] ?? null ) ? $in['custom_langs'] : [] as $code => $meta ) {
			$code = self::sanitize_lang_code( $code );
			if ( '' === $code || in_array( $code, self::BUILTIN_LANGS, true ) || ! is_array( $meta ) ) {
				continue;
			}
			$name = is_scalar( $meta['name'] ?? null ) ? mb_substr( sanitize_text_field( (string) $meta['name'] ), 0, 60 ) : '';
			$custom[ $code ] = [
				'name' => '' !== $name ? $name : $code,
				'dir'  => self::pick( $meta['dir'] ?? '', [ 'ltr', 'rtl' ], 'ltr' ),
			];
			if ( count( $custom ) >= self::MAX_CUSTOM_LANGS ) {
				break;
			}
		}

		// Labels: overrides for built-in languages, full sets for custom ones
		$strings = [];
		foreach ( is_array( $in['strings'] ?? null ) ? $in['strings'] : [] as $code => $labels ) {
			$code = self::sanitize_lang_code( $code );
			if ( '' === $code || ! self::is_known_lang( $code, $custom ) || ! is_array( $labels ) ) {
				continue;
			}
			foreach ( self::STRING_KEYS as $key ) {
				$value = is_scalar( $labels[ $key ] ?? null ) ? mb_substr( sanitize_text_field( (string) $labels[ $key ] ), 0, 120 ) : '';
				if ( '' !== $value ) {
					$strings[ $code ][ $key ] = $value;
				}
			}
		}

		$fallback = self::sanitize_lang_code( is_scalar( $in['fallback_lang'] ?? null ) ? $in['fallback_lang'] : '' );

		return [
			'fallback_lang' => self::is_known_lang( $fallback, $custom ) ? $fallback : 'en',
			'custom_langs'  => $custom,
			'strings'       => $strings,
		];
	}

	private static function pick( $value, array $allowed, string $fallback ): string {
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	// ── Inline SVG icons used across the admin UI ─────────────────────────────
	public static function icon( string $name ): string {
		$paths = [
			'logo'      => '<circle cx="12" cy="4.6" r="1.9" fill="currentColor" stroke="none"/><path d="M4.5 8.4c2.5.8 5 1.1 7.5 1.1s5-.3 7.5-1.1"/><path d="M12 9.6v4.6"/><path d="M12 14.2 9 20.5"/><path d="m12 14.2 3 6.3"/>',
			'brush'     => '<path d="M14.6 3.4a2 2 0 0 1 2.8 0l3.2 3.2a2 2 0 0 1 0 2.8L12 18l-6-6z"/><path d="M6 12c-2 0-3 1.5-3 3.5S2 19 2 19s3 2 5.5 1S9 17 9 15"/>',
			'toggles'   => '<rect x="2" y="5" width="20" height="14" rx="7"/><circle cx="15" cy="12" r="3"/>',
			'eye'       => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
			'person-circle' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="7.3" r="1.4" fill="currentColor" stroke="none"/><path d="M7.5 10.2c1.5.4 3 .6 4.5.6s3-.2 4.5-.6"/><path d="M12 10.8v3"/><path d="m12 13.8-2 3.7"/><path d="m12 13.8 2 3.7"/>',
			'adjust'    => '<path d="M4 6h9M17 6h3M4 12h3M11 12h9M4 18h11M19 18h1"/><circle cx="15" cy="6" r="2"/><circle cx="9" cy="12" r="2"/><circle cx="17" cy="18" r="2"/>',
			'upload'    => '<path d="M12 15V4"/><path d="m7 9 5-5 5 5"/><path d="M4 15v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/>',
			'globe'     => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/>',
			'sliders'   => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
			'text-size' => '<path d="m3 19 5-14 5 14"/><path d="M5 14h6"/><path d="m14 19 3.5-9 3.5 9"/><path d="M15.2 16h4.6"/>',
			'contrast'  => '<circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/>',
			'invert'    => '<rect x="3" y="3" width="18" height="18" rx="4"/><path d="M21 7v10a4 4 0 0 1-4 4H7z" fill="currentColor"/>',
			'drop'      => '<path d="M12 2.8c3.6 4.3 6 7.6 6 10.7a6 6 0 0 1-12 0c0-3.1 2.4-6.4 6-10.7z"/><path d="M12 19.5a6 6 0 0 0 6-6c0-.6-.1-1.2-.3-1.8H6.3c-.2.6-.3 1.2-.3 1.8a6 6 0 0 0 6 6z" fill="currentColor"/>',
			'link'      => '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/>',
			'type'      => '<path d="M4 7V5h16v2"/><path d="M12 5v14"/><path d="M9 19h6"/>',
			'spacing'   => '<path d="m8 14 4-10 4 10"/><path d="M9.6 10.5h4.8"/><path d="M3 19h18"/><path d="m6 16.5-3 2.5 3 2.5"/><path d="m18 16.5 3 2.5-3 2.5"/>',
			'pause'     => '<circle cx="12" cy="12" r="9"/><path d="M10 9v6M14 9v6"/>',
			'cursor'    => '<path d="M5 3.5 19 11l-6.2 1.7L9.6 19z" fill="currentColor" fill-opacity=".15"/>',
			'search'    => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
			'check'     => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
			'undo'      => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
		];
		return '<svg class="eza-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $paths[ $name ] ?? '' ) . '</svg>';
	}

	// Print an icon, escaped with an SVG-only allowlist.
	private static function the_icon( string $name ): void {
		$shape = [ 'd' => true, 'fill' => true, 'fill-opacity' => true, 'stroke' => true, 'cx' => true, 'cy' => true, 'r' => true, 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true ];
		echo wp_kses(
			self::icon( $name ),
			[
				'svg'    => [ 'class' => true, 'viewbox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'aria-hidden' => true, 'focusable' => true ],
				'path'   => $shape,
				'circle' => $shape,
				'rect'   => $shape,
			]
		);
	}

	// ── Render admin page ─────────────────────────────────────────────────────
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = self::get();

		$nav = [
			'appearance' => [ __( 'Appearance', 'ez-accessibility' ), 'brush' ],
			'features'   => [ __( 'Features', 'ez-accessibility' ), 'toggles' ],
			'visibility' => [ __( 'Visibility', 'ez-accessibility' ), 'eye' ],
			'languages'  => [ __( 'Languages', 'ez-accessibility' ), 'globe' ],
			'advanced'   => [ __( 'Advanced', 'ez-accessibility' ), 'sliders' ],
		];

		$selected_ids = array_filter( array_map( 'absint', explode( ',', $s['visibility_pages'] ) ) );
		if ( 'all' === $s['visibility_mode'] ) {
			$status = __( 'Showing on every page', 'ez-accessibility' );
		} elseif ( $selected_ids ) {
			/* translators: %d: number of pages */
			$status = sprintf( _n( 'Showing on %d page', 'Showing on %d pages', count( $selected_ids ), 'ez-accessibility' ), count( $selected_ids ) );
		} else {
			$status = '';
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display-only flags set by our own redirect.
		$just_saved    = isset( $_GET['eza-saved'] );
		$icon_rejected = isset( $_GET['eza-icon-rejected'] );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap eza-wrap">
			<header class="eza-topbar">
				<div class="eza-brand">
					<span class="eza-brand-mark"><img src="<?php echo esc_url( EZ_ACCESSIBILITY_URL . 'assets/img/logo.png' ); ?>" alt="" width="64" height="64"></span>
					<div>
						<h1><?php esc_html_e( 'EZ Accessibility', 'ez-accessibility' ); ?></h1>
						<p><?php esc_html_e( 'Give every visitor a comfortable way to read your site.', 'ez-accessibility' ); ?></p>
					</div>
				</div>
				<?php if ( $status ) : ?>
					<p class="eza-status">
						<?php echo esc_html( $status ); ?>
						<a href="#visibility" data-goto="visibility"><?php esc_html_e( 'Change', 'ez-accessibility' ); ?></a>
					</p>
				<?php endif; ?>
			</header>
			<hr class="wp-header-end">

			<?php if ( $icon_rejected ) : ?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'The custom icon wasn’t saved because the file isn’t a valid PNG, JPEG or WebP image under 512 KB. The button is using the default icon instead.', 'ez-accessibility' ); ?></p>
				</div>
			<?php endif; ?>
			<?php if ( ! $status ) : ?>
				<div class="notice notice-warning">
					<p>
						<?php esc_html_e( 'The button is hidden everywhere: “Selected pages only” is on, but no pages are selected.', 'ez-accessibility' ); ?>
						<a href="#visibility" data-goto="visibility"><?php esc_html_e( 'Choose pages', 'ez-accessibility' ); ?></a>
					</p>
				</div>
			<?php endif; ?>

			<form method="post" action="" class="eza-form" id="eza-form" style="--eza-bg:<?php echo esc_attr( $s['btn_bg_color'] ); ?>;--eza-fg:<?php echo esc_attr( $s['btn_icon_color'] ); ?>" data-saved="<?php echo esc_attr( $just_saved ? '1' : '0' ); ?>">
				<?php wp_nonce_field( 'eza_save_settings_action', 'eza_nonce' ); ?>
				<input type="hidden" name="eza_save_settings" value="1">
				<input type="hidden" name="eza_tab" id="eza-tab-field" value="appearance">

				<div class="eza-layout">

					<nav class="eza-nav" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'ez-accessibility' ); ?>" aria-orientation="vertical">
						<?php foreach ( $nav as $id => [ $label, $icon ] ) : ?>
							<button type="button" role="tab" class="eza-nav-item" id="eza-tab-<?php echo esc_attr( $id ); ?>" data-tab="<?php echo esc_attr( $id ); ?>" aria-controls="eza-panel-<?php echo esc_attr( $id ); ?>" aria-selected="<?php echo esc_attr( 'appearance' === $id ? 'true' : 'false' ); ?>" tabindex="<?php echo esc_attr( 'appearance' === $id ? '0' : '-1' ); ?>">
								<?php self::the_icon( $icon ); ?>
								<span><?php echo esc_html( $label ); ?></span>
							</button>
						<?php endforeach; ?>
					</nav>

					<div class="eza-content">
						<?php
						self::section_appearance( $s );
						self::section_features( $s );
						self::section_visibility( $s, $selected_ids );
						self::section_languages( $s );
						self::section_advanced( $s );
						?>
					</div>

					<?php self::render_preview( $s ); ?>

				</div>

				<div class="eza-savebar" id="eza-savebar">
					<span class="eza-savebar-msg" id="eza-savebar-msg" aria-live="polite"><?php esc_html_e( 'All changes saved', 'ez-accessibility' ); ?></span>
					<div class="eza-savebar-actions">
						<button type="button" class="eza-btn eza-btn--ghost" id="eza-discard" disabled><?php esc_html_e( 'Discard', 'ez-accessibility' ); ?></button>
						<button type="submit" class="eza-btn eza-btn--primary" id="eza-save"><?php esc_html_e( 'Save changes', 'ez-accessibility' ); ?></button>
					</div>
				</div>
			</form>

			<p class="eza-credit">
				<?php
				echo wp_kses(
					sprintf(
						/* translators: 1: plugin version, 2: link to the developer's site */
						esc_html__( 'EZ Accessibility %1$s · by %2$s', 'ez-accessibility' ),
						esc_html( EZ_ACCESSIBILITY_VERSION ),
						'<a href="' . esc_url( 'https://greyteck.site' ) . '" target="_blank" rel="noopener">' . esc_html__( 'Greyteck Studios', 'ez-accessibility' ) . '</a>'
					),
					[ 'a' => [ 'href' => true, 'target' => true, 'rel' => true ] ]
				);
				?>
			</p>

			<div class="eza-toast" id="eza-toast" role="status" aria-live="polite" hidden>
				<?php self::the_icon( 'check' ); ?>
				<span><?php esc_html_e( 'Settings saved', 'ez-accessibility' ); ?></span>
			</div>
		</div>
		<?php
	}

	// ── Section helpers ───────────────────────────────────────────────────────
	private static function section_open( string $id, string $title, string $desc ): void {
		?>
		<section class="eza-panel" id="eza-panel-<?php echo esc_attr( $id ); ?>" role="tabpanel" aria-labelledby="eza-tab-<?php echo esc_attr( $id ); ?>" tabindex="0"<?php if ( 'appearance' !== $id ) { echo ' hidden'; } ?>>
			<div class="eza-panel-head">
				<h2><?php echo esc_html( $title ); ?></h2>
				<p><?php echo esc_html( $desc ); ?></p>
			</div>
		<?php
	}

	private static function color_field( string $name, string $label, string $value, array $presets ): void {
		?>
		<div class="eza-field">
			<span class="eza-label" id="<?php echo esc_attr( $name ); ?>-label"><?php echo esc_html( $label ); ?></span>
			<div class="eza-color" data-color-field>
				<div class="eza-swatches" role="group" aria-labelledby="<?php echo esc_attr( $name ); ?>-label">
					<?php foreach ( $presets as $hex => $preset_label ) : ?>
						<button type="button" class="eza-swatch" style="--sw:<?php echo esc_attr( $hex ); ?>" data-color="<?php echo esc_attr( $hex ); ?>" aria-label="<?php echo esc_attr( $preset_label ); ?>" aria-pressed="<?php echo esc_attr( strtolower( $value ) === strtolower( $hex ) ? 'true' : 'false' ); ?>"></button>
					<?php endforeach; ?>
				</div>
				<label class="eza-color-custom">
					<input type="color" value="<?php echo esc_attr( $value ); ?>" data-color-picker aria-label="<?php echo esc_attr( sprintf( /* translators: %s: field label */ __( 'Custom %s', 'ez-accessibility' ), strtolower( $label ) ) ); ?>">
					<input type="text" class="eza-input eza-input--hex" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" maxlength="7" spellcheck="false" autocomplete="off" pattern="#[0-9a-fA-F]{6}" data-color-hex aria-labelledby="<?php echo esc_attr( $name ); ?>-label">
				</label>
			</div>
		</div>
		<?php
	}

	private static function section_appearance( array $s ): void {
		self::section_open( 'appearance', __( 'Appearance', 'ez-accessibility' ), __( 'Match the button to your brand and choose where it sits on screen.', 'ez-accessibility' ) );
		$custom_url = self::custom_icon_url( (int) $s['btn_custom_icon'] );
		$icon       = ( 'custom' === $s['btn_icon'] && ! $custom_url ) ? 'person' : $s['btn_icon'];
		?>
			<div class="eza-card">
				<div class="eza-field">
					<span class="eza-label" id="eza-icon-label"><?php esc_html_e( 'Button icon', 'ez-accessibility' ); ?></span>
					<div class="eza-icons" role="radiogroup" aria-labelledby="eza-icon-label">
						<?php foreach ( self::button_icon_labels() as $key => $label ) : ?>
							<label class="eza-icon-choice">
								<input type="radio" name="btn_icon" value="<?php echo esc_attr( $key ); ?>" <?php checked( $icon, $key ); ?>>
								<span class="eza-icon-face" data-icon-face><?php self::the_icon( self::button_icons()[ $key ] ); ?></span>
								<span class="eza-icon-name"><?php echo esc_html( $label ); ?></span>
							</label>
						<?php endforeach; ?>
						<label class="eza-icon-choice">
							<input type="radio" name="btn_icon" value="custom" id="eza-icon-custom" <?php checked( $icon, 'custom' ); ?>>
							<span class="eza-icon-face eza-icon-face--custom" id="eza-custom-face" data-icon-face>
								<?php if ( $custom_url ) : ?>
									<img src="<?php echo esc_url( $custom_url ); ?>" alt="">
								<?php else : ?>
									<?php self::the_icon( 'upload' ); ?>
								<?php endif; ?>
							</span>
							<span class="eza-icon-name"><?php esc_html_e( 'Your image', 'ez-accessibility' ); ?></span>
						</label>
					</div>

					<div class="eza-upload" id="eza-upload"<?php if ( 'custom' !== $icon ) { echo ' hidden'; } ?>>
						<input type="hidden" name="btn_custom_icon" id="eza-custom-icon" value="<?php echo esc_attr( $custom_url ? (int) $s['btn_custom_icon'] : 0 ); ?>">
						<div class="eza-upload-actions">
							<button type="button" class="eza-btn eza-btn--ghost eza-btn--sm" id="eza-icon-pick">
								<?php echo $custom_url ? esc_html__( 'Replace image', 'ez-accessibility' ) : esc_html__( 'Upload or choose image', 'ez-accessibility' ); ?>
							</button>
							<button type="button" class="eza-link-btn eza-link-btn--danger" id="eza-icon-remove"<?php if ( ! $custom_url ) { echo ' hidden'; } ?>><?php esc_html_e( 'Remove', 'ez-accessibility' ); ?></button>
						</div>
						<p class="eza-hint"><?php esc_html_e( 'PNG, JPEG or WebP, up to 512 KB. A square image with a transparent background works best. The icon color setting doesn’t apply to images.', 'ez-accessibility' ); ?></p>
						<p class="eza-hint"><?php esc_html_e( 'SVG files aren’t accepted, because they can contain code that runs in visitors’ browsers.', 'ez-accessibility' ); ?></p>
						<p class="eza-error" id="eza-icon-error" role="alert" hidden></p>
					</div>
				</div>
			</div>

			<div class="eza-card">
				<?php
				self::color_field( 'btn_bg_color', __( 'Brand color', 'ez-accessibility' ), $s['btn_bg_color'], [
					'#2346D3' => __( 'Blue', 'ez-accessibility' ),
					'#0F766E' => __( 'Teal', 'ez-accessibility' ),
					'#6D28D9' => __( 'Violet', 'ez-accessibility' ),
					'#BE123C' => __( 'Rose', 'ez-accessibility' ),
					'#B45309' => __( 'Amber', 'ez-accessibility' ),
					'#111827' => __( 'Ink', 'ez-accessibility' ),
				] );
				self::color_field( 'btn_icon_color', __( 'Icon color', 'ez-accessibility' ), $s['btn_icon_color'], [
					'#ffffff' => __( 'White', 'ez-accessibility' ),
					'#111827' => __( 'Ink', 'ez-accessibility' ),
				] );
				?>
				<p class="eza-contrast" id="eza-contrast" aria-live="polite">
					<span class="eza-contrast-ratio"></span>
					<span class="eza-contrast-text"></span>
				</p>
			</div>

			<div class="eza-card">
				<div class="eza-field">
					<span class="eza-label" id="eza-size-label"><?php esc_html_e( 'Button size', 'ez-accessibility' ); ?></span>
					<div class="eza-segmented" role="radiogroup" aria-labelledby="eza-size-label">
						<?php
						$sizes = [
							'small'  => [ __( 'Small', 'ez-accessibility' ), '48px' ],
							'medium' => [ __( 'Medium', 'ez-accessibility' ), '56px' ],
							'large'  => [ __( 'Large', 'ez-accessibility' ), '64px' ],
						];
						foreach ( $sizes as $val => [ $label, $px ] ) :
							?>
							<label class="eza-segment">
								<input type="radio" name="btn_size" value="<?php echo esc_attr( $val ); ?>" <?php checked( $s['btn_size'], $val ); ?>>
								<span><?php echo esc_html( $label ); ?> <small><?php echo esc_html( $px ); ?></small></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="eza-field">
					<span class="eza-label" id="eza-pos-label"><?php esc_html_e( 'Position', 'ez-accessibility' ); ?></span>
					<div class="eza-corners" role="radiogroup" aria-labelledby="eza-pos-label">
						<?php
						$corners = [
							'top-left'     => __( 'Top left', 'ez-accessibility' ),
							'top-right'    => __( 'Top right', 'ez-accessibility' ),
							'bottom-left'  => __( 'Bottom left', 'ez-accessibility' ),
							'bottom-right' => __( 'Bottom right', 'ez-accessibility' ),
						];
						foreach ( $corners as $val => $label ) :
							?>
							<label class="eza-corner eza-corner--<?php echo esc_attr( $val ); ?>">
								<input type="radio" name="btn_position" value="<?php echo esc_attr( $val ); ?>" <?php checked( $s['btn_position'], $val ); ?>>
								<span class="eza-corner-box"><span class="eza-corner-dot"></span></span>
								<span class="eza-corner-name"><?php echo esc_html( $label ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<p class="eza-hint"><?php esc_html_e( 'On right-to-left (RTL) pages the button mirrors to the opposite side automatically.', 'ez-accessibility' ); ?></p>
				</div>

				<div class="eza-field">
					<label class="eza-label" for="eza-offset"><?php esc_html_e( 'Distance from edge', 'ez-accessibility' ); ?></label>
					<div class="eza-range">
						<input type="range" id="eza-offset" name="btn_offset" min="0" max="80" step="2" value="<?php echo esc_attr( $s['btn_offset'] ); ?>">
						<output for="eza-offset" id="eza-offset-out"><?php echo esc_html( $s['btn_offset'] ); ?>px</output>
					</div>
				</div>
			</div>
		</section>
		<?php
	}

	private static function section_features( array $s ): void {
		self::section_open( 'features', __( 'Features', 'ez-accessibility' ), __( 'Choose which tools appear in the visitor panel. Turned-off tools are hidden completely.', 'ez-accessibility' ) );
		$groups = [
			'visual'  => __( 'Display', 'ez-accessibility' ),
			'reading' => __( 'Reading', 'ez-accessibility' ),
			'motion'  => __( 'Motion & pointer', 'ez-accessibility' ),
		];
		$copy = self::feature_copy();
		?>
			<div class="eza-bulk">
				<span id="eza-feat-count" aria-live="polite"></span>
				<span class="eza-bulk-actions">
					<button type="button" class="eza-link-btn" data-bulk="on"><?php esc_html_e( 'Enable all', 'ez-accessibility' ); ?></button>
					<button type="button" class="eza-link-btn" data-bulk="off"><?php esc_html_e( 'Disable all', 'ez-accessibility' ); ?></button>
				</span>
			</div>
			<?php foreach ( $groups as $group => $group_label ) : ?>
				<h3 class="eza-group-title"><?php echo esc_html( $group_label ); ?></h3>
				<div class="eza-feature-grid">
					<?php
					foreach ( self::features() as $key => $meta ) :
						if ( $meta['group'] !== $group ) {
							continue;
						}
						[ $title, $desc ] = $copy[ $key ];
						?>
						<label class="eza-feature">
							<span class="eza-feature-icon"><?php self::the_icon( $meta['icon'] ); ?></span>
							<span class="eza-feature-text">
								<strong><?php echo esc_html( $title ); ?></strong>
								<span><?php echo esc_html( $desc ); ?></span>
							</span>
							<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="0">
							<input type="checkbox" class="eza-switch" role="switch" name="<?php echo esc_attr( $key ); ?>" value="1" data-feature="<?php echo esc_attr( $key ); ?>" data-icon="<?php echo esc_attr( $meta['icon'] ); ?>" <?php checked( (bool) $s[ $key ] ); ?>>
						</label>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</section>
		<?php
	}

	private static function section_visibility( array $s, array $selected_ids ): void {
		self::section_open( 'visibility', __( 'Visibility', 'ez-accessibility' ), __( 'Show the accessibility button everywhere, or only on the pages you pick.', 'ez-accessibility' ) );
		$all_posts = get_posts( [
			'post_type'      => [ 'page', 'post' ],
			'posts_per_page' => 500,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );
		?>
			<div class="eza-choice-grid" role="radiogroup" aria-label="<?php esc_attr_e( 'Show widget on', 'ez-accessibility' ); ?>">
				<label class="eza-choice">
					<input type="radio" name="visibility_mode" value="all" <?php checked( $s['visibility_mode'], 'all' ); ?>>
					<span class="eza-choice-body">
						<strong><?php esc_html_e( 'Entire site', 'ez-accessibility' ); ?></strong>
						<span><?php esc_html_e( 'Recommended. Every page gets the accessibility button.', 'ez-accessibility' ); ?></span>
					</span>
				</label>
				<label class="eza-choice">
					<input type="radio" name="visibility_mode" value="selected" <?php checked( $s['visibility_mode'], 'selected' ); ?>>
					<span class="eza-choice-body">
						<strong><?php esc_html_e( 'Selected pages only', 'ez-accessibility' ); ?></strong>
						<span><?php esc_html_e( 'Pick specific pages and posts from a list.', 'ez-accessibility' ); ?></span>
					</span>
				</label>
			</div>

			<div class="eza-card eza-picker" id="eza-page-picker"<?php if ( 'selected' !== $s['visibility_mode'] ) { echo ' hidden'; } ?>>
				<div class="eza-picker-bar">
					<label class="eza-search">
						<?php self::the_icon( 'search' ); ?>
						<span class="screen-reader-text"><?php esc_html_e( 'Search pages', 'ez-accessibility' ); ?></span>
						<input type="search" id="eza-page-search" class="eza-input" placeholder="<?php esc_attr_e( 'Search pages and posts…', 'ez-accessibility' ); ?>">
					</label>
					<span class="eza-picker-count" id="eza-picker-count" aria-live="polite"></span>
					<button type="button" class="eza-link-btn" id="eza-select-all"><?php esc_html_e( 'Select all', 'ez-accessibility' ); ?></button>
					<button type="button" class="eza-link-btn" id="eza-select-none"><?php esc_html_e( 'Clear', 'ez-accessibility' ); ?></button>
				</div>
				<?php if ( empty( $all_posts ) ) : ?>
					<p class="eza-empty"><?php esc_html_e( 'No published pages or posts yet.', 'ez-accessibility' ); ?></p>
				<?php else : ?>
					<div class="eza-page-list" id="eza-page-list">
						<?php
						foreach ( $all_posts as $post_item ) :
							$type_obj   = get_post_type_object( $post_item->post_type );
							$type_label = $type_obj ? $type_obj->labels->singular_name : $post_item->post_type;
							$title      = '' !== $post_item->post_title ? $post_item->post_title : __( '(no title)', 'ez-accessibility' );
							?>
							<label class="eza-page-item">
								<input type="checkbox" name="visibility_pages[]" value="<?php echo esc_attr( $post_item->ID ); ?>" <?php checked( in_array( $post_item->ID, $selected_ids, true ) ); ?>>
								<span class="eza-page-title"><?php echo esc_html( $title ); ?></span>
								<span class="eza-page-type"><?php echo esc_html( $type_label ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<p class="eza-empty" id="eza-page-empty" hidden><?php esc_html_e( 'Nothing matches your search.', 'ez-accessibility' ); ?></p>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}

	private static function section_languages( array $s ): void {
		self::section_open( 'languages', __( 'Languages', 'ez-accessibility' ), __( 'The panel speaks your visitor’s language automatically, based on the page’s lang attribute. 28 languages are built in, left-to-right and right-to-left. Edit any of them, or add your own.', 'ez-accessibility' ) );
		$field_labels = [
			'panel_title'     => __( 'Panel title', 'ez-accessibility' ),
			'open_menu'       => __( 'Button label (screen readers)', 'ez-accessibility' ),
			'close'           => __( 'Close', 'ez-accessibility' ),
			'group_visual'    => __( 'Group: Display', 'ez-accessibility' ),
			'group_reading'   => __( 'Group: Reading', 'ez-accessibility' ),
			'group_motion'    => __( 'Group: Motion', 'ez-accessibility' ),
			'font_size'       => __( 'Text size', 'ez-accessibility' ),
			'high_contrast'   => __( 'High contrast', 'ez-accessibility' ),
			'invert'          => __( 'Invert colors', 'ez-accessibility' ),
			'grayscale'       => __( 'Grayscale', 'ez-accessibility' ),
			'highlight_links' => __( 'Highlight links', 'ez-accessibility' ),
			'readable_font'   => __( 'Readable font', 'ez-accessibility' ),
			'letter_spacing'  => __( 'Letter spacing', 'ez-accessibility' ),
			'pause_anim'      => __( 'Pause animations', 'ez-accessibility' ),
			'large_cursor'    => __( 'Large cursor', 'ez-accessibility' ),
			'decrease'        => __( 'Decrease (screen readers)', 'ez-accessibility' ),
			'increase'        => __( 'Increase (screen readers)', 'ez-accessibility' ),
			'reset'           => __( 'Reset button', 'ez-accessibility' ),
		];
		?>
			<input type="hidden" name="eza_languages" id="eza-languages-field" value="">

			<div class="eza-card">
				<div class="eza-field eza-field--inline">
					<div>
						<label class="eza-label" for="eza-fallback-lang"><?php esc_html_e( 'Fallback language', 'ez-accessibility' ); ?></label>
						<p class="eza-hint"><?php esc_html_e( 'Used when a page’s language isn’t in the list, and for any label you leave empty.', 'ez-accessibility' ); ?></p>
					</div>
					<select id="eza-fallback-lang" class="eza-input eza-select"></select>
				</div>
			</div>

			<div class="eza-card eza-card--flush">
				<div class="eza-lang-bar">
					<label class="eza-lang-pick">
						<span class="screen-reader-text"><?php esc_html_e( 'Language to edit', 'ez-accessibility' ); ?></span>
						<?php self::the_icon( 'globe' ); ?>
						<select id="eza-lang-select" class="eza-input eza-select"></select>
					</label>
					<span class="eza-lang-meta" id="eza-lang-meta"></span>
					<button type="button" class="eza-btn eza-btn--ghost eza-btn--sm" id="eza-lang-add-toggle" aria-expanded="false" aria-controls="eza-lang-add">
						<?php esc_html_e( '+ Add language', 'ez-accessibility' ); ?>
					</button>
				</div>

				<div class="eza-lang-add" id="eza-lang-add" hidden>
					<div class="eza-lang-add-grid">
						<label class="eza-lang-field">
							<span><?php esc_html_e( 'Language code', 'ez-accessibility' ); ?></span>
							<input type="text" class="eza-input" id="eza-new-code" placeholder="sw" maxlength="20" spellcheck="false" autocomplete="off" aria-describedby="eza-new-code-hint">
						</label>
						<label class="eza-lang-field">
							<span><?php esc_html_e( 'Name', 'ez-accessibility' ); ?></span>
							<input type="text" class="eza-input" id="eza-new-name" placeholder="Kiswahili" maxlength="60">
						</label>
						<fieldset class="eza-lang-field eza-dir-field">
							<legend><?php esc_html_e( 'Direction', 'ez-accessibility' ); ?></legend>
							<div class="eza-segmented">
								<label class="eza-segment"><input type="radio" name="eza_new_dir" value="ltr" checked form="eza-none"><span><?php esc_html_e( 'Left to right', 'ez-accessibility' ); ?></span></label>
								<label class="eza-segment"><input type="radio" name="eza_new_dir" value="rtl" form="eza-none"><span><?php esc_html_e( 'Right to left', 'ez-accessibility' ); ?></span></label>
							</div>
						</fieldset>
					</div>
					<p class="eza-hint" id="eza-new-code-hint"><?php esc_html_e( 'Use the code from your page’s lang attribute, e.g. “sw” for Swahili or “pt-br” for Brazilian Portuguese. A regional code like “pt-br” takes priority over “pt”.', 'ez-accessibility' ); ?></p>
					<p class="eza-error" id="eza-new-error" role="alert" hidden></p>
					<div class="eza-lang-add-actions">
						<button type="button" class="eza-btn eza-btn--ghost eza-btn--sm" id="eza-lang-add-cancel"><?php esc_html_e( 'Cancel', 'ez-accessibility' ); ?></button>
						<button type="button" class="eza-btn eza-btn--primary eza-btn--sm" id="eza-lang-add-save"><?php esc_html_e( 'Add language', 'ez-accessibility' ); ?></button>
					</div>
				</div>

				<div class="eza-lang-panel">
					<p class="eza-hint eza-lang-hint" id="eza-lang-hint"></p>
					<div class="eza-lang-grid" id="eza-lang-grid">
						<?php foreach ( $field_labels as $key => $label ) : ?>
							<label class="eza-lang-field">
								<span><?php echo esc_html( $label ); ?></span>
								<input type="text" class="eza-input" maxlength="120" data-string="<?php echo esc_attr( $key ); ?>">
							</label>
						<?php endforeach; ?>
					</div>
					<div class="eza-lang-foot">
						<button type="button" class="eza-link-btn" id="eza-lang-restore">
							<?php self::the_icon( 'undo' ); ?>
							<span></span>
						</button>
						<button type="button" class="eza-link-btn eza-link-btn--danger" id="eza-lang-delete" hidden><span></span></button>
					</div>
				</div>
			</div>
		</section>
		<?php
	}

	private static function section_advanced( array $s ): void {
		self::section_open( 'advanced', __( 'Advanced', 'ez-accessibility' ), __( 'Fine-tuning for themes that need it. Most sites can leave these alone.', 'ez-accessibility' ) );
		?>
			<div class="eza-card">
				<div class="eza-field eza-field--inline">
					<div>
						<label class="eza-label" for="eza-z-index"><?php esc_html_e( 'Stacking order (z-index)', 'ez-accessibility' ); ?></label>
						<p class="eza-hint"><?php esc_html_e( 'Raise this if a sticky header or chat bubble covers the button.', 'ez-accessibility' ); ?></p>
					</div>
					<input type="number" id="eza-z-index" name="z_index" value="<?php echo esc_attr( $s['z_index'] ); ?>" min="1" max="2147483647" class="eza-input eza-input--short">
				</div>
			</div>

			<div class="eza-card eza-card--muted">
				<h3 class="eza-card-title"><?php esc_html_e( 'JavaScript API', 'ez-accessibility' ); ?></h3>
				<p class="eza-hint"><?php esc_html_e( 'Control the panel from your theme, or refresh its language after a multilingual plugin switches languages:', 'ez-accessibility' ); ?></p>
				<pre class="eza-code"><code>window.EZAccessibility.open();
window.EZAccessibility.close();
window.EZAccessibility.toggle();
window.EZAccessibility.reset();
window.EZAccessibility.refresh();</code></pre>
			</div>
		</section>
		<?php
	}

	// ── Live preview (sticky sidebar) ─────────────────────────────────────────
	private static function render_preview( array $s ): void {
		// Labels are filled in by eza-admin.js in the language being edited.
		?>
		<aside class="eza-preview" aria-label="<?php esc_attr_e( 'Live preview', 'ez-accessibility' ); ?>">
			<div class="eza-preview-head">
				<span><?php esc_html_e( 'Live preview', 'ez-accessibility' ); ?></span>
				<button type="button" class="eza-link-btn" id="eza-preview-toggle" aria-pressed="true"><?php esc_html_e( 'Panel', 'ez-accessibility' ); ?></button>
			</div>
			<div class="eza-browser" id="eza-browser" data-pos="<?php echo esc_attr( $s['btn_position'] ); ?>" data-size="<?php echo esc_attr( $s['btn_size'] ); ?>" data-open="1" style="--eza-offset:<?php echo esc_attr( $s['btn_offset'] ); ?>">
				<div class="eza-browser-bar" aria-hidden="true"><?php echo esc_html( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></div>
				<div class="eza-browser-page" aria-hidden="true">
					<div class="eza-sk eza-sk--hero"></div>
					<div class="eza-sk eza-sk--line"></div>
					<div class="eza-sk eza-sk--line eza-sk--short"></div>
					<div class="eza-sk eza-sk--line"></div>
					<div class="eza-sk-cols"><div class="eza-sk eza-sk--card"></div><div class="eza-sk eza-sk--card"></div></div>
				</div>
				<div class="eza-mock" aria-hidden="true">
					<div class="eza-mock-panel">
						<div class="eza-mock-head"><span id="eza-mock-title"></span><i></i></div>
						<div class="eza-mock-grid" id="eza-mock-grid">
							<?php foreach ( self::features() as $key => $meta ) : ?>
								<span class="eza-mock-tile" data-feature="<?php echo esc_attr( $key ); ?>"<?php if ( ! $s[ $key ] ) { echo ' hidden'; } ?>>
									<?php self::the_icon( $meta['icon'] ); ?>
									<span data-string="<?php echo esc_attr( substr( $key, 5 ) ); ?>"></span>
								</span>
							<?php endforeach; ?>
						</div>
					</div>
					<span class="eza-mock-btn" id="eza-mock-btn"><?php
					$custom_url = self::custom_icon_url( (int) $s['btn_custom_icon'] );
					if ( 'custom' === $s['btn_icon'] && $custom_url ) {
						echo '<img src="' . esc_url( $custom_url ) . '" alt="">';
					} else {
						self::the_icon( self::button_icons()[ $s['btn_icon'] ] ?? 'logo' );
					}
				?></span>
				</div>
			</div>
			<p class="eza-hint eza-preview-note"><?php esc_html_e( 'Preview updates as you edit. Changes go live when you save.', 'ez-accessibility' ); ?></p>
		</aside>
		<?php
	}
}
