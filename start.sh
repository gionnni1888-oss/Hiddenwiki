#!/bin/bash
set -x

echo "=== START ==="
ls -la /etc/secrets/ || true

mkdir -p /var/lib/tor/hiddenwiki

if [ -f /etc/secrets/hs_ed25519_secret_key.b64 ]; then
    echo "Loading keys..."
    base64 -d /etc/secrets/hs_ed25519_secret_key.b64 > /var/lib/tor/hiddenwiki/hs_ed25519_secret_key
    base64 -d /etc/secrets/hs_ed25519_public_key.b64 > /var/lib/tor/hiddenwiki/hs_ed25519_public_key

    if [ -f /etc/secrets/hostname ]; then
        cp /etc/secrets/hostname /var/lib/tor/hiddenwiki/hostname
    elif [ -f /etc/secrets/hostname.txt ]; then
        cp /etc/secrets/hostname.txt /var/lib/tor/hiddenwiki/hostname
    fi
fi

# IMPORTANTE: dai la cartella a debian-tor e avvia Tor come debian-tor
chown -R debian-tor:debian-tor /var/lib/tor/hiddenwiki
chmod 700 /var/lib/tor/hiddenwiki
chmod 600 /var/lib/tor/hiddenwiki/*

echo "Avvio Tor come debian-tor..."
su -s /bin/bash debian-tor -c "tor -f /etc/tor/torrc" &
sleep 15

if [ -f /var/lib/tor/hiddenwiki/hostname ]; then
    echo "========================================="
    echo "ONION ADDRESS:"
    cat /var/lib/tor/hiddenwiki/hostname
    echo "========================================="
fi

echo "Avvio PHP-FPM..."
php-fpm8.2 -D || php-fpm8.3 -D || php-fpm -D

echo "Avvio Nginx..."
nginx -g "daemon off;"

touch /var/www/html/visits.json
chmod 666 /var/www/html/visits.json
