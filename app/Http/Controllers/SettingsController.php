<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::getAll();
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:20',
            'company_tax_number' => 'nullable|string|max:100',
            'document_file_prefix' => ['sometimes', 'required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/'],
            'company_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'company_login_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'company_print_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'quotation_print_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'invoice_print_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'purchase_order_print_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'company_favicon' => 'nullable|mimes:png,jpg,jpeg,ico|max:512',
            'theme_color' => 'nullable|in:blue,red,green,purple,orange,teal',
        ]);

        $fields = [
            'company_name',
            'company_email',
            'company_phone',
            'company_tax_number',
            'document_file_prefix',
            'company_address',
            'company_website',
            'currency',
            'timezone',
            'date_format',
            'language',
            'theme_color',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                Setting::set($field, $request->input($field));
            }
        }

        // Upload Logo Sidebar
        if ($request->hasFile('company_logo') && $request->file('company_logo')->isValid()) {
            $oldLogo = Setting::get('company_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            $path = $request->file('company_logo')->store('branding', 'public');
            Setting::set('company_logo', $path);
        }

        // Upload Logo Login
        if ($request->hasFile('company_login_logo') && $request->file('company_login_logo')->isValid()) {
            $oldLogo = Setting::get('company_login_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            $path = $request->file('company_login_logo')->store('branding', 'public');
            Setting::set('company_login_logo', $path);
        }

        // Logo kop surat dapat dibedakan untuk setiap jenis dokumen.
        $documentLogoKeys = [
            'quotation_print_logo',
            'invoice_print_logo',
            'purchase_order_print_logo',
        ];
        foreach ($documentLogoKeys as $key) {
            if ($request->hasFile($key) && $request->file($key)->isValid()) {
                $oldLogo = Setting::get($key);
                if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                    Storage::disk('public')->delete($oldLogo);
                }
                $path = $request->file($key)->store('branding', 'public');
                Setting::set($key, $path);
            }
        }

        // Upload Favicon
        if ($request->hasFile('company_favicon') && $request->file('company_favicon')->isValid()) {
            $oldFavicon = Setting::get('company_favicon');
            if ($oldFavicon && Storage::disk('public')->exists($oldFavicon)) {
                Storage::disk('public')->delete($oldFavicon);
            }
            $path = $request->file('company_favicon')->store('branding', 'public');
            Setting::set('company_favicon', $path);
        }

        // Notification toggles
        $notifFields = ['notif_overdue', 'notif_new_lead', 'notif_deal_won', 'notif_followup', 'notif_stage', 'notif_weekly', 'notif_target'];
        foreach ($notifFields as $field) {
            Setting::set($field, $request->has($field) ? '1' : '0');
        }

        return redirect()->route('settings.index')->with('success', 'Settings berhasil disimpan.');
    }

    public function deleteLogo(Request $request)
    {
        $type = $request->input('type', 'logo');
        $key = match ($type) {
            'favicon' => 'company_favicon',
            'login_logo' => 'company_login_logo',
            'print_logo' => 'company_print_logo',
            'quotation_print_logo' => 'quotation_print_logo',
            'invoice_print_logo' => 'invoice_print_logo',
            'purchase_order_print_logo' => 'purchase_order_print_logo',
            default => 'company_logo',
        };
        $path = Setting::get($key);
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
        Setting::set($key, '');
        return back()->with('success', 'Gambar berhasil dihapus.');
    }
}
