@forelse($messages as $type => $reportable)
    @php($type = \App\Enums\CheckType::from($type))
    <x-slot:header>
        {{$type->statusMessage()}}
    </x-slot:header>
    <x-message compact icon type="{{$type == \App\Enums\CheckType::Warning ? 'warning' : 'error'}}">
        <ul>
            @foreach($reportable as $check => $checkMessage)
                @php($check = \App\Enums\PartError::from($check))
                @if ($check == \App\Enums\PartError::TrackerHasUncertifiedSubfiles)
                    <li wire:key="part-{{$check->value}}-{{$loop->iteration}}">
                        <x-accordion id="partTrackerHoldsUncertSubparts">
                            <x-slot name="header">
                                <div>{{$checkMessage->first()['message']}}</div>
                            </x-slot>
                            <div class="px-4">
                                @foreach($part->uncertified_subparts() as $p)
                                    <a wire:key="uncer-subfile-{{$p->id}}" href="{{route('parts.show', $p)}}" class="underline decoration-dotted hover:decoration-solid hover:text-gray-500">{{$p->filename}} ({{$p->part_status->label()}})</a><br/>
                                @endforeach
                            </div>
                        </x-accordion>
                    </li>
                @elseif($check->isMultiLine())
                    <li wire:key="part-{{$check->value}}-{{$loop->iteration}}">
                        <x-accordion id="part{{$type->name}}{{$check->name}}">
                            <x-slot name="header">
                                <div>{{$check->multiLineHeader()}}</div>
                            </x-slot>
                            <div class="px-4">
                                @foreach($checkMessage as $message)
                                    <div wire:key="part-{{$check->value}}-{{$message['error']}}-{{$loop->iteration}}">
                                        Line {{$message['lineNumber']}}: {{$message['text']}}
                                    </div>
                                @endforeach
                            </div>
                        </x-accordion>
                    </li>
                @else
                    <li wire:key="part-tracker_holds-{{$loop->iteration}}">
                        {{$checkMessage->first()['message']}}
                    </li>
                @endif
            @endforeach
        </ul>
    </x-message>
@empty
    //Do Nothing
@endforelse
