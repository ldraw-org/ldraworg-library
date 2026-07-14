<div class="flex flex-wrap items-center gap-x-3 gap-y-2 p-2 bg-gray-50 rounded-lg border border-gray-100 w-fit max-w-full">

    <div class="font-mono tracking-tighter flex flex-col items-end text-gray-500 shrink-0">
        <span class="text-xs leading-none uppercase opacity-80">
            {{ now()->format('Y-m-d') }}
        </span>
        <span class="text-sm font-bold leading-tight">
            {{ now()->format('H:i:s') }}
        </span>
    </div>

    <div class="h-5 w-px bg-gray-300 shrink-0"></div>

    <div class="flex flex-wrap gap-5">
        @foreach (\App\Enums\PartStatus::trackerStatus() as $status)
            <div class="flex relative shrink-0">
                <x-library-icon
                    :icon="$status->icon()"
                    class="inline w-7 h-7 {{ $status->iconColor() }}"
                    title="{{ $status->label() }}"
                />

                <div class="absolute -top-2.5 -right-4 z-10 min-w-[1.25rem] h-[1.125rem] px-1.5 flex items-center justify-center bg-white border border-gray-200 shadow-sm text-xs font-black text-gray-800 leading-none rounded-full ring-1 ring-gray-50">
                    {{ Arr::get($summary, $status->value, 0) }}
                </div>
            </div>
        @endforeach
    </div>
</div>
