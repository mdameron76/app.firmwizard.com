<x-app-layout>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <!-- Progress Indicator -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    @for ($i = 1; $i <= 5; $i++)
                        <div class="flex items-center {{ $i < 5 ? 'flex-1' : '' }}">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full text-sm {{ $i == 3 ? 'bg-fw-accent text-fw-dark' : ($i < 3 ? 'bg-fw-gold' : 'bg-fw-darker') }} font-semibold">
                                {{ $i }}
                            </div>
                            @if ($i < 5)
                                <div class="flex-1 h-1 mx-2 {{ $i < 3 ? 'bg-fw-gold' : 'bg-fw-darker' }}"></div>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>

            <div class="grid grid-cols-3 gap-8">
                <!-- Left Side - Instructions -->
                <div class="col-span-1">
                    <div class="bg-fw-darker rounded-lg p-6 sticky top-6">
                        <h2 class="text-2xl font-bold text-fw-accent mb-4">Target Clients</h2>
                        <p class="text-gray-300 mb-6">
                            Understanding your ideal clients helps us create messaging that resonates and attracts the right cases.
                        </p>
                        <div class="text-sm text-gray-400 space-y-2">
                            <p>✓ Define client demographics</p>
                            <p>✓ Describe client characteristics</p>
                            <p>✓ Identify common situations</p>
                            <p>✓ Note typical concerns</p>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Form -->
                <div class="col-span-2">
                    <div class="bg-fw-darker rounded-lg shadow-xl overflow-hidden">
                        <!-- Header -->
                        <div class="px-8 py-6 border-b border-gray-700">
                            <h2 class="text-3xl font-bold text-white">Step 3: Target Clients</h2>
                            <p class="mt-2 text-gray-400">Help us understand who your ideal clients are</p>
                        </div>

                <!-- Form -->
                <form action="{{ route('onboarding.step.store', 3) }}" method="POST" class="px-8 py-6">
                    @csrf

                    <!-- Demographics -->
                    <div class="mb-8">
                        <label class="block text-sm font-medium text-gray-300 mb-3">
                            Client Demographics <span class="text-red-400">*</span>
                        </label>
                        <p class="text-sm text-gray-500 mb-4">Select all age groups that represent your typical clients</p>
                        
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            @php
                                $ageGroups = ['18-24', '25-34', '35-44', '45-54', '55-64', '65+'];
                            @endphp
                            
                            @foreach($ageGroups as $age)
                                <label class="flex items-center p-3 bg-fw-dark border border-gray-700 rounded-lg hover:border-fw-accent cursor-pointer transition-colors">
                                    <input type="checkbox" 
                                           name="age_groups[]" 
                                           value="{{ $age }}"
                                           class="w-4 h-4 text-fw-accent bg-gray-700 border-gray-600 rounded focus:ring-fw-accent focus:ring-2">
                                    <span class="ml-3 text-white">{{ $age }} years</span>
                                </label>
                            @endforeach
                        </div>
                        @error('age_groups')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Income Level -->
                    <div class="mb-8">
                        <label class="block text-sm font-medium text-gray-300 mb-3">
                            Typical Client Income Level
                        </label>
                        <div class="space-y-2">
                            @php
                                $incomeRanges = [
                                    'under_50k' => 'Under $50,000',
                                    '50k_100k' => '$50,000 - $100,000',
                                    '100k_200k' => '$100,000 - $200,000',
                                    '200k_plus' => '$200,000+',
                                    'mixed' => 'Mixed/Varies'
                                ];
                            @endphp
                            
                            @foreach($incomeRanges as $value => $label)
                                <label class="flex items-center p-3 bg-fw-dark border border-gray-700 rounded-lg hover:border-fw-accent cursor-pointer transition-colors">
                                    <input type="radio" 
                                           name="income_level" 
                                           value="{{ $value }}"
                                           class="w-4 h-4 text-fw-accent bg-gray-700 border-gray-600 focus:ring-fw-accent focus:ring-2">
                                    <span class="ml-3 text-white">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Client Type -->
                    <div class="mb-8">
                        <label class="block text-sm font-medium text-gray-300 mb-3">
                            Client Type <span class="text-red-400">*</span>
                        </label>
                        <div class="space-y-2">
                            @php
                                $clientTypes = [
                                    'individuals' => 'Individuals/Consumers',
                                    'small_business' => 'Small Businesses',
                                    'large_business' => 'Large Businesses/Corporations',
                                    'mixed' => 'Both Individuals & Businesses'
                                ];
                            @endphp
                            
                            @foreach($clientTypes as $value => $label)
                                <label class="flex items-center p-3 bg-fw-dark border border-gray-700 rounded-lg hover:border-fw-accent cursor-pointer transition-colors">
                                    <input type="radio" 
                                           name="client_type" 
                                           value="{{ $value }}"
                                           required
                                           class="w-4 h-4 text-fw-accent bg-gray-700 border-gray-600 focus:ring-fw-accent focus:ring-2">
                                    <span class="ml-3 text-white">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('client_type')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Psychographics -->
                    <div class="mb-8">
                        <label for="client_psychographics" class="block text-sm font-medium text-gray-300 mb-2">
                            Client Characteristics & Behaviors <span class="text-red-400">*</span>
                        </label>
                        <p class="text-sm text-gray-500 mb-3">Describe your typical client's personality, values, concerns, and decision-making patterns</p>
                        <textarea id="client_psychographics" 
                                  name="client_psychographics"
                                  rows="4"
                                  required
                                  placeholder="e.g., Budget-conscious families seeking honest legal help, Detail-oriented business owners who value clear communication..."
                                  class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent"></textarea>
                        @error('client_psychographics')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Common Situations -->
                    <div class="mb-8">
                        <label for="common_situations" class="block text-sm font-medium text-gray-300 mb-2">
                            Common Situations/Triggers <span class="text-red-400">*</span>
                        </label>
                        <p class="text-sm text-gray-500 mb-3">What situations cause clients to seek your services?</p>
                        <textarea id="common_situations" 
                                  name="common_situations"
                                  rows="4"
                                  required
                                  placeholder="e.g., Recently injured in car accident, Arrested and facing charges, Going through divorce, Starting a business..."
                                  class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent"></textarea>
                        @error('common_situations')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Client Objections -->
                    <div class="mb-8">
                        <label for="client_objections" class="block text-sm font-medium text-gray-300 mb-2">
                            Common Client Concerns/Objections (Optional)
                        </label>
                        <p class="text-sm text-gray-500 mb-3">What hesitations or concerns do clients typically have?</p>
                        <textarea id="client_objections" 
                                  name="client_objections"
                                  rows="3"
                                  placeholder="e.g., Worried about cost, Don't know if they have a case, Concerned about time commitment, Nervous about court..."
                                  class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent"></textarea>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-700">
                        <a href="{{ route('onboarding.step', 2) }}" 
                           class="px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-lg transition-colors">
                            ← Back
                        </a>
                        <button type="submit" 
                                class="px-6 py-3 bg-fw-accent hover:bg-fw-accent/80 text-fw-darker font-semibold rounded-lg transition-colors">
                            Save & Continue →
                        </button>
                    </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
