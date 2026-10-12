#!/bin/bash
set -e

# Se esistono le chiavi salvate come secret, le usiamo (stesso .onion)
if [ -f /etc/secrets/hs_ed25519_secret_key ]; then
    echo "Loading persistent onion keys..."
    mkdir -p /var/lib/tor/hiddenwiki
    cp /etc/secrets/hs_ed25519_secret_key /var/lib/tor/hiddenwiki/
    cp /etc/secrets/hs_ed25519_public_key /var/lib/tor/hiddenwiki/ 2>/dev/null || true
    cp /etc/secrets/hostname /var/lib/tor/hiddenwiki/ 2>/dev/null || true
    chown -R debian-tor:debian-tor /var/lib/tor/hiddenwiki
    chmod 700 /var/lib/tor/hiddenwiki
    chmod 600 /var/lib/tor/hiddenwiki/*
fi

# Avvia Tor
tor -f /etc/tor/torrc &
sleep 8

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
