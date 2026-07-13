<header class="site-header">
  <button class="site-menu-toggle" type="button" aria-expanded="false" aria-controls="site-navigation" aria-label="Abrir menu">
    <span></span><span></span><span></span>
  </button>
  <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo">
    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo-adriana-rosa.png'); ?>" alt="Adriana Rosa — Locutora">
  </a>

  <nav id="site-navigation">
    <ul class="site-nav">
      <li<?php echo is_front_page() ? ' class="current-menu-item"' : ''; ?>><a href="<?php echo esc_url(home_url('/')); ?>">Home</a></li>
      <li<?php echo is_page('sobre-nos') ? ' class="current-menu-item"' : ''; ?>><a href="<?php echo esc_url(home_url('/sobre-nos/')); ?>">Sobre nós</a></li>
      <li<?php echo is_page('servicos') ? ' class="current-menu-item"' : ''; ?>><a href="<?php echo esc_url(home_url('/servicos/')); ?>">Áudios</a></li>
      <li<?php echo is_page('contato') ? ' class="current-menu-item"' : ''; ?>><a href="<?php echo esc_url(home_url('/contato/')); ?>">Contato</a></li>
    </ul>
  </nav>
</header>
