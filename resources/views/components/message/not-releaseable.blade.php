@props(['part'])
@if ($part->part_check->has(['errors','tracker_holds']))
<x-message compact icon type="warning">
    <x-slot:header>
        This part is not releaseable
    </x-slot:header>
    <ul>
        @foreach($part->part_check->get(['errors','tracker_holds']) as $error => $context)
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
                @endif
        @endforeach
    </ul>
</x-message>
@endif