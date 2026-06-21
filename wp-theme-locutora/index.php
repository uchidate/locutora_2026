<?php get_header(); ?>

<main style="padding:56px;color:var(--cream);">
  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    <article>
      <h1 class="serif" style="font-size:36px;"><?php the_title(); ?></h1>
      <?php the_content(); ?>
    </article>
  <?php endwhile; endif; ?>
</main>

<?php get_footer(); ?>
