#!/usr/bin/env bash
#
# T-2.2 — Hardcoded Infrastructure Driver Gate (D-037 guarantee #1)
#
# Fails the build if an environment-varying infrastructure driver is hardcoded in
# application code. Queue/cache/session/filesystem connections MUST be resolved
# from config()/.env so that shared→VPS migration is configuration-only.
#
# Heuristic guardrail — reviewer judgement still applies. Tune ALLOWLIST/PATTERN
# as the codebase grows. Named config keys like 'public'/'local' (filesystem disks)
# are intentionally NOT flagged; only the shared-vs-VPS drivers are.
#
set -euo pipefail

SEARCH_PATHS=("app" "routes")
# Drivers that differ between shared hosting and VPS (must come from .env):
PATTERN="(Cache::store|Queue::connection|Session::driver|->connection|->store)\(\s*['\"](redis|database|sync|memcached|s3)['\"]"

FOUND=0
for path in "${SEARCH_PATHS[@]}"; do
  [ -d "$path" ] || continue
  if grep -rEn --include='*.php' "$PATTERN" "$path"; then
    FOUND=1
  fi
done

if [ "$FOUND" -ne 0 ]; then
  echo ""
  echo "✗ FAIL: hardcoded infrastructure driver detected (D-037 violation)."
  echo "  Resolve the driver from config()/.env instead of hardcoding it."
  echo "  See IMPLEMENTATION_GOVERNANCE §7 and DECISION_LOG D-037."
  exit 1
fi

echo "✓ PASS: no hardcoded infrastructure drivers (D-037)."
exit 0
