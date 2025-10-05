<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to SkillsXchange!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to SkillsXchange!</h1>
        <p>Your account has been created successfully</p>
    </div>
    
    <div class="content">
        <h2>Hello {{ $user->firstname }},</h2>
        
        <p>Welcome to SkillsXchange! Your account has been created successfully using your Google account.</p>
        
        <p><strong>Your Account Details:</strong></p>
        <ul>
            <li><strong>Email:</strong> {{ $user->email }}</li>
            <li><strong>Username:</strong> {{ $user->username }}</li>
            <li><strong>Status:</strong> Pending admin approval</li>
        </ul>
        
        <p>Please complete your profile by adding your skills and personal information. Your account will be reviewed by an admin before you can start using the platform.</p>
        
        <div style="text-align: center;">
            <a href="{{ $loginUrl }}" class="button">Complete Your Profile</a>
        </div>
        
        <p><strong>Next Steps:</strong></p>
        <ol>
            <li>Complete your profile with your skills and expertise</li>
            <li>Wait for admin approval (usually within 24 hours)</li>
            <li>Start connecting with other users and sharing skills!</li>
        </ol>
    </div>
    
    <div class="footer">
        <p>Best regards,<br>The SkillsXchange Team</p>
        <p>This email was sent to {{ $user->email }}</p>
    </div>
</body>
</html>
