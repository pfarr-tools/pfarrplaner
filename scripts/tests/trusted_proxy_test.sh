#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
MIDDLEWARE="$ROOT/src/app/Http/Middleware/TrustProxies.php"
OCTANE_CONFIG="$ROOT/src/config/octane.php"

grep -Fq "config('octane.trust_proxies'" "$MIDDLEWARE"
if grep -Fq "config('octane.trust.proxies'" "$MIDDLEWARE"; then
    echo 'TrustProxies uses the obsolete Octane config path' >&2
    exit 1
fi
grep -Fq "'trust_proxies' => env('TRUST_PROXY', '127.0.0.1')" "$OCTANE_CONFIG"
if grep -Fq "'trust_proxies' => [env(" "$OCTANE_CONFIG"; then
    echo 'Octane trusted proxies must remain a string for wildcard support' >&2
    exit 1
fi

echo 'Trusted proxy configuration test passed'
