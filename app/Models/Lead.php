<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use RuntimeException;

class Lead extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'lead_code','customer_id','company_name','pic_name','pic_position',
        'phone','email','address','industry','location','pipeline_stage','temperature',
        'product_interest','volume_estimate','timeline','notes_kebutuhan',
        'catatan_internal','probability','lead_score',
        'lead_source','competitor','expected_closing','user_id',
        'next_follow_up','next_follow_up_time','next_follow_up_notes'
    ];

    protected $casts = [
        'expected_closing' => 'date',
        'next_follow_up'   => 'date',
        'lead_score'       => 'decimal:1',
    ];

    public function salesUser(): BelongsTo  { return $this->belongsTo(User::class, 'user_id'); }
    public function user(): BelongsTo       { return $this->belongsTo(User::class, 'user_id'); }
    public function customer(): BelongsTo   { return $this->belongsTo(Customer::class); }
    public function activities(): HasMany   { return $this->hasMany(Activity::class); }
    public function purchaseOrders(): HasMany { return $this->hasMany(PurchaseOrder::class); }
    public function products(): HasMany     { return $this->hasMany(LeadProduct::class); }
    public function pics(): HasMany         { return $this->hasMany(LeadPic::class); }
    public function primaryPic(): HasMany   { return $this->hasMany(LeadPic::class)->where('is_primary', true); }

    public function getPipelineStageColorAttribute(): string
    {
        return match($this->pipeline_stage) {
            'Identifying' => 'primary',
            'Approaching' => 'warning',
            'Follow Up'   => 'purple',
            'Closing'     => 'success',
            'Won'         => 'teal',
            'Lost'        => 'danger',
            'Maintaining' => 'indigo',
            default       => 'secondary',
        };
    }

    public static function generateLeadCode(): string
    {
        $prefix = 'LEAD-' . date('Y') . '-';

        // Pakai withTrashed() karena lead_code tetap terkunci oleh unique index
        // meskipun data sudah soft delete. lockForUpdate() membantu mencegah
        // dua request bersamaan mengambil nomor urut yang sama.
        $last = static::withTrashed()
            ->where('lead_code', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('lead_code')
            ->value('lead_code');

        $seq = $last ? (intval(substr($last, -4)) + 1) : 1;

        do {
            $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
            $exists = static::withTrashed()->where('lead_code', $code)->exists();
            $seq++;
        } while ($exists && $seq <= 9999);

        return $code;
    }

    public static function createWithUniqueCode(array $attributes): self
    {
        unset($attributes['lead_code']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $attributes['lead_code'] = static::generateLeadCode();

            try {
                return static::create($attributes);
            } catch (QueryException $e) {
                if (!static::isDuplicateLeadCodeException($e)) {
                    throw $e;
                }
            }
        }

        throw new RuntimeException('Gagal membuat lead_code unik. Silakan coba simpan ulang.');
    }

    protected static function isDuplicateLeadCodeException(QueryException $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, 'leads_lead_code_unique')
            || str_contains($message, 'lead_code')
            || str_contains($message, 'Duplicate entry');
    }
}
