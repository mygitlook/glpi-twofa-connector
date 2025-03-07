
<?php
function plugin_twofactor_install() {
   global $DB;

   try {
      // Create database table if it doesn't exist
      if (!$DB->tableExists("glpi_plugin_twofactor_secrets")) {
         $query = "CREATE TABLE `glpi_plugin_twofactor_secrets` (
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `users_id` int(11) UNSIGNED NOT NULL,
            `secret` varchar(255) NOT NULL,
            `is_active` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
            `date_creation` TIMESTAMP NULL DEFAULT NULL,
            `date_mod` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `users_id` (`users_id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
         
         $DB->query($query) or die("Error creating glpi_plugin_twofactor_secrets " . $DB->error());
      }
      
      // Create OTPHP library files
      $base_dir = GLPI_ROOT . '/plugins/twofactor/lib';
      $otphp_dir = $base_dir . '/otphp';
      $trait_dir = $otphp_dir . '/Trait';
      
      // Create directories with proper permissions
      if (!file_exists($base_dir)) {
         if (!mkdir($base_dir, 0755, true)) {
            throw new Exception("Failed to create base directory: $base_dir");
         }
      }
      
      if (!file_exists($otphp_dir)) {
         if (!mkdir($otphp_dir, 0755, true)) {
            throw new Exception("Failed to create OTPHP directory: $otphp_dir");
         }
      }
      
      if (!file_exists($trait_dir)) {
         if (!mkdir($trait_dir, 0755, true)) {
            throw new Exception("Failed to create Trait directory: $trait_dir");
         }
      }
      
      // Define OTPHP files with their full content
      $otphp_files = [
         'ParameterTrait.php' => [
            'path' => $trait_dir . '/ParameterTrait.php',
            'content' => '<?php
namespace OTPHP\Trait;

trait ParameterTrait {
    private $label;
    private $issuer;
    
    public function setLabel($label) {
        $this->label = $label;
        return $this;
    }
    
    public function getLabel() {
        return $this->label;
    }
    
    public function setIssuer($issuer) {
        $this->issuer = $issuer;
        return $this;
    }
    
    public function getIssuer() {
        return $this->issuer;
    }
}'
         ],
         'OTP.php' => [
            'path' => $otphp_dir . '/OTP.php',
            'content' => '<?php
namespace OTPHP;

use OTPHP\Trait\ParameterTrait;
use OTPHP\Trait\Base32;

class OTP {
    use ParameterTrait;
    use Base32;
    
    protected $secret;
    protected $digest;
    protected $digits;
    
    public function __construct($secret) {
        $this->secret = $secret;
        $this->digest = "sha1";
        $this->digits = 6;
    }
    
    public function getSecret() {
        return $this->secret;
    }
    
    public function getDigest() {
        return $this->digest;
    }
    
    public function getDigits() {
        return $this->digits;
    }
}'
         ],
         'TOTP.php' => [
            'path' => $otphp_dir . '/TOTP.php',
            'content' => '<?php
namespace OTPHP;

class TOTP extends OTP {
    private $period;
    
    public function __construct($secret) {
        parent::__construct($secret);
        $this->period = 30;
    }
    
    public static function create($secret = null) {
        if (null === $secret) {
            $secret = self::generateSecret();
        }
        return new self($secret);
    }
    
    private static function generateSecret($length = 32) {
        $secret = random_bytes($length);
        return base64_encode($secret);
    }
    
    public function verify($code, $timestamp = null) {
        if (null === $timestamp) {
            $timestamp = time();
        }
        return $this->generateTotp($timestamp) == $code;
    }
    
    private function generateTotp($timestamp) {
        $time = floor($timestamp / $this->period);
        $input = pack("J", $time);
        $hash = hash_hmac($this->digest, $input, base64_decode($this->secret), true);
        $offset = ord($hash[strlen($hash) - 1]) & 0xF;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24) |
                 ((ord($hash[$offset + 1]) & 0xFF) << 16) |
                 ((ord($hash[$offset + 2]) & 0xFF) << 8) |
                 (ord($hash[$offset + 3]) & 0xFF);
        return str_pad($binary % pow(10, $this->digits), $this->digits, "0", STR_PAD_LEFT);
    }
    
    public function getQrCodeUri($qrCodeUrl, $placeholder) {
        $label = rawurlencode($this->getLabel());
        $issuer = rawurlencode($this->getIssuer());
        $secret = $this->getSecret();
        $uri = "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}";
        return str_replace($placeholder, rawurlencode($uri), $qrCodeUrl);
    }
}'
         ],
         'Base32.php' => [
            'path' => $trait_dir . '/Base32.php',
            'content' => '<?php
namespace OTPHP\Trait;

trait Base32 {
    private static function base32Decode($input) {
        return base64_decode($input);
    }
    
    private static function base32Encode($input) {
        return base64_encode($input);
    }
}'
         ]
      ];
      
      // Write all OTPHP files with proper permissions
      foreach ($otphp_files as $file => $data) {
         if (file_put_contents($data['path'], $data['content']) === false) {
            throw new Exception("Failed to write file: " . $data['path']);
         }
         chmod($data['path'], 0644);
      }
      
      // Create secrets for existing users
      if (!$DB->tableExists("glpi_plugin_twofactor_secrets")) {
         $users_query = "SELECT id FROM glpi_users WHERE is_active = 1 AND is_deleted = 0";
         $users_result = $DB->query($users_query);
         
         while ($user = $DB->fetch_assoc($users_result)) {
            $totp = \OTPHP\TOTP::create();
            $secret = $totp->getSecret();
            
            $insert_query = "INSERT INTO glpi_plugin_twofactor_secrets 
                           (users_id, secret, is_active, date_creation) 
                           VALUES 
                           ({$user['id']}, '$secret', 1, NOW())";
            $DB->query($insert_query);
         }
      }
      
      return true;
   } catch (Exception $e) {
      Toolbox::logError('2FA Installation Error: ' . $e->getMessage());
      return false;
   }
}

function plugin_twofactor_uninstall() {
   global $DB;
   
   try {
      // Drop the plugin table
      $DB->query("DROP TABLE IF EXISTS `glpi_plugin_twofactor_secrets`");
      
      // Remove the lib directory and all its contents
      $lib_dir = GLPI_ROOT . '/plugins/twofactor/lib';
      if (is_dir($lib_dir)) {
         array_map('unlink', glob("$lib_dir/otphp/Trait/*.*"));
         rmdir($lib_dir . '/otphp/Trait');
         array_map('unlink', glob("$lib_dir/otphp/*.*"));
         rmdir($lib_dir . '/otphp');
         array_map('unlink', glob("$lib_dir/*.*"));
         rmdir($lib_dir);
      }
      
      // Clear any 2FA-related session variables
      if (isset($_SESSION['plugin_twofactor_verified'])) {
         unset($_SESSION['plugin_twofactor_verified']);
      }
      if (isset($_SESSION['plugin_twofactor_needs_setup'])) {
         unset($_SESSION['plugin_twofactor_needs_setup']);
      }
      
      return true;
   } catch (Exception $e) {
      Toolbox::logError('2FA Uninstallation Error: ' . $e->getMessage());
      return false;
   }
}
