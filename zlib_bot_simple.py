#!/usr/bin/env python3
"""
Z-Library Bot - Windows Simplified Version

⚠️  IMPORTANT LEGAL WARNING ⚠️
This tool is for EDUCATIONAL PURPOSES ONLY. 
- Only use with legally available content
- Respect copyright laws and terms of service
- Do not use for mass downloading copyrighted material
- Use responsibly and ethically
"""

import os
import time
import json
import logging
import requests
from bs4 import BeautifulSoup
from urllib.parse import urljoin, urlparse
from tqdm import tqdm
from fake_useragent import UserAgent
import csv
from datetime import datetime

class SimpleConfig:
    """Simplified configuration for Windows"""
    
    def __init__(self):
        # Basic settings
        self.USER_AGENT = "Legal-Content-Aggregator/1.0 (Educational Purpose)"
        self.REQUEST_DELAY = 2  # seconds
        self.MAX_RETRIES = 3
        self.TIMEOUT = 30
        
        # Directories
        self.DOWNLOAD_DIR = "downloads"
        self.METADATA_DIR = "metadata"
        
        # Headers
        self.HEADERS = {
            'User-Agent': self.USER_AGENT,
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
            'Accept-Encoding': 'gzip, deflate',
            'Connection': 'keep-alive',
        }
        
        # Create directories
        os.makedirs(self.DOWNLOAD_DIR, exist_ok=True)
        os.makedirs(self.METADATA_DIR, exist_ok=True)

