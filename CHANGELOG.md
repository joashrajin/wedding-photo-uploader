# Wedding Photo Uploader - Changelog

## Version 1.2.0 (Current)
**Release Date:** 2026-06-17
**Feature: Email Notifications**

Implements the email-notification feature that earlier versions advertised but
never actually sent.

### ✨ New
- **Upload notifications:** when a guest uploads new media, the plugin now emails
  the site admin (the *Notification Email* setting, defaulting to the site admin
  address) so they know there's content awaiting moderation — including a count of
  pending items and a link to the moderation screen.
- Notifications are **debounced** (one email per 15 minutes by default) so a guest
  uploading many files — or abuse of the public endpoint — cannot flood the inbox.
- New filters for customization: `wpu_notification_recipient` (return empty to
  disable), `wpu_notification_throttle`, `wpu_notification_subject`,
  `wpu_notification_message`.

### 📝 Docs
- Corrected the feature wording: the notification goes to the **site admin on new
  upload for review** (the form does not collect guest emails), not "for approved
  content."

---

## Version 1.1.8
**Release Date:** 2026-06-17
**Maintenance & Cleanup Release**

Housekeeping pass after the 1.1.7 security release: removed dead code, fixed a
couple of real rendering/correctness bugs, and improved accessibility. No change
to the normal upload/moderation flow — safe drop-in update.

### 🧹 Cleanup / dead code
- Deleted the unused legacy `includes/frontend-form.php`, the stale
  `assets/js/admin.js` (enqueued on admin but targeting front-end selectors), and
  unused helpers (`WPU_i18n::get_js_translations()`, `wpu_check_heif_support()`).
- Removed the dead query in `render_admin_page()` and two phantom
  `delete_transient()` calls in `uninstall.php`.
- Removed the superseded `src/gallery/` dev scaffold from the working tree.

### 🐛 Fixes
- **Block styling:** fixed a double CSS-unit bug (`padding: 20pxpx`) so the
  uploader block's padding/border-radius render correctly.
- **Dates:** gallery upload dates now use `date_i18n()` (site timezone + locale).
- **Gallery video:** removed `controls="false"` (a boolean attribute that actually
  *enabled* native controls) and a leftover `console.log`.
- **Queries:** dropped an unused `wp_posts` JOIN from the gallery and admin queries.

### ♿ Accessibility
- Upload status region announced to screen readers (`role="status"`, `aria-live`).
- Decorative icons marked `aria-hidden`; attribution/play overlays now reveal on
  keyboard focus and touch devices (not hover-only).
- Added a `<noscript>` notice to the upload form.

### 🧰 Housekeeping
- Replaced the placeholder `Plugin URI` with the GitHub repository URL.

> Not addressed here (tracked for a future release): the "email notifications"
> feature is still advertised but not implemented, and the front-end size limit is
> still hardcoded rather than reading the configured per-type limits.

---

## Version 1.1.7
**Release Date:** 2026-06-17
**Security & WordPress.org Compliance Release**

This release hardens the plugin ahead of its public/source release. It follows a
full pre-publish security audit. No critical/high-severity exploitable issues
were found; the items below address the confirmed medium/low findings and
WordPress.org plugin-directory compliance.

### 🔐 Security Fixes
- **Anonymous upload abuse (DoS):** Added a per-IP rate limit, a per-request
  file-count cap, and per-uploader quota enforcement on the
  `wp_ajax_nopriv_wpu_upload_photos` endpoint. The quota helper
  (`wpu_check_uploader_limit`) was previously dead code and is now enforced.
- **Inert admin limits:** The Settings screen previously wrote to a `wpu_options`
  option that the uploader never read (`wpu_settings`), so configured limits had
  no effect. The Settings page is now reachable (a submenu), operates on
  `wpu_settings`, validates input, and the limits are enforced on upload.
- **DOM-based XSS:** The client-side script now escapes the user-controlled
  `File.name` everywhere it is inserted into the DOM — both the selected-file
  list and the "file too large" error message.

### 🛡️ WordPress.org Compliance
- **Local assets:** Bundled the SimpleLightbox library locally (was loaded from
  `cdnjs.cloudflare.com`), satisfying the "no remote assets" guideline and
  removing a visitor-IP leak / CDN-availability dependency.
- **No server-setting overrides:** Removed the runtime `@ini_set()` overrides of
  PHP upload/memory/time limits.
- **Upload directory hardening:** The generated `.htaccess` now blocks execution
  of script files (not just directory listing), and an `index.php` guard is added.
- **`upload_mimes`:** Now merges with WordPress's existing allowed types instead
  of replacing the entire map site-wide.

### 🧹 Code Quality
- **Consolidated admin action handlers:** Removed the duplicate single-item
  approve/reject/delete handler in `admin-interface.php`. It was dead/shadowed
  code (the `admin_init` handler in `class-wpu-admin.php` runs and redirects
  first); single-item approve/reject and bulk approve/reject/delete are
  unchanged. This removes the divergent-authorization-logic hazard.
- **Removed the dead `auto_approve` flag** from the default settings. It was
  never read; uploads remain moderated (status `pending`) by design.

---

## Version 1.1.6
**Release Date:** 2025-07-09
**Production Release**

This is the version that was live in production prior to 1.1.7. It is the build
packaged in `wedding-photo-uploader-1.1.6.zip` and matched the plugin source at
tag/commit for 1.1.6.

