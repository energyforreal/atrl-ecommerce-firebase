/**
 * Enhanced Dropdown Menu System
 * Provides smooth, user-friendly dropdown behavior with proper timing and interactions
 */

class DropdownManager {
  constructor() {
    this.dropdowns = new Map();
    this.activeDropdown = null;
    this.closeTimeout = null;
    this.hoverTimeout = null;
    this.isInitialized = false;
    
    this.init();
  }

  init() {
    if (this.isInitialized) return;
    
    // Find all dropdowns on the page
    const dropdownElements = document.querySelectorAll('.dropdown');
    
    dropdownElements.forEach(dropdown => {
      this.setupDropdown(dropdown);
    });

    // Setup global event listeners
    this.setupGlobalListeners();
    
    this.isInitialized = true;
    console.log(`DropdownManager initialized with ${dropdownElements.length} dropdowns`);
  }

  setupDropdown(dropdownElement) {
    const toggle = dropdownElement.querySelector('.dropdown-toggle');
    const menu = dropdownElement.querySelector('.dropdown-menu');
    
    if (!toggle || !menu) return;

    const dropdownId = `dropdown-${Math.random().toString(36).substr(2, 9)}`;
    dropdownElement.setAttribute('data-dropdown-id', dropdownId);

    // Store dropdown data
    this.dropdowns.set(dropdownId, {
      element: dropdownElement,
      toggle: toggle,
      menu: menu,
      isOpen: false
    });

    // Add event listeners
    this.addDropdownListeners(dropdownElement, dropdownId);
  }

  addDropdownListeners(dropdownElement, dropdownId) {
    const dropdown = this.dropdowns.get(dropdownId);
    const { toggle, menu } = dropdown;

    // Mouse enter on dropdown container
    dropdownElement.addEventListener('mouseenter', (e) => {
      this.clearTimeouts();
      this.openDropdown(dropdownId);
    });

    // Mouse leave on dropdown container
    dropdownElement.addEventListener('mouseleave', (e) => {
      this.scheduleClose(dropdownId);
    });

    // Click on toggle (for mobile/touch devices)
    toggle.addEventListener('click', (e) => {
      e.preventDefault();
      this.toggleDropdown(dropdownId);
    });

    // Prevent menu clicks from closing dropdown
    menu.addEventListener('click', (e) => {
      e.stopPropagation();
    });

    // Keyboard navigation
    toggle.addEventListener('keydown', (e) => {
      this.handleKeyboardNavigation(e, dropdownId);
    });

    // Focus management
    menu.addEventListener('keydown', (e) => {
      this.handleMenuKeyboardNavigation(e, dropdownId);
    });
  }

