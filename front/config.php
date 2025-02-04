<?php
include ("../../../inc/includes.php");

Session::checkLoginUser();

$plugin = new Plugin();
if (!$plugin->isActivated("twofactor")) {
   Html::displayNotFoundError();
}

Html::header("Two Factor Authentication", $_SERVER['PHP_SELF'], "config", "plugins");

// Create config object
$config = new PluginTwofactorConfig();

// Handle form submission if POST
if (isset($_POST['verification_code'])) {
    $userId = $_SESSION['glpiID'];
    $code = $_POST['verification_code'];
    
    // Get user's secret
    $query = "SELECT secret FROM glpi_plugin_twofactor_secrets 
              WHERE users_id = $userId AND is_active = 1";
    $result = $DB->query($query);
    
    if ($row = $DB->fetch_assoc($result)) {
        $secret = $row['secret'];
        
        // Verify TOTP code
        require_once(GLPI_ROOT . '/plugins/twofactor/lib/otphp/OTP.php');
        require_once(GLPI_ROOT . '/plugins/twofactor/lib/otphp/TOTP.php');
        $totp = new \OTPHP\TOTP($secret);
        
        if ($totp->verify($code)) {
            // Update user's 2FA status
            $query = "UPDATE glpi_plugin_twofactor_secrets 
                     SET is_active = 1 
                     WHERE users_id = $userId";
            $DB->query($query);
            
            $_SESSION['plugin_twofactor_verified'] = true;
            Html::redirect($CFG_GLPI['root_doc']);
        } else {
            Session::addMessageAfterRedirect(
                __('Invalid verification code', 'twofactor'),
                false,
                ERROR
            );
        }
    }
}

// Show the config form
$config->showConfigForm();

Html::footer();
?>