<?php get_header(); ?>

<?php
if (have_posts()) {
  the_post();
  $page_blocks = trim((string) get_the_content());
  if ($page_blocks !== '' && has_blocks($page_blocks)) {
    echo '<main class="internal-page internal-page--services">';
    echo apply_filters('the_content', $page_blocks);
    echo '</main>';
    get_footer();
    return;
  }
}
?>

<main class="internal-page internal-page--services">
  <section class="internal-hero internal-hero--services">
    <div class="internal-hero__inner"><h1>Áudios</h1></div>
  </section>

  <section class="audio-showcase">
    <h2>Locutora para URA, comerciais, institucionais, tutoriais; e<br>voz padrão para rádio e TV.</h2>
    <div class="audio-showcase__grid">
      <iframe title="Demos de Adriana Rosa no SoundCloud" loading="lazy" allow="autoplay" src="https://w.soundcloud.com/player/?url=http%3A%2F%2Fapi.soundcloud.com%2Fusers%2F12694227&amp;auto_play=false&amp;show_artwork=true&amp;color=ff0035"></iframe>
      <iframe title="Vídeos de locução institucional" loading="lazy" src="https://www.youtube.com/embed/videoseries?list=PLTqOomsLyDw3eTbCCkSR-h9lI0iH3wPQc" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    </div>
  </section>
</main>

<?php get_footer(); ?>
