@echo off
echo ========================================
echo Z-Library Bot - Windows Setup Script
echo ========================================

echo.
echo Step 1: Installing simplified dependencies...
pip install --user requests beautifulsoup4 fake-useragent tqdm python-dotenv

echo.
echo Step 2: Checking installations...
python -c "import requests; print('✅ requests OK')"
python -c "import bs4; print('✅ beautifulsoup4 OK')"
python -c "import fake_useragent; print('✅ fake-useragent OK')"
python -c "import tqdm; print('✅ tqdm OK')"
python -c "import dotenv; print('✅ python-dotenv OK')"

echo.
echo Step 3: Testing basic bot functionality...
python simple_windows_test.py

echo.
echo ========================================
echo Setup Complete!
echo ========================================
echo.
echo You can now run:
echo   python zlib_bot_simple.py
echo.
pause