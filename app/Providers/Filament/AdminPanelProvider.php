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
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

// Import Widget Sesuai Request Management
use App\Filament\Widgets\StatsOverview;
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
            ->favicon(asset('images/favicon.ico'))
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
                // 1 & 2. Total Nilai Aset & Jumlah Barang (Stats Overview)
                StatsOverview::class,

                // 3. Valuasi per Kategori (Chart)
                CategoryValueChart::class,

                // 4. Barang di Bawah Minimal Stok (Table)
                LatestLowStockItems::class,

                // 5. Barang Mati / Tidak Bergerak (Table)
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
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn() => Blade::render(<<<HTML
                <div class="flex items-center justify-center w-full p-4 text-xs font-medium text-gray-500 bg-white dark:bg-gray-900 dark:text-gray-400 border-t border-gray-200 dark:border-gray-800">
                    <span>
                        &copy; {{ date('Y') }} General Affairs Stock System. Built with <span class="text-red-500">❤</span> by 
                        <a href="https://www.instagram.com/faishalma_" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:underline">Faishal Muhammad</a>.
                    </span>
                </div>
            HTML)
            );
    }
}
