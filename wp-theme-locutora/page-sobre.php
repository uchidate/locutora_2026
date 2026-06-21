<?php get_header(); ?>

<main class="page-sobre">

  <!-- ══ HERO DA PÁGINA ══ -->
  <section class="page-hero">
    <p class="hero__eyebrow">Sobre</p>
    <h1 class="page-hero__title serif">
      <?php echo esc_html(get_bloginfo('name')); ?>
    </h1>
    <p class="page-hero__sub">Locutora profissional com anos de experiência em comerciais, vídeos institucionais, URA e e‑learning.</p>
  </section>

  <!-- ══ BIO ══ -->
  <section class="sobre-bio">
    <div class="sobre-bio__text">
      <?php
      if (have_posts()) :
        the_post();
        the_content();
      else : ?>
        <p>Sou locutora profissional dedicada a entregar a voz certa para cada projeto. Com estúdio próprio e equipamentos de alta qualidade, ofereço locução para comerciais de TV e rádio, vídeos institucionais, URA, e‑learning, audiobooks e espera telefônica.</p>
        <p>Meu compromisso é com a entrega rápida sem abrir mão da qualidade — porque o prazo do seu projeto importa tanto quanto o resultado final.</p>
        <p>Entre em contato e receba um orçamento em até 24h.</p>
      <?php endif; ?>
    </div>

    <div class="sobre-bio__stats">
      <div class="sobre-stat">
        <span class="sobre-stat__num serif">+10</span>
        <span class="sobre-stat__label">anos de experiência</span>
      </div>
      <div class="sobre-stat">
        <span class="sobre-stat__num serif">+500</span>
        <span class="sobre-stat__label">projetos entregues</span>
      </div>
      <div class="sobre-stat">
        <span class="sobre-stat__num serif">24h</span>
        <span class="sobre-stat__label">para orçamento</span>
      </div>
    </div>
  </section>

  <!-- ══ EQUIPAMENTOS / ESTÚDIO ══ -->
  <section class="sobre-studio">
    <p class="section-eyebrow">Estúdio</p>
    <h2 class="section-title" style="margin-bottom:32px;">Qualidade de estúdio profissional</h2>
    <div class="studio-grid">
      <div class="studio-item">
        <div class="studio-item__icon">🎙</div>
        <div class="studio-item__title">Microfone condensador</div>
        <p class="studio-item__desc">Captação limpa e detalhada para resultados de alta fidelidade.</p>
      </div>
      <div class="studio-item">
        <div class="studio-item__icon">🔇</div>
        <div class="studio-item__title">Cabine acusticamente tratada</div>
        <p class="studio-item__desc">Ambiente silencioso e sem reverb para entrega imediatamente utilizável.</p>
      </div>
      <div class="studio-item">
        <div class="studio-item__icon">⚡</div>
        <div class="studio-item__title">Entrega rápida</div>
        <p class="studio-item__desc">Arquivos em MP3, WAV ou qualquer formato que o projeto exigir.</p>
      </div>
    </div>
  </section>

  <!-- ══ CTA ══ -->
  <section class="cta-block" style="margin-top:0;">
    <blockquote class="cta-block__quote">Pronta para dar voz ao seu próximo projeto.</blockquote>
    <div class="cta-block__footer">
      <p class="cta-block__author">Orçamento sem compromisso em até 24h.</p>
      <div class="cta-block__action">
        <a href="<?php echo esc_url(get_permalink(get_page_by_path('orcamento'))); ?>" class="btn-primary" style="font-size:15px;padding:16px 30px;">
          Pedir orçamento
        </a>
      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>
