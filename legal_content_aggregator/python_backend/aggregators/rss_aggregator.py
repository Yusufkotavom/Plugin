#!/usr/bin/env python3
"""
RSS Feed Aggregator - Legal Content Collection

This module aggregates content from RSS feeds of legal sources including
open source news, project updates, and educational content.

LEGAL COMPLIANCE:
- Only uses publicly available RSS feeds
- Respects feed update frequencies
- Follows RSS specifications and best practices
- Proper attribution to original sources
"""

import asyncio
import aiohttp
import feedparser
import time
import logging
from typing import List, Dict, Optional, AsyncGenerator
from datetime import datetime, timedelta
from dataclasses import dataclass, field
from urllib.parse import urljoin, urlparse
import re
import hashlib

@dataclass
class RSSFeedItem:
    """RSS feed item data structure"""
    title: str
    description: str
    content: str
    link: str
    published: datetime
    author: str
    tags: List[str]
    category: str
    
    # Source information
    feed_title: str
    feed_url: str
    feed_description: str
    
    # Content analysis
    word_count: int = 0
    reading_time: int = 0  # minutes
    content_hash: str = ""
    
    # Media
    images: List[str] = field(default_factory=list)
    enclosures: List[Dict] = field(default_factory=list)
    
    def __post_init__(self):
        """Calculate derived fields"""
        # Calculate word count and reading time
        text_content = f"{self.title} {self.description} {self.content}"
        words = len(text_content.split())
        self.word_count = words
        self.reading_time = max(1, words // 200)  # Assume 200 words per minute
        
        # Generate content hash for duplicate detection
        content_for_hash = f"{self.title}{self.description}{self.link}"
        self.content_hash = hashlib.md5(content_for_hash.encode()).hexdigest()
    
    def to_dict(self) -> Dict:
        """Convert to dictionary for storage"""
        return {
            'content': {
                'title': self.title,
                'description': self.description,
                'content': self.content,
                'link': self.link,
                'published': self.published.isoformat(),
                'author': self.author,
                'tags': self.tags,
                'category': self.category
            },
            'source': {
                'feed_title': self.feed_title,
                'feed_url': self.feed_url,
                'feed_description': self.feed_description
            },
            'analysis': {
                'word_count': self.word_count,
                'reading_time': self.reading_time,
                'content_hash': self.content_hash
            },
            'media': {
                'images': self.images,
                'enclosures': self.enclosures
            },
            'aggregation_metadata': {
                'source': 'rss_feed',
                'collected_at': datetime.now().isoformat(),
                'legal_status': 'public_content'
            }
        }

class RSSFeedClient:
    """RSS feed client with caching and error handling"""
    
    def __init__(self, rate_limit: int = 60):
        self.rate_limit = rate_limit  # requests per hour
        self.requests_made = 0
        self.rate_reset_time = time.time() + 3600
        self.feed_cache = {}  # Simple feed cache
        
        self.headers = {
            'User-Agent': 'Legal-Content-Aggregator/1.0 (RSS Reader; Educational Purpose)',
            'Accept': 'application/rss+xml, application/xml, text/xml, */*',
            'Cache-Control': 'max-age=300'  # 5 minutes cache
        }
        
        self.logger = logging.getLogger(__name__)
    
    async def check_rate_limit(self):
        """Check and enforce rate limiting"""
        current_time = time.time()
        
        if current_time >= self.rate_reset_time:
            self.requests_made = 0
            self.rate_reset_time = current_time + 3600
            
        if self.requests_made >= self.rate_limit:
            wait_time = self.rate_reset_time - current_time
            self.logger.warning(f"Rate limit reached. Waiting {wait_time:.0f} seconds...")
            await asyncio.sleep(wait_time)
            self.requests_made = 0
            self.rate_reset_time = time.time() + 3600
            
        # Add small delay between requests
        await asyncio.sleep(1)
    
    async def fetch_feed(self, session: aiohttp.ClientSession, feed_url: str) -> Optional[Dict]:
        """Fetch and parse RSS feed"""
        await self.check_rate_limit()
        
        # Check cache first
        cache_key = feed_url
        if cache_key in self.feed_cache:
            cached_time, cached_data = self.feed_cache[cache_key]
            # Use cache if less than 30 minutes old
            if time.time() - cached_time < 1800:
                self.logger.info(f"📋 Using cached feed: {feed_url}")
                return cached_data
        
        try:
            async with session.get(feed_url, headers=self.headers) as response:
                self.requests_made += 1
                
                if response.status == 200:
                    content = await response.text()
                    
                    # Parse with feedparser
                    feed = feedparser.parse(content)
                    
                    if feed.bozo:
                        self.logger.warning(f"⚠️  Feed parsing issues: {feed_url}")
                        if hasattr(feed, 'bozo_exception'):
                            self.logger.warning(f"Exception: {feed.bozo_exception}")
                    
                    # Cache the result
                    self.feed_cache[cache_key] = (time.time(), feed)
                    
                    self.logger.info(f"✅ Successfully fetched feed: {feed_url}")
                    return feed
                    
                elif response.status == 304:
                    self.logger.info(f"📄 Feed not modified: {feed_url}")
                    return None
                    
                else:
                    self.logger.error(f"❌ HTTP {response.status}: {feed_url}")
                    return None
                    
        except Exception as e:
            self.logger.error(f"❌ Error fetching feed {feed_url}: {e}")
            return None

class RSSAggregator:
    """Main RSS aggregator"""
    
    def __init__(self, config: Dict = None):
        self.client = RSSFeedClient()
        self.config = config or {}
        self.logger = logging.getLogger(__name__)
        
        # Default RSS feeds for legal/open source content
        self.default_feeds = {
            'opensource_news': [
                'https://opensource.com/feed',
                'https://www.linuxfoundation.org/feed/',
                'https://www.fsf.org/static/fsforg/rss/news.xml',
                'https://blog.mozilla.org/feed/',
                'https://github.blog/feed/',
                'https://sourceforge.net/blog/feed/',
            ],
            'development_tools': [
                'https://blog.jetbrains.com/feed/',
                'https://code.visualstudio.com/feed.xml',
                'https://www.eclipse.org/home/rss-all.xml',
            ],
            'programming_news': [
                'https://dev.to/feed',
                'https://stackoverflow.blog/feed/',
                'https://www.reddit.com/r/programming/.rss',
            ],
            'security_news': [
                'https://www.owasp.org/feed.xml',
                'https://blog.cloudflare.com/rss/',
                'https://krebsonsecurity.com/feed/',
            ],
            'educational': [
                'https://www.khanacademy.org/feed',
                'https://ocw.mit.edu/rss/all/all_ocw.xml',
                'https://www.coursera.org/feed',
            ]
        }
        
        # Content filters
        self.filters = {
            'min_word_count': self.config.get('min_word_count', 50),
            'max_age_days': self.config.get('max_age_days', 30),
            'exclude_keywords': self.config.get('exclude_keywords', [
                'advertisement', 'sponsored', 'paid promotion'
            ]),
            'include_keywords': self.config.get('include_keywords', [
                'open source', 'free software', 'tutorial', 'guide', 
                'programming', 'development', 'code', 'github'
            ])
        }
    
    async def get_all_feeds(self) -> Dict[str, List[str]]:
        """Get all configured feed URLs"""
        feeds = self.default_feeds.copy()
        
        # Add custom feeds from config
        if 'custom_feeds' in self.config:
            feeds.update(self.config['custom_feeds'])
            
        return feeds
    
    async def aggregate_feeds(self, 
                            categories: List[str] = None,
                            max_items_per_feed: int = 50,
                            max_age_hours: int = 24) -> AsyncGenerator[RSSFeedItem, None]:
        """Aggregate items from RSS feeds"""
        
        feeds = await self.get_all_feeds()
        
        # Filter categories if specified
        if categories:
            feeds = {k: v for k, v in feeds.items() if k in categories}
        
        async with aiohttp.ClientSession() as session:
            for category, feed_urls in feeds.items():
                self.logger.info(f"🔍 Processing {category} feeds...")
                
                for feed_url in feed_urls:
                    try:
                        feed_data = await self.client.fetch_feed(session, feed_url)
                        if not feed_data:
                            continue
                            
                        # Process feed items
                        async for item in self._process_feed_items(
                            feed_data, category, max_items_per_feed, max_age_hours
                        ):
                            yield item
                            
                    except Exception as e:
                        self.logger.error(f"Error processing feed {feed_url}: {e}")
                        continue
    
    async def _process_feed_items(self, 
                                feed_data: Dict,
                                category: str,
                                max_items: int,
                                max_age_hours: int) -> AsyncGenerator[RSSFeedItem, None]:
        """Process items from a single feed"""
        
        feed_info = feed_data.get('feed', {})
        feed_title = feed_info.get('title', 'Unknown Feed')
        feed_url = feed_info.get('link', '')
        feed_description = feed_info.get('description', '')
        
        items = feed_data.get('entries', [])
        cutoff_time = datetime.now() - timedelta(hours=max_age_hours)
        
        processed_count = 0
        
        for entry in items:
            if processed_count >= max_items:
                break
                
            try:
                # Parse publication date
                published = self._parse_date(entry.get('published_parsed'))
                if not published or published < cutoff_time:
                    continue
                
                # Extract content
                title = entry.get('title', '')
                description = self._extract_description(entry)
                content = self._extract_content(entry)
                link = entry.get('link', '')
                author = self._extract_author(entry)
                tags = self._extract_tags(entry)
                
                # Content filtering
                if not self._passes_content_filter(title, description, content):
                    continue
                
                # Extract media
                images = self._extract_images(entry)
                enclosures = self._extract_enclosures(entry)
                
                item = RSSFeedItem(
                    title=title,
                    description=description,
                    content=content,
                    link=link,
                    published=published,
                    author=author,
                    tags=tags,
                    category=category,
                    feed_title=feed_title,
                    feed_url=feed_url,
                    feed_description=feed_description,
                    images=images,
                    enclosures=enclosures
                )
                
                yield item
                processed_count += 1
                
            except Exception as e:
                self.logger.error(f"Error processing feed item: {e}")
                continue
    
    def _parse_date(self, date_tuple) -> Optional[datetime]:
        """Parse date from feedparser tuple"""
        if not date_tuple:
            return None
            
        try:
            return datetime(*date_tuple[:6])
        except:
            return None
    
    def _extract_description(self, entry: Dict) -> str:
        """Extract description from entry"""
        # Try different fields
        for field in ['summary', 'description', 'subtitle']:
            if field in entry:
                desc = entry[field]
                if isinstance(desc, dict):
                    return desc.get('value', '')
                return str(desc)
                
        return ""
    
    def _extract_content(self, entry: Dict) -> str:
        """Extract full content from entry"""
        # Try content field first
        if 'content' in entry:
            content_list = entry['content']
            if content_list:
                content_item = content_list[0]
                if isinstance(content_item, dict):
                    return content_item.get('value', '')
                return str(content_item)
        
        # Fall back to description
        return self._extract_description(entry)
    
    def _extract_author(self, entry: Dict) -> str:
        """Extract author from entry"""
        # Try different author fields
        if 'author' in entry:
            return entry['author']
        if 'author_detail' in entry:
            detail = entry['author_detail']
            return detail.get('name', detail.get('email', ''))
        if 'dc_creator' in entry:
            return entry['dc_creator']
            
        return ""
    
    def _extract_tags(self, entry: Dict) -> List[str]:
        """Extract tags from entry"""
        tags = []
        
        # RSS categories/tags
        if 'tags' in entry:
            for tag in entry['tags']:
                if isinstance(tag, dict):
                    term = tag.get('term', '')
                    if term:
                        tags.append(term)
                else:
                    tags.append(str(tag))
        
        # Alternative tag fields
        for field in ['category', 'categories', 'keywords']:
            if field in entry:
                value = entry[field]
                if isinstance(value, list):
                    tags.extend([str(v) for v in value])
                elif isinstance(value, str):
                    tags.append(value)
        
        # Clean and deduplicate tags
        cleaned_tags = []
        for tag in tags:
            tag = tag.strip().lower()
            if tag and len(tag) > 1 and tag not in cleaned_tags:
                cleaned_tags.append(tag)
                
        return cleaned_tags[:10]  # Limit to 10 tags
    
    def _extract_images(self, entry: Dict) -> List[str]:
        """Extract image URLs from entry"""
        images = []
        
        # Look in content for images
        content = self._extract_content(entry)
        if content:
            import re
            img_urls = re.findall(r'<img[^>]+src=["\']([^"\']+)["\']', content)
            images.extend(img_urls)
        
        # Look for media content
        if 'media_content' in entry:
            for media in entry['media_content']:
                if media.get('type', '').startswith('image/'):
                    images.append(media.get('url', ''))
        
        # Limit and validate URLs
        valid_images = []
        for img_url in images[:5]:  # Max 5 images
            if img_url.startswith(('http://', 'https://')):
                valid_images.append(img_url)
                
        return valid_images
    
    def _extract_enclosures(self, entry: Dict) -> List[Dict]:
        """Extract enclosures (audio/video) from entry"""
        enclosures = []
        
        if 'enclosures' in entry:
            for enclosure in entry['enclosures'][:3]:  # Max 3 enclosures
                enclosure_info = {
                    'url': enclosure.get('href', ''),
                    'type': enclosure.get('type', ''),
                    'length': enclosure.get('length', 0)
                }
                if enclosure_info['url']:
                    enclosures.append(enclosure_info)
                    
        return enclosures
    
    def _passes_content_filter(self, title: str, description: str, content: str) -> bool:
        """Check if content passes quality filters"""
        
        # Combine all text for analysis
        full_text = f"{title} {description} {content}".lower()
        
        # Check minimum word count
        word_count = len(full_text.split())
        if word_count < self.filters['min_word_count']:
            return False
        
        # Check for excluded keywords
        for keyword in self.filters['exclude_keywords']:
            if keyword.lower() in full_text:
                return False
        
        # Check for included keywords (if specified)
        if self.filters['include_keywords']:
            has_included = any(
                keyword.lower() in full_text 
                for keyword in self.filters['include_keywords']
            )
            if not has_included:
                return False
        
        return True
    
    async def get_trending_topics(self, hours: int = 24, min_mentions: int = 3) -> List[Dict]:
        """Analyze trending topics from recent feed items"""
        
        # Collect recent items
        items = []
        async for item in self.aggregate_feeds(max_age_hours=hours):
            items.append(item)
            
        # Count tag frequencies
        tag_counts = {}
        for item in items:
            for tag in item.tags:
                tag_counts[tag] = tag_counts.get(tag, 0) + 1
        
        # Filter and sort trending topics
        trending = [
            {'topic': tag, 'mentions': count, 'trend_score': count}
            for tag, count in tag_counts.items()
            if count >= min_mentions
        ]
        
        return sorted(trending, key=lambda x: x['trend_score'], reverse=True)[:20]
    
    async def search_feeds(self, query: str, max_results: int = 100) -> List[RSSFeedItem]:
        """Search for items matching query"""
        
        query_lower = query.lower()
        results = []
        
        async for item in self.aggregate_feeds():
            # Search in title, description, and content
            searchable_text = f"{item.title} {item.description} {item.content}".lower()
            
            if query_lower in searchable_text:
                results.append(item)
                
                if len(results) >= max_results:
                    break
        
        return results
    
    async def get_feed_statistics(self) -> Dict:
        """Get statistics about aggregated feeds"""
        
        feeds = await self.get_all_feeds()
        total_feeds = sum(len(urls) for urls in feeds.values())
        
        # Collect sample of recent items
        items = []
        async for item in self.aggregate_feeds(max_age_hours=24):
            items.append(item)
            if len(items) >= 1000:  # Sample limit
                break
        
        # Calculate statistics
        if items:
            avg_reading_time = sum(item.reading_time for item in items) / len(items)
            avg_word_count = sum(item.word_count for item in items) / len(items)
            
            # Category distribution
            category_counts = {}
            for item in items:
                category_counts[item.category] = category_counts.get(item.category, 0) + 1
        else:
            avg_reading_time = 0
            avg_word_count = 0
            category_counts = {}
        
        return {
            'total_feeds': total_feeds,
            'feed_categories': len(feeds),
            'recent_items': len(items),
            'average_reading_time': round(avg_reading_time, 1),
            'average_word_count': round(avg_word_count),
            'category_distribution': category_counts,
            'last_updated': datetime.now().isoformat()
        }

async def main():
    """Example usage of RSS aggregator"""
    aggregator = RSSAggregator()
    
    print("📡 Starting RSS feed aggregation...")
    
    # Get recent items from all feeds
    items = []
    async for item in aggregator.aggregate_feeds(max_age_hours=24):
        items.append(item)
        print(f"📰 {item.title[:60]}... ({item.feed_title})")
        
        if len(items) >= 20:  # Limit for demo
            break
    
    print(f"\n✅ Found {len(items)} recent items")
    
    # Get trending topics
    print("\n🔥 Trending topics:")
    trending = await aggregator.get_trending_topics()
    for topic in trending[:10]:
        print(f"  #{topic['topic']} ({topic['mentions']} mentions)")
    
    # Get statistics
    stats = await aggregator.get_feed_statistics()
    print(f"\n📊 Feed Statistics:")
    print(f"  Total feeds: {stats['total_feeds']}")
    print(f"  Categories: {stats['feed_categories']}")
    print(f"  Recent items: {stats['recent_items']}")
    print(f"  Avg reading time: {stats['average_reading_time']} min")
    
    # Save sample data
    import json
    if items:
        with open('rss_sample_items.json', 'w') as f:
            json.dump([item.to_dict() for item in items[:5]], f, indent=2)
        print(f"\n💾 Saved sample data to rss_sample_items.json")

if __name__ == "__main__":
    asyncio.run(main())