# Laravel 7 Development Container

This devcontainer provides a PHP 7.4 environment for Laravel 7 development using a custom Dockerfile and Docker Compose.

## What's included

- PHP 7.4-FPM
- Composer (latest version)
- Essential PHP extensions (pdo_mysql, mbstring, exif, pcntl, bcmath, gd)
- Git and other development tools
- VS Code extensions for PHP development

## Getting Started

1. Make sure you have Docker and VS Code with the Dev Containers extension installed
2. Open this project in VS Code
3. When prompted, click "Reopen in Container" or use the Command Palette: `Dev Containers: Reopen in Container`
4. Wait for the container to build (this may take a few minutes the first time)
5. Once ready, you can run Laravel artisan commands:

```bash
php artisan --version
php artisan list
php artisan serve --host=0.0.0.0 --port=8000
```

## Accessing the Application

After running `php artisan serve --host=0.0.0.0 --port=8000`, your Laravel application will be available at:
- **Local**: http://localhost:8000
- **Container**: The server binds to all interfaces (0.0.0.0) to allow external access

## Troubleshooting Port Access

If you still can't access port 8000:

1. Make sure you're running the Laravel server with the correct host binding:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

2. Check if the port is already in use on your host machine:
   ```bash
   lsof -i :8000
   ```

3. Try rebuilding the devcontainer:
   - Command Palette: `Dev Containers: Rebuild Container`

## Available Ports

- Port 8000: Laravel development server
- Port 3000: Additional port for frontend tools if needed

## Architecture

- **Dockerfile**: Custom PHP 7.4-FPM image with Laravel requirements
- **Docker Compose**: Service orchestration for the development environment
- **Devcontainer**: VS Code integration with the Docker setup

## Notes

- The container automatically runs `composer install` after creation
- All your project files are mounted and changes are synced in real-time
- Uses PHP 7.4-FPM base image with essential extensions for Laravel
- The environment is configured specifically for running `php artisan` commands without additional complexity