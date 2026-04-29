<?php

namespace App\Filament\Widgets;

use App\Models\SalesActivity;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class RecentActivitiesWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 7;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Aktivitas Sales Terkini';

    public function table(Table $table): Table
    {
        [$start, $end] = $this->getPeriodRange();

        return $table
            ->query(
                SalesActivity::query()
                    ->with(['customer', 'createdBy'])
                    ->whereBetween('activity_date', [$start, $end])
                    ->latest('activity_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('activity_date')
                    ->label('Tanggal')->date('d M Y')->weight('medium'),
                Tables\Columns\TextColumn::make('customer.company_name')
                    ->label('Customer')->weight('semibold'),
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
                    ->label('Catatan')->limit(45)->color('gray'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Sales')->badge()->color('primary'),
            ])
            ->paginated(false);
    }

    protected function getPeriodRange(): array
    {
        $startDate = $this->filters['start_date'] ?? null;
        $endDate   = $this->filters['end_date'] ?? null;

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subMonths(5)->startOfMonth();
        $end   = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();

        return [$start, $end];
    }
}
