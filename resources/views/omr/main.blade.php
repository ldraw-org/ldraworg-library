<x-layout.omr>
    <x-slot:title>
        LDraw.org Official Model Repository
    </x-slot>
    <div class="flex flex-col space-y-2">
        <p class="p-2">
            Welcome to the LDraw OMR, the 'Official Model Repository'. Here you can find LDraw files of official models released by LEGO.
        </p>
        <div class="text-lg font-bold rounded border border-gray-200 bg-gray-200 p-2">Latest Models</div>
        <div class="grid grid-cols-5 space-x-2">            
            <x-omr-model.latest />
        </div>
        <div class="rounded border border-gray-200 divide-y">
            <div class="text-lg font-bold bg-gray-200 p-2">What is this?</div>
            <p class="p-2">
                The model repository is a database of files in the 'LDraw File Format' describing models that are released as sets by LEGO. 
                It also includes a specification of how the files should be named and structured (the OMR specification). If you want your 
                models to be submitted to this website, you first need to make sure your files are according the OMR spec. You can read 
                the specification here. And here is a tutorial on how to make your LDraw files OMR compliant.
            </p>
        </div>    
        <div class="rounded border border-gray-200 divide-y">
            <div class="text-lg font-bold bg-gray-200 p-2">How to use this website?</div>
            <p class="p-2">
                This website is made purely for organizing and accessing all the OMR files. This site is not meant to be used as a database 
                for LEGO sets, there are a lot of better websites for that. If you're looking for an LDraw file for a specific set, 
                you can enter the set number at the top search bar. If you just want to go through all available files and filter 
                through them, you can go to the all files page.            
            </p>
        </div>
        <div class="rounded border border-gray-200 divide-y">
            <div class="text-lg font-bold bg-gray-200 p-2">How can I submit files?</div>
            <p class="p-2">
                If you have LDraw files of official LEGO sets and you've made them OMR compliant, you can submit them at the LDraw forum. 
                At the moment we don't have an onsite submit form.
            </p>
        </div>
    </div>
</x-layout.omr>
  