<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script deve ser executado pela linha de comando.\n");
    exit(1);
}

$wpLoadPath = getenv('WP_LOAD_PATH') ?: '/var/www/html/wp-load.php';

if (!is_file($wpLoadPath)) {
    fwrite(STDERR, "wp-load.php não encontrado em {$wpLoadPath}.\n");
    exit(1);
}

require $wpLoadPath;
require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * Cria ou atualiza uma página estrutural sem substituir conteúdo editorial.
 */
function locutora_ensure_page(string $slug, string $title, string $template = 'default'): int
{
    $page = get_page_by_path($slug, OBJECT, 'page');

    if ($page instanceof WP_Post) {
        $pageId = (int) $page->ID;

        if ($page->post_status !== 'publish') {
            wp_update_post([
                'ID' => $pageId,
                'post_status' => 'publish',
            ]);
        }
    } else {
        $pageId = wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => $slug,
            'post_title' => $title,
            'post_content' => '',
        ], true);

        if (is_wp_error($pageId)) {
            throw new RuntimeException($pageId->get_error_message());
        }
    }

    if ($template !== 'default') {
        update_post_meta((int) $pageId, '_wp_page_template', $template);
    }

    return (int) $pageId;
}

$pages = [
    'home' => locutora_ensure_page('home', 'Home'),
    'sobre-nos' => locutora_ensure_page('sobre-nos', 'Sobre nós', 'page-sobre-nos.php'),
    'servicos' => locutora_ensure_page('servicos', 'Áudios', 'page-servicos.php'),
    'contato' => locutora_ensure_page('contato', 'Contato', 'page-contato.php'),
    'orcamento' => locutora_ensure_page('orcamento', 'Orçamento', 'page-orcamento.php'),
    'politica-de-privacidade' => locutora_ensure_page(
        'politica-de-privacidade',
        'Política de Privacidade',
        'page-politica-de-privacidade.php'
    ),
];

$options = [
    'blogname' => 'Adriana Rosa',
    'blogdescription' => 'Locutora profissional e gravações de voz',
    'timezone_string' => 'America/Sao_Paulo',
    'date_format' => 'd/m/Y',
    'time_format' => 'H:i',
    'start_of_week' => 1,
    'permalink_structure' => '/%postname%/',
    'show_on_front' => 'page',
    'page_on_front' => $pages['home'],
    'page_for_privacy_policy' => $pages['politica-de-privacidade'],
    'users_can_register' => 0,
    'default_comment_status' => 'closed',
    'default_ping_status' => 'closed',
    'default_pingback_flag' => 0,
];

foreach ($options as $option => $value) {
    update_option($option, $value);
}

$activePlugins = array_values(array_filter(
    (array) get_option('active_plugins', []),
    static fn (string $plugin): bool => is_file(WP_PLUGIN_DIR . '/' . $plugin)
));
update_option('active_plugins', $activePlugins);

$samplePage = get_page_by_path('sample-page', OBJECT, 'page');
if ($samplePage instanceof WP_Post && $samplePage->post_title === 'Sample Page') {
    wp_trash_post($samplePage->ID);
}

$helloWorld = get_page_by_path('hello-world', OBJECT, 'post');
if ($helloWorld instanceof WP_Post && $helloWorld->post_title === 'Hello world!') {
    wp_trash_post($helloWorld->ID);
}

switch_theme('locutora');
flush_rewrite_rules(false);

echo json_encode([
    'status' => 'ok',
    'theme' => get_option('stylesheet'),
    'front_page' => get_option('page_on_front'),
    'privacy_page' => get_option('page_for_privacy_policy'),
    'timezone' => get_option('timezone_string'),
    'permalink' => get_option('permalink_structure'),
    'pages' => $pages,
    'active_plugins' => get_option('active_plugins'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
