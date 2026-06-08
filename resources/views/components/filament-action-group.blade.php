@props(['group'])

@if ($group->isVisible())
    <x-filament-actions::group
        :actions="$group->getActions()"
        label="{{$group->getLabel()}}"
        icon="{{$group->getIcon()}}"
        color="{{$group->getColor()}}"
        @if($group->isButton()) button @endif
        @if($group->isOutlined()) outlined @endif
    />
@endif
