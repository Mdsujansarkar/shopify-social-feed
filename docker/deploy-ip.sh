#!/bin/bash

# Docker Deployment Script for Social Feed (IP Address)
# This script helps deploy with self-signed SSL

set -e

echo "=== Social Feed Docker Deployment (IP Address) ==="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
IP_ADDRESS=${1:-"203.8.25.9"}
DB_PASSWORD=$(openssl rand -base64 32)

echo -e "${BLUE}Configuration:${NC}"
echo "IP Address: $IP_ADDRESS"
echo "Database: PostgreSQL 16"
echo "SSL: Self-signed certificate"
echo ""

# Step 1: Generate SSL certificate
echo -e "${YELLOW}Step 1: Generating self-signed SSL certificate...${NC}"
chmod +x docker/ssl/generate-cert.sh
./docker/ssl/generate-cert.sh $IP_ADDRESS

# Step 2: Create .env file
if [ ! -f .env ]; then
    echo -e "${YELLOW}Step 2: Creating .env file...${NC}"
    cp .env.docker.example .env

    # Update with actual IP
    sed -i "s|203.8.25.9|$IP_ADDRESS|g" .env
    sed -i "s/change_this_secure_password/$DB_PASSWORD/g" .env

    # Generate APP_KEY
    echo -e "${YELLOW}Step 3: Generating application key...${NC}"
    docker-compose run --rm --no-deps app php artisan key:generate
else
    echo -e "${GREEN}✓ .env file already exists, skipping...${NC}"
fi

# Step 4: Create necessary directories
echo -e "${YELLOW}Step 4: Creating directories...${NC}"
mkdir -p storage/{app,framework,logs}
mkdir -p bootstrap/cache
mkdir -p docker/ssl/self-signed

# Step 5: Set permissions
echo -e "${YELLOW}Step 5: Setting permissions...${NC}"
chmod -R 775 storage bootstrap/cache

# Step 6: Build and start containers
echo -e "${YELLOW}Step 6: Building Docker containers...${NC}"
docker-compose build

echo -e "${YELLOW}Step 7: Starting containers...${NC}"
docker-compose up -d postgres redis app nginx

# Step 8: Wait for database to be ready
echo -e "${YELLOW}Step 8: Waiting for database to be ready...${NC}"
for i in {1..30}; do
    if docker-compose exec -T postgres pg_isready -U social_feed_user -d social_feed &> /dev/null; then
        echo -e "${GREEN}✓ Database is ready!${NC}"
        break
    fi
    echo "Waiting... ($i/30)"
    sleep 2
done

# Step 9: Run migrations
echo -e "${YELLOW}Step 9: Running database migrations...${NC}"
docker-compose exec -T app php artisan migrate --force

# Step 10: Optimize application
echo -e "${YELLOW}Step 10: Optimizing application...${NC}"
docker-compose exec -T app composer install --no-dev --optimize-autoloader
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

# Step 11: Create storage link
echo -e "${YELLOW}Step 11: Creating storage link...${NC}"
docker-compose exec -T app php artisan storage:link

# Step 12: Start worker and scheduler
echo -e "${YELLOW}Step 12: Starting background workers...${NC}"
docker-compose up -d worker scheduler

# Wait a moment for services to stabilize
sleep 3

# Show status
echo ""
echo -e "${GREEN}=== Deployment Complete! ===${NC}"
echo ""
echo -e "${BLUE}🌐 Application URL: https://$IP_ADDRESS${NC}"
echo ""
echo -e "${YELLOW}⚠️  IMPORTANT: Self-Signed SSL Certificate${NC}"
echo "   Your browser will show a security warning."
echo "   This is NORMAL for self-signed certificates."
echo "   Click 'Advanced' → 'Proceed to $IP_ADDRESS (unsafe)'"
echo ""
echo -e "${BLUE}📊 Service Status:${NC}"
docker-compose ps
echo ""
echo -e "${BLUE}🔧 Useful Commands:${NC}"
echo "  docker-compose logs -f [service]    # View logs"
echo "  docker-compose exec app sh          # Access container shell"
echo "  docker-compose restart [service]    # Restart a service"
echo "  docker-compose down                 # Stop all services"
echo "  docker-compose up -d                # Start all services"
echo ""
echo -e "${BLUE}📝 Next Steps:${NC}"
echo "  1. Access https://$IP_ADDRESS in your browser"
echo "  2. Accept the security warning (self-signed cert)"
echo "  3. Update .env with your Shopify & Instagram API credentials:"
echo "     docker-compose exec app nano .env"
echo "  4. Restart app and worker:"
echo "     docker-compose restart app worker scheduler"
echo ""
echo -e "${GREEN}✓ All done! Your application is running.${NC}"
