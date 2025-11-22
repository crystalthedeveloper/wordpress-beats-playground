# WordPress Beats Playground

A fully interactive, browser-based WordPress demo environment for the **Beats Upload Player** and **Beats Visualizer** plugins.
This repository contains the official **WordPress Playground configuration** that:

- Installs both plugins automatically from GitHub  
- Activates them inside a sandboxed WordPress instance  
- Creates a “Beats Playground” demo page  
- Loads the live shortcodes for hands-on testing  
- Requires **no installation, no hosting, and no setup**

This allows users to **try both plugins instantly**—just like a Webflow Marketplace preview.

---

## 🚀 Try the Live Demo (One Click)

Click below to launch a fresh WordPress environment with both plugins pre-installed:

👉 **https://playground.wordpress.net/?config=RAW_JSON_URL_HERE**

*(Replace with your real raw JSON link)*

The demo loads in seconds and includes:

- Beats Upload Player  
- Beats Visualizer  
- A combined live preview page  
- All core features enabled  

---

## 📦 Included in This Repo

### `playground-beats.json`  
The main configuration file that:

- Downloads both plugins from GitHub  
- Activates them  
- Creates the “Beats Playground” page  
- Inserts the shortcodes:  
  - `[beats_player_demo]`  
  - `[beats_visualizer_demo]`  
- Sets the page as the homepage  

This ensures a complete hands-on experience in Playground.

---

## 🛠 Requirements for the Plugins

To ensure the demo works correctly, each plugin includes a dedicated demo shortcode:

### Beats Upload Player

```php
add_shortcode('beats_player_demo', function () {
    return '<h3>Beats Upload Player Demo</h3>' . do_shortcode('[beats_upload_player]');
});
```

### Beats Visualizer

```php
add_shortcode('beats_visualizer_demo', function () {
    return '<h3>Beats Visualizer Demo</h3>' . do_shortcode('[beats_visualizer]');
});
```

These shortcodes are auto-inserted into the generated demo page.

---

## 🤝 About This Playground

This demo environment is ideal for:

- Users evaluating the plugins  
- Developers testing compatibility  
- Marketplace-style previews  
- Documentation examples  
- Plugin marketing and onboarding  

Everything runs inside the browser using **WebAssembly-powered WordPress** (no server needed).

---

## 📬 Questions or Feedback?

Feel free to open an issue or reach out at:  
https://www.crystalthedeveloper.ca
