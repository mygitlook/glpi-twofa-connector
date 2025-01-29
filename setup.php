<?php
function plugin_init_twofactor() {
   global $PLUGIN_HOOKS;
   
   $PLUGIN_HOOKS['csrf_compliant']['twofactor'] = true;
   
   // Add authentication hook - this is crucial for 2FA
   $PLUGIN_HOOKS['pre_login_authenticated']['twofactor'] = 'plugin_twofactor_check_auth';
   
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

function plugin_twofactor_check_auth($user) {
   include_once(GLPI_ROOT . '/plugins/twofactor/inc/auth.class.php');
   return PluginTwofactorAuth::checkAuth($user);
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