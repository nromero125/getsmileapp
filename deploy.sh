#!/bin/bash
set -e

echo "🔨 Building image..."
docker compose build app

echo "📦 Extracting public/build from image..."
docker rm -f tmp_extract 2>/dev/null || true
docker create --name tmp_extract dentaris_app
docker cp tmp_extract:/var/www/html/public/build ./public/
docker rm tmp_extract

echo "🚀 Starting services..."
docker compose up -d

echo "✅ Done. Logs:"
docker compose logs --tail=20 app
