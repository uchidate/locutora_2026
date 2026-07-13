<?php
defined('ABSPATH') || exit;

if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

const LOCUTORA_SITE_CONFIG_VERSION = 5;

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
        if (!$page instanceof WP_Post || trim((string) $page->post_content) !== '') {
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
    locutora_ensure_structural_page('orcamento', 'Orçamento', 'page-orcamento.php');

    update_option('blogname', 'Adriana Rosa');
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

    $sample_page = get_page_by_path('sample-page', OBJECT, 'page');
    if ($sample_page instanceof WP_Post && $sample_page->post_title === 'Sample Page') {
        wp_trash_post($sample_page->ID);
    }

    $hello_world = get_page_by_path('hello-world', OBJECT, 'post');
    if ($hello_world instanceof WP_Post && $hello_world->post_title === 'Hello world!') {
        wp_trash_post($hello_world->ID);
    }

    update_option('locutora_site_config_version', LOCUTORA_SITE_CONFIG_VERSION, false);
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
        <h1 class="hero__title serif"><?php echo esc_html($title); ?></h1>
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
        <h2 class="section-title"><?php echo esc_html($title); ?></h2>
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
      <h2 class="section-title reveal reveal--fade"><?php echo esc_html($title); ?></h2>
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
        <h2><?php echo nl2br(esc_html($heading)); ?></h2>
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
        . '<div class="internal-hero__inner"><h1>' . esc_html($title) . '</h1></div></section>';
}

function locutora_about_story_default_content(): string {
    return '<h3>Missão</h3><p>Dar voz às marcas com criatividade, qualidade e atenção aos detalhes, criando conexões autênticas entre empresas e seus públicos.</p>'
        . '<h3>Visão</h3><p>Ser referência em locução profissional, reconhecida pela inovação e relacionamento próximo com cada cliente.</p>'
        . '<h3>Valores</h3><p>Excelência, inovação, personalização, profissionalismo, ética, comprometimento e respeito em cada projeto.</p>';
}

function locutora_render_about_story_block(array $attributes): string {
    $title = $attributes['title'] ?? 'Fundada em 2004, em<br>São Paulo.';
    $content = $attributes['content'] ?? locutora_about_story_default_content();
    $image_url = ($attributes['imageUrl'] ?? '') ?: get_template_directory_uri() . '/assets/images/internal/sobre-intro.png';
    $image_alt = $attributes['imageAlt'] ?? 'Mesa de áudio de estúdio profissional';
    ob_start(); ?>
    <section class="about-story about-story--history"><div class="about-story__row">
      <div class="about-story__copy reveal reveal--slide-top"><h2><?php echo wp_kses($title, ['br' => []]); ?></h2><?php echo wp_kses_post($content); ?></div>
      <figure class="about-story__image reveal reveal--fade"><img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>"></figure>
    </div></section>
    <?php return (string) ob_get_clean();
}

function locutora_about_bio_default_content(): string {
    return '<p>Adriana Rosa é uma locutora profissional brasileira, atriz, radialista e comunicadora de São Paulo, com mais de 20 anos de experiência em locução profissional para publicidade, rádio, televisão, internet e projetos corporativos. Reconhecida pela versatilidade e qualidade de sua voz, atende clientes de todo o Brasil e do mercado internacional.</p>'
        . '<p>Formada em Produção Audiovisual e Teatro, Adriana também é radialista e possui pós-graduações em Influência Digital e Jornalismo Digital, unindo sólida formação acadêmica a ampla experiência prática no mercado da comunicação e do entretenimento.</p>'
        . '<p>Especialista em locução em português do Brasil, atua em campanhas publicitárias, voice over, vídeos institucionais, treinamentos corporativos, e-learning, URA, conteúdos digitais e produções audiovisuais. Sua experiência permite entregar uma comunicação clara, envolvente e alinhada aos objetivos de cada marca.</p>'
        . '<p>Como atriz profissional, Adriana Rosa oferece interpretações naturais, autênticas e versáteis, adaptando sua voz para diferentes estilos de locução: institucional, comercial, emocional, inspiracional, varejo, personagens e conteúdos corporativos.</p>'
        . '<p>Sua trajetória inclui passagens por importantes emissoras de rádio, como a Rádio Trianon e a Novabrasil FM. Atualmente, é locutora da Classic Pan, pertencente ao Grupo Jovem Pan.</p>'
        . '<p>Ao longo da carreira, já realizou trabalhos para grandes marcas nacionais e internacionais, entre elas: Amil, Santander, Bradesco, Globo, Prada, Apple, Danone, Banco da Amazônia, Mondial, Audi, Nespresso, Avon, Ticket, Netflix, Natura, Vivo, Volkswagen, GPA, 3M e McDonald\'s.</p>';
}

