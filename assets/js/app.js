// ============================================================
// Temizci Burada — Genel JavaScript
// ============================================================

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
  const photoInput   = document.getElementById('photoInput');
  const photoPreview = document.getElementById('photoPreview');
  const uploadArea   = document.querySelector('.photo-upload-area');

  if (photoInput && photoPreview) {
    photoInput.addEventListener('change', function () {
      const file = this.files[0];
      if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => {
          photoPreview.src = e.target.result;
          photoPreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });
  }

  if (uploadArea) {
    uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('drag-over'); });
    uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('drag-over'));
    uploadArea.addEventListener('drop', e => {
      e.preventDefault();
      uploadArea.classList.remove('drag-over');
      if (photoInput && e.dataTransfer.files.length) {
        photoInput.files = e.dataTransfer.files;
        photoInput.dispatchEvent(new Event('change'));
      }
    });
  }

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
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm)) e.preventDefault();
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
