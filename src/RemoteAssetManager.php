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
use Craft;

class RemoteAssetManager extends AssetManager
{

    private $published = [];
    private $existingFiles = [];
    public $revision = 'norev';
    public $store;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        if(CraftRemoteAssets::getInstance()->getSettings()->use == 's3Config') {
            $this->store = new S3RemoteAssetStore();
        } else {
            $this->store = new GoogleCloudRemoteAssetStore();
        }
        $this->fillExistingFiles();
    }

    public function fillExistingFiles()
    {
        $existing = $this->store->getExistingFiles($this->currentRevision());
        foreach ($existing as $key) {
            $parts = explode('/', $key);
            $hash = implode('/', array_slice($parts, 0, 2));
            if (!in_array($hash, $this->existingFiles)) {
                $this->existingFiles[] = $hash;
            }
            if (!in_array($key, $this->existingFiles)) {
                $this->existingFiles[] = $key;
            }
        }
    }

    public function behaviors()
    {
        return ['RevisionableResource' => RevisionableResourceBehavior::class];
    }

    public function currentRevision()
    {
        if ($this->revision == 'norev') {
            $this->revision = $this->getRevision();
            return $this->revision;
        }
        return $this->revision;
    }

    /**
     * @param $path
     *
     * @return mixed|string
     */
    protected function hash($path)
    {
        return $this->currentRevision() . DIRECTORY_SEPARATOR . sprintf('%x', crc32($path));
    }

    public function publish($path, $hashByName = false, $level = -1, $forceCopy = null, $assetsVersion = null)
    {

        $path = Yii::getAlias($path);
        $src = realpath($path);
        if (isset($this->published[$src])) {
            return $this->published[$src];
        } elseif ($src !== false) {
            if (is_file($src)) {
                $contentType = FileHelper::getMimeTypeByExtension($src);
                $filename = basename($src);
                $directory = $this->hash(pathinfo($src, PATHINFO_DIRNAME));
                $destFile = $directory . "/" . $filename;

                if ($forceCopy !== true && in_array($destFile, $this->existingFiles)) {
                    $this->published[$src] = [$destFile, $this->store->getURLForKey($destFile)];
                    return $this->published[$src];
                }

                if ($this->store->putFile($destFile, $src, $contentType)) {
                    Craft::info(Craft::t('craft-remote-assets', 'Sent file to remote store: '.$src, []), __METHOD__);
                } else {
                    throw new CException("Unable to send asset to remote store!");
                }

                $this->published[$src] = [$destFile, $this->store->getURLForKey($destFile)];
                return $this->published[$src];
            } elseif (is_dir($src)) {
                $directory = $this->hash($src);

                if ($forceCopy !== true && in_array($directory, $this->existingFiles)) {
                    $this->published[$src] = [$directory, $this->store->getURLForKey($directory)];
                    return $this->published[$src];
                }

                $files = FileHelper::findFiles(
                    $src,
                    [
                        'level' => -1
                    ]
                );
                foreach ($files as $file) {
                    $file = realpath($file);
                    $destFile = $directory . "/" . str_replace($src . "/", "", $file);
                    $contentType = FileHelper::getMimeTypeByExtension($destFile);
                    if ($this->store->putFile($destFile, $file, $contentType)) {
                        Craft::info(
                            Craft::t('craft-remote-assets', 'Sent file to remote store: {file}', ['file' => $destFile]),
                            __METHOD__
                        );
                    } else {
                        throw new CException("Unable to send assets to remote store!");
                    }
                }

                $this->published[$src] = [$directory, $this->store->getURLForKey($directory)];
                return $this->published[$src];
            }
        }
        Craft::info(
            Craft::t('craft-remote-assets', 'A requested asset is missing locally: {path}', ['path' => $path]),
            __METHOD__
        );
    }

    public function getPublishedUrl($sourcePath, bool $publish = false, $filePath = null)
    {
        $fullPath = $sourcePath;
        if ($filePath !== null) {
            $fullPath = $sourcePath . "/" . $filePath;
        }

        if ($publish === true) {
            $resp = $this->publish($fullPath);
            if (is_array($resp)) {
                return $resp[1];
            }
        }

        $src = Yii::getAlias($sourcePath);
        //We haven't just published it so let's see if it's already published
        if (isset($this->published[$src])) {
            $base = $this->published[$src][1];
            if ($filePath !== null) {
                return $base . "/" . $filePath;
            }
            return $base;
        }

        //It isn't published, last chance is to try to calculate the storage key
        //and see if it's already in storage
        if (is_string($src) && ($src = realpath($src)) !== false) {
            $destFile = $this->hash(pathinfo($src, PATHINFO_DIRNAME));
        }
        if ($filePath !== null) {
            $destFile .= '/'.$filePath;
        }

        if (in_array($destFile, $this->existingFiles)) {
            $this->published[$src] = [$directory, $this->store->getURLForKey($directory)];
            $base = $this->published[$src];
            if ($filePath !== null) {
                return $base . "/" . $filePath;
            }
            return $base;
        }

        //Epic fail
        return '';
    }

    /**
     * @param string $path
     *
     * @return mixed
     */
    protected function extractAssetId(string $path)
    {
        $pos   = (is_file($path)) ? 2 : 1;
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $id    = $parts[count($parts) - $pos];

        return $id;
    }
}
