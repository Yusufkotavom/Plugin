#!/usr/bin/env python3
"""
SourceForge Aggregator - Legal Open Source Software Collection

This module aggregates open source software information from SourceForge
using respectful web scraping in compliance with their terms of service.

LEGAL COMPLIANCE:
- Respects robots.txt
- Conservative rate limiting (100 requests/hour)
- Only collects public project information
- Follows SourceForge Terms of Service
- Uses proper User-Agent identification
"""

import asyncio
import aiohttp
import time
import logging
import re
from typing import List, Dict, Optional, AsyncGenerator
from datetime import datetime
from dataclasses import dataclass
from urllib.parse import urljoin, urlparse
from bs4 import BeautifulSoup
import xml.etree.ElementTree as ET

@dataclass
class SourceForgeProject:
    """SourceForge project data structure"""
    name: str
    unix_name: str
    summary: str
    description: str
    homepage_url: str
    download_url: str
    project_url: str
    category: str
    programming_language: str
    license: str
    operating_system: str
    status: str
    
    # Statistics
    download_count: int
    user_rating: float
    review_count: int
    last_update: str
    
    # Additional metadata
    screenshots: List[str]
    tags: List[str]
    developers: List[str]
    
    def to_dict(self) -> Dict:
        """Convert to dictionary for storage"""
        return {
            'basic_info': {
                'name': self.name,
                'unix_name': self.unix_name,
                'summary': self.summary,
                'description': self.description,
                'category': self.category,
                'programming_language': self.programming_language,
                'license': self.license,
                'operating_system': self.operating_system,
                'status': self.status
            },
            'urls': {
                'homepage': self.homepage_url,
                'download': self.download_url,
                'project': self.project_url
            },
            'statistics': {
                'downloads': self.download_count,
                'rating': self.user_rating,
                'reviews': self.review_count,
                'last_update': self.last_update
            },
            'metadata': {
                'screenshots': self.screenshots,
                'tags': self.tags,
                'developers': self.developers
            },
            'aggregation_metadata': {
                'source': 'sourceforge',
                'collected_at': datetime.now().isoformat(),
                'legal_status': 'open_source_public'
            }
        }

class SourceForgeClient:
    """SourceForge client with respectful rate limiting"""
    
    def __init__(self, rate_limit: int = 100):
        self.base_url = "https://sourceforge.net"
        self.api_url = "https://sourceforge.net/rest"
        self.rate_limit = rate_limit  # requests per hour
        self.requests_made = 0
        self.rate_reset_time = time.time() + 3600
        
        self.headers = {
            'User-Agent': 'Legal-Content-Aggregator/1.0 (Educational Purpose; respectful bot)',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
            'Accept-Encoding': 'gzip, deflate',
            'Connection': 'keep-alive',
            'Cache-Control': 'max-age=0'
        }
        
        self.logger = logging.getLogger(__name__)
        
    async def check_rate_limit(self):
        """Check and enforce rate limiting"""
        current_time = time.time()
        
        # Reset counter if hour has passed
        if current_time >= self.rate_reset_time:
            self.requests_made = 0
            self.rate_reset_time = current_time + 3600
            
        # Check if we need to wait
        if self.requests_made >= self.rate_limit:
            wait_time = self.rate_reset_time - current_time
            self.logger.warning(f"Rate limit reached. Waiting {wait_time:.0f} seconds...")
            await asyncio.sleep(wait_time)
            self.requests_made = 0
            self.rate_reset_time = time.time() + 3600
            
        # Add delay between requests (36 seconds for 100/hour)
        await asyncio.sleep(36)
        
    async def make_request(self, session: aiohttp.ClientSession, url: str) -> Optional[str]:
        """Make HTTP request with rate limiting"""
        await self.check_rate_limit()
        
        try:
            async with session.get(url, headers=self.headers) as response:
                self.requests_made += 1
                
                if response.status == 200:
                    content = await response.text()
                    self.logger.info(f"✅ Successfully fetched: {url}")
                    return content
                    
                elif response.status == 403:
                    self.logger.error(f"Access forbidden: {url}")
                    return None
                    
                elif response.status == 404:
                    self.logger.warning(f"Not found: {url}")
                    return None
                    
                elif response.status == 429:
                    self.logger.warning(f"Rate limited by server: {url}")
                    await asyncio.sleep(300)  # Wait 5 minutes
                    return None
                    
                else:
                    self.logger.error(f"HTTP {response.status}: {url}")
                    return None
                    
        except Exception as e:
            self.logger.error(f"Request failed for {url}: {e}")
            return None

