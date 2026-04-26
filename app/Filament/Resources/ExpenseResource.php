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
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
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
                Tables\Columns\TextColumn::make('expense_date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('category')->badge(),
                Tables\Columns\TextColumn::make('description')->limit(50),
                Tables\Columns\TextColumn::make('purchaseOrder.po_number')->label('PO')->default('-'),
                Tables\Columns\TextColumn::make('amount')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('createdBy.name')->label('By'),
            ])
            ->defaultSort('expense_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'shipping' => 'Shipping', 'import_duty' => 'Import Duty',
                        'handling' => 'Handling', 'other_po' => 'Other PO',
                        'salary' => 'Salary', 'rent_utility' => 'Rent & Utility',
                        'marketing' => 'Marketing', 'office' => 'Office', 'other_ops' => 'Other Operational',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
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
            'edit'   => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
