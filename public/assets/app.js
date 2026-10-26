const view = document.getElementById('view');
let state = null;
let tab = 'caca';
let pollTimer = null;

async function api(action, data) {
  const opt = data ? {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(data)
  } : {};
  const r = await fetch('api.php?action=' + action, opt);
  const j = await r.json();
  if (!r.ok) throw new Error(j.error || 'Erro inesperado.');
  return j;
}

function esc(s) {
  return String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
}

function setView(html) { view.innerHTML = html; }

// ---------- telas de autenticacao ----------

function showAuth(mode) {
  const isLogin = mode === 'login';
  setView(`
    <div class="card">
      <h2>${isLogin ? 'Entrar' : 'Criar conta'}</h2>
      <input id="email" type="email" placeholder="E-mail" autocomplete="email">
      <input id="pass" type="password" placeholder="Senha${isLogin ? '' : ' (min. 6 caracteres)'}" autocomplete="${isLogin ? 'current-password' : 'new-password'}">
      <div class="erro" id="erro"></div>
      <button id="btn">${isLogin ? 'Entrar em Vapor Alto' : 'Forjar minha conta'}</button>
      <p style="margin-top:10px;text-align:center">
        <span class="link" id="swap">${isLogin ? 'Nao tem conta? Registre-se' : 'Ja tem conta? Entrar'}</span>
      </p>
    </div>`);
  document.getElementById('swap').onclick = () => showAuth(isLogin ? 'register' : 'login');
  document.getElementById('btn').onclick = async () => {
    const erro = document.getElementById('erro');
    erro.textContent = '';
    try {
      const j = await api(isLogin ? 'login' : 'register', {
        email: document.getElementById('email').value,
        password: document.getElementById('pass').value
      });
      if (isLogin && j.has_char) startGame();
      else showCreateChar();
    } catch (e) { erro.textContent = e.message; }
  };
}

// ---------- criacao de personagem ----------

async function showCreateChar() {
  const {vocations} = await api('vocations');
  let sel = null;
  setView(`
    <div class="card">
      <h2>Crie seu forjado</h2>
      <input id="name" placeholder="Nome do personagem" maxlength="30">
      <div class="voc-grid">
        ${vocations.map(v => `
          <div class="voc-card" data-id="${v.id}">
            <h3>${esc(v.name)}</h3>
            <p>${esc(v.descricao)}</p>
            <p class="stats">HP ${v.hp_base}+${v.hp_pl}/nvl &middot; ATQ ${v.atk_base}+${v.atk_pl}/nvl &middot; DEF ${v.def_base}+${v.def_pl}/nvl &middot; REG ${v.regen}</p>
          </div>`).join('')}
      </div>
      <div class="erro" id="erro"></div>
      <button id="btn" disabled>Forjar personagem</button>
    </div>`);
  document.querySelectorAll('.voc-card').forEach(c => c.onclick = () => {
    document.querySelectorAll('.voc-card').forEach(x => x.classList.remove('sel'));
    c.classList.add('sel');
    sel = c.dataset.id;
    document.getElementById('btn').disabled = false;
  });
  document.getElementById('btn').onclick = async () => {
    const erro = document.getElementById('erro');
    erro.textContent = '';
    try {
      await api('create_character', {name: document.getElementById('name').value, vocation: sel});
      startGame();
    } catch (e) { erro.textContent = e.message; }
  };
}

// ---------- jogo ----------

async function refresh() {
  try {
    state = await api('state');
    updateExpRate(state.char.exp);
    renderGame();
  } catch (e) {
    stopPolling();
    showAuth('login');
  }
}

function startGame() {
  refresh();
  stopPolling();
  pollTimer = setInterval(refresh, 8000);
}

function stopPolling() {
  if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
}

function bar(cls, cur, max) {
  const pct = Math.max(0, Math.min(100, 100 * cur / Math.max(1, max)));
  return `<div class="bar ${cls}"><div style="width:${pct}%"></div><span>${cur} / ${max}</span></div>`;
}

