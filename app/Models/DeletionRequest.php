<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DeletionRequest extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'model_label',
        'module',
        'status',
        'reason',
        'review_note',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /**
     * Daftar target yang boleh dihapus melalui endpoint approval.
     * Daftar putih ini mencegah client mengirim nama class sembarang.
     */
    public const MODULES = [
        'leads' => [
            'model' => Lead::class,
            'label' => 'company_name',
            'title' => 'Lead',
            'route' => 'leads.index',
        ],
        'lead-pics' => [
            'model' => LeadPic::class,
            'label' => 'pic_name',
            'title' => 'PIC Lead',
            'route' => 'leads.index',
        ],
        'lead-products' => [
            'model' => LeadProduct::class,
            'label' => 'product_name',
            'title' => 'Produk Lead',
            'route' => 'leads.index',
        ],
        'customers' => [
            'model' => Customer::class,
            'label' => 'company_name',
            'title' => 'Customer',
            'route' => 'customers.index',
        ],
        'customer-pics' => [
            'model' => CustomerPic::class,
            'label' => 'pic_name',
            'title' => 'PIC Customer',
            'route' => 'customers.index',
        ],
        'suppliers' => [
            'model' => Supplier::class,
            'label' => 'supplier_name',
            'title' => 'Supplier',
            'route' => 'suppliers.index',
            'feature' => 'suppliers',
        ],
        'supplier-pics' => [
            'model' => SupplierPic::class,
            'label' => 'pic_name',
            'title' => 'PIC Supplier',
            'route' => 'suppliers.index',
            'feature' => 'suppliers',
        ],
        'supplier-products' => [
            'model' => SupplierProduct::class,
            'label' => 'product_name',
            'title' => 'Produk Supplier',
            'route' => 'suppliers.index',
            'feature' => 'suppliers',
        ],
        'purchase-orders' => [
            'model' => PurchaseOrder::class,
            'label' => 'po_number',
            'title' => 'Purchase Order',
            'route' => 'purchase-orders.index',
            'feature' => 'purchase_orders',
        ],
        'invoices' => [
            'model' => Invoice::class,
            'label' => 'invoice_number',
            'title' => 'Invoice',
            'route' => 'invoices.index',
        ],
        'quotations' => [
            'model' => Quotation::class,
            'label' => 'quotation_number',
            'title' => 'Penawaran',
            'route' => 'quotations.index',
        ],
        'products' => [
            'model' => Product::class,
            'label' => 'product_name',
            'title' => 'Master Barang',
            'route' => 'products.index',
        ],
        'tasks' => [
            'model' => Activity::class,
            'label' => 'subject',
            'title' => 'Task / Reminder',
            'route' => 'tasks.index',
        ],
    ];

    private static ?array $pendingTargetCache = null;

    public function target(): MorphTo
    {
        return $this->morphTo('model', 'model_type', 'model_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }

    public function getModuleTitleAttribute(): string
    {
        return self::MODULES[$this->module]['title'] ?? $this->module;
    }

    public static function configFor(string $module): ?array
    {
        return self::MODULES[$module] ?? null;
    }

    public static function labelFor(string $module, Model $model): string
    {
        $field = self::MODULES[$module]['label'] ?? null;

        return (string) ($field ? ($model->{$field} ?? '#' . $model->getKey()) : '#' . $model->getKey());
    }

    public static function requestFor(string $module, Model $model, int $userId, ?string $reason = null): self
    {
        $request = static::firstOrCreate(
            [
                'model_type' => $model->getMorphClass(),
                'model_id' => $model->getKey(),
                'status' => 'pending',
            ],
            [
                'model_label' => static::labelFor($module, $model),
                'module' => $module,
                'reason' => $reason,
                'requested_by' => $userId,
            ]
        );

        self::$pendingTargetCache = null;

        return $request;
    }

    /**
     * Cache satu query per request agar tombol pada tabel tidak menimbulkan N+1 query.
     */
    public static function pendingFor(string $module, int|string $modelId): bool
    {
        if (self::$pendingTargetCache === null) {
            self::$pendingTargetCache = static::where('status', 'pending')
                ->get(['module', 'model_id'])
                ->mapWithKeys(fn (self $request) => [
                    $request->module . ':' . $request->model_id => true,
                ])
                ->all();
        }

        return isset(self::$pendingTargetCache[$module . ':' . $modelId]);
    }

    public static function pendingCount(): int
    {
        return static::where('status', 'pending')->count();
    }

    public static function pendingIdsForModule(string $module): array
    {
        return static::where('module', $module)
            ->where('status', 'pending')
            ->pluck('model_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public static function clearPendingCache(): void
    {
        self::$pendingTargetCache = null;
    }
}
