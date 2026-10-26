<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    /**
     * Display general system settings.
     */
    public function index(): Response
    {
        $settings = Setting::all()->pluck('value', 'key')->all();

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update system settings.
     */
    public function update(Request $request): RedirectResponse
    {
        try {
            $data = $request->validate([
                'bakery_name' => ['required', 'string', 'max:255'],
                'bakery_phone' => ['required', 'string', 'max:50'],
                'bakery_email' => ['nullable', 'email', 'max:100'],
                'bakery_address' => ['required', 'string', 'max:255'],
                'currency_symbol' => ['required', 'string', 'max:10'],
                'receipt_header' => ['nullable', 'string', 'max:255'],
                'receipt_footer' => ['nullable', 'string', 'max:255'],
                'low_stock_threshold_default' => ['required', 'numeric', 'min:1'],
                'sms_alerts_enabled' => ['required', 'in:0,1'],
                'enable_credit_limit_warnings' => ['required', 'in:0,1'],
            ]);

            foreach ($data as $key => $val) {
                Setting::set($key, (string) $val, 'general');
            }

            return redirect()->route('settings.index')
                ->with('success', 'System settings updated successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
