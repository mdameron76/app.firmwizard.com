<x-app-layout>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <!-- Progress Indicator -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    @for ($i = 1; $i <= 5; $i++)
                        <div class="flex items-center {{ $i < 5 ? 'flex-1' : '' }}">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full text-sm {{ $i == 4 ? 'bg-fw-accent text-fw-dark' : ($i < 4 ? 'bg-fw-gold' : 'bg-fw-darker') }} font-semibold">
                                {{ $i }}
                            </div>
                            @if ($i < 5)
                                <div class="flex-1 h-1 mx-2 {{ $i < 4 ? 'bg-fw-gold' : 'bg-fw-darker' }}"></div>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>

            <div class="grid grid-cols-3 gap-8">
                <!-- Left Side - Instructions -->
                <div class="col-span-1">
                    <div class="bg-fw-darker rounded-lg p-6 sticky top-6">
                        <h2 class="text-2xl font-bold text-fw-accent mb-4">Service Area</h2>
                        <p class="text-gray-300 mb-6">
                            Define where you serve clients so we can focus your marketing on the right geographic areas.
                        </p>
                        <div class="text-sm text-gray-400 space-y-2">
                            <p>✓ Specify service area type</p>
                            <p>✓ Add primary location</p>
                            <p>✓ List additional areas</p>
                            <p>✓ Note office locations</p>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Form -->
                <div class="col-span-2">
                    <div class="bg-fw-darker rounded-lg shadow-xl overflow-hidden">
                        <!-- Header -->
                        <div class="px-8 py-6 border-b border-gray-700">
                            <h2 class="text-3xl font-bold text-white">Step 4: Service Area</h2>
                            <p class="mt-2 text-gray-400">Define your geographic service area</p>
                        </div>

                <!-- Form -->
                <form action="{{ route('onboarding.step.store', 4) }}" method="POST" class="px-8 py-6">
                    @csrf

                    <!-- Service Type -->
                    <div class="mb-8">
                        <label class="block text-sm font-medium text-gray-300 mb-3">
                            Service Area Type <span class="text-red-400">*</span>
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center p-3 bg-fw-dark border border-gray-700 rounded-lg hover:border-fw-accent cursor-pointer transition-colors">
                                <input type="radio" 
                                       name="service_type" 
                                       value="local"
                                       required
                                       class="w-4 h-4 text-fw-accent bg-gray-700 border-gray-600 focus:ring-fw-accent focus:ring-2">
                                <span class="ml-3 text-white">Local (City/County)</span>
                            </label>
                            <label class="flex items-center p-3 bg-fw-dark border border-gray-700 rounded-lg hover:border-fw-accent cursor-pointer transition-colors">
                                <input type="radio" 
                                       name="service_type" 
                                       value="regional"
                                       required
                                       class="w-4 h-4 text-fw-accent bg-gray-700 border-gray-600 focus:ring-fw-accent focus:ring-2">
                                <span class="ml-3 text-white">Regional (Multiple Cities/Counties)</span>
                            </label>
                            <label class="flex items-center p-3 bg-fw-dark border border-gray-700 rounded-lg hover:border-fw-accent cursor-pointer transition-colors">
                                <input type="radio" 
                                       name="service_type" 
                                       value="statewide"
                                       required
                                       class="w-4 h-4 text-fw-accent bg-gray-700 border-gray-600 focus:ring-fw-accent focus:ring-2">
                                <span class="ml-3 text-white">Statewide</span>
                            </label>
                            <label class="flex items-center p-3 bg-fw-dark border border-gray-700 rounded-lg hover:border-fw-accent cursor-pointer transition-colors">
                                <input type="radio" 
                                       name="service_type" 
                                       value="national"
                                       required
                                       class="w-4 h-4 text-fw-accent bg-gray-700 border-gray-600 focus:ring-fw-accent focus:ring-2">
                                <span class="ml-3 text-white">National/Multi-State</span>
                            </label>
                        </div>
                        @error('service_type')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Primary Office Location -->
                    <div class="mb-8">
                        <label for="primary_city" class="block text-sm font-medium text-gray-300 mb-2">
                            Primary Office City <span class="text-red-400">*</span>
                        </label>
                        <input type="text" 
                               id="primary_city" 
                               name="primary_city"
                               required
                               placeholder="e.g., Chicago"
                               class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent">
                        @error('primary_city')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-8">
                        <div>
                            <label for="primary_state" class="block text-sm font-medium text-gray-300 mb-2">
                                State <span class="text-red-400">*</span>
                            </label>
                            <input type="text" 
                                   id="primary_state" 
                                   name="primary_state"
                                   required
                                   placeholder="e.g., IL"
                                   maxlength="2"
                                   class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent">
                            @error('primary_state')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="primary_zip" class="block text-sm font-medium text-gray-300 mb-2">
                                ZIP Code <span class="text-red-400">*</span>
                            </label>
                            <input type="text" 
                                   id="primary_zip" 
                                   name="primary_zip"
                                   required
                                   placeholder="e.g., 60601"
                                   maxlength="10"
                                   class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent">
                            @error('primary_zip')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Additional Service Areas -->
                    <div class="mb-8">
                        <label for="additional_areas" class="block text-sm font-medium text-gray-300 mb-2">
                            Additional Service Areas (Optional)
                        </label>
                        <p class="text-sm text-gray-500 mb-3">List other cities, counties, or regions you serve</p>
                        <textarea id="additional_areas" 
                                  name="additional_areas"
                                  rows="3"
                                  placeholder="e.g., Oak Park, Naperville, Cook County, DuPage County..."
                                  class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent"></textarea>
                    </div>

                    <!-- Service Radius -->
                    <div class="mb-8">
                        <label for="service_radius" class="block text-sm font-medium text-gray-300 mb-2">
                            Service Radius
                        </label>
                        <select id="service_radius" 
                                name="service_radius"
                                class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent">
                            <option value="">Select radius...</option>
                            <option value="10">Within 10 miles</option>
                            <option value="25">Within 25 miles</option>
                            <option value="50">Within 50 miles</option>
                            <option value="100">Within 100 miles</option>
                            <option value="statewide">Entire state</option>
                            <option value="unlimited">Nationwide</option>
                        </select>
                    </div>

                    <!-- Virtual Services -->
                    <div class="mb-8">
                        <label class="flex items-center p-4 bg-fw-dark border border-gray-700 rounded-lg">
                            <input type="checkbox" 
                                   name="offers_virtual"
                                   value="1"
                                   class="w-4 h-4 text-fw-accent bg-gray-700 border-gray-600 rounded focus:ring-fw-accent focus:ring-2">
                            <span class="ml-3 text-white">We offer virtual/remote consultations and services</span>
                        </label>
                    </div>

                    <!-- Office Locations -->
                    <div class="mb-8">
                        <label for="office_locations" class="block text-sm font-medium text-gray-300 mb-2">
                            Office Locations (Optional)
                        </label>
                        <p class="text-sm text-gray-500 mb-3">If you have multiple offices, list their addresses</p>
                        <textarea id="office_locations" 
                                  name="office_locations"
                                  rows="3"
                                  placeholder="123 Main St, Chicago, IL 60601&#10;456 Oak Ave, Naperville, IL 60540"
                                  class="w-full px-4 py-3 bg-fw-dark border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-fw-accent focus:border-transparent"></textarea>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-700">
                        <a href="{{ route('onboarding.step', 3) }}" 
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
