#!/usr/bin/env bash
# Runs the regression suite against the given maatify/logger releases in a
# throwaway copy, so the working tree and composer.json stay untouched.
# Usage: scripts/verify-logger-matrix.sh [logger-version ...]  (default: 5.0.1 5.1.0 5.1.1 5.1.2 7.0.0)
#
# With the default list, a release whose PHP requirement the running PHP does not satisfy
# (for example logger 7.0.0 on PHP 8.2) is reported as SKIPPED instead of failing the run; at least
# one release must still run. Releases named on the command line are never skipped.
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
versions=("$@")
explicit=1
if [ "${#versions[@]}" -eq 0 ]; then
    versions=(5.0.1 5.1.0 5.1.1 5.1.2 7.0.0)
    explicit=0
fi

ran=0
for version in "${versions[@]}"; do
    work="$(mktemp -d)"
    cp -R "$root/composer.json" "$root/phpunit.xml" "$root/realm" "$root/tests" "$work/"
    if ! install_log="$(cd "$work" && composer require --no-interaction --no-progress "maatify/logger:=${version}" --with-all-dependencies 2>&1)"; then
        if [ "${explicit}" -eq 0 ] && grep -q "requires php" <<<"${install_log}"; then
            echo "== maatify/logger ${version}: SKIPPED (its PHP requirement is not satisfied by PHP $(php -r 'echo PHP_VERSION;'))"
            rm -rf "$work"
            continue
        fi
        echo "${install_log}" >&2
        rm -rf "$work"
        exit 1
    fi
    (
        cd "$work"
        echo "== maatify/logger ${version} (PHP $(php -r 'echo PHP_VERSION;'))"
        vendor/bin/phpunit --testsuite regression
    )
    ran=$((ran + 1))
    rm -rf "$work"
done

if [ "${ran}" -eq 0 ]; then
    echo "No logger release could run on this PHP version." >&2
    exit 1
fi
