<x-filament-panels::layout.base :livewire="$this">
    <div class="flex min-h-screen bg-white font-sans selection:bg-[#059669] selection:text-white">
        
        <!-- Left Side: Branding -->
        <div class="hidden lg:flex lg:flex-col lg:w-[55%] relative overflow-hidden bg-[#059669] text-white p-16 justify-between">
            <!-- Background Decoration Curves (Top Right) -->
            <div class="absolute top-[-15%] right-[-10%] w-[70%] h-[70%] rounded-full bg-white opacity-[0.03]"></div>
            <div class="absolute top-[-25%] right-[5%] w-[80%] h-[80%] rounded-full bg-white opacity-[0.02]"></div>
            <div class="absolute top-[5%] right-[-20%] w-[50%] h-[50%] rounded-full bg-[#047857] opacity-40"></div>
            
            <!-- Top Content -->
            <div class="z-10 mt-8">
                <!-- Logo Icon -->
                <div class="w-16 h-16 bg-[#34d399]/20 rounded-2xl flex items-center justify-center mb-8 border border-white/10 shadow-sm backdrop-blur-md">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                
                <h1 class="text-[5.5rem] font-black leading-[0.9] tracking-tighter mb-6" style="font-family: 'Arial Black', Impact, sans-serif;">
                    BANK<br>SAMPAH
                </h1>
                
                <div class="inline-block px-5 py-2 rounded-full bg-[#10b981] text-[11px] font-bold tracking-[0.2em] mt-2 border border-[#34d399]/40 shadow-sm">
                    • PT CISARUA MOUNTAIN DAIRY TBK •
                </div>
            </div>
            
            <!-- Bottom Content -->
            <div class="z-10 mb-8">
                <div class="flex items-center space-x-5 mb-5">
                    <div class="w-1 h-12 bg-[#34d399] rounded-full"></div>
                    <p class="text-[1.35rem] italic font-medium tracking-wide">"Dasi Aya - Tiada Sisa yang Tak Berdaya"</p>
                </div>
                <p class="text-[10px] font-bold tracking-[0.25em] opacity-80 mt-6 uppercase">HRGA DEPARTMENT</p>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="w-full lg:w-[45%] flex flex-col relative">
            <div class="flex-grow flex items-center justify-center p-8 sm:p-12">
                <div class="w-full max-w-sm">
                    
                    <h2 class="text-[2rem] font-black text-[#1e293b] mb-2 tracking-tight" style="font-family: 'Arial Black', Impact, sans-serif;">LOGIN</h2>
                    <p class="text-[13px] text-gray-500 font-semibold mb-10">Gunakan Nomor Induk Karyawan untuk akses sistem.</p>
                    
                    <x-filament-panels::form wire:submit="authenticate">
                        {{ $this->form }}

                        <div class="mt-8">
                            <button type="submit" class="w-full py-3.5 px-4 bg-[#059669] hover:bg-[#047857] text-white text-xs font-bold tracking-widest rounded-xl shadow-[0_8px_20px_-6px_rgba(5,150,105,0.6)] transition-all duration-200 transform hover:-translate-y-0.5">
                                MASUK SEKARANG
                            </button>
                        </div>
                    </x-filament-panels::form>
                    
                </div>
            </div>
            
            <!-- Footer -->
            <div class="w-full text-center pb-8 px-4">
                <p class="text-[9px] text-gray-400 font-bold tracking-widest uppercase">
                    &copy; 2024 HRGA DEPARTMENT &bull; MADE WITH <span class="text-red-500 text-[10px]">❤️</span> BY FAISHAL MUHAMMAD
                </p>
            </div>
        </div>
        
    </div>

    <!-- Custom CSS for form overrides to match design exactly -->
    <style>
        /* Form container spacing */
        .fi-form {
            gap: 1.5rem !important;
        }

        /* Label styling */
        .fi-fo-field-wrp-label {
            font-size: 0.65rem !important;
            font-weight: 800 !important;
            color: #94a3b8 !important;
            letter-spacing: 0.1em !important;
            text-transform: uppercase !important;
            margin-bottom: 0.5rem !important;
            display: block !important;
        }

        /* Input styling */
        .fi-input-wrapper {
            border-radius: 0.75rem !important;
            box-shadow: none !important;
            background-color: #f8fafc !important;
            border: 1px solid transparent !important;
            transition: all 0.2s ease !important;
            overflow: hidden !important;
        }

        .fi-input-wrapper:focus-within {
            background-color: white !important;
            border-color: #10b981 !important;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1) !important;
        }

        input {
            padding: 0.85rem 1rem !important;
            font-family: 'Courier New', Courier, monospace !important; /* To match the 'Contoh: 04.1234' look */
            font-size: 0.9rem !important;
            color: #334155 !important;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            width: 100% !important;
        }

        input:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        input::placeholder {
            color: #cbd5e1 !important;
            font-family: 'Courier New', Courier, monospace !important;
            font-size: 0.9rem !important;
        }

        /* Checkbox / Remember me */
        .fi-checkbox {
            border-radius: 0.25rem !important;
            border-color: #cbd5e1 !important;
        }
        .fi-checkbox:checked {
            background-color: #059669 !important;
            border-color: #059669 !important;
        }
        .fi-fo-field-wrp-label[for="data.remember"] {
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            color: #64748b !important;
            letter-spacing: normal !important;
            text-transform: none !important;
        }
    </style>
</x-filament-panels::layout.base>
