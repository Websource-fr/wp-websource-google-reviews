<?php
/**
 * Intégration WooCommerce optionnelle : affiche un bloc "avis du magasin"
 * sur une fiche produit qui n'a aucun avis produit, clairement labellisé,
 * sans jamais émettre de Review/AggregateRating au niveau du produit.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WGR_WooCommerce {

	public static function init(): void {
		add_action( 'woocommerce_after_single_product_summary', array( __CLASS__, 'maybe_render_store_reviews' ), 25 );
	}

	public static function maybe_render_store_reviews(): void {
		$settings = get_option( 'wgr_settings', array() );
		if ( empty( $settings['show_wc_fallback'] ) ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		// On n'affiche le repli QUE si le produit n'a aucun avis produit.
		if ( (int) $product->get_review_count() > 0 ) {
			return;
		}

		$reviews = array_slice( WGR_DB::get_published_reviews(), 0, 5 );
		if ( empty( $reviews ) ) {
			return;
		}

		$stats = WGR_DB::get_stats();
		$title = $settings['wc_fallback_title'] ?? __( 'Avis du magasin', 'websource-google-reviews' );

		wp_enqueue_style( 'wgr-public' );
		?>
		<div class="wgr-wc-fallback" data-wgr-store-reviews="1">
			<h2 class="wgr-wc-fallback-title"><?php echo esc_html( $title ); ?></h2>
			<p class="wgr-wc-fallback-disclaimer">
				<?php esc_html_e( "Ces avis concernent l'expérience générale avec le magasin, pas ce produit en particulier.", 'websource-google-reviews' ); ?>
			</p>
			<div class="wgr-summary">
				<span class="wgr-summary-average"><?php echo esc_html( number_format_i18n( $stats['average'], 1 ) ); ?></span>
				<span class="wgr-summary-stars"><?php echo wp_kses_post( WGR_Shortcode::stars_markup( (float) $stats['average'] ) ); ?></span>
			</div>
			<ul class="wgr-list wgr-list-compact">
				<?php foreach ( $reviews as $review ) : ?>
					<li class="wgr-item">
						<div class="wgr-item-head">
							<span class="wgr-item-author"><?php echo esc_html( $review['author_name'] ); ?></span>
							<span class="wgr-item-stars"><?php echo wp_kses_post( WGR_Shortcode::stars_markup( (float) $review['rating'] ) ); ?></span>
						</div>
						<p class="wgr-item-text"><?php echo esc_html( wp_trim_words( $review['review_text'], 30 ) ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="wgr-wc-fallback-link">
				<a href="<?php echo esc_url( WGR_Rewrite::get_page_url() ); ?>"><?php esc_html_e( 'Voir tous les avis du magasin', 'websource-google-reviews' ); ?></a>
			</p>
		</div>
		<?php
	}
}
