#!/usr/bin/env bash
# =====================================================================
# Laravel container entrypoint
#   - ينتظر اتصال MySQL.
#   - يضبط الصلاحيات.
#   - يبني الـ caches ويربط storage.
#   - يطبّق الترحيلات (migrate --force) إن أمكن.
#   - ثم ينفذ الأمر الممرّر (php-fpm افتراضياً).
# =====================================================================
set -euo pipefail

cd /var/www/html

log() { printf '[entrypoint] %s\n' "$*"; }

# 1) ضبط الصلاحيات (مفيد عند أول إقلاع أو بعد bind-mount)
chown -R www-data:www-data storage bootstrap/cache uploads 2>/dev/null || true
find storage bootstrap/cache uploads -type d -exec chmod 775 {} \; 2>/dev/null || true

# 2) انتظار MySQL إذا كانت بياناته موجودة
DB_HOST="${DB_HOST:-host.docker.internal}"
DB_PORT="${DB_PORT:-3306}"
DB_USERNAME="${DB_USERNAME:-}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_DATABASE="${DB_DATABASE:-}"

if [ -n "${DB_USERNAME}" ] && [ -n "${DB_DATABASE}" ]; then
    log "في انتظار قاعدة البيانات على ${DB_HOST}:${DB_PORT}..."
    tries=0
    until mysqladmin ping -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USERNAME}" -p"${DB_PASSWORD}" --silent >/dev/null 2>&1; do
        tries=$((tries + 1))
        if [ "${tries}" -ge 30 ]; then
            log "تجاوز عدد المحاولات؛ المتابعة بدون انتظار."
            break
        fi
        sleep 2
    done
    log "DB متاحة (أو انتهى المهلة)."
fi

# 3) مسح كاش قديم ثم إعادة بنائه
#    (مهم: عند إعادة البناء بعد تحديث الكود يجب التأكد أن config.php القديم
#     لا يستخدم بعد الآن. optimize:clear يحذف bootstrap/cache/config.php
#     وroutes-v7.php وviews/* وكاش الـ events.)
php artisan optimize:clear || log "optimize:clear فشل (متابعة)."

#    (لا نحتاج storage:link لأن الصور تُخدم من /uploads/ مباشرة)
php artisan config:cache  || log "config:cache فشل (متابعة)."
php artisan route:cache   || log "route:cache فشل (متابعة)."
php artisan view:cache    || log "view:cache فشل (متابعة)."

# 4) الترحيلات
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    log "تشغيل php artisan migrate --force"
    php artisan migrate --force || log "migrate فشل (متابعة)."
fi

# 5) توحيد مسارات الصور (idempotent — يطبع نتائج فقط عند وجود عمل)
if [ "${RUN_IMAGES_UNIFY:-true}" = "true" ]; then
    log "تشغيل php artisan images:unify"
    php artisan images:unify --quiet-when-clean || log "images:unify فشل (متابعة)."
fi

# 6) تشغيل CMD الممرّر
log "بدء: $*"
exec "$@"
