// Jensiechat, basisversie. Copyright (c) 2026 Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl). MIT-licentie, zie LICENSE.
// Het dashboard. Praat alleen met api.php, met het dashboardwachtwoord in de kop X-Sleutel.
// Alle zichtbare teksten komen uit taal/<taal>.json (de taal staat in config.php en in <html lang>).
(function () {
  'use strict';
  var OPSLAG = 'jensiechat-sleutel', sleutel = '', S = null, tab = 'overzicht', filter = 'alles', bewerk = null, timer = null, L = {};
  var $ = function (id) { return document.getElementById(id); };
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  /** Een tekst uit het taalbestand; {naam} wordt vervangen door v.naam. */
  function T(k, v) { var s = L[k] != null ? L[k] : k; if (v) Object.keys(v).forEach(function (n) { s = s.split('{' + n + '}').join(String(v[n])); }); return s; }
  /** Enkelvoud of meervoud: T(k + '_1') bij 1, anders T(k + '_n'). */
  function Tn(k, n, v) { v = v || {}; v.n = n; return T(k + (n === 1 ? '_1' : '_n'), v); }
  var LOC = 'nl-NL', nl = new Intl.NumberFormat(LOC);
  function datum(ts) { return new Date(ts * 1000).toLocaleDateString(LOC); }
  function klok(ts) { return new Date(ts * 1000).toLocaleTimeString(LOC, { hour: '2-digit', minute: '2-digit' }); }
  var ICON = {
    vink: '<svg viewBox="0 0 24 24"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>',
    kruis: '<svg viewBox="0 0 24 24"><path d="M7 7l10 10M17 7 7 17"/></svg>',
    klok: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 2"/></svg>',
    uitroep: '<svg viewBox="0 0 24 24"><path d="M12 7v6M12 17h.01"/></svg>',
    streep: '<svg viewBox="0 0 24 24"><path d="M7 12h10"/></svg>',
    bericht: '<svg viewBox="0 0 24 24"><path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v7a2.5 2.5 0 0 1-2.5 2.5H10l-4 4v-4.2A2.5 2.5 0 0 1 4 13.5Z"/></svg>',
    antwoord: '<svg viewBox="0 0 24 24"><path d="M9 7 4 12l5 5"/><path d="M4 12h10a6 6 0 0 1 6 6"/></svg>',
    klik: '<svg viewBox="0 0 24 24"><path d="M9 4v9l2.5-2 2 4.5 2-1-2-4.4 3.2-.4Z"/></svg>',
    reel: '<svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="4"/><path d="m10.5 9.5 4 2.5-4 2.5Z"/></svg>',
    x: '<svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>'
  };

  // ── Verbinding ──
  var volg = 0, getekend = 0, vorige = '', laatstGetekend = 0;
  function api(body) {
    var nr = ++volg;
    return fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Sleutel': sleutel }, body: JSON.stringify(body), cache: 'no-store' })
      .then(function (r) { return r.json().catch(function () { return { ok: false, fout: T('js.geen_geldig_antwoord', { status: r.status }) }; }).then(function (j) { j._status = r.status; j._nr = nr; return j; }); });
  }
  /** Neemt een antwoord van de server over, tenzij er al een nieuwer antwoord getekend is. Tekent alleen opnieuw als er iets veranderde. */
  function zet(j, stil) {
    if (j._nr < getekend) return; getekend = j._nr;
    var nu = j.gezondheid.nu; j.gezondheid.nu = 0; var sig = JSON.stringify([j.automations, j.media, j.dagen, j.activiteit, j.totaal, j.week, j.gezondheid, j.mensen]); j.gezondheid.nu = nu;
    S = j; if (stil && sig === vorige && Date.now() - laatstGetekend < 60000) return;
    vorige = sig; laatstGetekend = Date.now(); teken();
  }
  function laad(stil) {
    return api({ a: 'staat', filter: filter }).then(function (j) {
      if (j._status === 403) { toonSlot(); return; }
      if (!j.ok) { if (!stil) toast(j.fout || T('js.mis_algemeen'), true); return; }
      zet(j, stil);
    }).catch(function () { if (!stil) toast(T('js.geen_verbinding_server'), true); });
  }
  function verwerk(j, goed) {
    if (j.automations) zet(j);
    if (j.ok && goed) toast(goed);
    if (!j.ok && j.fout) toast(j.fout, true);
    return j;
  }

  // ── Slot ──
  function toonSlot() { $('app').hidden = true; $('slot').hidden = false; setTimeout(function () { $('slot-sleutel').focus(); }, 50); }
  $('slot-form').addEventListener('submit', function (e) {
    e.preventDefault(); sleutel = $('slot-sleutel').value.trim(); $('slot-fout').textContent = '';
    api({ a: 'staat' }).then(function (j) {
      if (j._status === 403) { $('slot-fout').textContent = T('js.wachtwoord_fout'); return; }
      if (!j.ok) { $('slot-fout').textContent = j.fout || T('js.mis_algemeen'); return; }
      try { localStorage.setItem(OPSLAG, sleutel); } catch (er) {}
      start(j);
    }).catch(function () { $('slot-fout').textContent = T('js.geen_verbinding_server'); });
  });
  $('afmelden').addEventListener('click', function () { try { localStorage.removeItem(OPSLAG); } catch (er) {} sleutel = ''; clearInterval(timer); $('slot-sleutel').value = ''; toonSlot(); });
  function start(j) {
    $('slot').hidden = true; $('app').hidden = false; S = j; getekend = j._nr || 0; naarTab(tab);
    clearInterval(timer); timer = setInterval(function () { if (!document.hidden && !$('blad').open) laad(true); }, 15000);
  }

  // ── Tabs ──
  function naarTab(t) {
    tab = t;
    document.querySelectorAll('.tabs button').forEach(function (b) { if (b.dataset.tab === t) b.setAttribute('aria-current', 'page'); else b.removeAttribute('aria-current'); });
    ['overzicht', 'automations', 'mensen', 'activiteit', 'gezondheid'].forEach(function (p) { $('p-' + p).hidden = p !== t; });
    try { history.replaceState(null, '', '#' + t); } catch (e) {}
    teken();
  }
  document.querySelectorAll('.tabs button').forEach(function (b) { b.addEventListener('click', function () { naarTab(b.dataset.tab); }); });
  $('kop-status').addEventListener('click', function () { naarTab('gezondheid'); });

  // ── Hulp ──
  function tijd(ts) {
    if (!ts) return T('js.tijd_nooit');
    var s = Math.max(0, Math.round(Date.now() / 1000 - ts));
    if (s < 45) return T('js.tijd_zojuist'); if (s < 3600) return T('js.tijd_min', { n: Math.round(s / 60) });
    if (s < 86400) return T('js.tijd_uur', { n: Math.round(s / 3600) });
    var d = new Date(ts * 1000);
    return d.toLocaleDateString(LOC, { day: 'numeric', month: 'short' }) + ' ' + d.toLocaleTimeString(LOC, { hour: '2-digit', minute: '2-digit' });
  }
  function pct(a, b) { return b ? Math.round(100 * a / b) + '%' : '–'; }
  function mediaNaam(id) { return id === 'volgende' ? T('js.media_volgende') : id ? (S.media_naam[id] || T('js.media_ouder')) : T('js.media_alle'); }
  function mediaVan(id) { for (var i = 0; i < S.media.length; i++) if (S.media[i].id === id) return S.media[i]; return null; }
  function duim(m) {
    if (m && m.thumb) return '<img class="duim" src="' + esc(m.thumb) + '" alt="" loading="lazy" referrerpolicy="no-referrer">';
    return '<span class="duim duim-leeg">' + ICON.reel + '</span>';
  }
  function autoNaam(id) { for (var i = 0; i < S.automations.length; i++) if (S.automations[i].id === id) return S.automations[i].naam; return T('js.auto_verwijderd'); }
  var toastT;
  function toast(t, mis) { var el = $('toast'); el.textContent = t; el.classList.toggle('mis', !!mis); el.classList.add('zicht'); clearTimeout(toastT); toastT = setTimeout(function () { el.classList.remove('zicht'); }, 3400); }

  // ── Stand van de gezondheid (voor de pil bovenin en de lijst) ──
  var dataOpen = null; // null = nog niet getest; true = de database is via internet op te vragen
  function checks() {
    var g = S.gezondheid, nu = g.nu, uit = [], actief = S.automations.some(function (a) { return a.aan; });
    if (!g.token) uit.push({ s: 'mis', t: T('chk.token'), p: T('chk.token_leeg') });
    else if (!g.token_check_ts) uit.push({ s: 'let', t: T('chk.token'), p: T('chk.token_nog_niet'), knop: 'controle' });
    else if (!g.token_ok) uit.push({ s: 'mis', t: T('chk.token'), p: (g.token_fout || T('chk.token_werkt_niet')) + ' ' + T('chk.token_stap'), knop: 'controle' });
    else uit.push({ s: 'ok', t: T('chk.token'), p: (g.token_verloopt ? T('chk.token_ok_verloopt', { datum: datum(g.token_verloopt) }) : T('chk.token_ok')) + ' ' + T('chk.gecontroleerd', { tijd: tijd(g.token_check_ts) }) });
    uit.push(g.ig_id ? { s: 'ok', t: T('chk.account'), p: '@' + (g.ig_username || '?') + (g.volgers ? ', ' + T('chk.volgers', { n: nl.format(+g.volgers) }) : '') + '.' }
      : { s: g.token ? 'mis' : 'uit', t: T('chk.account'), p: g.token_ok ? T('chk.account_stap') : T('chk.account_niet') });
    // Zonder App Review stuurt Meta meldingen alleen voor accounts met een rol op de app. Geen melding is dus normaal: de eigen controle is de hoofdweg.
    var wa = T('chk.webhook_adres', { adres: g.webhook_adres });
    if (!g.geheim) uit.push({ s: 'uit', t: T('chk.webhook'), p: T('chk.webhook_geen_geheim') });
    else if (g.laatste_webhook > nu - 86400) uit.push({ s: 'ok', t: T('chk.webhook'), p: T('chk.webhook_laatste', { tijd: tijd(g.laatste_webhook) }), code: wa });
    else uit.push({ s: 'uit', t: T('chk.webhook'), p: (g.laatste_webhook ? T('chk.webhook_laatste', { tijd: tijd(g.laatste_webhook) }) : T('chk.webhook_nog_geen')) + ' ' + T('chk.webhook_normaal') + (g.webhook_aangemeld ? ' ' + T('chk.webhook_aangemeld', { wat: g.webhook_aangemeld }) : ''), code: wa, knop: 'webhook' });
    var c = g.laatste_controle, cron = g.laatste_cron;
    if (!actief) uit.push(cron > nu - 180 ? { s: 'ok', t: T('chk.controle'), p: T('chk.controle_rust', { tijd: tijd(cron) }) } : { s: 'mis', t: T('chk.controle'), p: cron ? T('chk.cron_weg', { tijd: tijd(cron) }) : T('chk.cron_nooit'), code: g.cron_regel });
    else if (cron > nu - 180) uit.push({ s: g.controle_fout ? 'let' : 'ok', t: T('chk.controle'), p: (g.controle_fout ? T('chk.controle_fout', { fout: g.controle_fout }) : T('chk.controle_laatste', { tijd: tijd(c) })) + ' ' + T('chk.cron_elke_minuut') });
    else uit.push({ s: 'mis', t: T('chk.controle'), p: cron ? T('chk.cron_weg', { tijd: tijd(cron) }) : T('chk.cron_nooit'), code: g.cron_regel, knop: 'controle' });
    if (g.lees_pauze_tot > nu) uit.push({ s: 'let', t: T('chk.leespauze'), p: T('chk.leespauze_p', { klok: klok(g.lees_pauze_tot) }) });
    if (g.token_ok) uit.push({ s: g.gebruik >= 60 ? 'let' : 'ok', t: T('chk.verbruik'), p: T('chk.verbruik_p', { n: g.gebruik }) + (g.gebruik >= 60 ? ' ' + T('chk.verbruik_hoog') : '') });
    if (g.knop_kan !== null) uit.push(g.knop_kan ? { s: 'ok', t: T('chk.knoppen'), p: T('chk.knoppen_ok') } : { s: 'let', t: T('chk.knoppen'), p: T('chk.knoppen_nee') });
    if (g.token_ok) uit.push({ s: g.aanroepen_uur >= g.budget_uur ? 'let' : 'ok', t: T('chk.aanroepen'), p: T('chk.aanroepen_p', { n: g.aanroepen_uur, budget: g.budget_uur }) });
    if (g.pauze_tot > nu) uit.push({ s: 'let', t: T('chk.pauze'), p: T('chk.pauze_p', { klok: klok(g.pauze_tot) }) });
    uit.push({ s: g.wachtrij > 5 ? 'let' : 'ok', t: T('chk.wachtrij'), p: g.wachtrij ? Tn('chk.wachtrij', g.wachtrij) : T('chk.wachtrij_leeg') });
    uit.push({ s: g.fouten_24u ? 'let' : 'ok', t: T('chk.fouten'), p: g.fouten_24u ? T('chk.fouten_p', { n: g.fouten_24u }) : T('chk.fouten_geen') });
    if (dataOpen === true) uit.push({ s: 'mis', t: T('chk.data'), p: T('chk.data_open') });
    else if (g.data_in_webroot) uit.push({ s: 'let', t: T('chk.data'), p: T('chk.data_webmap') });
    else uit.push({ s: 'ok', t: T('chk.data'), p: T('chk.data_ok') + (g.config_in_map ? ' ' + T('chk.config_in_map') : '') });
    return uit;
  }

  // ── Tekenen ──
  function teken() {
    if (!S) return;
    var g = S.gezondheid;
    $('kop-account').textContent = g.ig_username ? '@' + g.ig_username : T('js.niet_gekoppeld');
    document.querySelectorAll('.vb-account').forEach(function (e) { e.textContent = '@' + (g.ig_username || T('js.jouwaccount')); });
    if (g.data_proef && dataOpen === null) { dataOpen = false; fetch(g.data_proef, { method: 'HEAD', cache: 'no-store' }).then(function (r) { dataOpen = r.status === 200; if (dataOpen) teken(); }).catch(function () {}); }
    var cs = checks(), ergst = cs.some(function (c) { return c.s === 'mis'; }) ? 'mis' : cs.some(function (c) { return c.s === 'let'; }) ? 'let' : 'ok';
    var st = $('kop-status'); st.dataset.stand = ergst;
    st.querySelector('span').textContent = T('js.status_' + ergst);
    if (tab === 'overzicht') tekenOverzicht();
    if (tab === 'automations') tekenAutos();
    if (tab === 'mensen') tekenMensen();
    if (tab === 'activiteit') tekenActiviteit();
    if (tab === 'gezondheid') tekenGezondheid(cs);
  }

  function tekenOverzicht() {
    var w = S.week, t = S.totaal, aan = S.automations.filter(function (a) { return a.aan; });
    var zin;
    if (!t.reacties) zin = esc(T('ov.leeg')) + '<small>' + esc(aan.length ? Tn('ov.leeg_aan', aan.length) : T('ov.leeg_uit')) + '</small>';
    else zin = esc(T('ov.week', { reacties: '\u0001', geklikt: w.geklikt || 0 })).replace('\u0001', '<em>' + esc(Tn('w.reacties', w.reacties)) + '</em>') + '<small>' + esc(T('ov.week_sub', { verstuurd: w.verstuurd || 0, totaal: t.reacties, geklikt: t.geklikt || 0 })) + '</small>';
    $('ov-zin').innerHTML = zin;
    var vandaag = S.dagen[S.dagen.length - 1];
    $('ov-cijfers').innerHTML = '<dl class="cijfer"><dt>' + esc(T('ov.c_reacties')) + '</dt><dd>' + nl.format(t.reacties) + '</dd><p>' + esc(T('ov.c_vandaag', { n: vandaag.reacties })) + '</p></dl>'
      + '<dl class="cijfer"><dt>' + esc(T('ov.c_verstuurd')) + '</dt><dd>' + nl.format(t.verstuurd || 0) + '</dd><p>' + esc(T('ov.c_van_reacties', { pct: pct(t.verstuurd, t.reacties) })) + '</p></dl>'
      + '<dl class="cijfer"><dt>' + esc(T('ov.c_geklikt')) + '</dt><dd>' + nl.format(t.geklikt || 0) + '</dd><p>' + esc(T('ov.c_geklikt_sub')) + '</p></dl>'
      + '<dl class="cijfer"><dt>' + esc(T('ov.c_ratio')) + '</dt><dd>' + (t.verstuurd ? Math.round(100 * t.geklikt / t.verstuurd) + '<small>%</small>' : '–') + '</dd><p>' + esc(T('ov.c_ratio_sub')) + '</p></dl>';
    grafiek(S.dagen);
    var rij = function (kop, naam, c) { return '<li class="rij">' + kop + '<div><div class="rij-naam">' + naam + '</div><div class="rij-sub">' + esc(T('ov.rij_sub', { reacties: c.reacties, verstuurd: c.verstuurd }) + (c.fout ? ', ' + T('ov.rij_mislukt', { n: c.fout }) : '')) + '</div></div><div class="rij-getal">' + esc(Tn('w.kliks', c.geklikt)) + '<small><span class="ratio">' + (c.ratio == null ? '–' : c.ratio + '%') + '</span></small></div></li>'; };
    $('ov-autos').innerHTML = S.automations.length ? '<ul class="rijen">' + S.automations.map(function (a) { return rij('<span class="duim duim-leeg">' + ICON.bericht + '</span>', esc(a.naam), a.cijfers); }).join('') + '</ul>' : '<p class="leeg">' + esc(T('ov.geen_autos')) + '</p>';
    var posts = S.media.filter(function (m) { return m.cijfers; });
    $('ov-reels').innerHTML = posts.length ? '<ul class="rijen">' + posts.map(function (m) { return rij(duim(m), m.permalink ? '<a href="' + esc(m.permalink) + '" target="_blank" rel="noopener">' + esc(m.naam) + '</a>' : esc(m.naam), m.cijfers); }).join('') + '</ul>' : '<p class="leeg">' + esc(T('ov.geen_posts')) + '</p>';
  }

  function grafiek(dagen) {
    var W = 720, H = 200, L0 = 28, B = 22, Tp = 8, n = dagen.length, bw = (W - L0) / n;
    var max = Math.max(4, Math.max.apply(null, dagen.map(function (d) { return Math.max(d.reacties, d.kliks); })));
    var stap = max <= 6 ? 2 : max <= 12 ? 4 : Math.ceil(max / 4 / 5) * 5; max = Math.ceil(max / stap) * stap;
    var y = function (v) { return Tp + (H - Tp - B) * (1 - v / max); };
    var s = '<svg viewBox="0 0 ' + W + ' ' + H + '" role="img" aria-label="' + esc(T('ov.grafiek_label')) + '">';
    for (var v = 0; v <= max; v += stap) s += '<line class="rooster" x1="' + L0 + '" x2="' + W + '" y1="' + y(v) + '" y2="' + y(v) + '"/><text x="' + (L0 - 8) + '" y="' + (y(v) + 4) + '" text-anchor="end">' + v + '</text>';
    var pts = [];
    dagen.forEach(function (d, i) {
      var x = L0 + i * bw, w = Math.max(3, bw * .62), xm = x + (bw - w) / 2;
      s += '<g class="doel" data-i="' + i + '"><rect x="' + x + '" y="' + Tp + '" width="' + bw + '" height="' + (H - Tp - B) + '" fill="transparent"/>';
      if (d.reacties) s += '<rect class="b-reacties" x="' + xm + '" y="' + y(d.reacties) + '" width="' + w + '" height="' + (y(0) - y(d.reacties)) + '" rx="3"/>';
      if (d.verstuurd) s += '<rect class="b-verstuurd" x="' + (xm + w * .25) + '" y="' + y(d.verstuurd) + '" width="' + (w * .5) + '" height="' + (y(0) - y(d.verstuurd)) + '" rx="2"/>';
      s += '</g>';
      pts.push([x + bw / 2, y(d.kliks)]);
      if ((i % 7 === 0 && n - 1 - i >= 4) || i === n - 1) { var dt = new Date(d.dag + 'T12:00:00'); s += '<text x="' + (x + bw / 2) + '" y="' + (H - 4) + '" text-anchor="middle">' + dt.getDate() + '-' + (dt.getMonth() + 1) + '</text>'; }
    });
    s += '<polyline class="l-klik" points="' + pts.map(function (p) { return p[0].toFixed(1) + ',' + p[1].toFixed(1); }).join(' ') + '"/>';
    dagen.forEach(function (d, i) { if (d.kliks) s += '<circle class="p-klik" cx="' + pts[i][0].toFixed(1) + '" cy="' + pts[i][1].toFixed(1) + '" r="3.5"/>'; });
    s += '</svg><p class="grafiek-tip" id="tip">' + esc(T('ov.grafiek_tip')) + '</p>';
    var el = $('ov-grafiek'); el.innerHTML = s;
    el.querySelectorAll('.doel').forEach(function (g) {
      var toon = function () { var d = dagen[+g.dataset.i]; $('tip').textContent = new Date(d.dag + 'T12:00:00').toLocaleDateString(LOC, { weekday: 'long', day: 'numeric', month: 'long' }) + ': ' + T('ov.grafiek_dag', { reacties: d.reacties, verstuurd: d.verstuurd, kliks: d.kliks }); };
      g.addEventListener('mouseenter', toon); g.addEventListener('click', toon);
    });
  }

  function tekenAutos() {
    var el = $('auto-lijst');
    if (!S.automations.length) { el.innerHTML = '<div class="glas"><p class="leeg">' + esc(T('au.leeg')) + '</p></div>'; return; }
    el.innerHTML = S.automations.map(function (a) {
      var c = a.cijfers, lab = a.aan ? (a.test ? '<span class="label label-test">' + esc(T('au.testmodus')) + '</span>' : '<span class="label label-aan">' + esc(T('au.aan')) + '</span>') : '<span class="label label-uit">' + esc(T('au.uit')) + '</span>';
      return '<article class="glas auto" data-id="' + a.id + '" tabindex="0" aria-label="' + esc(T('au.bewerken', { naam: a.naam })) + '">'
        + '<label class="schakel" data-stop><input type="checkbox" data-wissel="' + a.id + '"' + (a.aan ? ' checked' : '') + ' aria-label="' + esc(T('au.aan_uit', { naam: a.naam })) + '"><span class="schuif" aria-hidden="true"></span></label>'
        + '<div><h3>' + esc(a.naam) + ' ' + lab + '</h3><div class="regel">' + a.trefwoorden.map(function (w) { return '<span class="chip">' + esc(w) + '</span>'; }).join('')
        + '<span>' + esc(T('au.bij')) + ' <b>' + esc(mediaNaam(a.media_id)) + '</b></span><span>r/' + esc(a.slug) + '</span></div></div>'
        + '<div class="auto-cijfers"><div>' + c.reacties + '<small>' + esc(T('au.reacties')) + '</small></div><div>' + c.verstuurd + '<small>' + esc(T('au.verstuurd')) + '</small></div><div>' + (c.ratio == null ? '–' : c.ratio + '%') + '<small>' + esc(T('au.klikratio')) + '</small></div></div></article>';
    }).join('');
    el.querySelectorAll('.auto').forEach(function (k) {
      k.addEventListener('click', function (e) { if (e.target.closest('[data-stop]')) return; openBlad(+k.dataset.id); });
      k.addEventListener('keydown', function (e) { if ((e.key === 'Enter' || e.key === ' ') && e.target === k) { e.preventDefault(); openBlad(+k.dataset.id); } });
    });
    el.querySelectorAll('[data-wissel]').forEach(function (i) {
      i.addEventListener('change', function () {
        var aan = i.checked; i.disabled = true;
        api({ a: 'wissel', id: +i.dataset.wissel, aan: aan, filter: filter }).then(function (j) { i.disabled = false; verwerk(j, aan ? T('au.aangezet') : T('au.uitgezet')); })
          .catch(function () { i.disabled = false; i.checked = !aan; toast(T('js.lukte_niet_verbinding'), true); });
      });
    });
  }

  // ── Mensen en labels ──
  var mensFilter = '', mensZoek = '', labelOpen = '';
  function labelSoort(l) { return l === l.toUpperCase() && /[A-Z]/.test(l) ? 'woord' : (l === T('lab.geklikt')) ? 'goed' : ''; }
  function labelNaam(l) { return l === 'geklikt' ? T('lab.geklikt') : l === 'test' ? T('lab.test') : l; }
  function tekenMensen() {
    // Staat iemand net een label te typen, dan de lijst niet opnieuw opbouwen (anders is de invoer weg).
    var actief = document.activeElement; if (actief && actief.classList && actief.classList.contains('lab-in')) return;
    var alle = (S.mensen || []).map(function (p) { p.autoT = p.auto.map(labelNaam); return p; }), tel = {};
    alle.forEach(function (p) { p.autoT.concat(p.eigen).forEach(function (l) { tel[l] = (tel[l] || 0) + 1; }); });
    var namen = Object.keys(tel).sort(function (a, b) { return tel[b] - tel[a] || a.localeCompare(b); });
    if (mensFilter && !tel[mensFilter]) mensFilter = '';
    $('mens-labels').innerHTML = '<button type="button" data-l="" aria-pressed="' + (mensFilter === '' ? 'true' : 'false') + '">' + esc(T('me.iedereen')) + '<small>' + alle.length + '</small></button>'
      + namen.map(function (l) { return '<button type="button" data-l="' + esc(l) + '" aria-pressed="' + (mensFilter === l ? 'true' : 'false') + '">' + esc(l) + '<small>' + tel[l] + '</small></button>'; }).join('');
    $('mens-labels').querySelectorAll('button').forEach(function (b) { b.addEventListener('click', function () { mensFilter = b.dataset.l; tekenMensen(); }); });
    var z = mensZoek.toLowerCase().replace(/^@/, '');
    var lijst = alle.filter(function (p) {
      if (mensFilter && p.autoT.indexOf(mensFilter) < 0 && p.eigen.indexOf(mensFilter) < 0) return false;
      return !z || p.username.toLowerCase().indexOf(z) >= 0 || p.autoT.concat(p.eigen).some(function (l) { return l.toLowerCase().indexOf(z) >= 0; });
    });
    $('mens-telling').textContent = !alle.length ? '' : lijst.length === alle.length ? Tn('me.telling', alle.length) : T('me.telling_van', { n: lijst.length, totaal: alle.length });
    var el = $('mens-lijst');
    if (!alle.length) { el.innerHTML = '<li class="leeg">' + esc(T('me.leeg')) + '</li>'; return; }
    if (!lijst.length) { el.innerHTML = '<li class="leeg">' + esc(T('me.niemand')) + '</li>'; return; }
    el.innerHTML = lijst.slice(0, 300).map(function (p) {
      var sub = Tn('w.reacties', p.reacties) + ', ' + Tn('w.berichten', p.berichten) + (p.kliks ? ', ' + T('me.keer_geklikt', { n: p.kliks }) : '');
      var labs = p.autoT.map(function (l) { return '<span class="lab ' + labelSoort(l) + '">' + esc(l) + '</span>'; }).join('')
        + p.eigen.map(function (l) { return '<span class="lab eigen">' + esc(l) + '<button type="button" data-weg="' + esc(l) + '" aria-label="' + esc(T('me.label_weg', { label: l })) + '">' + ICON.x + '</button></span>'; }).join('')
        + (labelOpen === p.persoon ? '<input class="lab-in" type="text" maxlength="30" placeholder="' + esc(T('me.nieuw_label')) + '" aria-label="' + esc(T('me.nieuw_label_voor', { naam: p.username })) + '">' : '<button class="lab-erbij" type="button" data-erbij>' + esc(T('me.label_erbij')) + '</button>');
      return '<li class="mens" data-p="' + esc(p.persoon) + '" data-u="' + esc(p.username) + '"><div class="mens-naam"><a href="https://www.instagram.com/' + encodeURIComponent(p.username) + '/" target="_blank" rel="noopener">@' + esc(p.username) + '</a></div>'
        + '<time class="mens-tijd">' + tijd(p.laatst) + '</time><div class="mens-sub">' + esc(sub) + '</div><div class="mens-labels">' + labs + '</div></li>';
    }).join('') + (lijst.length > 300 ? '<li class="leeg">' + esc(T('me.meer', { n: lijst.length - 300 })) + '</li>' : '');
    el.querySelectorAll('.mens').forEach(function (li) {
      var u = li.dataset.u;
      var zetLabel = function (label, aan) { api({ a: 'label', username: u, label: label, aan: aan, filter: filter }).then(function (j) { labelOpen = ''; verwerk(j); tekenMensen(); }).catch(function () { toast(T('js.lukte_niet_verbinding'), true); }); };
      var plus = li.querySelector('[data-erbij]'); if (plus) plus.addEventListener('click', function () { labelOpen = li.dataset.p; tekenMensen(); var i = document.querySelector('.mens[data-p="' + CSS.escape(li.dataset.p) + '"] .lab-in'); if (i) i.focus(); });
      var inp = li.querySelector('.lab-in');
      if (inp) {
        inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); var v = inp.value.trim(); if (v) { inp.blur(); zetLabel(v, true); } } else if (e.key === 'Escape') { labelOpen = ''; inp.blur(); tekenMensen(); } });
        inp.addEventListener('blur', function () { setTimeout(function () { if (labelOpen === li.dataset.p && !inp.value.trim()) { labelOpen = ''; tekenMensen(); } }, 150); });
      }
      li.querySelectorAll('[data-weg]').forEach(function (b) { b.addEventListener('click', function () { zetLabel(b.dataset.weg, false); }); });
    });
  }
  $('mens-zoek').addEventListener('input', function () { mensZoek = this.value.trim(); tekenMensen(); });

  function stap(soort, ikoon, tekst) { return '<span class="stap ' + soort + '">' + ICON[ikoon] + esc(tekst) + '</span>'; }
  function tekenActiviteit() {
    var el = $('act-lijst'), rijen = S.activiteit;
    if (!rijen.length) { el.innerHTML = '<li class="leeg">' + esc(filter === 'alles' ? T('ac.leeg') : T('ac.leeg_filter')) + '</li>'; return; }
    el.innerHTML = rijen.map(function (r) {
      var dm = r.dm_status === 'verstuurd' ? stap('goed', 'vink', r.vorm === 'knop' ? T('ac.verstuurd_knop') : T('ac.verstuurd')) : r.dm_status === 'fout' ? stap('mis', 'kruis', T('ac.mislukt'))
        : r.dm_status === 'overgeslagen' ? stap('', 'streep', T('ac.overgeslagen')) : stap('wacht', 'klok', r.dm_status === 'bezig' ? T('ac.bezig') : T('ac.wachtrij'));
      var an = r.antwoord_status === 'geplaatst' ? stap('goed', 'antwoord', T('ac.antwoord')) : r.antwoord_status === 'fout' ? stap('mis', 'antwoord', T('ac.antwoord_mislukt'))
        : r.antwoord_status === 'wacht' && r.dm_status !== 'fout' ? stap('wacht', 'antwoord', T('ac.antwoord_volgt')) : '';
      var kl = r.dm_status === 'verstuurd' ? (r.kliks ? stap('goed', 'klik', T('ac.geklikt') + (r.kliks > 1 ? ' (' + r.kliks + 'x)' : '')) : stap('', 'klik', T('ac.niet_geklikt'))) : '';
      var fout = [r.dm_fout, r.antwoord_fout].filter(Boolean).join('. ');
      var wie = r.gewist ? esc(T('ac.gewist')) : '@' + esc(r.username || T('ac.onbekend'));
      return '<li class="act"><div class="act-wie">' + wie + ' <span>' + esc(T('ac.bij', { post: mediaNaam(r.media_id), automation: autoNaam(r.automation_id) }) + (r.test ? ', ' + T('lab.test') : '')) + '</span></div>'
        + '<time class="act-tijd" datetime="' + new Date(r.ts * 1000).toISOString() + '">' + tijd(r.ts) + '</time>'
        + (r.tekst ? '<div class="act-tekst">“' + esc(r.tekst) + '”</div>' : '')
        + '<div class="stappen">' + dm + an + kl + (r.dm_status === 'fout' && !r.gewist ? '<button class="knop knop-tweede knop-klein" type="button" data-opnieuw="' + r.id + '">' + esc(T('ac.opnieuw')) + '</button>' : '')
        + (r.dm_status === 'verstuurd' && r.antwoord_status === 'fout' && !r.gewist ? '<button class="knop knop-tweede knop-klein" type="button" data-antwoord="' + r.id + '">' + esc(T('ac.antwoord_opnieuw')) + '</button>' : '') + '</div>'
        + (fout ? '<div class="act-fout">' + esc(fout) + '</div>' : '') + '</li>';
    }).join('');
    el.querySelectorAll('[data-opnieuw]').forEach(function (b) {
      b.addEventListener('click', function () { b.disabled = true; api({ a: 'opnieuw', id: +b.dataset.opnieuw, filter: filter }).then(function (j) { verwerk(j, j.uitkomst === 'verstuurd' ? T('ac.t_verstuurd') : null);
        if (j.uitkomst === 'pauze' || j.uitkomst === 'later') toast(T('ac.t_wachtrij'));
        else if (j.uitkomst && j.uitkomst !== 'verstuurd') toast(T('ac.t_nog_niet'), true); }).catch(function () { b.disabled = false; toast(T('js.geen_verbinding'), true); }); });
    });
    el.querySelectorAll('[data-antwoord]').forEach(function (b) {
      b.addEventListener('click', function () { b.disabled = true; api({ a: 'antwoord_opnieuw', id: +b.dataset.antwoord, filter: filter }).then(function (j) { verwerk(j, j.gelukt ? T('ac.t_antwoord') : null); if (!j.gelukt) toast(T('ac.t_antwoord_niet'), true); }).catch(function () { b.disabled = false; toast(T('js.geen_verbinding'), true); }); });
    });
  }
  document.querySelectorAll('#filters button').forEach(function (b) {
    b.addEventListener('click', function () {
      filter = b.dataset.filter; document.querySelectorAll('#filters button').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
      $('act-lijst').innerHTML = '<li><div class="skelet"></div><div class="skelet"></div></li>'; laad();
    });
  });

  function tekenGezondheid(cs) {
    $('checks').innerHTML = cs.map(function (c) {
      var ik = c.s === 'ok' ? ICON.vink : c.s === 'mis' ? ICON.kruis : c.s === 'let' ? ICON.uitroep : ICON.streep;
      var knop = c.knop === 'webhook' ? '<button class="knop knop-tweede knop-klein" type="button" data-actie="webhook">' + esc(T('ge.webhook_aan')) + '</button>' : c.knop === 'controle' ? '<button class="knop knop-tweede knop-klein" type="button" data-actie="controle">' + esc(T('ge.nu_controleren')) + '</button>' : '';
      return '<li class="check" data-stand="' + c.s + '"><span class="check-stip" aria-hidden="true">' + ik + '</span><div><b>' + esc(c.t) + '</b><p>' + esc(c.p) + '</p>' + (c.code ? '<code>' + esc(c.code) + '</code>' : '') + '</div>' + knop + '</li>';
    }).join('');
    $('checks').querySelectorAll('[data-actie]').forEach(function (b) { b.addEventListener('click', function () { b.dataset.actie === 'webhook' ? webhookAan(b) : controleer(b); }); });
    var log = S.gezondheid.logboek;
    $('logboek').innerHTML = log.length ? log.map(function (l) { return '<li class="s-' + esc(l.soort) + '"><time>' + tijd(l.ts) + '</time><span>' + esc(l.tekst) + '</span></li>'; }).join('') : '<li><span></span><span>' + esc(T('ge.log_leeg')) + '</span></li>';
  }
  function controleer(b) {
    b.disabled = true; var oud = b.textContent; b.textContent = T('ge.bezig');
    api({ a: 'controle_nu', filter: filter }).then(function (j) { b.disabled = false; b.textContent = oud; verwerk(j, T('ge.gecontroleerd') + ' ' + (j.nieuw ? Tn('ge.nieuw', j.nieuw) : T('ge.niets_nieuws'))); });
  }
  function webhookAan(b) {
    b.disabled = true; api({ a: 'webhook_aanmelden', filter: filter }).then(function (j) { b.disabled = false; verwerk(j, T('ge.webhook_staat_aan')); });
  }
  $('nu-controleren').addEventListener('click', function () { controleer($('nu-controleren')); });
  $('wis-form').addEventListener('submit', function (e) {
    e.preventDefault(); var n = $('wis-naam').value.trim(); if (!n) return;
    if (!confirm(T('ge.wis_zeker', { naam: n }))) return;
    api({ a: 'verwijder_persoon', username: n, filter: filter }).then(function (j) { verwerk(j); $('wis-uit').textContent = j.ok ? (j.aantal ? Tn('ge.gewist', j.aantal) : T('ge.niets_gevonden')) : ''; $('wis-naam').value = ''; });
  });

  // ── Probeer een reactie ──
  var probeerT;
  $('probeer-tekst').addEventListener('input', function () {
    clearTimeout(probeerT); var t = this.value;
    probeerT = setTimeout(function () {
      if (!t.trim()) { $('probeer-uit').innerHTML = ''; return; }
      api({ a: 'probeer', tekst: t }).then(function (j) {
        var m = j.match;
        $('probeer-uit').innerHTML = !m ? esc(T('pr.geen')) : esc(T('pr.reageert', { naam: m.naam })).replace(esc(m.naam), '<b>' + esc(m.naam) + '</b>') + (m.aan ? (m.test ? ' ' + esc(T('pr.test')) : '') : ' ' + esc(T('pr.uit'))) + ' ' + esc(T('pr.bericht')) + '<pre>' + linkjes(esc(m.dm)) + '</pre>' + (m.knoppen || []).map(function (k) { return '<div>' + esc(T('pr.knop')) + ' <b>' + esc(k.title) + '</b></div>'; }).join('');
      });
    }, 250);
  });
  function linkjes(s) { return s.replace(/https?:\/\/[^\s<]+/g, function (u) { return '<a href="' + u + '" target="_blank" rel="noopener">' + u + '</a>'; }); }

  // ── Bewerken ──
  var blad = $('blad'), form = $('blad-form'), woorden = [];
  function zetFout(veld, tekst) {
    var em = form.querySelector('[data-voor="' + veld + '"]'); if (em) em.textContent = tekst || '';
    var inp = form.elements[veld]; if (inp && inp.setAttribute) inp.setAttribute('aria-invalid', tekst ? 'true' : 'false');
  }
  var VELDEN = ['naam', 'trefwoorden', 'dm_tekst', 'link', 'link2', 'slug', 'knop', 'knop2'];
  function openBlad(id) {
    var a = id ? S.automations.filter(function (x) { return x.id === id; })[0] : null;
    bewerk = a ? a.id : 0;
    $('blad-titel').textContent = a ? a.naam : T('bl.nieuw');
    $('blad-weg').hidden = !a;
    VELDEN.forEach(function (v) { zetFout(v, ''); });
    form.elements.stijl.value = a ? (a.stijl || 'tekst') : 'knop'; form.elements.knop.value = a ? (a.knop || '') : ''; form.elements.knop2.value = a ? (a.knop2 || '') : '';
    var mo = $('meer-opties'); if (mo) mo.open = !!(a && (a.knop2 || a.link2));
    var sel = $('media-keus'); sel.innerHTML = '<option value="">' + esc(T('js.media_alle')) + '</option><option value="volgende">' + esc(T('bl.media_volgende')) + '</option>' + S.media.map(function (m) { return '<option value="' + esc(m.id) + '">' + esc(m.naam) + '</option>'; }).join('');
    if (a && a.media_id && a.media_id !== 'volgende' && !mediaVan(a.media_id)) sel.innerHTML += '<option value="' + esc(a.media_id) + '">' + esc(T('js.media_ouder')) + '</option>';
    form.elements.naam.value = a ? a.naam : '';
    woorden = a ? a.trefwoorden.slice() : []; tekenWoorden();
    sel.value = a ? a.media_id : '';
    form.elements.dm_tekst.value = a ? a.dm_tekst : T('bl.std_bericht');
    form.elements.link.value = a ? a.link : ''; form.elements.link2.value = a ? a.link2 : '';
    form.elements.slug.value = a ? a.slug : '';
    form.elements.aan.checked = a ? !!a.aan : false; form.elements.test.checked = a ? !!a.test : true;
    form.elements.testers.value = a ? a.testers.map(function (t) { return '@' + t; }).join(', ') : '';
    tekenAntwoorden(a ? a.antwoorden : [T('bl.std_antwoord_1'), T('bl.std_antwoord_2'), T('bl.std_antwoord_3')]);
    $('slug-voor').textContent = S.korte_link.replace(/^https?:\/\//, '') + (S.mooie_links ? '/' : '/?s=');
    testersZicht(); voorbeeld();
    blad.showModal(); setTimeout(function () { form.elements.naam.focus(); }, 30);
  }
  function tekenWoorden() {
    var box = $('woorden'), inp = $('woord-in');
    box.querySelectorAll('.chip').forEach(function (c) { c.remove(); });
    woorden.forEach(function (w, i) {
      var c = document.createElement('span'); c.className = 'chip'; c.innerHTML = esc(w) + '<button type="button" aria-label="' + esc(T('bl.woord_weg', { woord: w })) + '">' + ICON.x + '</button>';
      c.querySelector('button').addEventListener('click', function () { woorden.splice(i, 1); tekenWoorden(); voorbeeld(); inp.focus(); });
      box.insertBefore(c, inp);
    });
  }
  function woordErbij() {
    var inp = $('woord-in'); inp.value.split(',').forEach(function (w) { w = w.trim().toLowerCase(); if (w && woorden.indexOf(w) < 0) woorden.push(w); });
    inp.value = ''; tekenWoorden(); voorbeeld(); zetFout('trefwoorden', '');
  }
  $('woord-in').addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); woordErbij(); }
    else if (e.key === 'Backspace' && !this.value && woorden.length) { woorden.pop(); tekenWoorden(); voorbeeld(); }
  });
  $('woord-in').addEventListener('blur', woordErbij);
  $('woorden').addEventListener('click', function (e) { if (e.target === this) $('woord-in').focus(); });
  function tekenAntwoorden(lijst) {
    var ol = $('antwoorden'); ol.innerHTML = '';
    lijst.forEach(function (t) { antwoordRij(t); });
    if (!lijst.length) antwoordRij('');
  }
  function antwoordRij(t) {
    var li = document.createElement('li'), ol = $('antwoorden');
    li.innerHTML = '<input type="text" maxlength="200" aria-label="' + esc(T('bl.antwoord_label')) + '" value="' + esc(t) + '"><button type="button" class="sluit" aria-label="' + esc(T('bl.antwoord_weg')) + '">' + ICON.x + '</button>';
    li.querySelector('button').addEventListener('click', function () { li.remove(); if (!ol.children.length) antwoordRij(''); voorbeeld(); });
    li.querySelector('input').addEventListener('input', voorbeeld);
    ol.appendChild(li);
  }
  $('antwoord-erbij').addEventListener('click', function () { antwoordRij(''); var i = $('antwoorden').lastElementChild.querySelector('input'); i.focus(); });
  function testersZicht() { $('testers-veld').hidden = !form.elements.test.checked; }
  form.elements.test.addEventListener('change', testersZicht);
  document.querySelectorAll('[data-zet]').forEach(function (b) {
    b.addEventListener('click', function () {
      var ta = $('dm-tekst'), s = ta.selectionStart, e = ta.selectionEnd, z = b.dataset.zet;
      ta.value = ta.value.slice(0, s) + z + ta.value.slice(e); ta.focus(); ta.selectionStart = ta.selectionEnd = s + z.length; voorbeeld();
    });
  });
  ['input', 'change'].forEach(function (ev) { form.addEventListener(ev, function (e) { if (e.target.name === 'slug') e.target.value = e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ''); voorbeeld(); }); });
  form.elements.naam.addEventListener('input', function () {
    if (bewerk) return; var s = form.elements.slug;
    if (!s.dataset.zelf) s.value = this.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 30);
  });
  form.elements.slug.addEventListener('input', function () { this.dataset.zelf = '1'; });
  function bytes(s) { return new TextEncoder().encode(s).length; }
  function dmUit() {
    var slug = form.elements.slug.value || 'link', basis = S.mooie_links ? S.korte_link + '/' + slug + '/abcdefg' : S.korte_link + '/?s=' + slug + '&c=abcdefg';
    return form.elements.dm_tekst.value.replace(/\{link\}/g, basis).replace(/\{link2\}/g, basis + (S.mooie_links ? '/2' : '&w=2')).replace(/\{naam\}/g, '@' + T('bl.iemand')).replace(/[ \t]+\n/g, '\n').trim();
  }
  function voorbeeld() {
    var dm = dmUit(), n = bytes(dm), t = $('bytes'), knop = form.elements.stijl.value === 'knop';
    form.classList.toggle('blad-form-knop', knop); form.classList.toggle('blad-form-tekst', !knop);
    var max = knop ? 610 : 1000, aantal = knop ? form.elements.dm_tekst.value.length : n;
    t.textContent = aantal + ' / ' + max + ' ' + (knop ? T('bl.tekens') : T('bl.bytes')); if (aantal > max) t.setAttribute('data-te-lang', ''); else t.removeAttribute('data-te-lang');
    var kn = []; if (knop && form.elements.knop.value.trim()) kn.push(form.elements.knop.value.trim()); if (knop && form.elements.link2.value.trim() && form.elements.knop2.value.trim()) kn.push(form.elements.knop2.value.trim());
    $('vb-knoppen').innerHTML = kn.map(function (k) { return '<span>' + esc(k) + '</span>'; }).join(''); $('vb-dm').classList.toggle('met-knoppen', kn.length > 0);
    $('vb-reactie').textContent = (woorden[0] || T('bl.trefwoord')).toUpperCase();
    var a = [].slice.call($('antwoorden').querySelectorAll('input')).map(function (i) { return i.value.trim(); }).filter(Boolean);
    $('vb-antwoord').textContent = a[0] || '';
    $('vb-dm').innerHTML = linkjes(esc(dm)) || '<span>' + esc(T('bl.vb_leeg')) + '</span>';
  }
  function sluitBlad() { blad.close(); }
  $('blad-sluit').addEventListener('click', sluitBlad); $('blad-annuleer').addEventListener('click', sluitBlad);
  form.addEventListener('submit', function (e) {
    e.preventDefault(); woordErbij();
    var x = {
      id: bewerk, naam: form.elements.naam.value, trefwoorden: woorden, media_id: form.elements.media_id.value, dm_tekst: form.elements.dm_tekst.value,
      link: form.elements.link.value, link2: form.elements.link2.value, slug: form.elements.slug.value,
      stijl: form.elements.stijl.value, knop: form.elements.knop.value, knop2: form.elements.knop2.value,
      antwoorden: [].slice.call($('antwoorden').querySelectorAll('input')).map(function (i) { return i.value; }),
      aan: form.elements.aan.checked, test: form.elements.test.checked, testers: form.elements.testers.value.split(/[\s,]+/)
    };
    var knop = $('blad-bewaar'); knop.disabled = true;
    VELDEN.forEach(function (v) { zetFout(v, ''); });
    api({ a: 'opslaan', automation: x, filter: filter }).then(function (j) {
      knop.disabled = false;
      if (j.fouten) { var eerste = null; Object.keys(j.fouten).forEach(function (k) { zetFout(k, j.fouten[k]); eerste = eerste || k; }); var el = form.querySelector('[data-voor="' + eerste + '"]'); if (el) el.scrollIntoView({ block: 'center', behavior: 'smooth' }); return; }
      if (j.ok) { verwerk(j, T('bl.bewaard') + ' ' + (x.aan ? (x.test ? T('bl.aan_test') : T('bl.staat_aan')) : T('bl.staat_uit'))); sluitBlad(); }
      else verwerk(j);
    }).catch(function () { knop.disabled = false; toast(T('bl.bewaren_mislukt'), true); });
  });
  $('blad-weg').addEventListener('click', function () {
    if (!bewerk || !confirm(T('bl.weg_zeker'))) return;
    api({ a: 'verwijder', id: bewerk, filter: filter }).then(function (j) { verwerk(j, T('bl.verwijderd')); if (j.ok) sluitBlad(); }).catch(function () { toast(T('bl.weg_mislukt'), true); });
  });
  $('nieuw').addEventListener('click', function () { delete form.elements.slug.dataset.zelf; openBlad(0); });

  // ── Start: eerst de teksten, dan de gegevens ──
  var taal = document.documentElement.lang === 'en' ? 'en' : 'nl';
  fetch('taal/' + taal + '.json?v=' + encodeURIComponent(document.documentElement.dataset.versie || ''), { cache: 'no-cache' }).then(function (r) { return r.json(); }).catch(function () { return {}; }).then(function (t) {
    L = t || {}; LOC = L._locale || LOC; nl = new Intl.NumberFormat(LOC);
    try { sleutel = localStorage.getItem(OPSLAG) || ''; } catch (e) {}
    var h = (location.hash || '').slice(1); if (['overzicht', 'automations', 'mensen', 'activiteit', 'gezondheid'].indexOf(h) >= 0) tab = h;
    if (!sleutel) { toonSlot(); return; }
    api({ a: 'staat', filter: filter }).then(function (j) {
      if (!j.ok) { toonSlot(); return; }
      start(j);
    }).catch(toonSlot);
  });
})();
