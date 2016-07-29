<?php

use Symfony\Component\Yaml\Yaml;

$databases = array();
$config_directories = array();
$settings['install_profile'] = 'cr';

/**
 * Load environment variables.
 */
$environment = __DIR__ . "/environment.yml";
if (file_exists($environment)) {
  $environment_variables = Yaml::parse(file_get_contents($environment));

  $databases = $environment_variables['databases'];
  $settings = array_merge($settings, $environment_variables['settings']);
  $config = array_merge($config, $environment_variables['config']);
  $config_directories['sync'] = $environment_variables['config_dir'];
}

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Include settings for platform.sh
 */
// Local settings. These come last so that they can override anything.
if (file_exists(__DIR__ . '/settings.local.php')) {
  include __DIR__ . '/settings.local.php';
}

// $local_settings = dirname(__FILE__) . '/settings.local.php';
// if (file_exists($local_settings)) {
//   require $local_settings;
//     echo "LOADED!! local settings";
//     echo "Salt: " . $drupal_hash_salt;
//     echo "Env var: " . $_ENV['PLATFORM_PROJECT_ENTROPY'];

// }
