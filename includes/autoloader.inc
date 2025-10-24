<?php

/**
 * @file
 * Autoloader helper for OpenAI module.
 * 
 * This file intelligently loads dependencies from either:
 * 1. Global vendor directory (if Composer Manager module is enabled)
 * 2. Scoped vendor directory (shipped with module to avoid conflicts)
 */

/**
 * Load the appropriate autoloader based on environment.
 * 
 * @return bool
 *   TRUE if autoloader was loaded successfully, FALSE otherwise.
 */
function openai_load_autoloader() {
  static $loaded = FALSE;
  
  if ($loaded) {
    return TRUE;
  }

  $module_path = BACKDROP_ROOT . '/' . backdrop_get_path('module', 'openai');
  
  // Option 1: Check if Composer Manager module is enabled and has a global autoloader
  if (module_exists('composer_manager')) {
    $composer_manager_path = backdrop_get_path('module', 'composer_manager');
    $global_autoloader = BACKDROP_ROOT . '/' . dirname($composer_manager_path) . '/vendor/autoload.php';
    
    if (file_exists($global_autoloader)) {
      require_once $global_autoloader;
      $loaded = TRUE;
      watchdog('openai', 'Loaded dependencies from Composer Manager global vendor directory.', [], WATCHDOG_INFO);
      return TRUE;
    }
  }
  
  // Option 2: Use scoped vendor directory (shipped with module)
  $scoped_autoloader = $module_path . '/build/autoload.php';
  if (file_exists($scoped_autoloader)) {
    require_once $scoped_autoloader;
    $loaded = TRUE;
    return TRUE;
  }
  
  // No autoloader found - log error
  watchdog('openai', 'Could not load Composer autoloader. OpenAI module may be corrupted or build directory is missing.', [], WATCHDOG_ERROR);
  backdrop_set_message(
    t('OpenAI module dependencies are missing. Please reinstall the module or enable the Composer Manager module.'),
    'error'
  );
  
  return FALSE;
}

/**
 * Get the namespace prefix used for scoped dependencies.
 * 
 * @return string
 *   The namespace prefix, or empty string if using unscoped dependencies.
 */
function openai_get_namespace_prefix() {
  // If using Composer Manager, no prefix
  if (module_exists('composer_manager')) {
    $composer_manager_path = backdrop_get_path('module', 'composer_manager');
    $global_autoloader = BACKDROP_ROOT . '/' . dirname($composer_manager_path) . '/vendor/autoload.php';
    if (file_exists($global_autoloader)) {
      return '';
    }
  }
  
  // Otherwise, using scoped vendor (shipped with module)
  return 'BackdropOpenAI\\';
}
