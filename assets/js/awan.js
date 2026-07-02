/* AWAN Platform — Core JavaScript v1.1 */

// ─── Dark Mode Init (MUST run before DOMContentLoaded to prevent FOUC) ─────
(function() {
  var stored = localStorage.getItem('awan-theme');
  var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  if (stored === 'dark' || (!stored && prefersDark)) {
    document.documentElement.setAttribute('data-theme', 'dark');
  }
})();

document.addEventListener('DOMContentLoaded', function() {
  'use strict';

  // ─── Flash message auto-dismiss ─────────────────────────────────────────
  document.querySelectorAll('.alert[data-dismiss]').forEach(function(el) {
    var delay = parseInt(el.dataset.dismiss, 10) || 4000;
    setTimeout(function() {
      el.style.transition = 'opacity 0.3s ease, max-height 0.3s ease, margin 0.3s ease, padding 0.3s ease';
      el.style.opacity = '0';
      el.style.maxHeight = '0';
      el.style.overflow = 'hidden';
      el.style.marginBottom = '0';
      el.style.padding = '0';
      setTimeout(function() { el.remove(); }, 350);
    }, delay);
  });

  // ─── Confirm dialogs ────────────────────────────────────────────────────
  document.addEventListener('click', function(e) {
    var el = e.target.closest('[data-confirm]');
    if (!el) return;
    if (!confirm(el.dataset.confirm || 'Are you sure?')) {
      e.preventDefault();
      e.stopPropagation();
    }
  });

  // ─── Active nav link ────────────────────────────────────────────────────
  var path = window.location.pathname;
  // Admin sidebar: PHP already sets the correct active class via section param.
  // JS only corrects the <a> tag inside active <li> items (PHP sets active on <li> only).
  document.querySelectorAll('.sidebar-nav-item.active a').forEach(function(link) {
    link.classList.add('active');
  });
  // Frontend nav: exact path match only
  document.querySelectorAll('.front-nav-links a, .mobile-nav-links a').forEach(function(link) {
    var href = link.getAttribute('href');
    if (href && path === href) {
      link.classList.add('active');
    }
  });

  // ─── Form submit loading state ───────────────────────────────────────────
  document.querySelectorAll('form[data-loading]').forEach(function(form) {
    form.addEventListener('submit', function() {
      var btn = form.querySelector('[type=submit]');
      if (btn) {
        btn.disabled = true;
        var orig = btn.innerHTML;
        btn.innerHTML = btn.dataset.loading || 'Please wait…';
        setTimeout(function() { btn.disabled = false; btn.innerHTML = orig; }, 12000);
      }
    });
  });

  // ─── Alert close ────────────────────────────────────────────────────────
  document.querySelectorAll('.alert-close').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var alert = btn.closest('.alert');
      if (alert) alert.remove();
    });
  });

  // ─── Table search ────────────────────────────────────────────────────────
  var tableSearch = document.getElementById('table-search');
  if (tableSearch) {
    tableSearch.addEventListener('input', function() {
      var q = this.value.toLowerCase();
      document.querySelectorAll('.table tbody tr').forEach(function(row) {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  // ─── Password visibility toggle ──────────────────────────────────────────
  document.querySelectorAll('[data-toggle-password]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var target = document.getElementById(btn.dataset.togglePassword);
      if (!target) return;
      target.type = target.type === 'password' ? 'text' : 'password';
      btn.textContent = target.type === 'password' ? 'Show' : 'Hide';
    });
  });

  // ─── Admin Sidebar Mobile Hamburger ──────────────────────────────────────
  var adminHamburger = document.querySelector('.admin-hamburger');
  var adminSidebar   = document.querySelector('.sidebar');
  var sidebarOverlay = document.querySelector('.sidebar-overlay');

  if (adminHamburger && adminSidebar) {
    function openAdminSidebar() {
      adminSidebar.classList.add('mobile-open');
      if (sidebarOverlay) {
        sidebarOverlay.style.display = 'block';
        setTimeout(function() { sidebarOverlay.classList.add('active'); }, 10);
      }
      document.body.style.overflow = 'hidden';
    }
    function closeAdminSidebar() {
      adminSidebar.classList.remove('mobile-open');
      if (sidebarOverlay) {
        sidebarOverlay.classList.remove('active');
        setTimeout(function() { sidebarOverlay.style.display = ''; }, 300);
      }
      document.body.style.overflow = '';
    }
    adminHamburger.addEventListener('click', function() {
      if (adminSidebar.classList.contains('mobile-open')) {
        closeAdminSidebar();
      } else {
        openAdminSidebar();
      }
    });
    if (sidebarOverlay) {
      sidebarOverlay.addEventListener('click', closeAdminSidebar);
    }
  }

  // ─── Frontend Mobile Menu ─────────────────────────────────────────────────
  var frontHamburger = document.querySelector('.front-hamburger');
  var mobileOverlay  = document.querySelector('.mobile-nav-overlay');
  var mobileDrawer   = document.querySelector('.mobile-nav-drawer');
  var mobileClose    = document.querySelector('.mobile-nav-close');

  function openMobileMenu() {
    if (!mobileOverlay || !mobileDrawer) return;
    mobileOverlay.style.display = 'block';
    mobileDrawer.classList.add('active');
    setTimeout(function() { mobileOverlay.classList.add('active'); }, 10);
    document.body.style.overflow = 'hidden';
  }
  function closeMobileMenu() {
    if (!mobileOverlay || !mobileDrawer) return;
    mobileOverlay.classList.remove('active');
    mobileDrawer.classList.remove('active');
    setTimeout(function() { mobileOverlay.style.display = ''; }, 300);
    document.body.style.overflow = '';
  }
  if (frontHamburger) frontHamburger.addEventListener('click', openMobileMenu);
  if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobileMenu);
  if (mobileClose) mobileClose.addEventListener('click', closeMobileMenu);

  // ─── Dark Mode Toggle ─────────────────────────────────────────────────────
  document.querySelectorAll('.theme-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      if (isDark) {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('awan-theme', 'light');
      } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('awan-theme', 'dark');
      }
    });
  });

  // ─── Animated Counters ────────────────────────────────────────────────────
  function animateCounter(el) {
    var target = parseInt(el.getAttribute('data-target') || el.textContent.replace(/\D/g, ''), 10);
    if (isNaN(target) || target === 0) return;
    var suffix = el.getAttribute('data-suffix') || '';
    var duration = 1400;
    var start = 0;
    var startTime = null;
    function step(ts) {
      if (!startTime) startTime = ts;
      var progress = Math.min((ts - startTime) / duration, 1);
      var ease = 1 - Math.pow(1 - progress, 3);
      var value = Math.floor(ease * target);
      el.textContent = value.toLocaleString() + suffix;
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  var counters = document.querySelectorAll('[data-counter]');
  if (counters.length) {
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
          if (entry.isIntersecting) {
            animateCounter(entry.target);
            io.unobserve(entry.target);
          }
        });
      }, { threshold: 0.2 });
      counters.forEach(function(el) { io.observe(el); });
    } else {
      counters.forEach(animateCounter);
    }
  }

  // ─── Back to Top ──────────────────────────────────────────────────────────
  var btt = document.querySelector('.back-to-top');
  if (btt) {
    window.addEventListener('scroll', function() {
      btt.classList.toggle('visible', window.scrollY > 400);
    }, { passive: true });
    btt.addEventListener('click', function(e) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // ─── FAQ Accordion ────────────────────────────────────────────────────────
  document.querySelectorAll('.faq-question').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var answer  = btn.nextElementSibling;
      var isOpen  = btn.classList.contains('open');
      // Close all
      document.querySelectorAll('.faq-question.open').forEach(function(ob) {
        ob.classList.remove('open');
        if (ob.nextElementSibling) ob.nextElementSibling.classList.remove('open');
      });
      if (!isOpen) {
        btn.classList.add('open');
        if (answer) answer.classList.add('open');
      }
    });
  });

  // ─── Global Search (AJAX) ─────────────────────────────────────────────────
  var searchInput   = document.querySelector('.search-box input[name="q"]');
  var searchResults = document.querySelector('.search-results');
  var searchTimer   = null;

  if (searchInput && searchResults) {
    searchInput.addEventListener('input', function() {
      clearTimeout(searchTimer);
      var q = this.value.trim();
      if (q.length < 2) {
        searchResults.classList.remove('active');
        return;
      }
      searchTimer = setTimeout(function() {
        fetch('/api/v1/search?q=' + encodeURIComponent(q), {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          renderSearchResults(data);
        })
        .catch(function() {
          searchResults.classList.remove('active');
        });
      }, 280);
    });
    document.addEventListener('click', function(e) {
      if (!e.target.closest('.search-box')) searchResults.classList.remove('active');
    });
  }

  function renderSearchResults(data) {
    if (!searchResults) return;
    var items = (data && data.data) ? data.data : [];
    if (!items.length) {
      searchResults.innerHTML = '<div class="search-results-empty">No results found</div>';
      searchResults.classList.add('active');
      return;
    }
    var html = '';
    items.slice(0, 8).forEach(function(item) {
      html += '<a href="' + esc(item.url) + '" class="search-result-item">' +
        '<span class="search-result-type">' + esc(item.type) + '</span>' +
        '<div><div class="search-result-title">' + esc(item.title) + '</div>' +
        (item.description ? '<div class="search-result-desc">' + esc(item.description) + '</div>' : '') +
        '</div></a>';
    });
    searchResults.innerHTML = html;
    searchResults.classList.add('active');
  }

  function esc(str) {
    var d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
  }

  // ─── Newsletter AJAX ──────────────────────────────────────────────────────
  var newsletterForm = document.querySelector('.newsletter-form');
  if (newsletterForm) {
    newsletterForm.addEventListener('submit', function(e) {
      e.preventDefault();
      var emailInput = newsletterForm.querySelector('input[type="email"]');
      var btn = newsletterForm.querySelector('button');
      if (!emailInput || !emailInput.value) return;
      if (btn) { btn.disabled = true; btn.textContent = 'Subscribing…'; }
      fetch('/api/v1/newsletter', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ email: emailInput.value })
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          newsletterForm.innerHTML = '<p style="color:var(--color-success);font-weight:600">Thanks for subscribing!</p>';
        } else {
          if (btn) { btn.disabled = false; btn.textContent = 'Subscribe'; }
          alert(data.message || 'Something went wrong. Please try again.');
        }
      })
      .catch(function() {
        if (btn) { btn.disabled = false; btn.textContent = 'Subscribe'; }
      });
    });
  }

  // ─── Report Issue Modal ───────────────────────────────────────────────────
  document.querySelectorAll('[data-open-modal]').forEach(function(trigger) {
    trigger.addEventListener('click', function() {
      var id = trigger.dataset.openModal;
      openModal(id);
    });
  });
  document.querySelectorAll('.modal-close').forEach(function(btn) {
    btn.addEventListener('click', function() {
      closeModal(btn.closest('.modal-overlay'));
    });
  });
  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) closeModal(overlay);
    });
  });
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.active').forEach(function(m) { closeModal(m); });
      closeMobileMenu();
    }
  });

  function openModal(idOrEl) {
    var modal = typeof idOrEl === 'string' ? document.getElementById(idOrEl) : idOrEl;
    if (!modal) return;
    modal.classList.add('active');
    modal.style.display = 'flex';
    requestAnimationFrame(function() { modal.classList.add('show'); });
    document.body.style.overflow = 'hidden';
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove('show');
    setTimeout(function() {
      modal.classList.remove('active');
      modal.style.display = '';
    }, 300);
    document.body.style.overflow = '';
  }

  // Expose openModal globally for inline use
  window.openModal = openModal;
  window.closeModal = closeModal;

  // ─── Tabs ─────────────────────────────────────────────────────────────────
  document.querySelectorAll('[data-tab]').forEach(function(tab) {
    tab.addEventListener('click', function() {
      var target = tab.dataset.tab;
      var parent = tab.closest('[data-tabs]') || document;
      parent.querySelectorAll('[data-tab]').forEach(function(t) { t.classList.remove('active'); });
      parent.querySelectorAll('[data-tab-panel]').forEach(function(p) { p.style.display = 'none'; });
      tab.classList.add('active');
      var panel = parent.querySelector('[data-tab-panel="' + target + '"]');
      if (panel) panel.style.display = '';
    });
  });

  // ─── Copy to clipboard ────────────────────────────────────────────────────
  document.querySelectorAll('[data-copy]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      navigator.clipboard && navigator.clipboard.writeText(btn.dataset.copy).then(function() {
        var orig = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(function() { btn.textContent = orig; }, 1500);
      });
    });
  });

});

// 
// 
// 