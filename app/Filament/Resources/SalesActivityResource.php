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
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('customer_id')
                ->label('Customer')
                ->relationship('customer', 'company_name')
                ->required()->searchable()->preload(),
            Forms\Components\Select::make('stage')
                ->label('Stage Saat Ini')
                ->options([
                    'identifying'  => '🔍 Identifying',
                    'approaching'  => '🤝 Approaching',
                    'following_up' => '💬 Following Up',
                    'closing'      => '🎯 Closing',
                    'maintaining'  => '✅ Maintaining',
                ])
                ->required(),
            Forms\Components\Select::make('type')
                ->label('Metode Kontak')
                ->options([
                    'phone'    => '📞 Phone',
                    'visit'    => '🤝 Visit',
                    'whatsapp' => '💬 WhatsApp',
                    'email'    => '✉️ Email',
                    'other'    => 'Other',
                ])->required(),
            Forms\Components\DatePicker::make('activity_date')
                ->label('Tanggal')->required()->default(now()),
            Forms\Components\Textarea::make('notes')
                ->label('Catatan / Hasil Follow-up')
                ->placeholder('Apa yang dibahas? Apa hasilnya? Langkah selanjutnya?')
                ->nullable()->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('activity_date')
                    ->label('Tanggal')->date('d M Y')->sortable()
                    ->icon('heroicon-m-calendar')->weight('medium'),
                Tables\Columns\TextColumn::make('customer.company_name')
                    ->label('Customer')->searchable()->sortable()->weight('semibold'),
                Tables\Columns\BadgeColumn::make('stage')
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
                        default        => $state,
                    }),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Metode')
                    ->colors([
                        'success' => 'visit',
                        'info'    => 'phone',
                        'warning' => 'whatsapp',
                        'primary' => 'email',
                        'gray'    => 'other',
                    ]),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')->limit(55)->color('gray'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Sales')->badge()->color('primary'),
            ])
            ->defaultSort('activity_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->options([
                        'identifying'  => 'Identifying',
                        'approaching'  => 'Approaching',
                        'following_up' => 'Following Up',
                        'closing'      => 'Closing',
                        'maintaining'  => 'Maintaining',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'phone'    => 'Phone',
                        'visit'    => 'Visit',
                        'whatsapp' => 'WhatsApp',
                        'email'    => 'Email',
                        'other'    => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('customer_id')
                    ->relationship('customer', 'company_name')
                    ->label('Customer')->searchable()->preload(),
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
