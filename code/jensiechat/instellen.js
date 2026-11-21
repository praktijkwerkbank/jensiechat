// Jensiechat, basisversie. Copyright (c) 2026 Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl). MIT-licentie, zie LICENSE.
// Het invulscherm: tijdzone van de browser, kleur meteen laten zien, wachtwoord tonen of laten maken. Los bestand vanwege de CSP.
(function () {
  'use strict';
  var tz = document.getElementById('in-tz');
  try { if (tz && !tz.value) tz.value = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) {}
  var stijl = document.getElementById('in-kleur'), eigen = document.getElementById('in-eigen'), eigenKeuze = document.getElementById('in-eigen-keuze');
  function zet(k) { if (/^#[0-9a-f]{6}$/i.test(k)) stijl.href = stijl.href.replace(/k=[0-9a-f]{6}/i, 'k=' + k.slice(1).toLowerCase()); }
  document.querySelectorAll('input[name=kleur]').forEach(function (r) { r.addEventListener('change', function () { zet(r.value); }); });
  if (eigen) eigen.addEventListener('input', function () { eigenKeuze.value = eigen.value; eigenKeuze.checked = true; zet(eigen.value); });
  var ww = document.getElementById('in-ww'), ww2 = document.getElementById('in-ww2'), toon = document.querySelector('.in-toon');
  function zichtbaar(ja) { ww.type = ww2.type = ja ? 'text' : 'password'; toon.textContent = ja ? toon.dataset.verberg : toon.dataset.toon; toon.setAttribute('aria-pressed', ja ? 'true' : 'false'); }
  toon.addEventListener('click', function () { zichtbaar(ww.type === 'password'); });
  document.getElementById('in-maak').addEventListener('click', function () {
    var tekens = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789', b = new Uint32Array(20), s = '';
    crypto.getRandomValues(b); for (var i = 0; i < b.length; i++) { s += tekens[b[i] % tekens.length]; if (i === 4 || i === 9 || i === 14) s += '-'; }
    ww.value = ww2.value = s; zichtbaar(true); ww.focus(); ww.select();
  });
})();
