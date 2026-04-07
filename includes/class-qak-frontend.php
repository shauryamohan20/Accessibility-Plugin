<?php
/**
 * QAK_Frontend
 * Renders the floating accessibility button and panel on the frontend.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QAK_Frontend {

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'wp_footer',          [ __CLASS__, 'render_widget' ], 100 );
	}

	// ── Check if widget should be shown on current page ───────────────────────
	private static function is_excluded(): bool {
		$s    = QAK_Settings::get();
		$mode = $s['visibility_mode'] ?? 'all';

		if ( 'all' === $mode ) {
			return false; // show everywhere
		}

		// mode === 'selected': show only on explicitly chosen pages/posts
		$pages_raw = $s['visibility_pages'] ?? '';
		if ( '' === $pages_raw ) {
			return true; // nothing selected yet → hide everywhere
		}

		$page_ids = array_filter( array_map( 'absint', explode( ',', $pages_raw ) ) );
		return ! in_array( get_queried_object_id(), $page_ids, true );
	}

	// ── Enqueue frontend assets ───────────────────────────────────────────────
	public static function enqueue_assets(): void {
		if ( self::is_excluded() ) {
			return;
		}

		$s = QAK_Settings::get();

		wp_enqueue_style(
			'qak-frontend-css',
			QAK_URL . 'assets/css/qak-frontend.css',
			[],
			QAK_VERSION
		);

		wp_enqueue_script(
			'qak-frontend-js',
			QAK_URL . 'assets/js/qak-frontend.js',
			[],
			QAK_VERSION,
			true  // footer
		);

		// Pass settings to JS via localization (safe, escaped by wp_json_encode internally)
		wp_localize_script( 'qak-frontend-js', 'QAKConfig', [
			'position'   => $s['btn_position'],
			'btnBg'      => $s['btn_bg_color'],
			'btnIcon'    => $s['btn_icon_color'],
			'btnSize'    => $s['btn_size'],
			'zIndex'     => absint( $s['z_index'] ),
			'features'   => [
				'fontSize'      => (bool) $s['feat_font_size'],
				'highContrast'  => (bool) $s['feat_high_contrast'],
				'invert'        => (bool) $s['feat_invert'],
				'grayscale'     => (bool) $s['feat_grayscale'],
				'highlightLinks'=> (bool) $s['feat_highlight_links'],
				'readableFont'  => (bool) $s['feat_readable_font'],
				'letterSpacing' => (bool) $s['feat_letter_spacing'],
				'pauseAnim'     => (bool) $s['feat_pause_anim'],
				'largeCursor'   => (bool) $s['feat_large_cursor'],
			],
			'strings' => [
				'en' => [
					'panelTitle'    => $s['lang_en_panel_title'],
					'fontSize'      => $s['lang_en_font_size'],
					'highContrast'  => $s['lang_en_high_contrast'],
					'invert'        => $s['lang_en_invert'],
					'grayscale'     => $s['lang_en_grayscale'],
					'highlightLinks'=> $s['lang_en_highlight_links'],
					'readableFont'  => $s['lang_en_readable_font'],
					'letterSpacing' => $s['lang_en_letter_spacing'],
					'pauseAnim'     => $s['lang_en_pause_anim'],
					'largeCursor'   => $s['lang_en_large_cursor'],
					'reset'         => $s['lang_en_reset'],
				],
				'ar' => [
					'panelTitle'    => $s['lang_ar_panel_title'],
					'fontSize'      => $s['lang_ar_font_size'],
					'highContrast'  => $s['lang_ar_high_contrast'],
					'invert'        => $s['lang_ar_invert'],
					'grayscale'     => $s['lang_ar_grayscale'],
					'highlightLinks'=> $s['lang_ar_highlight_links'],
					'readableFont'  => $s['lang_ar_readable_font'],
					'letterSpacing' => $s['lang_ar_letter_spacing'],
					'pauseAnim'     => $s['lang_ar_pause_anim'],
					'largeCursor'   => $s['lang_ar_large_cursor'],
					'reset'         => $s['lang_ar_reset'],
				],
				'he' => [
					'panelTitle'    => $s['lang_he_panel_title'],
					'fontSize'      => $s['lang_he_font_size'],
					'highContrast'  => $s['lang_he_high_contrast'],
					'invert'        => $s['lang_he_invert'],
					'grayscale'     => $s['lang_he_grayscale'],
					'highlightLinks'=> $s['lang_he_highlight_links'],
					'readableFont'  => $s['lang_he_readable_font'],
					'letterSpacing' => $s['lang_he_letter_spacing'],
					'pauseAnim'     => $s['lang_he_pause_anim'],
					'largeCursor'   => $s['lang_he_large_cursor'],
					'reset'         => $s['lang_he_reset'],
				],
			],
		] );
	}

	// ── Render the widget HTML in footer ──────────────────────────────────────
	public static function render_widget(): void {
		if ( self::is_excluded() ) {
			return;
		}
		// Minimal HTML shell — JS builds the panel dynamically.
		// No user data is output here; all config comes from wp_localize_script.
		?>
		<div id="qak-widget-root" aria-label="<?php esc_attr_e( 'Accessibility options', 'qudra-accesskit' ); ?>" role="region">
			<button
				id="qak-trigger-btn"
				type="button"
				aria-haspopup="true"
				aria-expanded="false"
				aria-label="<?php esc_attr_e( 'Open accessibility menu', 'qudra-accesskit' ); ?>"
			>
				<svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
					<circle cx="12" cy="12" r="10"/>
					<circle cx="12" cy="8" r="1.5" class="qak-svg-fill"/>
					<path d="M9 11h3v7"/><path d="M9 18h6"/>
				</svg>
			</button>
			<div id="qak-panel" role="dialog" aria-modal="false" aria-label="<?php esc_attr_e( 'Accessibility menu', 'qudra-accesskit' ); ?>" hidden>
				<!-- Populated by qak-frontend.js -->
			</div>
		</div>
		<?php
	}
}
