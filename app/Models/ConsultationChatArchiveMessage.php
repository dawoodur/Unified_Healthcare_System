<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one message or shared photo inside an admin-only consultation
 * chat archive — see ConsultationChatArchive.php.
 */
class ConsultationChatArchiveMessage extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'archive_message_id';

    protected $fillable = ['archive_id', 'sender_account_id', 'type', 'message_text', 'photo_path', 'sent_at_label'];

    public function archive(): BelongsTo
    {
        return $this->belongsTo(ConsultationChatArchive::class, 'archive_id', 'archive_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'sender_account_id', 'account_id');
    }

    public function isPhoto(): bool
    {
        return $this->type === 'photo';
    }
}
