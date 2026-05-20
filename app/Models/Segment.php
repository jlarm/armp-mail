<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SegmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['email_list_id', 'name', 'rules'])]
class Segment extends Model
{
    /** @use HasFactory<SegmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'email_list_id' => 'integer',
            'name' => 'string',
            'rules' => 'json',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EmailList, $this>
     */
    public function emailList(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }
}
