<?php
function plugin_init_twofactor() {
   global $PLUGIN_HOOKS;
   
   try {
      $PLUGIN_HOOKS['csrf_compliant']['twofactor'] = true;
      
      // Register the plugin class
      Plugin::registerClass('PluginTwofactorConfig', [
         'addtabon' => ['User']
      ]);
      
      // Register authentication hooks
      $PLUGIN_HOOKS['pre_login']['twofactor'] = 'plugin_twofactor_check_auth';
      $PLUGIN_HOOKS['post_login']['twofactor'] = 'plugin_twofactor_check_auth';
      
      // Hook for new user creation
      $PLUGIN_HOOKS['user_creation']['twofactor'] = 'plugin_twofactor_user_creation';
      
      // Add configuration page - removed rights check to ensure redirection works
      if (Session::getLoginUserID()) {
         $PLUGIN_HOOKS['menu_toadd']['twofactor'] = ['config' => 'PluginTwofactorConfig'];
         $PLUGIN_HOOKS['config_page']['twofactor'] = 'front/config.php';
      }
      
      return true;
   } catch (Exception $e) {
      Toolbox::logError('2FA Plugin Initialization Error: ' . $e->getMessage());
      return false;
   }
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
            'min' => '10.0.0',
            'max' => '10.0.99',
            'dev' => false
         ],
         'php' => [
            'min' => '7.4.0'
         ]
      ]
   ];
}

function plugin_twofactor_check_auth() {
   global $DB, $CFG_GLPI;
   
   // Skip check if not logged in
   if (!isset($_SESSION['glpiID'])) {
      return true;
   }

   // Skip 2FA check for specific pages
   $current_page = $_SERVER['PHP_SELF'];
   $allowed_pages = [
      '/front/login.php',
      '/plugins/twofactor/front/verify.php',
      '/plugins/twofactor/front/config.php',
      '/front/plugin.form.php',
      '/front/plugin.php',
      '/ajax/common.tabs.php'
   ];
   
   foreach ($allowed_pages as $page) {
      if (strpos($current_page, $page) !== false) {
         return true;
      }
   }
   
   try {
      $userId = $_SESSION['glpiID'];
      
      // Check if user has active 2FA setup
      $query = "SELECT * FROM glpi_plugin_twofactor_secrets 
                WHERE users_id = $userId AND is_active = 1";
      $result = $DB->query($query);
      
      if ($DB->numrows($result) === 0) {
         // Force redirect to 2FA setup if not configured
         $_SESSION['plugin_twofactor_needs_setup'] = true;
         Html::redirect($CFG_GLPI['root_doc'] . '/plugins/twofactor/front/config.php');
         exit();
      }
      
      // If user has 2FA but hasn't verified in this session
      if (!isset($_SESSION['plugin_twofactor_verified'])) {
         Html::redirect($CFG_GLPI['root_doc'] . '/plugins/twofactor/front/verify.php');
         exit();
      }
      
   } catch (Exception $e) {
      Toolbox::logError('2FA Check Error: ' . $e->getMessage());
      return true;
   }
   
   return true;
}

function plugin_twofactor_user_creation($user) {
   global $DB;
   
   try {
      // Generate and store 2FA secret for new user
      require_once(GLPI_ROOT . '/plugins/twofactor/lib/otphp/OTP.php');
      $totp = \OTPHP\TOTP::create();
      $secret = $totp->getSecret();
      
      $query = "INSERT INTO glpi_plugin_twofactor_secrets 
                (users_id, secret, is_active, date_creation) 
                VALUES 
                ({$user->fields['id']}, '$secret', 1, NOW())";
      $DB->query($query);
   } catch (Exception $e) {
      Toolbox::logError('2FA User Creation Error: ' . $e->getMessage());
   }
}