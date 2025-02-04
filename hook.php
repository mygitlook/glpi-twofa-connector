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
         
         // Create OTPHP library directories with proper permissions
         $lib_paths = [
            GLPI_ROOT . '/plugins/twofactor/lib',
            GLPI_ROOT . '/plugins/twofactor/lib/otphp',
            GLPI_ROOT . '/plugins/twofactor/lib/otphp/Trait'
         ];
         
         foreach ($lib_paths as $path) {
            if (!is_dir($path)) {
               if (!mkdir($path, 0755, true)) {
                  throw new Exception("Failed to create directory: $path");
               }
               // Ensure Apache can read the directory
               chmod($path, 0755);
            }
         }
         
         // Define OTPHP files to download with proper versions
         $otphp_files = [
            'OTP.php' => 'https://raw.githubusercontent.com/Spomky-Labs/otphp/v10.0.3/src/OTP.php',
            'TOTP.php' => 'https://raw.githubusercontent.com/Spomky-Labs/otphp/v10.0.3/src/TOTP.php',
            'Trait/Base32.php' => 'https://raw.githubusercontent.com/Spomky-Labs/otphp/v10.0.3/src/Trait/Base32.php',
            'Trait/ParameterTrait.php' => 'https://raw.githubusercontent.com/Spomky-Labs/otphp/v10.0.3/src/Trait/ParameterTrait.php'
         ];
         
         // Download each file and set proper permissions
         foreach ($otphp_files as $file => $url) {
            $file_path = GLPI_ROOT . '/plugins/twofactor/lib/otphp/' . $file;
            if (!file_exists($file_path)) {
               $content = file_get_contents($url);
               if ($content === false) {
                  throw new Exception("Failed to download OTPHP file: $file");
               }
               if (!file_put_contents($file_path, $content)) {
                  throw new Exception("Failed to write OTPHP file: $file");
               }
               // Ensure Apache can read the file
               chmod($file_path, 0644);
            }
         }
         
         // Verify all required files exist before proceeding
         foreach ($otphp_files as $file => $url) {
            $file_path = GLPI_ROOT . '/plugins/twofactor/lib/otphp/' . $file;
            if (!file_exists($file_path)) {
                throw new Exception("Required OTPHP file not found: $file");
            }
         }
         
         // Include OTPHP library
         require_once(GLPI_ROOT . '/plugins/twofactor/lib/otphp/OTP.php');
         require_once(GLPI_ROOT . '/plugins/twofactor/lib/otphp/TOTP.php');
         
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