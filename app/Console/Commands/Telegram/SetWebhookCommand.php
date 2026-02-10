<?php

namespace App\Console\Commands\Telegram;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class SetWebhookCommand extends Command
{
    protected $signature = 'telegram:webhook:set
                            {--remove : Remove webhook instead of setting}';

    protected $description = 'Set or remove Telegram bot webhook';

    public function handle(): int
    {
        if ($this->option('remove')) {
            Telegram::removeWebhook();
            $this->info('Webhook removed successfully');

            return 0;
        }

        $url = config('telegram.webhook_url');

        if (! $url) {
            $this->error('TELEGRAM_WEBHOOK_URL not configured');

            return 1;
        }

        Telegram::setWebhook(['url' => $url]);

        $this->info("Webhook set to: {$url}");

        // Get webhook info
        $info = Telegram::getWebhookInfo();
        $this->table(
            ['Property', 'Value'],
            [
                ['URL', $info->url],
                ['Has Custom Cert', $info->has_custom_certificate ? 'Yes' : 'No'],
                ['Pending Updates', $info->pending_update_count],
                ['IP Address', $info->ip_address ?? 'N/A'],
            ]
        );

        return 0;
    }
}