class SimpleZLibBot:
    """Simplified Z-Library Bot for Windows"""
    
    def __init__(self):
        self.config = SimpleConfig()
        self.session = requests.Session()
        self.session.headers.update(self.config.HEADERS)
        self.ua = UserAgent()
        self.setup_logging()
        
    def setup_logging(self):
        """Setup basic logging"""
        logging.basicConfig(
            level=logging.INFO,
            format='%(asctime)s - %(levelname)s - %(message)s',
            handlers=[
                logging.FileHandler("bot.log"),
                logging.StreamHandler()
            ]
        )
        self.logger = logging.getLogger(__name__)
        
    def display_legal_warning(self):
        """Display legal warning"""
        print("\n" + "="*60)
        print("⚠️  IMPORTANT LEGAL WARNING ⚠️")
        print("="*60)
        print("This tool is for EDUCATIONAL PURPOSES ONLY.")
        print("- Only use with legally available content")
        print("- Respect copyright laws and terms of service")
        print("- Do not use for mass downloading copyrighted material")
        print("- Use responsibly and ethically")
        print("="*60)
        
        while True:
            response = input("\nDo you understand and agree to use this tool legally? (yes/no): ").lower()
            if response == 'yes':
                break
            elif response == 'no':
                print("Exiting. Please only use this tool for legal purposes.")
                exit(1)
            else:
                print("Please enter 'yes' or 'no'")
                
    def get_random_user_agent(self):
        """Get random user agent"""
        try:
            return self.ua.random
        except:
            return self.config.USER_AGENT
            
    def make_request(self, url, **kwargs):
        """Make HTTP request with rate limiting"""
        try:
            # Rate limiting
            time.sleep(self.config.REQUEST_DELAY)
            
            # Rotate user agent
            self.session.headers['User-Agent'] = self.get_random_user_agent()
            
            response = self.session.get(url, timeout=self.config.TIMEOUT, **kwargs)
            response.raise_for_status()
            
            self.logger.info(f"✅ Successfully fetched: {url}")
            return response
            
        except requests.exceptions.RequestException as e:
            self.logger.error(f"❌ Error fetching {url}: {str(e)}")
            return None
    
    def search_books_demo(self, query, limit=10):
        """Demo search function (replace with real implementation)"""
        self.logger.info(f"🔍 Demo search for: {query}")
        
        # Demo results for testing
        results = []
        for i in range(min(limit, 5)):
            result = {
                'title': f"Demo Book {i+1}: {query}",
                'author': f"Demo Author {i+1}",
                'year': 2020 + i,
                'format': 'PDF',
                'size': f"{1.2 + i*0.3:.1f} MB",
                'description': f"This is a demo book about {query} for educational purposes.",
                'isbn': f"978-0-123-45678-{i}",
                'language': 'English',
                'pages': 200 + i*10,
                'download_url': None,  # Would be real URL
                'source_url': f"https://example.com/book-{i}",
                'extraction_date': datetime.now().isoformat(),
                'legal_status': 'Demo - Educational Use Only'
            }
            results.append(result)
            
        return results
    
    def save_metadata_json(self, metadata, filename):
        """Save metadata to JSON"""
        try:
            filepath = os.path.join(self.config.METADATA_DIR, f"{filename}.json")
            with open(filepath, 'w', encoding='utf-8') as f:
                json.dump(metadata, f, indent=2, ensure_ascii=False)
            self.logger.info(f"💾 Saved metadata: {filename}.json")
            return True
        except Exception as e:
            self.logger.error(f"❌ Error saving metadata: {e}")
            return False
    
    def export_to_csv(self, results, filename="search_results.csv"):
        """Export results to CSV"""
        try:
            filepath = os.path.join(self.config.METADATA_DIR, filename)
            if results:
                fieldnames = results[0].keys()
                with open(filepath, 'w', newline='', encoding='utf-8') as csvfile:
                    writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
                    writer.writeheader()
                    writer.writerows(results)
            self.logger.info(f"📊 Exported to CSV: {filename}")
            return True
        except Exception as e:
            self.logger.error(f"❌ Error exporting CSV: {e}")
            return False
    
    def download_file(self, url, filename):
        """Download a file with progress bar"""
        if not url:
            self.logger.warning("⚠️  No download URL provided")
            return False
            
        try:
            response = self.make_request(url, stream=True)
            if not response:
                return False
                
            filepath = os.path.join(self.config.DOWNLOAD_DIR, filename)
            total_size = int(response.headers.get('content-length', 0))
            
            with open(filepath, 'wb') as f:
                if total_size > 0:
                    with tqdm(total=total_size, unit='B', unit_scale=True, desc=filename) as pbar:
                        for chunk in response.iter_content(chunk_size=8192):
                            if chunk:
                                f.write(chunk)
                                pbar.update(len(chunk))
                else:
                    for chunk in response.iter_content(chunk_size=8192):
                        if chunk:
                            f.write(chunk)
                            
            self.logger.info(f"⬇️  Downloaded: {filename}")
            return True
            
        except Exception as e:
            self.logger.error(f"❌ Download failed {filename}: {e}")
            return False
    
    def run_demo_search(self, query="python programming", limit=3):
        """Run a demo search"""
        self.display_legal_warning()
        
        print(f"\n🔍 Running demo search for: {query}")
        results = self.search_books_demo(query, limit)
        
        if not results:
            print("❌ No results found.")
            return
            
        print(f"\n📚 Found {len(results)} demo results:")
        for i, book in enumerate(results, 1):
            print(f"\n{i}. {book['title']}")
            print(f"   📖 Author: {book['author']}")
            print(f"   📅 Year: {book['year']}")
            print(f"   📄 Format: {book['format']}")
            print(f"   💾 Size: {book['size']}")
            print(f"   ⚖️  Status: {book['legal_status']}")
            
        # Save results
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        csv_filename = f"demo_search_{query.replace(' ', '_')}_{timestamp}.csv"
        
        self.export_to_csv(results, csv_filename)
        
        # Save individual metadata
        for i, book in enumerate(results):
            metadata_filename = f"demo_book_{i+1}_{timestamp}"
            self.save_metadata_json(book, metadata_filename)
            
        print(f"\n✅ Demo completed!")
        print(f"📁 Results saved in:")
        print(f"   📊 CSV: {self.config.METADATA_DIR}\\{csv_filename}")
        print(f"   📋 JSON: {self.config.METADATA_DIR}\\")
        print(f"   📝 Logs: bot.log")

def main():
    """Main function"""
    print("🤖 Z-Library Educational Bot - Windows Version")
    print("📚 Educational web scraping demonstration")
    
    bot = SimpleZLibBot()
    
    print("\n🎯 Demo Mode Options:")
    print("1. Quick demo (3 results)")
    print("2. Custom search")
    print("3. Exit")
    
    while True:
        choice = input("\nEnter your choice (1-3): ").strip()
        
        if choice == "1":
            bot.run_demo_search("python programming", 3)
            break
        elif choice == "2":
            query = input("Enter search query: ").strip()
            if query:
                try:
                    limit = int(input("Enter number of results (1-10, default 3): ") or "3")
                    limit = max(1, min(limit, 10))  # Clamp between 1-10
                except ValueError:
                    limit = 3
                bot.run_demo_search(query, limit)
            else:
                print("❌ Query cannot be empty")
                continue
            break
        elif choice == "3":
            print("👋 Goodbye!")
            exit(0)
        else:
            print("❌ Invalid choice. Please enter 1, 2, or 3.")

if __name__ == "__main__":
    main()