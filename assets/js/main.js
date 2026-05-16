// ============================================================
// Queima das Fitas do Porto — Main JS
// ============================================================

'use strict';

// ---- Auto-dismiss flash messages ----
document.addEventListener('DOMContentLoaded', function () {
  const flashes = document.querySelectorAll('.qf-flash');
  flashes.forEach(function (el) {
    setTimeout(function () {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
      bsAlert.close();
    }, 5000);
  });
});

// ---- Countdown Timer ----
// Usage: <div class="qf-countdown" data-target="2026-05-06T22:00:00"></div>
function initCountdowns() {
  const timers = document.querySelectorAll('.qf-countdown[data-target]');

  timers.forEach(function (el) {
    const target = new Date(el.getAttribute('data-target')).getTime();

    function tick() {
      const now  = Date.now();
      const diff = target - now;

      if (diff <= 0) {
        el.innerHTML = '<span class="text-success fw-bold"><i class="fas fa-circle-check me-1"></i>A decorrer agora!</span>';
        return;
      }

      const days    = Math.floor(diff / 86400000);
      const hours   = Math.floor((diff % 86400000) / 3600000);
      const minutes = Math.floor((diff % 3600000) / 60000);
      const seconds = Math.floor((diff % 60000) / 1000);

      el.innerHTML =
        unit(days,    'Dias') +
        unit(hours,   'Horas') +
        unit(minutes, 'Min') +
        unit(seconds, 'Seg');
    }

    function unit(val, label) {
      return `<div class="countdown-unit">
                <span class="countdown-value">${String(val).padStart(2,'0')}</span>
                <span class="countdown-label">${label}</span>
              </div>`;
    }

    tick();
    setInterval(tick, 1000);
  });
}

document.addEventListener('DOMContentLoaded', initCountdowns);

// ---- Star Rating Hover Effect ----
function initStarRating() {
  const ratings = document.querySelectorAll('.star-rating');
  ratings.forEach(function (container) {
    const labels = container.querySelectorAll('label');
    labels.forEach(function (label) {
      label.addEventListener('mouseenter', function () {
        labels.forEach(l => l.style.color = '');
        // highlight this and all siblings that come after (flex row-reverse)
        let found = false;
        labels.forEach(function (l) {
          if (l === label) found = true;
          if (found) l.style.color = '#f4c542';
        });
      });
      label.addEventListener('mouseleave', function () {
        labels.forEach(l => l.style.color = '');
      });
    });
  });
}

document.addEventListener('DOMContentLoaded', initStarRating);

// ---- Delete confirmation dialogs ----
document.addEventListener('DOMContentLoaded', function () {
  const deleteForms = document.querySelectorAll('form[data-confirm]');
  deleteForms.forEach(function (form) {
    form.addEventListener('submit', function (e) {
      const msg = form.getAttribute('data-confirm') || 'Tens a certeza que pretendes eliminar este registo?';
      if (!confirm(msg)) {
        e.preventDefault();
      }
    });
  });

  // Also handle links with data-confirm
  const deleteLinks = document.querySelectorAll('a[data-confirm]');
  deleteLinks.forEach(function (link) {
    link.addEventListener('click', function (e) {
      const msg = link.getAttribute('data-confirm') || 'Tens a certeza?';
      if (!confirm(msg)) {
        e.preventDefault();
      }
    });
  });
});

// ---- Client-side form validation ----
document.addEventListener('DOMContentLoaded', function () {
  const forms = document.querySelectorAll('.qf-validate');
  forms.forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      form.classList.add('was-validated');
    });
  });
});

// ---- Dynamic event type label ----
function initEventTypeBadge() {
  const select = document.getElementById('event-type-select');
  if (!select) return;

  select.addEventListener('change', function () {
    const badge = document.getElementById('event-type-preview');
    if (!badge) return;
    const map = {
      'academic_ceremony': ['badge-academic',  'Cerimónia Académica'],
      'concert':           ['badge-concert',   'Concerto'],
      'cultural_activity': ['badge-cultural',  'Actividade Cultural'],
    };
    const val = this.value;
    if (map[val]) {
      badge.className = 'badge ' + map[val][0];
      badge.textContent = map[val][1];
    } else {
      badge.className = 'badge bg-secondary';
      badge.textContent = 'Tipo';
    }
  });
}

document.addEventListener('DOMContentLoaded', initEventTypeBadge);

// ---- Animate bar chart on scroll ----
function initBarCharts() {
  const bars = document.querySelectorAll('.bar-fill[data-width]');
  if (!bars.length) return;

  const observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        const bar = entry.target;
        bar.style.width = bar.getAttribute('data-width') + '%';
        observer.unobserve(bar);
      }
    });
  }, { threshold: 0.1 });

  bars.forEach(function (bar) {
    bar.style.width = '0%';
    observer.observe(bar);
  });
}

document.addEventListener('DOMContentLoaded', initBarCharts);

// ---- File input preview ----
document.addEventListener('DOMContentLoaded', function () {
  const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');
  fileInputs.forEach(function (input) {
    input.addEventListener('change', function () {
      const previewId = input.getAttribute('data-preview');
      const preview   = document.getElementById(previewId);
      if (!preview) return;
      const file = input.files[0];
      if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function (e) {
          preview.src = e.target.result;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });
  });
});
