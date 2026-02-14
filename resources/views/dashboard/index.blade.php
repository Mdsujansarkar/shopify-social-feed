<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Shopify Instagram Integration</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-900">Instagram Integration</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">{{ $shop->shop_data['name'] ?? $shop->shop_domain }}</span>
                    <a href="{{ route('shopify.install', ['shop' => $shop->shop_domain]) }}" class="text-sm text-blue-600 hover:text-blue-700">
                        Settings
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @session('success')
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endsession

        @session('error')
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endsession

        <!-- Instagram Account Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Instagram Account</h2>
                @if($instagramAccount)
                    <div class="space-x-2">
                        <a href="{{ route('instagram.refresh-token', ['shop' => $shop->shop_domain]) }}"
                           class="text-sm bg-yellow-100 text-yellow-800 px-3 py-1 rounded hover:bg-yellow-200">
                            Refresh Token
                        </a>
                        <a href="{{ route('instagram.disconnect', ['shop' => $shop->shop_domain]) }}"
                           class="text-sm bg-red-100 text-red-800 px-3 py-1 rounded hover:bg-red-200"
                           onclick="return confirm('Are you sure you want to disconnect your Instagram account?')">
                            Disconnect
                        </a>
                    </div>
                @else
                    <a href="{{ route('instagram.connect', ['shop' => $shop->shop_domain]) }}"
                       class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200">
                        Connect Instagram
                    </a>
                @endif
            </div>

            @if($instagramAccount)
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-200">
                        @if($instagramAccount->profile_picture_url)
                            <img src="{{ $instagramAccount->profile_picture_url }}" alt="Profile" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3a7 7 0 01-7-7z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900">@{{ $instagramAccount->username }}</h3>
                        <p class="text-sm text-gray-600">{{ $instagramAccount->followers_count }} followers</p>
                        <p class="text-xs text-gray-500 mt-1">
                            Token expires: {{ $instagramAccount->token_expires_at ? $instagramAccount->token_expires_at->diffForHumans() : 'N/A' }}
                            @if($instagramAccount->isTokenExpired())
                                <span class="text-red-600 font-medium">(EXPIRED)</span>
                            @elseif($instagramAccount->willTokenExpireIn(7))
                                <span class="text-yellow-600 font-medium">(Expires soon)</span>
                            @endif
                        </p>
                    </div>
                </div>
            @else
                <p class="text-gray-600">No Instagram account connected. Click "Connect Instagram" to get started.</p>
            @endif
        </div>

        <!-- Recent Media Section -->
        @if($instagramAccount && $recentMedia->count() > 0)
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Posts</h2>
                    <div class="space-x-2">
                        <button onclick="syncMedia()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition duration-200">
                            Sync New Posts
                        </button>
                        <a href="{{ route('dashboard.instagram-media', ['shop' => $shop->shop_domain]) }}"
                           class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 transition duration-200">
                            View All
                        </a>
                    </div>
                </div>

                <div id="media-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($recentMedia as $media)
                        <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                            <img src="{{ $media->media_url }}" alt="Instagram post" class="w-full h-full object-cover">
                        </div>
                    @endforeach
                </div>

                <div id="sync-status" class="hidden mt-4 text-sm text-gray-600"></div>
            </div>
        @endif
    </main>

    <script>
        function syncMedia() {
            const statusDiv = document.getElementById('sync-status');
            statusDiv.textContent = 'Syncing...';
            statusDiv.classList.remove('hidden');

            fetch('{{ route('dashboard.sync-media', ['shop' => $shop->shop_domain]) }}', {
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
                    statusDiv.classList.add('text-green-600');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    statusDiv.textContent = data.error || 'Sync failed';
                    statusDiv.classList.add('text-red-600');
                }
            })
            .catch(error => {
                statusDiv.textContent = 'Sync failed: ' + error.message;
                statusDiv.classList.add('text-red-600');
            });
        }
    </script>
</body>
</html>
