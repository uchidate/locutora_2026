<?php
/**
 * Template part: Demo Card
 * Vars esperadas: $post (WP_Post), $audio_url (string), $duracao (string), $tag (string)
 */
$tag      = $tag      ?? 'Demo';
$duracao  = $duracao  ?? '';
$audio_url = $audio_url ?? '';
?>
<article class="demo-card">
  <div class="demo-card__header">
    <span class="demo-card__tag"><?php echo esc_html($tag); ?></span>
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
