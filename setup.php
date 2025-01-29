<?php
function plugin_init_twofactor() {
   global $PLUGIN_HOOKS;
   
   $PLUGIN_HOOKS['csrf_compliant']['twofactor'] = true;
   $PLUGIN_HOOKS['post_init']['twofactor'] = 'plugin_twofactor_postinit';
   
   // Add menu entry
   $PLUGIN_HOOKS['menu_toadd']['twofactor'] = array(
      'config' => 'PluginTwofactorConfig'
   );
}

function plugin_version_twofactor() {
   return array(
      'name' => 'Two Factor Authentication',
      'version' => '1.0.0',
      'author' => 'Your Name',
      'license' => 'GPLv2+',
      'homepage' => 'https://github.com/yourusername/glpi-twofactor',
      'requirements' => array(
         'glpi' => array(
            'min' => '9.5'
         )
      )
   );
}