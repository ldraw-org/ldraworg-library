@props(['header'])

<div x-data="{ {{$attributes->get('id')}} : false }" class="flex flex-col">
    <div {{$header->attributes->class(['flex flex-row'])}} >
        <x-mdi-menu-right class="w-7 place-self-center cursor-pointer" ::class="{{$attributes->get('id')}} && 'rotate-90'" @@click="{{$attributes->get('id')}} = !{{$attributes->get('id')}}" />
        <div class="select-none cursor-pointer" @@click="{{$attributes->get('id')}} = !{{$attributes->get('id')}}">{{$header}}</div>
    </div>
    <div 
        x-show="{{$attributes->get('id')}}" 
        x-transition:enter="transition ease-out duration-100" 
        x-transition:enter-start="transform opacity-0"
        x-cloak
    >
        {{$slot}}
    </div>
</div>