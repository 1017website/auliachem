<?php

namespace App\Filament\Resources;

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
            Forms\Components\Select::make('po_id')
                ->label('Purchase Order (opsional)')
                ->relationship('purchaseOrder', 'po_number')
                ->nullable()->searchable(),
            Forms\Components\Select::make('category')
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
                ])->required(),
            Forms\Components\TextInput::make('description')->required()->maxLength(255),
            Forms\Components\TextInput::make('amount')->numeric()->required()->prefix('Rp'),
            Forms\Components\DatePicker::make('expense_date')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_date')
                    ->date('d M Y')->sortable()->icon('heroicon-m-calendar'),
                Tables\Columns\BadgeColumn::make('category')
                    ->colors([
                        'info'    => fn ($s) => in_array($s, ['shipping', 'import_duty', 'handling', 'other_po']),
                        'warning' => fn ($s) => in_array($s, ['salary', 'rent_utility']),
                        'primary' => fn ($s) => in_array($s, ['marketing', 'office', 'other_ops']),
                    ]),
                Tables\Columns\TextColumn::make('description')->limit(50),
                Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                    ->label('PO')->default('-')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('amount')->money('IDR')->sortable()->color('danger'),
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
            'index'  => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'view'   => Pages\ViewExpense::route('/{record}'),
            'edit'   => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
