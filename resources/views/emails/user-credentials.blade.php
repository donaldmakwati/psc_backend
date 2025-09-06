<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Account Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .credentials-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .credential-item {
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .credential-item:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #495057;
            display: inline-block;
            width: 120px;
        }
        .value {
            color: #007bff;
            font-weight: 500;
        }
        .password-highlight {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 8px;
            font-family: monospace;
            font-size: 16px;
            font-weight: bold;
        }
        .login-button {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .login-button:hover {
            background: #0056b3;
        }
        .warning {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🚌 Bus Ticketing System</div>
            <h2>Welcome to Our Platform!</h2>
        </div>

        <p>Hello <strong>{{ $userName }}</strong>,</p>
        
        <p>Your account has been successfully created in our Bus Ticketing System. Below are your login credentials:</p>

        <div class="credentials-box">
            <div class="credential-item">
                <span class="label">Full Name:</span>
                <span class="value">{{ $userName }}</span>
            </div>
            <div class="credential-item">
                <span class="label">Email:</span>
                <span class="value">{{ $email }}</span>
            </div>
            <div class="credential-item">
                <span class="label">Staff ID:</span>
                <span class="value">{{ $staffId }}</span>
            </div>
            <div class="credential-item">
                <span class="label">Role:</span>
                <span class="value">{{ $role }}</span>
            </div>
            <div class="credential-item">
                <span class="label">Password:</span>
                <div class="password-highlight">{{ $password }}</div>
            </div>
        </div>

        <div class="warning">
            <strong>⚠️ Security Notice:</strong> Please change your password after your first login for security purposes.
        </div>

        <div style="text-align: center;">
            <p class="login-button">Login to System</p>
        </div>

        <p><strong>Next Steps:</strong></p>
        <ol>
            <li>Click the login button above or visit our website</li>
            <li>Use your username, role and the provided password to log in</li>
            <!-- <li>Change your password in the profile settings</li>
            <li>Complete your profile information if needed</li> -->
        </ol>

        <div class="footer">
            <p>If you have any questions or need assistance, please contact our support team.</p>
            <p>&copy; {{ date('Y') }} Bus Ticketing System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>