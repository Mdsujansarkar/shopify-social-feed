<?php

namespace App\Jobs;

use App\Models\InstagramAccount;
use App\Services\InstagramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncInstagramMediaJob implements ShouldQueue
{
    use Queueable;

    protected InstagramAccount $instagramAccount;
    protected int $limit;

    /**
     * Create a new job instance.
     */
    public function __construct(InstagramAccount $instagramAccount, int $limit = 100)
    {
        $this->instagramAccount = $instagramAccount;
        $this->limit = $limit;
    }

    /**
     * Execute the job.
     */
    public function handle(InstagramService $instagramService): void
    {
        try {
            $synced = $instagramService->syncMedia($this->instagramAccount);

            Log::info('Instagram media synced successfully', [
                'shop_id' => $this->instagramAccount->shop_id,
                'instagram_account_id' => $this->instagramAccount->id,
                'count' => count($synced),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync Instagram media', [
                'shop_id' => $this->instagramAccount->shop_id,
                'instagram_account_id' => $this->instagramAccount->id,
                'error' => $e->getMessage(),
            ]);

            // Release the job back to the queue with a delay
            $this->release(300); // 5 minutes
        }
    }
}
