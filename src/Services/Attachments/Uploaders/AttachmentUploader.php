<?php

namespace App\Services\Attachments\Uploaders;

use App\DTO\Services\Attachments\AttachmentUploadResult;
use App\DTO\Services\Attachments\UploadData;
use App\Exceptions\ExceptionWithMsg;
use App\Helpers\Attachment\AttachmentFactory;
use App\Services\Attachments\Providers\OrderAttachmentInterface;
use Illuminate\Http\UploadedFile;

class AttachmentUploader implements AttachmentUploaderInterface
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

        $answer = $this->_upload($uploadData->file, $orderId);

        if ($answer->state === true && !empty($answer->attachment_id)) {
            $this->provider->appendAttachment($orderId, $answer);
            $answer->comment = $this->provider->getAttachmentAsComment($answer->attachment_id, $orderId);
        }
        return $answer;
    }

    /**
     * @param UploadedFile|null $file
     * @param string $orderId
     * @return AttachmentUploadResult|array
     * @throws ExceptionWithMsg
     */
    protected function _upload(?UploadedFile $file, string $orderId): AttachmentUploadResult
    {
        if($file === null) {
            throw new ExceptionWithMsg(l('Файл не указан'));
        }
// получаем MIME файла
        $fileMIMEType = explode('/', $file->getClientMimeType());

        // загружаем файл на сервер
        $attachment = new AttachmentFactory();
        $fileObj = $attachment->create($fileMIMEType[0]);
        return $fileObj->upload($this->provider->getAttachmentsPathPrefix() . '/' . $orderId, $file);
    }
}