<x-mail::message>
# Parts Tracker Daily Summary for {{$date->format('Y-m-d')}}
@foreach($parts as $part)
![{{$part->description}}]({{$message->embed(storage_path("app/images/library/{$part->libFolder()}/" . substr($part->filename, 0, -4) . '_thumb.png'))}})
## [{{$part->filename}} - {{$part->description}}]({{route('parts.show', $part)}})
@foreach ($part->events->whereBetween('created_at', [$date, $next]) as $event)
### On {{$event->created_at}}: 
@switch($event->part_event_type->slug)
@case('submit')
A new version of file was submitted by {{$event->user->name}}
@break
@case('edit') 
The header was edited by {{$event->user->name}}
@break
@case('rename') 
The part was moved/renamed
@break
@case('comment') 
{{$event->user->name}} made a comment
@break
@case('review')
@empty($event->vote_type_code) 
{{$event->user->name}} cancelled thier vote.
@else
{{$event->user->name}} left a **{{strtolower($event->vote_type->name)}}** vote.
@endempty
@break
@endswitch
@endforeach

See [{{route('parts.show', $part)}}]({{route('parts.show', $part)}})

---
@endforeach
</x-mail::message>