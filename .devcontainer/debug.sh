#!/bin/bash

echo "=== Docker Container Troubleshooting ==="

echo "1. Checking container status..."
docker-compose ps

echo ""
echo "2. Checking app container logs..."
docker-compose logs app | tail -20

echo ""
echo "3. Checking nginx container logs..."
docker-compose logs nginx | tail -20

echo ""
echo "4. Testing PHP-FPM connection from nginx container..."
docker-compose exec nginx nc -zv app 9000

echo ""
echo "5. Checking PHP-FPM processes in app container..."
docker-compose exec app ps aux | grep php-fpm

echo ""
echo "6. Checking port 9000 in app container..."
docker-compose exec app netstat -tlnp | grep 9000

echo ""
echo "7. Testing simple PHP file..."
docker-compose exec nginx wget -O - http://app:9000 || echo "Direct connection failed"

echo ""
echo "8. Creating test PHP info file..."
echo "<?php phpinfo(); ?>" > ../public/test.php
echo "Visit http://localhost:8000/test.php to test PHP processing"

echo ""
echo "=== Troubleshooting Complete ==="