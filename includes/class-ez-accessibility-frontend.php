<?php
/**
 * EZ_Accessibility_Frontend
 * Loads the floating accessibility button and panel on the frontend.
 * The widget itself is built by eza-frontend.js inside a Shadow DOM so
 * theme styles can never leak into it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EZ_Accessibility_Frontend {

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	// ── Check if widget should be shown on current page ───────────────────────
	private static function is_excluded(): bool {
		$s    = EZ_Accessibility_Settings::get();
		$mode = $s['visibility_mode'] ?? 'all';

		if ( 'all' === $mode ) {
			return false; // show everywhere
		}

		// mode === 'selected': show only on explicitly chosen pages/posts
		$pages_raw = $s['visibility_pages'] ?? '';
		if ( '' === $pages_raw ) {
			return true; // nothing selected yet → hide everywhere
		}

		// Only single posts/pages (and a static "Posts page") can be selected. On
		// archives the queried ID is a term or user ID, which must not match.
		if ( ! is_singular() && ! ( is_home() && ! is_front_page() ) ) {
			return true;
		}

		$page_ids = array_filter( array_map( 'absint', explode( ',', $pages_raw ) ) );
		return ! in_array( get_queried_object_id(), $page_ids, true );
	}

	// ── Enqueue frontend assets ───────────────────────────────────────────────
	public static function enqueue_assets(): void {
		if ( self::is_excluded() ) {
			return;
		}

		$s = EZ_Accessibility_Settings::get();

		// Page-level feature styles (contrast, fonts, spacing…).
		wp_enqueue_style( 'ez-accessibility-frontend', EZ_ACCESSIBILITY_URL . 'assets/css/eza-frontend.css', [], EZ_ACCESSIBILITY_VERSION );

		// Built-in translations live in a static, cacheable file; only overrides are inlined.
		wp_enqueue_script( 'ez-accessibility-i18n', EZ_ACCESSIBILITY_URL . 'assets/js/eza-i18n.js', [], EZ_ACCESSIBILITY_VERSION, true );
		wp_enqueue_script( 'ez-accessibility-frontend', EZ_ACCESSIBILITY_URL . 'assets/js/eza-frontend.js', [ 'ez-accessibility-i18n' ], EZ_ACCESSIBILITY_VERSION, true );

		$features = [];
		foreach ( array_keys( EZ_Accessibility_Settings::features() ) as $key ) {
			// feat_font_size → fontSize
			$js_key              = lcfirst( str_replace( ' ', '', ucwords( str_replace( '_', ' ', substr( $key, 5 ) ) ) ) );
			$features[ $js_key ] = (bool) $s[ $key ];
		}

		// Button icon: a built-in key, or a custom image that is still a valid attachment.
		$icon     = array_key_exists( $s['btn_icon'], EZ_Accessibility_Settings::button_icons() ) ? $s['btn_icon'] : 'person';
		$icon_url = 'custom' === $s['btn_icon'] ? EZ_Accessibility_Settings::custom_icon_url( (int) $s['btn_custom_icon'] ) : '';

		$custom = [];
		foreach ( $s['custom_langs'] as $code => $meta ) {
			$custom[ $code ] = $meta['dir'] ?? 'ltr';
		}

		// Passed to JS via localization (safe, escaped by wp_json_encode internally).
		wp_localize_script( 'ez-accessibility-frontend', 'EZAccessibilityConfig', [
			'position' => $s['btn_position'],
			'offset'   => absint( $s['btn_offset'] ),
			'btnBg'    => $s['btn_bg_color'],
			'btnIcon'  => $s['btn_icon_color'],
			'btnSize'  => $s['btn_size'],
			'icon'     => $icon,
			'iconUrl'  => $icon_url, // custom image, already validated; '' otherwise
			'zIndex'   => absint( $s['z_index'] ),
			'cssUrl'   => add_query_arg( 'ver', EZ_ACCESSIBILITY_VERSION, EZ_ACCESSIBILITY_URL . 'assets/css/eza-widget.css' ),
			'features' => $features,
			'fallback' => $s['fallback_lang'],
			'custom'   => (object) $custom,      // code => dir
			'strings'  => (object) $s['strings'], // code => { key: label } overrides
			'siteLang' => get_bloginfo( 'language' ),
		] );
	}
}
