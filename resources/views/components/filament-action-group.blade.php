@props(['group'])

@if ($group->isVisible())
    <x-filament-actions::group
        :actions="$group->getActions()"
        label="{{$group->getLabel()}}"
        icon="{{$group->getIcon()}}"
        color="{{$group->getColor()}}"
        :button="$group->isButton()"
        :outlined="$group->isOutlined()"
    />
@endif
