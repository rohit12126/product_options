<?php

namespace App\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;

class ExcopyMailerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $configuration;
    public $mailable;

    /**
     * Create a new job instance.
     *
     * @param array $siteConfig
     * @param Mailable $mailable
     */
    public function __construct(array $siteConfig, Mailable $mailable)
    {
        // new config for white labels
        $configuration = config('mail');
        $configuration['host'] = config("app.server_config.{$siteConfig['smtp_config']}.host");
        $configuration['port'] = config("app.server_config.{$siteConfig['smtp_config']}.port");
        $configuration['username'] = config("app.server_config.{$siteConfig['smtp_config']}.username");
        $configuration['password'] = config("app.server_config.{$siteConfig['smtp_config']}.password");
        $this->configuration = $configuration;
        $this->mailable = $mailable;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $mailer = app()->makeWith('excopy.mailer', $this->configuration);
        $mailer->send($this->mailable);
    }
}