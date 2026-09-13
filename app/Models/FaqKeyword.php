<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one keyword that hints at one FAQ. The table's real primary
 * key is the (faq_id, keyword) pair, not a single ID column — this model
 * is only ever queried/listed in bulk (never looked up by a single ID
 * via find()), so that's not a practical problem here.
 */
class FaqKeyword extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $table = 'faq_keywords';
    protected $primaryKey = 'faq_id';

    protected $fillable = ['faq_id', 'keyword'];

    public function faq(): BelongsTo
    {
        return $this->belongsTo(Faq::class, 'faq_id', 'faq_id');
    }
}
