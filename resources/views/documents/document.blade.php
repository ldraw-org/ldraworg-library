<x-layout.documentation>
    <x-slot:title>{{$document->title . ($document->draft ? ' (Draft)' : '')}}</x-slot>
    <x-slot:breadcrumbs>
        <x-breadcrumb-item class="active" item="{{$document->title . ($document->draft ? ' (Draft)' : '')}}" />
    </x-slot>
    <div class="p-4 space-y-6">
        <div>
            <h1 class="py-2 font-bold text-4xl">{{$document->title . ($document->draft ? ' (Draft)' : '')}}</h1>
        </div>
        <div class="flex flex-row space-x-2">
            <div class="hidden md:contents h-screen sticky top-4">
              <x-document.toc :categories="collect($document->category()->get())" />
            </div>
            <div class="md:scroll-y-auto border-x px-1 border-gray-200 w-full">
                <x-message compact type="{{$document->draft ? 'warning' : 'info'}}">
                    <x-slot:header>
                        Maintained By: {{$document->maintainer}}<br>
                    </x-slot:>
                    @if ($document->revision_history != '' && $document->draft == false)
                        <div>
                                <strong>Revision History:</strong>
                                <div class="flex flex-col space-y-2">
                                    @if ($document->type == \App\Enums\DocumentType::Markdown)
                                        {!! str($document->revision_history)->markdown()->sanitizeHtml() !!}
                                    @else
                                        {!! str($document->revision_history)->sanitizeHtml() !!}
                                    @endif
                                </div>
                        </div>
                    @endif
                    @if ($document->draft == false)
                        <p>
                            This is an ratified, official LDraw.org document. 
                            Non-adminstrative changes can only be made with the approval of the maintainer.
                        </p>
                    @else
                        <p>
                            This document is in a draft status and it not currently ratified for use. 
                        </p>
                    @endif
                </x-message>     
                <div class="flex flex-col space-y-2">
                    @if ($document->type == \App\Enums\DocumentType::Markdown)
                        {!! $doc_content !!}
                    @else
                        {!! str($doc_content)->sanitizeHtml() !!}
                    @endif
                </div>
            </div>
            @if ($toc != '')
                <div class="hidden md:flex flex-col h-screen sticky overflow-y-scroll top-4">{!! $toc !!}</div>
            @endif
        </div>
     </div>
</x-layout.documentation>
