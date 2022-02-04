<?php

namespace App\Services\Attachments;

use App\DTO\Services\Attachments\AttachmentUploadResult;
use App\DTO\Services\Attachments\UploadData;
use App\Exceptions\ExceptionWithMsg;
use App\Helpers\Attachment\AttachmentFactory;
use App\Helpers\ProductsWebcam;
use App\Services\Attachments\Providers\OrderAttachmentInterface;
use App\Services\Attachments\Uploaders\AttachmentUploader;
use App\Services\Attachments\Uploaders\AttachmentUploaderInterface;
use App\Services\Attachments\Uploaders\PhotoUploader;
use App\Services\Attachments\Uploaders\WebcamUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class AttachmentsUpload
{
    const ATTACHMENT = 0;
    const WEBCAM = 1;
    const PHOTO = 2;

    /**
     * @var OrderAttachmentInterface
     */
    private $provider;

    /**
     * @param OrderAttachmentInterface $provider
     */
    public function __construct(OrderAttachmentInterface $provider)
    {
        $this->provider = $provider;
    }

    public function as(int $type): AttachmentUploaderInterface
    {
        switch($type) {
            case self::WEBCAM:
                return new WebcamUploader($this->provider);
            case self::PHOTO:
                return new PhotoUploader($this->provider);
            default:
                return new AttachmentUploader($this->provider);
        }
    }

    /**
     * @param UploadData $uploadData
     * @param int $orderId
     * @return AttachmentUploadResult
     * @throws ExceptionWithMsg
     */
    public function photoUpload(UploadData $uploadData, int $orderId): AttachmentUploadResult
    {
    }
}