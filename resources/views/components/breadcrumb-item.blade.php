@props(['item', 'active' => null])
<div class="px-2">
    <x-mdi-chevron-double-right class="size-4" />
</div>
<div @class(["font-bold" => !is_null($active)])>
    {{$item}}
</div>