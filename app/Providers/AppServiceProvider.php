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
        //
        // 'web' MUST be listed explicitly here: setUpdateRoute() fully
        // replaces Livewire's default route registration rather than
        // extending it, so without 'web' this route gets no session
        // middleware at all — every request runs with a fresh, disconnected
        // session that never reaches the response as a cookie. That doesn't
        // break isolated interactions (search-as-you-type, filters) since
        // they don't depend on state surviving to a *different* request,
        // but it silently breaks anything that must persist across
        // requests — most visibly login: Auth::attempt() succeeds and
        // redirectIntended() fires, but the new session cookie never gets
        // sent back, so the very next page load looks logged out again.
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)->middleware('web', 'throttle:60,1');
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
