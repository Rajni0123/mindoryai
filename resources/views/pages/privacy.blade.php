@extends('layouts.app')

@section('title', 'Privacy Policy - ' . config('app.name', 'BlinkStudy'))
@section('description', 'Read our privacy policy to understand how we protect your data')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-12">

    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-3xl md:text-4xl font-bold dark:text-white text-gray-900 mb-3">
            Privacy Policy
        </h1>
        <div class="w-20 h-1 bg-primary mx-auto mb-4"></div>
        <p class="dark:text-gray-500 text-gray-600">Effective date: {{ date('F d, Y') }}</p>
    </div>

    <!-- Content Card -->
    <div class="dark:bg-white/[0.02] bg-white rounded-xl shadow-sm dark:shadow-none border dark:border-white/[0.06] border-gray-200 p-6 md:p-10 space-y-10">

        <!-- Introduction -->
        <section>
            <div class="flex items-start space-x-3 mb-4">
                <div class="w-2 h-2 bg-primary rounded-full mt-2"></div>
                <div>
                    <h2 class="text-xl font-semibold dark:text-white text-gray-900 mb-3">Our Commitment</h2>
                    <p class="dark:text-gray-400 text-gray-700 leading-relaxed">
                        We value your privacy. This policy outlines how BlinkStudy collects, uses, and protects your information when you use our AI-powered image analysis service.
                    </p>
                </div>
            </div>
        </section>

        <!-- Information Collection -->
        <section>
            <h2 class="text-2xl font-bold dark:text-white text-gray-900 mb-6 pb-2 border-b dark:border-white/[0.06] border-gray-100">Information We Collect</h2>
            <div class="grid md:grid-cols-2 gap-6">
                <div class="dark:bg-white/5 bg-blue-50 p-5 rounded-lg dark:border dark:border-white/[0.06]">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 dark:bg-primary/10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                            <span class="text-primary font-semibold">1</span>
                        </div>
                        <h3 class="font-semibold dark:text-white text-gray-900">Uploaded Images</h3>
                    </div>
                    <p class="dark:text-gray-400 text-gray-700 text-sm">Images are temporarily stored and automatically deleted after 60 seconds.</p>
                </div>

                <div class="dark:bg-white/5 bg-blue-50 p-5 rounded-lg dark:border dark:border-white/[0.06]">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 dark:bg-primary/10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                            <span class="text-primary font-semibold">2</span>
                        </div>
                        <h3 class="font-semibold dark:text-white text-gray-900">IP Address</h3>
                    </div>
                    <p class="dark:text-gray-400 text-gray-700 text-sm">Collected for security and IP whitelisting enforcement.</p>
                </div>

                <div class="dark:bg-white/5 bg-blue-50 p-5 rounded-lg dark:border dark:border-white/[0.06]">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 dark:bg-primary/10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                            <span class="text-primary font-semibold">3</span>
                        </div>
                        <h3 class="font-semibold dark:text-white text-gray-900">Usage Data</h3>
                    </div>
                    <p class="dark:text-gray-400 text-gray-700 text-sm">Basic statistics to improve service quality.</p>
                </div>

                <div class="dark:bg-white/5 bg-blue-50 p-5 rounded-lg dark:border dark:border-white/[0.06]">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 dark:bg-primary/10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                            <span class="text-primary font-semibold">4</span>
                        </div>
                        <h3 class="font-semibold dark:text-white text-gray-900">Security Data</h3>
                    </div>
                    <p class="dark:text-gray-400 text-gray-700 text-sm">Data for abuse prevention and system protection.</p>
                </div>
            </div>
        </section>

        <!-- How We Use Information -->
        <section>
            <h2 class="text-2xl font-bold dark:text-white text-gray-900 mb-6 pb-2 border-b dark:border-white/[0.06] border-gray-100">How We Use Your Information</h2>
            <div class="space-y-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <p class="dark:text-gray-400 text-gray-700">Provide AI-powered image analysis and educational explanations</p>
                </div>
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <p class="dark:text-gray-400 text-gray-700">Improve service quality and user experience</p>
                </div>
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <p class="dark:text-gray-400 text-gray-700">Enforce security measures and prevent abuse</p>
                </div>
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <p class="dark:text-gray-400 text-gray-700">Maintain system performance and troubleshoot issues</p>
                </div>
            </div>
        </section>

        <!-- Data Security -->
        <section>
            <h2 class="text-2xl font-bold dark:text-white text-gray-900 mb-6 pb-2 border-b dark:border-white/[0.06] border-gray-100">Data Security & Protection</h2>
            <div class="grid md:grid-cols-2 gap-6">
                <div class="border dark:border-white/[0.06] border-gray-200 rounded-lg p-5 hover:border-primary/50 transition-colors">
                    <div class="text-primary mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold dark:text-white text-gray-900 mb-2">Automatic Deletion</h3>
                    <p class="dark:text-gray-400 text-gray-700 text-sm">All uploaded images are automatically deleted after 60 seconds.</p>
                </div>

                <div class="border dark:border-white/[0.06] border-gray-200 rounded-lg p-5 hover:border-primary/50 transition-colors">
                    <div class="text-primary mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold dark:text-white text-gray-900 mb-2">Encrypted Connections</h3>
                    <p class="dark:text-gray-400 text-gray-700 text-sm">All data transmission is encrypted using HTTPS protocol.</p>
                </div>

                <div class="border dark:border-white/[0.06] border-gray-200 rounded-lg p-5 hover:border-primary/50 transition-colors">
                    <div class="text-primary mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold dark:text-white text-gray-900 mb-2">IP Whitelisting</h3>
                    <p class="dark:text-gray-400 text-gray-700 text-sm">Only authorized IP addresses can access our platform.</p>
                </div>

                <div class="border dark:border-white/[0.06] border-gray-200 rounded-lg p-5 hover:border-primary/50 transition-colors">
                    <div class="text-primary mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold dark:text-white text-gray-900 mb-2">Secure Storage</h3>
                    <p class="dark:text-gray-400 text-gray-700 text-sm">Data stored on secure servers with restricted access.</p>
                </div>
            </div>
        </section>

        <!-- Data Retention -->
        <section>
            <h2 class="text-2xl font-bold dark:text-white text-gray-900 mb-6 pb-2 border-b dark:border-white/[0.06] border-gray-100">Data Retention</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y dark:divide-white/[0.06] divide-gray-200">
                    <thead class="dark:bg-white/5 bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium dark:text-gray-400 text-gray-500 uppercase tracking-wider">Data Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium dark:text-gray-400 text-gray-500 uppercase tracking-wider">Retention Period</th>
                            <th class="px-4 py-3 text-left text-xs font-medium dark:text-gray-400 text-gray-500 uppercase tracking-wider">Purpose</th>
                        </tr>
                    </thead>
                    <tbody class="dark:bg-white/[0.02] bg-white divide-y dark:divide-white/[0.06] divide-gray-200">
                        <tr>
                            <td class="px-4 py-3 text-sm dark:text-white text-gray-900 font-medium">Uploaded Images</td>
                            <td class="px-4 py-3 text-sm dark:text-gray-400 text-gray-700">60 seconds</td>
                            <td class="px-4 py-3 text-sm dark:text-gray-400 text-gray-700">Temporary processing</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-sm dark:text-white text-gray-900 font-medium">Server Logs</td>
                            <td class="px-4 py-3 text-sm dark:text-gray-400 text-gray-700">30 days</td>
                            <td class="px-4 py-3 text-sm dark:text-gray-400 text-gray-700">Security & troubleshooting</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-sm dark:text-white text-gray-900 font-medium">AI Responses</td>
                            <td class="px-4 py-3 text-sm dark:text-gray-400 text-gray-700">Indefinite</td>
                            <td class="px-4 py-3 text-sm dark:text-gray-400 text-gray-700">Service improvement</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Third-Party Services -->
        <section>
            <div class="dark:bg-white/5 bg-gray-50 rounded-lg p-6 border dark:border-white/[0.06] border-gray-200">
                <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-4">Third-Party Services</h2>
                <p class="dark:text-gray-400 text-gray-700 mb-4">
                    We use OpenAI's GPT-4 Vision API for image analysis. Images sent to OpenAI are processed according to their privacy policy.
                </p>
                <div class="flex items-center text-sm dark:text-gray-500 text-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>OpenAI does not use API images to train their models.</span>
                </div>
            </div>
        </section>

        <!-- Your Rights -->
        <section>
            <h2 class="text-2xl font-bold dark:text-white text-gray-900 mb-6 pb-2 border-b dark:border-white/[0.06] border-gray-100">Your Rights</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="dark:bg-white/[0.02] bg-white border dark:border-white/[0.06] border-gray-200 rounded-lg p-4 hover:shadow-sm dark:hover:border-white/10 transition-shadow">
                    <div class="flex items-center mb-2">
                        <div class="w-6 h-6 dark:bg-primary/10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                            <span class="text-primary text-sm font-semibold">i</span>
                        </div>
                        <h3 class="font-medium dark:text-white text-gray-900">Right to Know</h3>
                    </div>
                    <p class="dark:text-gray-400 text-gray-700 text-sm">Know what data we collect and how it's used.</p>
                </div>

                <div class="dark:bg-white/[0.02] bg-white border dark:border-white/[0.06] border-gray-200 rounded-lg p-4 hover:shadow-sm dark:hover:border-white/10 transition-shadow">
                    <div class="flex items-center mb-2">
                        <div class="w-6 h-6 dark:bg-primary/10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-3 h-3 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3 class="font-medium dark:text-white text-gray-900">Right to Delete</h3>
                    </div>
                    <p class="dark:text-gray-400 text-gray-700 text-sm">Request deletion of your data from our systems.</p>
                </div>

                <div class="dark:bg-white/[0.02] bg-white border dark:border-white/[0.06] border-gray-200 rounded-lg p-4 hover:shadow-sm dark:hover:border-white/10 transition-shadow">
                    <div class="flex items-center mb-2">
                        <div class="w-6 h-6 dark:bg-primary/10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-3 h-3 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3 class="font-medium dark:text-white text-gray-900">Right to Opt-out</h3>
                    </div>
                    <p class="dark:text-gray-400 text-gray-700 text-sm">Opt-out of data collection by not using our service.</p>
                </div>

                <div class="dark:bg-white/[0.02] bg-white border dark:border-white/[0.06] border-gray-200 rounded-lg p-4 hover:shadow-sm dark:hover:border-white/10 transition-shadow">
                    <div class="flex items-center mb-2">
                        <div class="w-6 h-6 dark:bg-primary/10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-3 h-3 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM7 8H5v2h2V8zm2 0h2v2H9V8zm6 0h-2v2h2V8z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3 class="font-medium dark:text-white text-gray-900">Right to Contact</h3>
                    </div>
                    <p class="dark:text-gray-400 text-gray-700 text-sm">Contact us with any privacy concerns or questions.</p>
                </div>
            </div>
        </section>

        <!-- Contact & Updates -->
        <section>
            <div class="grid md:grid-cols-2 gap-8">
                <div class="dark:bg-primary/5 bg-blue-50 rounded-lg p-6 border dark:border-primary/20 border-blue-100">
                    <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-4">Contact Us</h2>
                    <p class="dark:text-gray-400 text-gray-700 mb-4">
                        Have questions about our privacy practices? We're here to help.
                    </p>
                    <a href="{{ route('support') }}" class="inline-flex items-center text-primary hover:text-primary/80 font-medium">
                        Visit Support Page
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </a>
                </div>

                <div class="dark:bg-white/5 bg-gray-50 rounded-lg p-6 border dark:border-white/[0.06] border-gray-200">
                    <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-4">Policy Updates</h2>
                    <p class="dark:text-gray-400 text-gray-700 mb-3">
                        We may update this policy. Check back periodically for changes.
                    </p>
                    <div class="flex items-center text-sm dark:text-gray-500 text-gray-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>Last updated: {{ date('F d, Y') }}</span>
                    </div>
                </div>
            </div>
        </section>

    </div>

    <!-- Quick Summary -->
    <div class="mt-8 text-center">
        <p class="dark:text-gray-500 text-gray-600 text-sm">
            By using BlinkStudy, you agree to this Privacy Policy.
        </p>
    </div>

</div>
@endsection
