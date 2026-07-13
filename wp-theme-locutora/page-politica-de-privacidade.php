<?php get_header(); ?>

<main class="privacy-page">
  <article class="privacy-page__content">
    <h1>Política de Privacidade</h1>
    <?php
    while (have_posts()) {
      the_post();
      $privacy_content = trim((string) get_the_content());

      if ($privacy_content !== '') {
        the_content();
      } else {
        get_template_part('template-parts/privacy-content');
      }
    }
    ?>
  </article>
</main>

<?php get_footer(); ?>
