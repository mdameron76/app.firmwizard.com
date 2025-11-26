<x-app-layout>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <!-- Progress Indicator -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    @for ($i = 1; $i <= 5; $i++)
                        <div class="flex items-center {{ $i < 5 ? 'flex-1' : '' }}">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full text-sm {{ $i == 2 ? 'bg-fw-accent text-fw-dark' : ($i < 2 ? 'bg-fw-gold' : 'bg-fw-darker') }} font-semibold">
                                {{ $i }}
                            </div>
                            @if ($i < 5)
                                <div class="flex-1 h-1 mx-2 {{ $i < 2 ? 'bg-fw-gold' : 'bg-fw-darker' }}"></div>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>

            <div class="grid grid-cols-3 gap-8">
                <!-- Left Side - Instructions -->
                <div class="col-span-1">
                    <div class="bg-fw-darker rounded-lg p-6 sticky top-6">
                        <h2 class="text-2xl font-bold text-fw-accent mb-4">Practice Areas</h2>
                        <p class="text-gray-300 mb-6">
                            Tell us about your legal expertise and the types of cases you handle. This helps us target the right clients.
                        </p>
                        <div class="text-sm text-gray-400 space-y-2">
                            <p>✓ Select primary practice areas</p>
                            <p>✓ Describe specific case types</p>
                            <p>✓ Share your experience level</p>
                            <p>✓ Highlight notable results</p>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Form -->
                <div class="col-span-2">
                    <div class="bg-fw-darker rounded-lg shadow-xl overflow-hidden">
                        <!-- Header -->
                        <div class="px-8 py-6 border-b border-gray-700">
                            <h2 class="text-3xl font-bold text-white">Step 2: Practice Areas</h2>
                            <p class="mt-2 text-gray-400">Tell us about your firm's practice areas and specializations</p>
                        </div>

                <!-- Form -->
                <form action="{{ route('onboarding.step.store', 2) }}" method="POST" class="px-8 py-6">
                    @csrf

                    <!-- Primary Practice Areas -->
                    <div class="mb-8">
                        <label class="block text-sm font-medium text-gray-300 mb-3">
                            Primary Practice Areas <span class="text-red-400">*</span>
                        </label>
                        <p class="text-sm text-gray-500 mb-4">Select all that apply (minimum 1)</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @php
                                $practiceAreas = [
                                    'Personal Injury',
                                    'Criminal Defense',
                                    'Family Law',
                                    'Estate Planning',
                                    'Real Estate',
                                    'Business Law',
                                    'Employment Law',
                                    'Immigration',
                                    'Bankruptcy',
                                    'Intellectual Property',
                                    'Tax Law',
                                    'Environmental Law',
                                    'Healthcare Law',
                                    'Civil Litigation',
                                    'Workers Compensation',
                                    'Medical Malpractice',
                                ];
                            @endphp
                            
                            @foreach($practiceAreas as $area)
                                <label class="flex items-center p-3 bg-fw-dark border border-gray-700 rounded-lg hover:border-fw-accent cursor-pointer transition-colors">
                                    <input type="checkbox" 
                                           name="practice_areas[]" 
                                           value="{{ $area }}"
                                           class="w-4 h-4 text-fw-accent bg-gray-700 border-gray-600 rounded focus:ring-fw-accent focus:ring-2">
                                    <span class="ml-3 text-white">{{ $area }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('practice_areas')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Other Practice Areas -->
                    <div class="mb-8">
                        <label for="other_practice_areas" class="block text-sm font-medium text-gray-300 mb-2">
                            Other Practice Areas (Optional)
                        </label>
                        <input type="text" 
                               id="other_practice_areas" 
                               name="other_practice_areas"
                               placeholder="e.g., Elder Law, Entertainment Law"
                               class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent">
                    </div>

                    <!-- Case Types -->
                    <div class="mb-8">
                        <label for="case_types" class="block text-sm font-medium text-gray-300 mb-2">
                            Specific Case Types You Handle <span class="text-red-400">*</span>
                        </label>
                        <p class="text-sm text-gray-500 mb-3">Be specific about what cases you take (e.g., "Car accidents, slip and falls, dog bites")</p>
                        <textarea id="case_types" 
                                  name="case_types"
                                  rows="4"
                                  required
                                  placeholder="Describe the specific types of cases your firm handles..."
                                  class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent"></textarea>
                        @error('case_types')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Years in Practice -->
                    <div class="mb-8">
                        <label for="years_in_practice" class="block text-sm font-medium text-gray-300 mb-2">
                            Years in Practice <span class="text-red-400">*</span>
                        </label>
                        <select id="years_in_practice" 
                                name="years_in_practice"
                                required
                                class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent">
                            <option value="">Select years...</option>
                            <option value="0-2">0-2 years</option>
                            <option value="3-5">3-5 years</option>
                            <option value="6-10">6-10 years</option>
                            <option value="11-20">11-20 years</option>
                            <option value="20+">20+ years</option>
                        </select>
                        @error('years_in_practice')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Notable Results/Settlements -->
                    <div class="mb-8">
                        <label for="notable_results" class="block text-sm font-medium text-gray-300 mb-2">
                            Notable Results or Settlements (Optional)
                        </label>
                        <p class="text-sm text-gray-500 mb-3">Highlight any impressive case results or settlements</p>
                        <textarea id="notable_results" 
                                  name="notable_results"
                                  rows="3"
                                  placeholder="e.g., $2.5M settlement for car accident victim, Not guilty verdict in high-profile criminal case..."
                                  class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent"></textarea>
                    </div>

                    <!-- Certifications/Awards -->
                    <div class="mb-8">
                        <label for="certifications" class="block text-sm font-medium text-gray-300 mb-2">
                            Certifications, Awards, or Recognitions (Optional)
                        </label>
                        <p class="text-sm text-gray-500 mb-3">e.g., Board Certified, Super Lawyers, Avvo 10.0 Rating</p>
                        <textarea id="certifications" 
                                  name="certifications"
                                  rows="3"
                                  placeholder="List any professional certifications, awards, or recognitions..."
                                  class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent"></textarea>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-700">
                        <a href="{{ route('onboarding.step', 1) }}" 
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
