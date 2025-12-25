<div class="flex items-center flex-row gap-3 p-1.5 bg-gray-50 rounded-lg border border-gray-100 w-fit">
    
    {{-- 1. Date & Time (Stacked) --}}
    <div class="font-mono tracking-tighter flex flex-col items-end text-gray-500">
        {{-- Date on Top --}}
        <span class="text-[9px] leading-none uppercase opacity-80">
            {{ now()->format('Y-m-d') }}
        </span>
        {{-- Time on Bottom --}}
        <span class="text-[10px] font-bold leading-tight">
            {{ now()->format('H:i:s') }}
        </span>
    </div>

    {{-- Vertical Divider --}}
    <div class="h-5 w-px bg-gray-300"></div>

    {{-- 2. Status Icons with Overlaid Badges --}}
    <div class="flex flex-row gap-3.5">
        @foreach (\App\Enums\PartStatus::trackerStatus() as $status)
            <div class="flex relative">
                {{-- Icon --}}
                <x-library-icon 
                    :icon="$status->icon()" 
                    class="inline w-6 h-6 {{ $status->iconColor() }}"
                    title="{{ $status->label() }}" 
                />
                
                {{-- Notification-style Badge --}}
                <div class="absolute -top-1.5 -right-2 z-10 min-w-[0.9rem] h-3.5 px-1 flex items-center justify-center bg-white border border-gray-200 shadow-sm text-[9px] font-black text-gray-800 leading-none rounded-full ring-1 ring-gray-50">
                    {{ Arr::get($summary, $status->value, 0) }}
                </div>
            </div>
        @endforeach
    </div>
</div>