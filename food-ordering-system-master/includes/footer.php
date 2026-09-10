  <!-- CampusBite AI Floating Assistant Button -->
  <button type="button" class="btn btn-primary" onclick="document.getElementById('cb-ai-modal').classList.add('active');" style="position:fixed; bottom:24px; right:24px; z-index:99; border-radius:999px; padding:0.75rem 1.25rem; box-shadow:var(--shadow-lg); font-size:0.9rem;">
    <i class="fa-solid fa-wand-magic-sparkles"></i>
    <span>CampusBite AI</span>
  </button>

  <!-- CampusBite AI Modal -->
  <div id="cb-ai-modal" class="modal-overlay">
    <div class="modal-card">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
        <div style="display:flex; align-items:center; gap:0.65rem;">
          <div style="width:36px; height:36px; border-radius:8px; background:linear-gradient(135deg, var(--brand-primary), var(--brand-secondary)); display:flex; align-items:center; justify-content:center; color:#fff;">
            <i class="fa-solid fa-robot"></i>
          </div>
          <div>
            <h3 style="font-family:var(--font-display); font-size:1.2rem; font-weight:800; color:#FFFFFF;">CampusBite AI</h3>
            <p style="font-size:0.78rem; color:var(--text-secondary);">Your Smart Campus Cafeteria Concierge</p>
          </div>
        </div>
        <button type="button" onclick="document.getElementById('cb-ai-modal').classList.remove('active');" style="background:none; border:none; color:var(--text-secondary); font-size:1.2rem; cursor:pointer;">✕</button>
      </div>

      <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;">
        Tell me what you're craving or your budget, and I'll find available cafeteria matches!
      </p>

      <!-- Quick prompts -->
      <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:1.25rem;">
        <button type="button" class="cat-pill" style="font-size:0.78rem; padding:0.35rem 0.75rem;" onclick="document.getElementById('cb-ai-input').value='under ₹100'; CampusBite.askAI('under 100');">💰 Under ₹100</button>
        <button type="button" class="cat-pill" style="font-size:0.78rem; padding:0.35rem 0.75rem;" onclick="document.getElementById('cb-ai-input').value='spicy evening snack'; CampusBite.askAI('spicy evening snack');">🌶️ Spicy Snack</button>
        <button type="button" class="cat-pill" style="font-size:0.78rem; padding:0.35rem 0.75rem;" onclick="document.getElementById('cb-ai-input').value='cold beverage'; CampusBite.askAI('cold beverage');">🥤 Cold Drink</button>
        <button type="button" class="cat-pill" style="font-size:0.78rem; padding:0.35rem 0.75rem;" onclick="document.getElementById('cb-ai-input').value='healthy breakfast'; CampusBite.askAI('healthy breakfast');">🥗 Breakfast</button>
      </div>

      <form onsubmit="event.preventDefault(); CampusBite.askAI(document.getElementById('cb-ai-input').value);" style="display:flex; gap:0.65rem; margin-bottom:1.25rem;">
        <input type="text" id="cb-ai-input" class="form-control" placeholder="e.g. I want something spicy under ₹120..." style="background:var(--bg-surface-2);">
        <button type="submit" class="btn btn-primary" style="white-space:nowrap;">
          <i class="fa-solid fa-paper-plane"></i> Ask
        </button>
      </form>

      <div id="cb-ai-results">
        <!-- AI Recommendations will render here -->
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer style="background:var(--bg-surface); border-top:1px solid var(--border-subtle); padding:3.5rem 0 2rem; margin-top:auto;">
    <div class="container">
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:2.5rem; margin-bottom:2.5rem;">
        <div>
          <div class="cb-logo" style="margin-bottom:1rem;">
            <div class="cb-logo-icon">
              <i class="fa-solid fa-utensils"></i>
            </div>
            <span>Campus<span class="text-orange">Bite</span></span>
          </div>
          <p style="font-size:0.88rem; color:var(--text-secondary); line-height:1.6; margin-bottom:1.25rem;">
            Smart Campus Food Ordering & Queue Management Platform. Order fresh meals, skip lines, and enjoy hot food.
          </p>
          <div style="display:inline-flex; align-items:center; gap:0.5rem; background:var(--brand-subtle); border:1px solid rgba(255,90,54,0.3); border-radius:999px; padding:0.35rem 0.85rem; font-size:0.78rem; font-weight:700; color:var(--brand-primary);">
            <i class="fa-solid fa-clock"></i> Cafeteria: 8:00 AM - 10:00 PM
          </div>
        </div>

        <div>
          <h4 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#FFFFFF; margin-bottom:1.15rem;">Quick Links</h4>
          <ul style="list-style:none; display:flex; flex-direction:column; gap:0.65rem; font-size:0.88rem; color:var(--text-secondary);">
            <li><a href="index.php" style="transition:0.15s ease;" onmouseover="this.style.color='#FF5A36'" onmouseout="this.style.color='#A1A1AA'">Home</a></li>
            <li><a href="menu.php" style="transition:0.15s ease;" onmouseover="this.style.color='#FF5A36'" onmouseout="this.style.color='#A1A1AA'">Browse Menu</a></li>
            <li><a href="index.php#how-it-works" style="transition:0.15s ease;" onmouseover="this.style.color='#FF5A36'" onmouseout="this.style.color='#A1A1AA'">How It Works</a></li>
            <li><a href="orders.php" style="transition:0.15s ease;" onmouseover="this.style.color='#FF5A36'" onmouseout="this.style.color='#A1A1AA'">Order Tracking</a></li>
          </ul>
        </div>

        <div>
          <h4 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#FFFFFF; margin-bottom:1.15rem;">Campus Cafeteria</h4>
          <ul style="list-style:none; display:flex; flex-direction:column; gap:0.65rem; font-size:0.88rem; color:var(--text-secondary);">
            <li><i class="fa-solid fa-location-dot text-orange" style="margin-right:0.5rem;"></i> Student Center, Ground Floor</li>
            <li><i class="fa-solid fa-phone text-orange" style="margin-right:0.5rem;"></i> Helpdesk: Ext. 4021</li>
            <li><i class="fa-solid fa-envelope text-orange" style="margin-right:0.5rem;"></i> cafeteria@campusbite.edu</li>
          </ul>
        </div>

        <div>
          <h4 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#FFFFFF; margin-bottom:1.15rem;">Hackathon Demo</h4>
          <p style="font-size:0.82rem; color:var(--text-secondary); line-height:1.5; margin-bottom:0.75rem;">
            Student: <code style="background:rgba(255,255,255,0.06); padding:2px 6px; border-radius:4px; color:var(--brand-primary);">user1</code> / <code style="background:rgba(255,255,255,0.06); padding:2px 6px; border-radius:4px; color:var(--brand-primary);">pass1</code><br>
            Admin: <code style="background:rgba(255,255,255,0.06); padding:2px 6px; border-radius:4px; color:#F59E0B;">root</code> / <code style="background:rgba(255,255,255,0.06); padding:2px 6px; border-radius:4px; color:#F59E0B;">toor</code>
          </p>
          <a href="login.php" class="btn btn-secondary btn-sm" style="width:100%;">Switch Account / Login</a>
        </div>
      </div>

      <div style="border-top:1px solid var(--border-subtle); padding-top:1.5rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; font-size:0.8rem; color:var(--text-muted);">
        <p>© 2026 CampusBite. Smart Campus Food Ordering Platform.</p>
        <p>Built with ❤️ for Campus Hackathon.</p>
      </div>
    </div>
  </footer>

  <!-- Core JavaScript -->
  <script src="js/campusbite.js"></script>
</body>
</html>
