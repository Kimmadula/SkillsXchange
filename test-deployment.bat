@echo off
REM Test SkillsXchange Deployment

echo 🧪 Testing SkillsXchange Deployment...
echo.

echo 📍 Testing Render deployment: https://skillsxchange-13vk.onrender.com
echo.

echo 🔍 Testing health endpoint...
curl -s https://skillsxchange-13vk.onrender.com/health
echo.
echo.

echo 🔍 Testing database connection...
curl -s https://skillsxchange-13vk.onrender.com/test-db
echo.
echo.

echo 🔍 Testing debug endpoint...
curl -s https://skillsxchange-13vk.onrender.com/debug
echo.
echo.

echo 🔍 Testing security endpoint...
curl -s https://skillsxchange-13vk.onrender.com/security-test
echo.
echo.

echo ✅ Deployment testing complete!
echo.
echo 🌐 Your SkillsXchange application is live at:
echo    https://skillsxchange-13vk.onrender.com
echo.
echo 📱 Test these features:
echo    - User registration and login
echo    - Skill trading system
echo    - Video calling (Firebase)
echo    - Task management
echo    - Real-time chat
echo.
pause
