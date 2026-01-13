@extends('backoffice.layouts.app')

@section('title', 'Profile Settings')
@section('page-title', 'Profile Settings')

@section('content')
<div class="space-y-6">
    <!-- Profile Header -->
    <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg shadow-lg p-8 text-white">
        <div class="flex items-center space-x-6">
            <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center font-bold text-4xl backdrop-blur-sm">
                @if(Auth::user()->first_name && Auth::user()->last_name)
                    {{ strtoupper(substr(Auth::user()->first_name, 0, 1)) }}
                @else
                    {{ strtoupper(substr(Auth::user()->name ?? Auth::user()->email, 0, 1)) }}
                @endif
            </div>
            <div>
                <h2 class="text-3xl font-bold">
                    @if(Auth::user()->first_name && Auth::user()->last_name)
                        {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                    @else
                        {{ Auth::user()->name ?? 'User' }}
                    @endif
                </h2>
                <p class="text-white/90 mt-1">{{ Auth::user()->email }}</p>
                <div class="flex items-center mt-2 space-x-4">
                    @if(Auth::user()->email_verified_at)
                    <span class="inline-flex items-center text-sm">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Email Verified
                    </span>
                    @endif
                    <span class="text-sm text-white/80">Member since {{ Auth::user()->created_at->format('M Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Information Card -->
    <div class="bg-white rounded-lg shadow-sm border-l-4 border-purple-500">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Profile Information</h3>
                    <p class="text-sm text-gray-600">Update your account's profile information and email address</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            @include('profile.partials.update-profile-information-form')
        </div>
    </div>

    <!-- Update Password Card -->
    <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-500">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Update Password</h3>
                    <p class="text-sm text-gray-600">Ensure your account is using a long, random password to stay secure</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            @include('profile.partials.update-password-form')
        </div>
    </div>

    <!-- Delete Account Card -->
    <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-500">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Delete Account</h3>
                    <p class="text-sm text-gray-600">Permanently delete your account and all associated data</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</div>
@endsection
