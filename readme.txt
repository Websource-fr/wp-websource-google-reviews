=== Websource Google Reviews ===
Contributors: websource
Tags: google reviews, avis clients, schema, aggregaterating, woocommerce
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Publie une page publique d'avis clients Google réels avec JSON-LD AggregateRating, sans jamais inventer de note.

== Description ==

Websource Google Reviews vous permet de publier vos **vrais avis Google** (Maps ou Google Business Profile) sur votre site WordPress, sans passer par une API payante.

**Fonctionnalités :**

* Collez le texte brut copié depuis votre panneau d'avis Google : le plugin l'analyse automatiquement pour extraire la note, l'auteur, le texte et la date relative ("il y a 3 mois") de chaque avis.
* Corrigez ou ajoutez des avis un par un via une interface d'administration classique (ajout/édition/suppression).
* Une page publique dédiée est créée automatiquement à l'URL `/avis-clients/` (slug personnalisable), listant tous les avis publiés.
* Un shortcode `[websource_google_reviews]` permet d'afficher la liste des avis n'importe où (limite configurable via `limit`).
* Un bloc JSON-LD `AggregateRating` (schema.org) **réel**, calculé en direct à partir des avis enregistrés (moyenne, nombre d'avis, bornes 1-5), est injecté dans le `<head>` de la page avis.
* Une fonction PHP `wgr_get_aggregate_rating()` est exposée pour que votre thème ou votre plugin SEO injecte cette même note dans son propre JSON-LD `Organization`/`LocalBusiness` global.
* Si WooCommerce est actif, un bloc "avis du magasin" optionnel peut s'afficher sur les fiches produit qui n'ont aucun avis produit — clairement labellisé comme avis du magasin, jamais comme avis du produit, et sans générer de schema `Review`/`AggregateRating` au niveau du produit.
* Les avis sont stockés dans une table dédiée en base de données (créée à l'activation), pas dans les articles/pages.

Le plugin fonctionne intégralement sur un site WordPress sans WooCommerce ; seule la fonctionnalité de repli sur les fiches produit nécessite WooCommerce.

== Installation ==

1. Copiez le dossier `websource-google-reviews` dans `wp-content/plugins/`.
2. Activez le plugin depuis le menu Extensions. La table `wp_wgr_reviews` est créée automatiquement.
3. Allez dans **Avis Google > Réglages** pour définir le titre et le slug de la page publique.
4. Allez dans **Avis Google > Importer** pour coller le texte de vos avis Google, ou dans **Avis Google > Avis** pour les saisir manuellement.
5. Consultez `/avis-clients/` (ou le slug choisi) pour voir la page publique.

== Frequently Asked Questions ==

= Le plugin invente-t-il des avis ou une note si je n'en ai aucun ? =

Non. Tant qu'aucun avis n'est enregistré, aucun bloc `AggregateRating` n'est généré et `wgr_get_aggregate_rating()` retourne `null`.

= Comment intégrer la note globale dans le JSON-LD Organization de mon thème ? =

Dans votre `functions.php` ou votre plugin SEO :

    $rating = wgr_get_aggregate_rating();
    if ( $rating ) {
        $organization_schema['aggregateRating'] = $rating;
    }

= Le parseur de collage Google se trompe sur certains avis, que faire ? =

Le format du copier-coller Google n'est pas un standard garanti. Après un import, relisez toujours la liste des avis dans **Avis Google > Avis** et corrigez/supprimez ce qui est mal reconnu — c'est justement le rôle de l'interface manuelle.

= Le bloc "avis du magasin" apparaît-il comme un avis du produit dans les moteurs de recherche ? =

Non, il est volontairement affiché sans balisage `Review`/`AggregateRating` au niveau du produit, pour ne jamais laisser croire qu'il s'agit d'avis sur ce produit précis.

== Changelog ==

= 1.0.0 =
* Version initiale.
