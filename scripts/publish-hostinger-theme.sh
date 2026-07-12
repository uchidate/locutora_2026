#!/usr/bin/env bash

set -euo pipefail

repo_root="$(git rev-parse --show-toplevel)"
cd "$repo_root"

if [[ "$(git branch --show-current)" != "main" ]]; then
  echo "Erro: a publicação deve ser executada a partir da branch main." >&2
  exit 1
fi

if [[ -n "$(git status --porcelain)" ]]; then
  echo "Erro: existem alterações locais ainda não commitadas." >&2
  exit 1
fi

git fetch --quiet origin main

if ! git merge-base --is-ancestor origin/main HEAD; then
  echo "Erro: main divergiu de origin/main; atualize o histórico antes de publicar." >&2
  exit 1
fi

test -f wp-theme-locutora/style.css
test -f wp-theme-locutora/functions.php
grep -q "Theme Name:" wp-theme-locutora/style.css

while IFS= read -r -d '' php_file; do
  php -l "$php_file" >/dev/null
done < <(find wp-theme-locutora -type f -name '*.php' -print0)

git push origin main

deploy_branch="hostinger-theme"
temporary_branch="hostinger-theme-local-build"

git branch -D "$temporary_branch" >/dev/null 2>&1 || true
git subtree split \
  --prefix=wp-theme-locutora \
  --branch="$temporary_branch" >/dev/null

deploy_commit="$(git rev-parse "$temporary_branch")"
git push --force origin "$temporary_branch:$deploy_branch"
git branch -D "$temporary_branch" >/dev/null

echo "Tema publicado na branch $deploy_branch ($deploy_commit)."
