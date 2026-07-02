<?php
defined('AWAN') or die();
require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../plugins/_sdk.php';
require_once AWAN_ROOT . '/_core/Plugin.php';

$slug = 'frontend-studio';
plugin_track('plugin_view', '/plugins/frontend-studio/', ['plugin_slug' => $slug]);

$css_url = plugin_asset($slug, 'studio.css');
$js_url  = plugin_asset($slug, 'studio.js');
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Frontend Studio — Awan Tools</title>
<meta name="description" content="Professional browser-based frontend IDE. HTML, CSS, JavaScript, live preview, AI assistant, and more.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= htmlspecialchars($css_url) ?>">
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════════════════════
     IDE SHELL
══════════════════════════════════════════════════════════════════════════════ -->
<div id="fs-shell" class="fs-shell">

  <!-- Title Bar -->
  <div class="fs-titlebar" id="fs-titlebar">
    <div class="fs-titlebar-left">
      <a href="/" class="fs-logo" title="Back to Awan Tools">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      </a>
      <div class="fs-menu-bar">
        <button class="fs-menu-btn" data-menu="file">File</button>
        <button class="fs-menu-btn" data-menu="edit">Edit</button>
        <button class="fs-menu-btn" data-menu="view">View</button>
        <button class="fs-menu-btn" data-menu="run">Run</button>
        <button class="fs-menu-btn" data-menu="help">Help</button>
      </div>
    </div>
    <div class="fs-titlebar-center">
      <button class="fs-title-palette" id="fs-title-palette" title="Search commands (Ctrl+Shift+P)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <span id="fs-title-project">Frontend Studio</span>
        <span class="fs-title-kbd">Ctrl+Shift+P</span>
      </button>
    </div>
    <div class="fs-titlebar-right">
      <button class="fs-tb-btn" id="fs-btn-preview-toggle" title="Toggle Preview (Ctrl+Shift+V)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>
      </button>
      <button class="fs-tb-btn fs-tools-trigger-btn" id="fs-btn-tools-overlay" title="Frontend Tools (Ctrl+Shift+T)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        <span class="fs-tb-label">Tools</span>
      </button>
      <button class="fs-tb-btn" id="fs-btn-ai-float" title="AI Assistant (Ctrl+Shift+A)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 6v6l4 2"/><circle cx="20" cy="4" r="3" fill="currentColor" stroke="none"/></svg>
      </button>
      <button class="fs-tb-btn" id="fs-btn-fullscreen" title="Toggle Fullscreen (F11)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
      </button>
    </div>
  </div>

  <!-- Work Area -->
  <div class="fs-workarea" id="fs-workarea">

    <!-- Activity Bar -->
    <div class="fs-actbar" id="fs-actbar">
      <div class="fs-actbar-top">
        <button class="fs-act-btn active" data-panel="explorer" title="Explorer (Ctrl+Shift+E)">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        </button>
        <button class="fs-act-btn" data-panel="search" title="Search (Ctrl+Shift+F)">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </button>
        <button class="fs-act-btn" data-panel="extensions" title="Extensions (Ctrl+Shift+X)">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </button>
        <button class="fs-act-btn" data-panel="snippets" title="Snippets Library (Ctrl+Shift+K)">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="9" y1="12" x2="13" y2="12"/></svg>
        </button>
        <button class="fs-act-btn" id="fs-act-ai-btn" title="AI Assistant (Ctrl+Shift+A)">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 6v6l4 2"/><circle cx="20" cy="4" r="3" fill="currentColor" stroke="none"/></svg>
        </button>
        <button class="fs-act-btn" data-panel="git" title="Source Control (Ctrl+Shift+G)">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="18" r="3"/><circle cx="6" cy="6" r="3"/><circle cx="18" cy="6" r="3"/><path d="M18 9v2a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V9"/><line x1="12" y1="12" x2="12" y2="15"/></svg>
          <span class="fs-act-badge" id="fs-git-badge" style="display:none">0</span>
        </button>
        <button class="fs-act-btn" id="fs-act-tools-btn" title="Frontend Tools (Ctrl+Shift+T)">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        </button>
      </div>
      <div class="fs-actbar-bottom">
        <button class="fs-act-btn" id="fs-act-collapse-sidebar" title="Toggle Sidebar (Ctrl+B)">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" id="fs-collapse-icon"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/></svg>
        </button>
        <button class="fs-act-btn" data-panel="settings" title="Settings">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        </button>
      </div>
    </div>

    <!-- Sidebar -->
    <div class="fs-sidebar" id="fs-sidebar">
      <div class="fs-sidebar-resize" id="fs-sidebar-resize"></div>

      <!-- Explorer Panel -->
      <div class="fs-panel active" id="fs-panel-explorer">
        <div class="fs-panel-header">
          <span class="fs-panel-title">EXPLORER</span>
          <div class="fs-panel-actions">
            <button class="fs-icon-btn" id="fs-btn-new-file" title="New File">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
            </button>
            <button class="fs-icon-btn" id="fs-btn-new-folder" title="New Folder">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
            </button>
            <button class="fs-icon-btn" id="fs-btn-new-project" title="New Project">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
            <button class="fs-icon-btn" id="fs-btn-collapse-all" title="Collapse All">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
            </button>
            <button class="fs-icon-btn" id="fs-btn-export-zip" title="Export Project as ZIP">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </button>
            <button class="fs-icon-btn" id="fs-btn-import-zip" title="Import ZIP">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </button>
          </div>
        </div>
        <div class="fs-explorer-content" id="fs-explorer-content">
          <!-- Project switcher + file tree rendered here by JS -->
        </div>
      </div>

      <!-- Search Panel -->
      <div class="fs-panel" id="fs-panel-search">
        <div class="fs-panel-header">
          <span class="fs-panel-title">SEARCH</span>
        </div>
        <div class="fs-search-box">
          <div class="fs-search-input-wrap">
            <input type="text" id="fs-search-query" placeholder="Search in project..." autocomplete="off" spellcheck="false">
            <div class="fs-search-opts">
              <button class="fs-search-opt-btn" id="fs-search-case" title="Match Case" data-active="false">Aa</button>
              <button class="fs-search-opt-btn" id="fs-search-regex" title="Use Regex" data-active="false">.*</button>
            </div>
          </div>
          <div class="fs-search-input-wrap">
            <input type="text" id="fs-search-replace" placeholder="Replace..." autocomplete="off" spellcheck="false">
            <div class="fs-search-opts">
              <button class="fs-icon-btn" id="fs-search-do-replace" title="Replace All">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
              </button>
            </div>
          </div>
        </div>
        <div class="fs-search-results" id="fs-search-results"></div>
      </div>

      <!-- Extensions Panel -->
      <div class="fs-panel" id="fs-panel-extensions">
        <div class="fs-panel-header">
          <span class="fs-panel-title">EXTENSIONS</span>
          <input type="text" class="fs-panel-search" id="fs-ext-search" placeholder="Search extensions...">
        </div>
        <div class="fs-ext-list" id="fs-ext-list"></div>
      </div>

      <!-- Settings Panel -->
      <div class="fs-panel fs-settings-panel" id="fs-panel-settings">
        <div class="fs-panel-header">
          <span class="fs-panel-title">SETTINGS</span>
        </div>
        <div class="fs-settings-content" id="fs-settings-content"></div>
      </div>

      <!-- Git Panel -->
      <div class="fs-panel fs-git-panel" id="fs-panel-git">
        <div class="fs-panel-header">
          <span class="fs-panel-title">SOURCE CONTROL</span>
          <div class="fs-panel-actions" id="fs-git-header-actions"></div>
        </div>
        <div id="fs-git-panel-content" class="fs-git-panel-content"></div>
      </div>

      <!-- Snippets Panel -->
      <div class="fs-panel fs-snip-panel" id="fs-panel-snippets">
        <div class="fs-panel-header">
          <span class="fs-panel-title">SNIPPETS</span>
          <div class="fs-panel-actions">
            <button class="fs-icon-btn" id="fs-snip-save-sel" title="Save Selection as Snippet">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
          </div>
        </div>
        <div class="fs-snip-toolbar">
          <input type="text" class="fs-snip-search" id="fs-snip-search" placeholder="Search snippets…" autocomplete="off" spellcheck="false">
          <div class="fs-snip-cats" id="fs-snip-cats">
            <button class="fs-snip-cat active" data-cat="all">All</button>
            <button class="fs-snip-cat" data-cat="html">HTML</button>
            <button class="fs-snip-cat" data-cat="css">CSS</button>
            <button class="fs-snip-cat" data-cat="js">JS</button>
            <button class="fs-snip-cat" data-cat="user">Mine</button>
          </div>
        </div>
        <div class="fs-snip-list" id="fs-snip-list"></div>
        <div class="fs-snip-form" id="fs-snip-form" style="display:none">
          <div class="fs-snip-form-row">
            <input type="text" id="fs-snip-form-name" placeholder="Snippet name…" class="fs-snip-form-input">
            <select id="fs-snip-form-lang" class="fs-snip-form-select">
              <option value="html">HTML</option>
              <option value="css">CSS</option>
              <option value="js">JS</option>
            </select>
          </div>
          <input type="text" id="fs-snip-form-desc" placeholder="Description (optional)" class="fs-snip-form-input">
          <textarea id="fs-snip-form-code" class="fs-snip-form-code" placeholder="Paste snippet code here…" spellcheck="false"></textarea>
          <div class="fs-snip-form-btns">
            <button class="fs-btn-primary" id="fs-snip-form-submit">Save Snippet</button>
            <button class="fs-icon-btn" id="fs-snip-form-cancel">Cancel</button>
          </div>
        </div>
        <button class="fs-snip-new-btn" id="fs-snip-new-btn">+ New Snippet</button>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         FLOATING AI ASSISTANT PANEL
    ═══════════════════════════════════════════════════════════ -->
    <div class="fs-float-ai" id="fs-panel-ai">
      <div class="fs-float-ai-header" id="fs-float-ai-header">
        <div class="fs-float-ai-title">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 6v6l4 2"/><circle cx="20" cy="4" r="3" fill="currentColor" stroke="none"/></svg>
          AI Assistant
          <span class="fs-float-ai-provider-chip" id="fs-float-ai-chip"></span>
        </div>
        <div class="fs-float-ai-ctrl">
          <button class="fs-float-ai-ctrl-btn" id="fs-float-minimize-btn" title="Minimize">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
          </button>
          <button class="fs-float-ai-ctrl-btn" id="fs-float-resize-btn" title="Resize">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
          </button>
          <button class="fs-float-ai-ctrl-btn fs-float-ai-ctrl-btn--close" id="fs-float-close-btn" title="Close">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
      </div>
      <div class="fs-float-ai-body" id="fs-float-ai-body">
        <div class="fs-ai-provider-bar" id="fs-ai-provider-bar">
          <span class="fs-ai-provider-label" id="fs-ai-provider-label">No provider configured</span>
          <button class="fs-ai-configure-btn" id="fs-ai-configure-btn">Configure</button>
        </div>
        <div class="fs-ai-actions" id="fs-ai-actions">
          <button class="fs-ai-action-btn" data-action="explain">Explain</button>
          <button class="fs-ai-action-btn" data-action="fix">Fix Bugs</button>
          <button class="fs-ai-action-btn" data-action="improve">Improve</button>
          <button class="fs-ai-action-btn" data-action="refactor">Refactor</button>
          <button class="fs-ai-action-btn" data-action="comment">Comment</button>
          <button class="fs-ai-action-btn" data-action="docs">Docs</button>
        </div>
        <div class="fs-ai-messages" id="fs-ai-messages"></div>
        <div class="fs-ai-input-area">
          <div class="fs-ai-context-row">
            <select id="fs-ai-context" class="fs-ai-context-sel">
              <option value="selection">Selection</option>
              <option value="file" selected>Current File</option>
              <option value="project">Project</option>
            </select>
            <button class="fs-icon-btn" id="fs-ai-settings-btn" title="Configure AI">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </button>
            <button class="fs-icon-btn" id="fs-ai-clear-btn" title="Clear Chat">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </button>
          </div>
          <div class="fs-ai-textarea-wrap">
            <textarea id="fs-ai-input" placeholder="Ask AI anything…" rows="3" spellcheck="false"></textarea>
            <button class="fs-ai-send-btn" id="fs-ai-send">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
          </div>
        </div>
      </div>
      <div class="fs-float-ai-resize-handle" id="fs-float-ai-resize-handle"></div>
    </div>

    <!-- Editor + Preview Container -->
    <div class="fs-editor-container" id="fs-editor-container">

      <!-- Tab Bar -->
      <div class="fs-tabbar" id="fs-tabbar">
        <div class="fs-tabs-scroll" id="fs-tabs-scroll">
          <div class="fs-tabs" id="fs-tabs">
            <!-- Tabs rendered by JS -->
          </div>
        </div>
        <div class="fs-tabbar-actions">
          <button class="fs-icon-btn" id="fs-tab-overflow-btn" title="More tabs">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <button class="fs-icon-btn" id="fs-tab-split-btn" title="Split Editor">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="1"/><line x1="12" y1="3" x2="12" y2="21"/></svg>
          </button>
        </div>
      </div>

      <!-- Editor + Preview Workspace -->
      <div class="fs-workspace" id="fs-workspace">

        <!-- Editor Pane -->
        <div class="fs-editor-pane" id="fs-editor-pane">
          <div class="fs-breadcrumb" id="fs-breadcrumb"></div>
          <div class="fs-editor-wrap" id="fs-editor-wrap">
            <div id="fs-monaco-container" style="width:100%;height:100%;"></div>
            <div class="fs-editor-loading" id="fs-editor-loading">
              <div class="fs-spinner"></div>
              <span>Loading editor…</span>
            </div>
          </div>
          <!-- Welcome / Empty state -->
          <div class="fs-welcome" id="fs-welcome">
            <div class="fs-welcome-inner">
              <div class="fs-welcome-logo">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/><line x1="12" y1="2" x2="12" y2="22"/></svg>
              </div>
              <h1 class="fs-welcome-title">Frontend Studio</h1>
              <p class="fs-welcome-sub">Professional browser-based IDE</p>
              <div class="fs-welcome-actions">
                <button class="fs-welcome-btn primary" id="fs-welcome-new-project">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  New Project
                </button>
                <button class="fs-welcome-btn" id="fs-welcome-open-file">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  Open File
                </button>
              </div>
              <div class="fs-welcome-recent" id="fs-welcome-recent"></div>
              <div id="fs-welcome-templates"></div>
              <div id="fs-welcome-actions"></div>
              <div class="fs-welcome-shortcuts">
                <div class="fs-shortcut-hint"><kbd>Ctrl+Shift+P</kbd> Command Palette</div>
                <div class="fs-shortcut-hint"><kbd>Ctrl+P</kbd> Quick Open</div>
                <div class="fs-shortcut-hint"><kbd>Ctrl+S</kbd> Save</div>
                <div class="fs-shortcut-hint"><kbd>Ctrl+B</kbd> Toggle Sidebar</div>
                <div class="fs-shortcut-hint"><kbd>F5</kbd> Refresh Preview</div>
                <div class="fs-shortcut-hint"><kbd>Shift+Alt+F</kbd> Format</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Resize handle between editor and preview -->
        <div class="fs-preview-resize" id="fs-preview-resize" style="display:none;"></div>

        <!-- Preview Pane -->
        <div class="fs-preview-pane" id="fs-preview-pane" style="display:none;">
          <div class="fs-preview-toolbar">
            <div class="fs-preview-devices">
              <button class="fs-device-btn active" data-device="desktop" title="Desktop">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
              </button>
              <button class="fs-device-btn" data-device="tablet" title="Tablet">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
              </button>
              <button class="fs-device-btn" data-device="phone" title="Phone">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
              </button>
              <button class="fs-device-btn" data-device="responsive" title="Responsive">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
              </button>
            </div>
            <div class="fs-preview-dims" id="fs-preview-dims">1280 × 720</div>
            <div class="fs-preview-controls">
              <button class="fs-icon-btn" id="fs-preview-rotate" title="Rotate">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
              </button>
              <select class="fs-preview-zoom-sel" id="fs-preview-zoom">
                <option value="0.5">50%</option>
                <option value="0.75">75%</option>
                <option value="1" selected>100%</option>
                <option value="1.25">125%</option>
                <option value="1.5">150%</option>
              </select>
              <button class="fs-icon-btn" id="fs-preview-refresh" title="Refresh (F5)">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
              </button>
              <button class="fs-icon-btn" id="fs-preview-pause" title="Pause auto-refresh">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
              </button>
              <button class="fs-icon-btn" id="fs-preview-newwindow" title="Open in new window">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
              </button>
              <button class="fs-icon-btn" id="fs-preview-close" title="Close Preview">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
          </div>
          <div class="fs-preview-frame-wrap" id="fs-preview-frame-wrap">
            <iframe id="fs-preview-iframe" sandbox="allow-scripts allow-same-origin allow-forms allow-modals allow-popups" title="Preview"></iframe>
          </div>
          <div class="fs-preview-link-bar" id="fs-preview-link-bar" style="display:none;">
            <span>Linked assets for this file:</span>
            <div id="fs-preview-links"></div>
            <button class="fs-sm-btn" id="fs-preview-manage-links">Manage Links</button>
          </div>
        </div>
      </div>

      <!-- Bottom Panel -->
      <div class="fs-bottom-panel" id="fs-bottom-panel">
        <div class="fs-bottom-resize" id="fs-bottom-resize"></div>
        <div class="fs-bottom-tabs">
          <button class="fs-bottom-tab active" data-btab="console">Console</button>
          <button class="fs-bottom-tab" data-btab="problems">Problems</button>
          <button class="fs-bottom-tab" data-btab="output">Output</button>
          <div class="fs-bottom-tab-spacer"></div>
          <button class="fs-icon-btn" id="fs-bottom-close" title="Close Panel">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="fs-bottom-content">
          <div class="fs-btab-pane active" id="fs-btab-console">
            <div class="fs-console-output" id="fs-console-output">
              <div class="fs-console-empty">No console output yet. Open a preview to capture logs.</div>
            </div>
          </div>
          <div class="fs-btab-pane" id="fs-btab-problems">
            <div class="fs-problems-list" id="fs-problems-list">
              <div class="fs-console-empty">No problems detected.</div>
            </div>
          </div>
          <div class="fs-btab-pane" id="fs-btab-output">
            <div class="fs-console-output" id="fs-output-log">
              <div class="fs-console-empty">Output will appear here.</div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ══════════════════════════════════════════════════════
         TOOLS OVERLAY — Frontend Code Toolkit
    ═══════════════════════════════════════════════════════ -->
    <div id="fs-tools-panel" class="fs-tools-panel" style="display:none;">
      <div class="fs-tools-header">
        <div class="fs-tools-header-left">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
          <span class="fs-tools-header-title">Frontend Code Tools</span>
          <span class="fs-tools-header-sub">19 tools</span>
        </div>
        <div class="fs-tools-header-right">
          <button class="fs-tools-action-btn" id="fs-tools-expand-all" title="Expand all sections">Expand All</button>
          <button class="fs-tools-action-btn" id="fs-tools-collapse-all" title="Collapse all sections">Collapse All</button>
          <button class="fs-tools-close-btn" id="fs-tools-close-btn" title="Close (Esc)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
      </div>
      <div class="fs-tools-body">

        <!-- ── HTML TOOLS ──────────────────────────────────── -->
        <div id="ft-tab-html" class="ft-tab-panel">
          <div class="ft-tool-grid">
            <div class="ft-tool-card"><div class="ft-tool-card-head"><div class="ft-tool-card-title">HTML Formatter</div><div class="ft-tool-card-desc">Beautify and indent HTML markup</div></div><textarea id="ht-format-in" class="ft-textarea" rows="6" placeholder="Paste HTML here..."></textarea><div class="ft-tool-actions"><button class="ft-btn ft-btn-primary" onclick="FT.runHtml('format')">Format</button><button class="ft-btn" onclick="FT.copyEl('ht-format-out',this)">Copy</button><button class="ft-btn" onclick="FT.dlEl('ht-format-out','formatted.html')">↓</button><button class="ft-btn ft-btn-danger" onclick="FT.clearTool('ht-format-in','ht-format-out')">Clear</button></div><textarea id="ht-format-out" class="ft-textarea ft-textarea-out" rows="6" readonly placeholder="Formatted output..."></textarea></div>
            <div class="ft-tool-card"><div class="ft-tool-card-head"><div class="ft-tool-card-title">HTML Minifier</div><div class="ft-tool-card-desc">Compress HTML by removing whitespace and comments</div></div><textarea id="ht-minify-in" class="ft-textarea" rows="6" placeholder="Paste HTML here..."></textarea><div class="ft-tool-actions"><button class="ft-btn ft-btn-primary" onclick="FT.runHtml('minify')">Minify</button><button class="ft-btn" onclick="FT.copyEl('ht-minify-out',this)">Copy</button><button class="ft-btn" onclick="FT.dlEl('ht-minify-out','minified.html')">↓</button><button class="ft-btn ft-btn-danger" onclick="FT.clearTool('ht-minify-in','ht-minify-out')">Clear</button></div><textarea id="ht-minify-out" class="ft-textarea ft-textarea-out" rows="6" readonly placeholder="Minified output..."></textarea></div>
            <div class="ft-tool-card"><div class="ft-tool-card-head"><div class="ft-tool-card-title">HTML Encoder</div><div class="ft-tool-card-desc">Encode special characters as HTML entities (&amp;lt;, &amp;amp;…)</div></div><textarea id="ht-encode-in" class="ft-textarea" rows="5" placeholder="Text or HTML to encode..."></textarea><div class="ft-tool-actions"><button class="ft-btn ft-btn-primary" onclick="FT.runHtml('encode')">Encode</button><button class="ft-btn" onclick="FT.copyEl('ht-encode-out',this)">Copy</button><button class="ft-btn ft-btn-danger" onclick="FT.clearTool('ht-encode-in','ht-encode-out')">Clear</button></div><textarea id="ht-encode-out" class="ft-textarea ft-textarea-out" rows="5" readonly placeholder="Encoded output..."></textarea></div>
            <div class="ft-tool-card"><div class="ft-tool-card-head"><div class="ft-tool-card-title">HTML Decoder</div><div class="ft-tool-card-desc">Decode HTML entities back to readable characters</div></div><textarea id="ht-decode-in" class="ft-textarea" rows="5" placeholder="&amp;lt;p&amp;gt;Hello&amp;lt;/p&amp;gt;..."></textarea><div class="ft-tool-actions"><button class="ft-btn ft-btn-primary" onclick="FT.runHtml('decode')">Decode</button><button class="ft-btn" onclick="FT.copyEl('ht-decode-out',this)">Copy</button><button class="ft-btn ft-btn-danger" onclick="FT.clearTool('ht-decode-in','ht-decode-out')">Clear</button></div><textarea id="ht-decode-out" class="ft-textarea ft-textarea-out" rows="5" readonly placeholder="Decoded output..."></textarea></div>
            <div class="ft-tool-card"><div class="ft-tool-card-head"><div class="ft-tool-card-title">Strip HTML Tags</div><div class="ft-tool-card-desc">Remove all HTML tags, leaving plain text only</div></div><textarea id="ht-strip-in" class="ft-textarea" rows="5" placeholder="Paste HTML here..."></textarea><div class="ft-tool-actions"><button class="ft-btn ft-btn-primary" onclick="FT.runHtml('strip')">Strip Tags</button><button class="ft-btn" onclick="FT.copyEl('ht-strip-out',this)">Copy</button><button class="ft-btn ft-btn-danger" onclick="FT.clearTool('ht-strip-in','ht-strip-out')">Clear</button></div><textarea id="ht-strip-out" class="ft-textarea ft-textarea-out" rows="5" readonly placeholder="Plain text output..."></textarea></div>
            <div class="ft-tool-card"><div class="ft-tool-card-head"><div class="ft-tool-card-title">HTML → Markdown</div><div class="ft-tool-card-desc">Convert HTML markup to Markdown syntax</div></div><textarea id="ht-tomd-in" class="ft-textarea" rows="5" placeholder="&lt;h1&gt;Title&lt;/h1&gt;&lt;p&gt;Text&lt;/p&gt;..."></textarea><div class="ft-tool-actions"><button class="ft-btn ft-btn-primary" onclick="FT.runHtml('tomd')">Convert</button><button class="ft-btn" onclick="FT.copyEl('ht-tomd-out',this)">Copy</button><button class="ft-btn" onclick="FT.dlEl('ht-tomd-out','output.md')">↓</button><button class="ft-btn ft-btn-danger" onclick="FT.clearTool('ht-tomd-in','ht-tomd-out')">Clear</button></div><textarea id="ht-tomd-out" class="ft-textarea ft-textarea-out" rows="5" readonly placeholder="Markdown output..."></textarea></div>
            <div class="ft-tool-card ft-span-2"><div class="ft-tool-card-head"><div class="ft-tool-card-title">HTML Table Generator</div><div class="ft-tool-card-desc">Generate a ready-to-use HTML table with custom rows and columns</div></div><div class="ft-gen-row"><label class="ft-gen-label">Rows</label><input type="number" id="ht-table-rows" class="ft-input-sm" value="3" min="1" max="20" style="width:64px"><label class="ft-gen-label">Cols</label><input type="number" id="ht-table-cols" class="ft-input-sm" value="4" min="1" max="10" style="width:64px"><label class="ft-gen-label ft-checkbox-label"><input type="checkbox" id="ht-table-head" checked> With &lt;thead&gt;</label><button class="ft-btn ft-btn-primary" onclick="FT.genTable()">Generate</button><button class="ft-btn" onclick="FT.copyEl('ht-table-out',this)">Copy</button></div><textarea id="ht-table-out" class="ft-textarea ft-textarea-out" rows="8" readonly placeholder="Generated table HTML..."></textarea></div>
          </div>
        </div>

        <!-- ── CSS TOOLS ───────────────────────────────────── -->
        <div id="ft-tab-css" class="ft-tab-panel">
          <div class="ft-tool-grid">
            <div class="ft-tool-card"><div class="ft-tool-card-head"><div class="ft-tool-card-title">CSS Formatter</div><div class="ft-tool-card-desc">Beautify and indent CSS with consistent spacing</div></div><textarea id="ct-format-in" class="ft-textarea" rows="7" placeholder="Paste CSS here..."></textarea><div class="ft-tool-actions"><button class="ft-btn ft-btn-primary" onclick="FT.runCss('format')">Format</button><button class="ft-btn" onclick="FT.copyEl('ct-format-out',this)">Copy</button><button class="ft-btn" onclick="FT.dlEl('ct-format-out','style.css')">↓</button><button class="ft-btn ft-btn-danger" onclick="FT.clearTool('ct-format-in','ct-format-out')">Clear</button></div><textarea id="ct-format-out" class="ft-textarea ft-textarea-out" rows="7" readonly placeholder="Formatted CSS..."></textarea></div>
            <div class="ft-tool-card"><div class="ft-tool-card-head"><div class="ft-tool-card-title">CSS Minifier</div><div class="ft-tool-card-desc">Remove comments, whitespace and shorten color values</div></div><textarea id="ct-minify-in" class="ft-textarea" rows="7" placeholder="Paste CSS here..."></textarea><div class="ft-tool-actions"><button class="ft-btn ft-btn-primary" onclick="FT.runCss('minify')">Minify</button><button class="ft-btn" onclick="FT.copyEl('ct-minify-out',this)">Copy</button><button class="ft-btn" onclick="FT.dlEl('ct-minify-out','style.min.css')">↓</button><button class="ft-btn ft-btn-danger" onclick="FT.clearTool('ct-minify-in','ct-minify-out')">Clear</button></div><textarea id="ct-minify-out" class="ft-textarea ft-textarea-out" rows="7" readonly placeholder="Minified CSS..."></textarea></div>
            <div class="ft-tool-card"><div class="ft-tool-card-head"><div class="ft-tool-card-title">CSS Validator</div><div class="ft-tool-card-desc">Basic structural validation — balanced braces, syntax checks</div></div><textarea id="ct-validate-in" class="ft-textarea" rows="7" placeholder="Paste CSS to validate..."></textarea><div class="ft-tool-actions"><button class="ft-btn ft-btn-primary" onclick="FT.runCss('validate')">Validate</button><button class="ft-btn ft-btn-danger" onclick="FT.clearTool('ct-validate-in','ct-validate-out')">Clear</button></div><textarea id="ct-validate-out" class="ft-textarea ft-textarea-out" rows="7" readonly placeholder="Validation results..."></textarea></div>
            <div class="ft-tool-card"><div class="ft-tool-card-head"><div class="ft-tool-card-title">CSS → SCSS</div><div class="ft-tool-card-desc">Convert CSS to SCSS comment style, ready for nesting</div></div><textarea id="ct-toscss-in" class="ft-textarea" rows="7" placeholder="Paste CSS here..."></textarea><div class="ft-tool-actions"><button class="ft-btn ft-btn-primary" onclick="FT.runCss('toscss')">Convert</button><button class="ft-btn" onclick="FT.copyEl('ct-toscss-out',this)">Copy</button><button class="ft-btn" onclick="FT.dlEl('ct-toscss-out','style.scss')">↓</button><button class="ft-btn ft-btn-danger" onclick="FT.clearTool('ct-toscss-in','ct-toscss-out')">Clear</button></div><textarea id="ct-toscss-out" class="ft-textarea ft-textarea-out" rows="7" readonly placeholder="SCSS output..."></textarea></div>
            <div class="ft-tool-card ft-span-2"><div class="ft-tool-card-head"><div class="ft-tool-card-title">CSS Variable Extractor</div><div class="ft-tool-card-desc">Find all CSS custom properties (--variables) and generate a :root block</div></div><textarea id="ct-vars-in" class="ft-textarea" rows="5" placeholder="Paste CSS with var(--*) usages or declarations..."></textarea><div class="ft-tool-actions"><button class="ft-btn ft-btn-primary" onclick="FT.runCss('vars')">Extract Variables</button><button class="ft-btn" onclick="FT.copyEl('ct-vars-out',this)">Copy</button><button class="ft-btn ft-btn-danger" onclick="FT.clearTool('ct-vars-in','ct-vars-out')">Clear</button></div><textarea id="ct-vars-out" class="ft-textarea ft-textarea-out" rows="5" readonly placeholder=":root { --your-vars: here; }"></textarea></div>
          </div>
        </div>

        <!-- ── JS TOOLS ────────────────────────────────────── -->
        <div id="ft-tab-js" class="ft-tab-panel">
          <div class="ft-tool-grid">
            <div class="ft-tool-card"><div class="ft-tool-card-head"><div class="ft-tool-card-title">JS Formatter</div><div class="ft-tool-card-desc">Re-indent JavaScript code based on brace structure</div></div><textarea id="jt-format-in" class="ft-textarea" rows="7" placeholder="Paste JS here..."></textarea><div class="ft-tool-actions"><button class="ft-btn ft-btn-primary" onclick="FT.runJs('format')">Format</button><button class="ft-btn" onclick="FT.copyEl('jt-format-out',this)">Copy</button><button class="ft-btn" onclick="FT.dlEl('jt-format-out','script.js')">↓</button><button class="ft-btn ft-btn-danger" onclick="FT.clearTool('jt-format-in','jt-format-out')">Clear</button></div><textarea id="jt-format-out" class="ft-textarea ft-textarea-out" rows="7" readonly placeholder="Formatted JS..."></textarea></div>
            <div class="ft-tool-card"><div class="ft-tool-card-head"><div class="ft-tool-card-title">JS Minifier</div><div class="ft-tool-card-desc">Strip comments and collapse whitespace from JavaScript</div></div><textarea id="jt-minify-in" class="ft-textarea" rows="7" placeholder="Paste JS here..."></textarea><div class="ft-tool-actions"><button class="ft-btn ft-btn-primary" onclick="FT.runJs('minify')">Minify</button><button class="ft-btn" onclick="FT.copyEl('jt-minify-out',this)">Copy</button><button class="ft-btn" onclick="FT.dlEl('jt-minify-out','script.min.js')">↓</button><button class="ft-btn ft-btn-danger" onclick="FT.clearTool('jt-minify-in','jt-minify-out')">Clear</button></div><textarea id="jt-minify-out" class="ft-textarea ft-textarea-out" rows="7" readonly placeholder="Minified JS..."></textarea></div>
            <div class="ft-tool-card"><div class="ft-tool-card-head"><div class="ft-tool-card-title">Syntax Checker</div><div class="ft-tool-card-desc">Detect JavaScript syntax errors using the browser engine</div></div><textarea id="jt-syntax-in" class="ft-textarea" rows="7" placeholder="Paste JS to check..."></textarea><div class="ft-tool-actions"><button class="ft-btn ft-btn-primary" onclick="FT.runJs('syntax')">Check Syntax</button><button class="ft-btn ft-btn-danger" onclick="FT.clearTool('jt-syntax-in','jt-syntax-out')">Clear</button></div><textarea id="jt-syntax-out" class="ft-textarea ft-textarea-out" rows="7" readonly placeholder="Syntax check results..."></textarea></div>
            <div class="ft-tool-card"><div class="ft-tool-card-head"><div class="ft-tool-card-title">JS Escape / Unescape</div><div class="ft-tool-card-desc">Escape special characters for use in JS strings</div></div><textarea id="jt-escape-in" class="ft-textarea" rows="5" placeholder="Text or JS code..."></textarea><div class="ft-tool-actions"><button class="ft-btn ft-btn-primary" onclick="FT.runJs('escape')">Escape</button><button class="ft-btn" onclick="FT.runJs('unescape')">Unescape</button><button class="ft-btn" onclick="FT.copyEl('jt-escape-out',this)">Copy</button><button class="ft-btn ft-btn-danger" onclick="FT.clearTool('jt-escape-in','jt-escape-out')">Clear</button></div><textarea id="jt-escape-out" class="ft-textarea ft-textarea-out" rows="5" readonly placeholder="Output..."></textarea></div>
            <div class="ft-tool-card"><div class="ft-tool-card-head"><div class="ft-tool-card-title">Variable Name Converter</div><div class="ft-tool-card-desc">Convert between camelCase, snake_case, kebab-case, PascalCase</div></div><textarea id="jt-convert-in" class="ft-textarea" rows="4" placeholder="my variable name&#10;myVariableName&#10;my-variable-name"></textarea><div class="ft-tool-actions"><select id="jt-convert-style" class="ft-select"><option value="camel">camelCase</option><option value="pascal">PascalCase</option><option value="snake">snake_case</option><option value="kebab">kebab-case</option><option value="screaming">SCREAMING_SNAKE</option></select><button class="ft-btn ft-btn-primary" onclick="FT.convertVarNames()">Convert</button><button class="ft-btn" onclick="FT.copyEl('jt-convert-out',this)">Copy</button><button class="ft-btn ft-btn-danger" onclick="FT.clearTool('jt-convert-in','jt-convert-out')">Clear</button></div><textarea id="jt-convert-out" class="ft-textarea ft-textarea-out" rows="4" readonly placeholder="Converted names..."></textarea></div>
            <div class="ft-tool-card ft-span-2"><div class="ft-tool-card-head"><div class="ft-tool-card-title">JS Console Runner</div><div class="ft-tool-card-desc">Execute JavaScript in a sandboxed iframe and capture console output</div></div><textarea id="jt-runner-in" class="ft-textarea" rows="7" placeholder="// JavaScript to run&#10;console.log('Hello!');&#10;console.log(2 + 2);&#10;console.warn('Watch out!');"></textarea><div class="ft-tool-actions"><button class="ft-btn ft-btn-primary" onclick="FT.runJsConsole()">▶ Run</button><button class="ft-btn" onclick="FT.clearConsole()">Clear Output</button></div><div id="jt-runner-out" class="ft-console-output"><div class="ft-console-empty">Output will appear here after running…</div></div></div>
          </div>
        </div>

        <!-- ── DIFF CHECKER ────────────────────────────────── -->
        <div id="ft-tab-diff" class="ft-tab-panel">
          <div class="ft-diff-wrap">
            <div class="ft-diff-editors">
              <div class="ft-diff-col"><div class="ft-diff-col-label">Original / Left</div><textarea id="diff-a" class="ft-textarea ft-diff-ta" placeholder="Paste original text or code here..."></textarea></div>
              <div class="ft-diff-col"><div class="ft-diff-col-label">Modified / Right</div><textarea id="diff-b" class="ft-textarea ft-diff-ta" placeholder="Paste modified text or code here..."></textarea></div>
            </div>
            <div class="ft-diff-toolbar">
              <button class="ft-btn ft-btn-primary" onclick="FT.runDiff()">Compare</button>
              <button class="ft-btn" onclick="FT.clearDiff()">Clear</button>
            </div>
            <div id="diff-out" class="ft-diff-result"><div class="ft-diff-empty">Paste two pieces of text above and click Compare to see the diff.</div></div>
          </div>
        </div>

      </div><!-- /.fs-tools-body -->
    </div><!-- /#fs-tools-panel -->

  </div>

  <!-- Status Bar -->
  <div class="fs-statusbar" id="fs-statusbar">
    <div class="fs-status-left">
      <button class="fs-status-item fs-status-branch" id="fs-status-project" title="Switch Project">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        <span id="fs-status-project-name">No Project</span>
      </button>
      <button class="fs-status-item fs-status-git-branch" id="fs-status-git" title="Source Control" style="display:none" onclick="FS.panels.setActive('git')">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/></svg>
        <span id="fs-status-git-branch-name"></span>
      </button>
      <span class="fs-status-item" id="fs-status-errors" title="Errors">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span id="fs-status-error-count">0</span>
      </span>
      <span class="fs-status-item" id="fs-status-warnings">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
        <span id="fs-status-warn-count">0</span>
      </span>
    </div>
    <div class="fs-status-right">
      <span class="fs-status-item" id="fs-status-autosave">Autosave: On</span>
      <span class="fs-status-item fs-status-sep">|</span>
      <button class="fs-status-item" id="fs-status-lang" title="Change Language">HTML</button>
      <span class="fs-status-sep">|</span>
      <span class="fs-status-item" id="fs-status-encoding">UTF-8</span>
      <span class="fs-status-sep">|</span>
      <button class="fs-status-item" id="fs-status-pos" title="Go to Line">Ln 1, Col 1</button>
      <span class="fs-status-sep">|</span>
      <button class="fs-status-item" id="fs-status-indent" title="Indentation">Spaces: 2</button>
      <span class="fs-status-sep">|</span>
      <span class="fs-status-item" id="fs-status-ai-provider">No AI</span>
    </div>
  </div>

