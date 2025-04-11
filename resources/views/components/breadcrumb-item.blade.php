@props(['item', 'active' => null])
<div class="px-2">
    <x-library-icon icon="breadcrumbs-separater" class="w-4" />
</div>
<div @class(["font-bold" => !is_null($active)])>
    {{$item}}
</div>