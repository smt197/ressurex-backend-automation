@extends('backoffice.layouts.app')

@section('title', 'Create Route')
@section('page-title', 'Create Dynamic Route')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border-l-4 border-purple-500">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Route Configuration</h3>
            <p class="text-sm text-gray-600">Configure a new dynamic route for your application</p>
        </div>

        <form action="{{ route('backoffice.routes.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

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
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('name') border-red-500 @enderror"
                            value="{{ old('name') }}"
                            placeholder="api.users.index"
                            required
                        />
                        <p class="mt-1 text-xs text-gray-500">Lowercase, dots, dashes, underscores only (e.g., api.users.index)</p>
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
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('uri') border-red-500 @enderror"
                            value="{{ old('uri') }}"
                            placeholder="api/users"
                            required
                        />
                        <p class="mt-1 text-xs text-gray-500">The route path (e.g., api/users, api/menus/{id})</p>
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
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('method') border-red-500 @enderror"
                            required
                        >
                            <option value="GET" {{ old('method') == 'GET' ? 'selected' : '' }}>GET</option>
                            <option value="POST" {{ old('method') == 'POST' ? 'selected' : '' }}>POST</option>
                            <option value="PUT" {{ old('method') == 'PUT' ? 'selected' : '' }}>PUT</option>
                            <option value="PATCH" {{ old('method') == 'PATCH' ? 'selected' : '' }}>PATCH</option>
                            <option value="DELETE" {{ old('method') == 'DELETE' ? 'selected' : '' }}>DELETE</option>
                            <option value="OPTIONS" {{ old('method') == 'OPTIONS' ? 'selected' : '' }}>OPTIONS</option>
                        </select>
                        @error('method')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('description') border-red-500 @enderror"
                            placeholder="Optional description for this route"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('controller') border-red-500 @enderror"
                            value="{{ old('controller') }}"
                            placeholder="App\Http\Controllers\Api\UserController"
                            required
                        />
                        <p class="mt-1 text-xs text-gray-500">Full namespace path to the controller</p>
                        @error('controller')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('action') border-red-500 @enderror"
                            value="{{ old('action') }}"
                            placeholder="index"
                            required
                        />
                        <p class="mt-1 text-xs text-gray-500">Method name in the controller (e.g., index, store, show)</p>
                        @error('action')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('guard') border-red-500 @enderror"
                            required
                        >
                            <option value="web" {{ old('guard') == 'web' ? 'selected' : '' }}>Web</option>
                            <option value="api" {{ old('guard', 'api') == 'api' ? 'selected' : '' }}>API</option>
                        </select>
                        @error('guard')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('middleware') border-red-500 @enderror"
                            value="{{ old('middleware') }}"
                            placeholder="throttle:60,1, cors"
                        />
                        <p class="mt-1 text-xs text-gray-500">Comma-separated list of middleware</p>
                        @error('middleware')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Requires Auth Checkbox -->
                    <div class="md:col-span-2">
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                id="requires_auth"
                                name="requires_auth"
                                value="1"
                                class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                                {{ old('requires_auth') ? 'checked' : '' }}
                            />
                            <label for="requires_auth" class="ml-2 text-sm font-medium text-gray-700">
                                Requires Authentication
                            </label>
                        </div>
                        <p class="ml-6 text-xs text-gray-500">Check this if the route requires authentication</p>
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
                                    class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                                    {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}
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
                                    class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                                    {{ in_array($role->name, old('roles', [])) ? 'checked' : '' }}
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
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('order') border-red-500 @enderror"
                            value="{{ old('order', 0) }}"
                        />
                        @error('order')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
                                    class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                                    {{ old('is_active', true) ? 'checked' : '' }}
                                />
                                <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">
                                    Activate Route Immediately
                                </label>
                            </div>
                            <p class="ml-6 text-xs text-gray-500">Route will be available after saving</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="text-sm text-blue-800">
                        <p class="font-medium mb-1">Important Notes:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Make sure the controller and method exist before activating the route</li>
                            <li>After creating the route, you may need to clear the route cache</li>
                            <li>For API routes, use the 'api' guard</li>
                            <li>Permissions and roles are optional but recommended for protected routes</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('backoffice.routes.index') }}" class="px-6 py-2 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 transition-all">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-purple-600 to-blue-600 text-white font-medium rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Create Route
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
