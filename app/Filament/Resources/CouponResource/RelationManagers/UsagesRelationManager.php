<?php

namespace App\Filament\Resources\CouponResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsagesRelationManager extends RelationManager
{
    protected static string $relationship = 'usages';

    protected static ?string $modelLabel = 'Uso';
    protected static ?string $pluralModelLabel = 'Historial de Usos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // El formulario se deja vacío intencionalmente
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.first_name')
                    ->label('Cliente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Nro. Pedido')
                    ->searchable(),
                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('Monto Descontado')
                    ->money('ARS'),
                Tables\Columns\TextColumn::make('used_at')
                    ->label('Fecha de Uso')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
