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

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\services\Assets;
use craft\events\GetAssetThumbUrlEvent;
use craft\events\AssetThumbEvent;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\Image;


use yii\base\Event;

/**
 * Class CraftRemoteAssets
 *
 * @author    Matt Gray
 * @package   CraftRemoteAssets
 * @since     0.1.0
 *
 */
class CraftRemoteAssets extends Plugin
{

    public static $plugin;
    public $schemaVersion = '0.1.5';

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Craft::$app->set('assetManager', function () {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $config        = [
                'class'           => RemoteAssetManager::class,
                'basePath'        => $generalConfig->resourceBasePath,
                'baseUrl'         => $generalConfig->resourceBaseUrl,
                'fileMode'        => $generalConfig->defaultFileMode,
                'dirMode'         => $generalConfig->defaultDirMode,
                'appendTimestamp' => false,
            ];
            return Craft::createObject($config);
        });

        $settings = $this->getSettings();
        if ($settings->preventDisable) {
            Event::on(
                Plugins::class,
                Plugins::EVENT_BEFORE_DISABLE_PLUGIN,
                function (PluginEvent $event) {
                    if ($event->plugin === $this) {
                        echo('Plugin can\'t be disabled');
                        exit;
                    }
                }
            );
        }

        Craft::info(
            Craft::t(
                'craft-remote-assets',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    public function beforeUninstall(): bool
    {
        $settings = $this->getSettings();
        if ($settings->preventUninstall) {
            return false;
        }
        return parent::beforeUninstall();
    }

    protected function createSettingsModel()
    {
        return new \servd\craftremoteassets\models\Settings();
    }
}
