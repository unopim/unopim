# UnoPim - Application Features Guide

> A comprehensive guide to UnoPim's features for business users, product managers, and decision-makers.

---

## What is UnoPim?

UnoPim is an open-source **Product Information Management (PIM)** system that provides your business with a single, centralized place to organize, enrich, and distribute product information across all your sales channels, languages, and currencies.

Whether you sell through eCommerce platforms, print catalogs, marketplaces, or retail systems, UnoPim ensures your product data is consistent, complete, and always up to date.

---

## 1. Product Management

### Simple Products
Create and manage individual products with detailed information. Each product has a unique SKU and can hold unlimited attributes across multiple languages and channels.

**What you can do:**
- Create products with any number of attributes (name, description, price, dimensions, materials, etc.)
- Assign products to multiple categories simultaneously
- Set product status (active/inactive) to control visibility
- Upload product images and media files
- Copy existing products to quickly create similar items

### Configurable Products
Manage products that come in multiple variants (e.g., a T-shirt available in different sizes and colors). A single configurable product groups its variants together while sharing common information.

**What you can do:**
- Define which attributes create variants (e.g., Size, Color)
- Manage variant-specific values (e.g., different images per color)
- See all variants in one place under the parent product
- Bulk update shared information across all variants

### Product Associations
Link products to each other for merchandising purposes:

| Association Type | Purpose | Example |
|-----------------|---------|---------|
| **Related Products** | Show similar items | "You might also like..." |
| **Up-Sells** | Suggest premium alternatives | "Consider upgrading to..." |
| **Cross-Sells** | Recommend complementary items | "Frequently bought together..." |

### Product Completeness
Track how complete your product information is. UnoPim scores each product based on how many required fields are filled in, broken down by channel and language.

**What you can do:**
- See a completeness percentage for each product per channel and locale
- Identify products missing critical information
- Filter and sort products by completeness to prioritize enrichment work
- Configure which attributes count toward completeness per channel

### Product Bulk Editing
Edit multiple products at once without opening each product individually.

**What you can do:**
- Select multiple products from the product grid
- Update shared attributes in bulk (e.g., set status, change a category)
- Save time when making mass changes to your catalog

---

## 2. Flexible Attribute System

### 12 Attribute Types
UnoPim supports a wide range of attribute types to describe your products precisely:

| Attribute Type | Use Case | Example |
|---------------|----------|---------|
| **Text** | Short text values | Brand name, SKU |
| **Textarea** | Long descriptions | Product description, care instructions |
| **Boolean** | Yes/No values | "Is recyclable?", "In stock?" |
| **Price** | Currency amounts | Retail price, wholesale price |
| **Select** | Single choice from list | Color, Material |
| **Multi-Select** | Multiple choices from list | Certifications, Features |
| **Date** | Calendar dates | Release date, Expiry date |
| **Date & Time** | Dates with time | Launch timestamp |
| **Checkbox** | Toggleable options | Feature flags |
| **File** | Document uploads | Spec sheets, manuals (PDF, etc.) |
| **Image** | Image uploads | Product photos, diagrams |
| **Gallery** | Multiple images & videos | Product photo galleries with video support |

### Attribute Scoping
Each attribute can be configured to hold different values depending on context:

| Scope | Meaning | Example |
|-------|---------|---------|
| **Global** | Same value everywhere | SKU, weight, dimensions |
| **Per Language** | Different value per language | Product name, description |
| **Per Channel** | Different value per sales channel | Channel-specific price |
| **Per Channel + Language** | Different value per channel and language | Channel-specific description in each language |

### Attribute Families
Group related attributes into families. When you create a product, you assign it to a family, which determines which attributes are available.

**Example families:**
- "Electronics" family: includes voltage, wattage, connectivity, battery life
- "Clothing" family: includes size, color, material, care instructions
- "Food & Beverage" family: includes ingredients, nutrition facts, allergens

### Attribute Groups
Within a family, organize attributes into logical groups for a clean editing experience:
- General Information (name, description, status)
- Technical Specifications (weight, dimensions)
- Marketing Content (meta title, meta description)
- Media (images, videos, documents)

