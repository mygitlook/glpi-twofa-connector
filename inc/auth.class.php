<?php
class PluginTwofactorAuth extends CommonDBTM {
   
   static function checkAuth($user) {
      global $DB, $CFG_GLPI;
      
      if (!isset($user['id'])) {
         return true;
      }
      
      $userId = $user['id'];
      
      // Check if current page is already a 2FA page
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
}