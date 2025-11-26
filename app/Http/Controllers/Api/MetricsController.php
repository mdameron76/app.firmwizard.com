<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Firm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetricsController extends Controller
{
    /**
     * Get metrics summary for a firm
     */
    public function summary(Request $request, Firm $firm): JsonResponse
    {
        $metricTypes = $request->input('types', [
            'leads_monthly',
            'visibility_score',
            'gbp_health',
            'ads_coverage',
        ]);

        $summary = [];

        foreach ($metricTypes as $type) {
            $latestDaily = $firm->metricsDaily()
                ->where('metric_type', $type)
                ->orderBy('date', 'desc')
                ->first();

            if ($latestDaily) {
                $summary[$type] = [
                    'current_value' => $latestDaily->metric_value,
                    'date' => $latestDaily->date,
                    'meta' => $latestDaily->meta_json,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get time series metrics for a firm
     */
    public function timeseries(Request $request, Firm $firm): JsonResponse
    {
        $validated = $request->validate([
            'metric_type' => 'required|string',
            'period' => 'nullable|string|in:daily,monthly',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:365',
        ]);

        $period = $validated['period'] ?? 'daily';
        $limit = $validated['limit'] ?? 30;

        $query = $period === 'daily'
            ? $firm->metricsDaily()
            : $firm->metricsMonthly();

        $query->where('metric_type', $validated['metric_type'])
            ->orderBy($period === 'daily' ? 'date' : 'month', 'desc')
            ->limit($limit);

        if (isset($validated['start_date'])) {
            $column = $period === 'daily' ? 'date' : 'month';
            $query->where($column, '>=', $validated['start_date']);
        }

        if (isset($validated['end_date'])) {
            $column = $period === 'daily' ? 'date' : 'month';
            $query->where($column, '<=', $validated['end_date']);
        }

        $metrics = $query->get();

        return response()->json([
            'success' => true,
            'data' => [
                'metric_type' => $validated['metric_type'],
                'period' => $period,
                'data_points' => $metrics,
            ],
        ]);
    }
}
