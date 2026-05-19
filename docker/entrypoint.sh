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

# 1) فحص قابلية الكتابة على bind mounts وwrite probe صريح
#    ملاحظة: عند التشغيل بـ user=82:82 (www-data) لا نستطيع chown — بل يجب أن
#    يكون المجلد على المضيف ملكاً لـ UID 82 أو قابلاً للكتابة قبل تشغيل الحاوية.
#    لذا نكتفي بالفحص والإبلاغ بدل الصمت.
for dir in storage bootstrap/cache uploads; do
    full="/var/www/html/$dir"
    if [ ! -d "$full" ]; then
        log "WARN: المجلد $full غير موجود — سيُنشَأ."
        mkdir -p "$full" 2>/dev/null || true
    fi
    if [ ! -w "$full" ]; then
        log "ERROR: $full غير قابل للكتابة! نفّذ على المضيف: sudo chown -R 82:82 ./data/$dir"
        log "       ثم: docker compose restart laravel-fpm"
    fi
done

# write probe: تأكيد قابلية كتابة uploads (ضروري لرفع الصور)
if ! (echo t > /var/www/html/uploads/.writetest 2>/dev/null && rm -f /var/www/html/uploads/.writetest 2>/dev/null); then
    log "ERROR: الكتابة في /var/www/html/uploads فاشلة — رفع الصور سيفشل!"
    log "       شغّل: ./scripts/fix-permissions.sh ثم أعد تشغيل الحاوية."
else
    log "OK: /var/www/html/uploads قابل للكتابة."
fi

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
