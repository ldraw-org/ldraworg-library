<x-slot:title>
    Confirm Current CA
</x-slot>
<div class="flex flex-col space-y-2">
    <p>
        Our records indicate that you have not confirmed the current Contributer's Agreement.
        Prior to allowing you to submit or edit any parts, you must read and affirm the current
        Contributer's Agreement.
    </p>
    <p>
        Please read the following and click agree to continue:
    </p>
    <div class="text-lg font-bold">
        The Contributor Agreement Dated 2024-06-06
    </div>
    <div class="border border-gray-200 rounded p-2 space-y-2">
        <p>
            By submitting work ("the Work") to The LDraw Organization ("LDraw.org"), the submitter ("the Author"), 
            agrees to release the Work under the Creative Commons Attribution License 4.0 International License ("CC BY 4.0")
            (or future versions at the descretion of the LDraw.org Library Administrator) or, at the Author's option, 
            to the public domain via the Creative Commons CC0 Public Domain Dedication ("CC0"). The Author can decide to 
            change the chosen license from CC BY 4.0 to CC0. A decision to change licenses will affect all LDraw.org related
            Works from the Author. The decision to use the CC0 license is permanent and cannot be changed. All edits to the
            Work will be covered by the license chosen by the Author of the Work.
        </p>
        <p>
            The human readable and legal text of the CC BY 4.0 license can be found at this link:<br/>
            <a class="underline decoration-dotted hover:decoration-solid hover:text-gray-500" href="https://creativecommons.org/licenses/by/4.0/">https://creativecommons.org/licenses/by/4.0/</a>
        </p>
        <p>
            The human readable and legal text of the CC0 license can be found at this link::<br/>
            <a class="underline decoration-dotted hover:decoration-solid hover:text-gray-500" href="https://creativecommons.org/publicdomain/zero/1.0/">https://creativecommons.org/publicdomain/zero/1.0/</a>
        </p>   
    </div>
    <div class="text-lg font-bold">
        Changes from the previous CA
    </div>
    <div class="border border-gray-200 rounded p-2 space-y-2">
        <p>
            Added the option for the Author to release their parts to the public domain via CC0.
        </p>
        <p>
            Note: The ability for a user to choose CC0 is not yet implemented and will be added to the library at a later date
        </p>
    </div>
    <x-filament::button wire:click="updateLicense" class="w-fit">I agree</x-filament::button>
</div>