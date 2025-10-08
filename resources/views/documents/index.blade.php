<x-layout.documentation>
    <x-slot:title>Documentation Index</x-slot>
    <div class="flex flex-col space-y-2">
        @forelse($categories as $category)
            <h1 class="font-bold text-xl">{{$category->title}}</h1>
            <ul class="pl-6 py-2 list-disc">
                @foreach ($category->published_documents->sortBy('order') as $doc)
                    @if(!$doc->restricted || Auth::user()->can('documents.restricted.view'))
                        <li>
                            <a href="{{route('documentation.show', [$doc->category, $doc])}}">{{$doc->title}}</a>
                        </li>
                    @endif
                @endforeach
            </ul>
        @empty
            <div>No published documents</div>
        @endforelse
    </div>
</x-layout.documentation>