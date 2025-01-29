<?php
class PluginTwofactorAuth extends CommonDBTM {
   
   static function checkAuth($user) {
      global $DB;
      
      if (!isset($user['id'])) {
         return true;
      }
      
      $userId = $user['id'];
      
      // Check if user has 2FA enabled
      $query = "SELECT * FROM glpi_plugin_twofactor_secrets 
                WHERE users_id = $userId AND is_active = 1";
      $result = $DB->query($query);
      
      if ($DB->numrows($result) > 0) {
         // User has 2FA enabled, redirect to verification page
         if (!isset($_SESSION['plugin_twofactor_verified'])) {
            Html::redirect($CFG_GLPI['root_doc'] . '/plugins/twofactor/front/verify.php');
            return false;
         }
      }
      
      return true;
   }
}