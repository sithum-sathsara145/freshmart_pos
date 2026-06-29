<?php

/*
|--------------------------------------------------------------------------
| API credential definitions
|--------------------------------------------------------------------------
|
| Drives the "API keys" tab in Settings. Each group renders as a card; each
| field becomes an input. Fields flagged 'secret' => true are encrypted at
| rest, never sent back to the browser, and shown masked.
|
| To support a new provider in future, just add a group here — no controller
| or view changes are needed. Field keys are the settings.key_name values.
|
*/

return [

    'cloudinary' => [
        'label'       => 'Cloudinary',
        'description' => 'Stores and serves product images.',
        'fields'      => [
            'cloudinary_cloud_name' => ['label' => 'Cloud name', 'secret' => false, 'placeholder' => 'e.g. da3s174op'],
            'cloudinary_api_key'    => ['label' => 'API key',    'secret' => true,  'placeholder' => ''],
            'cloudinary_api_secret' => ['label' => 'API secret', 'secret' => true,  'placeholder' => ''],
            'cloudinary_folder'     => ['label' => 'Upload folder', 'secret' => false, 'placeholder' => 'products'],
        ],
    ],

    // Future providers go here, e.g.:
    // 'sms_gateway' => [
    //     'label'  => 'SMS gateway',
    //     'fields' => [
    //         'sms_api_key' => ['label' => 'API key', 'secret' => true],
    //     ],
    // ],

];
