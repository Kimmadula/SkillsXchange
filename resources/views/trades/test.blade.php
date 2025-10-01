<!DOCTYPE html>
<html>
<head>
    <title>Trade Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug { background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Trade Test Page</h1>
    
    <div class="debug">
        <h3>Debug Information:</h3>
        <p><strong>Trade ID:</strong> {{ $trade->id ?? 'Not found' }}</p>
        <p><strong>Trade Status:</strong> {{ $trade->status ?? 'Not found' }}</p>
        <p><strong>User ID:</strong> {{ $user->id ?? 'Not found' }}</p>
        <p><strong>Offering User:</strong> {{ $trade->offeringUser->firstname ?? 'Not found' }}</p>
        <p><strong>Offering Skill:</strong> {{ $trade->offeringSkill->name ?? 'Not found' }}</p>
        <p><strong>Looking Skill:</strong> {{ $trade->lookingSkill->name ?? 'Not found' }}</p>
    </div>
    
    <h2>Raw Trade Data:</h2>
    <pre>{{ print_r($trade->toArray(), true) }}</pre>
    
    <h2>Raw User Data:</h2>
    <pre>{{ print_r($user->toArray(), true) }}</pre>
</body>
</html>
