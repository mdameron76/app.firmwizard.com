<aside 
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
    class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transition-transform duration-300 ease-in-out lg:translate-x-0"
>
    <div class="flex flex-col h-full">
        <!-- Logo -->
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-lg">FW</span>
                </div>
                <span class="font-bold text-xl text-gray-900">FirmWizard</span>
            </a>
            
            <!-- Close button (mobile) -->
            <button 
                @click="sidebarOpen = false" 
                class="lg:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            <!-- Dashboard -->
            <a 
                href="{{ route('dashboard') }}" 
                class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-fw-accent text-fw-dark font-semibold' : 'text-gray-300 hover:bg-fw-darker' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span class="font-medium">Dashboard</span>
            </a>

            <!-- AI Agents (with dropdown) -->
            <div class="space-y-1">
                <button 
                    @click="aiAgentsOpen = !aiAgentsOpen"
                    class="flex items-center justify-between w-full px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('ai-agents.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' }}"
                >
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span class="font-medium">AI Agents</span>
                    </div>
                    <svg 
                        :class="aiAgentsOpen ? 'rotate-180' : ''" 
                        class="w-4 h-4 transition-transform" 
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <!-- AI Agents Submenu -->
                <div 
                    x-show="aiAgentsOpen" 
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform -translate-y-2"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    class="pl-11 space-y-1"
                >
                    <a 
                        href="{{ route('ai-agents.blog-posts') }}" 
                        class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('ai-agents.blog-posts') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}"
                    >
                        📝 Blog Posts
                    </a>
                    <a 
                        href="{{ route('ai-agents.competitor-analysis') }}" 
                        class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('ai-agents.competitor-analysis') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}"
                    >
                        🔍 Competitor Analysis
                    </a>
                    <a 
                        href="{{ route('ai-agents.website-analysis') }}" 
                        class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('ai-agents.website-analysis') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}"
                    >
                        🌐 Website Analysis
                    </a>
                    <a 
                        href="{{ route('ai-agents.gbp') }}" 
                        class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('ai-agents.gbp') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}"
                    >
                        📍 Google Business Profile
                    </a>
                    <a 
                        href="{{ route('ai-agents.google-ads') }}" 
                        class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('ai-agents.google-ads') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}"
                    >
                        💰 Google Ads
                    </a>
                    <a 
                        href="{{ route('ai-agents.lsa') }}" 
                        class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('ai-agents.lsa') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}"
                    >
                        🎯 Local Services Ads
                    </a>
                    <a 
                        href="{{ route('ai-agents.meta-ads') }}" 
                        class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('ai-agents.meta-ads') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}"
                    >
                        📱 Meta Ads
                    </a>
                    <a 
                        href="{{ route('ai-agents.backlinks') }}" 
                        class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('ai-agents.backlinks') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}"
                    >
                        🔗 Backlinks
                    </a>
                    <a 
                        href="{{ route('ai-agents.analytics') }}" 
                        class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('ai-agents.analytics') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}"
                    >
                        📊 Analytics
                    </a>
                </div>
            </div>

            <!-- Integrations -->
            <a 
                href="{{ route('integrations.index') }}" 
                class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('integrations.*') ? 'bg-fw-accent text-fw-dark font-semibold' : 'text-gray-300 hover:bg-fw-darker' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span class="font-medium">Integrations</span>
            </a>

            <!-- Settings -->
            <a 
                href="{{ route('settings.index') }}" 
                class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('settings.*') ? 'bg-fw-accent text-fw-dark font-semibold' : 'text-gray-300 hover:bg-fw-darker' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="font-medium">Settings</span>
            </a>
        </nav>

        <!-- Footer -->
        <div class="px-3 py-4 border-t border-gray-200">
            <div class="px-3 py-2 bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg">
                <p class="text-xs font-semibold text-gray-900">Need Help?</p>
                <p class="text-xs text-gray-600 mt-1">Contact support</p>
            </div>
        </div>
    </div>
</aside>

<!-- Overlay for mobile -->
<div 
    x-show="sidebarOpen" 
    @click="sidebarOpen = false"
    x-cloak
    class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
></div>
