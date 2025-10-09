<x-layout.documentation>
    <x-slot:title>{{$document->title}}</x-slot>
    @push('css')
        @vite('resources/css/documentation.css')
    @endpush
    <x-slot:breadcrumbs>
        <x-breadcrumb-item class="active" item="{{$document->title}}" />
    </x-slot>
    <div class="p-4 space-y-6">
        <div class="documentation">
            <h1>{{$document->title}}</h1>
        </div>
        <div class="flex flex-row space-x-2">
            <div class="documentation h-screen sticky top-4">
                <div class="flex flex-col">
                    <div class="font-bold">{{$document->category->title}}</div>
                    <ol class="flex flex-col space-y-2 list-decimal">
                        @foreach($document->category->published_documents->sortBy('order') as $doc)
                            @if(!$doc->restricted || Auth::user()->can('documents.restricted.view'))
                                <li>
                                    @if ($doc->type == \App\Enums\DocumentType::Link)
                                        <a href="{{$doc->content}}">{{$doc->title}}</a>
                                    @else
                                        <a href="{{route('documentation.show', [$doc->category, $doc])}}">{{$doc->title}}</a>
                                    @endif
                                </li>
                            @endif
                        @endforeach
                    </ol>
                </div>
            </div>
            <div class="scroll-y-auto border-x px-1 border-gray-200">
                <x-message compact type="info">
                    <x-slot:header>
                        Maintained By: {{$document->maintainer}}<br>
                    </x-slot:>
{{--
                    <div>
                            <strong>Revision History:</strong>
                            <div class="documentation">{!! str($document->revision_history)->markdown()->sanitizeHtml() !!}</div>
                    </div>
--}}
                    <p>
                        This is an ratified, official LDraw.org document. 
                        Non-adminstrative changes can only be made with the approval of the maintainer.
                    </p>
                </x-message>     
                <div class="documentation">{!! $doc_content !!}</div>
            </div>
            <div class="documentation h-screen sticky top-4">{!! $toc !!}</div>
        </div>
     </div>
</x-layout.documentation>
