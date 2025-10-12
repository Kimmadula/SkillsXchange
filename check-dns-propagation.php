<?php
/**
 * DNS Propagation Checker
 * 
 * This script helps you check if DNS propagation is successful
 * for your skillsxchange.site domain
 */

echo "🔍 DNS Propagation Checker for skillsxchange.site\n";
echo "==============================================\n\n";

echo "📋 METHODS TO CHECK DNS PROPAGATION:\n";
echo "===================================\n\n";

echo "1. 🌐 Online DNS Checkers (Easiest):\n";
echo "   - https://dnschecker.org/\n";
echo "   - https://whatsmydns.net/\n";
echo "   - https://dns.google/query?name=skillsxchange.site&type=A\n";
echo "   - Enter: skillsxchange.site\n";
echo "   - Look for: 216.24.57.1 in results\n\n";

echo "2. 💻 Command Line (if you have access):\n";
echo "   Windows (Command Prompt):\n";
echo "   nslookup skillsxchange.site\n";
echo "   nslookup www.skillsxchange.site\n\n";
echo "   Windows (PowerShell):\n";
echo "   Resolve-DnsName skillsxchange.site\n";
echo "   Resolve-DnsName www.skillsxchange.site\n\n";

echo "3. 🌐 Browser Test:\n";
echo "   - Try visiting: https://skillsxchange.site\n";
echo "   - Try visiting: https://www.skillsxchange.site\n";
echo "   - Both should show your SkillsXchange app\n\n";

echo "4. 🔧 Render Dashboard Check:\n";
echo "   - Go back to Render Custom Domains page\n";
echo "   - Look for green checkmarks instead of red X's\n";
echo "   - Click 'Verify' buttons - they should succeed\n\n";

echo "⏱️ PROPAGATION TIMELINE:\n";
echo "=======================\n";
echo "✅ Immediate (0-5 minutes): Local DNS cache\n";
echo "✅ Fast (5-15 minutes): Most DNS servers\n";
echo "✅ Complete (15-60 minutes): Global propagation\n";
echo "✅ Maximum (24-48 hours): All DNS servers worldwide\n\n";

echo "🎯 WHAT TO LOOK FOR:\n";
echo "===================\n";
echo "✅ skillsxchange.site → 216.24.57.1\n";
echo "✅ www.skillsxchange.site → skillsxchange-crus.onrender.com\n";
echo "✅ Both domains load your SkillsXchange app\n";
echo "✅ Render shows green checkmarks\n\n";

echo "❌ COMMON ISSUES:\n";
echo "================\n";
echo "❌ Still showing old IP: Wait longer (up to 1 hour)\n";
echo "❌ DNS not found: Check GoDaddy DNS records\n";
echo "❌ Wrong IP: Verify A record in GoDaddy\n";
echo "❌ www not working: Check CNAME record\n\n";

echo "🧪 QUICK TEST COMMANDS:\n";
echo "======================\n";
echo "1. Test root domain:\n";
echo "   curl -I https://skillsxchange.site\n\n";
echo "2. Test www subdomain:\n";
echo "   curl -I https://www.skillsxchange.site\n\n";
echo "3. Check DNS resolution:\n";
echo "   nslookup skillsxchange.site\n\n";

echo "📊 SUCCESS INDICATORS:\n";
echo "=====================\n";
echo "✅ Domain loads your app (not GoDaddy parking page)\n";
echo "✅ SSL certificate is active (green lock in browser)\n";
echo "✅ Both skillsxchange.site and www.skillsxchange.site work\n";
echo "✅ Render dashboard shows verified domains\n";
echo "✅ No DNS errors in browser console\n\n";

echo "🚀 NEXT STEPS AFTER SUCCESS:\n";
echo "===========================\n";
echo "1. ✅ Test user registration emails\n";
echo "2. ✅ Test password reset emails\n";
echo "3. ✅ Update any hardcoded URLs in your app\n";
echo "4. ✅ Set up email service with your domain\n\n";

echo "💡 TIP: Start checking after 5 minutes, but don't worry if it takes up to 1 hour!\n";
