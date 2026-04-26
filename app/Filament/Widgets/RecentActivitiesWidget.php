<?php

namespace App\Filament\Widgets;

use App\Models\SalesActivity;
use App\Models\SalesLead;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentActivitiesWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Aktivitas Sales Terkini';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SalesActivity::query()
                    ->with(['salesLead.customer', 'createdBy'])
                    ->latest('activity_date')
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('activity_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipe')
                    ->colors([
                        'success' => 'visit',
                        'info'    => 'phone',
                        'warning' => 'whatsapp',
                        'primary' => 'email',
                        'gray'    => 'other',
                    ]),
                Tables\Columns\TextColumn::make('salesLead.customer.company_name')
                    ->label('Customer')
                    ->searchable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('salesLead.stage')
                    ->label('Stage')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(45)
                    ->color('gray'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Sales')
                    ->badge()
                    ->color('primary'),
            ])
            ->paginated(false);
    }
}
