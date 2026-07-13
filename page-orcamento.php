<?php get_header(); ?>

<?php
if (have_posts()) {
  the_post();
  $page_blocks = trim((string) get_the_content());
  if ($page_blocks !== '' && has_blocks($page_blocks)) {
    echo '<main>';
    echo apply_filters('the_content', $page_blocks);
    echo '</main>';
    get_footer();
    return;
  }
}
?>

<main>
  <section class="page-hero">
    <p class="hero__eyebrow">Orçamento</p>
    <h1 class="page-hero__title serif">Vamos trabalhar juntos?</h1>
    <p class="page-hero__sub">Preencha o formulário abaixo e responderei em até 24h com proposta e prazo.</p>
  </section>

  <div class="page-orcamento">
    <p class="orcamento-intro">
      Quanto mais detalhar o projeto — tipo, duração, tom e prazo —, mais preciso será o orçamento. Mas se ainda estiver na fase de levantamento, sem problema: me conta a ideia e conversamos.
    </p>

    <?php the_content(); ?>
  </div>
</main>

<?php get_footer(); ?>
