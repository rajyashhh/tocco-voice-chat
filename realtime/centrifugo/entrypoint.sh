#!/bin/sh
# entrypoint حاوية Centrifugo — يولّد config.json من config.template.json بقيم البيئة (.env)
# ثم يشغّل Centrifugo. يعتمد على sed فقط (متوفر في صورة alpine؛ لا envsubst).
#
# الفائدة: العميل يعدّل .env فقط. لا يلمس JSON ولا يشغّل أدوات يدوية. صفر أسرار مكشوفة
# في القالب (القالب placeholders فقط)، والقيم الحقيقية تعيش في .env على السيرفر.
set -e

TEMPLATE=/centrifugo/config.template.json
OUT=/tmp/centrifugo-config.json

# المتغيّرات المطلوبة — لو أي واحد فاضٍ، أوقف بوضوح بدل ما تقلع بإعداد مكسور.
REQUIRED="CENTRIFUGO_API_KEY CENTRIFUGO_HMAC_SECRET CENTRIFUGO_PROXY_SECRET CENTRIFUGO_REDIS_ADDRESS CENTRIFUGO_CLIENT_ALLOWED_ORIGINS LARAVEL_PROXY_BASE_URL"
missing=""
for v in $REQUIRED; do
  eval "val=\${$v}"
  [ -z "$val" ] && missing="$missing $v"
done
if [ -n "$missing" ]; then
  echo "FATAL: متغيّرات Centrifugo مفقودة في .env:$missing" >&2
  echo "       راجع docs/INSTALL-*.md قسم 'تشغيل سيرفر الريل تايم'." >&2
  exit 1
fi
# CENTRIFUGO_REDIS_PASSWORD اختياري (redis بلا كلمة مرور افتراضياً) — استخدم فارغاً إن غاب.
: "${CENTRIFUGO_REDIS_PASSWORD:=}"

# استبدال آمن بـ sed: نستخدم | كفاصل ونهرّب أي | داخل القيم (نادر، لكن للأمان).
esc() { printf '%s' "$1" | sed -e 's/[&|\\]/\\&/g'; }

cp "$TEMPLATE" "$OUT"
for v in $REQUIRED CENTRIFUGO_REDIS_PASSWORD; do
  eval "val=\${$v}"
  sed -i "s|\${$v}|$(esc "$val")|g" "$OUT"
done

# فحص أمان: لا يجوز بقاء أي placeholder ${...} غير محلول.
if grep -q '\${' "$OUT"; then
  echo "FATAL: بقيت placeholders غير محلولة في config.json:" >&2
  grep -o '\${[A-Z_]*}' "$OUT" | sort -u >&2
  exit 1
fi

echo "Centrifugo config generated OK (origins: ${CENTRIFUGO_CLIENT_ALLOWED_ORIGINS})"
exec centrifugo -c "$OUT"