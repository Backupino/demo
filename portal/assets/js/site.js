/**
 * Backupino Public Portal & Landing Master Interactive Controller.
 * Fully ports all interactive features from React: Theme mode, sticky header,
 * mobile drawer, animated pipeline simulator, FAQ accordion, and scroll reveal.
 */
(function() {
  'use strict';

  // 1. Theme Mode Management (Dark / Light)
  function initTheme() {
    var stored = localStorage.getItem('theme');
    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    var theme = stored ? stored : (prefersDark ? 'dark' : 'light');

    document.documentElement.setAttribute('data-theme', theme);
    updateThemeIcons(theme);

    document.querySelectorAll('.theme-toggle-btn').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        var current = document.documentElement.getAttribute('data-theme') || 'light';
        var next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        updateThemeIcons(next);
      });
    });
  }

  function updateThemeIcons(theme) {
    document.querySelectorAll('.theme-icon-sun').forEach(function(el) {
      el.style.display = theme === 'dark' ? 'inline-block' : 'none';
    });
    document.querySelectorAll('.theme-icon-moon').forEach(function(el) {
      el.style.display = theme === 'dark' ? 'none' : 'inline-block';
    });
  }

  // 2. Sticky Header Behavior
  function initStickyHeader() {
    var header = document.querySelector('.site-header');
    if (!header) return;

    var onScroll = function() {
      if (window.scrollY > 8) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // 3. Mobile Navigation Drawer
  function initMobileDrawer() {
    var openBtn = document.querySelector('.hamburger, .mobile-menu-btn');
    var closeBtn = document.querySelector('.mobile-menu-close, .drawer-close-btn');
    var drawer = document.querySelector('.mobile-menu, .mobile-drawer');
    var overlay = document.querySelector('.mobile-menu-backdrop, .drawer-backdrop');

    if (!drawer) return;

    function open() {
      drawer.classList.add('open');
      if (openBtn) openBtn.classList.add('open');
      if (overlay) overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function close() {
      drawer.classList.remove('open');
      if (openBtn) openBtn.classList.remove('open');
      if (overlay) overlay.classList.remove('open');
      document.body.style.overflow = '';
    }

    if (openBtn) {
      openBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (drawer.classList.contains('open')) {
          close();
        } else {
          open();
        }
      });
    }
    if (closeBtn) closeBtn.addEventListener('click', close);
    if (overlay) overlay.addEventListener('click', close);

    document.querySelectorAll('.mobile-nav-links a, .mobile-menu-actions a').forEach(function(link) {
      link.addEventListener('click', close);
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && drawer.classList.contains('open')) {
        close();
      }
    });
  }

  // 4. Language Switcher Dropdown
  function initLangDropdown() {
    var switchers = document.querySelectorAll('.lang-switcher, .lang-dropdown-wrapper');
    switchers.forEach(function(s) {
      var trigger = s.querySelector('button.icon-btn, .lang-dropdown-trigger');
      var menu = s.querySelector('.lang-menu, .lang-dropdown-menu');
      if (!trigger || !menu) return;

      trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        var wasOpen = menu.classList.contains('open');
        document.querySelectorAll('.lang-menu, .lang-dropdown-menu').forEach(function(m) { m.classList.remove('open'); });
        if (!wasOpen) {
          menu.classList.add('open');
          trigger.setAttribute('aria-expanded', 'true');
        } else {
          menu.classList.remove('open');
          trigger.setAttribute('aria-expanded', 'false');
        }
      });
    });

    document.addEventListener('click', function() {
      document.querySelectorAll('.lang-menu, .lang-dropdown-menu').forEach(function(m) { m.classList.remove('open'); });
      document.querySelectorAll('.lang-switcher button, .lang-dropdown-trigger').forEach(function(b) { b.setAttribute('aria-expanded', 'false'); });
    });
  }

  // 5. Live Animated Backup Pipeline Demo
  function initPipelineSimulator() {
    var cards = document.querySelectorAll('.backup-card');
    if (!cards.length) return;

    cards.forEach(function(card) {
      var fills = card.querySelectorAll('.pipeline-fill');
      var pcts = card.querySelectorAll('.pipeline-stage-pct');
      var statFiles = card.querySelector('.stat-val-files');
      var statRows = card.querySelector('.stat-val-rows');
      var statSize = card.querySelector('.stat-val-size');

      var progress = 0; // 0..400
      var paused = false;

      setInterval(function() {
        if (paused) return;
        progress += 1.4 + Math.random() * 2.4;
        if (progress >= 400) {
          paused = true;
          progress = 400;
          update();
          setTimeout(function() {
            progress = 0;
            paused = false;
          }, 1800);
          return;
        }
        update();
      }, 50);

      function update() {
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
          var f = Math.round((progress / 400) * 8421);
          statFiles.textContent = f.toLocaleString();
        }
        if (statRows) {
          var r = Math.round((progress / 400) * 28436);
          statRows.textContent = r.toLocaleString();
        }
        if (statSize) {
          var s = ((progress / 400) * 186.4).toFixed(1);
          statSize.textContent = s + ' MB';
        }
      }
    });
  }

  // 6. FAQ Accordion Interaction
  function initFaqAccordion() {
    document.querySelectorAll('.faq-item').forEach(function(item) {
      var summary = item.querySelector('.faq-summary');
      if (!summary) return;

      summary.addEventListener('click', function(e) {
        e.preventDefault();
        var wasOpen = item.classList.contains('open');
        item.classList.toggle('open');
        var answer = item.querySelector('.faq-answer');
        if (answer) {
          answer.style.display = wasOpen ? 'none' : 'block';
        }
      });
    });
  }

  // 7. Pricing Annual / Monthly Billing Switcher
  function initBillingSwitcher() {
    var toggles = document.querySelectorAll('.billing-toggle-btn');
    if (!toggles.length) return;

    toggles.forEach(function(btn) {
      btn.addEventListener('click', function() {
        var isAnnual = this.getAttribute('data-period') === 'annual';
        toggles.forEach(function(t) { t.classList.remove('active'); });
        this.classList.add('active');

        document.querySelectorAll('.pricing-card').forEach(function(card) {
          var priceEl = card.querySelector('.price-value');
          var periodEl = card.querySelector('.price-period');
          if (!priceEl) return;

          var monthly = priceEl.getAttribute('data-monthly');
          var annual = priceEl.getAttribute('data-annual');
          if (isAnnual && annual) {
            priceEl.textContent = annual;
            if (periodEl) periodEl.textContent = '/ سالانه';
          } else if (monthly) {
            priceEl.textContent = monthly;
            if (periodEl) periodEl.textContent = '/ ماهانه';
          }
        });
      });
    });
  }

  // 8. Copy to Clipboard Utility
  function initCopy() {
    document.querySelectorAll('.copy-trigger').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var text = this.getAttribute('data-copy');
        if (!text) return;
        var self = this;
        navigator.clipboard.writeText(text).then(function() {
          var original = self.innerHTML;
          self.textContent = 'کپی شد! ✓';
          setTimeout(function() { self.innerHTML = original; }, 2000);
        });
      });
    });
  }

  // 9. IntersectionObserver Scroll Reveal
  function initScrollReveal() {
    var elements = document.querySelectorAll('.reveal');
    if (!elements.length) return;

    if (!('IntersectionObserver' in window)) {
      elements.forEach(function(el) { el.classList.add('visible'); });
      return;
    }

    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    elements.forEach(function(el) {
      var delay = el.getAttribute('data-delay');
      if (delay) {
        el.style.setProperty('--reveal-delay', delay + 'ms');
      }
      observer.observe(el);
    });
  }

  // Boot on DOM Ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  function boot() {
    initTheme();
    initStickyHeader();
    initMobileDrawer();
    initLangDropdown();
    initPipelineSimulator();
    initFaqAccordion();
    initBillingSwitcher();
    initCopy();
    initScrollReveal();
  }
})();
