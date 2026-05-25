/* =============================================================
   app.js — Subscription Management System
   All pages live in a single JS file for clean organisation.
   ============================================================= */

const API_BASE = 'http://localhost/project/backend';

/* =============================================================
   UTILITY HELPERS
   ============================================================= */
const $ = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

function formatINR(amount) {
  return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(amount);
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

function getInitials(name = '') {
  return name.trim().split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase() || 'U';
}

/* =============================================================
   TOAST NOTIFICATIONS
   ============================================================= */
function showToast(message, type = 'info', duration = 3500) {
  let container = $('#toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const icons = { success: '✓', error: '✕', info: 'ℹ' };
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `<span>${icons[type] || 'ℹ'}</span> ${message}`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = 'toastOut 0.3s ease forwards';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

/* =============================================================
   INLINE ALERT (within auth forms)
   ============================================================= */
function showAlert(containerId, message, type = 'error') {
  const existing = $(`#${containerId} .alert`);
  if (existing) existing.remove();

  const alert = document.createElement('div');
  alert.className = `alert alert-${type}`;
  alert.textContent = message;

  const container = $(`#${containerId}`);
  if (container) container.prepend(alert);
}

/* =============================================================
   AUTH STATE
   ============================================================= */
function getUser() {
  try {
    return JSON.parse(localStorage.getItem('subms_user')) || null;
  } catch { return null; }
}

function setUser(user) {
  localStorage.setItem('subms_user', JSON.stringify(user));
}

function clearUser() {
  localStorage.removeItem('subms_user');
}

/* =============================================================
   PAGE ROUTER (SPA-style)
   ============================================================= */
function showPage(pageId) {
  $$('.page-wrapper').forEach(p => p.classList.remove('active'));
  $$('.nav-link').forEach(l => l.classList.remove('active'));

  const page = $(`#page-${pageId}`);
  if (page) page.classList.add('active');

  const navLink = $(`.nav-link[data-page="${pageId}"]`);
  if (navLink) navLink.classList.add('active');

  window.scrollTo(0, 0);

  // Trigger page-specific init
  if (pageId === 'plans')     initPlansPage();
  if (pageId === 'dashboard') initDashboardPage();
}

/* =============================================================
   NAVBAR  —  render based on auth state
   ============================================================= */
function renderNavbar() {
  const user = getUser();
  const navLinks = $('#nav-links');
  if (!navLinks) return;

  if (user) {
    navLinks.innerHTML = `
      <a class="nav-link" data-page="plans"     href="#" id="nav-plans">Plans</a>
      <a class="nav-link" data-page="dashboard" href="#" id="nav-dashboard">Dashboard</a>
      <button class="btn btn-outline" id="btn-logout" style="font-size:0.82rem;padding:8px 16px;">Sign Out</button>
    `;
    $('#btn-logout').addEventListener('click', logout);
  } else {
    navLinks.innerHTML = `
      <a class="nav-link" data-page="login"    href="#" id="nav-login">Login</a>
      <a class="nav-link" data-page="register" href="#" id="nav-register">Register</a>
    `;
  }

  // Attach nav click handlers
  $$('.nav-link[data-page]', navLinks).forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      showPage(link.dataset.page);
    });
  });
}

function logout() {
  clearUser();
  renderNavbar();
  showPage('login');
  showToast('Signed out successfully.', 'info');
}

/* =============================================================
   REGISTER
   ============================================================= */
