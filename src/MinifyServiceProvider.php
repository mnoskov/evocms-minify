<?php

namespace EvolutionCMS\Minify;

use EvolutionCMS\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class MinifyServiceProvider extends ServiceProvider
{
    protected $namespace = 'minify';

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('minify', Minify::class);

        $this->loadViewsFrom(dirname(__DIR__) . '/views', $this->namespace);

        $this->publishes([dirname(__DIR__) . '/resources/config' => EVO_CORE_PATH . 'custom/config']);
    }

    public function boot()
    {
        Blade::directive('minify', function($args) {
            return "<?php echo evo()->minify->process($args); ?>";
        });
    }
}
