@props(['group'])
@use('App\Enums\LibraryIcon')

<div
    {{ $attributes }}
    x-data="{
        collapsed: false,
        probeWidth: 0,
        containerWidth: 0,
        observer: null,
        checkOverflow() {
            this.probeWidth = this.$refs.probe.scrollWidth;
            this.containerWidth = this.$el.parentElement.clientWidth;
            this.collapsed = this.probeWidth > this.containerWidth;
        },
        init() {
            this.checkOverflow();
            this.observer = new ResizeObserver(() => this.checkOverflow());
            this.observer.observe(this.$el.parentElement);
        },
        destroy() {
            this.observer?.disconnect();
        },
    }"
    x-ref="container"
    class="relative w-fit"
>
    @if ($group->isVisible())
        <div x-ref="probe" class="invisible absolute left-0 top-0 pointer-events-none whitespace-nowrap" aria-hidden="true">
            <x-filament-actions::group
                :actions="$group->getActions()"
                label="{{ $group->getLabel() }}"
                icon="{{ $group->getIcon() }}"
                color="{{ $group->getColor() }}"
                :button="$group->isButton() && !$group->isButtonGroup()"
                :outlined="$group->isOutlined()"
                :buttonGroup="$group->isButtonGroup()"
            />
        </div>

        <div x-show="!collapsed" x-cloak>
            <x-filament-actions::group
                :actions="$group->getActions()"
                label="{{ $group->getLabel() }}"
                icon="{{ $group->getIcon() }}"
                color="{{ $group->getColor() }}"
                :button="!$group->isButtonGroup()"
                :outlined="$group->isOutlined()"
                :buttonGroup="$group->isButtonGroup()"
            />
        </div>

        <div x-show="collapsed" x-cloak>
            <x-filament-actions::group
                :actions="$group->getActions()"
                label="{{ $group->getLabel() }}"
                icon="{{ LibraryIcon::MenuDown }}"
                color="{{ $group->getColor() }}"
                button
                :outlined="$group->isOutlined()"
            />
        </div>
    @endif
</div>
