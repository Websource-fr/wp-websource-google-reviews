<?php
/**
 * Vue : page de réglages.
 *
 * @var array $settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap wgr-admin">
	<h1><?php esc_html_e( 'Websource Google Reviews — Réglages', 'websource-google-reviews' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'wgr_settings_group' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="wgr_page_title"><?php esc_html_e( 'Titre de la page', 'websource-google-reviews' ); ?></label></th>
				<td>
					<input type="text" id="wgr_page_title" name="wgr_settings[page_title]" class="regular-text"
						value="<?php echo esc_attr( $settings['page_title'] ?? __( 'Avis clients', 'websource-google-reviews' ) ); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="wgr_page_slug"><?php esc_html_e( 'Slug de la page (URL)', 'websource-google-reviews' ); ?></label></th>
				<td>
					<code><?php echo esc_html( home_url( '/' ) ); ?></code>
					<input type="text" id="wgr_page_slug" name="wgr_settings[page_slug]" class="regular-text"
						value="<?php echo esc_attr( $settings['page_slug'] ?? 'avis-clients' ); ?>" />
					<code>/</code>
					<p class="description"><?php esc_html_e( 'Par défaut : avis-clients. Les permaliens seront régénérés automatiquement.', 'websource-google-reviews' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="wgr_business_name"><?php esc_html_e( 'Nom de l’établissement', 'websource-google-reviews' ); ?></label></th>
				<td>
					<input type="text" id="wgr_business_name" name="wgr_settings[business_name]" class="regular-text"
						value="<?php echo esc_attr( $settings['business_name'] ?? get_bloginfo( 'name' ) ); ?>" />
					<p class="description"><?php esc_html_e( 'Utilisé dans le schema JSON-LD LocalBusiness de la page avis.', 'websource-google-reviews' ); ?></p>
				</td>
			</tr>
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Repli WooCommerce', 'websource-google-reviews' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wgr_settings[show_wc_fallback]" value="1"
							<?php checked( ! empty( $settings['show_wc_fallback'] ) ); ?> />
						<?php esc_html_e( 'Afficher un bloc "avis du magasin" sur les fiches produit sans avis', 'websource-google-reviews' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="wgr_wc_fallback_title"><?php esc_html_e( 'Titre du bloc "avis du magasin"', 'websource-google-reviews' ); ?></label></th>
				<td>
					<input type="text" id="wgr_wc_fallback_title" name="wgr_settings[wc_fallback_title]" class="regular-text"
						value="<?php echo esc_attr( $settings['wc_fallback_title'] ?? __( 'Avis du magasin', 'websource-google-reviews' ) ); ?>" />
				</td>
			</tr>
			<?php else : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'WooCommerce', 'websource-google-reviews' ); ?></th>
				<td><p class="description"><?php esc_html_e( 'WooCommerce n’est pas actif : le bloc "avis du magasin" sur les fiches produit est indisponible. Le reste du plugin fonctionne normalement.', 'websource-google-reviews' ); ?></p></td>
			</tr>
			<?php endif; ?>
		</table>

		<?php submit_button(); ?>
	</form>

	<h2><?php esc_html_e( 'Intégration schema.org', 'websource-google-reviews' ); ?></h2>
	<p><?php esc_html_e( 'Pour injecter la note globale (aggregateRating) dans le JSON-LD Organization/LocalBusiness que votre thème ou votre plugin SEO génère déjà, utilisez la fonction suivante :', 'websource-google-reviews' ); ?></p>
	<pre class="wgr-code-sample">$rating = wgr_get_aggregate_rating();
if ( $rating ) {
    $organization_schema['aggregateRating'] = $rating;
}</pre>
	<p class="description"><?php esc_html_e( 'Retourne null tant qu’aucun avis n’est enregistré (jamais de note inventée).', 'websource-google-reviews' ); ?></p>
</div>
