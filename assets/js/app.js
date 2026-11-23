/* Тахта — мантиқи фронтенд: мавзӯъ, диалогҳо, кашидани корт (fetch), корти вазифа */
(() => {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  // ---------- Тост ----------
  function toast(msg, err = false) {
    const t = document.createElement('div');
    t.className = 'toast' + (err ? ' err' : '');
    t.textContent = msg;
    $('#toasts').appendChild(t);
    setTimeout(() => { t.classList.add('out'); setTimeout(() => t.remove(), 320); }, err ? 4200 : 2600);
  }
  const pending = sessionStorage.getItem('kbToast');
  if (pending) { sessionStorage.removeItem('kbToast'); toast(pending); }
  const reloadWith = msg => { sessionStorage.setItem('kbToast', msg); location.reload(); };

  // ---------- Мавзӯъ ----------
  $('#themeBtn')?.addEventListener('click', () => {
    const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = next;
    localStorage.setItem('kb-theme', next);
  });

  // ---------- Диалогҳои умумӣ ----------
  document.addEventListener('click', e => {
    const o = e.target.closest('[data-open]');
    if (o) { $('#' + o.dataset.open)?.showModal(); return; }
    if (e.target.closest('[data-close]')) { e.target.closest('dialog')?.close(); return; }
    if (e.target.tagName === 'DIALOG') e.target.close(); // клик ба берун
  });

  if (!window.KB) return; // поён — танҳо саҳифаи тахта
  const KB = window.KB;

  // ---------- API ----------
  async function api(action, data, query = '') {
    const form = data instanceof FormData;
    const res = await fetch('api.php?action=' + action + query, {
      method: data === undefined ? 'GET' : 'POST',
      headers: { 'X-CSRF-Token': KB.csrf, ...(form || data === undefined ? {} : { 'Content-Type': 'application/json' }) },
      body: data === undefined ? undefined : (form ? data : JSON.stringify(data)),
    });
    let j;
    try { j = await res.json(); } catch { throw new Error('Сервер ҷавоби нодуруст дод.'); }
    if (!j.ok) throw new Error(j.error || 'Хато рух дод.');
    return j;
  }
  const run = async (fn) => { try { return await fn(); } catch (err) { toast(err.message, true); } };

  // ---------- Ҳисобкунакҳои сутунҳо ----------
  function refreshColumns() {
    $$('.col[data-col]').forEach(col => {
      const n = $$('.card', col).length;
      $('.count', col).textContent = n;
      col.classList.toggle('is-empty', n === 0);
    });
  }

  // ---------- Кашидани корт ----------
  let dragged = null, origin = null, dropped = false;

  function afterElement(body, y) {
    let best = { o: -Infinity, el: null };
    $$('.card:not(.dragging)', body).forEach(el => {
      const b = el.getBoundingClientRect();
      const o = y - b.top - b.height / 2;
      if (o < 0 && o > best.o) best = { o, el };
    });
    return best.el;
  }

  document.addEventListener('dragstart', e => {
    const c = e.target.closest?.('.card[draggable="true"]');
    if (!c) return;
    dragged = c; dropped = false;
    origin = { body: c.parentElement, next: c.nextElementSibling };
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', c.dataset.task);
    requestAnimationFrame(() => c.classList.add('dragging'));
    document.body.classList.add('is-dragging');
  });

  document.addEventListener('dragend', () => {
    if (!dragged) return;
    dragged.classList.remove('dragging');
    document.body.classList.remove('is-dragging');
    $$('.col.over').forEach(c => c.classList.remove('over'));
    if (!dropped) { origin.body.insertBefore(dragged, origin.next); refreshColumns(); } // дар ҷои нодуруст партофта шуд
    dragged = null;
  });

  $$('.col-body').forEach(body => {
    const col = body.closest('.col');
    body.addEventListener('dragover', e => {
      if (!dragged) return;
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      col.classList.add('over');
      col.classList.remove('is-empty');
      const after = afterElement(body, e.clientY);
      if (after) body.insertBefore(dragged, after); else body.appendChild(dragged);
    });
    col.addEventListener('dragleave', e => { if (!col.contains(e.relatedTarget)) { col.classList.remove('over'); refreshColumns(); } });
    col.addEventListener('drop', e => {
      if (!dragged) return;
      e.preventDefault();
      dropped = true;
      col.classList.remove('over');
      persistMove(dragged, body);
    });
  });

  async function persistMove(card, body) {
    const sameSpot = body === origin.body && card.nextElementSibling === origin.next;
    refreshColumns();
    if (sameSpot) return;
    const next = card.nextElementSibling;
    const snapshot = { ...origin }; // нусха, чунки dragend origin-ро нигоҳ медорад
    try {
      const j = await api('move_task', { task_id: +card.dataset.task, column_id: +body.dataset.col, before_id: next ? +next.dataset.task : 0 });
      card.classList.remove('settle'); void card.offsetWidth; card.classList.add('settle');
      toast(j.moved ? `Вазифа ба «${j.column_name}» гузашт` : 'Тартиб нигоҳ дошта шуд');
      markLate(card, body);
    } catch (err) {
      snapshot.body.insertBefore(card, snapshot.next);
      refreshColumns();
      card.classList.add('flash-err');
      toast(err.message, true);
    }
  }
  // Мӯҳлати гузашта дар сутуни охирин нишон дода намешавад
  function markLate(card, body) {
    const chip = $('.chip:not(.chip-c):not(.chip-f)', card);
    if (!chip) return;
    const [d, m, y] = chip.textContent.trim().split('.').map(Number);
    const late = new Date(y, m - 1, d) < new Date(new Date().toDateString()) && +body.dataset.col !== KB.lastCol;
    chip.classList.toggle('chip-late', late);
  }
  function moveCardDom(id, colId) {
    const card = $(`.card[data-task="${id}"]`), body = $(`.col-body[data-col="${colId}"]`);
    if (!card || !body) return;
    body.appendChild(card); refreshColumns(); markLate(card, body);
    card.classList.remove('settle'); void card.offsetWidth; card.classList.add('settle');
  }

  // ---------- Диалоги вазифа ----------
  const taskDlg = $('#taskDlg');
  const fmtDT = s => { const d = new Date(s.replace(' ', 'T')); return d.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }); };
  const fmtD = s => s ? s.split('-').reverse().join('.') : '—';
  const PRIO = { high: 'Баланд', medium: 'Миёна', low: 'Паст' };
  const FIELD = { title: 'номи вазифаро', description: 'тавсифро', assignee: 'иҷрокунандаро', deadline: 'мӯҳлатро', priority: 'афзалиятро' };
  const kb = n => n > 1048576 ? (n / 1048576).toFixed(1) + ' МБ' : Math.max(1, Math.round(n / 1024)) + ' КБ';
  const av = (id, name) => `<span class="avatar" style="--h:${(id * 67) % 360}" title="${esc(name)}">${esc(name.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase())}</span>`;

  function histLine(h) {
    const who = `<b>${esc(h.full_name)}</b>`;
    let txt;
    if (h.action === 'created') txt = `${who} вазифаро сохт`;
    else if (h.action === 'moved') txt = `${who} корти вазифаро аз «${esc(h.old_value)}» ба «${esc(h.new_value)}» гузаронд`;
    else if (h.action === 'comment') txt = `${who} шарҳ навишт`;
    else if (h.action === 'attachment') txt = `${who} файл илова кард: ${esc(h.new_value)}`;
    else txt = `${who} ${FIELD[h.field_name] || 'майдонро'} иваз кард: <s>${esc(h.old_value)}</s> → ${esc(h.new_value)}`;
    return `<li class="${esc(h.action)}">${txt}<small>${fmtDT(h.created_at)}</small></li>`;
  }

  function renderTask(j) {
    const t = j.task, edit = j.can_edit;
    const memberOpts = sel => '<option value="">Таъин нашавад</option>' + KB.members.map(m => `<option value="${m.id}" ${+sel === m.id ? 'selected' : ''}>${esc(m.name)}</option>`).join('');
    const colOpts = KB.columns.map(c => `<option value="${c.id}" ${+t.column_id === c.id ? 'selected' : ''}>${esc(c.name)}</option>`).join('');
    const main = edit ? `
      <form id="editForm" class="form" data-id="${t.id}">
        <label>Ном<input name="title" required maxlength="200" value="${esc(t.title)}"></label>
        <label>Тавсиф<textarea name="description" rows="4">${esc(t.description || '')}</textarea></label>
        <div class="grid2">
          <label>Иҷрокунанда<select name="assignee_id">${memberOpts(t.assignee_id)}</select></label>
          <label>Мӯҳлат<input type="date" name="deadline" value="${esc(t.deadline || '')}"></label>
          <label>Афзалият<select name="priority">${Object.entries(PRIO).map(([k, v]) => `<option value="${k}" ${t.priority === k ? 'selected' : ''}>${v}</option>`).join('')}</select></label>
          <label>Сутун<select id="colSel">${colOpts}</select></label>
        </div>
        <div class="t-actions"><button type="button" class="btn btn-danger" id="delTask">Нест кардан</button><button class="btn btn-primary" type="submit">Нигоҳ доштан</button></div>
      </form>` : `
      <h2 class="t-title">${esc(t.title)}</h2>
      <div class="t-sub"><span class="chip chip-prio">${PRIO[t.priority]}</span><span class="chip">Мӯҳлат: ${fmtD(t.deadline)}</span></div>
      <p class="t-desc ${t.description ? '' : 'none'}">${t.description ? esc(t.description) : 'Тавсиф нест'}</p>
      <label class="form"><span class="muted" style="font-size:.84rem;font-weight:600">Гузаронидан ба сутун</span><select id="colSel">${colOpts}</select></label>`;

    const comments = j.comments.length ? j.comments.map(c => `
      <div class="comment">${av(c.user_id, c.full_name)}<div class="bubble"><b>${esc(c.full_name)}</b><small>${fmtDT(c.created_at)}</small><p>${esc(c.comment)}</p></div></div>`).join('')
      : '<p class="muted" style="margin-bottom:12px">Ҳоло шарҳ нест.</p>';

    $('.dlg-body', taskDlg).innerHTML = `
      <div class="dlg-head"><span class="muted">${esc(t.column_name)}</span><button type="button" class="icon-btn" data-close aria-label="Пӯшидан">✕</button></div>
      <div class="t-grid" data-task="${t.id}">
        <div>
          ${main}
          <div class="sec" style="margin-top:26px"><h4>Шарҳҳо (${j.comments.length})</h4>${comments}
            <form id="commentForm" class="comment-form"><textarea name="comment" rows="2" maxlength="2000" placeholder="Шарҳ нависед…" required></textarea><button class="btn btn-ghost btn-sm" style="justify-self:end" type="submit">Фиристодан</button></form>
          </div>
        </div>
        <aside class="t-side">
          <div class="sec"><h4>Замимаҳо (${j.files.length})</h4>
            ${j.files.map(f => `<div class="file"><a href="download.php?id=${f.id}">${esc(f.file_name)}</a><small class="muted">${kb(+f.file_size)}</small></div>`).join('')}
            <label class="file-up"><input type="file" id="fileInp">+ Файл илова кардан (то 5 МБ)</label>
          </div>
          <div class="sec"><h4>Таърихи тағйирот</h4><ul class="timeline">${j.history.map(histLine).join('')}</ul></div>
        </aside>
      </div>`;
    syncCard(t.id, j);
  }

  function syncCard(id, j) {
    const card = $(`.card[data-task="${id}"]`);
    if (!card) return;
    const set = (sel, n) => { const el = $(sel, card); if (el) { el.hidden = !n; $('b', el).textContent = n; } };
    set('.chip-c', j.comments.length); set('.chip-f', j.files.length);
  }

  let currentId = 0;
  async function loadTask(id, first) {
    currentId = id;
    if (first) { $('.dlg-body', taskDlg).innerHTML = '<div class="skeleton"></div>'; taskDlg.showModal(); }
    try { renderTask(await api('get_task', undefined, '&id=' + id)); }
    catch (err) { toast(err.message, true); taskDlg.close(); }
  }

  $('#board').addEventListener('click', e => {
    const card = e.target.closest('.card');
    if (card) return loadTask(+card.dataset.task, true);
    const add = e.target.closest('[data-add-task]');
    if (add) { const f = $('#createForm'); f.elements.column_id.value = add.dataset.addTask; $('#createDlg').showModal(); f.elements.title.focus(); return; }
    const del = e.target.closest('[data-del-col]');
    if (del && confirm('Ин сутунро нест кунед?')) run(async () => { await api('delete_column', { column_id: +del.dataset.delCol }); reloadWith('Сутун нест карда шуд'); });
  });
  $('#board').addEventListener('keydown', e => { if (e.key === 'Enter' && e.target.matches('.card')) loadTask(+e.target.dataset.task, true); });

  taskDlg.addEventListener('submit', e => {
    e.preventDefault();
    const f = e.target;
    if (f.id === 'editForm') run(async () => {
      await api('update_task', { id: +f.dataset.id, ...Object.fromEntries(new FormData(f)) });
      reloadWith('Тағйирот нигоҳ дошта шуд');
    });
    if (f.id === 'commentForm') run(async () => {
      await api('add_comment', { task_id: currentId, comment: f.elements.comment.value });
      toast('Шарҳ илова шуд'); await loadTask(currentId, false);
    });
  });
  taskDlg.addEventListener('change', e => {
    if (e.target.id === 'fileInp' && e.target.files[0]) run(async () => {
      const fd = new FormData(); fd.append('task_id', currentId); fd.append('file', e.target.files[0]);
      await api('upload', fd); toast('Файл боргузорӣ шуд'); await loadTask(currentId, false);
    });
    if (e.target.id === 'colSel') run(async () => {
      const j = await api('move_task', { task_id: currentId, column_id: +e.target.value, before_id: 0 });
      moveCardDom(currentId, +e.target.value); toast(`Вазифа ба «${j.column_name}» гузашт`); await loadTask(currentId, false);
    });
  });
  taskDlg.addEventListener('click', e => {
    if (e.target.id === 'delTask' && confirm('Вазифа якбора нест карда мешавад. Давом диҳем?'))
      run(async () => { await api('delete_task', { id: currentId }); reloadWith('Вазифа нест карда шуд'); });
  });

  // ---------- Формаҳои роҳбар ----------
  $('#newTaskBtn')?.addEventListener('click', () => { $('#createDlg').showModal(); $('#createForm').elements.title.focus(); });
  $('#createForm')?.addEventListener('submit', e => {
    e.preventDefault();
    run(async () => { await api('create_task', { project_id: KB.project, ...Object.fromEntries(new FormData(e.target)) }); reloadWith('Вазифа сохта шуд'); });
  });
  $('#colForm')?.addEventListener('submit', e => {
    e.preventDefault();
    run(async () => { await api('create_column', { project_id: KB.project, name: e.target.elements.name.value }); reloadWith('Сутун илова шуд'); });
  });
  $('#memberForm')?.addEventListener('submit', e => {
    e.preventDefault();
    run(async () => { const j = await api('add_member', { project_id: KB.project, email: e.target.elements.email.value }); reloadWith(j.name + ' ба лоиҳа илова шуд'); });
  });
  $('#memberDlg')?.addEventListener('click', e => {
    const b = e.target.closest('[data-rm-member]');
    if (b && confirm('Иштирокчиро аз лоиҳа бароред? Вазифаҳои ӯ бе иҷрокунанда мемонанд.'))
      run(async () => { await api('remove_member', { project_id: KB.project, user_id: +b.dataset.rmMember }); reloadWith('Иштирокчӣ аз лоиҳа бароварда шуд'); });
  });

  // Кушодани вазифа аз ҳавола (?task=ID), масалан аз рӯйхати мӯҳлаташ гузашта
  const want = +new URLSearchParams(location.search).get('task');
  if (want) loadTask(want, true);
})();
