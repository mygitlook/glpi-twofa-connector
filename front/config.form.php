<?php
include ("../../../inc/includes.php");

$plugin = new Plugin();
if (!$plugin->isActivated("twofactor")) {
   Html::displayNotFoundError();
}

$config = new PluginTwofactorConfig();

Html::header("Two Factor Authentication", $_SERVER['PHP_SELF'], "config", "plugins");
$config->showConfigForm();
Html::footer();