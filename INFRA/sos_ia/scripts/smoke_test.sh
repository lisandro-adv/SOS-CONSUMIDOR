#!/usr/bin/env bash
set -euo pipefail

api_url="${1:-http://127.0.0.1:8000}"

curl --fail --silent --show-error "${api_url}/health"
printf '\nhealth: ok\n'

if [[ -n "${SOS_IA_TEST_TOKEN:-}" ]]; then
  curl --fail --silent --show-error \
    -H "Authorization: Bearer ${SOS_IA_TEST_TOKEN}" \
    "${api_url}/v1/me"
  printf '\nsessão: ok\n'
else
  printf 'sessão: não testada; defina SOS_IA_TEST_TOKEN somente no shell da VPS\n'
fi
