<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\View\PanelsRenderHook; // Pastikan ini ada
use Illuminate\Support\Facades\Blade; // Pastikan ini ada
use Illuminate\Support\HtmlString;

// Import Widget
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\StockOpnameTrendChart;
use App\Filament\Widgets\CategoryValueChart;
use App\Filament\Widgets\LatestLowStockItems;
use App\Filament\Widgets\DeadStockItems;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile()
            ->brandName('G.A.S.S. | GA Stock System')
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Slate,
            ])
            ->font('Inter')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                StatsOverview::class,
                StockOpnameTrendChart::class,
                CategoryValueChart::class,
                LatestLowStockItems::class,
                DeadStockItems::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            // --- INI BAGIAN YANG DITAMBAHKAN ---
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn(): HtmlString => new HtmlString('
                    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
                ')
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn() => Blade::render('
                    <div class="flex items-center justify-center w-full p-4 text-xs font-medium text-gray-500 bg-white dark:bg-gray-900 dark:text-gray-400 border-t border-gray-200 dark:border-gray-800">
                        <span>
                            &copy; {{ date("Y") }} General Affairs Stock System. Built with <span class="text-red-500">❤</span> by 
                            <a href="https://www.instagram.com/faishalma_" target="_blank" class="text-primary-600 hover:underline">Faishal Muhammad</a>.
                        </span>
                    </div>
                ')
            );
    }
}
