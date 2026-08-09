#!/usr/bin/env bash
set -Eeuo pipefail

readonly HESTIA_BACKUP_DIR="/backup"
readonly RETENTION_DIR="${HESTIA_BACKUP_DIR}/sos-backup-retention/daily"
readonly LOG_FILE="/var/log/sos-backup-retention.log"
readonly LOCK_FILE="/run/lock/sos-backup-retention.lock"
readonly RETAIN_DAILY=7
readonly MIN_FREE_GIB=12

exec 9>"${LOCK_FILE}"
if ! flock -n 9; then
  printf '%s backup ignorado: outra validacao esta em andamento\n' "$(date --iso-8601=seconds)" >>"${LOG_FILE}"
  exit 0
fi

mkdir -p "${RETENTION_DIR}"
chmod 700 "$(dirname "${RETENTION_DIR}")" "${RETENTION_DIR}"
touch "${LOG_FILE}"
chmod 600 "${LOG_FILE}"

log() {
  printf '%s %s\n' "$(date --iso-8601=seconds)" "$*" | tee -a "${LOG_FILE}"
}

latest_backup="$({
  find "${HESTIA_BACKUP_DIR}" -maxdepth 1 -type f -name 'user.????-??-??_??-??-??.tar' \
    -printf '%T@ %p\n'
} | sort -nr | awk 'NR == 1 {sub(/^[^ ]+ /, ""); print; exit}')"

if [[ -z "${latest_backup}" || ! -f "${latest_backup}" ]]; then
  log "ERRO nenhum backup Hestia foi encontrado"
  exit 1
fi

age_seconds=$(( $(date +%s) - $(stat -c %Y "${latest_backup}") ))
if (( age_seconds > 43200 )); then
  log "ERRO backup Hestia mais recente tem mais de 12 horas: ${latest_backup}"
  exit 1
fi

backup_name="$(basename "${latest_backup}")"
retained_backup="${RETENTION_DIR}/${backup_name}"
checksum_file="${retained_backup}.sha256"

if [[ -f "${retained_backup}" && -f "${checksum_file}" ]]; then
  log "OK backup ja validado e retido: ${backup_name}"
else
  if pgrep -f '/usr/local/hestia/bin/v-backup-user' >/dev/null 2>&1; then
    log "ERRO backup Hestia ainda esta em execucao; a validacao sera repetida pelo timer"
    exit 1
  fi

  log "validando catalogo TAR: ${backup_name}"
  tar -tf "${latest_backup}" >/dev/null

  if ! ln "${latest_backup}" "${retained_backup}" 2>/dev/null; then
    cp --reflink=auto --preserve=mode,timestamps "${latest_backup}" "${retained_backup}"
  fi
  checksum="$(sha256sum "${retained_backup}" | awk '{print $1}')"
  printf '%s  %s\n' "${checksum}" "${backup_name}" >"${checksum_file}.tmp"
  chmod 600 "${checksum_file}.tmp"
  mv "${checksum_file}.tmp" "${checksum_file}"
  log "OK retido e validado SHA-256=${checksum}: ${backup_name}"
fi

mapfile -t retained_files < <(
  find "${RETENTION_DIR}" -maxdepth 1 -type f -name 'user.????-??-??_??-??-??.tar' -printf '%f\n' | sort -r
)

for (( index=RETAIN_DAILY; index<${#retained_files[@]}; index++ )); do
  expired="${RETENTION_DIR}/${retained_files[$index]}"
  rm -f -- "${expired}" "${expired}.sha256"
  log "retencao removeu copia expirada: $(basename "${expired}")"
done

available_kib="$(df -Pk "${HESTIA_BACKUP_DIR}" | awk 'NR == 2 {print $4}')"
minimum_kib=$(( MIN_FREE_GIB * 1024 * 1024 ))
if (( available_kib < minimum_kib )); then
  log "ALERTA espaco livre abaixo de ${MIN_FREE_GIB} GiB: $(( available_kib / 1024 / 1024 )) GiB"
  exit 2
fi

find "${RETENTION_DIR}" -maxdepth 1 -type f -name '*.sha256' -print0 | while IFS= read -r -d '' checksum_path; do
  [[ -f "${checksum_path%.sha256}" ]] || rm -f -- "${checksum_path}"
done

log "OK rotina concluida; ${#retained_files[@]} copia(s) antes da poda; espaco livre=$(( available_kib / 1024 / 1024 )) GiB"
