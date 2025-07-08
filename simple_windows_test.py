#!/usr/bin/env python3
"""
Simple Windows Test for Z-Library Bot
Tests basic functionality without complex dependencies
"""

import sys

def test_imports():
    """Test that all required modules can be imported"""
    print("🧪 Testing imports...")
    
    try:
        import requests
        print("✅ requests - OK")
    except ImportError as e:
        print(f"❌ requests - FAILED: {e}")
        return False
    
    try:
        from bs4 import BeautifulSoup
        print("✅ beautifulsoup4 - OK")
    except ImportError as e:
        print(f"❌ beautifulsoup4 - FAILED: {e}")
        return False
    
    try:
        from fake_useragent import UserAgent
        print("✅ fake-useragent - OK")
    except ImportError as e:
        print(f"❌ fake-useragent - FAILED: {e}")
        return False
    
    try:
        from tqdm import tqdm
        print("✅ tqdm - OK")
    except ImportError as e:
        print(f"❌ tqdm - FAILED: {e}")
        return False
    
    try:
        import csv
        print("✅ csv - OK")
    except ImportError as e:
        print(f"❌ csv - FAILED: {e}")
        return False
    
    try:
        import json
        print("✅ json - OK")
    except ImportError as e:
        print(f"❌ json - FAILED: {e}")
        return False
    
    return True

def test_basic_functionality():
    """Test basic bot functionality"""
    print("\n🔧 Testing basic functionality...")
    
    try:
        from zlib_bot_simple import SimpleZLibBot, SimpleConfig
        print("✅ Bot classes imported successfully")
        
        # Test config
        config = SimpleConfig()
        print("✅ Configuration created")
        
        # Test bot initialization  
        bot = SimpleZLibBot()
        print("✅ Bot initialized")
        
        # Test demo search
        results = bot.search_books_demo("test", 2)
        print(f"✅ Demo search returned {len(results)} results")
        
        # Test CSV export
        success = bot.export_to_csv(results, "test_output.csv")
        if success:
            print("✅ CSV export successful")
        else:
            print("❌ CSV export failed")
            
        return True
        
    except Exception as e:
        print(f"❌ Functionality test failed: {e}")
        return False

def test_file_operations():
    """Test file operations"""
    print("\n📁 Testing file operations...")
    
    import os
    
    try:
        # Test directory creation
        test_dir = "test_output"
        os.makedirs(test_dir, exist_ok=True)
        print("✅ Directory creation works")
        
        # Test file writing
        test_file = os.path.join(test_dir, "test.txt")
        with open(test_file, 'w', encoding='utf-8') as f:
            f.write("Test content")
        print("✅ File writing works")
        
        # Test file reading
        with open(test_file, 'r', encoding='utf-8') as f:
            content = f.read()
        print("✅ File reading works")
        
        # Cleanup
        os.remove(test_file)
        os.rmdir(test_dir)
        print("✅ File cleanup works")
        
        return True
        
    except Exception as e:
        print(f"❌ File operations failed: {e}")
        return False

def main():
    """Run all tests"""
    print("=" * 50)
    print("🤖 Z-Library Bot - Windows Tests")
    print("=" * 50)
    
    all_passed = True
    
    # Test imports
    if not test_imports():
        all_passed = False
        print("\n❌ Import tests failed!")
        print("💡 Run the setup script: windows_setup.bat")
        return False
    
    # Test file operations
    if not test_file_operations():
        all_passed = False
        print("\n❌ File operations failed!")
    
    # Test basic functionality
    if not test_basic_functionality():
        all_passed = False
        print("\n❌ Functionality tests failed!")
    
    print("\n" + "=" * 50)
    if all_passed:
        print("🎉 ALL TESTS PASSED!")
        print("✅ Your system is ready to run the Z-Library bot")
        print("\nYou can now run:")
        print("   python zlib_bot_simple.py")
    else:
        print("❌ SOME TESTS FAILED!")
        print("💡 Please fix the issues above before proceeding")
    print("=" * 50)
    
    return all_passed

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)