# 🪟 Windows Setup Guide for Z-Library Bot

## 🚨 Quick Fix for Your Current Issues

Based on your error messages, here's the exact solution:

### Step 1: Use Simplified Installation

**Instead of running:**
```cmd
pip install -r requirements.txt
```

**Run this instead:**
```cmd
pip install --user requests beautifulsoup4 fake-useragent tqdm python-dotenv
```

### Step 2: Use the Simplified Bot

**Instead of running:**
```cmd
python zlib_bot.py
```

**Run this instead:**
```cmd
python zlib_bot_simple.py
```

## 🔧 Complete Setup Process

### Option A: Automated Setup (Recommended)

1. **Download and run the setup script:**
   ```cmd
   windows_setup.bat
   ```

2. **Follow the prompts and wait for completion**

### Option B: Manual Setup

1. **Open Command Prompt as Administrator:**
   - Press `Win + R`
   - Type `cmd`
   - Press `Ctrl + Shift + Enter`

2. **Install dependencies one by one:**
   ```cmd
   pip install --user requests
   pip install --user beautifulsoup4
   pip install --user fake-useragent
   pip install --user tqdm
   pip install --user python-dotenv
   ```

3. **Test the installation:**
   ```cmd
   python simple_windows_test.py
   ```

4. **Run the bot:**
   ```cmd
   python zlib_bot_simple.py
   ```

## ❌ Why Your Original Installation Failed

### Problem 1: Pandas Requires C++ Compiler
```
error: Microsoft Visual C++ 14.0 is required
```

**Solution:** We removed pandas dependency and use built-in CSV module instead.

### Problem 2: Complex Dependencies
The original `requirements.txt` included packages that need compilation on Windows.

**Solution:** Use `requirements_simple.txt` with only pure Python packages.

## 🎯 What to Expect After Setup

### File Structure After Installation:
```
your_folder/
├── zlib_bot_simple.py          # ✅ Working bot
├── simple_windows_test.py      # ✅ Test script
├── windows_setup.bat           # ✅ Setup script
├── downloads/                  # Created automatically
├── metadata/                   # Created automatically
└── bot.log                     # Created when you run the bot
```

### When You Run the Bot:
1. **Legal warning** will be displayed
2. **Demo mode options** will appear
3. **Sample results** will be generated
4. **Files will be saved** in metadata/ folder

## 🐛 Troubleshooting Common Issues

### Issue: "Module not found"
```cmd
ModuleNotFoundError: No module named 'tqdm'
```

**Solution:**
```cmd
pip install --user tqdm
```

### Issue: "Permission denied"
**Solution:** Run Command Prompt as Administrator or use `--user` flag

### Issue: "SSL Certificate error"
**Solution:**
```cmd
pip install --trusted-host pypi.org --trusted-host pypi.python.org --user requests
```

### Issue: Bot hangs or doesn't respond
**Solutions:**
1. Check your internet connection
2. Try running with verbose output:
   ```cmd
   python -v zlib_bot_simple.py
   ```
3. Check the log file: `bot.log`

## 🔒 Windows Security Considerations

### Windows Defender
If Windows Defender blocks the script:
1. Add folder to exclusions
2. Or allow the script when prompted

### Firewall
The bot needs internet access for:
- Installing packages (pip)
- Making HTTP requests (demo mode)

## 📁 File Locations on Windows

### Where packages are installed:
```
C:\Users\{YourUsername}\AppData\Roaming\Python\Python3X\site-packages\
```

### Where output files are saved:
```
C:\path\to\your\bot\folder\
├── downloads\     # Downloaded files
├── metadata\      # JSON and CSV files
└── bot.log        # Log file
```

## 🎯 Testing Your Installation

Run this command to test everything:
```cmd
python simple_windows_test.py
```

Expected output:
```
🧪 Testing imports...
✅ requests - OK
✅ beautifulsoup4 - OK
✅ fake-useragent - OK
✅ tqdm - OK
✅ csv - OK
✅ json - OK

📁 Testing file operations...
✅ Directory creation works
✅ File writing works
✅ File reading works
✅ File cleanup works

🔧 Testing basic functionality...
✅ Bot classes imported successfully
✅ Configuration created
✅ Bot initialized
✅ Demo search returned 2 results
✅ CSV export successful

🎉 ALL TESTS PASSED!
```

## 🚀 Running the Bot

### Basic Usage:
```cmd
python zlib_bot_simple.py
```

### What You'll See:
1. Legal warning (type "yes" to continue)
2. Menu with options:
   - Quick demo (3 results)
   - Custom search
   - Exit
3. Demo results with metadata
4. Files saved to `metadata/` folder

### Sample Output Files:
- `demo_search_python_programming_20240708_143022.csv`
- `demo_book_1_20240708_143022.json`
- `demo_book_2_20240708_143022.json`
- `bot.log`

## 🆘 If All Else Fails

### Last Resort Commands:
```cmd
# Uninstall everything
pip uninstall -y requests beautifulsoup4 fake-useragent tqdm python-dotenv

# Reinstall with no cache
pip install --no-cache-dir --user requests beautifulsoup4 fake-useragent tqdm python-dotenv

# Test again
python simple_windows_test.py
```

### Get Help:
1. Check `bot.log` for detailed error messages
2. Run with verbose Python: `python -v script_name.py`
3. Check Python version: `python --version` (should be 3.8+)

## ✅ Success Checklist

- [ ] Python 3.8+ installed
- [ ] All packages installed successfully
- [ ] Test script passes all checks
- [ ] Bot runs without errors
- [ ] Output files are created
- [ ] Log file shows no errors

---

**🎉 Once setup is complete, you can run the bot successfully on Windows!**