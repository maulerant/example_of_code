<?php

namespace App\Services\Attachments\Providers;

use App\App;
use App\DTO\Services\Attachments\AttachmentUploadResult;
use App\Exceptions\ExceptionWithMsg;
use App\Helpers\Order\MergeAndSortCollection;
use App\Helpers\TextAnchorsHandler;
use App\Models\Attachment;
use App\Models\Model;
use App\Models\OrderAttachment;
use App\Models\Orders;
use App\Models\OrdersComments;
use App\Models\OrdersImages;
use App\Repositories\RHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class OrderProvider implements OrderAttachmentInterface
{
    use MergeAndSortCollection;

    /**
     * @param string $name
     * @return mixed
     */
    public function getAttachmentByName(string $name)
    {
        return OrderAttachment::join('attachments', 'order_attachment.attachment_id', '=', 'attachments.id')
            ->where('attachments.name', $name)
            ->first();
    }

    /**
     * @param Model $orderAttachment
     */
    public function deleteAttachment(Model $orderAttachment)
    {
        OrderAttachment::where(array(
            array('order_id', $orderAttachment->order_id),
            array('attachment_id', $orderAttachment->attachment_id)
        ))->delete();

        Attachment::where('id', $orderAttachment->attachment_id)->delete();

        Storage::delete('orders/' . $orderAttachment->order_id . '/' . $orderAttachment->name);

        Cache::forget(App::$db_config['dbname'] . '_orders_attachments_' . $orderAttachment->order_id);
    }

    /**
     * @param int $orderId
     * @return mixed
     */
    public function getAttachmentsByOrderId(int $orderId)
    {
        return OrderAttachment::join('attachments', 'order_attachment.attachment_id', '=', 'attachments.id')
            ->where('order_attachment.order_id', $orderId)
            ->get();
    }

    /**
     * @param int $orderId
     * @return mixed
     */
    public function getOrderImages(int $orderId)
    {
        return OrdersImages::where('order_id', $orderId)
            ->get();
    }

    /**
     * @param int $orderId
     * @param AttachmentUploadResult $answer
     * @throws ExceptionWithMsg
     */
    public function appendAttachment(int $orderId, AttachmentUploadResult $answer)
    {
        if (OrderAttachment::where('order_id', $orderId)
            ->whereIn('attachment_id', Attachment::where('hash', $answer->hash)->pluck('id'))
            ->exists()
        ) {
            throw new ExceptionWithMsg(l('Этот файл уже привязан к заказу'));
        }
        OrderAttachment::insert(array(
            'order_id' => $orderId,
            'attachment_id' => $answer->attachment_id
        ));
        Cache::forget(App::$db_config['dbname'] . '_orders_attachments_' . $orderId);
    }

    /**
     * @return string
     * @throws ExceptionWithMsg
     */
    public function getAttachmentsPathPrefix(): string
    {
        return 'orders';
    }

    /**
     * @param int $attachmentId
     * @param int $orderId
     * @return string
     * @throws ExceptionWithMsg
     * @throws \Throwable
     */
    public function getAttachmentAsComment(int $attachmentId, int $orderId): string
    {
        $comment = Attachment::with('user:fio')->find($attachmentId);
        $comment['fio'] = $comment['user']['fio'];
        $comment['text'] = l('Добавлен файл');
        $comment['url'] = route('attachments.show', [$orderId, $comment['name']]);
        $comment['date_add'] = $comment['created_at'];
        return view('orders.genorder.templates.comments._attachment', array(
            'order_id' => $orderId,
            'comment' => $comment
        ))->render();
    }

    /**
     * @param int $orderImageId
     * @return mixed
     */
    public function deleteOrderImage(int $orderImageId)
    {
        return OrdersImages::where('id', $orderImageId)
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
        $attachment = Orders::findOrFail($orderId)->order_attachments()->create([
            'user_id' => Auth::id(),
            'attachment_type' => $data->file_type,
            'name' => $data->file_name,
            'hash' => $hash
        ]);
        Cache::forget(App::$db_config['dbname'] . '_order_attachments_' . $orderId);
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

        $orderImageId = OrdersImages::create([
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
        $comments = $this->getComments($orderId);
        $attachments = Cache::remember(App::$db_config['dbname'] . '_orders_attachments_' . $orderId, 3600, function () use ($orderId) {
            return Attachment::select(DB::raw('*, "attachment" as comment_type'))->whereHas('orders', function ($query) use ($orderId) {
                $query->where('id', $orderId);
            })->get();
        });
        return $this->getMergedAndSortedCollections($comments, $attachments);
    }

    /**
     * Get comments
     *
     * @param $order_id
     * @return array
     */
    protected function getComments($order_id)
    {
        $comments = OrdersComments::select(DB::raw('*, date_add as created_at, "comment" as comment_type'))
            ->where('order_id', $order_id)
            ->orderByRaw('date_add DESC, id DESC')
            ->get();
        foreach ($comments as $comment) {
            $text = (new TextAnchorsHandler(h($comment['text'])))->getTextWithActiveAnchors();
            $comment['text'] = nl2br($text);
        }
        return $comments;
    }
}