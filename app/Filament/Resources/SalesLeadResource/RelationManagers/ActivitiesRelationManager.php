<?php

namespace App\Filament\Resources\SalesLeadResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';
    protected static ?string $title = 'Activities';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->options([
                    'phone' => 'Phone', 'visit' => 'Visit',
                    'whatsapp' => 'WhatsApp', 'email' => 'Email', 'other' => 'Other',
                ])->required(),
            Forms\Components\DatePicker::make('activity_date')->required(),
            Forms\Components\Textarea::make('notes')->nullable()->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'visit', 'info' => 'phone',
                        'warning' => 'whatsapp', 'primary' => 'email', 'gray' => 'other',
                    ]),
                Tables\Columns\TextColumn::make('activity_date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('notes')->limit(60),
                Tables\Columns\TextColumn::make('createdBy.name')->label('By'),
            ])
            ->defaultSort('activity_date', 'desc')
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }
}
