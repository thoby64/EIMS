#!/bin/bash
set -e

echo "🚀 Starting EIMS Application..."

# Run migrations
echo "📦 Running database migrations..."
php artisan migrate --force

# Clear and rebuild caches
echo "⚙️ Clearing application caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Rebuild caches
echo "🔧 Building application caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure assets are accessible
echo "📂 Setting permissions for assets..."
chmod -R 755 public/build
chmod -R 755 storage

# Start the application
echo "✅ Application started successfully!"
exec apache2-foreground
