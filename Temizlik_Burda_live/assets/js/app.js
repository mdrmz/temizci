// ============================================================
// Temizci Burada — Genel JavaScript
// ============================================================

// ============================================================
// Reusable confirm modal (replaces native confirm)
// ============================================================
(function () {
  if (window.tbConfirm) return;

  const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'textarea:not([disabled])',
    'select:not([disabled])',
    '[tabindex]:not([tabindex="-1"])'
  ].join(',');

  window.tbConfirm = function tbConfirm(message, options = {}) {
    if (typeof document === 'undefined' || !document.body) {
      return Promise.resolve(window.confirm(message));
    }

    return new Promise((resolve) => {
      const title = options.title || 'Onay Gerekli';
      const confirmText = options.confirmText || 'Devam Et';
      const cancelText = options.cancelText || 'Vazgeç';

      const modal = document.createElement('div');
      modal.className = 'tb-confirm-modal';
      modal.innerHTML = `
        <div class="tb-confirm-card" role="dialog" aria-modal="true" aria-label="${title}">
          <button type="button" class="tb-confirm-close" aria-label="Kapat">&times;</button>
          <div class="tb-confirm-icon">!</div>
          <h3>${title}</h3>
          <p>${message || 'Bu işlemi onaylıyor musunuz?'}</p>
          <div class="tb-confirm-actions">
            <button type="button" class="btn btn-ghost tb-confirm-cancel">${cancelText}</button>
            <button type="button" class="btn btn-primary tb-confirm-approve">${confirmText}</button>
          </div>
        </div>
      `;

      const prevOverflow = document.body.style.overflow;
      document.body.style.overflow = 'hidden';
      document.body.appendChild(modal);
      requestAnimationFrame(() => modal.classList.add('active'));

      const card = modal.querySelector('.tb-confirm-card');
      const closeBtn = modal.querySelector('.tb-confirm-close');
      const cancelBtn = modal.querySelector('.tb-confirm-cancel');
      const approveBtn = modal.querySelector('.tb-confirm-approve');
      const focusables = card ? Array.from(card.querySelectorAll(focusableSelector)) : [];

      const cleanup = (approved) => {
        document.removeEventListener('keydown', keyHandler, true);
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 180);
        document.body.style.overflow = prevOverflow;
        resolve(approved);
      };

      approveBtn?.addEventListener('click', () => cleanup(true));
      cancelBtn?.addEventListener('click', () => cleanup(false));
      closeBtn?.addEventListener('click', () => cleanup(false));
      modal.addEventListener('click', (event) => {
        if (event.target === modal) cleanup(false);
      });

      const keyHandler = (event) => {
        if (event.key === 'Escape') {
          event.preventDefault();
          cleanup(false);
          return;
        }

        if (event.key !== 'Tab' || focusables.length === 0) return;

        const first = focusables[0];
        const last = focusables[focusables.length - 1];

        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      };

      document.addEventListener('keydown', keyHandler, true);
      setTimeout(() => (approveBtn || cancelBtn || closeBtn || card)?.focus(), 10);
    });
  };
})();

