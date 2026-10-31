# Dahu Order Notes

Fait ressortir les notes de commande WooCommerce qu'une personne a réellement
écrites, parmi celles que WooCommerce et ses extensions ajoutent d'elles-mêmes.

Développé et maintenu par [Hugo Vial-Jaime — Dahu-Concept](https://dahu-concept.fr).

*La version anglaise de ce document est dans `README.md`. En cas d'écart, c'est
elle qui fait foi : c'est elle qui part sur wordpress.org.*

## Le problème

Sur une boutique active, le panneau des notes de commande se remplit d'entrées
produites par la machine : changements de statut, mouvements de stock,
remboursements, numéros de suivi, événements de facturation. La seule note qui
compte, celle qu'un collègue a tapée au sujet de ce client précis, est enterrée
dedans.

WooCommerce distingue déjà les notes client des notes système. Ce qu'il ne sait
pas distinguer, c'est la troisième catégorie : les notes produites par du code
*pendant qu'un membre de l'équipe est connecté*. Changer le statut d'une
commande depuis l'administration produit une note attribuée à cette personne,
indiscernable dans le balisage d'une note qu'elle aurait tapée elle-même.

Dahu Order Notes repère celles-là, et laisse en évidence celles écrites à la
main.

## Comment la détection fonctionne

Une note automatique ne se reconnaît qu'à sa formulation. Écrire cette
formulation en dur lierait l'extension à une seule langue : elle reconstruit
donc ses motifs à l'exécution, à partir des chaînes traduisibles de WooCommerce
lui-même :

```php
__( 'Order status changed from %1$s to %2$s.', 'woocommerce' )
```

Les marqueurs deviennent des jokers, le reste est comparé littéralement. Une
boutique française reconnaît donc « État de la commande modifié de … », une
allemande « Bestellstatus geändert von … », sans configuration ni fichier de
traduction propre.

Le contenu d'une note est normalisé avant comparaison — HTML retiré, entités
décodées, tous les genres d'espaces aplatis — parce que les notes WooCommerce
portent couramment des espaces insécables, que la typographie française met
devant chaque deux-points.

## Fonctionnalités

- Met en évidence les notes privées écrites à la main sur l'écran de commande
- Détection indépendante de la langue, reconstruite depuis les traductions de
  WooCommerce
- Couleurs de mise en évidence configurables
- Phrases supplémentaires configurables depuis l'écran de réglages, sans code
- Filtre `dahu_order_notes_auto_patterns` pour les développeurs
- Fonctionne avec l'ancien stockage des commandes comme avec le HPOS
- N'écrit jamais en base ; la désactivation ne laisse aucune trace

## Prérequis

| | |
|---|---|
| WordPress | 6.0 ou plus |
| PHP | 7.4 ou plus |
| WooCommerce | 7.0 ou plus |
| HPOS | Compatible |

## Installation

1. Téléchargez la dernière version, ou clonez ce dépôt dans
   `wp-content/plugins/`.
2. Activez l'extension depuis l'écran des extensions.
3. Ajustez au besoin les couleurs sous **WooCommerce → Notes de commande**.

La mise en évidence est active par défaut.

## Configuration

| Réglage | Effet |
|---|---|
| Mise en évidence | Active ou désactive la mise en évidence. |
| Couleur des notes écrites à la main | Fond des notes identifiées comme écrites à la main. |
| Couleur de l'en-tête des notes client | Fond de l'en-tête des notes envoyées au client. |
| Notes automatiques supplémentaires | Une phrase par ligne. Toute note commençant par l'une d'elles est considérée comme automatique. Du texte simple, pas des expressions régulières. |

## Pour les développeurs

Ajouter vos propres motifs, en expressions PCRE complètes :

```php
add_filter( 'dahu_order_notes_auto_patterns', function ( array $patterns ) {
    $patterns[] = '/^Numéro de suivi ajouté/i';
    return $patterns;
} );
```

Changer les écrans d'administration où la mise en évidence s'applique :

```php
add_filter( 'dahu_order_notes_order_screens', function ( array $screens ) {
    $screens[] = 'mon_ecran_de_commande';
    return $screens;
} );
```

## Services externes

Cette extension ne contacte aucun service externe.

## Licence

MIT — voir [LICENSE](LICENSE).