  setupGlobalListeners() {
    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
      const clickedDropdown = e.target.closest('.dropdown');
      
      if (!clickedDropdown && this.activeDropdown) {
        this.closeActiveDropdown();
      }
    });

    // Handle escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.activeDropdown) {
        this.closeActiveDropdown();
        this.focusToggle(this.activeDropdown);
      }
    });

    // Handle window resize
    window.addEventListener('resize', () => {
      if (this.activeDropdown) {
        this.closeActiveDropdown();
      }
    });

    // Handle scroll (optional - close on scroll)
    let scrollTimeout;
    window.addEventListener('scroll', () => {
      if (this.activeDropdown) {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
          this.closeActiveDropdown();
        }, 150);
      }
    });
  }

  openDropdown(dropdownId) {
    const dropdown = this.dropdowns.get(dropdownId);
    if (!dropdown || dropdown.isOpen) return;

    // Close any other open dropdowns
    if (this.activeDropdown && this.activeDropdown !== dropdownId) {
      this.closeDropdown(this.activeDropdown);
    }

    // Open the dropdown
    dropdown.isOpen = true;
    dropdown.menu.style.display = 'block';
    dropdown.menu.setAttribute('aria-expanded', 'true');
    dropdown.toggle.setAttribute('aria-expanded', 'true');
    
    // Trigger reflow for smooth animation
    dropdown.menu.offsetHeight;
    
    // Apply open styles
    dropdown.menu.style.opacity = '1';
    dropdown.menu.style.visibility = 'visible';
    dropdown.menu.style.transform = 'translateY(0)';
    
    this.activeDropdown = dropdownId;
    dropdown.element.classList.add('dropdown-open');
  }

  closeDropdown(dropdownId) {
    const dropdown = this.dropdowns.get(dropdownId);
    if (!dropdown || !dropdown.isOpen) return;

    dropdown.isOpen = false;
    dropdown.menu.setAttribute('aria-expanded', 'false');
    dropdown.toggle.setAttribute('aria-expanded', 'false');
    
    // Apply close styles
    dropdown.menu.style.opacity = '0';
    dropdown.menu.style.visibility = 'hidden';
    dropdown.menu.style.transform = 'translateY(-10px)';
    
    dropdown.element.classList.remove('dropdown-open');
    
    if (this.activeDropdown === dropdownId) {
      this.activeDropdown = null;
    }

    // Hide menu after animation
    setTimeout(() => {
      if (!dropdown.isOpen) {
        dropdown.menu.style.display = 'none';
      }
    }, 300);
  }

  toggleDropdown(dropdownId) {
    const dropdown = this.dropdowns.get(dropdownId);
    if (!dropdown) return;

    if (dropdown.isOpen) {
      this.closeDropdown(dropdownId);
    } else {
      this.openDropdown(dropdownId);
    }
  }

  scheduleClose(dropdownId) {
    this.clearTimeouts();
    this.closeTimeout = setTimeout(() => {
      this.closeDropdown(dropdownId);
    }, 300); // 300ms delay as requested
  }

  closeActiveDropdown() {
    if (this.activeDropdown) {
      this.closeDropdown(this.activeDropdown);
    }
  }

  clearTimeouts() {
    if (this.closeTimeout) {
      clearTimeout(this.closeTimeout);
      this.closeTimeout = null;
    }
    if (this.hoverTimeout) {
      clearTimeout(this.hoverTimeout);
      this.hoverTimeout = null;
    }
  }

  handleKeyboardNavigation(e, dropdownId) {
    switch (e.key) {
      case 'Enter':
      case ' ':
        e.preventDefault();
        this.toggleDropdown(dropdownId);
        break;
      case 'ArrowDown':
        e.preventDefault();
        this.openDropdown(dropdownId);
        this.focusFirstMenuItem(dropdownId);
        break;
    }
  }

  handleMenuKeyboardNavigation(e, dropdownId) {
    const dropdown = this.dropdowns.get(dropdownId);
    const menuItems = dropdown.menu.querySelectorAll('a');
    const currentIndex = Array.from(menuItems).indexOf(document.activeElement);

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        const nextIndex = (currentIndex + 1) % menuItems.length;
        menuItems[nextIndex].focus();
        break;
      case 'ArrowUp':
        e.preventDefault();
        const prevIndex = currentIndex <= 0 ? menuItems.length - 1 : currentIndex - 1;
        menuItems[prevIndex].focus();
        break;
      case 'Escape':
        e.preventDefault();
        this.closeDropdown(dropdownId);
        this.focusToggle(dropdownId);
        break;
      case 'Tab':
        // Allow natural tab behavior but close dropdown
        setTimeout(() => {
          if (!dropdown.element.contains(document.activeElement)) {
            this.closeDropdown(dropdownId);
          }
        }, 0);
        break;
    }
  }

  focusToggle(dropdownId) {
    const dropdown = this.dropdowns.get(dropdownId);
    if (dropdown) {
      dropdown.toggle.focus();
    }
  }

  focusFirstMenuItem(dropdownId) {
    const dropdown = this.dropdowns.get(dropdownId);
    if (dropdown) {
      const firstMenuItem = dropdown.menu.querySelector('a');
      if (firstMenuItem) {
        firstMenuItem.focus();
      }
    }
  }

  // Public API methods
  refresh() {
    this.dropdowns.clear();
    this.activeDropdown = null;
    this.clearTimeouts();
    this.init();
  }

  destroy() {
    this.dropdowns.clear();
    this.activeDropdown = null;
    this.clearTimeouts();
    this.isInitialized = false;
  }
}

// Initialize when DOM is ready
let dropdownManager;

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    dropdownManager = new DropdownManager();
  });
} else {
  dropdownManager = new DropdownManager();
}

// Export for global access
window.DropdownManager = DropdownManager;
window.dropdownManager = dropdownManager;
