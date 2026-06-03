#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

tmp_stash_dir="$(mktemp -d)"
tmp_stash_manifest="$tmp_stash_dir/.manifest"
: > "$tmp_stash_manifest"

stash_path() {
    local path="$1"

    if [[ -e "$path" || -L "$path" ]]; then
        mkdir -p "$tmp_stash_dir/$(dirname "$path")"
        mv -f "$path" "$tmp_stash_dir/$path"
        printf '%s\n' "$path" >> "$tmp_stash_manifest"
    fi
}

restore_stashed_paths() {
    if [[ -f "$tmp_stash_manifest" ]]; then
        while IFS= read -r path; do
            if [[ -e "$tmp_stash_dir/$path" || -L "$tmp_stash_dir/$path" ]]; then
                mkdir -p "$(dirname "$path")"
                mv -f "$tmp_stash_dir/$path" "$path"
            fi
        done < "$tmp_stash_manifest"
    fi

    rm -rf "$tmp_stash_dir"
}

trap restore_stashed_paths EXIT
trap 'trap - EXIT; restore_stashed_paths; exit 130' INT
trap 'trap - EXIT; restore_stashed_paths; exit 143' TERM

while IFS= read -r -d '' env_file; do
    stash_path "${env_file#./}"
done < <(find . -maxdepth 1 -type f -name '.env*' -print0)

for path in \
    .phpdoc \
    .phpunit.cache \
    .phpunit.result.cache \
    public/storage; do
    stash_path "$path"
done

while IFS= read -r -d '' generated_file; do
    stash_path "${generated_file#./}"
done < <(find bootstrap/cache storage/framework/views storage/logs -maxdepth 1 -type f ! -name '.gitignore' -print0 2>/dev/null || true)

rm -rf .vercel/output public/build
npx --yes vercel build --prod --yes

matches="$(grep -RIlE '\"user/\.env[^"]*\":|mongodb(\+srv)?://|user/\.phpdoc/|user/\.phpunit|user/public/storage|user/storage/framework/views/[^"]+\.php|user/storage/logs/[^"]+\.log' .vercel/output/functions/api/index.func/.vc-config.json .vercel/output 2>/dev/null || true)"
if [[ -n "$matches" ]]; then
    printf '%s\n' "Forbidden deployment content detected in:" >&2
    printf '%s\n' "$matches" >&2
    printf '%s\n' "Refusing to deploy. Check the Vercel output before retrying." >&2
    exit 1
fi

npx --yes vercel deploy --prebuilt --prod "$@"
