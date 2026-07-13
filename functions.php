<?php
defined('ABSPATH') || exit;

if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

const LOCUTORA_SITE_CONFIG_VERSION = 15;

/* ─── Suporte do tema ─── */
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    add_theme_support('editor-styles');
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_editor_style(['assets/css/main.css', 'assets/css/editor.css']);

    register_nav_menus([
        'primary' => 'Menu principal',
    ]);
});

function locutora_dom_inner_html(DOMNode $node): string {
    $html = '';

    foreach ($node->childNodes as $child) {
        $html .= $node->ownerDocument->saveHTML($child);
    }

    return trim($html);
}

/**
 * Converte o HTML confiável do tema em blocos nativos editáveis do Gutenberg.
 */
function locutora_html_to_core_blocks(string $html): string {
    if (!class_exists('DOMDocument')) {
        return '';
    }

    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML(
        '<?xml encoding="UTF-8"><div id="locutora-block-root">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (!$loaded) {
        return '';
    }

    $root = $document->getElementById('locutora-block-root');
    if (!$root instanceof DOMElement) {
        return '';
    }

    $blocks = [];

    foreach ($root->childNodes as $node) {
        if (!$node instanceof DOMElement) {
            continue;
        }

        $tag = strtolower($node->tagName);

        if ($tag === 'p') {
            $blocks[] = "<!-- wp:paragraph -->\n" . $document->saveHTML($node) . "\n<!-- /wp:paragraph -->";
            continue;
        }

        if ($tag === 'ul' || $tag === 'ol') {
            $attributes = [];
            if ($tag === 'ol') {
                $attributes['ordered'] = true;
                $start = (int) $node->getAttribute('start');
                if ($start > 1) {
                    $attributes['start'] = $start;
                }
            }

            $node->setAttribute('class', trim($node->getAttribute('class') . ' wp-block-list'));
            $serialized_attributes = $attributes ? ' ' . wp_json_encode($attributes) : '';
            $blocks[] = '<!-- wp:list' . $serialized_attributes . " -->\n"
                . $document->saveHTML($node)
                . "\n<!-- /wp:list -->";
            continue;
        }

        if (in_array($tag, ['h2', 'h3', 'h4'], true)) {
            $level = (int) substr($tag, 1);
            $blocks[] = '<!-- wp:heading {"level":' . $level . "} -->\n"
                . $document->saveHTML($node)
                . "\n<!-- /wp:heading -->";
            continue;
        }

        $blocks[] = "<!-- wp:html -->\n" . $document->saveHTML($node) . "\n<!-- /wp:html -->";
    }

    return implode("\n\n", $blocks);
}

function locutora_seed_privacy_blocks(): void {
    $privacy_page = get_page_by_path('politica-de-privacidade', OBJECT, 'page');
    if (!$privacy_page instanceof WP_Post || trim((string) $privacy_page->post_content) !== '') {
        return;
    }

    ob_start();
    get_template_part('template-parts/privacy-content');
    $privacy_html = (string) ob_get_clean();
    $privacy_blocks = locutora_html_to_core_blocks($privacy_html);

    if ($privacy_blocks !== '') {
        wp_update_post([
            'ID' => $privacy_page->ID,
            'post_content' => $privacy_blocks,
        ]);
    }
}

function locutora_seed_home_blocks(): void {
    $home_page = get_page_by_path('home', OBJECT, 'page');
    if (!$home_page instanceof WP_Post || trim((string) $home_page->post_content) !== '') {
        return;
    }

    wp_update_post([
        'ID' => $home_page->ID,
        'post_content' => implode("\n\n", [
            '<!-- wp:locutora/hero /-->',
            '<!-- wp:locutora/intro /-->',
            '<!-- wp:locutora/services /-->',
            '<!-- wp:locutora/contact-cta /-->',
        ]),
    ]);
}

function locutora_seed_internal_blocks(): void {
    $pages = [
        'sobre-nos' => [
            '<!-- wp:locutora/internal-hero {"title":"Sobre nós:","variant":"sobre"} /-->',
            '<!-- wp:locutora/about-story /-->',
            '<!-- wp:locutora/about-bio /-->',
            '<!-- wp:locutora/brands /-->',
        ],
        'servicos' => [
            '<!-- wp:locutora/internal-hero {"title":"Áudios","variant":"services"} /-->',
            '<!-- wp:locutora/audio-showcase /-->',
        ],
        'contato' => [
            '<!-- wp:locutora/internal-hero {"title":"Contato","variant":"contact"} /-->',
            '<!-- wp:locutora/contact-form /-->',
        ],
    ];

    foreach ($pages as $slug => $blocks) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        if (!$page instanceof WP_Post) {
            continue;
        }

        $current_content = trim((string) $page->post_content);
        if ($current_content !== '') {
            continue;
        }

        wp_update_post(['ID' => $page->ID, 'post_content' => implode("\n\n", $blocks)]);
    }
}

/* ─── Configuração inicial segura ao ativar o tema ─── */
function locutora_ensure_structural_page(string $slug, string $title, string $template = 'default'): int {
    $page = get_page_by_path($slug, OBJECT, 'page');

    if ($page instanceof WP_Post) {
        $page_id = (int) $page->ID;
    } else {
        $page_id = wp_insert_post([
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_name'    => $slug,
            'post_title'   => $title,
            'post_content' => '',
        ], true);

        if (is_wp_error($page_id)) {
            return 0;
        }
    }

    if ($template !== 'default') {
        update_post_meta((int) $page_id, '_wp_page_template', $template);
    }

    return (int) $page_id;
}

function locutora_configure_site_on_activation(): void {
    $home_id = locutora_ensure_structural_page('home', 'Home');
    $privacy_id = locutora_ensure_structural_page(
        'politica-de-privacidade',
        'Política de Privacidade',
        'page-politica-de-privacidade.php'
    );

    locutora_ensure_structural_page('sobre-nos', 'Sobre nós', 'page-sobre-nos.php');
    locutora_ensure_structural_page('servicos', 'Áudios', 'page-servicos.php');
    locutora_ensure_structural_page('contato', 'Contato', 'page-contato.php');

    update_option('blogname', 'Locutora.com');
    update_option('blogdescription', 'Locutora profissional e gravações de voz');
    update_option('timezone_string', 'America/Sao_Paulo');
    update_option('date_format', 'd/m/Y');
    update_option('time_format', 'H:i');
    update_option('start_of_week', 1);
    update_option('permalink_structure', '/%postname%/');
    update_option('users_can_register', 0);
    update_option('default_comment_status', 'closed');
    update_option('default_ping_status', 'closed');
    update_option('default_pingback_flag', 0);

    $host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    if (str_ends_with($host, '.hostingersite.com')) {
        update_option('blog_public', 0);
    }

    if ($home_id > 0) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $home_id);
    }

    if ($privacy_id > 0) {
        update_option('page_for_privacy_policy', $privacy_id);
    }

    locutora_seed_privacy_blocks();
    locutora_seed_home_blocks();
    locutora_seed_internal_blocks();
    locutora_apply_rank_math_metadata();
    locutora_configure_rank_math_identity();

    $english_privacy = get_post(3);
    if ($english_privacy instanceof WP_Post
        && $english_privacy->post_type === 'page'
        && $english_privacy->post_title === 'Privacy Policy') {
        wp_trash_post($english_privacy->ID);
    }

    $budget_page = get_post(10);
    if ($budget_page instanceof WP_Post
        && $budget_page->post_type === 'page'
        && $budget_page->post_name === 'orcamento') {
        wp_trash_post($budget_page->ID);
    }

    $sample_page = get_page_by_path('sample-page', OBJECT, 'page');
    if ($sample_page instanceof WP_Post && $sample_page->post_title === 'Sample Page') {
        wp_trash_post($sample_page->ID);
    }

    $hello_world = get_page_by_path('hello-world', OBJECT, 'post');
    if ($hello_world instanceof WP_Post && $hello_world->post_title === 'Hello world!') {
        wp_trash_post($hello_world->ID);
    }

    update_option('locutora_site_config_version', LOCUTORA_SITE_CONFIG_VERSION, false);
    update_option('locutora_pending_cache_purge', 1, false);
    do_action('litespeed_purge_all');
    flush_rewrite_rules(false);
}
add_action('after_switch_theme', 'locutora_configure_site_on_activation');

