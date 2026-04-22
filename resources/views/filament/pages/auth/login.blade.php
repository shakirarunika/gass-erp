<x-filament-panels::layout.base :livewire="$this">
    <div class="custom-login-container">
        
        <!-- Left Side: Branding -->
        <div class="custom-login-left">
            <!-- Background Decoration Curves (Top Right) -->
            <div class="bg-shape shape-1"></div>
            <div class="bg-shape shape-2"></div>
            <div class="bg-shape shape-3"></div>
            
            <!-- Top Content -->
            <div class="z-10 mt-8">
                <!-- Logo Icon -->
                <div class="logo-icon-container">
                    <svg class="logo-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                
                <h1 class="bank-sampah-title">
                    BANK<br>SAMPAH
                </h1>
                
                <div class="pt-pill">
                    • PT CISARUA MOUNTAIN DAIRY TBK •
                </div>
            </div>
            
            <!-- Bottom Content -->
            <div class="z-10 mb-8">
                <div class="quote-container">
                    <div class="quote-line"></div>
                    <p class="quote-text">"Dasi Aya - Tiada Sisa yang Tak Berdaya"</p>
                </div>
                <p class="hrga-text">HRGA DEPARTMENT</p>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="custom-login-right">
            <div class="form-wrapper">
                <div class="login-form-container">
                    <h2 class="login-title">LOGIN</h2>
                    <p class="login-subtitle">Gunakan Nomor Induk Karyawan untuk akses sistem.</p>
                    
                    <x-filament-panels::form wire:submit="authenticate">
                        {{ $this->form }}

                        <div style="margin-top: 2rem;">
                            <button type="submit" class="custom-submit-btn">
                                MASUK SEKARANG
                            </button>
                        </div>
                    </x-filament-panels::form>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="login-footer">
                <p>
                    &copy; 2024 HRGA DEPARTMENT &bull; MADE WITH <span style="color: #ef4444; font-size: 10px;">❤️</span> BY FAISHAL MUHAMMAD
                </p>
            </div>
        </div>
        
    </div>

    <!-- Custom CSS for layout and styling -->
    <style>
        /* Base Container */
        .custom-login-container {
            display: flex;
            min-height: 100vh;
            background-color: white;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: -1.5rem; /* negate default filament base layout padding if any */
        }
        
        body, html {
            margin: 0;
            padding: 0;
            background: white !important;
        }

        ::selection {
            background-color: #059669;
            color: white;
        }

        /* Left Panel */
        .custom-login-left {
            display: none;
            position: relative;
            overflow: hidden;
            background-color: #059669;
            color: white;
            padding: 4rem;
            flex-direction: column;
            justify-content: space-between;
        }
        @media (min-width: 1024px) {
            .custom-login-left {
                display: flex;
                width: 55%;
            }
        }

        /* Background Shapes */
        .bg-shape {
            position: absolute;
            border-radius: 50%;
        }
        .shape-1 {
            top: -15%;
            right: -10%;
            width: 70%;
            padding-bottom: 70%;
            background-color: rgba(255, 255, 255, 0.03);
        }
        .shape-2 {
            top: -25%;
            right: 5%;
            width: 80%;
            padding-bottom: 80%;
            background-color: rgba(255, 255, 255, 0.02);
        }
        .shape-3 {
            top: 5%;
            right: -20%;
            width: 50%;
            padding-bottom: 50%;
            background-color: rgba(4, 120, 87, 0.4);
        }

        .z-10 { z-index: 10; position: relative; }
        .mt-8 { margin-top: 2rem; }
        .mb-8 { margin-bottom: 2rem; }

        /* Logo Icon */
        .logo-icon-container {
            width: 4rem;
            height: 4rem;
            background-color: rgba(52, 211, 153, 0.2);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .logo-icon {
            width: 2rem;
            height: 2rem;
            color: white;
        }

        /* Titles and Texts */
        .bank-sampah-title {
            font-size: 5.5rem;
            font-weight: 900;
            line-height: 0.9;
            letter-spacing: -0.05em;
            margin-bottom: 1.5rem;
            font-family: 'Arial Black', Impact, sans-serif;
            color: white;
        }

        .pt-pill {
            display: inline-block;
            padding: 0.5rem 1.25rem;
            border-radius: 9999px;
            background-color: #10b981;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.2em;
            margin-top: 0.5rem;
            border: 1px solid rgba(52, 211, 153, 0.4);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            color: white;
        }

        .quote-container {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }
        .quote-line {
            width: 4px;
            height: 3rem;
            background-color: #34d399;
            border-radius: 9999px;
            margin-right: 1.25rem;
        }
        .quote-text {
            font-size: 1.35rem;
            font-style: italic;
            font-weight: 500;
            letter-spacing: 0.025em;
            margin: 0;
            color: white;
        }
        .hrga-text {
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.25em;
            opacity: 0.8;
            margin-top: 1.5rem;
            text-transform: uppercase;
            color: white;
        }

        /* Right Panel */
        .custom-login-right {
            width: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        @media (min-width: 1024px) {
            .custom-login-right {
                width: 45%;
            }
        }

        .form-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        @media (min-width: 640px) {
            .form-wrapper {
                padding: 3rem;
            }
        }

        .login-form-container {
            width: 100%;
            max-width: 24rem; /* 384px */
        }

        .login-title {
            font-size: 2rem;
            font-weight: 900;
            color: #1e293b;
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
            font-family: 'Arial Black', Impact, sans-serif;
            text-align: left;
        }
        .login-subtitle {
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 2.5rem;
            text-align: left;
        }

        /* Form Overrides */
        .fi-form {
            gap: 1.5rem !important;
            display: flex !important;
            flex-direction: column !important;
            grid-template-columns: 1fr !important;
        }
        
        .fi-fo-field-wrp {
            grid-column: span 1 / span 1 !important;
            margin-bottom: 0 !important;
        }

        .fi-fo-field-wrp-label {
            font-size: 0.65rem !important;
            font-weight: 800 !important;
            color: #475569 !important; /* Darker slate */
            letter-spacing: 0.1em !important;
            text-transform: uppercase !important;
            margin-bottom: 0.5rem !important;
            display: block !important;
        }

        .fi-input-wrapper {
            border-radius: 0.75rem !important;
            box-shadow: none !important;
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            transition: all 0.2s ease !important;
            overflow: hidden !important;
            margin: 0 !important;
        }

        .fi-input-wrapper:focus-within {
            background-color: white !important;
            border-color: #10b981 !important;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1) !important;
        }

        .fi-input-wrapper input {
            padding: 0.85rem 1rem !important;
            font-family: 'Courier New', Courier, monospace !important;
            font-size: 0.9rem !important;
            color: #1e293b !important;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            width: 100% !important;
            outline: none !important;
        }

        .fi-input-wrapper input:focus {
            outline: none !important;
            box-shadow: none !important;
            border: none !important;
        }

        .fi-input-wrapper input::placeholder {
            color: #cbd5e1 !important;
            font-family: 'Courier New', Courier, monospace !important;
            font-size: 0.9rem !important;
        }

        /* Checkbox Override */
        .fi-checkbox {
            border-radius: 0.25rem !important;
            border-color: #cbd5e1 !important;
        }
        .fi-checkbox:checked {
            background-color: #059669 !important;
            border-color: #059669 !important;
        }
        .fi-fo-field-wrp-label[for*="remember"] {
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            color: #64748b !important;
            letter-spacing: normal !important;
            text-transform: none !important;
            margin-bottom: 0 !important;
            display: inline-block !important;
        }

        /* Submit Button */
        .custom-submit-btn {
            width: 100%;
            padding: 0.875rem 1rem;
            background-color: #059669;
            color: white;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.1em;
            border-radius: 0.75rem;
            border: none;
            cursor: pointer;
            box-shadow: 0 8px 20px -6px rgba(5, 150, 105, 0.6);
            transition: all 0.2s ease;
            text-transform: uppercase;
            text-align: center;
        }
        .custom-submit-btn:hover {
            background-color: #047857;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.7);
        }

        /* Footer */
        .login-footer {
            width: 100%;
            text-align: center;
            padding-bottom: 2rem;
            padding-left: 1rem;
            padding-right: 1rem;
            position: absolute;
            bottom: 0;
            left: 0;
        }
        .login-footer p {
            font-size: 9px;
            color: #94a3b8;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin: 0;
        }
        
        /* Hide base filament stuff if it bleeds */
        .fi-theme-switcher { display: none !important; }
        .fi-simple-main-ctn { max-width: none !important; padding: 0 !important; box-shadow: none !important; border: none !important; background: transparent !important; }
        main { flex: 1; display: flex; flex-direction: column; }
    </style>
</x-filament-panels::layout.base>
