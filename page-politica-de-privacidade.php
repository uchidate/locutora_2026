<?php get_header(); ?>

<main class="privacy-page">
  <article class="privacy-page__content">
    <h1>Política de Privacidade</h1>
    <?php
    while (have_posts()) {
      the_post();
      the_content();
    }
    ?>
  </article>
</main>

<?php get_footer(); ?>
