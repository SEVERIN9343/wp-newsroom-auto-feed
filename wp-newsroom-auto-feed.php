<?php
/*
Plugin Name: WP Newsroom Auto Feed
Description: Plugin WordPress pour afficher automatiquement les actualités et publications selon les catégories, avec shortcodes, pagination, home slider et sidebar de recherche.
Version: 1.0.0
Author: Sévérin OGAH
*/

if (!defined('ABSPATH')) {
    exit;
}

/* =======================================================
   ASSETS
======================================================= */
function zeb_newsroom_enqueue_assets() {
    wp_enqueue_style(
        'zeb-newsroom-style',
        plugin_dir_url(__FILE__) . 'assets/css/newsroom.css',
        array(),
        '1.0.0'
    );

    wp_enqueue_script(
        'zeb-newsroom-script',
        plugin_dir_url(__FILE__) . 'assets/js/newsroom.js',
        array(),
        '1.0.0',
        true
    );
}

function zeb_newsroom_force_enqueue_assets() {
    zeb_newsroom_enqueue_assets();
}

add_action('wp_enqueue_scripts', 'zeb_newsroom_enqueue_assets');


/* =======================================================
   HELPERS
======================================================= */
function zeb_newsroom_get_current_page() {
    return max(1, (int) ($_GET['znpg'] ?? 1));
}

function zeb_newsroom_get_current_topic() {
    return isset($_GET['topic']) ? sanitize_title(wp_unslash($_GET['topic'])) : '';
}

function zeb_newsroom_get_page_url_by_path($path, $fallback = '#') {
    $page = get_page_by_path(sanitize_title($path));

    if ($page) {
        return get_permalink($page->ID);
    }

    return $fallback;
}

function zeb_newsroom_get_category_link($category_slug) {
    $term = get_category_by_slug($category_slug);

    if ($term && !is_wp_error($term)) {
        return get_category_link($term->term_id);
    }

    return home_url('/');
}


/* =======================================================
   DOMAINES FIXES SIDEBAR / LABELS
======================================================= */
function zeb_newsroom_get_sidebar_categories_config() {
    return array(
        array('slug' => 'eolien', 'label' => 'Eolien'),
        array('slug' => 'aeronautique', 'label' => 'Aéronautique'),
        array('slug' => 'interne', 'label' => 'Interne'),
        array('slug' => 'evenements', 'label' => 'Evenements'),
        array('slug' => 'maintenance-industrielle', 'label' => 'Maintenance industrielle'),
        array('slug' => 'automobile', 'label' => 'Automobile'),
    );
}

function zeb_newsroom_get_resolved_sidebar_categories() {
    $resolved = array();

    foreach (zeb_newsroom_get_sidebar_categories_config() as $item) {
        $term = get_category_by_slug($item['slug']);

        if (!$term || is_wp_error($term)) {
            $term = get_term_by('name', $item['label'], 'category');
        }

        $resolved[] = array(
            'term'  => ($term && !is_wp_error($term)) ? $term : null,
            'label' => $item['label'],
            'slug'  => $item['slug'],
        );
    }

    return $resolved;
}

