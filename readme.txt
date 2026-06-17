=== Wedding Photo Uploader ===
Contributors: joashrajin
Tags: wedding, photo, upload, gallery, video
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let wedding guests upload photos and videos, moderate them, and display approved media in a filterable, responsive gallery.

== Description ==

Wedding Photo Uploader lets your guests contribute their own photos and videos
from your special day. Uploads go into a moderation queue so you approve content
before it appears, then approved media is shown in a responsive gallery that can
be filtered and sorted.

Features:

* Photo **and** video uploads, with separate size limits per type
* HEIF/HEIC support
* Real-time upload progress tracking
* Admin moderation queue for photos and videos (approve / reject)
* Gallery with filtering (photos / videos / both) and sorting (date / name / filename)
* Lightbox display for photos
* Email notification to the site admin when new media is uploaded for review
* Mobile-responsive design

The plugin provides two blocks (no shortcodes are required):

* **Uploader** (`wedding-photo-uploader/photo-form`) — the guest-facing upload form
* **Gallery** (`wedding-photo-uploader/gallery`) — displays approved media with filtering and sorting

== Installation ==

1. Upload the plugin zip via **Plugins → Add New → Upload Plugin**, or copy the
   `wedding-photo-uploader` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Add the **Uploader** block to a page where guests will upload.
4. Add the **Gallery** block to the page where approved media should appear.
5. Review and approve uploads under the plugin's admin moderation screen.

== Frequently Asked Questions ==

= What file types can guests upload? =

Photos (including HEIF/HEIC) and videos. Allowed types and per-type size limits
are configurable in the plugin settings.

= Do uploads appear immediately? =

No. Uploads enter a moderation queue and only appear in the gallery after you
approve them.

= Does it use shortcodes? =

No — it provides two editor blocks (Uploader and Gallery).

= Will I be notified when guests upload? =

Yes. The site admin (the *Notification Email* address in the plugin settings)
receives an email when new media is uploaded for review. Notifications are batched
so a guest uploading many files only triggers one email.

== Changelog ==

= 1.2.0 =
* New: email notification to the site admin when guests upload new media for
  review (uses the Notification Email setting), debounced to avoid inbox flooding,
  with filters to customize or disable it.
* Docs: corrected the email-notification description.

= 1.1.8 =
* Maintenance & housekeeping release.
* Removed dead/unused code (legacy upload form, stale admin script, unused helpers).
* Fixed block padding/border-radius rendering (a double CSS-unit bug).
* Dropped an unused database JOIN from the gallery and admin queries.
* Upload dates now respect the site's timezone and locale.
* Accessibility: upload status is announced to screen readers, gallery video
  controls/play cue behave correctly, attribution shows on touch devices, and a
  no-JavaScript notice was added to the upload form.

= 1.1.7 =
* Security & WordPress.org compliance release.
* Added per-IP rate limiting, a per-request file cap, and per-uploader quota
  enforcement on the anonymous upload endpoint to prevent resource-exhaustion abuse.
* Fixed the admin upload-limit settings, which previously had no effect; the
  Settings screen now controls the limits the uploader actually enforces.
* Fixed a DOM-based XSS in the uploader's client-side file list (filenames are
  now escaped).
* Bundled the SimpleLightbox library locally instead of loading it from a CDN.
* Removed runtime ini_set() overrides of server PHP limits.
* Hardened the upload directory (.htaccess now blocks script execution; added index.php).
* upload_mimes now merges with, rather than replaces, WordPress's allowed types.

= 1.1.6 =
* Previous production release. Matches the live, shipping build of the plugin.
* Note: the individual point releases between 1.0.8 and 1.1.6 (1.0.9–1.1.5) were
  not separately documented; see CHANGELOG.md for the full history and details.

= 1.0.8 =
* Terminology consistency ("photo" → "media") and admin moderation bug fixes
  (approve/reject redirects, individual action handling).

= 1.0.7 =
* Security hardening: path-traversal fix in file deletion, improved prepared
  statements, enhanced input sanitization and output escaping.

= 1.0.6 =
* Added video upload support (MP4, MOV, AVI, MKV, WebM), gallery filtering and
  sorting, video previews in admin, and a `file_type` database column.

= 1.0.0 – 1.0.5 =
* Initial release and incremental improvements: photo uploads, admin moderation,
  gallery display, email notifications, and upload progress tracking.

== Upgrade Notice ==

= 1.2.0 =
Adds email notifications to the site admin when new media is uploaded. Safe drop-in update.

= 1.1.8 =
Maintenance release: cleanup, a rendering fix, and accessibility improvements. Safe drop-in update.

= 1.1.7 =
Security and compliance update. Recommended for all users. After updating,
deactivate and reactivate the plugin once so the hardened upload-directory
protection is applied.

= 1.1.6 =
Previous stable release. See CHANGELOG.md for full details.
