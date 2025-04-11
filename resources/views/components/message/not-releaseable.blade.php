@props(['part'])
@if ($part->part_check->has(['tracker_holds']))
    <x-message compact icon type="error">
        <x-slot:header>
            This part has the following automated holds from the Parts Tracker
        </x-slot:header>
        <ul>
            @foreach($part->part_check->get(['tracker_holds']) as $error => $context)
                @if ($error == \App\Enums\PartError::TrackerHasUncertifiedSubfiles->value)
                    <li wire:key="part-error-{{$loop->iteration}}">
                        <x-accordion id="showContents">
                            <x-slot name="header">
                                <div>{{__("partcheck.{$error}")}}</div>
                            </x-slot>
                            <div class="px-4">
                                @foreach($part->uncertified_subparts() as $p)
                                    <a wire:key="uncer-subfile-{{$p->id}}" href="{{route('parts.show', $p)}}" class="underline decoration-dotted hover:decoration-solid hover:text-gray-500">{{$p->filename}} ({{$p->part_status->label()}})</a><br/>
                                @endforeach
                            </div>
                        </x-accordion>
                    </li>
                @else
                    @if ($context)
                        @foreach ($context as $index => $replace)
                            <li wire:key="part-error-{{$loop->parent->iteration}}-{{$loop->iteration}}">
                                {{__("partcheck.{$error}", $replace)}}
                            </li>
                        @endforeach
                    @else
                        <li wire:key="part-error-{{$loop->iteration}}">
                            {{__("partcheck.{$error}")}}
                        </li>
                    @endif
                @endif
            @endforeach
        </ul>
    </x-message>
@endif
@if ($part->part_check->has(['errors']))
    <x-message compact icon type="error">
        <x-slot:header>
            This part has the following errors
        </x-slot:header>
        <ul>
            @foreach($part->part_check->get(['errors']) as $error => $context)
                @if ($context)
                    @foreach ($context as $index => $replace)
                        <li wire:key="part-error-{{$loop->parent->iteration}}-{{$loop->iteration}}">
                            {{__("partcheck.{$error}", $replace)}}
                        </li>
                    @endforeach
                @else
                    <li wire:key="part-error-{{$loop->iteration}}">
                        {{__("partcheck.{$error}")}}
                    </li>
                @endif
            @endforeach
        </ul>
    </x-message>
@endif
@if ($part->part_check->has(['warnings']))
    <x-message compact icon type="warning">
        <x-slot:header>
            This part has the following warnings
        </x-slot:header>
        <ul>
            @foreach($part->part_check->get(['warnings']) as $error => $context)
                @if ($context)
                    @foreach ($context as $replace)
                        <li wire:key="part-error-{{$loop->parent->iteration}}-{{$loop->iteration}}">
                            {{__("partcheck.{$error}", $replace)}}
                        </li>
                    @endforeach
                @else
                    <li wire:key="part-error-{{$loop->iteration}}">
                        {{__("partcheck.{$error}")}}
                    </li>
                @endif
            @endforeach
        </ul>
    </x-message>
@endif