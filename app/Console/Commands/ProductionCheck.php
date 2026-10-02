<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProductionCheck extends Command
{
    protected $signature = 'app:production-check';

    protected $description = 'Verify essential production settings without exposing secrets or changing state';

    public function handle(): int
    {
        $checks = [
            'APP_ENV must be production' => app()->environment('production'),
            'APP_DEBUG must be false' => ! config('app.debug'),
            'APP_KEY must be set' => (bool) config('app.key'),
            'APP_URL must use HTTPS' => str_starts_with(config('app.url', ''), 'https://'),
            'FRONTEND_URL must use HTTPS' => str_starts_with(config('app.frontend_url', ''), 'https://'),
            'Session cookies must be secure' => (bool) config('session.secure'),
            'Session storage must persist' => ! in_array(config('session.driver'), ['array', 'cookie'], true),
            'Queue connection must persist' => ! in_array(config('queue.default'), ['sync', 'null'], true),
            'SMTP or a configured mail provider is required' => ! in_array(config('mail.default'), ['log', 'array'], true),
            'Stateful SPA domains must be configured' => count(config('sanctum.stateful', [])) > 0,
            'Use a dedicated database account' => config('database.connections.mysql.username') !== 'root',
        ];
        foreach ($checks as $message => $passed) {
            $passed ? $this->info('PASS: '.$message) : $this->error('FAIL: '.$message);
        }

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
