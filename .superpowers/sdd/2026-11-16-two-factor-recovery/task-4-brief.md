# Task 4 start brief — Super-Admin recovery UI

- Plan: `docs/superpowers/plans/2026-11-16-two-factor-recovery.md`
- Issue: #80, “Neue Features zur Verwaltung von Passwörtern”
- Branch: `feature/issue-80-passwortverwaltung`
- Base commit: `f35e637c` — Task 3 recovery gate
- Scope: expose the recovery action and audit history only to Super-Admins editing another account; collect current password and support reference; show safe audit history; provide a visible success/error/cancel flow.
- Constraints: no password, TOTP secret, recovery code or IP in Inertia history props; preserve the existing Task 2 route contract; review read-only before the stage commit; comment on Issue #80 after commit.
- User-approved behavior: identity verification is performed before recovery; the affected account must reenroll TOTP before using the application.
