<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * مراقبة الـ Queue Jobs والـ Compensation Jobs
 */
class QueueMonitorController extends Controller
{
    /**
     * عرض حالة الـ Queue Jobs
     */
    public function queueStatus()
    {
        try {
            // جلب الـ Jobs الموجودة في الـ Queue
            $pendingJobs = DB::table('jobs')
                ->select('id', 'queue', 'payload', 'attempts', 'created_at')
                ->orderBy('id', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    return [
                        'id' => $job->id,
                        'queue' => $job->queue,
                        'job_class' => $payload['displayName'] ?? 'Unknown',
                        'attempts' => $job->attempts,
                        'created_at' => $job->created_at,
                    ];
                });

            // جلب الـ Failed Jobs
            $failedJobs = DB::table('failed_jobs')
                ->select('id', 'connection', 'queue', 'payload', 'exception', 'failed_at')
                ->orderBy('id', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    return [
                        'id' => $job->id,
                        'queue' => $job->queue,
                        'job_class' => $payload['displayName'] ?? 'Unknown',
                        'exception' => substr($job->exception, 0, 200) . '...',
                        'failed_at' => $job->failed_at,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'pending_jobs' => $pendingJobs,
                    'failed_jobs' => $failedJobs,
                    'pending_count' => $pendingJobs->count(),
                    'failed_count' => $failedJobs->count(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get queue status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'فشل جلب حالة الـ Queue: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض نتائج الـ Compensation Jobs
     */
    public function compensationResults(Request $request)
    {
        try {
            $batchId = $request->get('batch_id');
            $logsPath = storage_path('logs');

            // جلب كل ملفات النتائج
            $resultFiles = glob($logsPath . '/compensation_result_*.json');
            $auditFiles = glob($logsPath . '/compensation_audit_*.json');

            $results = [];

            // قراءة آخر 10 نتائج
            foreach (array_slice($resultFiles, -10) as $file) {
                $data = json_decode(file_get_contents($file), true);
                $results[] = [
                    'file' => basename($file),
                    'batch_id' => $data['results']['batch_id'] ?? 'unknown',
                    'completed_at' => $data['completed_at'] ?? null,
                    'total_users' => $data['results']['total_users'] ?? 0,
                    'processed' => $data['results']['processed'] ?? 0,
                    'failed' => $data['results']['failed'] ?? 0,
                    'total_amount' => $data['results']['total_amount'] ?? 0,
                ];
            }

            // لو في batch_id محدد، جيب تفاصيله
            $specificResult = null;
            if ($batchId) {
                $resultFile = $logsPath . "/compensation_result_{$batchId}.json";
                $auditFile = $logsPath . "/compensation_audit_{$batchId}.json";

                if (file_exists($resultFile)) {
                    $specificResult = json_decode(file_get_contents($resultFile), true);
                }

                if (file_exists($auditFile)) {
                    $specificResult['audit'] = json_decode(file_get_contents($auditFile), true);
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'recent_results' => $results,
                    'specific_result' => $specificResult,
                    'total_result_files' => count($resultFiles),
                    'total_audit_files' => count($auditFiles),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get compensation results', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'فشل جلب نتائج التعويض: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض صفحة HTML للمراقبة
     */
    public function monitorPage()
    {
        return view('admin.queue-monitor');
    }
}
