<x-app-sidebar>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">
            Dashboard
        </h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Welcome Section -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <h1 class="text-3xl font-bold">Welcome back, {{ Auth::user()->name }}! 👋</h1>
            <p class="mt-2 text-blue-100">Your AI marketing agents are working to grow your firm.</p>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Pending Tasks</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">12</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">Awaiting your approval</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Active Agents</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">9</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">Running smoothly</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">This Month</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">847</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">Actions completed</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Integrations</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">3</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">Connected platforms</p>
            </div>
        </div>

        <!-- AI Agents Grid -->
        <div>
            <h3 class="text-xl font-bold text-gray-900 mb-4">AI Agents</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- Blog Posts Agent -->
                <a href="{{ route('ai-agents.blog-posts') }}" class="block group">
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6 border-2 border-transparent group-hover:border-blue-500">
                        <div class="flex items-start justify-between mb-4">
                            <div class="text-4xl">📝</div>
                            <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full flex items-center">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></span>
                                Active
                            </span>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Blog Posts</h4>
                        <p class="text-sm text-gray-600 mb-4">Generate SEO-optimized blog content based on your firm profile</p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-yellow-600 font-medium">3 pending approval</span>
                            <span class="text-gray-400">Last run: 2 hours ago</span>
                        </div>
                    </div>
                </a>

                <!-- Competitor Analysis Agent -->
                <a href="{{ route('ai-agents.competitor-analysis') }}" class="block group">
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6 border-2 border-transparent group-hover:border-blue-500">
                        <div class="flex items-start justify-between mb-4">
                            <div class="text-4xl">🔍</div>
                            <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full flex items-center">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></span>
                                Active
                            </span>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Competitor Analysis</h4>
                        <p class="text-sm text-gray-600 mb-4">Monitor and analyze your competitors' marketing strategies</p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-green-600 font-medium">Up to date</span>
                            <span class="text-gray-400">Last run: 5 hours ago</span>
                        </div>
                    </div>
                </a>

                <!-- Website Analysis Agent -->
                <a href="{{ route('ai-agents.website-analysis') }}" class="block group">
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6 border-2 border-transparent group-hover:border-blue-500">
                        <div class="flex items-start justify-between mb-4">
                            <div class="text-4xl">🌐</div>
                            <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full flex items-center">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></span>
                                Active
                            </span>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Website Analysis</h4>
                        <p class="text-sm text-gray-600 mb-4">Get insights on SEO, performance, and content gaps</p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-blue-600 font-medium">2 recommendations</span>
                            <span class="text-gray-400">Last run: 1 day ago</span>
                        </div>
                    </div>
                </a>

                <!-- Google Business Profile Agent -->
                <a href="{{ route('ai-agents.gbp') }}" class="block group">
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6 border-2 border-transparent group-hover:border-blue-500">
                        <div class="flex items-start justify-between mb-4">
                            <div class="text-4xl">📍</div>
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">Inactive</span>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Google Business Profile</h4>
                        <p class="text-sm text-gray-600 mb-4">Optimize your GBP listing for local search</p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 font-medium">Not configured</span>
                            <span class="text-gray-400">Never run</span>
                        </div>
                    </div>
                </a>

                <!-- Google Ads Agent -->
                <a href="{{ route('ai-agents.google-ads') }}" class="block group">
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6 border-2 border-transparent group-hover:border-blue-500">
                        <div class="flex items-start justify-between mb-4">
                            <div class="text-4xl">💰</div>
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">Inactive</span>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Google Ads</h4>
                        <p class="text-sm text-gray-600 mb-4">Get recommendations for your Google Ads campaigns</p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 font-medium">Not configured</span>
                            <span class="text-gray-400">Never run</span>
                        </div>
                    </div>
                </a>

                <!-- Local Services Ads Agent -->
                <a href="{{ route('ai-agents.lsa') }}" class="block group">
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6 border-2 border-transparent group-hover:border-blue-500">
                        <div class="flex items-start justify-between mb-4">
                            <div class="text-4xl">🎯</div>
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">Inactive</span>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Local Services Ads</h4>
                        <p class="text-sm text-gray-600 mb-4">Optimize your LSA performance and profile</p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 font-medium">Not configured</span>
                            <span class="text-gray-400">Never run</span>
                        </div>
                    </div>
                </a>

                <!-- Meta Ads Agent -->
                <a href="{{ route('ai-agents.meta-ads') }}" class="block group">
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6 border-2 border-transparent group-hover:border-blue-500">
                        <div class="flex items-start justify-between mb-4">
                            <div class="text-4xl">📱</div>
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">Inactive</span>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Meta Ads</h4>
                        <p class="text-sm text-gray-600 mb-4">Facebook and Instagram ad recommendations</p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 font-medium">Not configured</span>
                            <span class="text-gray-400">Never run</span>
                        </div>
                    </div>
                </a>

                <!-- Backlinks Agent -->
                <a href="{{ route('ai-agents.backlinks') }}" class="block group">
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6 border-2 border-transparent group-hover:border-blue-500">
                        <div class="flex items-start justify-between mb-4">
                            <div class="text-4xl">🔗</div>
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">Inactive</span>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Backlinks</h4>
                        <p class="text-sm text-gray-600 mb-4">Find valuable backlink opportunities for SEO</p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 font-medium">Not configured</span>
                            <span class="text-gray-400">Never run</span>
                        </div>
                    </div>
                </a>

                <!-- Analytics Agent -->
                <a href="{{ route('ai-agents.analytics') }}" class="block group">
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6 border-2 border-transparent group-hover:border-blue-500">
                        <div class="flex items-start justify-between mb-4">
                            <div class="text-4xl">📊</div>
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">Inactive</span>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Analytics</h4>
                        <p class="text-sm text-gray-600 mb-4">Unified analytics across all platforms</p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 font-medium">Not configured</span>
                            <span class="text-gray-400">Never run</span>
                        </div>
                    </div>
                </a>

            </div>
        </div>
    </div>
</x-app-sidebar>
