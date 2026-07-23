<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Health check endpoint for monitoring services
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function check()
    {
        $status = [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'services' => []
        ];

        try {
            // Check database connection
            DB::connection()->getPdo();
            $status['services']['database'] = [
                'status' => 'ok',
                'driver' => config('database.default')
            ];
        } catch (\Exception $e) {
            $status['services']['database'] = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            $status['status'] = 'error';
        }

        try {
            // Check cache
            cache()->put('health_check', true, now()->addMinute());
            $status['services']['cache'] = ['status' => 'ok'];
        } catch (\Exception $e) {
            $status['services']['cache'] = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            $status['status'] = 'error';
        }

        try {
            // Check filesystem
            if (!is_writable(storage_path('logs'))) {
                throw new \Exception('Storage logs directory not writable');
            }
            $status['services']['filesystem'] = ['status' => 'ok'];
        } catch (\Exception $e) {
            $status['services']['filesystem'] = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            $status['status'] = 'error';
        }

        $statusCode = $status['status'] === 'ok' ? 200 : 503;

        return response()->json($status, $statusCode);
    }
}
