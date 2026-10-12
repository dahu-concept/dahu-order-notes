# RESUME TECHNIQUE — Dahu Order Notes

Document interne, en français.

---

## Portage depuis la version Annad

### Version Annad d'origine

- Dépôt source Annad : `/Dev/dahu-notes/` (slug Annad : `dahu-notes`)
- Slug public : `dahu-order-notes` — **renommé** pour la découvrabilité sur
  wordpress.org ; voir `/Dev/dahu-public/CORRESPONDANCE-ANNAD.md`
- Version de référence au moment du portage : `1.1.0`
- Date du portage : 2026-10-06
- Version publique de départ : `2.0.0` (majeure, le mécanisme de détection
  change de nature)

### Retiré

| Élément retiré | Raison |
|---|---|
| Liste de 17 motifs regex en français écrits en dur | Rendait le plugin inopérant hors d'une boutique francophone. Remplacée, voir ci-dessous. |
| Lien `github.com/Epilouptique` dans la ligne méta | Dépôt privé à l'époque ; pour une extension publique le lien pointe sur `dahu-concept.fr`. |
| Licence GPL v2 or later | Versions publiques en MIT. Aucun code GPL n'a été recopié dans ce plugin : il n'utilise que des hooks WooCommerce, le passage en MIT est donc licite. |
| `Author URI: mailto:hugo@vialjaime.fr` | Aligné sur `mailto:contact@dahu-concept.fr`, adresse de contact publique. |
| Slug `dahu-notes` | Renommé `dahu-order-notes` : « notes » seul ne dit rien à un anglophone, « order notes » est le terme WooCommerce et le mot-clé réellement recherché. Text domain, constantes, classes, options et hooks renommés en conséquence. |

### Généralisé

| Élément | Avant (Annad) | Après (public) |
|---|---|---|
| Détection des notes auto | 17 regex françaises en dur, dont des motifs d'extensions spécifiques (Chronopost, Sendcloud, PDF Invoices) | Motifs reconstruits à l'exécution depuis les chaînes traduisibles de WooCommerce (`__( 'Order status changed from %1$s to %2$s.', 'woocommerce' )`), placeholders convertis en jokers |
| Extensibilité | Filtre `dahu_notes_highlight_auto_patterns` seulement | Filtre renommé `dahu_order_notes_auto_patterns` **plus** un champ de réglages « Additional automatic notes » (texte brut, une phrase par ligne) pour les non-développeurs |
| Écrans concernés | Liste en dur | Filtre `dahu_order_notes_order_screens` |
| Injection CSS | `<style>` écrit dans `admin_head` | `wp_register_style` + `wp_add_inline_style` sur `admin_enqueue_scripts` |
| Couleurs | `esc_html()` à la sortie | Re-validées par `sanitize_hex_color()` à la sortie, repli sur les valeurs par défaut |
| Classe marqueur | `auto-note` | `dahu-auto-note` (préfixée, évite toute collision) |
| Structure | Fichier unique de 8,6 Ko | `includes/` : `-settings`, `-detector`, `-highlighter` |
| Langue des chaînes | Français | Anglais (langue source), traductions par `.po` |
| Désinstallation | Aucune | `uninstall.php`, mono-site et multisite |

### Détail du mécanisme de détection

