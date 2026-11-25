@use('App\Enums\LibraryIcon')
@use('App\Enums\ReleaseStage')
<div @if ($this->run?->isRunning()) wire:poll.5s.visible @endif>
    @if ($run = $this->run)
        <div class="flex flex-col gap-2 bg-white rounded-lg p-4">
            <div class="text-lg font-bold">
                @if ($run->hasFailed())
                    Parts release failed
                @elseif ($run->isRunning())
                    Parts release in progress
                @else
                    Parts release complete
                @endif
                @if ($run->release)
                    ({{ $run->release->name }})
                @endif
            </div>
            <div class="text-sm text-gray-600">
                Started {{ $run->created_at->diffForHumans() }} by {{ $run->user->name }}.
                Last update {{ $run->updated_at->diffForHumans() }}.
            </div>
            <ol class="flex flex-col gap-1">
                @foreach (ReleaseStage::cases() as $stage)
                    @continue($stage === ReleaseStage::Queued || $stage === ReleaseStage::Complete)
                    @php($isDone = $stage->isBefore($run->stage))
                    @php($isCurrent = $stage === $run->stage)
                    <li wire:key="release-stage-{{ $stage->value }}" @class([
                        'flex items-center gap-2',
                        'text-gray-400' => !$isDone && !$isCurrent,
                        'font-bold' => $isCurrent,
                    ])>
                        @if ($isDone)
                            <x-library-icon :icon="LibraryIcon::Certify" class="w-5" color="fill-green-600" />
                        @elseif ($isCurrent && $run->hasFailed())
                            <x-library-icon :icon="LibraryIcon::Error" class="w-5" color="fill-red-600" />
                        @elseif ($isCurrent)
                            <x-library-icon :icon="LibraryIcon::Refresh" class="w-5 animate-spin" color="fill-blue-600" />
                        @else
                            <span class="w-5"></span>
                        @endif
                        {{ $stage->label() }}
                        @if ($isCurrent && !is_null($run->progress_total))
                            ({{ $run->progress_current }} of {{ $run->progress_total }})
                        @endif
                    </li>
                @endforeach
            </ol>
            @if ($run->hasFailed())
                <x-message type="error">
                    <x-slot:header>{{ $run->stage->label() }} failed</x-slot:header>
                    <div>{{ $run->error }}</div>
                    <div class="mt-2">New releases are locked out until the database is rolled back.</div>
                </x-message>
            @endif
        </div>
    @endif
</div>
