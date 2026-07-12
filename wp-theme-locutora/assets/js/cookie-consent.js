(() => {
  const consent = document.querySelector('[data-cookie-consent]');
  const accept = document.querySelector('[data-cookie-consent-accept]');

  if (!consent || !accept) return;

  const storageKey = 'locutora-cookie-consent';
  let accepted = false;

  try {
    accepted = window.localStorage.getItem(storageKey) === 'accepted';
  } catch (error) {
    accepted = false;
  }

  if (!accepted) consent.hidden = false;

  accept.addEventListener('click', () => {
    consent.hidden = true;

    try {
      window.localStorage.setItem(storageKey, 'accepted');
    } catch (error) {
      // The banner still closes when storage is unavailable.
    }
  });
})();