### Swatches
For visual attributes like Color, display color swatches or image swatches instead of plain text labels. Customers can see the actual color or material texture at a glance.

---

## 3. Category Management

### Hierarchical Category Tree
Organize your products into a multi-level category structure with unlimited nesting depth.

**Example:**
```
Electronics
  +-- Computers
  |   +-- Laptops
  |   +-- Desktops
  |   +-- Accessories
  +-- Mobile Phones
  |   +-- Smartphones
  |   +-- Feature Phones
  +-- Audio
      +-- Headphones
      +-- Speakers
```

**What you can do:**
- Create categories with unique codes
- Drag and rearrange the category tree
- Assign products to one or more categories
- Search and filter categories
- Mass delete categories

### Custom Category Fields
Add custom information to your categories beyond just a name. Define category-specific fields like:
- Category description (per language)
- Category banner image
- SEO meta title and description
- Custom sorting options

---

## 4. Multi-Language Support (Localization)

### 33 Built-in Languages
UnoPim supports 33 languages out of the box, including:

Arabic, Catalan, Danish, German, English (AU/GB/NZ/US), Spanish, Finnish, French, Hindi, Croatian, Indonesian, Italian, Japanese, Korean, Mongolian, Dutch, Norwegian, Polish, Portuguese (BR/PT), Romanian, Russian, Swedish, Filipino, Turkish, Ukrainian, Vietnamese, Chinese (Simplified/Traditional)

**What you can do:**
- Maintain product names, descriptions, and marketing content in every language your business operates in
- Each translator works only on their language without affecting others
- The admin interface itself supports multiple UI languages so team members can work in their preferred language

### AI-Powered Translation
Use the built-in Magic AI feature to automatically translate product content from one language to others, saving hours of manual translation work.

---

## 5. Multi-Channel Distribution

### What are Channels?
A channel represents a destination where your product data is sent - your eCommerce store, a marketplace (Amazon, eBay), a print catalog, a mobile app, or a retail POS system.

**What you can do:**
- Create multiple channels, each with its own set of languages and currencies
- Maintain channel-specific product information (e.g., different descriptions for your website vs. Amazon)
- Assign a root category tree to each channel
- Control which products appear in which channels

### Channel-Specific Content
Different channels often need different product data:
- Your website might need long, SEO-optimized descriptions
- Amazon might need bullet-point features in a specific format
- Your print catalog needs concise, formatted text

UnoPim lets you maintain all of these from one central place.

---

## 6. Multi-Currency Support

Manage product pricing in multiple currencies. Each channel can support different currencies, and price attributes automatically accommodate all configured currencies.

**Supported features:**
- Define active currencies with proper symbols and decimal places
- Assign currencies to specific channels
- Maintain separate prices per currency (no automatic conversion - you control exact pricing)

---

## 7. Data Import & Export

### Import
Bring product and category data into UnoPim from external sources:

| Feature | Description |
|---------|-------------|
| **File Formats** | CSV and Excel (XLSX) |
| **Supported Entities** | Products, Categories |
| **Validation** | Automatic data validation before import |
| **Error Reporting** | Downloadable error reports showing exactly which rows failed and why |
| **Batch Processing** | Large files are processed in batches via background jobs |
| **Sample Files** | Download sample CSV templates for each entity type |
| **Configurable Options** | Field separator, validation strategy, allowed error count |

### Export
Send your product and category data to external systems:

| Feature | Description |
|---------|-------------|
| **File Formats** | CSV and Excel (XLSX) |
| **Supported Entities** | Products, Categories |
| **Quick Export** | Export directly from the product grid (selected products or all) |
| **With Media** | Option to include product images in the export |
| **Job Tracking** | Monitor export progress in real-time |
| **Filters** | Export specific subsets of data using filters |

### Job Tracker
Every import and export operation is tracked as a job with:
- Real-time progress monitoring (percentage complete)
- Success/failure status
- Error counts and downloadable error reports
- Job history for auditing

---

## 8. AI-Powered Content Generation (Magic AI)