function initRegisterPage() {
  const form = $('#register-form');
  if (!form || form.dataset.init) return;
  form.dataset.init = '1';

  form.addEventListener('submit', async e => {
    e.preventDefault();

    const name     = $('#reg-name').value.trim();
    const email    = $('#reg-email').value.trim();
    const password = $('#reg-password').value;
    const btn      = form.querySelector('button[type="submit"]');

    if (!name || !email || !password) {
      showAlert('register-form-wrap', 'Please fill in all fields.');
      return;
    }
    if (password.length < 6) {
      showAlert('register-form-wrap', 'Password must be at least 6 characters.');
      return;
    }

    btn.disabled = true;
    btn.textContent = 'Creating account…';

    try {
      const res  = await fetch(`${API_BASE}/auth/register.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, password })
      });
      const data = await res.json();

      if (data.success) {
        setUser({ user_id: data.user_id, name: data.name, email: data.email });
        renderNavbar();
        showPage('dashboard');
        showToast(`Welcome, ${data.name}! 🎉`, 'success');
      } else {
        showAlert('register-form-wrap', data.message || 'Registration failed.');
      }
    } catch {
      showAlert('register-form-wrap', 'Network error. Please try again.');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Create Account';
    }
  });
}

/* =============================================================
   LOGIN
   ============================================================= */
function initLoginPage() {
  const form = $('#login-form');
  if (!form || form.dataset.init) return;
  form.dataset.init = '1';

  form.addEventListener('submit', async e => {
    e.preventDefault();

    const email    = $('#login-email').value.trim();
    const password = $('#login-password').value;
    const btn      = form.querySelector('button[type="submit"]');

    if (!email || !password) {
      showAlert('login-form-wrap', 'Please enter email and password.');
      return;
    }

    btn.disabled = true;
    btn.textContent = 'Signing in…';

    try {
      const res  = await fetch(`${API_BASE}/auth/login.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
      });
      const data = await res.json();

      if (data.success) {
        setUser({ user_id: data.user_id, name: data.name, email: data.email });
        renderNavbar();
        showPage('dashboard');
        showToast(`Welcome back, ${data.name}!`, 'success');
      } else {
        showAlert('login-form-wrap', data.message || 'Login failed.');
      }
    } catch {
      showAlert('login-form-wrap', 'Network error. Please try again.');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Sign In';
    }
  });
}

/* =============================================================
   PLANS PAGE
   ============================================================= */
let allPlansCache = [];
let currentCoupon = { code: null, discount: 0 };

async function initPlansPage() {
  const grid = $('#plans-grid');
  const catFilter = $('#category-filter');
  if (!grid) return;

  grid.innerHTML = `
    <div class="state-container" style="grid-column:1/-1;">
      <div class="spinner"></div>
      <p>Loading plans…</p>
    </div>`;

  try {
    if (catFilter && catFilter.options.length <= 1) {
      const catRes = await fetch(`${API_BASE}/plans/get_categories.php`);
      const catData = await catRes.json();
      if (catData.success) {
        catData.categories.forEach(c => {
          const opt = document.createElement('option');
          opt.value = c.category_id;
          opt.textContent = c.name;
          catFilter.appendChild(opt);
        });
      }
      catFilter.addEventListener('change', () => renderPlans(allPlansCache));
    }

    const couponBtn = $('#apply-coupon-btn');
    if (couponBtn && !couponBtn.dataset.init) {
       couponBtn.dataset.init = '1';
       couponBtn.addEventListener('click', async () => {
          const code = $('#coupon-input').value.trim();
          const msgEl = $('#coupon-msg');
          if(!code) return;
          
          msgEl.textContent = 'Validating...';
          msgEl.style.color = 'var(--text-secondary)';
          
          try {
             const res = await fetch(`${API_BASE}/coupons/validate.php?code=${code}`);
             const data = await res.json();
             if(data.success) {
                currentCoupon = { code, discount: data.discount_percent };
                msgEl.textContent = `✅ ${data.discount_percent}% off applied!`;
                msgEl.style.color = 'var(--success)';
                renderPlans(allPlansCache);
             } else {
                currentCoupon = { code: null, discount: 0 };
                msgEl.textContent = `❌ ${data.message}`;
                msgEl.style.color = 'var(--danger)';
                renderPlans(allPlansCache);
             }
          } catch(e) {
             msgEl.textContent = `❌ Error validating coupon.`;
             msgEl.style.color = 'var(--danger)';
          }
       });
    }

    const res  = await fetch(`${API_BASE}/plans/get_plans.php`);
    const data = await res.json();

    if (!data.success || !data.plans.length) {
      grid.innerHTML = `
        <div class="state-container" style="grid-column:1/-1;">
          <div class="state-icon">📭</div>
          <h3>No Plans Available</h3>
          <p>Check back soon — plans will be added shortly.</p>
        </div>`;
      return;
    }

    allPlansCache = data.plans;
    renderPlans(allPlansCache);
  } catch {
    grid.innerHTML = `
      <div class="state-container" style="grid-column:1/-1;">
        <div class="state-icon">⚠️</div>
        <h3>Error Loading Plans</h3>
        <p>Could not connect to the server. Ensure XAMPP is running.</p>
      </div>`;
  }
}

