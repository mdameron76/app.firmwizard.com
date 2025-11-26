<x-app-layout>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <!-- Progress Indicator -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    @for ($i = 1; $i <= 5; $i++)
                        <div class="flex items-center {{ $i < 5 ? 'flex-1' : '' }}">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full text-sm {{ $i == 5 ? 'bg-fw-accent text-fw-dark' : ($i < 5 ? 'bg-fw-gold' : 'bg-fw-darker') }} font-semibold">
                                {{ $i }}
                            </div>
                            @if ($i < 5)
                                <div class="flex-1 h-1 mx-2 {{ $i < 5 ? 'bg-fw-gold' : 'bg-fw-darker' }}"></div>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>

            <div class="grid grid-cols-3 gap-8">
                <!-- Left Side - Instructions -->
                <div class="col-span-1">
                    <div class="bg-fw-darker rounded-lg p-6 sticky top-6">
                        <h2 class="text-2xl font-bold text-fw-accent mb-4">Marketing Goals</h2>
                        <p class="text-gray-300 mb-6">
                            Share your marketing objectives and budget so we can create a strategy that delivers results.
                        </p>
                        <div class="text-sm text-gray-400 space-y-2">
                            <p>✓ Define your goals</p>
                            <p>✓ Set lead volume targets</p>
                            <p>✓ Specify your budget</p>
                            <p>✓ Choose timeline</p>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Form -->
                <div class="col-span-2">
                    <div class="bg-fw-darker rounded-lg shadow-xl overflow-hidden">
                        <!-- Header -->
                        <div class="px-8 py-6 border-b border-gray-700">
                            <h2 class="text-3xl font-bold text-white">Step 5: Marketing Goals</h2>
                            <p class="mt-2 text-gray-400">Define your marketing objectives and budget</p>
                        </div>

                <!-- Form -->
                <form action="{{ route('onboarding.step.store', 5) }}" method="POST" class="px-8 py-6">
                    @csrf

                    <!-- Primary Marketing Goals -->
                    <div class="mb-8">
                        <label class="block text-sm font-medium text-gray-300 mb-3">
                            Primary Marketing Goals <span class="text-red-400">*</span>
                        </label>
                        <p class="text-sm text-gray-500 mb-4">Select all that apply</p>
                        
                        <div class="space-y-2">
                            @php
                                $goals = [
                                    'increase_leads' => 'Increase qualified leads',
                                    'brand_awareness' => 'Build brand awareness',
                                    'specific_practice' => 'Grow specific practice area',
                                    'case_quality' => 'Improve case quality/value',
                                    'local_visibility' => 'Increase local visibility',
                                    'online_reputation' => 'Improve online reputation',
                                    'website_traffic' => 'Increase website traffic',
                                    'phone_calls' => 'Generate more phone calls',
                                ];
                            @endphp
                            
                            @foreach($goals as $value => $label)
                                <label class="flex items-center p-3 bg-fw-dark border border-gray-700 rounded-lg hover:border-fw-accent cursor-pointer transition-colors">
                                    <input type="checkbox" 
                                           name="marketing_goals[]" 
                                           value="{{ $value }}"
                                           class="w-4 h-4 text-fw-accent bg-gray-700 border-gray-600 rounded focus:ring-fw-accent focus:ring-2">
                                    <span class="ml-3 text-white">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('marketing_goals')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Current Lead Volume -->
                    <div class="mb-8">
                        <label for="current_leads" class="block text-sm font-medium text-gray-300 mb-2">
                            Current Monthly Lead Volume
                        </label>
                        <select id="current_leads" 
                                name="current_leads"
                                class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent">
                            <option value="">Select range...</option>
                            <option value="0-10">0-10 leads/month</option>
                            <option value="11-25">11-25 leads/month</option>
                            <option value="26-50">26-50 leads/month</option>
                            <option value="51-100">51-100 leads/month</option>
                            <option value="100+">100+ leads/month</option>
                        </select>
                    </div>

                    <!-- Desired Lead Volume -->
                    <div class="mb-8">
                        <label for="desired_leads" class="block text-sm font-medium text-gray-300 mb-2">
                            Desired Monthly Lead Volume
                        </label>
                        <select id="desired_leads" 
                                name="desired_leads"
                                class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent">
                            <option value="">Select range...</option>
                            <option value="10-25">10-25 leads/month</option>
                            <option value="26-50">26-50 leads/month</option>
                            <option value="51-100">51-100 leads/month</option>
                            <option value="101-200">101-200 leads/month</option>
                            <option value="200+">200+ leads/month</option>
                        </select>
                    </div>

                    <!-- Monthly Marketing Budget -->
                    <div class="mb-8">
                        <label for="monthly_budget" class="block text-sm font-medium text-gray-300 mb-2">
                            Monthly Marketing Budget <span class="text-red-400">*</span>
                        </label>
                        <select id="monthly_budget" 
                                name="monthly_budget"
                                required
                                class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent">
                            <option value="">Select budget range...</option>
                            <option value="under_1k">Under $1,000</option>
                            <option value="1k_3k">$1,000 - $3,000</option>
                            <option value="3k_5k">$3,000 - $5,000</option>
                            <option value="5k_10k">$5,000 - $10,000</option>
                            <option value="10k_20k">$10,000 - $20,000</option>
                            <option value="20k_plus">$20,000+</option>
                        </select>
                        @error('monthly_budget')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Current Marketing Channels -->
                    <div class="mb-8">
                        <label class="block text-sm font-medium text-gray-300 mb-3">
                            Current Marketing Channels
                        </label>
                        <p class="text-sm text-gray-500 mb-4">What are you currently using?</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @php
                                $channels = [
                                    'Google Ads',
                                    'Facebook Ads',
                                    'SEO',
                                    'Local Services Ads',
                                    'Google Business Profile',
                                    'Social Media',
                                    'Content Marketing/Blog',
                                    'Email Marketing',
                                    'Referrals',
                                    'Print/Traditional Media',
                                ];
                            @endphp
                            
                            @foreach($channels as $channel)
                                <label class="flex items-center p-3 bg-fw-dark border border-gray-700 rounded-lg hover:border-fw-accent cursor-pointer transition-colors">
                                    <input type="checkbox" 
                                           name="current_channels[]" 
                                           value="{{ $channel }}"
                                           class="w-4 h-4 text-fw-accent bg-gray-700 border-gray-600 rounded focus:ring-fw-accent focus:ring-2">
                                    <span class="ml-3 text-white">{{ $channel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Target Case Value -->
                    <div class="mb-8">
                        <label for="target_case_value" class="block text-sm font-medium text-gray-300 mb-2">
                            Target Case Value/Fee
                        </label>
                        <p class="text-sm text-gray-500 mb-3">What's your typical or desired case value?</p>
                        <select id="target_case_value" 
                                name="target_case_value"
                                class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent">
                            <option value="">Select range...</option>
                            <option value="under_1k">Under $1,000</option>
                            <option value="1k_5k">$1,000 - $5,000</option>
                            <option value="5k_10k">$5,000 - $10,000</option>
                            <option value="10k_25k">$10,000 - $25,000</option>
                            <option value="25k_50k">$25,000 - $50,000</option>
                            <option value="50k_plus">$50,000+</option>
                        </select>
                    </div>

                    <!-- Timeline -->
                    <div class="mb-8">
                        <label for="timeline" class="block text-sm font-medium text-gray-300 mb-2">
                            When do you want to see results? <span class="text-red-400">*</span>
                        </label>
                        <select id="timeline" 
                                name="timeline"
                                required
                                class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent">
                            <option value="">Select timeline...</option>
                            <option value="immediate">Immediate (1-2 months)</option>
                            <option value="short_term">Short term (3-6 months)</option>
                            <option value="medium_term">Medium term (6-12 months)</option>
                            <option value="long_term">Long term (1+ year)</option>
                        </select>
                        @error('timeline')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Additional Notes -->
                    <div class="mb-8">
                        <label for="additional_notes" class="block text-sm font-medium text-gray-300 mb-2">
                            Additional Marketing Notes (Optional)
                        </label>
                        <p class="text-sm text-gray-500 mb-3">Anything else we should know about your marketing goals?</p>
                        <textarea id="additional_notes" 
                                  name="additional_notes"
                                  rows="4"
                                  placeholder="Any specific challenges, past experiences, or priorities we should know about..."
                                  class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent"></textarea>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-700">
                        <a href="{{ route('onboarding.step', 4) }}" 
                           class="px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-lg transition-colors">
                            ← Back
                        </a>
                        <button type="submit" 
                                class="px-6 py-3 bg-fw-gold hover:bg-fw-gold/80 text-fw-darker font-bold rounded-lg transition-colors">
                            Complete Onboarding 🎉
                        </button>
                    </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
