<?php
/**
 * Template part: Demo Card
 * Args: card_tag, audio_url, soundcloud_url, duracao
 *
 * Modo 1 — SoundCloud embed: quando soundcloud_url estiver preenchida
 * Modo 2 — WaveSurfer:       quando audio_url (MP3) estiver preenchida
 */
$card_tag      = $args['card_tag']      ?? 'Demo';
$duracao       = $args['duracao']       ?? '';
$audio_url     = $args['audio_url']     ?? '';
$soundcloud_url = $args['soundcloud_url'] ?? '';

$has_sc  = !empty($soundcloud_url);
$has_mp3 = !empty($audio_url);
?>
<article class="demo-card">
  <div class="demo-card__header">
    <span class="demo-card__tag"><?php echo esc_html($card_tag); ?></span>
    <?php if ($duracao) : ?>
      <span class="demo-card__dur"><?php echo esc_html($duracao); ?></span>
    <?php endif; ?>
  </div>

  <div class="demo-card__title"><?php echo esc_html(get_the_title()); ?></div>

  <?php if ($has_sc) : ?>

    <!-- ── Modo SoundCloud embed ── -->
    <div class="demo-card__sc">
      <iframe
        class="sc-iframe"
        scrolling="no"
        frameborder="no"
        allow="autoplay; encrypted-media"
        loading="lazy"
        src="<?php echo esc_url(locutora_soundcloud_embed_url($soundcloud_url, true)); ?>">
      </iframe>
    </div>

  <?php elseif ($has_mp3) : ?>

    <!-- ── Modo WaveSurfer (MP3) ── -->
    <div class="demo-card__player">
      <button class="demo-card__play" data-src="<?php echo esc_url($audio_url); ?>" aria-label="Reproduzir">
        <svg width="11" height="13" viewBox="0 0 11 13" fill="#C9A35B" aria-hidden="true">
          <path d="M0 0v13l11-6.5z"/>
        </svg>
      </button>
      <div class="demo-card__wave" aria-hidden="true"></div>
    </div>

  <?php else : ?>

    <!-- ── Sem áudio ainda ── -->
    <p class="demo-card__no-audio">Áudio em breve</p>

  <?php endif; ?>
</article>
