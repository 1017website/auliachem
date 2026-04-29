<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Purchase Orders';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            // ═══════════ HEADER PO ═══════════
            Forms\Components\Section::make('Informasi Purchase Order')
                ->schema([
                    Forms\Components\TextInput::make('po_number')
                        ->label('No. PO')
                        ->required()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true)
                        ->placeholder('PO-2026-001'),
                    Forms\Components\DatePicker::make('po_date')
                        ->label('Tanggal PO')
                        ->required()
                        ->default(now())
                        ->native(false),
                    Forms\Components\Select::make('customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'company_name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('supplier_id')
                        ->label('Supplier')
                        ->relationship('supplier', 'company_name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'pending'   => 'Pending',
                            'confirmed' => 'Confirmed',
                            'delivered' => 'Delivered',
                            'invoiced'  => 'Invoiced',
                        ])
                        ->default('pending')
                        ->required()
                        ->native(false),
                ])->columns(2),

            // ═══════════ LINE ITEMS (REPEATER) ═══════════
            Forms\Components\Section::make('Item Penjualan')
                ->description('Tambahkan satu atau lebih produk yang dijual dalam PO ini')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->label('')
                        ->schema([
                            Forms\Components\Select::make('product_category_id')
                                ->label('Kategori')
                                ->relationship('productCategory', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('product_name')
                                ->label('Nama Produk')
                                ->required()
                                ->placeholder('Contoh: Methanol 99%')
                                ->columnSpan(3),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Qty')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcRow($get, $set))
                                ->columnSpan(1),
                            Forms\Components\TextInput::make('unit')
                                ->label('Unit')
                                ->required()
                                ->default('kg')
                                ->datalist(['kg', 'liter', 'drum', 'pcs', 'ton', 'galon'])
                                ->columnSpan(1),
                            Forms\Components\TextInput::make('unit_price')
                                ->label('Harga Jual / unit')
                                ->numeric()
                                ->required()
                                ->prefix('Rp')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcRow($get, $set))
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('unit_cost')
                                ->label('COGS / unit')
                                ->numeric()
                                ->required()
                                ->prefix('Rp')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcRow($get, $set))
                                ->columnSpan(2),
                            Forms\Components\Placeholder::make('subtotal_display')
                                ->label('Subtotal')
                                ->content(function (Get $get): string {
                                    $qty   = (float) $get('quantity');
                                    $price = (float) $get('unit_price');
                                    return 'Rp ' . number_format($qty * $price, 0, ',', '.');
                                })
                                ->columnSpan(1),
                            Forms\Components\Textarea::make('notes')
                                ->label('Catatan (opsional)')
                                ->rows(2)
                                ->columnSpanFull(),
                        ])
                        ->columns(12)
                        ->defaultItems(1)
                        ->addActionLabel('+ Tambah Item')
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->cloneable()
                        ->itemLabel(fn (array $state): ?string =>
                            ($state['product_name'] ?? null)
                                ? $state['product_name'] . ' (' . ($state['quantity'] ?? 0) . ' ' . ($state['unit'] ?? '') . ')'
                                : 'Item baru'
                        )
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::updateHeaderTotals($get, $set)),
                ])->collapsible(),

            // ═══════════ TOTALS (READONLY DISPLAY) ═══════════
            Forms\Components\Section::make('Ringkasan')
                ->schema([
                    Forms\Components\Placeholder::make('total_amount_display')
                        ->label('Total Penjualan')
                        ->content(fn (Get $get) => 'Rp ' . number_format(self::calcTotal($get, 'unit_price'), 0, ',', '.')),
                    Forms\Components\Placeholder::make('cogs_display')
                        ->label('Total COGS')
                        ->content(fn (Get $get) => 'Rp ' . number_format(self::calcTotal($get, 'unit_cost'), 0, ',', '.')),
                    Forms\Components\Placeholder::make('gross_profit_display')
                        ->label('Gross Profit')
                        ->content(function (Get $get) {
                            $gp = self::calcTotal($get, 'unit_price') - self::calcTotal($get, 'unit_cost');
                            return 'Rp ' . number_format($gp, 0, ',', '.');
                        }),
                ])->columns(3),
        ]);
    }

    protected static function recalcRow(Get $get, Set $set): void
    {
        $qty   = (float) $get('quantity');
        $price = (float) $get('unit_price');
        $cost  = (float) $get('unit_cost');
        $set('subtotal', $qty * $price);
        $set('subtotal_cogs', $qty * $cost);
        $set('subtotal_gross_profit', ($qty * $price) - ($qty * $cost));
    }

    protected static function calcTotal(Get $get, string $priceField): float
    {
        $items = $get('items') ?? [];
        $sum = 0;
        foreach ($items as $row) {
            $qty   = (float) ($row['quantity'] ?? 0);
            $price = (float) ($row[$priceField] ?? 0);
            $sum += $qty * $price;
        }
        return $sum;
    }

    protected static function updateHeaderTotals(Get $get, Set $set): void
    {
        // Simpan ke field tersembunyi agar masuk ke kolom parent
        // Sebenarnya recalculateTotals() di model PO yang akan handle setelah save,
        // jadi placeholder display sudah cukup untuk UI.
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('No. PO')
                    ->searchable()->sortable()->weight('semibold')->copyable(),
                Tables\Columns\TextColumn::make('po_date')
                    ->label('Tanggal')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('customer.company_name')
                    ->label('Customer')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('supplier.company_name')
                    ->label('Supplier')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('gross_profit')
                    ->label('Gross Profit')->money('IDR')->color('success')->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray'    => 'pending',
                        'info'    => 'confirmed',
                        'warning' => 'delivered',
                        'success' => 'invoiced',
                    ]),
            ])
            ->defaultSort('po_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'confirmed' => 'Confirmed',
                        'delivered' => 'Delivered',
                        'invoiced'  => 'Invoiced',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn () => \App\Filament\Exports\PurchaseOrderExporter::download()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton(),
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
                Tables\Actions\RestoreAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\ExpensesRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            \Illuminate\Database\Eloquent\SoftDeletingScope::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view'   => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit'   => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
