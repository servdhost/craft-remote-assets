<?php
/**
 * Craft Remote Assets plugin for Craft CMS 3.x
 *
 * Move CP assets to an external filesystem such as S3
 *
 * @link      https://twitter.com/servdhosting
 * @copyright Copyright (c) 2018 Matt Gray
 */

namespace servd\craftremoteassets;

use craft\web\AssetManager;
use Exception;
use Yii;
use yii\helpers\FileHelper;
use Google\Cloud\Storage\StorageClient;
use Craft;

class GoogleCloudRemoteAssetStore
{

    public $bucket;
    public $root;
    public $gsClient;

    public function __construct()
    {
    }

    public function putFile($key, $local, $contentType)
    {
        $config = CraftRemoteAssets::getInstance()->getSettings()->gsConfig;
        try {
            $options = [
                'resumable' => false,
                'name' => $config['root'] . '/' . $key,
                'predefinedAcl' => 'publicRead'
            ];
            $this->getClient()->bucket($config['bucket'])->upload(
                fopen($local, 'r'),
                $options
            );
        } catch (CException $e) {
            Craft::info($e->getMessage());
            return false;
        }
        return true;
    }

    public function getURLForKey($key)
    {
        $config = CraftRemoteAssets::getInstance()->getSettings()->gsConfig;
        return 'https://storage.googleapis.com/'.$config['bucket'].'/'.$config['root'].'/'.$key;
    }

    public function getExistingFiles($withPrefix = '')
    {
        $config = CraftRemoteAssets::getInstance()->getSettings()->gsConfig;

        $objects = $this->getClient()->bucket($config['bucket'])->objects([
            'prefix' => $config['root'] . '/' . (strlen($withPrefix) > 0 ? $withPrefix . '/' : '' ),
            'fields' => 'items/name,nextPageToken'
        ]);

        $allItems = [];
        foreach ($objects as $object) {
            $fullKey = str_ireplace($config['root'] . '/', '', $object->name());
            $allItems[] = $fullKey;
        }

        return $allItems;
    }

    private function getClient()
    {
        $config = CraftRemoteAssets::getInstance()->getSettings()->gsConfig;
        if ($this->gsClient === null) {
            $this->gsClient = new StorageClient([
                'projectId' => $config['projectId'],
                'keyFilePath' => $config['keyFilePath'],
            ]);
        }
        return $this->gsClient;
    }
}
