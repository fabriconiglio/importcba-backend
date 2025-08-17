<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Actions\Action;

class ViewCoupon extends ViewRecord
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label('Editar Cupón')
                ->icon('heroicon-o-pencil')
                ->url(fn () => $this->getResource()::getUrl('edit', ['record' => $this->record])),
            Action::make('duplicate')
                ->label('Duplicar')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->action(function () {
                    $newCoupon = $this->record->replicate();
                    $newCoupon->code = $this->record->code . '_copy';
                    $newCoupon->name = $this->record->name . ' (Copia)';
                    $newCoupon->is_active = false;
                    $newCoupon->save();
                    
                    return redirect()->route('filament.admin.resources.coupons.edit', $newCoupon);
                }),
            Action::make('toggle_status')
                ->label(fn () => $this->record->is_active ? 'Desactivar' : 'Activar')
                ->icon(fn () => $this->record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn () => $this->record->is_active ? 'danger' : 'success')
                ->action(function () {
                    $this->record->update(['is_active' => !$this->record->is_active]);
                    $this->notify('success', 'Estado del cupón actualizado correctamente.');
                })
                ->requiresConfirmation(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Cupón')
                    ->schema([
                        Infolists\Components\TextEntry::make('code')
                            ->label('Código')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('name')
                            ->label('Nombre')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Descripción')
                            ->markdown()
                            ->columnSpanFull(),
                    ])->columns(2),

                Infolists\Components\Section::make('Configuración del Descuento')
                    ->schema([
                        Infolists\Components\TextEntry::make('type')
                            ->label('Tipo')
                            ->badge()
                            ->formatStateUsing(fn($state) => match($state) {
                                'percentage' => 'Porcentaje',
                                'fixed_amount' => 'Monto Fijo',
                                default => $state,
                            })
                            ->color(fn($state) => match($state) {
                                'percentage' => 'success',
                                'fixed_amount' => 'info',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('value')
                            ->label('Valor')
                            ->formatStateUsing(fn($state, $record) => 
                                $record->type === 'percentage' ? "{$state}%" : "$" . number_format($state, 2)
                            )
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('minimum_amount')
                            ->label('Monto Mínimo')
                            ->money('ARS')
                            ->formatStateUsing(fn($state) => $state ?: 'Sin mínimo'),
                        Infolists\Components\TextEntry::make('max_discount')
                            ->label('Descuento Máximo')
                            ->money('ARS')
                            ->formatStateUsing(fn($state) => $state ?: 'Sin límite'),
                    ])->columns(2),

                Infolists\Components\Section::make('Límites y Restricciones')
                    ->schema([
                        Infolists\Components\TextEntry::make('usage_limit')
                            ->label('Límite Total')
                            ->formatStateUsing(fn($state) => $state ?: 'Ilimitado'),
                        Infolists\Components\TextEntry::make('used_count')
                            ->label('Veces Usado')
                            ->badge()
                            ->color(fn($state, $record) => 
                                $record->usage_limit && $state >= $record->usage_limit ? 'danger' : 'success'
                            ),
                        Infolists\Components\TextEntry::make('per_user_limit')
                            ->label('Límite por Usuario')
                            ->formatStateUsing(fn($state) => $state ?: 'Sin límite'),
                        Infolists\Components\TextEntry::make('remaining_uses')
                            ->label('Usos Restantes')
                            ->formatStateUsing(function ($state, $record) {
                                if (!$record->usage_limit) return 'Ilimitado';
                                $remaining = $record->usage_limit - $record->used_count;
                                return max(0, $remaining);
                            })
                            ->badge()
                            ->color(fn($state) => $state > 0 ? 'success' : 'danger'),
                    ])->columns(2),

                Infolists\Components\Section::make('Vigencia')
                    ->schema([
                        Infolists\Components\TextEntry::make('starts_at')
                            ->label('Fecha de Inicio')
                            ->dateTime('d/m/Y H:i')
                            ->color(fn($state) => 
                                $state && now()->lt($state) ? 'warning' : 'success'
                            ),
                        Infolists\Components\TextEntry::make('expires_at')
                            ->label('Fecha de Expiración')
                            ->dateTime('d/m/Y H:i')
                            ->color(fn($state) => 
                                $state && now()->gt($state) ? 'danger' : 'success'
                            ),
                        Infolists\Components\TextEntry::make('is_active')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn($state) => $state ? 'Activo' : 'Inactivo')
                            ->color(fn($state) => $state ? 'success' : 'danger'),
                        Infolists\Components\TextEntry::make('status_summary')
                            ->label('Estado General')
                            ->formatStateUsing(function ($state, $record) {
                                if (!$record->is_active) return 'Inactivo';
                                if ($record->expires_at && now()->gt($record->expires_at)) return 'Expirado';
                                if ($record->usage_limit && $record->used_count >= $record->usage_limit) return 'Agotado';
                                if ($record->starts_at && now()->lt($record->starts_at)) return 'Pendiente de Inicio';
                                return 'Disponible';
                            })
                            ->badge()
                            ->color(function ($state, $record) {
                                if (!$record->is_active) return 'danger';
                                if ($record->expires_at && now()->gt($record->expires_at)) return 'danger';
                                if ($record->usage_limit && $record->used_count >= $record->usage_limit) return 'danger';
                                if ($record->starts_at && now()->lt($record->starts_at)) return 'warning';
                                return 'success';
                            }),
                    ])->columns(2),

                Infolists\Components\Section::make('Estadísticas de Uso')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Creado el')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Actualizado el')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('usage_rate')
                            ->label('Tasa de Uso')
                            ->formatStateUsing(function ($state, $record) {
                                if (!$record->usage_limit) return 'N/A';
                                $rate = ($record->used_count / $record->usage_limit) * 100;
                                return number_format($rate, 1) . '%';
                            })
                            ->badge()
                            ->color(function ($state, $record) {
                                if (!$record->usage_limit) return 'gray';
                                $rate = ($record->used_count / $record->usage_limit) * 100;
                                if ($rate >= 80) return 'danger';
                                if ($rate >= 60) return 'warning';
                                return 'success';
                            }),
                    ])->columns(2),
            ]);
    }
}
