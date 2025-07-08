#!/usr/bin/env python3
"""
Z-Library Bot - Educational Web Scraping Example

⚠️  IMPORTANT LEGAL WARNING ⚠️
This tool is for EDUCATIONAL PURPOSES ONLY. 
- Only use with legally available content
- Respect copyright laws and terms of service
- Do not use for mass downloading copyrighted material
- Use responsibly and ethically

Author: Educational Bot
License: MIT (for educational use only)
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
from config import Config

class ZLibBot:
    def __init__(self):
        self.config = Config()
        self.session = requests.Session()
        self.session.headers.update(self.config.HEADERS)
        self.ua = UserAgent()
        self.setup_logging()
        self.setup_directories()
        
    def setup_logging(self):
        """Setup logging configuration"""
        logging.basicConfig(
            level=getattr(logging, self.config.LOG_LEVEL),
            format='%(asctime)s - %(levelname)s - %(message)s',
            handlers=[
                logging.FileHandler(self.config.LOG_FILE),
                logging.StreamHandler()
            ]
        )
        self.logger = logging.getLogger(__name__)
        
    def setup_directories(self):
        """Create necessary directories"""
        os.makedirs(self.config.DOWNLOAD_DIR, exist_ok=True)
        os.makedirs(self.config.METADATA_DIR, exist_ok=True)
        
    def display_legal_warning(self):
        """Display legal warning to user"""
        print("\n" + "="*60)
        print("⚠️  IMPORTANT LEGAL WARNING ⚠️")
        print("="*60)
        print("This tool is for EDUCATIONAL PURPOSES ONLY.")
        print("- Only use with legally available content")
        print("- Respect copyright laws and terms of service")
        print("- Do not use for mass downloading copyrighted material")
        print("- Use responsibly and ethically")
        print("="*60)
        
        response = input("\nDo you understand and agree to use this tool legally? (yes/no): ")
        if response.lower() != 'yes':
            print("Exiting. Please only use this tool for legal purposes.")
            exit(1)
            
    def get_random_user_agent(self):
        """Get a random user agent"""
        try:
            return self.ua.random
        except:
            return self.config.USER_AGENT
            
    def make_request(self, url, **kwargs):
        """Make a rate-limited HTTP request"""
        try:
            # Rotate user agent
            self.session.headers['User-Agent'] = self.get_random_user_agent()
            
            # Rate limiting
            time.sleep(self.config.REQUEST_DELAY)
            
            response = self.session.get(url, timeout=self.config.TIMEOUT, **kwargs)
            response.raise_for_status()
            
            self.logger.info(f"Successfully fetched: {url}")
            return response
            
        except requests.exceptions.RequestException as e:
            self.logger.error(f"Error fetching {url}: {str(e)}")
            return None
            
    def search_books(self, query, limit=10):
        """
        Search for books (DEMO FUNCTION - adapt for legal sources)
        This is a template function that should be adapted for legal book sources
        """
        self.logger.info(f"Searching for: {query}")
        
        # This is a placeholder - you should adapt this for legal book sources
        # such as Project Gutenberg, Open Library, etc.
        search_results = []
        
        # Example structure of what search results might look like
        for i in range(min(limit, 5)):  # Limited demo results
            result = {
                'title': f"Demo Book {i+1} - {query}",
                'author': f"Demo Author {i+1}",
                'year': 2020 + i,
                'format': 'pdf',
                'size': '1.2 MB',
                'download_url': None,  # Would be populated from legal sources
                'metadata_url': None,
                'description': f"This is a demo book result for educational purposes",
                'isbn': f"978-0-123-45678-{i}",
                'language': 'English',
                'pages': 200 + i*10
            }
            search_results.append(result)
            
        return search_results
        
    def extract_metadata(self, book_url):
        """Extract metadata from a book page"""
        response = self.make_request(book_url)
        if not response:
            return None
            
        soup = BeautifulSoup(response.content, 'html.parser')
        
        # This is a template - adapt for your legal source
        metadata = {
            'title': 'Demo Title',
            'author': 'Demo Author',
            'year': '2023',
            'publisher': 'Demo Publisher',
            'isbn': '978-0-123-456789',
            'language': 'English',
            'pages': '200',
            'format': 'PDF',
            'size': '1.5 MB',
            'description': 'Demo description',
            'tags': ['demo', 'educational'],
            'extraction_date': datetime.now().isoformat()
        }
        
        return metadata
        
    def download_book(self, download_url, filename):
        """Download a book file"""
        if not download_url:
            self.logger.warning("No download URL provided")
            return False
            
        try:
            response = self.make_request(download_url, stream=True)
            if not response:
                return False
                
            filepath = os.path.join(self.config.DOWNLOAD_DIR, filename)
            
            # Get file size for progress bar
            total_size = int(response.headers.get('content-length', 0))
            
            with open(filepath, 'wb') as file:
                if total_size > 0:
                    with tqdm(total=total_size, unit='B', unit_scale=True, desc=filename) as pbar:
                        for chunk in response.iter_content(chunk_size=8192):
                            if chunk:
                                file.write(chunk)
                                pbar.update(len(chunk))
                else:
                    for chunk in response.iter_content(chunk_size=8192):
                        if chunk:
                            file.write(chunk)
                            
            self.logger.info(f"Downloaded: {filename}")
            return True
            
        except Exception as e:
            self.logger.error(f"Error downloading {filename}: {str(e)}")
            return False
            
    def save_metadata(self, metadata, filename):
        """Save metadata to JSON file"""
        try:
            filepath = os.path.join(self.config.METADATA_DIR, f"{filename}.json")
            with open(filepath, 'w', encoding='utf-8') as file:
                json.dump(metadata, file, indent=2, ensure_ascii=False)
            self.logger.info(f"Saved metadata: {filename}.json")
            return True
        except Exception as e:
            self.logger.error(f"Error saving metadata: {str(e)}")
            return False
            
    def export_to_csv(self, results, filename="search_results.csv"):
        """Export search results to CSV"""
        try:
            filepath = os.path.join(self.config.METADATA_DIR, filename)
            if results:
                # Get field names from the first result
                fieldnames = results[0].keys()
                with open(filepath, 'w', newline='', encoding='utf-8') as csvfile:
                    writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
                    writer.writeheader()
                    writer.writerows(results)
            self.logger.info(f"Exported results to: {filename}")
            return True
        except Exception as e:
            self.logger.error(f"Error exporting to CSV: {str(e)}")
            return False
            
    def run_search_and_download(self, query, limit=5, download=False):
        """Main function to search and optionally download books"""
        self.display_legal_warning()
        
        print(f"\n🔍 Searching for: {query}")
        results = self.search_books(query, limit)
        
        if not results:
            print("No results found.")
            return
            
        print(f"\n📚 Found {len(results)} results:")
        for i, book in enumerate(results, 1):
            print(f"\n{i}. {book['title']}")
            print(f"   Author: {book['author']}")
            print(f"   Year: {book['year']}")
            print(f"   Format: {book['format']}")
            print(f"   Size: {book['size']}")
            
        # Save metadata
        self.export_to_csv(results, f"search_{query.replace(' ', '_')}.csv")
        
        for book in results:
            metadata_filename = f"{book['title'].replace(' ', '_')}"
            self.save_metadata(book, metadata_filename)
            
        if download:
            print("\n⬇️  Starting downloads...")
            for book in results:
                if book.get('download_url'):
                    filename = f"{book['title']}.{book['format']}"
                    self.download_book(book['download_url'], filename)
                else:
                    print(f"⚠️  No download URL for: {book['title']}")
                    
        print(f"\n✅ Task completed! Check '{self.config.DOWNLOAD_DIR}' and '{self.config.METADATA_DIR}' directories.")

def main():
    """Main function"""
    print("🤖 Z-Library Educational Bot")
    print("Educational web scraping demonstration")
    
    bot = ZLibBot()
    
    # Example usage
    query = input("\nEnter search query: ").strip()
    if not query:
        query = "python programming"  # Default search
        
    limit = int(input("Enter number of results (default 5): ") or 5)
    
    download_choice = input("Download books? (y/n, default n): ").lower()
    download = download_choice == 'y'
    
    bot.run_search_and_download(query, limit, download)

if __name__ == "__main__":
    main()