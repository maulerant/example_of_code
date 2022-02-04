<?php

namespace App\Services\Attachments\Uploaders;

use App\DTO\Services\Attachments\AttachmentUploadResult;
use App\DTO\Services\Attachments\UploadData;

interface AttachmentUploaderInterface
{
    public function upload(UploadData $uploadData, string $orderId = null): AttachmentUploadResult;
}