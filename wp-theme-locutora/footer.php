<footer class="site-footer">
  <div class="site-footer__main">
    <a class="site-footer__brand" href="<?php echo esc_url(home_url('/')); ?>">
      <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo-adriana-rosa.png'); ?>" alt="Adriana Rosa — Locutora">
      <span aria-hidden="true">Adriana Rosa</span>
    </a>

    <section class="site-footer__column" aria-labelledby="footer-contact-title">
      <h2 id="footer-contact-title">Entre em contato</h2>
      <a href="https://wa.me/5511984404171" target="_blank" rel="noopener">(11) 98440-4171</a>
      <a href="mailto:adrianarosa@locutora.com">adrianarosa@locutora.com</a>
      <a href="mailto:adrianarosa.voz@gmail.com">adrianarosa.voz@gmail.com</a>
      <div class="site-footer__socials" aria-label="Redes sociais">
        <a href="https://instagram.com/adriana.rosa_s" target="_blank" rel="noopener">Instagram</a>
        <a href="https://linkedin.com/in/adrianarosa-voiceover/" target="_blank" rel="noopener">LinkedIn</a>
        <a href="https://youtube.com/adrianalocutoracom" target="_blank" rel="noopener">YouTube</a>
      </div>
    </section>

    <nav class="site-footer__column" aria-labelledby="footer-nav-title">
      <h2 id="footer-nav-title">Institucional</h2>
      <a href="<?php echo esc_url(home_url('/sobre-nos/')); ?>">Sobre nós</a>
      <a href="<?php echo esc_url(home_url('/servicos/')); ?>">Serviços</a>
      <a href="<?php echo esc_url(home_url('/contato/')); ?>">Contato</a>
    </nav>
  </div>
  <div class="site-footer__legal">
    <span>2022 &copy; Todos os Direitos Reservados</span>
    <span aria-hidden="true">|</span>
    <a href="<?php echo esc_url(home_url('/politica-de-privacidade/')); ?>">Política de Privacidade</a>
  </div>
</footer>

<a class="whatsapp-float" href="https://wa.me/5511984404171" target="_blank" rel="noopener" aria-label="Conversar pelo WhatsApp">
  <svg viewBox="0 0 32 32" aria-hidden="true"><path fill="currentColor" d="M16.1 3a12.7 12.7 0 0 0-10.9 19.2L3.4 29l7-1.8A12.7 12.7 0 1 0 16.1 3Zm0 23.2c-1.9 0-3.8-.5-5.4-1.5l-.4-.2-4.1 1.1 1.1-4-.3-.4a10.4 10.4 0 1 1 9.1 5Zm5.7-7.8c-.3-.2-1.8-.9-2.1-1-.3-.1-.5-.2-.7.2l-1 1.2c-.2.2-.4.2-.7.1-2-.8-3.4-2.3-4.4-4-.2-.3 0-.5.1-.7l.5-.6c.2-.2.2-.4.3-.6.1-.2 0-.4 0-.6l-1-2.3c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.4-1.2 1.2-1.2 3 0 1.7 1.3 3.4 1.4 3.7.2.2 2.5 3.8 6 5.3.8.4 1.5.6 2 .7.8.3 1.6.2 2.2.1.7-.1 1.8-.7 2-1.4.3-.7.3-1.3.2-1.4-.1-.2-.3-.2-.6-.4Z"/></svg>
</a>

<?php wp_footer(); ?>
</body>
</html>
