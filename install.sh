#!/usr/bin/env bash
# ============================================================
# pimabox — install.sh
# Quick installer for servers with SSH access.
# Usage: bash install.sh
# ============================================================

set -e

REPO="https://raw.githubusercontent.com/ludescherstudio/pimabox/main"
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo ""
echo "  pimabox — measure more. manage less."
echo "  ──────────────────────────────────────"
echo ""

# ---- Check PHP ----
if ! command -v php &> /dev/null; then
    echo -e "${RED}Error: PHP is not available on this system.${NC}"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo -e "${GREEN}✓${NC} PHP $PHP_VERSION found"

# ---- Target directory ----
echo ""
read -p "Install directory (default: current directory): " INSTALL_DIR
INSTALL_DIR="${INSTALL_DIR:-.}"

if [ ! -d "$INSTALL_DIR" ]; then
    mkdir -p "$INSTALL_DIR"
fi

cd "$INSTALL_DIR"
echo -e "${GREEN}✓${NC} Installing into: $(pwd)"

# ---- Download core files ----
echo ""
echo "Downloading files..."

for FILE in pimabox.php tracker.php config.php; do
    curl -sSL "$REPO/$FILE" -o "$FILE"
    echo -e "${GREEN}✓${NC} $FILE"
done

# ---- Create cache directory ----
mkdir -p cache
curl -sSL "$REPO/cache/.htaccess" -o "cache/.htaccess"
chmod 0750 cache
echo -e "${GREEN}✓${NC} cache/ directory created"

# ---- Handle .htaccess ----
HTACCESS_LINES="RewriteEngine On\nRewriteRule ^pimabox\$   pimabox.php [L]\nRewriteRule ^analytics\$ pimabox.php [L]"

if [ -f ".htaccess" ]; then
    echo ""
    echo -e "${YELLOW}!${NC} .htaccess already exists — adding pimabox rules only"
    if ! grep -q "pimabox.php" .htaccess; then
        echo "" >> .htaccess
        echo "# pimabox" >> .htaccess
        printf "$HTACCESS_LINES\n" >> .htaccess
        echo -e "${GREEN}✓${NC} Rules added to existing .htaccess"
    else
        echo -e "${GREEN}✓${NC} .htaccess already contains pimabox rules — skipped"
    fi
else
    curl -sSL "$REPO/.htaccess" -o ".htaccess"
    echo -e "${GREEN}✓${NC} .htaccess created"
fi

# ---- Handle robots.txt ----
ROBOTS_LINES="Disallow: /pimabox\nDisallow: /analytics\nDisallow: /tracker.php\nDisallow: /cache/"

if [ -f "robots.txt" ]; then
    echo -e "${YELLOW}!${NC} robots.txt already exists — adding pimabox rules only"
    if ! grep -q "tracker.php" robots.txt; then
        echo "" >> robots.txt
        echo "# pimabox" >> robots.txt
        printf "$ROBOTS_LINES\n" >> robots.txt
        echo -e "${GREEN}✓${NC} Rules added to existing robots.txt"
    else
        echo -e "${GREEN}✓${NC} robots.txt already contains pimabox rules — skipped"
    fi
else
    curl -sSL "$REPO/robots.txt" -o "robots.txt"
    echo -e "${GREEN}✓${NC} robots.txt created"
fi

# ---- Configure ----
echo ""
echo "Configuration"
echo "─────────────"
read -p "Dashboard password: " STATS_PASSWORD
read -p "Tracker token (shown in your snippet, treat as public): " TRACKER_TOKEN
read -p "Timezone (default: Europe/Vienna): " TIMEZONE
TIMEZONE="${TIMEZONE:-Europe/Vienna}"

# Write values into config.php
sed -i "s|define('STATS_PASSWORD', 'change-me-please')|define('STATS_PASSWORD', '$STATS_PASSWORD')|" config.php
sed -i "s|define('TRACKER_TOKEN', 'my-secret-word')|define('TRACKER_TOKEN', '$TRACKER_TOKEN')|" config.php
sed -i "s|define('TIMEZONE', 'Europe/Vienna')|define('TIMEZONE', '$TIMEZONE')|" config.php

echo -e "${GREEN}✓${NC} config.php configured"

# ---- Done ----
echo ""
echo "  ──────────────────────────────────────"
echo -e "  ${GREEN}pimabox installed successfully!${NC}"
echo ""
echo "  Next steps:"
echo "  1. Open yourdomain.com/pimabox in your browser"
echo "  2. Add the tracking snippet to your site:"
echo ""
echo "     <script>"
echo "     fetch('/tracker.php?p=' + encodeURIComponent(location.pathname)"
echo "       + '&title=' + encodeURIComponent(document.title)"
echo "       + '&r=' + encodeURIComponent(document.referrer)"
echo "       + '&t=$TRACKER_TOKEN');"
echo "     </script>"
echo ""
echo "  Full documentation: https://github.com/ludescherstudio/pimabox"
echo "  ──────────────────────────────────────"
echo ""
