/* Scannable Codes Plugin */
const SC = (() => {

  /* ── State ──────────────────────────────────────────────────────── */
  let _activeTab  = 'scanner';
  let _scanMode   = 'camera';
  let _cameraActive  = false;
  let _scanner       = null;
  let _codeType      = 'qr';
  let _qrPreset      = 'text';
  let _qrInstance    = null;
  let _logoDataUrl   = null;
  let _dotStyle      = 'rounded';
  let _lastResult    = null;
  let _liveTimer     = null;

  /* ── QR Content Presets ──────────────────────────────────────────── */
  const QR_PRESETS = [
    { id:'text',    label:'Text',     icon:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>' },
    { id:'url',     label:'URL',      icon:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>' },
    { id:'email',   label:'Email',    icon:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22 6 12 13 2 6"/></svg>' },
    { id:'phone',   label:'Phone',    icon:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.41 2 2 0 0 1 3.6 1.23h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.82a16 16 0 0 0 5.94 5.94l.86-.86a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 17l.19-.08z"/></svg>' },
    { id:'sms',     label:'SMS',      icon:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>' },
    { id:'wifi',    label:'Wi-Fi',    icon:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>' },
    { id:'vcard',   label:'Contact',  icon:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' },
    { id:'geo',     label:'Location', icon:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>' },
    { id:'event',   label:'Event',    icon:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>' },
  ];

  /* ── Code Type Definitions ────────────────────────────────────────── */
  const TYPES = [
    { id:'qr',         label:'QR Code',     group:'2D Codes',    lib:'qr',        bcid:null,         jsc:null },
    { id:'pdf417',     label:'PDF417',      group:'2D Codes',    lib:'bwip',      bcid:'pdf417',     jsc:null,      placeholder:'Enter text or data\u2026' },
    { id:'datamatrix', label:'Data Matrix', group:'2D Codes',    lib:'bwip',      bcid:'datamatrix', jsc:null,      placeholder:'Enter text or binary data\u2026' },
    { id:'aztec',      label:'Aztec',       group:'2D Codes',    lib:'bwip',      bcid:'azteccode',  jsc:null,      placeholder:'Enter text (used in transit)\u2026' },
    { id:'code128',    label:'Code 128',    group:'1D Standard', lib:'jsbarcode', bcid:null,         jsc:'CODE128', placeholder:'Enter alphanumeric text\u2026' },
    { id:'code39',     label:'Code 39',     group:'1D Standard', lib:'jsbarcode', bcid:null,         jsc:'CODE39',  placeholder:'UPPERCASE letters + digits' },
    { id:'code93',     label:'Code 93',     group:'1D Standard', lib:'bwip',      bcid:'code93',     jsc:null,      placeholder:'Uppercase letters + digits' },
    { id:'codabar',    label:'Codabar',     group:'1D Standard', lib:'jsbarcode', bcid:null,         jsc:'codabar', placeholder:'A123456789A (start/stop: A B C D)' },
    { id:'ean13',      label:'EAN-13',      group:'Retail',      lib:'jsbarcode', bcid:null,         jsc:'EAN13',   placeholder:'12 or 13 digits (e.g. 590123412345)' },
    { id:'ean8',       label:'EAN-8',       group:'Retail',      lib:'jsbarcode', bcid:null,         jsc:'EAN8',    placeholder:'7 or 8 digits' },
    { id:'upca',       label:'UPC-A',       group:'Retail',      lib:'jsbarcode', bcid:null,         jsc:'UPC',     placeholder:'11 or 12 digits' },
    { id:'upce',       label:'UPC-E',       group:'Retail',      lib:'jsbarcode', bcid:null,         jsc:'UPCE',    placeholder:'6 to 8 digits' },
    { id:'itf14',      label:'ITF-14',      group:'Logistics',   lib:'jsbarcode', bcid:null,         jsc:'ITF14',   placeholder:'13 or 14 digits (shipping)' },
    { id:'msi',        label:'MSI Plessey', group:'Logistics',   lib:'jsbarcode', bcid:null,         jsc:'MSI',     placeholder:'Digits (inventory & shelves)' },
    { id:'pharmacode', label:'Pharmacode',  group:'Logistics',   lib:'jsbarcode', bcid:null,         jsc:'pharmacode', placeholder:'Number from 3 to 131070' },
  ];

  /* ── Helpers ──────────────────────────────────────────────────── */
  function el(id) { return document.getElementById(id); }

  function toast(msg, type) {
    const t = document.createElement('div');
    t.className = 'sc-toast' + (type ? ' ' + type : '');
    t.textContent = msg;
    document.body.appendChild(t);
    requestAnimationFrame(() => t.classList.add('show'));
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 260); }, 2800);
  }

  function isUrl(s) { try { return /^https?:\/\//i.test(s.trim()); } catch(e) { return false; } }

  function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function typeById(id) { return TYPES.find(t => t.id === id) || null; }

  function fld(id) { const e = el(id); return e ? e.value.trim() : ''; }
  function chk(id) { const e = el(id); return e ? e.checked : false; }

  /* ── Tab Switching ──────────────────────────────────────────────── */
  function switchTab(id) {
    if (id === _activeTab) return;
    if (_activeTab === 'scanner' && _cameraActive) stopCamera();
    _activeTab = id;
    document.querySelectorAll('.sc-tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === id));
    document.querySelectorAll('.sc-tab-panel').forEach(p => p.classList.toggle('active', p.id === 'sc-tab-' + id));
  }

  /* ── Scanner: Mode ──────────────────────────────────────────────── */
  function switchScanMode(mode) {
    if (mode === _scanMode) return;
    if (_cameraActive) stopCamera();
    clearResult();
    _scanMode = mode;
    el('sc-btn-camera').classList.toggle('active', mode === 'camera');
    el('sc-btn-upload').classList.toggle('active', mode === 'upload');
    el('sc-cam-wrap').style.display    = mode === 'camera' ? '' : 'none';
    el('sc-upload-wrap').style.display = mode === 'upload' ? '' : 'none';
  }

  /* ── Scanner: Camera ────────────────────────────────────────────── */
  async function toggleCamera() { _cameraActive ? await stopCamera() : await startCamera(); }

  function allFormats() {
    if (!window.Html5QrcodeSupportedFormats) return undefined;
    return Object.values(Html5QrcodeSupportedFormats).filter(v => typeof v === 'number');
  }

  async function startCamera() {
    if (!window.Html5Qrcode) { toast('Scanner library still loading\u2026', 'warning'); return; }
    try {
      el('sc-cam-idle').style.display = 'none';
      el('sc-reader').style.display   = '';
      if (_scanner) { try { await _scanner.stop(); } catch(e){} _scanner.clear(); _scanner = null; }
      el('sc-reader').innerHTML = '';
      const fmts = allFormats();
      _scanner = new Html5Qrcode('sc-reader', fmts ? { formatsToSupport: fmts } : {});
      await _scanner.start(
        { facingMode:'environment' },
        { fps:12, qrbox:{ width:240, height:240 }, aspectRatio:1.0 },
        onCameraScan, () => {}
      );
      _cameraActive = true;
      const btn = el('sc-cam-btn');
      if (btn) { btn.innerHTML = stopCamBtnHtml(); btn.classList.replace('btn-primary','btn-secondary'); }
      el('sc-scan-status').style.display = 'flex';
    } catch(err) {
      el('sc-cam-idle').style.display = '';
      el('sc-reader').style.display   = 'none';
      let msg = 'Camera unavailable.';
      if ((err.message||'').includes('ermission')) msg = 'Camera permission denied \u2014 please allow access.';
      else if ((err.message||'').includes('otFound')) msg = 'No camera found on this device.';
      toast(msg, 'error');
    }
  }

  async function stopCamera() {
    if (_scanner) { try { await _scanner.stop(); } catch(e){} try { _scanner.clear(); } catch(e){} _scanner = null; }
    _cameraActive = false;
    el('sc-reader').innerHTML  = '';
    el('sc-reader').style.display    = 'none';
    el('sc-cam-idle').style.display  = '';
    el('sc-scan-status').style.display = 'none';
    const btn = el('sc-cam-btn');
    if (btn) { btn.innerHTML = startCamBtnHtml(); btn.classList.replace('btn-secondary','btn-primary'); }
  }

  function startCamBtnHtml() { return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>Start Camera'; }
  function stopCamBtnHtml()  { return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><rect x="2" y="2" width="20" height="20" rx="3"/></svg>Stop Camera'; }

  function onCameraScan(text, result) {
    const fmt = (result && result.result && result.result.format && result.result.format.formatName) || 'Code';
    showResult(text, formatName(fmt));
    stopCamera();
  }

  /* ── Scanner: Upload ────────────────────────────────────────────── */
  function handleDragOver(e)  { e.preventDefault(); el('sc-drop-zone').classList.add('dragover'); }
  function handleDragLeave()  { el('sc-drop-zone').classList.remove('dragover'); }
  function handleDrop(e) {
    e.preventDefault(); el('sc-drop-zone').classList.remove('dragover');
    const f = e.dataTransfer.files[0];
    if (f && f.type.startsWith('image/')) scanFile(f);
    else toast('Please drop an image file.', 'warning');
  }
  function handleFileInput(e) { const f = e.target.files[0]; if (f) scanFile(f); e.target.value = ''; }
  function triggerUpload() { el('sc-file-input').click(); }

  async function scanFile(file) {
    clearResult();
    const dz = el('sc-drop-zone');
    const saved = dz.innerHTML;
    dz.innerHTML = '<div class="sc-spinner"></div>';
    try {
      if (!window.Html5Qrcode) throw new Error('Library not ready');
      const tmp = el('sc-tmp-reader') || (() => {
        const d = document.createElement('div'); d.id='sc-tmp-reader'; d.style.display='none';
        document.body.appendChild(d); return d;
      })();
      const fmts = allFormats();
      const fs = new Html5Qrcode('sc-tmp-reader', fmts ? { formatsToSupport: fmts } : {});
      const res = await fs.scanFileV2(file, false);
      try { await fs.stop(); } catch(e){}
      const fmt = (res && res.result && res.result.format && res.result.format.formatName) || 'Code';
      showResult(res.decodedText, formatName(fmt));
    } catch(err) {
      toast('No code detected in this image.', 'warning');
    } finally {
      dz.innerHTML = saved;
    }
  }

  /* ── Scanner: Result ─────────────────────────────────────────────── */
  function formatName(raw) {
    const map = { QR_CODE:'QR Code', AZTEC:'Aztec Code', CODABAR:'Codabar', CODE_39:'Code 39',
      CODE_93:'Code 93', CODE_128:'Code 128', DATA_MATRIX:'Data Matrix', ITF:'ITF',
      EAN_13:'EAN-13', EAN_8:'EAN-8', PDF_417:'PDF417', UPC_A:'UPC-A', UPC_E:'UPC-E',
      RSS_14:'GS1 DataBar', MAXICODE:'MaxiCode' };
    return map[raw] || (raw||'').replace(/_/g,' ');
  }

  function showResult(text, typeName) {
    _lastResult = text;
    el('sc-result-badge').textContent = typeName || 'Code';
    const valEl = el('sc-result-value');
    if (isUrl(text)) {
      valEl.innerHTML = '<a href="' + escHtml(text) + '" target="_blank" rel="noopener noreferrer">' + escHtml(text) + '</a>';
      valEl.className = 'sc-result-value is-url';
    } else {
      valEl.textContent = text;
      valEl.className = 'sc-result-value';
    }
    const card = el('sc-result-card');
    if (card) { card.style.display=''; card.scrollIntoView({ behavior:'smooth', block:'nearest' }); }
  }

  function clearResult() {
    _lastResult = null;
    const card = el('sc-result-card');
    if (card) { card.style.display='none'; el('sc-result-value').textContent=''; }
  }

  function copyResult() {
    if (!_lastResult) return;
    navigator.clipboard.writeText(_lastResult)
      .then(() => toast('Copied!','success'))
      .catch(() => {
        const ta = document.createElement('textarea');
        ta.value = _lastResult; ta.style.cssText='position:fixed;opacity:0';
        document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove();
        toast('Copied!','success');
      });
  }

  /* ── Generator: Type Selection ──────────────────────────────────── */
  function selectType(id) {
    _codeType = id;
    const type = typeById(id);
    if (!type) return;
    document.querySelectorAll('.sc-type-chip').forEach(c => c.classList.toggle('selected', c.dataset.typeId === id));
    renderForm(type);
    clearPreview();
    scheduleLive();
  }

  /* ── Generator: Form Rendering ──────────────────────────────────── */
  function renderForm(type) {
    const wrap  = el('sc-form-container');
    const title = el('sc-form-title');
    if (title) title.textContent = type.label + ' Options';

    if (type.lib === 'qr') {
      wrap.innerHTML = buildQRForm();
      initQRPreset(_qrPreset);
      syncColor('sc-fg-color','sc-fg-hex');
      syncColor('sc-bg-color','sc-bg-hex');
      syncRange('sc-size-range','sc-size-val','px');
      syncRange('sc-logo-size-range','sc-logo-size-val','%', v => Math.round(v*100));
      syncRange('sc-logo-margin-range','sc-logo-margin-val','px');
      initDotOptions();
    } else if (type.lib === 'jsbarcode') {
      wrap.innerHTML = buildBarcodeForm(type);
      syncColor('sc-bc-fg-color','sc-bc-fg-hex');
      syncColor('sc-bc-bg-color','sc-bc-bg-hex');
      syncRange('sc-bc-width-range','sc-bc-width-val','x');
      syncRange('sc-bc-height-range','sc-bc-height-val','px');
      syncRange('sc-bc-fontsize-range','sc-bc-fontsize-val','px');
    } else {
      wrap.innerHTML = buildTwoDForm(type);
      syncRange('sc-td-scale-range','sc-td-scale-val','x');
    }
    bindLiveEvents();
  }

  /* ── QR Preset Switching ─────────────────────────────────────────── */
  function switchQrPreset(id) {
    _qrPreset = id;
    document.querySelectorAll('.sc-qr-preset-btn').forEach(b => b.classList.toggle('active', b.dataset.preset === id));
    const wrap = el('sc-qr-preset-fields');
    if (wrap) {
      wrap.innerHTML = buildPresetFields(id);
      bindLiveEvents();
      scheduleLive();
    }
  }

  function initQRPreset(id) {
    _qrPreset = id;
    document.querySelectorAll('.sc-qr-preset-btn').forEach(b => b.classList.toggle('active', b.dataset.preset === id));
    const wrap = el('sc-qr-preset-fields');
    if (wrap) { wrap.innerHTML = buildPresetFields(id); bindLiveEvents(); }
  }

  /* Build the content fields for the selected preset */
  function buildPresetFields(id) {
    switch(id) {
      case 'text':
        return fField('Text Content', '<textarea id="sc-p-text" rows="3" placeholder="Enter your plain text here\u2026"></textarea>');

      case 'url':
        return fField('Website URL', '<input type="text" id="sc-p-url" placeholder="https://example.com">',
          'Include the full URL with https://');

      case 'email':
        return fField('Email Address', '<input type="text" id="sc-p-email-to" placeholder="recipient@example.com">') +
          fField('Subject (optional)', '<input type="text" id="sc-p-email-sub" placeholder="Hello there">') +
          fField('Body (optional)', '<textarea id="sc-p-email-body" rows="2" placeholder="Hi, I wanted to reach out\u2026"></textarea>');

      case 'phone':
        return fField('Phone Number', '<input type="text" id="sc-p-phone" placeholder="+1 555 000 0000">',
          'Include country code (e.g. +1 for USA)');

      case 'sms':
        return fField('Phone Number', '<input type="text" id="sc-p-sms-phone" placeholder="+1 555 000 0000">') +
          fField('Message (optional)', '<textarea id="sc-p-sms-msg" rows="2" placeholder="Type the pre-filled SMS message\u2026"></textarea>');

      case 'wifi':
        return fField('Network Name (SSID)', '<input type="text" id="sc-p-wifi-ssid" placeholder="MyHomeNetwork">') +
          fField('Password', '<input type="text" id="sc-p-wifi-pass" placeholder="Enter Wi-Fi password">') +
          fField('Security Type', '<select id="sc-p-wifi-auth"><option value="WPA" selected>WPA / WPA2 (most common)</option><option value="WEP">WEP (legacy)</option><option value="">None (open network)</option></select>') +
          '<div class="sc-toggle-row"><input type="checkbox" id="sc-p-wifi-hidden"><label for="sc-p-wifi-hidden">Hidden network (not broadcasting SSID)</label></div>';

      case 'vcard':
        return '<div class="sc-field-row">' +
            fField('First Name', '<input type="text" id="sc-p-vc-first" placeholder="John">') +
            fField('Last Name', '<input type="text" id="sc-p-vc-last" placeholder="Doe">') +
          '</div>' +
          fField('Organization', '<input type="text" id="sc-p-vc-org" placeholder="ACME Inc.">') +
          fField('Job Title', '<input type="text" id="sc-p-vc-title" placeholder="Product Manager">') +
          fField('Phone', '<input type="text" id="sc-p-vc-phone" placeholder="+1 555 000 0000">') +
          fField('Email', '<input type="text" id="sc-p-vc-email" placeholder="john@example.com">') +
          fField('Website', '<input type="text" id="sc-p-vc-url" placeholder="https://johndoe.com">') +
          fField('Address', '<input type="text" id="sc-p-vc-addr" placeholder="123 Main St, New York, NY 10001">');

      case 'geo':
        return '<div class="sc-field-row">' +
            fField('Latitude', '<input type="text" id="sc-p-geo-lat" placeholder="40.7128">') +
            fField('Longitude', '<input type="text" id="sc-p-geo-lng" placeholder="-74.0060">') +
          '</div>' +
          '<div class="sc-field-hint" style="margin-top:-6px">Tip: Right-click any spot in Google Maps \u2192 copy coordinates</div>';

      case 'event':
        return fField('Event Title', '<input type="text" id="sc-p-ev-title" placeholder="Team Standup">') +
          '<div class="sc-field-row">' +
            fField('Start Date & Time', '<input type="datetime-local" id="sc-p-ev-start">') +
            fField('End Date & Time', '<input type="datetime-local" id="sc-p-ev-end">') +
          '</div>' +
          fField('Location', '<input type="text" id="sc-p-ev-loc" placeholder="Conference Room A, Floor 3">') +
          fField('Description', '<textarea id="sc-p-ev-desc" rows="2" placeholder="Optional event description\u2026"></textarea>');

      default:
        return fField('Content', '<textarea id="sc-p-text" rows="3" placeholder="Enter content\u2026"></textarea>');
    }
  }

  /* Assemble the QR data string from the current preset's fields */
  function assembleQrContent() {
    switch(_qrPreset) {
      case 'text':   return fld('sc-p-text');
      case 'url':    return fld('sc-p-url');
      case 'phone':  { const n = fld('sc-p-phone'); return n ? 'tel:' + n.replace(/\s/g,'') : ''; }
      case 'email': {
        const to  = fld('sc-p-email-to');
        const sub = fld('sc-p-email-sub');
        const bod = fld('sc-p-email-body');
        if (!to) return '';
        let s = 'mailto:' + encodeURIComponent(to);
        const params = [];
        if (sub) params.push('subject=' + encodeURIComponent(sub));
        if (bod) params.push('body=' + encodeURIComponent(bod));
        if (params.length) s += '?' + params.join('&');
        return s;
      }
      case 'sms': {
        const phone = fld('sc-p-sms-phone');
        const msg   = fld('sc-p-sms-msg');
        if (!phone) return '';
        return 'smsto:' + phone.replace(/\s/g,'') + (msg ? ':' + msg : '');
      }
      case 'wifi': {
        const ssid = fld('sc-p-wifi-ssid');
        const pass = fld('sc-p-wifi-pass');
        const auth = fld('sc-p-wifi-auth');
        const hid  = chk('sc-p-wifi-hidden');
        if (!ssid) return '';
        return 'WIFI:T:' + auth + ';S:' + ssid + ';P:' + pass + ';H:' + (hid?'true':'false') + ';;';
      }
      case 'vcard': {
        const first = fld('sc-p-vc-first');
        const last  = fld('sc-p-vc-last');
        const org   = fld('sc-p-vc-org');
        const title = fld('sc-p-vc-title');
        const phone = fld('sc-p-vc-phone');
        const email = fld('sc-p-vc-email');
        const url   = fld('sc-p-vc-url');
        const addr  = fld('sc-p-vc-addr');
        if (!first && !last) return '';
        let v = 'BEGIN:VCARD\r\nVERSION:3.0\r\n';
        v += 'FN:' + (first + ' ' + last).trim() + '\r\n';
        v += 'N:' + last + ';' + first + ';;;\r\n';
        if (org)   v += 'ORG:' + org   + '\r\n';
        if (title) v += 'TITLE:' + title + '\r\n';
        if (phone) v += 'TEL;TYPE=CELL:' + phone + '\r\n';
        if (email) v += 'EMAIL:' + email + '\r\n';
        if (url)   v += 'URL:' + url   + '\r\n';
        if (addr)  v += 'ADR;TYPE=WORK:;;' + addr + ';;;;\r\n';
        v += 'END:VCARD';
        return v;
      }
      case 'geo': {
        const lat = fld('sc-p-geo-lat');
        const lng = fld('sc-p-geo-lng');
        if (!lat || !lng) return '';
        return 'geo:' + lat + ',' + lng;
      }
      case 'event': {
        const title = fld('sc-p-ev-title');
        const start = fld('sc-p-ev-start');
        const end   = fld('sc-p-ev-end');
        const loc   = fld('sc-p-ev-loc');
        const desc  = fld('sc-p-ev-desc');
        if (!title) return '';
        function toIcal(dtLocal) {
          if (!dtLocal) return '';
          return dtLocal.replace(/[-:]/g,'').replace('T','T') + 'Z';
        }
        let v = 'BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\n';
        v += 'SUMMARY:' + title + '\r\n';
        if (start) v += 'DTSTART:' + toIcal(start) + '\r\n';
        if (end)   v += 'DTEND:'   + toIcal(end)   + '\r\n';
        if (loc)   v += 'LOCATION:' + loc + '\r\n';
        if (desc)  v += 'DESCRIPTION:' + desc + '\r\n';
        v += 'END:VEVENT\r\nEND:VCALENDAR';
        return v;
      }
      default: return '';
    }
  }

  /* ── Color / Range helpers ──────────────────────────────────────── */
  function syncColor(colorId, hexId) {
    const col = el(colorId), hex = el(hexId);
    if (!col || !hex) return;
    col.addEventListener('input', () => { hex.value = col.value; scheduleLive(); });
    hex.addEventListener('input', () => {
      if (/^#[0-9a-f]{6}$/i.test(hex.value)) { col.value = hex.value; scheduleLive(); }
    });
  }

  function syncRange(rangeId, valId, unit, transform) {
    const r = el(rangeId), v = el(valId);
    if (!r || !v) return;
    const upd = () => {
      const raw = parseFloat(r.value);
      v.textContent = (transform ? transform(raw) : raw) + unit;
      scheduleLive();
    };
    r.addEventListener('input', upd);
    upd();
  }

  function initDotOptions() {
    document.querySelectorAll('.sc-dot-opt').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.sc-dot-opt').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        _dotStyle = btn.dataset.dot;
        scheduleLive();
      });
    });
    const cur = document.querySelector('.sc-dot-opt[data-dot="' + _dotStyle + '"]');
    if (cur) cur.classList.add('selected');
  }

  function bindLiveEvents() {
    const container = el('sc-form-container');
    if (!container) return;
    container.querySelectorAll('input, textarea, select').forEach(inp => {
      if (inp.type === 'color' || inp.type === 'range' || inp.type === 'file') return;
      const ev = (inp.tagName === 'SELECT' || inp.type === 'checkbox' || inp.type === 'datetime-local') ? 'change' : 'input';
      inp.addEventListener(ev, scheduleLive);
    });
  }

  /* ── Live Generation (debounced) ─────────────────────────────────── */
  function scheduleLive() { clearTimeout(_liveTimer); _liveTimer = setTimeout(generateCode, 380); }

  function toggleAdvanced() {
    const sec = el('sc-advanced-section'), btn = el('sc-advanced-toggle');
    if (!sec) return;
    const open = sec.classList.toggle('open');
    if (btn) btn.innerHTML = open
      ? '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg> Less options'
      : '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg> More options';
  }

  function trigLogoUpload() { if (el('sc-logo-input')) el('sc-logo-input').click(); }

  function handleLogoInput(e) {
    const file = e.target.files[0]; if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
      _logoDataUrl = ev.target.result;
      const prev = el('sc-logo-thumb'); if (prev) { prev.src=_logoDataUrl; prev.style.display=''; }
      const lbl  = el('sc-logo-label'); if (lbl) lbl.textContent = file.name;
      const rem  = el('sc-logo-remove'); if (rem) rem.style.display='';
      const ext  = el('sc-logo-extra'); if (ext) ext.style.display='';
      scheduleLive();
    };
    reader.readAsDataURL(file);
  }

  function removeLogo() {
    _logoDataUrl = null;
    const prev = el('sc-logo-thumb'); if (prev) { prev.src=''; prev.style.display='none'; }
    const lbl  = el('sc-logo-label'); if (lbl) lbl.textContent='No logo selected';
    const rem  = el('sc-logo-remove'); if (rem) rem.style.display='none';
    const ext  = el('sc-logo-extra'); if (ext) ext.style.display='none';
    const inp  = el('sc-logo-input'); if (inp) inp.value='';
    scheduleLive();
  }

  /* ── Form HTML Builders ──────────────────────────────────────────── */
  function fField(label, input, hint) {
    return '<div class="sc-field"><label>' + label + '</label>' + input +
      (hint ? '<div class="sc-field-hint">' + hint + '</div>' : '') + '</div>';
  }

  function colorRow(colorId, hexId, defaultColor) {
    return '<div class="sc-color-row">' +
      '<input type="color" class="sc-color-swatch" id="' + colorId + '" value="' + defaultColor + '">' +
      '<input type="text" id="' + hexId + '" value="' + defaultColor + '" maxlength="7" placeholder="#000000">' +
      '</div>';
  }

  function rangeRow(rangeId, valId, min, max, val, step, unit, transform) {
    const raw = parseFloat(val);
    const display = (transform ? transform(raw) : raw) + unit;
    return '<div class="sc-range-row">' +
      '<input type="range" id="' + rangeId + '" min="' + min + '" max="' + max + '" value="' + val + '" step="' + step + '">' +
      '<span class="sc-range-val" id="' + valId + '">' + display + '</span>' +
      '</div>';
  }

  function buildQRForm() {
    // Preset picker
    const presetRow = '<div class="sc-field">' +
      '<label>Content Type</label>' +
      '<div class="sc-qr-preset-row">' +
        QR_PRESETS.map(p =>
          '<button type="button" class="sc-qr-preset-btn" data-preset="' + p.id + '" onclick="SC.switchQrPreset(\'' + p.id + '\')">' +
          p.icon + p.label + '</button>'
        ).join('') +
      '</div>' +
    '</div>';

    const presetFields = '<div id="sc-qr-preset-fields">' + buildPresetFields(_qrPreset) + '</div>';

    const advanced =
      '<button class="sc-advanced-toggle" id="sc-advanced-toggle" type="button" onclick="SC.toggleAdvanced()">' +
        '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg> More options' +
      '</button>' +
      '<div class="sc-advanced-section" id="sc-advanced-section">' +
        '<div class="sc-field-row">' +
          fField('Size', rangeRow('sc-size-range','sc-size-val',100,600,300,10,'px')) +
          fField('Error Correction', '<select id="sc-qr-ec"><option value="L">L \u2014 Low (7%)</option><option value="M" selected>M \u2014 Medium (15%)</option><option value="Q">Q \u2014 Quartile (25%)</option><option value="H">H \u2014 High (30%)</option></select>') +
        '</div>' +
        '<div class="sc-field-row">' +
          fField('Foreground', colorRow('sc-fg-color','sc-fg-hex','#000000')) +
          fField('Background', colorRow('sc-bg-color','sc-bg-hex','#ffffff')) +
        '</div>' +
        fField('Dot Style', dotStyleOptions()) +
        '<hr class="sc-divider">' +
        fField('Embed Logo',
          '<div class="sc-logo-row">' +
            '<img id="sc-logo-thumb" class="sc-logo-thumb" src="" style="display:none">' +
            '<div class="sc-logo-meta">' +
              '<span class="sc-logo-name" id="sc-logo-label">No logo selected</span>' +
              '<div style="display:flex;gap:6px">' +
                '<button type="button" class="btn btn-ghost btn-sm" onclick="SC.trigLogoUpload()">Browse</button>' +
                '<button type="button" class="btn btn-ghost btn-sm" id="sc-logo-remove" onclick="SC.removeLogo()" style="display:none">Remove</button>' +
              '</div>' +
            '</div>' +
            '<input type="file" id="sc-logo-input" accept="image/*" style="display:none" onchange="SC.handleLogoInput(event)">' +
          '</div>'
        ) +
        '<div id="sc-logo-extra" style="display:none">' +
          '<div class="sc-field-row">' +
            fField('Logo Size',   rangeRow('sc-logo-size-range','sc-logo-size-val',0.1,0.45,0.25,0.05,'%', v => Math.round(v*100))) +
            fField('Logo Margin', rangeRow('sc-logo-margin-range','sc-logo-margin-val',0,20,5,1,'px')) +
          '</div>' +
        '</div>' +
      '</div>';

    return '<div class="sc-form-body">' + presetRow + presetFields + advanced + '</div>';
  }

  function buildBarcodeForm(type) {
    return '<div class="sc-form-body">' +
      fField('Value', '<input type="text" id="sc-bc-value" placeholder="' + (type.placeholder||'') + '">') +
      '<div class="sc-field-row">' +
        fField('Bar Width', rangeRow('sc-bc-width-range','sc-bc-width-val',1,5,2,0.5,'x')) +
        fField('Height',    rangeRow('sc-bc-height-range','sc-bc-height-val',30,250,100,5,'px')) +
      '</div>' +
      '<div class="sc-field-row">' +
        fField('Font Size',    rangeRow('sc-bc-fontsize-range','sc-bc-fontsize-val',8,40,20,1,'px')) +
        fField('Text Position','<select id="sc-bc-textpos"><option value="bottom" selected>Bottom</option><option value="top">Top</option></select>') +
      '</div>' +
      '<div class="sc-field-row">' +
        fField('Bar Color',  colorRow('sc-bc-fg-color','sc-bc-fg-hex','#000000')) +
        fField('Background', colorRow('sc-bc-bg-color','sc-bc-bg-hex','#ffffff')) +
      '</div>' +
      '<div class="sc-toggle-row"><input type="checkbox" id="sc-bc-showtext" checked><label for="sc-bc-showtext">Show value text below barcode</label></div>' +
    '</div>';
  }

  function buildTwoDForm(type) {
    return '<div class="sc-form-body">' +
      fField('Value', '<textarea id="sc-td-value" rows="4" placeholder="' + (type.placeholder||'') + '"></textarea>') +
      fField('Scale', rangeRow('sc-td-scale-range','sc-td-scale-val',1,12,3,1,'x')) +
      '<div class="sc-toggle-row"><input type="checkbox" id="sc-td-showtext" checked><label for="sc-td-showtext">Include human-readable text</label></div>' +
    '</div>';
  }

  function dotStyleOptions() {
    const dots = [
      { id:'square',         svg:'<rect x="2" y="2" width="10" height="10"/><rect x="16" y="2" width="10" height="10"/><rect x="2" y="16" width="10" height="10"/><rect x="16" y="16" width="10" height="10"/>' },
      { id:'dots',           svg:'<circle cx="7" cy="7" r="5"/><circle cx="21" cy="7" r="5"/><circle cx="7" cy="21" r="5"/><circle cx="21" cy="21" r="5"/>' },
      { id:'rounded',        svg:'<rect x="2" y="2" width="10" height="10" rx="3"/><rect x="16" y="2" width="10" height="10" rx="3"/><rect x="2" y="16" width="10" height="10" rx="3"/><rect x="16" y="16" width="10" height="10" rx="3"/>' },
      { id:'classy',         svg:'<rect x="2" y="2" width="10" height="10" rx="3"/><rect x="16" y="2" width="10" height="10" rx="0"/><rect x="2" y="16" width="10" height="10" rx="0"/><rect x="16" y="16" width="10" height="10" rx="3"/>' },
      { id:'classy-rounded', svg:'<rect x="2" y="2" width="10" height="10" rx="5"/><rect x="16" y="2" width="10" height="10" rx="5"/><rect x="2" y="16" width="10" height="10" rx="5"/><rect x="16" y="16" width="10" height="10" rx="5"/>' },
      { id:'extra-rounded',  svg:'<rect x="1" y="1" width="12" height="12" rx="6"/><rect x="15" y="1" width="12" height="12" rx="6"/><rect x="1" y="15" width="12" height="12" rx="6"/><rect x="15" y="15" width="12" height="12" rx="6"/>' },
    ];
    return '<div class="sc-dot-options">' +
      dots.map(d => '<button type="button" class="sc-dot-opt" data-dot="' + d.id + '" title="' + d.id + '"><svg viewBox="0 0 28 28" fill="currentColor">' + d.svg + '</svg></button>').join('') +
    '</div>';
  }

  /* ── Error display ───────────────────────────────────────────────── */
  function showInputError(inputId, msg) {
    const inp = el(inputId); if (!inp) return;
    inp.classList.add('sc-input-error');
    let errEl = inp.parentNode.querySelector('.sc-field-error-msg');
    if (!errEl) { errEl = document.createElement('div'); errEl.className='sc-field-error-msg'; inp.parentNode.appendChild(errEl); }
    errEl.textContent = '\u26a0 ' + msg;
    inp.focus();
  }

  function clearInputErrors() {
    document.querySelectorAll('.sc-input-error').forEach(i => i.classList.remove('sc-input-error'));
    document.querySelectorAll('.sc-field-error-msg').forEach(m => m.remove());
  }

  /* ── Generation ──────────────────────────────────────────────────── */
  function generateCode() {
    const type = typeById(_codeType);
    if (!type) return;
    clearInputErrors();
    clearPreview();
    try {
      if (type.lib === 'qr')             genQR(type);
      else if (type.lib === 'jsbarcode') genBarcode(type);
      else                               genBwip(type);
    } catch(err) {
      showPreviewError(err.message || 'Generation failed.');
    }
  }

  /* ── QR Generation ───────────────────────────────────────────────── */
  function genQR(type) {
    if (!window.QRCodeStyling) { showPreviewError('QR library not loaded yet, please wait.'); return; }
    const content = assembleQrContent();
    if (!content) { clearPreview(); return; }

    const size       = parseInt((el('sc-size-range')||{value:300}).value);
    const ec         = (el('sc-qr-ec')||{value:'M'}).value;
    const fg         = (el('sc-fg-color')||{value:'#000000'}).value;
    const bg         = (el('sc-bg-color')||{value:'#ffffff'}).value;
    const logoSize   = parseFloat((el('sc-logo-size-range')||{value:0.25}).value);
    const logoMargin = parseInt((el('sc-logo-margin-range')||{value:5}).value);

    const opts = {
      width:size, height:size, type:'svg', data:content,
      dotsOptions:         { color:fg, type:_dotStyle },
      backgroundOptions:   { color:bg },
      cornersSquareOptions:{ color:fg },
      cornersDotOptions:   { color:fg },
      qrOptions:           { errorCorrectionLevel:ec },
    };
    if (_logoDataUrl) {
      opts.image       = _logoDataUrl;
      opts.imageOptions= { crossOrigin:'anonymous', margin:logoMargin, imageSize:logoSize, hideBackgroundDots:true };
    }
    _qrInstance ? _qrInstance.update(opts) : (_qrInstance = new QRCodeStyling(opts));
    const container = el('sc-qr-output');
    container.innerHTML = '';
    _qrInstance.append(container);
    container.style.display = 'flex';
    el('sc-barcode-svg').style.display   = 'none';
    el('sc-bwip-canvas').style.display   = 'none';
    el('sc-preview-empty').style.display = 'none';
    const svgBtn = el('sc-dl-svg'); if (svgBtn) svgBtn.disabled = false;
    el('sc-download-bar').style.display = '';
    updateMeta('QR \u2014 ' + (QR_PRESETS.find(p => p.id===_qrPreset)||{label:''}).label, content.length + ' chars', size + '\xd7' + size + 'px');
  }

  /* ── JsBarcode ────────────────────────────────────────────────────── */
  function genBarcode(type) {
    if (!window.JsBarcode) { showPreviewError('Barcode library not loaded yet.'); return; }
    const value = (el('sc-bc-value')||{}).value || '';
    if (!value.trim()) { clearPreview(); return; }
    const width   = parseFloat((el('sc-bc-width-range')||{value:2}).value);
    const height  = parseInt((el('sc-bc-height-range')||{value:100}).value);
    const fontSize= parseInt((el('sc-bc-fontsize-range')||{value:20}).value);
    const textPos = (el('sc-bc-textpos')||{value:'bottom'}).value;
    const showTxt = (el('sc-bc-showtext')||{checked:true}).checked;
    const fg      = (el('sc-bc-fg-color')||{value:'#000000'}).value;
    const bg      = (el('sc-bc-bg-color')||{value:'#ffffff'}).value;
    const svgEl   = el('sc-barcode-svg');
    svgEl.innerHTML = '';
    try {
      JsBarcode(svgEl, value, { format:type.jsc, width, height, fontSize, displayValue:showTxt, textPosition:textPos, lineColor:fg, background:bg, margin:16 });
    } catch(err) {
      showInputError('sc-bc-value', 'Invalid value: ' + (err.message||err));
      showPreviewError('Invalid value for ' + type.label);
      return;
    }
    svgEl.style.display = '';
    el('sc-qr-output').style.display='none'; el('sc-bwip-canvas').style.display='none'; el('sc-preview-empty').style.display='none';
    const svgBtn = el('sc-dl-svg'); if (svgBtn) svgBtn.disabled=false;
    el('sc-download-bar').style.display='';
    updateMeta(type.label, value, '');
  }

  /* ── bwip-js ─────────────────────────────────────────────────────── */
  function genBwip(type) {
    if (!window.bwipjs) { showPreviewError('2D code library not loaded yet.'); return; }
    const value = (el('sc-td-value')||{}).value || '';
    if (!value.trim()) { clearPreview(); return; }
    const scale   = parseInt((el('sc-td-scale-range')||{value:3}).value);
    const showTxt = (el('sc-td-showtext')||{checked:true}).checked;
    const canvas  = el('sc-bwip-canvas');
    canvas.width=0; canvas.height=0;
    try {
      bwipjs.toCanvas(canvas, { bcid:type.bcid, text:value, scale, height:20, includetext:showTxt, textxalign:'center', paddingwidth:4, paddingheight:4 });
    } catch(err) {
      showInputError('sc-td-value', err.message||err);
      showPreviewError('Generation error: ' + (err.message||err));
      return;
    }
    canvas.style.display='';
    el('sc-qr-output').style.display='none'; el('sc-barcode-svg').style.display='none'; el('sc-preview-empty').style.display='none';
    const svgBtn = el('sc-dl-svg'); if (svgBtn) svgBtn.disabled=true;
    el('sc-download-bar').style.display='';
    updateMeta(type.label, value, scale+'x scale');
  }

  /* ── Preview helpers ──────────────────────────────────────────────── */
  function clearPreview() {
    const qo=el('sc-qr-output');    if(qo){qo.innerHTML='';qo.style.display='none';}
    const sv=el('sc-barcode-svg'); if(sv){sv.innerHTML='';sv.style.display='none';}
    const cv=el('sc-bwip-canvas'); if(cv){cv.width=0;cv.height=0;cv.style.display='none';}
    const em=el('sc-preview-empty');if(em)em.style.display='';
    const dl=el('sc-download-bar');if(dl)dl.style.display='none';
    const me=el('sc-preview-meta');if(me)me.textContent='';
    const er=el('sc-preview-error');if(er)er.style.display='none';
  }

  function showPreviewError(msg) {
    const em=el('sc-preview-empty');if(em)em.style.display='none';
    let err=el('sc-preview-error');
    if(!err){
      err=document.createElement('div');
      err.id='sc-preview-error'; err.className='sc-preview-error';
      el('sc-preview-box').appendChild(err);
    }
    err.textContent='\u26a0\ufe0f '+msg; err.style.display='';
  }

  function updateMeta(typeName, value, extra) {
    const meta=el('sc-preview-meta');if(!meta)return;
    meta.textContent=typeName+' \xb7 '+(value.length>40?value.substring(0,40)+'\u2026':value)+(extra?' \xb7 '+extra:'');
  }

  /* ── Downloads ────────────────────────────────────────────────────── */
  function downloadCode(format) {
    const type=typeById(_codeType);if(!type){toast('Generate a code first.','warning');return;}
    if(type.lib==='qr'){
      if(!_qrInstance){toast('No code generated yet.','warning');return;}
      _qrInstance.download({name:'qr-code',extension:format}); return;
    }
    if(type.lib==='jsbarcode'){
      if(format==='svg') dlBarcodeSVG(type.label);
      else dlBarcodeRaster(format,type.label);
      return;
    }
    if(type.lib==='bwip'){
      if(format==='svg'){toast('SVG not available for '+type.label+'. Use PNG or JPEG.','warning');return;}
      dlCanvasRaster(el('sc-bwip-canvas'),format,type.id);
    }
  }

  function dlBarcodeSVG(name){
    const svgEl=el('sc-barcode-svg');if(!svgEl||!svgEl.innerHTML){toast('Generate a barcode first.','warning');return;}
    const blob=new Blob([new XMLSerializer().serializeToString(svgEl)],{type:'image/svg+xml'});
    trigDl(URL.createObjectURL(blob),slugify(name)+'.svg');
  }

  function dlBarcodeRaster(format,name){
    const svgEl=el('sc-barcode-svg');if(!svgEl||!svgEl.innerHTML){toast('Generate first.','warning');return;}
    const url=URL.createObjectURL(new Blob([new XMLSerializer().serializeToString(svgEl)],{type:'image/svg+xml'}));
    const img=new Image();
    img.onload=()=>{
      const c=document.createElement('canvas');
      c.width=svgEl.viewBox.baseVal.width||svgEl.getBoundingClientRect().width||400;
      c.height=svgEl.viewBox.baseVal.height||svgEl.getBoundingClientRect().height||150;
      const ctx=c.getContext('2d');
      if(format==='jpeg'){ctx.fillStyle='#fff';ctx.fillRect(0,0,c.width,c.height);}
      ctx.drawImage(img,0,0,c.width,c.height);
      URL.revokeObjectURL(url);
      c.toBlob(b=>{if(b)trigDl(URL.createObjectURL(b),slugify(name)+'.'+format);},format==='jpeg'?'image/jpeg':'image/png',0.92);
    };
    img.onerror=()=>{URL.revokeObjectURL(url);toast('Download failed. Try PNG.','error');};
    img.src=url;
  }

  function dlCanvasRaster(canvas,format,name){
    if(!canvas||canvas.width===0){toast('Generate first.','warning');return;}
    if(format==='jpeg'){
      const tmp=document.createElement('canvas');tmp.width=canvas.width;tmp.height=canvas.height;
      const ctx=tmp.getContext('2d');ctx.fillStyle='#fff';ctx.fillRect(0,0,tmp.width,tmp.height);
      ctx.drawImage(canvas,0,0);
      tmp.toBlob(b=>b&&trigDl(URL.createObjectURL(b),name+'.jpeg'),'image/jpeg',0.92);
    }else{
      canvas.toBlob(b=>b&&trigDl(URL.createObjectURL(b),name+'.png'),'image/png');
    }
  }

  function trigDl(url,filename){
    const a=document.createElement('a');a.href=url;a.download=filename;
    document.body.appendChild(a);a.click();document.body.removeChild(a);
    setTimeout(()=>URL.revokeObjectURL(url),5000);
    toast('Download started!','success');
  }

  function slugify(s){return(s||'code').toLowerCase().replace(/\s+/g,'-');}

  /* ── Type icon helper ────────────────────────────────────────────── */
  function typeIcon(t) {
    const icons={
      qr:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="5" y="5" width="3" height="3" fill="currentColor" stroke="none"/><rect x="5" y="16" width="3" height="3" fill="currentColor" stroke="none"/><rect x="16" y="5" width="3" height="3" fill="currentColor" stroke="none"/></svg>',
      pdf417:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="2" height="14"/><rect x="6" y="5" width="3" height="14"/><rect x="11" y="5" width="1" height="14"/><rect x="14" y="5" width="3" height="14"/><rect x="19" y="5" width="3" height="14"/></svg>',
      datamatrix:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="1"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>',
      aztec:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/></svg>',
    };
    const bar='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="5" x2="4" y2="19"/><line x1="7" y1="5" x2="7" y2="19"/><line x1="10" y1="5" x2="10" y2="19"/><line x1="13" y1="5" x2="13" y2="19"/><line x1="16" y1="5" x2="16" y2="19"/><line x1="19" y1="5" x2="19" y2="19"/></svg>';
    return icons[t.id]||bar;
  }

  /* ── Init ────────────────────────────────────────────────────────── */
  function init() {
    // Build type chip strip
    const strip=el('sc-type-strip');
    if(strip){
      const groups={},order=[];
      TYPES.forEach(t=>{ if(!groups[t.group]){groups[t.group]=[];order.push(t.group);} groups[t.group].push(t); });
      let html='';
      order.forEach(g=>{
        html+='<div class="sc-type-strip-group"><div class="sc-strip-label">'+g+'</div><div class="sc-strip-row">';
        groups[g].forEach(t=>{
          html+='<button type="button" class="sc-type-chip" data-type-id="'+t.id+'" onclick="SC.selectType(\''+t.id+'\')">' + typeIcon(t) + t.label + '</button>';
        });
        html+='</div></div>';
      });
      strip.innerHTML=html;
    }
    selectType('qr');
  }

  /* ── Public API ──────────────────────────────────────────────────── */
  return {
    switchTab, switchScanMode, toggleCamera,
    handleDragOver, handleDragLeave, handleDrop, handleFileInput, triggerUpload,
    selectType, switchQrPreset,
    downloadCode, copyResult, clearResult,
    toggleAdvanced, trigLogoUpload, handleLogoInput, removeLogo,
    init,
  };
})();

document.addEventListener('DOMContentLoaded', SC.init);
