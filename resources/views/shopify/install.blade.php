<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Shopify App</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Shopify App Installation</h1>
            <p class="text-gray-600 mt-2">Connect your Shopify store</p>
        </div>

        @if(isset($error))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                {{ $error }}
            </div>
        @endif

        <form action="{{ route('shopify.install') }}" method="GET" class="space-y-4">
            <div>
                <label for="shop" class="block text-sm font-medium text-gray-700 mb-2">
                    Shop Domain
                </label>
                <input
                    type="text"
                    id="shop"
                    name="shop"
                    placeholder="your-store.myshopify.com"
                    required
                    pattern="[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <p class="text-xs text-gray-500 mt-1">Enter your store's myshopify.com domain</p>
            </div>

            <button
                type="submit"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-200 font-medium"
            >
                Install App
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('welcome') }}" class="text-sm text-blue-600 hover:text-blue-700">
                &larr; Back to Home
            </a>
        </div>
    </div>
</body>
</html>
