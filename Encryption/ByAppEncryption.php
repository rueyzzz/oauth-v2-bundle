<?php
namespace Keboola\OAuthV2Bundle\Encryption;

use Keboola\Syrup\Client,
    Keboola\Syrup\ClientException;
use Keboola\Syrup\Exception\ApplicationException;
use Keboola\StorageApi\Client as StorageApi;

class ByAppEncryption
{
    /**
     * @param string $secret String to encrypt
     * @param string $componentId
     * @param string $token SAPI token
     * @return string Encrypted $secret by application $componentId
     */
    public static function encrypt($secret, $componentId, $token = null, $toConfig = false, $sapiUrl)
    {
        if(empty($sapiUrl)) {
            throw new ApplicationException("StorageApi url is empty and must be set");
        }
        $storageApiClient = new StorageApi([
            "token" => $token,
            "userAgent" => 'oauth-v2',
            "url" => $sapiUrl
        ]);
        $components = $storageApiClient->indexAction()["components"];
        $syrupApiUrl = null;
        foreach ($components as $component) {
            if ($component["id"] == 'queue') {
                // strip the component uri to syrup api uri
                // eg https://syrup.keboola.com/docker/docker-demo => https://syrup.keboola.com
                $syrupApiUrl = substr($component["uri"], 0, strpos($component["uri"], "/", 8));
                break;
            }
        }
        if(empty($syrupApiUrl)) {
            throw new ApplicationException("SyrupApi url is empty");
        }

        $config = [
            'super' => 'docker',
            "url" => $syrupApiUrl
        ];
        if (!is_null($token)) {
            $config['token'] = $token;
        }

        $client = Client::factory($config);

        try {
            return $client->encryptString($componentId, $secret, $toConfig ? ["path" => "configs"] : []);
        } catch(ClientException $e) {
            throw new UserException("Component based encryption of the app secret failed: " . $e->getMessage());
        }
    }
}
