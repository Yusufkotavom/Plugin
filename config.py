import os
from dotenv import load_dotenv

load_dotenv()

class Config:
    # Basic settings
    USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
    REQUEST_DELAY = 2  # Delay between requests in seconds
    MAX_RETRIES = 3
    TIMEOUT = 30
    
    # Download settings
    DOWNLOAD_DIR = "downloads"
    METADATA_DIR = "metadata"
    
    # Rate limiting
    REQUESTS_PER_MINUTE = 30
    
    # Headers
    HEADERS = {
        'User-Agent': USER_AGENT,
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language': 'en-US,en;q=0.5',
        'Accept-Encoding': 'gzip, deflate',
        'Connection': 'keep-alive',
        'Upgrade-Insecure-Requests': '1',
    }
    
    # File formats to handle
    SUPPORTED_FORMATS = ['pdf', 'epub', 'mobi', 'azw3', 'txt', 'djvu']
    
    # Logging
    LOG_LEVEL = "INFO"
    LOG_FILE = "bot.log"