add_action('init', function (): void {
    if ((int) get_option('locutora_site_config_version', 0) < LOCUTORA_SITE_CONFIG_VERSION) {
        locutora_configure_site_on_activation();
    }
}, 99);

function locutora_is_temporary_environment(): bool {
    $host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    return str_ends_with($host, '.hostingersite.com');
}

add_action('init', function (): void {
    $expected = locutora_is_temporary_environment() ? 0 : 1;
    if ((int) get_option('blog_public', 0) !== $expected) {
        update_option('blog_public', $expected);
    }
}, 98);

function locutora_apply_rank_math_metadata(): void {
    $metadata = [
        'home' => [
            'title' => 'Home - Locutora',
            'description' => 'Fundada em 2004 pela radialista, locutora profissional e atriz Adriana Rosa, a Locutora.com oferece serviços de locução profissional e voice over em português.',
            'keywords' => 'locutora profissional, locução profissional, voice over em português, gravação de voz',
        ],
        'sobre-nos' => [
            'title' => 'Locutora Adriana Rosa - Locutora',
            'description' => 'Conheça Adriana Rosa, locutora profissional especializada em URA, comerciais e institucionais. Saiba mais sobre sua experiência e trabalhos.',
            'keywords' => 'Adriana Rosa, locutora profissional, atriz, radialista',
        ],
        'servicos' => [
            'title' => 'Áudios - Locutora',
            'description' => 'Locutora profissional Adriana Rosa. Ouça alguns trabalhos.',
            'keywords' => 'locutora, locutora para URA, espera telefônica, gravação de spot, gravação de audiobook',
        ],
        'contato' => [
            'title' => 'Contato - Locutora',
            'description' => 'Entre em contato com Locutora.com - Adriana Rosa (11) 98440-4171.',
            'keywords' => 'contato Locutora.com, Adriana Rosa, orçamento de locução',
        ],
        'politica-de-privacidade' => [
            'title' => 'Política de privacidade - Locutora',
            'description' => 'Política de privacidade e proteção de dados da Locutora.com.',
            'keywords' => 'política de privacidade, proteção de dados',
        ],
    ];

    foreach ($metadata as $slug => $values) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        if (!$page instanceof WP_Post) {
            continue;
        }
        update_post_meta($page->ID, 'rank_math_title', $values['title']);
        update_post_meta($page->ID, 'rank_math_description', $values['description']);
        update_post_meta($page->ID, 'rank_math_focus_keyword', $values['keywords']);
        delete_post_meta($page->ID, '_yoast_wpseo_title');
        delete_post_meta($page->ID, '_yoast_wpseo_metadesc');
    }
}

function locutora_configure_rank_math_identity(): void {
    $titles = (array) get_option('rank-math-options-titles', []);
    $titles['knowledgegraph_type'] = 'company';
    $titles['knowledgegraph_name'] = 'Locutora.com';
    $titles['website_name'] = 'Locutora.com';
    $titles['website_alternate_name'] = 'Adriana Rosa';
    $titles['social_url_instagram'] = 'https://www.instagram.com/adriana.rosa_s';
    $titles['social_url_linkedin'] = 'https://www.linkedin.com/in/adrianarosa-voiceover/';
    $titles['social_url_youtube'] = 'https://www.youtube.com/adrianalocutoracom';
    $titles['disable_author_archives'] = 'on';
    update_option('rank-math-options-titles', $titles);

    $modules = (array) get_option('rank_math_modules', []);
    update_option('rank_math_modules', array_values(array_unique(array_merge($modules, ['sitemap', 'schema', 'local-seo']))));

    $sitemap = (array) get_option('rank-math-options-sitemap', []);
    $sitemap['authors_sitemap'] = 'off';
    $sitemap['authors_html_sitemap'] = 'off';
    $sitemap['pt_page_sitemap'] = 'on';
    update_option('rank-math-options-sitemap', $sitemap);

    if (class_exists('RankMath\\Sitemap\\Cache')) {
        \RankMath\Sitemap\Cache::invalidate_storage();
    }
}

function locutora_install_rank_math_seo(): void {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

    $plugin_file = 'seo-by-rank-math/rank-math.php';
    $yoast = 'wordpress-seo/wp-seo.php';

    if (is_plugin_active($plugin_file)) {
        locutora_apply_rank_math_metadata();
        locutora_configure_rank_math_identity();
        if (is_plugin_active($yoast)) {
            deactivate_plugins($yoast, true);
        }
        if (file_exists(WP_PLUGIN_DIR . '/' . $yoast)) {
            delete_plugins([$yoast]);
        }
        update_option('locutora_rank_math_install_status', 'active');
        return;
    }

    if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
        $api = plugins_api('plugin_information', [
            'slug' => 'seo-by-rank-math',
            'fields' => ['sections' => false],
        ]);
        if (is_wp_error($api) || empty($api->download_link)) {
            update_option('locutora_rank_math_install_status', 'erro-api');
            return;
        }

        $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
        $installed = $upgrader->install($api->download_link);
        if (is_wp_error($installed) || $installed !== true) {
            update_option('locutora_rank_math_install_status', 'erro-instalacao');
            return;
        }
    }

    $yoast_was_active = is_plugin_active($yoast);
    if ($yoast_was_active) {
        deactivate_plugins($yoast, true);
    }

    $activated = activate_plugin($plugin_file);
    if (is_wp_error($activated) || !is_plugin_active($plugin_file)) {
        if ($yoast_was_active && file_exists(WP_PLUGIN_DIR . '/' . $yoast)) {
            activate_plugin($yoast);
        }
        update_option('locutora_rank_math_install_status', 'erro-ativacao');
        return;
    }

    locutora_apply_rank_math_metadata();
    locutora_configure_rank_math_identity();

    if (file_exists(WP_PLUGIN_DIR . '/' . $yoast)) {
        delete_plugins([$yoast]);
    }

    update_option('locutora_rank_math_install_status', 'active');
}
add_action('locutora_install_rank_math_seo', 'locutora_install_rank_math_seo');

add_action('init', function (): void {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    if (!is_plugin_active('seo-by-rank-math/rank-math.php')
        && !wp_next_scheduled('locutora_install_rank_math_seo')) {
        wp_schedule_single_event(time() + 5, 'locutora_install_rank_math_seo');
    }
}, 100);

