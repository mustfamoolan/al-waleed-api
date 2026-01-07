#!/bin/bash

# Deployment script for Al-Waleed API
# This script is executed on the VPS server

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
DEPLOY_PATH="${DEPLOY_PATH:-/var/www/al-waleed-api}"
BRANCH="${BRANCH:-main}"

echo -e "${GREEN}🚀 Starting deployment...${NC}"

# Navigate to project directory
cd "$DEPLOY_PATH" || {
    echo -e "${RED}❌ Error: Directory $DEPLOY_PATH not found!${NC}"
    exit 1
}

echo -e "${YELLOW}📍 Current directory: $(pwd)${NC}"

# Pull latest changes
echo -e "${YELLOW}🔄 Pulling latest changes from Git...${NC}"
git fetch origin
git reset --hard "origin/$BRANCH" || {
    echo -e "${RED}❌ Error: Failed to pull from Git!${NC}"
    exit 1
}

# Show current commit
CURRENT_COMMIT=$(git rev-parse --short HEAD)
echo -e "${GREEN}✅ Latest commit: $CURRENT_COMMIT${NC}"

# Stop Docker containers
echo -e "${YELLOW}🐳 Stopping Docker containers...${NC}"
docker compose down || true

# Build Docker images
echo -e "${YELLOW}🔨 Building Docker images...${NC}"
docker compose build --no-cache

# Start Docker containers
echo -e "${YELLOW}🚀 Starting Docker containers...${NC}"
docker compose up -d

# Wait for services to be ready
echo -e "${YELLOW}⏳ Waiting for services to be ready...${NC}"
sleep 15

# Check if containers are running
if ! docker compose ps | grep -q "Up"; then
    echo -e "${RED}❌ Error: Some containers failed to start!${NC}"
    docker compose logs
    exit 1
fi

# Run migrations
echo -e "${YELLOW}📦 Running database migrations...${NC}"
docker compose exec -T app php artisan migrate --force || {
    echo -e "${YELLOW}⚠️  Warning: Migrations failed, but continuing...${NC}"
}

# Clear cache
echo -e "${YELLOW}🧹 Clearing application cache...${NC}"
docker compose exec -T app php artisan config:clear || true
docker compose exec -T app php artisan cache:clear || true
docker compose exec -T app php artisan route:clear || true
docker compose exec -T app php artisan view:clear || true

# Optimize for production
echo -e "${YELLOW}✨ Optimizing for production...${NC}"
docker compose exec -T app php artisan config:cache || true
docker compose exec -T app php artisan route:cache || true
docker compose exec -T app php artisan view:cache || true

# Show container status
echo -e "${GREEN}📊 Container status:${NC}"
docker compose ps

echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo -e "${GREEN}🌐 Application is running at: http://$(hostname -I | awk '{print $1}'):8000${NC}"

