<?php
$cta_url   = get_permalink(get_page_by_path('orcamento')) ?: '#orcamento';
?>
<header class="site-header">
  <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo">
    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo-adriana-rosa.png'); ?>" alt="Adriana Rosa — Locutora">
  </a>

  <?php
  wp_nav_menu([
    'theme_location' => 'primary',
    'container'      => 'nav',
    'container_class'=> 'site-nav',
    'items_wrap'     => '<ul class="site-nav">%3$s</ul>',
    'fallback_cb'    => function () { ?>
      <nav>
        <ul class="site-nav">
          <li><a href="<?php echo esc_url(home_url('/')); ?>">Home</a></li>
          <li><a href="<?php echo esc_url(home_url('/sobre-nos/')); ?>">Sobre nós</a></li>
          <li><a href="<?php echo esc_url(home_url('/servicos/')); ?>">Áudios</a></li>
          <li><a href="<?php echo esc_url(home_url('/contato/')); ?>">Contato</a></li>
        </ul>
      </nav>
    <?php },
  ]);
  ?>
</header>
