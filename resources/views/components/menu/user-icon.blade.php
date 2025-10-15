<div {{$attributes}}>
    <div class="w-min md:w-full md:w-min position-items-center">
        <div class="block relative" x-data="{showChildren:false}" @mouseenter="showChildren=true" @mouseleave="showChildren=false"> 
    
            <div class="w-8 h-8 rounded-full flex justify-center items-center bg-purple-300 cursor-pointer">
                <div class="text-black font-bold">{{str(Auth::user()->realname)->initials()}}</div>
            </div>
    
            <div class="bg-white rounded border border-gray-300 text-sm absolute bottom-0 md:bottom-auto md:top-auto left-10 md:left-auto md:right-0 md:min-w-full w-40 md:w-56 z-30 mt-1" x-show="showChildren" x-transition:enter.duration.300ms x-transition:leave.delay.50ms style="display: none;">
                <span class="absolute bottom-2 md:bottom-auto -left-7 md:left-auto md:right-2 w-3 h-3 bg-white border border-gray-200 transform rotate-45 -mt-1 ml-6"></span>
                <div class="bg-white rounded w-full relative z-10 py-1">
                    <ul class="list-reset">
                        <x-menu.item link="{{route('dashboard.index')}}" label="User Dashboard" />
                        @can(\App\Enums\Permission::AdminDashboardView)
                            <x-menu.item link="{{route('admin.index')}}" label="Admin Dashboard" />
                        @endcan
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
