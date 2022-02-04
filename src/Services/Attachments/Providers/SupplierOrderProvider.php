<?php

namespace App\Services\Attachments\Providers;

use App\App;
use App\DTO\Services\Attachments\AttachmentUploadResult;
use App\Exceptions\ExceptionWithMsg;
use App\Helpers\Order\MergeAndSortCollection;
use App\Models\Attachment;
use App\Models\ContractorsSuppliersOrders;
use App\Models\ContractorSupplierOrderAttachment;
use App\Models\ContractorSupplierOrderImage;
use App\Models\Model;
use App\Repositories\RHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class SupplierOrderProvider implements OrderAttachmentInterface
{
    use MergeAndSortCollection;

    /**
     * @param string $name
     * @return mixed
     */
    public function getAttachmentByName(string $name)
    {
        return ContractorSupplierOrderAttachment::join('attachments', 'contractor_supplier_order_attachments.attachment_id', '=', 'attachments.id')
            ->where('attachments.name', $name)
            ->first();
    }

    /**
     * @param Model $orderAttachment
     */
    public function deleteAttachment(Model $orderAttachment)
    {
        ContractorSupplierOrderAttachment::where(array(
            array('order_id', $orderAttachment->order_id),
            array('attachment_id', $orderAttachment->attachment_id)
        ))->delete();

        Attachment::where('id', $orderAttachment->attachment_id)->delete();

        Storage::delete('supplier_orders/' . $orderAttachment->order_id . '/' . $orderAttachment->name);

        Cache::forget(App::$db_config['dbname'] . '_supplier_orders_attachments_' . $orderAttachment->order_id);
    }

    /**
     * @param int $orderId
     * @return mixed
     */
    public function getAttachmentsByOrderId(int $orderId)
    {
        return ContractorSupplierOrderAttachment::join('attachments', 'contractor_supplier_order_attachments.attachment_id', '=', 'attachments.id')
            ->where('contractor_supplier_order_attachments.order_id', $orderId)
            ->get();
    }

    /**
     * @param int $orderId
     * @return mixed
     */
    public function getOrderImages(int $orderId)
    {
        return ContractorSupplierOrderImage::where('order_id', $orderId)
            ->get();
    }

    /**
     * @param int $orderId
     * @param AttachmentUploadResult $answer
     * @throws ExceptionWithMsg
     */
    public function appendAttachment(int $orderId, AttachmentUploadResult $answer)
    {
        if (ContractorSupplierOrderAttachment::where('order_id', $orderId)
            ->whereIn('attachment_id', Attachment::where('hash', $answer->hash)->pluck('id'))
            ->exists()
        ) {
            throw new ExceptionWithMsg(l('Этот файл уже привязан к заказу'));
        }
        ContractorSupplierOrderAttachment::insert(array(
            'order_id' => $orderId,
            'attachment_id' => $answer->attachment_id
        ));
        Cache::forget(App::$db_config['dbname'] . '_supplier_orders_attachments_' . $orderId);
    }

    /**
     * @return string
     * @throws ExceptionWithMsg
     */
    public function getAttachmentsPathPrefix(): string
    {
        return 'supplier_orders';
    }

    /**
     * @param int $attachmentId
     * @param int $orderId
     * @return string
     */
    public function getAttachmentAsComment(int $attachmentId, int $orderId): string
    {
        return '';
    }

    /**
     * @param int $orderImageId
     * @return mixed
     */
    public function deleteOrderImage(int $orderImageId)
    {
        return ContractorSupplierOrderImage::where('id', $orderImageId)
            ->delete();
    }

    /**
     * @param int $orderId
     * @param AttachmentUploadResult $data
     * @param string $hash
     * @return Attachment
     */
    public function addAttachment(int $orderId, AttachmentUploadResult $data, string $hash): Attachment
    {
        $attachment = ContractorsSuppliersOrders::findOrFail($orderId)->order_attachments()->create([
            'user_id' => Auth::id(),
            'attachment_type' => $data->file_type,
            'name' => $data->file_name,
            'hash' => $hash
        ]);
        Cache::forget(App::$db_config['dbname'] . 'supplier_orders_attachments_' . $orderId);
        return $attachment;
    }

    /**
     * @param int $orderId
     * @param string $imageName
     * @return mixed
     */
    public function addOrderImage(int $orderId, string $imageName): int
    {
        $mod_id = App::$all_configs['configs']['orders-manage-page'];

        $orderImageId = ContractorSupplierOrderImage::create([
            'image_name' => trim($imageName),
            'order_id' => intval($orderId)
        ])->id;

        if ($orderImageId) {
            app()->make(RHistory::class)->add('add-image-goods', $mod_id, intval($orderId));
        }

        return $orderImageId;
    }

    /**
     * @param int $orderId
     * @return Collection
     */
    public function getCommentsAndAttachment(int $orderId): Collection
    {
        $attachments = Cache::remember(App::$db_config['dbname'] . '_supplier_orders_attachments_' . $orderId, 3600, function () use ($orderId) {
            return Attachment::select(DB::raw('*, "attachment" as comment_type'))->whereHas('supplier_orders', function ($query) use ($orderId) {
                $query->where('contractors_suppliers_orders.id', $orderId);
            })->get();
        });
        return $this->getMergedAndSortedCollections(collect([]), $attachments);
    }
}