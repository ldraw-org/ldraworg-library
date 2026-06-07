@props(['actionGroup'])

@php $group = $this->$actionGroup() @endphp

@if ($group->isVisible())
    <x-filament-actions::group
        :actions="$group->getActions()"
        label="{{$group->getLabel()}}"
        icon="{{$group->getIcon()}}"
        button="{{$group->isButton()}}"
        color="{{$group->getColor()}}"
        outlined="{{$group->isOutlined()}}"

    />
@endif
