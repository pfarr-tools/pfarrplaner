#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SCRIPT="$ROOT/scripts/legacy-migrate.sh"

grep -q -- '\[--dev|--prod|--demo\]' "$SCRIPT"
grep -q -- '--demo' "$SCRIPT"
grep -q 'target="\${target:-dev}"' "$SCRIPT"
grep -q -- '--dev)' "$SCRIPT"
grep -q -- '--prod)' "$SCRIPT"
grep -q -- '--demo)' "$SCRIPT"
grep -q 'compose.production.yaml' "$SCRIPT"
grep -q 'APP_ENV=production' "$SCRIPT"
grep -q 'pfarrplaner-app:commit-' "$SCRIPT"
grep -q 'docker build --pull --target production' "$SCRIPT"
grep -q 'legacy_path" == /\*' "$SCRIPT"
grep -q 'legacy_local' "$SCRIPT"
grep -q 'bash -c' "$SCRIPT"

output="$(bash "$SCRIPT" example:relative --dry-run 2>&1 || true)"
grep -q 'Zielumgebung: dev' <<<"$output"

output="$(bash "$SCRIPT" example:/srv/legacy --prod --dry-run 2>&1 || true)"
grep -q 'Für --prod und --demo muss APP_ENV=production gesetzt sein' <<<"$output"

output="$(bash "$SCRIPT" example:/srv/legacy --dry-run --dev --prod 2>&1 || true)"
grep -q 'nicht gleichzeitig' <<<"$output"

TEST_ROOT="$(mktemp -d)"
trap 'rm -rf "$TEST_ROOT"' EXIT
mkdir -p "$TEST_ROOT/bin" "$TEST_ROOT/legacy" "$TEST_ROOT/scripts"
cp "$ROOT/scripts/legacy-env.sh" "$TEST_ROOT/scripts/legacy-env.sh"
cat > "$TEST_ROOT/.env" <<'EOF'
APP_ENV=production
EOF
cat > "$TEST_ROOT/legacy/.env" <<'EOF'
DB_CONNECTION=mysql
DB_DATABASE=legacy
DB_USERNAME=legacy
DB_PASSWORD=secret
APP_KEY=legacy-app-key
DATABASE_KEY=legacy-database-key
EOF
cat > "$TEST_ROOT/bin/mariadb-dump" <<'EOF'
#!/usr/bin/env bash
exit 0
EOF
chmod +x "$TEST_ROOT/bin/mariadb-dump"
git -C "$TEST_ROOT" init -q
git -C "$TEST_ROOT" -c user.name=Test -c user.email=test@example.test add .
git -C "$TEST_ROOT" -c user.name=Test -c user.email=test@example.test commit -qm initial
output="$(PLANER_MIGRATE_ROOT="$TEST_ROOT" PATH="$TEST_ROOT/bin:$PATH" bash "$SCRIPT" "$TEST_ROOT/legacy" --prod --dry-run 2>&1)"
grep -q 'Produktionsbild: pfarrplaner-app:commit-' <<<"$output"

echo 'legacy migration target tests passed'
