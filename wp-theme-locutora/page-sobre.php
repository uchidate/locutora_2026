<?php get_header(); ?>

<main class="internal-page internal-page--sobre">
  <section class="internal-hero internal-hero--sobre">
    <div class="internal-hero__inner"><h1>Sobre nós:</h1></div>
  </section>

  <section class="about-story">
    <div class="about-story__row">
    <div class="about-story__copy reveal reveal--slide-top">
      <h2>Fundada em 2004, em<br>São Paulo.</h2>
      <h3>Missão</h3>
      <p>Dar voz às marcas com criatividade, qualidade e atenção aos detalhes, criando conexões autênticas entre empresas e seus públicos.</p>
      <h3>Visão</h3>
      <p>Ser referência em locução profissional, reconhecida pela inovação e relacionamento próximo com cada cliente.</p>
      <h3>Valores</h3>
      <p>Excelência, inovação, personalização, profissionalismo, ética, comprometimento e respeito em cada projeto.</p>
    </div>
    <figure class="about-story__image reveal reveal--fade">
      <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/internal/sobre-intro.png'); ?>" alt="Mesa de áudio de estúdio profissional">
    </figure>
    </div>

    <div class="about-bio__row">
      <figure class="about-bio__image reveal reveal--fade">
        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/internal/adriana-rosa-retrato.jpg'); ?>" alt="Retrato ilustrado de Adriana Rosa">
      </figure>
      <div class="about-bio__copy reveal reveal--slide-bottom">
        <h2>Adriana Rosa</h2>
        <p>Adriana Rosa é uma locutora profissional brasileira, atriz, radialista e comunicadora de São Paulo, com mais de 20 anos de experiência em locução profissional para publicidade, rádio, televisão, internet e projetos corporativos. Reconhecida pela versatilidade e qualidade de sua voz, atende clientes de todo o Brasil e do mercado internacional.</p>
        <p>Formada em Produção Audiovisual e Teatro, Adriana também é radialista e possui pós-graduações em Influência Digital e Jornalismo Digital, unindo sólida formação acadêmica a ampla experiência prática no mercado da comunicação e do entretenimento.</p>
        <p>Especialista em locução em português do Brasil, atua em campanhas publicitárias, voice over, vídeos institucionais, treinamentos corporativos, e-learning, URA, conteúdos digitais e produções audiovisuais. Sua experiência permite entregar uma comunicação clara, envolvente e alinhada aos objetivos de cada marca.</p>
        <p>Como atriz profissional, Adriana Rosa oferece interpretações naturais, autênticas e versáteis, adaptando sua voz para diferentes estilos de locução: institucional, comercial, emocional, inspiracional, varejo, personagens e conteúdos corporativos.</p>
        <p>Sua trajetória inclui passagens por importantes emissoras de rádio, como a Rádio Trianon e a Novabrasil FM. Atualmente, é locutora da Classic Pan, pertencente ao Grupo Jovem Pan.</p>
        <p>Ao longo da carreira, já realizou trabalhos para grandes marcas nacionais e internacionais, entre elas: Amil, Santander, Bradesco, Globo, Prada, Apple, Danone, Banco da Amazônia, Mondial, Audi, Nespresso, Avon, Ticket, Netflix, Natura, Vivo, Volkswagen, GPA, 3M e McDonald's.</p>
      </div>
    </div>
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
