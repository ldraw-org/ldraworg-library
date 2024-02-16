@props(['label', 'link' => null, 'level' => 0])
@php 
    $mname = strtolower(preg_replace('/[^A-Za-z0-9_]/', '', $label)) 
@endphp
<li x-data="{ {{$mname}} : false }" @@mouseover="{{$mname}} = true" @@mouseover.away="{{$mname}} = false" class="p-2 hover:bg-gray-300 relative">
    {{$label}}
    @if($level == 0)
        <x-fas-caret-down class="inline size-4" />
    @else
        <x-fas-caret-right class="inline size-4" />
    @endif
    <ul 
        @class([
            'flex flex-col bg-white absolute divide-y border rounded-md w-max z-50',
            'mt-2 left-0 end-0' => $level == 0,
            'left-3/4 end-0' => $level > 0
        ])
        x-show="{{$mname}}" 
        x-transition:enter="transition ease-out duration-100" 
        x-transition:enter-start="transform opacity-0"
        x-cloak
    >
    {{$slot}}
    </ul>
</li>