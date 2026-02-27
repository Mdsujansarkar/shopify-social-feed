# Docker Deployment Guide

## Production Deployment

### Prerequisites
- Docker and Docker Compose installed
- Domain name pointing to your server
- SSH access to server

### Quick Start

1. **Upload files to server**
   ```bash
   scp -r . user@your-server:/var/www/social-feed
   ssh user@your-server
   cd /var/www/social-feed
   ```

2. **Run deployment script**
   ```bash
   chmod +x docker/deploy.sh
   ./docker/deploy.sh your-domain.com your-email@example.com
   ```

3. **Configure environment**
   ```bash
   docker-compose exec app nano .env
   # Add your Shopify and Instagram credentials
   ```

4. **Restart with new config**
   ```bash
   docker-compose restart app worker scheduler
   ```

### Manual Setup

If you prefer manual setup instead of the deployment script:

1. **Update nginx config**
   ```bash
   nano docker/nginx/conf.d/app.conf
   # Change social-feed.example.com to your domain
   ```

2. **Create .env file**
   ```bash
   cp .env.docker.example .env
   nano .env
   # Update all required values
   ```

3. **Generate app key**
   ```bash
   docker-compose run --rm app php artisan key:generate
   ```

4. **Build and start**
   ```bash
   docker-compose build
   docker-compose up -d
   ```

5. **Run migrations**
   ```bash
   docker-compose exec app php artisan migrate --force
   ```

6. **Obtain SSL certificate**
   ```bash
   # First, update domain in app.conf
   docker-compose run --rm certbot certonly --webroot \
     --webroot-path=/var/www/certbot \
     --email your-email@example.com \
     --agree-tos \
     -d your-domain.com
   ```

### Useful Commands

```bash
# View logs
docker-compose logs -f [service]

# Restart specific service
docker-compose restart nginx

# Access container shell
docker-compose exec app sh

# Run artisan commands
docker-compose exec app php artisan [command]

# Update application
docker-compose exec app composer install
docker-compose exec app php artisan migrate --force

# Stop all services
docker-compose down

# Start all services
docker-compose up -d

# View status
docker-compose ps
```

## Development Setup

For local development with hot reload:

```bash
cd docker
docker-compose -f docker-compose.dev.yml up -d

# Access at http://localhost:8000
```

### Development Commands

```bash
# Run tests
docker-compose -f docker-compose.dev.yml exec app php artisan test

# Install npm packages
docker-compose -f docker-compose.dev.yml exec app npm install

# Build assets
docker-compose -f docker-compose.dev.yml exec app npm run build
```

## Troubleshooting

### SSL Certificate Issues

**Certificate not found:**
- Make sure the domain is correctly set in `docker/nginx/conf.d/app.conf`
- Ensure your domain DNS is pointing to the server
- Run certbot manually again

**Auto-renewal not working:**
- Certbot container auto-renews every 12 hours
- Check logs: `docker-compose logs certbot`

### Database Issues

**Connection refused:**
- Check if postgres is running: `docker-compose ps`
- Verify credentials in .env match docker-compose.yml
- Check logs: `docker-compose logs postgres`

### Performance Tuning

**Adjust PHP-FPM settings:**
Edit `docker/php/www.conf` and rebuild:
```bash
docker-compose build app
docker-compose up -d app
```

**Adjust Nginx workers:**
Edit `docker/nginx/nginx.conf` and restart:
```bash
docker-compose restart nginx
```

### Backup & Restore

**Backup database:**
```bash
docker-compose exec postgres pg_dump -U social_feed_user social_feed > backup.sql
```

**Restore database:**
```bash
docker-compose exec -T postgres psql -U social_feed_user social_feed < backup.sql
```

**Backup volumes:**
```bash
docker run --rm -v social-feed_postgres-data:/data -v $(pwd):/backup alpine tar czf /backup/postgres-backup.tar.gz -C /data .
```

## Monitoring

### View Resource Usage
```bash
docker stats
```

### Check Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f worker
```

## Security Checklist

- [ ] Change default database passwords
- [ ] Set strong APP_KEY
- [ ] Enable HTTPS with valid SSL
- [ ] Set `APP_DEBUG=false` in production
- [ ] Configure firewall rules
- [ ] Set up regular backups
- [ ] Monitor logs for suspicious activity
- [ ] Keep Docker images updated

## Updating the Application

```bash
# Pull latest code
git pull

# Rebuild containers
docker-compose build

# Restart services
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --force

# Clear cache
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```
