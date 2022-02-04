<?php

namespace App\Services\Attachments;

use App\DTO\Services\Attachments\AttachmentDeleteResult;
use App\DTO\Services\Attachments\AttachmentsWithImages;
use App\Services\Attachments\Providers\OrderAttachmentInterface;

class Attachments
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
     * @throws \Throwable
     */
    public function removeOrderAttachment(string $fileName): AttachmentDeleteResult
    {
        $orderAttachment = $this->provider->getAttachmentByName($fileName);
        if (empty($orderAttachment->name)) {
            return new AttachmentDeleteResult([
                'id' => null,
                'state' => false,
                'status' => 'danger',
                'msg' => l('Файл не удален.')
            ]);
        }
        $this->provider->deleteAttachment($orderAttachment);
        return new AttachmentDeleteResult([
            'id' => empty($orderAttachment) ? null : (int)$orderAttachment->attachment_id,
            'state' => true,
            'status' => 'success',
            'msg' => l('Файл успешно удален.')
        ]);
    }

    /**
     * @param $data
     * @return mixed
     * @throws \Throwable
     */
    public function getOrderAttachment(int $orderId): ?AttachmentsWithImages
    {
        // получаем все вложения и их типы
        $attachments = $this->provider->getAttachmentsByOrderId($orderId);

        // генерируем ответ с контентом
        if ($attachments->count() == 0) {
            return new AttachmentsWithImages([
                'attachments' => collect([]),
                'images' => collect([]),
                'orderId' => $orderId
            ]);
        }
        $images = $this->provider->getOrderImages($orderId);
        return new AttachmentsWithImages([
            'attachments' => $attachments,
            'images' => $images,
            'orderId' => $orderId
        ]);
    }

    /**
     * @param $orderImageId
     * @return mixed
     */
    public function removeOrderImage($orderImageId)
    {
        return $this->provider->deleteOrderImage($orderImageId);
    }

}