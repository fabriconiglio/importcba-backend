<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

class SiteConfigController extends Controller
{
    /**
     * Get bank details for payments
     */
    public function getBankDetails(): JsonResponse
    {
        try {
            $bankDetails = [
                'bank_name' => SiteSetting::where('key', 'bank_name')->value('value') ?? 'Banco Santander Río',
                'account_type' => SiteSetting::where('key', 'account_type')->value('value') ?? 'Cuenta Corriente',
                'account_number' => SiteSetting::where('key', 'account_number')->value('value') ?? '472-358294/7',
                'cbu' => SiteSetting::where('key', 'cbu')->value('value') ?? '0720472388000035829475',
                'account_holder' => SiteSetting::where('key', 'account_holder')->value('value') ?? 'Import CBA',
                'cuit' => SiteSetting::where('key', 'cuit')->value('value') ?? '30-71569842-3',
                'whatsapp' => SiteSetting::where('key', 'whatsapp_number')->value('value') ?? '+54 9 351 808-4713',
            ];

            return response()->json([
                'success' => true,
                'data' => $bankDetails,
                'message' => 'Datos bancarios obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos bancarios: ' . $e->getMessage()
            ], 500);
        }
    }
}