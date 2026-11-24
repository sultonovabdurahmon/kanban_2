// public/assets/js/app.js — интерактивии Kanban
// Ҳамчун ES-модул пайваст мешавад: <script type="module" src="../assets/js/app.js"></script>

/* ---------- 1. Утилитаҳо ---------- */

// Даъвати fn-ро то он даме ба таъхир мегузорад, ки корбар delay мс таваққуф кунад.
function debounce(fn, delay = 300) {
  let timerId;
  return (...args) => {
    clearTimeout(timerId);
    timerId = setTimeout(() => fn(...args), delay);
  };
}

// 'YYYY-MM-DD' -> 'DD.MM.YYYY' (қимати холӣ -> '')
function formatDate(isoDate) {
  if (!isoDate) return '';
  const [year, month, day] = isoDate.split('-');
  return `${day}.${month}.${year}`;
}

const PRIORITY_LABELS = { high: 'Баланд', medium: 'Миёна', low: 'Паст' };

/* ---------- 2. Валидатсияи шакли сабти ном ---------- */

const FIELD_MESSAGES = {
  name: { valueMissing: 'Номи худро ворид кунед.' },
  email: {
    valueMissing: 'Email-ро ворид кунед.',
    typeMismatch: 'Email-ро дуруст ворид кунед, масалан name@example.tj.',
  },
  password: { valueMissing: 'Паролро ворид кунед.' },
  password_confirm: { valueMissing: 'Паролро такроран ворид кунед.' },
};

function messageFor(field) {
  const v = field.validity;
  if (v.valid) return '';
  if (v.customError) return field.validationMessage; // қоидаи мо тавассути setCustomValidity()
  const custom = FIELD_MESSAGES[field.name] ?? {};
  if (v.valueMissing) return custom.valueMissing ?? 'Ин майдонро пур кунед.';
  if (v.tooLong) return `На зиёда аз ${field.maxLength} аломат.`;
  if (v.tooShort) return `На камтар аз ${field.minLength} аломат.`;
  if (v.typeMismatch) return custom.typeMismatch ?? 'Формати нодуруст.';
  return 'Қимати майдонро санҷед.';
}

function showFieldState(field) {
  const message = messageFor(field);
  const feedback = document.getElementById(`${field.id}-error`);
  field.classList.toggle('is-invalid', message !== '');
  if (message) field.setAttribute('aria-invalid', 'true');
  else field.removeAttribute('aria-invalid');
  if (feedback) feedback.textContent = message;
  return message === '';
}

// Қоидаи худӣ барои ду майдон: паролҳо бояд баробар бошанд
function checkPasswordMatch(form) {
  const { password, password_confirm: confirm } = form.elements;
  confirm.setCustomValidity(''); // хатои пештараро бекор мекунем, вагарна майдон то абад нодуруст мемонад
  if (password.value && confirm.value && password.value !== confirm.value) {
    confirm.setCustomValidity('Паролҳо мувофиқ нестанд.');
  }
}

function initRegisterForm() {
  const form = document.getElementById('register-form');
  if (!form) return; // дар ин саҳифа шакл нест
  const { password_confirm: confirm } = form.elements;
  const fields = () => form.querySelectorAll('input, select, textarea');

  const refresh = (field) => {
    checkPasswordMatch(form);
    showFieldState(field);
    if (field !== confirm && confirm.classList.contains('is-invalid')) showFieldState(confirm);
  };

  // focusout боло мебарояд (баръакси blur) — як коркардкунанда барои тамоми шакл
  form.addEventListener('focusout', (event) => {
    if (event.target.matches('input, select, textarea')) refresh(event.target);
  });
  form.addEventListener('input', (event) => {
    if (event.target.classList.contains('is-invalid')) refresh(event.target);
  });
  form.addEventListener('submit', (event) => {
    checkPasswordMatch(form);
    let firstInvalid = null;
    fields().forEach((field) => {
      if (!showFieldState(field) && !firstInvalid) firstInvalid = field;
    });
    if (firstInvalid) {
      event.preventDefault(); // шаклро бо хатоҳо намефиристем
      firstInvalid.focus();
    }
  });
}

/* ---------- 3. Ҷустуҷӯи фаврӣ ---------- */

function createCell(text, label, className = '') {
  const td = document.createElement('td');
  td.textContent = text ?? ''; // textContent: ҳар гуна < > & аз додаҳо ҳамчун матн бароварда мешаванд
  if (label) td.dataset.label = label; // барои намоиши корт дар телефон
  if (className) td.className = className;
  return td;
}

