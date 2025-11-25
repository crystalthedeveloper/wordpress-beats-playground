# WordPress Beats Playground

This repository houses a **WordPress Playground configuration** that spins up a disposable site focused on the **Beats Upload Player** plugin. The Playground session installs the plugin from GitHub, activates it, and creates a “Beats Playground” page that renders the `[beats_player_demo]` shortcode so anyone can try the uploader in seconds—no hosting, no local setup.

---

## 🚀 Quick Start

1. Open the link below in any modern browser.  
2. Playground provisions a brand-new WordPress instance (powered by WebAssembly).  
3. The Beats Upload Player plugin is already active, and the homepage shows the demo shortcode.

👉 **https://playground.wordpress.net/?config=https://raw.githubusercontent.com/crystalthedeveloper/wordpress-beats-playground/main/playground-beats.json**

> Forking the repo? Replace the `raw.githubusercontent.com` URL above with your fork’s raw `playground-beats.json`.

---

## 📁 Repository Contents

- `README.md` — Documentation for launching and customizing the Playground experience.  
- `playground-beats.json` — The configuration consumed by WordPress Playground.

---

## 🔧 How the Playground Is Provisioned

The `playground-beats.json` file defines every automated step:

```json
{
  "plugins": [
    {
      "type": "github",
      "repo": "crystalthedeveloper/wordpress-plugin-beats-upload-player",
      "branch": "main",
      "path": "/"
    }
  ],
  "steps": [
    { "step": "activatePlugin", "plugin": "wordpress-plugin-beats-upload-player/beats-upload-player.php" },
    {
      "step": "createPost",
      "title": "Beats Playground",
      "slug": "beats-playground",
      "content": "<!-- wp:shortcode -->[beats_player_demo]<!-- /wp:shortcode -->"
    },
    { "step": "setHomepage", "pageId": "{{posts.beats-playground}}" }
  ]
}
```

- **Plugin install:** pulls the Beats Upload Player directly from GitHub.  
- **Activation:** ensures the plugin is ready the moment Playground boots.  
- **Demo page:** renders the `[beats_player_demo]` shortcode inside a Block Editor shortcode block.  
- **Homepage:** the newly created “Beats Playground” page becomes the front page so the demo loads immediately.

---

## ✏️ Customize the Demo

- **Modify the page content:** edit the `content` field to include copy, headings, or additional shortcodes.  
- **Add more plugins:** append entries to the `plugins` array (GitHub, ZIP, or WordPress.org sources are supported).  
- **Chain steps:** Playground also accepts steps such as `importFile`, `setOption`, or running `wp-cli` commands. See the [official docs](https://wordpress.github.io/wordpress-playground/) for the full schema.

### Adding the Beats Visualizer (optional)

If you want the page to showcase the Beats Visualizer too, add another plugin entry and update the content:

```json
{
  "type": "github",
  "repo": "crystalthedeveloper/wordpress-plugin-beats-visualizer",
  "branch": "main",
  "path": "/"
}
```

Then reference its shortcode, e.g.:

```html
<!-- wp:heading --><h2>Visualizer Preview</h2><!-- /wp:heading -->
<!-- wp:shortcode -->[beats_visualizer_demo]<!-- /wp:shortcode -->
```

---

## 🧩 Shortcode Helpers

The demo expects each plugin to register its own “demo wrapper” shortcode so the preview has context. Example:

```php
add_shortcode( 'beats_player_demo', function () {
    return '<h3>Beats Upload Player Demo</h3>' . do_shortcode( '[beats_upload_player]' );
} );
```

Ship an equivalent snippet in each plugin to keep the Playground content clean.

---

## 🤝 Feedback

Questions, bugs, or feature ideas? Open an issue or reach out via https://www.crystalthedeveloper.ca. Thanks for testing Beats in Playground!
