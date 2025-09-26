<x-mail::message>
# Parts Tracker Daily Summary for {{$date->format('Y-m-d')}}
@foreach($parts as $part)
![{{$part->description}}]({{$message->embed($part->getFirstMediaPath('image'))}})
## [{{$part->filename}} - {{$part->description}}]({{route('parts.show', $part)}})
@foreach ($part->events->whereBetween('created_at', [$date, $next]) as $event)
### On {{$event->created_at}}:
@switch($event->event_type)
@case(\App\Enums\EventType::Submit)
A new version of file was submitted by {{$event->user->name}}
@break
@case(\App\Enums\EventType::HeaderEdit)
The header was edited by {{$event->user->name}}
@break
@case(\App\Enums\EventType::Rename)
The part was moved/renamed
@break
@case(\App\Enums\EventType::Comment)
{{$event->user->name}} made a comment
@break
@case(\App\Enums\EventType::Review)
@empty($event->vote_type)
{{$event->user->name}} cancelled thier vote.
@else
{{$event->user->name}} left a **{{strtolower($event->vote_type->label())}}** vote.
@endempty
@break
@endswitch
@endforeach

See [{{route('parts.show', $part)}}]({{route('parts.show', $part)}})

---
@endforeach
</x-mail::message>
