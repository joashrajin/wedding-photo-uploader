#!/bin/bash
#
# Builds the distributable plugin zip (the file you upload to WordPress).
#
# The version is read directly from the plugin header in
# wedding-photo-uploader.php so it can never drift out of sync with the code.
# Bump "Version:" there (and in package.json) and this script follows.

set -euo pipefail

PLUGIN_SLUG="wedding-photo-uploader"
PLUGIN_DIR="${PLUGIN_SLUG}"          # temporary staging dir (WP expects this layout)
MAIN_FILE="wedding-photo-uploader.php"

# Derive the version from the plugin header: " * Version: 1.1.6"
VERSION=$(grep -iE "^[[:space:]]*\*[[:space:]]*Version:" "${MAIN_FILE}" \
    | head -1 | sed -E 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')

if [ -z "${VERSION}" ]; then
    echo "ERROR: could not read Version from ${MAIN_FILE}" >&2
    exit 1
fi

ZIP_FILE="${PLUGIN_SLUG}-${VERSION}.zip"

echo "Building ${ZIP_FILE} ..."

# Remove only the zip we're about to (re)build — keeps other release zips intact.
rm -f "${ZIP_FILE}"
rm -rf "${PLUGIN_DIR}"

# Build the block bundles (blocks/*/build).
npm run build

# Stage the plugin in the folder layout WordPress expects.
# Only ship runtime files — dev tooling, sources outside blocks/, and
# everything we keep in archive/ are simply never copied in.
mkdir -p "${PLUGIN_DIR}"
cp -r assets blocks includes package.json uninstall.php "${MAIN_FILE}" "${PLUGIN_DIR}/"

# Zip the staged plugin. The cp list above is an allowlist, so the only thing
# left to strip is macOS junk that can appear inside the copied folders.
zip -r "${ZIP_FILE}" "${PLUGIN_DIR}" -x "*.DS_Store"

# Remove the staging dir.
rm -rf "${PLUGIN_DIR}"

echo "Created ${ZIP_FILE}"
