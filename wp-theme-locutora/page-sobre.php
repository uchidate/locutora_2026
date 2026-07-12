<?php get_header(); ?>

<main class="internal-page internal-page--sobre">
  <section class="internal-hero internal-hero--sobre">
    <div class="internal-hero__inner"><h1>Sobre nós:</h1></div>
  </section>

  <section class="about-story">
    <div class="about-story__copy">
      <h2>Fundada em 2004, em<br>São Paulo.</h2>
      <h3>Missão</h3>
      <p>Dar voz às marcas com criatividade, qualidade e atenção aos detalhes, criando conexões autênticas entre empresas e seus públicos.</p>
      <h3>Visão</h3>
      <p>Ser referência em locução profissional, reconhecida pela inovação e relacionamento próximo com cada cliente.</p>
      <h3>Valores</h3>
      <p>Excelência, inovação, personalização, profissionalismo, ética, comprometimento e respeito em cada projeto.</p>
    </div>
    <figure class="about-story__image">
      <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/internal/sobre-intro.png'); ?>" alt="Mesa de áudio de estúdio profissional">
    </figure>
  </section>

  <section class="brand-showcase">
    <h2>Marcas que já confiaram em nossa voz</h2>
    <div class="brand-grid">
      <?php
      $brands = ['apple','Liza','santander','Globo','Claro','boticario','Adria2','bradesco','3m','natura','cielo','amil','avon2','viacredi','mcdonalds','neoenergia','danone','paodeaçucar','boston2','Vivo','netflix','Nespresso'];
      foreach ($brands as $brand) : ?>
        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/brands/' . $brand . '.png'); ?>" alt="<?php echo esc_attr($brand); ?>" loading="lazy">
      <?php endforeach; ?>
    </div>
  </section>
</main>

<?php get_footer(); ?>
