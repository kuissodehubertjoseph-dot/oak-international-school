/**
 * Composant logo — utilisé sur toutes les pages publiques (navbar + pied de
 * page). Lit le réglage global "logo_mode" / "logo_text" (site_settings,
 * exposé en lecture publique par api-settings.php) et bascule entre l'image
 * du logo et un texte de marque stylisé.
 *
 * Mise en cache localStorage pour un rendu instantané au chargement suivant,
 * avec revalidation en arrière-plan. L'onglet Développeur invalide ce cache
 * (nouvelle valeur écrite dans localStorage) dès qu'un réglage est enregistré ;
 * cette page le relira au prochain chargement.
 */
(function () {
  var CACHE_KEY = 'siteLogoSettings';
  var CACHE_TTL_MS = 5 * 60 * 1000;

  function applyLogo(settings) {
    if (!settings || !settings.logo_mode) return;
    var isText = settings.logo_mode === 'text';
    var text = settings.logo_text || 'OAK International School';

    document.querySelectorAll('.navbar-logo, .footer-logo-area img').forEach(function (img) {
      var textEl = img.nextElementSibling && img.nextElementSibling.classList.contains('brand-logo-text-dynamic')
        ? img.nextElementSibling
        : img.parentElement.querySelector(':scope > .brand-logo-text-dynamic');

      if (isText) {
        img.style.display = 'none';
        if (!textEl) {
          textEl = document.createElement('span');
          textEl.className = 'brand-logo-text-dynamic';
          img.insertAdjacentElement('afterend', textEl);
        }
        textEl.textContent = text;
        textEl.style.display = '';
      } else {
        img.style.display = '';
        img.alt = text;
        if (textEl) { textEl.style.display = 'none'; }
      }
    });
  }

  function readCache() {
    try {
      var raw = localStorage.getItem(CACHE_KEY);
      if (!raw) return null;
      var parsed = JSON.parse(raw);
      if (!parsed || !parsed.cachedAt || Date.now() - parsed.cachedAt > CACHE_TTL_MS) return null;
      return parsed;
    } catch (e) { return null; }
  }

  function writeCache(settings) {
    try {
      localStorage.setItem(CACHE_KEY, JSON.stringify(Object.assign({}, settings, { cachedAt: Date.now() })));
    } catch (e) { /* stockage indisponible — pas bloquant */ }
  }

  function fetchAndApply() {
    fetch('api-settings.php', { headers: { 'Accept': 'application/json' } })
      .then(function (res) { return res.ok ? res.json() : null; })
      .then(function (data) {
        if (!data) return;
        applyLogo(data);
        writeCache(data);
      })
      .catch(function () { /* réglage par défaut (image) déjà affiché */ });
  }

  var cached = readCache();
  if (cached) { applyLogo(cached); }
  fetchAndApply();
})();
