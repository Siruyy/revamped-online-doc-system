<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MailDiagnoseCommand extends Command
{
    protected $signature = 'mail:diagnose';

    protected $description = 'Check mail and queue configuration without sending email or exposing credentials';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');
        $transport = (string) config('mail.mailers.'.$mailer.'.transport', $mailer);
        $queue = (string) config('queue.default');
        $this->info('Mailer: '.$mailer.' ('.$transport.')');
        $this->line('Queue connection: '.$queue);
        $this->line('Configuration cached: '.(app()->configurationIsCached() ? 'yes' : 'no'));
        $blocked = false;

        if (in_array($transport, ['log', 'array'], true)) {
            $this->error('This mailer does not deliver to inboxes. Configure a delivery mailer on the web app and queue worker.');
            $blocked = true;
        }

        if ($transport === 'resend') {
            $hasKey = filled(config('services.resend.key'));
            $this->line('Resend API key: '.($hasKey ? 'configured' : 'missing'));
            $blocked = $blocked || ! $hasKey;
        }
        $sender = (string) config('mail.from.address');

        if (! filter_var($sender, FILTER_VALIDATE_EMAIL) || str_ends_with($sender, '@example.com')) {
            $this->warn('Configure MAIL_FROM_ADDRESS with an address approved by your mail provider.');
            $blocked = true;
        }

        if (config('queue.connections.'.$queue.'.driver') === 'database') {
            try {
                $connection = config('queue.connections.'.$queue.'.connection');
                $table = (string) config('queue.connections.'.$queue.'.table', 'jobs');
                $this->line('Pending jobs (all queues): '.DB::connection($connection)->table($table)->count());

                if (in_array(config('queue.failed.driver'), ['database', 'database-uuids'], true)) {
                    $this->line('Failed jobs: '.DB::connection(config('queue.failed.database'))
                        ->table((string) config('queue.failed.table', 'failed_jobs'))->count());
                }
            } catch (Throwable) {
                $this->warn('Queue counts unavailable. Check the database connection and migrations.');
                $blocked = true;
            }
        }

        if ($queue !== 'sync') {
            $this->line('A persistent queue worker is required. After changing mail settings, refresh cached config and restart workers.');
        }
        $this->line('No email sent. This check does not verify worker health, provider acceptance, or inbox delivery.');

        return $blocked ? self::FAILURE : self::SUCCESS;
    }
}
