<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One row = one platform FAQ. See FaqController for the rule-based keyword matching that finds one. */
class Faq extends Model
{
    public $timestamps = false;
    protected $table = 'faqs';
    protected $primaryKey = 'faq_id';

    protected $fillable = ['question', 'answer', 'category'];

    public function keywords(): HasMany
    {
        return $this->hasMany(FaqKeyword::class, 'faq_id', 'faq_id');
    }
}
