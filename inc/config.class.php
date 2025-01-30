<?php
class PluginTwofactorConfig extends CommonDBTM {
   static function getTypeName($nb = 0) {
      return __('Two Factor Authentication', 'twofactor');
   }

   function showConfigForm() {
      global $DB, $CFG_GLPI;
      
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
      }
      
      // Create TOTP object for QR code
      $totp = new \OTPHP\TOTP($secret);
      $totp->setLabel('GLPI 2FA - ' . $_SESSION['glpiname']);
      $totp->setIssuer('GLPI');
      
      echo "<div class='center'>";
      echo "<form name='form' method='post'>";
      echo "<table class='tab_cadre_fixe'>";
      
      echo "<tr><th colspan='2'>" . __('Two Factor Authentication Setup', 'twofactor') . "</th></tr>";
      
      // Show QR code
      $qrCodeUrl = $totp->getQrCodeUri('https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=[DATA]', '[DATA]');
      echo "<tr><td colspan='2' class='center'>";
      echo "<img src='" . $qrCodeUrl . "' alt='QR Code' style='margin: 20px;'>";
      echo "<br>";
      echo __('Scan this QR code with your authenticator app', 'twofactor');
      echo "</td></tr>";
      
      // Show secret key
      echo "<tr><td colspan='2' class='center'>";
      echo __('Manual entry code: ', 'twofactor') . "<code>" . $secret . "</code>";
      echo "</td></tr>";
      
      // Verification code input
      echo "<tr><td class='center' colspan='2'>";
      echo __('Enter verification code: ', 'twofactor');
      echo "<input type='text' name='verification_code' required style='width: 200px;'>";
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='2' class='center'>";
      echo "<input type='submit' name='update' class='submit' value='" . __('Verify and Enable 2FA', 'twofactor') . "'>";
      echo "</td></tr>";
      
      echo "</table>";
      echo "</form>";
      echo "</div>";
   }
}