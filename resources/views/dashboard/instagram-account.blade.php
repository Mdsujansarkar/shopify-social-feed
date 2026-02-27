<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instagram Account - Shopify Integration</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('dashboard.index', ['shop' => $shop->shop_domain]) }}" class="text-blue-600 hover:text-blue-700">
                        &larr; Dashboard
                    </a>
                    <h1 class="text-xl font-bold text-gray-900">Instagram Account Details</h1>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Profile Header -->
            <div class="bg-gradient-to-r from-purple-500 to-pink-500 p-6 text-white">
                <div class="flex items-center space-x-6">
                    <div class="w-24 h-24 rounded-full overflow-hidden bg-white p-1">
                        @if($instagramAccount->profile_picture_url)
                            <img src="{{ $instagramAccount->profile_picture_url }}" alt="Profile" class="w-full h-full object-cover rounded-full">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3a7 7 0 01-7-7z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold">@{{ $instagramAccount->username }}</h2>
                        <p class="text-purple-100">{{ $instagramAccount->followers_count }} followers</p>
                        <p class="text-purple-100 text-sm">Instagram Business ID: {{ $instagramAccount->instagram_business_account_id }}</p>
                    </div>
                </div>
            </div>

            <!-- Account Details -->
            <div class="p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Account Information</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border border-gray-200 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Username</p>
                        <p class="font-medium text-gray-900">@{{ $instagramAccount->username }}</p>
                    </div>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Followers</p>
                        <p class="font-medium text-gray-900">{{ number_format($instagramAccount->followers_count) }}</p>
                    </div>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Token Expires</p>
                        <p class="font-medium @if($instagramAccount->isTokenExpired()) text-red-600 @elseif($instagramAccount->willTokenExpireIn(7)) text-yellow-600 @else text-gray-900 @endif">
                            @if($instagramAccount->token_expires_at)
                                {{ $instagramAccount->token_expires_at->format('F j, Y - g:i A') }}
                                <span class="block text-xs mt-1">({{ $instagramAccount->token_expires_at->diffForHumans() }})</span>
                            @else
                                Not set
                            @endif
                        </p>
                    </div>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Connected At</p>
                        <p class="font-medium text-gray-900">{{ $instagramAccount->created_at->format('F j, Y - g:i A') }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="pt-4 border-t border-gray-200">
                    <div class="flex flex-wrap gap-3">
                        <button onclick="refreshToken()" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600 transition duration-200">
                            Refresh Token
                        </button>
                        <a href="{{ route('dashboard.instagram-media', ['shop' => $shop->shop_domain]) }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200">
                            View Media
                        </a>
                        <a href="{{ route('instagram.disconnect', ['shop' => $shop->shop_domain]) }}" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition duration-200" onclick="return confirm('Are you sure you want to disconnect?')">
                            Disconnect Account
                        </a>
                    </div>
                </div>

                <!-- Status Message -->
                <div id="status-message" class="hidden mt-4 rounded-md p-4"></div>
            </div>
        </div>
    </main>

    <script>
        function refreshToken() {
            const statusDiv = document.getElementById('status-message');
            statusDiv.textContent = 'Refreshing token...';
            statusDiv.className = 'mt-4 rounded-md p-4 bg-blue-50 text-blue-700';
            statusDiv.classList.remove('hidden');

            fetch('{{ route('dashboard.refresh-token', ['shop' => $shop->shop_domain]) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusDiv.textContent = data.message;
                    statusDiv.className = 'mt-4 rounded-md p-4 bg-green-50 text-green-700';
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    statusDiv.textContent = data.error || 'Refresh failed';
                    statusDiv.className = 'mt-4 rounded-md p-4 bg-red-50 text-red-700';
                }
            })
            .catch(error => {
                statusDiv.textContent = 'Refresh failed: ' + error.message;
                statusDiv.className = 'mt-4 rounded-md p-4 bg-red-50 text-red-700';
            });
        }
    </script>
</body>
</html>
