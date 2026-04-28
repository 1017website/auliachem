<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Customers';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Perusahaan')
                ->schema([
                    Forms\Components\TextInput::make('company_name')
                        ->label('Nama Perusahaan')->required()->maxLength(255),
                    Forms\Components\TextInput::make('pic_name')
                        ->label('Nama PIC')->required()->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label('No. HP / Telepon')->required()->maxLength(50)->tel(),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')->email()->nullable(),
                    Forms\Components\TextInput::make('city')
                        ->label('Kota')->maxLength(100)->nullable(),
                    Forms\Components\Textarea::make('address')
                        ->label('Alamat')->nullable()->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Klasifikasi')
                ->schema([
                    Forms\Components\Select::make('industry_id')
                        ->label('Industri')
                        ->relationship('industry', 'name')
                        ->required()->searchable()->preload(),
                    Forms\Components\Select::make('type')
                        ->label('Tipe Customer')
                        ->options(['potential' => 'Potential', 'existing' => 'Existing'])
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'prospect' => 'Prospect',
                            'active'   => 'Active',
                            'inactive' => 'Inactive',
                        ])
                        ->default('prospect')->required(),
                    Forms\Components\Select::make('assigned_to')
                        ->label('Assigned To')
                        ->relationship('assignedTo', 'name')
                        ->required()->searchable()->preload(),
                ])->columns(2),

            Forms\Components\Section::make('Produk yang Biasa Dibeli')
                ->description('Pilih kategori produk yang sering dipesan customer ini')
                ->schema([
                    Forms\Components\CheckboxList::make('productCategories')
                        ->label('')
                        ->relationship('productCategories', 'name')
                        ->columns(3)
                        ->gridDirection('row'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Perusahaan')->searchable()->sortable()->weight('semibold'),
                Tables\Columns\TextColumn::make('pic_name')
                    ->label('PIC')->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('No. HP')->icon('heroicon-m-phone'),
                Tables\Columns\TextColumn::make('industry.name')
                    ->label('Industri')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('productCategories.name')
                    ->label('Produk')->badge()->color('info')->separator(', '),
                Tables\Columns\BadgeColumn::make('latestActivity.stage')
                    ->label('Stage')
                    ->colors([
                        'gray'    => 'identifying',
                        'info'    => 'approaching',
                        'warning' => 'following_up',
                        'primary' => 'closing',
                        'success' => 'maintaining',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'identifying'  => 'Identifying',
                        'approaching'  => 'Approaching',
                        'following_up' => 'Following Up',
                        'closing'      => 'Closing',
                        'maintaining'  => 'Maintaining',
                        default        => '—',
                    }),
                Tables\Columns\TextColumn::make('latestActivity.activity_date')
                    ->label('Last Contact')->date('d M Y')
                    ->icon('heroicon-m-calendar')->color('gray'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'prospect',
                        'success' => 'active',
                        'gray'    => 'inactive',
                    ]),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Sales')->badge()->color('primary'),
                Tables\Columns\TextColumn::make('activities_count')
                    ->counts('activities')->label('Aktivitas')->badge()->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'prospect' => 'Prospect',
                        'active'   => 'Active',
                        'inactive' => 'Inactive',
                    ]),
                Tables\Filters\SelectFilter::make('industry_id')
                    ->relationship('industry', 'name')->label('Industri'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->icon('heroicon-o-eye')->color('gray')->iconButton(),
                Tables\Actions\EditAction::make()->icon('heroicon-o-pencil-square')->color('warning')->iconButton(),
                Tables\Actions\DeleteAction::make()->icon('heroicon-o-trash')->iconButton(),
                Tables\Actions\RestoreAction::make()->icon('heroicon-o-arrow-uturn-left')->iconButton(),
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
            RelationManagers\ActivitiesRelationManager::class,
            RelationManagers\PurchaseOrdersRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['latestActivity'])
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);

        $user = Auth::user();
        if ($user && $user->hasRole('sales')) {
            $query->where('assigned_to', $user->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view'   => Pages\ViewCustomer::route('/{record}'),
            'edit'   => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
