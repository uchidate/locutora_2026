<?php
defined('ABSPATH') || exit;

/* ─── Suporte do tema ─── */
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);

    register_nav_menus([
        'primary' => 'Menu principal',
    ]);
});

/* ─── Enqueue assets ─── */
add_action('wp_enqueue_scripts', function () {
    $v = wp_get_theme()->get('Version');

    wp_enqueue_style(
        'google-fonts',
        'https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Hanken+Grotesk:wght@400;500;600;700&family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700;12..96,800&family=JetBrains+Mono:wght@400;500&display=swap',
        [],
        null
    );

    wp_enqueue_style('locutora-main', get_template_directory_uri() . '/assets/css/main.css', ['google-fonts'], $v);

    wp_enqueue_script(
        'wavesurfer',
        'https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.min.js',
        [],
        '7',
        true
    );

    wp_enqueue_script('locutora-player', get_template_directory_uri() . '/assets/js/player.js', ['wavesurfer'], $v, true);
});

/* ─── CPT: Demo de áudio ─── */
add_action('init', function () {
    register_post_type('demo', [
        'labels' => [
            'name'               => 'Demos',
            'singular_name'      => 'Demo',
            'add_new_item'       => 'Adicionar demo',
            'edit_item'          => 'Editar demo',
            'new_item'           => 'Novo demo',
            'view_item'          => 'Ver demo',
            'search_items'       => 'Buscar demos',
            'not_found'          => 'Nenhum demo encontrado',
            'not_found_in_trash' => 'Nenhum demo na lixeira',
        ],
        'public'       => true,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-microphone',
        'supports'     => ['title', 'thumbnail'],
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'demos'],
    ]);
});

/* ─── Taxonomia: Segmento ─── */
add_action('init', function () {
    register_taxonomy('segmento', 'demo', [
        'labels' => [
            'name'          => 'Segmentos',
            'singular_name' => 'Segmento',
            'add_new_item'  => 'Adicionar segmento',
        ],
        'hierarchical'  => true,
        'show_in_rest'  => true,
        'rewrite'       => ['slug' => 'segmento'],
    ]);
});

/* ─── ACF: campos dos demos (fallback sem ACF) ─── */
// Com ACF Pro: crie um Field Group em ACF > Field Groups > "Demo"
// ligado ao post type "demo" com os campos:
//   - audio_file  (File, retorna URL)
//   - duracao     (Text, ex: "0:48")
//
// Helpers para obter os valores mesmo sem ACF ativo:
function locutora_get_soundcloud_url(int $post_id): string {
    if (function_exists('get_field')) {
        return (string) (get_field('soundcloud_url', $post_id) ?? '');
    }
    return (string) get_post_meta($post_id, '_soundcloud_url', true);
}

function locutora_get_audio_url(int $post_id): string {
    if (function_exists('get_field')) {
        $file = get_field('audio_file', $post_id);
        return (string) ($file['url'] ?? '');
    }
    return (string) get_post_meta($post_id, '_audio_file_url', true);
}

function locutora_get_duracao(int $post_id): string {
    if (function_exists('get_field')) {
        return (string) (get_field('duracao', $post_id) ?? '');
    }
    return (string) get_post_meta($post_id, '_duracao', true);
}

/**
 * Monta a URL do iframe do SoundCloud com as cores do tema.
 * Aceita URL de track ou de playlist.
 */
function locutora_soundcloud_embed_url(string $sc_url, bool $mini = true): string {
    // Constrói manualmente para evitar double-encoding do # da cor
    $color = 'C9A35B'; // sem # — SC aceita assim
    $visual = $mini ? 'false' : 'true';
    return add_query_arg([
        'url'           => rawurlencode($sc_url),
        'color'         => '%23' . $color,
        'auto_play'     => 'false',
        'hide_related'  => 'true',
        'show_comments' => 'false',
        'show_user'     => 'false',
        'show_reposts'  => 'false',
        'show_teaser'   => 'false',
        'visual'        => $visual,
    ], 'https://w.soundcloud.com/player/');
}

/* ─── Helper: clientes (logos) ─── */
// Com ACF: Field Group "Opções do tema" (Options Page)
//   - clientes (Repeater)
//       - nome (Text)
function locutora_get_clientes(): array {
    if (function_exists('get_field')) {
        $acf = get_field('clientes', 'option');
        if (!empty($acf)) return (array) $acf;
    }
    return [
        ['nome' => 'Apple'],
        ['nome' => 'Netflix'],
        ['nome' => 'Santander'],
        ['nome' => 'Bradesco'],
        ['nome' => 'Globo'],
        ['nome' => 'Nespresso'],
    ];
}

/* ─── ACF: local JSON (sincroniza field groups automaticamente) ─── */
add_filter('acf/settings/load_json', function ($paths) {
    $paths[] = get_stylesheet_directory() . '/acf-json';
    return $paths;
});
add_filter('acf/settings/save_json', function () {
    return get_stylesheet_directory() . '/acf-json';
});

/* ─── Options Page (ACF free suporta desde v6.x) ─── */
add_action('acf/init', function () {
    if (function_exists('acf_add_options_page')) {
        acf_add_options_page([
            'page_title' => 'Configurações do site',
            'menu_title' => 'Configurações',
            'menu_slug'  => 'locutora-settings',
            'capability' => 'manage_options',
        ]);
    }
});
