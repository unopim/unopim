<p align="center">
  <a href="https://unopim.com/">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://github.com/user-attachments/assets/5001c5b0-1ef3-4887-a907-f9c20082b0e6">
      <source media="(prefers-color-scheme: light)" srcset="https://github.com/user-attachments/assets/a1e6793d-376e-4452-925b-c72b7d07389a">
      <img src="https://github.com/user-attachments/assets/a1e6793d-376e-4452-925b-c72b7d07389a" alt="UnoPim logo">
    </picture>
  </a>
</p>

<p align="center">
  <a href="https://github.com/unopim/unopim/releases"><img src="https://img.shields.io/github/v/release/unopim/unopim?label=version" alt="Latest Version"></a>
  <a href="https://github.com/unopim/unopim/blob/master/LICENSE"><img src="https://img.shields.io/github/license/unopim/unopim" alt="License"></a>
  <a href="https://github.com/unopim/unopim/actions"><img src="https://img.shields.io/github/actions/workflow/status/unopim/unopim/linting_tests.yml?branch=master&label=tests" alt="Tests"></a>
  <a href="https://github.com/unopim/unopim"><img src="https://img.shields.io/github/stars/unopim/unopim" alt="GitHub Stars"></a>
</p>

UnoPim is an open-source Product Information Management (PIM) system built on Laravel 12. It helps businesses organize, manage, and enrich their product information in one central repository — now with built-in AI agent capabilities for conversational product management.

## 🆕 What's New in v2.0.0

- **AI Agent Chat** — Manage products through natural language with 32+ tool actions (search, create, update, bulk edit, export, generate content/images, memory, planning, quality reports, and more)
- **Agentic PIM** — Autonomous product enrichment, catalog quality monitoring, approval workflows, content feedback loop, and persistent agent memory
- **Multi-Platform MagicAI** — Support for 10+ AI providers (OpenAI, Anthropic, Gemini, Groq, Ollama, Mistral, DeepSeek, and more) with database-backed credential management
- **Laravel 12 Upgrade** — Modernized bootstrap architecture with `bootstrap/app.php` fluent API, removing Kernel classes
- **Enhanced Dashboard** — Channel readiness, product trends, recent activity, and needs-attention widgets
- **Import/Export Performance & Tracker UI** — Real-time step-by-step progress tracking, ZIP image upload, eager loading optimizations, increased batch sizes, deferred indexing, and field processor improvements for high-volume data handling
- **AI-Powered Search** — Embedding similarity and semantic ranking services for intelligent product discovery
- **Improved CI/CD** — Translation auditing, Composer caching, concurrency groups, PHP 8.3 across all workflows

> Upgrading from v1.0.0? See the [Upgrade Guide](UPGRADE-1.0.0-2.0.0.md) and [CHANGELOG](CHANGELOG.md).

## 🛠️ System Requirements

Ensure your server meets the following requirements:

* **Server**: Nginx or Apache2
* **RAM**: 8GB
* **PHP**: **8.3** or higher
* **Node.js**: **20 LTS** or higher
* **Composer**: **2.5** or higher
* **Database (choose one):**
  * **MySQL**: 8.0.32 or higher
  * **PostgreSQL**: **14.x or higher** *(recommended)*


## ⚙️ Scalability