function renderPlans(plans) {
  const grid = $('#plans-grid');
  const catFilter = $('#category-filter');
  const selectedCat = catFilter ? catFilter.value : 'all';

  const filtered = selectedCat === 'all' 
                   ? plans 
                   : plans.filter(p => p.category_id == selectedCat);

  if (!filtered.length) {
    grid.innerHTML = `<div class="state-container" style="grid-column:1/-1;">
      <p>No plans found for this category.</p></div>`;
    return;
  }

  grid.innerHTML = '';
  // Identify the middle plan to be the Most Popular (if there's more than 1)
  const popularIndex = Math.floor(filtered.length / 2);
  
  filtered.forEach((plan, index) => {
    const finalPrice = currentCoupon.discount > 0 
        ? plan.price - (plan.price * (currentCoupon.discount / 100)) 
        : plan.price;

    const isPopular = (index === popularIndex && filtered.length > 1);

    const card = document.createElement('div');
    card.className = 'plan-card';
    card.innerHTML = `
      ${isPopular ? '<div class="popular-badge">Most Popular</div>' : ''}
      <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">${plan.category_name || 'General'}</div>
      <div class="plan-name" style="margin-top:-8px;">${escHtml(plan.name)}</div>
      <div class="plan-price">
        ${formatINR(finalPrice)}
        ${currentCoupon.discount > 0 ? `<div style="text-decoration:line-through; font-size:1rem; color:var(--danger); display:inline-block; margin-left:8px;">${formatINR(plan.price)}</div>` : ''}
        <span>/ plan</span>
      </div>
      <div class="plan-duration">
        📅 ${plan.duration_days} day${plan.duration_days !== 1 ? 's' : ''}
      </div>
      <button class="btn btn-primary" id="subscribe-btn-${plan.plan_id}" data-plan-id="${plan.plan_id}">
        Subscribe Now
      </button>
    `;
    grid.appendChild(card);
    $(`#subscribe-btn-${plan.plan_id}`).addEventListener('click', () => subscribeToPlan(plan));
  });
}

