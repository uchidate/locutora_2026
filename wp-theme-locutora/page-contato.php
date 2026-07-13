<?php get_header(); ?>

<?php
if (have_posts()) {
  the_post();
  $page_blocks = trim((string) get_the_content());
  if ($page_blocks !== '' && has_blocks($page_blocks)) {
    echo '<main class="internal-page internal-page--contact">';
    echo apply_filters('the_content', $page_blocks);
    echo '</main>';
    get_footer();
    return;
  }
}
?>

<main class="internal-page internal-page--contact">
  <section class="internal-hero internal-hero--contact">
    <div class="internal-hero__inner"><h1>Contato</h1></div>
  </section>

  <section class="contact-section">
    <form class="contact-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="locutora_contact">
      <?php wp_nonce_field('locutora_contact', 'locutora_contact_nonce'); ?>
      <label>Nome *<input name="nome" type="text" required autocomplete="name"></label>
      <label>E-mail *<input name="email" type="email" required autocomplete="email"></label>
      <label>Telefone<input name="telefone" type="tel" autocomplete="tel"></label>
      <label>Assunto *<input name="assunto" type="text" required></label>
      <label>Mensagem<textarea name="mensagem" rows="7"></textarea></label>
      <button type="submit">Enviar mensagem</button>
      <?php if (isset($_GET['enviado'])) : ?>
        <p class="contact-form__feedback" role="status"><?php echo $_GET['enviado'] === '1' ? 'Mensagem enviada com sucesso.' : 'Não foi possível enviar. Tente novamente.'; ?></p>
      <?php endif; ?>
    </form>
  </section>
</main>

<?php get_footer(); ?>