document.addEventListener('DOMContentLoaded', () => {

  // ---- Sidebar Mobil Toggle ----
  const hamburger = document.getElementById('hamburger');
  const sidebar   = document.getElementById('appSidebar');
  const overlay   = document.getElementById('sidebarOverlay');

  function openSidebar() {
    sidebar?.classList.add('open');
    overlay?.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('open');
    document.body.style.overflow = '';
  }
  hamburger?.addEventListener('click', openSidebar);
  overlay?.addEventListener('click', closeSidebar);

  // ---- Public navbar mobile behavior (no hamburger) ----
  document.querySelectorAll('.navbar').forEach(navbar => {
    navbar.classList.remove('has-mobile-menu');
    navbar.classList.remove('mobile-open');
  });

  // ---- Active Sidebar Link ----
  const currentPath = window.location.pathname;
  document.querySelectorAll('.sidebar-link').forEach(link => {
    if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').split('/').pop().replace('.php',''))) {
      link.classList.add('active');
    }
  });

  // ---- Flash mesaj otomatik gizle ----
  const flash = document.querySelector('.flash');
  if (flash) {
    setTimeout(() => {
      flash.style.opacity = '0';
      flash.style.transform = 'translateY(-10px)';
      flash.style.transition = 'all 0.4s ease';
      setTimeout(() => flash.remove(), 400);
    }, 4000);
  }

  // ---- Fotoğraf Yükleme Önizleme ----
  function bindImageUploadPreview(input, preview, area, onPreview = null) {
    if (!input || !preview) return;

    const renderPreview = file => {
      if (!file || !file.type || !file.type.startsWith('image/')) return;
      const reader = new FileReader();
      reader.onload = e => {
        preview.src = e.target?.result || '';
        preview.style.display = 'block';
        if (typeof onPreview === 'function') {
          onPreview();
        }
      };
      reader.readAsDataURL(file);
    };

    input.addEventListener('change', () => {
      renderPreview(input.files && input.files[0] ? input.files[0] : null);
    });

    if (!area) return;

    area.addEventListener('dragover', e => {
      e.preventDefault();
      area.classList.add('drag-over');
    });
    area.addEventListener('dragleave', () => area.classList.remove('drag-over'));
    area.addEventListener('drop', e => {
      e.preventDefault();
      area.classList.remove('drag-over');

      if (!e.dataTransfer || !e.dataTransfer.files || !e.dataTransfer.files.length) {
        return;
      }

      const firstFile = e.dataTransfer.files[0];
      if (!firstFile.type || !firstFile.type.startsWith('image/')) {
        return;
      }

      if (window.DataTransfer) {
        const dt = new DataTransfer();
        dt.items.add(firstFile);
        input.files = dt.files;
      } else {
        input.files = e.dataTransfer.files;
      }

      input.dispatchEvent(new Event('change'));
    });
  }

  bindImageUploadPreview(
    document.getElementById('photoInput'),
    document.getElementById('photoPreview'),
    document.querySelector('.photo-upload-area')
  );

  const avatarInitials = document.getElementById('avatarInitials');
  bindImageUploadPreview(
    document.getElementById('avatarInput'),
    document.getElementById('avatarPreview'),
    document.getElementById('avatarUploadArea'),
    () => {
      if (avatarInitials) {
        avatarInitials.style.display = 'none';
      }
    }
  );

  // ---- Form Validasyon ----
  document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', function (e) {
      let valid = true;
      this.querySelectorAll('[required]').forEach(input => {
        const group = input.closest('.form-group');
        const err   = group?.querySelector('.form-error');
        if (!input.value.trim()) {
          valid = false;
          input.style.borderColor = '#ef4444';
          if (err) err.textContent = 'Bu alan zorunludur.';
        } else {
          input.style.borderColor = '';
          if (err) err.textContent = '';
        }
      });
      if (!valid) e.preventDefault();
    });
  });

  // ---- Submit buton loader ----
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function () {
      const btn = this.querySelector('[type="submit"]');
      if (btn && !btn.disabled) {
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="loader"></span> Lütfen bekleyin...';
        setTimeout(() => {
          btn.disabled = false;
          btn.innerHTML = origText;
        }, 8000);
      }
    });
  });

  // ---- Scroll Animasyonları ----
  const observer = new IntersectionObserver(entries => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.classList.add('animate-in');
          entry.target.style.opacity = '1';
        }, i * 80);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.step-card, .listing-card, .stat-card, .cat-item').forEach(el => {
    el.style.opacity = '0';
    observer.observe(el);
  });

  // ---- Karakter sayacı ----
  document.querySelectorAll('textarea[maxlength]').forEach(ta => {
    const max     = parseInt(ta.getAttribute('maxlength'));
    const counter = document.createElement('span');
    counter.className = 'form-hint';
    counter.style.textAlign = 'right';
    counter.style.display = 'block';
    ta.parentNode.insertBefore(counter, ta.nextSibling);
    const update = () => { counter.textContent = `${ta.value.length} / ${max} karakter`; };
    ta.addEventListener('input', update);
    update();
  });

  // ---- Confirm dialog ----
  document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', async function (e) {
      if (this.dataset.confirmApproved === '1') {
        this.dataset.confirmApproved = '0';
        return;
      }

      const submitter = e.submitter || null;
      e.preventDefault();
      const approved = await window.tbConfirm(this.dataset.confirm || 'Bu işlemi onaylıyor musunuz?', {
        title: 'İşlemi Onayla',
        confirmText: 'Evet, Devam Et',
        cancelText: 'Vazgeç'
      });

      if (!approved) return;
      this.dataset.confirmApproved = '1';
      if (typeof this.requestSubmit === 'function') {
        this.requestSubmit(submitter || undefined);
      } else {
        this.submit();
      }
    });
  });

  document.querySelectorAll('a[data-confirm], button[data-confirm]').forEach(el => {
    el.addEventListener('click', async function (e) {
      e.preventDefault();
      const approved = await window.tbConfirm(this.dataset.confirm || 'Bu işlemi onaylıyor musunuz?', {
        title: 'İşlemi Onayla',
        confirmText: 'Evet, Devam Et',
        cancelText: 'Vazgeç'
      });
      if (!approved) return;

      if (this.tagName === 'A' && this.href) {
        window.location.href = this.href;
      } else if (this.tagName === 'BUTTON' && this.form) {
        this.form.requestSubmit(this);
      }
    });
  });

});

