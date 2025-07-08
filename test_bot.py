#!/usr/bin/env python3
"""
Test Script for Z-Library Bot
Safe testing without real downloads
"""

import unittest
from unittest.mock import Mock, patch
import os
import sys
import tempfile
import shutil

# Add the project directory to path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from zlib_bot import ZLibBot
from config import Config

class TestZLibBot(unittest.TestCase):
    """Test cases for ZLibBot"""
    
    def setUp(self):
        """Set up test fixtures"""
        self.test_dir = tempfile.mkdtemp()
        self.bot = ZLibBot()
        
        # Override directories for testing
        self.bot.config.DOWNLOAD_DIR = os.path.join(self.test_dir, "downloads")
        self.bot.config.METADATA_DIR = os.path.join(self.test_dir, "metadata")
        self.bot.config.LOG_FILE = os.path.join(self.test_dir, "test.log")
        
        # Create test directories
        self.bot.setup_directories()
        
    def tearDown(self):
        """Clean up test fixtures"""
        shutil.rmtree(self.test_dir, ignore_errors=True)
    
    def test_setup_directories(self):
        """Test directory creation"""
        self.assertTrue(os.path.exists(self.bot.config.DOWNLOAD_DIR))
        self.assertTrue(os.path.exists(self.bot.config.METADATA_DIR))
    
    def test_search_books_demo(self):
        """Test demo search functionality"""
        results = self.bot.search_books("test query", limit=3)
        
        self.assertIsInstance(results, list)
        self.assertEqual(len(results), 3)
        
        for result in results:
            self.assertIn('title', result)
            self.assertIn('author', result)
            self.assertIn('year', result)
            self.assertIn('format', result)
    
    def test_save_metadata(self):
        """Test metadata saving"""
        test_metadata = {
            'title': 'Test Book',
            'author': 'Test Author',
            'year': '2023'
        }
        
        success = self.bot.save_metadata(test_metadata, "test_book")
        self.assertTrue(success)
        
        # Check if file was created
        metadata_file = os.path.join(self.bot.config.METADATA_DIR, "test_book.json")
        self.assertTrue(os.path.exists(metadata_file))
    
    def test_export_to_csv(self):
        """Test CSV export functionality"""
        test_results = [
            {'title': 'Book 1', 'author': 'Author 1', 'year': '2023'},
            {'title': 'Book 2', 'author': 'Author 2', 'year': '2024'}
        ]
        
        success = self.bot.export_to_csv(test_results, "test_export.csv")
        self.assertTrue(success)
        
        # Check if file was created
        csv_file = os.path.join(self.bot.config.METADATA_DIR, "test_export.csv")
        self.assertTrue(os.path.exists(csv_file))
    
    @patch('requests.Session.get')
    def test_make_request(self, mock_get):
        """Test HTTP request functionality"""
        # Mock successful response
        mock_response = Mock()
        mock_response.status_code = 200
        mock_response.raise_for_status.return_value = None
        mock_get.return_value = mock_response
        
        response = self.bot.make_request("http://example.com")
        self.assertIsNotNone(response)
        mock_get.assert_called_once()
    
    def test_get_random_user_agent(self):
        """Test user agent generation"""
        user_agent = self.bot.get_random_user_agent()
        self.assertIsInstance(user_agent, str)
        self.assertTrue(len(user_agent) > 0)

def run_integration_test():
    """Run integration test with demo functionality"""
    print("🧪 Running Integration Test")
    print("="*50)
    
    bot = ZLibBot()
    
    # Test search
    print("Testing search functionality...")
    results = bot.search_books("machine learning", limit=2)
    print(f"✅ Search returned {len(results)} results")
    
    # Test metadata saving
    print("Testing metadata saving...")
    for i, result in enumerate(results):
        success = bot.save_metadata(result, f"test_book_{i}")
        if success:
            print(f"✅ Saved metadata for: {result['title']}")
    
    # Test CSV export
    print("Testing CSV export...")
    success = bot.export_to_csv(results, "integration_test.csv")
    if success:
        print("✅ CSV export successful")
    
    print("\n🎉 Integration test completed successfully!")
    print(f"Check the '{bot.config.METADATA_DIR}' directory for output files.")

def run_safe_demo():
    """Run a safe demo without any real downloads"""
    print("🎭 Safe Demo Mode")
    print("="*50)
    print("This demo shows the bot functionality without real downloads.")
    print("All operations are simulated for educational purposes.")
    
    bot = ZLibBot()
    
    # Override the legal warning for demo
    bot.display_legal_warning = lambda: print("Demo mode - no legal concerns")
    
    # Run demo search
    bot.run_search_and_download("artificial intelligence", limit=3, download=False)

if __name__ == "__main__":
    print("Z-Library Bot Test Suite")
    print("Choose an option:")
    print("1. Run unit tests")
    print("2. Run integration test")
    print("3. Run safe demo")
    
    choice = input("\nEnter your choice (1-3): ").strip()
    
    if choice == "1":
        # Run unit tests
        unittest.main(verbosity=2, exit=False)
    elif choice == "2":
        # Run integration test
        run_integration_test()
    elif choice == "3":
        # Run safe demo
        run_safe_demo()
    else:
        print("Invalid choice. Running unit tests by default.")
        unittest.main(verbosity=2)