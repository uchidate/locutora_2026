<?php get_header(); ?>

<?php
// Hero: MP3 via ACF ou meta
$hero_audio_field = function_exists('get_field') ? get_field('hero_audio', 'option') : null;
$hero_audio = is_array($hero_audio_field) ? ($hero_audio_field['url'] ?? '') : get_option('locutora_hero_audio', '');

// Hero: URL do SoundCloud (fallback quando não há MP3)
$hero_sc_url = function_exists('get_field')
  ? (get_field('hero_soundcloud_url', 'option') ?? '')
  : get_option('locutora_hero_sc_url', 'https://soundcloud.com/adrianarosalocutora');

$hero_dur = function_exists('get_field')
  ? (get_field('hero_duracao', 'option') ?? '0:48')
  : get_option('locutora_hero_duracao', '0:48');

$cta_url = get_permalink(get_page_by_path('orcamento')) ?: '#orcamento';
?>

<!-- ══ HERO ══ -->
<section class="hero" id="hero">
  <video class="hero__video is-active" autoplay muted playsinline preload="metadata" aria-hidden="true">
    <source src="<?php echo esc_url(get_template_directory_uri() . '/assets/video/vitrine-1.mp4'); ?>" type="video/mp4">
  </video>
  <video class="hero__video" muted playsinline preload="metadata" aria-hidden="true">
    <source src="<?php echo esc_url(get_template_directory_uri() . '/assets/video/vitrine-2.mp4'); ?>" type="video/mp4">
  </video>
  <video class="hero__video" muted playsinline preload="metadata" aria-hidden="true">
    <source src="<?php echo esc_url(get_template_directory_uri() . '/assets/video/vitrine-3.mp4'); ?>" type="video/mp4">
  </video>
  <div class="hero__overlay" aria-hidden="true"></div>
  <div class="hero__content">
  <p class="hero__eyebrow">Locutora.com</p>

  <h1 class="hero__title serif">
    <?php echo wp_kses_post(
      get_theme_mod('locutora_hero_titulo', 'Gravações profissionais')
    ); ?>
  </h1>

  <p class="hero__sub">
    <?php echo esc_html(get_theme_mod(
      'locutora_hero_sub',
      'Adriana Rosa'
    )); ?>
  </p>

  </div>

  <?php if ($hero_audio) : ?>
  <!-- Hero player — WaveSurfer (MP3) -->
  <div class="hero-player">
    <button class="hero-player__btn" id="hero-play" data-src="<?php echo esc_url($hero_audio); ?>" aria-label="Reproduzir demo reel">
      <svg width="20" height="22" viewBox="0 0 20 22" fill="#0F0E0C" aria-hidden="true">
        <path d="M0 0v22l20-11z"/>
      </svg>
    </button>
    <div class="hero-player__wave" id="hero-wave" aria-hidden="true"></div>
    <div class="hero-player__meta">
      <p class="hero-player__label">Demo reel</p>
      <p class="hero-player__time mono" id="hero-time"><?php echo esc_html($hero_dur); ?></p>
    </div>
  </div>

  <?php elseif ($hero_sc_url) : ?>
  <!-- Hero player — SoundCloud embed -->
  <div class="hero-player hero-player--sc">
    <div class="hero-player__sc-label">
      <p class="hero-player__label">Demo reel</p>
      <p class="hero-player__sub-label">via SoundCloud</p>
    </div>
    <iframe
      class="hero-sc-iframe"
      scrolling="no"
      frameborder="no"
      allow="autoplay; encrypted-media"
      loading="lazy"
      src="<?php echo esc_url(locutora_soundcloud_embed_url($hero_sc_url, false)); ?>">
    </iframe>
  </div>
  <?php endif; ?>
</section>

<!-- ══ APRESENTAÇÃO ══ -->
<section class="legacy-intro" id="sobre">
  <div class="legacy-intro__copy">
    <p class="section-eyebrow">Locutora profissional</p>
    <h2 class="section-title"><?php echo esc_html(get_theme_mod('locutora_intro_titulo', 'Locutora.com')); ?></h2>
    <h3><?php echo esc_html(get_theme_mod('locutora_intro_chamada', 'Gravação de voz para publicidade, TV, rádio e URA')); ?></h3>
    <p><?php echo esc_html(get_theme_mod('locutora_intro_texto_1', 'Sou Adriana Rosa, locutora profissional atuando no mercado desde 2004, especializada em gravação de voz para campanhas publicitárias, vídeos institucionais, URA, espera telefônica, conteúdos corporativos e projetos digitais.')); ?></p>
    <p><?php echo esc_html(get_theme_mod('locutora_intro_texto_2', 'Ao longo de mais de duas décadas de experiência, atendi empresas, agências e produtoras em todo o Brasil e exterior, sempre com qualidade profissional e entrega rápida.')); ?></p>
    <a href="<?php echo esc_url(get_permalink(get_page_by_path('sobre')) ?: '#sobre'); ?>" class="legacy-link">Conheça Adriana Rosa →</a>
  </div>
  <figure class="legacy-intro__portrait">
    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/adriana-rosa.jpg'); ?>" alt="Adriana Rosa em estúdio">
  </figure>
