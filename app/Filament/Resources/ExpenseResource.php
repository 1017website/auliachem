<?php

namespace App\Filament\Resources;

use App\Filament\Exports\ExpenseExporter;
use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Expenses';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Detail Expense')
                ->schema([
                    Forms\Components\Select::make('po_id')
                        ->label('Purchase Order (opsional)')
                        ->relationship('purchaseOrder', 'po_number')
                        ->nullable()->searchable(),
                    Forms\Components\Select::make('category')
                        ->label('Kategori')
                        ->options([
                            'shipping'     => 'Shipping',
                            'import_duty'  => 'Import Duty',
                            'handling'     => 'Handling',
                            'other_po'     => 'Other PO',
                            'salary'       => 'Salary',
                            'rent_utility' => 'Rent & Utility',
                            'marketing'    => 'Marketing',
                            'office'       => 'Office',
                            'other_ops'    => 'Other Operational',
                        ])->required()->native(false),
                    Forms\Components\TextInput::make('description')
                        ->label('Deskripsi')->required()->maxLength(255)->columnSpanFull(),
                    Forms\Components\TextInput::make('amount')
                        ->label('Jumlah')->numeric()->required()->prefix('Rp'),
                    Forms\Components\DatePicker::make('expense_date')
                        ->label('Tanggal Expense')->required()->default(now())->native(false),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_date')
                    ->date('d M Y')->sortable()->label('Tanggal'),
                Tables\Columns\BadgeColumn::make('category')
                    ->label('Kategori')
                    ->colors([
                        'info'    => fn ($s) => in_array($s, ['shipping', 'import_duty', 'handling', 'other_po']),
                        'warning' => fn ($s) => in_array($s, ['salary', 'rent_utility']),
                        'primary' => fn ($s) => in_array($s, ['marketing', 'office', 'other_ops']),
                    ]),
                Tables\Columns\TextColumn::make('description')->label('Deskripsi')->limit(50),
                Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                    ->label('PO')->default('-')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')->money('IDR')->sortable()->color('danger'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('By')->badge()->color('primary'),
            ])
            ->defaultSort('expense_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'shipping'     => 'Shipping',
                        'import_duty'  => 'Import Duty',
                        'handling'     => 'Handling',
                        'other_po'     => 'Other PO',
                        'salary'       => 'Salary',
                        'rent_utility' => 'Rent & Utility',
                        'marketing'    => 'Marketing',
                        'office'       => 'Office',
                        'other_ops'    => 'Other Operational',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn () => ExpenseExporter::download()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton(),
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
                Tables\Actions\RestoreAction::make()->iconButton(),
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
            'index'  => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'view'   => Pages\ViewExpense::route('/{record}'),
            'edit'   => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
