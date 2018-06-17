# Craft Remote Assets plugin for Craft CMS 3.x

Move CP assets to an external filesystem such as S3

![Screenshot](resources/img/plugin-logo.png)

## Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require servd/craft-remote-assets

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Craft Remote Assets.

## Craft Remote Assets Overview

In a load balanced environment with multiple PHP servers you can't serve Yii bundles or thumbnails
from the local file system. This plugin publishes CP bundles and asset thumbnails to S3 instead.
Tested in a multi-PHP, single nginx K8s cluster with no volume mounts.

## Configuring Craft Remote Assets

Create a file at `config/craft-remote-assets.php` which looks like this:

```
<?php

return [
    's3Config' => [
        'region' => 'eu-west-1',
        'bucket' => 'yourbucketname',
        'root' => 'defaultKeyPrepend',
        'key' => 's3APIKey',
        'secret' => 's3APISecret'
    ];
];
```

Your S3 API key will need to be linked to an IAM user with bucket read and write permissions.

## Using Craft Remote Assets

Install it. You're done.

Brought to you by [Servd](https://twitter.com/servdhost)