> **Note on history:** The individual point releases between 1.0.8 and 1.1.6
> (1.0.9 through 1.1.5) were not separately documented in this repository, and
> there is no commit history available to reconstruct them. Rather than invent
> change notes, this entry records 1.1.6 as the verified current/shipping
> release. If you recall the specific changes made across the 1.1.x line, add
> them here.

### Feature set at 1.1.6
The shipping plugin provides:
- Photo and video upload with size and type restrictions (incl. HEIF/HEIC)
- Admin moderation interface for both photos and videos
- Gallery display with filtering (photos / videos / both) and sorting
  (date, name, filename)
- Lightbox gallery display for photos
- Real-time upload progress tracking
- Email notifications for approved content
- Mobile-responsive design

---

## Version 1.0.8
**Release Date:** [Current Date]
**Terminology & Bug Fix Release**

### 🎨 User Interface Improvements
- **Completed:** Final terminology consistency - all remaining "photo" references updated to "media"
- **Enhanced:** Upload success messages now display "Successfully uploaded X media file(s)!"
- **Improved:** Validation messages consistently use "file" instead of "photo"
- **Updated:** All button text from "Upload Photos" to "Upload Media"
- **Standardized:** JavaScript translations for consistent terminology

### 🐛 Bug Fixes
- **Fixed:** Admin interface redirect issue - approve/reject buttons now properly redirect back to correct tab
- **Enhanced:** Individual action handling - single-item approve/reject now works correctly
- **Improved:** Tab navigation - both bulk actions and individual actions maintain proper tab state
- **Corrected:** GET/POST parameter handling for individual admin actions

### 🔧 Technical Enhancements
- **Better:** Admin navigation with proper nonce handling
- **Enhanced:** Tab parameter passing for correct redirection
- **Improved:** Individual action link generation with tab context
- **Consistent:** Plugin-wide language standardization complete

---

## Version 1.0.7
**Release Date:** Previous Version
**Security & UX Enhancement Release**

### 🔐 Security Improvements
- **Fixed:** Path traversal vulnerability in file deletion operations
- **Enhanced:** Database query security with improved prepared statement usage
- **Added:** Directory validation to prevent unauthorized file access
- **Improved:** File path construction using WordPress upload directory standards
- **Enhanced:** Realpath validation for all file operations

### 🎨 User Experience Improvements
- **Updated:** All UI text from "photos" to "media" to better reflect video support
- **Improved:** Block descriptions to mention both photos and videos
- **Enhanced:** Admin interface terminology for better clarity
- **Updated:** Menu titles and confirmation messages

### 🛡️ WordPress.org Compliance
- **Achieved:** Full WordPress.org security standards compliance
- **Enhanced:** Input sanitization and output escaping
- **Improved:** CSRF protection and nonce verification
- **Added:** Comprehensive security measures throughout codebase

### 🏗️ Technical Improvements
- **Enhanced:** Error handling and logging
- **Improved:** Code documentation and comments
- **Added:** Upgrade path from v1.0.6 with security enhancements
- **Optimized:** File upload security validation

### 📋 Security Audit Results
- **Security Rating:** 9.5/10 (Excellent)
- **Status:** Production Ready
- **Compliance:** WordPress.org Approved

---

## Version 1.0.6
**Release Date:** Previous Version
**Video Support Release**

### ✨ New Features
- **Added:** Video upload support (MP4, MOV, AVI, MKV, WebM)
- **Added:** Gallery filtering (Photos/Videos/Both)
- **Added:** Gallery sorting options (Date, Name, Filename)
- **Added:** Video preview in admin interface
- **Added:** Different size limits for photos vs videos

### 🔧 Technical Changes
- **Added:** `file_type` column to database
- **Enhanced:** MIME type validation for videos
- **Improved:** Upload progress tracking
- **Added:** Video-specific settings

### 🎨 UI/UX Improvements
- **Added:** Filter buttons in gallery
- **Enhanced:** Responsive design for mixed media
- **Improved:** Admin interface with type indicators
- **Added:** Video playback controls in gallery

---

## Previous Versions (1.0.0 - 1.0.5)

### Version 1.0.5
- Fixed JSON parse errors and improved error handling
- Enhanced upload progress tracking

### Version 1.0.4
- Improved upload progress tracking with individual file uploads
- Better error handling

### Version 1.0.3
- Fixed admin page conflicts
- Added proper script/style restrictions

### Version 1.0.2
- Added upload progress tracking feature
- Performance improvements

### Version 1.0.1
- Data preservation improvements
- Bug fixes

### Version 1.0.0
- Initial release
- Photo upload functionality
- Admin moderation interface
- Basic gallery display
- Email notifications

---

## Upgrade Path

### From v1.0.6 to v1.0.7
- **Automatic:** No database changes required
- **Enhanced:** Security improvements applied automatically
- **Updated:** UI text changes take effect immediately
- **Maintained:** All existing data and settings preserved

### From v1.0.0-1.0.5 to v1.0.7
- **Database:** Automatic migration with `file_type` column addition
- **Settings:** Video support settings added automatically
- **Data:** All existing photos preserved and marked as 'photo' type
- **Features:** Full video support enabled

## Security Notes

**v1.0.7** represents a significant security enhancement release. All users are strongly encouraged to upgrade for:
- Enhanced security posture
- WordPress.org compliance
- Improved user experience
- Better terminology and clarity

---

*For technical support or security reports, please contact through the WordPress.org plugin directory.* 