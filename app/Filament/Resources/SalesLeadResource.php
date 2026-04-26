<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesLeadResource\Pages;
use App\Filament\Resources\SalesLeadResource\RelationManagers;
use App\Models\SalesLead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SalesLeadResource extends Resource
{
    protected static ?string $model = SalesLead::class;
    protected static ?string $navigationIcon = 'heroicon-o-funnel';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('customer_id')
                ->relationship('customer', 'company_name')->required()->searchable()->preload(),
            Forms\Components\Select::make('assigned_to')
                ->label('Assigned To')
                ->relationship('assignedTo', 'name')->required()->searchable()->preload(),
            Forms\Components\Select::make('stage')
                ->options([
                    'identifying'  => 'Identifying',
                    'approaching'  => 'Approaching',
                    'following_up' => 'Following Up',
                    'closing'      => 'Closing',
                    'maintaining'  => 'Maintaining',
                ])->required(),
            Forms\Components\Select::make('status')
                ->options(['open' => 'Open', 'won' => 'Won', 'lost' => 'Lost'])
                ->default('open')->required(),
            Forms\Components\Textarea::make('notes')->nullable()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.company_name')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('stage')
                    ->colors([
                        'gray'    => 'identifying',
                        'info'    => 'approaching',
                        'warning' => 'following_up',
                        'primary' => 'closing',
                        'success' => 'maintaining',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors(['warning' => 'open', 'success' => 'won', 'danger' => 'lost']),
                Tables\Columns\TextColumn::make('assignedTo.name')->label('Assigned To'),
                Tables\Columns\TextColumn::make('activities_count')->counts('activities')->label('Activities'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d M Y')->sortable()->label('Updated'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->options([
                        'identifying' => 'Identifying', 'approaching' => 'Approaching',
                        'following_up' => 'Following Up', 'closing' => 'Closing', 'maintaining' => 'Maintaining',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['open' => 'Open', 'won' => 'Won', 'lost' => 'Lost']),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
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
            'index'  => Pages\ListSalesLeads::route('/'),
            'create' => Pages\CreateSalesLead::route('/create'),
            'edit'   => Pages\EditSalesLead::route('/{record}/edit'),
        ];
    }
}
