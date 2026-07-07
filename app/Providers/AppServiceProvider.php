<?php

namespace App\Providers;

use App\Models\CareerStatus;
use App\Observers\CareerStatusObserver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Throttle Livewire's AJAX update endpoint — the public student
        // search page has no auth to rely on, so this is the backstop
        // against scripted rapid-fire search requests.
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)->middleware('throttle:60,1');
        });

        // Notify admins the moment a student's career status is recorded.
        CareerStatus::observe(CareerStatusObserver::class);

        // Force https:// in every generated URL (asset(), route(), etc.) in
        // production, even if a proxy hop in front of the app reports plain
        // HTTP — belt-and-suspenders alongside the TrustProxies config in
        // bootstrap/app.php, which handles isSecure()/session cookies.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // App\Listeners\LogNotificationSent and LogNotificationFailed are
        // wired up automatically by Laravel's event auto-discovery (their
        // handle() methods type-hint NotificationSent/NotificationFailed) —
        // registering them again here with Event::listen() would fire each
        // one twice per notification.
    }
}