function renderGame() {
  const c = state.char;
  const tabs = [['caca','Caca'],['mochila','Mochila'],['loja','Loja'],['ranking','Ranking']];
  let body = '';

  if (tab === 'caca') body = renderCaca();
  else if (tab === 'mochila') body = renderMochila();
  else if (tab === 'loja') body = renderLoja();
  else body = renderRanking();

  setView(`
    <div class="card">
      <h2><img class="voc-ico" src="assets/img/voc/${c.vocation_id}.png" alt="">
        ${esc(c.name)} <small style="color:var(--muted)">${esc(c.vocation)} — nivel ${c.level}</small></h2>
      ${bar('hp', c.hp, c.hp_max)}
      ${bar('xp', c.exp, c.exp_next)}
      <div class="statline"><span>Ataque <b>${c.atk}</b> &middot; Defesa <b>${c.def}</b></span>
      <span>Ouro <b>${c.gold}</b> &middot; Pocoes <b>${c.potions_s}s/${c.potions_m}m</b></span></div>
      <div class="statline"><span class="${c.hunting ? 'cacando' : 'parado'}">
        ${c.hunting ? 'Cacando em ' + esc(nomeArea(c.area_id)) : 'Descansando em Vapor Alto'}</span>
        <span><span style="color:var(--muted);font-size:.75rem">~${expPerMin} exp/min</span>
        &nbsp;<span class="link" id="sair">Sair</span></span></div>
    </div>
    <nav class="tabs">${tabs.map(([k, l]) =>
      `<button class="${tab === k ? 'ativo' : ''}" data-tab="${k}">${l}</button>`).join('')}</nav>
    ${body}`);

  document.getElementById('sair').onclick = async () => {
    stopPolling();
    await api('logout', {});
    showAuth('login');
  };
  document.querySelectorAll('nav.tabs button').forEach(b => b.onclick = () => { tab = b.dataset.tab; renderGame(); });
  wireTab();
  const log = document.getElementById('log');
  if (log) log.scrollTop = log.scrollHeight;
}

function nomeArea(id) {
  const a = state.areas.find(a => a.id == id);
  return a ? a.name : '';
}

const AREA_BG = {1:'tubulacoes',2:'minas',3:'ermo',4:'forja',5:'ninho'};

function renderCaca() {
  const c = state.char;
  let battle = '';
  if (c.hunting) {
    const bg = AREA_BG[c.area_id] || 'tubulacoes';
    battle = `
    <div class="batalha" style="background-image:url('assets/img/areas/${bg}.jpg')">
      <div class="lutador">
        <img class="boneco" src="assets/img/voc/${c.vocation_id}.png" alt="">
        <div class="bar hp mini"><div style="width:${Math.round(100*c.hp/Math.max(1,c.hp_max))}%"></div></div>
        <span>${esc(c.name)}</span>
      </div>
      <div class="vs">VS</div>
      <div class="lutador" id="enemy">
        ${state.enemy
          ? `<img class="boneco inimigo" src="${state.enemy.img}" alt=""><span>${esc(state.enemy.name)}</span>`
          : '<span class="procurando">procurando alvo...</span>'}
      </div>
    </div>`;
  }
  return battle + `
    <div class="card"><h2>Registro de caca</h2>
      <div id="log">${state.log.map(l =>
        `<p><span class="ts">${esc(l.ts.slice(11, 19))}</span>${esc(l.message)}</p>`).join('')}</div>
    </div>
    <div class="card"><h2>Areas de caca</h2>
      ${state.areas.map(a => `
      <div class="area-card" style="background-image:url('assets/img/areas/${AREA_BG[a.id]}.jpg')">
        <div class="area-info">
          <b>${esc(a.name)}</b> <small>nivel ${a.min_level}+</small>
          <p>${esc(a.descricao)}</p>
        </div>
        ${c.area_id == a.id
          ? '<button class="sec" data-stop="1">Parar</button>'
          : `<button data-area="${a.id}" ${c.level < a.min_level ? 'disabled' : ''}>Cacar</button>`}
      </div>`).join('')}
    </div>`;
}

function renderMochila() {
  if (!state.inventory.length) return '<div class="card"><h2>Mochila</h2><p class="loading">Vazia.</p></div>';
  return `<div class="card"><h2>Mochila</h2><ul class="lista">${state.inventory.map(i => `
    <li>
      <img class="item-ico" src="assets/img/tibia/items/${i.id}.png" alt="">
      <span class="nome">${esc(i.name)}
        <small>${i.slot === 'arma' ? 'ATQ +' + i.attack : 'DEF +' + i.defense} — nivel ${i.min_level}+</small></span>
      ${i.equipped == 1
        ? '<span class="badge">equipado</span><button class="sec" data-unequip="' + i.id + '">Tirar</button>'
        : `<button data-equip="${i.id}">Equipar</button><button class="sec" data-sell="${i.id}">Vender (${Math.floor(i.price/2)})</button>`}
    </li>`).join('')}</ul></div>`;
}

