<div {{$attributes}}>
    <div class="relative inline-block" 
         x-data="{ showChildren: false }" 
         @mouseenter="showChildren = true" 
         @mouseleave="showChildren = false"
         @click.away="showChildren = false">
        
        <div class="w-8 h-8 rounded-full flex flex-shrink-0 justify-center items-center bg-purple-300 cursor-pointer hover:bg-purple-400 transition-colors relative z-20">
            <span class="text-black text-xs font-bold pointer-events-none">
                {{ str(Auth::user()->realname ?? 'User')->initials() }}
            </span>
        </div>

        <div class="absolute right-0 top-full z-50 w-48"
             style="display: none; border-top: 10px solid transparent; margin-top: -5px;"
             x-show="showChildren"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100">
            
            <div class="bg-white rounded-md shadow-lg border border-gray-200 overflow-visible relative">
                
                <div class="absolute -top-1.5 right-3 w-3 h-3 bg-white border-t border-l border-gray-200 transform rotate-45"></div>

                <ul class="flex flex-col py-1 relative z-10 bg-white rounded-md">
                    <li>
                        <a href="{{ route('dashboard.index') }}" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700 transition-colors">
                            User Dashboard
                        </a>
                    </li>
                    
                    @can(\App\Enums\Permission::AdminDashboardView)
                    <li>
                        <a href="{{ route('admin.index') }}" 
                           class="block px-4 py-2 text-sm text-gray-700 border-t border-gray-100 hover:bg-purple-50 hover:text-purple-700 transition-colors">
                            Admin Dashboard
                        </a>
                    </li>
                    @endcan
                </ul>
            </div>
        </div>
    </div>
</div>