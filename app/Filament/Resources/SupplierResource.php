<?php

namespace App\Filament\Resources;

use App\Filament\Exports\SupplierExporter;
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
    protected static ?string $navigationLabel = 'Suppliers';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Supplier')
                ->schema([
                    Forms\Components\TextInput::make('company_name')->label('Nama Perusahaan')->required()->maxLength(255),
                    Forms\Components\TextInput::make('pic_name')->label('PIC')->required()->maxLength(255),
                    Forms\Components\TextInput::make('phone')->label('No. Telepon')->required()->maxLength(50)->tel(),
                    Forms\Components\TextInput::make('country')->label('Negara')->required()->maxLength(100),
                ])->columns(2),

            Forms\Components\Section::make('Klasifikasi')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Tipe')
                        ->options(['potential' => 'Potential', 'existing' => 'Existing'])->required()->native(false),
                    Forms\Components\Select::make('source')
                        ->label('Source')
                        ->options(['local' => 'Local', 'import' => 'Import'])->required()->native(false),
                    Forms\Components\Select::make('principal_id')
                        ->label('Principal')
                        ->relationship('principal', 'name')->nullable()->searchable()->preload(),
                    Forms\Components\Select::make('product_category_id')
                        ->label('Kategori Produk')
                        ->relationship('productCategory', 'name')->required()->searchable()->preload(),
                ])->columns(2),

            Forms\Components\Section::make('Detail Operasional')
                ->schema([
                    Forms\Components\TextInput::make('lead_time_days')
                        ->label('Lead Time')->numeric()->nullable()->suffix('hari'),
                    Forms\Components\Select::make('currency')
                        ->label('Currency')
                        ->options(['IDR' => 'IDR', 'USD' => 'USD', 'EUR' => 'EUR'])
                        ->default('IDR')->required()->native(false),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')->label('Perusahaan')
                    ->searchable()->sortable()->weight('semibold'),
                Tables\Columns\TextColumn::make('pic_name')->searchable()->label('PIC'),
                Tables\Columns\TextColumn::make('country')->label('Negara')->color('gray'),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors(['warning' => 'potential', 'success' => 'existing']),
                Tables\Columns\BadgeColumn::make('source')
                    ->colors(['info' => 'local', 'primary' => 'import']),
                Tables\Columns\TextColumn::make('productCategory.name')
                    ->label('Kategori')->badge()->color('gray'),
                Tables\Columns\BadgeColumn::make('currency')
                    ->colors(['success' => 'IDR', 'warning' => 'USD', 'info' => 'EUR']),
                Tables\Columns\TextColumn::make('lead_time_days')
                    ->suffix(' hari')->label('Lead Time')->color('gray')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(['potential' => 'Potential', 'existing' => 'Existing']),
                Tables\Filters\SelectFilter::make('source')
                    ->options(['local' => 'Local', 'import' => 'Import']),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn () => SupplierExporter::download()),
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            \Illuminate\Database\Eloquent\SoftDeletingScope::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'view'   => Pages\ViewSupplier::route('/{record}'),
            'edit'   => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
