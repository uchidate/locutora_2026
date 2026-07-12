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

</section>

<!-- ══ APRESENTAÇÃO ══ -->
<section class="legacy-intro" id="sobre">
  <div class="legacy-intro__copy">
    <h2 class="section-title"><?php echo esc_html(get_theme_mod('locutora_intro_titulo', 'Locutora.com')); ?></h2>
    <h3>Locutora Profissional | Gravação de voz para publicidade, TV, rádio e URA</h3>
    <p><strong>Se você procura uma locutora profissional para dar mais credibilidade e impacto à comunicação da sua empresa, está no lugar certo.</strong></p>
    <p><?php echo esc_html(get_theme_mod('locutora_intro_texto_1', 'Sou Adriana Rosa, locutora profissional atuando no mercado desde 2004, especializada em gravação de voz para campanhas publicitárias, vídeos institucionais, URA, espera telefônica, conteúdos corporativos e projetos digitais. Ao longo de mais de duas décadas de experiência, atendi empresas, agências e produtoras em todo o Brasil e exterior, sempre com qualidade profissional e entrega rápida.')); ?></p>
    <h3>Voz para Publicidade, TV e Rádio</h3>
    <p>Uma boa voz para publicidade faz toda a diferença na conexão com o público. Realizo locuções para campanhas promocionais, comerciais, lançamentos de produtos e ações de marketing, oferecendo uma voz marcante e alinhada à identidade da sua marca.</p>
    <p>Também produzo gravações de voz para TV e rádio, criando mensagens claras, envolventes e profissionais para diferentes formatos de mídia.</p>
    <h3>Serviços de Locução Profissional</h3>
    <p>Gravação para publicidade, TV, rádio, URA, espera telefônica, vídeos institucionais, treinamentos, e-learning, campanhas e conteúdo digital.</p>
    <ul>
      <li>Gravação de voz para publicidade</li>
      <li>Voz para TV e rádio</li>
      <li>Gravação de URA profissional</li>
      <li>Espera telefônica personalizada</li>
      <li>Locução para vídeos institucionais</li>
      <li>Gravação de URA e espera telefônica</li>
      <li>Locução para treinamentos e e-learning</li>
      <li>Campanhas promocionais e comerciais</li>
      <li>Conteúdo para redes sociais e mídia digital</li>
    </ul>
    <h3>Locutora Profissional com Experiência Comprovada</h3>
    <p>Desde 2004, desenvolvo projetos de locução para empresas de diversos segmentos, contribuindo para fortalecer marcas e melhorar a comunicação com clientes. Minha experiência permite adaptar a interpretação e o estilo de voz às necessidades de cada projeto.</p>
    <h3>Qualidade Profissional e Atendimento Nacional</h3>
    <p>Com estúdio próprio e equipamentos profissionais, realizo gravações de voz com alta qualidade técnica, garantindo excelente resultado para empresas que precisam de uma comunicação eficiente e profissional.</p>
    <p>Solicite um orçamento e descubra como uma voz humana e profissional pode valorizar sua marca, sua campanha e seus projetos de comunicação.</p>
    <a href="<?php echo esc_url(home_url('/sobre-nos/')); ?>" class="legacy-link">Conheça</a>
  </div>
  <figure class="legacy-intro__portrait">
    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/intro.png'); ?>" alt="Adriana Rosa, locutora profissional">
  </figure>
</section>

<!-- ══ SERVIÇOS ══ -->
<section class="services" id="servicos">
  <h2 class="section-title">Fazemos gravações para:</h2>
  <div class="services-grid">
    <?php
    $servicos_fallback = [
      ['Comerciais', 'servico-comerciais.webp'],
      ['Emissoras de rádio e tv', 'servico-tv.webp'],
      ['Conteúdos para internet', 'servico-internet.webp'],
      ['E muito mais', 'servico-mais.webp'],
    ];
    $servicos_posts = get_posts(['post_type' => 'servico', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC']);
    $servicos = $servicos_posts
      ? array_map(fn($item) => [$item->post_title, 'servico-mais.webp'], $servicos_posts)
      : $servicos_fallback;
    foreach (array_slice($servicos, 0, 4) as [$titulo, $icone]) : ?>
      <a class="service-item" href="<?php echo esc_url(home_url('/servicos/')); ?>">
        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/' . $icone); ?>" alt="">
        <h3 class="service-item__title"><?php echo esc_html($titulo); ?></h3>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══ CONTATO ══ -->
<section class="cta-block" id="contato">
  <video autoplay muted loop playsinline preload="metadata" aria-hidden="true">
    <source src="<?php echo esc_url(get_template_directory_uri() . '/assets/video/contato.mp4'); ?>" type="video/mp4">
  </video>
  <div class="cta-block__shade"></div>
  <div class="cta-block__content">
    <h2>Entre em contato<br>com Adriana Rosa</h2>
    <a href="<?php echo esc_url(home_url('/contato/')); ?>" class="btn-outline">Contato</a>
  </div>
</section>

<?php get_footer(); ?>
