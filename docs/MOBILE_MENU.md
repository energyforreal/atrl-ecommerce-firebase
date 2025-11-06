# Mobile Hamburger Side Menu – Implementation Guide

## Scope

Enable the existing mobile hamburger side menu on every page in `static-site/` by standardizing required HTML, CSS, and JS. This doc provides copy/paste blocks and verification steps.

## Files Involved

- HTML pages: all files under `static-site/*.html` (e.g., `index.html`, `shop.html`, `about.html`, etc.)
- Styles: `static-site/css/styles.css` (already contains mobile menu styles)
- Scripts: `static-site/js/app.js` (already initializes the mobile menu), `static-site/js/dropdown.js` (desktop dropdown)

## What You Need To Add On Every Page

### 1) Add header with logo, desktop nav, and hamburger button

Use this exact structure near the top of `<body>`:

```html
<header class="site-header">
  <div class="container header-inner">
    <a class="logo" href="index.html"><img src="logo.png" alt="ATTRAL" style="height:32px; width:auto;" /></a>
    <button class="hamburger-menu" aria-label="Toggle menu" id="hamburger-menu-btn">
      <span></span>
      <span></span>
      <span></span>
    </button>
    <nav class="nav">
      <a href="index.html" class="active">Home</a>
      <a href="shop.html">Shop</a>
      <a href="account.html">My Account</a>
      <a href="affiliates.html">Affiliate Central</a>
      <a href="contact.html">Contact Us</a>
      <div class="dropdown">
        <a href="#" class="dropdown-toggle" role="button" aria-haspopup="true" aria-expanded="false" aria-label="More menu">More ▾</a>
        <div class="dropdown-menu" role="menu" aria-labelledby="dropdown-toggle">
          <a href="about.html" role="menuitem">About Us</a>
          <a href="blog.html" role="menuitem">Blog</a>
          <a href="privacy.html" role="menuitem">Privacy</a>
          <a href="terms.html" role="menuitem">Terms</a>
        </div>
      </div>
      <a href="cart.html" class="cart-link">Cart <span id="cart-count" class="badge">0</span></a>
    </nav>
  </div>
</header>
```

**Important:** Update the `class="active"` on the proper link for each page (e.g., on `shop.html`, make the "Shop" link active).

### 2) Add the mobile overlay and drawer immediately after the header

```html
<!-- Mobile Navigation Overlay -->
<div class="mobile-nav-overlay" id="mobile-nav-overlay"></div>

<!-- Mobile Navigation Menu -->
<div class="mobile-nav-menu" id="mobile-nav-menu">
  <div class="mobile-nav-menu-header">
    <a class="logo" href="index.html">ATTRAL</a>
    <button class="mobile-nav-close" aria-label="Close menu" id="mobile-nav-close">×</button>
  </div>
  <div class="mobile-nav-menu-content">
    <nav class="nav">
      <a href="index.html" class="active">Home</a>
      <a href="shop.html">Shop</a>
      <a href="account.html">My Account</a>
      <a href="affiliates.html">Affiliate Central</a>
      <a href="contact.html">Contact Us</a>
      <div class="dropdown">
        <a href="#" class="dropdown-toggle" role="button" aria-haspopup="true" aria-expanded="false" aria-label="More menu">More ▾</a>
        <div class="dropdown-menu" role="menu" aria-labelledby="dropdown-toggle">
          <a href="about.html" role="menuitem">About Us</a>
          <a href="blog.html" role="menuitem">Blog</a>
          <a href="privacy.html" role="menuitem">Privacy</a>
          <a href="terms.html" role="menuitem">Terms</a>
        </div>
      </div>
      <a href="cart.html" class="cart-link">Cart <span id="mobile-cart-count" class="badge">0</span></a>
    </nav>
  </div>
</div>
```

