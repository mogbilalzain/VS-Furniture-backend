<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ImageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the ImageUrlHelper
        $this->app->singleton('image-url-helper', function () {
            return new \App\Helpers\ImageUrlHelper();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Auto-load helper files
        if (file_exists(app_path('Helpers/ImageUrlHelper.php'))) {
            require_once app_path('Helpers/ImageUrlHelper.php');
        }
    }
}
