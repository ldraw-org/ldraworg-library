<x-menu>
    <x-menu.library-dropdown />
    @can('create', \App\Models\Part::class)
        <x-menu.item label="Submit" link="{{route('tracker.submit')}}" />
    @endcan
    <x-menu.item label="Parts List" link="{{route('tracker.index')}}" /> 
    <x-menu.item label="Activity" link="{{route('tracker.activity')}}" /> 
    <x-menu.item label="Weekly New Parts" link="{{route('tracker.weekly')}}" />
    <x-menu.dropdown label="Documentation">
            <x-menu.dropdown label="LDraw File Format" level="1">
                    <x-menu.item label="LDraw File Format Specification" link="https://www.ldraw.org/article/218.html" />
                    <x-menu.item label="Colour Definition (!COLOUR) Language Extension" link="https://www.ldraw.org/article/299.html" />
                    <x-menu.item label="Back Face Culling (BFC) Language Extension" link="https://www.ldraw.org/article/415.html" />
                    <x-menu.item label="Texture Mapping (!TEXMAP) Language Extension" link="https://www.ldraw.org/texmap-spec.html" />
                    <x-menu.item label="!CATEGORY and !KEYWORDS Language Extension" link="https://www.ldraw.org/article/340.html" />
                    <x-menu.item label="Multi-Part Document (MPD) and Image Embedding (!DATA) Language Extension" link="https://www.ldraw.org/article/47.html" />
                    <x-menu.item label="Localisation Guideline" link="https://www.ldraw.org/article/559.html" />
            </x-menu.dropdown>
            <x-menu.dropdown label="LDraw.org Official Parts Library Standards" level="1">
                    <x-menu.item label="LDraw.org Official Parts Library Specifications" link="https://www.ldraw.org/article/512.html" />
                    <x-menu.item label="Official Library Part Number Specification" link="https://www.ldraw.org/part-number-spec.html" />
                    <x-menu.item label="Official Library Header Specification" link="https://www.ldraw.org/article/398.html" />
                    <x-menu.item label="LDraw.org Library Policies and FAQ" link="https://www.ldraw.org/pt-policies.html" />
            </x-menu.dropdown>
            <x-menu.dropdown label="Official Model Repository (OMR) Standards " level="1">
                    <x-menu.item label="Official Model Repository (OMR) Specification" link="https://www.ldraw.org/article/593.html" />
                    <x-menu.item label="Rules and procedures for the Official Model Repository" link="https://www.ldraw.org/docs-main/official-model-repository-omr/rules-and-procedures-for-the-official-model-repository.html" />
            </x-menu.dropdown>
            <x-menu.dropdown label="Quick Reference Guides" level="1">
                    <x-menu.item label="Primitive Reference" link="https://wiki.ldraw.org/wiki/Primitives_Reference" />
                    <x-menu.item label="Colour Definition Reference" link="https://www.ldraw.org/article/547.html" />
                    <x-menu.item label="Common Error Check Messages" link="https://www.ldraw.org/docs-main/ldraw-org-quick-reference-guides/common-error-check-messages.html" />
            </x-menu.dropdown>
            <x-menu.dropdown label="Licenses" level="1">
                    <x-menu.item label="LDraw.org Contributor Agreement" link="https://www.ldraw.org/docs-main/licenses/ldraw-org-contributor-agreement.html" />
                    <x-menu.item label="Legal Info" link="https://www.ldraw.org/docs-main/licenses/legal-info.html" />
            </x-menu.dropdown>
    </x-menu.dropdown>
    <x-menu.dropdown label="Tools">
        <x-menu.item label="Part Search" link="{{route('search.part')}}" />
        <x-menu.item label="Pattern/Shortcut Part Summary" link="{{route('search.suffix')}}" /> 
        <x-menu.item label="Sticker Sheet Lookup" link="{{route('sticker-sheet.index')}}" /> 
        @if(!empty($summaries))
            <x-menu.dropdown label="Review Summaries" level="1">
                @foreach($summaries as $summary)
                    <x-menu.item label="{{$summary->header}}" link="{{route('tracker.summary.view', $summary)}}" /> 
                @endforeach
            </x-menu.dropdown>
        @endif
        <x-menu.item label="Download All Unofficial Files" link="{{asset('library/unofficial/ldrawunf.zip')}}" />
        <x-menu.item label="Download Last 24 Hours of Submits" link="{{route('tracker.last-day')}}" />
        <x-menu.item label="Parts in Next Update" link="{{route('tracker.next-release')}}" />
        <x-menu.item label="Parts Tracker History" link="{{route('tracker.history')}}" />
    </x-menu.dropdown>
</x-menu>
