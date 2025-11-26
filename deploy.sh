#!/bin/bash

echo "=========================================="
echo "FirmWizard Production Deployment"
echo "=========================================="
echo ""

# Navigate to app directory
cd /var/www/html/app.firmwizard.com || { echo "App directory not found!"; exit 1; }

echo "✓ Current directory: $(pwd)"
echo ""

# Pull latest code
echo "📥 Pulling latest code from GitHub..."
git pull origin master || { echo "Git pull failed!"; exit 1; }
echo "✓ Code updated"
echo ""

# Install PHP dependencies
echo "📦 Installing Composer dependencies..."
/opt/plesk/php/8.4/bin/php /usr/local/bin/composer install --no-dev --optimize-autoloader
echo "✓ Composer packages installed"
echo ""

# Install Node dependencies
echo "📦 Installing NPM dependencies..."
npm install --production
echo "✓ NPM packages installed"
echo ""

# Build frontend assets
echo "🔨 Building frontend assets..."
npm run build
echo "✓ Assets compiled"
echo ""

# Run migrations
echo "🗄️  Running database migrations..."
/opt/plesk/php/8.4/bin/php artisan migrate --force
echo "✓ Migrations completed"
echo ""

# Clear and cache config
echo "⚙️  Caching configuration..."
/opt/plesk/php/8.4/bin/php artisan config:cache
/opt/plesk/php/8.4/bin/php artisan route:cache
/opt/plesk/php/8.4/bin/php artisan view:cache
echo "✓ Configuration cached"
echo ""

# Generate Swagger docs
echo "📚 Generating API documentation..."
/opt/plesk/php/8.4/bin/php artisan l5-swagger:generate
echo "✓ API docs generated"
echo ""

# Install Python dependencies
echo "🐍 Installing Python dependencies..."
pip3 install -r app/Scripts/Python/requirements.txt
echo "✓ Python packages installed"
echo ""

# Set permissions
echo "🔒 Setting permissions..."
chmod -R 755 storage bootstrap/cache
chown -R psaadm:psaadm storage bootstrap/cache
echo "✓ Permissions set"
echo ""

# Restart queue workers (if running)
echo "🔄 Restarting queue workers..."
/opt/plesk/php/8.4/bin/php artisan queue:restart
echo "✓ Queue workers restarted"
echo ""

echo "=========================================="
echo "✅ Deployment Complete!"
echo "=========================================="
echo ""
echo "Your application is now running the latest code."
echo "Check https://app.firmwizard.com to verify."
