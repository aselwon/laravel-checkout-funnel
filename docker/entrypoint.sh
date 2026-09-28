#!/bin/sh
set -eu
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
# A named volume persists the local demo key across restarts; production supplies APP_KEY.
if [ -z "${APP_KEY:-}" ]; then
    if [ ! -s storage/app.key ]; then
        php -r 'echo "base64:".base64_encode(random_bytes(32));' > storage/app.key
        chmod 600 storage/app.key
    fi
    export APP_KEY="$(cat storage/app.key)"
fi
exec "$@"
