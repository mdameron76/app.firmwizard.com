<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-fw-darker rounded-lg shadow-xl overflow-hidden">
                <!-- Header -->
                <div class="px-8 py-6 border-b border-gray-700">
                    <h1 class="text-3xl font-bold text-white">AI Agents Hub</h1>
                    <p class="mt-2 text-gray-400">Trigger AI-powered insights and recommendations for your firm</p>
                </div>

                @if(session('success'))
                    <div class="mx-8 mt-6 bg-green-900 border border-green-700 text-green-200 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mx-8 mt-6 bg-red-900 border border-red-700 text-red-200 px-4 py-3 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Available Agents Grid -->
                <div class="px-8 py-6">
                    <h2 class="text-xl font-semibold text-white mb-6">Available Agents</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($availableAgents as $type => $agent)
                            <div class="bg-fw-dark border border-gray-700 rounded-lg p-6 hover:border-fw-accent transition-colors">
                                <div class="text-4xl mb-3">{{ $agent['icon'] }}</div>
                                <h3 class="text-lg font-semibold text-white mb-2">{{ $agent['name'] }}</h3>
                                <p class="text-gray-400 text-sm mb-4">{{ $agent['description'] }}</p>
                                <form action="{{ route('ai-agents.trigger', $type) }}" method="POST">
                                    @csrf
                                    <button type="submit" 
                                            class="w-full bg-fw-accent hover:bg-fw-accent/80 text-fw-darker font-semibold py-2 px-4 rounded transition-colors">
                                        Trigger Agent
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Recent Jobs -->
                <div class="px-8 py-6 border-t border-gray-700">
                    <h2 class="text-xl font-semibold text-white mb-6">Recent Jobs</h2>
                    
                    @if($recentJobs->isEmpty())
                        <p class="text-gray-400">No AI agent jobs have been run yet. Trigger an agent above to get started!</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Job Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Started</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Completed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-700">
                                    @foreach($recentJobs as $job)
                                        <tr class="hover:bg-fw-dark/50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-white font-medium">
                                                    {{ $availableAgents[$job->job_type]['name'] ?? ucwords(str_replace('_', ' ', $job->job_type)) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($job->status === 'completed')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-900 text-green-200">
                                                        Completed
                                                    </span>
                                                @elseif($job->status === 'running')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-900 text-blue-200">
                                                        Running
                                                    </span>
                                                @elseif($job->status === 'failed')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-900 text-red-200">
                                                        Failed
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-700 text-gray-300">
                                                        Pending
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                                {{ $job->started_at ? $job->started_at->format('M d, Y H:i') : '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                                {{ $job->completed_at ? $job->completed_at->format('M d, Y H:i') : '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @if($job->status === 'completed')
                                                    <a href="{{ route('ai-agents.show', $job) }}" 
                                                       class="text-fw-accent hover:text-fw-accent/80 font-medium">
                                                        View Results
                                                    </a>
                                                @else
                                                    <span class="text-gray-500">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
