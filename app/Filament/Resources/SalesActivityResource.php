<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesActivityResource\Pages;
use App\Models\SalesActivity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SalesActivityResource extends Resource
{
    protected static ?string $model = SalesActivity::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Aktivitas';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('sales_lead_id')
                ->relationship('salesLead', 'id')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->customer->company_name . ' — ' . $record->stage)
                ->required()->searchable()->preload(),
            Forms\Components\Select::make('type')
                ->options([
                    'phone'    => 'Phone',
                    'visit'    => 'Visit',
                    'whatsapp' => 'WhatsApp',
                    'email'    => 'Email',
                    'other'    => 'Other',
                ])->required(),
            Forms\Components\DatePicker::make('activity_date')->required(),
            Forms\Components\Textarea::make('notes')->nullable()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('salesLead.customer.company_name')
                    ->label('Customer')->searchable()->sortable()->weight('semibold'),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'visit',
                        'info'    => 'phone',
                        'warning' => 'whatsapp',
                        'primary' => 'email',
                        'gray'    => 'other',
                    ]),
                Tables\Columns\TextColumn::make('activity_date')
                    ->date('d M Y')->sortable()->icon('heroicon-m-calendar'),
                Tables\Columns\TextColumn::make('notes')->limit(55)->color('gray'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('By')->badge()->color('primary'),
            ])
            ->defaultSort('activity_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'phone'    => 'Phone',
                        'visit'    => 'Visit',
                        'whatsapp' => 'WhatsApp',
                        'email'    => 'Email',
                        'other'    => 'Other',
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            \Illuminate\Database\Eloquent\SoftDeletingScope::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSalesActivities::route('/'),
            'create' => Pages\CreateSalesActivity::route('/create'),
            'view'   => Pages\ViewSalesActivity::route('/{record}'),
            'edit'   => Pages\EditSalesActivity::route('/{record}/edit'),
        ];
    }
}
