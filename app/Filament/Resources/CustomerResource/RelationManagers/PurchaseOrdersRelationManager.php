<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseOrders';
    protected static ?string $title = 'Purchase Orders';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('po_number')->required()->unique(ignoreRecord: true),
            Forms\Components\DatePicker::make('po_date')->required()->default(now()),
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

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->searchable()->weight('semibold')->copyable(),
                Tables\Columns\TextColumn::make('po_date')
                    ->date('d M Y')->sortable()->icon('heroicon-m-calendar'),
                Tables\Columns\TextColumn::make('supplier.company_name'),
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
            ->headerActions([
                Tables\Actions\CreateAction::make()->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->icon('heroicon-o-pencil-square')->iconButton(),
                Tables\Actions\DeleteAction::make()->icon('heroicon-o-trash')->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }
}