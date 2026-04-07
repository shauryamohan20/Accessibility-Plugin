# Qudra AccessKit WP

A lightweight, secure WordPress accessibility plugin with a floating widget, multilingual support, and a polished admin dashboard. Built for real-world sites — zero dependencies on the frontend, no conflicts with other plugins.

---

## Features

**Accessibility Panel**
- Font size increase / decrease — reads the page's actual font sizes at load time and scales proportionally, including text set by Elementor inline styles and CSS class rules
- High contrast mode
- Invert colors
- Grayscale mode
- Highlight links
- Readable font (OpenDyslexic for LTR; clean system sans-serif fallback for RTL)
- Letter spacing control
- Pause all animations
- Large cursor
- One-click reset

**Multilingual**
- Full UI string support for English, Arabic, and Hebrew
- Auto-detects active language from `<html lang>` and `dir` attributes
- Panel labels, button text, and all controls switch language automatically
- RTL layout auto-mirrors the button and panel to the correct side — pure CSS, no flicker

**Admin Dashboard**
- Dedicated top-level menu item in the WordPress sidebar (AccessKit) with the universal accessibility icon
- Live button preview updates as you change colors and size
- Visual corner selector — click a corner on a miniature page diagram to set position
- Per-feature enable/disable toggles organized by group
- Visibility control — show the widget on the entire site or on selected pages only, with a searchable checkbox list of all published pages and posts

**Technical**
- Zero frontend JavaScript dependencies — pure vanilla JS
- All settings in a single `wp_options` row — one DB call, no custom tables
- Full `localStorage` persistence across sessions with type-validated reads
- Public JS API (`window.AccessKitWP`) for integration with multilingual plugins
- Works with Elementor HTML widgets and standard page builders
- Designed to avoid conflicts — all CSS classes, JS identifiers, and PHP symbols are prefixed

---

## Requirements

- WordPress 6.5 or higher
- PHP 8.0 or higher

---

## Installation

1. Download the zip file
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the zip and click **Install Now**
4. Activate the plugin
5. Go to **AccessKit** in the WordPress sidebar to configure

---

## Configuration

**Button Appearance** — Set background color, icon color, and size (Small / Medium / Large)

**Position** — Choose any of the four corners using the visual selector. RTL languages automatically mirror the position horizontally

**Features** — Enable or disable each accessibility feature individually from the dashboard

**Visibility** — Choose between showing the widget on the entire site or on specific pages and posts only

**Language Strings** — Customize every label in the panel for English, Arabic, and Hebrew separately

**Advanced** — Override the z-index if needed for theme compatibility

---

## Multilingual Plugin Integration

After your multilingual plugin switches the active language, call:

```js
window.AccessKitWP.refresh();
```

This re-detects the language from `<html lang>` and re-applies any language-dependent features such as the RTL font fallback.

---

## JS API

```js
window.AccessKitWP.open()      // Open the accessibility panel
window.AccessKitWP.close()     // Close the panel
window.AccessKitWP.toggle()    // Toggle open/closed
window.AccessKitWP.reset()     // Reset all features and clear localStorage
window.AccessKitWP.refresh()   // Re-detect language after a language switch
```

---

## Notes on iframes

Features apply to the page DOM only. Same-origin iframes can be supported with additional configuration. Cross-origin iframes (such as embedded donation forms, maps, or video players from external domains) cannot be controlled due to browser security restrictions — this is a platform-level limitation, not a plugin limitation, and is an accepted exception under WCAG guidelines.

---

## License

GPL-2.0-or-later — see [GNU General Public License](https://www.gnu.org/licenses/gpl-2.0.html)
