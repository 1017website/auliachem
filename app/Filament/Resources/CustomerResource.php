<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
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
            Forms\Components\TextInput::make('company_name')->required()->maxLength(255),
            Forms\Components\TextInput::make('pic_name')->required()->maxLength(255),
            Forms\Components\TextInput::make('phone')->required()->maxLength(50)->tel(),
            Forms\Components\TextInput::make('email')->email()->nullable(),
            Forms\Components\Select::make('industry_id')
                ->relationship('industry', 'name')->required()->searchable()->preload(),
            Forms\Components\Select::make('type')
                ->options(['potential' => 'Potential', 'existing' => 'Existing'])->required(),
            Forms\Components\Select::make('assigned_to')
                ->label('Assigned To')
                ->relationship('assignedTo', 'name')
                ->required()->searchable()->preload(),
            Forms\Components\TextInput::make('city')->maxLength(100)->nullable(),
            Forms\Components\Textarea::make('address')->nullable()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->searchable()->sortable()->weight('semibold'),
                Tables\Columns\TextColumn::make('pic_name')->searchable()->label('PIC'),
                Tables\Columns\TextColumn::make('phone')->icon('heroicon-m-phone'),
                Tables\Columns\TextColumn::make('industry.name')->sortable()->badge()->color('gray'),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors(['warning' => 'potential', 'success' => 'existing']),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned To')->badge()->color('primary'),
                Tables\Columns\TextColumn::make('city')->icon('heroicon-m-map-pin')->color('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')->sortable()->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(['potential' => 'Potential', 'existing' => 'Existing']),
                Tables\Filters\SelectFilter::make('industry_id')
                    ->relationship('industry', 'name')->label('Industry'),
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([
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
