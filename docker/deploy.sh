#!/bin/bash

# Docker Deployment Script for Social Feed
# This script helps deploy the application with Docker

set -e

echo "=== Social Feed Docker Deployment ==="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if domain is provided
if [ -z "$1" ]; then
    echo -e "${RED}Error: Domain name is required${NC}"
    echo "Usage: ./deploy.sh your-domain.com [email@for-letsencrypt.com]"
    echo ""
    echo "Example: ./deploy.sh social-feed.example.com admin@example.com"
    exit 1
fi

DOMAIN=$1
EMAIL=${2:-"admin@$DOMAIN"}

echo -e "${GREEN}Configuration:${NC}"
echo "Domain: $DOMAIN"
echo "Email: $EMAIL"
echo ""

# Update nginx config with domain
echo -e "${YELLOW}Step 1: Updating Nginx configuration...${NC}"
sed -i "s/social-feed.example.com/$DOMAIN/g" docker/nginx/conf.d/app.conf

# Update docker-compose environment
if [ ! -f .env ]; then
    echo -e "${YELLOW}Step 2: Creating .env file...${NC}"
    cp .env.docker.example .env

    # Generate secure password
    DB_PASSWORD=$(openssl rand -base64 32)
    sed -i "s/change_this_secure_password/$DB_PASSWORD/g" .env

    # Generate APP_KEY
    echo -e "${YELLOW}Step 3: Generating application key...${NC}"
    docker-compose run -rm --no-deps app php artisan key:generate
fi

# Create necessary directories
echo -e "${YELLOW}Step 4: Creating directories...${NC}"
mkdir -p docker/ssl/certbot/{conf,www}
mkdir -p storage/{app,framework,logs}
mkdir -p bootstrap/cache

# Set permissions
echo -e "${YELLOW}Step 5: Setting permissions...${NC}"
chmod -R 775 storage bootstrap/cache

# Build and start containers
echo -e "${YELLOW}Step 6: Building Docker containers...${NC}"
docker-compose build

echo -e "${YELLOW}Step 7: Starting containers...${NC}"
docker-compose up -d postgres redis app nginx

# Wait for database to be ready
echo -e "${YELLOW}Step 8: Waiting for database...${NC}"
sleep 10

# Run migrations
echo -e "${YELLOW}Step 9: Running migrations...${NC}"
docker-compose exec app php artisan migrate --force

# Install dependencies and optimize
echo -e "${YELLOW}Step 10: Optimizing application...${NC}"
docker-compose exec app composer install --no-dev --optimize-autoloader
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Start worker and scheduler
echo -e "${YELLOW}Step 11: Starting background workers...${NC}"
docker-compose up -d worker scheduler

# Obtain SSL certificate
echo -e "${YELLOW}Step 12: Obtaining SSL certificate...${NC}"
echo -e "${YELLOW}Make sure your domain ($DOMAIN) is pointing to this server!${NC}"
read -p "Press enter to continue with SSL setup..." dummy

docker-compose run --rm certbot certonly --webroot --webroot-path=/var/www/certbot --email $EMAIL --agree-tos --no-eff-email -d $DOMAIN

# Reload nginx with SSL
echo -e "${YELLOW}Step 13: Restarting nginx with SSL...${NC}"
docker-compose restart nginx

echo ""
echo -e "${GREEN}=== Deployment Complete! ===${NC}"
echo ""
echo "Your application should now be available at: https://$DOMAIN"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Update your .env with Shopify and Instagram API credentials"
echo "2. Run: docker-compose exec app php artisan storage:link"
echo "3. Check logs: docker-compose logs -f"
echo "4. View status: docker-compose ps"
echo ""
echo -e "${GREEN}Useful commands:${NC}"
echo "  docker-compose logs -f [service]    # View logs"
echo "  docker-compose exec app sh          # Access container shell"
echo "  docker-compose restart [service]    # Restart a service"
echo "  docker-compose down                 # Stop all services"
echo "  docker-compose up -d                # Start all services"
