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
        'bucket' => 'servd-assets',
        'root' => 'noapp',
        'key' => '',
        'secret' => ''
    ];
    public $preventUninstall = false;
    public $preventDisable = false;

    public function rules()
    {
        return [
            [['s3Config'], 'required'],
        ];
    }
}
