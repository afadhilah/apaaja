#!/bin/bash

echo "Starting Vercel build..."

# Install Node dependencies
echo "Installing Node dependencies..."
npm install

# Build frontend assets
echo "Building frontend assets..."
npm run build

echo "Build completed successfully!"