function zeb_newsroom_get_post_domain_data($post_id = 0) {
    $post_id = (int) $post_id;

    if ($post_id <= 0) {
        $post_id = get_the_ID();
    }

    $result = array(
        'label'        => '',
        'matched_slug' => '',
        'matched_term' => null,
        'source'       => '',
    );

    if ($post_id <= 0) {
        return $result;
    }

    $resolved_sidebar_categories = zeb_newsroom_get_resolved_sidebar_categories();
    $cats = get_the_category($post_id);
    $tags = get_the_tags($post_id);

    $custom = get_post_meta($post_id, 'zeb_card_label', true);
    $custom = is_string($custom) ? trim($custom) : '';

    if ($custom !== '') {
        $result['label'] = $custom;
        $result['source'] = 'custom';
    }

    if (!empty($tags) && !is_wp_error($tags)) {
        foreach ($tags as $tag) {
            $tag_slug = sanitize_title((string) $tag->slug);
            $tag_name = sanitize_title((string) $tag->name);

            foreach ($resolved_sidebar_categories as $item) {
                $cfg_slug = sanitize_title((string) $item['slug']);
                $cfg_label = sanitize_title((string) $item['label']);
                $term = $item['term'];

                $term_slug = $term ? sanitize_title((string) $term->slug) : '';
                $term_name = $term ? sanitize_title((string) $term->name) : '';

                $matched = (
                    $tag_slug === $cfg_slug ||
                    $tag_slug === $cfg_label ||
                    $tag_name === $cfg_slug ||
                    $tag_name === $cfg_label ||
                    ($term_slug !== '' && ($tag_slug === $term_slug || $tag_name === $term_slug)) ||
                    ($term_name !== '' && ($tag_slug === $term_name || $tag_name === $term_name))
                );

                if ($matched) {
                    $result['matched_slug'] = $cfg_slug;
                    $result['matched_term'] = $term;
                    $result['source'] = 'tag-match';

                    if ($result['label'] === '') {
                        $result['label'] = $term ? $term->name : $item['label'];
                    }

                    return $result;
                }
            }
        }
    }

    if (!empty($cats) && !is_wp_error($cats)) {
        foreach ($cats as $cat) {
            $cat_slug = sanitize_title((string) $cat->slug);
            $cat_name = sanitize_title((string) $cat->name);

            foreach ($resolved_sidebar_categories as $item) {
                $cfg_slug = sanitize_title((string) $item['slug']);
                $cfg_label = sanitize_title((string) $item['label']);
                $term = $item['term'];

                $term_slug = $term ? sanitize_title((string) $term->slug) : '';
                $term_name = $term ? sanitize_title((string) $term->name) : '';

                $matched = (
                    $cat_slug === $cfg_slug ||
                    $cat_slug === $cfg_label ||
                    $cat_name === $cfg_slug ||
                    $cat_name === $cfg_label ||
                    ($term_slug !== '' && ($cat_slug === $term_slug || $cat_name === $term_slug)) ||
                    ($term_name !== '' && ($cat_slug === $term_name || $cat_name === $term_name))
                );

                if ($matched) {
                    $result['matched_slug'] = $cfg_slug;
                    $result['matched_term'] = $term;
                    $result['source'] = 'category-match';

                    if ($result['label'] === '') {
                        $result['label'] = $term ? $term->name : $item['label'];
                    }

                    return $result;
                }
            }
        }
    }

    if ($result['label'] === '' && !empty($tags) && !is_wp_error($tags)) {
        $result['label'] = (string) $tags[0]->name;
        $result['source'] = 'tag';
        return $result;
    }

    if ($result['label'] === '' && !empty($cats) && !is_wp_error($cats)) {
        $result['label'] = (string) $cats[0]->name;
        $result['source'] = 'category';
        return $result;
    }

    return $result;
}

function zeb_newsroom_get_card_label($post_id = 0) {
    $data = zeb_newsroom_get_post_domain_data($post_id);
    return isset($data['label']) ? (string) $data['label'] : '';
}


/* =======================================================
   QUERY ARGS
======================================================= */
function zeb_newsroom_get_category_posts_args($atts, $with_pagination = true) {
    $category = sanitize_title($atts['category'] ?? 'actualites');
    $posts = max(1, (int) ($atts['posts'] ?? 6));
    $offset = max(0, (int) ($atts['offset'] ?? 0));
    $topic = sanitize_title($atts['topic'] ?? zeb_newsroom_get_current_topic());

    $args = array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => $posts,
        'ignore_sticky_posts' => true,
        'category_name'       => $category,
        'orderby'             => 'date',
        'order'               => 'DESC',
    );

    if ($topic !== '') {
        $args['tax_query'] = array(
            'relation' => 'OR',
            array(
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => array($topic),
            ),
            array(
                'taxonomy' => 'post_tag',
                'field'    => 'slug',
                'terms'    => array($topic),
            ),
        );
    }

    if ($with_pagination) {
        $paged = zeb_newsroom_get_current_page();
        $args['paged'] = $paged;

        if ($offset > 0) {
            $args['offset'] = $offset + (($paged - 1) * $posts);
        }
    } else {
        if ($offset > 0) {
            $args['offset'] = $offset;
        }
    }

    return $args;
}


