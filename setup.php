<?php
function plugin_init_twofactor() {
   global $PLUGIN_HOOKS;
   
   $PLUGIN_HOOKS['csrf_compliant']['twofactor'] = true;
   
   // Add authentication hooks - this is crucial for 2FA
   $PLUGIN_HOOKS['pre_init']['twofactor'] = 'plugin_twofactor_check_auth';
   $PLUGIN_HOOKS['init_session']['twofactor'] = 'plugin_twofactor_check_auth';
   
   // Hook for new user creation
   $PLUGIN_HOOKS['user_creation']['twofactor'] = 'plugin_twofactor_user_creation';
   
   // Add menu entry for configuration
   Plugin::registerClass('PluginTwofactorConfig', ['addtomenu' => true]);
   $PLUGIN_HOOKS['menu_toadd']['twofactor'] = [
      'config' => 'PluginTwofactorConfig'
   ];
}

function plugin_version_twofactor() {
   return [
      'name' => 'Two Factor Authentication',
      'version' => '1.0.0',
      'author' => 'Your Name',
      'license' => 'GPLv2+',
      'homepage' => '',
      'requirements' => [
         'glpi' => [
            'min' => '9.5'
         ]
      ]
   ];
}

function plugin_twofactor_check_auth() {
   global $DB, $CFG_GLPI;
   
   // Skip check if not logged in or on login page
   if (!isset($_SESSION['glpiID']) || strpos($_SERVER['PHP_SELF'], '/front/login.php') !== false) {
      return true;
   }
   
   // Skip 2FA check for specific pages
   $current_page = $_SERVER['PHP_SELF'];
   $allowed_pages = [
      '/plugins/twofactor/front/verify.php',
      '/plugins/twofactor/front/config.form.php'
   ];
   
   foreach ($allowed_pages as $page) {
      if (strpos($current_page, $page) !== false) {
         return true;
      }
   }
   
   $userId = $_SESSION['glpiID'];
   
   // Check if user has 2FA secret
   $query = "SELECT * FROM glpi_plugin_twofactor_secrets 
             WHERE users_id = $userId";
   $result = $DB->query($query);
   
   if ($DB->numrows($result) > 0) {
      $row = $DB->fetch_assoc($result);
      
      // If user has 2FA enabled but not verified in this session
      if (!isset($_SESSION['plugin_twofactor_verified'])) {
         Html::redirect($CFG_GLPI['root_doc'] . '/plugins/twofactor/front/verify.php');
         exit();
      }
   } else {
      // User doesn't have 2FA set up at all, redirect to setup
      Html::redirect($CFG_GLPI['root_doc'] . '/plugins/twofactor/front/config.form.php');
      exit();
   }
   
   return true;
}

function plugin_twofactor_user_creation($user) {
   global $DB;
   
   // Generate and store 2FA secret for new user
   require_once(GLPI_ROOT . '/plugins/twofactor/lib/otphp/lib/otphp.php');
   $totp = \OTPHP\TOTP::create();
   $secret = $totp->getSecret();
   
   $query = "INSERT INTO glpi_plugin_twofactor_secrets 
             (users_id, secret, is_active, date_creation) 
             VALUES 
             ({$user->fields['id']}, '$secret', 1, NOW())";
   $DB->query($query);
}