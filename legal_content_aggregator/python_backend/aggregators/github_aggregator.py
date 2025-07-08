#!/usr/bin/env python3
"""
GitHub API Aggregator - Legal Open Source Software Collection

This module aggregates open source software information from GitHub
using their official API in compliance with their terms of service.

LEGAL COMPLIANCE:
- Uses official GitHub API
- Respects rate limits (5000 requests/hour for authenticated users)
- Only collects public repository information
- Follows GitHub's Terms of Service
"""

import asyncio
import aiohttp
import time
import logging
from typing import List, Dict, Optional, AsyncGenerator
from datetime import datetime, timedelta
from dataclasses import dataclass
from urllib.parse import urljoin

@dataclass
class GitHubRepository:
    """GitHub repository data structure"""
    id: int
    name: str
    full_name: str
    description: str
    html_url: str
    clone_url: str
    homepage: str
    language: str
    license_name: str
    license_key: str
    stargazers_count: int
    watchers_count: int
    forks_count: int
    open_issues_count: int
    size: int
    default_branch: str
    created_at: str
    updated_at: str
    pushed_at: str
    topics: List[str]
    has_downloads: bool
    archived: bool
    disabled: bool
    
    # Additional metadata
    readme_content: Optional[str] = None
    release_info: Optional[Dict] = None
    contributor_count: Optional[int] = None
    
    def to_dict(self) -> Dict:
        """Convert to dictionary for storage"""
        return {
            'id': self.id,
            'name': self.name,
            'full_name': self.full_name,
            'description': self.description,
            'html_url': self.html_url,
            'clone_url': self.clone_url,
            'homepage': self.homepage,
            'language': self.language,
            'license': {
                'name': self.license_name,
                'key': self.license_key
            },
            'stats': {
                'stars': self.stargazers_count,
                'watchers': self.watchers_count,
                'forks': self.forks_count,
                'issues': self.open_issues_count,
                'size_kb': self.size
            },
            'metadata': {
                'default_branch': self.default_branch,
                'created_at': self.created_at,
                'updated_at': self.updated_at,
                'pushed_at': self.pushed_at,
                'topics': self.topics,
                'has_downloads': self.has_downloads,
                'archived': self.archived,
                'disabled': self.disabled
            },
            'additional_info': {
                'readme_content': self.readme_content,
                'release_info': self.release_info,
                'contributor_count': self.contributor_count
            },
            'aggregation_metadata': {
                'source': 'github_api',
                'collected_at': datetime.now().isoformat(),
                'legal_status': 'open_source_public'
            }
        }

class GitHubAPIClient:
    """GitHub API client with rate limiting and error handling"""
    
    def __init__(self, token: str, rate_limit: int = 5000):
        self.token = token
        self.api_base = "https://api.github.com"
        self.rate_limit = rate_limit  # requests per hour
        self.requests_made = 0
        self.rate_reset_time = time.time() + 3600  # 1 hour from now
        
        # Headers for API requests
        self.headers = {
            'Authorization': f'token {token}',
            'Accept': 'application/vnd.github.v3+json',
            'User-Agent': 'Legal-Content-Aggregator/1.0'
        }
        
        self.logger = logging.getLogger(__name__)
        
    async def check_rate_limit(self) -> bool:
        """Check if we can make more API requests"""
        current_time = time.time()
        
        # Reset counter if hour has passed
        if current_time >= self.rate_reset_time:
            self.requests_made = 0
            self.rate_reset_time = current_time + 3600
            
        # Check if we're under the limit
        if self.requests_made >= self.rate_limit:
            wait_time = self.rate_reset_time - current_time
            self.logger.warning(f"Rate limit reached. Waiting {wait_time:.0f} seconds...")
            await asyncio.sleep(wait_time)
            self.requests_made = 0
            self.rate_reset_time = time.time() + 3600
            
        return True
        
    async def make_request(self, session: aiohttp.ClientSession, endpoint: str) -> Optional[Dict]:
        """Make authenticated API request"""
        await self.check_rate_limit()
        
        url = urljoin(self.api_base, endpoint)
        
        try:
            async with session.get(url, headers=self.headers) as response:
                self.requests_made += 1
                
                if response.status == 200:
                    data = await response.json()
                    
                    # Update rate limit info from headers
                    remaining = response.headers.get('X-RateLimit-Remaining')
                    reset_time = response.headers.get('X-RateLimit-Reset')
                    
                    if remaining:
                        self.requests_made = self.rate_limit - int(remaining)
                    if reset_time:
                        self.rate_reset_time = int(reset_time)
                        
                    return data
                    
                elif response.status == 403:
                    self.logger.error("API rate limit exceeded or token invalid")
                    return None
                    
                elif response.status == 404:
                    self.logger.warning(f"Resource not found: {endpoint}")
                    return None
                    
                else:
                    self.logger.error(f"API request failed: {response.status} - {endpoint}")
                    return None
                    
        except Exception as e:
            self.logger.error(f"Request error for {endpoint}: {e}")
            return None

