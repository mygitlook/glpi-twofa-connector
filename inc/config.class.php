<?php
class PluginTwofactorConfig extends CommonDBTM {
   static function getTypeName($nb = 0) {
      return __('Two Factor Authentication', 'twofactor');
   }

   function showConfigForm() {
      global $CFG_GLPI;
      
      echo "<form name='form' action='" . $CFG_GLPI['root_doc'] . "/plugins/twofactor/front/config.form.php' method='post'>";
      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      
      echo "<tr><th colspan='2'>" . __('Two Factor Authentication Settings', 'twofactor') . "</th></tr>";
      
      // Add your configuration options here
      
      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='2' class='center'>";
      echo "<input type='submit' name='update' class='submit' value='" . __('Save') . "'>";
      echo "</td></tr>";
      
      echo "</table>";
      echo "</div>";
      Html::closeForm();
   }
}