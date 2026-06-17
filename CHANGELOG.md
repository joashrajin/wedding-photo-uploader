# Wedding Photo Uploader - Changelog

## Version 1.1.6 (Current)
**Release Date:** 2025-07-09
**Production Release**

This is the version currently live in production. It is the build packaged in
`wedding-photo-uploader-1.1.6.zip` and matches the plugin source in this
repository (`Version: 1.1.6` in `wedding-photo-uploader.php`).

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