#!/usr/bin/env bash

set -euo pipefail

repo_root="$(git rev-parse --show-toplevel)"
cd "$repo_root"

site_url="${HOSTINGER_SITE_URL:-https://plum-walrus-211817.hostingersite.com}"
site_url="${site_url%/}"
expected_version="$(awk -F ': *' '/^Version:/{print $2; exit}' wp-theme-locutora/style.css)"
cache_buster="$(date +%s)"
temporary_dir="$(mktemp -d)"
trap 'rm -rf "$temporary_dir"' EXIT

theme_css_url="$site_url/wp-content/themes/wp-theme-locutora/style.css?deploy_check=$cache_buster"
curl -fsSL --max-time 45 "$theme_css_url" -o "$temporary_dir/style.css"

deployed_version="$(awk -F ': *' '/^Version:/{print $2; exit}' "$temporary_dir/style.css")"
if [[ "$deployed_version" != "$expected_version" ]]; then
  echo "Erro: versão publicada $deployed_version; esperada $expected_version." >&2
  exit 1
fi

routes=(
  "/"
  "/sobre-nos/"
  "/servicos/"
  "/contato/"
  "/orcamento/"
  "/politica-de-privacidade/"
)

for route in "${routes[@]}"; do
  output_file="$temporary_dir/$(printf '%s' "$route" | tr '/' '_' | sed 's/^_*$/home/').html"
  status="$(curl -sSL --max-time 45 -A 'Mozilla/5.0' -o "$output_file" -w '%{http_code}' "$site_url$route?deploy_check=$cache_buster")"

  if [[ "$status" != "200" ]]; then
    echo "Erro: $route retornou HTTP $status." >&2
    exit 1
  fi

  if ! grep -Fq '/wp-content/themes/wp-theme-locutora/' "$output_file"; then
    echo "Erro: $route não está usando o tema wp-theme-locutora." >&2
    exit 1
  fi

  echo "OK $status $route"
done

echo "Deploy Hostinger validado na versão $deployed_version."
