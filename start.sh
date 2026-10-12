#!/bin/bash
set -x   # stampa ogni comando

echo "=== START ==="
echo "Secrets presenti:"
ls -la /etc/secrets/ || echo "Nessuna cartella /etc/secrets"

mkdir -p /var/lib/tor/hiddenwiki

if [ -f /etc/secrets/hs_ed25519_secret_key.b64 ]; then
    echo "Trovato secret key b64"
    base64 -d /etc/secrets/hs_ed25519_secret_key.b64 > /var/lib/tor/hiddenwiki/hs_ed25519_secret_key
else
    echo "ERRORE: manca hs_ed25519_secret_key.b64"
fi

if [ -f /etc/secrets/hs_ed25519_public_key.b64 ]; then
    echo "Trovato public key b64"
    base64 -d /etc/secrets/hs_ed25519_public_key.b64 > /var/lib/tor/hiddenwiki/hs_ed25519_public_key
else
    echo "ERRORE: manca hs_ed25519_public_key.b64"
fi

if [ -f /etc/secrets/hostname ]; then
    echo "Trovato hostname"
    cp /etc/secrets/hostname /var/lib/tor/hiddenwiki/hostname
elif [ -f /etc/secrets/hostname.txt ]; then
    echo "Trovato hostname.txt"
    cp /etc/secrets/hostname.txt /var/lib/tor/hiddenwiki/hostname
else
    echo "WARNING: hostname non trovato"
fi

ls -la /var/lib/tor/hiddenwiki/ || true
chown -R debian-tor:debian-tor /var/lib/tor/hiddenwiki 2>/dev/null || true
chmod 700 /var/lib/tor/hiddenwiki
chmod 600 /var/lib/tor/hiddenwiki/* 2>/dev/null || true

echo "Avvio Tor..."
tor -f /etc/tor/torrc &
TOR_PID=$!
sleep 12

if [ -f /var/lib/tor/hiddenwiki/hostname ]; then
    echo "========================================="
    echo "ONION ADDRESS:"
    cat /var/lib/tor/hiddenwiki/hostname
    echo "========================================="
else
    echo "ERRORE: hostname non generato"
fi

echo "Avvio PHP-FPM..."
php-fpm8.2 -D || php-fpm8.3 -D || php-fpm -D || echo "PHP-FPM fallito"

echo "Avvio Nginx..."
nginx -g "daemon off;"