function renderLoja() {
  return `<div class="card"><h2>Loja de Vapor Alto</h2>
    <ul class="lista">
      <li><img class="item-ico" src="assets/img/tibia/items/101.png" alt="">
        <span class="nome">Pocao de Vapor <small>cura 60 HP (uso automatico)</small></span><button data-potion="s">25 ouro</button></li>
      <li><img class="item-ico" src="assets/img/tibia/items/102.png" alt="">
        <span class="nome">Pocao de Brasa <small>cura 180 HP (uso automatico)</small></span><button data-potion="m">80 ouro</button></li>
      ${state.shop.map(i => `
      <li>
        <img class="item-ico" src="assets/img/tibia/items/${i.id}.png" alt="">
        <span class="nome">${esc(i.name)}
          <small>${i.slot === 'arma' ? 'ATQ +' + i.attack : 'DEF +' + i.defense} — nivel ${i.min_level}+${i.vocation_id ? ' — vocacao especifica' : ''}</small></span>
        <button data-buy="${i.id}" ${state.char.gold < i.price ? 'disabled' : ''}>${i.price} ouro</button>
      </li>`).join('')}
    </ul></div>`;
}

let rankingCache = null;
let prevExp = null, prevExpAt = null, expPerMin = 0;

function updateExpRate(exp) {
  const now = Date.now();
  if (prevExp !== null && now - prevExpAt > 30000 && exp >= prevExp) {
    expPerMin = Math.round((exp - prevExp) / ((now - prevExpAt) / 60000));
    prevExp = exp; prevExpAt = now;
  } else if (prevExp === null) {
    prevExp = exp; prevExpAt = now;
  }
}
function renderRanking() {
  if (!rankingCache) {
    api('ranking').then(j => { rankingCache = j.ranking; if (tab === 'ranking') renderGame(); });
    return '<div class="card"><h2>Ranking</h2><p class="loading">Carregando...</p></div>';
  }
  return `<div class="card"><h2>Ranking</h2><ul class="lista">${rankingCache.map((r, i) => `
    <li><span class="nome">#${i+1} ${esc(r.name)} <small>${esc(r.vocation)}</small></span>
    <span>nivel <b>${r.level}</b></span></li>`).join('') || '<li>Sem forjados ainda.</li>'}</ul></div>`;
}

function wireTab() {
  document.querySelectorAll('[data-area]').forEach(b => b.onclick = async () => {
    try { await api('hunt_start', {area_id: b.dataset.area}); refresh(); } catch (e) { alert(e.message); }
  });
  document.querySelectorAll('[data-stop]').forEach(b => b.onclick = async () => {
    await api('hunt_stop', {}); refresh();
  });
  document.querySelectorAll('[data-equip]').forEach(b => b.onclick = async () => {
    try { await api('equip', {item_id: b.dataset.equip}); refresh(); } catch (e) { alert(e.message); }
  });
  document.querySelectorAll('[data-unequip]').forEach(b => b.onclick = async () => {
    await api('unequip', {item_id: b.dataset.unequip}); refresh();
  });
  document.querySelectorAll('[data-sell]').forEach(b => b.onclick = async () => {
    try { await api('sell', {item_id: b.dataset.sell}); refresh(); } catch (e) { alert(e.message); }
  });
  document.querySelectorAll('[data-buy]').forEach(b => b.onclick = async () => {
    try { await api('buy', {item_id: b.dataset.buy}); refresh(); } catch (e) { alert(e.message); }
  });
  document.querySelectorAll('[data-potion]').forEach(b => b.onclick = async () => {
    try { await api('buy', {potion: b.dataset.potion}); refresh(); } catch (e) { alert(e.message); }
  });
}

// ---------- boot ----------

(async () => {
  try {
    state = await api('state');
    startGame();
  } catch (e) {
    showAuth('login');
  }
})();
