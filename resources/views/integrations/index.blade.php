@extends('layouts.app-sidebar')

@section('content')
    <div class="space-y-6">
        <!-- Coming Soon Banner -->
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
            <div class="flex items-start space-x-4">
                <div class="text-4xl">🚧</div>
                <div>
                    <h3 class="text-lg font-semibold text-purple-900">Integration Management - Coming Soon!</h3>
                    <p class="text-purple-700 mt-2">
                        Connect your marketing platforms to enable automated AI agents.
                    </p>
                </div>
            </div>
        </div>

        <!-- Available Integrations Preview -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Available Integrations</h3>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <span class="text-2xl">📝</span>
                        <div>
                            <h4 class="font-semibold text-gray-900">WordPress</h4>
                            <p class="text-sm text-gray-500">Connect your WordPress site for blog publishing</p>
                        </div>
                    </div>
                    <button disabled class="px-4 py-2 bg-gray-100 text-gray-400 rounded-lg text-sm font-medium cursor-not-allowed">
                        Coming Soon
                    </button>
                </div>

                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <span class="text-2xl">📍</span>
                        <div>
                            <h4 class="font-semibold text-gray-900">Google Business Profile</h4>
                            <p class="text-sm text-gray-500">Manage your GBP listing automatically</p>
                        </div>
                    </div>
                    <button disabled class="px-4 py-2 bg-gray-100 text-gray-400 rounded-lg text-sm font-medium cursor-not-allowed">
                        Coming Soon
                    </button>
                </div>

                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <span class="text-2xl">💰</span>
                        <div>
                            <h4 class="font-semibold text-gray-900">Google Ads</h4>
                            <p class="text-sm text-gray-500">Optimize your Google Ads campaigns</p>
                        </div>
                    </div>
                    <button disabled class="px-4 py-2 bg-gray-100 text-gray-400 rounded-lg text-sm font-medium cursor-not-allowed">
                        Coming Soon
                    </button>
                </div>

                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <span class="text-2xl">📱</span>
                        <div>
                            <h4 class="font-semibold text-gray-900">Meta Ads</h4>
                            <p class="text-sm text-gray-500">Connect Facebook & Instagram advertising</p>
                        </div>
                    </div>
                    <button disabled class="px-4 py-2 bg-gray-100 text-gray-400 rounded-lg text-sm font-medium cursor-not-allowed">
                        Coming Soon
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
