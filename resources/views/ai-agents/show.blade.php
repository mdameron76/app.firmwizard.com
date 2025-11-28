@extends('layouts.app-sidebar')

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="px-8 py-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            {{ ucwords(str_replace('_', ' ', $job->job_type)) }} Results
                        </h1>
                        <p class="mt-2 text-gray-600">
                            Started: {{ $job->started_at ? $job->started_at->format('M d, Y \a\t H:i') : 'Not started' }}
                        </p>
                    </div>
                    <a href="{{ route('ai-agents.index') }}" 
                       class="bg-fw-dark hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded transition-colors">
                        ← Back to AI Agents
                    </a>
                </div>
                </div>

                <!-- Status Badge -->
                <div class="px-8 py-4 bg-fw-dark border-b border-gray-700">
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-400 font-medium">Status:</span>
                        @if($job->status === 'completed')
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-900 text-green-200">
                                ✓ Completed
                            </span>
                            @if($job->completed_at)
                                <span class="text-gray-400 text-sm">
                                    Finished: {{ $job->completed_at->format('M d, Y \a\t H:i') }}
                                </span>
                            @endif
                        @elseif($job->status === 'running')
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-blue-900 text-blue-200">
                                ⟳ Running
                            </span>
                        @elseif($job->status === 'failed')
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-red-900 text-red-200">
                                ✗ Failed
                            </span>
                        @else
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-gray-700 text-gray-300">
                                ⋯ Pending
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Results Content -->
                <div class="px-8 py-6">
                    @if($job->status === 'completed' && $job->output_json)
                        @php
                            $output = is_string($job->output_json) ? json_decode($job->output_json, true) : $job->output_json;
                        @endphp

                        @if($job->job_type === 'blog_post')
                            <!-- Blog Post Topics -->
                            <div class="space-y-6">
                                <h2 class="text-2xl font-semibold text-white mb-4">Recommended Blog Topics</h2>
                                @foreach($output['topics'] ?? [] as $topic)
                                    <div class="bg-fw-dark border border-gray-700 rounded-lg p-6">
                                        <h3 class="text-xl font-semibold text-fw-accent mb-2">{{ $topic['title'] ?? 'Untitled' }}</h3>
                                        @if(isset($topic['description']))
                                            <p class="text-gray-300 mb-3">{{ $topic['description'] }}</p>
                                        @endif
                                        <div class="flex items-center space-x-4 text-sm">
                                            @if(isset($topic['seo_score']))
                                                <span class="text-gray-400">SEO Score: 
                                                    <span class="text-fw-gold font-semibold">{{ $topic['seo_score'] }}/10</span>
                                                </span>
                                            @endif
                                            @if(isset($topic['keywords']) && is_array($topic['keywords']))
                                                <span class="text-gray-400">Keywords: 
                                                    <span class="text-white">{{ implode(', ', $topic['keywords']) }}</span>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                        @elseif($job->job_type === 'competitor_analysis')
                            <!-- Competitor Analysis -->
                            <div class="space-y-6">
                                <h2 class="text-2xl font-semibold text-white mb-4">Competitor Analysis</h2>
                                
                                <!-- Competitor Overview -->
                                @if(isset($output['competitor_overview']))
                                    <div class="mb-8">
                                        <h3 class="text-xl font-semibold text-fw-accent mb-4">Competitor Overview</h3>
                                        @foreach($output['competitor_overview'] as $competitor)
                                            <div class="bg-fw-dark border border-gray-700 rounded-lg p-6 mb-4">
                                                <div class="flex items-start justify-between mb-4">
                                                    <div>
                                                        <h4 class="text-lg font-semibold text-white">{{ $competitor['name'] ?? 'Unknown Competitor' }}</h4>
                                                        <p class="text-gray-400 text-sm">
                                                            {{ $competitor['city'] ?? '' }} • {{ $competitor['primary_practice'] ?? '' }}
                                                        </p>
                                                    </div>
                                                    @if(isset($competitor['visibility_score']))
                                                        <div class="text-right">
                                                            <div class="text-2xl font-bold text-fw-gold">{{ $competitor['visibility_score'] }}/100</div>
                                                            <div class="text-xs text-gray-400">Visibility Score</div>
                                                        </div>
                                                    @endif
                                                </div>
                                                
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    @if(isset($competitor['strengths']))
                                                        <div>
                                                            <h5 class="text-sm font-semibold text-gray-400 mb-2">Strengths</h5>
                                                            <ul class="list-disc list-inside text-gray-300 text-sm space-y-1">
                                                                @foreach($competitor['strengths'] as $strength)
                                                                    <li>{{ $strength }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                    @if(isset($competitor['weaknesses']))
                                                        <div>
                                                            <h5 class="text-sm font-semibold text-gray-400 mb-2">Weaknesses</h5>
                                                            <ul class="list-disc list-inside text-gray-300 text-sm space-y-1">
                                                                @foreach($competitor['weaknesses'] as $weakness)
                                                                    <li>{{ $weakness }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                
                                <!-- Opportunities -->
                                @if(isset($output['opportunities']))
                                    <div class="mb-8">
                                        <h3 class="text-xl font-semibold text-fw-accent mb-4">Growth Opportunities</h3>
                                        @foreach($output['opportunities'] as $opportunity)
                                            <div class="bg-fw-dark border border-gray-700 rounded-lg p-6 mb-4">
                                                <div class="flex items-start gap-4">
                                                    @if(isset($opportunity['priority']))
                                                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                                                            {{ $opportunity['priority'] === 'high' ? 'bg-red-900 text-red-200' : '' }}
                                                            {{ $opportunity['priority'] === 'medium' ? 'bg-yellow-900 text-yellow-200' : '' }}
                                                            {{ $opportunity['priority'] === 'low' ? 'bg-blue-900 text-blue-200' : '' }}">
                                                            {{ strtoupper($opportunity['priority']) }}
                                                        </span>
                                                    @endif
                                                    <div class="flex-1">
                                                        <h4 class="text-lg font-semibold text-white mb-2">{{ $opportunity['description'] ?? '' }}</h4>
                                                        @if(isset($opportunity['why_it_matters']))
                                                            <p class="text-gray-400 text-sm mb-2">
                                                                <span class="font-semibold">Why it matters:</span> {{ $opportunity['why_it_matters'] }}
                                                            </p>
                                                        @endif
                                                        @if(isset($opportunity['first_step']))
                                                            <p class="text-fw-gold text-sm">
                                                                <span class="font-semibold">First step:</span> {{ $opportunity['first_step'] }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                
                                <!-- Gaps -->
                                @if(isset($output['gaps']) && count($output['gaps']) > 0)
                                    <div>
                                        <h3 class="text-xl font-semibold text-fw-accent mb-4">Identified Gaps</h3>
                                        <div class="bg-fw-dark border border-gray-700 rounded-lg p-6">
                                            <ul class="list-disc list-inside text-gray-300 space-y-2">
                                                @foreach($output['gaps'] as $gap)
                                                    <li>{{ $gap }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endif
                            </div>

                        @elseif($job->job_type === 'website_analysis')
                            <!-- Website Analysis -->
                            <div class="space-y-6">
                                <h2 class="text-2xl font-semibold text-white mb-4">Website Analysis</h2>
                                
                                <!-- Overall Score -->
                                <div class="bg-fw-dark border border-gray-700 rounded-lg p-6">
                                    <div class="text-center mb-6">
                                        <div class="text-6xl font-bold text-fw-gold mb-2">{{ $output['overall_score'] ?? 0 }}/100</div>
                                        <div class="text-gray-400">Overall Website Score</div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div class="text-center">
                                            <div class="text-3xl font-bold text-white">{{ $output['seo_score'] ?? 0 }}</div>
                                            <div class="text-xs text-gray-400">SEO</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-3xl font-bold text-white">{{ $output['performance_score'] ?? 0 }}</div>
                                            <div class="text-xs text-gray-400">Performance</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-3xl font-bold text-white">{{ $output['accessibility_score'] ?? 0 }}</div>
                                            <div class="text-xs text-gray-400">Accessibility</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-3xl font-bold text-white">{{ $output['ux_score'] ?? 0 }}</div>
                                            <div class="text-xs text-gray-400">User Experience</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Recommendations -->
                                <div class="bg-fw-dark border border-gray-700 rounded-lg p-6">
                                    <h3 class="text-xl font-semibold text-white mb-4">Recommendations</h3>
                                    <div class="space-y-3">
                                        @foreach($output['recommendations'] ?? [] as $rec)
                                            <div class="flex items-start space-x-3">
                                                <span class="text-fw-accent mt-1">→</span>
                                                <div class="flex-1">
                                                    <div class="text-white font-medium">{{ $rec['title'] }}</div>
                                                    <div class="text-gray-400 text-sm">{{ $rec['description'] }}</div>
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        Priority: <span class="text-fw-gold">{{ ucfirst($rec['priority']) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                        @else
                            <!-- Generic JSON Output -->
                            <div class="bg-fw-dark border border-gray-700 rounded-lg p-6">
                                <h3 class="text-xl font-semibold text-white mb-4">Results</h3>
                                <pre class="bg-black text-green-400 p-4 rounded overflow-x-auto text-sm font-mono">{{ json_encode($output, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        @endif

                    @elseif($job->status === 'failed')
                        <div class="bg-red-900/20 border border-red-700 rounded-lg p-6">
                            <h3 class="text-xl font-semibold text-red-400 mb-2">Job Failed</h3>
                            <p class="text-gray-300">This AI agent job encountered an error and could not be completed.</p>
                            @if($job->error_message)
                                <div class="mt-4 p-4 bg-black rounded text-red-400 font-mono text-sm">
                                    {{ $job->error_message }}
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="bg-blue-900/20 border border-blue-700 rounded-lg p-6">
                            <h3 class="text-xl font-semibold text-blue-400 mb-2">Job In Progress</h3>
                            <p class="text-gray-300">This AI agent is currently {{ $job->status }}. Please check back in a few moments.</p>
                            <button onclick="window.location.reload()" 
                                    class="mt-4 bg-fw-accent hover:bg-fw-accent/80 text-fw-darker font-semibold py-2 px-4 rounded transition-colors">
                                Refresh Page
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
