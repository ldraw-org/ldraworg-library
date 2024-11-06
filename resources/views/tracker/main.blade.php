<x-layout.tracker>
  <x-slot:title>
    Parts Tracker Main
  </x-slot>
  <div class="space-y-2">
        <p class="p-2">
            The Parts Tracker is the system we use to submit files to the LDraw.org Part Library.
            The Parts Tracker allows users to download unofficial parts, submit new files, update existing unofficial files, and review unofficial parts.
        </p>
        <div class="grid grid-cols-2 p-2 gap-2">
            <div class="flex flex-col">
                <div class="text-lg font-bold">
                    <a class="underline decoration-dotted hover:decoration-solid hover:text-gray-500" href="https://www.ldraw.org/pt-policies.html">LDraw.org Library Policies and FAQ</a>
                </div>
                <p>
                    Parts Tracker frequently asked questions and policies for users.
                </p>
  
                <div class="text-lg font-bold">
                    <a class="underline decoration-dotted hover:decoration-solid hover:text-gray-500" href="{{route('tracker.activity')}}">Activity Log</a>
                </div>
                <p>
                    View the recent submissions, reviews, and admin actions on the tracker.
                </p>
            </div>
            <div class="flex flex-col">
                <div class="text-lg font-bold">
                    <a class="underline decoration-dotted hover:decoration-solid hover:text-gray-500" href="{{route('tracker.index')}}">Parts List</a>
                </div>
                <p>
                    The complete list of library files.
                </p>
  
                <div class="text-lg font-bold">
                    <a class="underline decoration-dotted hover:decoration-solid hover:text-gray-500" href="{{asset('library/unofficial/ldrawunf.zip')}}">Download All Unofficial Files</a>
                </div>
                <p>
                    Get all the current unofficial part files in one zip file.<br> 
                    <span class="font-bold">Please remember</span>: These are unofficial parts. They may be incomplete, or
                    inaccurate, and it is possible that when they are officially released they
                    may be changed in ways that could mess up any model you use them in.  This
                    is far more likely for Held parts than Certified parts.
                </p>
            </div>
        </div>
        <div class="flex flex-col p-2">
            <div class="text-md font-bold">
                Stats for Unofficial Files:
            </div>
            <x-part.unofficial-part-count small="0"/>
        </div>
    </div>
</x-layout.tracker>
