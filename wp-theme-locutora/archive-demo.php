<?php get_header(); ?>

<?php
$current_seg = isset($_GET['segmento']) ? sanitize_text_field($_GET['segmento']) : '';

$query_args = [
  'post_type'      => 'demo',
  'posts_per_page' => -1,
  'orderby'        => 'menu_order',
  'order'          => 'ASC',
];

if ($current_seg) {
  $query_args['tax_query'] = [[
    'taxonomy' => 'segmento',
    'field'    => 'slug',
    'terms'    => $current_seg,
  ]];
}

$demos = new WP_Query($query_args);
$segmentos = get_terms(['taxonomy' => 'segmento', 'hide_empty' => true]);
?>

<main>
  <section class="page-hero">
    <p class="hero__eyebrow">Portfólio</p>
    <h1 class="page-hero__title serif">Todos os demos</h1>
    <p class="page-hero__sub">Ouça amostras do meu trabalho em diferentes segmentos e formatos.</p>
  </section>

  <section class="demos-archive">

    <!-- Filtro por segmento -->
    <div class="demos-filter" role="navigation" aria-label="Filtrar por segmento">
      <a href="<?php echo esc_url(get_post_type_archive_link('demo')); ?>"
         class="filter-btn <?php echo !$current_seg ? 'active' : ''; ?>">
        Todos
      </a>
      <?php if (!is_wp_error($segmentos)) foreach ($segmentos as $seg) : ?>
        <a href="<?php echo esc_url(add_query_arg('segmento', $seg->slug, get_post_type_archive_link('demo'))); ?>"
           class="filter-btn <?php echo $current_seg === $seg->slug ? 'active' : ''; ?>">
          <?php echo esc_html($seg->name); ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Grid de demos -->
    <div class="demos-grid">
      <?php
      if ($demos->have_posts()) :
        while ($demos->have_posts()) :
          $demos->the_post();
          $pid       = get_the_ID();
          $soundcloud_url = locutora_get_soundcloud_url($pid);
          $audio_url      = locutora_get_audio_url($pid);
          $duracao        = locutora_get_duracao($pid);
          $terms          = get_the_terms($pid, 'segmento');
          $card_tag       = $terms && !is_wp_error($terms) ? $terms[0]->name : 'Demo';

          get_template_part('template-parts/demo-card', null, compact('audio_url', 'soundcloud_url', 'duracao', 'card_tag'));
        endwhile;
        wp_reset_postdata();
      else : ?>
        <p style="color:var(--muted);grid-column:1/-1;padding:32px 0;">
          Nenhum demo encontrado<?php echo $current_seg ? ' neste segmento' : ''; ?>.
        </p>
      <?php endif; ?>
    </div>

    <!-- Paginação -->
    <?php if ($demos->max_num_pages > 1) : ?>
      <div style="margin-top:40px;display:flex;justify-content:center;">
        <?php echo paginate_links(['total' => $demos->max_num_pages, 'type' => 'list']); ?>
      </div>
    <?php endif; ?>

  </section>
</main>

<?php get_footer(); ?>
