@props(['part'])
@if ($part->tracker_holds->isNotEmpty())
    <x-message compact icon type="error">
        <x-slot:header>
            This part has the following automated holds from the Parts Tracker
        </x-slot:header>
        <ul>
            @foreach($part->tracker_holds as $error)
                @if ($error->error == \App\Enums\PartError::TrackerHasUncertifiedSubfiles->value)
                    <li wire:key="part-tracker_holds-{{$loop->iteration}}">
                        <x-accordion id="showContents">
                            <x-slot name="header">
                                <div>{{$error->message()}}</div>
                            </x-slot>
                            <div class="px-4">
                                @foreach($part->uncertified_subparts() as $p)
                                    <a wire:key="uncer-subfile-{{$p->id}}" href="{{route('parts.show', $p)}}" class="underline decoration-dotted hover:decoration-solid hover:text-gray-500">{{$p->filename}} ({{$p->part_status->label()}})</a><br/>
                                @endforeach
                            </div>
                        </x-accordion>
                    </li>
                @else
                    <li wire:key="part-error-{{$loop->iteration}}">
                        {{$error->message()}}
                    </li>
                @endif
            @endforeach
        </ul>
    </x-message>
@endif
@if ($part->errors->isNotEmpty())
    <x-message compact icon type="error">
        <x-slot:header>
            This part has the following errors
        </x-slot:header>
        <ul>
            @foreach($part->errors as $error)
                <li wire:key="part-error-{{$loop->iteration}}">
                    {{$error->message()}}
                </li>
            @endforeach
        </ul>
    </x-message>
@endif
@if ($part->warnings->isNotEmpty())
    <x-message compact icon type="warning">
        <x-slot:header>
            This part has the following warnings
        </x-slot:header>
        <ul>
            @foreach($part->warnings as $error)
                <li wire:key="part-warning-{{$loop->iteration}}">
                    {{$error->message()}}
                </li>
            @endforeach
        </ul>
    </x-message>
@endif