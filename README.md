# Z-Library Educational Bot

## ⚠️ IMPORTANT LEGAL WARNING ⚠️

**This tool is for EDUCATIONAL PURPOSES ONLY.**

- ✅ **Legal Use Cases:**
  - Learning web scraping techniques
  - Accessing public domain books
  - Working with legally available content
  - Educational research and development

- ❌ **Prohibited Uses:**
  - Downloading copyrighted material without permission
  - Mass downloading for commercial purposes
  - Violating website terms of service
  - Any illegal activities

**You are responsible for ensuring your use complies with applicable laws and terms of service.**

## 📋 Features

- 🔍 **Search Functionality**: Search for books with metadata extraction
- 📊 **Metadata Collection**: Extract and save detailed book information
- 📁 **Organized Storage**: Automatic file organization and naming
- 🚦 **Rate Limiting**: Respectful request handling
- 📈 **Progress Tracking**: Visual download progress bars
- 📝 **Export Options**: CSV and JSON metadata export
- 🛡️ **Error Handling**: Robust error handling and logging

## 🚀 Installation

1. **Clone or download this project**

2. **Install dependencies:**
```bash
pip install -r requirements.txt
```

3. **Run the bot:**
```bash
python zlib_bot.py
```

## 📖 Usage

### Basic Search
```python
from zlib_bot import ZLibBot

bot = ZLibBot()
results = bot.search_books("python programming", limit=10)
```

### Search with Download
```bash
python zlib_bot.py
# Follow the interactive prompts
```

### Configuration

Edit `config.py` to customize:
- Request delays and rate limiting
- Download directories
- File formats
- Headers and user agents

## 📁 Project Structure

```
zlib_bot/
├── zlib_bot.py          # Main bot script
├── config.py            # Configuration settings
├── requirements.txt     # Python dependencies
├── README.md           # This file
├── downloads/          # Downloaded books (created automatically)
├── metadata/           # Book metadata (created automatically)
└── bot.log            # Application logs
```

## 🔧 Configuration Options

### Rate Limiting
- `REQUEST_DELAY`: Delay between requests (seconds)
- `REQUESTS_PER_MINUTE`: Maximum requests per minute
- `MAX_RETRIES`: Number of retry attempts

### File Handling
- `DOWNLOAD_DIR`: Directory for downloaded books
- `METADATA_DIR`: Directory for metadata files
- `SUPPORTED_FORMATS`: Supported file formats

## 📊 Metadata Fields

The bot extracts the following metadata:
- Title and Author
- Publication year and publisher
- ISBN and language
- File format and size
- Description and tags
- Number of pages

## 🛠️ Customization

### Adding New Sources

To adapt for legal book sources:

1. **Modify search function:**
```python
def search_books(self, query, limit=10):
    # Implement search for your legal source
    # e.g., Project Gutenberg, Open Library
    pass
```

2. **Update metadata extraction:**
```python
def extract_metadata(self, book_url):
    # Adapt for your source's HTML structure
    pass
```

### Custom Headers
```python
# In config.py
HEADERS = {
    'User-Agent': 'Your Custom User Agent',
    'Accept': 'text/html,application/xhtml+xml',
    # Add more headers as needed
}
```

## 📝 Logging

The bot creates detailed logs in `bot.log`:
- Request information
- Error messages
- Download progress
- Metadata extraction details

## 🤝 Legal Alternatives

Consider these legal book sources:
- **Project Gutenberg**: Public domain books
- **Open Library**: Internet Archive books
- **Google Books**: Limited preview access
- **Academic repositories**: University libraries
- **Government publications**: Public domain documents

## 🧪 Testing

The bot includes demo functionality for safe testing:
```bash
python zlib_bot.py
# Use demo mode to test without real downloads
```

## 🔒 Security Considerations

- Use VPN if needed for privacy
- Respect robots.txt files
- Implement proper rate limiting
- Use rotating user agents
- Handle CAPTCHA challenges appropriately

## 📄 License

MIT License - Educational Use Only

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes (ensure legal compliance)
4. Submit a pull request

## 📞 Support

For educational purposes and legal questions:
- Check your local copyright laws
- Consult legal counsel if needed
- Use only for learning and legal content

## 🔄 Version History

- **v1.0.0**: Initial educational release
  - Basic search and download functionality
  - Metadata extraction
  - Rate limiting and error handling

---

**Remember: This tool is for educational purposes. Always respect copyright laws and terms of service.**