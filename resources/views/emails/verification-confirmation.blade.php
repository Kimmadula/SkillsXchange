<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Verified - SkillsXchange</title>
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
        .success-icon {
            font-size: 48px;
            color: #28a745;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Email Verified Successfully!</h1>
        <p>Your account is now fully activated</p>
    </div>
    
    <div class="content">
        <div style="text-align: center;">
            <div class="success-icon">✅</div>
        </div>
        
        <h2>Congratulations {{ $user->firstname }}!</h2>
        
        <p>Your email address has been successfully verified with Google. You now have full access to all SkillsXchange features!</p>
        
        <p><strong>What you can do now:</strong></p>
        <ul>
            <li>✅ Participate in skill trading</li>
            <li>✅ Send and receive notifications</li>
            <li>✅ Access all platform features</li>
            <li>✅ Receive transaction updates via email</li>
        </ul>
        
        <div style="text-align: center;">
            <a href="{{ $loginUrl }}" class="button">Start Trading Skills</a>
        </div>
        
        <p><strong>Security Note:</strong> Your account is now more secure with verified email authentication. You'll receive important updates and notifications directly to your verified email address.</p>
    </div>
    
    <div class="footer">
        <p>Best regards,<br>The SkillsXchange Team</p>
        <p>This email was sent to {{ $user->email }}</p>
    </div>
</body>
</html>
