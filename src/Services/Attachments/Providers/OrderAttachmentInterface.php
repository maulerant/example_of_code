<?php

namespace App\Services\Attachments\Providers;

use App\DTO\Services\Attachments\AttachmentUploadResult;
use App\Exceptions\ExceptionWithMsg;
use App\Models\Attachment;
use App\Models\Model;
use Illuminate\Support\Collection;

interface OrderAttachmentInterface
{
    /**
     * @param string $name
     * @return mixed
     */
    public function getAttachmentByName(string $name);

    /**
     * @param int $orderId
     * @return mixed
     */
    public function getAttachmentsByOrderId(int $orderId);

    /**
     * @param Model $orderAttachment
     * @return mixed
     */
    public function deleteAttachment(Model $orderAttachment);

    /**
     * @param int $orderId
     * @param AttachmentUploadResult $data
     * @param string $hash
     * @return Attachment
     */
    public function addAttachment(int $orderId, AttachmentUploadResult $data, string $hash): Attachment;

    /**
     * @param int $orderId
     * @param AttachmentUploadResult $answer
     * @throws ExceptionWithMsg
     */
    public function appendAttachment(int $orderId, AttachmentUploadResult $answer);

    /**
     * @return string
     * @throws ExceptionWithMsg
     */
    public function getAttachmentsPathPrefix(): string;

    /**
     * @param int $attachmentId
     * @param int $orderId
     * @return string
     */
    public function getAttachmentAsComment(int $attachmentId, int $orderId): string;

    /**
     * @param int $orderId
     * @return Collection
     */
    public function getCommentsAndAttachment(int $orderId): Collection;

    /**
     * @param int $orderId
     * @return mixed
     */
    public function getOrderImages(int $orderId);

    /**
     * @param int $orderId
     * @param string $imageName
     * @return mixed
     */
    public function addOrderImage(int $orderId, string $imageName): int;

    /**
     * @param int $orderImageId
     * @return mixed
     */
    public function deleteOrderImage(int $orderImageId);
}