</div>

<!-- Command Palette -->
<div class="fs-palette-overlay" id="fs-palette-overlay" style="display:none;">
  <div class="fs-palette">
    <div class="fs-palette-input-wrap">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="fs-palette-input" placeholder="Type a command or search..." autocomplete="off" spellcheck="false">
      <kbd class="fs-palette-esc">Esc</kbd>
    </div>
    <div class="fs-palette-results" id="fs-palette-results"></div>
  </div>
</div>

<!-- Modal / Dialog -->
<div class="fs-modal-overlay" id="fs-modal-overlay" style="display:none;">
  <div class="fs-modal" id="fs-modal">
    <div class="fs-modal-header">
      <span class="fs-modal-title" id="fs-modal-title"></span>
      <button class="fs-modal-close" id="fs-modal-close">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="fs-modal-body" id="fs-modal-body"></div>
    <div class="fs-modal-footer" id="fs-modal-footer"></div>
  </div>
</div>

<!-- Context Menu -->
<div class="fs-ctx-menu" id="fs-ctx-menu" style="display:none;"></div>

<!-- Toast Notifications -->
<div class="fs-toasts" id="fs-toasts"></div>

<!-- AI Settings Modal -->
<div class="fs-ai-config-overlay" id="fs-ai-config-overlay" style="display:none;">
  <div class="fs-ai-config-modal">
    <div class="fs-modal-header">
      <span class="fs-modal-title">Configure AI Provider</span>
      <button class="fs-modal-close" id="fs-ai-config-close">×</button>
    </div>
    <div class="fs-ai-config-body" id="fs-ai-config-body"></div>
  </div>
</div>

<!-- JSZip for ZIP import/export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<!-- Frontend Code Toolkit CSS (tools panel) -->
<link rel="stylesheet" href="/plugins/frontend-toolkit/assets/ft-tools.css">
<!-- Monaco loader -->
<script>
var require = { paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs' } };
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs/loader.min.js"></script>
<!-- Frontend Code Toolkit JS (tools panel) -->
<script src="/plugins/frontend-toolkit/assets/ft-tools.js"></script>
<script src="<?= htmlspecialchars($js_url) ?>"></script>
</body>
</html>
<?php exit; ?>
