<?php
include ("../../../inc/includes.php");

Session::checkLoginUser();

if (isset($_POST['code'])) {
   $userId = $_SESSION['glpiID'];
   $code = $_POST['code'];
   
   $query = "SELECT secret FROM glpi_plugin_twofactor_secrets 
             WHERE users_id = $userId AND is_active = 1";
   $result = $DB->query($query);
   
   if ($row = $DB->fetch_assoc($result)) {
      $secret = $row['secret'];
      
      // Verify TOTP code
      require_once(GLPI_ROOT . '/plugins/twofactor/lib/otphp/lib/otphp.php');
      $totp = new \OTPHP\TOTP($secret);
      
      if ($totp->verify($code)) {
         $_SESSION['plugin_twofactor_verified'] = true;
         Html::redirect($CFG_GLPI['root_doc']);
      }
   }
   
   Session::addMessageAfterRedirect(__('Invalid verification code', 'twofactor'), false, ERROR);
}

Html::header("Two Factor Authentication", $_SERVER['PHP_SELF']);
?>

<div class="center">
   <form method="post">
      <table class="tab_cadre_fixe">
         <tr>
            <th colspan="2"><?php echo __('Enter verification code', 'twofactor'); ?></th>
         </tr>
         <tr>
            <td>
               <input type="text" name="code" required>
            </td>
         </tr>
         <tr>
            <td class="center">
               <input type="submit" value="<?php echo __('Verify', 'twofactor'); ?>" class="submit">
            </td>
         </tr>
      </table>
   </form>
</div>

<?php
Html::footer();