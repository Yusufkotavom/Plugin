#!/usr/bin/env python3
"""
Legal Content Aggregator - Core Configuration

This configuration module manages all settings for the legal content
aggregation system, ensuring compliance with terms of service and
respecting rate limits for all sources.
"""

import os
import json
from typing import Dict, List, Optional
from dataclasses import dataclass
from pathlib import Path
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

@dataclass
class DatabaseConfig:
    """Database configuration settings"""
    host: str = os.getenv("DB_HOST", "localhost")
    port: int = int(os.getenv("DB_PORT", "5432"))
    name: str = os.getenv("DB_NAME", "legal_aggregator")
    user: str = os.getenv("DB_USER", "postgres")
    password: str = os.getenv("DB_PASSWORD", "")
    
    @property
    def url(self) -> str:
        return f"postgresql://{self.user}:{self.password}@{self.host}:{self.port}/{self.name}"

@dataclass
class RedisConfig:
    """Redis configuration for caching"""
    host: str = os.getenv("REDIS_HOST", "localhost")
    port: int = int(os.getenv("REDIS_PORT", "6379"))
    db: int = int(os.getenv("REDIS_DB", "0"))
    password: str = os.getenv("REDIS_PASSWORD", "")

@dataclass
class WordPressConfig:
    """WordPress API configuration"""
    url: str = os.getenv("WP_URL", "")
    username: str = os.getenv("WP_USERNAME", "")
    password: str = os.getenv("WP_PASSWORD", "")
    api_base: str = "/wp-json/wp/v2/"

@dataclass
class GitHubConfig:
    """GitHub API configuration"""
    token: str = os.getenv("GITHUB_TOKEN", "")
    rate_limit: int = 5000  # requests per hour
    delay: float = 1.0  # seconds between requests
    
@dataclass
class SourceForgeConfig:
    """SourceForge scraping configuration"""
    base_url: str = "https://sourceforge.net"
    rate_limit: int = 100  # requests per hour
    delay: float = 36.0  # seconds between requests (conservative)
    user_agent: str = "Legal-Content-Aggregator/1.0 (Educational Purpose)"

@dataclass
class RSSConfig:
    """RSS feed configuration"""
    sources: List[str] = None
    update_interval: int = 3600  # seconds (1 hour)
    max_items_per_feed: int = 50
    
    def __post_init__(self):
        if self.sources is None:
            self.sources = [
                "https://sourceforge.net/blog/feed/",
                "https://github.blog/feed/",
                "https://opensource.com/feed",
                "https://www.fosshub.com/rss.xml"
            ]

@dataclass
class AppStoreConfig:
    """App store API configurations"""
    # F-Droid (Android FOSS)
    fdroid_api: str = "https://f-droid.org/api/v1/"
    
    # Flathub (Linux)
    flathub_api: str = "https://flathub.org/api/v1/"
    
    # Chocolatey (Windows)
    chocolatey_api: str = "https://community.chocolatey.org/api/v2/"
    
    # Homebrew (macOS)
    homebrew_api: str = "https://formulae.brew.sh/api/"