function initials(name) {
  return (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((word) => word[0]).join('');
}

function createTaskRow(task) {
  const tr = document.createElement('tr');

  const titleCell = createCell('', 'Вазифа');
  const link = document.createElement('a');
  link.href = `task.html?id=${encodeURIComponent(task.id)}`;
  link.textContent = task.title;
  titleCell.append(link);

  const assigneeCell = createCell('', 'Иҷрокунанда');
  if (task.assignee) {
    const wrap = document.createElement('div');
    wrap.className = 'd-inline-flex align-items-center gap-2';
    const avatar = document.createElement('span');
    avatar.className = 'avatar';
    avatar.setAttribute('aria-hidden', 'true');
    avatar.textContent = initials(task.assignee);
    wrap.append(avatar, document.createTextNode(task.assignee));
    assigneeCell.append(wrap);
  } else {
    assigneeCell.textContent = '—';
  }

  const dueCell = createCell('', 'Мӯҳлат');
  if (!task.due_date) {
    dueCell.textContent = '—';
  } else if (task.is_overdue) {
    const span = document.createElement('span');
    span.className = 'is-overdue';
    span.textContent = `${formatDate(task.due_date)} · гузашта`;
    dueCell.append(span);
  } else {
    dueCell.textContent = formatDate(task.due_date);
  }

  // Синфи нишон танҳо аз рӯйхати маълум гирифта мешавад (ҳимоя аз синфи бегона)
  const priority = Object.hasOwn(PRIORITY_LABELS, task.priority) ? task.priority : 'low';
  const priorityCell = createCell('', 'Афзалият');
  const badge = document.createElement('span');
  badge.className = `badge priority priority-${priority}`;
  badge.textContent = PRIORITY_LABELS[priority];
  priorityCell.append(badge);

  const columnCell = createCell('', 'Сутун');
  const tag = document.createElement('span');
  tag.className = 'badge column-tag';
  tag.textContent = task.column ?? '';
  columnCell.append(tag);

  const actionsCell = createCell('', '', 'text-end cell-actions');
  const open = document.createElement('a');
  open.className = 'btn btn-sm btn-outline-primary';
  open.href = link.href;
  open.textContent = 'Кушодан';
  const del = document.createElement('button');
  del.type = 'button';
  del.className = 'btn btn-sm btn-outline-danger ms-1';
  del.dataset.deleteUrl = `/tasks/${encodeURIComponent(task.id)}/delete`;
  del.dataset.taskName = task.title;
  del.textContent = 'Нест кардан';
  actionsCell.append(open, del);

  tr.append(titleCell, createCell(task.project, 'Лоиҳа'), assigneeCell, dueCell, priorityCell, columnCell, actionsCell);
  return tr;
}

function renderTasks(tbody, tasks) {
  if (tasks.length === 0) {
    const tr = document.createElement('tr');
    const td = createCell('Вазифаҳо ёфт нашуданд. Ҷустуҷӯро тағйир диҳед.');
    td.colSpan = 7;
    td.className = 'text-center text-muted py-4';
    tr.append(td);
    tbody.replaceChildren(tr);
    return;
  }
  tbody.replaceChildren(...tasks.map(createTaskRow));
}

function initTaskSearch() {
  const input = document.getElementById('task-search');
  const tbody = document.getElementById('tasks-tbody');
  if (!input || !tbody) return;
  const statusBox = document.getElementById('task-search-status');
  const totalBox = document.getElementById('tasks-total');
  const setStatus = (text) => { if (statusBox) statusBox.textContent = text; };
  let controller = null; // AbortController-и дархости ҷорӣ

  async function search(query) {
    controller?.abort(); // дархости қаблиро, агар ҳанӯз анҷом наёфта бошад, бекор мекунем
    controller = new AbortController();
    tbody.setAttribute('aria-busy', 'true');
    setStatus('Ҷустуҷӯ…');
    try {
      const response = await fetch(`/api/tasks?q=${encodeURIComponent(query)}`, {
        headers: { Accept: 'application/json' },
        signal: controller.signal,
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`); // fetch дар 404/500 хато намепартояд
      const tasks = await response.json();
      renderTasks(tbody, tasks);
      setStatus(tasks.length ? `Вазифаҳои ёфтшуда: ${tasks.length}` : 'Вазифаҳо ёфт нашуданд.');
      if (totalBox) totalBox.textContent = `Ёфт шуд: ${tasks.length}`;
    } catch (error) {
      if (error.name === 'AbortError') return; // дархост бо дархости навтар бекор шуд — ин муқаррарӣ аст
      console.warn('Хатои ҷустуҷӯи фаврӣ:', error);
      setStatus('Ҷустуҷӯро иҷро кардан муяссар нашуд. Пайвастро санҷед ва бори дигар кӯшиш кунед.');
    } finally {
      tbody.removeAttribute('aria-busy');
    }
  }

  const debouncedSearch = debounce(search, 300);
  input.addEventListener('input', () => debouncedSearch(input.value.trim()));
}

/* ---------- 4. Тасдиқи нест кардан (равзанаи модалӣ) ---------- */

function initDeleteConfirm() {
  const modalElement = document.getElementById('confirm-delete-modal');
  if (!modalElement || !window.bootstrap) return;
  const form = document.getElementById('confirm-delete-form');
  const nameElement = document.getElementById('confirm-delete-name');
  const submitButton = form.querySelector('[type="submit"]');
  const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
  let lastTrigger = null;

  // Як коркардкунанда барои тамоми саҳифа (вогузорӣ): барои сатрҳое, ки ҷустуҷӯи фаврӣ баъдтар илова мекунад, низ кор мекунад
  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-delete-url]');
    if (!button) return;
    lastTrigger = button;
    form.action = button.dataset.deleteUrl;
    nameElement.textContent = button.dataset.taskName ?? '';
    submitButton.disabled = false;
    modal.show();
  });

  // Пас аз пӯшидан фокусро ба тугмае, ки аз он сар карда будем, бармегардонем (дастрасӣ)
  modalElement.addEventListener('hidden.bs.modal', () => {
    if (lastTrigger?.isConnected) lastTrigger.focus();
  });
  form.addEventListener('submit', () => { submitButton.disabled = true; }); // муҳофизат аз пахши дукарата
}

/* ---------- 5. Оғоз ---------- */
// Модул пас аз таҳлили HTML иҷро мешавад (мисли defer), бинобар ин DOMContentLoaded лозим нест.
initRegisterForm();
initTaskSearch();
initDeleteConfirm();
