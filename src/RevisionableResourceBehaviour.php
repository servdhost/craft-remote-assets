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

use yii\base\Behavior;
use Craft;

class RevisionableResourceBehavior extends Behavior
{
    public $revision = null;

    public $revFile = null;

    public $modifiedFiles = [];

    public function getRevision()
    {
        if (!$this->revFile) {
            $this->revFile = Craft::$app->path->getConfigPath().'/revision';
        }

        if ($this->revision) {
            return $this->revision;
        }

        if (file_exists($this->revFile)) {
            $this->revision = trim(file_get_contents($this->revFile));

            return $this->revision;
        }

        return 'norev';
    }

    /**
     * @return string
     */
    protected function getRevisionResourcePath()
    {
        return $this->owner->basePath . DIRECTORY_SEPARATOR . $this->getRevision();
    }
}
