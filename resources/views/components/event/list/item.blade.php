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
                @if($event->user->can('part.vote.admincertify'))
                    <x-fas-crown class="h-4 w-4" title="Part Library Admin"/>
                @elseif($event->user->can('part.vote.fasttrack'))
                    <x-fas-user-graduate class="h-4 w-4" title="Senior Part Reviewer"/>
                @elseif($event->user->can('part.edit.header'))
                    <x-fas-user-plus class="h-4 w-4" title="Part Header Editor"/>
                @elseif($event->user->can('part.vote.certify'))
                    <x-fas-user-check class="h-4 w-4" title="Part Reviewer"/>
                @elseif($event->user->can('part.submit.regular'))
                    <x-fas-user-pen class="h-4 w-4" title="Part Author"/>
                @endif
            </div>
            <div>
            @switch($event->part_event_type->slug)
              @case('submit')
                @if(isset($event->initial_submit) && $event->initial_submit == true)
                  initially submitted the part.
                @else
                  submitted a new version of the part.
                @endisset
              @break
              @case('edit')
              edited the part header.
              @break
              @case('review')
                @if(is_null($event->vote_type_code))
                  cancelled their vote.
                @else
                  posted a vote of {{$event->vote_type->name}}.
                @endif
              @break
              @case('comment')
              made the following comment.
              @break
              @case('rename')
              renamed the part.
              @break
            @endswitch 
            </div>
        </div>
        <div class="text-xs text-gray-500">
          {{ $event->created_at }}
        </div>
    </div>
    <p class="mt-4 event-comment">
        @if($event->part_event_type->slug == 'rename')
            "{{$event->moved_from_filename}}" to "{{$event->moved_to_filename}}"
        @endif            
        @if($event->part_event_type->slug == 'edit' && !is_null($event->header_changes))
          <x-event.list.edit-accordian :changes="$event->header_changes" />
          @if(!is_null($event->comment))  
            Comment:<br>
          @endif    
        @endif
        @if(!is_null($event->comment) && $event->part_event_type->slug !== 'rename')            
            {!! $event->processedComment() !!}
        @endif
    </p>
</div>