function locutora_requested_plugins(): array {
    return [
        ['slug' => 'litespeed-cache', 'file' => 'litespeed-cache/litespeed-cache.php', 'label' => 'LiteSpeed Cache'],
        ['slug' => 'wp-mail-smtp', 'file' => 'wp-mail-smtp/wp_mail_smtp.php', 'label' => 'WP Mail SMTP'],
        ['slug' => 'two-factor', 'file' => 'two-factor/two-factor.php', 'label' => 'Two Factor'],
        ['slug' => 'advanced-custom-fields', 'file' => 'advanced-custom-fields/acf.php', 'label' => 'Advanced Custom Fields'],
        ['slug' => 'enable-media-replace', 'file' => 'enable-media-replace/enable-media-replace.php', 'label' => 'Enable Media Replace'],
    ];
}

function locutora_install_requested_plugin(string $slug, string $plugin_file): void {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

    $statuses = (array) get_option('locutora_requested_plugin_statuses', []);
    if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
        $api = plugins_api('plugin_information', [
            'slug' => $slug,
            'fields' => ['sections' => false],
        ]);
        if (is_wp_error($api) || empty($api->download_link)) {
            $statuses[$slug] = 'erro-api';
            update_option('locutora_requested_plugin_statuses', $statuses, false);
            return;
        }

        $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
        $installed = $upgrader->install($api->download_link);
        if (is_wp_error($installed) || $installed !== true) {
            $statuses[$slug] = 'erro-instalacao';
            update_option('locutora_requested_plugin_statuses', $statuses, false);
            return;
        }
    }

    $activated = activate_plugin($plugin_file);
    if (is_wp_error($activated) || !is_plugin_active($plugin_file)) {
        $statuses[$slug] = 'erro-ativacao';
    } else {
        $statuses[$slug] = 'active';
    }
    update_option('locutora_requested_plugin_statuses', $statuses, false);
}
add_action('locutora_install_requested_plugin', 'locutora_install_requested_plugin', 10, 2);

add_action('init', function (): void {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    foreach (locutora_requested_plugins() as $plugin) {
        $args = [$plugin['slug'], $plugin['file']];
        if (!is_plugin_active($plugin['file']) && !wp_next_scheduled('locutora_install_requested_plugin', $args)) {
            wp_schedule_single_event(time() + 5, 'locutora_install_requested_plugin', $args);
            break;
        }
    }
}, 101);

add_filter('rank_math/opengraph/facebook/image', function ($image) {
    return $image ?: (string) locutora_setting('social_image', get_template_directory_uri() . '/assets/images/intro.png');
});

add_filter('rank_math/opengraph/twitter/image', function ($image) {
    return $image ?: (string) locutora_setting('social_image', get_template_directory_uri() . '/assets/images/intro.png');
});

add_filter('robots_txt', function (string $output): string {
    if (locutora_is_temporary_environment()) {
        return "User-agent: *\nDisallow: /\n";
    }
    return $output;
}, 99);

add_action('wp_head', function (): void {
    if (!defined('RANK_MATH_VERSION')) {
        return;
    }
    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebSite',
                '@id' => home_url('/#website'),
                'url' => home_url('/'),
                'name' => 'Locutora.com',
                'alternateName' => 'Adriana Rosa',
                'inLanguage' => 'pt-BR',
            ],
            [
                '@type' => ['Organization', 'ProfessionalService'],
                '@id' => home_url('/#organization'),
                'name' => 'Locutora.com',
                'url' => home_url('/'),
                'foundingDate' => '2004',
                'founder' => ['@type' => 'Person', 'name' => 'Adriana Rosa'],
                'logo' => ['@type' => 'ImageObject', 'url' => get_template_directory_uri() . '/assets/images/logo-adriana-rosa.png'],
                'image' => get_template_directory_uri() . '/assets/images/intro.png',
                'email' => 'adrianarosa@locutora.com',
                'telephone' => '+55 11 98440-4171',
                'areaServed' => ['@type' => 'Country', 'name' => 'Brasil'],
                'sameAs' => [
                    'https://www.linkedin.com/in/adrianarosa-voiceover/',
                    'https://www.instagram.com/adriana.rosa_s',
                    'https://www.youtube.com/adrianalocutoracom',
                ],
            ],
        ],
    ];
    ?><script type="application/ld+json" id="locutora-rank-math-schema"><?php echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script><?php
}, 30);

add_filter('wp_robots', function (array $robots): array {
    if (locutora_is_temporary_environment()) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
        unset($robots['index'], $robots['follow']);
    }

    return $robots;
});

add_action('send_headers', function (): void {
    if (locutora_is_temporary_environment() && !headers_sent()) {
        header('X-Robots-Tag: noindex, nofollow', true);
    }
    if ((int) get_option('locutora_pending_cache_purge', 0) === 1 && !headers_sent()) {
        header('X-LiteSpeed-Purge: *', true);
        header('Cache-Control: no-cache, no-store, must-revalidate', true);
        delete_option('locutora_pending_cache_purge');
    }
});

remove_action('wp_head', 'wp_generator');

