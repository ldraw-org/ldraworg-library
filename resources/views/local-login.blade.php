<x-layout.base>
    <form method="POST" action="{{route('dev.local-login')}}">
        @csrf
        <input class="border" name="name">
        <input class="border" type="password" name="password">
        <button type="submit">Submit</button>
    </form>
</x-layout.base>
