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
use Aws\S3\S3Client;
use Craft;

class S3RemoteAssetStore
{

    public $bucket;
    public $root;
    public $s3;

    public function __construct()
    {
    }

    public function putFile($key, $local, $contentType)
    {
        $config = CraftRemoteAssets::getInstance()->getSettings()->s3Config;
        try {
            $this->getS3()->putObject(
                [
                    'Bucket' => $config['bucket'],
                    'Key' => $config['root'] . '/' . $key,
                    'SourceFile' => $local,
                    'ContentType' => $contentType,
                    'ACL' => 'public-read' //TODO: Check for private asset uploads
                ]
            );
        } catch (CException $e) {
            Craft::info($e->getMessage());
            return false;
        }
        return true;
    }

    public function getURLForKey($key)
    {
        $config = CraftRemoteAssets::getInstance()->getSettings()->s3Config;
        return 'https://s3-' .
            $config['region'] .
            '.amazonaws.com/' .
            $config['bucket'] . '/' .
            $config['root'] . '/' .
            $key;
    }

    public function getExistingFiles($withPrefix = '')
    {
        $config = CraftRemoteAssets::getInstance()->getSettings()->s3Config;
        $s3 = $this->getS3();
        $iterator = $s3->getIterator('ListObjects', [
            'Bucket' => $config['bucket'],
            'Prefix' => $config['root'] . '/' . (strlen($withPrefix) > 0 ? $withPrefix . '/' : '' ),
        ]);
        $allItems = [];
        foreach ($iterator as $object) {
            $fullKey = str_ireplace($config['root'] . '/', '', $object['Key']);
            $allItems[] = $fullKey;
        }
        return $allItems;
    }

    private function getS3()
    {
        $config = CraftRemoteAssets::getInstance()->getSettings()->s3Config;
        if ($this->s3 === null) {
            $this->s3 = new S3Client([
                'version' => '2006-03-01',
                'region' => $config['region'],
                'credentials' => [
                    'key' => $config['key'],
                    'secret' => $config['secret'],
                ],
            ]);
        }
        return $this->s3;
    }
}
