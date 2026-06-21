<?php
$logo_text = get_bloginfo('name') ?: 'Mariana Reis';
$cta_url   = get_permalink(get_page_by_path('orcamento')) ?: '#orcamento';
?>
<header class="site-header">
  <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo">
    <?php echo esc_html($logo_text); ?><span>.</span>
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
          <li><a href="#demos">Demos</a></li>
          <li><a href="#servicos">Serviços</a></li>
          <li><a href="#sobre">Sobre</a></li>
          <li><a href="#contato">Contato</a></li>
        </ul>
      </nav>
    <?php },
  ]);
  ?>

  <a href="<?php echo esc_url($cta_url); ?>" class="btn-primary">Pedir orçamento</a>
</header>
