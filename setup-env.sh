#!/bin/bash

echo "Setting up environment configuration..."

if [ -f .env ]; then
    echo ".env file already exists. Backing up to .env.backup..."
    cp .env .env.backup
fi

echo "Copying env.template to .env..."
cp env.template .env

echo ""
echo "✅ Environment file created successfully!"
echo ""
echo "📝 Next steps:"
echo "1. Edit .env file with your actual values"
echo "2. Get your Pusher credentials from https://dashboard.pusher.com/"
echo "3. Replace the Pusher values in .env:"
echo "   - PUSHER_APP_ID=your_actual_app_id"
echo "   - PUSHER_APP_KEY=your_actual_key"
echo "   - PUSHER_APP_SECRET=your_actual_secret"
echo "   - PUSHER_APP_CLUSTER=your_actual_cluster"
echo ""
echo "4. Run: php artisan key:generate"
echo "5. Run: npm run build"
echo ""