/* =======================================================
   SIDEPANEL
======================================================= */
function zeb_newsroom_sidepanel_markup() {
    $actualites_url = zeb_newsroom_get_page_url_by_path('actualites', '#');
    $publications_url = zeb_newsroom_get_page_url_by_path('publications', '#');

    ob_start();
    ?>
    <aside class="zn-sidePanel" aria-label="Recherche articles">
        <button class="zn-sidePanelToggle" type="button" aria-expanded="false" aria-controls="znSidePanelBox">
            <span class="zn-sidePanelArrow" aria-hidden="true">→</span>
            <span class="zn-sidePanelText">Trouver un article</span>
        </button>

        <div class="zn-sidePanelBox" id="znSidePanelBox">
            <div class="zn-sidePanelInner">

                <div class="zn-sidePanelBlock" data-reveal="up" data-delay="60">
                    <h3 class="zn-sidePanelTitle">Rechercher</h3>
                    <form class="zn-sideSearch" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                        <input type="hidden" name="post_type" value="post">
                        <input type="text" name="s" placeholder="Tapez un mot-clé..." aria-label="Rechercher un article">
                        <button type="submit">OK</button>
                    </form>
                </div>

                <div class="zn-sidePanelBlock" data-reveal="up" data-delay="140">
                    <h3 class="zn-sidePanelTitle">Rubriques</h3>
                    <nav class="zn-sidePanelLinks" aria-label="Rubriques newsroom">
                        <a href="<?php echo esc_url($actualites_url); ?>">Actualités</a>
                        <a href="<?php echo esc_url($publications_url); ?>">Publications</a>
                    </nav>
                </div>

            </div>
        </div>
    </aside>
    <?php
    return ob_get_clean();
}


/* =======================================================
   PAGINATION
======================================================= */
function zeb_newsroom_render_pagination($query) {
    if (!$query instanceof WP_Query) {
        return '';
    }

    if ((int) $query->max_num_pages <= 1) {
        return '';
    }

    $current = zeb_newsroom_get_current_page();
    $base_url = remove_query_arg('znpg');

    $links = paginate_links(array(
        'base'      => esc_url_raw(add_query_arg('znpg', '%#%', $base_url)),
        'format'    => '',
        'current'   => $current,
        'total'     => max(1, (int) $query->max_num_pages),
        'type'      => 'array',
        'prev_text' => '←',
        'next_text' => '→',
    ));

    if (empty($links) || !is_array($links)) {
        return '';
    }

    $html = '<nav class="zn-pagination" aria-label="Pagination des articles">';

    foreach ($links as $link) {
        $html .= '<span class="zn-pageNum">' . $link . '</span>';
    }

    $html .= '</nav>';

    return $html;
}


