<x-filament-panels::page.simple>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap');

        /* Background Animation */
        body {
            background: linear-gradient(-45deg, #0284c7, #2563eb, #3b82f6, #60a5fa);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            font-family: 'Outfit', sans-serif !important;
            height: 100vh;
            margin: 0;
            overflow: hidden;
            color: #ffffff;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Bubbles/Blobs for background */
        .shape-blob {
            background: #93c5fd;
            height: 250px;
            width: 250px;
            border-radius: 50%;
            position: absolute;
            filter: blur(90px);
            z-index: -1;
            opacity: 0.7;
            animation: moveBlob 12s infinite alternate ease-in-out;
        }

        .shape-blob.one {
            top: 5%;
            left: 15%;
            background: #38bdf8;
            animation-duration: 14s;
        }

        .shape-blob.two {
            bottom: 5%;
            right: 15%;
            background: #818cf8;
            animation-duration: 18s;
            animation-direction: alternate-reverse;
        }

        @keyframes moveBlob {
            from { transform: translate(0, 0) scale(1); }
            to { transform: translate(60px, -60px) scale(1.3); }
        }

        /* Fix container backgrounds */
        .fi-simple-layout {
            background: transparent !important;
        }
        main {
            background: transparent !important;
        }

        /* Glassmorphism for the main card */
        .fi-simple-main-ctn > div {
            background: rgba(255, 255, 255, 0.15) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.15) !important;
            border-radius: 24px !important;
            padding: 2.5rem !important;
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }

        .fi-simple-main-ctn > div:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 45px 0 rgba(0, 0, 0, 0.2) !important;
        }

        /* Typography */
        .fi-simple-main-ctn h1, 
        .fi-simple-main-ctn h2, 
        .fi-simple-main-ctn span, 
        .fi-simple-main-ctn p, 
        .fi-simple-main-ctn label {
            color: #ffffff !important;
        }
        
        .fi-simple-main-ctn p.text-sm {
            color: #f1f5f9 !important;
        }

        /* Input fields glassmorphism */
        .fi-input-wrapper {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            backdrop-filter: blur(10px) !important;
            transition: all 0.3s ease;
            border-radius: 12px !important;
        }

        .fi-input-wrapper:focus-within {
            background: rgba(255, 255, 255, 0.2) !important;
            border-color: #bfdbfe !important;
            box-shadow: 0 0 0 4px rgba(191, 219, 254, 0.3) !important;
        }

        input {
            color: white !important;
        }
        
        input::placeholder {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active {
            -webkit-background-clip: text;
            -webkit-text-fill-color: white;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px rgba(255, 255, 255, 0.05);
        }

        /* Button styling */
        button[type="submit"] {
            background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 12px !important;
            font-weight: 600 !important;
            letter-spacing: 0.5px !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 4px 15px rgba(29, 78, 216, 0.4) !important;
            position: relative;
            overflow: hidden;
            color: white !important;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px) scale(1.02) !important;
            box-shadow: 0 8px 25px rgba(29, 78, 216, 0.6) !important;
            background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
        }

        button[type="submit"]::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.6s ease;
        }

        button[type="submit"]:hover::after {
            left: 100%;
        }

        /* Checkbox styling */
        .fi-checkbox {
            border-color: rgba(255, 255, 255, 0.4) !important;
            background-color: rgba(255, 255, 255, 0.1) !important;
        }
        .fi-checkbox:checked {
            background-color: #2563eb !important;
            border-color: #2563eb !important;
        }

        /* Links */
        a {
            color: #dbeafe !important;
            transition: all 0.3s ease !important;
        }
        a:hover {
            color: #ffffff !important;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        /* Fix for filament logo */
        .fi-logo {
            font-family: 'Outfit', sans-serif !important;
            font-size: 2rem !important;
            font-weight: 700 !important;
            color: white !important;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            letter-spacing: -0.5px;
        }

        /* Hide dark mode switch on login if any */
        .fi-theme-switcher {
            display: none !important;
        }
    </style>

    <!-- Decorative blobs -->
    <div class="shape-blob one"></div>
    <div class="shape-blob two"></div>

    @if (filament()->hasRegistration())
        <x-slot name="subheading">
            {{ __('filament-panels::pages/auth/login.actions.register.before') }}

            {{ $this->registerAction }}
        </x-slot>
    @endif

    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>
</x-filament-panels::page.simple>