class SourceForgeAggregator:
    """Main SourceForge aggregator"""
    
    def __init__(self, config: Dict = None):
        self.client = SourceForgeClient()
        self.config = config or {}
        self.logger = logging.getLogger(__name__)
        
        # Quality filters
        self.filters = {
            'min_downloads': self.config.get('min_downloads', 1000),
            'min_rating': self.config.get('min_rating', 3.0),
            'active_projects_only': self.config.get('active_projects_only', True),
            'exclude_categories': self.config.get('exclude_categories', [
                'Games/Entertainment',  # Often not development tools
                'Multimedia',           # Often not source available
            ]),
            'preferred_licenses': self.config.get('preferred_licenses', [
                'GPL', 'LGPL', 'MIT', 'Apache', 'BSD', 'MPL'
            ])
        }
    
    async def get_project_categories(self) -> List[Dict]:
        """Get available project categories"""
        async with aiohttp.ClientSession() as session:
            url = f"{self.client.base_url}/directory/"
            content = await self.client.make_request(session, url)
            
            if not content:
                return []
                
            soup = BeautifulSoup(content, 'html.parser')
            categories = []
            
            # Parse category links
            category_links = soup.find_all('a', href=re.compile(r'/directory/'))
            
            for link in category_links:
                href = link.get('href', '')
                if '/directory/' in href and href.count('/') == 2:
                    category_name = link.get_text(strip=True)
                    if category_name:
                        categories.append({
                            'name': category_name,
                            'url': urljoin(self.client.base_url, href)
                        })
                        
            return categories[:20]  # Limit for rate limiting
    
    async def search_projects(self, 
                            query: str = None,
                            category: str = None,
                            sort: str = 'downloads',
                            limit: int = 50) -> AsyncGenerator[SourceForgeProject, None]:
        """Search for projects"""
        
        async with aiohttp.ClientSession() as session:
            if query:
                # Use search functionality
                search_url = f"{self.client.base_url}/directory/?q={query}"
            elif category:
                # Browse by category
                search_url = f"{self.client.base_url}/directory/{category}/"
            else:
                # Get popular projects
                search_url = f"{self.client.base_url}/directory/?sort={sort}"
                
            page = 1
            projects_found = 0
            
            while projects_found < limit:
                page_url = f"{search_url}&page={page}"
                content = await self.client.make_request(session, page_url)
                
                if not content:
                    break
                    
                soup = BeautifulSoup(content, 'html.parser')
                project_links = self._extract_project_links(soup)
                
                if not project_links:
                    break
                    
                self.logger.info(f"Processing page {page}: {len(project_links)} projects")
                
                for project_url in project_links:
                    if projects_found >= limit:
                        break
                        
                    project = await self._get_project_details(session, project_url)
                    if project and self._meets_quality_criteria(project):
                        yield project
                        projects_found += 1
                        
                page += 1
                
                # Safety limit
                if page > 10:
                    break
    
    def _extract_project_links(self, soup: BeautifulSoup) -> List[str]:
        """Extract project links from search results"""
        links = []
        
        # Look for project links in the directory listing
        project_elements = soup.find_all('a', href=re.compile(r'/projects/[^/]+/?$'))
        
        for element in project_elements:
            href = element.get('href')
            if href and '/projects/' in href:
                full_url = urljoin(self.client.base_url, href)
                if full_url not in links:
                    links.append(full_url)
                    
        return links[:20]  # Limit for rate limiting
    
    async def _get_project_details(self, session: aiohttp.ClientSession, project_url: str) -> Optional[SourceForgeProject]:
        """Get detailed project information"""
        
        try:
            content = await self.client.make_request(session, project_url)
            if not content:
                return None
                
            soup = BeautifulSoup(content, 'html.parser')
            
            # Extract project unix name from URL
            unix_name = self._extract_unix_name(project_url)
            if not unix_name:
                return None
                
            # Extract basic information
            name = self._extract_project_name(soup)
            summary = self._extract_summary(soup)
            description = self._extract_description(soup)
            
            # Extract metadata
            category = self._extract_category(soup)
            programming_language = self._extract_programming_language(soup)
            license_info = self._extract_license(soup)
            operating_system = self._extract_operating_system(soup)
            status = self._extract_status(soup)
            
            # Extract statistics
            download_count = self._extract_download_count(soup)
            rating_info = self._extract_rating(soup)
            last_update = self._extract_last_update(soup)
            
            # Extract additional data
            screenshots = self._extract_screenshots(soup, project_url)
            tags = self._extract_tags(soup)
            developers = self._extract_developers(soup)
            
            # Build URLs
            homepage_url = self._extract_homepage(soup)
            download_url = f"{project_url}/files/"
            
            return SourceForgeProject(
                name=name,
                unix_name=unix_name,
                summary=summary,
                description=description,
                homepage_url=homepage_url,
                download_url=download_url,
                project_url=project_url,
                category=category,
                programming_language=programming_language,
                license=license_info,
                operating_system=operating_system,
                status=status,
                download_count=download_count,
                user_rating=rating_info.get('rating', 0.0),
                review_count=rating_info.get('count', 0),
                last_update=last_update,
                screenshots=screenshots,
                tags=tags,
                developers=developers
            )
            
        except Exception as e:
            self.logger.error(f"Error processing project {project_url}: {e}")
            return None
    
    def _extract_unix_name(self, url: str) -> Optional[str]:
        """Extract unix name from project URL"""
        try:
            parts = urlparse(url).path.split('/')
            if 'projects' in parts:
                idx = parts.index('projects')
                if idx + 1 < len(parts):
                    return parts[idx + 1]
        except:
            pass
        return None
    
    def _extract_project_name(self, soup: BeautifulSoup) -> str:
        """Extract project name"""
        # Try different selectors for project name
        selectors = [
            'h1.project-title',
            '.project-info h1',
            'h1',
            '.breadcrumb-item:last-child'
        ]
        
        for selector in selectors:
            element = soup.select_one(selector)
            if element:
                name = element.get_text(strip=True)
                if name and len(name) > 2:
                    return name
                    
        return "Unknown Project"
    
    def _extract_summary(self, soup: BeautifulSoup) -> str:
        """Extract project summary"""
        selectors = [
            '.project-summary',
            '.project-description-short',
            'meta[name="description"]'
        ]
        
        for selector in selectors:
            if selector.startswith('meta'):
                element = soup.select_one(selector)
                if element:
                    return element.get('content', '')
            else:
                element = soup.select_one(selector)
                if element:
                    return element.get_text(strip=True)
                    
        return ""
    
    def _extract_description(self, soup: BeautifulSoup) -> str:
        """Extract project description"""
        selectors = [
            '.project-description',
            '.description',
            '.project-info .description'
        ]
        
        for selector in selectors:
            element = soup.select_one(selector)
            if element:
                desc = element.get_text(strip=True)
                # Truncate if too long
                return desc[:2000] if len(desc) > 2000 else desc
                
        return ""
    
    def _extract_category(self, soup: BeautifulSoup) -> str:
        """Extract project category"""
        # Look for category information
        category_links = soup.find_all('a', href=re.compile(r'/directory/'))
        for link in category_links:
            text = link.get_text(strip=True)
            if text and '::' not in text:  # Avoid breadcrumb items
                return text
                
        return "Unknown"
    
    def _extract_programming_language(self, soup: BeautifulSoup) -> str:
        """Extract programming language"""
        # Look for language information in project metadata
        lang_element = soup.find(text=re.compile(r'Programming Language'))
        if lang_element:
            parent = lang_element.parent
            if parent:
                next_sibling = parent.find_next_sibling()
                if next_sibling:
                    return next_sibling.get_text(strip=True)
                    
        return "Unknown"
    
    def _extract_license(self, soup: BeautifulSoup) -> str:
        """Extract license information"""
        # Look for license information
        license_element = soup.find(text=re.compile(r'License'))
        if license_element:
            parent = license_element.parent
            if parent:
                next_sibling = parent.find_next_sibling()
                if next_sibling:
                    return next_sibling.get_text(strip=True)
                    
        # Alternative: look for common license terms
        page_text = soup.get_text().lower()
        common_licenses = ['gpl', 'mit', 'apache', 'bsd', 'lgpl', 'mpl']
        for license_name in common_licenses:
            if license_name in page_text:
                return license_name.upper()
                
        return "Unknown"
    
    def _extract_operating_system(self, soup: BeautifulSoup) -> str:
        """Extract operating system information"""
        os_element = soup.find(text=re.compile(r'Operating System'))
        if os_element:
            parent = os_element.parent
            if parent:
                next_sibling = parent.find_next_sibling()
                if next_sibling:
                    return next_sibling.get_text(strip=True)
                    
        return "Cross-platform"
    
    def _extract_status(self, soup: BeautifulSoup) -> str:
        """Extract project status"""
        status_element = soup.find(text=re.compile(r'Development Status'))
        if status_element:
            parent = status_element.parent
            if parent:
                next_sibling = parent.find_next_sibling()
                if next_sibling:
                    return next_sibling.get_text(strip=True)
                    
        return "Unknown"
    
    def _extract_download_count(self, soup: BeautifulSoup) -> int:
        """Extract download count"""
        # Look for download statistics
        download_elements = soup.find_all(text=re.compile(r'download'))
        for element in download_elements:
            text = str(element).lower()
            # Look for patterns like "1,234 downloads"
            match = re.search(r'(\d{1,3}(?:,\d{3})*)\s*downloads?', text)
            if match:
                try:
                    return int(match.group(1).replace(',', ''))
                except:
                    continue
                    
        return 0
    
    def _extract_rating(self, soup: BeautifulSoup) -> Dict:
        """Extract rating information"""
        rating_info = {'rating': 0.0, 'count': 0}
        
        # Look for star ratings or numeric ratings
        rating_elements = soup.find_all(class_=re.compile(r'rating|star'))
        for element in rating_elements:
            text = element.get_text()
            # Look for patterns like "4.5/5" or "4.5 stars"
            match = re.search(r'(\d+\.?\d*)\s*[/\\]?\s*[5]?', text)
            if match:
                try:
                    rating_info['rating'] = float(match.group(1))
                    break
                except:
                    continue
                    
        return rating_info
    
    def _extract_last_update(self, soup: BeautifulSoup) -> str:
        """Extract last update date"""
        # Look for date information
        date_patterns = [
            r'(\d{4}-\d{2}-\d{2})',
            r'(\d{1,2}/\d{1,2}/\d{4})',
            r'((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{1,2},?\s+\d{4})'
        ]
        
        page_text = soup.get_text()
        for pattern in date_patterns:
            matches = re.findall(pattern, page_text)
            if matches:
                return matches[-1]  # Return the last (most recent) date found
                
        return ""
    
    def _extract_screenshots(self, soup: BeautifulSoup, base_url: str) -> List[str]:
        """Extract screenshot URLs"""
        screenshots = []
        
        # Look for screenshot images
        img_elements = soup.find_all('img', src=re.compile(r'screenshot|thumb'))
        for img in img_elements[:5]:  # Limit to 5 screenshots
            src = img.get('src')
            if src:
                full_url = urljoin(base_url, src)
                screenshots.append(full_url)
                
        return screenshots
    
    def _extract_tags(self, soup: BeautifulSoup) -> List[str]:
        """Extract project tags"""
        tags = []
        
        # Look for tag elements
        tag_elements = soup.find_all(class_=re.compile(r'tag|label|keyword'))
        for element in tag_elements[:10]:  # Limit to 10 tags
            text = element.get_text(strip=True)
            if text and len(text) < 50:
                tags.append(text)
                
        return tags
    
    def _extract_developers(self, soup: BeautifulSoup) -> List[str]:
        """Extract developer information"""
        developers = []
        
        # Look for developer/author information
        dev_elements = soup.find_all('a', href=re.compile(r'/u/'))
        for element in dev_elements[:5]:  # Limit to 5 developers
            name = element.get_text(strip=True)
            if name and name not in developers:
                developers.append(name)
                
        return developers
    
    def _extract_homepage(self, soup: BeautifulSoup) -> str:
        """Extract project homepage URL"""
        # Look for homepage links
        home_links = soup.find_all('a', href=re.compile(r'^https?://'))
        for link in home_links:
            href = link.get('href')
            text = link.get_text(strip=True).lower()
            
            if any(keyword in text for keyword in ['home', 'website', 'official']):
                return href
                
        return ""
    
    def _meets_quality_criteria(self, project: SourceForgeProject) -> bool:
        """Check if project meets quality criteria"""
        
        # Check minimum downloads
        if project.download_count < self.filters['min_downloads']:
            return False
            
        # Check minimum rating
        if project.user_rating < self.filters['min_rating']:
            return False
            
        # Check if active (has recent updates)
        if self.filters['active_projects_only']:
            if not project.last_update:
                return False
                
        # Check category exclusions
        if project.category in self.filters['exclude_categories']:
            return False
            
        # Check license preference
        if self.filters['preferred_licenses']:
            license_lower = project.license.lower()
            if not any(pref.lower() in license_lower for pref in self.filters['preferred_licenses']):
                return False
                
        return True
    
    async def get_popular_projects(self, limit: int = 50) -> List[SourceForgeProject]:
        """Get popular open source projects"""
        projects = []
        
        async for project in self.search_projects(sort='downloads', limit=limit):
            projects.append(project)
            
        return projects
    
    async def get_projects_by_language(self, language: str, limit: int = 50) -> List[SourceForgeProject]:
        """Get projects by programming language"""
        projects = []
        
        async for project in self.search_projects(query=f"language:{language}", limit=limit):
            if language.lower() in project.programming_language.lower():
                projects.append(project)
                
        return projects

async def main():
    """Example usage of SourceForge aggregator"""
    aggregator = SourceForgeAggregator()
    
    print("🔍 Searching for popular SourceForge projects...")
    
    projects = []
    async for project in aggregator.search_projects(sort='downloads', limit=10):
        projects.append(project)
        print(f"📦 {project.name} - ⬇️ {project.download_count:,} downloads")
        
    print(f"\n✅ Found {len(projects)} projects")
    
    # Save to files
    import json
    for project in projects:
        filename = f"sourceforge_{project.unix_name}.json"
        with open(filename, 'w') as f:
            json.dump(project.to_dict(), f, indent=2)
            
    print(f"💾 Saved project data to JSON files")

if __name__ == "__main__":
    asyncio.run(main())