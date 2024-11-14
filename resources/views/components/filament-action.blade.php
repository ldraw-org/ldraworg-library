@props(['action'])

@if ($this->$action->isVisible())
    {{ $this->$action }}
@endif
