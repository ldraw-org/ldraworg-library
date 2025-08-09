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
            <div class="scroll-y-auto">
                <x-message compact type="info">
                    <x-slot:header>
                        Maintained By: {{$document->maintainer}}<br>
                    </x-slot:>
                    <div>
                            <strong>Revision History:</strong>
                            <div class="documentation">{!! str($document->revision_history)->markdown()->sanitizeHtml() !!}</div>
                    </div>
                    <p>
                        This is an ratified, official LDraw.org document. 
                        Non-adminstrative changes can only be made with the approval of the maintainer.
                    </p>
                </x-message>     
                <div class="documentation">{!! $doc_content !!}</div>
            </div>
            <div class="documentation h-screen sticky top-4 border border-gray-200 rounded-lg">{!! $toc !!}</div>
        </div>
     </div>
</x-layout.documentation>
