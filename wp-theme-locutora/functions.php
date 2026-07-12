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

    if ($home_id > 0) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $home_id);
    }

    if ($privacy_id > 0) {
        update_option('page_for_privacy_policy', $privacy_id);
    }

    flush_rewrite_rules(false);
}
add_action('after_switch_theme', 'locutora_configure_site_on_activation');

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
