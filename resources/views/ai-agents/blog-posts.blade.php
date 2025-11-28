@extends('layouts.app-sidebar')

@section('content')
    <div class="space-y-6">
        <!-- Coming Soon Banner -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex items-start space-x-4">
                <div class="text-4xl">🚧</div>
                <div>
                    <h3 class="text-lg font-semibold text-blue-900">Blog Post Management - Coming Soon!</h3>
                    <p class="text-blue-700 mt-2">
                        This feature is currently under development. Soon you'll be able to:
                    </p>
                    <ul class="mt-3 space-y-2 text-blue-700">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Generate AI-powered blog posts based on your marketing recommendations
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Review and approve blog posts before publishing
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Automatically publish to your WordPress site
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Track performance and SEO metrics
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Placeholder Content -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">What to Expect</h3>
            <p class="text-gray-600 mb-4">
                The Blog Post Agent will analyze your firm's AI marketing recommendations and automatically generate 
                SEO-optimized blog content tailored to your practice areas and target audience.
            </p>
            <p class="text-gray-600">
                You'll be able to review each post, make edits if needed, and publish directly to your WordPress 
                site with a single click.
            </p>
        </div>
    </div>
@endsection
