<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verify Email - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            position: relative;
            overflow: hidden;
        }

        /* Animated background blobs */
        body::before,
        body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.5;
            animation: float 8s ease-in-out infinite;
        }

        body::before {
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.2);
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        body::after {
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.15);
            bottom: -150px;
            right: -150px;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) translateX(0px); }
            25% { transform: translateY(-30px) translateX(30px); }
            50% { transform: translateY(-60px) translateX(-30px); }
            75% { transform: translateY(-30px) translateX(-60px); }
        }

        .container {
            max-width: 500px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
        }

        .icon-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            background: #ffffff;
            border-radius: 50%;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .icon-wrapper svg {
            width: 40px;
            height: 40px;
            color: #667eea;
        }

        .card-header h2 {
            color: #ffffff;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .card-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
        }

        .card-body {
            padding: 2.5rem 2rem;
        }

        .info-box {
            text-align: center;
            margin-bottom: 2rem;
        }

        .check-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);
            border-radius: 50%;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 20px rgba(139, 92, 246, 0.3);
        }

        .check-icon svg {
            width: 36px;
            height: 36px;
            color: #ffffff;
        }

        .info-box p {
            color: #374151;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .info-box p:last-child {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .success-message {
            background: #ecfdf5;
            border: 1px solid #6ee7b7;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            animation: fadeIn 0.4s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .success-message svg {
            width: 20px;
            height: 20px;
            color: #059669;
            margin-right: 0.75rem;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .success-message p {
            color: #065f46;
            font-size: 0.9rem;
            margin: 0;
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-family: inherit;
        }

        .btn svg {
            width: 20px;
            height: 20px;
            margin-right: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .divider {
            position: relative;
            text-align: center;
            margin: 1.5rem 0;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #d1d5db;
        }

        .divider span {
            position: relative;
            background: #ffffff;
            padding: 0 1rem;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .help-box {
            background: #eff6ff;
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1.5rem;
            text-align: center;
        }

        .help-box p {
            color: #1e40af;
            font-size: 0.85rem;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .help-box svg {
            width: 16px;
            height: 16px;
            margin-right: 0.5rem;
        }

        .footer {
            text-align: center;
            margin-top: 2rem;
            color: #ffffff;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 640px) {
            body {
                padding: 1.5rem 1rem;
            }

            .card-header {
                padding: 2rem 1.5rem;
            }

            .card-header h2 {
                font-size: 1.75rem;
            }

            .card-body {
                padding: 2rem 1.5rem;
            }

            .icon-wrapper {
                width: 70px;
                height: 70px;
            }

            .icon-wrapper svg {
                width: 35px;
                height: 35px;
            }
        }

        /* Loading animation */
        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-primary:disabled::after {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            margin-left: 10px;
            border: 2px solid #ffffff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header">
                <div class="icon-wrapper">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h2>Verify Your Email</h2>
                <p>Check your inbox</p>
            </div>

            <!-- Body -->
            <div class="card-body">
                <!-- Info Message -->
                <div class="info-box">
                    <div class="check-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p>
                        <strong>Thanks for signing up!</strong><br>
                        Before getting started, could you verify your email address by clicking on the link we just emailed to you?
                    </p>
                    <p>
                        If you didn't receive the email, we will gladly send you another.
                    </p>
                </div>

                <!-- Success Message -->
                @if (session('status') == 'verification-link-sent')
                    <div class="success-message">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <p>A new verification link has been sent to your email address.</p>
                    </div>
                @endif

                <div class="button-group">
                    <!-- Resend Verification Email -->
                    <form method="POST" action="{{ route('verification.send') }}" id="resendForm">
                        @csrf
                        <button type="submit" class="btn btn-primary" id="resendBtn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Resend Verification Email
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="divider">
                        <span>or</span>
                    </div>

                    <!-- Logout -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Log Out
                        </button>
                    </form>
                </div>

                <!-- Help Text -->
                <div class="help-box">
                    <p>
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        Check your spam folder if you don't see the email in your inbox.
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Prevent double submission
        document.getElementById('resendForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('resendBtn');
            if (btn.disabled) {
                e.preventDefault();
                return;
            }
            btn.disabled = true;
            btn.textContent = 'Sending...';
        });

        // Auto-hide success message after 5 seconds
        const successMsg = document.querySelector('.success-message');
        if (successMsg) {
            setTimeout(() => {
                successMsg.style.transition = 'opacity 0.5s ease';
                successMsg.style.opacity = '0';
                setTimeout(() => successMsg.remove(), 500);
            }, 5000);
        }
    </script>
</body>
</html>
