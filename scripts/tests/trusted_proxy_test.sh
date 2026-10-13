#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
MIDDLEWARE="$ROOT/src/app/Http/Middleware/TrustProxies.php"

grep -Fq "config('octane.trust_proxies'" "$MIDDLEWARE"
if grep -Fq "config('octane.trust.proxies'" "$MIDDLEWARE"; then
    echo 'TrustProxies uses the obsolete Octane config path' >&2
    exit 1
fi

echo 'Trusted proxy configuration test passed'
