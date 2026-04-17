<?php

require_once __DIR__ . '/cloudinary.php';

use Cloudinary\Api\Upload\UploadApi;

class CloudinaryHelper
{
    public static function upload($tmpPath, $folder)
    {
        $upload = (new UploadApi())->upload(
            $tmpPath,
            [
                'folder' => $folder,
                'resource_type' => 'auto'
            ]
        );

        return [
            'url' => $upload['secure_url'],
            'public_id' => $upload['public_id']
        ];
    }

    public static function delete($publicId)
    {
        if (!$publicId) return;
        (new UploadApi())->destroy($publicId);
    }
}