<x-app-layout>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <!-- Progress Steps -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    @for ($i = 1; $i <= 5; $i++)
                        <div class="flex items-center {{ $i < 5 ? 'flex-1' : '' }}">
                            <div class="flex items-center justify-center w-12 h-12 rounded-full {{ $i <= $currentStep ? 'bg-fw-accent text-fw-dark' : 'bg-fw-darker text-gray-400' }} font-semibold">
                                {{ $i }}
                            </div>
                            @if ($i < 5)
                                <div class="flex-1 h-1 mx-2 {{ $i < $currentStep ? 'bg-fw-accent' : 'bg-fw-darker' }}"></div>
                            @endif
                        </div>
                    @endfor
                </div>
                <div class="flex items-center justify-between mt-4 text-sm">
                    <span class="{{ $currentStep == 1 ? 'text-fw-accent' : 'text-gray-400' }}">Brand Voice</span>
                    <span class="{{ $currentStep == 2 ? 'text-fw-accent' : 'text-gray-400' }}">Practice Areas</span>
                    <span class="{{ $currentStep == 3 ? 'text-fw-accent' : 'text-gray-400' }}">Target Clients</span>
                    <span class="{{ $currentStep == 4 ? 'text-fw-accent' : 'text-gray-400' }}">Service Area</span>
                    <span class="{{ $currentStep == 5 ? 'text-fw-accent' : 'text-gray-400' }}">Marketing Goals</span>
                </div>
            </div>

            <!-- Welcome Message -->
            <div class="bg-fw-darker rounded-lg shadow-lg p-8 mb-8">
                <h1 class="text-3xl font-bold text-fw-accent mb-4">Welcome to FirmWizard Onboarding</h1>
                <p class="text-gray-300 mb-6">
                    We'll walk you through 5 quick steps to build your firm's profile. This should take about 7-10 minutes.
                    Your progress is saved automatically.
                </p>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('onboarding.step', ['step' => $currentStep]) }}" class="px-6 py-3 bg-fw-accent text-fw-dark font-semibold rounded-lg hover:bg-opacity-90 transition">
                        {{ $currentStep == 1 ? 'Get Started' : 'Continue Wizard' }}
                    </a>
                    @if ($currentStep > 1)
                        <span class="text-gray-400">Step {{ $currentStep }} of 5</span>
                    @endif
                </div>
            </div>

            <!-- Firm Info -->
            <div class="bg-fw-darker rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-fw-gold mb-4">Firm Information</h2>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-400">Firm Name:</span>
                        <span class="text-white ml-2">{{ $firm->name }}</span>
                    </div>
                    @if ($firm->website_url)
                        <div>
                            <span class="text-gray-400">Website:</span>
                            <a href="{{ $firm->website_url }}" target="_blank" class="text-fw-accent ml-2 hover:underline">{{ $firm->website_url }}</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
