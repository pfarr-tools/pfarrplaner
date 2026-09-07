#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
APP_CONFIG="$ROOT/src/config/app.php"
LOGIN_CONTROLLER="$ROOT/src/app/Http/Controllers/Auth/LoginController.php"
INERTIA_MIDDLEWARE="$ROOT/src/app/Http/Middleware/HandleInertiaRequests.php"

grep -q "'demo_mode' =>" "$APP_CONFIG"
grep -q "config('app.demo_mode')" "$LOGIN_CONTROLLER"
grep -q "config('app.demo_mode')" "$INERTIA_MIDDLEWARE"

echo 'Demo mode login tests passed'
