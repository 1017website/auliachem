<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';
    protected static ?string $title = 'Timeline Aktivitas';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('stage')
                ->label('Stage Saat Ini')
                ->options([
                    'identifying'  => '🔍 Identifying',
                    'approaching'  => '🤝 Approaching',
                    'following_up' => '💬 Following Up',
                    'closing'      => '🎯 Closing',
                    'maintaining'  => '✅ Maintaining',
                ])
                ->required()
                ->columnSpanFull(),
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
                ->placeholder('Apa yang dibahas? Apa hasilnya?')
                ->nullable()->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('activity_date')
                    ->label('Tanggal')->date('d M Y')->sortable()
                    ->icon('heroicon-m-calendar')->weight('medium'),
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
                Tables\Columns\TextColumn::make('notes')->label('Catatan')->limit(60)->color('gray'),
                Tables\Columns\TextColumn::make('createdBy.name')->label('Oleh')->badge()->color('primary'),
            ])
            ->defaultSort('activity_date', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Catat Aktivitas')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->icon('heroicon-o-pencil-square')->color('warning')->iconButton(),
                Tables\Actions\DeleteAction::make()->icon('heroicon-o-trash')->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }
}