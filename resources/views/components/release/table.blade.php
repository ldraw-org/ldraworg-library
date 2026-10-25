@props(['release'])
@use("App\Enums\LibraryIcon")
@php
    $isLatest = $release->isLatest();
    $hasImage = $release->hasMedia('image');
    $link = 'inline-flex items-start gap-2 text-blue-600 hover:text-blue-800 hover:underline visited:text-purple-600';
@endphp
<section class="w-full max-w-5xl overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
    <header class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-gray-200 bg-gray-50 p-4">
        <h2 class="text-lg font-bold">LDraw.org Library Update {{ $release->name }}</h2>
        @if($isLatest)
            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">
                Latest release
            </span>
        @endif
    </header>

    <div @class([
        'grid divide-y divide-gray-200 lg:divide-x lg:divide-y-0',
        'lg:grid-cols-[auto_1fr_1fr]' => $hasImage,
        'lg:grid-cols-2' => ! $hasImage,
    ])>
        @if($hasImage)
            <div class="flex items-start justify-center p-4">
                <img class="rounded" src="{{ $release->getFirstMediaUrl('image', 'thumb') }}"
                     alt="Preview of LDraw.org Library Update {{ $release->name }}">
            </div>
        @endif

        <div class="flex flex-col gap-3 p-4">
            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500">Release Notes</h3>
            <div class="text-gray-800">{{ $release->notesHtml() }}</div>

            @if($release->notes_url())
                <a class="{{ $link }} font-semibold" href="{{ $release->notes_url() }}">
                    <x-library-icon :icon="LibraryIcon::FileDocumentOutline" class="w-5" aria-hidden="true"/>
                    View full notes file
                </a>
            @endif

            @if($release->hasMedia('view'))
                <div>
                    <a class="{{ $link }} font-semibold" href="{{ route('part-update.view', $release) }}">
                        <x-library-icon :icon="LibraryIcon::CubeOutline" class="w-5" aria-hidden="true"/>
                        Preview parts in update
                    </a>
                    <p class="ml-7 text-sm text-gray-500">(graphics-intensive page)</p>
                </div>
            @endif
        </div>

        <div class="flex flex-col gap-3 p-4">
            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500">Download Links</h3>

            @if($isLatest)
                <a class="{{ $link }}" data-pan="update-complete-zip-{{ $release->short }}"
                   href="{{ asset('library/updates/complete.zip') }}">
                    <x-library-icon :icon="LibraryIcon::DownloadZip" class="w-5" aria-hidden="true"/>
                    Complete LDraw.org Library Zip archive (complete.zip)
                </a>

                @if($release->hasWindowsInstaller())
                    <a class="{{ $link }}" data-pan="update-complete-exe-{{ $release->short }}"
                       href="{{ asset('library/updates/LDrawParts.exe') }}">
                        <x-library-icon :icon="LibraryIcon::MicrosoftWindows" class="w-5" aria-hidden="true"/>
                        Complete LDraw.org Library Windows installer (LDrawParts.exe)
                    </a>
                @else
                    <span class="inline-flex items-start gap-2 text-gray-500">
                        <x-library-icon :icon="LibraryIcon::MicrosoftWindows" class="w-5" aria-hidden="true"/>
                        Complete LDraw.org Library Windows installer (coming soon)
                    </span>
                @endif
            @endif

            <a class="{{ $link }}" data-pan="update-zip-{{ $release->short }}"
               href="{{ asset('library/updates/lcad' . $release->short . '.zip') }}">
                <x-library-icon :icon="LibraryIcon::Download" class="w-5" aria-hidden="true"/>
                Zip archive of the updated files ({{ $release->short }}.zip)
            </a>
        </div>
    </div>
</section>
