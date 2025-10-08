<x-layout.documentation>
    <x-slot:title>Documentation Index</x-slot>
    <div class="flex flex-col space-y-2">
        @foreach(\App\Models\Document\Document::where('published', true)->get()->sortBy('category.order')->groupBy('category.title') as $category => $docs)
            <h1 class="font-bold text-xl">{{$category}}</h1>
            <ul class="pl-6 py-2 list-disc">
                @foreach ($docs->sortBy('order') as $doc)
                    @if(!$doc->restricted || Auth::user()->can('documents.restricted.view'))
                        <li>
                            <a href="{{route('documentation.show', [$doc->category, $doc])}}">{{$doc->title}}</a>
                        </li>
                    @endif
                @endforeach
            </ul>
        @endforeach
    </div>
</x-layout.documentation>