function locutora_render_about_bio_block(array $attributes): string {
    $title = $attributes['title'] ?? 'Adriana Rosa';
    $content = $attributes['content'] ?? locutora_about_bio_default_content();
    $image_url = ($attributes['imageUrl'] ?? '') ?: get_template_directory_uri() . '/assets/images/internal/adriana-rosa-retrato.jpg';
    $image_alt = $attributes['imageAlt'] ?? 'Retrato ilustrado de Adriana Rosa';
    ob_start(); ?>
    <section class="about-story about-story--bio"><div class="about-bio__row">
      <figure class="about-bio__image reveal reveal--fade"><img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>"></figure>
      <div class="about-bio__copy reveal reveal--slide-bottom"><h2><?php echo esc_html($title); ?></h2><?php echo wp_kses_post($content); ?></div>
    </div></section>
    <?php return (string) ob_get_clean();
}

function locutora_default_brand_urls(): array {
    $names = ['apple','Liza','santander','Globo','Claro','boticario','Adria2','bradesco','3m','natura','cielo','amil','avon2','viacredi','mcdonalds','neoenergia','danone','paodeaçucar','boston2','Vivo','netflix','Nespresso'];
    return array_map(static fn(string $name): string => get_template_directory_uri() . '/assets/images/brands/' . $name . '.png', $names);
}

function locutora_render_brands_block(array $attributes): string {
    $title = $attributes['title'] ?? 'Conheça as marcas que já trabalhou';
    $images = !empty($attributes['images']) && is_array($attributes['images']) ? $attributes['images'] : locutora_default_brand_urls();
    ob_start(); ?>
    <section class="brand-showcase"><h2><?php echo esc_html($title); ?></h2><div class="brand-grid">
      <?php foreach ($images as $index => $url) : ?><img src="<?php echo esc_url($url); ?>" alt="Marca <?php echo esc_attr((string) ($index + 1)); ?>" loading="lazy"><?php endforeach; ?>
    </div></section>
    <?php return (string) ob_get_clean();
}

function locutora_render_audio_showcase_block(array $attributes): string {
    $title = $attributes['title'] ?? 'Locutora para URA, comerciais, institucionais, tutoriais; e<br>voz padrão para rádio e TV.';
    $soundcloud = $attributes['soundcloudUrl'] ?? 'https://w.soundcloud.com/player/?url=http%3A%2F%2Fapi.soundcloud.com%2Fusers%2F12694227&auto_play=false&show_artwork=true&color=ff0035';
    $youtube = $attributes['youtubeUrl'] ?? 'https://www.youtube.com/embed/videoseries?list=PLTqOomsLyDw3eTbCCkSR-h9lI0iH3wPQc';
    ob_start(); ?>
    <section class="audio-showcase"><h2><?php echo wp_kses($title, ['br' => []]); ?></h2><div class="audio-showcase__grid">
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
                'title' => ['type' => 'string', 'default' => 'Fundada em 2004, em<br>São Paulo.'],
                'content' => ['type' => 'string', 'default' => locutora_about_story_default_content()],
                'imageUrl' => ['type' => 'string', 'default' => ''],
                'imageAlt' => ['type' => 'string', 'default' => 'Mesa de áudio de estúdio profissional'],
            ],
        ],
        'locutora/about-bio' => [
            'render_callback' => 'locutora_render_about_bio_block',
            'attributes' => [
                'title' => ['type' => 'string', 'default' => 'Adriana Rosa'],
                'content' => ['type' => 'string', 'default' => locutora_about_bio_default_content()],
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
                'title' => ['type' => 'string', 'default' => 'Locutora para URA, comerciais, institucionais, tutoriais; e<br>voz padrão para rádio e TV.'],
                'soundcloudUrl' => ['type' => 'string', 'default' => 'https://w.soundcloud.com/player/?url=http%3A%2F%2Fapi.soundcloud.com%2Fusers%2F12694227&auto_play=false&show_artwork=true&color=ff0035'],
                'youtubeUrl' => ['type' => 'string', 'default' => 'https://www.youtube.com/embed/videoseries?list=PLTqOomsLyDw3eTbCCkSR-h9lI0iH3wPQc'],
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
