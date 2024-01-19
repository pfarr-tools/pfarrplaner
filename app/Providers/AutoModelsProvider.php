<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AutoModelsProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
// auto-register model routes
        foreach (\File::allFiles(app_path('Models')) as $file) {
            if (($file->getExtension() == 'php') && (!Str::contains($file->getPathname(), 'Abstract'))) {
                $className = substr('App\\Models\\' . Str::replace('/', '\\', $file->getRelativePathname()), 0, -4);
                if (method_exists($className, 'registerContracts')) {
                    $className::registerContracts($this->app);
                }
            }
        };
    }
}
