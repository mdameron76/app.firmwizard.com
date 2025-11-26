<x-app-layout>
    <div class="py-12" x-data="step1Data()">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <!-- Progress Indicator -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    @for ($i = 1; $i <= 5; $i++)
                        <div class="flex items-center {{ $i < 5 ? 'flex-1' : '' }}">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full text-sm {{ $i == 1 ? 'bg-fw-accent text-fw-dark' : ($i < 1 ? 'bg-fw-gold' : 'bg-fw-darker') }} font-semibold">
                                {{ $i }}
                            </div>
                            @if ($i < 5)
                                <div class="flex-1 h-1 mx-2 {{ $i < 1 ? 'bg-fw-gold' : 'bg-fw-darker' }}"></div>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>

            <div class="grid grid-cols-3 gap-8">
                <!-- Left Side - Instructions -->
                <div class="col-span-1">
                    <div class="bg-fw-darker rounded-lg p-6 sticky top-6">
                        <h2 class="text-2xl font-bold text-fw-accent mb-4">Brand Voice</h2>
                        <p class="text-gray-300 mb-6">
                            Define how your firm communicates with potential clients. This helps us tailor all marketing content to match your firm's personality.
                        </p>
                        <div class="text-sm text-gray-400 space-y-2">
                            <p>✓ Choose a tone that resonates</p>
                            <p>✓ Select your communication style</p>
                            <p>✓ Pick core values (3-5)</p>
                            <p>✓ Craft your message</p>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Form -->
                <div class="col-span-2">
                    <form @submit.prevent="submitStep" class="space-y-6">
                        <!-- Tone -->
                        <div class="bg-fw-darker rounded-lg p-6">
                            <label class="block text-lg font-semibold text-white mb-4">Tone</label>
                            <div class="grid grid-cols-2 gap-4">
                                <div @click="formData.tone = 'Compassionate yet strong'" :class="formData.tone === 'Compassionate yet strong' ? 'bg-fw-accent text-fw-dark' : 'bg-fw-dark text-gray-300 hover:bg-opacity-80'" class="p-4 rounded-lg cursor-pointer transition border-2 border-transparent" :class="{'!border-fw-gold': formData.tone === 'Compassionate yet strong'}">
                                    <div class="font-semibold">Compassionate yet strong</div>
                                </div>
                                <div @click="formData.tone = 'Direct and fearless'" :class="formData.tone === 'Direct and fearless' ? 'bg-fw-accent text-fw-dark' : 'bg-fw-dark text-gray-300 hover:bg-opacity-80'" class="p-4 rounded-lg cursor-pointer transition border-2 border-transparent" :class="{'!border-fw-gold': formData.tone === 'Direct and fearless'}">
                                    <div class="font-semibold">Direct and fearless</div>
                                </div>
                                <div @click="formData.tone = 'Calm and reassuring'" :class="formData.tone === 'Calm and reassuring' ? 'bg-fw-accent text-fw-dark' : 'bg-fw-dark text-gray-300 hover:bg-opacity-80'" class="p-4 rounded-lg cursor-pointer transition border-2 border-transparent" :class="{'!border-fw-gold': formData.tone === 'Calm and reassuring'}">
                                    <div class="font-semibold">Calm and reassuring</div>
                                </div>
                                <div @click="formData.tone = 'Strategic and precise'" :class="formData.tone === 'Strategic and precise' ? 'bg-fw-accent text-fw-dark' : 'bg-fw-dark text-gray-300 hover:bg-opacity-80'" class="p-4 rounded-lg cursor-pointer transition border-2 border-transparent" :class="{'!border-fw-gold': formData.tone === 'Strategic and precise'}">
                                    <div class="font-semibold">Strategic and precise</div>
                                </div>
                            </div>
                        </div>

                        <!-- Style -->
                        <div class="bg-fw-darker rounded-lg p-6">
                            <label class="block text-lg font-semibold text-white mb-4">Communication Style</label>
                            <div class="space-y-3">
                                <div @click="formData.style = 'Professional but approachable'" :class="formData.style === 'Professional but approachable' ? 'bg-fw-accent text-fw-dark' : 'bg-fw-dark text-gray-300 hover:bg-opacity-80'" class="p-4 rounded-lg cursor-pointer transition">
                                    <div class="font-semibold">Professional but approachable</div>
                                </div>
                                <div @click="formData.style = 'Highly formal'" :class="formData.style === 'Highly formal' ? 'bg-fw-accent text-fw-dark' : 'bg-fw-dark text-gray-300 hover:bg-opacity-80'" class="p-4 rounded-lg cursor-pointer transition">
                                    <div class="font-semibold">Highly formal</div>
                                </div>
                                <div @click="formData.style = 'Conversational and plain-language'" :class="formData.style === 'Conversational and plain-language' ? 'bg-fw-accent text-fw-dark' : 'bg-fw-dark text-gray-300 hover:bg-opacity-80'" class="p-4 rounded-lg cursor-pointer transition">
                                    <div class="font-semibold">Conversational and plain-language</div>
                                </div>
                            </div>
                        </div>

                        <!-- Core Values -->
                        <div class="bg-fw-darker rounded-lg p-6">
                            <label class="block text-lg font-semibold text-white mb-2">Core Values</label>
                            <p class="text-sm text-gray-400 mb-4">Select 3-5 values that define your firm</p>
                            <div class="grid grid-cols-2 gap-3">
                                <template x-for="value in availableValues" :key="value">
                                    <div @click="toggleValue(value)" :class="formData.values.includes(value) ? 'bg-fw-accent text-fw-dark' : 'bg-fw-dark text-gray-300 hover:bg-opacity-80'" class="p-3 rounded-lg cursor-pointer transition text-sm">
                                        <span x-text="value"></span>
                                    </div>
                                </template>
                            </div>
                            <p class="text-xs text-gray-500 mt-2" x-show="formData.values.length > 0">
                                Selected: <span x-text="formData.values.length"></span> of 5
                            </p>
                        </div>

                        <!-- Core Message -->
                        <div class="bg-fw-darker rounded-lg p-6">
                            <label class="block text-lg font-semibold text-white mb-4">Core Message / Tagline</label>
                            <textarea x-model="formData.messaging" rows="3" class="w-full bg-fw-dark border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:border-fw-accent focus:ring focus:ring-fw-accent focus:ring-opacity-50" placeholder="E.g., Guiding families through difficult transitions with strength and compassion"></textarea>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="flex justify-between items-center pt-6">
                            <a href="{{ route('onboarding.index') }}" class="px-6 py-3 bg-fw-dark text-gray-300 rounded-lg hover:bg-opacity-80 transition">
                                Back to Overview
                            </a>
                            <button type="submit" :disabled="!isValid" :class="isValid ? 'bg-fw-accent hover:bg-opacity-90' : 'bg-gray-600 cursor-not-allowed'" class="px-8 py-3 text-fw-dark font-semibold rounded-lg transition">
                                Next Step →
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function step1Data() {
            return {
                formData: {
                    tone: @json($wizardData['step_1']['tone'] ?? ''),
                    style: @json($wizardData['step_1']['style'] ?? ''),
                    values: @json($wizardData['step_1']['values'] ?? []),
                    messaging: @json($wizardData['step_1']['messaging'] ?? ''),
                },
                availableValues: [
                    'Client-focused',
                    'Empathetic',
                    'Assertive advocacy',
                    'Family-first approach',
                    'Results-driven',
                    'Transparent',
                    'Compassionate',
                    'Strategic thinkers',
                ],
                get isValid() {
                    return this.formData.tone && 
                           this.formData.style && 
                           this.formData.values.length >= 3 && 
                           this.formData.values.length <= 5 &&
                           this.formData.messaging.length > 0;
                },
                toggleValue(value) {
                    const index = this.formData.values.indexOf(value);
                    if (index > -1) {
                        this.formData.values.splice(index, 1);
                    } else if (this.formData.values.length < 5) {
                        this.formData.values.push(value);
                    }
                },
                async submitStep() {
                    try {
                        const response = await fetch('/api/firms/{{ $firm->id }}/onboarding/step/1', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                            },
                            body: JSON.stringify(this.formData)
                        });
                        
                        if (response.ok) {
                            window.location.href = '{{ route('onboarding.step', ['step' => 2]) }}';
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                }
            }
        }
    </script>
</x-app-layout>