// ============================================================
// Notifications Logic
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    const notifBtn = document.getElementById('notifBtn');
    const notifBadge = document.getElementById('notifBadge');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifList = document.getElementById('notifList');

    if (notifBtn && notifDropdown) {
        // Toggle dropdown
        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('active');
            
            // Eğer açıldıysa Fetch yap
            if (notifDropdown.classList.contains('active')) {
                fetchNotifications(true); 
            }
        });

        // Tıklanan yere göre dropdown kapanması
        document.addEventListener('click', (e) => {
            if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
                notifDropdown.classList.remove('active');
            }
        });

        // Polling (Her 30 saniyede bir yeni bildirim var mı diye sessizce kontrol et)
        setInterval(() => fetchNotifications(false), 30000);
        
        // Initial fetch
        fetchNotifications(false);

        // Hepsini okundu işaretle
        document.getElementById('markAllRead')?.addEventListener('click', (e) => {
            e.stopPropagation();
            markNotificationsAsRead();
            notifBadge.style.display = 'none';
            // Listeyi de görsel olarak güncelle
            document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.replace('unread', 'read'));
        });
    }

    function fetchNotifications(markRead) {
        fetch(APP_URL + '/notifications_ajax.php')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Update Badge
                    if (data.unread_count > 0) {
                        notifBadge.textContent = data.unread_count;
                        notifBadge.style.display = 'flex';
                    } else {
                        notifBadge.style.display = 'none';
                    }

                    // Render List
                    renderNotifications(data.notifications);

                    // Eğer dropdown açıkken tıklandıysa ve okunmamış mesaj varsa işaretle
                    if (markRead && data.unread_count > 0) {
                        markNotificationsAsRead();
                        notifBadge.style.display = 'none';
                    }
                }
            })
            .catch(err => console.error('Notification error', err));
    }

    function renderNotifications(items) {
        if (!notifList) return;
        
        if (items.length === 0) {
            notifList.innerHTML = '<div style="padding:15px;text-align:center;color:var(--text-muted);font-size:0.85rem;">Henüz bildiriminiz yok.</div>';
            return;
        }

        notifList.innerHTML = items.map(item => {
            const isReadClass = item.is_read == 1 ? 'read' : 'unread';
            const icon = getNotifIcon(item.type);
            const date = new Date(item.created_at).toLocaleDateString('tr-TR', { hour: '2-digit', minute: '2-digit' });
            
            return `
                <a href="${item.link || '#'}" class="notif-item ${isReadClass}" style="display:flex;gap:12px;padding:12px 14px;border-bottom:1px solid var(--border-light);text-decoration:none;transition:var(--transition);color:inherit;">
                    <div style="width:36px;height:36px;border-radius:50%;background:rgba(108,99,255,0.1);display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;">
                        ${icon}
                    </div>
                    <div>
                        <div style="font-size:0.85rem;margin-bottom:4px;color:var(--text-primary);line-height:1.4;">${item.message}</div>
                        <div style="font-size:0.7rem;color:var(--text-muted);">${date}</div>
                    </div>
                </a>
            `;
        }).join('');
    }

    function markNotificationsAsRead() {
        fetch(APP_URL + '/notifications_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_read' })
        });
    }

    function getNotifIcon(type) {
        switch(type) {
            case 'offer': return '💬';
            case 'message': return '✉️';
            case 'system': return '⚙️';
            case 'review': return '⭐';
            default: return '🔔';
        }
    }
});

