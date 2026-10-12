#!/bin/bash
set -e

# Crea directory
mkdir -p /var/lib/tor/hiddenwiki

# Se esistono i secret in base64, li decodifica
if [ -f /etc/secrets/hs_ed25519_secret_key.b64 ]; then
    echo "Loading persistent onion keys from base64..."
    base64 -d /etc/secrets/hs_ed25519_secret_key.b64 > /var/lib/tor/hiddenwiki/hs_ed25519_secret_key
    base64 -d /etc/secrets/hs_ed25519_public_key.b64 > /var/lib/tor/hiddenwiki/hs_ed25519_public_key
    cp /etc/secrets/hostname /var/lib/tor/hiddenwiki/hostname
    chown -R debian-tor:debian-tor /var/lib/tor/hiddenwiki
    chmod 700 /var/lib/tor/hiddenwiki
    chmod 600 /var/lib/tor/hiddenwiki/*
fi

# Avvia Tor
tor -f /etc/tor/torrc &
sleep 10

# Mostra l'indirizzo
if [ -f /var/lib/tor/hiddenwiki/hostname ]; then
    echo "========================================="
    echo "ONION ADDRESS:"
    cat /var/lib/tor/hiddenwiki/hostname
    echo "========================================="
fi

# Avvia PHP-FPM e Nginx
php-fpm8.2 -D || php-fpm8.3 -D || php-fpm -D
nginx -g "daemon off;"
