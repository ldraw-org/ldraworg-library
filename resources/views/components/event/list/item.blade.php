@props(['event'])
<div class="flex-flex-col rounded border p-4" {{ $attributes }}>
    <div class="flex flex-row space-x-4 place-items-center" >
        <x-event.icon :$event />
        <div class="flex flex-row space-x-2 place-items-center font-bold">
            @if(!$event->user->is_legacy && !$event->user->is_synthetic && !$event->user->is_ptadmin)
            <a class="text-blue-500 visited:text-violet-500 hover:text-blue-300 hover:underline" href="https://forums.ldraw.org/private.php?action=send&uid={{$event->user->forum_user_id}}&subject=Regarding%20Parts%20Tracker%20file%20{{$event->part->filename}}">
              {{ $event->user->author_string }}
            </a>
            @else
              <div>{{ $event->user->author_string }}</div>
            @endif
            <div class="flex flex-row">
                @if($event->user->hasRole('Library Admin'))
                    <x-fas-crown class="h-4 w-4" title="Part Library Admin"/>
                @elseif($event->user->hasRole('Senior Reviewer'))
                    <x-fas-user-graduate class="h-4 w-4" title="Senior Part Reviewer"/>
                @elseif($event->user->hasRole('Part Header Editor'))
                    <x-fas-user-plus class="h-4 w-4" title="Part Header Editor"/>
                @elseif($event->user->hasRole('Part Reviewer'))
                    <x-fas-user-check class="h-4 w-4" title="Part Reviewer"/>
                @elseif($event->user->hasRole('Part Author'))
                    <x-fas-user-pen class="h-4 w-4" title="Part Author"/>
                @endif
            </div>
            <div>
            @switch($event->event_type)
              @case(\App\Enums\EventType::Submit)
                @if(isset($event->initial_submit) && $event->initial_submit == true)
                  initially submitted the part.
                @else
                  submitted a new version of the part.
                @endisset
              @break
              @case(\App\Enums\EventType::HeaderEdit)
              edited the part header.
              @break
              @case(\App\Enums\EventType::Review)
                @if(is_null($event->vote_type))
                  cancelled their vote.
                @else
                  posted a vote of {{$event->vote_type->label()}}.
                @endif
              @break
              @case(\App\Enums\EventType::Comment)
              made the following comment.
              @break
              @case(\App\Enums\EventType::Rename)
              renamed the part.
              @break
            @endswitch
            </div>
        </div>
        <div class="text-xs text-gray-500">
          {{ $event->created_at }}
        </div>
    </div>
    <div class="mt-4 event-comment font-mono center max-w-screen-xl w-full break-words overflow-auto">
        @if($event->event_type == \App\Enums\EventType::Rename)
            "{{$event->moved_from_filename}}" to "{{$event->moved_to_filename}}"
        @endif
        @if($event->event_type == \App\Enums\EventType::HeaderEdit && !is_null($event->header_changes))
          <x-event.list.edit-accordian :changes="$event->header_changes" />
          @if(!is_null($event->comment))
            Comment:<br>
          @endif
        @endif
        @if(!is_null($event->comment) && $event->event_type !== \App\Enums\EventType::Rename)
            {!! $event->processedComment() !!}
        @endif
    </div>
</div>
