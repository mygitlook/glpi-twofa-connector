<?php
function plugin_twofactor_install() {
   global $DB;

   try {
      if (!$DB->tableExists("glpi_plugin_twofactor_secrets")) {
         $query = "CREATE TABLE `glpi_plugin_twofactor_secrets` (
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `users_id` int(11) UNSIGNED NOT NULL,
            `secret` varchar(255) NOT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT '1',
            `date_creation` TIMESTAMP NULL DEFAULT NULL,
            `date_mod` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `users_id` (`users_id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
         
         $DB->query($query) or die("Error creating glpi_plugin_twofactor_secrets " . $DB->error());
         
         // Get all existing users
         $users_query = "SELECT id FROM glpi_users WHERE is_active = 1 AND is_deleted = 0";
         $users_result = $DB->query($users_query);
         
         // Generate and store 2FA secrets for all existing users
         if (!is_dir(GLPI_ROOT . '/plugins/twofactor/lib/otphp')) {
            mkdir(GLPI_ROOT . '/plugins/twofactor/lib/otphp/lib', 0755, true);
         }
         
         // Download OTPHP library if not exists
         if (!file_exists(GLPI_ROOT . '/plugins/twofactor/lib/otphp/lib/otphp.php')) {
            $otphp_url = 'https://raw.githubusercontent.com/Spomky-Labs/otphp/master/lib/otphp.php';
            $otphp_content = file_get_contents($otphp_url);
            file_put_contents(GLPI_ROOT . '/plugins/twofactor/lib/otphp/lib/otphp.php', $otphp_content);
         }
         
         require_once(GLPI_ROOT . '/plugins/twofactor/lib/otphp/lib/otphp.php');
         
         while ($user = $DB->fetch_assoc($users_result)) {
            $totp = \OTPHP\TOTP::create();
            $secret = $totp->getSecret();
            
            $insert_query = "INSERT INTO glpi_plugin_twofactor_secrets 
                           (users_id, secret, is_active, date_creation) 
                           VALUES 
                           ({$user['id']}, '$secret', 1, NOW())";
            $DB->query($insert_query);
         }
      }
      return true;
   } catch (Exception $e) {
      Toolbox::logError('2FA Installation Error: ' . $e->getMessage());
      return false;
   }
}

function plugin_twofactor_uninstall() {
   global $DB;
   
   try {
      $tables = array(
         'glpi_plugin_twofactor_secrets'
      );

      foreach($tables as $table) {
         $DB->query("DROP TABLE IF EXISTS `$table`");
      }
      
      // Clear any 2FA-related session variables
      if (isset($_SESSION['plugin_twofactor_verified'])) {
         unset($_SESSION['plugin_twofactor_verified']);
      }
      
      return true;
   } catch (Exception $e) {
      Toolbox::logError('2FA Uninstallation Error: ' . $e->getMessage());
      return false;
   }
}