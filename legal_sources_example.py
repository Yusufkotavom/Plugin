#!/usr/bin/env python3
"""
Legal Book Sources Example
This file shows how to adapt the bot for legal book sources
"""

import requests
from bs4 import BeautifulSoup
import json
from urllib.parse import quote
from zlib_bot import ZLibBot

class LegalBookBot(ZLibBot):
    """Extended bot with legal book source implementations"""
    
    def search_project_gutenberg(self, query, limit=10):
        """Search Project Gutenberg for public domain books"""
        self.logger.info(f"Searching Project Gutenberg for: {query}")
        
        # Project Gutenberg search API
        base_url = "https://www.gutenberg.org/ebooks/search/"
        params = {
            'query': query,
            'submit_search': 'Go!'
        }
        
        try:
            response = self.make_request(base_url, params=params)
            if not response:
                return []
                
            soup = BeautifulSoup(response.content, 'html.parser')
            results = []
            
            # Parse search results (adapt based on actual HTML structure)
            book_items = soup.find_all('li', class_='booklink')[:limit]
            
            for item in book_items:
                try:
                    title_elem = item.find('a')
                    if title_elem:
                        title = title_elem.get_text(strip=True)
                        book_url = title_elem.get('href')
                        
                        # Extract additional metadata
                        author_elem = item.find('span', class_='subtitle')
                        author = author_elem.get_text(strip=True) if author_elem else "Unknown"
                        
                        result = {
                            'title': title,
                            'author': author,
                            'source': 'Project Gutenberg',
                            'url': f"https://www.gutenberg.org{book_url}",
                            'license': 'Public Domain',
                            'formats': ['txt', 'epub', 'html'],
                            'legal_status': 'Public Domain'
                        }
                        results.append(result)
                        
                except Exception as e:
                    self.logger.error(f"Error parsing book item: {e}")
                    continue
                    
            return results
            
        except Exception as e:
            self.logger.error(f"Error searching Project Gutenberg: {e}")
            return []
    
    def search_open_library(self, query, limit=10):
        """Search Open Library for books"""
        self.logger.info(f"Searching Open Library for: {query}")
        
        # Open Library Search API
        api_url = "https://openlibrary.org/search.json"
        params = {
            'q': query,
            'limit': limit,
            'fields': 'key,title,author_name,first_publish_year,isbn,language,subject'
        }
        
        try:
            response = self.make_request(api_url, params=params)
            if not response:
                return []
                
            data = response.json()
            results = []
            
            for doc in data.get('docs', []):
                try:
                    result = {
                        'title': doc.get('title', 'Unknown Title'),
                        'author': ', '.join(doc.get('author_name', ['Unknown Author'])),
                        'year': doc.get('first_publish_year'),
                        'isbn': doc.get('isbn', [None])[0] if doc.get('isbn') else None,
                        'language': ', '.join(doc.get('language', ['en'])),
                        'subjects': doc.get('subject', [])[:5],  # First 5 subjects
                        'source': 'Open Library',
                        'url': f"https://openlibrary.org{doc.get('key')}",
                        'legal_status': 'Varies - Check Individual Book'
                    }
                    results.append(result)
                    
                except Exception as e:
                    self.logger.error(f"Error parsing Open Library result: {e}")
                    continue
                    
            return results
            
        except Exception as e:
            self.logger.error(f"Error searching Open Library: {e}")
            return []
    
    def search_internet_archive(self, query, limit=10):
        """Search Internet Archive for books"""
        self.logger.info(f"Searching Internet Archive for: {query}")
        
        # Internet Archive Search API
        api_url = "https://archive.org/advancedsearch.php"
        params = {
            'q': f'title:({query}) AND mediatype:texts',
            'fl': 'identifier,title,creator,date,description,format',
            'rows': limit,
            'output': 'json'
        }
        
        try:
            response = self.make_request(api_url, params=params)
            if not response:
                return []
                
            data = response.json()
            results = []
            
            for doc in data.get('response', {}).get('docs', []):
                try:
                    result = {
                        'title': doc.get('title', 'Unknown Title'),
                        'author': doc.get('creator', 'Unknown Author'),
                        'year': doc.get('date'),
                        'description': doc.get('description', '')[:200] + '...' if doc.get('description') else '',
                        'formats': doc.get('format', []),
                        'source': 'Internet Archive',
                        'url': f"https://archive.org/details/{doc.get('identifier')}",
                        'legal_status': 'Varies - Check Individual Item'
                    }
                    results.append(result)
                    
                except Exception as e:
                    self.logger.error(f"Error parsing Internet Archive result: {e}")
                    continue
                    
            return results
            
        except Exception as e:
            self.logger.error(f"Error searching Internet Archive: {e}")
            return []
    
    def search_all_legal_sources(self, query, limit_per_source=5):
        """Search all legal sources and combine results"""
        print(f"\n🔍 Searching all legal sources for: {query}")
        
        all_results = []
        
        # Search Project Gutenberg
        print("📚 Searching Project Gutenberg...")
        pg_results = self.search_project_gutenberg(query, limit_per_source)
        all_results.extend(pg_results)
        
        # Search Open Library
        print("📖 Searching Open Library...")
        ol_results = self.search_open_library(query, limit_per_source)
        all_results.extend(ol_results)
        
        # Search Internet Archive
        print("📜 Searching Internet Archive...")
        ia_results = self.search_internet_archive(query, limit_per_source)
        all_results.extend(ia_results)
        
        return all_results
    
    def get_book_download_links(self, book_url, source):
        """Extract download links for a specific book"""
        if 'gutenberg.org' in book_url:
            return self.get_gutenberg_download_links(book_url)
        elif 'openlibrary.org' in book_url:
            return self.get_openlibrary_download_links(book_url)
        elif 'archive.org' in book_url:
            return self.get_archive_download_links(book_url)
        else:
            return []
    
    def get_gutenberg_download_links(self, book_url):
        """Get download links from Project Gutenberg"""
        try:
            response = self.make_request(book_url)
            if not response:
                return []
                
            soup = BeautifulSoup(response.content, 'html.parser')
            download_links = []
            
            # Find download table
            download_table = soup.find('table', class_='files')
            if download_table:
                for row in download_table.find_all('tr')[1:]:  # Skip header
                    cells = row.find_all('td')
                    if len(cells) >= 3:
                        format_cell = cells[0]
                        link_cell = cells[1]
                        
                        format_text = format_cell.get_text(strip=True)
                        link = link_cell.find('a')
                        
                        if link:
                            download_url = link.get('href')
                            if download_url.startswith('/'):
                                download_url = f"https://www.gutenberg.org{download_url}"
                                
                            download_links.append({
                                'format': format_text,
                                'url': download_url,
                                'size': cells[2].get_text(strip=True) if len(cells) > 2 else 'Unknown'
                            })
            
            return download_links
            
        except Exception as e:
            self.logger.error(f"Error getting Gutenberg download links: {e}")
            return []

def main():
    """Example usage of legal book sources"""
    print("🤖 Legal Book Sources Bot")
    print("Educational demonstration with legal sources only")
    
    bot = LegalBookBot()
    
    # Example searches
    query = input("\nEnter search query: ").strip() or "python programming"
    
    # Search all legal sources
    results = bot.search_all_legal_sources(query, limit_per_source=3)
    
    if results:
        print(f"\n📚 Found {len(results)} results from legal sources:")
        for i, book in enumerate(results, 1):
            print(f"\n{i}. {book['title']}")
            print(f"   Author: {book['author']}")
            print(f"   Source: {book['source']}")
            print(f"   Legal Status: {book['legal_status']}")
            print(f"   URL: {book['url']}")
            
        # Export results
        bot.export_to_csv(results, f"legal_search_{query.replace(' ', '_')}.csv")
        print(f"\n✅ Results exported to metadata directory")
    else:
        print("No results found in legal sources.")

if __name__ == "__main__":
    main()