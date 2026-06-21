<?php
/**
 * Template part: Demo Card
 * Args: card_tag (string), audio_url (string), duracao (string)
 * WP 6.x passa os dados via $args, não como variáveis extraídas.
 */
$card_tag  = $args['card_tag']  ?? 'Demo';
$duracao   = $args['duracao']   ?? '';
$audio_url = $args['audio_url'] ?? '';
?>
<article class="demo-card">
  <div class="demo-card__header">
    <span class="demo-card__tag"><?php echo esc_html($card_tag); ?></span>
    <?php if ($duracao) : ?>
      <span class="demo-card__dur"><?php echo esc_html($duracao); ?></span>
    <?php endif; ?>
  </div>

  <div class="demo-card__title"><?php echo esc_html(get_the_title()); ?></div>

  <div class="demo-card__player">
    <button class="demo-card__play" data-src="<?php echo esc_url($audio_url); ?>" aria-label="Reproduzir">
      <svg width="11" height="13" viewBox="0 0 11 13" fill="#C9A35B" aria-hidden="true">
        <path d="M0 0v13l11-6.5z"/>
      </svg>
    </button>
    <div class="demo-card__wave" aria-hidden="true"></div>
  </div>
</article>
