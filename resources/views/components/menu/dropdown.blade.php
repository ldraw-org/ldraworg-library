@props(['label', 'link' => null, 'level' => 0])
@php 
    $mname = strtolower(preg_replace('/[^A-Za-z0-9_]/', '', $label)) 
@endphp
<li x-data="{ {{$mname}} : false }" @@mouseover="{{$mname}} = true" @@mouseover.away="{{$mname}} = false" class="p-2 hover:bg-gray-300 relative">
    {{$label}}
    @if($level == 0)
        <x-library-icon icon="menu-down" class="inline w-7" />
    @else
        <x-library-icon icon="menu-right" class="inline w-7" />
    @endif
    <ul 
        @class([
            'flex flex-col bg-white absolute divide-y border rounded-md w-max z-50',
            'mt-2 left-0 end-0' => $level == 0,
            'left-0 end-0 md:left-1/4 md:end-0' => $level > 0
        ])
        x-show="{{$mname}}" 
        x-transition:enter="transition ease-out delay-300 duration-100" 
        x-transition:enter-start="transform opacity-0"
        x-cloak
    >
    {{$slot}}
    </ul>
</li>