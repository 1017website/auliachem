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
    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    protected static ?string $navigationLabel = 'Sales Leads';
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
                Tables\Columns\TextColumn::make('customer.company_name')
                    ->searchable()->sortable()->weight('semibold'),
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
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned To')->badge()->color('primary'),
                Tables\Columns\TextColumn::make('activities_count')
                    ->counts('activities')->label('Activities')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d M Y')->sortable()->label('Updated')->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->options([
                        'identifying'  => 'Identifying',
                        'approaching'  => 'Approaching',
                        'following_up' => 'Following Up',
                        'closing'      => 'Closing',
                        'maintaining'  => 'Maintaining',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['open' => 'Open', 'won' => 'Won', 'lost' => 'Lost']),
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
            'view'   => Pages\ViewSalesLead::route('/{record}'),
            'edit'   => Pages\EditSalesLead::route('/{record}/edit'),
        ];
    }
}