UnoPim integrates with leading AI providers to help you create and enhance product content faster.

### Supported AI Providers
| Provider | Description |
|----------|-------------|
| **OpenAI** (GPT) | Industry-leading language model |
| **Groq** | High-speed inference |
| **Google Gemini** | Google's AI model |
| **Ollama** | Self-hosted, privacy-first AI |

### What Magic AI Can Do
- **Generate product descriptions** from basic attributes (name, category, key features)
- **Translate content** across multiple languages with one click
- **Write marketing copy** tailored to different channels
- **Create SEO content** (meta titles, meta descriptions)
- **Custom prompts** - define your own AI instructions for specific use cases

### System Prompt Management
Administrators can configure:
- Default AI behavior and tone of voice
- Maximum token limits per generation
- Custom prompt templates for different content types

---

## 9. RESTful API for Integration

### API Overview
UnoPim provides a comprehensive REST API for integrating with external systems (eCommerce platforms, ERPs, marketplaces, custom applications).

| API Area | Available Operations |
|----------|---------------------|
| **Products** | List, Get, Create, Update, Patch, Delete |
| **Categories** | List, Get, Create, Update, Patch, Delete |
| **Attributes** | List, Get, Create, Update + Options management |
| **Attribute Families** | List, Get, Create, Update |
| **Attribute Groups** | List, Get, Create, Update |
| **Category Fields** | List, Get, Create, Update |
| **Channels** | List, Get |
| **Locales** | List, Get |
| **Currencies** | List, Get |
| **Media Files** | Upload (product images, category images, swatches) |
| **Configurable Products** | List, Get, Create, Update, Patch |

### Authentication
The API uses industry-standard **OAuth2** authentication:
1. Create an API key in the admin panel
2. Obtain an access token using your credentials
3. Include the token in all API requests
4. Tokens auto-expire for security (configurable duration)

### API Permissions
Each API key can be configured with:
- **Full access** - can do everything
- **Custom permissions** - granular control over which endpoints the key can access

---

## 10. Webhooks

UnoPim can notify external systems in real-time when product data changes.

**Supported events:**
- Product created
- Product updated
- Product bulk imported

**Use case:** When a product is updated in UnoPim, a webhook instantly notifies your eCommerce platform to refresh its product page - no polling needed.

---

## 11. User Management & Access Control

### Role-Based Access Control (RBAC)
Control exactly what each team member can see and do with **80+ granular permissions**.

**Permission areas:**
| Area | Permissions Available |
|------|----------------------|
| **Dashboard** | View dashboard |
| **Products** | View, Create, Copy, Edit, Delete, Mass Update, Mass Delete |
| **Categories** | View, Create, Edit, Delete, Mass Delete |
| **Category Fields** | View, Create, Edit, Delete, Mass Update, Mass Delete |
| **Attributes** | View, Create, Edit, Delete, Mass Delete |
| **Attribute Groups** | View, Create, Edit, Delete |
| **Attribute Families** | View, Create, Edit, Delete |
| **Data Transfer** | View, Create, Edit, Delete, Execute imports/exports |
| **Settings** | Manage Locales, Currencies, Channels, Users, Roles |
| **Configuration** | System configuration access |

### User Roles
Create custom roles for different team members:

| Example Role | Permissions |
|-------------|-------------|
| **Product Manager** | Full catalog access, no settings access |
| **Content Editor** | Edit products and categories, no delete access |
| **Translator** | Edit product content (locale-specific), read-only for structure |
| **Data Analyst** | View + Export only, no editing |
| **Administrator** | Full access to everything |

### User Preferences
Each user can configure:
- Preferred UI language (choose from 33 languages)
- Timezone setting
- Profile image

---

## 12. Version Control & History

### Change Tracking
UnoPim automatically tracks every change made to:
- Products
- Categories
- Attributes
- Attribute Families
- Channels
- Roles

**What you can see:**
- Who made the change
- When it was made
- What the old value was
- What the new value is
- Side-by-side comparison of changes

**Use case:** If a product description was changed incorrectly, you can review the history to find the previous version and understand what happened.

---

