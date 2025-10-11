@props(['categories'])

<div class="flex flex-col space-y-2">
    @forelse($categories as $category)
        @cannot(\App\Enums\Permission::DocumentViewRestricted)
          @if($category->published_documents->where('restricted', false)->isEmpty())
            @continue
          @endif
        @endcannot
        <h1 class="font-bold text-xl">{{$category->title}}</h1>
        @if($category->published_documents->where('draft', true)->isNotEmpty())
            <h2 class="font-bold text-lg pl-6">Active</h2>
        @endif
        <x-document.toc.doc-list class="pl-8" :documents="$category->published_documents" />
        <x-document.toc.doc-list class="pl-8" :documents="$category->published_documents" restricted />
        @if($category->published_documents->where('draft', true)->isNotEmpty())
            <h2 class="font-bold text-lg pl-6">Drafts</h2>
            <x-document.toc.doc-list class="pl-8" :documents="$category->published_documents" draft />
            <x-document.toc.doc-list class="pl-8" :documents="$category->published_documents" draft restricted />
        @endif
    @empty
        <div>No published documents</div>
    @endforelse
</div>
