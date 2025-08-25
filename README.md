<p align="center">
  <a href="https://unopim.com/">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://github.com/user-attachments/assets/5001c5b0-1ef3-4887-a907-f9c20082b0e6">
      <source media="(prefers-color-scheme: light)" srcset="https://github.com/user-attachments/assets/a1e6793d-376e-4452-925b-c72b7d07389a">
      <img src="https://github.com/user-attachments/assets/a1e6793d-376e-4452-925b-c72b7d07389a" alt="UnoPim logo">
    </picture>
  </a>
</p>

UnoPim is an open-source Product Information Management (PIM) system built on the Laravel framework. It helps businesses organize, manage, and enrich their product information in one central repository.

## 🛠️ System Requirements

Ensure your server meets the following requirements:

- **Server**: Nginx or Apache2
- **RAM**: 8GB
- **Node.js**: 18.17.1 LTS or higher
- **PHP**: 8.2 or higher
- **Composer**: 2.5 or higher
- **MySQL**: Version 8.0.32 or higher

## ✨ Features

- **Centralized Product Management**  
  Manage all your product data in one place.

  ![Centralized Product Management Interface](https://raw.githubusercontent.com/unopim/temp-media/main/catalog-management.png)

- **Data Enrichment**  
  Enhance your product information with detailed attributes and descriptions.

  ![Data Enrichment Interface](https://raw.githubusercontent.com/unopim/temp-media/main/data-enrichment.png)

- **Magic AI for Product Content Generation**  
  Automatically generate engaging product content using advanced Large Language Model (LLM) technology.

  ![AI-powered Product Content Generation](https://raw.githubusercontent.com/unopim/temp-media/main/advanced-features.png)

- **User Management**  
  Control user access and permissions.

  ![User Management Interface](https://raw.githubusercontent.com/unopim/temp-media/main/access-control.png)

- **API Integration**  
  Seamlessly integrate with other systems via RESTful APIs.

  ![API Integration Interface](https://raw.githubusercontent.com/unopim/temp-media/main/api-integration.png)

- **Localization**  
  Support for multiple languages and locales.

  ![Localization Support](https://raw.githubusercontent.com/unopim/temp-media/main/localization-and-channels.png)

- **Multi-Channel**  
  Support for multiple sales channels.

  ![Multi-Channel Support](https://raw.githubusercontent.com/unopim/temp-media/main/multi-channel-support.png)

- **Multi-Currency**  
  Support for multiple currencies.

  ![Multi-Currency Support](https://raw.githubusercontent.com/unopim/temp-media/main/multi-currency-support.png)

- **Import/Export Functionality**  
  Easily import and export product data using CSV and XLSX formats, with a quick export feature for streamlined data handling.

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
   To execute import/export operations, initiate the Queue command:

   ```bash
   php artisan queue:work
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

👉 [**Launch UnoPim on AWS**](https://aws.amazon.com/marketplace/pp/prodview-fdyosdv7k3cgw)

This AMI allows you to get started with UnoPim on a cloud environment without manual setup. Ideal for scalable production or testing environments.

## 📖 Usage

Once installed, you can start adding and managing your products. The intuitive interface allows you to categorize products, enrich data, and manage user permissions easily.

## 📬 Postman API Collection

To interact with UnoPim's API, you can use our official Postman collection:

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
