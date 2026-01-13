@extends('backoffice.layouts.app')

@section('title', 'Dynamic Routes')
@section('page-title', 'Dynamic Routes Management')

@section('content')
<div class="space-y-6">
    <!-- Header Actions -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Dynamic Routes</h2>
            <p class="text-sm text-gray-600 mt-1">Manage API and web routes dynamically</p>
        </div>
        <div class="flex space-x-3">
            <form action="{{ route('backoffice.routes.clear-cache') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white font-medium rounded-lg hover:bg-yellow-700 transition-all shadow-md">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Clear Cache
                </button>
            </form>
            <a href="{{ route('backoffice.routes.create') }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-blue-600 text-white font-medium rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all shadow-lg hover:shadow-xl">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Create Route
            </a>
        </div>
    </div>

    <!-- Routes Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Route Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">URI</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Controller</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guard/Auth</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($routes as $route)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold
                                {{ $route->method === 'GET' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $route->method === 'POST' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $route->method === 'PUT' || $route->method === 'PATCH' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $route->method === 'DELETE' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ $route->method }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $route->name }}</div>
                            @if($route->description)
                            <div class="text-xs text-gray-500">{{ Str::limit($route->description, 40) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <code class="text-sm text-purple-600 bg-purple-50 px-2 py-1 rounded">{{ $route->uri }}</code>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ class_basename($route->controller) }}</div>
                            <div class="text-xs text-gray-500">{{ $route->action }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col space-y-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    {{ $route->guard === 'api' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ strtoupper($route->guard) }}
                                </span>
                                @if($route->requires_auth)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-orange-100 text-orange-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    Auth
                                </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($route->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Inactive
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <!-- Toggle -->
                                <form action="{{ route('backoffice.routes.toggle', $route) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-{{ $route->is_active ? 'gray' : 'green' }}-600 hover:text-{{ $route->is_active ? 'gray' : 'green' }}-900" title="{{ $route->is_active ? 'Deactivate' : 'Activate' }}">
                                        @if($route->is_active)
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        @endif
                                    </button>
                                </form>

                                <!-- Edit -->
                                <a href="{{ route('backoffice.routes.edit', $route) }}" class="text-blue-600 hover:text-blue-900" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>

                                <!-- Delete -->
                                <form action="{{ route('backoffice.routes.destroy', $route) }}" method="POST" onsubmit="return confirm('Delete this route?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No routes</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by creating a new route.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($routes->hasPages())
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            {{ $routes->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
