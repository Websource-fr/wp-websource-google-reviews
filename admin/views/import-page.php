<?php
/**
 * Vue : import par collage du texte brut Google.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap wgr-admin">
	<h1><?php esc_html_e( 'Importer des avis Google', 'websource-google-reviews' ); ?></h1>

	<?php if ( isset( $_GET['wgr_count'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
			<?php
			printf(
				/* translators: %d: number of imported reviews */
				esc_html( _n( '%d avis importé. Vérifiez et corrigez-le si besoin dans la liste des avis.', '%d avis importés. Vérifiez et corrigez-les si besoin dans la liste des avis.', (int) $_GET['wgr_count'], 'websource-google-reviews' ) ),
				(int) $_GET['wgr_count']
			);
			?>
			</p>
		</div>
	<?php endif; ?>

	<p>
		<?php esc_html_e( 'Ouvrez votre fiche Google Maps ou le panneau d’avis Google (Google Business Profile), sélectionnez et copiez le bloc de texte contenant vos avis, puis collez-le ci-dessous. Le texte sera analysé automatiquement pour en extraire l’auteur, la note, le texte et la date relative de chaque avis.', 'websource-google-reviews' ); ?>
	</p>
	<p class="description">
		<?php esc_html_e( 'Le format du copier-coller Google n’étant pas garanti, relisez toujours le résultat dans la liste des avis et corrigez au besoin manuellement.', 'websource-google-reviews' ); ?>
	</p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'wgr_parse_reviews' ); ?>
		<input type="hidden" name="action" value="wgr_parse_reviews" />
		<textarea name="wgr_raw_text" rows="16" class="large-text code" placeholder="<?php esc_attr_e( 'Collez ici le texte copié depuis Google…', 'websource-google-reviews' ); ?>" required></textarea>
		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Analyser et importer', 'websource-google-reviews' ); ?></button>
		</p>
	</form>
</div>
