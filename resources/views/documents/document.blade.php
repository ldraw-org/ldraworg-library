<x-layout.documentation>
    <x-slot:title>{{$document->title . ($document->draft ? ' (Draft)' : '')}}</x-slot>
    @push('css')
        @vite('resources/css/documentation.css')
    @endpush
    <x-slot:breadcrumbs>
        <x-breadcrumb-item class="active" item="{{$document->title . ($document->draft ? ' (Draft)' : '')}}" />
    </x-slot>
    <div class="p-4 space-y-6">
        <div>
            <h1 class="py-2 font-bold text-4xl">{{$document->title . ($document->draft ? ' (Draft)' : '')}}</h1>
        </div>
        <div class="flex flex-row space-x-2">
            <div class="h-screen sticky top-4">
                <div class="flex flex-col">
                    <div class="font-bold">{{$document->category->title}}</div>
                    <ol class="flex flex-col space-y-2 list-decimal">
                        @forelse ($document->category->published_documents->where('restricted', false)->sortBy('order') as $doc)
                            <li>
                                @if ($doc->type == \App\Enums\DocumentType::Link)
                                    <a href="{{$doc->content}}">{{$doc->title}}</a>
                                @else
                                    <a href="{{route('documentation.show', [$doc->category, $doc])}}">{{$doc->title . ($doc->draft ? ' (Draft)' : '')}}</a>
                                @endif
                            </li>
                        @empty
                            {{-- Do nothing --}}
                        @endforelse
                        
                        @forelse ($document->category->published_documents->where('restricted', true)->sortBy('order') as $doc)
                            @can('view', $doc)
                                <li>
                                    @if ($doc->type == \App\Enums\DocumentType::Link)
                                        <a href="{{$doc->content}}">{{$doc->title}}</a>
                                    @else
                                        <a href="{{route('documentation.show', [$doc->category, $doc])}}">{{$doc->title . ($doc->draft ? ' (Draft)' : '')}}</a>
                                    @endif
                                </li>
                            @endcan
                        @empty
                            {{-- Do nothing --}}
                        @endforelse
                    </ol>
                </div>
            </div>
            <div class="scroll-y-auto border-x px-1 border-gray-200">
                <x-message compact type="info">
                    <x-slot:header>
                        Maintained By: {{$document->maintainer}}<br>
                    </x-slot:>
                    @if ($document->revision_history != '')
                        <div>
                                <strong>Revision History:</strong>
                                <div class="documentation">
                                    @if ($document->type == \App\Enums\DocumentType::Markdown)
                                        {!! str($document->revision_history)->markdown()->sanitizeHtml() !!}
                                    @else
                                        {!! str($document->revision_history)->sanitizeHtml() !!}
                                    @endif
                                </div>
                        </div>
                    @endif
                    <p>
                        This is an ratified, official LDraw.org document. 
                        Non-adminstrative changes can only be made with the approval of the maintainer.
                    </p>
                </x-message>     
                <div class="documentation">
                    @if ($document->type == \App\Enums\DocumentType::Markdown)
                        {!! $doc_content !!}
                    @else
                        {!! str($doc_content)->sanitizeHtml() !!}
                    @endif
                </div>
            </div>
            @if ($toc != '')
                <div class="documentation h-screen sticky top-4">{!! $toc !!}</div>
            @endif
        </div>
     </div>
</x-layout.documentation>
