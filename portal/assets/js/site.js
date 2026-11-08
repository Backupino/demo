/**
 * Backupino Public Portal & Landing Interactive Script.
 */
(function() {
  'use strict';

  // 1. Theme Mode Management (Dark / Light)
  function initTheme() {
    var stored = localStorage.getItem('bk_theme_mode');
    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    var theme = stored ? stored : (prefersDark ? 'dark' : 'light');

    document.documentElement.setAttribute('data-theme', theme);

    var toggles = document.querySelectorAll('.bk-theme-toggle');
    toggles.forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        var current = document.documentElement.getAttribute('data-theme') || 'light';
        var next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('bk_theme_mode', next);
      });
    });
  }

  // 2. Sticky Header Behavior
  function initStickyHeader() {
    var header = document.querySelector('.bk-site-header');
    if (!header) return;

    var onScroll = function() {
      if (window.scrollY > 15) {
        header.classList.add('is-scrolled');
      } else {
        header.classList.remove('is-scrolled');
      }
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // 3. Language Switcher Dropdown
  function initLangDropdown() {
    var dropdowns = document.querySelectorAll('.bk-lang-dropdown');
    dropdowns.forEach(function(d) {
      var btn = d.querySelector('.bk-lang-trigger');
      if (!btn) return;
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        d.classList.toggle('open');
      });
    });

    document.addEventListener('click', function() {
      dropdowns.forEach(function(d) { d.classList.remove('open'); });
    });
  }

  // 4. Live Animated Pipeline Demo Simulator
  function initPipelineSimulator() {
    var container = document.getElementById('bk-pipeline-demo');
    if (!container) return;

    var fills = container.querySelectorAll('.bk-stage-fill');
    var pcts = container.querySelectorAll('.bk-stage-pct');
    var statFiles = container.querySelector('.bk-stat-files');
    var statRows = container.querySelector('.bk-stat-rows');
    var statSize = container.querySelector('.bk-stat-size');

    var progress = 0; // 0..400
    var paused = false;

    setInterval(function() {
      if (paused) return;
      progress += 1.8 + Math.random() * 2.2;
      if (progress >= 400) {
        paused = true;
        progress = 400;
        updateUI();
        setTimeout(function() {
          progress = 0;
          paused = false;
        }, 2200);
        return;
      }
      updateUI();
    }, 50);

    function updateUI() {
      fills.forEach(function(fill, idx) {
        var start = idx * 100;
        var local = Math.min(100, Math.max(0, progress - start));
        fill.style.width = local + '%';
        if (local >= 100) {
          fill.classList.add('done');
        } else {
          fill.classList.remove('done');
        }
        if (pcts[idx]) {
          pcts[idx].textContent = Math.round(local) + '%';
        }
      });

      if (statFiles) {
        var f = Math.round((progress / 400) * 8420);
        statFiles.textContent = f.toLocaleString();
      }
      if (statRows) {
        var r = Math.round((progress / 400) * 28430);
        statRows.textContent = r.toLocaleString();
      }
      if (statSize) {
        var s = ((progress / 400) * 186.4).toFixed(1);
        statSize.textContent = s + ' MB';
      }
    }
  }

  // 5. Copy Checksum to Clipboard
  function initCopyHash() {
    document.querySelectorAll('.bk-copy-hash-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var hash = this.getAttribute('data-hash');
        if (!hash) return;
        navigator.clipboard.writeText(hash).then(function() {
          var orig = btn.textContent;
          btn.textContent = 'کپی شد! ✓';
          setTimeout(function() { btn.textContent = orig; }, 2000);
        });
      });
    });
  }

  // Initialize on DOMContentLoaded
  document.addEventListener('DOMContentLoaded', function() {
    initTheme();
    initStickyHeader();
    initLangDropdown();
    initPipelineSimulator();
    initCopyHash();
  });
})();
