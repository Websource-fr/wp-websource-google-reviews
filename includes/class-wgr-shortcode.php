<?php
/**
 * Shortcode [websource_google_reviews] : liste publique des avis.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WGR_Shortcode {

	public static function init(): void {
		add_shortcode( 'websource_google_reviews', array( __CLASS__, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function enqueue_assets(): void {
		wp_register_style( 'wgr-public', WGR_PLUGIN_URL . 'assets/css/wgr-public.css', array(), WGR_VERSION );
	}

	/**
	 * @param array $atts Attributs du shortcode : limit (nombre d'avis affichés, 0 = tous).
	 */
	public static function render( $atts ): string {
		wp_enqueue_style( 'wgr-public' );

		$atts = shortcode_atts(
			array(
				'limit' => 0,
			),
			$atts,
			'websource_google_reviews'
		);

		$reviews = WGR_DB::get_published_reviews();
		$stats   = WGR_DB::get_stats();

		if ( (int) $atts['limit'] > 0 ) {
			$reviews = array_slice( $reviews, 0, (int) $atts['limit'] );
		}

		if ( empty( $reviews ) ) {
			return '<p class="wgr-empty">' . esc_html__( 'Aucun avis pour le moment.', 'websource-google-reviews' ) . '</p>';
		}

		ob_start();
		?>
		<div class="wgr-reviews">
			<div class="wgr-summary">
				<span class="wgr-summary-average"><?php echo esc_html( number_format_i18n( $stats['average'], 1 ) ); ?></span>
				<span class="wgr-summary-stars"><?php echo wp_kses_post( self::stars_markup( (float) $stats['average'] ) ); ?></span>
				<span class="wgr-summary-count">
					<?php
					printf(
						/* translators: %d: number of reviews */
						esc_html( _n( 'Basé sur %d avis', 'Basé sur %d avis', $stats['count'], 'websource-google-reviews' ) ),
						(int) $stats['count']
					);
					?>
				</span>
			</div>

			<ul class="wgr-list">
				<?php foreach ( $reviews as $review ) : ?>
					<li class="wgr-item">
						<div class="wgr-item-head">
							<span class="wgr-item-author"><?php echo esc_html( $review['author_name'] ); ?></span>
							<span class="wgr-item-stars"><?php echo wp_kses_post( self::stars_markup( (float) $review['rating'] ) ); ?></span>
						</div>
						<p class="wgr-item-text"><?php echo esc_html( $review['review_text'] ); ?></p>
						<?php if ( ! empty( $review['relative_date'] ) ) : ?>
							<span class="wgr-item-date"><?php echo esc_html( $review['relative_date'] ); ?></span>
						<?php elseif ( ! empty( $review['review_date'] ) ) : ?>
							<span class="wgr-item-date"><?php echo esc_html( mysql2date( get_option( 'date_format' ), $review['review_date'] ) ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Génère un rendu étoile (texte échappable, pas d'icône externe requise).
	 */
	public static function stars_markup( float $rating ): string {
		$rating = max( 0, min( 5, $rating ) );
		$full   = (int) floor( $rating );
		$empty  = 5 - $full;

		$out = '<span class="wgr-stars" aria-label="' . esc_attr(
			sprintf(
				/* translators: %s: rating out of 5 */
				__( '%s sur 5 étoiles', 'websource-google-reviews' ),
				number_format_i18n( $rating, 1 )
			)
		) . '">';
		$out .= str_repeat( '★', $full );
		$out .= str_repeat( '☆', $empty );
		$out .= '</span>';

		return $out;
	}
}
