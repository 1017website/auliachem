<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PipelineLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'pipelineLogs';
    protected static ?string $title = 'Riwayat Stage';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->icon('heroicon-m-clock')
                    ->weight('medium'),
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
                Tables\Columns\BadgeColumn::make('contact_type')
                    ->label('Kontak')
                    ->colors([
                        'success' => 'visit',
                        'info'    => 'phone',
                        'warning' => 'whatsapp',
                        'primary' => 'email',
                        'gray'    => 'other',
                    ]),
                Tables\Columns\TextColumn::make('contact_date')
                    ->label('Tgl Kontak')
                    ->date('d M Y')
                    ->icon('heroicon-m-calendar'),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(60)
                    ->color('gray'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Oleh')
                    ->badge()
                    ->color('primary'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25]);
    }
}