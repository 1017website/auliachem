<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
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
            Forms\Components\TextInput::make('po_number')
                ->required()->maxLength(100)->unique(ignoreRecord: true),
            Forms\Components\DatePicker::make('po_date')->required(),
            Forms\Components\Select::make('customer_id')
                ->relationship('customer', 'company_name')->required()->searchable()->preload(),
            Forms\Components\Select::make('sales_lead_id')
                ->relationship('salesLead', 'id')
                ->getOptionLabelFromRecordUsing(fn ($r) => $r->customer->company_name . ' — ' . $r->stage)
                ->nullable()->searchable(),
            Forms\Components\Select::make('supplier_id')
                ->relationship('supplier', 'company_name')->required()->searchable()->preload(),
            Forms\Components\Select::make('product_category_id')
                ->relationship('productCategory', 'name')->required()->searchable()->preload(),
            Forms\Components\TextInput::make('total_amount')->numeric()->required()->prefix('Rp'),
            Forms\Components\TextInput::make('cogs')->label('COGS')->numeric()->required()->prefix('Rp'),
            Forms\Components\TextInput::make('gross_profit')->numeric()->required()->prefix('Rp'),
            Forms\Components\Select::make('status')
                ->options([
                    'pending'   => 'Pending',
                    'confirmed' => 'Confirmed',
                    'delivered' => 'Delivered',
                    'invoiced'  => 'Invoiced',
                ])
                ->default('pending')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->searchable()->sortable()->weight('semibold')->copyable(),
                Tables\Columns\TextColumn::make('po_date')
                    ->date('d M Y')->sortable()->icon('heroicon-m-calendar'),
                Tables\Columns\TextColumn::make('customer.company_name')
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('supplier.company_name')->searchable(),
                Tables\Columns\TextColumn::make('productCategory.name')->label('Kategori')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('total_amount')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('gross_profit')->money('IDR')->color('success'),
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
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->iconButton(),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->iconButton(),
                Tables\Actions\RestoreAction::make()
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->iconButton(),
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
