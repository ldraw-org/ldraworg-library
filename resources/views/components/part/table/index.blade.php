@props(['parts', 'title' => '', 'none' => 'None', 'missing' => []])
<div class="text-lg font-bold">{{$title}}</div>
@if ($parts->isNotEmpty() || !empty($missing))
<table class="border border-gray-200 rounded-lg w-full">
    <thead class="border-b-2 border-b-black">
        <tr class="*:bg-gray-200 *:font-bold *:justify-self-start *:p-2">
            <th>Image</th>
            <th>Part</th>
            <th>DAT</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody class="divide-y">
        @foreach ($parts as $part)
            <x-part.table.row :part="$part" wire:key="part-{{$part->id}}"/>
        @endforeach 
        @foreach ($missing as $m)
            <tr class="bg-red-200" wire:key="missing-{{str_replace('/', '', $m)}}"><td></td><td><p>{{$m}}<p><p>Missing<p></td><td></td><td></td></tr>
        @endforeach
    </tbody>
</table>
@else
<p>
    {{$none}}
</p>
@endif
