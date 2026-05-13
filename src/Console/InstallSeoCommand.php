<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Console;

use Illuminate\Console\Command;

final class InstallSeoCommand extends Command
{
    protected $signature = 'seo:install';

    protected $description = 'Publish IvanBaric SEO package resources.';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'seo-config']);
        $this->call('vendor:publish', ['--tag' => 'seo-migrations']);
        $this->call('vendor:publish', ['--tag' => 'seo-views']);

        $this->line('');
        $this->info('IvanBaric SEO installed.');
        $this->line('Run: php artisan migrate');
        $this->line('Use: use IvanBaric\\Seo\\Concerns\\HasSeo; on any Eloquent model.');
        $this->line('Render: {!! seo()->render($model) !!} or <x-seo::meta :model="$model" />');
        $this->line('Context: tenant and locale data are inherited from IvanBaric Corexis resolvers.');

        return self::SUCCESS;
    }
}