## 13. Dashboard & Monitoring

### Volume Dashboard
The dashboard provides an at-a-glance view of your catalog health:
- Total products count
- Total categories count
- Total attribute families count
- Catalog structure overview
- Completeness overview (if enabled)

---

## 14. User Interface

### Light & Dark Themes
UnoPim supports both light and dark themes. Users can switch between them with a single click. The preference is remembered across sessions.

### Advanced Data Grids
Every list view in UnoPim uses powerful data grids with:
- **Column sorting** - click any column header to sort
- **Advanced filtering** - filter by text, date ranges, boolean values, dropdowns, price ranges
- **Search** - global search across all columns
- **Pagination** - configurable items per page
- **Column management** - show/hide columns as needed
- **Mass actions** - select multiple items for bulk operations
- **Export** - export grid data directly to CSV or Excel
- **State persistence** - your filter/sort preferences are remembered

### Responsive Design
The admin panel works on desktops, tablets, and mobile devices with responsive breakpoints.

---

## 15. Elasticsearch Integration (Optional)

For large catalogs (thousands to millions of products), UnoPim can integrate with Elasticsearch to provide:
- Lightning-fast product search
- Advanced filtering capabilities
- Scalable performance even with 10+ million products

---

## 16. Notifications

### In-App Notifications
Receive real-time notifications within UnoPim for important events:
- Import job completed
- Export job completed
- Import validation failed

### Email Notifications
Configure email notifications for critical events so team members stay informed even when not logged in.

---

## 17. Installation & Setup

### Multiple Installation Options

| Method | Best For |
|--------|----------|
| **Composer** | PHP developers, custom hosting |
| **GUI Installer** | Non-technical users, visual step-by-step setup |
| **Docker** | Quick setup, consistent environments, DevOps teams |

### System Requirements
- PHP 8.2+
- MySQL 8.0+ or PostgreSQL 14+
- 8 GB RAM (recommended)
- Node.js 18.17+ (for building assets)

---

## 18. Security

UnoPim includes enterprise-grade security features:

| Feature | Description |
|---------|-------------|
| **HTTPS Enforcement** | Strict Transport Security (HSTS) enabled |
| **XSS Protection** | Cross-site scripting prevention headers |
| **CSRF Protection** | All forms protected against cross-site request forgery |
| **Clickjacking Prevention** | X-Frame-Options set to SAMEORIGIN |
| **Content Sniffing Prevention** | X-Content-Type-Options: nosniff |
| **OAuth2 for API** | Industry-standard token-based API authentication |
| **Session Security** | Encrypted cookies, configurable session timeouts |
| **Rate Limiting** | API rate limiting (60 requests/minute) to prevent abuse |
| **Maintenance Mode** | IP-whitelisted maintenance mode for safe updates |
| **Audit Trail** | Complete change history for compliance and accountability |

---

## Feature Summary by Version

| Version | Key Features Added |
|---------|-------------------|
| **v0.1.x** | Core PIM, Products, Categories, Attributes, Import/Export, API, Magic AI, Dark Theme, Gallery Attribute |
| **v0.2.0** | GUI Installer, In-App Notifications, Email Notifications, PATCH/DELETE API endpoints, Magic Image |
| **v0.3.0** | Dynamic DataGrid columns, Enhanced Elasticsearch, Quick Export, Playwright E2E tests, Upgrade automation |
| **v1.0.0** | PostgreSQL support, Product Completeness, Bulk Edit, Webhooks, System Prompts, Video Gallery, Custom AI Prompts |

---

## Getting Started

1. **Install UnoPim** using Composer, Docker, or the GUI installer
2. **Configure your channels** - define where your product data will go
3. **Set up languages and currencies** for your markets
4. **Create attribute families** - define the data structure for your products
5. **Import or create products** - bring in existing data or start fresh
6. **Enrich product content** - add descriptions, images, and translations
7. **Use Magic AI** to accelerate content creation and translation
8. **Export or integrate via API** to push data to your sales channels
9. **Invite team members** with appropriate roles and permissions

---

*UnoPim - Your Central Hub for Product Information*
