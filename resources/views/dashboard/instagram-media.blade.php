<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instagram Media - Shopify Integration</title>
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
                    <h1 class="text-xl font-bold text-gray-900">Instagram Media</h1>
                </div>
                <span class="text-sm text-gray-600">@{{ $instagramAccount->username }}</span>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('dashboard.instagram-media', ['shop' => $shop->shop_domain, 'type' => 'all']) }}"
                   class="px-4 py-2 rounded-md @if($mediaType === 'all') bg-blue-600 text-white @else bg-gray-200 text-gray-800 hover:bg-gray-300 @endif">
                    All
                </a>
                <a href="{{ route('dashboard.instagram-media', ['shop' => $shop->shop_domain, 'type' => 'image']) }}"
                   class="px-4 py-2 rounded-md @if($mediaType === 'image') bg-blue-600 text-white @else bg-gray-200 text-gray-800 hover:bg-gray-300 @endif">
                    Images
                </a>
                <a href="{{ route('dashboard.instagram-media', ['shop' => $shop->shop_domain, 'type' => 'video']) }}"
                   class="px-4 py-2 rounded-md @if($mediaType === 'video') bg-blue-600 text-white @else bg-gray-200 text-gray-800 hover:bg-gray-300 @endif">
                    Videos
                </a>
                <a href="{{ route('dashboard.instagram-media', ['shop' => $shop->shop_domain, 'type' => 'carousel_album']) }}"
                   class="px-4 py-2 rounded-md @if($mediaType === 'carousel_album') bg-blue-600 text-white @else bg-gray-200 text-gray-800 hover:bg-gray-300 @endif">
                    Carousels
                </a>
            </div>
        </div>

        <!-- Media Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($media as $item)
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="aspect-square bg-gray-100">
                        @if($item->media_type === 'VIDEO')
                            <video src="{{ $item->media_url }}" class="w-full h-full object-cover" controls></video>
                        @else
                            <img src="{{ $item->media_url }}" alt="Instagram post" class="w-full h-full object-cover">
                        @endif
                    </div>
                    <div class="p-4">
                        <div class="flex items-center space-x-4 text-sm text-gray-600 mb-2">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0M10 10.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm1.414-6.914a2 2 0 11-2.828 2.828l-5 5a2 2 0 01-2.828 0l-5-5a2 2 0 010-2.828l5-5a2 2 0 012.828 0l5 5a2 2 0 010 2.828z" clip-rule="evenodd"/>
                                </svg>
                                {{ $item->likes_count }}
                            </span>
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                                </svg>
                                {{ $item->comments_count }}
                            </span>
                            <span class="text-xs text-gray-500">{{ $item->posted_at ? $item->posted_at->diffForHumans() : '' }}</span>
                        </div>
                        @if($item->caption)
                            <p class="text-sm text-gray-700 line-clamp-2">{{ Str::limit($item->caption, 100) }}</p>
                        @endif
                        <div class="mt-2">
                            <span class="inline-block px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">
                                {{ $item->media_type }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($media->hasPages())
            <div class="mt-8 flex justify-center">
                {{ $media->appends(['type'])->links() }}
            </div>
        @endif
    </main>
</body>
</html>
