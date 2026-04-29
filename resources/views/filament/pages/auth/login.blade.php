<div class="auliachem-login-split">
    {{-- ═══════════ BRANDING SIDE ═══════════ --}}
    <div class="auliachem-login-brand">
        <div class="auliachem-login-brand-content">
            <div class="auliachem-login-logo">
                <div class="auliachem-login-logo-mark">⚗</div>
                <span>Auliachem CRM</span>
            </div>
        </div>

        <div class="auliachem-login-brand-content auliachem-login-tagline">
            <h1>Kelola sales,<br>tumbuhkan bisnis.</h1>
            <p>Platform CRM terintegrasi untuk mengelola customer, supplier, sales pipeline, dan transaksi dalam satu tempat.</p>

            <div class="auliachem-login-features">
                <div class="auliachem-login-feature">
                    <div class="auliachem-login-feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <span>Pipeline 5-stage: Identify, Approach, Follow Up, Close, Maintain</span>
                </div>
                <div class="auliachem-login-feature">
                    <div class="auliachem-login-feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <span>Real-time analytics: Revenue, Gross Profit, Nett Profit</span>
                </div>
                <div class="auliachem-login-feature">
                    <div class="auliachem-login-feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <span>Export laporan ke Excel kapan saja</span>
                </div>
            </div>
        </div>

        <div class="auliachem-login-footer">
            © {{ date('Y') }} Auliachem. Internal use only.
        </div>
    </div>

    {{-- ═══════════ FORM SIDE ═══════════ --}}
    <div class="auliachem-login-form-side">
        <div class="auliachem-login-form-inner">
            <h2>Selamat datang kembali</h2>
            <p class="subtitle">Masuk untuk melanjutkan ke dashboard CRM Anda.</p>

            <x-filament-panels::form wire:submit="authenticate">
                {{ $this->form }}

                <x-filament-panels::form.actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />
            </x-filament-panels::form>
        </div>
    </div>
</div>
