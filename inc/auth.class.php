<?php
class PluginTwofactorAuth extends CommonDBTM {
   
   static function checkAuth($user) {
      global $DB, $CFG_GLPI;
      
      if (!isset($user['id'])) {
         return true;
      }
      
      $userId = $user['id'];
      
      // Check if user has 2FA secret
      $query = "SELECT * FROM glpi_plugin_twofactor_secrets 
                WHERE users_id = $userId";
      $result = $DB->query($query);
      
      if ($DB->numrows($result) > 0) {
         $row = $DB->fetch_assoc($result);
         
         // If user has 2FA enabled but not verified in this session
         if (!isset($_SESSION['plugin_twofactor_verified'])) {
            // Redirect to verification page
            Html::redirect($CFG_GLPI['root_doc'] . '/plugins/twofactor/front/verify.php');
            return false;
         }
      } else {
         // User doesn't have 2FA set up at all, redirect to setup
         Html::redirect($CFG_GLPI['root_doc'] . '/plugins/twofactor/front/config.form.php');
         return false;
      }
      
      return true;
   }
}