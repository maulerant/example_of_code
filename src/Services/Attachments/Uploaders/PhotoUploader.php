<?php

namespace App\Services\Attachments\Uploaders;

use App\DTO\Services\Attachments\AttachmentUploadResult;
use App\DTO\Services\Attachments\UploadData;
use App\Exceptions\ExceptionWithMsg;
use App\Services\Attachments\Providers\OrderAttachmentInterface;
use Illuminate\Support\Arr;

class PhotoUploader implements AttachmentUploaderInterface
{
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

    /**
     * @param UploadData $uploadData
     * @param string|null $orderId
     * @return AttachmentUploadResult
     * @throws ExceptionWithMsg
     */
    public function upload(UploadData $uploadData, string $orderId = null): AttachmentUploadResult
    {
        if($uploadData->file === null) {
            throw new ExceptionWithMsg(l('Файл не задан'));
        }
        $path = $uploadData->file->store($this->provider->getAttachmentsPathPrefix() . '/' . $orderId);
        $new_img_name = explode('/', $path);

        return new AttachmentUploadResult([
            'image_info' => [
                'imgprefix' => $this->provider->getAttachmentsPathPrefix() . '/' . $orderId . '/',
                'imgid' => $this->provider->addOrderImage($orderId, last($new_img_name)),
                'imgname' => Arr::last($new_img_name)
            ]
        ]);
    }
}