class GitHubAggregator:
    """Main GitHub content aggregator"""
    
    def __init__(self, token: str, config: Dict = None):
        self.client = GitHubAPIClient(token)
        self.config = config or {}
        self.logger = logging.getLogger(__name__)
        
        # Search criteria for quality open source projects
        self.search_criteria = {
            'min_stars': self.config.get('min_stars', 100),
            'min_forks': self.config.get('min_forks', 10),
            'languages': self.config.get('languages', [
                'Python', 'JavaScript', 'Java', 'C++', 'C', 'Go', 
                'Rust', 'TypeScript', 'PHP', 'Ruby', 'Swift', 'Kotlin'
            ]),
            'licenses': self.config.get('licenses', [
                'mit', 'apache-2.0', 'gpl-3.0', 'bsd-3-clause', 
                'bsd-2-clause', 'lgpl-3.0', 'mpl-2.0'
            ]),
            'max_age_days': self.config.get('max_age_days', 365),
            'exclude_archived': True,
            'exclude_disabled': True
        }
    
    async def search_repositories(self, 
                                query: str, 
                                sort: str = 'stars',
                                order: str = 'desc',
                                per_page: int = 100,
                                max_pages: int = 10) -> AsyncGenerator[GitHubRepository, None]:
        """Search for repositories matching criteria"""
        
        async with aiohttp.ClientSession() as session:
            for page in range(1, max_pages + 1):
                endpoint = f"/search/repositories?q={query}&sort={sort}&order={order}&per_page={per_page}&page={page}"
                
                data = await self.client.make_request(session, endpoint)
                if not data or 'items' not in data:
                    break
                    
                repositories = data['items']
                if not repositories:
                    break
                    
                self.logger.info(f"Processing page {page}: {len(repositories)} repositories")
                
                for repo_data in repositories:
                    # Filter based on criteria
                    if not self._meets_criteria(repo_data):
                        continue
                        
                    # Get additional details
                    enhanced_repo = await self._enhance_repository_data(session, repo_data)
                    if enhanced_repo:
                        yield enhanced_repo
                        
                # Small delay between pages
                await asyncio.sleep(1)
    
    def _meets_criteria(self, repo_data: Dict) -> bool:
        """Check if repository meets quality criteria"""
        
        # Check stars
        if repo_data.get('stargazers_count', 0) < self.search_criteria['min_stars']:
            return False
            
        # Check forks
        if repo_data.get('forks_count', 0) < self.search_criteria['min_forks']:
            return False
            
        # Check if archived/disabled
        if self.search_criteria['exclude_archived'] and repo_data.get('archived', False):
            return False
            
        if self.search_criteria['exclude_disabled'] and repo_data.get('disabled', False):
            return False
            
        # Check license
        license_info = repo_data.get('license')
        if license_info and license_info.get('key'):
            if license_info['key'] not in self.search_criteria['licenses']:
                return False
        else:
            # No license information - might not be truly open source
            return False
            
        # Check age
        updated_at = repo_data.get('updated_at')
        if updated_at:
            try:
                updated_date = datetime.fromisoformat(updated_at.replace('Z', '+00:00'))
                age_days = (datetime.now() - updated_date.replace(tzinfo=None)).days
                if age_days > self.search_criteria['max_age_days']:
                    return False
            except:
                pass
                
        return True
    
    async def _enhance_repository_data(self, session: aiohttp.ClientSession, repo_data: Dict) -> Optional[GitHubRepository]:
        """Enhance repository data with additional information"""
        
        try:
            full_name = repo_data['full_name']
            
            # Get detailed repository info
            repo_endpoint = f"/repos/{full_name}"
            detailed_repo = await self.client.make_request(session, repo_endpoint)
            
            if not detailed_repo:
                return None
                
            # Get README content
            readme_content = await self._get_readme(session, full_name)
            
            # Get latest release info
            release_info = await self._get_latest_release(session, full_name)
            
            # Get contributor count
            contributor_count = await self._get_contributor_count(session, full_name)
            
            # Parse license information
            license_info = detailed_repo.get('license', {})
            license_name = license_info.get('name', 'Unknown') if license_info else 'Unknown'
            license_key = license_info.get('key', 'unknown') if license_info else 'unknown'
            
            return GitHubRepository(
                id=detailed_repo['id'],
                name=detailed_repo['name'],
                full_name=detailed_repo['full_name'],
                description=detailed_repo.get('description', ''),
                html_url=detailed_repo['html_url'],
                clone_url=detailed_repo['clone_url'],
                homepage=detailed_repo.get('homepage', ''),
                language=detailed_repo.get('language', ''),
                license_name=license_name,
                license_key=license_key,
                stargazers_count=detailed_repo['stargazers_count'],
                watchers_count=detailed_repo['watchers_count'],
                forks_count=detailed_repo['forks_count'],
                open_issues_count=detailed_repo['open_issues_count'],
                size=detailed_repo['size'],
                default_branch=detailed_repo['default_branch'],
                created_at=detailed_repo['created_at'],
                updated_at=detailed_repo['updated_at'],
                pushed_at=detailed_repo['pushed_at'],
                topics=detailed_repo.get('topics', []),
                has_downloads=detailed_repo.get('has_downloads', False),
                archived=detailed_repo.get('archived', False),
                disabled=detailed_repo.get('disabled', False),
                readme_content=readme_content,
                release_info=release_info,
                contributor_count=contributor_count
            )
            
        except Exception as e:
            self.logger.error(f"Error enhancing repository data: {e}")
            return None
    
    async def _get_readme(self, session: aiohttp.ClientSession, full_name: str) -> Optional[str]:
        """Get repository README content"""
        endpoint = f"/repos/{full_name}/readme"
        readme_data = await self.client.make_request(session, endpoint)
        
        if readme_data and 'content' in readme_data:
            try:
                import base64
                content = base64.b64decode(readme_data['content']).decode('utf-8')
                # Truncate if too long
                return content[:5000] if len(content) > 5000 else content
            except:
                return None
        return None
    
    async def _get_latest_release(self, session: aiohttp.ClientSession, full_name: str) -> Optional[Dict]:
        """Get latest release information"""
        endpoint = f"/repos/{full_name}/releases/latest"
        release_data = await self.client.make_request(session, endpoint)
        
        if release_data:
            return {
                'tag_name': release_data.get('tag_name'),
                'name': release_data.get('name'),
                'published_at': release_data.get('published_at'),
                'download_count': sum(asset.get('download_count', 0) for asset in release_data.get('assets', [])),
                'assets_count': len(release_data.get('assets', []))
            }
        return None
    
    async def _get_contributor_count(self, session: aiohttp.ClientSession, full_name: str) -> Optional[int]:
        """Get contributor count (first page only to respect rate limits)"""
        endpoint = f"/repos/{full_name}/contributors?per_page=1"
        contributors_data = await self.client.make_request(session, endpoint)
        
        if contributors_data and isinstance(contributors_data, list):
            # This is a rough estimate - would need to paginate for exact count
            return len(contributors_data) if len(contributors_data) < 30 else 30
        return None
    
    async def get_trending_repositories(self, 
                                     language: str = None, 
                                     since: str = 'weekly') -> List[GitHubRepository]:
        """Get trending repositories"""
        
        # Build search query for trending repos
        query_parts = ['stars:>50']  # Minimum star threshold
        
        if language:
            query_parts.append(f'language:{language}')
            
        # Date range for "trending"
        if since == 'daily':
            date_threshold = (datetime.now() - timedelta(days=1)).strftime('%Y-%m-%d')
        elif since == 'weekly':
            date_threshold = (datetime.now() - timedelta(days=7)).strftime('%Y-%m-%d')
        else:  # monthly
            date_threshold = (datetime.now() - timedelta(days=30)).strftime('%Y-%m-%d')
            
        query_parts.append(f'created:>{date_threshold}')
        
        query = ' '.join(query_parts)
        
        repositories = []
        async for repo in self.search_repositories(query, sort='stars', order='desc', max_pages=5):
            repositories.append(repo)
            
        return repositories
    
    async def get_repositories_by_topic(self, topic: str, limit: int = 100) -> List[GitHubRepository]:
        """Get repositories by topic/tag"""
        
        query = f'topic:{topic} stars:>10'
        
        repositories = []
        count = 0
        
        async for repo in self.search_repositories(query, sort='stars', order='desc'):
            repositories.append(repo)
            count += 1
            if count >= limit:
                break
                
        return repositories

