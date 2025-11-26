<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Dashboard') }} - {{ $firm->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if (!$onboardingComplete)
                <!-- Onboarding CTA -->
                <div class="bg-gradient-to-r from-fw-accent to-fw-gold rounded-lg shadow-lg p-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-fw-dark mb-2">Complete Your Onboarding</h3>
                            <p class="text-fw-darker">Finish setting up your firm profile to unlock all features and start getting insights.</p>
                        </div>
                        <a href="{{ route('onboarding.index') }}" class="px-6 py-3 bg-fw-dark text-white font-semibold rounded-lg hover:bg-opacity-90 transition whitespace-nowrap">
                            Continue Setup →
                        </a>
                    </div>
                </div>
            @endif

            <!-- Key Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Leads This Month -->
                <div class="bg-fw-darker rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-400">Leads This Month</h3>
                        <svg class="w-5 h-5 text-fw-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    @if ($metrics['leads_monthly'])
                        <div class="text-3xl font-bold text-white">{{ number_format($metrics['leads_monthly']->metric_value) }}</div>
                        <p class="text-xs text-gray-500 mt-1">{{ now()->format('F Y') }}</p>
                    @else
                        <div class="text-gray-500 text-sm">No data yet</div>
                    @endif
                </div>

                <!-- Visibility Score -->
                <div class="bg-fw-darker rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-400">Visibility Score</h3>
                        <svg class="w-5 h-5 text-fw-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                    @if ($metrics['visibility_score'])
                        <div class="text-3xl font-bold text-white">{{ number_format($metrics['visibility_score']->metric_value) }}<span class="text-lg">/100</span></div>
                        <p class="text-xs text-gray-500 mt-1">Updated {{ $metrics['visibility_score']->date->diffForHumans() }}</p>
                    @else
                        <div class="text-gray-500 text-sm">No data yet</div>
                    @endif
                </div>

                <!-- Active AI Jobs -->
                <div class="bg-fw-darker rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-400">Active AI Jobs</h3>
                        <svg class="w-5 h-5 text-fw-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-bold text-white">{{ $recentJobs->where('status', 'running')->count() }}</div>
                    <p class="text-xs text-gray-500 mt-1">{{ $recentJobs->count() }} total jobs</p>
                </div>
            </div>

            <!-- AI Agents Hub -->
            <div class="bg-fw-darker rounded-lg shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-white">AI Agents Hub</h3>
                    <a href="{{ route('ai-agents.index') }}" class="text-fw-accent hover:text-fw-accent/80 text-sm font-medium">
                        View All →
                    </a>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        @foreach (['blog_post' => 'Blog Posts', 'competitor_analysis' => 'Competitors', 'website_analysis' => 'Website', 'gbp_analysis' => 'Google Business', 'google_ads' => 'Google Ads'] as $type => $label)
                            <div class="bg-fw-dark rounded-lg p-4 hover:bg-opacity-80 transition cursor-pointer">
                                <div class="text-center">
                                    <div class="text-2xl mb-2">{{ ['blog_post' => '📝', 'competitor_analysis' => '🔍', 'website_analysis' => '🌐', 'gbp_analysis' => '📍', 'google_ads' => '💰'][$type] }}</div>
                                    <div class="text-sm font-medium text-white">{{ $label }}</div>
                                    @php
                                        $jobCount = $recentJobs->where('job_type', $type)->count();
                                    @endphp
                                    @if ($jobCount > 0)
                                        <div class="text-xs text-fw-accent mt-1">{{ $jobCount }} job(s)</div>
                                    @else
                                        <div class="text-xs text-gray-500 mt-1">Ready</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-6 text-center">
                        <a href="{{ route('ai-agents.index') }}" 
                           class="inline-block bg-fw-accent hover:bg-fw-accent/80 text-fw-darker font-semibold py-2 px-6 rounded transition-colors">
                            Launch AI Agents Hub
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            @if ($recentJobs->count() > 0)
                <div class="bg-fw-darker rounded-lg shadow-lg p-6">
                    <h3 class="text-xl font-semibold text-white mb-4">Recent AI Jobs</h3>
                    <div class="space-y-3">
                        @foreach ($recentJobs as $job)
                            <div class="bg-fw-dark rounded-lg p-4 flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="w-2 h-2 rounded-full {{ ['pending' => 'bg-gray-500', 'running' => 'bg-fw-accent', 'completed' => 'bg-fw-gold', 'failed' => 'bg-red-500'][$job->status] }}"></div>
                                    <div>
                                        <div class="text-white font-medium">{{ ucwords(str_replace('_', ' ', $job->job_type)) }}</div>
                                        <div class="text-xs text-gray-400">{{ $job->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                                <span class="px-3 py-1 text-xs rounded-full {{ ['pending' => 'bg-gray-700 text-gray-300', 'running' => 'bg-fw-accent/20 text-fw-accent', 'completed' => 'bg-fw-gold/20 text-fw-gold', 'failed' => 'bg-red-500/20 text-red-400'][$job->status] }}">
                                    {{ ucfirst($job->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
