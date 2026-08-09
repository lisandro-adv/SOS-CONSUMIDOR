#!/usr/bin/env bash
set -Eeuo pipefail

readonly REMOTE_HOST="root@187.77.48.16"
readonly SSH_KEY="/Users/lisandromoraes/.ssh/id_ed25519"
readonly REMOTE_DIR="/backup/sos-backup-retention/daily"
readonly DEST_DIR="/Users/lisandromoraes/Dropbox/_ESCRITORIO/PROJETOS/SOSCONSUMIDOR.COM.BR/BACKUPS/VPS_AUTOMATICOS/SITE"
readonly LOG_DIR="${DEST_DIR}/logs"
readonly LOG_FILE="${LOG_DIR}/pull.log"
readonly RETAIN_DAILY=7
readonly RETAIN_WEEKLY=4

mkdir -p "${DEST_DIR}" "${LOG_DIR}"
chmod 700 "${DEST_DIR}" "${LOG_DIR}"
touch "${LOG_FILE}"
chmod 600 "${LOG_FILE}"

lock_dir="${DEST_DIR}/.pull.lock"
if ! mkdir "${lock_dir}" 2>/dev/null; then
  printf '%s sincronizacao ignorada: outra copia esta em andamento\n' "$(date '+%Y-%m-%dT%H:%M:%S%z')" >>"${LOG_FILE}"
  exit 0
fi
trap 'rmdir "${lock_dir}" 2>/dev/null || true' EXIT

log() {
  printf '%s %s\n' "$(date '+%Y-%m-%dT%H:%M:%S%z')" "$*" | tee -a "${LOG_FILE}"
}

ssh_options=(-i "${SSH_KEY}" -o BatchMode=yes -o ConnectTimeout=15)
latest_name="$(ssh "${ssh_options[@]}" "${REMOTE_HOST}" \
  "find '${REMOTE_DIR}' -maxdepth 1 -type f -name 'user.????-??-??_??-??-??.tar' -printf '%f\\n' | sort -r | head -n 1")"

if [[ -z "${latest_name}" ]]; then
  log "ERRO nenhuma copia validada encontrada na VPS do site"
  exit 1
fi

if [[ ! -f "${DEST_DIR}/${latest_name}" ]]; then
  log "baixando ${latest_name}"
  rsync -a --partial -e "ssh -i ${SSH_KEY} -o BatchMode=yes -o ConnectTimeout=15" \
    "${REMOTE_HOST}:${REMOTE_DIR}/${latest_name}" \
    "${REMOTE_HOST}:${REMOTE_DIR}/${latest_name}.sha256" \
    "${DEST_DIR}/"
fi

(
  cd "${DEST_DIR}"
  shasum -a 256 -c "${latest_name}.sha256"
)
log "OK copia externa validada: ${latest_name}"

keep_file="$(mktemp -t sos-backup-keep.XXXXXX)"
weekly_file="$(mktemp -t sos-backup-weekly.XXXXXX)"
trap 'rm -f "${keep_file}" "${weekly_file}"; rmdir "${lock_dir}" 2>/dev/null || true' EXIT

find "${DEST_DIR}" -maxdepth 1 -type f -name 'user.????-??-??_??-??-??.tar' -exec basename {} \; \
  | sort -r | head -n "${RETAIN_DAILY}" >>"${keep_file}"

while IFS= read -r archive_name; do
  archive_date="${archive_name#user.}"
  archive_date="${archive_date%%_*}"
  if [[ "$(date -j -f '%Y-%m-%d' "${archive_date}" '+%u' 2>/dev/null || true)" == "7" ]]; then
    printf '%s\n' "${archive_name}" >>"${weekly_file}"
  fi
done < <(find "${DEST_DIR}" -maxdepth 1 -type f -name 'user.????-??-??_??-??-??.tar' -exec basename {} \; | sort -r)

head -n "${RETAIN_WEEKLY}" "${weekly_file}" >>"${keep_file}"
sort -u -o "${keep_file}" "${keep_file}"

while IFS= read -r archive_path; do
  archive_name="$(basename "${archive_path}")"
  if ! grep -Fqx "${archive_name}" "${keep_file}"; then
    rm -f -- "${archive_path}" "${archive_path}.sha256"
    log "retencao local removeu: ${archive_name}"
  fi
done < <(find "${DEST_DIR}" -maxdepth 1 -type f -name 'user.????-??-??_??-??-??.tar' | sort)

log "OK sincronizacao e retencao concluidas"
