/* ════════════════════════════════════════════════════════════════════════════
   Frontend Studio — IDE Core
   ════════════════════════════════════════════════════════════════════════════ */
(function () {
'use strict';

const FS = window.FS = {};

/* ══════════════════════════════════════════════════════════════════════════
   CONSTANTS
══════════════════════════════════════════════════════════════════════════ */
FS.THEMES = {
  dark:        { label: 'Dark',          monaco: 'vs-dark' },
  light:       { label: 'Light',         monaco: 'vs' },
  hc:          { label: 'High Contrast', monaco: 'hc-black' },
  monokai:     { label: 'Monokai',       monaco: 'vs-dark' },
  dracula:     { label: 'Dracula',       monaco: 'vs-dark' },
  'tokyo-night':{ label: 'Tokyo Night',  monaco: 'vs-dark' },
  catppuccin:  { label: 'Catppuccin',    monaco: 'vs-dark' },
  'github-dark':{ label: 'GitHub Dark',  monaco: 'vs-dark' },
};

FS.LANGS = {
  html: 'html', htm: 'html',
  css: 'css', scss: 'scss', less: 'less',
  js: 'javascript', mjs: 'javascript', cjs: 'javascript',
  ts: 'typescript', tsx: 'typescript',
  json: 'json', jsonc: 'json',
  svg: 'xml',
  md: 'markdown', markdown: 'markdown',
  txt: 'plaintext', text: 'plaintext',
  xml: 'xml', xhtml: 'xml',
  yaml: 'yaml', yml: 'yaml',
  sh: 'shell',
};

FS.FILE_ICONS = {
  html: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#e44d26" stroke-width="2"><path d="M4 3l1.5 16.5L12 21l6.5-1.5L20 3z"/></svg>`,
  htm:  `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#e44d26" stroke-width="2"><path d="M4 3l1.5 16.5L12 21l6.5-1.5L20 3z"/></svg>`,
  css:  `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#264de4" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>`,
  scss: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#cd6799" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>`,
  less: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1d365d" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>`,
  js:   `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f0db4f" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>`,
  mjs:  `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f0db4f" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>`,
  ts:   `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#007acc" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>`,
  json: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a6e22e" stroke-width="2"><path d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4"/><polyline points="15 3 21 9"/><path d="M21 3l-6 6"/></svg>`,
  md:   `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9e9e9e" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>`,
  svg:  `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ff9900" stroke-width="2"><circle cx="12" cy="12" r="9"/></svg>`,
  txt:  `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9e9e9e" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>`,
  folder: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fs-accent)" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>`,
  'folder-open': `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fs-accent)" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><polyline points="16 17 21 12 16 7"/></svg>`,
  default: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>`,
};

function getFileIcon(name) {
  const ext = name.split('.').pop().toLowerCase();
  return FS.FILE_ICONS[ext] || FS.FILE_ICONS.default;
}

function getLang(name) {
  const ext = name.split('.').pop().toLowerCase();
  return FS.LANGS[ext] || 'plaintext';
}

function uid() {
  return Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
}

/* ══════════════════════════════════════════════════════════════════════════
   EVENT BUS
══════════════════════════════════════════════════════════════════════════ */
FS.events = {
  _map: {},
  on(ev, fn) { (this._map[ev] = this._map[ev] || []).push(fn); return this; },
  off(ev, fn) { if (this._map[ev]) this._map[ev] = this._map[ev].filter(f => f !== fn); },
  emit(ev, ...args) { (this._map[ev] || []).forEach(fn => fn(...args)); },
};

/* ══════════════════════════════════════════════════════════════════════════
   DATABASE (IndexedDB)
══════════════════════════════════════════════════════════════════════════ */
FS.db = {
  _db: null,
  open() {
    return new Promise((resolve, reject) => {
      const req = indexedDB.open('fs-ide', 2);
      req.onupgradeneeded = e => {
        const db = e.target.result;
        if (!db.objectStoreNames.contains('projects')) {
          db.createObjectStore('projects', { keyPath: 'id' });
        }
        if (!db.objectStoreNames.contains('files')) {
          const fs = db.createObjectStore('files', { keyPath: 'id' });
          fs.createIndex('projectId', 'projectId', { unique: false });
          fs.createIndex('parentId', 'parentId', { unique: false });
        }
      };
      req.onsuccess = e => { this._db = e.target.result; resolve(this._db); };
      req.onerror = e => reject(e.target.error);
    });
  },
  tx(store, mode = 'readonly') {
    return this._db.transaction(store, mode).objectStore(store);
  },
  get(store, key) {
    return new Promise((res, rej) => {
      const req = this.tx(store).get(key);
      req.onsuccess = () => res(req.result);
      req.onerror = e => rej(e.target.error);
    });
  },
  getAll(store) {
    return new Promise((res, rej) => {
      const req = this.tx(store).getAll();
      req.onsuccess = () => res(req.result);
      req.onerror = e => rej(e.target.error);
    });
  },
  getAllByIndex(store, index, value) {
    return new Promise((res, rej) => {
      const req = this.tx(store).index(index).getAll(value);
      req.onsuccess = () => res(req.result);
      req.onerror = e => rej(e.target.error);
    });
  },
  put(store, obj) {
    return new Promise((res, rej) => {
      const req = this.tx(store, 'readwrite').put(obj);
      req.onsuccess = () => res(req.result);
      req.onerror = e => rej(e.target.error);
    });
  },
  delete(store, key) {
    return new Promise((res, rej) => {
      const req = this.tx(store, 'readwrite').delete(key);
      req.onsuccess = () => res(req.result);
      req.onerror = e => rej(e.target.error);
    });
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   SETTINGS
══════════════════════════════════════════════════════════════════════════ */
FS.settings = {
  KEY: 'fs-settings',
  DEFAULTS: {
    theme: 'dark',
    editorFontSize: 14,
    editorFontFamily: "'JetBrains Mono', Consolas, monospace",
    editorTabSize: 2,
    editorInsertSpaces: true,
    editorWordWrap: 'off',
    editorMinimap: true,
    editorLineNumbers: 'on',
    editorAutoSave: true,
    autoSaveDelay: 1000,
    editorFormatOnSave: false,
    previewAutoRefresh: true,
    previewDevice: 'desktop',
    previewPaused: false,
    currentProjectId: null,
    sidebarWidth: 280,
    bottomPanelHeight: 200,
    sidebarVisible: true,
    bottomPanelVisible: true,
    activePanel: 'explorer',
    firstRunDone: false,
    aiProvider: null,
    aiKey: '',
    aiEndpoint: '',
    aiModel: '',
    aiOrg: '',
    aiTemp: 0.7,
    aiTopP: 1,
    aiMaxTokens: 2048,
    aiSystemPrompt: 'You are a helpful frontend development assistant. Respond concisely and accurately.',
    closedTabs: [],
  },
  _data: {},
  load() {
    try {
      const raw = localStorage.getItem(this.KEY);
      this._data = raw ? { ...this.DEFAULTS, ...JSON.parse(raw) } : { ...this.DEFAULTS };
    } catch { this._data = { ...this.DEFAULTS }; }
    return this._data;
  },
  save() {
    try { localStorage.setItem(this.KEY, JSON.stringify(this._data)); } catch {}
  },
  get(k) { return this._data[k]; },
  set(k, v) { this._data[k] = v; this.save(); FS.events.emit('settings:change', k, v); },
  all() { return { ...this._data }; },
  export() { return JSON.stringify(this._data, null, 2); },
  import(json) {
    try {
      const d = JSON.parse(json);
      this._data = { ...this.DEFAULTS, ...d };
      this.save();
      return true;
    } catch { return false; }
  },
  reset() { this._data = { ...this.DEFAULTS }; this.save(); },
};

/* ══════════════════════════════════════════════════════════════════════════
   UI UTILITIES
══════════════════════════════════════════════════════════════════════════ */
FS.ui = {
  /* Toast notifications */
  toast(msg, type = 'info', duration = 3000) {
    const t = document.createElement('div');
    t.className = `fs-toast ${type}`;
    t.textContent = msg;
    document.getElementById('fs-toasts').appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateX(20px)'; t.style.transition = '.2s'; setTimeout(() => t.remove(), 200); }, duration);
  },

  /* Modal dialog */
  modal(title, body, footer, opts = {}) {
    const overlay = document.getElementById('fs-modal-overlay');
    const modal   = document.getElementById('fs-modal');
    const w = opts.width || 440;
    modal.style.width = w + 'px';
    document.getElementById('fs-modal-title').textContent = title;
    document.getElementById('fs-modal-body').innerHTML = '';
    document.getElementById('fs-modal-footer').innerHTML = '';
    if (typeof body === 'string') {
      document.getElementById('fs-modal-body').innerHTML = body;
    } else if (body instanceof HTMLElement) {
      document.getElementById('fs-modal-body').appendChild(body);
    }
    if (typeof footer === 'string') {
      document.getElementById('fs-modal-footer').innerHTML = footer;
    }
    overlay.style.display = 'flex';
    const input = modal.querySelector('input');
    if (input) setTimeout(() => input.focus(), 50);
    return overlay;
  },
  closeModal() {
    document.getElementById('fs-modal-overlay').style.display = 'none';
  },
  prompt(title, label, defaultVal = '') {
    return new Promise(resolve => {
      const id = 'modal-prompt-' + uid();
      this.modal(title,
        `<label class="fs-modal-label">${label}</label><input class="fs-modal-input" id="${id}" value="${FS.ui.esc(defaultVal)}" type="text">`,
        `<button class="fs-btn-ghost fs-sm-btn ghost" id="modal-cancel">Cancel</button><button class="fs-btn-primary fs-sm-btn" id="modal-ok">OK</button>`
      );
      const ok = () => {
        const val = document.getElementById(id)?.value.trim();
        this.closeModal();
        resolve(val || null);
      };
      const cancel = () => { this.closeModal(); resolve(null); };
      document.getElementById('modal-ok')?.addEventListener('click', ok);
      document.getElementById('modal-cancel')?.addEventListener('click', cancel);
      document.getElementById(id)?.addEventListener('keydown', e => { if (e.key === 'Enter') ok(); if (e.key === 'Escape') cancel(); });
      document.getElementById('fs-modal-close')?.addEventListener('click', cancel, { once: true });
    });
  },
  confirm(title, msg) {
    return new Promise(resolve => {
      this.modal(title,
        `<p class="fs-modal-text">${msg}</p>`,
        `<button class="fs-sm-btn ghost" id="modal-cancel">Cancel</button><button class="fs-sm-btn danger" id="modal-ok">Confirm</button>`
      );
      document.getElementById('modal-ok')?.addEventListener('click', () => { this.closeModal(); resolve(true); });
      document.getElementById('modal-cancel')?.addEventListener('click', () => { this.closeModal(); resolve(false); });
      document.getElementById('fs-modal-close')?.addEventListener('click', () => { this.closeModal(); resolve(false); }, { once: true });
    });
  },

  /* Context menu */
  contextMenu(x, y, items) {
    const menu = document.getElementById('fs-ctx-menu');
    menu.innerHTML = '';
    items.forEach(item => {
      if (item === 'sep') {
        const sep = document.createElement('div');
        sep.className = 'fs-ctx-sep';
        menu.appendChild(sep);
        return;
      }
      const el = document.createElement('div');
      el.className = 'fs-ctx-item' + (item.danger ? ' danger' : '');
      el.innerHTML = `${item.icon || ''}<span>${this.esc(item.label)}</span>${item.kbd ? `<span class="fs-ctx-kbd">${item.kbd}</span>` : ''}`;
      el.addEventListener('click', () => { this.closeCtx(); item.action?.(); });
      menu.appendChild(el);
    });
    menu.style.display = 'block';
    const rect = menu.getBoundingClientRect();
    const vw = window.innerWidth, vh = window.innerHeight;
    menu.style.left = (Math.min(x, vw - 200)) + 'px';
    menu.style.top = (Math.min(y, vh - menu.offsetHeight - 10)) + 'px';
  },
  closeCtx() {
    document.getElementById('fs-ctx-menu').style.display = 'none';
  },

  esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   PROJECTS
══════════════════════════════════════════════════════════════════════════ */
FS.projects = {
  _list: [],
  _current: null,

  async loadAll() {
    this._list = await FS.db.getAll('projects');
    this._list.sort((a, b) => b.updated - a.updated);
    return this._list;
  },

  async create(name) {
    const proj = {
      id: uid(),
      name: name || 'Untitled Project',
      description: '',
      created: Date.now(),
      updated: Date.now(),
      linkedAssets: {},
    };
    await FS.db.put('projects', proj);
    this._list.unshift(proj);
    return proj;
  },

  async rename(id, name) {
    const proj = await FS.db.get('projects', id);
    if (!proj) return;
    proj.name = name;
    proj.updated = Date.now();
    await FS.db.put('projects', proj);
    const idx = this._list.findIndex(p => p.id === id);
    if (idx !== -1) this._list[idx] = proj;
    return proj;
  },

  async duplicate(id) {
    const proj = await FS.db.get('projects', id);
    if (!proj) return;
    const newProj = { ...proj, id: uid(), name: proj.name + ' (Copy)', created: Date.now(), updated: Date.now() };
    await FS.db.put('projects', newProj);
    const files = await FS.db.getAllByIndex('files', 'projectId', id);
    for (const f of files) {
      await FS.db.put('files', { ...f, id: uid(), projectId: newProj.id });
    }
    this._list.unshift(newProj);
    return newProj;
  },

  async delete(id) {
    await FS.db.delete('projects', id);
    const files = await FS.db.getAllByIndex('files', 'projectId', id);
    for (const f of files) await FS.db.delete('files', f.id);
    this._list = this._list.filter(p => p.id !== id);
  },

  async get(id) {
    return FS.db.get('projects', id);
  },

  async updateLinkedAssets(projectId, htmlFileId, cssIds, jsIds) {
    const proj = await FS.db.get('projects', projectId);
    if (!proj) return;
    proj.linkedAssets = proj.linkedAssets || {};
    proj.linkedAssets[htmlFileId] = { css: cssIds, js: jsIds };
    proj.updated = Date.now();
    await FS.db.put('projects', proj);
    const idx = this._list.findIndex(p => p.id === projectId);
    if (idx !== -1) this._list[idx] = proj;
    return proj;
  },

  get current() { return this._current; },
  async setCurrent(id) {
    this._current = this._list.find(p => p.id === id) || null;
    if (!this._current && id) this._current = await this.get(id);
    FS.settings.set('currentProjectId', id);
    FS.events.emit('project:change', this._current);
    return this._current;
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   FILE SYSTEM
══════════════════════════════════════════════════════════════════════════ */
FS.files = {
  async getAll(projectId) {
    return FS.db.getAllByIndex('files', 'projectId', projectId);
  },

  async create(projectId, parentId, name, type = 'file', content = '') {
    const file = {
      id: uid(),
      projectId,
      parentId: parentId || null,
      type,
      name,
      content: type === 'file' ? content : '',
      created: Date.now(),
      updated: Date.now(),
    };
    await FS.db.put('files', file);
    return file;
  },

  async rename(id, name) {
    const f = await FS.db.get('files', id);
    if (!f) return;
    f.name = name;
    f.updated = Date.now();
    await FS.db.put('files', f);
    return f;
  },

  async updateContent(id, content) {
    const f = await FS.db.get('files', id);
    if (!f) return;
    f.content = content;
    f.updated = Date.now();
    await FS.db.put('files', f);
    return f;
  },

  async duplicate(id) {
    const f = await FS.db.get('files', id);
    if (!f) return;
    const ext = f.name.includes('.') ? '.' + f.name.split('.').pop() : '';
    const base = f.name.replace(/\.[^.]+$/, '');
    const newFile = { ...f, id: uid(), name: base + '-copy' + ext, created: Date.now(), updated: Date.now() };
    await FS.db.put('files', newFile);
    return newFile;
  },

  async delete(id) {
    const f = await FS.db.get('files', id);
    if (!f) return;
    await FS.db.delete('files', id);
    if (f.type === 'folder') {
      const children = await FS.db.getAllByIndex('files', 'parentId', id);
      for (const c of children) await this.delete(c.id);
    }
  },

  async get(id) {
    return FS.db.get('files', id);
  },

  buildTree(files) {
    const map = {};
    const roots = [];
    files.forEach(f => { map[f.id] = { ...f, children: [] }; });
    files.forEach(f => {
      if (f.parentId && map[f.parentId]) {
        map[f.parentId].children.push(map[f.id]);
      } else {
        roots.push(map[f.id]);
      }
    });
    const sort = arr => {
      arr.sort((a, b) => {
        if (a.type === 'folder' && b.type !== 'folder') return -1;
        if (a.type !== 'folder' && b.type === 'folder') return 1;
        return a.name.localeCompare(b.name);
      });
      arr.forEach(n => sort(n.children));
      return arr;
    };
    return sort(roots);
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   EXPLORER
══════════════════════════════════════════════════════════════════════════ */
FS.explorer = {
  _openFolders: new Set(),
  _files: [],
  _tree: [],

  async refresh() {
    const proj = FS.projects.current;
    const container = document.getElementById('fs-explorer-content');
    if (!proj) {
      container.innerHTML = `<div class="fs-explorer-empty">
        <p>No project open.</p>
        <button class="fs-btn-primary" onclick="FS.explorer.newProject()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          New Project
        </button>
      </div>`;
      document.getElementById('fs-welcome').classList.remove('hidden');
      FS.status.update({ projectName: 'No Project' });
      return;
    }

    document.getElementById('fs-welcome').classList.add('hidden');
    this._files = await FS.files.getAll(proj.id);
    this._tree = FS.files.buildTree(this._files);

    let html = `<div class="fs-project-switcher">
      <button class="fs-project-name-btn" id="fs-proj-switch-btn" title="Switch project">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        ${FS.ui.esc(proj.name)}
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
    </div>
    <div class="fs-tree">${this._renderTree(this._tree, 1)}</div>`;
    container.innerHTML = html;

    document.getElementById('fs-proj-switch-btn')?.addEventListener('click', e => {
      this.showProjectList(e.currentTarget);
    });
    FS.status.update({ projectName: proj.name });
    FS.tabs.refreshDirtyFromExplorer();
    this._bindTreeEvents();
  },

  _renderTree(nodes, depth) {
    return nodes.map(node => {
      if (node.type === 'folder') {
        const isOpen = this._openFolders.has(node.id);
        return `<div class="fs-tree-item" data-id="${node.id}" data-type="folder" data-depth="${depth}">
          <span class="fs-tree-arrow ${isOpen ? 'open' : ''}">${this._arrowSvg()}</span>
          <span class="fs-tree-icon">${isOpen ? FS.FILE_ICONS['folder-open'] : FS.FILE_ICONS.folder}</span>
          <span class="fs-tree-name">${FS.ui.esc(node.name)}</span>
        </div>
        <div class="fs-tree-children ${isOpen ? 'open' : ''}" data-parent="${node.id}">
          ${this._renderTree(node.children, depth + 1)}
        </div>`;
      }
      const activeTabFileId = FS.tabs._tabs[FS.tabs._activeIdx]?.fileId;
      const isActive = activeTabFileId === node.id;
      return `<div class="fs-tree-item${isActive ? ' active' : ''}" data-id="${node.id}" data-type="file" data-name="${FS.ui.esc(node.name)}" data-depth="${depth}">
        <span class="fs-tree-arrow leaf">${this._arrowSvg()}</span>
        <span class="fs-tree-icon">${getFileIcon(node.name)}</span>
        <span class="fs-tree-name">${FS.ui.esc(node.name)}</span>
      </div>`;
    }).join('');
  },

  _arrowSvg() {
    return `<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>`;
  },

  _bindTreeEvents() {
    document.querySelectorAll('.fs-tree-item[data-type="file"]').forEach(el => {
      el.addEventListener('click', () => FS.tabs.openFile(el.dataset.id));
      el.addEventListener('contextmenu', e => {
        e.preventDefault();
        this._fileContextMenu(el.dataset.id, el.dataset.name, e.clientX, e.clientY);
      });
    });
    document.querySelectorAll('.fs-tree-item[data-type="folder"]').forEach(el => {
      el.addEventListener('click', () => this._toggleFolder(el.dataset.id));
      el.addEventListener('contextmenu', e => {
        e.preventDefault();
        this._folderContextMenu(el.dataset.id, e.clientX, e.clientY);
      });
    });
  },

  _toggleFolder(id) {
    if (this._openFolders.has(id)) this._openFolders.delete(id);
    else this._openFolders.add(id);
    this.refresh();
  },

  _fileContextMenu(id, name, x, y) {
    FS.ui.contextMenu(x, y, [
      { label: 'Open', action: () => FS.tabs.openFile(id) },
      { label: 'Rename', action: () => this.renameFile(id) },
      { label: 'Duplicate', action: () => this.duplicateFile(id) },
      'sep',
      { label: 'Delete', danger: true, action: () => this.deleteFile(id) },
    ]);
  },

  _folderContextMenu(id, x, y) {
    FS.ui.contextMenu(x, y, [
      { label: 'New File Here', action: () => this.newFile(id) },
      { label: 'New Folder Here', action: () => this.newFolder(id) },
      { label: 'Rename', action: () => this.renameFolder(id) },
      'sep',
      { label: 'Delete', danger: true, action: () => this.deleteFolder(id) },
    ]);
  },

  async showProjectList(anchor) {
    const projects = FS.projects._list;
    const items = projects.map(p => ({
      label: p.name + (p.id === FS.projects.current?.id ? ' ✓' : ''),
      action: async () => {
        await FS.projects.setCurrent(p.id);
        FS.tabs.closeAll();
        await this.refresh();
        await FS.welcome.refreshRecent();
      }
    }));
    items.push('sep');
    items.push({ label: '+ New Project', action: () => this.newProject() });
    const rect = anchor.getBoundingClientRect();
    FS.ui.contextMenu(rect.left, rect.bottom + 2, items);
  },

  async newProject() {
    const name = await FS.ui.prompt('New Project', 'Project name:', 'My Project');
    if (!name) return;
    const proj = await FS.projects.create(name);
    await FS.projects.setCurrent(proj.id);
    FS.tabs.closeAll();
    // Create default files
    const html = await FS.files.create(proj.id, null, 'index.html', 'file',
      '<!DOCTYPE html>\n<html lang="en">\n<head>\n  <meta charset="UTF-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <title>' + name + '</title>\n  <link rel="stylesheet" href="style.css">\n</head>\n<body>\n  <h1>Hello, World!</h1>\n  <script src="app.js"><\/script>\n</body>\n</html>');
    await FS.files.create(proj.id, null, 'style.css', 'file', 'body {\n  font-family: system-ui, sans-serif;\n  margin: 0;\n  padding: 20px;\n  background: #f5f5f5;\n}\n\nh1 {\n  color: #333;\n}');
    await FS.files.create(proj.id, null, 'app.js', 'file', "console.log('Hello from " + name + "!');");
    // Auto-link CSS and JS to HTML
    const cssFile = (await FS.files.getAll(proj.id)).find(f => f.name === 'style.css');
    const jsFile  = (await FS.files.getAll(proj.id)).find(f => f.name === 'app.js');
    if (cssFile && jsFile) {
      await FS.projects.updateLinkedAssets(proj.id, html.id, [cssFile.id], [jsFile.id]);
    }
    await this.refresh();
    FS.tabs.openFile(html.id);
    FS.ui.toast('Project "' + name + '" created', 'success');
    await FS.welcome.refreshRecent();
  },

  async newFile(parentId) {
    const proj = FS.projects.current;
    if (!proj) { FS.ui.toast('Open a project first', 'error'); return; }

    // Show file type picker
    const types = [
      { ext: 'html', label: 'HTML' }, { ext: 'css', label: 'CSS' },
      { ext: 'js', label: 'JavaScript' }, { ext: 'json', label: 'JSON' },
      { ext: 'md', label: 'Markdown' }, { ext: 'svg', label: 'SVG' },
      { ext: 'ts', label: 'TypeScript' }, { ext: 'txt', label: 'Text' },
    ];
    let selectedExt = 'html';
    const grid = types.map(t =>
      `<button class="fs-file-type-btn${t.ext === selectedExt ? ' selected' : ''}" data-ext="${t.ext}">${getFileIcon(t.ext + '.' + t.ext)}<span>${t.label}</span></button>`
    ).join('');

    const body = document.createElement('div');
    body.innerHTML = `<label class="fs-modal-label">File name:</label>
      <input class="fs-modal-input" id="new-file-name" value="index.html" type="text">
      <div class="fs-modal-label" style="margin-top:8px;">File type:</div>
      <div class="fs-modal-file-grid">${grid}</div>`;
    FS.ui.modal('New File', body,
      `<button class="fs-sm-btn ghost" id="modal-cancel">Cancel</button><button class="fs-sm-btn" id="modal-ok">Create</button>`);

    body.querySelectorAll('.fs-file-type-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        body.querySelectorAll('.fs-file-type-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        selectedExt = btn.dataset.ext;
        const nameInput = document.getElementById('new-file-name');
        const cur = nameInput.value;
        const base = cur.includes('.') ? cur.split('.').slice(0, -1).join('.') : cur;
        nameInput.value = base + '.' + selectedExt;
      });
    });

    document.getElementById('modal-ok')?.addEventListener('click', async () => {
      const name = document.getElementById('new-file-name')?.value.trim();
      if (!name) return;
      FS.ui.closeModal();
      const defaults = {
        html: '<!DOCTYPE html>\n<html lang="en">\n<head>\n  <meta charset="UTF-8">\n  <title>Document</title>\n</head>\n<body>\n  \n</body>\n</html>',
        css: '/* Styles */\n',
        js: '// JavaScript\n',
        json: '{\n  \n}\n',
        md: '# Title\n\n',
        svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">\n  \n</svg>\n',
        ts: '// TypeScript\n',
        txt: '',
      };
      const ext = name.split('.').pop().toLowerCase();
      const content = defaults[ext] || '';
      const file = await FS.files.create(proj.id, parentId || null, name, 'file', content);
      await this.refresh();
      FS.tabs.openFile(file.id);
      FS.ui.toast('File "' + name + '" created', 'success');
    });
    document.getElementById('modal-cancel')?.addEventListener('click', () => FS.ui.closeModal());
    document.getElementById('fs-modal-close')?.addEventListener('click', () => FS.ui.closeModal(), { once: true });
  },

  async newFolder(parentId) {
    const proj = FS.projects.current;
    if (!proj) return;
    const name = await FS.ui.prompt('New Folder', 'Folder name:', 'components');
    if (!name) return;
    await FS.files.create(proj.id, parentId || null, name, 'folder');
    this._openFolders.add(parentId);
    await this.refresh();
  },

  async exportZip() {
    const proj = FS.projects.current;
    if (!proj) { FS.ui.toast('No project open', 'error'); return; }
    if (typeof JSZip === 'undefined') { FS.ui.toast('JSZip not available', 'error'); return; }
    const files = await FS.files.getAll(proj.id);
    const zip = new JSZip();
    const addFiles = (nodes, folder) => {
      nodes.forEach(node => {
        if (node.type === 'folder') {
          addFiles(node.children || [], folder.folder(node.name));
        } else {
          folder.file(node.name, node.content || '');
        }
      });
    };
    // Build flat list, skip folders for now (flat export)
    files.filter(f => f.type === 'file').forEach(f => zip.file(f.name, f.content || ''));
    try {
      const blob = await zip.generateAsync({ type: 'blob', compression: 'DEFLATE' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = (proj.name || 'project').replace(/[^a-z0-9_-]/gi, '_') + '.zip';
      a.click();
      setTimeout(() => URL.revokeObjectURL(a.href), 5000);
      FS.ui.toast('Project exported as ZIP', 'success');
    } catch(e) {
      FS.ui.toast('Export failed: ' + e.message, 'error');
    }
  },

  async importZip() {
    if (typeof JSZip === 'undefined') { FS.ui.toast('JSZip not available', 'error'); return; }
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.zip';
    input.onchange = async (e) => {
      const file = e.target.files[0];
      if (!file) return;
      try {
        const zip = await JSZip.loadAsync(file);
        const projName = file.name.replace(/\.zip$/i, '') || 'Imported Project';
        const proj = await FS.projects.create(projName);
        await FS.projects.setCurrent(proj.id);
        FS.tabs.closeAll();
        const entries = [];
        zip.forEach((path, entry) => { if (!entry.dir) entries.push({ path, entry }); });
        for (const { path, entry } of entries) {
          try {
            const content = await entry.async('string');
            const name = path.split('/').pop();
            await FS.files.create(proj.id, null, name, 'file', content);
          } catch(_) { /* skip binary files */ }
        }
        await this.refresh();
        await FS.welcome.refreshRecent();
        FS.ui.toast(`Imported "${projName}" (${entries.length} files)`, 'success');
      } catch(e) {
        FS.ui.toast('Import failed: ' + e.message, 'error');
      }
    };
    input.click();
  },

  async newProjectFromTemplate(templateId) {
    const templates = {
      blank: { name: 'Blank Project', files: [
        { name: 'index.html', content: '<!DOCTYPE html>\n<html lang="en">\n<head>\n  <meta charset="UTF-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <title>Blank</title>\n</head>\n<body>\n\n</body>\n</html>' },
      ]},
      webapp: { name: 'Web App', files: [
        { name: 'index.html', content: '<!DOCTYPE html>\n<html lang="en">\n<head>\n  <meta charset="UTF-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <title>My App</title>\n  <link rel="stylesheet" href="style.css">\n</head>\n<body>\n  <div id="app">\n    <header class="header">\n      <h1>My App</h1>\n      <nav>\n        <a href="#">Home</a>\n        <a href="#">About</a>\n        <a href="#">Contact</a>\n      </nav>\n    </header>\n    <main class="main">\n      <h2>Welcome!</h2>\n      <p>Start building your app here.</p>\n      <button id="btn">Click Me</button>\n    </main>\n  </div>\n  <script src="app.js"><\/script>\n</body>\n</html>' },
        { name: 'style.css', content: '*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }\n\nbody {\n  font-family: system-ui, -apple-system, sans-serif;\n  background: #f8fafc;\n  color: #1e293b;\n  min-height: 100vh;\n}\n\n.header {\n  background: #1e293b;\n  color: #fff;\n  padding: 1rem 2rem;\n  display: flex;\n  align-items: center;\n  justify-content: space-between;\n}\n\n.header nav a {\n  color: #94a3b8;\n  text-decoration: none;\n  margin-left: 1.5rem;\n  transition: color .2s;\n}\n\n.header nav a:hover { color: #fff; }\n\n.main {\n  max-width: 800px;\n  margin: 3rem auto;\n  padding: 0 1rem;\n}\n\n.main h2 { margin-bottom: 0.75rem; }\n\nbutton {\n  margin-top: 1rem;\n  padding: 0.5rem 1.5rem;\n  background: #3b82f6;\n  color: #fff;\n  border: none;\n  border-radius: 6px;\n  font-size: 1rem;\n  cursor: pointer;\n  transition: background .2s;\n}\n\nbutton:hover { background: #2563eb; }' },
        { name: 'app.js', content: "document.getElementById('btn')?.addEventListener('click', () => {\n  alert('Hello from My App!');\n});\n" },
      ]},
      landing: { name: 'Landing Page', files: [
        { name: 'index.html', content: '<!DOCTYPE html>\n<html lang="en">\n<head>\n  <meta charset="UTF-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <title>Product Landing</title>\n  <link rel="stylesheet" href="style.css">\n</head>\n<body>\n  <nav class="nav">\n    <span class="nav-brand">Brand</span>\n    <a href="#features">Features</a>\n    <a href="#cta" class="btn-nav">Get Started</a>\n  </nav>\n  <section class="hero">\n    <h1>Build Something Amazing</h1>\n    <p>The fastest way to launch your next product.</p>\n    <a href="#cta" class="btn-hero">Start for Free →</a>\n  </section>\n  <section class="features" id="features">\n    <h2>Why Choose Us</h2>\n    <div class="feature-grid">\n      <div class="feature-card"><h3>⚡ Fast</h3><p>Lightning-fast performance out of the box.</p></div>\n      <div class="feature-card"><h3>🔒 Secure</h3><p>Enterprise-grade security built in.</p></div>\n      <div class="feature-card"><h3>🎨 Beautiful</h3><p>Stunning UI with zero effort.</p></div>\n    </div>\n  </section>\n  <section class="cta" id="cta">\n    <h2>Ready to start?</h2>\n    <p>Join thousands of happy users today.</p>\n    <a href="#" class="btn-hero">Get Started Free</a>\n  </section>\n  <footer>© 2025 Brand. All rights reserved.</footer>\n</body>\n</html>' },
        { name: 'style.css', content: "*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }\nbody { font-family: system-ui, sans-serif; color: #1e293b; }\n\n.nav { display: flex; align-items: center; gap: 2rem; padding: 1rem 2rem; background: #fff; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 10; }\n.nav-brand { font-weight: 700; font-size: 1.2rem; margin-right: auto; }\n.nav a { color: #475569; text-decoration: none; font-size: 0.95rem; }\n.btn-nav { background: #3b82f6; color: #fff !important; padding: 0.4rem 1rem; border-radius: 6px; }\n\n.hero { text-align: center; padding: 6rem 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }\n.hero h1 { font-size: clamp(2rem, 5vw, 3.5rem); font-weight: 800; margin-bottom: 1rem; }\n.hero p { font-size: 1.2rem; opacity: 0.9; margin-bottom: 2rem; }\n.btn-hero { display: inline-block; padding: 0.75rem 2rem; background: #fff; color: #667eea; border-radius: 8px; text-decoration: none; font-weight: 700; transition: transform .2s, box-shadow .2s; }\n.btn-hero:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,.2); }\n\n.features { padding: 5rem 2rem; background: #f8fafc; }\n.features h2 { text-align: center; font-size: 2rem; margin-bottom: 3rem; }\n.feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; max-width: 900px; margin: 0 auto; }\n.feature-card { background: #fff; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,.06); }\n.feature-card h3 { font-size: 1.1rem; margin-bottom: 0.5rem; }\n\n.cta { text-align: center; padding: 5rem 2rem; background: #1e293b; color: #fff; }\n.cta h2 { font-size: 2rem; margin-bottom: 0.75rem; }\n.cta p { opacity: 0.7; margin-bottom: 2rem; }\n.cta .btn-hero { background: #3b82f6; color: #fff; }\n\nfooter { text-align: center; padding: 1.5rem; font-size: 0.875rem; color: #64748b; background: #0f172a; color: #475569; }" },
      ]},
      portfolio: { name: 'Portfolio', files: [
        { name: 'index.html', content: '<!DOCTYPE html>\n<html lang="en">\n<head>\n  <meta charset="UTF-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <title>My Portfolio</title>\n  <link rel="stylesheet" href="style.css">\n</head>\n<body>\n  <header class="hero">\n    <img src="https://via.placeholder.com/120" alt="Profile Photo" class="avatar">\n    <h1>Your Name</h1>\n    <p class="tagline">Frontend Developer · UI Designer</p>\n    <div class="links">\n      <a href="#">GitHub</a> · <a href="#">LinkedIn</a> · <a href="mailto:you@email.com">Email</a>\n    </div>\n  </header>\n  <main>\n    <section class="about">\n      <h2>About Me</h2>\n      <p>I build beautiful, fast, and accessible web experiences. Passionate about clean code and great design.</p>\n    </section>\n    <section class="projects">\n      <h2>Projects</h2>\n      <div class="project-grid">\n        <div class="project-card"><h3>Project One</h3><p>A short description of the project and what it does.</p><a href="#">View →</a></div>\n        <div class="project-card"><h3>Project Two</h3><p>A short description of the project and what it does.</p><a href="#">View →</a></div>\n        <div class="project-card"><h3>Project Three</h3><p>A short description of the project and what it does.</p><a href="#">View →</a></div>\n      </div>\n    </section>\n  </main>\n  <footer>Made with ❤ by Your Name</footer>\n</body>\n</html>' },
        { name: 'style.css', content: "*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }\nbody { font-family: system-ui, sans-serif; color: #1e293b; background: #f8fafc; }\n.hero { text-align: center; padding: 4rem 2rem; background: #fff; border-bottom: 1px solid #e2e8f0; }\n.avatar { width: 100px; height: 100px; border-radius: 50%; border: 4px solid #e2e8f0; margin-bottom: 1rem; }\n.hero h1 { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }\n.tagline { color: #64748b; margin-bottom: 1rem; }\n.links a { color: #3b82f6; text-decoration: none; }\nmain { max-width: 900px; margin: 3rem auto; padding: 0 1.5rem; }\nsection { margin-bottom: 3rem; }\nh2 { font-size: 1.5rem; margin-bottom: 1.25rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0; }\n.project-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; }\n.project-card { background: #fff; border-radius: 10px; padding: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,.07); }\n.project-card h3 { margin-bottom: 0.5rem; }\n.project-card p { color: #64748b; font-size: 0.9rem; margin-bottom: 0.75rem; }\n.project-card a { color: #3b82f6; font-size: 0.9rem; text-decoration: none; font-weight: 600; }\nfooter { text-align: center; padding: 2rem; color: #94a3b8; font-size: 0.875rem; }" },
      ]},
    };
    const tpl = templates[templateId];
    if (!tpl) return;
    const proj = await FS.projects.create(tpl.name);
    await FS.projects.setCurrent(proj.id);
    FS.tabs.closeAll();
    let firstFile = null;
    for (const f of tpl.files) {
      const created = await FS.files.create(proj.id, null, f.name, 'file', f.content);
      if (!firstFile) firstFile = created;
    }
    // Auto-link assets
    const allFiles = await FS.files.getAll(proj.id);
    const htmlFile = allFiles.find(f => f.name.endsWith('.html'));
    const cssFile = allFiles.find(f => f.name.endsWith('.css'));
    const jsFile = allFiles.find(f => f.name.endsWith('.js'));
    if (htmlFile && (cssFile || jsFile)) {
      await FS.projects.updateLinkedAssets(proj.id, htmlFile.id, cssFile ? [cssFile.id] : [], jsFile ? [jsFile.id] : []);
    }
    await this.refresh();
    if (htmlFile) FS.tabs.openFile(htmlFile.id);
    else if (firstFile) FS.tabs.openFile(firstFile.id);
    FS.ui.toast(`"${tpl.name}" template loaded`, 'success');
    await FS.welcome.refreshRecent();
  },

  async renameFile(id) {
    const f = await FS.files.get(id);
    if (!f) return;
    const name = await FS.ui.prompt('Rename File', 'New name:', f.name);
    if (!name || name === f.name) return;
    await FS.files.rename(id, name);
    FS.tabs.updateTabName(id, name);
    await this.refresh();
  },

  async renameFolder(id) {
    const f = await FS.files.get(id);
    if (!f) return;
    const name = await FS.ui.prompt('Rename Folder', 'New name:', f.name);
    if (!name || name === f.name) return;
    await FS.files.rename(id, name);
    await this.refresh();
  },

  async duplicateFile(id) {
    const f = await FS.files.duplicate(id);
    if (!f) return;
    await this.refresh();
    FS.ui.toast('Duplicated as "' + f.name + '"', 'success');
  },

  async deleteFile(id) {
    const f = await FS.files.get(id);
    if (!f) return;
    const ok = await FS.ui.confirm('Delete File', `Delete "${f.name}"? This cannot be undone.`);
    if (!ok) return;
    FS.tabs.closeTab(FS.tabs._tabs.findIndex(t => t.fileId === id));
    await FS.files.delete(id);
    await this.refresh();
    FS.ui.toast('Deleted "' + f.name + '"', 'info');
  },

  async deleteFolder(id) {
    const f = await FS.files.get(id);
    if (!f) return;
    const ok = await FS.ui.confirm('Delete Folder', `Delete "${f.name}" and all its contents?`);
    if (!ok) return;
    await FS.files.delete(id);
    await this.refresh();
  },

  collapseAll() {
    this._openFolders.clear();
    this.refresh();
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   TABS
══════════════════════════════════════════════════════════════════════════ */
FS.tabs = {
  _tabs: [],
  _activeIdx: -1,
  _closedHistory: [],

  async openFile(fileId) {
    const existing = this._tabs.findIndex(t => t.fileId === fileId);
    if (existing !== -1) {
      this.setActive(existing);
      return;
    }
    const f = await FS.files.get(fileId);
    if (!f || f.type === 'folder') return;
    this._tabs.push({ fileId: f.id, name: f.name, lang: getLang(f.name), dirty: false, pinned: false });
    this.setActive(this._tabs.length - 1);
  },

  setActive(idx) {
    if (idx < 0 || idx >= this._tabs.length) return;
    this._activeIdx = idx;
    const tab = this._tabs[idx];
    this.render();
    FS.editor.openFile(tab.fileId);
    FS.breadcrumb.update(tab.name);
    FS.status.update({ lang: tab.lang });
    // Highlight active file in explorer
    document.querySelectorAll('.fs-tree-item').forEach(el => {
      el.classList.toggle('active', el.dataset.id === tab.fileId);
    });
    FS.events.emit('tab:change', tab);
  },

  closeTab(idx, force = false) {
    if (idx < 0 || idx >= this._tabs.length) return;
    const tab = this._tabs[idx];
    if (tab.dirty && !force) {
      FS.ui.confirm('Unsaved Changes', `"${tab.name}" has unsaved changes. Discard?`).then(ok => {
        if (ok) this._doClose(idx);
      });
      return;
    }
    this._doClose(idx);
  },

  _doClose(idx) {
    const tab = this._tabs[idx];
    this._closedHistory.push(tab.fileId);
    if (this._closedHistory.length > 20) this._closedHistory.shift();
    this._tabs.splice(idx, 1);
    if (this._tabs.length === 0) {
      this._activeIdx = -1;
      FS.editor.showWelcome();
      this.render();
      return;
    }
    const newIdx = Math.min(idx, this._tabs.length - 1);
    this.setActive(newIdx);
  },

  closeAll() {
    this._tabs = [];
    this._activeIdx = -1;
    this.render();
    FS.editor.showWelcome();
  },

  closeOthers(idx) {
    const tab = this._tabs[idx];
    this._tabs = [tab];
    this._activeIdx = 0;
    this.render();
    FS.editor.openFile(tab.fileId);
  },

  closeToRight(idx) {
    this._tabs = this._tabs.slice(0, idx + 1);
    if (this._activeIdx > idx) this._activeIdx = idx;
    this.render();
  },

  reopenClosed() {
    const fileId = this._closedHistory.pop();
    if (fileId) this.openFile(fileId);
  },

  markDirty(fileId, dirty) {
    const idx = this._tabs.findIndex(t => t.fileId === fileId);
    if (idx !== -1) {
      this._tabs[idx].dirty = dirty;
      this.render();
    }
  },

  updateTabName(fileId, name) {
    const idx = this._tabs.findIndex(t => t.fileId === fileId);
    if (idx !== -1) {
      this._tabs[idx].name = name;
      this._tabs[idx].lang = getLang(name);
      this.render();
    }
  },

  refreshDirtyFromExplorer() { this.render(); },

  next() {
    if (this._tabs.length < 2) return;
    this.setActive((this._activeIdx + 1) % this._tabs.length);
  },

  prev() {
    if (this._tabs.length < 2) return;
    this.setActive((this._activeIdx - 1 + this._tabs.length) % this._tabs.length);
  },

  render() {
    const container = document.getElementById('fs-tabs');
    container.innerHTML = this._tabs.map((tab, idx) => `
      <div class="fs-tab${idx === this._activeIdx ? ' active' : ''}${tab.dirty ? ' dirty' : ''}${tab.pinned ? ' pinned' : ''}"
           data-idx="${idx}" title="${FS.ui.esc(tab.name)}">
        <span class="fs-tab-icon">${getFileIcon(tab.name)}</span>
        <span class="fs-tab-name">${FS.ui.esc(tab.name)}</span>
        <span class="fs-tab-dirty"></span>
        <button class="fs-tab-close" data-idx="${idx}" title="Close">
          <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>`).join('');

    container.querySelectorAll('.fs-tab').forEach(el => {
      const idx = parseInt(el.dataset.idx);
      el.addEventListener('click', e => {
        if (!e.target.closest('.fs-tab-close')) this.setActive(idx);
      });
      el.addEventListener('contextmenu', e => {
        e.preventDefault();
        FS.ui.contextMenu(e.clientX, e.clientY, [
          { label: 'Close', action: () => this.closeTab(idx) },
          { label: 'Close Others', action: () => this.closeOthers(idx) },
          { label: 'Close to the Right', action: () => this.closeToRight(idx) },
          'sep',
          { label: 'Reopen Closed Tab', action: () => this.reopenClosed(), kbd: 'Ctrl+Shift+T' },
        ]);
      });
    });
    container.querySelectorAll('.fs-tab-close').forEach(btn => {
      btn.addEventListener('click', e => {
        e.stopPropagation();
        this.closeTab(parseInt(btn.dataset.idx));
      });
    });

    // Scroll active tab into view
    const activeEl = container.querySelector('.fs-tab.active');
    if (activeEl) activeEl.scrollIntoView({ block: 'nearest', inline: 'nearest' });
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   EDITOR (Monaco)
══════════════════════════════════════════════════════════════════════════ */
FS.editor = {
  _instance: null,
  _ready: false,
  _currentFileId: null,
  _saveTimer: null,
  _decorations: [],

  async init() {
    return new Promise(resolve => {
      require(['vs/editor/editor.main'], () => {
        const settings = FS.settings;
        this._instance = monaco.editor.create(
          document.getElementById('fs-monaco-container'), {
            value: '',
            language: 'html',
            theme: FS.THEMES[settings.get('theme')]?.monaco || 'vs-dark',
            fontSize: settings.get('editorFontSize'),
            fontFamily: settings.get('editorFontFamily'),
            tabSize: settings.get('editorTabSize'),
            insertSpaces: settings.get('editorInsertSpaces'),
            wordWrap: settings.get('editorWordWrap'),
            minimap: { enabled: settings.get('editorMinimap') },
            lineNumbers: settings.get('editorLineNumbers'),
            renderLineHighlight: 'all',
            scrollBeyondLastLine: false,
            smoothScrolling: true,
            cursorSmoothCaretAnimation: 'on',
            bracketPairColorization: { enabled: true },
            guides: { bracketPairs: true, indentation: true },
            suggest: { showKeywords: true },
            quickSuggestions: true,
            automaticLayout: true,
            padding: { top: 8, bottom: 8 },
            fontLigatures: true,
            renderWhitespace: 'selection',
            scrollbar: { verticalScrollbarSize: 8, horizontalScrollbarSize: 8 },
          }
        );

        this._instance.onDidChangeModelContent(() => {
          if (!this._currentFileId) return;
          FS.tabs.markDirty(this._currentFileId, true);
          if (settings.get('editorAutoSave')) {
            clearTimeout(this._saveTimer);
            this._saveTimer = setTimeout(() => this.save(), settings.get('autoSaveDelay'));
          }
          if (settings.get('previewAutoRefresh') && !settings.get('previewPaused')) {
            clearTimeout(this._previewTimer);
            this._previewTimer = setTimeout(() => FS.preview.refresh(), 600);
          }
        });

        this._instance.onDidChangeCursorPosition(e => {
          FS.status.update({ line: e.position.lineNumber, col: e.position.column });
        });

        this._instance.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, () => this.save());
        this._instance.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyMod.Shift | monaco.KeyCode.KeyS, () => this.saveAll());
        this._instance.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyMod.Shift | monaco.KeyCode.KeyP, () => FS.palette.open());
        this._instance.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyP, () => FS.palette.openQuickFile());

        document.getElementById('fs-editor-loading').classList.add('hidden');
        this._ready = true;
        resolve(this._instance);
      });
    });
  },

  async openFile(fileId) {
    if (!this._ready) return;
    const f = await FS.files.get(fileId);
    if (!f) return;
    this._currentFileId = fileId;
    const lang = getLang(f.name);
    const model = monaco.editor.createModel(f.content, lang);
    const old = this._instance.getModel();
    this._instance.setModel(model);
    if (old) old.dispose();
    // Hide welcome, show editor
    document.getElementById('fs-welcome').classList.add('hidden');
    FS.breadcrumb.update(f.name);
    FS.status.update({ lang });
    FS.events.emit('file:open', f);
  },

  async save(fileId) {
    const id = fileId || this._currentFileId;
    if (!id) return;
    const content = this._instance.getValue();
    await FS.files.updateContent(id, content);
    FS.tabs.markDirty(id, false);
    FS.status.update({ autosave: 'Saved' });
    setTimeout(() => FS.status.update({ autosave: FS.settings.get('editorAutoSave') ? 'Autosave: On' : 'Autosave: Off' }), 2000);
    if (FS.settings.get('previewAutoRefresh')) FS.preview.refresh();
    FS.events.emit('file:save', id);
  },

  async saveAll() {
    for (const tab of FS.tabs._tabs) {
      if (tab.dirty) await this.save(tab.fileId);
    }
    FS.ui.toast('All files saved', 'success');
  },

  showWelcome() {
    document.getElementById('fs-welcome').classList.remove('hidden');
    this._currentFileId = null;
    if (this._instance) {
      const model = monaco.editor.createModel('', 'plaintext');
      const old = this._instance.getModel();
      this._instance.setModel(model);
      if (old) old.dispose();
    }
  },

  format() {
    this._instance?.getAction('editor.action.formatDocument')?.run();
  },

  getContent() { return this._instance?.getValue() || ''; },
  getSelection() {
    const sel = this._instance?.getSelection();
    if (!sel) return '';
    return this._instance.getModel()?.getValueInRange(sel) || '';
  },
  replaceSelection(text) {
    const sel = this._instance?.getSelection();
    if (!sel) return;
    this._instance.executeEdits('ai', [{ range: sel, text }]);
  },
  insertAtCursor(text) {
    const pos = this._instance?.getPosition();
    if (!pos) return;
    this._instance.executeEdits('ai', [{ range: new monaco.Range(pos.lineNumber, pos.column, pos.lineNumber, pos.column), text }]);
  },
  goToLine(n) {
    this._instance?.revealLineInCenter(n);
    this._instance?.setPosition({ lineNumber: n, column: 1 });
    this._instance?.focus();
  },
  updateOptions(opts) {
    this._instance?.updateOptions(opts);
  },
  applyTheme(themeKey) {
    const mt = FS.THEMES[themeKey]?.monaco || 'vs-dark';
    if (this._ready) monaco.editor.setTheme(mt);
  },
  focus() { this._instance?.focus(); },
};

/* ══════════════════════════════════════════════════════════════════════════
   BREADCRUMB
══════════════════════════════════════════════════════════════════════════ */
FS.breadcrumb = {
  update(fileName) {
    const proj = FS.projects.current;
    const el = document.getElementById('fs-breadcrumb');
    if (!el) return;
    el.innerHTML = proj ? `<span>${FS.ui.esc(proj.name)}</span><span class="fs-breadcrumb-sep">›</span><span>${FS.ui.esc(fileName)}</span>` : FS.ui.esc(fileName);
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   PREVIEW
══════════════════════════════════════════════════════════════════════════ */
FS.preview = {
  _device: 'desktop',
  _zoom: 1,
  _landscape: false,
  _paused: false,
  _visible: false,
  _blobUrl: null,

  DEVICES: {
    desktop:   { w: 1280, h: 720 },
    tablet:    { w: 768,  h: 1024 },
    phone:     { w: 375,  h: 812 },
    responsive: { w: null, h: null },
  },

  show() {
    this._visible = true;
    document.getElementById('fs-preview-pane').style.display = 'flex';
    document.getElementById('fs-preview-resize').style.display = 'block';
    document.getElementById('fs-btn-preview-toggle').style.opacity = '1';
    this.refresh();
  },

  hide() {
    this._visible = false;
    document.getElementById('fs-preview-pane').style.display = 'none';
    document.getElementById('fs-preview-resize').style.display = 'none';
  },

  toggle() {
    if (this._visible) this.hide(); else this.show();
  },

  downloadCurrentFile() {
    const tab = FS.tabs._tabs[FS.tabs._activeIdx];
    if (!tab) { FS.ui.toast('No file open', 'error'); return; }
    const content = FS.editor.getContent();
    const blob = new Blob([content], { type: 'text/plain' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = tab.name;
    a.click();
    setTimeout(() => URL.revokeObjectURL(a.href), 5000);
    FS.ui.toast('Downloaded ' + tab.name, 'success');
  },

  setDevice(device) {
    this._device = device;
    document.querySelectorAll('.fs-device-btn').forEach(b => b.classList.toggle('active', b.dataset.device === device));
    this._applyDevice();
  },

  _applyDevice() {
    const iframe = document.getElementById('fs-preview-iframe');
    const wrap   = document.getElementById('fs-preview-frame-wrap');
    const dims   = document.getElementById('fs-preview-dims');
    const dev = this.DEVICES[this._device];
    if (!dev || !dev.w) {
      iframe.style.width = '100%';
      iframe.style.height = '100%';
      dims.textContent = 'Responsive';
      wrap.style.alignItems = 'stretch';
      wrap.style.justifyContent = 'stretch';
    } else {
      let w = this._landscape ? dev.h : dev.w;
      let h = this._landscape ? dev.w : dev.h;
      dims.textContent = `${w} × ${h}`;
      iframe.style.width = w + 'px';
      iframe.style.height = h + 'px';
      iframe.style.transform = `scale(${this._zoom})`;
      iframe.style.transformOrigin = 'top left';
      wrap.style.alignItems = 'flex-start';
      wrap.style.justifyContent = 'flex-start';
    }
  },

  async refresh() {
    if (this._paused || !this._visible) return;
    const proj = FS.projects.current;
    const activeTab = FS.tabs._tabs[FS.tabs._activeIdx];
    if (!proj || !activeTab) {
      this._loadEmpty();
      return;
    }
    const file = await FS.files.get(activeTab.fileId);
    if (!file) return;

    const lang = getLang(file.name);
    let html = '';

    if (lang === 'html') {
      html = await this._buildHtmlDoc(proj, file);
    } else if (lang === 'markdown') {
      html = this._markdownPreview(file.content);
    } else if (lang === 'css' || lang === 'scss') {
      html = `<!DOCTYPE html><html><head><style>${file.content}</style></head><body><p style="padding:20px;font-family:sans-serif;color:#666">CSS file — no HTML to preview</p></body></html>`;
    } else if (lang === 'xml') {
      html = `<!DOCTYPE html><html><head></head><body><pre style="padding:20px;font-family:monospace;font-size:13px;white-space:pre-wrap">${FS.ui.esc(file.content)}</pre></body></html>`;
    } else if (lang === 'javascript') {
      html = `<!DOCTYPE html><html><head><title>JS Preview</title></head><body><script>${file.content}<\/script><p style="padding:20px;font-family:sans-serif;color:#666">JS file — open console to see output</p></body></html>`;
    } else {
      html = `<!DOCTYPE html><html><head></head><body><pre style="padding:20px;font-family:monospace;font-size:13px;white-space:pre-wrap;margin:0">${FS.ui.esc(file.content)}</pre></body></html>`;
    }

    this._loadHtml(html);
    this._applyDevice();
  },

  async _buildHtmlDoc(proj, htmlFile) {
    const linkedAssets = proj.linkedAssets?.[htmlFile.id] || {};
    const cssIds = linkedAssets.css || [];
    const jsIds  = linkedAssets.js  || [];
    const allFiles = await FS.files.getAll(proj.id);
    const fileMap = {};
    allFiles.forEach(f => fileMap[f.id] = f);

    let content = htmlFile.content;
    // Inline linked CSS files
    const cssBlocks = cssIds.map(id => fileMap[id]?.content || '').filter(Boolean);
    const jsBlocks  = jsIds.map(id  => fileMap[id]?.content || '').filter(Boolean);

    // Inject inline style/script instead of href/src since blob can't load external refs
    if (cssBlocks.length) {
      const styleTag = `<style>/* injected by Frontend Studio */\n${cssBlocks.join('\n')}\n</style>`;
      if (content.includes('</head>')) {
        content = content.replace('</head>', styleTag + '</head>');
      } else {
        content = styleTag + content;
      }
    }
    if (jsBlocks.length) {
      const scriptTag = `<script>/* injected */\n${jsBlocks.join('\n')}\n<\/script>`;
      if (content.includes('</body>')) {
        content = content.replace('</body>', scriptTag + '</body>');
      } else {
        content = content + scriptTag;
      }
    }
    // Remove file-based href/src references since we inline them
    content = content.replace(/href="[^"]*\.css"/g, 'href=""');
    content = content.replace(/src="[^"]*\.js"/g, 'src=""');
    return content;
  },

  _markdownPreview(md) {
    const escaped = md
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/^### (.+)/gm, '<h3>$1</h3>')
      .replace(/^## (.+)/gm, '<h2>$1</h2>')
      .replace(/^# (.+)/gm, '<h1>$1</h1>')
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/\*(.+?)\*/g, '<em>$1</em>')
      .replace(/`([^`]+)`/g, '<code>$1</code>')
      .replace(/^- (.+)/gm, '<li>$1</li>')
      .replace(/\n\n/g, '</p><p>')
      .replace(/\n/g, '<br>');
    return `<!DOCTYPE html><html><head><style>body{font-family:system-ui;max-width:680px;margin:0 auto;padding:20px;line-height:1.7;color:#333}h1,h2,h3{color:#111}code{background:#f0f0f0;padding:2px 5px;border-radius:3px;font-size:.9em}pre{background:#f5f5f5;padding:12px;border-radius:6px;overflow:auto}</style></head><body><p>${escaped}</p></body></html>`;
  },

  _loadHtml(html) {
    const iframe = document.getElementById('fs-preview-iframe');
    if (this._blobUrl) URL.revokeObjectURL(this._blobUrl);
    const blob = new Blob([html], { type: 'text/html' });
    this._blobUrl = URL.createObjectURL(blob);
    iframe.src = this._blobUrl;
  },

  _loadEmpty() {
    document.getElementById('fs-preview-iframe').src = 'about:blank';
  },

  openInNewWindow() {
    if (this._blobUrl) window.open(this._blobUrl, '_blank');
    else FS.ui.toast('Refresh preview first', 'error');
  },

  async manageLinks() {
    const proj = FS.projects.current;
    const tab = FS.tabs._tabs[FS.tabs._activeIdx];
    if (!proj || !tab) return;
    const file = await FS.files.get(tab.fileId);
    if (!file || getLang(file.name) !== 'html') {
      FS.ui.toast('Open an HTML file to manage links', 'info');
      return;
    }
    const allFiles = await FS.files.getAll(proj.id);
    const cssFiles = allFiles.filter(f => ['css','scss','less'].includes(f.name.split('.').pop().toLowerCase()));
    const jsFiles  = allFiles.filter(f => ['js','mjs','ts'].includes(f.name.split('.').pop().toLowerCase()));
    const linked = proj.linkedAssets?.[file.id] || { css: [], js: [] };

    const body = `<p class="fs-modal-text" style="margin-bottom:10px;">Choose which CSS and JS files to include in the preview for <strong>${FS.ui.esc(file.name)}</strong>.</p>
    <strong style="font-size:12px;color:var(--fs-text-dim)">CSS Files</strong>
    <div class="fs-link-checkbox-list" id="link-css-list">
      ${cssFiles.map(f => `<label class="fs-link-checkbox"><input type="checkbox" value="${f.id}" ${linked.css.includes(f.id)?'checked':''}> ${FS.ui.esc(f.name)}</label>`).join('') || '<span style="font-size:12px;color:var(--fs-text-dim)">No CSS files in project</span>'}
    </div>
    <strong style="font-size:12px;color:var(--fs-text-dim);display:block;margin-top:12px">JS Files</strong>
    <div class="fs-link-checkbox-list" id="link-js-list">
      ${jsFiles.map(f => `<label class="fs-link-checkbox"><input type="checkbox" value="${f.id}" ${linked.js.includes(f.id)?'checked':''}> ${FS.ui.esc(f.name)}</label>`).join('') || '<span style="font-size:12px;color:var(--fs-text-dim)">No JS files in project</span>'}
    </div>`;

    FS.ui.modal('Manage Asset Links', body,
      `<button class="fs-sm-btn ghost" id="modal-cancel">Cancel</button><button class="fs-sm-btn" id="modal-ok">Save Links</button>`
    );
    document.getElementById('modal-ok')?.addEventListener('click', async () => {
      const cssIds = [...document.querySelectorAll('#link-css-list input:checked')].map(i => i.value);
      const jsIds  = [...document.querySelectorAll('#link-js-list input:checked')].map(i => i.value);
      await FS.projects.updateLinkedAssets(proj.id, file.id, cssIds, jsIds);
      FS.ui.closeModal();
      FS.ui.toast('Asset links saved', 'success');
      this.refresh();
    });
    document.getElementById('modal-cancel')?.addEventListener('click', () => FS.ui.closeModal());
    document.getElementById('fs-modal-close')?.addEventListener('click', () => FS.ui.closeModal(), { once: true });
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   EXTENSIONS
══════════════════════════════════════════════════════════════════════════ */
FS.extensions = {
  _extensions: [
    { id: 'html-fmt',    name: 'HTML Formatter',      desc: 'Beautify HTML with proper indentation',      icon: '🌐', core: true  },
    { id: 'css-fmt',     name: 'CSS Formatter',       desc: 'Format CSS, SCSS, LESS',                    icon: '🎨', core: true  },
    { id: 'js-fmt',      name: 'JS Formatter',        desc: 'Beautify JavaScript code',                  icon: '⚡', core: true  },
    { id: 'json-fmt',    name: 'JSON Formatter',      desc: 'Format and validate JSON',                  icon: '📦', core: true  },
    { id: 'minifier',    name: 'Minifier',            desc: 'Minify HTML, CSS, or JS',                   icon: '📉', core: false },
    { id: 'md-preview',  name: 'Markdown Preview',    desc: 'Real-time markdown rendering',              icon: '📝', core: true  },
    { id: 'color-pick',  name: 'Color Picker',        desc: 'Visual color selector with formats',        icon: '🎨', core: false },
    { id: 'gradient',    name: 'Gradient Generator',  desc: 'Build CSS gradients with live preview',     icon: '🌈', core: false },
    { id: 'shadow',      name: 'Box Shadow Generator',desc: 'Create box-shadow with visual controls',    icon: '🔲', core: false },
    { id: 'border-rad',  name: 'Border Radius Tool',  desc: 'Generate complex border-radius shapes',     icon: '⬜', core: false },
    { id: 'lorem',       name: 'Lorem Generator',     desc: 'Generate lorem ipsum placeholder text',     icon: '📄', core: false },
    { id: 'regex',       name: 'Regex Tester',        desc: 'Test and debug regular expressions',        icon: '🔍', core: false },
    { id: 'base64',      name: 'Base64 Encoder',      desc: 'Encode/decode Base64 and URL',              icon: '🔐', core: false },
    { id: 'px-to-rem',   name: 'PX ↔ REM Converter',  desc: 'Convert between px and rem units',          icon: '📐', core: false },
    { id: 'a11y',        name: 'Accessibility Check', desc: 'Check common accessibility issues in HTML', icon: '♿', core: false },
  ],
  _enabled: new Set(['html-fmt','css-fmt','js-fmt','json-fmt','md-preview','color-pick','lorem','regex','base64','minifier','gradient','shadow','border-rad','px-to-rem','a11y']),

  render() {
    const container = document.getElementById('fs-ext-list');
    const query = document.getElementById('fs-ext-search')?.value.toLowerCase() || '';
    const filtered = this._extensions.filter(e => !query || e.name.toLowerCase().includes(query) || e.desc.toLowerCase().includes(query));
    container.innerHTML = `
      <div class="fs-ext-section-title">All Extensions</div>
      ${filtered.map(ext => `
        <div class="fs-ext-item" data-id="${ext.id}">
          <div class="fs-ext-icon">${ext.icon}</div>
          <div class="fs-ext-info">
            <div class="fs-ext-name">${FS.ui.esc(ext.name)}</div>
            <div class="fs-ext-desc">${FS.ui.esc(ext.desc)}</div>
          </div>
          <button class="fs-ext-toggle${this._enabled.has(ext.id) ? ' on' : ''}" data-id="${ext.id}" title="Toggle ${ext.core ? '(core, cannot disable)' : ''}" ${ext.core ? 'disabled' : ''}></button>
        </div>`).join('')}`;
    container.querySelectorAll('.fs-ext-item').forEach(el => {
      el.addEventListener('click', e => {
        if (!e.target.closest('.fs-ext-toggle')) {
          this.openExtension(el.dataset.id);
        }
      });
    });
    container.querySelectorAll('.fs-ext-toggle:not([disabled])').forEach(btn => {
      btn.addEventListener('click', e => {
        e.stopPropagation();
        const id = btn.dataset.id;
        if (this._enabled.has(id)) this._enabled.delete(id);
        else this._enabled.add(id);
        btn.classList.toggle('on', this._enabled.has(id));
      });
    });
  },

  openExtension(id) {
    switch(id) {
      case 'html-fmt': case 'css-fmt': case 'js-fmt': this._openFormatter(id); break;
      case 'json-fmt': this._openJsonFormatter(); break;
      case 'minifier': this._openMinifier(); break;
      case 'color-pick': this._openColorPicker(); break;
      case 'gradient': this._openGradientGen(); break;
      case 'shadow': this._openShadowGen(); break;
      case 'border-rad': this._openBorderRadiusGen(); break;
      case 'lorem': this._openLorem(); break;
      case 'regex': this._openRegex(); break;
      case 'base64': this._openBase64(); break;
      case 'px-to-rem': this._openPxRem(); break;
      case 'a11y': this._openA11y(); break;
      case 'md-preview': FS.preview.show(); break;
    }
  },

  _openFormatter(id) {
    const langMap = { 'html-fmt':'html','css-fmt':'css','js-fmt':'js' };
    const lang = langMap[id];
    const content = FS.editor.getContent();
    if (!content) { FS.ui.toast('No content to format', 'info'); return; }
    try {
      let formatted = this._basicFormat(content, lang);
      FS.ui.modal(`Format ${lang.toUpperCase()}`,
        `<div class="fs-ext-run-result" style="max-height:200px">${FS.ui.esc(formatted.slice(0,2000))}</div>`,
        `<button class="fs-sm-btn ghost" onclick="FS.ui.closeModal()">Cancel</button><button class="fs-sm-btn" id="apply-fmt">Apply to Editor</button>`
      );
      document.getElementById('apply-fmt')?.addEventListener('click', () => {
        FS.editor._instance.setValue(formatted);
        FS.ui.closeModal();
        FS.ui.toast('Formatted', 'success');
      });
    } catch(e) {
      FS.ui.toast('Formatting error: ' + e.message, 'error');
    }
  },

  _openJsonFormatter() {
    const content = FS.editor.getContent();
    try {
      const parsed = JSON.parse(content);
      const formatted = JSON.stringify(parsed, null, 2);
      FS.ui.modal('JSON Formatter',
        `<div class="fs-ext-run-result" style="max-height:200px">${FS.ui.esc(formatted.slice(0,2000))}</div>`,
        `<button class="fs-sm-btn ghost" onclick="FS.ui.closeModal()">Cancel</button><button class="fs-sm-btn" id="apply-json">Apply to Editor</button>`
      );
      document.getElementById('apply-json')?.addEventListener('click', () => {
        FS.editor._instance.setValue(formatted);
        FS.ui.closeModal();
        FS.ui.toast('JSON formatted', 'success');
      });
    } catch(e) {
      FS.ui.modal('JSON Formatter', `<div class="fs-ext-run-result" style="color:var(--fs-danger)">Invalid JSON: ${FS.ui.esc(e.message)}</div>`,
        `<button class="fs-sm-btn" onclick="FS.ui.closeModal()">Close</button>`);
    }
  },

  _openMinifier() {
    const content = FS.editor.getContent();
    const tab = FS.tabs._tabs[FS.tabs._activeIdx];
    const lang = tab ? getLang(tab.name) : 'plaintext';
    const minified = this._minify(content, lang);
    FS.ui.modal('Minifier',
      `<p class="fs-modal-text" style="font-size:11px">Original: ${content.length} chars → Minified: ${minified.length} chars (${Math.round((1-minified.length/content.length)*100)}% smaller)</p>
       <div class="fs-ext-run-result" style="max-height:150px">${FS.ui.esc(minified.slice(0,1000))}</div>`,
      `<button class="fs-sm-btn ghost" onclick="FS.ui.closeModal()">Cancel</button>
       <button class="fs-sm-btn" id="apply-min">Apply to Editor</button>
       <button class="fs-sm-btn ghost" onclick="navigator.clipboard.writeText('${minified.replace(/'/g,"\\'")}');FS.ui.toast('Copied!','success')">Copy</button>`
    );
    document.getElementById('apply-min')?.addEventListener('click', () => {
      FS.editor._instance.setValue(minified);
      FS.ui.closeModal();
    });
  },

  _openColorPicker() {
    FS.ui.modal('Color Picker', `
      <div style="text-align:center;padding:8px">
        <input type="color" id="cp-input" value="#007acc" style="width:80px;height:60px;border:none;cursor:pointer;background:none">
        <div style="margin-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <div>
            <div class="fs-modal-label">HEX</div>
            <div id="cp-hex" style="font-family:monospace;font-size:13px;padding:5px;background:var(--fs-surface2);border-radius:4px">#007acc</div>
          </div>
          <div>
            <div class="fs-modal-label">RGB</div>
            <div id="cp-rgb" style="font-family:monospace;font-size:13px;padding:5px;background:var(--fs-surface2);border-radius:4px">0,122,204</div>
          </div>
          <div>
            <div class="fs-modal-label">HSL</div>
            <div id="cp-hsl" style="font-family:monospace;font-size:13px;padding:5px;background:var(--fs-surface2);border-radius:4px"></div>
          </div>
        </div>
      </div>`,
      `<button class="fs-sm-btn ghost" onclick="FS.ui.closeModal()">Close</button><button class="fs-sm-btn" id="cp-insert">Insert HEX</button>`
    );
    const updateColor = (hex) => {
      document.getElementById('cp-hex').textContent = hex;
      const r=parseInt(hex.slice(1,3),16), g=parseInt(hex.slice(3,5),16), b=parseInt(hex.slice(5,7),16);
      document.getElementById('cp-rgb').textContent = `rgb(${r},${g},${b})`;
      const rr=r/255,gg=g/255,bb=b/255,mx=Math.max(rr,gg,bb),mn=Math.min(rr,gg,bb);
      const l=(mx+mn)/2;
      let h=0,s=0;
      if(mx!==mn){s=l>.5?(mx-mn)/(2-mx-mn):(mx-mn)/(mx+mn);
        if(mx===rr)h=(gg-bb)/(mx-mn)+(gg<bb?6:0);
        else if(mx===gg)h=(bb-rr)/(mx-mn)+2;
        else h=(rr-gg)/(mx-mn)+4;h/=6;}
      document.getElementById('cp-hsl').textContent = `hsl(${Math.round(h*360)},${Math.round(s*100)}%,${Math.round(l*100)}%)`;
    };
    document.getElementById('cp-input')?.addEventListener('input', e => updateColor(e.target.value));
    updateColor('#007acc');
    document.getElementById('cp-insert')?.addEventListener('click', () => {
      const hex = document.getElementById('cp-hex').textContent;
      FS.editor.insertAtCursor(hex);
      FS.ui.closeModal();
    });
  },

  _openLorem() {
    FS.ui.modal('Lorem Ipsum Generator',
      `<div class="fs-lor-options">
        <span class="fs-lor-label">Paragraphs:</span>
        <input type="number" class="fs-lor-input" id="lor-count" value="3" min="1" max="20">
        <span class="fs-lor-label">Mode:</span>
        <select style="padding:4px 6px;font-size:12px;background:var(--fs-input-bg);border:1px solid var(--fs-input-border);border-radius:4px;color:var(--fs-text)" id="lor-mode">
          <option value="p">Paragraphs</option>
          <option value="w">Words</option>
        </select>
      </div>
      <div class="fs-ext-run-result" id="lor-output" style="max-height:200px"></div>`,
      `<button class="fs-sm-btn ghost" onclick="FS.ui.closeModal()">Close</button>
       <button class="fs-sm-btn ghost" id="lor-regen">Regenerate</button>
       <button class="fs-sm-btn" id="lor-insert">Insert</button>`
    );
    const words = 'Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua Ut enim ad minim veniam quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur Excepteur sint occaecat cupidatat non proident sunt in culpa qui officia deserunt mollit anim id est laborum'.split(' ');
    const gen = () => {
      const cnt = parseInt(document.getElementById('lor-count')?.value) || 3;
      const mode = document.getElementById('lor-mode')?.value || 'p';
      let text = '';
      if (mode === 'p') {
        for (let i=0;i<cnt;i++) {
          const len = 30+Math.floor(Math.random()*40);
          let p = [];
          for(let j=0;j<len;j++) p.push(words[Math.floor(Math.random()*words.length)]);
          text += p.join(' ')+'\n\n';
        }
      } else {
        let ws=[];
        for(let i=0;i<cnt;i++) ws.push(words[Math.floor(Math.random()*words.length)]);
        text = ws.join(' ');
      }
      document.getElementById('lor-output').textContent = text.trim();
    };
    gen();
    document.getElementById('lor-regen')?.addEventListener('click', gen);
    document.getElementById('lor-insert')?.addEventListener('click', () => {
      const text = document.getElementById('lor-output')?.textContent || '';
      FS.editor.insertAtCursor(text);
      FS.ui.closeModal();
    });
  },

  _openRegex() {
    FS.ui.modal('Regex Tester', `
      <div class="fs-regex-row">
        <span style="font-size:12px;color:var(--fs-text-dim)">/</span>
        <input type="text" class="fs-regex-input" id="rx-pattern" placeholder="Pattern..." value="\\b\\w+\\b">
        <span style="font-size:12px;color:var(--fs-text-dim)">/</span>
        <input type="text" class="fs-regex-flags" id="rx-flags" value="g" placeholder="flags">
      </div>
      <textarea id="rx-input" style="width:100%;height:80px;background:var(--fs-input-bg);border:1px solid var(--fs-input-border);border-radius:4px;color:var(--fs-text);padding:6px;font-size:12px;resize:vertical" placeholder="Test string...">Hello World! This is a test.</textarea>
      <div class="fs-regex-result" id="rx-result"></div>`,
      `<button class="fs-sm-btn" onclick="FS.ui.closeModal()">Close</button>`
    );
    const test = () => {
      const pattern = document.getElementById('rx-pattern')?.value;
      const flags   = document.getElementById('rx-flags')?.value;
      const input   = document.getElementById('rx-input')?.value;
      const result  = document.getElementById('rx-result');
      try {
        const rx = new RegExp(pattern, flags);
        const matches = [...input.matchAll(rx.global ? rx : new RegExp(pattern, flags+'g'))];
        if (!matches.length) { result.innerHTML = '<span style="color:var(--fs-text-dim)">No matches</span>'; return; }
        let html = input;
        let offset = 0;
        matches.forEach(m => {
          const s = m.index + offset;
          const e = s + m[0].length;
          const rep = `<mark class="fs-regex-match">${FS.ui.esc(m[0])}</mark>`;
          html = html.slice(0, s) + rep + html.slice(e);
          offset += rep.length - m[0].length;
        });
        result.innerHTML = `<div style="margin-bottom:6px;font-size:11px;color:var(--fs-text-dim)">${matches.length} match${matches.length!==1?'es':''}</div>` + html;
      } catch(e) {
        result.innerHTML = `<span class="fs-regex-error">Error: ${FS.ui.esc(e.message)}</span>`;
      }
    };
    ['rx-pattern','rx-flags','rx-input'].forEach(id => document.getElementById(id)?.addEventListener('input', test));
    test();
  },

  _openBase64() {
    FS.ui.modal('Base64 Encoder', `
      <label class="fs-modal-label">Input</label>
      <textarea id="b64-input" style="width:100%;height:80px;background:var(--fs-input-bg);border:1px solid var(--fs-input-border);border-radius:4px;color:var(--fs-text);padding:6px;font-size:12px;resize:vertical" placeholder="Text to encode/decode..."></textarea>
      <div style="display:flex;gap:8px;margin:8px 0">
        <button class="fs-sm-btn" id="b64-encode">Encode Base64</button>
        <button class="fs-sm-btn ghost" id="b64-decode">Decode Base64</button>
        <button class="fs-sm-btn ghost" id="b64-url-encode">URL Encode</button>
        <button class="fs-sm-btn ghost" id="b64-url-decode">URL Decode</button>
      </div>
      <label class="fs-modal-label">Output</label>
      <div class="fs-ext-run-result" id="b64-output" style="min-height:60px;max-height:120px"></div>`,
      `<button class="fs-sm-btn ghost" onclick="FS.ui.closeModal()">Close</button><button class="fs-sm-btn" id="b64-insert">Insert Output</button>`
    );
    const go = fn => {
      try {
        const val = document.getElementById('b64-input')?.value || '';
        document.getElementById('b64-output').textContent = fn(val);
      } catch(e) { document.getElementById('b64-output').textContent = 'Error: '+e.message; }
    };
    document.getElementById('b64-encode')?.addEventListener('click', () => go(v => btoa(unescape(encodeURIComponent(v)))));
    document.getElementById('b64-decode')?.addEventListener('click', () => go(v => decodeURIComponent(escape(atob(v)))));
    document.getElementById('b64-url-encode')?.addEventListener('click', () => go(encodeURIComponent));
    document.getElementById('b64-url-decode')?.addEventListener('click', () => go(decodeURIComponent));
    document.getElementById('b64-insert')?.addEventListener('click', () => {
      const out = document.getElementById('b64-output')?.textContent || '';
      FS.editor.insertAtCursor(out);
      FS.ui.closeModal();
    });
  },

  _openGradientGen() {
    FS.ui.modal('Gradient Generator', `
      <div class="fs-gen-layout">
        <div class="fs-gen-preview" id="grad-preview" style="height:100px;border-radius:8px;margin-bottom:12px;background:linear-gradient(90deg,#007acc,#a855f7)"></div>
        <div class="fs-gen-row">
          <label class="fs-gen-label">Type</label>
          <select class="fs-gen-select" id="grad-type">
            <option value="linear">Linear</option>
            <option value="radial">Radial</option>
            <option value="conic">Conic</option>
          </select>
        </div>
        <div class="fs-gen-row" id="grad-angle-row">
          <label class="fs-gen-label">Angle</label>
          <input type="range" id="grad-angle" min="0" max="360" value="90" style="flex:1">
          <span id="grad-angle-val" style="font-size:11px;width:36px;text-align:right;color:var(--fs-text-dim)">90°</span>
        </div>
        <div class="fs-gen-row">
          <label class="fs-gen-label">Color 1</label>
          <input type="color" id="grad-c1" value="#007acc" style="width:40px;height:28px;border:none;background:none;cursor:pointer">
          <input type="range" id="grad-p1" min="0" max="100" value="0" style="flex:1;margin-left:8px">
          <span id="grad-p1-val" style="font-size:11px;width:32px;text-align:right;color:var(--fs-text-dim)">0%</span>
        </div>
        <div class="fs-gen-row">
          <label class="fs-gen-label">Color 2</label>
          <input type="color" id="grad-c2" value="#a855f7" style="width:40px;height:28px;border:none;background:none;cursor:pointer">
          <input type="range" id="grad-p2" min="0" max="100" value="100" style="flex:1;margin-left:8px">
          <span id="grad-p2-val" style="font-size:11px;width:32px;text-align:right;color:var(--fs-text-dim)">100%</span>
        </div>
        <div class="fs-ext-run-result" id="grad-output" style="margin-top:8px;cursor:pointer" title="Click to copy"></div>
      </div>`,
      `<button class="fs-sm-btn ghost" onclick="FS.ui.closeModal()">Close</button>
       <button class="fs-sm-btn" id="grad-insert">Insert CSS</button>`
    );
    const update = () => {
      const type = document.getElementById('grad-type')?.value || 'linear';
      const angle = document.getElementById('grad-angle')?.value || 90;
      const c1 = document.getElementById('grad-c1')?.value || '#007acc';
      const p1 = document.getElementById('grad-p1')?.value || 0;
      const c2 = document.getElementById('grad-c2')?.value || '#a855f7';
      const p2 = document.getElementById('grad-p2')?.value || 100;
      document.getElementById('grad-angle-val').textContent = angle + '°';
      document.getElementById('grad-p1-val').textContent = p1 + '%';
      document.getElementById('grad-p2-val').textContent = p2 + '%';
      document.getElementById('grad-angle-row').style.display = type === 'linear' ? 'flex' : 'none';
      let css = '';
      if (type === 'linear') css = `linear-gradient(${angle}deg, ${c1} ${p1}%, ${c2} ${p2}%)`;
      else if (type === 'radial') css = `radial-gradient(circle, ${c1} ${p1}%, ${c2} ${p2}%)`;
      else css = `conic-gradient(from 0deg, ${c1} ${p1}%, ${c2} ${p2}%)`;
      document.getElementById('grad-preview').style.background = css;
      const full = `background: ${css};`;
      document.getElementById('grad-output').textContent = full;
    };
    ['grad-type','grad-angle','grad-c1','grad-p1','grad-c2','grad-p2'].forEach(id => document.getElementById(id)?.addEventListener('input', update));
    document.getElementById('grad-output')?.addEventListener('click', () => {
      navigator.clipboard.writeText(document.getElementById('grad-output').textContent);
      FS.ui.toast('Copied!', 'success');
    });
    document.getElementById('grad-insert')?.addEventListener('click', () => {
      FS.editor.insertAtCursor(document.getElementById('grad-output').textContent);
      FS.ui.closeModal();
    });
    update();
  },

  _openShadowGen() {
    FS.ui.modal('Box Shadow Generator', `
      <div class="fs-gen-preview" id="shad-preview" style="height:90px;border-radius:8px;margin-bottom:12px;display:flex;align-items:center;justify-content:center;background:var(--fs-surface2)">
        <div id="shad-box" style="width:60px;height:60px;background:var(--fs-accent);border-radius:8px;transition:box-shadow .15s"></div>
      </div>
      <div class="fs-gen-row"><label class="fs-gen-label">H-Offset</label><input type="range" id="sh-x" min="-50" max="50" value="4" style="flex:1"><span id="sh-x-v" class="fs-gen-val">4px</span></div>
      <div class="fs-gen-row"><label class="fs-gen-label">V-Offset</label><input type="range" id="sh-y" min="-50" max="50" value="4" style="flex:1"><span id="sh-y-v" class="fs-gen-val">4px</span></div>
      <div class="fs-gen-row"><label class="fs-gen-label">Blur</label><input type="range" id="sh-blur" min="0" max="80" value="12" style="flex:1"><span id="sh-blur-v" class="fs-gen-val">12px</span></div>
      <div class="fs-gen-row"><label class="fs-gen-label">Spread</label><input type="range" id="sh-spread" min="-20" max="40" value="0" style="flex:1"><span id="sh-spread-v" class="fs-gen-val">0px</span></div>
      <div class="fs-gen-row"><label class="fs-gen-label">Opacity</label><input type="range" id="sh-alpha" min="0" max="100" value="30" style="flex:1"><span id="sh-alpha-v" class="fs-gen-val">30%</span></div>
      <div class="fs-gen-row"><label class="fs-gen-label">Color</label><input type="color" id="sh-color" value="#000000" style="width:40px;height:28px;border:none;background:none;cursor:pointer"><label class="fs-gen-label" style="margin-left:12px">Inset</label><input type="checkbox" id="sh-inset" style="margin-left:4px"></div>
      <div class="fs-ext-run-result" id="shad-output" style="margin-top:8px;cursor:pointer" title="Click to copy"></div>`,
      `<button class="fs-sm-btn ghost" onclick="FS.ui.closeModal()">Close</button>
       <button class="fs-sm-btn" id="shad-insert">Insert CSS</button>`
    );
    const update = () => {
      const x = document.getElementById('sh-x')?.value || 4;
      const y = document.getElementById('sh-y')?.value || 4;
      const blur = document.getElementById('sh-blur')?.value || 12;
      const spread = document.getElementById('sh-spread')?.value || 0;
      const alpha = (document.getElementById('sh-alpha')?.value || 30) / 100;
      const color = document.getElementById('sh-color')?.value || '#000000';
      const inset = document.getElementById('sh-inset')?.checked;
      document.getElementById('sh-x-v').textContent = x + 'px';
      document.getElementById('sh-y-v').textContent = y + 'px';
      document.getElementById('sh-blur-v').textContent = blur + 'px';
      document.getElementById('sh-spread-v').textContent = spread + 'px';
      document.getElementById('sh-alpha-v').textContent = Math.round(alpha * 100) + '%';
      const r = parseInt(color.slice(1,3),16), g = parseInt(color.slice(3,5),16), b = parseInt(color.slice(5,7),16);
      const rgba = `rgba(${r},${g},${b},${alpha})`;
      const shadow = `${inset ? 'inset ' : ''}${x}px ${y}px ${blur}px ${spread}px ${rgba}`;
      document.getElementById('shad-box').style.boxShadow = shadow;
      const css = `box-shadow: ${shadow};`;
      document.getElementById('shad-output').textContent = css;
    };
    ['sh-x','sh-y','sh-blur','sh-spread','sh-alpha','sh-color','sh-inset'].forEach(id => document.getElementById(id)?.addEventListener('input', update));
    document.getElementById('shad-output')?.addEventListener('click', () => {
      navigator.clipboard.writeText(document.getElementById('shad-output').textContent);
      FS.ui.toast('Copied!', 'success');
    });
    document.getElementById('shad-insert')?.addEventListener('click', () => {
      FS.editor.insertAtCursor(document.getElementById('shad-output').textContent);
      FS.ui.closeModal();
    });
    update();
  },

  _openBorderRadiusGen() {
    FS.ui.modal('Border Radius Generator', `
      <div style="display:flex;gap:16px;align-items:flex-start">
        <div id="br-preview" style="width:100px;height:100px;background:var(--fs-accent);flex-shrink:0;transition:border-radius .15s"></div>
        <div style="flex:1">
          <div class="fs-gen-row"><label class="fs-gen-label">Top-Left</label><input type="range" id="br-tl" min="0" max="100" value="8" style="flex:1"><span id="br-tl-v" class="fs-gen-val">8px</span></div>
          <div class="fs-gen-row"><label class="fs-gen-label">Top-Right</label><input type="range" id="br-tr" min="0" max="100" value="8" style="flex:1"><span id="br-tr-v" class="fs-gen-val">8px</span></div>
          <div class="fs-gen-row"><label class="fs-gen-label">Bottom-Right</label><input type="range" id="br-br" min="0" max="100" value="8" style="flex:1"><span id="br-br-v" class="fs-gen-val">8px</span></div>
          <div class="fs-gen-row"><label class="fs-gen-label">Bottom-Left</label><input type="range" id="br-bl" min="0" max="100" value="8" style="flex:1"><span id="br-bl-v" class="fs-gen-val">8px</span></div>
          <div class="fs-gen-row" style="margin-top:4px"><label class="fs-gen-label">Unit</label>
            <select class="fs-gen-select" id="br-unit"><option>px</option><option>%</option><option>em</option></select>
            <button class="fs-sm-btn ghost" id="br-all" style="margin-left:8px;font-size:11px">Apply All</button>
          </div>
        </div>
      </div>
      <div class="fs-ext-run-result" id="br-output" style="margin-top:10px;cursor:pointer" title="Click to copy"></div>`,
      `<button class="fs-sm-btn ghost" onclick="FS.ui.closeModal()">Close</button>
       <button class="fs-sm-btn" id="br-insert">Insert CSS</button>`
    );
    const update = () => {
      const unit = document.getElementById('br-unit')?.value || 'px';
      const vals = ['tl','tr','br','bl'].map(id => {
        const v = document.getElementById(`br-${id}`)?.value || 0;
        document.getElementById(`br-${id}-v`).textContent = v + unit;
        return v + unit;
      });
      const css = `border-radius: ${vals.join(' ')};`;
      document.getElementById('br-preview').style.borderRadius = vals.join(' ');
      document.getElementById('br-output').textContent = css;
    };
    ['br-tl','br-tr','br-br','br-bl','br-unit'].forEach(id => document.getElementById(id)?.addEventListener('input', update));
    document.getElementById('br-all')?.addEventListener('click', () => {
      const v = document.getElementById('br-tl')?.value || 8;
      ['br-tr','br-br','br-bl'].forEach(id => { const el = document.getElementById(id); if (el) el.value = v; });
      update();
    });
    document.getElementById('br-output')?.addEventListener('click', () => {
      navigator.clipboard.writeText(document.getElementById('br-output').textContent);
      FS.ui.toast('Copied!', 'success');
    });
    document.getElementById('br-insert')?.addEventListener('click', () => {
      FS.editor.insertAtCursor(document.getElementById('br-output').textContent);
      FS.ui.closeModal();
    });
    update();
  },

  _openPxRem() {
    FS.ui.modal('PX ↔ REM Converter', `
      <div class="fs-gen-row" style="margin-bottom:12px">
        <label class="fs-gen-label">Base font size (px)</label>
        <input type="number" id="pr-base" value="16" min="1" max="32" style="width:60px;background:var(--fs-input-bg);border:1px solid var(--fs-input-border);border-radius:4px;color:var(--fs-text);padding:4px 6px;font-size:12px">
      </div>
      <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:12px;align-items:center">
        <div>
          <label class="fs-modal-label">PX</label>
          <input type="number" id="pr-px" value="16" class="fs-modal-input" placeholder="Enter px...">
        </div>
        <div style="text-align:center;color:var(--fs-text-dim);font-size:18px;padding-top:18px">⇄</div>
        <div>
          <label class="fs-modal-label">REM</label>
          <input type="number" id="pr-rem" value="1" step="0.001" class="fs-modal-input" placeholder="Enter rem...">
        </div>
      </div>
      <div id="pr-common" style="margin-top:12px">
        <div class="fs-modal-label">Common values</div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px" id="pr-chips"></div>
      </div>`,
      `<button class="fs-sm-btn ghost" onclick="FS.ui.closeModal()">Close</button>
       <button class="fs-sm-btn" id="pr-insert-rem">Insert REM</button>
       <button class="fs-sm-btn ghost" id="pr-insert-px">Insert PX</button>`
    );
    const getBase = () => parseFloat(document.getElementById('pr-base')?.value) || 16;
    document.getElementById('pr-px')?.addEventListener('input', e => {
      const rem = (parseFloat(e.target.value) || 0) / getBase();
      document.getElementById('pr-rem').value = Math.round(rem * 10000) / 10000;
    });
    document.getElementById('pr-rem')?.addEventListener('input', e => {
      const px = (parseFloat(e.target.value) || 0) * getBase();
      document.getElementById('pr-px').value = Math.round(px * 100) / 100;
    });
    const chips = document.getElementById('pr-chips');
    [8,10,12,14,16,18,20,24,32,48,64].forEach(px => {
      const chip = document.createElement('button');
      chip.className = 'fs-sm-btn ghost';
      chip.style.cssText = 'font-size:11px;padding:3px 8px';
      chip.textContent = px + 'px';
      chip.addEventListener('click', () => {
        document.getElementById('pr-px').value = px;
        document.getElementById('pr-rem').value = Math.round((px / getBase()) * 10000) / 10000;
      });
      chips.appendChild(chip);
    });
    document.getElementById('pr-insert-rem')?.addEventListener('click', () => {
      const val = document.getElementById('pr-rem')?.value || '1';
      FS.editor.insertAtCursor(val + 'rem');
      FS.ui.closeModal();
    });
    document.getElementById('pr-insert-px')?.addEventListener('click', () => {
      const val = document.getElementById('pr-px')?.value || '16';
      FS.editor.insertAtCursor(val + 'px');
      FS.ui.closeModal();
    });
  },

  _openA11y() {
    const content = FS.editor.getContent();
    const tab = FS.tabs._tabs[FS.tabs._activeIdx];
    if (!tab || getLang(tab.name) !== 'html') {
      FS.ui.toast('Open an HTML file first', 'info');
      return;
    }
    const issues = [];
    // Parse simple issues using regex patterns
    const imgNoAlt = content.match(/<img(?![^>]*\balt\s*=)[^>]*>/gi) || [];
    imgNoAlt.forEach(() => issues.push({ level: 'error', msg: '<img> missing alt attribute — required for screen readers' }));

    const inputNoLabel = content.match(/<input(?![^>]*\baria-label|\btype="hidden")[^>]*>/gi) || [];
    const labelCount = (content.match(/<label[^>]*>/gi) || []).length;
    if (inputNoLabel.length > labelCount) {
      issues.push({ level: 'warn', msg: `${inputNoLabel.length - labelCount} <input> element(s) may lack an associated <label>` });
    }

    if (!content.includes('<html') || (!content.includes('lang=') && !content.includes("lang ="))) {
      issues.push({ level: 'warn', msg: '<html> element missing lang attribute (e.g. lang="en")' });
    }
    if (!content.includes('<title') || content.match(/<title>\s*<\/title>/)) {
      issues.push({ level: 'error', msg: 'Missing or empty <title> element' });
    }
    if (!content.includes('<meta name="viewport"') && !content.includes("<meta name='viewport'")) {
      issues.push({ level: 'warn', msg: 'Missing viewport meta tag for mobile responsiveness' });
    }
    const headings = content.match(/<h[1-6][^>]*>/gi) || [];
    if (!headings.some(h => h.startsWith('<h1'))) {
      issues.push({ level: 'warn', msg: 'No <h1> heading found — each page should have one main heading' });
    }
    const links = content.match(/<a[^>]*>[^<]*<\/a>/gi) || [];
    const genericLinks = links.filter(l => />(click here|here|read more|more|link)<\/a>/i.test(l));
    if (genericLinks.length) {
      issues.push({ level: 'warn', msg: `${genericLinks.length} link(s) use generic text ("click here", "here") — use descriptive link text` });
    }
    const btnNoText = content.match(/<button[^>]*>\s*<\/button>/gi) || [];
    if (btnNoText.length) {
      issues.push({ level: 'error', msg: `${btnNoText.length} empty <button> element(s) — add text or aria-label` });
    }
    if (!issues.length) issues.push({ level: 'pass', msg: 'No obvious issues detected — great job!' });

    const colors = { error: 'var(--fs-danger)', warn: 'var(--fs-warning)', pass: 'var(--fs-success)' };
    const icons = { error: '✕', warn: '⚠', pass: '✓' };
    const html = `<div style="font-size:12px">
      <div style="margin-bottom:10px;color:var(--fs-text-dim)">${issues.filter(i=>i.level==='error').length} errors · ${issues.filter(i=>i.level==='warn').length} warnings</div>
      ${issues.map(i => `<div style="display:flex;gap:8px;padding:6px 0;border-bottom:1px solid var(--fs-border)">
        <span style="color:${colors[i.level]};font-weight:700;flex-shrink:0">${icons[i.level]}</span>
        <span style="color:var(--fs-text)">${FS.ui.esc(i.msg)}</span>
      </div>`).join('')}
    </div>`;
    FS.ui.modal('Accessibility Check', html,
      `<button class="fs-sm-btn" onclick="FS.ui.closeModal()">Close</button>`
    );
  },

  _basicFormat(code, lang) {
    if (lang === 'json') {
      return JSON.stringify(JSON.parse(code), null, 2);
    }
    // Basic indentation improvement for HTML/CSS/JS
    let lines = code.split('\n');
    let indentLevel = 0;
    const tab = '  ';
    return lines.map(line => {
      line = line.trim();
      if (!line) return '';
      if (line.match(/^<\//)) indentLevel = Math.max(0, indentLevel-1);
      else if (line.match(/^}/)) indentLevel = Math.max(0, indentLevel-1);
      const result = tab.repeat(indentLevel) + line;
      if (line.match(/<[^\/][^>]*[^\/]>/) && !line.match(/<\//) && !line.match(/\/>/)) indentLevel++;
      if (line.match(/\{$/) && !line.match(/\}/)) indentLevel++;
      return result;
    }).join('\n');
  },

  _minify(code, lang) {
    if (lang === 'json') {
      try { return JSON.stringify(JSON.parse(code)); } catch { return code; }
    }
    return code
      .replace(/\/\*[\s\S]*?\*\//g, '')
      .replace(/\/\/[^\n]*/g, '')
      .replace(/\n+/g, ' ')
      .replace(/\s{2,}/g, ' ')
      .replace(/\s*([{};:,>+~])\s*/g, '$1')
      .trim();
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   AI
══════════════════════════════════════════════════════════════════════════ */
FS.ai = {
  _messages: [],

  getProviderConfig() {
    return {
      provider: FS.settings.get('aiProvider'),
      key:      FS.settings.get('aiKey'),
      endpoint: FS.settings.get('aiEndpoint'),
      model:    FS.settings.get('aiModel'),
      org:      FS.settings.get('aiOrg'),
      temp:     FS.settings.get('aiTemp'),
      topP:     FS.settings.get('aiTopP'),
      maxTokens:FS.settings.get('aiMaxTokens'),
      system:   FS.settings.get('aiSystemPrompt'),
    };
  },

  PROVIDERS: {
    openai:    { label: 'OpenAI',       endpoint: 'https://api.openai.com/v1/chat/completions', models: ['gpt-4o','gpt-4o-mini','gpt-4-turbo','gpt-3.5-turbo'] },
    anthropic: { label: 'Anthropic',    endpoint: 'https://api.anthropic.com/v1/messages',       models: ['claude-3-5-sonnet-20241022','claude-3-haiku-20240307'] },
    gemini:    { label: 'Google Gemini',endpoint: 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent', models: ['gemini-1.5-pro','gemini-1.5-flash','gemini-1.0-pro'] },
    openrouter:{ label: 'OpenRouter',   endpoint: 'https://openrouter.ai/api/v1/chat/completions', models: ['openai/gpt-4o','anthropic/claude-3.5-sonnet','google/gemini-pro'] },
    deepseek:  { label: 'DeepSeek',     endpoint: 'https://api.deepseek.com/chat/completions',    models: ['deepseek-chat','deepseek-coder'] },
    groq:      { label: 'Groq',         endpoint: 'https://api.groq.com/openai/v1/chat/completions', models: ['llama-3.1-70b-versatile','mixtral-8x7b-32768'] },
    mistral:   { label: 'Mistral',      endpoint: 'https://api.mistral.ai/v1/chat/completions',   models: ['mistral-large-latest','mistral-medium','mistral-small'] },
    custom:    { label: 'Custom',        endpoint: '', models: [] },
  },

  openConfig() {
    const cfg = this.getProviderConfig();
    const providerOpts = Object.entries(this.PROVIDERS).map(([k,v]) =>
      `<option value="${k}" ${cfg.provider===k?'selected':''}>${FS.ui.esc(v.label)}</option>`
    ).join('');
    document.getElementById('fs-ai-config-body').innerHTML = `
      <div class="fs-config-row">
        <label class="fs-config-label">Provider</label>
        <select class="fs-config-select" id="ai-cfg-provider">${providerOpts}</select>
      </div>
      <div class="fs-config-row">
        <label class="fs-config-label">API Key</label>
        <input class="fs-config-input" id="ai-cfg-key" type="password" value="${FS.ui.esc(cfg.key)}" placeholder="sk-...">
      </div>
      <div class="fs-config-row" id="ai-cfg-endpoint-row">
        <label class="fs-config-label">Endpoint (leave blank for default)</label>
        <input class="fs-config-input" id="ai-cfg-endpoint" value="${FS.ui.esc(cfg.endpoint)}" placeholder="https://...">
      </div>
      <div class="fs-config-row">
        <label class="fs-config-label">Model</label>
        <input class="fs-config-input" id="ai-cfg-model" value="${FS.ui.esc(cfg.model)}" placeholder="Model name or ID">
      </div>
      <div class="fs-config-row">
        <label class="fs-config-label">Temperature (0–2)</label>
        <input class="fs-config-input" id="ai-cfg-temp" type="number" value="${cfg.temp}" min="0" max="2" step="0.1">
      </div>
      <div class="fs-config-row">
        <label class="fs-config-label">Max Tokens</label>
        <input class="fs-config-input" id="ai-cfg-maxtok" type="number" value="${cfg.maxTokens}" min="100" max="128000" step="100">
      </div>
      <div class="fs-config-row">
        <label class="fs-config-label">System Prompt</label>
        <textarea class="fs-config-input" id="ai-cfg-system" rows="3" style="resize:vertical">${FS.ui.esc(cfg.system)}</textarea>
      </div>
      <div class="fs-config-footer">
        <button class="fs-sm-btn ghost" id="ai-cfg-cancel">Cancel</button>
        <button class="fs-sm-btn" id="ai-cfg-save">Save</button>
      </div>`;
    document.getElementById('fs-ai-config-overlay').style.display = 'flex';
    document.getElementById('ai-cfg-save')?.addEventListener('click', () => {
      FS.settings.set('aiProvider', document.getElementById('ai-cfg-provider')?.value);
      FS.settings.set('aiKey', document.getElementById('ai-cfg-key')?.value);
      FS.settings.set('aiEndpoint', document.getElementById('ai-cfg-endpoint')?.value);
      FS.settings.set('aiModel', document.getElementById('ai-cfg-model')?.value);
      FS.settings.set('aiTemp', parseFloat(document.getElementById('ai-cfg-temp')?.value) || 0.7);
      FS.settings.set('aiMaxTokens', parseInt(document.getElementById('ai-cfg-maxtok')?.value) || 2048);
      FS.settings.set('aiSystemPrompt', document.getElementById('ai-cfg-system')?.value);
      document.getElementById('fs-ai-config-overlay').style.display = 'none';
      this.updateProviderBar();
      FS.ui.toast('AI settings saved', 'success');
    });
    ['ai-cfg-cancel','fs-ai-config-close'].forEach(id =>
      document.getElementById(id)?.addEventListener('click', () => document.getElementById('fs-ai-config-overlay').style.display = 'none')
    );
  },

  updateProviderBar() {
    const provider = FS.settings.get('aiProvider');
    const model    = FS.settings.get('aiModel');
    const label    = document.getElementById('fs-ai-provider-label');
    const chip     = document.getElementById('fs-float-ai-chip');
    if (!provider || !FS.settings.get('aiKey')) {
      if (label) label.textContent = 'No provider configured';
      if (chip)  { chip.textContent = ''; chip.style.display = 'none'; }
      FS.status.update({ aiProvider: 'No AI' });
    } else {
      const pName = this.PROVIDERS[provider]?.label || provider;
      if (label) label.textContent = `${pName} · ${model || 'no model'}`;
      if (chip)  { chip.textContent = pName; chip.style.display = ''; }
      FS.status.update({ aiProvider: `${pName}` });
    }
  },

  getContext(mode) {
    if (mode === 'selection') return FS.editor.getSelection() || FS.editor.getContent();
    if (mode === 'file') return FS.editor.getContent();
    if (mode === 'project') {
      const proj = FS.projects.current;
      return proj ? `Project: ${proj.name}\n\nCurrent file:\n${FS.editor.getContent()}` : FS.editor.getContent();
    }
    return '';
  },

  async send(userMsg, contextMode = 'file') {
    const cfg = this.getProviderConfig();
    if (!cfg.provider || !cfg.key) {
      FS.ui.toast('Configure AI provider first', 'error');
      this.openConfig();
      return;
    }
    const context = this.getContext(contextMode);
    const messages = [
      { role: 'system', content: cfg.system },
      ...this._messages.slice(-10),
      { role: 'user', content: context ? `Context:\n\`\`\`\n${context}\n\`\`\`\n\n${userMsg}` : userMsg },
    ];
    this._addMessage('user', userMsg);
    const thinking = this._addThinking();

    try {
      const reply = await this._callApi(cfg, messages);
      thinking.remove();
      const msgEl = this._addMessage('ai', reply);
      this._messages.push({ role:'user', content:userMsg });
      this._messages.push({ role:'assistant', content:reply });
      this._addMsgActions(msgEl, reply);
    } catch(e) {
      thinking.remove();
      this._addMessage('ai', `Error: ${e.message}`);
    }
  },

  async runAction(action) {
    const prompts = {
      explain: 'Explain what this code does in simple terms.',
      fix: 'Find and fix any bugs in this code. Show the corrected version.',
      improve: 'Suggest improvements to this code for better quality, performance, and readability.',
      refactor: 'Refactor this code following best practices. Explain the changes.',
      comment: 'Add helpful comments to this code.',
      docs: 'Generate documentation for this code.',
    };
    const prompt = prompts[action] || action;
    await this.send(prompt, document.getElementById('fs-ai-context')?.value || 'file');
  },

  _addMessage(role, content) {
    const container = document.getElementById('fs-ai-messages');
    const el = document.createElement('div');
    el.className = `fs-ai-msg fs-ai-msg-${role}`;
    // Basic markdown rendering
    const rendered = content
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/```[\w]*\n?([\s\S]*?)```/g, '<pre><code>$1</code></pre>')
      .replace(/`([^`]+)`/g, '<code>$1</code>')
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/\n/g, '<br>');
    el.innerHTML = rendered;
    container.appendChild(el);
    container.scrollTop = container.scrollHeight;
    return el;
  },

  _addThinking() {
    const container = document.getElementById('fs-ai-messages');
    const el = document.createElement('div');
    el.className = 'fs-ai-thinking';
    el.innerHTML = `<div class="fs-spinner" style="width:14px;height:14px;border-width:2px"></div><span>Thinking…</span>`;
    container.appendChild(el);
    container.scrollTop = container.scrollHeight;
    return el;
  },

  _addMsgActions(el, content) {
    const acts = document.createElement('div');
    acts.className = 'fs-ai-msg-actions';
    acts.innerHTML = `<button class="fs-ai-msg-btn" data-action="copy">Copy</button>
      <button class="fs-ai-msg-btn" data-action="insert">Insert</button>
      <button class="fs-ai-msg-btn" data-action="replace">Replace Selection</button>`;
    acts.querySelector('[data-action="copy"]')?.addEventListener('click', () => {
      navigator.clipboard.writeText(content).then(() => FS.ui.toast('Copied', 'success'));
    });
    acts.querySelector('[data-action="insert"]')?.addEventListener('click', () => {
      const code = this._extractCode(content);
      FS.editor.insertAtCursor(code);
      FS.ui.toast('Inserted', 'success');
    });
    acts.querySelector('[data-action="replace"]')?.addEventListener('click', () => {
      const code = this._extractCode(content);
      FS.editor.replaceSelection(code);
      FS.ui.toast('Replaced', 'success');
    });
    el.appendChild(acts);
  },

  _extractCode(text) {
    const match = text.match(/```[\w]*\n?([\s\S]*?)```/);
    return match ? match[1].trim() : text;
  },

  async _callApi(cfg, messages) {
    const provider = cfg.provider;
    const pInfo = this.PROVIDERS[provider] || this.PROVIDERS.custom;
    let endpoint = cfg.endpoint || pInfo.endpoint;
    let body, headers;

    if (provider === 'anthropic') {
      endpoint = endpoint || 'https://api.anthropic.com/v1/messages';
      headers = { 'Content-Type':'application/json', 'x-api-key':cfg.key, 'anthropic-version':'2023-06-01' };
      body = JSON.stringify({ model: cfg.model || 'claude-3-haiku-20240307', max_tokens: cfg.maxTokens, messages: messages.filter(m=>m.role!=='system'), system: cfg.system });
    } else if (provider === 'gemini') {
      const model = cfg.model || 'gemini-1.5-flash';
      endpoint = `https://generativelanguage.googleapis.com/v1beta/models/${model}:generateContent?key=${cfg.key}`;
      headers = { 'Content-Type':'application/json' };
      const userMsgs = messages.filter(m=>m.role!=='system');
      body = JSON.stringify({ contents: userMsgs.map(m=>({ role: m.role==='assistant'?'model':'user', parts:[{text:m.content}] })) });
    } else {
      // OpenAI-compatible
      endpoint = endpoint || pInfo.endpoint;
      headers = { 'Content-Type':'application/json', 'Authorization':'Bearer '+cfg.key };
      if (cfg.org) headers['OpenAI-Organization'] = cfg.org;
      body = JSON.stringify({ model: cfg.model, messages, temperature: cfg.temp, max_tokens: cfg.maxTokens });
    }

    const res = await fetch(endpoint, { method:'POST', headers, body });
    if (!res.ok) {
      const err = await res.text();
      throw new Error(`API error ${res.status}: ${err.slice(0,200)}`);
    }
    const data = await res.json();
    if (provider === 'anthropic') return data.content?.[0]?.text || '';
    if (provider === 'gemini') return data.candidates?.[0]?.content?.parts?.[0]?.text || '';
    return data.choices?.[0]?.message?.content || '';
  },

  clearChat() {
    this._messages = [];
    document.getElementById('fs-ai-messages').innerHTML = '';
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   SEARCH
══════════════════════════════════════════════════════════════════════════ */
FS.search = {
  async run() {
    const query = document.getElementById('fs-search-query')?.value || '';
    const useCase = document.getElementById('fs-search-case')?.dataset.active === 'true';
    const useRegex = document.getElementById('fs-search-regex')?.dataset.active === 'true';
    const container = document.getElementById('fs-search-results');

    if (!query) { container.innerHTML = `<div class="fs-search-empty">Type to search…</div>`; return; }
    const proj = FS.projects.current;
    if (!proj) { container.innerHTML = `<div class="fs-search-empty">No project open</div>`; return; }

    const files = await FS.files.getAll(proj.id);
    const textFiles = files.filter(f => f.type === 'file' && f.content !== undefined);
    let html = '';
    let totalMatches = 0;

    for (const file of textFiles) {
      const content = file.content || '';
      let rx;
      try {
        rx = useRegex
          ? new RegExp(query, useCase ? 'g' : 'gi')
          : new RegExp(query.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'), useCase ? 'g' : 'gi');
      } catch { continue; }

      const lines = content.split('\n');
      const matches = [];
      lines.forEach((line, i) => {
        if (rx.test(line)) matches.push({ line: i+1, text: line.trim().slice(0,80) });
        rx.lastIndex = 0;
      });
      if (!matches.length) continue;
      totalMatches += matches.length;
      html += `<div class="fs-search-file-group">
        <div class="fs-search-file-header">
          ${getFileIcon(file.name)} ${FS.ui.esc(file.name)}
          <span style="margin-left:auto;opacity:.6">${matches.length}</span>
        </div>
        ${matches.slice(0,20).map(m => `
          <div class="fs-search-match" data-fileid="${file.id}" data-line="${m.line}">
            <span class="fs-search-line-num">${m.line}</span>${FS.ui.esc(m.text).replace(new RegExp(FS.ui.esc(query),'gi'), s => `<mark>${s}</mark>`)}
          </div>`).join('')}
      </div>`;
    }

    container.innerHTML = html || `<div class="fs-search-empty">No results for "${FS.ui.esc(query)}"</div>`;
    if (totalMatches) {
      container.insertAdjacentHTML('afterbegin', `<div style="padding:6px 12px;font-size:11px;color:var(--fs-text-dim)">${totalMatches} matches</div>`);
    }
    container.querySelectorAll('.fs-search-match').forEach(el => {
      el.addEventListener('click', async () => {
        await FS.tabs.openFile(el.dataset.fileid);
        setTimeout(() => FS.editor.goToLine(parseInt(el.dataset.line)), 300);
        FS.panels.setActive('explorer');
      });
    });
  },

  async replaceAll() {
    const query = document.getElementById('fs-search-query')?.value || '';
    const replacement = document.getElementById('fs-search-replace')?.value || '';
    if (!query) return;
    const proj = FS.projects.current;
    if (!proj) return;
    const files = await FS.files.getAll(proj.id);
    let count = 0;
    for (const file of files) {
      if (file.type !== 'file') continue;
      const content = file.content || '';
      const rx = new RegExp(query.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'), 'g');
      const newContent = content.replace(rx, replacement);
      if (newContent !== content) {
        await FS.files.updateContent(file.id, newContent);
        count += (content.match(rx) || []).length;
      }
    }
    FS.ui.toast(`Replaced ${count} occurrence(s)`, 'success');
    this.run();
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   COMMAND PALETTE
══════════════════════════════════════════════════════════════════════════ */
FS.palette = {
  _open: false,
  _mode: 'commands', // 'commands' | 'files'
  _selectedIdx: 0,
  _filtered: [],

  COMMANDS: [
    { label: 'New Project',         group: 'File',    kbd: '',                  action: () => FS.explorer.newProject() },
    { label: 'New File',            group: 'File',    kbd: 'Ctrl+N',            action: () => FS.explorer.newFile() },
    { label: 'New Folder',          group: 'File',    kbd: '',                  action: () => FS.explorer.newFolder() },
    { label: 'Save',                group: 'File',    kbd: 'Ctrl+S',            action: () => FS.editor.save() },
    { label: 'Save All',            group: 'File',    kbd: 'Ctrl+Shift+S',      action: () => FS.editor.saveAll() },
    { label: 'Open File',           group: 'File',    kbd: 'Ctrl+P',            action: () => FS.palette.openQuickFile() },
    { label: 'Close Tab',           group: 'File',    kbd: 'Ctrl+W',            action: () => FS.tabs.closeTab(FS.tabs._activeIdx) },
    { label: 'Reopen Closed Tab',   group: 'File',    kbd: 'Ctrl+Shift+T',      action: () => FS.tabs.reopenClosed() },
    { label: 'Toggle Preview',      group: 'View',    kbd: '',                  action: () => FS.preview.toggle() },
    { label: 'Toggle Sidebar',      group: 'View',    kbd: 'Ctrl+B',            action: () => FS.panels.toggleSidebar() },
    { label: 'Toggle Bottom Panel', group: 'View',    kbd: 'Ctrl+`',            action: () => FS.panels.toggleBottom() },
    { label: 'Show Explorer',       group: 'View',    kbd: 'Ctrl+Shift+E',      action: () => FS.panels.setActive('explorer') },
    { label: 'Show Search',         group: 'View',    kbd: 'Ctrl+Shift+F',      action: () => FS.panels.setActive('search') },
    { label: 'Show Extensions',     group: 'View',    kbd: 'Ctrl+Shift+X',      action: () => FS.panels.setActive('extensions') },
    { label: 'Show Snippets',       group: 'View',    kbd: 'Ctrl+Shift+K',      action: () => FS.panels.setActive('snippets') },
    { label: 'Show AI Assistant',   group: 'View',    kbd: 'Ctrl+Shift+A',      action: () => FS.panels.setActive('ai') },
    { label: 'Show Settings',       group: 'View',    kbd: '',                  action: () => FS.panels.setActive('settings') },
    { label: 'Format Document',     group: 'Edit',    kbd: 'Shift+Alt+F',       action: () => FS.editor.format() },
    { label: 'Go to Line',          group: 'Edit',    kbd: 'Ctrl+G',            action: () => FS.palette.goToLine() },
    { label: 'Refresh Preview',     group: 'Run',     kbd: 'F5',                action: () => FS.preview.refresh() },
    { label: 'Manage Asset Links',  group: 'Run',     kbd: '',                  action: () => FS.preview.manageLinks() },
    { label: 'Open in New Window',  group: 'Run',     kbd: '',                  action: () => FS.preview.openInNewWindow() },
    { label: 'Switch Theme: Dark',  group: 'Themes',  kbd: '',                  action: () => FS.settings_ui.applyTheme('dark') },
    { label: 'Switch Theme: Light', group: 'Themes',  kbd: '',                  action: () => FS.settings_ui.applyTheme('light') },
    { label: 'Switch Theme: Monokai',group:'Themes',  kbd: '',                  action: () => FS.settings_ui.applyTheme('monokai') },
    { label: 'Switch Theme: Dracula',group:'Themes',  kbd: '',                  action: () => FS.settings_ui.applyTheme('dracula') },
    { label: 'Collapse All Folders',group: 'Explorer',kbd: '',                  action: () => FS.explorer.collapseAll() },
    { label: 'Configure AI',        group: 'AI',      kbd: '',                  action: () => FS.ai.openConfig() },
    { label: 'Clear AI Chat',       group: 'AI',      kbd: '',                  action: () => FS.ai.clearChat() },
    { label: 'Save Selection as Snippet', group: 'Snippets', kbd: '',           action: () => FS.snippets.saveSelection() },
    { label: 'Insert Snippet',      group: 'Snippets', kbd: 'Ctrl+Shift+K',    action: () => FS.panels.setActive('snippets') },
    { label: 'Export Settings',     group: 'Settings',kbd: '',                  action: () => FS.settings_ui.exportSettings() },
    { label: 'Import Settings',     group: 'Settings',kbd: '',                  action: () => FS.settings_ui.importSettings() },
    { label: 'Reset Settings',      group: 'Settings',kbd: '',                  action: () => FS.settings_ui.resetSettings() },
    { label: 'Export Project as ZIP',   group: 'File',       kbd: '',             action: () => FS.explorer.exportZip() },
    { label: 'Import ZIP',              group: 'File',       kbd: '',             action: () => FS.explorer.importZip() },
    { label: 'Download Current File',   group: 'File',       kbd: '',             action: () => FS.preview.downloadCurrentFile() },
    { label: 'New Blank Project',       group: 'Templates',  kbd: '',             action: () => FS.explorer.newProjectFromTemplate('blank') },
    { label: 'New Web App Project',     group: 'Templates',  kbd: '',             action: () => FS.explorer.newProjectFromTemplate('webapp') },
    { label: 'New Landing Page',        group: 'Templates',  kbd: '',             action: () => FS.explorer.newProjectFromTemplate('landing') },
    { label: 'New Portfolio Site',      group: 'Templates',  kbd: '',             action: () => FS.explorer.newProjectFromTemplate('portfolio') },
    { label: 'Open HTML Formatter',     group: 'Extensions', kbd: '',             action: () => FS.extensions.openExtension('html-fmt') },
    { label: 'Open Minifier',           group: 'Extensions', kbd: '',             action: () => FS.extensions.openExtension('minifier') },
    { label: 'Open Color Picker',       group: 'Extensions', kbd: '',             action: () => FS.extensions.openExtension('color-pick') },
    { label: 'Open Gradient Generator', group: 'Extensions', kbd: '',             action: () => FS.extensions.openExtension('gradient') },
    { label: 'Open Shadow Generator',   group: 'Extensions', kbd: '',             action: () => FS.extensions.openExtension('shadow') },
    { label: 'Open Border Radius Tool', group: 'Extensions', kbd: '',             action: () => FS.extensions.openExtension('border-rad') },
    { label: 'Open Lorem Generator',    group: 'Extensions', kbd: '',             action: () => FS.extensions.openExtension('lorem') },
    { label: 'Open Regex Tester',       group: 'Extensions', kbd: '',             action: () => FS.extensions.openExtension('regex') },
    { label: 'Open PX ↔ REM Converter', group: 'Extensions', kbd: '',             action: () => FS.extensions.openExtension('px-to-rem') },
    { label: 'Accessibility Check',     group: 'Extensions', kbd: '',             action: () => FS.extensions.openExtension('a11y') },
  ],

  open() {
    this._mode = 'commands';
    this._selectedIdx = 0;
    document.getElementById('fs-palette-overlay').style.display = 'flex';
    const input = document.getElementById('fs-palette-input');
    input.value = '';
    input.placeholder = 'Type a command…';
    input.focus();
    this._render('');
    this._open = true;
  },

  openQuickFile() {
    this._mode = 'files';
    this._selectedIdx = 0;
    document.getElementById('fs-palette-overlay').style.display = 'flex';
    const input = document.getElementById('fs-palette-input');
    input.value = '';
    input.placeholder = 'Open a file in project…';
    input.focus();
    this._renderFiles('');
    this._open = true;
  },

  close() {
    document.getElementById('fs-palette-overlay').style.display = 'none';
    this._open = false;
    FS.editor.focus();
  },

  goToLine() {
    this._mode = 'goto';
    const overlay = document.getElementById('fs-palette-overlay');
    overlay.style.display = 'flex';
    const input = document.getElementById('fs-palette-input');
    input.value = ':';
    input.placeholder = ':line number';
    input.focus();
    document.getElementById('fs-palette-results').innerHTML = `<div class="fs-palette-item"><span style="font-size:12px;color:var(--fs-text-dim)">Type : followed by a line number (e.g. :42)</span></div>`;
    this._open = true;
  },

  _render(query) {
    const q = query.toLowerCase();
    let grouped = {};
    this.COMMANDS.filter(c => !q || c.label.toLowerCase().includes(q) || c.group.toLowerCase().includes(q))
      .forEach(c => { (grouped[c.group] = grouped[c.group] || []).push(c); });
    this._filtered = Object.values(grouped).flat();
    let html = '';
    Object.entries(grouped).forEach(([group, cmds]) => {
      html += `<div class="fs-palette-group-label">${group}</div>`;
      cmds.forEach(cmd => {
        const idx = this._filtered.indexOf(cmd);
        html += `<div class="fs-palette-item${idx === this._selectedIdx ? ' selected' : ''}" data-idx="${idx}">
          <span class="fs-palette-item-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
          <span class="fs-palette-item-text"><div class="fs-palette-item-label">${FS.ui.esc(cmd.label)}</div></span>
          ${cmd.kbd ? `<span class="fs-palette-item-kbd">${FS.ui.esc(cmd.kbd)}</span>` : ''}
        </div>`;
      });
    });
    document.getElementById('fs-palette-results').innerHTML = html || `<div class="fs-palette-item"><span style="font-size:12px;color:var(--fs-text-dim)">No commands found</span></div>`;
    this._bindItems();
  },

  async _renderFiles(query) {
    const proj = FS.projects.current;
    const files = proj ? await FS.files.getAll(proj.id) : [];
    const q = query.toLowerCase();
    this._filtered = files.filter(f => f.type === 'file' && (!q || f.name.toLowerCase().includes(q)));
    const html = this._filtered.map((f, idx) => `
      <div class="fs-quick-open-item${idx===this._selectedIdx?' selected':''}" data-idx="${idx}">
        ${getFileIcon(f.name)}
        <span>${FS.ui.esc(f.name)}</span>
        <span class="fs-quick-open-path"></span>
      </div>`).join('') || `<div class="fs-palette-item"><span style="font-size:12px;color:var(--fs-text-dim)">No files found</span></div>`;
    document.getElementById('fs-palette-results').innerHTML = html;
    this._bindItems();
  },

  _bindItems() {
    document.querySelectorAll('.fs-palette-item[data-idx], .fs-quick-open-item[data-idx]').forEach(el => {
      el.addEventListener('click', () => this._execute(parseInt(el.dataset.idx)));
      el.addEventListener('mouseenter', () => {
        this._selectedIdx = parseInt(el.dataset.idx);
        document.querySelectorAll('.fs-palette-item, .fs-quick-open-item').forEach(e => e.classList.remove('selected'));
        el.classList.add('selected');
      });
    });
  },

  _execute(idx) {
    if (idx < 0 || idx >= this._filtered.length) return;
    const item = this._filtered[idx];
    this.close();
    if (this._mode === 'files') {
      FS.tabs.openFile(item.id);
    } else {
      item.action?.();
    }
  },

  handleKey(e) {
    if (!this._open) return;
    if (e.key === 'Escape') { this.close(); return; }
    if (e.key === 'ArrowDown') {
      this._selectedIdx = Math.min(this._selectedIdx+1, this._filtered.length-1);
      this._refresh(); e.preventDefault();
    } else if (e.key === 'ArrowUp') {
      this._selectedIdx = Math.max(this._selectedIdx-1, 0);
      this._refresh(); e.preventDefault();
    } else if (e.key === 'Enter') {
      this._execute(this._selectedIdx); e.preventDefault();
    }
  },

  _refresh() {
    document.querySelectorAll('.fs-palette-item[data-idx], .fs-quick-open-item[data-idx]').forEach(el => {
      el.classList.toggle('selected', parseInt(el.dataset.idx) === this._selectedIdx);
    });
    const sel = document.querySelector('.fs-palette-item.selected, .fs-quick-open-item.selected');
    sel?.scrollIntoView({ block: 'nearest' });
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   SNIPPETS
══════════════════════════════════════════════════════════════════════════ */
FS.snippets = {
  _cat: 'all',
  _query: '',

  _builtIn: [
    /* ── HTML ──────────────────────────────────────────────────────── */
    { id:'bi-html5', lang:'html', cat:'html', name:'HTML5 Boilerplate',
      desc:'Complete HTML5 document starter',
      code:'<!DOCTYPE html>\n<html lang="en">\n<head>\n  <meta charset="UTF-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <title>Document</title>\n  <link rel="stylesheet" href="style.css">\n</head>\n<body>\n\n  <script src="script.js"><\/script>\n</body>\n</html>' },

    { id:'bi-meta-og', lang:'html', cat:'html', name:'Open Graph Meta Tags',
      desc:'Social sharing meta tags',
      code:'<meta property="og:title" content="Page Title">\n<meta property="og:description" content="Page description">\n<meta property="og:image" content="https://example.com/image.jpg">\n<meta property="og:url" content="https://example.com">\n<meta property="og:type" content="website">\n<meta name="twitter:card" content="summary_large_image">' },

    { id:'bi-bootstrap', lang:'html', cat:'html', name:'Bootstrap 5 CDN',
      desc:'Bootstrap CSS + JS bundle via CDN',
      code:'<!-- Bootstrap 5 -->\n<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">\n<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"><\/script>' },

    { id:'bi-tailwind', lang:'html', cat:'html', name:'Tailwind CSS CDN',
      desc:'Tailwind CSS via CDN Play',
      code:'<script src="https://cdn.tailwindcss.com"><\/script>' },

    { id:'bi-html-form', lang:'html', cat:'html', name:'Form Skeleton',
      desc:'Basic form with common inputs',
      code:'<form action="#" method="post">\n  <div class="form-group">\n    <label for="name">Name</label>\n    <input type="text" id="name" name="name" placeholder="Your name" required>\n  </div>\n  <div class="form-group">\n    <label for="email">Email</label>\n    <input type="email" id="email" name="email" placeholder="you@example.com" required>\n  </div>\n  <div class="form-group">\n    <label for="message">Message</label>\n    <textarea id="message" name="message" rows="4" placeholder="Your message..."></textarea>\n  </div>\n  <button type="submit">Submit</button>\n</form>' },

    { id:'bi-html-nav', lang:'html', cat:'html', name:'Navigation Bar',
      desc:'Responsive nav with logo + links',
      code:'<nav class="navbar">\n  <a class="navbar-brand" href="#">Brand</a>\n  <button class="nav-toggle" id="nav-toggle" aria-label="Toggle navigation">\n    <span></span><span></span><span></span>\n  </button>\n  <ul class="nav-links" id="nav-links">\n    <li><a href="#">Home</a></li>\n    <li><a href="#">About</a></li>\n    <li><a href="#">Services</a></li>\n    <li><a href="#">Contact</a></li>\n  </ul>\n</nav>' },

    { id:'bi-html-card', lang:'html', cat:'html', name:'Card Component',
      desc:'Image + title + body card',
      code:'<div class="card">\n  <img class="card-img" src="https://via.placeholder.com/400x200" alt="Card image">\n  <div class="card-body">\n    <h3 class="card-title">Card Title</h3>\n    <p class="card-text">Some quick example text to build on the card title.</p>\n    <a href="#" class="btn">Read More</a>\n  </div>\n</div>' },

    { id:'bi-html-modal', lang:'html', cat:'html', name:'Modal Dialog',
      desc:'Accessible modal with open/close',
      code:'<!-- Trigger -->\n<button id="modal-open">Open Modal</button>\n\n<!-- Modal -->\n<div class="modal-overlay" id="modal" role="dialog" aria-modal="true" style="display:none">\n  <div class="modal-box">\n    <button class="modal-close" id="modal-close" aria-label="Close">&times;</button>\n    <h2>Modal Title</h2>\n    <p>Modal content goes here.</p>\n  </div>\n</div>\n\n<script>\n  document.getElementById(\'modal-open\').addEventListener(\'click\', () => document.getElementById(\'modal\').style.display = \'flex\');\n  document.getElementById(\'modal-close\').addEventListener(\'click\', () => document.getElementById(\'modal\').style.display = \'none\');\n<\/script>' },

    /* ── CSS ────────────────────────────────────────────────────────── */
    { id:'bi-css-reset', lang:'css', cat:'css', name:'CSS Reset',
      desc:'Modern minimal CSS reset',
      code:'*, *::before, *::after {\n  box-sizing: border-box;\n  margin: 0;\n  padding: 0;\n}\n\nhtml {\n  font-size: 16px;\n  scroll-behavior: smooth;\n  -webkit-text-size-adjust: 100%;\n}\n\nbody {\n  font-family: system-ui, -apple-system, sans-serif;\n  line-height: 1.6;\n  color: #333;\n}\n\nimg, video, svg {\n  display: block;\n  max-width: 100%;\n}\n\na {\n  color: inherit;\n  text-decoration: none;\n}\n\nbutton, input, select, textarea {\n  font: inherit;\n  border: none;\n  background: none;\n}' },

    { id:'bi-css-vars', lang:'css', cat:'css', name:'CSS Custom Properties',
      desc:'Design token boilerplate',
      code:':root {\n  /* Colors */\n  --color-primary:   #3b82f6;\n  --color-secondary: #8b5cf6;\n  --color-accent:    #f59e0b;\n  --color-bg:        #ffffff;\n  --color-surface:   #f8fafc;\n  --color-text:      #1e293b;\n  --color-text-dim:  #64748b;\n  --color-border:    #e2e8f0;\n\n  /* Typography */\n  --font-sans:  system-ui, -apple-system, sans-serif;\n  --font-mono:  "JetBrains Mono", "Fira Code", monospace;\n  --text-xs:    0.75rem;\n  --text-sm:    0.875rem;\n  --text-base:  1rem;\n  --text-lg:    1.125rem;\n  --text-xl:    1.25rem;\n  --text-2xl:   1.5rem;\n\n  /* Spacing */\n  --space-1: 0.25rem;\n  --space-2: 0.5rem;\n  --space-4: 1rem;\n  --space-8: 2rem;\n\n  /* Misc */\n  --radius-sm: 4px;\n  --radius-md: 8px;\n  --radius-lg: 16px;\n  --shadow-sm: 0 1px 3px rgba(0,0,0,.1);\n  --shadow-md: 0 4px 16px rgba(0,0,0,.12);\n  --transition: .2s ease;\n}' },

    { id:'bi-css-flex-center', lang:'css', cat:'css', name:'Flexbox Center',
      desc:'Center content horizontally and vertically',
      code:'.flex-center {\n  display: flex;\n  align-items: center;\n  justify-content: center;\n}\n\n/* Full viewport center */\n.full-center {\n  display: flex;\n  align-items: center;\n  justify-content: center;\n  min-height: 100vh;\n}' },

    { id:'bi-css-grid', lang:'css', cat:'css', name:'CSS Grid Auto-fit',
      desc:'Responsive grid with auto-fitting columns',
      code:'.grid {\n  display: grid;\n  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));\n  gap: 1.5rem;\n}\n\n/* Fixed 12-column grid */\n.grid-12 {\n  display: grid;\n  grid-template-columns: repeat(12, 1fr);\n  gap: 1rem;\n}\n\n.col-6  { grid-column: span 6; }\n.col-4  { grid-column: span 4; }\n.col-3  { grid-column: span 3; }' },

    { id:'bi-css-dark', lang:'css', cat:'css', name:'Dark Mode Media Query',
      desc:'Auto dark mode with prefers-color-scheme',
      code:'/* Light mode (default) */\n:root {\n  --bg: #ffffff;\n  --text: #1a1a1a;\n  --surface: #f5f5f5;\n  --border: #e0e0e0;\n}\n\n/* Dark mode */\n@media (prefers-color-scheme: dark) {\n  :root {\n    --bg: #0f0f0f;\n    --text: #f0f0f0;\n    --surface: #1e1e1e;\n    --border: #333333;\n  }\n}\n\nbody {\n  background: var(--bg);\n  color: var(--text);\n}' },

    { id:'bi-css-scrollbar', lang:'css', cat:'css', name:'Custom Scrollbar',
      desc:'Webkit custom scrollbar styles',
      code:'/* Custom scrollbar (Chrome/Edge/Safari) */\n::-webkit-scrollbar {\n  width: 8px;\n  height: 8px;\n}\n::-webkit-scrollbar-track {\n  background: transparent;\n}\n::-webkit-scrollbar-thumb {\n  background: rgba(100, 100, 100, 0.4);\n  border-radius: 4px;\n}\n::-webkit-scrollbar-thumb:hover {\n  background: rgba(100, 100, 100, 0.7);\n}\n\n/* Firefox */\n* {\n  scrollbar-width: thin;\n  scrollbar-color: rgba(100,100,100,.4) transparent;\n}' },

    { id:'bi-css-glass', lang:'css', cat:'css', name:'Glassmorphism Card',
      desc:'Frosted glass card effect',
      code:'.glass-card {\n  background: rgba(255, 255, 255, 0.15);\n  backdrop-filter: blur(12px);\n  -webkit-backdrop-filter: blur(12px);\n  border: 1px solid rgba(255, 255, 255, 0.25);\n  border-radius: 16px;\n  padding: 2rem;\n  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);\n}\n\n/* Dark glass */\n.glass-card-dark {\n  background: rgba(0, 0, 0, 0.25);\n  backdrop-filter: blur(12px);\n  border: 1px solid rgba(255, 255, 255, 0.1);\n  border-radius: 16px;\n  padding: 2rem;\n}' },

    { id:'bi-css-btn', lang:'css', cat:'css', name:'Button Styles',
      desc:'Primary, secondary and ghost button variants',
      code:'.btn {\n  display: inline-flex;\n  align-items: center;\n  gap: .5rem;\n  padding: .5rem 1.25rem;\n  border-radius: 6px;\n  font-weight: 500;\n  font-size: .9rem;\n  cursor: pointer;\n  transition: all .2s;\n  border: none;\n}\n.btn-primary {\n  background: #3b82f6;\n  color: #fff;\n}\n.btn-primary:hover { background: #2563eb; }\n\n.btn-secondary {\n  background: #e2e8f0;\n  color: #1e293b;\n}\n.btn-secondary:hover { background: #cbd5e1; }\n\n.btn-ghost {\n  background: transparent;\n  color: #3b82f6;\n  border: 1.5px solid #3b82f6;\n}\n.btn-ghost:hover { background: #eff6ff; }\n\n.btn:disabled {\n  opacity: .45;\n  pointer-events: none;\n}' },

    { id:'bi-css-animation', lang:'css', cat:'css', name:'Smooth Animations',
      desc:'Reusable animation + transition utilities',
      code:'/* Transitions */\n.transition { transition: all .2s ease; }\n.transition-fast { transition: all .1s ease; }\n.transition-slow { transition: all .4s ease; }\n\n/* Fade in */\n@keyframes fadeIn {\n  from { opacity: 0; transform: translateY(8px); }\n  to   { opacity: 1; transform: translateY(0); }\n}\n.fade-in { animation: fadeIn .3s ease forwards; }\n\n/* Slide in from left */\n@keyframes slideIn {\n  from { opacity: 0; transform: translateX(-16px); }\n  to   { opacity: 1; transform: translateX(0); }\n}\n.slide-in { animation: slideIn .3s ease forwards; }\n\n/* Pulse */\n@keyframes pulse {\n  0%, 100% { opacity: 1; }\n  50%       { opacity: .5; }\n}\n.pulse { animation: pulse 2s ease-in-out infinite; }\n\n/* Spin */\n@keyframes spin {\n  from { transform: rotate(0deg); }\n  to   { transform: rotate(360deg); }\n}\n.spin { animation: spin 1s linear infinite; }' },

    /* ── JS ─────────────────────────────────────────────────────────── */
    { id:'bi-js-dom', lang:'js', cat:'js', name:'DOMContentLoaded Wrapper',
      desc:'Safe DOM-ready wrapper',
      code:'document.addEventListener(\'DOMContentLoaded\', () => {\n  // Your code here\n  init();\n});\n\nfunction init() {\n  console.log(\'DOM ready\');\n}' },

    { id:'bi-js-fetch', lang:'js', cat:'js', name:'Fetch with Error Handling',
      desc:'Async fetch with JSON parsing and error handling',
      code:'async function fetchData(url, options = {}) {\n  try {\n    const response = await fetch(url, {\n      headers: { \'Content-Type\': \'application/json\' },\n      ...options,\n    });\n\n    if (!response.ok) {\n      throw new Error(`HTTP ${response.status}: ${response.statusText}`);\n    }\n\n    return await response.json();\n  } catch (err) {\n    console.error(\'Fetch error:\', err);\n    throw err;\n  }\n}\n\n// Usage\nfetchData(\'https://api.example.com/data\')\n  .then(data => console.log(data))\n  .catch(err => console.error(err));' },

    { id:'bi-js-debounce', lang:'js', cat:'js', name:'Debounce',
      desc:'Delays a function until after a wait period',
      code:'function debounce(fn, delay = 300) {\n  let timer;\n  return function (...args) {\n    clearTimeout(timer);\n    timer = setTimeout(() => fn.apply(this, args), delay);\n  };\n}\n\n// Usage\nconst handleSearch = debounce((e) => {\n  console.log(\'Searching:\', e.target.value);\n}, 400);\n\ndocument.getElementById(\'search\').addEventListener(\'input\', handleSearch);' },

    { id:'bi-js-throttle', lang:'js', cat:'js', name:'Throttle',
      desc:'Limits function calls to once per interval',
      code:'function throttle(fn, limit = 200) {\n  let lastCall = 0;\n  return function (...args) {\n    const now = Date.now();\n    if (now - lastCall >= limit) {\n      lastCall = now;\n      fn.apply(this, args);\n    }\n  };\n}\n\n// Usage\nconst handleScroll = throttle(() => {\n  console.log(\'scroll Y:\', window.scrollY);\n}, 100);\n\nwindow.addEventListener(\'scroll\', handleScroll);' },

    { id:'bi-js-storage', lang:'js', cat:'js', name:'LocalStorage Helper',
      desc:'Typed get/set/remove wrapper for localStorage',
      code:'const storage = {\n  get(key, fallback = null) {\n    try {\n      const val = localStorage.getItem(key);\n      return val !== null ? JSON.parse(val) : fallback;\n    } catch { return fallback; }\n  },\n  set(key, value) {\n    try { localStorage.setItem(key, JSON.stringify(value)); }\n    catch (e) { console.warn(\'localStorage error:\', e); }\n  },\n  remove(key) {\n    localStorage.removeItem(key);\n  },\n  clear() {\n    localStorage.clear();\n  },\n};\n\n// Usage\nstorage.set(\'user\', { name: \'Alice\', theme: \'dark\' });\nconst user = storage.get(\'user\');\nconsole.log(user.name); // Alice' },

    { id:'bi-js-delegate', lang:'js', cat:'js', name:'Event Delegation',
      desc:'Efficient event handling on dynamic children',
      code:'function delegate(parent, selector, event, handler) {\n  parent.addEventListener(event, (e) => {\n    const target = e.target.closest(selector);\n    if (target && parent.contains(target)) {\n      handler.call(target, e);\n    }\n  });\n}\n\n// Usage — handles clicks on .btn inside #list (even dynamically added ones)\ndelegate(document.getElementById(\'list\'), \'.btn\', \'click\', function(e) {\n  console.log(\'Clicked:\', this.textContent);\n});' },

    { id:'bi-js-clipboard', lang:'js', cat:'js', name:'Copy to Clipboard',
      desc:'Modern clipboard API with fallback',
      code:'async function copyToClipboard(text) {\n  if (navigator.clipboard?.writeText) {\n    await navigator.clipboard.writeText(text);\n  } else {\n    // Fallback for older browsers\n    const el = document.createElement(\'textarea\');\n    el.value = text;\n    el.style.position = \'fixed\';\n    el.style.opacity = \'0\';\n    document.body.appendChild(el);\n    el.select();\n    document.execCommand(\'copy\');\n    document.body.removeChild(el);\n  }\n}\n\n// Usage\ndocument.getElementById(\'copy-btn\').addEventListener(\'click\', async () => {\n  await copyToClipboard(\'Hello, world!\');\n  alert(\'Copied!\');\n});' },

    { id:'bi-js-observer', lang:'js', cat:'js', name:'Intersection Observer',
      desc:'Trigger animations when elements enter viewport',
      code:'const observer = new IntersectionObserver((entries) => {\n  entries.forEach(entry => {\n    if (entry.isIntersecting) {\n      entry.target.classList.add(\'visible\');\n      observer.unobserve(entry.target); // animate once\n    }\n  });\n}, {\n  threshold: 0.1,\n  rootMargin: \'0px 0px -40px 0px\',\n});\n\ndocument.querySelectorAll(\'.animate\').forEach(el => observer.observe(el));' },

    { id:'bi-js-pubsub', lang:'js', cat:'js', name:'Pub/Sub Event Bus',
      desc:'Lightweight publish-subscribe pattern',
      code:'const EventBus = {\n  _events: {},\n  on(event, listener) {\n    (this._events[event] ||= []).push(listener);\n    return () => this.off(event, listener);\n  },\n  off(event, listener) {\n    this._events[event] = (this._events[event] || []).filter(l => l !== listener);\n  },\n  emit(event, ...args) {\n    (this._events[event] || []).forEach(l => l(...args));\n  },\n  once(event, listener) {\n    const off = this.on(event, (...args) => { listener(...args); off(); });\n  },\n};\n\n// Usage\nEventBus.on(\'user:login\', user => console.log(\'Logged in:\', user));\nEventBus.emit(\'user:login\', { id: 1, name: \'Alice\' });' },

    { id:'bi-js-clone', lang:'js', cat:'js', name:'Deep Clone Object',
      desc:'Deep clone with structuredClone + JSON fallback',
      code:'function deepClone(obj) {\n  if (typeof structuredClone === \'function\') {\n    return structuredClone(obj);\n  }\n  return JSON.parse(JSON.stringify(obj));\n}\n\n// Usage\nconst original = { a: 1, b: { c: [1, 2, 3] } };\nconst clone = deepClone(original);\nclone.b.c.push(4);\nconsole.log(original.b.c); // [1, 2, 3] — unchanged' },
  ],

  _loadUser() {
    try { return JSON.parse(localStorage.getItem('fs_user_snippets') || '[]'); }
    catch { return []; }
  },
  _saveUser(arr) {
    localStorage.setItem('fs_user_snippets', JSON.stringify(arr));
  },

  _all() {
    const user = this._loadUser().map(s => ({ ...s, _user: true }));
    return [...this._builtIn, ...user];
  },

  _filtered() {
    const q = this._query.toLowerCase();
    return this._all().filter(s => {
      if (this._cat !== 'all') {
        if (this._cat === 'user') return !!s._user;
        if (s.cat !== this._cat) return false;
      }
      if (!q) return true;
      return s.name.toLowerCase().includes(q) || (s.desc || '').toLowerCase().includes(q) || s.lang.includes(q);
    });
  },

  _esc(str) { return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); },

  _preview(code) {
    const lines = code.split('\n');
    const preview = lines.slice(0, 5).map(l => this._esc(l)).join('\n');
    return lines.length > 5 ? preview + '\n<span class="fs-snip-more">+' + (lines.length - 5) + ' more lines…</span>' : preview;
  },

  render() {
    const list = document.getElementById('fs-snip-list');
    if (!list) return;
    const snippets = this._filtered();
    if (!snippets.length) {
      list.innerHTML = '<div class="fs-snip-empty">No snippets match your search</div>';
      return;
    }
    list.innerHTML = snippets.map(s => `
      <div class="fs-snip-card" data-id="${s.id}">
        <div class="fs-snip-card-hd">
          <span class="fs-snip-lang fs-snip-lang-${s.lang}">${s.lang.toUpperCase()}</span>
          <span class="fs-snip-name">${this._esc(s.name)}</span>
          ${s._user ? `<button class="fs-snip-del" data-id="${s.id}" title="Delete snippet">×</button>` : ''}
        </div>
        ${s.desc ? `<div class="fs-snip-desc">${this._esc(s.desc)}</div>` : ''}
        <pre class="fs-snip-preview">${this._preview(s.code)}</pre>
        <div class="fs-snip-btns">
          <button class="fs-snip-btn-insert" data-id="${s.id}">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Insert
          </button>
          <button class="fs-snip-btn-copy" data-id="${s.id}">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            Copy
          </button>
          ${s._user ? `<button class="fs-snip-btn-edit" data-id="${s.id}">Edit</button>` : ''}
        </div>
      </div>
    `).join('');
  },

  insert(id) {
    const s = this._all().find(x => x.id === id);
    if (!s) return;
    const editor = FS.editor._instance;
    if (!editor) { FS.ui.toast('Open a file first', 'warn'); return; }
    const sel = editor.getSelection();
    editor.executeEdits('snippets', [{ range: sel, text: s.code, forceMoveMarkers: true }]);
    editor.focus();
    FS.ui.toast(`Inserted: ${s.name}`, 'success');
  },

  copy(id) {
    const s = this._all().find(x => x.id === id);
    if (!s) return;
    navigator.clipboard.writeText(s.code)
      .then(() => FS.ui.toast('Copied to clipboard!', 'success'))
      .catch(() => FS.ui.toast('Clipboard unavailable', 'error'));
  },

  deleteUser(id) {
    if (!confirm('Delete this snippet?')) return;
    this._saveUser(this._loadUser().filter(s => s.id !== id));
    this.render();
    FS.ui.toast('Snippet deleted', 'info');
  },

  saveNew(name, lang, desc, code, editId = null) {
    if (!name.trim() || !code.trim()) { FS.ui.toast('Name and code are required', 'warn'); return false; }
    let arr = this._loadUser();
    if (editId) {
      arr = arr.map(s => s.id === editId ? { ...s, name, lang, cat: lang, desc, code } : s);
      FS.ui.toast(`Snippet "${name}" updated`, 'success');
    } else {
      arr.push({ id: 'user-' + Date.now(), lang, cat: lang, name, desc, code });
      FS.ui.toast(`Snippet "${name}" saved!`, 'success');
    }
    this._saveUser(arr);
    this.render();
    return true;
  },

  saveSelection() {
    const code = FS.editor.getSelection?.() || '';
    if (!code.trim()) { FS.ui.toast('Select some code first', 'warn'); return; }
    this._showForm('', 'html', '', code);
    FS.panels.setActive('snippets');
  },

  _editingId: null,

  _showForm(name = '', lang = 'html', desc = '', code = '', editId = null) {
    this._editingId = editId;
    const form = document.getElementById('fs-snip-form');
    const list = document.getElementById('fs-snip-list');
    const newBtn = document.getElementById('fs-snip-new-btn');
    if (!form) return;
    document.getElementById('fs-snip-form-name').value = name;
    document.getElementById('fs-snip-form-lang').value = lang;
    document.getElementById('fs-snip-form-desc').value = desc;
    document.getElementById('fs-snip-form-code').value = code;
    form.style.display = 'flex';
    if (list) list.style.display = 'none';
    if (newBtn) newBtn.style.display = 'none';
    document.getElementById('fs-snip-form-name').focus();
  },

  _hideForm() {
    const form = document.getElementById('fs-snip-form');
    const list = document.getElementById('fs-snip-list');
    const newBtn = document.getElementById('fs-snip-new-btn');
    if (form) form.style.display = 'none';
    if (list) list.style.display = '';
    if (newBtn) newBtn.style.display = '';
    this._editingId = null;
  },

  init() {
    document.getElementById('fs-snip-search')?.addEventListener('input', e => {
      this._query = e.target.value;
      this.render();
    });

    document.getElementById('fs-snip-cats')?.addEventListener('click', e => {
      const btn = e.target.closest('.fs-snip-cat');
      if (!btn) return;
      document.querySelectorAll('.fs-snip-cat').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      this._cat = btn.dataset.cat;
      this.render();
    });

    document.getElementById('fs-snip-save-sel')?.addEventListener('click', () => this.saveSelection());

    document.getElementById('fs-snip-new-btn')?.addEventListener('click', () => this._showForm());

    document.getElementById('fs-snip-form-cancel')?.addEventListener('click', () => this._hideForm());

    document.getElementById('fs-snip-form-submit')?.addEventListener('click', () => {
      const name = document.getElementById('fs-snip-form-name').value;
      const lang = document.getElementById('fs-snip-form-lang').value;
      const desc = document.getElementById('fs-snip-form-desc').value;
      const code = document.getElementById('fs-snip-form-code').value;
      if (this.saveNew(name, lang, desc, code, this._editingId)) this._hideForm();
    });

    document.getElementById('fs-snip-list')?.addEventListener('click', e => {
      const insert = e.target.closest('.fs-snip-btn-insert');
      const copy   = e.target.closest('.fs-snip-btn-copy');
      const del    = e.target.closest('.fs-snip-del');
      const edit   = e.target.closest('.fs-snip-btn-edit');
      if (insert) this.insert(insert.dataset.id);
      if (copy)   this.copy(copy.dataset.id);
      if (del)    this.deleteUser(del.dataset.id);
      if (edit) {
        const s = this._loadUser().find(x => x.id === edit.dataset.id);
        if (s) this._showForm(s.name, s.lang || 'html', s.desc || '', s.code, s.id);
      }
    });

    this.render();
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   PANELS
══════════════════════════════════════════════════════════════════════════ */
FS.panels = {
  setActive(panelId) {
    if (panelId === 'ai') { FS.floatAI.open(); return; }
    if (panelId === 'tools') { FS.toolsOverlay.open(); return; }
    const validPanels = ['explorer','search','extensions','snippets','git','settings'];
    if (!validPanels.includes(panelId)) panelId = 'explorer';
    document.querySelectorAll('.fs-act-btn[data-panel]').forEach(btn => btn.classList.toggle('active', btn.dataset.panel === panelId));
    document.querySelectorAll('.fs-panel').forEach(p => p.classList.remove('active'));
    document.getElementById(`fs-panel-${panelId}`)?.classList.add('active');
    FS.settings.set('activePanel', panelId);
    if (panelId === 'extensions') FS.extensions.render();
    if (panelId === 'settings')   FS.settings_ui.render();
    if (panelId === 'snippets')   FS.snippets.render();
  },

  toggleSidebar() {
    const sidebar = document.getElementById('fs-sidebar');
    const visible = !sidebar.classList.contains('hidden');
    sidebar.classList.toggle('hidden', visible);
    FS.settings.set('sidebarVisible', !visible);
    setTimeout(() => FS.editor._instance?.layout(), 150);
  },

  toggleBottom() {
    const panel = document.getElementById('fs-bottom-panel');
    const visible = !panel.classList.contains('hidden');
    panel.classList.toggle('hidden', visible);
    FS.settings.set('bottomPanelVisible', !visible);
    setTimeout(() => FS.editor._instance?.layout(), 150);
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   SETTINGS UI
══════════════════════════════════════════════════════════════════════════ */
FS.settings_ui = {
  render() {
    const s = FS.settings;
    const themeGrid = Object.entries(FS.THEMES).map(([k,v]) =>
      `<div class="fs-theme-option${s.get('theme')===k?' active':''}" data-theme="${k}">${v.label}</div>`
    ).join('');
    document.getElementById('fs-settings-content').innerHTML = `
      <div class="fs-settings-section">
        <div class="fs-settings-section-title">Appearance</div>
        <div class="fs-settings-item"><label class="fs-settings-item-label">Theme</label></div>
        <div class="fs-theme-grid">${themeGrid}</div>
        <div class="fs-settings-item">
          <label class="fs-settings-item-label">Font Size<span class="fs-settings-item-sub">Editor font size (px)</span></label>
          <input type="number" class="fs-settings-input" id="s-font-size" value="${s.get('editorFontSize')}" min="8" max="32">
        </div>
        <div class="fs-settings-item">
          <label class="fs-settings-item-label">Font Family</label>
          <select class="fs-settings-select" id="s-font-family">
            <option value="'JetBrains Mono', Consolas, monospace" ${s.get('editorFontFamily').includes('JetBrains')?'selected':''}>JetBrains Mono</option>
            <option value="'Fira Code', Consolas, monospace" ${s.get('editorFontFamily').includes('Fira')?'selected':''}>Fira Code</option>
            <option value="Consolas, monospace" ${s.get('editorFontFamily')==='Consolas, monospace'?'selected':''}>Consolas</option>
            <option value="monospace" ${s.get('editorFontFamily')==='monospace'?'selected':''}>Monospace</option>
          </select>
        </div>
      </div>
      <div class="fs-settings-section">
        <div class="fs-settings-section-title">Editor</div>
        <div class="fs-settings-item">
          <label class="fs-settings-item-label">Tab Size</label>
          <select class="fs-settings-select" id="s-tab-size">
            <option value="2" ${s.get('editorTabSize')===2?'selected':''}>2</option>
            <option value="4" ${s.get('editorTabSize')===4?'selected':''}>4</option>
            <option value="8" ${s.get('editorTabSize')===8?'selected':''}>8</option>
          </select>
        </div>
        <div class="fs-settings-item">
          <label class="fs-settings-item-label">Word Wrap</label>
          <button class="fs-settings-toggle${s.get('editorWordWrap')==='on'?' on':''}" id="s-word-wrap"></button>
        </div>
        <div class="fs-settings-item">
          <label class="fs-settings-item-label">Minimap</label>
          <button class="fs-settings-toggle${s.get('editorMinimap')?' on':''}" id="s-minimap"></button>
        </div>
        <div class="fs-settings-item">
          <label class="fs-settings-item-label">Line Numbers</label>
          <button class="fs-settings-toggle${s.get('editorLineNumbers')==='on'?' on':''}" id="s-line-nums"></button>
        </div>
        <div class="fs-settings-item">
          <label class="fs-settings-item-label">Auto Save<span class="fs-settings-item-sub">Save after typing stops</span></label>
          <button class="fs-settings-toggle${s.get('editorAutoSave')?' on':''}" id="s-autosave"></button>
        </div>
        <div class="fs-settings-item">
          <label class="fs-settings-item-label">Auto Save Delay (ms)</label>
          <input type="number" class="fs-settings-input" id="s-autosave-delay" value="${s.get('autoSaveDelay')}" min="200" max="10000" step="200">
        </div>
      </div>
      <div class="fs-settings-section">
        <div class="fs-settings-section-title">Preview</div>
        <div class="fs-settings-item">
          <label class="fs-settings-item-label">Auto Refresh<span class="fs-settings-item-sub">Refresh preview on save</span></label>
          <button class="fs-settings-toggle${s.get('previewAutoRefresh')?' on':''}" id="s-preview-refresh"></button>
        </div>
      </div>
      <div class="fs-settings-section">
        <div class="fs-settings-section-title">Keyboard Shortcuts</div>
        <div style="padding:8px 16px">
          ${FS.palette.COMMANDS.filter(c=>c.kbd).map(c=>
            `<div class="fs-settings-item" style="padding:4px 0">
              <span class="fs-settings-item-label">${FS.ui.esc(c.label)}</span>
              <kbd>${FS.ui.esc(c.kbd)}</kbd>
            </div>`).join('')}
        </div>
      </div>
      <div class="fs-settings-footer">
        <button class="fs-sm-btn ghost" id="s-export">Export</button>
        <button class="fs-sm-btn ghost" id="s-import">Import</button>
        <button class="fs-sm-btn danger" id="s-reset">Reset Defaults</button>
      </div>`;

    // Theme grid
    document.querySelectorAll('.fs-theme-option').forEach(el => {
      el.addEventListener('click', () => this.applyTheme(el.dataset.theme));
    });

    // Toggles
    const toggles = {
      's-word-wrap':    () => { const v = s.get('editorWordWrap')==='on'?'off':'on'; s.set('editorWordWrap',v); FS.editor.updateOptions({wordWrap:v}); },
      's-minimap':      () => { const v=!s.get('editorMinimap'); s.set('editorMinimap',v); FS.editor.updateOptions({minimap:{enabled:v}}); },
      's-line-nums':    () => { const v=s.get('editorLineNumbers')==='on'?'off':'on'; s.set('editorLineNumbers',v); FS.editor.updateOptions({lineNumbers:v}); },
      's-autosave':     () => { const v=!s.get('editorAutoSave'); s.set('editorAutoSave',v); FS.status.update({autosave:v?'Autosave: On':'Autosave: Off'}); },
      's-preview-refresh':() => { s.set('previewAutoRefresh',!s.get('previewAutoRefresh')); },
    };
    Object.entries(toggles).forEach(([id, fn]) => {
      document.getElementById(id)?.addEventListener('click', function() { fn(); this.classList.toggle('on'); });
    });

    // Number inputs
    document.getElementById('s-font-size')?.addEventListener('change', e => {
      const v = parseInt(e.target.value)||14;
      s.set('editorFontSize',v); FS.editor.updateOptions({fontSize:v});
    });
    document.getElementById('s-tab-size')?.addEventListener('change', e => {
      const v = parseInt(e.target.value)||2;
      s.set('editorTabSize',v); FS.editor.updateOptions({tabSize:v});
    });
    document.getElementById('s-autosave-delay')?.addEventListener('change', e => {
      s.set('autoSaveDelay', parseInt(e.target.value)||1000);
    });
    document.getElementById('s-font-family')?.addEventListener('change', e => {
      s.set('editorFontFamily', e.target.value); FS.editor.updateOptions({fontFamily:e.target.value});
    });

    // Actions
    document.getElementById('s-export')?.addEventListener('click', () => this.exportSettings());
    document.getElementById('s-import')?.addEventListener('click', () => this.importSettings());
    document.getElementById('s-reset')?.addEventListener('click', () => this.resetSettings());
  },

  applyTheme(key) {
    document.documentElement.setAttribute('data-theme', key);
    FS.settings.set('theme', key);
    FS.editor.applyTheme(key);
    document.querySelectorAll('.fs-theme-option').forEach(el =>
      el.classList.toggle('active', el.dataset.theme === key));
    FS.ui.toast(`Theme: ${FS.THEMES[key]?.label}`, 'info');
  },

  exportSettings() {
    const blob = new Blob([FS.settings.export()], { type: 'application/json' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'frontend-studio-settings.json';
    a.click();
    FS.ui.toast('Settings exported', 'success');
  },

  async importSettings() {
    const name = await FS.ui.prompt('Import Settings', 'Paste settings JSON:', '');
    if (!name) return;
    if (FS.settings.import(name)) {
      this.applyTheme(FS.settings.get('theme'));
      this.render();
      FS.ui.toast('Settings imported', 'success');
    } else {
      FS.ui.toast('Invalid JSON', 'error');
    }
  },

  async resetSettings() {
    const ok = await FS.ui.confirm('Reset Settings', 'Reset all settings to defaults?');
    if (!ok) return;
    FS.settings.reset();
    this.applyTheme('dark');
    this.render();
    FS.ui.toast('Settings reset', 'info');
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   STATUS BAR
══════════════════════════════════════════════════════════════════════════ */
FS.status = {
  _state: { lang:'', line:1, col:1, indent:2, errors:0, warnings:0, autosave:'', projectName:'', aiProvider:'' },
  update(patch) {
    Object.assign(this._state, patch);
    const s = this._state;
    const langEl = document.getElementById('fs-status-lang');
    if (langEl && patch.lang !== undefined) langEl.textContent = s.lang.toUpperCase() || 'TEXT';
    const posEl = document.getElementById('fs-status-pos');
    if (posEl && (patch.line !== undefined || patch.col !== undefined)) posEl.textContent = `Ln ${s.line}, Col ${s.col}`;
    const indentEl = document.getElementById('fs-status-indent');
    if (indentEl) indentEl.textContent = `Spaces: ${FS.settings.get('editorTabSize')}`;
    if (patch.autosave !== undefined) {
      const el = document.getElementById('fs-status-autosave');
      if (el) el.textContent = s.autosave;
    }
    if (patch.projectName !== undefined) {
      const el = document.getElementById('fs-status-project-name');
      if (el) el.textContent = s.projectName;
    }
    if (patch.aiProvider !== undefined) {
      const el = document.getElementById('fs-status-ai-provider');
      if (el) el.textContent = s.aiProvider || 'No AI';
    }
    if (patch.errors !== undefined) {
      const el = document.getElementById('fs-status-error-count');
      if (el) el.textContent = s.errors;
    }
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   WELCOME
══════════════════════════════════════════════════════════════════════════ */
FS.welcome = {
  async refreshRecent() {
    const projects = FS.projects._list.slice(0, 5);
    const container = document.getElementById('fs-welcome-recent');
    if (container) {
      if (!projects.length) {
        container.innerHTML = '';
      } else {
        container.innerHTML = `<h3>Recent Projects</h3>` + projects.map(p =>
          `<div class="fs-welcome-recent-item" data-id="${p.id}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            ${FS.ui.esc(p.name)}
          </div>`).join('');
        container.querySelectorAll('[data-id]').forEach(el => {
          el.addEventListener('click', async () => {
            await FS.projects.setCurrent(el.dataset.id);
            FS.tabs.closeAll();
            await FS.explorer.refresh();
          });
        });
      }
    }

    // Templates section
    const tplContainer = document.getElementById('fs-welcome-templates');
    if (tplContainer) {
      const templates = [
        { id: 'webapp',    label: 'Web App',      icon: '🌐', desc: 'HTML + CSS + JS starter' },
        { id: 'landing',   label: 'Landing Page', icon: '🚀', desc: 'Product landing page' },
        { id: 'portfolio', label: 'Portfolio',    icon: '🎨', desc: 'Personal portfolio site' },
        { id: 'blank',     label: 'Blank',        icon: '📄', desc: 'Empty HTML file' },
      ];
      tplContainer.innerHTML = `<h3>Start from Template</h3>
        <div class="fs-welcome-tpl-grid">
          ${templates.map(t =>
            `<div class="fs-welcome-tpl-card" data-tpl="${t.id}">
              <span class="fs-welcome-tpl-icon">${t.icon}</span>
              <div>
                <div class="fs-welcome-tpl-name">${t.label}</div>
                <div class="fs-welcome-tpl-desc">${t.desc}</div>
              </div>
            </div>`
          ).join('')}
        </div>`;
      tplContainer.querySelectorAll('[data-tpl]').forEach(el => {
        el.addEventListener('click', () => FS.explorer.newProjectFromTemplate(el.dataset.tpl));
      });
    }

    // Quick actions section
    const actContainer = document.getElementById('fs-welcome-actions');
    if (actContainer) {
      actContainer.innerHTML = `<div class="fs-welcome-action-row">
        <button class="fs-welcome-action-btn" id="fs-welcome-open-file2">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Open File
        </button>
        <button class="fs-welcome-action-btn" id="fs-welcome-import-zip2">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          Import ZIP
        </button>
      </div>`;
      document.getElementById('fs-welcome-open-file2')?.addEventListener('click', () => {
        document.getElementById('fs-welcome-open-file')?.click();
      });
      document.getElementById('fs-welcome-import-zip2')?.addEventListener('click', () => {
        FS.explorer.importZip();
      });
    }
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   RESIZE HANDLERS
══════════════════════════════════════════════════════════════════════════ */
FS.resize = {
  init() {
    // Sidebar resize
    const sidebarResizer = document.getElementById('fs-sidebar-resize');
    let dragging = false, startX = 0, startW = 0;
    sidebarResizer?.addEventListener('mousedown', e => {
      dragging = true;
      startX = e.clientX;
      startW = document.getElementById('fs-sidebar').offsetWidth;
      sidebarResizer.classList.add('dragging');
      e.preventDefault();
    });
    document.addEventListener('mousemove', e => {
      if (!dragging) return;
      const w = Math.max(180, Math.min(600, startW + e.clientX - startX));
      document.getElementById('fs-sidebar').style.width = w + 'px';
      FS.settings.set('sidebarWidth', w);
    });
    document.addEventListener('mouseup', () => {
      if (dragging) { dragging = false; sidebarResizer?.classList.remove('dragging'); FS.editor._instance?.layout(); }
    });

    // Bottom panel resize
    const bottomResizer = document.getElementById('fs-bottom-resize');
    let bDragging = false, startY = 0, startH = 0;
    bottomResizer?.addEventListener('mousedown', e => {
      bDragging = true;
      startY = e.clientY;
      startH = document.getElementById('fs-bottom-panel').offsetHeight;
      bottomResizer.classList.add('dragging');
      e.preventDefault();
    });
    document.addEventListener('mousemove', e => {
      if (!bDragging) return;
      const h = Math.max(80, Math.min(600, startH - (e.clientY - startY)));
      document.getElementById('fs-bottom-panel').style.height = h + 'px';
      FS.settings.set('bottomPanelHeight', h);
    });
    document.addEventListener('mouseup', () => {
      if (bDragging) { bDragging = false; bottomResizer?.classList.remove('dragging'); FS.editor._instance?.layout(); }
    });

    // Preview resize
    const previewResizer = document.getElementById('fs-preview-resize');
    let pDragging = false, pStartX = 0, pStartW = 0;
    previewResizer?.addEventListener('mousedown', e => {
      pDragging = true;
      pStartX = e.clientX;
      pStartW = document.getElementById('fs-preview-pane').offsetWidth;
      previewResizer.classList.add('dragging');
      e.preventDefault();
    });
    document.addEventListener('mousemove', e => {
      if (!pDragging) return;
      const w = Math.max(200, Math.min(window.innerWidth*0.8, pStartW - (e.clientX - pStartX)));
      document.getElementById('fs-preview-pane').style.width = w + 'px';
    });
    document.addEventListener('mouseup', () => {
      if (pDragging) { pDragging = false; previewResizer?.classList.remove('dragging'); FS.editor._instance?.layout(); }
    });

    // Restore saved sizes
    const savedSW = FS.settings.get('sidebarWidth');
    if (savedSW) document.getElementById('fs-sidebar').style.width = savedSW + 'px';
    const savedBH = FS.settings.get('bottomPanelHeight');
    if (savedBH) document.getElementById('fs-bottom-panel').style.height = savedBH + 'px';
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   KEYBOARD SHORTCUTS
══════════════════════════════════════════════════════════════════════════ */
FS.shortcuts = {
  init() {
    document.addEventListener('keydown', e => {
      const ctrl = e.ctrlKey || e.metaKey;
      const shift = e.shiftKey;

      // Palette
      if (ctrl && shift && e.key === 'P') { e.preventDefault(); FS.palette.open(); return; }
      if (ctrl && !shift && e.key === 'p') { e.preventDefault(); FS.palette.openQuickFile(); return; }

      // Handle palette navigation
      if (FS.palette._open) { FS.palette.handleKey(e); return; }

      // Panel toggles
      if (ctrl && e.key === 'b') { e.preventDefault(); FS.panels.toggleSidebar(); return; }
      if (ctrl && e.key === '`') { e.preventDefault(); FS.panels.toggleBottom(); return; }
      if (ctrl && shift && e.key === 'E') { e.preventDefault(); FS.panels.setActive('explorer'); return; }
      if (ctrl && shift && e.key === 'F') { e.preventDefault(); FS.panels.setActive('search'); return; }
      if (ctrl && shift && e.key === 'X') { e.preventDefault(); FS.panels.setActive('extensions'); return; }
      if (ctrl && shift && e.key === 'A') { e.preventDefault(); FS.floatAI.toggle(); return; }
      if (ctrl && shift && e.key === 'T') { e.preventDefault(); FS.toolsOverlay.open(); return; }
      if (ctrl && shift && e.key === 'K') { e.preventDefault(); FS.panels.setActive('snippets'); return; }

      // Tabs
      if (ctrl && e.key === 'w') { e.preventDefault(); FS.tabs.closeTab(FS.tabs._activeIdx); return; }
      if (ctrl && e.key === 'Tab') { e.preventDefault(); shift ? FS.tabs.prev() : FS.tabs.next(); return; }
      if (ctrl && shift && e.key === 'T') { e.preventDefault(); FS.tabs.reopenClosed(); return; }

      // F keys
      if (e.key === 'F5') { e.preventDefault(); FS.preview.refresh(); return; }
      if (e.key === 'F11') { e.preventDefault(); toggleFullscreen(); return; }

      // New file
      if (ctrl && !shift && e.key === 'n') { e.preventDefault(); FS.explorer.newFile(); return; }
    });

    // Close menus on click outside
    document.addEventListener('click', e => {
      if (!e.target.closest('#fs-ctx-menu')) FS.ui.closeCtx();
      if (!e.target.closest('#fs-palette-overlay') || e.target.id === 'fs-palette-overlay') {
        if (FS.palette._open) FS.palette.close();
      }
      if (!e.target.closest('#fs-modal-overlay') || e.target.id === 'fs-modal-overlay') {
        // don't close modal on outside click (to avoid accidental dismissal)
      }
    });

    // Command palette input
    document.getElementById('fs-palette-input')?.addEventListener('input', e => {
      const q = e.target.value;
      if (FS.palette._mode === 'files') FS.palette._renderFiles(q);
      else if (FS.palette._mode === 'goto') {
        const line = parseInt(q.replace(':',''));
        if (!isNaN(line)) {
          document.getElementById('fs-palette-results').innerHTML = `<div class="fs-palette-item selected" data-idx="0">Go to line ${line}</div>`;
          FS.editor.goToLine(line);
        }
      } else FS.palette._render(q);
    });
  },
};

function toggleFullscreen() {
  if (!document.fullscreenElement) document.documentElement.requestFullscreen?.();
  else document.exitFullscreen?.();
}

/* ══════════════════════════════════════════════════════════════════════════
   WIRE UP UI EVENTS
══════════════════════════════════════════════════════════════════════════ */
FS.bindUI = function() {
  // Title bar
  document.getElementById('fs-title-palette')?.addEventListener('click', () => FS.palette.open());
  document.getElementById('fs-btn-fullscreen')?.addEventListener('click', toggleFullscreen);
  document.getElementById('fs-btn-preview-toggle')?.addEventListener('click', () => FS.preview.toggle());

  // Activity bar — only buttons with data-panel control the sidebar
  document.querySelectorAll('.fs-act-btn[data-panel]').forEach(btn => {
    btn.addEventListener('click', () => {
      if (btn.classList.contains('active')) {
        FS.panels.toggleSidebar();
      } else {
        if (document.getElementById('fs-sidebar').classList.contains('hidden')) {
          FS.panels.toggleSidebar();
        }
        FS.panels.setActive(btn.dataset.panel);
      }
    });
  });
  // Collapse sidebar button (no data-panel — separate handler)
  document.getElementById('fs-act-collapse-sidebar')?.addEventListener('click', () => FS.panels.toggleSidebar());
  // AI float toggle (actbar)
  document.getElementById('fs-act-ai-btn')?.addEventListener('click', () => FS.floatAI.toggle());
  // Tools overlay (actbar)
  document.getElementById('fs-act-tools-btn')?.addEventListener('click', () => FS.toolsOverlay.open());
  // Tools overlay (titlebar button)
  document.getElementById('fs-btn-tools-overlay')?.addEventListener('click', () => FS.toolsOverlay.open());
  // AI float (titlebar button)
  document.getElementById('fs-btn-ai-float')?.addEventListener('click', () => FS.floatAI.toggle());
  // Float AI panel controls
  document.getElementById('fs-float-minimize-btn')?.addEventListener('click', () => FS.floatAI.minimize());
  document.getElementById('fs-float-resize-btn')?.addEventListener('click', () => FS.floatAI.cycleSize());
  document.getElementById('fs-float-close-btn')?.addEventListener('click', () => FS.floatAI.close());
  // Tools overlay controls
  document.getElementById('fs-tools-close-btn')?.addEventListener('click', () => FS.toolsOverlay.close());

  // Explorer actions
  document.getElementById('fs-btn-new-file')?.addEventListener('click', () => FS.explorer.newFile());
  document.getElementById('fs-btn-new-folder')?.addEventListener('click', () => FS.explorer.newFolder());
  document.getElementById('fs-btn-new-project')?.addEventListener('click', () => FS.explorer.newProject());
  document.getElementById('fs-btn-collapse-all')?.addEventListener('click', () => FS.explorer.collapseAll());
  document.getElementById('fs-btn-export-zip')?.addEventListener('click', () => FS.explorer.exportZip());
  document.getElementById('fs-btn-import-zip')?.addEventListener('click', () => FS.explorer.importZip());

  // Welcome screen
  document.getElementById('fs-welcome-new-project')?.addEventListener('click', () => FS.explorer.newProject());
  document.getElementById('fs-welcome-open-file')?.addEventListener('click', () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.html,.htm,.css,.js,.json,.md,.svg,.txt,.ts,.scss,.less';
    input.onchange = async (e) => {
      const file = e.target.files[0];
      if (!file) return;
      const content = await file.text();
      const proj = FS.projects.current || await (async () => {
        const p = await FS.projects.create('Imported Files');
        await FS.projects.setCurrent(p.id);
        return p;
      })();
      const f = await FS.files.create(proj.id, null, file.name, 'file', content);
      await FS.explorer.refresh();
      FS.tabs.openFile(f.id);
    };
    input.click();
  });

  // Search
  document.getElementById('fs-search-query')?.addEventListener('input', () => FS.search.run());
  document.getElementById('fs-search-case')?.addEventListener('click', function() {
    this.dataset.active = this.dataset.active === 'true' ? 'false' : 'true';
    this.classList.toggle('active', this.dataset.active === 'true');
    FS.search.run();
  });
  document.getElementById('fs-search-regex')?.addEventListener('click', function() {
    this.dataset.active = this.dataset.active === 'true' ? 'false' : 'true';
    this.classList.toggle('active', this.dataset.active === 'true');
    FS.search.run();
  });
  document.getElementById('fs-search-do-replace')?.addEventListener('click', () => FS.search.replaceAll());

  // Extension search
  document.getElementById('fs-ext-search')?.addEventListener('input', () => FS.extensions.render());

  // AI panel
  document.getElementById('fs-ai-settings-btn')?.addEventListener('click', () => FS.ai.openConfig());
  document.getElementById('fs-ai-configure-btn')?.addEventListener('click', () => FS.ai.openConfig());
  document.getElementById('fs-ai-clear-btn')?.addEventListener('click', () => FS.ai.clearChat());
  document.getElementById('fs-ai-send')?.addEventListener('click', async () => {
    const input = document.getElementById('fs-ai-input');
    const msg = input?.value.trim();
    if (!msg) return;
    input.value = '';
    await FS.ai.send(msg, document.getElementById('fs-ai-context')?.value || 'file');
  });
  document.getElementById('fs-ai-input')?.addEventListener('keydown', async e => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      document.getElementById('fs-ai-send')?.click();
    }
  });
  document.querySelectorAll('.fs-ai-action-btn').forEach(btn => {
    btn.addEventListener('click', () => FS.ai.runAction(btn.dataset.action));
  });
  document.getElementById('fs-ai-config-close')?.addEventListener('click', () => {
    document.getElementById('fs-ai-config-overlay').style.display = 'none';
  });

  // Preview controls
  document.querySelectorAll('.fs-device-btn').forEach(btn => {
    btn.addEventListener('click', () => FS.preview.setDevice(btn.dataset.device));
  });
  document.getElementById('fs-preview-refresh')?.addEventListener('click', () => FS.preview.refresh());
  document.getElementById('fs-preview-pause')?.addEventListener('click', function() {
    FS.preview._paused = !FS.preview._paused;
    this.title = FS.preview._paused ? 'Resume auto-refresh' : 'Pause auto-refresh';
    this.style.opacity = FS.preview._paused ? '0.5' : '1';
  });
  document.getElementById('fs-preview-newwindow')?.addEventListener('click', () => FS.preview.openInNewWindow());
  document.getElementById('fs-preview-close')?.addEventListener('click', () => FS.preview.hide());
  document.getElementById('fs-preview-rotate')?.addEventListener('click', () => {
    FS.preview._landscape = !FS.preview._landscape;
    FS.preview._applyDevice();
  });
  document.getElementById('fs-preview-zoom')?.addEventListener('change', e => {
    FS.preview._zoom = parseFloat(e.target.value);
    FS.preview._applyDevice();
  });
  document.getElementById('fs-preview-manage-links')?.addEventListener('click', () => FS.preview.manageLinks());

  // Bottom panel tabs
  document.querySelectorAll('.fs-bottom-tab[data-btab]').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.fs-bottom-tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.fs-btab-pane').forEach(p => p.classList.remove('active'));
      tab.classList.add('active');
      document.getElementById(`fs-btab-${tab.dataset.btab}`)?.classList.add('active');
    });
  });
  document.getElementById('fs-bottom-close')?.addEventListener('click', () => FS.panels.toggleBottom());

  // Status bar
  document.getElementById('fs-status-pos')?.addEventListener('click', () => FS.palette.goToLine());
  document.getElementById('fs-status-project')?.addEventListener('click', async e => {
    FS.explorer.showProjectList(e.currentTarget);
  });
  document.getElementById('fs-status-lang')?.addEventListener('click', () => FS.panels.setActive('explorer'));

  // Modal close
  document.getElementById('fs-modal-close')?.addEventListener('click', () => FS.ui.closeModal());
  document.getElementById('fs-modal-overlay')?.addEventListener('click', e => {
    if (e.target === document.getElementById('fs-modal-overlay')) FS.ui.closeModal();
  });

  // Palette overlay close
  document.getElementById('fs-palette-overlay')?.addEventListener('click', e => {
    if (e.target === document.getElementById('fs-palette-overlay')) FS.palette.close();
  });

  // Tab overflow (show all tabs)
  document.getElementById('fs-tab-overflow-btn')?.addEventListener('click', () => {
    const items = FS.tabs._tabs.map((t, idx) => ({
      label: t.name + (t.dirty ? ' ●' : ''),
      action: () => FS.tabs.setActive(idx),
    }));
    const btn = document.getElementById('fs-tab-overflow-btn');
    const rect = btn.getBoundingClientRect();
    FS.ui.contextMenu(rect.left, rect.bottom + 2, items);
  });

  // Menu bar (basic dropdowns)
  document.querySelectorAll('.fs-menu-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      const menu = btn.dataset.menu;
      const rect = btn.getBoundingClientRect();
      const menus = {
        file: [
          { label: 'New Project', action: () => FS.explorer.newProject() },
          { label: 'New File', action: () => FS.explorer.newFile(), kbd: 'Ctrl+N' },
          'sep',
          { label: 'New: Blank Project', action: () => FS.explorer.newProjectFromTemplate('blank') },
          { label: 'New: Web App', action: () => FS.explorer.newProjectFromTemplate('webapp') },
          { label: 'New: Landing Page', action: () => FS.explorer.newProjectFromTemplate('landing') },
          { label: 'New: Portfolio', action: () => FS.explorer.newProjectFromTemplate('portfolio') },
          'sep',
          { label: 'Save', action: () => FS.editor.save(), kbd: 'Ctrl+S' },
          { label: 'Save All', action: () => FS.editor.saveAll(), kbd: 'Ctrl+Shift+S' },
          'sep',
          { label: 'Open Local File', action: () => document.getElementById('fs-welcome-open-file')?.click() },
          { label: 'Import ZIP', action: () => FS.explorer.importZip() },
          { label: 'Export Project as ZIP', action: () => FS.explorer.exportZip() },
          { label: 'Download Current File', action: () => FS.preview.downloadCurrentFile() },
        ],
        edit: [
          { label: 'Format Document', action: () => FS.editor.format(), kbd: 'Shift+Alt+F' },
          { label: 'Go to Line', action: () => FS.palette.goToLine(), kbd: 'Ctrl+G' },
          'sep',
          { label: 'Search in Project', action: () => FS.panels.setActive('search'), kbd: 'Ctrl+Shift+F' },
        ],
        view: [
          { label: 'Toggle Preview', action: () => FS.preview.toggle() },
          { label: 'Toggle Sidebar', action: () => FS.panels.toggleSidebar(), kbd: 'Ctrl+B' },
          { label: 'Toggle Bottom Panel', action: () => FS.panels.toggleBottom(), kbd: 'Ctrl+`' },
          'sep',
          ...Object.entries(FS.THEMES).map(([k,v]) => ({ label: 'Theme: '+v.label, action: () => FS.settings_ui.applyTheme(k) })),
        ],
        run: [
          { label: 'Refresh Preview', action: () => FS.preview.refresh(), kbd: 'F5' },
          { label: 'Open in New Window', action: () => FS.preview.openInNewWindow() },
          { label: 'Manage Asset Links', action: () => FS.preview.manageLinks() },
        ],
        help: [
          { label: 'Command Palette', action: () => FS.palette.open(), kbd: 'Ctrl+Shift+P' },
          { label: 'Keyboard Shortcuts', action: () => { FS.panels.setActive('settings'); } },
          'sep',
          { label: 'Back to Awan Tools', action: () => window.location = '/' },
        ],
      };
      FS.ui.contextMenu(rect.left, rect.bottom + 2, menus[menu] || []);
    });
  });

  // Preview: show link bar for HTML files
  FS.events.on('file:open', async (file) => {
    const lang = getLang(file.name);
    const bar = document.getElementById('fs-preview-link-bar');
    if (bar) bar.style.display = lang === 'html' ? 'flex' : 'none';
  });
};

/* ══════════════════════════════════════════════════════════════════════════
   INIT
══════════════════════════════════════════════════════════════════════════ */
FS.init = async function() {
  // Load settings
  FS.settings.load();

  // Apply theme
  const theme = FS.settings.get('theme') || 'dark';
  document.documentElement.setAttribute('data-theme', theme);

  // Apply layout prefs
  if (!FS.settings.get('sidebarVisible')) {
    document.getElementById('fs-sidebar').classList.add('hidden');
  }
  if (!FS.settings.get('bottomPanelVisible')) {
    document.getElementById('fs-bottom-panel').classList.add('hidden');
  }

  // Init IndexedDB
  try {
    await FS.db.open();
  } catch(e) {
    FS.ui.toast('Storage unavailable: ' + e.message, 'error');
  }

  // Load projects
  await FS.projects.loadAll();

  // Init UI event bindings
  FS.bindUI();
  FS.shortcuts.init();
  FS.resize.init();
  FS.floatAI.init();
  FS.snippets.init();

  // Set active panel
  FS.panels.setActive(FS.settings.get('activePanel') || 'explorer');

  // Load current project
  const savedProjectId = FS.settings.get('currentProjectId');
  if (savedProjectId) {
    await FS.projects.setCurrent(savedProjectId);
  }

  // Render explorer
  await FS.explorer.refresh();
  await FS.welcome.refreshRecent();

  // Update AI status
  FS.ai.updateProviderBar();

  // Update status bar
  FS.status.update({
    autosave: FS.settings.get('editorAutoSave') ? 'Autosave: On' : 'Autosave: Off',
    lang: 'text',
  });

  // Init Monaco editor
  await FS.editor.init();

  // Init extensions
  FS.extensions.render();

  // If sidebar not visible initially, show it
  if (!FS.settings.get('sidebarVisible')) {
    document.getElementById('fs-sidebar').classList.add('hidden');
  }
};

/* ══════════════════════════════════════════════════════════════════════════
   FLOATING AI PANEL
══════════════════════════════════════════════════════════════════════════ */
FS.floatAI = {
  _visible: false,
  _minimized: false,
  _sizes: ['normal', 'wide', 'tall'],
  _sizeIdx: 0,
  _drag: { active: false, startX: 0, startY: 0, origX: 0, origY: 0 },
  _resize: { active: false, startX: 0, startY: 0, origW: 0, origH: 0 },

  init() {
    this._initDrag();
    this._initResize();
    // ESC key closes float AI
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && this._visible) this.close();
    }, true);
  },

  open() {
    const panel = document.getElementById('fs-panel-ai');
    if (!panel) return;
    panel.classList.add('fs-float-visible');
    panel.classList.remove('fs-float-minimized');
    this._visible = true;
    this._minimized = false;
    document.getElementById('fs-act-ai-btn')?.classList.add('active');
    document.getElementById('fs-btn-ai-float')?.classList.add('active');
    FS.ai.updateProviderBar();
    // Focus input
    setTimeout(() => document.getElementById('fs-ai-input')?.focus(), 100);
  },

  close() {
    const panel = document.getElementById('fs-panel-ai');
    if (!panel) return;
    panel.classList.remove('fs-float-visible', 'fs-float-minimized');
    this._visible = false;
    this._minimized = false;
    document.getElementById('fs-act-ai-btn')?.classList.remove('active');
    document.getElementById('fs-btn-ai-float')?.classList.remove('active');
  },

  toggle() {
    if (this._visible) this.close();
    else this.open();
  },

  minimize() {
    const panel = document.getElementById('fs-panel-ai');
    if (!panel) return;
    this._minimized = !this._minimized;
    panel.classList.toggle('fs-float-minimized', this._minimized);
    const btn = document.getElementById('fs-float-minimize-btn');
    if (btn) {
      btn.title = this._minimized ? 'Restore' : 'Minimize';
      btn.querySelector('svg')?.replaceWith(...(this._minimized
        ? [Object.assign(document.createElementNS('http://www.w3.org/2000/svg','svg'), {
            innerHTML: '<polyline points="5 15 12 8 19 15"/>',
            ...(s => (s.setAttribute('width','12'), s.setAttribute('height','12'), s.setAttribute('viewBox','0 0 24 24'), s.setAttribute('fill','none'), s.setAttribute('stroke','currentColor'), s.setAttribute('stroke-width','2.5'), s))(document.createElementNS('http://www.w3.org/2000/svg','svg'))
          })]
        : [Object.assign(document.createElementNS('http://www.w3.org/2000/svg','svg'), {
            innerHTML: '<line x1="5" y1="12" x2="19" y2="12"/>',
            ...(s => (s.setAttribute('width','12'), s.setAttribute('height','12'), s.setAttribute('viewBox','0 0 24 24'), s.setAttribute('fill','none'), s.setAttribute('stroke','currentColor'), s.setAttribute('stroke-width','2.5'), s))(document.createElementNS('http://www.w3.org/2000/svg','svg'))
          })]
      ));
    }
  },

  cycleSize() {
    const panel = document.getElementById('fs-panel-ai');
    if (!panel) return;
    this._sizeIdx = (this._sizeIdx + 1) % this._sizes.length;
    panel.dataset.size = this._sizes[this._sizeIdx];
  },

  _initDrag() {
    const panel = document.getElementById('fs-panel-ai');
    const handle = document.getElementById('fs-float-ai-header');
    if (!panel || !handle) return;

    handle.addEventListener('mousedown', e => {
      if (e.target.closest('button')) return;
      this._drag.active = true;
      this._drag.startX = e.clientX;
      this._drag.startY = e.clientY;
      const rect = panel.getBoundingClientRect();
      this._drag.origX = rect.left;
      this._drag.origY = rect.top;
      panel.style.transition = 'none';
      panel.style.right = 'auto';
      panel.style.bottom = 'auto';
      panel.style.left = rect.left + 'px';
      panel.style.top = rect.top + 'px';
      e.preventDefault();
    });

    document.addEventListener('mousemove', e => {
      if (!this._drag.active) return;
      const dx = e.clientX - this._drag.startX;
      const dy = e.clientY - this._drag.startY;
      const x = Math.max(0, Math.min(window.innerWidth - panel.offsetWidth, this._drag.origX + dx));
      const y = Math.max(0, Math.min(window.innerHeight - 40, this._drag.origY + dy));
      panel.style.left = x + 'px';
      panel.style.top = y + 'px';
    });

    document.addEventListener('mouseup', () => {
      if (this._drag.active) {
        this._drag.active = false;
        panel.style.transition = '';
      }
    });
  },

  _initResize() {
    const panel = document.getElementById('fs-panel-ai');
    const handle = document.getElementById('fs-float-ai-resize-handle');
    if (!panel || !handle) return;

    handle.addEventListener('mousedown', e => {
      this._resize.active = true;
      this._resize.startX = e.clientX;
      this._resize.startY = e.clientY;
      this._resize.origW = panel.offsetWidth;
      this._resize.origH = panel.offsetHeight;
      panel.style.transition = 'none';
      e.preventDefault();
    });

    document.addEventListener('mousemove', e => {
      if (!this._resize.active) return;
      const dw = e.clientX - this._resize.startX;
      const dh = e.clientY - this._resize.startY;
      panel.style.width = Math.max(280, this._resize.origW + dw) + 'px';
      panel.style.height = Math.max(300, this._resize.origH + dh) + 'px';
    });

    document.addEventListener('mouseup', () => {
      if (this._resize.active) {
        this._resize.active = false;
        panel.style.transition = '';
      }
    });
  },
};

/* ══════════════════════════════════════════════════════════════════════════
   TOOLS OVERLAY
══════════════════════════════════════════════════════════════════════════ */
FS.toolsOverlay = {
  _ready: false,

  open() {
    const panel = document.getElementById('fs-tools-panel');
    if (!panel) return;
    panel.style.display = 'flex';
    document.getElementById('fs-act-tools-btn')?.classList.add('active');
    document.getElementById('fs-btn-tools-overlay')?.classList.add('active');
    if (!this._ready) this._init();
    // Focus first textarea
    setTimeout(() => panel.querySelector('textarea')?.focus(), 100);
  },

  close() {
    const panel = document.getElementById('fs-tools-panel');
    if (panel) panel.style.display = 'none';
    document.getElementById('fs-act-tools-btn')?.classList.remove('active');
    document.getElementById('fs-btn-tools-overlay')?.classList.remove('active');
  },

  _init() {
    this._ready = true;
    const body = document.querySelector('#fs-tools-panel .fs-tools-body');
    if (!body) return;

    // Build accordion groups around existing tab panels
    const groups = [
      { id: 'ft-tab-html',  label: 'HTML Tools',   desc: '7 tools — format, minify, encode/decode, strip, convert, generate' },
      { id: 'ft-tab-css',   label: 'CSS Tools',    desc: '5 tools — format, minify, validate, SCSS, extract variables' },
      { id: 'ft-tab-js',    label: 'JS Tools',     desc: '6 tools — format, minify, syntax check, escape, convert, run' },
      { id: 'ft-tab-diff',  label: 'Diff Checker', desc: 'Side-by-side text and code comparison' },
    ];

    // Save references BEFORE clearing — innerHTML = '' removes them from the DOM
    const tabEls = {};
    groups.forEach(g => { tabEls[g.id] = document.getElementById(g.id); });

    body.innerHTML = '';

    groups.forEach((g, i) => {
      const tabEl = tabEls[g.id];
      if (!tabEl) return;

      const acc = document.createElement('div');
      acc.className = 'fs-accordion open';

      const hd = document.createElement('div');
      hd.className = 'fs-accordion-hd';
      hd.innerHTML = `
        <div class="fs-accordion-hd-left">
          <svg class="fs-accordion-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          <span class="fs-accordion-label">${g.label}</span>
          <span class="fs-accordion-desc">${g.desc}</span>
        </div>
        <div class="fs-accordion-hd-right">
          <kbd class="fs-accordion-kbd">${g.label.replace(' Tools','').replace(' Checker','')}</kbd>
        </div>
      `;
      hd.addEventListener('click', () => {
        const isOpen = acc.classList.contains('open');
        acc.classList.toggle('open', !isOpen);
      });

      const bd = document.createElement('div');
      bd.className = 'fs-accordion-bd';

      tabEl.style.display = 'block';
      tabEl.classList.remove('active');
      bd.appendChild(tabEl);

      acc.appendChild(hd);
      acc.appendChild(bd);
      body.appendChild(acc);
    });

    // Expand / Collapse All
    document.getElementById('fs-tools-expand-all')?.addEventListener('click', () => {
      document.querySelectorAll('#fs-tools-panel .fs-accordion').forEach(a => a.classList.add('open'));
    });
    document.getElementById('fs-tools-collapse-all')?.addEventListener('click', () => {
      document.querySelectorAll('#fs-tools-panel .fs-accordion').forEach(a => a.classList.remove('open'));
    });

    // Close on Escape
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && document.getElementById('fs-tools-panel')?.style.display !== 'none') {
        this.close();
      }
    });
  },
};

// Boot
document.addEventListener('DOMContentLoaded', () => FS.init());

})();
