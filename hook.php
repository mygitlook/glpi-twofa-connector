<?php
function plugin_twofactor_install() {
   global $DB;

   if (!$DB->tableExists("glpi_plugin_twofactor_secrets")) {
      $query = "CREATE TABLE `glpi_plugin_twofactor_secrets` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `users_id` int(11) NOT NULL,
         `secret` varchar(255) NOT NULL,
         `is_active` tinyint(1) NOT NULL DEFAULT '0',
         `date_creation` datetime DEFAULT NULL,
         `date_mod` datetime DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `users_id` (`users_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      
      $DB->query($query) or die("Error creating glpi_plugin_twofactor_secrets " . $DB->error());
   }
   return true;
}

function plugin_twofactor_uninstall() {
   global $DB;
   
   $tables = array(
      'glpi_plugin_twofactor_secrets'
   );

   foreach($tables as $table) {
      $DB->query("DROP TABLE IF EXISTS `$table`");
   }
   return true;
}