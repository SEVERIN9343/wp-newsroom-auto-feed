# WP Newsroom Auto Feed

Plugin WordPress permettant d’afficher automatiquement les dernières actualités et publications selon les catégories.

## Objectif

Ce plugin a été développé pour automatiser l’affichage des articles WordPress sur différentes zones du site :

- page mère Actualités
- page mère Publications
- homepage
- blocs “À la une”
- flux d’articles avec pagination

Lorsqu’un article est publié dans une catégorie donnée, il remonte automatiquement dans les blocs correspondants.

## Fonctionnalités

- Affichage automatique des articles les plus récents
- Shortcode pour article mis en avant
- Shortcode pour grille d’actualités ou publications
- Shortcode pour slider d’actualités en page d’accueil
- Filtrage par catégorie
- Filtrage par tag ou domaine
- Pagination personnalisée
- Side panel de recherche
- Animations au scroll
- Slider horizontal pour la home page
- Support des images mises en avant
- Compatible avec les catégories WordPress natives

## Shortcodes

### Article mis en avant

```text
[zeb_news_featured category="actualites" posts="1"]
```

### Flux d’actualités

```text
[zeb_news_feed category="actualites" posts="6" title="Nos actualités"]
```

### Flux de publications

```text
[zeb_news_feed category="publications" posts="6" title="Nos publications"]
```

### Bloc home page

```text
[zeb_home_news category="actualites" posts="4" title="Nos actualités"]
```

## Stack utilisée

- PHP
- WordPress
- WP_Query
- Shortcodes WordPress
- JavaScript
- CSS responsive
- IntersectionObserver

## Installation

1. Copier le dossier du plugin dans :

```bash
wp-content/plugins/wp-newsroom-auto-feed/
```

2. Activer le plugin depuis l’administration WordPress :

```text
Extensions > Activer
```

3. Utiliser les shortcodes dans les pages WordPress.

## Structure du plugin

```text
wp-newsroom-auto-feed/
├── wp-newsroom-auto-feed.php
├── README.md
└── assets/
    ├── css/
    │   └── newsroom.css
    └── js/
        └── newsroom.js
```

## Auteur

Sévérin OGAH