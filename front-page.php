<?php get_header(); ?>

<?php
// Hero: áudio principal vem das opções do tema (ACF) ou meta
$hero_audio = function_exists('get_field')
  ? ((array) get_field('hero_audio', 'option'))['url'] ?? ''
  : get_option('locutora_hero_audio', '');

$hero_dur = function_exists('get_field')
  ? get_field('hero_duracao', 'option') ?? '0:48'
  : get_option('locutora_hero_duracao', '0:48');

$cta_url = get_permalink(get_page_by_path('orcamento')) ?: '#orcamento';
?>

<!-- ══ HERO ══ -->
<section class="hero" id="hero">
  <p class="hero__eyebrow">Locutora profissional</p>

  <h1 class="hero__title serif">
    <?php echo wp_kses_post(
      get_option('locutora_hero_titulo', 'A voz certa para a sua marca ser ouvida.')
    ); ?>
  </h1>

  <p class="hero__sub">
    <?php echo esc_html(get_option(
      'locutora_hero_sub',
      'Locução para comerciais, vídeos institucionais, URA e e‑learning — com entrega rápida e qualidade de estúdio.'
    )); ?>
  </p>

  <div class="hero__actions">
    <a href="#demos" class="btn-primary" style="font-size:15px;padding:16px 30px;border-radius:999px;">Ouvir demos</a>
    <a href="<?php echo esc_url($cta_url); ?>" class="btn-outline">Pedir orçamento</a>
  </div>

  <?php if ($hero_audio) : ?>
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
  <?php endif; ?>
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
        $pid       = get_the_ID();
        $audio_url = locutora_get_audio_url($pid);
        $duracao   = locutora_get_duracao($pid);
        $terms    = get_the_terms($pid, 'segmento');
        $card_tag = $terms && !is_wp_error($terms) ? $terms[0]->name : 'Demo';

        get_template_part('template-parts/demo-card', null, compact('audio_url', 'duracao', 'card_tag'));
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
    $servicos = [
      ['Comercial &amp; varejo',    'Spots de rádio e TV com energia de venda.'],
      ['Vídeo institucional',       'Voz que transmite confiança e autoridade.'],
      ['URA &amp; atendimento',     'Mensagens de espera e menus telefônicos.'],
      ['E‑learning',                'Narração clara para cursos e treinamentos.'],
      ['Audiobook &amp; narração',  'Leitura envolvente do começo ao fim.'],
      ['Espera telefônica',         'Sua marca falando enquanto o cliente aguarda.'],
    ];
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
  $depoimento = get_option('locutora_depoimento', '"Precisava de uma voz que passasse seriedade sem ser fria. Ela entregou exatamente isso — e antes do prazo."');
  $dep_autor  = get_option('locutora_depoimento_autor', '— Diretora de Marketing, agência placeholder');
  ?>
  <blockquote class="cta-block__quote"><?php echo esc_html($depoimento); ?></blockquote>

  <div class="cta-block__footer">
    <p class="cta-block__author"><?php echo esc_html($dep_autor); ?></p>
    <div class="cta-block__action">
      <div class="cta-block__text">
        <p class="cta-block__heading serif">Vamos gravar?</p>
        <p class="cta-block__sub">Orçamento em até 24h.</p>
      </div>
      <a href="<?php echo esc_url($cta_url); ?>" class="btn-primary" style="font-size:15px;padding:16px 30px;">
        Pedir orçamento
      </a>
    </div>
  </div>
</section>

<?php get_footer(); ?>
