/* Nexora agent panel — the luxury windows.
   The window of a user (the details of the user and the links of his
   services) and the window of the account that was just made.
   The file never changes anything: it only shows what the server sends. */
(function () {
  'use strict';

  var CFG = window.NX_AGENT || {};

  /* ---------------------------------------------------------- helpers */
  function $(id) { return document.getElementById(id); }

  function esc(v) {
    return String(v === null || v === undefined ? '' : v)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function faNum(v) {
    var n = Number(v) || 0;
    try {
      return new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 2 }).format(n);
    } catch (e) {
      return String(n);
    }
  }

  function faDate(ts) {
    if (ts === null || ts === undefined || ts === '') return '—';
    // A date that is already written (the column of the sale is sometimes a
    // date and not a number) is shown as it is.
    if (typeof ts === 'string' && !/^\d+$/.test(ts.trim())) return ts;
    var s = Number(ts) || 0;
    if (!s) return '—';
    var d = new Date(s * 1000);
    if (isNaN(d.getTime())) return '—';
    try {
      return new Intl.DateTimeFormat('fa-IR', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit'
      }).format(d);
    } catch (e) {
      return d.toLocaleString();
    }
  }

  var STATUS = {
    active: 'فعال',
    end_of_time: 'زمانش تمام شده',
    end_of_volume: 'حجمش تمام شده',
    sendedwarn: 'نزدیک پایان',
    send_on_hold: 'متوقف',
    unpaid: 'پرداخت نشده',
    expired: 'منقضی',
    pending: 'در انتظار'
  };

  function statusText(s) {
    var key = String(s || '').toLowerCase();
    return STATUS[key] || (s ? String(s) : '—');
  }

  function statusClass(s) {
    var key = String(s || '').toLowerCase();
    if (key === 'active') return 'p-green';
    if (key === 'send_on_hold' || key === 'unpaid' || key === 'pending') return 'p-orange';
    if (key === 'expired') return 'p-red';
    return 'p-gray';
  }

  function toast(msg) {
    var el = $('nx-toast');
    if (!el) {
      el = document.createElement('div');
      el.id = 'nx-toast';
      el.className = 'nx-toast';
      document.body.appendChild(el);
    }
    el.textContent = msg;
    el.classList.add('on');
    window.clearTimeout(el._t);
    el._t = window.setTimeout(function () { el.classList.remove('on'); }, 2200);
  }

  function copyText(text) {
    var value = String(text || '');
    if (!value) { toast('چیزی برای کپی نیست'); return; }
    var done = function () { toast('در کلیپ‌بورد کپی شد ✓'); };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(value).then(done, function () { copyFallback(value, done); });
    } else {
      copyFallback(value, done);
    }
  }

  function copyFallback(value, done) {
    var ta = document.createElement('textarea');
    ta.value = value;
    ta.setAttribute('readonly', 'readonly');
    ta.style.position = 'fixed';
    ta.style.top = '-1000px';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    ta.setSelectionRange(0, ta.value.length);
    var ok = false;
    try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
    document.body.removeChild(ta);
    if (ok) { done(); } else { toast('کپی نشد؛ متن را با دست انتخاب کنید'); }
  }

  /* ---------------------------------------------------- the window of a user */
  var loading = false;

  function customerModal() { return $('nx-customer-modal'); }

  function openCustomer(id, withLinks) {
    var modal = customerModal();
    if (!modal || loading) return;
    loading = true;
    var body = $('nx-cust-body');
    var title = $('nx-cust-title');
    var sub = $('nx-cust-sub');
    var ic = $('nx-cust-ic');
    if (ic) ic.textContent = withLinks ? '🔗' : '👤';
    if (title) title.textContent = withLinks ? 'لینک سرویس‌های کاربر' : 'مشخصات کاربر';
    if (sub) sub.textContent = withLinks
      ? 'لینک اتصال هر سرویس از پنل خوانده می‌شود'
      : 'فقط خواندنی — هیچ چیزی از اینجا تغییر نمی‌کند';
    if (body) body.innerHTML = '<div class="nx-lux-wait"><span class="spin"></span><span>در حال خواندن اطلاعات…</span></div>';
    modal.hidden = false;
    document.body.style.overflow = 'hidden';

    var url = (CFG.info || 'customer-info.php') + '?customer=' + encodeURIComponent(id) +
      '&_csrf=' + encodeURIComponent(CFG.csrf || '') + (withLinks ? '&links=1' : '');
    fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json().catch(function () { return { ok: false, msg: 'پاسخ سرور خوانده نشد.' }; }); })
      .then(function (data) {
        loading = false;
        if (!data || !data.ok) {
          if (body) body.innerHTML = '<div class="nx-lux-empty">❌ ' + esc((data && data.msg) ? data.msg : 'اطلاعات خوانده نشد.') + '</div>';
          return;
        }
        if (body) body.innerHTML = renderCustomer(data, !!withLinks);
      })
      .catch(function () {
        loading = false;
        if (body) body.innerHTML = '<div class="nx-lux-empty">❌ ارتباط با سرور برقرار نشد. دوباره تلاش کنید.</div>';
      });
  }

  function renderCustomer(data, withLinks) {
    var c = data.customer || {};
    var services = data.services || [];
    var html = '';

    html += '<div class="nx-lux-grid">';
    html += '<div class="nx-lux-item"><span>نام کاربری</span><b>' + esc(c.username || '—') + '</b></div>';
    html += '<div class="nx-lux-item"><span>شناسهٔ کاربر</span><b class="mono">' + esc(c.id || '—') + '</b></div>';
    html += '<div class="nx-lux-item"><span>موجودی کیف پول</span><b>' + esc(faNum(c.balance)) + ' تومان</b></div>';
    html += '<div class="nx-lux-item"><span>تاریخ عضویت</span><b>' + esc(faDate(c.register)) + '</b></div>';
    html += '<div class="nx-lux-item"><span>تعداد سرویس</span><b>' + esc(faNum(services.length)) + ' سرویس</b></div>';
    html += '<div class="nx-lux-item"><span>پنل سرویس‌ها</span><b>' + esc(uniquePanels(services)) + '</b></div>';
    html += '</div>';

    html += '<div class="nx-lux-sub">سرویس‌ها</div>';
    if (!services.length) {
      html += '<div class="nx-lux-empty">این کاربر هنوز سرویسی ندارد.</div>';
    } else {
      services.forEach(function (svc) {
        html += renderService(svc, withLinks);
      });
    }
    return html;
  }

  function uniquePanels(services) {
    var seen = {};
    var out = [];
    (services || []).forEach(function (svc) {
      var name = String(svc.panel || '');
      if (name && !seen[name]) { seen[name] = 1; out.push(name); }
    });
    return out.length ? out.join('، ') : '—';
  }

  function renderService(svc, withLinks) {
    var html = '';
    html += '<div class="nx-svc">';
    html += '<div class="top">';
    html += '<b>' + esc(svc.product || svc.username || 'سرویس') + '</b>';
    html += '<span class="pill ' + statusClass(svc.status) + '">' + esc(statusText(svc.status)) + '</span>';
    html += '<span class="sp"></span>';
    html += '<span class="mono">' + esc(svc.username || '') + '</span>';
    html += '</div>';
    html += '<div class="lnk">پنل: <b>' + esc(svc.panel || '—') + '</b> • حجم: <b>' +
      esc(svc.volume ? faNum(svc.volume) + ' گیگابایت' : 'نامحدود') + '</b> • مدت: <b>' +
      esc(svc.days ? faNum(svc.days) + ' روز' : 'نامحدود') + '</b> • مبلغ: <b>' +
      esc(faNum(svc.price)) + ' تومان</b> • تاریخ: <b>' + esc(faDate(svc.time)) + '</b></div>';
    if (withLinks) {
      if (svc.error) {
        html += '<div class="nx-lux-empty">⚠️ ' + esc(svc.error) + '</div>';
      } else if (svc.link || (svc.links && svc.links.length)) {
        if (svc.link) {
          html += codeBlock(svc.link, 'لینک اتصال');
        }
        (svc.links || []).forEach(function (link) {
          html += codeBlock(link, 'کانفیگ');
        });
      } else {
        html += '<div class="nx-lux-empty">این سرویس لینکی ندارد.</div>';
      }
    }
    html += '</div>';
    return html;
  }

  function codeBlock(text, label) {
    return '<div class="nx-lux-code">' +
      '<button type="button" class="cp" onclick="nxCopy(this)">📋 کپی</button>' +
      (label ? '<div class="lb">' + esc(label) + '</div>' : '') +
      '<code>' + esc(text) + '</code>' +
      '</div>';
  }

  function closeCustomer() {
    var modal = customerModal();
    if (modal) modal.hidden = true;
    document.body.style.overflow = '';
  }

  /* ------------------------------------------ the window of the new account */
  function openCreated() {
    var modal = $('nx-created-modal');
    if (!modal) return;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function closeCreated() {
    var modal = $('nx-created-modal');
    if (modal) modal.hidden = true;
    document.body.style.overflow = '';
  }

  function copyAllCreated() {
    var text = String(CFG.createdText || '');
    if (!text) {
      var body = $('nx-created-body');
      text = body ? (body.innerText || '') : '';
    }
    copyText(text);
  }

  /* ----------------------------------------------------------- the wiring */
  window.nxOpenCustomer = function (id) { openCustomer(id, false); };
  window.nxOpenLinks = function (id) { openCustomer(id, true); };
  window.nxCloseCustomer = closeCustomer;
  window.nxOpenCreated = openCreated;
  window.nxCloseCreated = closeCreated;
  window.nxCopyAllCreated = copyAllCreated;
  window.nxCopy = function (btn) {
    var box = btn ? btn.parentNode : null;
    var code = box ? box.querySelector('code') : null;
    copyText(code ? (code.textContent || '') : '');
  };

  document.addEventListener('DOMContentLoaded', function () {
    var modal = customerModal();
    if (modal) {
      modal.addEventListener('click', function (e) { if (e.target === modal) closeCustomer(); });
    }
    var made = $('nx-created-modal');
    if (made) {
      made.addEventListener('click', function (e) { if (e.target === made) closeCreated(); });
    }
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      closeCustomer();
      closeCreated();
    });
  });
})();
