#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMMAND="$ROOT/src/app/Console/Commands/InstallInitialAdmin.php"

test -f "$COMMAND"
grep -q "protected \$signature = 'pfarrplaner:initial-admin" "$COMMAND"
grep -q '^namespace App\\Console\\Commands;' "$COMMAND"
grep -q "createOrUpdateAdminUser" "$COMMAND"
grep -q "RoleService::ROLE_SUPER_ADMIN" "$COMMAND"
grep -q "Role::firstOrCreate" "$COMMAND"
grep -q "must_change_password" "$COMMAND"
grep -q "password-stdin" "$COMMAND"
! grep -q "echo.*password" "$COMMAND"

echo 'initial admin strategy tests passed'
