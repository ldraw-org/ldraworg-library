@props(['part'])
<x-message compact icon type="warning">
    <x-slot:header>
        This part is not releaseable
    </x-slot:header>
    <ul>
        @foreach($part->part_check_messages['errors'] as $error)
            <li wire:key="part-error-{{$loop->iteration}}">
                @if ($error == 'Has uncertified subfiles')
                    <x-accordion id="showContents">
                        <x-slot name="header">
                            <div>Has uncertified subfiles</div>
                        </x-slot>
                        <div class="px-4">
                            @foreach($part->descendants->whereIn('part_status', [\App\Enums\PartStatus::AwaitingAdminReview, \App\Enums\PartStatus::NeedsMoreVotes, \App\Enums\PartStatus::ErrorsFound]) as $p)
                                <a wire:key="uncer-subfile-{{$p->id}}" href="{{route('parts.show', $p)}}" class="underline decoration-dotted hover:decoration-solid hover:text-gray-500">{{$p->filename}} ({{$p->part_status->label()}})</a><br/>
                            @endforeach
                        </div>
                    </x-accordion>
                @else
                    {{$error}}
                @endif
            </li>
        @endforeach
    </ul>
</x-message>
