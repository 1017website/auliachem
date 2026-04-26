<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('company_name')->required()->maxLength(255),
            Forms\Components\TextInput::make('pic_name')->required()->maxLength(255),
            Forms\Components\TextInput::make('phone')->required()->maxLength(50)->tel(),
            Forms\Components\TextInput::make('country')->required()->maxLength(100),
            Forms\Components\Select::make('type')
                ->options(['potential' => 'Potential', 'existing' => 'Existing'])->required(),
            Forms\Components\Select::make('source')
                ->options(['local' => 'Local', 'import' => 'Import'])->required(),
            Forms\Components\Select::make('principal_id')
                ->relationship('principal', 'name')->nullable()->searchable()->preload(),
            Forms\Components\Select::make('product_category_id')
                ->relationship('productCategory', 'name')->required()->searchable()->preload(),
            Forms\Components\TextInput::make('lead_time_days')->numeric()->nullable()->suffix('hari'),
            Forms\Components\Select::make('currency')
                ->options(['IDR' => 'IDR', 'USD' => 'USD', 'EUR' => 'EUR'])->default('IDR')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('pic_name')->searchable(),
                Tables\Columns\TextColumn::make('country'),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors(['warning' => 'potential', 'success' => 'existing']),
                Tables\Columns\BadgeColumn::make('source')
                    ->colors(['info' => 'local', 'primary' => 'import']),
                Tables\Columns\TextColumn::make('productCategory.name')->label('Kategori'),
                Tables\Columns\TextColumn::make('currency')->badge(),
                Tables\Columns\TextColumn::make('lead_time_days')->suffix(' hari')->label('Lead Time'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(['potential' => 'Potential', 'existing' => 'Existing']),
                Tables\Filters\SelectFilter::make('source')
                    ->options(['local' => 'Local', 'import' => 'Import']),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
