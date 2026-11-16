#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Removes the bot of the free version of Nexora from the server.
#
#   sudo bash uninstall.sh [directory] [--purge]
#
# The files of the bot, its cron dispatcher and the vhost of nginx are
# removed. The database of the bot stays, so the bot can be installed again
# with the same data. With --purge the database is dropped as well.
#
# Nothing of the hub and nothing of the other bots of the server is touched.
# ---------------------------------------------------------------------------
set -Eeuo pipefail

PURGE=0
TARGET=""
PHP_BIN="$(command -v php || true)"

usage() {
  cat <<'USAGE'
Usage: sudo bash uninstall.sh [nexoraBOT_DIRECTORY] [--purge]

  --purge   The database of the bot is dropped as well.
USAGE
}

for arg in "$@"; do
  case "$arg" in
    --purge) PURGE=1 ;;
    -h|--help) usage; exit 0 ;;
    -*)
      echo "Unknown option: $arg" >&2; usage; exit 2 ;;
    *)
      if [[ -n "$TARGET" ]]; then
        echo "Only one directory." >&2
        exit 2
      fi
      TARGET="$arg" ;;
  esac
done

# The directory of the bot is searched, when it is not given.
if [[ -z "$TARGET" ]]; then
  for candidate in /var/www/html/nexorabot /var/www/html/nexorabotconfig /var/www/nexorabot \
                  /var/www/html/bot /var/www/html/* /var/www/* /srv/http/* /opt/*; do
    if [[ -f "$candidate/function.php" && -f "$candidate/index.php" && -f "$candidate/config.php" ]]; then
      TARGET="$candidate"
      break
    fi
  done
fi

if [[ -z "$TARGET" || ! -d "$TARGET" ]]; then
  echo "The directory of the bot was not found. Pass it as an argument." >&2
  usage
  exit 1
fi

TARGET="$(cd -- "$TARGET" 2>/dev/null && pwd)"
echo "==> Removing the bot from $TARGET"

# ---- the cron dispatcher --------------------------------------------------
# The lines of the crontab that point at this installation are taken out.
if command -v crontab >/dev/null 2>&1; then
  for _user in root www-data; do
    id -u "$_user" >/dev/null 2>&1 || continue
    _cron="$(crontab -u "$_user" -l 2>/dev/null || true)"
    if [[ -n "$_cron" ]] && printf '%s' "$_cron" | grep -qF "$TARGET"; then
      printf '%s' "$_cron" | grep -vF "$TARGET" | crontab -u "$_user" - || true
      echo "    the cron dispatcher of $_user was removed"
    fi
  done
fi

# ---- the vhost of nginx ---------------------------------------------------
if [[ -d /etc/nginx/sites-enabled ]]; then
  for _vhost in /etc/nginx/sites-enabled/*; do
    [[ -e "$_vhost" ]] || continue
    if grep -qF "$TARGET" "$_vhost" 2>/dev/null; then
      rm -f -- "$_vhost"
      echo "    the vhost of nginx was removed: $(basename "$_vhost")"
    fi
  done
  if command -v nginx >/dev/null 2>&1; then
    nginx -t >/dev/null 2>&1 && systemctl reload nginx 2>/dev/null && echo "    nginx was reloaded" || true
  fi
fi

# ---- the files of the bot -------------------------------------------------
# The configuration is kept once, so a new installation can be made with the
# same token and the same database.
_keep="/var/backups/nexorabot"
if [[ "${EUID:-$(id -u)}" -eq 0 ]]; then
  mkdir -p "$_keep"
  chmod 700 "$_keep" 2>/dev/null || true
  if [[ -f "$TARGET/config.php" ]]; then
    cp -a -- "$TARGET/config.php" "$_keep/config.php.last" 2>/dev/null || true
    echo "    the configuration was saved in $_keep/config.php.last"
  fi
fi

rm -rf -- "$TARGET"
echo "    the files of the bot were removed"

# ---- the database ---------------------------------------------------------
if [[ "$PURGE" -eq 1 ]]; then
  echo "==> Removing the database of the bot"
  if [[ -z "$PHP_BIN" ]]; then
    echo "PHP CLI is required for --purge." >&2
    exit 1
  fi
  if [[ -f "$_keep/config.php.last" ]]; then
    "$PHP_BIN" -r '
      $t = $argv[1];
      require $t;
      if (!isset($dbname) || $dbname === "") { fwrite(STDERR, "The database is not written in the configuration.\n"); exit(1); }
      if (!isset($pdo) || !($pdo instanceof PDO)) { fwrite(STDERR, "No connection to the database.\n"); exit(1); }
      $pdo->exec("DROP DATABASE IF EXISTS `" . str_replace("`", "", $dbname) . "`");
      echo "    the database " . $dbname . " was dropped\n";
    ' "$_keep/config.php.last" || echo "    WARNING: the database was not dropped"
  else
    echo "    WARNING: the configuration of the bot is not there - the database was kept"
  fi
else
  echo "    the database of the bot was kept (use --purge to drop it)"
fi

echo
echo "Uninstall complete."
