/**
 * BITEORA - Food-Tech Client Controller & Live Poller
 * "Skip the Queue. Eat Smarter."
 */

const Biteora = {
  // Cart Management
  getCart() {
    try {
      return JSON.parse(localStorage.getItem('cb_cart')) || [];
    } catch (e) {
      return [];
    }
  },

  saveCart(cart) {
    localStorage.setItem('cb_cart', JSON.stringify(cart));
    this.updateCartBadge();
  },

  addToCart(id, name, price, image, isVeg = 1) {
    let cart = this.getCart();
    let item = cart.find(i => i.id == id);
    if (item) {
      item.qty += 1;
    } else {
      cart.push({
        id: parseInt(id),
        name: name,
        price: parseFloat(price),
        image: image || 'images/sample-food.jpg',
        isVeg: isVeg,
        qty: 1
      });
    }
    this.saveCart(cart);
    this.showToast(`Added "${name}" to cart! 🍔`);
  },

  updateQty(id, delta) {
    let cart = this.getCart();
    let item = cart.find(i => i.id == id);
    if (item) {
      item.qty += delta;
      if (item.qty <= 0) {
        cart = cart.filter(i => i.id != id);
      }
    }
    this.saveCart(cart);
    this.renderCartUI();
  },

  removeItem(id) {
    let cart = this.getCart().filter(i => i.id != id);
    this.saveCart(cart);
    this.renderCartUI();
    this.showToast('Item removed from cart');
  },

  clearCart() {
    localStorage.removeItem('cb_cart');
    this.updateCartBadge();
  },

  getCartCount() {
    return this.getCart().reduce((acc, item) => acc + item.qty, 0);
  },

  getCartTotal() {
    return this.getCart().reduce((acc, item) => acc + (item.price * item.qty), 0);
  },

  updateCartBadge() {
    const badge = document.getElementById('cb-cart-badge');
    if (badge) {
      const count = this.getCartCount();
      badge.textContent = count;
      badge.style.display = count > 0 ? 'inline-flex' : 'none';
    }
  },

  renderCartUI() {
    const cartContainer = document.getElementById('cb-cart-items');
    const totalEl = document.getElementById('cb-cart-total');
    const grandTotalEl = document.getElementById('cb-cart-grand-total');
    const checkoutBtn = document.getElementById('cb-checkout-btn');
    const emptyState = document.getElementById('cb-cart-empty');
    const cartFormInputs = document.getElementById('cb-cart-form-inputs');

    if (!cartContainer) return;

    const cart = this.getCart();
    if (cart.length === 0) {
      cartContainer.innerHTML = '';
      if (emptyState) emptyState.style.display = 'block';
      if (totalEl) totalEl.textContent = '₹0.00';
      if (grandTotalEl) grandTotalEl.textContent = '₹0.00';
      if (checkoutBtn) checkoutBtn.setAttribute('disabled', 'disabled');
      if (cartFormInputs) cartFormInputs.innerHTML = '';
      return;
    }

    if (emptyState) emptyState.style.display = 'none';
    if (checkoutBtn) checkoutBtn.removeAttribute('disabled');

    let html = '';
    let formHiddenInputs = '';

    cart.forEach(item => {
      const itemTotal = (item.price * item.qty).toFixed(2);
      formHiddenInputs += `<input type="hidden" name="${item.id}" value="${item.qty}">`;
      
      html += `
        <div class="cart-item-row" style="display:flex; align-items:center; justify-content:space-between; padding:0.85rem 0; border-bottom:1px solid var(--border-subtle);">
          <div style="display:flex; align-items:center; gap:0.85rem;">
            <img src="${item.image}" alt="${item.name}" style="width:48px; height:48px; object-fit:cover; border-radius:8px; border:1px solid var(--border-subtle);">
            <div>
              <div style="font-weight:700; font-size:0.92rem; color:var(--text-primary);">${item.name}</div>
              <div style="font-size:0.8rem; color:var(--text-secondary);">₹${item.price.toFixed(2)} each</div>
            </div>
          </div>
          <div style="display:flex; align-items:center; gap:1.15rem;">
            <div class="qty-control">
              <button type="button" class="qty-btn" onclick="Biteora.updateQty(${item.id}, -1)">-</button>
              <span class="qty-val">${item.qty}</span>
              <button type="button" class="qty-btn" onclick="Biteora.updateQty(${item.id}, 1)">+</button>
            </div>
            <div style="font-weight:800; font-family:var(--font-display); min-width:65px; text-align:right; color:var(--text-primary);">₹${itemTotal}</div>
            <button type="button" onclick="Biteora.removeItem(${item.id})" style="background:none; border:none; color:var(--danger); cursor:pointer; padding:0.3rem;" title="Remove">✕</button>
          </div>
        </div>
      `;
    });

    cartContainer.innerHTML = html;
    if (cartFormInputs) cartFormInputs.innerHTML = formHiddenInputs;

    const total = this.getCartTotal();
    if (totalEl) totalEl.textContent = `₹${total.toFixed(2)}`;
    if (grandTotalEl) grandTotalEl.textContent = `₹${total.toFixed(2)}`;
    
    const hiddenTotal = document.getElementById('cb-hidden-total');
    if (hiddenTotal) hiddenTotal.value = total;
  },

  // Toast System
  showToast(msg, type = 'success') {
    let container = document.getElementById('cb-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'cb-toast-container';
      container.style.cssText = 'position:fixed; bottom:24px; left:24px; z-index:9999; display:flex; flex-direction:column; gap:0.5rem;';
      document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = `badge badge-${type === 'success' ? 'brand' : 'rose'}`;
    toast.style.cssText = 'padding:0.75rem 1.15rem; font-size:0.85rem; border-radius:10px; background:#FFFFFF; border:1px solid var(--border-subtle); color:var(--text-primary); box-shadow:var(--shadow-lg); animation:fadeIn 0.2s ease; margin:0;';
    toast.innerHTML = `<span>${msg}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transition = 'opacity 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  },

  // Biteora AI Assistant
  askAI(query) {
    const resultsContainer = document.getElementById('cb-ai-results');
    if (!resultsContainer) return;

    resultsContainer.innerHTML = '<div style="text-align:center; padding:1.5rem; color:var(--text-secondary);">🤖 Biteora AI is searching live cafeteria matches...</div>';

    fetch(`api/ai-recommend.php?q=${encodeURIComponent(query)}`)
      .then(res => res.json())
      .then(data => {
        if (!data.success || !data.items || data.items.length === 0) {
          resultsContainer.innerHTML = `<div style="text-align:center; padding:1.5rem; color:var(--text-secondary);">${data.message || 'No matching food found. Try asking for "under 100", "spicy snack", or "drinks"!'}</div>`;
          return;
        }

        let html = `<div style="font-size:0.82rem; color:var(--brand-primary); font-weight:700; margin-bottom:0.65rem;">✨ ${data.message}</div><div style="display:flex; flex-direction:column; gap:0.65rem;">`;
        data.items.forEach(item => {
          html += `
            <div style="display:flex; align-items:center; justify-content:space-between; background:var(--bg-surface-2); border:1px solid var(--border-subtle); padding:0.65rem 0.85rem; border-radius:10px;">
              <div style="display:flex; align-items:center; gap:0.65rem;">
                <img src="${item.image}" alt="${item.name}" style="width:42px; height:42px; object-fit:cover; border-radius:6px;">
                <div>
                  <div style="font-weight:700; font-size:0.88rem; color:var(--text-primary);">${item.name}</div>
                  <div style="font-size:0.75rem; color:var(--text-secondary);">${item.category_name || 'Cafeteria'} • ⭐ ${item.rating || '4.8'}</div>
                </div>
              </div>
              <div style="display:flex; align-items:center; gap:0.65rem;">
                <span style="font-weight:800; font-family:var(--font-display); color:var(--brand-primary);">₹${parseFloat(item.price).toFixed(2)}</span>
                <button type="button" onclick="Biteora.addToCart(${item.id}, '${item.name.replace(/'/g, "\\'")}', ${item.price}, '${item.image}', ${item.is_veg});" class="btn btn-primary btn-sm" style="font-size:0.75rem; padding:0.35rem 0.65rem;">Add +</button>
              </div>
            </div>
          `;
        });
        html += '</div>';
        resultsContainer.innerHTML = html;
      })
      .catch(err => {
        resultsContainer.innerHTML = '<div style="color:var(--danger); text-align:center; padding:1rem;">Failed to fetch AI recommendations.</div>';
      });
  }
};

// Aliases for seamless backwards compatibility
const CampusBite = Biteora;
window.Biteora = Biteora;
window.CampusBite = Biteora;

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
  Biteora.updateCartBadge();
  Biteora.renderCartUI();

  // Universal food image fallback recovery
  const fallbacks = [
    'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=600&q=80',
    'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400"><rect width="600" height="400" fill="%23FFF0EB"/><circle cx="300" cy="180" r="50" fill="%23FFD8CC"/><text x="300" y="270" font-family="sans-serif" font-size="20" font-weight="bold" fill="%23FF5A36" text-anchor="middle">Biteora Fresh Dish</text></svg>'
  ];

  document.querySelectorAll('.food-img-wrap img, .menu-card img').forEach(img => {
    img.addEventListener('error', function() {
      const step = parseInt(this.dataset.errStep || '0', 10);
      if (step < fallbacks.length) {
        this.dataset.errStep = step + 1;
        this.src = fallbacks[step];
      }
    });
  });
});
