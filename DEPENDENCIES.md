# Dependency Management with PHP-Scoper

## The Problem

Backdrop CMS does not require Composer for module installation. When multiple modules ship their own `vendor/` directories with the same dependencies (e.g., Guzzle, OpenAI client), namespace collisions can occur.

## The Solution

This module uses **php-scoper** to create a prefixed copy of all dependencies, avoiding conflicts with other modules. The autoloader intelligently chooses between:

1. **Global vendor** (if Composer Manager module is enabled)
2. **Scoped vendor** (prefixed, shipped with module)
3. **Regular vendor** (development mode only)

## For Developers

### Development Setup

```bash
# Install dependencies normally
composer install

# Dependencies are in vendor/ (not prefixed)
# The autoloader.inc will use this in development mode
```

### Building for Distribution

```bash
# Run the build script
./build.sh

# This creates vendor-scoped/ with prefixed dependencies
# All classes will be under BackdropOpenAI\ namespace
```

### How It Works

**Autoloader Priority:**
1. Check if `composer_manager` module exists → use global `vendor/autoload.php`
2. Check if `vendor-scoped/autoload.php` exists → use prefixed dependencies
3. Fallback to `vendor/autoload.php` (development mode)

**Files:**
- `scoper.inc.php` - Configuration for php-scoper
- `includes/autoloader.inc` - Smart autoloader that detects environment
- `build.sh` - Build script to generate scoped vendor

### Manual Build Steps

If you prefer not to use the build script:

```bash
# 1. Install dependencies
composer install --no-dev

# 2. Install php-scoper
composer require --dev humbug/php-scoper

# 3. Run php-scoper
vendor/bin/php-scoper add-prefix --output-dir=vendor-scoped --force

# 4. Generate optimized autoloader
composer dump-autoload --working-dir=vendor-scoped --classmap-authoritative --no-dev
```

## For End Users

### With Composer Manager

If you have the [Composer Manager](https://backdropcms.org/project/composer_manager) module enabled:

1. The module will use your global vendor directory
2. No special setup needed
3. Dependencies are shared across all modules

### Without Composer Manager

The module ships with `vendor-scoped/` directory containing all dependencies with the `BackdropOpenAI\` namespace prefix. This prevents conflicts with other modules.

1. Extract the module to your modules directory
2. Enable the module
3. Dependencies load automatically from `vendor-scoped/`

## Troubleshooting

### "Could not load Composer autoloader" error

**Cause:** Neither global vendor nor scoped vendor directories are found.

**Solution:**
1. Enable Composer Manager module, OR
2. Download a release that includes `vendor-scoped/`, OR
3. Run `./build.sh` to generate `vendor-scoped/`

### Namespace conflicts

**Cause:** Using unscoped vendor with other modules that ship the same dependencies.

**Solution:**
1. Enable Composer Manager for centralized dependency management, OR
2. Use the scoped build (`./build.sh`) to prefix all dependencies

## Architecture

```
openai/
├── includes/
│   ├── autoloader.inc          # Smart autoloader (priority: global → scoped → vendor)
│   ├── OpenAIApi.php            # Uses autoloader.inc
│   └── StringHelper.php
├── vendor/                      # Development dependencies (gitignored)
├── vendor-scoped/               # Distribution dependencies (prefixed, gitignored)
├── scoper.inc.php               # php-scoper configuration
├── build.sh                     # Build script
└── composer.json                # Dependency definitions + build scripts
```

## References

- [php-scoper](https://github.com/humbug/php-scoper) - Tool for prefixing PHP code
- [Composer Manager](https://backdropcms.org/project/composer_manager) - Centralized Composer for Backdrop