/* =======================================================
   SHORTCODE FEATURED
======================================================= */
function zeb_news_featured_shortcode($atts) {
    $atts = shortcode_atts(array(
        'category' => 'actualites',
        'title'    => 'À la une',
        'posts'    => 1,
        'offset'   => 0,
        'topic'    => '',
    ), $atts, 'zeb_news_featured');

    zeb_newsroom_force_enqueue_assets();

    $q = new WP_Query(zeb_newsroom_get_category_posts_args($atts, false));

    if (!$q->have_posts()) {
        return '<div class="zn-empty">Aucun article disponible pour le moment.</div>';
    }

    ob_start();

    while ($q->have_posts()) : $q->the_post();
        $thumb = get_the_post_thumbnail_url(get_the_ID(), 'large');
        $label = zeb_newsroom_get_card_label(get_the_ID());
        ?>
        <article class="zn-featured" data-reveal="up">
            <a class="zn-featuredMedia" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
                <?php if ($thumb): ?>
                    <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>">
                <?php else: ?>
                    <span class="zn-featuredPlaceholder"></span>
                <?php endif; ?>
            </a>

            <div class="zn-featuredBody">
                <div class="zn-meta">
                    <time class="zn-metaDate" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                        <?php echo esc_html(get_the_date('d.m.Y')); ?>
                    </time>

                    <?php if ($label !== '') : ?>
                        <span class="zn-metaLabel"><?php echo esc_html($label); ?></span>
                    <?php endif; ?>
                </div>

                <h2 class="zn-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>

                <div class="zn-text">
                    <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 28, '…')); ?></p>
                </div>

                <a class="zn-btn" href="<?php the_permalink(); ?>">Lire l’article</a>
            </div>
        </article>
        <?php
    endwhile;

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('zeb_news_featured', 'zeb_news_featured_shortcode');