</section>

<!-- ══ LOGOS ══ -->
<section class="logos-strip" aria-label="Marcas atendidas">
  <span class="logos-strip__label">Já deram voz a</span>
  <div class="logos-strip__items">
    <?php foreach (locutora_get_clientes() as $cliente) : ?>
      <span><?php echo esc_html($cliente['nome']); ?></span>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══ DEMOS ══ -->
<section class="portfolio" id="demos">
  <div class="section-header">
    <div>
      <p class="section-eyebrow">Portfólio</p>
      <h2 class="section-title">Demos selecionados</h2>
    </div>
    <a href="<?php echo esc_url(get_post_type_archive_link('demo')); ?>" class="section-link">
      Ver todos os áudios →
    </a>
  </div>

  <div class="demos-grid">
    <?php
    $demos = new WP_Query([
      'post_type'      => 'demo',
      'posts_per_page' => 4,
      'orderby'        => 'menu_order',
      'order'          => 'ASC',
    ]);

    if ($demos->have_posts()) :
      while ($demos->have_posts()) :
        $demos->the_post();
        $pid            = get_the_ID();
        $soundcloud_url = locutora_get_soundcloud_url($pid);
        $audio_url      = locutora_get_audio_url($pid);
        $duracao        = locutora_get_duracao($pid);
        $terms          = get_the_terms($pid, 'segmento');
        $card_tag       = $terms && !is_wp_error($terms) ? $terms[0]->name : 'Demo';

        get_template_part('template-parts/demo-card', null, compact('audio_url', 'soundcloud_url', 'duracao', 'card_tag'));
      endwhile;
      wp_reset_postdata();
    else : ?>
      <p style="color:var(--muted);grid-column:1/-1;">Nenhum demo cadastrado ainda.</p>
    <?php endif; ?>
  </div>
</section>

<!-- ══ SERVIÇOS ══ -->
<section class="services" id="servicos">
  <h2 class="section-title" style="margin-bottom:28px;">Para cada projeto, a entonação certa</h2>
  <div class="services-grid">
    <?php
    $servicos_fallback = [
      ['Comerciais',                 'Locução publicitária para campanhas, spots e vídeos.'],
      ['Emissoras de rádio e TV',    'Chamadas, vinhetas e conteúdos para programação.'],
      ['Conteúdos para internet',    'Voz para vídeos, podcasts, aplicativos e redes sociais.'],
      ['URA &amp; espera telefônica','Atendimento claro, acolhedor e alinhado à sua marca.'],
      ['Vídeos institucionais',      'Narração profissional para empresas e apresentações.'],
      ['E muito mais',               'E-learning, audiobooks, documentários e projetos especiais.'],
    ];
    $servicos_posts = get_posts(['post_type' => 'servico', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC']);
    $servicos = $servicos_posts
      ? array_map(fn($item) => [$item->post_title, wp_strip_all_tags($item->post_content)], $servicos_posts)
      : $servicos_fallback;
    foreach ($servicos as [$titulo, $desc]) : ?>
      <div class="service-item">
        <p class="service-item__title"><?php echo $titulo; ?></p>
        <p class="service-item__desc"><?php echo esc_html($desc); ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══ DEPOIMENTO + CTA ══ -->
<section class="cta-block" id="contato">
  <?php
  $depoimento = get_theme_mod('locutora_depoimento', 'Precisava de uma voz que passasse seriedade sem ser fria. Ela entregou exatamente isso — e antes do prazo.');
  $dep_autor  = get_theme_mod('locutora_depoimento_autor', 'Cliente Locutora.com');
  ?>
  <blockquote class="cta-block__quote"><?php echo esc_html($depoimento); ?></blockquote>

  <div class="cta-block__footer">
    <p class="cta-block__author"><?php echo esc_html($dep_autor); ?></p>
    <div class="cta-block__action">
      <div class="cta-block__text">
        <p class="cta-block__heading serif">Vamos gravar?</p>
        <p class="cta-block__sub">Orçamento em até 24h.</p>
      </div>
      <a href="https://wa.me/<?php echo esc_attr(preg_replace('/\D+/', '', get_theme_mod('locutora_whatsapp', '5511984404171'))); ?>" target="_blank" rel="noopener" class="btn-primary" style="font-size:15px;padding:16px 30px;">
        Falar no WhatsApp
      </a>
    </div>
  </div>
</section>

<?php get_footer(); ?>
