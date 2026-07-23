(function () {
  'use strict';

  const state = window.AWAN_WHITEBOARD;
  if (!state || !state.board) return;

  const svg = document.getElementById('wbCanvas');
  const objectLayer = document.getElementById('wbObjectLayer');
  const draftLayer = document.getElementById('wbDraftLayer');
  const gridRect = document.getElementById('wbGridRect');
  const saveState = document.getElementById('wbSaveState');
  const zoomLabel = document.getElementById('wbZoomLabel');
  const titleInput = document.getElementById('wbTitleInput');
  const boardTitle = document.getElementById('wbBoardTitle');

  const model = {
    objects: Array.isArray(state.objects) ? state.objects : [],
    viewport: Object.assign({ x: 0, y: 0, zoom: 1 }, state.viewport || {}),
    tool: 'select',
    color: '#182230',
    width: 4,
    grid: true,
    snap: false,
    drawing: null,
    history: [],
    future: [],
    dirty: false,
    saveTimer: null,
  };

  function svgPoint(event) {
    const rect = svg.getBoundingClientRect();
    return {
      x: (event.clientX - rect.left - model.viewport.x) / model.viewport.zoom,
      y: (event.clientY - rect.top - model.viewport.y) / model.viewport.zoom,
    };
  }

  function snapPoint(point) {
    if (!model.snap) return point;
    return { x: Math.round(point.x / 12) * 12, y: Math.round(point.y / 12) * 12 };
  }

  function colorFor(object) {
    return object.color || '#182230';
  }

  function svgElement(name, attributes = {}) {
    const element = document.createElementNS('http://www.w3.org/2000/svg', name);
    Object.entries(attributes).forEach(([key, value]) => element.setAttribute(key, String(value)));
    return element;
  }

  function renderObject(object) {
    const color = colorFor(object);
    const width = Number(object.width || 4);
    const opacity = Number(object.opacity || 1);
    const common = {
      fill: 'none',
      stroke: color,
      'stroke-width': width,
      'stroke-linecap': 'round',
      'stroke-linejoin': 'round',
      opacity,
    };
    if (object.type === 'stroke') {
      if (!object.points || object.points.length < 1) return null;
      const polyline = svgElement('polyline', common);
      polyline.setAttribute('points', object.points.map(point => `${Number(point.x)},${Number(point.y)}`).join(' '));
      if (object.tool === 'highlighter') polyline.setAttribute('stroke-linecap', 'square');
      return polyline;
    }
    if (object.type === 'shape') {
      const x = Math.min(object.x, object.x + object.w);
      const y = Math.min(object.y, object.y + object.h);
      const w = Math.abs(object.w);
      const h = Math.abs(object.h);
      if (object.shape === 'circle') {
        return svgElement('ellipse', { ...common, cx: object.x + object.w / 2, cy: object.y + object.h / 2, rx: w / 2, ry: h / 2 });
      }
      if (object.shape === 'line') return svgElement('line', { ...common, x1: object.x, y1: object.y, x2: object.x + object.w, y2: object.y + object.h });
      if (object.shape === 'arrow') {
        const x2 = object.x + object.w, y2 = object.y + object.h;
        const angle = Math.atan2(y2 - object.y, x2 - object.x);
        const size = Math.max(8, width * 3);
        const a = `${x2 - size * Math.cos(angle - Math.PI / 6)},${y2 - size * Math.sin(angle - Math.PI / 6)}`;
        const b = `${x2 - size * Math.cos(angle + Math.PI / 6)},${y2 - size * Math.sin(angle + Math.PI / 6)}`;
        return svgElement('path', { ...common, d: `M ${object.x} ${object.y} L ${x2} ${y2} M ${a} L ${x2} ${y2} L ${b}` });
      }
      return svgElement('rect', { ...common, x, y, width: w, height: h, rx: object.shape === 'rounded' ? 12 : 2 });
    }
    if (object.type === 'text') {
      const text = svgElement('text', { x: object.x, y: object.y, fill: color, 'font-size': Number(object.size || 24), 'font-family': 'Inter, sans-serif' });
      text.textContent = object.text || '';
      return text;
    }
    if (object.type === 'sticky') {
      const group = svgElement('g');
      group.append(
        svgElement('rect', { x: object.x, y: object.y, width: object.w, height: object.h, rx: 5, fill: object.color || '#ffd979', opacity: .96 })
      );
      const text = svgElement('text', { x: object.x + 12, y: object.y + 27, fill: '#5b4b1e', 'font-size': 14, 'font-family': 'Inter, sans-serif' });
      text.textContent = object.text || 'Note';
      group.append(text);
      return group;
    }
    return null;
  }

  function render() {
    const transform = `translate(${model.viewport.x} ${model.viewport.y}) scale(${model.viewport.zoom})`;
    objectLayer.setAttribute('transform', transform);
    draftLayer.setAttribute('transform', transform);
    gridRect.setAttribute('visibility', model.grid ? 'visible' : 'hidden');
    objectLayer.replaceChildren();
    model.objects.forEach(object => {
      const element = renderObject(object);
      if (element) objectLayer.append(element);
    });
    zoomLabel.textContent = `${Math.round(model.viewport.zoom * 100)}%`;
    svg.classList.toggle('is-pan', model.tool === 'pan');
    svg.classList.toggle('is-select', model.tool === 'select');
  }

  function renderDraft(object) {
    const element = renderObject(object);
    draftLayer.replaceChildren();
    if (element) draftLayer.append(element);
  }

  function markDirty() {
    model.dirty = true;
    saveState.textContent = 'Unsaved changes';
    clearTimeout(model.saveTimer);
    model.saveTimer = setTimeout(save, 900);
  }

  function snapshot() {
    return JSON.parse(JSON.stringify({ objects: model.objects, viewport: model.viewport }));
  }

  function remember() {
    model.history.push(snapshot());
    if (model.history.length > 40) model.history.shift();
    model.future = [];
  }

  function restore(snapshotData) {
    model.objects = snapshotData.objects || [];
    model.viewport = Object.assign({ x: 0, y: 0, zoom: 1 }, snapshotData.viewport || {});
    render();
    markDirty();
  }

  function undo() {
    if (!model.history.length) return;
    model.future.push(snapshot());
    restore(model.history.pop());
  }

  function redo() {
    if (!model.future.length) return;
    model.history.push(snapshot());
    restore(model.future.pop());
  }

  async function save() {
    if (!model.dirty) return;
    saveState.textContent = 'Saving…';
    try {
      const response = await fetch(`${window.location.pathname}?action=save_board`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': state.csrf },
        body: JSON.stringify({
          board_id: state.board.id,
          objects: model.objects,
          viewport: model.viewport,
        }),
      });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.error || 'Save failed');
      model.dirty = false;
      saveState.textContent = `Saved ${new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}`;
    } catch (error) {
      saveState.textContent = 'Save failed — retrying';
      clearTimeout(model.saveTimer);
      model.saveTimer = setTimeout(save, 1800);
    }
  }

  function newId() {
    return `wb_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 8)}`;
  }

  function finishDrawing(point) {
    const drawing = model.drawing;
    if (!drawing) return;
    const end = snapPoint(point);
    if (drawing.mode === 'stroke') {
      drawing.object.points.push(end);
      if (drawing.object.points.length > 1) {
        model.objects.push(drawing.object);
        markDirty();
      }
    } else {
      drawing.object.w = end.x - drawing.start.x;
      drawing.object.h = end.y - drawing.start.y;
      if (Math.abs(drawing.object.w) > 4 || Math.abs(drawing.object.h) > 4) {
        model.objects.push(drawing.object);
        markDirty();
      }
    }
    model.drawing = null;
      draftLayer.replaceChildren();
    render();
  }

  function previewDrawing(point) {
    const drawing = model.drawing;
    if (!drawing) return;
    const end = snapPoint(point);
    if (drawing.mode === 'stroke') {
      drawing.object.points.push(end);
      renderDraft(drawing.object);
      return;
    }
    drawing.object.w = end.x - drawing.start.x;
    drawing.object.h = end.y - drawing.start.y;
    renderDraft(drawing.object);
  }

  svg.addEventListener('pointerdown', event => {
    if (event.button !== 0 && event.pointerType === 'mouse') return;
    svg.setPointerCapture(event.pointerId);
    const point = snapPoint(svgPoint(event));
    if (model.tool === 'pan' || event.shiftKey || event.code === 'Space') {
      model.drawing = { mode: 'pan', start: { x: event.clientX, y: event.clientY }, viewport: Object.assign({}, model.viewport) };
      return;
    }
    if (model.tool === 'select') return;
    remember();
    if (model.tool === 'eraser') {
      const hit = model.objects.findIndex(object => {
        if (object.type === 'stroke') return (object.points || []).some(p => Math.hypot(p.x - point.x, p.y - point.y) < 18);
        return object.x !== undefined && point.x >= object.x && point.x <= object.x + (object.w || 0) && point.y >= object.y && point.y <= object.y + (object.h || 0);
      });
      if (hit >= 0) { model.objects.splice(hit, 1); markDirty(); render(); }
      return;
    }
    if (model.tool === 'text' || model.tool === 'sticky') {
      const text = window.prompt(model.tool === 'text' ? 'Text for this board' : 'Sticky note text', '');
      if (text) {
        model.objects.push(model.tool === 'text'
          ? { id: newId(), type: 'text', x: point.x, y: point.y, text: text.slice(0, 500), color: model.color, size: 24 }
          : { id: newId(), type: 'sticky', x: point.x, y: point.y, w: 160, h: 100, text: text.slice(0, 220), color: '#ffd979' });
        markDirty(); render();
      }
      return;
    }
    const shapeTool = ['rect', 'circle', 'line', 'arrow'].includes(model.tool);
    const highlighter = model.tool === 'highlighter';
    model.drawing = {
      mode: shapeTool ? 'shape' : 'stroke',
      start: point,
      object: shapeTool
        ? { id: newId(), type: 'shape', shape: model.tool === 'rect' ? 'rect' : model.tool, x: point.x, y: point.y, w: 0, h: 0, color: model.color, width: model.width }
        : { id: newId(), type: 'stroke', tool: model.tool, points: [point], color: model.color, width: highlighter ? model.width * 3 : model.width, opacity: highlighter ? .34 : 1 },
    };
  });

  svg.addEventListener('pointermove', event => {
    if (!model.drawing) return;
    if (model.drawing.mode === 'pan') {
      model.viewport.x = model.drawing.viewport.x + event.clientX - model.drawing.start.x;
      model.viewport.y = model.drawing.viewport.y + event.clientY - model.drawing.start.y;
      render();
      return;
    }
    previewDrawing(svgPoint(event));
  });

  svg.addEventListener('pointerup', event => {
    if (!model.drawing) return;
    if (model.drawing.mode === 'pan') { model.drawing = null; return; }
    finishDrawing(svgPoint(event));
  });
  svg.addEventListener('pointercancel', () => { model.drawing = null; draftLayer.replaceChildren(); });
  svg.addEventListener('wheel', event => {
    event.preventDefault();
    const before = svgPoint(event);
    const nextZoom = Math.max(.15, Math.min(3.5, model.viewport.zoom * (event.deltaY > 0 ? .9 : 1.1)));
    model.viewport.zoom = nextZoom;
    const rect = svg.getBoundingClientRect();
    model.viewport.x = event.clientX - rect.left - before.x * nextZoom;
    model.viewport.y = event.clientY - rect.top - before.y * nextZoom;
    render();
    markDirty();
  }, { passive: false });

  document.querySelectorAll('.wb-tool').forEach(button => button.addEventListener('click', () => {
    document.querySelectorAll('.wb-tool').forEach(item => item.classList.remove('is-active'));
    button.classList.add('is-active');
    model.tool = button.dataset.tool;
    render();
  }));
  document.querySelectorAll('.wb-color').forEach(button => button.addEventListener('click', () => {
    document.querySelectorAll('.wb-color').forEach(item => item.classList.remove('is-active'));
    button.classList.add('is-active');
    model.color = button.dataset.color;
  }));
  document.getElementById('wbCustomColor').addEventListener('input', event => { model.color = event.target.value; });
  document.getElementById('wbWidth').addEventListener('input', event => {
    model.width = Number(event.target.value);
    document.getElementById('wbWidthLabel').textContent = `${model.width} px`;
  });
  document.getElementById('wbGrid').addEventListener('click', event => {
    model.grid = !model.grid; event.currentTarget.classList.toggle('is-on', model.grid); render();
  });
  document.getElementById('wbSnap').addEventListener('click', event => {
    model.snap = !model.snap; event.currentTarget.classList.toggle('is-on', model.snap);
  });
  document.getElementById('wbZoomIn').addEventListener('click', () => { model.viewport.zoom = Math.min(3.5, model.viewport.zoom * 1.15); render(); markDirty(); });
  document.getElementById('wbZoomOut').addEventListener('click', () => { model.viewport.zoom = Math.max(.15, model.viewport.zoom / 1.15); render(); markDirty(); });
  document.getElementById('wbFit').addEventListener('click', () => { model.viewport = { x: svg.clientWidth / 2, y: svg.clientHeight / 2, zoom: 1 }; render(); markDirty(); });
  document.getElementById('wbUndo').addEventListener('click', undo);
  document.getElementById('wbRedo').addEventListener('click', redo);
  document.getElementById('wbToggleProperties').addEventListener('click', () => document.querySelector('.wb-properties').classList.toggle('is-open'));
  titleInput.addEventListener('change', async () => {
    const title = titleInput.value.trim() || 'Untitled board';
    titleInput.value = title;
    try {
      const body = new URLSearchParams({ action: 'rename_board', board_id: String(state.board.id), title });
      const response = await fetch(`${window.location.pathname}?action=rename_board`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': state.csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
        body,
      });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.error || 'Rename failed');
      boardTitle.textContent = result.title;
    } catch (error) {
      saveState.textContent = 'Rename failed';
    }
  });
  document.getElementById('wbExportBtn').addEventListener('click', () => {
    const clone = svg.cloneNode(true);
    clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
    clone.querySelector('#wbGridRect')?.remove();
    const blob = new Blob([new XMLSerializer().serializeToString(clone)], { type: 'image/svg+xml' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `${(boardTitle.textContent || 'whiteboard').replace(/[^a-z0-9]+/gi, '-').toLowerCase()}.svg`;
    link.click();
    URL.revokeObjectURL(link.href);
  });

  document.addEventListener('keydown', event => {
    if (event.target.matches('input, textarea')) return;
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') { event.preventDefault(); event.shiftKey ? redo() : undo(); return; }
    const keys = { v: 'select', h: 'pan', p: 'pencil', e: 'eraser', t: 'text' };
    if (keys[event.key.toLowerCase()]) document.querySelector(`.wb-tool[data-tool="${keys[event.key.toLowerCase()]}"]`)?.click();
  });

  render();
})();