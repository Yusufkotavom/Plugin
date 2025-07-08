# Legal Content Aggregation System - Project Structure

## 📁 Project Overview

```
legal_content_aggregator/
├── 🐍 python_backend/
│   ├── aggregators/
│   │   ├── __init__.py
│   │   ├── github_aggregator.py      # GitHub API integration
│   │   ├── sourceforge_aggregator.py # SourceForge scraper
│   │   ├── appstore_aggregator.py    # App store APIs
│   │   ├── rss_aggregator.py         # RSS feed processor
│   │   └── education_aggregator.py   # Educational resources
│   ├── core/
│   │   ├── __init__.py
│   │   ├── database.py               # Database management
│   │   ├── api.py                    # REST API endpoints
│   │   ├── scheduler.py              # Task scheduling
│   │   └── config.py                 # Configuration
│   ├── wordpress/
│   │   ├── __init__.py
│   │   ├── wp_client.py             # WordPress API client
│   │   └── post_formatter.py        # Content formatting
│   ├── requirements.txt
│   └── main.py
│
├── 🔌 wordpress_plugin/
│   ├── legal-software-directory/
│   │   ├── legal-software-directory.php
│   │   ├── includes/
│   │   │   ├── class-admin.php
│   │   │   ├── class-api.php
│   │   │   ├── class-cron.php
│   │   │   └── class-database.php
│   │   ├── templates/
│   │   │   ├── software-listing.php
│   │   │   ├── category-view.php
│   │   │   └── single-software.php
│   │   ├── assets/
│   │   │   ├── css/
│   │   │   ├── js/
│   │   │   └── images/
│   │   └── readme.txt
│
├── 🌐 web_dashboard/
│   ├── index.html
│   ├── css/
│   ├── js/
│   └── api/
│
├── 📊 database/
│   ├── schema.sql
│   └── migrations/
│
├── 📋 config/
│   ├── settings.json
│   ├── sources.yaml
│   └── .env.example
│
└── 📚 docs/
    ├── README.md
    ├── API_DOCS.md
    ├── INSTALLATION.md
    └── LEGAL_GUIDELINES.md
```

## 🎯 System Components

### 1. Python Backend Engine
- **Multi-source aggregation**
- **REST API server**
- **Database management**
- **Task scheduling**
- **WordPress integration**

### 2. WordPress Plugin
- **Admin dashboard**
- **Content management**
- **Public display**
- **SEO optimization**
- **User ratings**

### 3. Web Dashboard
- **Monitoring interface**
- **Configuration panel**
- **Analytics**
- **Manual curation tools**

### 4. Database Layer
- **PostgreSQL/MySQL support**
- **Content storage**
- **Metadata indexing**
- **User management**

## 🔄 Data Flow

```
External Sources → Python Aggregators → Database → WordPress Plugin → Public Website
     ↓                    ↓                ↓              ↓              ↓
GitHub API          Content Parser    PostgreSQL    Admin Panel    User Interface
SourceForge         Duplicate Check   Elasticsearch  Curation      Search & Browse
RSS Feeds          Content Filter     Redis Cache    Publishing    Downloads
App Stores         WordPress API      Backup System  Analytics     Reviews
```

## 🛡️ Legal Compliance

- ✅ **Only official sources**
- ✅ **Proper attribution** 
- ✅ **Respect rate limits**
- ✅ **Terms of service compliance**
- ✅ **DMCA compliance**
- ✅ **Open source licensing**