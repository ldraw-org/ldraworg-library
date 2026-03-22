<x-layout.base>
    <x-slot:title>
        LDraw.org Library Main
    </x-slot>

    <div class="flex flex-col space-y-4 w-full max-w-5xl mx-auto">

        <x-panel>
            Welcome to the LDraw.org library. Here you will find the Parts Tracker,
            parts updates, documentation for the LDraw file format and library,
            and the Official Model Repository.
        </x-panel>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <x-card
                class="bg-white"
                image="{{ asset('/images/cards/tracker.png') }}"
                link="{{ route('tracker.main') }}"
            >
                <x-slot:title>
                    Parts Tracker
                </x-slot>

                The Parts Tracker is the system we use to submit files to the
                LDraw.org Part Library. The Parts Tracker allows users to download
                unofficial parts, submit new files, update existing unofficial
                files, and review unofficial parts.
            </x-card>

            <x-card.latest-update class="bg-white"/>

            <x-card
                class="bg-white"
                image="{{ asset('/images/cards/doc.png') }}"
                link="https://www.ldraw.org/docs-main.html"
            >
                <x-slot:title>
                    Documentation
                </x-slot>

                The reference documentation for the LDraw File Format and
                LDraw.org Official Parts Library.
            </x-card>

            <x-card
                class="bg-white"
                image="{{ asset('/images/cards/omr.png') }}"
                link="{{ route('omr.main') }}"
            >
                <x-slot:title>
                    Official Model Repository
                </x-slot>

                The Official Model Repository (OMR) is a library of official LEGO®
                sets created in LDraw format.
            </x-card>

        </div>

    </div>
</x-layout.base>
