// Chipi Frozen Food - front-end helpers
(function () {
  const BASE = window.CHIPI_BASE || '';

  // ---- Toast helper ----
  window.chipiToast = function (msg, type = 'success') {
    let wrap = document.getElementById('chipi-toast-wrap');
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.id = 'chipi-toast-wrap';
      wrap.style.cssText = 'position:fixed;top:1rem;left:50%;transform:translateX(-50%);z-index:2000;width:auto;max-width:92%;';
      document.body.appendChild(wrap);
    }
    const el = document.createElement('div');
    const color = type === 'success' ? '#1b8a4b' : (type === 'error' ? '#c0392b' : '#1e88d6');
    el.style.cssText = `background:${color};color:#fff;padding:.7rem 1.1rem;border-radius:14px;margin-bottom:.5rem;box-shadow:0 8px 24px rgba(0,0,0,.2);font-weight:700;text-align:center;animation:chipiIn .25s ease`;
    el.textContent = msg;
    wrap.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(() => el.remove(), 300); }, 2600);
  };

  function updateCartBadges(count) {
    document.querySelectorAll('[data-cart-count]').forEach(function (b) {
      b.textContent = count;
      b.style.display = count > 0 ? '' : 'none';
    });
  }

  // ---- Add to cart (AJAX) ----
  document.addEventListener('click', function (ev) {
    const btn = ev.target.closest('[data-add-cart]');
    if (!btn) return;
    ev.preventDefault();
    const id = btn.getAttribute('data-add-cart');
    let qty = 1;
    const qWrap = btn.closest('[data-card]');
    if (qWrap) { const qi = qWrap.querySelector('.qty-input'); if (qi) qty = parseInt(qi.value) || 1; }
    const fd = new FormData();
    fd.append('product_id', id);
    fd.append('qty', qty);
    fd.append('csrf', window.CHIPI_CSRF || '');
    btn.disabled = true;
    fetch(BASE + '/customer/cart-action.php?action=add', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        btn.disabled = false;
        if (d.ok) { updateCartBadges(d.count); window.chipiToast('Ditambahkan ke keranjang'); }
        else { window.chipiToast(d.msg || 'Gagal', 'error'); }
      })
      .catch(() => { btn.disabled = false; window.chipiToast('Terjadi kesalahan', 'error'); });
  });

  // ---- Qty steppers on cards ----
  document.addEventListener('click', function (ev) {
    const dec = ev.target.closest('[data-qty-dec]');
    const inc = ev.target.closest('[data-qty-inc]');
    if (!dec && !inc) return;
    const wrap = (dec || inc).closest('.qty-stepper');
    const input = wrap.querySelector('.qty-input');
    let v = parseInt(input.value) || 1;
    v = inc ? v + 1 : Math.max(1, v - 1);
    input.value = v;
    input.dispatchEvent(new Event('change'));
  });

  // ---- Admin sidebar toggle ----
  window.toggleSidebar = function () {
    document.querySelector('.admin-sidebar')?.classList.toggle('open');
    document.querySelector('.sidebar-backdrop')?.classList.toggle('show');
  };

  // ---- Confirm dialogs ----
  document.addEventListener('submit', function (ev) {
    const f = ev.target;
    if (f.hasAttribute('data-confirm')) {
      if (!confirm(f.getAttribute('data-confirm'))) ev.preventDefault();
    }
  });
})();

const style = document.createElement('style');
style.textContent = '@keyframes chipiIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}';
document.head.appendChild(style);
