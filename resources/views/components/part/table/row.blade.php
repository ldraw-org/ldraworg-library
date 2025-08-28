@props(['part'])
<tr class="*:p-2">
  <td>
    @if($part->isUnofficial())
    <img class="w-[35px] max-h-[75px] object-scale-down" src="{{asset('images/library/unofficial/' . substr($part->filename, 0, -4) . '_thumb.png')}}" alt='part thumb image' title="part_thumb">
    @else
    <img class="w-[35px] max-h-[75px] object-scale-down" src="{{asset('images/library/official/' . substr($part->filename, 0, -4) . '_thumb.png')}}" alt='part thumb image' title="part_thumb">
    @endif
  </td>
  <td class="text-wrap">
    <a class="hover:underline" href="{{route('parts.show', $part)}}">
        <p class="font-bold break-all">{{ $part->filename }}</p>
        <p class="break-word">{{ $part->description }}</p>
    </a>
  </td>
  <td>
    <a class="hover:underline" href="{{route('part.download', ['library' => $part->libFolder(), 'filename' => $part->filename])}}">[DAT]</a>
  </td>
  <td>
    @if($part->isUnofficial())
    <x-part.status :$part show-status />
    @else
      @isset ($part->unofficial_part)
        <a href="{{ route('parts.show', $part->unofficial_part) }}">Updated part on tracker</a>
	    <x-part.status :part="$part->unofficial_part" show-status />
      @endisset
    @endif    
  </td>  
</tr>
