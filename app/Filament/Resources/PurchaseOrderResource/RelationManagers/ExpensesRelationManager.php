<?php

namespace App\Filament\Resources\PurchaseOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';
    protected static ?string $title = 'Expenses PO';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category')
                ->options([
                    'shipping' => 'Shipping', 'import_duty' => 'Import Duty',
                    'handling' => 'Handling', 'other_po' => 'Other PO',
                ])->required(),
            Forms\Components\TextInput::make('description')->required()->maxLength(255),
            Forms\Components\TextInput::make('amount')->numeric()->required()->prefix('Rp'),
            Forms\Components\DatePicker::make('expense_date')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')->badge(),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('amount')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('expense_date')->date('d M Y')->sortable(),
            ])
            ->defaultSort('expense_date', 'desc')
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }
}
