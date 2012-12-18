<?php

// Some constants
if (!defined('MANY_TO_MANY'))    define('MANY_TO_MANY', 'MANY_TO_MANY');
if (!defined('UPLOAD_DELETE'))   define('UPLOAD_DELETE', 'delete-');
if (!defined('UPLOAD_OLD'))      define('UPLOAD_OLD', 'old-');
if (!defined('UPLOAD_REPLACE'))  define('UPLOAD_REPLACE', 'replace-');

// Bring in bundle dependencies
Bundle::start('former');
Autoloader::alias('Former\Former', 'Former');
Bundle::start('bkwld');
Bundle::start('messages');
Bundle::start('croppa');

// Load specific interal classes
Autoloader::map(array(
	'Decoy_Base_Controller' => Bundle::path('decoy').'controllers/base.php',
	'Decoy_Base_Model' => Bundle::path('decoy').'models/base.php',
	'Decoy\Auth_Interface' => Bundle::path('decoy').'library/auth_interface.php',
));

// Load all models
Autoloader::directories(array(
	Bundle::path('decoy').'models',
));

// Decoy namespaced classes are all in the library
Autoloader::namespaces(array(
  'Decoy' => Bundle::path('decoy').'library',
));

// Auto-publish the assets when developing locally
if (Request::is_env('local') && !Request::cli()) {
	
	// Had to use this route rather than Command::run(), I couldn't
	// trigger deploy for some reason.
	ob_start();
	$publisher = new Laravel\CLI\Tasks\Bundle\Publisher;
	$publisher->publish('decoy');
	ob_end_clean(); // Supress the output from the above, which does an echo
}

// Load Decoy specific helpers
require_once('helpers.php');

// Tell the Messages bundle to use the transport defined in the
// Decoy config file
Config::set('messages::config.default', Config::get('decoy::decoy.messages_default_transport'));

// Alias the auth class for easier referencing
$auth_class = Config::get('decoy::decoy.auth_class');
if (!class_exists($auth_class)) throw new Exception('Auth class does not exist: '.$auth_class);
class_alias(Config::get('decoy::decoy.auth_class'), 'Decoy_Auth', true);
if (!is_a(new Decoy_Auth, 'Decoy\Auth_Interface')) throw new Exception('Auth class does not implement Decoy\Auth_Interface:'.$auth_class);
Decoy_Auth::check();
die;