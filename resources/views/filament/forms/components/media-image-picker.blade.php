@use("App\Enums\LibraryIcon")
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.$entangle('{{ $getStatePath() }}'),
            toggle(id) {
                const current = this.state ?? []
                this.state = current.includes(id)
                    ? current.filter((i) => i !== id)
                    : [...current, id]
            },
            isSelected(id) {
                return (this.state ?? []).includes(id)
            },
        }"
        class="grid grid-cols-2 gap-3 sm:grid-cols-4"
    >
        @foreach ($images as $image)
            <button
                type="button"
                x-on:click="toggle({{ $image['id'] }})"
                x-bind:aria-pressed="isSelected({{ $image['id'] }})"
                x-bind:class="isSelected({{ $image['id'] }})
                    ? 'ring-2 ring-green-600 opacity-100'
                    : 'ring-1 ring-gray-300 opacity-60 hover:opacity-100 dark:ring-white/20'"
                class="relative flex h-40 items-center justify-center overflow-hidden rounded-lg bg-gray-50 p-2 transition dark:bg-white/5"
                title="{{ $image['name'] }}"
            >
                <img
                    src="{{ $image['url'] }}"
                    alt="{{ $image['name'] }}"
                    loading="lazy"
                    class="max-h-full max-w-full object-contain"
                >

                <span
                    x-show="isSelected({{ $image['id'] }})"
                    x-cloak
                    class="absolute right-1 top-1 z-10 rounded-full bg-green-600 p-1 text-white shadow"
                >
                    <x-library-icon :icon="LibraryIcon::Certify" color="bg-green-600" class="h-4 w-4" />
                </span>
            </button>
        @endforeach
    </div>
</x-dynamic-component>