// ============================================================
// Marketing Promo Bar + Popups (intro + exit intent)
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    const path = (window.location.pathname || '').toLowerCase();
    if (path.includes('/admin')) return;
    if (window.matchMedia('(max-width: 768px)').matches) return;

    const DAY_MS = 24 * 60 * 60 * 1000;
    const PROMO_HIDE_TS_KEY = 'tb_promo_bar_hide_ts_v1';
    const INTRO_POPUP_TS_KEY = 'tb_intro_popup_ts_v1';
    const EXIT_POPUP_TS_KEY = 'tb_exit_popup_ts_v1';
    const POPUP_SESSION_KEY = 'tb_popup_shown_session_v1';
    const promoCooldownMs = 3 * DAY_MS;
    const popupCooldownMs = 7 * DAY_MS;

    function readTs(key) {
        try {
            const val = parseInt(localStorage.getItem(key) || '0', 10);
            return Number.isFinite(val) ? val : 0;
        } catch (_) {
            return 0;
        }
    }

    function writeTs(key, value) {
        try {
            localStorage.setItem(key, String(value));
        } catch (_) {}
    }

    function shouldShow(key, cooldownMs) {
        return Date.now() - readTs(key) >= cooldownMs;
    }

    function isPopupShownInSession() {
        try {
            return sessionStorage.getItem(POPUP_SESSION_KEY) === '1';
        } catch (_) {
            return false;
        }
    }

    function markPopupShown(key) {
        const now = Date.now();
        writeTs(key, now);
        try {
            sessionStorage.setItem(POPUP_SESSION_KEY, '1');
        } catch (_) {}
    }

    function addPromoBar() {
        if (!shouldShow(PROMO_HIDE_TS_KEY, promoCooldownMs)) return;
        if (document.getElementById('tbPromoBar')) return;

        const promo = document.createElement('div');
        promo.id = 'tbPromoBar';
        promo.className = 'tb-promo-bar';
        promo.innerHTML = `
            <div class="tb-promo-inner">
                <div class="tb-promo-text">
                    <strong>Buralar Şirketi</strong> ile kurumsal temizlik süreçlerinizi daha hızlı ve güvenli yönetin.
                </div>
                <div class="tb-promo-actions">
                    <a class="tb-promo-btn" href="/nasil-calisir">Şirketi Tanı</a>
                    <button class="tb-promo-close" type="button" aria-label="Kapat">&times;</button>
                </div>
            </div>
        `;

        document.body.prepend(promo);
        const closeBtn = promo.querySelector('.tb-promo-close');
        closeBtn?.addEventListener('click', () => {
            writeTs(PROMO_HIDE_TS_KEY, Date.now());
            promo.remove();
        });
    }

    function renderPopup(type) {
        if (document.getElementById('tbMarketingModal')) return;
        if (isPopupShownInSession()) return;

        const isExit = type === 'exit';
        const title = isExit ? 'Çıkmadan Önce Kısa Bir Not' : 'Buralar Şirketi ile Tanışın';
        const desc = isExit
            ? 'Ücretsiz kayıt ile teklifleri ve uzman profilleri tek ekranda karşılaştırabilirsiniz.'
            : 'Kurumsal temizlik ihtiyaçlarınız için ilan, teklif ve takip sürecini tek panelden yönetin.';

        markPopupShown(isExit ? EXIT_POPUP_TS_KEY : INTRO_POPUP_TS_KEY);

        const modal = document.createElement('div');
        modal.id = 'tbMarketingModal';
        modal.className = 'tb-marketing-modal';
        modal.innerHTML = `
            <div class="tb-marketing-card" role="dialog" aria-modal="true" aria-label="${title}">
                <button type="button" class="tb-modal-close" aria-label="Kapat">&times;</button>
                <div class="tb-modal-tag">Kurumsal Tanıtım</div>
                <h3>${title}</h3>
                <p>${desc}</p>
                <ul class="tb-modal-list">
                    <li>Onaylı profiller ve şeffaf teklif akışı</li>
                    <li>İlan, mesaj ve destek sürecini tek yerde yönetim</li>
                    <li>Hızlı başlangıç ve mobil uyumlu panel</li>
                </ul>
                <div class="tb-modal-actions">
                    <a href="/nasil-calisir" class="tb-modal-btn tb-modal-btn-primary">Detayları İncele</a>
                    <a href="/register" class="tb-modal-btn tb-modal-btn-ghost">Ücretsiz Başla</a>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        requestAnimationFrame(() => modal.classList.add('active'));

        const close = () => {
            modal.classList.remove('active');
            setTimeout(() => modal.remove(), 200);
        };

        modal.addEventListener('click', (e) => {
            if (e.target === modal) close();
        });
        modal.querySelector('.tb-modal-close')?.addEventListener('click', close);
        document.addEventListener('keydown', function onEsc(e) {
            if (e.key === 'Escape') {
                close();
                document.removeEventListener('keydown', onEsc);
            }
        });
    }

    addPromoBar();

    // Intro popup: 15sn sonra veya %40 scroll'da (hangisi onceyse)
    if (shouldShow(INTRO_POPUP_TS_KEY, popupCooldownMs)) {
        let introTriggered = false;
        const openIntro = () => {
            if (introTriggered) return;
            introTriggered = true;
            window.removeEventListener('scroll', onScroll);
            renderPopup('intro');
        };

        const onScroll = () => {
            const doc = document.documentElement;
            const full = Math.max(1, doc.scrollHeight - window.innerHeight);
            const ratio = window.scrollY / full;
            if (ratio >= 0.4) openIntro();
        };

        window.addEventListener('scroll', onScroll, { passive: true });
        setTimeout(openIntro, 15000);
    }

    // Exit intent popup (desktop)
    if (window.innerWidth > 1024 && shouldShow(EXIT_POPUP_TS_KEY, popupCooldownMs)) {
        document.addEventListener('mouseleave', (e) => {
            if (e.clientY <= 0 && !isPopupShownInSession()) {
                renderPopup('exit');
            }
        });
    }
});

// ============================================================
// Mobile sticky CTA (conversion first)
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    const path = (window.location.pathname || '').toLowerCase();
    const isAdmin = path.startsWith('/admin') || path.includes('/admin/');
    const isAuthPage = path.endsWith('/login') || path.endsWith('/register') || path.endsWith('/login.php') || path.endsWith('/register.php');
    const isCreatePage = path.includes('/listings/create');
    const isMobile = window.matchMedia('(max-width: 768px)').matches;
    const hasBottomNav = window.matchMedia('(max-width: 860px)').matches;

    // Bottom navigation is already the primary mobile action surface.
    // Avoid stacking an extra fixed CTA on top of it.
    if (!isMobile || hasBottomNav || isAdmin || isAuthPage || isCreatePage) return;
    if (document.querySelector('.tb-mobile-cta')) return;

    const hideKey = 'tb_mobile_cta_hide_until_v1';
    let hiddenUntil = 0;
    try {
        hiddenUntil = parseInt(localStorage.getItem(hideKey) || '0', 10) || 0;
    } catch (_) {
        hiddenUntil = 0;
    }
    if (Date.now() < hiddenUntil) return;

    const insidePanel = !!document.querySelector('.app-layout, #appSidebar');
    const primaryHref = insidePanel ? '/listings/create?quick=1' : '/quick-request';
    const primaryLabel = insidePanel ? 'Yeni İlan Aç' : "30 sn'de Talep Aç";
    const hintText = insidePanel ? 'Teklifleri daha hızlı topla' : 'Acil yardım, taşıma ve günlük işler';

    const cta = document.createElement('aside');
    cta.className = 'tb-mobile-cta';
    cta.setAttribute('aria-label', 'Hızlı teklif aksiyonu');
    cta.innerHTML = `
        <button type="button" class="tb-mobile-cta__close" aria-label="Kapat">&times;</button>
        <div class="tb-mobile-cta__content">
            <div class="tb-mobile-cta__text">
                <strong>${primaryLabel}</strong>
                <span>${hintText}</span>
            </div>
            <div class="tb-mobile-cta__actions">
                <a class="tb-mobile-cta__btn tb-mobile-cta__btn--primary" href="${primaryHref}">${primaryLabel}</a>
                <a class="tb-mobile-cta__btn tb-mobile-cta__btn--ghost" href="/listings/browse">İlanlara Bak</a>
            </div>
        </div>
    `;

    const trackEvent = (name, extra = {}) => {
        const payload = {
            event: name,
            page_path: window.location.pathname,
            device_type: 'mobile',
            ...extra,
        };
        try {
            if (Array.isArray(window.dataLayer)) {
                window.dataLayer.push(payload);
            }
            if (typeof window.gtag === 'function') {
                window.gtag('event', name, payload);
            }
        } catch (_) {}
    };

    const closeBtn = cta.querySelector('.tb-mobile-cta__close');
    const primaryBtn = cta.querySelector('.tb-mobile-cta__btn--primary');
    const secondaryBtn = cta.querySelector('.tb-mobile-cta__btn--ghost');

    closeBtn?.addEventListener('click', () => {
        try {
            const sixHoursMs = 6 * 60 * 60 * 1000;
            localStorage.setItem(hideKey, String(Date.now() + sixHoursMs));
        } catch (_) {}
        cta.remove();
        document.body.classList.remove('tb-has-mobile-cta');
        trackEvent('mobile_cta_closed');
    });

    primaryBtn?.addEventListener('click', () => {
        trackEvent('mobile_cta_primary_click', { cta_target: primaryHref });
    });

    secondaryBtn?.addEventListener('click', () => {
        trackEvent('mobile_cta_secondary_click', { cta_target: '/listings/browse' });
    });

    document.body.appendChild(cta);
    document.body.classList.add('tb-has-mobile-cta');
    trackEvent('mobile_cta_shown', { cta_target: primaryHref });

    const syncBottomOffset = () => {
        const cookieBanner = document.getElementById('cookieBanner');
        let extra = 0;
        if (cookieBanner) {
            const style = window.getComputedStyle(cookieBanner);
            if (style.display !== 'none' && cookieBanner.offsetHeight > 0) {
                extra = cookieBanner.offsetHeight + 8;
            }
        }
        cta.style.bottom = `calc(${12 + extra}px + env(safe-area-inset-bottom))`;
    };

    syncBottomOffset();
    window.addEventListener('resize', syncBottomOffset);

    const cookieBanner = document.getElementById('cookieBanner');
    if (cookieBanner && typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(syncBottomOffset);
        observer.observe(cookieBanner, { attributes: true, attributeFilter: ['style', 'class'] });
    }
});

// ============================================================
// Lightweight conversion tracking
// ============================================================
(function () {
    function cleanLabel(value, maxLen = 80) {
        const text = String(value || '').replace(/\s+/g, ' ').trim();
        if (!text) return '';
        return text.length > maxLen ? text.slice(0, maxLen) : text;
    }

    window.tbTrack = window.tbTrack || function (eventName, params = {}) {
        const name = cleanLabel(eventName, 50).toLowerCase().replace(/[^a-z0-9_]/g, '_');
        if (!name) return;

        const payload = {
            page_path: window.location.pathname,
            page_title: document.title || '',
            ...params,
        };

        try {
            if (typeof window.gtag === 'function') {
                window.gtag('event', name, payload);
                return;
            }
            if (Array.isArray(window.dataLayer)) {
                window.dataLayer.push({ event: name, ...payload });
            }
        } catch (_) {}
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-track]').forEach((el) => {
            if (el.dataset.trackBound === '1') return;
            el.dataset.trackBound = '1';

            el.addEventListener('click', () => {
                const eventName = cleanLabel(el.dataset.track, 50);
                if (!eventName) return;

                window.tbTrack(eventName, {
                    element_tag: (el.tagName || '').toLowerCase(),
                    element_text: cleanLabel(el.textContent, 60),
                    target_url: cleanLabel(el.getAttribute('href') || ''),
                });
            });
        });

        document.querySelectorAll('form').forEach((form) => {
            if (form.dataset.trackSubmitBound === '1') return;
            form.dataset.trackSubmitBound = '1';

            form.addEventListener('submit', () => {
                const formId = cleanLabel(form.id || form.name || form.getAttribute('action') || 'anonymous_form', 64);
                window.tbTrack('form_submit', { form_id: formId });
            });
        });

        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');
            if (!link) return;

            const href = (link.getAttribute('href') || '').trim();
            if (!href) return;

            const hrefLower = href.toLowerCase();
            if (hrefLower.startsWith('mailto:')) {
                window.tbTrack('contact_email_click', { target_url: href });
            } else if (hrefLower.startsWith('tel:')) {
                window.tbTrack('contact_phone_click', { target_url: href });
            } else if (hrefLower.includes('wa.me') || hrefLower.includes('whatsapp')) {
                window.tbTrack('contact_whatsapp_click', { target_url: href });
            }
        });
    });
})();

// ============================================================
// Mobile bottom navigation (public + member pages, non-admin)
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    const path = (window.location.pathname || '/').toLowerCase();
    const isAdmin = path.startsWith('/admin') || path.includes('/admin/');
    const isMobile = window.matchMedia('(max-width: 860px)').matches;

    if (isAdmin || !isMobile) return;
    if (document.querySelector('.tb-mobile-nav')) return;

    const appBase = (typeof window.APP_URL === 'string' ? window.APP_URL.trim() : '').replace(/\/+$/, '');
    const toAppUrl = (href) => {
        if (!href) return '#';
        if (/^https?:\/\//i.test(href)) return href;
        const clean = href.startsWith('/') ? href : `/${href}`;
        return appBase ? `${appBase}${clean}` : clean;
    };

    const normalizePath = (value) => {
        const clean = (value || '/').split('?')[0].split('#')[0].replace(/\/+$/, '');
        return clean === '' ? '/' : clean;
    };

    const currentPath = normalizePath(path);
    const hasSidebar = !!document.querySelector('#appSidebar');
    const hasLogoutLink = !!document.querySelector('a[href$="/logout"],a[href="/logout"],a[href$="logout.php"],a[href*="/logout?"]');
    const hasProfileLink = !!document.querySelector('a[href$="/profile"],a[href*="/profile.php"]');
    const isMember = hasSidebar || hasLogoutLink || hasProfileLink;
    const isWorkerProfilePage = currentPath.includes('/worker_profile');

    const items = isMember ? [
        { key: 'dashboard', label: 'Panel', icon: '&#128200;', href: '/dashboard', active: ['/dashboard'] },
        { key: 'browse', label: 'Ilanlar', icon: '&#128269;', href: '/listings/browse', active: ['/listings/browse', '/listings/detail'] },
        { key: 'favorites', label: 'Favori', icon: '&#10084;', href: '/favorites', active: ['/favorites', '/favorites.php'] },
        { key: 'share', label: 'Paylas', icon: '&#10150;', action: 'share', quick: true },
        { key: 'messages', label: 'Mesaj', icon: '&#9993;', href: '/messages.php', active: ['/messages', '/messages.php'] },
        { key: 'logout', label: 'Cikis', icon: '&#10162;', href: '/logout', action: 'logout', danger: true },
    ] : [
        { key: 'home', label: 'Ana Sayfa', icon: '&#8962;', href: '/', active: ['/'] },
        { key: 'browse', label: 'Ilanlar', icon: '&#128269;', href: '/listings/browse', active: ['/listings/browse', '/listings/detail', '/hizmet'] },
        { key: 'quick', label: 'Hizli', icon: '&#9889;', href: '/quick-request', active: ['/quick-request'], quick: true },
        { key: 'login', label: 'Giris', icon: '&#128274;', href: '/login', active: ['/login', '/login.php'] },
        { key: 'register', label: 'Kayit', icon: '&#10010;', href: '/register', active: ['/register', '/register.php'] },
    ];

    if (isWorkerProfilePage) {
        items[2] = { key: 'contact', label: 'Mesaj', icon: '&#9993;', href: '/login', active: ['/messages', '/messages.php'], quick: true };
    }

    const visibleItems = items.filter((item) => !item.hidden);
    if (visibleItems.length < 3) return;

    const copyToClipboard = async (text) => {
        if (!text) return false;
        try {
            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                await navigator.clipboard.writeText(text);
                return true;
            }
        } catch (_) {}

        try {
            const helper = document.createElement('textarea');
            helper.value = text;
            helper.setAttribute('readonly', 'readonly');
            helper.style.position = 'fixed';
            helper.style.opacity = '0';
            helper.style.left = '-9999px';
            document.body.appendChild(helper);
            helper.select();
            const success = document.execCommand('copy');
            document.body.removeChild(helper);
            return success;
        } catch (_) {
            return false;
        }
    };

    const flashNavLabel = (element, text) => {
        const label = element?.querySelector('.tb-mobile-nav__label');
        if (!label || !text) return;
        const previous = label.textContent;
        label.textContent = text;
        setTimeout(() => {
            label.textContent = previous;
        }, 1600);
    };

    const isActiveItem = (item) => {
        if (item.action) return false;
        const targets = Array.isArray(item.active) ? item.active : [item.href];
        return targets.some((target) => {
            const targetPath = normalizePath(target);
            if (targetPath === '/') return currentPath === '/';
            return currentPath === targetPath || currentPath.startsWith(`${targetPath}/`);
        });
    };

    const nav = document.createElement('nav');
    nav.className = 'tb-mobile-nav';
    nav.setAttribute('aria-label', 'Mobil gezinme');
    nav.innerHTML = visibleItems.map((item) => {
        const activeClass = isActiveItem(item) ? 'tb-mobile-nav__item--active' : '';
        const quickClass = item.quick ? 'tb-mobile-nav__item--quick' : '';
        const dangerClass = item.danger ? 'tb-mobile-nav__item--danger' : '';
        const href = item.action ? '#' : toAppUrl(item.href);
        const actionAttr = item.action ? ` data-action="${item.action}"` : '';
        return `
            <a class="tb-mobile-nav__item ${activeClass} ${quickClass} ${dangerClass}" href="${href}"${actionAttr} aria-label="${item.label}" data-track="mobile_nav_${item.key}">
                <span class="tb-mobile-nav__icon" aria-hidden="true">${item.icon || ''}</span>
                <span class="tb-mobile-nav__label">${item.label}</span>
            </a>
        `;
    }).join('');

    document.body.appendChild(nav);
    document.body.classList.add('tb-has-mobile-nav');

    const appHamburger = document.getElementById('hamburger');
    if (appHamburger && appHamburger.closest('.app-header')) {
        appHamburger.classList.add('tb-sidebar-toggle-ready');
    }

    nav.querySelectorAll('[data-action="logout"]').forEach((link) => {
        link.addEventListener('click', async (event) => {
            event.preventDefault();
            const shouldLogout = await window.tbConfirm('Çıkış yapmak istediğinize emin misiniz?', {
                title: 'Çıkış Onayı',
                confirmText: 'Evet, Çıkış Yap',
                cancelText: 'İptal'
            });
            if (!shouldLogout) return;
            window.location.href = toAppUrl('/logout');
        });
    });

    nav.querySelectorAll('[data-action="share"]').forEach((link) => {
        link.addEventListener('click', async (event) => {
            event.preventDefault();

            const shareUrl = window.location.href;
            const shareTitle = document.title || 'Temizci Burada';

            if (navigator.share && typeof navigator.share === 'function') {
                try {
                    await navigator.share({ title: shareTitle, text: shareTitle, url: shareUrl });
                    return;
                } catch (_) {}
            }

            const copied = await copyToClipboard(shareUrl);
            flashNavLabel(link, copied ? 'Kopyalandi' : 'Paylasim');
        });
    });

    const syncFloatingElements = () => {
        const cta = document.querySelector('.tb-mobile-cta');
        if (!cta) return;
        cta.style.bottom = 'calc(var(--mobile-nav-h, 74px) + 16px + env(safe-area-inset-bottom))';
    };

    syncFloatingElements();
    window.addEventListener('resize', syncFloatingElements);
});
