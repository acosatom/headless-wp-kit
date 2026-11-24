#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

rm -rf dist
mkdir -p dist/headless-wp-kit

cp -R headless-wp-kit.php includes profiles readme.txt uninstall.php LICENSE dist/headless-wp-kit/

(
  cd dist
  zip -qr headless-wp-kit.zip headless-wp-kit
)

echo "Built dist/headless-wp-kit.zip"
