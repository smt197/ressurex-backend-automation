@extends('backoffice.layouts.app')

@section('title', 'Edit Route')
@section('page-title', 'Edit Dynamic Route')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-500">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Edit Route: {{ $route->name }}</h3>
            <p class="text-sm text-gray-600">Modify route configuration</p>
        </div>

        <form action="{{ route('backoffice.routes.update', $route) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Route Basics -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-md font-semibold text-gray-900 mb-4">Basic Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Route Name -->
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Route Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                            value="{{ old('name', $route->name) }}"
                            required
                        />
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- URI -->
                    <div class="md:col-span-1">
                        <label for="uri" class="block text-sm font-medium text-gray-700 mb-2">
                            URI <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="uri"
                            name="uri"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('uri') border-red-500 @enderror"
                            value="{{ old('uri', $route->uri) }}"
                            required
                        />
                        @error('uri')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Method -->
                    <div class="md:col-span-1">
                        <label for="method" class="block text-sm font-medium text-gray-700 mb-2">
                            HTTP Method <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="method"
                            name="method"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                        >
                            <option value="GET" {{ old('method', $route->method) == 'GET' ? 'selected' : '' }}>GET</option>
                            <option value="POST" {{ old('method', $route->method) == 'POST' ? 'selected' : '' }}>POST</option>
                            <option value="PUT" {{ old('method', $route->method) == 'PUT' ? 'selected' : '' }}>PUT</option>
                            <option value="PATCH" {{ old('method', $route->method) == 'PATCH' ? 'selected' : '' }}>PATCH</option>
                            <option value="DELETE" {{ old('method', $route->method) == 'DELETE' ? 'selected' : '' }}>DELETE</option>
                            <option value="OPTIONS" {{ old('method', $route->method) == 'OPTIONS' ? 'selected' : '' }}>OPTIONS</option>
                        </select>
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="2"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >{{ old('description', $route->description) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Controller & Action -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-md font-semibold text-gray-900 mb-4">Controller Configuration</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Controller -->
                    <div>
                        <label for="controller" class="block text-sm font-medium text-gray-700 mb-2">
                            Controller Class <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="controller"
                            name="controller"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            value="{{ old('controller', $route->controller) }}"
                            required
                        />
                    </div>

                    <!-- Action -->
                    <div>
                        <label for="action" class="block text-sm font-medium text-gray-700 mb-2">
                            Controller Method <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="action"
                            name="action"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            value="{{ old('action', $route->action) }}"
                            required
                        />
                    </div>
                </div>
            </div>

            <!-- Authentication & Authorization -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-md font-semibold text-gray-900 mb-4">Authentication & Authorization</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Guard -->
                    <div>
                        <label for="guard" class="block text-sm font-medium text-gray-700 mb-2">
                            Guard <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="guard"
                            name="guard"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                        >
                            <option value="web" {{ old('guard', $route->guard) == 'web' ? 'selected' : '' }}>Web</option>
                            <option value="api" {{ old('guard', $route->guard) == 'api' ? 'selected' : '' }}>API</option>
                        </select>
                    </div>

                    <!-- Middleware -->
                    <div>
                        <label for="middleware" class="block text-sm font-medium text-gray-700 mb-2">
                            Additional Middleware
                        </label>
                        <input
                            type="text"
                            id="middleware"
                            name="middleware"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            value="{{ old('middleware', is_array($route->middleware) ? implode(', ', $route->middleware) : '') }}"
                        />
                        <p class="mt-1 text-xs text-gray-500">Comma-separated list</p>
                    </div>

                    <!-- Requires Auth -->
                    <div class="md:col-span-2">
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                id="requires_auth"
                                name="requires_auth"
                                value="1"
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                {{ old('requires_auth', $route->requires_auth) ? 'checked' : '' }}
                            />
                            <label for="requires_auth" class="ml-2 text-sm font-medium text-gray-700">
                                Requires Authentication
                            </label>
                        </div>
                    </div>

                    <!-- Permissions -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Permissions
                        </label>
                        <div class="max-h-40 overflow-y-auto border border-gray-300 rounded-lg p-3 bg-white">
                            @forelse($permissions as $permission)
                            <div class="flex items-center mb-2">
                                <input
                                    type="checkbox"
                                    id="permission_{{ $permission->id }}"
                                    name="permissions[]"
                                    value="{{ $permission->name }}"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    {{ in_array($permission->name, old('permissions', $route->permissions ?? [])) ? 'checked' : '' }}
                                />
                                <label for="permission_{{ $permission->id }}" class="ml-2 text-sm text-gray-700">
                                    {{ $permission->name }}
                                </label>
                            </div>
                            @empty
                            <p class="text-sm text-gray-500">No permissions available</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Roles -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Roles
                        </label>
                        <div class="flex flex-wrap gap-3">
                            @forelse($roles as $role)
                            <div class="flex items-center">
                                <input
                                    type="checkbox"
                                    id="role_{{ $role->id }}"
                                    name="roles[]"
                                    value="{{ $role->name }}"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    {{ in_array($role->name, old('roles', $route->roles ?? [])) ? 'checked' : '' }}
                                />
                                <label for="role_{{ $role->id }}" class="ml-2 text-sm text-gray-700">
                                    {{ ucfirst($role->name) }}
                                </label>
                            </div>
                            @empty
                            <p class="text-sm text-gray-500">No roles available</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status & Order -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-md font-semibold text-gray-900 mb-4">Status & Priority</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Order -->
                    <div>
                        <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                            Display Order
                        </label>
                        <input
                            type="number"
                            id="order"
                            name="order"
                            min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            value="{{ old('order', $route->order) }}"
                        />
                    </div>

                    <!-- Is Active -->
                    <div class="flex items-center h-full">
                        <div>
                            <div class="flex items-center">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    name="is_active"
                                    value="1"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    {{ old('is_active', $route->is_active) ? 'checked' : '' }}
                                />
                                <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">
                                    Route is Active
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('backoffice.routes.index') }}" class="px-6 py-2 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 transition-all">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-medium rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Update Route
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
