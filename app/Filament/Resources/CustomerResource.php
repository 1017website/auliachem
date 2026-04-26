<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use App\Models\User;
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
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
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
                Tables\Columns\TextColumn::make('company_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('pic_name')->searchable(),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('industry.name')->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors(['warning' => 'potential', 'success' => 'existing']),
                Tables\Columns\TextColumn::make('assignedTo.name')->label('Assigned To')->sortable(),
                Tables\Columns\TextColumn::make('city'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(['potential' => 'Potential', 'existing' => 'Existing']),
                Tables\Filters\SelectFilter::make('industry_id')
                    ->relationship('industry', 'name')->label('Industry'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
