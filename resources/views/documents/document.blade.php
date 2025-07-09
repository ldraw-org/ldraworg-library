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
        <x-message compact type="info">
            <x-slot:header>
                Maintained By: {{$document->maintainer}}<br>
            </x-slot:>
            <div class="documentation">
                    <strong>Revision History:</strong>
                    {!! str($document->revision_history)->markdown()->sanitizeHtml() !!}
            </div>
            <p>
                This is an ratified, official LDraw.org document. 
                Non-adminstrative changes can only be made with the approval of the maintainer.
            </p>
        </x-message>     
        <div class="documentation flex flex-col md:flex-row gap-2">
            <div>{!! str($document->content)->markdown()->sanitizeHtml() !!}</div>
            <div class="md:w-3/5 border rounded-lg mx-4 h-fit"></div>
        </div>
    </div>
</x-layout.documentation>
