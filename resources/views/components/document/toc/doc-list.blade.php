@props(['documents', 'draft' => false, 'restricted' => false])
@forelse ($documents->where('draft', $draft)->where('restricted', $restricted)->sortBy('order') as $doc)
<ul {{$attributes->merge(['class' => 'list-disc']) }}>
    <li>
        <a href="{{route('documentation.show', [$doc->category, $doc])}}">{{$doc->title . ($doc->draft ? ' (Draft)' : '')}}</a>
    </li>
</ul>
@empty
    {{-- Do nothing --}}
@endforelse
