@props(['event'])
@use("\App\Enums\LibraryIcon")
<div class="flex-flex-col rounded border border-gray-200 p-4" {{ $attributes }}>
    <div class="flex flex-row space-x-4 place-items-center" >
        <x-event.icon :$event />
        <div class="flex flex-row space-x-2 place-items-center font-bold">
            <div>
                {{ $authorString }}
            </div>
            @if($authorIcon !== null)
                <div class="flex flex-row">
                    <x-library-icon :icon="$authorIcon" class="w-5" title="{{$authorIconTitle}}"/>
                </div>
            @endif
            <div>
                {{ $eventText }}
            </div>
        </div>
        <div class="text-xs text-gray-500">
            {{ $formattedDate }}
        </div>
    </div>
    @if($comment !== null || $hasHeaderEditAccordion)
        <div class="mt-4 event-comment font-mono center max-w-screen-xl w-full break-words overflow-auto">
            @if($hasHeaderEditAccordion)
                <x-event.list.edit-accordian :changes="$event->header_changes" />
                @if($comment !== null)
                    Comment:<br>
                @endif
            @endif
            @if($comment !== null)
                {{ $event->processedComment() }}
            @endif
        </div>
    @endif
</div>
