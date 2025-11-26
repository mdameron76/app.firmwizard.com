<?php

namespace App\Jobs;

use App\Models\AiJob;
use App\Services\AgentFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessAiJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AiJob $aiJob
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update status to running
            $this->aiJob->update([
                'status' => 'running',
                'started_at' => now(),
            ]);

            // Load firm with profile
            $firm = $this->aiJob->firm()->with('activeAiFirmProfile')->first();
            
            if (!$firm || !$firm->activeAiFirmProfile) {
                throw new \Exception('Firm or active profile not found');
            }

            // Get agent instance
            $agent = AgentFactory::make($this->aiJob->job_type);

            // Prepare input data
            $input = array_merge(
                [
                    'firm_profile' => $firm->activeAiFirmProfile->profile_json,
                    'firm' => [
                        'id' => $firm->id,
                        'name' => $firm->name,
                        'website_url' => $firm->website_url,
                    ],
                ],
                $this->aiJob->input_json ?? []
            );

            // Execute agent
            $output = $agent->execute($input);

            // Update job with results
            $this->aiJob->update([
                'status' => 'completed',
                'output_json' => $output,
                'finished_at' => now(),
            ]);

            Log::info("AI Job {$this->aiJob->id} ({$this->aiJob->job_type}) completed successfully");

        } catch (\Exception $e) {
            // Update job with error
            $this->aiJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            Log::error("AI Job {$this->aiJob->id} ({$this->aiJob->job_type}) failed: " . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        $this->aiJob->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'finished_at' => now(),
        ]);
    }
}
