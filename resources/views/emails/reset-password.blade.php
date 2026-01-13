<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .email-header .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .email-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .email-body {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
        }

        .message {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.8;
        }

        .button-container {
            text-align: center;
            margin: 40px 0;
        }

        .reset-button {
            display: inline-block;
            padding: 16px 48px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }

        .reset-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .expiration-notice {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px 20px;
            margin: 30px 0;
            border-radius: 4px;
            font-size: 14px;
            color: #856404;
        }

        .expiration-notice .icon {
            display: inline-block;
            margin-right: 8px;
        }

        .fallback-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .fallback-section p {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
        }

        .fallback-url {
            word-break: break-all;
            font-size: 12px;
            color: #667eea;
            background: white;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            font-family: 'Courier New', monospace;
        }

        .security-notice {
            margin-top: 30px;
            padding: 20px;
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            border-radius: 4px;
        }

        .security-notice .title {
            font-weight: 600;
            color: #1976d2;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .security-notice p {
            font-size: 13px;
            color: #0d47a1;
            margin: 0;
        }

        .email-footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            color: #666;
            font-size: 13px;
        }

        .email-footer p {
            margin: 5px 0;
        }

        .email-footer .app-name {
            font-weight: 600;
            color: #667eea;
        }

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

            .reset-button {
                padding: 14px 36px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div class="icon">🔐</div>
            <h1>Reset Your Password</h1>
        </div>

        <div class="email-body">
            <div class="greeting">Hello!</div>

            <div class="message">
                You are receiving this email because we received a password reset request for your account.
                Click the button below to reset your password and regain access to your account.
            </div>

            <div class="button-container">
                <a href="{{ $actionUrl }}" class="reset-button">Reset Password</a>
            </div>

            <div class="expiration-notice">
                <span class="icon">⏱️</span>
                <strong>Important:</strong> This password reset link will expire in <strong>60 minutes</strong>.
                Please complete the reset process before the link expires.
            </div>

            <div class="security-notice">
                <div class="title">🛡️ Security Notice</div>
                <p>
                    If you did not request a password reset, no further action is required.
                    Your account remains secure, and you can safely ignore this email.
                </p>
            </div>

            <div class="fallback-section">
                <p>If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:</p>
                <div class="fallback-url">{{ $actionUrl }}</div>
            </div>
        </div>

        <div class="email-footer">
            <p>Regards,</p>
            <p class="app-name">{{ config('app.name') }}</p>
            <p style="margin-top: 20px; color: #999; font-size: 12px;">
                This is an automated message, please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>