/* =======================================================
   SHORTCODE FEED
======================================================= */
function zeb_news_feed_shortcode($atts) {
    $atts = shortcode_atts(array(
        'category'   => 'actualites',
        'posts'      => 6,
        'title'      => '',
        'more'       => '0',
        'offset'     => 0,
        'pagination' => '1',
        'sidepanel'  => '1',
        'topic'      => '',
    ), $atts, 'zeb_news_feed');

    zeb_newsroom_force_enqueue_assets();

    $category = sanitize_title((string) $atts['category']);
    $show_more = in_array((string) $atts['more'], array('1', 'true', 'yes'), true);
    $show_pagination = in_array((string) $atts['pagination'], array('1', 'true', 'yes'), true);
    $show_sidepanel = in_array((string) $atts['sidepanel'], array('1', 'true', 'yes'), true);
    $topic = sanitize_title((string) ($atts['topic'] ?: zeb_newsroom_get_current_topic()));

    $q = new WP_Query(zeb_newsroom_get_category_posts_args($atts, $show_pagination));

    ob_start();

    echo '<section class="zn-feedWrap">';

    if ($show_sidepanel) {
        echo zeb_newsroom_sidepanel_markup();
    }

    if (!empty($atts['title'])) {
        echo '<h2 class="zn-sectionTitle" data-reveal="up">' . esc_html($atts['title']) . '</h2>';
    }

    if ($topic !== '') {
        echo '<div class="zn-topicBar" data-reveal="up">';
        echo '<span class="zn-topicLabel">Filtre actif :</span> ';
        echo '<strong class="zn-topicValue">' . esc_html(ucwords(str_replace('-', ' ', $topic))) . '</strong>';
        echo ' <a class="zn-topicReset" href="' . esc_url(zeb_newsroom_get_page_url_by_path($category, '#')) . '">Réinitialiser</a>';
        echo '</div>';
    }

    if ($q->have_posts()) {
        echo '<div class="zn-grid" data-stagger="1">';

        while ($q->have_posts()) : $q->the_post();
            $thumb = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
            $label = zeb_newsroom_get_card_label(get_the_ID());
            ?>
            <article class="zn-card" data-stagger-item>
                <a class="zn-cardMedia" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
                    <?php if ($thumb): ?>
                        <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>">
                    <?php else: ?>
                        <span class="zn-cardPlaceholder"></span>
                    <?php endif; ?>
                </a>

                <div class="zn-cardBody">
                    <div class="zn-cardMetaRow">
                        <div class="zn-cardDate">
                            <?php echo esc_html(get_the_date('d M Y')); ?>
                        </div>

                        <?php if ($label !== '') : ?>
                            <div class="zn-cardCats">
                                <?php echo esc_html($label); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <h3 class="zn-cardTitle">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>

                    <p class="zn-cardExcerpt">
                        <?php echo esc_html(wp_trim_words(get_the_excerpt(), 22, '...')); ?>
                    </p>

                    <a class="zn-cardMore" href="<?php the_permalink(); ?>">
                        En savoir plus <span aria-hidden="true">→</span>
                    </a>
                </div>
            </article>
            <?php
        endwhile;

        echo '</div>';

        if ($show_pagination) {
            echo zeb_newsroom_render_pagination($q);
        }

        if ($show_more) {
            $cat_link = zeb_newsroom_get_page_url_by_path($category, '#');
            echo '<div class="zn-actions" data-reveal="up"><a class="zn-btn zn-btn--ghost" href="' . esc_url($cat_link) . '">Voir plus</a></div>';
        }

    } else {
        echo '<div class="zn-empty">Aucun contenu publié pour le moment.</div>';
    }

    echo '</section>';

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('zeb_news_feed', 'zeb_news_feed_shortcode');


/* =======================================================
   HOME NEWS SHORTCODE
======================================================= */
function zeb_home_news_shortcode($atts) {
    zeb_newsroom_force_enqueue_assets();

    $atts = shortcode_atts(array(
        'category' => 'actualites',
        'posts'    => 4,
        'all_page' => 'actualites',
        'all_url'  => '',
        'title'    => 'Nos actualités',
        'kicker'   => "APERÇUS DE L'ENTREPRISE",
    ), $atts, 'zeb_home_news');

    $category = sanitize_title((string) $atts['category']);
    $posts = max(1, (int) $atts['posts']);

    $all_url = trim((string) $atts['all_url']);

    if ($all_url === '') {
        $all_url = zeb_newsroom_get_page_url_by_path(
            $atts['all_page'],
            zeb_newsroom_get_category_link($category)
        );
    }

    $q = new WP_Query(array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => $posts,
        'ignore_sticky_posts' => true,
        'category_name'       => $category,
        'orderby'             => 'date',
        'order'               => 'DESC',
    ));

    if (!$q->have_posts()) {
        return '';
    }

    ob_start();
    ?>
    <section class="hn-wrap" aria-label="Actualités">
        <header class="hn-head">
            <div class="hn-kicker">
                <span class="hn-kdot" aria-hidden="true"></span>
                <span><?php echo esc_html($atts['kicker']); ?></span>
            </div>

            <div class="hn-toprow">
                <h2 class="hn-title"><?php echo esc_html($atts['title']); ?></h2>

                <div class="hn-nav" aria-label="Navigation actualités">
                    <button class="hn-btnNav" type="button" data-hn-prev aria-label="Actualité précédente">←</button>
                    <button class="hn-btnNav" type="button" data-hn-next aria-label="Actualité suivante">→</button>
                </div>
            </div>
        </header>

        <div class="hn-track" data-hn-track>
            <?php while ($q->have_posts()) : $q->the_post(); ?>
                <?php
                $post_id = get_the_ID();
                $thumb = get_the_post_thumbnail_url($post_id, 'large');
                $label = zeb_newsroom_get_card_label($post_id);
                $meta_text = $label !== '' ? '• ' . $label : '• BLOG';
                ?>
                <article class="hn-card">
                    <a class="hn-media" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
                        <?php if ($thumb): ?>
                            <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
                        <?php else: ?>
                            <span class="hn-mediaPlaceholder"></span>
                        <?php endif; ?>
                    </a>

                    <div class="hn-body">
                        <div class="hn-meta">
                            <span class="hn-cat"><?php echo esc_html($meta_text); ?></span>
                            <span class="hn-date"><?php echo esc_html(mb_strtoupper(get_the_date('d M, Y'), 'UTF-8')); ?></span>
                        </div>

                        <h3 class="hn-h3">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>

                        <div class="hn-author">PAR MASER ENGINEERING</div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <div class="hn-cta">
            <a class="hn-all" href="<?php echo esc_url($all_url); ?>">
                Voir toutes les actualités <span aria-hidden="true">→</span>
            </a>
        </div>
    </section>
    <?php

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('zeb_home_news', 'zeb_home_news_shortcode');