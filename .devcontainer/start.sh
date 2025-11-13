#!/bin/bash

echo "Starting PHP-FPM..."

# Ensure the PHP-FPM configuration is correct
echo "Checking PHP-FPM configuration..."
php-fpm -t

# Start PHP-FPM in the background
php-fpm -D

# Verify PHP-FPM is running
echo "Checking if PHP-FPM is running..."
if command -v ps >/dev/null 2>&1; then
    ps aux | grep php-fpm
else
    echo "ps command not available, checking processes via /proc"
    ls /proc/*/comm 2>/dev/null | xargs grep -l php-fpm 2>/dev/null | wc -l | awk '{if($1>0) print "PHP-FPM processes found: " $1; else print "No PHP-FPM processes found"}'
fi

# Check if PHP-FPM is listening on port 9000
echo "Checking if PHP-FPM is listening on port 9000..."
if command -v netstat >/dev/null 2>&1; then
    netstat -tlnp | grep 9000 || echo "PHP-FPM not listening on port 9000"
else
    echo "netstat not available, checking via /proc/net/tcp"
    if grep -q ':2328' /proc/net/tcp; then
        echo "PHP-FPM is listening on port 9000"
    else
        echo "PHP-FPM not listening on port 9000"
    fi
fi

echo "PHP-FPM startup complete"

# Keep the container running for development
sleep infinity