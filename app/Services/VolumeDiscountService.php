<?php

namespace App\Services;

class VolumeDiscountService
{
    /**
     * Obtener descuento por volumen basado en el subtotal
     */
    public function getVolumeDiscount(float $subtotal): array
    {
        $discountPercentage = 0;
        $discountAmount = 0;
        $nextTier = null;

        if ($subtotal >= 500000) {
            $discountPercentage = 20;
            $discountAmount = ($subtotal * 20) / 100;
        } elseif ($subtotal >= 400000) {
            $discountPercentage = 15;
            $discountAmount = ($subtotal * 15) / 100;
            $nextTier = [
                'amount' => 500000,
                'percentage' => 20,
                'remaining' => 500000 - $subtotal
            ];
        } elseif ($subtotal >= 300000) {
            $discountPercentage = 10;
            $discountAmount = ($subtotal * 10) / 100;
            $nextTier = [
                'amount' => 400000,
                'percentage' => 15,
                'remaining' => 400000 - $subtotal
            ];
        } else {
            $nextTier = [
                'amount' => 300000,
                'percentage' => 10,
                'remaining' => 300000 - $subtotal
            ];
        }

        return [
            'has_discount' => $discountPercentage > 0,
            'percentage' => $discountPercentage,
            'amount' => $discountAmount,
            'next_tier' => $nextTier,
            'subtotal' => $subtotal,
            'final_amount' => $subtotal - $discountAmount
        ];
    }

    /**
     * Obtener información de todos los niveles de descuento
     */
    public function getDiscountTiers(): array
    {
        return [
            [
                'min_amount' => 300000,
                'percentage' => 10,
                'description' => 'Superando $300.000'
            ],
            [
                'min_amount' => 400000,
                'percentage' => 15,
                'description' => 'Superando $400.000'
            ],
            [
                'min_amount' => 500000,
                'percentage' => 20,
                'description' => 'Superando $500.000'
            ]
        ];
    }

    /**
     * Calcular cuánto falta para el siguiente nivel de descuento
     */
    public function getNextTierProgress(float $subtotal): ?array
    {
        $tiers = $this->getDiscountTiers();
        
        foreach ($tiers as $tier) {
            if ($subtotal < $tier['min_amount']) {
                return [
                    'current_amount' => $subtotal,
                    'next_tier_amount' => $tier['min_amount'],
                    'next_tier_percentage' => $tier['percentage'],
                    'remaining' => $tier['min_amount'] - $subtotal,
                    'progress_percentage' => ($subtotal / $tier['min_amount']) * 100
                ];
            }
        }

        return null; // Ya estás en el nivel más alto
    }
} 