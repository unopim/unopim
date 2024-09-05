<p align="center">
  <a href="https://unopim.com/">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://github.com/user-attachments/assets/5001c5b0-1ef3-4887-a907-f9c20082b0e6">
      <source media="(prefers-color-scheme: light)" srcset="https://github.com/user-attachments/assets/a1e6793d-376e-4452-925b-c72b7d07389a">
      <img src="https://github.com/user-attachments/assets/a1e6793d-376e-4452-925b-c72b7d07389a" alt="UnoPim">
    </picture>
  </a>
</p>

UnoPim is an open-source Product Information Management (PIM) system built on the Laravel framework. It helps businesses organize, manage, and enrich their product information in one central repository.
 
### 🛠️ System Requirements

Before you begin, ensure your server meets the following requirements:

- **Server**: Apache 2
- **RAM**: 8GB
- **Node.js**: 18.17.1 LTS or higher
- **PHP**: 8.2 or higher
- **Composer**: 2.5 or higher
- **MySQL**: Version 8.0.32 or higher
 
## ✨ Features

- **Centralized Product Management**  
  Manage all your product data in one place.

  ![enter image description here](https://raw.githubusercontent.com/unopim/temp-media/main/catalog-management.png)


- **User Management**  
  Control user access and permissions.

  ![enter image description here](https://raw.githubusercontent.com/unopim/temp-media/main/access-control.png)

- **API Integration**  
  Seamlessly integrate with other systems via RESTful APIs.

  ![enter image description here](https://raw.githubusercontent.com/unopim/temp-media/main/api-integration.png)

- **Localization**  
  Support for multiple languages and locales.

    ![enter image description here](https://raw.githubusercontent.com/unopim/temp-media/main/localization-and-channels.png)

- **Import/Export Functionality**  
  Easily import and export product data using CSV and XLSX formats, with a quick export feature for streamlined data handling.

    ![enter image description here](https://raw.githubusercontent.com/unopim/temp-media/main/data-transfer.png)

- **Magic AI for Product Content Generation**  
  Automatically generate engaging product content using advanced LLM (Large Language Model) technology.

  ![enter image description here](https://raw.githubusercontent.com/unopim/temp-media/main/advanced-features.png)


## Installation

To get started with UnoPim, follow these steps:

1. **Project Setup**:
    ```bash
    composer create-project unopim/unopim
    cd unopim
    ```

2. **Install the UnoPim**:
    ```bash
    php artisan unopim:install
    ```

3. **Serve the application**:
    ```bash
    php artisan serve
    ```

4. **Access UnoPim**:
    Open your browser and go to `http://localhost:8000`.

5. **Queue Operations** 
   To execute import/export operations, you are required to initiate the Queue command. Execute the following command:

   ```bash
   php artisan queue:work
   ```

## Usage

Once installed, you can start adding and managing your products. The intuitive interface allows you to easily categorize products, enrich product data, and manage user permissions.

## Contributing

We welcome contributions from the community. To contribute:

1. Fork the repository.
2. Create a new branch (`git checkout -b feature/your-feature`).
3. Commit your changes (`git commit -m 'Add some feature'`).
4. Push to the branch (`git push origin feature/your-feature`).
5. Open a pull request.

## Security

If you discover any security vulnerabilities, please follow our [Security Policy](SECURITY.md) and report them to [support@webkul.com](mailto:support@webkul.com).

## License

UnoPim is open-sourced software licensed under the [Open Software License (OSL) 3.0](LICENSE.txt).

## Acknowledgements

We would like to thank all the contributors and the Laravel community for their support and contributions.