async function subscribeToPlan(plan) {
  const user = getUser();
  if (!user) {
    showToast('Please sign in to subscribe.', 'error');
    showPage('login');
    return;
  }

  const btn = $(`#subscribe-btn-${plan.plan_id}`);
  btn.disabled = true;
  btn.textContent = 'Processing…';

  try {
    const pmRes = await fetch(`${API_BASE}/payment_methods/get_methods.php?user_id=${user.user_id}`);
    const pmData = await pmRes.json();
    if(!pmData.success || pmData.methods.length === 0) {
       showToast('Please add a Payment Method in your Dashboard before subscribing.', 'error');
       showPage('dashboard');
       btn.disabled = false;
       btn.textContent = 'Subscribe Now';
       return;
    }

    const methodId = pmData.methods[0].method_id;
    const payload = { user_id: user.user_id, plan_id: plan.plan_id, method_id: methodId };
    if (currentCoupon.code) payload.coupon_code = currentCoupon.code;

    const res  = await fetch(`${API_BASE}/subscriptions/subscribe.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      showToast(`✅ Subscribed to ${plan.name} successfully!`, 'success');
      showPage('dashboard');
    } else {
      showToast(data.message || 'Subscription failed.', 'error');
    }
  } catch {
    showToast('Network error. Please try again.', 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Subscribe Now';
  }
}

/* =============================================================
   DASHBOARD PAGE
   ============================================================= */
async function initDashboardPage() {
  const user = getUser();

  // Update user chip
  const avatarEl = $('#user-avatar');
  const nameEl   = $('#user-name-display');
  if (avatarEl && user) avatarEl.textContent = getInitials(user.name);
  if (nameEl  && user) nameEl.textContent    = user.name || user.email;

  await Promise.all([
    loadSubscriptions(),
    loadPayments(),
    loadNotifications(),
    loadPaymentMethods(),
    loadInvoices()
  ]);
}

async function loadSubscriptions() {
  const user = getUser();
  const grid  = $('#subscriptions-grid');
  const statsRow = $('#stats-row');
  if (!grid) return;

  grid.innerHTML = `
    <div class="state-container" style="grid-column:1/-1;">
      <div class="spinner"></div>
      <p>Loading subscriptions…</p>
    </div>`;

  try {
    const res  = await fetch(`${API_BASE}/subscriptions/get_subscriptions.php?user_id=${user.user_id}`);
    const data = await res.json();

    if (!data.success) throw new Error(data.message);

    const subs = data.subscriptions;

    // Update stats
    if (statsRow) {
      const active    = subs.filter(s => s.status === 'ACTIVE').length;
      const cancelled = subs.filter(s => s.status === 'CANCELLED').length;
      const expired   = subs.filter(s => s.status === 'EXPIRED').length;

      statsRow.innerHTML = `
        <div class="stat-card">
          <div class="stat-value" id="stat-total">${subs.length}</div>
          <div class="stat-label">Total</div>
        </div>
        <div class="stat-card">
          <div class="stat-value" style="color:var(--success)" id="stat-active">${active}</div>
          <div class="stat-label">Active</div>
        </div>
        <div class="stat-card">
          <div class="stat-value" style="color:var(--danger)" id="stat-cancelled">${cancelled}</div>
          <div class="stat-label">Cancelled</div>
        </div>
        <div class="stat-card">
          <div class="stat-value" style="color:var(--text-muted)" id="stat-expired">${expired}</div>
          <div class="stat-label">Expired</div>
        </div>
      `;
    }

    if (!subs.length) {
      grid.innerHTML = `
        <div class="state-container" style="grid-column:1/-1;">
          <div class="state-icon">📂</div>
          <h3>No Subscriptions Yet</h3>
          <p>Browse our plans and subscribe to get started.</p>
        </div>`;
      return;
    }

    grid.innerHTML = '';
    subs.forEach(sub => {
      const card = document.createElement('div');
      card.className = 'subscription-card';
      card.id = `sub-card-${sub.sub_id}`;
      card.innerHTML = `
        <div class="sub-header">
          <div class="sub-plan-name">${escHtml(sub.plan_name)}</div>
          <span class="status-badge ${sub.status}">${sub.status}</span>
        </div>
        <div class="sub-divider"></div>
        <div class="sub-meta">
          <div class="sub-meta-item">
            <div class="label">Start Date</div>
            <div class="value">${formatDate(sub.start_date)}</div>
          </div>
          <div class="sub-meta-item">
            <div class="label">End Date</div>
            <div class="value">${formatDate(sub.end_date)}</div>
          </div>
          <div class="sub-meta-item">
            <div class="label">Price</div>
            <div class="value">${formatINR(sub.price)}</div>
          </div>
          <div class="sub-meta-item">
            <div class="label">Auto-Renew</div>
            <div class="value">${sub.auto_renew ? '✅ On' : '❌ Off'}</div>
          </div>
        </div>
        ${sub.status === 'ACTIVE'
          ? `<button class="btn btn-danger" id="cancel-btn-${sub.sub_id}" data-sub-id="${sub.sub_id}">
               Cancel Subscription
             </button>`
          : ''}
      `;
      grid.appendChild(card);

      if (sub.status === 'ACTIVE') {
        $(`#cancel-btn-${sub.sub_id}`).addEventListener('click', () => cancelSubscription(sub.sub_id, sub.plan_name));
      }
    });
  } catch {
    grid.innerHTML = `
      <div class="state-container" style="grid-column:1/-1;">
        <div class="state-icon">⚠️</div>
        <h3>Error Loading Subscriptions</h3>
        <p>Could not connect to the server.</p>
      </div>`;
  }
}