# Example usage and search queries
POPULAR_SEARCH_QUERIES = {
    'web_frameworks': 'topic:web-framework stars:>1000',
    'mobile_apps': 'topic:mobile-app stars:>500',
    'data_science': 'topic:data-science OR topic:machine-learning stars:>500',
    'developer_tools': 'topic:developer-tools OR topic:cli stars:>200',
    'security_tools': 'topic:security OR topic:cybersecurity stars:>100',
    'educational': 'topic:education OR topic:tutorial stars:>50',
    'game_engines': 'topic:game-engine OR topic:gamedev stars:>100',
    'blockchain': 'topic:blockchain OR topic:cryptocurrency stars:>50'
}

async def main():
    """Example usage of GitHub aggregator"""
    import os
    
    # Get GitHub token from environment
    token = os.getenv('GITHUB_TOKEN')
    if not token:
        print("Please set GITHUB_TOKEN environment variable")
        return
        
    aggregator = GitHubAggregator(token)
    
    print("🔍 Searching for popular Python web frameworks...")
    
    repositories = []
    async for repo in aggregator.search_repositories(
        'language:python topic:web-framework stars:>1000',
        max_pages=2
    ):
        repositories.append(repo)
        print(f"📦 {repo.full_name} - ⭐ {repo.stargazers_count}")
        
    print(f"\n✅ Found {len(repositories)} repositories")
    
    # Save to files
    import json
    for repo in repositories:
        filename = f"github_{repo.name.replace('/', '_')}.json"
        with open(filename, 'w') as f:
            json.dump(repo.to_dict(), f, indent=2)
            
    print(f"💾 Saved repository data to JSON files")

if __name__ == "__main__":
    asyncio.run(main())