Le point dur du portage. Les motifs d'origine (`État de la commande modifié
de`, `Réduction des niveaux de stock`…) sont **exactement les traductions
françaises des chaînes de WooCommerce core**. Les régénérer via `__()` dans le
domaine `woocommerce` reproduit donc le comportement français à l'identique,
tout en le rendant correct dans toutes les locales traduites.

Conversion d'un gabarit en expression régulière :

1. Les placeholders `%s`, `%d`, `%1$s` sont remplacés par une sentinelle.
2. La chaîne est passée à `preg_quote()`.
3. La sentinelle est remplacée par `.*?`.
4. Le tout est ancré sur `^` et rendu insensible à la casse.

Les variantes source **et** traduite sont compilées, pour couvrir le cas d'une
boutique anglophone et celui d'une note enregistrée avant un changement de
locale.

Banc d'essai exécuté au moment du portage : 12 cas sur 3 locales (fr_FR, en_US,
de_DE), 4 notes automatiques et 2 notes humaines par locale. Tous passent.

### Dépendances tierces neutralisées

Aucune. Le plugin n'a jamais dépendu que de WooCommerce.

### Suivi des évolutions Annad

Évolutions côté Annad postérieures au portage et pas encore reportées ici :

- [ ] (rien à ce jour)

À surveiller : si Hugo ajoute des motifs propres à une extension (Dahu Post,
Sendcloud, Chronopost) dans la version Annad, ils n'ont **pas** vocation à
remonter dans la version publique — ils se déclarent côté boutique dans le
champ de réglages.

---

## Contexte du problème

Sur les commandes WooCommerce, l'historique des notes privées mélange en
permanence deux types de contenu : des notes générées automatiquement
(changement de statut, variation de stock, remboursement, numéro de suivi,
facture générée) et des notes réellement tapées à la main par un opérateur
(SAV, préparation, litige), qui contiennent l'information utile au quotidien.

Sur une boutique à fort volume, le bruit des notes automatiques rend difficile
le repérage des notes manuscrites lors du traitement d'une commande.

## Décisions d'architecture

- **Aucune écriture en base.** Le plugin ne modifie jamais le contenu ni les
  métadonnées des notes. Toute la logique est un calcul à l'affichage
  (`woocommerce_order_note_class` + feuille de style en ligne). Aucun risque de
  corruption, désactivation sans effet de bord.
- **Détection par contenu, pas par métadonnée.** WooCommerce ne distingue
  nativement que `customer-note` et `system-note`. Une note générée par du code
  **pendant qu'un administrateur est connecté** est attribuée à cet
  administrateur et devient indiscernable d'une note tapée à la main. C'est
  précisément ce cas que le plugin traite, et le libellé est le seul signal
  disponible.
- **Motifs reconstruits depuis les traductions de WooCommerce** plutôt
  qu'écrits en dur. Voir le détail ci-dessus.
- **Normalisation avant matching.** Les notes WooCommerce contiennent
  régulièrement des espaces insécables (`&nbsp;`, U+00A0), la typographie
  française en plaçant un avant chaque deux-points. Le contenu est donc
  dé-balisé, décodé, et tous ses espaces aplatis avant `preg_match`.
- **Motifs utilisateurs traités comme du texte littéral**, jamais comme des
  expressions régulières : une faute de frappe dans l'écran de réglages ne peut
  pas casser l'écran des commandes. Les développeurs passent par le filtre pour
  du vrai PCRE.
- **`@preg_match` sur les motifs filtrés.** Un motif malformé injecté par un
  tiers émettrait un warning et polluerait l'écran des commandes ; l'erreur est
  silencée volontairement, avec l'annotation PHPCS correspondante.
- **Support HPOS explicite** : styles chargés uniquement sur `shop_order`
  (legacy) et `woocommerce_page_wc-orders` (HPOS).

## Bugs historiques / pièges connus

- **Espaces insécables non normalisés.** Une première version testait les regex
  sur le contenu brut, ce qui faisait échouer le matching sur « Ajouté les
  lignes&nbsp;: ». Résolu par la normalisation systématique.
- **Confusion avec les notes déjà catégorisées.** Il faut sortir tôt si la note
  est déjà `customer-note` ou `system-note`, sous peine de la marquer à tort.
- **Piège d'initialisation.** `__( …, 'woocommerce' )` doit être appelé après
  `init`, sinon la traduction n'est pas chargée et les motifs sortent en
  anglais. D'où la compilation paresseuse avec cache statique dans
  `Dahu_Order_Notes_Detector::get_patterns()`, jamais au chargement du fichier.
