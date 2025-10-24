#!/bin/bash

# Build script for OpenAI Backdrop module
# This script scopes dependencies to avoid conflicts with other modules

set -e

# Check if we're in the module root
if [ ! -f "openai.info" ]; then
    echo "Error: This script must be run from the openai module root directory."
    exit 1
fi

echo "Building scoped dependencies for OpenAI module..."

# Check if vendor/bin/php-scoper exists
if [ ! -f "vendor/bin/php-scoper" ]; then
    echo "Error: php-scoper not found. Please run 'composer install' first."
    exit 1
fi

# Remove old scoped directory
if [ -d "build" ]; then
    echo "Removing old build directory..."
    rm -rf build
fi

# Run php-scoper
echo "Running php-scoper..."
vendor/bin/php-scoper add-prefix --force

echo "Done! Scoped dependencies are in build/"
