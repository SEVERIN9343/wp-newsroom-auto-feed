# WP Newsroom Auto Feed

Plugin WordPress permettant d’afficher automatiquement les dernières actualités et publications selon les catégories.

## 🎯 Problème
Sur un site corporate, les actualités, publications ou articles doivent souvent être affichés à plusieurs endroits du site. Les gérer manuellement peut prendre du temps et créer des oublis ou incohérences.

## 💡 Solution
Newsroom Auto Feed automatise l’affichage des articles WordPress selon les catégories, tags ou zones du site grâce à des shortcodes personnalisés.

## 🚀 Bénéfices
- Gain de temps dans la gestion éditoriale
- Affichage automatique des contenus récents
- Meilleure cohérence entre les pages
- Amélioration du maillage interne
- Compatible avec une stratégie SEO de contenu

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