<?php
/**
 * QAK_Settings
 * Handles the WordPress admin settings page for Qudra AccessKit WP.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QAK_Settings {

	// ── Init ──────────────────────────────────────────────────────────────────
	public static function init(): void {
		add_action( 'admin_menu',            [ __CLASS__, 'add_menu' ] );
		add_action( 'admin_init',            [ __CLASS__, 'handle_save' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	// ── Default settings ──────────────────────────────────────────────────────
	public static function defaults(): array {
		return [
			// Button appearance
			'btn_bg_color'    => '#1E6264',
			'btn_icon_color'  => '#ffffff',
			'btn_size'        => 'medium',   // small | medium | large

			// Position
			'btn_position'    => 'bottom-right', // top-left | top-right | bottom-left | bottom-right

			// Z-index
			'z_index'         => 99999,

			// Visibility
			'visibility_mode'  => 'all',  // all | selected
			'visibility_pages' => '',     // comma-separated post/page IDs (used when mode=selected)

			// Features enabled
			'feat_font_size'       => true,
			'feat_high_contrast'   => true,
			'feat_invert'          => true,
			'feat_grayscale'       => true,
			'feat_highlight_links' => true,
			'feat_readable_font'   => true,
			'feat_letter_spacing'  => true,
			'feat_pause_anim'      => true,
			'feat_large_cursor'    => true,

			// Language strings — English
			'lang_en_panel_title'       => 'Accessibility',
			'lang_en_font_size'         => 'Font Size',
			'lang_en_high_contrast'     => 'High Contrast',
			'lang_en_invert'            => 'Invert Colors',
			'lang_en_grayscale'         => 'Grayscale',
			'lang_en_highlight_links'   => 'Highlight Links',
			'lang_en_readable_font'     => 'Readable Font',
			'lang_en_letter_spacing'    => 'Letter Spacing',
			'lang_en_pause_anim'        => 'Pause Animations',
			'lang_en_large_cursor'      => 'Large Cursor',
			'lang_en_reset'             => 'Reset All',

			// Language strings — Arabic
			'lang_ar_panel_title'       => 'إمكانية الوصول',
			'lang_ar_font_size'         => 'حجم الخط',
			'lang_ar_high_contrast'     => 'تباين عالٍ',
			'lang_ar_invert'            => 'عكس الألوان',
			'lang_ar_grayscale'         => 'تدرج الرمادي',
			'lang_ar_highlight_links'   => 'إبراز الروابط',
			'lang_ar_readable_font'     => 'خط مقروء',
			'lang_ar_letter_spacing'    => 'تباعد الأحرف',
			'lang_ar_pause_anim'        => 'إيقاف الحركة',
			'lang_ar_large_cursor'      => 'مؤشر كبير',
			'lang_ar_reset'             => 'إعادة تعيين',

			// Language strings — Hebrew
			'lang_he_panel_title'       => 'נגישות',
			'lang_he_font_size'         => 'גודל גופן',
			'lang_he_high_contrast'     => 'ניגודיות גבוהה',
			'lang_he_invert'            => 'היפוך צבעים',
			'lang_he_grayscale'         => 'גווני אפור',
			'lang_he_highlight_links'   => 'הדגשת קישורים',
			'lang_he_readable_font'     => 'גופן קריא',
			'lang_he_letter_spacing'    => 'ריווח אותיות',
			'lang_he_pause_anim'        => 'השהיית אנימציות',
			'lang_he_large_cursor'      => 'סמן גדול',
			'lang_he_reset'             => 'איפוס הכל',
		];
	}

	// ── Get saved option (merged with defaults) ───────────────────────────────
	public static function get(): array {
		$saved    = get_option( QAK_OPTION, [] );
		$defaults = self::defaults();
		return wp_parse_args( $saved, $defaults );
	}

	// ── Admin menu ────────────────────────────────────────────────────────────
	public static function add_menu(): void {
		add_menu_page(
			__( 'Qudra AccessKit', 'qudra-accesskit' ),
			__( 'AccessKit', 'qudra-accesskit' ),
			'manage_options',
			'qudra-accesskit',
			[ __CLASS__, 'render_page' ],
			'dashicons-universal-access-alt',
			80
		);
	}

	// ── Enqueue admin assets ──────────────────────────────────────────────────
	public static function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_qudra-accesskit' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style(
			'qak-admin-css',
			QAK_URL . 'assets/css/qak-admin.css',
			[],
			QAK_VERSION
		);
		wp_enqueue_script(
			'qak-admin-js',
			QAK_URL . 'assets/js/qak-admin.js',
			[ 'wp-color-picker', 'jquery' ],
			QAK_VERSION,
			true
		);
	}

	// ── Handle form save ──────────────────────────────────────────────────────
	public static function handle_save(): void {
		if ( ! isset( $_POST['qak_save_settings'] ) ) {
			return;
		}
		check_admin_referer( 'qak_save_settings_action', 'qak_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'qudra-accesskit' ) );
		}

		$raw      = $_POST; // We sanitize each field individually below.
		$defaults = self::defaults();
		$clean    = [];

		// Colors
		$clean['btn_bg_color']   = sanitize_hex_color( $raw['btn_bg_color']   ?? $defaults['btn_bg_color'] );
		$clean['btn_icon_color'] = sanitize_hex_color( $raw['btn_icon_color'] ?? $defaults['btn_icon_color'] );

		// Size
		$allowed_sizes           = [ 'small', 'medium', 'large' ];
		$clean['btn_size']       = in_array( $raw['btn_size'] ?? '', $allowed_sizes, true )
		                           ? $raw['btn_size']
		                           : 'medium';

		// Position
		$allowed_pos             = [ 'top-left', 'top-right', 'bottom-left', 'bottom-right' ];
		$clean['btn_position']   = in_array( $raw['btn_position'] ?? '', $allowed_pos, true )
		                           ? $raw['btn_position']
		                           : 'bottom-right';

		// Z-index
		$clean['z_index']        = absint( $raw['z_index'] ?? 99999 );
		if ( $clean['z_index'] < 1 ) {
			$clean['z_index'] = 99999;
		}

		// Visibility mode
		$allowed_modes            = [ 'all', 'selected' ];
		$clean['visibility_mode'] = in_array( $raw['visibility_mode'] ?? '', $allowed_modes, true )
		                            ? $raw['visibility_mode']
		                            : 'all';

		// Visibility pages (checkbox array → sanitised comma-separated IDs)
		$raw_vis_pages             = isset( $raw['visibility_pages'] ) && is_array( $raw['visibility_pages'] )
		                             ? $raw['visibility_pages']
		                             : [];
		$clean_vis_ids             = array_filter( array_map( 'absint', $raw_vis_pages ) );
		$clean['visibility_pages'] = implode( ',', $clean_vis_ids );

		// Feature toggles
		$features = [
			'feat_font_size', 'feat_high_contrast', 'feat_invert', 'feat_grayscale',
			'feat_highlight_links', 'feat_readable_font', 'feat_letter_spacing',
			'feat_pause_anim', 'feat_large_cursor',
		];
		foreach ( $features as $feat ) {
			$clean[ $feat ] = isset( $raw[ $feat ] ) && '1' === $raw[ $feat ];
		}

		// Language strings
		$lang_keys = [
			'panel_title', 'font_size', 'high_contrast', 'invert', 'grayscale',
			'highlight_links', 'readable_font', 'letter_spacing', 'pause_anim',
			'large_cursor', 'reset',
		];
		foreach ( [ 'en', 'ar', 'he' ] as $lang ) {
			foreach ( $lang_keys as $key ) {
				$field_name = "lang_{$lang}_{$key}";
				$clean[ $field_name ] = sanitize_text_field(
					$raw[ $field_name ] ?? ( $defaults[ $field_name ] ?? '' )
				);
			}
		}

		update_option( QAK_OPTION, $clean );
		add_settings_error( 'qak_messages', 'qak_saved', __( 'Settings saved.', 'qudra-accesskit' ), 'updated' );
	}

	// ── Render admin page ─────────────────────────────────────────────────────
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = self::get();
		settings_errors( 'qak_messages' );
		?>
		<div class="wrap qak-admin-wrap">
			<div class="qak-admin-header">
				<div class="qak-admin-header-icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="8" r="1.5" fill="currentColor" stroke="none"/><path d="M9 11h3v7"/><path d="M9 18h6"/></svg>
				</div>
				<div>
					<h1><?php esc_html_e( 'Qudra AccessKit WP', 'qudra-accesskit' ); ?></h1>
					<p><?php esc_html_e( 'Lightweight accessibility toolkit for your visitors.', 'qudra-accesskit' ); ?></p>
				</div>
			</div>

			<form method="post" action="" class="qak-admin-form">
				<?php wp_nonce_field( 'qak_save_settings_action', 'qak_nonce' ); ?>
				<input type="hidden" name="qak_save_settings" value="1">

				<div class="qak-admin-grid">

					<!-- LEFT COLUMN -->
					<div class="qak-admin-left">

						<!-- Section: Button Appearance -->
						<div class="qak-card">
							<div class="qak-card-header">
								<span class="qak-card-icon">🎨</span>
								<h2><?php esc_html_e( 'Button Appearance', 'qudra-accesskit' ); ?></h2>
							</div>
							<div class="qak-card-body">
								<div class="qak-field-row">
									<label><?php esc_html_e( 'Background Color', 'qudra-accesskit' ); ?></label>
									<input type="text" name="btn_bg_color" value="<?php echo esc_attr( $s['btn_bg_color'] ); ?>" class="qak-color-picker" data-target="bg">
								</div>
								<div class="qak-field-row">
									<label><?php esc_html_e( 'Icon Color', 'qudra-accesskit' ); ?></label>
									<input type="text" name="btn_icon_color" value="<?php echo esc_attr( $s['btn_icon_color'] ); ?>" class="qak-color-picker" data-target="icon">
								</div>
								<div class="qak-field-row">
									<label><?php esc_html_e( 'Button Size', 'qudra-accesskit' ); ?></label>
									<div class="qak-pill-group">
										<?php foreach ( [ 'small' => 'Small (48px)', 'medium' => 'Medium (56px)', 'large' => 'Large (64px)' ] as $val => $label ) : ?>
											<label class="qak-pill <?php echo $s['btn_size'] === $val ? 'qak-pill--active' : ''; ?>">
												<input type="radio" name="btn_size" value="<?php echo esc_attr( $val ); ?>" <?php checked( $s['btn_size'], $val ); ?> hidden>
												<?php echo esc_html( $label ); ?>
											</label>
										<?php endforeach; ?>
									</div>
								</div>
								<!-- Live preview -->
								<div class="qak-preview-wrap">
									<p class="qak-preview-label"><?php esc_html_e( 'Live Preview', 'qudra-accesskit' ); ?></p>
									<div class="qak-preview-stage">
										<div id="qak-preview-btn" style="background:<?php echo esc_attr( $s['btn_bg_color'] ); ?>;">
											<svg viewBox="0 0 24 24" fill="none" stroke="<?php echo esc_attr( $s['btn_icon_color'] ); ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
												<circle cx="12" cy="12" r="10"/>
												<circle cx="12" cy="8" r="1.5" fill="<?php echo esc_attr( $s['btn_icon_color'] ); ?>" stroke="none"/>
												<path d="M9 11h3v7"/><path d="M9 18h6"/>
											</svg>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- Section: Position -->
						<div class="qak-card">
							<div class="qak-card-header">
								<span class="qak-card-icon">📍</span>
								<h2><?php esc_html_e( 'Button Position', 'qudra-accesskit' ); ?></h2>
							</div>
							<div class="qak-card-body">
								<p class="qak-hint"><?php esc_html_e( 'Click a corner to set the button position. For RTL languages (Arabic/Hebrew), the button automatically mirrors to the opposite horizontal side.', 'qudra-accesskit' ); ?></p>
								<div class="qak-corner-selector">
									<div class="qak-corner-page">
										<?php foreach ( [ 'top-left', 'top-right', 'bottom-left', 'bottom-right' ] as $pos ) : ?>
											<label class="qak-corner <?php echo esc_attr( $pos ); ?> <?php echo $s['btn_position'] === $pos ? 'qak-corner--active' : ''; ?>" data-pos="<?php echo esc_attr( $pos ); ?>">
												<input type="radio" name="btn_position" value="<?php echo esc_attr( $pos ); ?>" <?php checked( $s['btn_position'], $pos ); ?> hidden>
												<span class="qak-corner-dot"></span>
											</label>
										<?php endforeach; ?>
										<div class="qak-corner-page-lines">
											<div></div><div></div><div></div>
										</div>
									</div>
									<p class="qak-corner-label" id="qak-pos-label"><?php echo esc_html( ucwords( str_replace( '-', ' ', $s['btn_position'] ) ) ); ?></p>
								</div>
							</div>
						</div>

						<!-- Section: Visibility -->
						<div class="qak-card">
							<div class="qak-card-header">
								<span class="qak-card-icon">👁️</span>
								<h2><?php esc_html_e( 'Visibility', 'qudra-accesskit' ); ?></h2>
							</div>
							<div class="qak-card-body">
								<div class="qak-field-row">
									<label><?php esc_html_e( 'Show Widget On', 'qudra-accesskit' ); ?></label>
									<div class="qak-vis-options">
										<label class="qak-vis-opt">
											<input type="radio" name="visibility_mode" value="all" <?php checked( $s['visibility_mode'], 'all' ); ?>>
											<span><?php esc_html_e( 'Entire Site', 'qudra-accesskit' ); ?></span>
										</label>
										<label class="qak-vis-opt">
											<input type="radio" name="visibility_mode" value="selected" <?php checked( $s['visibility_mode'], 'selected' ); ?>>
											<span><?php esc_html_e( 'Selected Pages Only', 'qudra-accesskit' ); ?></span>
										</label>
									</div>
								</div>

								<div class="qak-page-picker" id="qak-page-picker"<?php echo 'selected' !== $s['visibility_mode'] ? ' hidden' : ''; ?>>
									<div class="qak-field-row" style="margin-bottom:8px;">
										<label><?php esc_html_e( 'Choose Pages', 'qudra-accesskit' ); ?></label>
									</div>
									<input type="text" id="qak-page-search" class="qak-input qak-page-search-input" placeholder="<?php esc_attr_e( 'Search pages&hellip;', 'qudra-accesskit' ); ?>">
									<div class="qak-page-picker-actions">
										<button type="button" class="qak-page-action" id="qak-select-all"><?php esc_html_e( 'Select all', 'qudra-accesskit' ); ?></button>
										<button type="button" class="qak-page-action" id="qak-select-none"><?php esc_html_e( 'Clear all', 'qudra-accesskit' ); ?></button>
									</div>
									<div class="qak-page-list" id="qak-page-list">
										<?php
										$all_posts = get_posts( [
											'post_type'      => [ 'page', 'post' ],
											'posts_per_page' => -1,
											'post_status'    => 'publish',
											'orderby'        => 'title',
											'order'          => 'ASC',
										] );
										$selected_ids = array_filter(
											array_map( 'absint', explode( ',', $s['visibility_pages'] ) )
										);
										foreach ( $all_posts as $post_item ) :
											$type_obj = get_post_type_object( $post_item->post_type );
											$type_label = $type_obj ? esc_html( $type_obj->labels->singular_name ) : esc_html( $post_item->post_type );
										?>
											<label class="qak-page-item">
												<input type="checkbox"
													name="visibility_pages[]"
													value="<?php echo esc_attr( $post_item->ID ); ?>"
													<?php checked( in_array( $post_item->ID, $selected_ids, true ) ); ?>>
												<span class="qak-page-item-title"><?php echo esc_html( $post_item->post_title ); ?></span>
												<small class="qak-page-item-meta"><?php echo $type_label; ?> #<?php echo esc_html( $post_item->ID ); ?></small>
											</label>
										<?php endforeach; ?>
									</div>
									<?php if ( empty( $all_posts ) ) : ?>
										<p class="qak-hint"><?php esc_html_e( 'No published pages or posts found.', 'qudra-accesskit' ); ?></p>
									<?php endif; ?>
								</div>
							</div>
						</div>

						<!-- Section: Advanced -->
						<div class="qak-card">
							<div class="qak-card-header">
								<span class="qak-card-icon">⚙️</span>
								<h2><?php esc_html_e( 'Advanced', 'qudra-accesskit' ); ?></h2>
							</div>
							<div class="qak-card-body">
								<div class="qak-field-row">
									<label for="qak_z_index"><?php esc_html_e( 'Z-Index', 'qudra-accesskit' ); ?></label>
									<input type="number" id="qak_z_index" name="z_index" value="<?php echo esc_attr( $s['z_index'] ); ?>" min="1" max="2147483647" class="qak-input-short">
								</div>
							</div>
						</div>

					</div><!-- /.qak-admin-left -->

					<!-- RIGHT COLUMN -->
					<div class="qak-admin-right">

						<!-- Section: Features -->
						<div class="qak-card">
							<div class="qak-card-header">
								<span class="qak-card-icon">✅</span>
								<h2><?php esc_html_e( 'Accessibility Features', 'qudra-accesskit' ); ?></h2>
							</div>
							<div class="qak-card-body">
								<?php
								$feature_groups = [
									__( 'Visual', 'qudra-accesskit' ) => [
										'feat_font_size'     => __( 'Font Size Control', 'qudra-accesskit' ),
										'feat_high_contrast' => __( 'High Contrast Mode', 'qudra-accesskit' ),
										'feat_invert'        => __( 'Invert Colors', 'qudra-accesskit' ),
										'feat_grayscale'     => __( 'Grayscale Mode', 'qudra-accesskit' ),
									],
									__( 'Reading', 'qudra-accesskit' ) => [
										'feat_highlight_links' => __( 'Highlight Links', 'qudra-accesskit' ),
										'feat_readable_font'   => __( 'Readable Font (LTR only)', 'qudra-accesskit' ),
										'feat_letter_spacing'  => __( 'Letter Spacing', 'qudra-accesskit' ),
									],
									__( 'Motion & Sensory', 'qudra-accesskit' ) => [
										'feat_pause_anim'   => __( 'Pause Animations', 'qudra-accesskit' ),
										'feat_large_cursor' => __( 'Large Cursor', 'qudra-accesskit' ),
									],
								];
								foreach ( $feature_groups as $group_label => $features ) :
								?>
									<p class="qak-group-label"><?php echo esc_html( $group_label ); ?></p>
									<?php foreach ( $features as $key => $label ) : ?>
										<div class="qak-toggle-row">
											<span class="qak-toggle-label"><?php echo esc_html( $label ); ?></span>
											<label class="qak-toggle-switch">
												<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="0">
												<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1" <?php checked( (bool) $s[ $key ] ); ?>>
												<span class="qak-toggle-track"><span class="qak-toggle-thumb"></span></span>
											</label>
										</div>
									<?php endforeach; ?>
								<?php endforeach; ?>
							</div>
						</div>

						<!-- Section: Language Strings -->
						<div class="qak-card">
							<div class="qak-card-header">
								<span class="qak-card-icon">🌐</span>
								<h2><?php esc_html_e( 'Language Strings', 'qudra-accesskit' ); ?></h2>
							</div>
							<div class="qak-card-body qak-lang-tabs-wrap">
								<div class="qak-lang-tabs">
									<button type="button" class="qak-lang-tab qak-lang-tab--active" data-lang="en">English</button>
									<button type="button" class="qak-lang-tab" data-lang="ar">العربية</button>
									<button type="button" class="qak-lang-tab" data-lang="he">עברית</button>
								</div>
								<?php foreach ( [ 'en' => 'English', 'ar' => 'العربية', 'he' => 'עברית' ] as $lang => $lang_label ) : ?>
									<div class="qak-lang-panel <?php echo 'en' === $lang ? 'qak-lang-panel--active' : ''; ?>" data-lang="<?php echo esc_attr( $lang ); ?>">
										<?php
										$lang_fields = [
											'panel_title'    => 'Panel Title',
											'font_size'      => 'Font Size',
											'high_contrast'  => 'High Contrast',
											'invert'         => 'Invert Colors',
											'grayscale'      => 'Grayscale',
											'highlight_links'=> 'Highlight Links',
											'readable_font'  => 'Readable Font',
											'letter_spacing' => 'Letter Spacing',
											'pause_anim'     => 'Pause Animations',
											'large_cursor'   => 'Large Cursor',
											'reset'          => 'Reset Button',
										];
										foreach ( $lang_fields as $field_key => $field_label ) :
											$option_name = "lang_{$lang}_{$field_key}";
										?>
											<div class="qak-field-row qak-field-row--compact">
												<label><?php echo esc_html( $field_label ); ?></label>
												<input type="text"
													name="<?php echo esc_attr( $option_name ); ?>"
													value="<?php echo esc_attr( $s[ $option_name ] ); ?>"
													class="qak-input"
													<?php echo in_array( $lang, [ 'ar', 'he' ], true ) ? 'dir="rtl"' : ''; ?>>
											</div>
										<?php endforeach; ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>

					</div><!-- /.qak-admin-right -->

				</div><!-- /.qak-admin-grid -->

				<div class="qak-admin-footer">
					<button type="submit" class="qak-btn-save">
						<?php esc_html_e( 'Save Settings', 'qudra-accesskit' ); ?>
					</button>
				</div>

			</form>
		</div>
		<?php
	}
}