- [Learn how UnoPim scales to handle over **10 million products**](https://unopim.com/scaling-unopim-for-10-million-products/)

  <a href="https://unopim.com/scaling-unopim-for-10-million-products/" target="_blank">
    <img src="https://github.com/user-attachments/assets/c264d658-3723-46ff-8b60-2b9506a7a412" alt="10 million products" width="790">
  </a>


## ✨ Features

- **AI Agent Chat**
  Manage products through natural language — search, create, update, bulk edit, export, categorize, and generate content via conversational AI with multi-step tool calling.

- **Magic AI with Multi-Platform Support**
  Generate product content, images, and translations using 10+ AI providers. Configure and manage AI platforms with encrypted credential storage, connection testing, and dynamic model selection.

  ![AI-powered Product Content Generation](https://raw.githubusercontent.com/unopim/temp-media/main/advanced-features.png)

- **Centralized Product Management**
  Manage all your product data in one place.

  ![Centralized Product Management Interface](https://raw.githubusercontent.com/unopim/temp-media/main/catalog-management.png)

- **Data Enrichment**
  Enhance your product information with detailed attributes and descriptions.

  ![Data Enrichment Interface](https://raw.githubusercontent.com/unopim/temp-media/main/data-enrichment.png)

- **Dashboard with Analytics**
  Monitor channel readiness, product trends, recent activity, and items needing attention at a glance.

- **User Management**
  Control user access and permissions.

  ![User Management Interface](https://raw.githubusercontent.com/unopim/temp-media/main/access-control.png)

- **API Integration**
  Seamlessly integrate with other systems via RESTful APIs.

  ![API Integration Interface](https://raw.githubusercontent.com/unopim/temp-media/main/api-integration.png)

- **Localization**
  Support for 30+ languages and locales.

  ![Localization Support](https://raw.githubusercontent.com/unopim/temp-media/main/localization-and-channels.png)

- **Multi-Channel**
  Support for multiple sales channels.

  ![Multi-Channel Support](https://raw.githubusercontent.com/unopim/temp-media/main/multi-channel-support.png)

- **Multi-Currency**
  Support for multiple currencies.

  ![Multi-Currency Support](https://raw.githubusercontent.com/unopim/temp-media/main/multi-currency-support.png)

- **Import/Export with Real-Time Tracker**
  Import and export product data using CSV, XLSX, and ZIP formats with real-time step-by-step progress tracking, pipeline visualization, and job-specific logging. Optimized for high-volume data handling with eager loading, increased batch sizes, and deferred indexing.

  ![Data Import/Export Interface](https://raw.githubusercontent.com/unopim/temp-media/main/data-transfer.png)

- **Themes**
  UI compatible with both Light and Dark themes.

  ![Light/Dark Theme Interface](https://raw.githubusercontent.com/unopim/temp-media/main/user-interface.png)

- **Version Control**
  Track the history of changes in your product data.

  ![Version Control Interface](https://raw.githubusercontent.com/unopim/temp-media/main/version-control.png)

## 🚀 Installation

To get started with UnoPim, follow these steps:

1. **Project Setup**:
    ```bash
    composer create-project unopim/unopim
    cd unopim
    ```

2. **Install UnoPim**:
    ```bash
    php artisan unopim:install
    ```

3. **Serve the application**:
    ```bash
    php artisan serve
    ```

4. **Access UnoPim**:
   Open your browser and go to `http://localhost:8000`.

5. **Queue Operations**:
   To execute import/export operations, AI agent tasks, and product completeness score calculation, start the queue worker:

   ```bash
   php artisan queue:work --queue=system,default,completeness
   ```

## 🐳 Installation with Docker

If you have Docker/Docker Compose installed, follow these steps:

1. **Clone the repository**:
   - HTTPS: `git clone https://github.com/unopim/unopim.git`
   - SSH: `git clone git@github.com:unopim/unopim.git`

2. **Enter the directory**:
   ```bash
   cd unopim
   ```

3. **Start the Docker containers**:
   ```bash
   docker-compose up -d
   ```

   This will pull the necessary images and set up the environment. Once running, access the application at:

   - Application: `http://localhost:8000`
   - MySQL: `http://localhost:3306`


> **Note**:
> If MySQL is already running on your system, change the MySQL port in the `docker-compose.yml` and `.env` files.
> Run `docker-compose up -d` again to apply changes.

## ☁️ Cloud Installation via Amazon AMI

You can also deploy UnoPim quickly using our pre-configured Amazon Machine Image (AMI) available on the AWS Marketplace:

[**Launch UnoPim on AWS**](https://aws.amazon.com/marketplace/pp/prodview-fdyosdv7k3cgw)

This AMI allows you to get started with UnoPim on a cloud environment without manual setup. Ideal for scalable production or testing environments.

## 📖 Usage

Once installed, you can start adding and managing your products. The intuitive interface allows you to categorize products, enrich data, and manage user permissions easily. Use the AI Agent Chat to manage products through natural language commands.

## 📬 Postman API Collection

To interact with UnoPim's API, you can use our official Postman collection:

[UnoPim APIs Documentation](https://documenter.getpostman.com/view/37137259/2sBXVhEWjS)

[UnoPim APIs on Postman](https://www.postman.com/unopim/unopim-apis/collection/kzy03uh/official-unopim-apis?ctx=info)

This collection provides ready-to-use API requests for various UnoPim features. You can import it directly into your Postman workspace and start testing the APIs.

## 🤝 Contributing

We welcome contributions! To contribute:

1. Fork the repository.
2. Create a new branch (`git checkout -b feature/your-feature`).
3. Commit your changes (`git commit -m 'Add feature'`).
4. Push to the branch (`git push origin feature/your-feature`).
5. Open a pull request.

## 🔒 Security

If you discover any security vulnerabilities, please follow our [Security Policy](SECURITY.md) and report them to [support@webkul.com](mailto:support@webkul.com).

## 📝 License

UnoPim is open-source software distributed under the [MIT License](LICENSE).

## 🙏 Acknowledgements

We extend our thanks to all contributors and the Laravel community for their support and contributions.
