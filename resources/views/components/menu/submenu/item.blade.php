@props(['label', 'link'])

<li>
    <a href="{{ $link }}" class="px-4 py-2 flex w-full items-start hover:bg-gray-100 no-underline hover:no-underline transition-colors duration-100 cursor-pointer"> 
        <span class="flex-1">{{ $label }}</span> 
    </a>
</li>
