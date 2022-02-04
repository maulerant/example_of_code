<?php

namespace App\Services\Attachments\Uploaders;

use App\DTO\Services\Attachments\AttachmentUploadResult;
use App\DTO\Services\Attachments\UploadData;
use App\Exceptions\ExceptionWithMsg;
use App\Helpers\ProductsWebcam;
use App\Services\Attachments\Providers\OrderAttachmentInterface;

class WebcamUploader implements AttachmentUploaderInterface
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
        if (empty($orderId)) {
            throw new ExceptionWithMsg(l('Заказ не найден'));
        }
        $webcam = new ProductsWebcam();

        $result = $webcam->upload_image($orderId, $this->provider->getAttachmentsPathPrefix(), $uploadData);

        if ($result->hasError()) {
            throw new ExceptionWithMsg(!empty($result->error) ? $result->error : l('Произошла ошибка при сохранении'));
        }
        $attachment = $this->provider->addAttachment($orderId, $result, md5($uploadData->base64dataUrl));
        $result->comment = $this->provider->getAttachmentAsComment($attachment->id, $orderId);
        $result->attachment_id = $attachment->id;
        return $result;
    }
}