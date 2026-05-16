#!/usr/bin/env bash
set -euo pipefail

package="${1:-AJAXaf.ocmod.zip}"
root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cd "$root"
rm -f "$package"
zip -qr "$package" install.json README.md admin catalog ocmod
printf 'Created %s\n' "$package"
