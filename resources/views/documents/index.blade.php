<x-layout.documentation>
    <x-slot:title>Documentation Index</x-slot>
    <div class="flex flex-col space-y-2">
        @forelse($categories as $category)
            @if((!Auth::check() || Auth::user()->cannot('documents.restricted.view')) && $category->documents->where('published', true)->where('restricted', false)->isEmpty())
                @continue
            @endif
            <h1 class="font-bold text-xl">{{$category->title}}</h1>
            <ul class="pl-6 py-2 list-disc">
                @forelse ($category->published_documents->where('restricted', false)->sortBy('order') as $doc)
                    @if(!$doc->restricted || (Auth::check() && Auth::user()->can('documents.restricted.view')))
                        <li>
                            @if ($doc->type == \App\Enums\DocumentType::Link)
                                <a href="{{$doc->content}}">{{$doc->title}}</a>
                            @else
                                <a href="{{route('documentation.show', [$doc->category, $doc])}}">{{$doc->title}}</a>
                            @endif
                        </li>
                    @endif
                @empty
                    {{-- Do nothing --}}
                @endforelse
                @can('documents.restricted.view')
                    @forelse ($category->published_documents->where('restricted', true)->sortBy('order') as $doc)
                        <li>
                            @if ($doc->type == \App\Enums\DocumentType::Link)
                                <a href="{{$doc->content}}">{{$doc->title}}</a>
                            @else
                                <a href="{{route('documentation.show', [$doc->category, $doc])}}">{{$doc->title}}</a>
                            @endif
                        </li>
                    @empty
                        {{-- Do nothing --}}
                    @endforelse
                @endcan
            </ul>
        @empty
            <div>No published documents</div>
        @endforelse
    </div>
</x-layout.documentation>