**Important:** Keep the IDs exactly as above: `hamburger-menu-btn`, `mobile-nav-overlay`, `mobile-nav-menu`, `mobile-nav-close`, `cart-count`, `mobile-cart-count`. The JS auto-flattens the "More" dropdown inside the drawer; you don't need extra logic.

### 3) Ensure CSS link exists

In the `<head>` section, include:

```html
<link rel="stylesheet" href="css/styles.css?v=6" />
```

No additional CSS required; `styles.css` already includes `.hamburger-menu`, `.mobile-nav-overlay`, `.mobile-nav-menu`, responsive rules, and body scroll lock via `body.menu-open`.

### 4) Ensure JS includes at the end of `<body>`

Before the closing `</body>` tag, include:

```html
<script src="js/config.js"></script>
<script src="js/app.js?v=6"></script>
<script src="js/firebase.js"></script>
<script src="js/dropdown.js"></script>
```

`app.js` contains the mobile menu initializer that wires up open/close, overlay, escape key, resize handling, and cart count sync.

## How It Works (already implemented)

- CSS hides desktop `.nav` and shows the `.hamburger-menu` below 768px; the drawer slides in from the right.
- JS adds/removes `.active` on overlay/menu and toggles `body.menu-open` to prevent scroll.
- Clicking links, overlay, close button, or pressing Escape closes the drawer. Resizing above 768px also closes.

**Key JS entry points:**

The mobile menu initialization is in `static-site/js/app.js` (lines 793-926):

```javascript
// Mobile Navigation Menu Toggle
(function() {
  'use strict';
  
  function initMobileMenu() {
    const hamburgerBtn = document.getElementById('hamburger-menu-btn');
    const mobileNavOverlay = document.getElementById('mobile-nav-overlay');
    const mobileNavMenu = document.getElementById('mobile-nav-menu');
    const mobileNavClose = document.getElementById('mobile-nav-close');
    
    if (!hamburgerBtn || !mobileNavOverlay || !mobileNavMenu) {
      return; // Mobile menu elements not present on this page
    }
    
    function openMenu() {
      hamburgerBtn.classList.add('active');
      mobileNavOverlay.classList.add('active');
      mobileNavMenu.classList.add('active');
      document.body.classList.add('menu-open');
    }
    
    function closeMenu() {
      hamburgerBtn.classList.remove('active');
      mobileNavOverlay.classList.remove('active');
      mobileNavMenu.classList.remove('active');
      document.body.classList.remove('menu-open');
    }
    
    // Event handlers for hamburger, close button, overlay, links, Escape key, resize, etc.
  }
  
  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileMenu);
  } else {
    initMobileMenu();
  }
})();
```

## Page-by-Page Checklist

For each HTML page in `static-site/`:

- [ ] Add the header block (Section 1) and set the correct `active` link.
- [ ] Add the overlay/drawer block (Section 2) just after the header.
- [ ] Verify `<head>` includes `css/styles.css`.
- [ ] Verify end of `<body>` includes `js/app.js` and `js/dropdown.js`.

## Accessibility Notes

- Buttons include `aria-label` attributes.
- Escape closes the menu; overlay click closes.
- Consider adding focus management later if needed; not required for initial rollout.

## Manual Test Steps

1. Narrow browser to <768px.
2. Tap hamburger: menu slides in; background scroll locks.
3. Tap overlay or ×: menu closes.
4. Tap a nav link: menu closes and page navigates.
5. Resize above 768px while open: drawer closes.
6. Cart badge in header and drawer stay in sync.

## Troubleshooting

- **Drawer doesn't appear:** Ensure IDs match exactly and `app.js` is loaded after HTML.
- **Styles off:** Confirm `css/styles.css` is linked and not cached (bust with `?v=6`).
- **Links duplicate under "More" in drawer:** This is expected; JS flattens submenu automatically.

## Implementation Status

All main customer-facing pages should have the mobile menu implemented. Admin pages and special utility pages may not require the full navigation structure.

