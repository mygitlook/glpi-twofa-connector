<?php
class PluginTwofactorConfig extends CommonDBTM {
   static function getTypeName($nb = 0) {
      return __('Two Factor Authentication', 'twofactor');
   }

   function showConfigForm() {
      global $CFG_GLPI;
      
      $userId = $_SESSION['glpiID'];
      
      // Check if 2FA is already set up
      $query = "SELECT * FROM glpi_plugin_twofactor_secrets 
                WHERE users_id = $userId";
      $result = $DB->query($query);
      
      if ($DB->numrows($result) == 0) {
         // Generate new secret
         require_once(GLPI_ROOT . '/plugins/twofactor/lib/otphp/lib/otphp.php');
         $totp = \OTPHP\TOTP::create();
         $secret = $totp->getSecret();
         
         // Save secret
         $query = "INSERT INTO glpi_plugin_twofactor_secrets 
                  (users_id, secret, date_creation) 
                  VALUES ($userId, '$secret', NOW())";
         $DB->query($query);
      } else {
         $row = $DB->fetch_assoc($result);
         $secret = $row['secret'];
         $isActive = $row['is_active'];
      }
      
      echo "<form name='form' action='" . $CFG_GLPI['root_doc'] . "/plugins/twofactor/front/config.form.php' method='post'>";
      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      
      echo "<tr><th colspan='2'>" . __('Two Factor Authentication Settings', 'twofactor') . "</th></tr>";
      
      // Show QR code
      $totp = new \OTPHP\TOTP($secret);
      $totp->setLabel('GLPI 2FA');
      $qrCodeUrl = $totp->getQrCodeUri();
      
      echo "<tr><td colspan='2' class='center'>";
      echo "<img src='" . $qrCodeUrl . "' alt='QR Code'>";
      echo "<br>";
      echo __('Scan this QR code with your authenticator app', 'twofactor');
      echo "</td></tr>";
      
      // Verification code input
      echo "<tr><td>";
      echo __('Enter verification code to enable 2FA', 'twofactor');
      echo "</td><td>";
      echo "<input type='text' name='verification_code' required>";
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='2' class='center'>";
      echo "<input type='submit' name='update' class='submit' value='" . __('Enable 2FA', 'twofactor') . "'>";
      echo "</td></tr>";
      
      echo "</table>";
      echo "</div>";
      Html::closeForm();
   }
}