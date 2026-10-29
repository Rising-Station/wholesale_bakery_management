<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Throwable;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $shopName = 'Mominul Bekari';
        $bakeryPhone = '+880 1711-000004';
        $bakeryEmail = 'sales@mominulbekari.com';
        $bakeryAddress = 'Central Distribution Hub, Commercial Road Zone, Dhaka';
        $currencySymbol = '৳';

        try {
            $shopName = Setting::get('bakery_name', 'Mominul Bekari');
            $bakeryPhone = Setting::get('bakery_phone', '+880 1711-000004');
            $bakeryEmail = Setting::get('bakery_email', 'sales@mominulbekari.com');
            $bakeryAddress = Setting::get('bakery_address', 'Central Distribution Hub, Commercial Road Zone, Dhaka');
            $currencySymbol = Setting::get('currency_symbol', '৳');
        } catch (Throwable) {
            // Fallback if settings table is not yet migrated
        }

        return [
            ...parent::share($request),
            'shop_name' => $shopName,
            'settings' => [
                'bakery_name' => $shopName,
                'bakery_phone' => $bakeryPhone,
                'bakery_email' => $bakeryEmail,
                'bakery_address' => $bakeryAddress,
                'currency_symbol' => $currencySymbol,
            ],
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
        ];
    }
}