async function cancelSubscription(subId, planName) {
  const user = getUser();
  if (!user) return;

  const btn = $(`#cancel-btn-${subId}`);
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Cancelling…';
  }

  try {
    const res  = await fetch(`${API_BASE}/subscriptions/cancel.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ sub_id: subId, user_id: user.user_id })
    });
    const data = await res.json();

    if (data.success) {
      showToast(`"${planName}" subscription cancelled.`, 'info');
      // Reload subscriptions to reflect change
      await loadSubscriptions();
    } else {
      showToast(data.message || 'Cancellation failed.', 'error');
      if (btn) { btn.disabled = false; btn.textContent = 'Cancel Subscription'; }
    }
  } catch {
    showToast('Network error. Please try again.', 'error');
    if (btn) { btn.disabled = false; btn.textContent = 'Cancel Subscription'; }
  }
}

async function loadPayments() {
  const user = getUser();
  const section = $('#payments-section');
  if (!section) return;

  try {
    const res = await fetch(`${API_BASE}/payments/get_payments.php?user_id=${user.user_id}`);
    const data = await res.json();
    if (!data.success || !data.payments.length) {
       section.innerHTML = `<p style="text-align:center; color:var(--text-muted);">No payment history found.</p>`;
       return;
    }
    let html = `<table class="data-table">
                  <thead><tr><th>Date</th><th>Plan</th><th>Method</th><th>Amount</th><th>Status</th></tr></thead>
                  <tbody>`;
    data.payments.forEach(p => {
       const methodStr = p.method_type ? `${escHtml(p.method_type)} (...${escHtml(p.method_details).slice(-4)})` : 'N/A';
       html += `<tr>
                  <td>${formatDate(p.payment_date)}</td>
                  <td>${escHtml(p.plan_name)}</td>
                  <td>${methodStr}</td>
                  <td>${formatINR(p.amount)}</td>
                  <td><span class="status-badge ${p.status}">${p.status}</span></td>
                </tr>`;
    });
    html += `</tbody></table>`;
    section.innerHTML = html;

    // Render Spending Chart
    const ctx = document.getElementById('spendingChart');
    if (ctx && window.Chart) {
       const monthsHash = {};
       for (let i = 5; i >= 0; i--) {
           const d = new Date();
           d.setMonth(d.getMonth() - i);
           const mName = d.toLocaleString('default', { month: 'short' });
           monthsHash[mName] = 0;
       }
       data.payments.forEach(p => {
           if (p.status !== 'SUCCESS') return;
           const d = new Date(p.payment_date);
           const mName = d.toLocaleString('default', { month: 'short' });
           if (monthsHash[mName] !== undefined) {
               monthsHash[mName] += parseFloat(p.amount);
           }
       });
       
       if(window.spendingChartInstance) {
           window.spendingChartInstance.destroy();
       }
       window.spendingChartInstance = new Chart(ctx, {
           type: 'line',
           data: {
               labels: Object.keys(monthsHash),
               datasets: [{
                   label: 'Monthly Spending (₹)',
                   data: Object.values(monthsHash),
                   borderColor: '#6c63ff',
                   backgroundColor: 'rgba(108, 99, 255, 0.1)',
                   borderWidth: 3,
                   fill: true,
                   tension: 0.4,
                   pointBackgroundColor: '#22d17a',
                   pointRadius: 4
               }]
           },
           options: {
               responsive: true,
               maintainAspectRatio: false,
               plugins: { legend: { display: false } },
               scales: {
                   y: { ticks: { color: '#9ba3c5' }, grid: { color: 'rgba(255,255,255,0.05)' }, beginAtZero: true },
                   x: { ticks: { color: '#9ba3c5' }, grid: { display: false } }
               }
           }
       });
    }
  } catch {
    section.innerHTML = `<p style="text-align:center; color:var(--danger);">Error loading payments.</p>`;
  }
}

async function loadPaymentMethods() {
  const user = getUser();
  const list = $('#payment-methods-list');
  if(!list) return;

  try {
    const res = await fetch(`${API_BASE}/payment_methods/get_methods.php?user_id=${user.user_id}`);
    const data = await res.json();
    if (!data.success || !data.methods.length) {
       list.innerHTML = `<p style="color:var(--text-muted);">No payment methods saved.</p>`;
    } else {
       list.innerHTML = '';
       data.methods.forEach(m => {
          list.innerHTML += `<div style="background:var(--bg-secondary); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
             <div style="font-weight:600; color:var(--text-primary);">${escHtml(m.type)}</div>
             <div style="color:var(--text-secondary);">...${escHtml(m.details).slice(-4)}</div>
          </div>`;
       });
    }
  } catch {
    list.innerHTML = `<p style="color:var(--danger);">Error loading methods.</p>`;
  }

  const btnAdd = $('#btn-add-method');
  if(btnAdd && !btnAdd.dataset.init) {
     btnAdd.dataset.init = '1';
     btnAdd.addEventListener('click', async () => {
         const type = $('#new-method-type').value;
         const details = $('#new-method-details').value.trim();
         if(!details) return showToast('Please enter method details', 'error');

         btnAdd.disabled = true;
         try {
            const addRes = await fetch(`${API_BASE}/payment_methods/add_method.php`, {
               method: 'POST',
               headers: {'Content-Type': 'application/json'},
               body: JSON.stringify({ user_id: user.user_id, type, details })
            });
            const addData = await addRes.json();
            if(addData.success) {
               showToast('Payment method saved!', 'success');
               $('#new-method-details').value = '';
               loadPaymentMethods();
            } else {
               showToast(addData.message || 'Failed to save', 'error');
            }
         } catch {
            showToast('Network error', 'error');
         } finally {
            btnAdd.disabled = false;
         }
     });
  }
}

async function loadInvoices() {
  const user = getUser();
  const section = $('#invoices-section');
  if (!section) return;

  try {
    const res = await fetch(`${API_BASE}/invoices/get_invoices.php?user_id=${user.user_id}`);
    const data = await res.json();
    if (!data.success || !data.invoices.length) {
       section.innerHTML = `<p style="text-align:center; color:var(--text-muted);">No invoices found.</p>`;
       return;
    }
    let html = `<table class="data-table">
                  <thead><tr><th>Invoice ID</th><th>Date</th><th>Plan</th><th>Amount</th><th>Action</th></tr></thead>
                  <tbody>`;
    data.invoices.forEach(i => {
       html += `<tr>
                  <td style="color:var(--text-muted); font-weight:600;">#INV-${i.invoice_id.toString().padStart(4, '0')}</td>
                  <td>${formatDate(i.generated_date)}</td>
                  <td>${escHtml(i.plan_name)}</td>
                  <td>${formatINR(i.amount)}</td>
                  <td><a class="btn btn-outline" href="${API_BASE}/invoices/download_invoice.php?invoice_id=${i.invoice_id}&user_id=${user.user_id}" target="_blank" style="padding:4px 12px; font-size:0.75rem;">Download</a></td>
                </tr>`;
    });
    html += `</tbody></table>`;
    section.innerHTML = html;
  } catch {
    section.innerHTML = `<p style="text-align:center; color:var(--danger);">Error loading invoices.</p>`;
  }
}

async function loadNotifications() {
  const user = getUser();
  const section = $('#notifications-section');
  if (!section) return;

  try {
    const res = await fetch(`${API_BASE}/notifications/get_notifications.php?user_id=${user.user_id}`);
    const data = await res.json();
    if (!data.success || !data.notifications.length) {
       section.innerHTML = `<p style="text-align:center; color:var(--text-muted);">You have no notifications.</p>`;
       return;
    }
    section.innerHTML = '';
    data.notifications.forEach(n => {
       const el = document.createElement('div');
       el.className = 'notification-card';
       el.innerHTML = `
         <div class="notif-message">${escHtml(n.message)}</div>
         <div class="notif-time">${formatDate(n.created_at)}</div>
       `;
       section.appendChild(el);
    });
  } catch {
    section.innerHTML = `<p style="text-align:center; color:var(--danger);">Error loading notifications.</p>`;
  }
}

/* =============================================================
   SECURITY — HTML escape
   ============================================================= */
function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

/* =============================================================
   BOOTSTRAP — runs on DOM ready
   ============================================================= */
document.addEventListener('DOMContentLoaded', () => {
  const user = getUser();

  renderNavbar();
  initRegisterPage();
  initLoginPage();

  // Attach cross-page links
  const goRegister = $('#go-register');
  const goLogin    = $('#go-login');
  if (goRegister) goRegister.addEventListener('click', () => showPage('register'));
  if (goLogin)    goLogin.addEventListener('click',    () => showPage('login'));

  // Determine starting page
  if (user) {
    showPage('dashboard');
  } else {
    showPage('login');
  }
});
