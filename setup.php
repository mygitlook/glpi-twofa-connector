<?php
function plugin_init_twofactor() {
   global $PLUGIN_HOOKS;
   
   $PLUGIN_HOOKS['csrf_compliant']['twofactor'] = true;
   
   // Add authentication hook - this is crucial for 2FA
   $PLUGIN_HOOKS['pre_login_authenticated']['twofactor'] = 'plugin_twofactor_check_auth';
   
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