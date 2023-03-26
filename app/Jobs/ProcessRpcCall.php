<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ProcessRpcCall implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $url;
    protected array $requestData;

    /**
     * Create a new job instance.
     *
     * @param string $url
     * @param array $requestData
     * @return void
     */
    public function __construct(string $url, array $requestData)
    {
        $this->url = $url;
        $this->requestData = $requestData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // Send the RPC call using Laravel's HTTP client
        Http::post($this->url, $this->requestData);
    }
}
