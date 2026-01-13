<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Verify Email - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            line-height: 1.6;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
        }

        .email-header .icon {
            width: 80px;
            height: 80px;
            background: #ffffff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .email-header .icon svg {
            width: 40px;
            height: 40px;
            fill: #667eea;
        }

        .email-header h1 {
            color: #ffffff;
            font-size: 28px;
            font-weight: bold;
            margin: 0 0 10px 0;
        }

        .email-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            margin: 0;
        }

        .email-body {
            padding: 40px 30px;
        }

        .welcome-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .welcome-icon svg {
            width: 36px;
            height: 36px;
            fill: #ffffff;
        }

        .text-center {
            text-align: center;
        }

        .email-body h2 {
            color: #1f2937;
            font-size: 24px;
            margin: 0 0 20px 0;
            text-align: center;
        }

        .email-body p {
            color: #374151;
            font-size: 16px;
            margin: 0 0 20px 0;
            text-align: center;
        }

        .email-body .small-text {
            color: #6b7280;
            font-size: 14px;
        }

        .button-container {
            text-align: center;
            margin: 30px 0;
        }

        .verify-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: transform 0.2s ease;
        }

        .verify-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .info-box {
            background: #eff6ff;
            border-radius: 12px;
            padding: 20px;
            margin: 30px 0;
            text-align: center;
        }

        .info-box p {
            color: #1e40af;
            font-size: 14px;
            margin: 0;
        }

        .divider {
            height: 1px;
            background: #e5e7eb;
            margin: 30px 0;
        }

        .link-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            word-break: break-all;
        }

        .link-box p {
            color: #6b7280;
            font-size: 12px;
            margin: 0 0 10px 0;
            text-align: left;
        }

        .link-box a {
            color: #667eea;
            font-size: 13px;
            word-break: break-all;
        }

        .email-footer {
            background: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .email-footer p {
            color: #6b7280;
            font-size: 14px;
            margin: 0 0 10px 0;
        }

        .email-footer .social-links {
            margin: 20px 0;
        }

        .email-footer .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #667eea;
            text-decoration: none;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            body {
                padding: 20px 10px;
            }

            .email-header {
                padding: 30px 20px;
            }

            .email-header h1 {
                font-size: 24px;
            }

            .email-body {
                padding: 30px 20px;
            }

            .verify-button {
                padding: 14px 30px;
                font-size: 16px;
            }

            .email-footer {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="icon">
                <svg viewBox="0 0 24 24">
                    <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h1>Verify Your Email Address</h1>
            <p>One more step to get started</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            <div class="text-center">
                <div class="welcome-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>

            <h2>Welcome to {{ config('app.name') }}!</h2>

            <p>
                Thanks for signing up! We're excited to have you on board.
                To complete your registration and access all features, please verify your email address.
            </p>

            <p class="small-text">
                Click the button below to verify your email address:
            </p>

            <!-- Button -->
            <div class="button-container">
                <a href="{{ $actionUrl }}" class="verify-button">
                    Verify Email Address
                </a>
            </div>

            <div class="info-box">
                <p>
                    <strong>⏱️ This link will expire in 60 minutes</strong><br>
                    For security reasons, please verify your email as soon as possible.
                </p>
            </div>

            <div class="divider"></div>

            <p class="small-text">
                If the button doesn't work, copy and paste this link into your browser:
            </p>

            <div class="link-box">
                <p><strong>Verification Link:</strong></p>
                <a href="{{ $actionUrl }}">{{ $actionUrl }}</a>
            </div>

            <div class="divider"></div>

            <p class="small-text">
                <strong>Didn't create an account?</strong><br>
                If you didn't sign up for {{ config('app.name') }}, you can safely ignore this email.
            </p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p><strong>{{ config('app.name') }}</strong></p>
            <p>
                This is an automated email. Please do not reply to this message.<br>
                If you need help, please contact our support team.
            </p>
            <div class="divider"></div>
            <p style="font-size: 12px; color: #9ca3af;">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