/* ─── Enqueue assets ─── */
add_action('wp_enqueue_scripts', function () {
    $v = wp_get_theme()->get('Version');

    wp_enqueue_style(
        'google-fonts',
        'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap',
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
    wp_enqueue_script('locutora-hero', get_template_directory_uri() . '/assets/js/hero.js', [], $v, true);
    wp_enqueue_script('locutora-cookie-consent', get_template_directory_uri() . '/assets/js/cookie-consent.js', [], $v, true);
});

function locutora_intro_block_default_content(): string {
    return '<h3>Locutora Profissional | Gravação de voz para publicidade, TV, rádio e URA</h3>'
        . '<p><strong>Se você procura uma locutora profissional para dar mais credibilidade e impacto à comunicação da sua empresa, está no lugar certo.</strong></p>'
        . '<p>Sou Adriana Rosa, locutora profissional atuando no mercado desde 2004, especializada em gravação de voz para campanhas publicitárias, vídeos institucionais, URA, espera telefônica, conteúdos corporativos e projetos digitais. Ao longo de mais de duas décadas de experiência, atendi empresas, agências e produtoras em todo o Brasil e exterior, sempre com qualidade profissional e entrega rápida.</p>'
        . '<h3>Voz para Publicidade, TV e Rádio</h3>'
        . '<p>Uma boa voz para publicidade faz toda a diferença na conexão com o público. Realizo locuções para campanhas promocionais, comerciais, lançamentos de produtos e ações de marketing, oferecendo uma voz marcante e alinhada à identidade da sua marca.</p>'
        . '<p>Também produzo gravações de voz para TV e rádio, criando mensagens claras, envolventes e profissionais para diferentes formatos de mídia.</p>'
        . '<h3>Serviços de Locução Profissional</h3>'
        . '<p>Gravação para publicidade, TV, rádio, URA, espera telefônica, vídeos institucionais, treinamentos, e-learning, campanhas e conteúdo digital.</p>'
        . '<ul><li>Gravação de voz para publicidade</li><li>Voz para TV e rádio</li><li>Gravação de URA profissional</li><li>Espera telefônica personalizada</li><li>Locução para vídeos institucionais</li><li>Gravação de URA e espera telefônica</li><li>Locução para treinamentos e e-learning</li><li>Campanhas promocionais e comerciais</li><li>Conteúdo para redes sociais e mídia digital</li></ul>'
        . '<h3>Locutora Profissional com Experiência Comprovada</h3>'
        . '<p>Desde 2004, desenvolvo projetos de locução para empresas de diversos segmentos, contribuindo para fortalecer marcas e melhorar a comunicação com clientes. Minha experiência permite adaptar a interpretação e o estilo de voz às necessidades de cada projeto.</p>'
        . '<h3>Qualidade Profissional e Atendimento Nacional</h3>'
        . '<p>Com estúdio próprio e equipamentos profissionais, realizo gravações de voz com alta qualidade técnica, garantindo excelente resultado para empresas que precisam de uma comunicação eficiente e profissional.</p>'
        . '<p>Solicite um orçamento e descubra como uma voz humana e profissional pode valorizar sua marca, sua campanha e seus projetos de comunicação.</p>';
}

function locutora_rich_heading(string $text, bool $preserve_lines = false): string {
    if ($preserve_lines && !preg_match('/<br\s*\/?>/i', $text)) {
        $text = nl2br($text);
    }

    return wp_kses($text, [
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'br' => [],
    ]);
}

function locutora_heading_style(array $attributes): string {
    $alignments = ['left', 'center', 'right'];
    $fonts = [
        'montserrat' => "'Montserrat', Arial, sans-serif",
        'arial' => 'Arial, sans-serif',
        'georgia' => "Georgia, 'Times New Roman', serif",
    ];
    $styles = [];
    $alignment = (string) ($attributes['titleAlign'] ?? '');
    $font = (string) ($attributes['titleFont'] ?? '');

    if (in_array($alignment, $alignments, true)) {
        $styles[] = 'text-align:' . $alignment;
    }
    if (isset($fonts[$font])) {
        $styles[] = 'font-family:' . $fonts[$font];
    }

    return implode(';', $styles);
}

function locutora_heading_style_attribute(array $attributes): string {
    $style = locutora_heading_style($attributes);
    return $style !== '' ? ' style="' . esc_attr($style) . '"' : '';
}

function locutora_render_hero_block(array $attributes): string {
    $eyebrow = $attributes['eyebrow'] ?? 'Locutora.com';
    $title = $attributes['title'] ?? 'Gravações profissionais';
    $subtitle = $attributes['subtitle'] ?? 'Adriana Rosa';
    $uri = get_template_directory_uri();

    ob_start(); ?>
    <section class="hero" id="hero">
      <?php foreach ([1, 2, 3] as $index) : ?>
        <video class="hero__video<?php echo $index === 1 ? ' is-active' : ''; ?>" <?php echo $index === 1 ? 'autoplay ' : ''; ?>muted playsinline preload="metadata" aria-hidden="true">
          <source src="<?php echo esc_url($uri . '/assets/video/vitrine-' . $index . '.mp4'); ?>" type="video/mp4">
        </video>
      <?php endforeach; ?>
      <div class="hero__overlay" aria-hidden="true"></div>
      <div class="hero__content">
        <p class="hero__eyebrow"><?php echo esc_html($eyebrow); ?></p>
        <h1 class="hero__title serif"<?php echo locutora_heading_style_attribute($attributes); ?>><?php echo locutora_rich_heading((string) $title); ?></h1>
        <p class="hero__sub"><?php echo esc_html($subtitle); ?></p>
      </div>
    </section>
    <?php return (string) ob_get_clean();
}

function locutora_render_intro_block(array $attributes): string {
    $title = $attributes['title'] ?? 'Locutora.com';
    $content = $attributes['content'] ?? locutora_intro_block_default_content();
    $button_label = $attributes['buttonLabel'] ?? 'Conheça';
    $button_url = $attributes['buttonUrl'] ?? '';
    $button_url = $button_url ?: home_url('/sobre-nos/');
    $portrait_url = $attributes['portraitUrl'] ?? '';
    $portrait_url = $portrait_url ?: get_template_directory_uri() . '/assets/images/intro.png';
    $portrait_alt = $attributes['portraitAlt'] ?? 'Adriana Rosa, locutora profissional';

    ob_start(); ?>
    <section class="legacy-intro" id="sobre">
      <div class="legacy-intro__copy reveal reveal--slide-top">
        <h2 class="section-title"<?php echo locutora_heading_style_attribute($attributes); ?>><?php echo locutora_rich_heading((string) $title); ?></h2>
        <?php echo wp_kses_post($content); ?>
        <a href="<?php echo esc_url($button_url); ?>" class="legacy-link reveal reveal--slide-bottom"><?php echo esc_html($button_label); ?></a>
      </div>
      <figure class="legacy-intro__portrait reveal reveal--fade">
        <img src="<?php echo esc_url($portrait_url); ?>" alt="<?php echo esc_attr($portrait_alt); ?>">
      </figure>
    </section>
    <?php return (string) ob_get_clean();
}

function locutora_render_services_block(array $attributes): string {
    $title = $attributes['title'] ?? 'Fazemos gravações para:';
    $services_url = $attributes['servicesUrl'] ?? '';
    $services_url = $services_url ?: home_url('/servicos/');
    $services = [
        [$attributes['item1'] ?? 'Comerciais', $attributes['icon1Url'] ?? '', 'servico-comerciais.webp'],
        [$attributes['item2'] ?? 'Emissoras de rádio e tv', $attributes['icon2Url'] ?? '', 'servico-tv.webp'],
        [$attributes['item3'] ?? 'Conteúdos para internet', $attributes['icon3Url'] ?? '', 'servico-internet.webp'],
        [$attributes['item4'] ?? 'E muito mais', $attributes['icon4Url'] ?? '', 'servico-mais.webp'],
    ];

    ob_start(); ?>
    <section class="services" id="servicos">
      <h2 class="section-title reveal reveal--fade"<?php echo locutora_heading_style_attribute($attributes); ?>><?php echo locutora_rich_heading((string) $title); ?></h2>
      <div class="services-grid">
        <?php foreach ($services as [$service_title, $icon_url, $fallback_icon]) : ?>
          <a class="service-item reveal reveal--fade" href="<?php echo esc_url($services_url); ?>">
            <img src="<?php echo esc_url($icon_url ?: get_template_directory_uri() . '/assets/images/' . $fallback_icon); ?>" alt="">
            <h3 class="service-item__title"><?php echo esc_html($service_title); ?></h3>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php return (string) ob_get_clean();
}

function locutora_render_contact_cta_block(array $attributes): string {
    $heading = $attributes['heading'] ?? "Entre em contato\ncom Adriana Rosa";
    $button_label = $attributes['buttonLabel'] ?? 'Contato';
    $button_url = $attributes['buttonUrl'] ?? '';
    $button_url = $button_url ?: home_url('/contato/');
    $video_url = $attributes['videoUrl'] ?? '';
    $video_url = $video_url ?: get_template_directory_uri() . '/assets/video/contato.mp4';

    ob_start(); ?>
    <section class="cta-block" id="contato">
      <video autoplay muted loop playsinline preload="metadata" aria-hidden="true">
        <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
      </video>
      <div class="cta-block__shade"></div>
      <div class="cta-block__content reveal reveal--fade">
        <h2<?php echo locutora_heading_style_attribute($attributes); ?>><?php echo locutora_rich_heading((string) $heading, true); ?></h2>
        <a href="<?php echo esc_url($button_url); ?>" class="btn-outline"><?php echo esc_html($button_label); ?></a>
      </div>
    </section>
    <?php return (string) ob_get_clean();
}

function locutora_render_internal_hero_block(array $attributes): string {
    $title = $attributes['title'] ?? '';
    $variant = in_array($attributes['variant'] ?? '', ['sobre', 'services', 'contact'], true) ? $attributes['variant'] : 'sobre';
    $background_url = $attributes['backgroundUrl'] ?? '';
    $style = $background_url ? ' style="background-image:url(' . esc_url($background_url) . ')"' : '';

    return '<section class="internal-hero internal-hero--' . esc_attr($variant) . '"' . $style . '>'
        . '<div class="internal-hero__inner"><h1' . locutora_heading_style_attribute($attributes) . '>' . locutora_rich_heading((string) $title) . '</h1></div></section>';
}

function locutora_about_story_default_content(): string {
    return '<h3>Missão</h3><p>Dar voz às marcas com criatividade, qualidade e atenção aos detalhes, criando conexões autênticas entre empresas e seus públicos.</p>'
        . '<h3>Visão</h3><p>Ser referência em locução profissional, reconhecida pela inovação e relacionamento próximo com cada cliente.</p>'
        . '<h3>Valores</h3><p>Excelência, inovação, personalização, profissionalismo, ética, comprometimento e respeito em cada projeto.</p>';
}

function locutora_render_about_story_block(array $attributes): string {
    $title = $attributes['title'] ?? "Fundada em 2004, em\nSão Paulo.";
    $title = preg_replace('/<br\s*\/?>/i', "\n", $title) ?: $title;
    $legacy_content = $attributes['content'] ?? locutora_about_story_default_content();
    $uses_legacy_content = $legacy_content !== locutora_about_story_default_content();
    $image_url = ($attributes['imageUrl'] ?? '') ?: get_template_directory_uri() . '/assets/images/internal/sobre-intro.png';
    $image_alt = $attributes['imageAlt'] ?? 'Mesa de áudio de estúdio profissional';
    ob_start(); ?>
    <section class="about-story about-story--history"><div class="about-story__row">
      <div class="about-story__copy reveal reveal--slide-top"><h2<?php echo locutora_heading_style_attribute($attributes); ?>><?php echo locutora_rich_heading((string) $title, true); ?></h2>
        <?php if ($uses_legacy_content) : ?>
          <?php echo wp_kses_post($legacy_content); ?>
        <?php else : ?>
          <h3><?php echo esc_html($attributes['missionTitle'] ?? 'Missão'); ?></h3>
          <p><?php echo esc_html($attributes['missionText'] ?? 'Dar voz às marcas com criatividade, qualidade e atenção aos detalhes, criando conexões autênticas entre empresas e seus públicos.'); ?></p>
          <h3><?php echo esc_html($attributes['visionTitle'] ?? 'Visão'); ?></h3>
          <p><?php echo esc_html($attributes['visionText'] ?? 'Ser referência em locução profissional, reconhecida pela inovação e relacionamento próximo com cada cliente.'); ?></p>
          <h3><?php echo esc_html($attributes['valuesTitle'] ?? 'Valores'); ?></h3>
          <p><?php echo esc_html($attributes['valuesText'] ?? 'Excelência, inovação, personalização, profissionalismo, ética, comprometimento e respeito em cada projeto.'); ?></p>
        <?php endif; ?>
      </div>
      <figure class="about-story__image reveal reveal--fade"><img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>"></figure>
    </div></section>
    <?php return (string) ob_get_clean();
}

function locutora_about_bio_default_content(): string {
    return implode('', array_map(static fn(string $paragraph): string => '<p>' . $paragraph . '</p>', locutora_about_bio_default_paragraphs()));
}

function locutora_about_bio_default_paragraphs(): array {
    return [
        'Adriana Rosa é uma locutora profissional brasileira, atriz, radialista e comunicadora de São Paulo, com mais de 20 anos de experiência em locução profissional para publicidade, rádio, televisão, internet e projetos corporativos. Reconhecida pela versatilidade e qualidade de sua voz, atende clientes de todo o Brasil e do mercado internacional.',
        'Formada em Produção Audiovisual e Teatro, Adriana também é radialista e possui pós-graduações em Influência Digital e Jornalismo Digital, unindo sólida formação acadêmica a ampla experiência prática no mercado da comunicação e do entretenimento.',
        'Especialista em locução em português do Brasil, atua em campanhas publicitárias, voice over, vídeos institucionais, treinamentos corporativos, e-learning, URA, conteúdos digitais e produções audiovisuais. Sua experiência permite entregar uma comunicação clara, envolvente e alinhada aos objetivos de cada marca.',
        'Como atriz profissional, Adriana Rosa oferece interpretações naturais, autênticas e versáteis, adaptando sua voz para diferentes estilos de locução: institucional, comercial, emocional, inspiracional, varejo, personagens e conteúdos corporativos.',
        'Sua trajetória inclui passagens por importantes emissoras de rádio, como a Rádio Trianon e a Novabrasil FM. Atualmente, é locutora da Classic Pan, pertencente ao Grupo Jovem Pan.',
        "Ao longo da carreira, já realizou trabalhos para grandes marcas nacionais e internacionais, entre elas: Amil, Santander, Bradesco, Globo, Prada, Apple, Danone, Banco da Amazônia, Mondial, Audi, Nespresso, Avon, Ticket, Netflix, Natura, Vivo, Volkswagen, GPA, 3M e McDonald's.",
    ];
}

function locutora_render_about_bio_block(array $attributes): string {
    $title = $attributes['title'] ?? 'Adriana Rosa';
    $legacy_content = $attributes['content'] ?? locutora_about_bio_default_content();
    $uses_legacy_content = $legacy_content !== locutora_about_bio_default_content();
    $paragraphs = locutora_about_bio_default_paragraphs();
    $image_url = ($attributes['imageUrl'] ?? '') ?: get_template_directory_uri() . '/assets/images/internal/adriana-rosa-retrato.jpg';
    $image_alt = $attributes['imageAlt'] ?? 'Retrato ilustrado de Adriana Rosa';
    ob_start(); ?>
    <section class="about-story about-story--bio"><div class="about-bio__row">
      <figure class="about-bio__image reveal reveal--fade"><img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>"></figure>
      <div class="about-bio__copy reveal reveal--slide-bottom"><h2<?php echo locutora_heading_style_attribute($attributes); ?>><?php echo locutora_rich_heading((string) $title); ?></h2>
        <?php if ($uses_legacy_content) : echo wp_kses_post($legacy_content); else : ?>
          <?php foreach ($paragraphs as $index => $paragraph) : ?><p><?php echo esc_html($attributes['paragraph' . ($index + 1)] ?? $paragraph); ?></p><?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div></section>
    <?php return (string) ob_get_clean();
}

function locutora_brand_asset_url(string $url): string {
    $brand_directory_url = get_template_directory_uri() . '/assets/images/brands/';
    if (!str_starts_with($url, $brand_directory_url)) {
        return $url;
    }

    $visible_assets = [
        'apple.png' => 'apple-visible.png',
        'Globo.png' => 'Globo-visible.png',
        'Claro.png' => 'Claro-visible.png',
        'boticario.png' => 'boticario-visible.png',
        'avon2.png' => 'avon2-visible.png',
    ];
    $filename = wp_basename((string) wp_parse_url($url, PHP_URL_PATH));
    if (isset($visible_assets[$filename])) {
        $url = str_replace('/' . $filename, '/' . $visible_assets[$filename], $url);
    }

    return add_query_arg('ver', wp_get_theme()->get('Version'), remove_query_arg('ver', $url));
}

function locutora_default_brand_urls(): array {
    $names = ['apple','Liza','santander','Globo','Claro','boticario','Adria2','bradesco','3m','natura','cielo','amil','avon2','viacredi','mcdonalds','neoenergia','danone','paodeaçucar','boston2','Vivo','netflix','Nespresso'];
    return array_map(static fn(string $name): string => locutora_brand_asset_url(get_template_directory_uri() . '/assets/images/brands/' . $name . '.png'), $names);
}

function locutora_render_brands_block(array $attributes): string {
    $title = $attributes['title'] ?? 'Conheça as marcas que já trabalhou';
    $images = !empty($attributes['images']) && is_array($attributes['images']) ? $attributes['images'] : locutora_default_brand_urls();
    ob_start(); ?>
    <section class="brand-showcase"><h2<?php echo locutora_heading_style_attribute($attributes); ?>><?php echo locutora_rich_heading((string) $title); ?></h2><div class="brand-grid">
      <?php foreach ($images as $index => $url) : ?><img src="<?php echo esc_url(locutora_brand_asset_url((string) $url)); ?>" alt="Marca <?php echo esc_attr((string) ($index + 1)); ?>" loading="lazy"><?php endforeach; ?>
    </div></section>
    <?php return (string) ob_get_clean();
}

function locutora_audio_showcase_soundcloud_embed_url(string $url): string {
    $url = trim($url);
    if ($url === '') {
        $url = 'https://soundcloud.com/adrianarosalocutora';
    }
    if (str_contains($url, 'w.soundcloud.com/player/')) {
        return $url;
    }
    return 'https://w.soundcloud.com/player/?url=' . rawurlencode($url) . '&auto_play=false&show_artwork=true&color=ff0035';
}

function locutora_youtube_embed_url(string $url): string {
    $url = trim($url);
    if ($url === '') {
        return 'https://www.youtube.com/embed/videoseries?list=PLTqOomsLyDw3eTbCCkSR-h9lI0iH3wPQc';
    }
    if (str_contains($url, '/embed/')) {
        return $url;
    }

    $query = [];
    parse_str((string) wp_parse_url($url, PHP_URL_QUERY), $query);
    if (!empty($query['list'])) {
        return 'https://www.youtube.com/embed/videoseries?list=' . rawurlencode((string) $query['list']);
    }

    $video_id = $query['v'] ?? '';
    if ($video_id === '' && str_contains((string) wp_parse_url($url, PHP_URL_HOST), 'youtu.be')) {
        $video_id = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');
    }
    return $video_id !== '' ? 'https://www.youtube.com/embed/' . rawurlencode((string) $video_id) : $url;
}

function locutora_render_audio_showcase_block(array $attributes): string {
    $title = $attributes['title'] ?? "Locutora para URA, comerciais, institucionais, tutoriais; e\nvoz padrão para rádio e TV.";
    $title = preg_replace('/<br\s*\/?>/i', "\n", $title) ?: $title;
    $soundcloud = locutora_audio_showcase_soundcloud_embed_url((string) ($attributes['soundcloudUrl'] ?? ''));
    $youtube = locutora_youtube_embed_url((string) ($attributes['youtubeUrl'] ?? ''));
    $background_url = trim((string) ($attributes['backgroundUrl'] ?? ''));
    $section_style = $background_url !== '' ? 'background-image:url(' . esc_url_raw($background_url) . ')' : '';
    ob_start(); ?>
    <section class="audio-showcase"<?php echo $section_style !== '' ? ' style="' . esc_attr($section_style) . '"' : ''; ?>><h2<?php echo locutora_heading_style_attribute($attributes); ?>><?php echo locutora_rich_heading((string) $title, true); ?></h2><div class="audio-showcase__grid">
      <iframe title="Demos de Adriana Rosa no SoundCloud" loading="lazy" allow="autoplay" src="<?php echo esc_url($soundcloud); ?>"></iframe>
      <iframe title="Vídeos de locução institucional" loading="lazy" src="<?php echo esc_url($youtube); ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    </div></section>
    <?php return (string) ob_get_clean();
}

function locutora_render_contact_form_block(array $attributes): string {
    $button_label = $attributes['buttonLabel'] ?? 'Enviar mensagem';
    ob_start(); ?>
    <section class="contact-section"><form class="contact-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="locutora_contact"><?php wp_nonce_field('locutora_contact', 'locutora_contact_nonce'); ?>
      <label><?php echo esc_html($attributes['nameLabel'] ?? 'Nome *'); ?><input name="nome" type="text" required autocomplete="name"></label>
      <label><?php echo esc_html($attributes['emailLabel'] ?? 'E-mail *'); ?><input name="email" type="email" required autocomplete="email"></label>
      <label><?php echo esc_html($attributes['phoneLabel'] ?? 'Telefone'); ?><input name="telefone" type="tel" autocomplete="tel"></label>
      <label><?php echo esc_html($attributes['subjectLabel'] ?? 'Assunto *'); ?><input name="assunto" type="text" required></label>
      <label><?php echo esc_html($attributes['messageLabel'] ?? 'Mensagem'); ?><textarea name="mensagem" rows="7"></textarea></label>
      <button type="submit"><?php echo esc_html($button_label); ?></button>
      <?php if (isset($_GET['enviado'])) : ?><p class="contact-form__feedback" role="status"><?php echo $_GET['enviado'] === '1' ? 'Mensagem enviada com sucesso.' : 'Não foi possível enviar. Tente novamente.'; ?></p><?php endif; ?>
    </form></section>
    <?php return (string) ob_get_clean();
}

add_action('init', function (): void {
    $version = wp_get_theme()->get('Version');
    wp_register_script(
        'locutora-blocks-editor',
        get_template_directory_uri() . '/assets/js/blocks-editor.js',
        ['wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render'],
        $version,
        true
    );

    $blocks = [
        'locutora/hero' => [
            'render_callback' => 'locutora_render_hero_block',
            'attributes' => [
                'eyebrow' => ['type' => 'string', 'default' => 'Locutora.com'],
                'title' => ['type' => 'string', 'default' => 'Gravações profissionais'],
                'subtitle' => ['type' => 'string', 'default' => 'Adriana Rosa'],
            ],
        ],
        'locutora/intro' => [
            'render_callback' => 'locutora_render_intro_block',
            'attributes' => [
                'title' => ['type' => 'string', 'default' => 'Locutora.com'],
                'content' => ['type' => 'string', 'default' => locutora_intro_block_default_content()],
                'buttonLabel' => ['type' => 'string', 'default' => 'Conheça'],
                'buttonUrl' => ['type' => 'string', 'default' => ''],
                'portraitUrl' => ['type' => 'string', 'default' => ''],
                'portraitAlt' => ['type' => 'string', 'default' => 'Adriana Rosa, locutora profissional'],
            ],
        ],
        'locutora/services' => [
            'render_callback' => 'locutora_render_services_block',
            'attributes' => [
                'title' => ['type' => 'string', 'default' => 'Fazemos gravações para:'],
                'item1' => ['type' => 'string', 'default' => 'Comerciais'],
                'item2' => ['type' => 'string', 'default' => 'Emissoras de rádio e tv'],
                'item3' => ['type' => 'string', 'default' => 'Conteúdos para internet'],
                'item4' => ['type' => 'string', 'default' => 'E muito mais'],
                'servicesUrl' => ['type' => 'string', 'default' => ''],
                'icon1Url' => ['type' => 'string', 'default' => ''],
                'icon2Url' => ['type' => 'string', 'default' => ''],
                'icon3Url' => ['type' => 'string', 'default' => ''],
                'icon4Url' => ['type' => 'string', 'default' => ''],
            ],
        ],
        'locutora/contact-cta' => [
            'render_callback' => 'locutora_render_contact_cta_block',
            'attributes' => [
                'heading' => ['type' => 'string', 'default' => "Entre em contato\ncom Adriana Rosa"],
                'buttonLabel' => ['type' => 'string', 'default' => 'Contato'],
                'buttonUrl' => ['type' => 'string', 'default' => ''],
                'videoUrl' => ['type' => 'string', 'default' => ''],
            ],
        ],
        'locutora/internal-hero' => [
            'render_callback' => 'locutora_render_internal_hero_block',
            'attributes' => [
                'title' => ['type' => 'string', 'default' => ''],
                'variant' => ['type' => 'string', 'default' => 'sobre'],
                'backgroundUrl' => ['type' => 'string', 'default' => ''],
            ],
        ],
        'locutora/about-story' => [
            'render_callback' => 'locutora_render_about_story_block',
            'attributes' => [
                'title' => ['type' => 'string', 'default' => "Fundada em 2004, em\nSão Paulo."],
                'content' => ['type' => 'string', 'default' => locutora_about_story_default_content()],
                'missionTitle' => ['type' => 'string', 'default' => 'Missão'],
                'missionText' => ['type' => 'string', 'default' => 'Dar voz às marcas com criatividade, qualidade e atenção aos detalhes, criando conexões autênticas entre empresas e seus públicos.'],
                'visionTitle' => ['type' => 'string', 'default' => 'Visão'],
                'visionText' => ['type' => 'string', 'default' => 'Ser referência em locução profissional, reconhecida pela inovação e relacionamento próximo com cada cliente.'],
                'valuesTitle' => ['type' => 'string', 'default' => 'Valores'],
                'valuesText' => ['type' => 'string', 'default' => 'Excelência, inovação, personalização, profissionalismo, ética, comprometimento e respeito em cada projeto.'],
                'imageUrl' => ['type' => 'string', 'default' => ''],
                'imageAlt' => ['type' => 'string', 'default' => 'Mesa de áudio de estúdio profissional'],
            ],
        ],
        'locutora/about-bio' => [
            'render_callback' => 'locutora_render_about_bio_block',
            'attributes' => [
                'title' => ['type' => 'string', 'default' => 'Adriana Rosa'],
                'content' => ['type' => 'string', 'default' => locutora_about_bio_default_content()],
                'paragraph1' => ['type' => 'string', 'default' => locutora_about_bio_default_paragraphs()[0]],
                'paragraph2' => ['type' => 'string', 'default' => locutora_about_bio_default_paragraphs()[1]],
                'paragraph3' => ['type' => 'string', 'default' => locutora_about_bio_default_paragraphs()[2]],
                'paragraph4' => ['type' => 'string', 'default' => locutora_about_bio_default_paragraphs()[3]],
                'paragraph5' => ['type' => 'string', 'default' => locutora_about_bio_default_paragraphs()[4]],
                'paragraph6' => ['type' => 'string', 'default' => locutora_about_bio_default_paragraphs()[5]],
                'imageUrl' => ['type' => 'string', 'default' => ''],
                'imageAlt' => ['type' => 'string', 'default' => 'Retrato ilustrado de Adriana Rosa'],
            ],
        ],
        'locutora/brands' => [
            'render_callback' => 'locutora_render_brands_block',
            'attributes' => [
                'title' => ['type' => 'string', 'default' => 'Conheça as marcas que já trabalhou'],
                'images' => ['type' => 'array', 'default' => []],
            ],
        ],
        'locutora/audio-showcase' => [
            'render_callback' => 'locutora_render_audio_showcase_block',
            'attributes' => [
                'title' => ['type' => 'string', 'default' => "Locutora para URA, comerciais, institucionais, tutoriais; e\nvoz padrão para rádio e TV."],
                'soundcloudUrl' => ['type' => 'string', 'default' => 'https://soundcloud.com/adrianarosalocutora'],
                'youtubeUrl' => ['type' => 'string', 'default' => 'https://www.youtube.com/playlist?list=PLTqOomsLyDw3eTbCCkSR-h9lI0iH3wPQc'],
                'backgroundUrl' => ['type' => 'string', 'default' => ''],
            ],
        ],
        'locutora/contact-form' => [
            'render_callback' => 'locutora_render_contact_form_block',
            'attributes' => [
                'nameLabel' => ['type' => 'string', 'default' => 'Nome *'],
                'emailLabel' => ['type' => 'string', 'default' => 'E-mail *'],
                'phoneLabel' => ['type' => 'string', 'default' => 'Telefone'],
                'subjectLabel' => ['type' => 'string', 'default' => 'Assunto *'],
                'messageLabel' => ['type' => 'string', 'default' => 'Mensagem'],
                'buttonLabel' => ['type' => 'string', 'default' => 'Enviar mensagem'],
            ],
        ],
    ];

    foreach ($blocks as $name => $settings) {
        $settings['attributes']['titleAlign'] = ['type' => 'string', 'default' => ''];
        $settings['attributes']['titleFont'] = ['type' => 'string', 'default' => ''];
        register_block_type($name, array_merge($settings, [
            'api_version' => 3,
            'editor_script' => 'locutora-blocks-editor',
        ]));
    }
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

/* ─── CPT: Serviços (editável pela administradora) ─── */
add_action('init', function () {
    register_post_type('servico', [
        'labels' => [
            'name'          => 'Serviços',
            'singular_name' => 'Serviço',
            'add_new_item'  => 'Adicionar serviço',
            'edit_item'     => 'Editar serviço',
            'not_found'     => 'Nenhum serviço cadastrado',
        ],
        'public'       => false,
        'show_ui'      => true,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-megaphone',
        'supports'     => ['title', 'editor', 'page-attributes'],
    ]);
});

/* ─── Conteúdo principal no Personalizador nativo ─── */
add_action('customize_register', function (WP_Customize_Manager $customizer) {
    $customizer->add_section('locutora_content', [
        'title'       => 'Conteúdo da página inicial',
        'description' => 'Edite os textos principais sem alterar o tema.',
        'priority'    => 30,
    ]);

    $fields = [
        'locutora_hero_titulo' => ['Título do destaque', 'Gravações profissionais', 'text'],
        'locutora_hero_sub' => ['Nome no destaque', 'Adriana Rosa', 'text'],
        'locutora_intro_titulo' => ['Título da apresentação', 'Locutora.com', 'text'],
        'locutora_intro_chamada' => ['Chamada da apresentação', 'Gravação de voz para publicidade, TV, rádio e URA', 'text'],
        'locutora_intro_texto_1' => ['Primeiro parágrafo', 'Sou Adriana Rosa, locutora profissional atuando no mercado desde 2004, especializada em gravação de voz para campanhas publicitárias, vídeos institucionais, URA, espera telefônica, conteúdos corporativos e projetos digitais.', 'textarea'],
        'locutora_intro_texto_2' => ['Segundo parágrafo', 'Ao longo de mais de duas décadas de experiência, atendi empresas, agências e produtoras em todo o Brasil e exterior, sempre com qualidade profissional e entrega rápida.', 'textarea'],
        'locutora_whatsapp' => ['WhatsApp com DDI e DDD', '5511984404171', 'text'],
        'locutora_depoimento' => ['Depoimento', 'Precisava de uma voz que passasse seriedade sem ser fria. Ela entregou exatamente isso — e antes do prazo.', 'textarea'],
        'locutora_depoimento_autor' => ['Autor do depoimento', 'Cliente Locutora.com', 'text'],
    ];

    foreach ($fields as $id => [$label, $default, $type]) {
        $customizer->add_setting($id, [
            'default'           => $default,
            'sanitize_callback' => $type === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field',
        ]);
        $customizer->add_control($id, [
            'section' => 'locutora_content',
            'label'   => $label,
            'type'    => $type,
        ]);
    }
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

/* ─── Configurações estruturadas com ACF gratuito ─── */
add_action('init', function (): void {
    register_post_type('locutora_config', [
        'labels' => [
            'name' => 'Configurações do site',
            'singular_name' => 'Configurações do site',
            'edit_item' => 'Editar configurações do site',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-admin-settings',
        'supports' => ['title'],
        'capability_type' => 'page',
        'map_meta_cap' => true,
    ]);
}, 9);

function locutora_config_post_id(): int {
    $saved_id = (int) get_option('locutora_config_post_id', 0);
    $saved_post = $saved_id > 0 ? get_post($saved_id) : null;
    if ($saved_post instanceof WP_Post && $saved_post->post_type === 'locutora_config') {
        return $saved_id;
    }
    $posts = get_posts([
        'post_type' => 'locutora_config',
        'post_status' => 'private',
        'numberposts' => 1,
        'fields' => 'ids',
    ]);
    $post_id = isset($posts[0]) ? (int) $posts[0] : 0;
    if ($post_id > 0) {
        update_option('locutora_config_post_id', $post_id, false);
    }
    return $post_id;
}

add_action('init', function (): void {
    if (!post_type_exists('locutora_config') || locutora_config_post_id() > 0) {
        return;
    }
    $post_id = wp_insert_post([
        'post_type' => 'locutora_config',
        'post_status' => 'private',
        'post_title' => 'Configurações da Locutora',
    ]);
    if (!is_wp_error($post_id) && $post_id > 0) {
        update_option('locutora_config_post_id', (int) $post_id, false);
    }
}, 20);

function locutora_setting(string $field, $fallback = '') {
    $post_id = locutora_config_post_id();
    if ($post_id > 0 && function_exists('get_field')) {
        $value = get_field($field, $post_id);
        if ($value !== null && $value !== '' && $value !== false) {
            return $value;
        }
    }
    return $fallback;
}

add_action('acf/init', function (): void {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    acf_add_local_field_group([
        'key' => 'group_locutora_site_settings',
        'title' => 'Dados exibidos no site',
        'fields' => [
            ['key' => 'field_locutora_footer_logo', 'label' => 'Logo do rodapé', 'name' => 'footer_logo', 'type' => 'image', 'return_format' => 'url', 'preview_size' => 'medium'],
            ['key' => 'field_locutora_phone', 'label' => 'Telefone', 'name' => 'contact_phone', 'type' => 'text', 'default_value' => '(11) 98440-4171'],
            ['key' => 'field_locutora_whatsapp', 'label' => 'Link do WhatsApp', 'name' => 'whatsapp_url', 'type' => 'url', 'default_value' => 'https://wa.me/5511984404171?text=Entro%20em%20contato%20atrav%C3%A9s%20do%20site'],
            ['key' => 'field_locutora_email_primary', 'label' => 'E-mail principal', 'name' => 'email_primary', 'type' => 'email', 'default_value' => 'adrianarosa@locutora.com'],
            ['key' => 'field_locutora_email_secondary', 'label' => 'E-mail secundário', 'name' => 'email_secondary', 'type' => 'email', 'default_value' => 'adrianarosa.voz@gmail.com'],
            ['key' => 'field_locutora_linkedin', 'label' => 'LinkedIn', 'name' => 'linkedin_url', 'type' => 'url', 'default_value' => 'https://www.linkedin.com/in/adrianarosa-voiceover/'],
            ['key' => 'field_locutora_instagram', 'label' => 'Instagram', 'name' => 'instagram_url', 'type' => 'url', 'default_value' => 'https://www.instagram.com/adriana.rosa_s'],
            ['key' => 'field_locutora_youtube', 'label' => 'YouTube', 'name' => 'youtube_url', 'type' => 'url', 'default_value' => 'https://www.youtube.com/adrianalocutoracom'],
            ['key' => 'field_locutora_copyright', 'label' => 'Ano dos direitos autorais', 'name' => 'copyright_year', 'type' => 'number', 'default_value' => 2026, 'min' => 2004, 'max' => 2100],
            ['key' => 'field_locutora_social_image', 'label' => 'Imagem social padrão', 'name' => 'social_image', 'type' => 'image', 'return_format' => 'url', 'preview_size' => 'medium'],
        ],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'locutora_config']]],
        'position' => 'normal',
        'style' => 'seamless',
    ]);
});

/* ─── Formulário de contato ─── */
function locutora_handle_contact(): void {
    if (!isset($_POST['locutora_contact_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['locutora_contact_nonce'])), 'locutora_contact')) {
        wp_die('Solicitação inválida.', 403);
    }

    $nome = sanitize_text_field(wp_unslash($_POST['nome'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $telefone = sanitize_text_field(wp_unslash($_POST['telefone'] ?? ''));
    $assunto = sanitize_text_field(wp_unslash($_POST['assunto'] ?? ''));
    $mensagem = sanitize_textarea_field(wp_unslash($_POST['mensagem'] ?? ''));
    $ok = $nome && is_email($email) && $assunto && wp_mail(
        get_option('admin_email'),
        '[Locutora.com] ' . $assunto,
        "Nome: {$nome}\nE-mail: {$email}\nTelefone: {$telefone}\n\n{$mensagem}",
        ['Reply-To: ' . $nome . ' <' . $email . '>']
    );

    wp_safe_redirect(add_query_arg('enviado', $ok ? '1' : '0', home_url('/contato/')));
    exit;
}
add_action('admin_post_nopriv_locutora_contact', 'locutora_handle_contact');
add_action('admin_post_locutora_contact', 'locutora_handle_contact');
