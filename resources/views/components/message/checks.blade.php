@foreach($messages as $type)
    <x-message compact icon type="{{$type['type']->statusType()}}">
        <x-slot:header>
            {{$type['type']->statusMessage()}}
        </x-slot:header>
        <ul>
            @foreach($type['checks'] as $item)
                <li wire:key="{{$item['check']->type()->value}}-{{$loop->iteration}}">
                    @if ($item['check']->isMultiLine())
                        <x-accordion id="{{$item['check']->type()->value}}-{{$item['check']->value}}">
                            <x-slot name="header">
                                <div>{{$item['check']->multiLineHeader()}}</div>
                            </x-slot>
                            <div class="px-4">
                                @if($item['check'] === \App\Services\Check\Enums\PartAutomatedHold::TrackerHasUncertifiedSubfiles)
                                    @foreach($part->uncertified_subparts() as $p)
                                        <a
                                            wire:key="uncer-subfile-{{$p->id}}"
                                            href="{{route('parts.show', $p)}}"
                                            class="underline decoration-dotted hover:decoration-solid hover:text-gray-500">
                                            {{$p->filename}} ({{$p->part_status->label()}})
                                        </a><br/>
                                    @endforeach
                                @else
                                    @foreach($item['lines'] as $line)
                                        <div wire:key="{{$item['check']->type()->value}}-{{$item['check']->value}}-{{$loop->iteration}}">
                                            {{ $line }}
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </x-accordion>
                    @else
                        {{$item['message']}}
                    @endif
                </li>
            @endforeach
        </ul>
    </x-message>
@endforeach
