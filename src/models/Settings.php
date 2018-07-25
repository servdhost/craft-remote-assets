<?php
/**
 * Craft Remote Assets plugin for Craft CMS 3.x
 *
 * Move CP assets to an external filesystem such as S3
 *
 * @link      https://twitter.com/servdhosting
 * @copyright Copyright (c) 2018 Matt Gray
 */

namespace servd\craftremoteassets\models;

use craft\base\Model;

class Settings extends Model
{
    public $s3Config = [
        'region' => 'eu-west-1',
        'bucket' => '',
        'root' => '',
        'key' => '',
        'secret' => ''
    ];
    public $gsConfig = [
      'bucket' => '',
      'root' => '',
      'projectId' => '',
      'keyFilePath' => ''
    ];
    public $use = 's3Config';
    public $preventUninstall = false;
    public $preventDisable = false;

    public function rules()
    {
        return [
            [['use', 's3Config', 'gcConfig'], 'required'],
        ];
    }
}