class Config:
    """Main configuration class"""
    
    def __init__(self):
        # Core settings
        self.debug = os.getenv("DEBUG", "False").lower() == "true"
        self.secret_key = os.getenv("SECRET_KEY", "dev-secret-key-change-in-production")
        self.allowed_hosts = os.getenv("ALLOWED_HOSTS", "localhost,127.0.0.1").split(",")
        
        # Component configurations
        self.database = DatabaseConfig()
        self.redis = RedisConfig()
        self.wordpress = WordPressConfig()
        self.github = GitHubConfig()
        self.sourceforge = SourceForgeConfig()
        self.rss = RSSConfig()
        self.appstore = AppStoreConfig()
        
        # Paths
        self.base_dir = Path(__file__).parent.parent
        self.logs_dir = self.base_dir / "logs"
        self.temp_dir = self.base_dir / "temp"
        self.config_dir = self.base_dir.parent / "config"
        
        # Create directories
        self.logs_dir.mkdir(exist_ok=True)
        self.temp_dir.mkdir(exist_ok=True)
        
        # Aggregation settings
        self.aggregation_settings = {
            "max_concurrent_requests": 5,
            "request_timeout": 30,
            "retry_attempts": 3,
            "retry_delay": 5,
            "min_content_length": 100,
            "max_content_age_days": 30,
            "duplicate_threshold": 0.85,  # similarity threshold for duplicate detection
        }
        
        # Content filtering
        self.content_filters = {
            "min_rating": 3.0,
            "required_licenses": ["MIT", "GPL", "Apache", "BSD", "CC", "Public Domain"],
            "blocked_keywords": ["crack", "keygen", "serial", "pirate", "warez"],
            "required_keywords": ["free", "open source", "freeware", "libre"],
        }
        
        # Rate limiting (requests per hour)
        self.rate_limits = {
            "github": 5000,
            "sourceforge": 100,
            "fdroid": 1000,
            "flathub": 500,
            "chocolatey": 200,
            "homebrew": 1000,
            "rss_feeds": 50,
        }
        
        # Load custom settings if available
        self._load_custom_settings()
    
    def _load_custom_settings(self):
        """Load custom settings from config file"""
        config_file = self.config_dir / "settings.json"
        if config_file.exists():
            try:
                with open(config_file, 'r') as f:
                    custom_settings = json.load(f)
                    
                # Update aggregation settings
                if "aggregation" in custom_settings:
                    self.aggregation_settings.update(custom_settings["aggregation"])
                    
                # Update content filters
                if "content_filters" in custom_settings:
                    self.content_filters.update(custom_settings["content_filters"])
                    
                # Update rate limits
                if "rate_limits" in custom_settings:
                    self.rate_limits.update(custom_settings["rate_limits"])
                    
            except Exception as e:
                print(f"Warning: Could not load custom settings: {e}")
    
    def get_user_agent(self, service: str = "default") -> str:
        """Get appropriate user agent for different services"""
        base_ua = "Legal-Content-Aggregator/1.0 (Educational Purpose; +https://example.com/contact)"
        
        service_specific = {
            "github": f"Legal-Content-Aggregator-GitHub/1.0",
            "sourceforge": f"Legal-Content-Aggregator-SourceForge/1.0",
            "rss": f"Legal-Content-Aggregator-RSS/1.0",
        }
        
        return service_specific.get(service, base_ua)
    
    def is_legal_source(self, url: str) -> bool:
        """Check if a URL is from a legal/official source"""
        legal_domains = [
            "github.com",
            "sourceforge.net", 
            "f-droid.org",
            "flathub.org",
            "chocolatey.org",
            "brew.sh",
            "formulae.brew.sh",
            "opensource.org",
            "fsf.org",
            "apache.org",
            "mozilla.org",
            "gnu.org",
            "mit.edu",
            "archive.org",
            "gutenberg.org",
            "khanacademy.org",
            "coursera.org",
            "edx.org",
        ]
        
        from urllib.parse import urlparse
        domain = urlparse(url).netloc.lower()
        
        return any(legal_domain in domain for legal_domain in legal_domains)
    
    def get_rate_limit(self, service: str) -> int:
        """Get rate limit for a specific service"""
        return self.rate_limits.get(service, 100)  # Default to 100 requests/hour
    
    def validate_config(self) -> List[str]:
        """Validate configuration and return list of issues"""
        issues = []
        
        # Check required API tokens
        if not self.github.token:
            issues.append("GitHub token is required for API access")
            
        if not self.wordpress.url:
            issues.append("WordPress URL is required for content publishing")
            
        # Check database connection
        if not all([self.database.host, self.database.name, self.database.user]):
            issues.append("Database configuration is incomplete")
            
        # Check directories
        required_dirs = [self.logs_dir, self.temp_dir]
        for directory in required_dirs:
            if not directory.exists():
                try:
                    directory.mkdir(parents=True, exist_ok=True)
                except Exception as e:
                    issues.append(f"Cannot create directory {directory}: {e}")
        
        return issues

# Global config instance
config = Config()

# Export commonly used settings
DATABASE_URL = config.database.url
REDIS_URL = f"redis://{config.redis.host}:{config.redis.port}/{config.redis.db}"
DEBUG = config.debug
SECRET_KEY = config.secret_key