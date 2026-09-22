<?php
/**
 * Interface d'administration : réglages, import par collage, CRUD manuel des avis.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WGR_Admin {

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_wgr_parse_reviews', array( __CLASS__, 'handle_parse_reviews' ) );
		add_action( 'admin_post_wgr_save_review', array( __CLASS__, 'handle_save_review' ) );
		add_action( 'admin_post_wgr_delete_review', array( __CLASS__, 'handle_delete_review' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, 'websource-google-reviews' ) ) {
			return;
		}
		wp_enqueue_style( 'wgr-admin', WGR_PLUGIN_URL . 'assets/css/wgr-admin.css', array(), WGR_VERSION );
	}

	public static function register_menu(): void {
		add_menu_page(
			__( 'Avis Google', 'websource-google-reviews' ),
			__( 'Avis Google', 'websource-google-reviews' ),
			'manage_options',
			'websource-google-reviews',
			array( __CLASS__, 'render_reviews_page' ),
			'dashicons-star-filled',
			58
		);

		add_submenu_page(
			'websource-google-reviews',
			__( 'Avis', 'websource-google-reviews' ),
			__( 'Avis', 'websource-google-reviews' ),
			'manage_options',
			'websource-google-reviews',
			array( __CLASS__, 'render_reviews_page' )
		);

		add_submenu_page(
			'websource-google-reviews',
			__( 'Importer (coller depuis Google)', 'websource-google-reviews' ),
			__( 'Importer', 'websource-google-reviews' ),
			'manage_options',
			'websource-google-reviews-import',
			array( __CLASS__, 'render_import_page' )
		);

		add_submenu_page(
			'websource-google-reviews',
			__( 'Réglages', 'websource-google-reviews' ),
			__( 'Réglages', 'websource-google-reviews' ),
			'manage_options',
			'websource-google-reviews-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function register_settings(): void {
		register_setting( 'wgr_settings_group', 'wgr_settings', array( __CLASS__, 'sanitize_settings' ) );
	}

	public static function sanitize_settings( array $input ): array {
		$existing = get_option( 'wgr_settings', array() );

		$output                       = $existing;
		$output['page_slug']          = sanitize_title( $input['page_slug'] ?? 'avis-clients' );
		$output['page_title']         = sanitize_text_field( $input['page_title'] ?? __( 'Avis clients', 'websource-google-reviews' ) );
		$output['business_name']      = sanitize_text_field( $input['business_name'] ?? get_bloginfo( 'name' ) );
		$output['show_wc_fallback']   = ! empty( $input['show_wc_fallback'] ) ? 1 : 0;
		$output['wc_fallback_title']  = sanitize_text_field( $input['wc_fallback_title'] ?? __( 'Avis du magasin', 'websource-google-reviews' ) );

		// Le slug a changé : les règles de réécriture doivent être régénérées.
		if ( ( $existing['page_slug'] ?? '' ) !== $output['page_slug'] ) {
			add_action( 'shutdown', 'flush_rewrite_rules' );
		}

		return $output;
	}

	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = get_option( 'wgr_settings', array() );
		include WGR_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	public static function render_import_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		include WGR_PLUGIN_DIR . 'admin/views/import-page.php';
	}

	public static function render_reviews_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$editing_review = null;
		if ( isset( $_GET['edit'] ) ) {
			$editing_review = WGR_DB::get_review( (int) $_GET['edit'] );
		}

		$reviews = WGR_DB::get_all_reviews();
		include WGR_PLUGIN_DIR . 'admin/views/reviews-page.php';
	}

	/**
	 * Traite le collage de texte brut Google et pré-remplit via le parseur.
	 */
	public static function handle_parse_reviews(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Action non autorisée.', 'websource-google-reviews' ) );
		}
		check_admin_referer( 'wgr_parse_reviews' );

		$raw_text = isset( $_POST['wgr_raw_text'] ) ? wp_unslash( $_POST['wgr_raw_text'] ) : '';
		$parsed   = WGR_Parser::parse( $raw_text );
		$inserted = WGR_DB::bulk_insert( $parsed );

		$redirect = add_query_arg(
			array(
				'page'      => 'websource-google-reviews-import',
				'wgr_count' => $inserted,
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Enregistre un avis créé/édité manuellement.
	 */
	public static function handle_save_review(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Action non autorisée.', 'websource-google-reviews' ) );
		}
		check_admin_referer( 'wgr_save_review' );

		$id   = isset( $_POST['review_id'] ) ? (int) $_POST['review_id'] : 0;
		$data = array(
			'author_name'   => sanitize_text_field( wp_unslash( $_POST['author_name'] ?? '' ) ),
			'rating'        => (int) ( $_POST['rating'] ?? 5 ),
			'review_text'   => sanitize_textarea_field( wp_unslash( $_POST['review_text'] ?? '' ) ),
			'review_date'   => sanitize_text_field( wp_unslash( $_POST['review_date'] ?? '' ) ),
			'relative_date' => sanitize_text_field( wp_unslash( $_POST['relative_date'] ?? '' ) ),
			'status'        => 'published',
			'source'        => 'manual',
		);

		if ( $id > 0 ) {
			WGR_DB::update_review( $id, $data );
		} else {
			WGR_DB::insert_review( $data );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=websource-google-reviews&wgr_saved=1' ) );
		exit;
	}

	public static function handle_delete_review(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Action non autorisée.', 'websource-google-reviews' ) );
		}
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		check_admin_referer( 'wgr_delete_review_' . $id );

		if ( $id > 0 ) {
			WGR_DB::delete_review( $id );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=websource-google-reviews&wgr_deleted=1' ) );
		exit;
	}
}
