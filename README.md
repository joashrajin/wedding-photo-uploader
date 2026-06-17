# Wedding Photo Uploader

A WordPress plugin that lets wedding guests upload their own photos and videos,
which the host moderates before they appear in a filterable, responsive gallery.

- **Version:** 1.2.0
- **Requires WordPress:** 5.8 or higher
- **Requires PHP:** 7.4 or higher
- **License:** [GPL-2.0-or-later](LICENSE)
- **Author:** Joash Rajin

## Features

- Photo **and** video uploads (with separate size limits per type)
- HEIF/HEIC support
- Real-time upload progress tracking
- Admin moderation queue for both photos and videos (approve / reject)
- Gallery display with filtering (photos / videos / both) and sorting (date / name / filename)
- Lightbox display for photos
- Email notification to the site admin when new media is uploaded for review
- Mobile-responsive design

## Blocks

The plugin registers two Gutenberg blocks (no shortcodes):

| Block | Name | Purpose |
|-------|------|---------|
| Uploader | `wedding-photo-uploader/photo-form` | The guest-facing upload form |
| Gallery  | `wedding-photo-uploader/gallery`    | Displays approved photos/videos with filtering & sorting |

## Installation

### From a release zip
1. Build or download `wedding-photo-uploader-<version>.zip`.
2. In WordPress: **Plugins → Add New → Upload Plugin**, choose the zip, and **Activate**.
3. Add the **Uploader** block to a page for guests, and the **Gallery** block where approved media should appear.

### From source
```bash
npm install      # install build dependencies
npm run build    # build the block bundles into blocks/*/build
./create-zip.sh  # produce wedding-photo-uploader-<version>.zip
```
`create-zip.sh` reads the version straight from the plugin header, so it always
matches `wedding-photo-uploader.php`.

## Project layout

```
wedding-photo-uploader.php   Plugin bootstrap (header, constants, hooks)
uninstall.php                Cleanup on uninstall
includes/                    PHP classes (uploader, gallery, admin, activator, …)
assets/                      Front-end & admin CSS/JS
blocks/                      Gutenberg blocks (uploader + gallery): src/ and built build/
create-zip.sh                Builds the distributable plugin zip
CHANGELOG.md                 Release history
```

## License

This plugin is free software, released under the **GNU General Public License
v2.0 or later**. See the [LICENSE](LICENSE) file for the full text.
