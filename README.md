<p align="center">
  <a href="https://unopim.com/">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://github.com/user-attachments/assets/5001c5b0-1ef3-4887-a907-f9c20082b0e6">
      <source media="(prefers-color-scheme: light)" srcset="https://github.com/user-attachments/assets/a1e6793d-376e-4452-925b-c72b7d07389a">
      <img src="https://github.com/user-attachments/assets/a1e6793d-376e-4452-925b-c72b7d07389a" alt="UnoPim" width="280">
    </picture>
  </a>
</p>

<h3 align="center">Open-source PIM and DAM, built on Laravel</h3>

<p align="center">
  Organize, enrich and publish product data from one place, with an AI agent built in.
</p>

<p align="center">
  <a href="https://packagist.org/packages/unopim/unopim"><img src="https://img.shields.io/packagist/v/unopim/unopim?label=release" alt="Latest release"></a>
  <a href="https://github.com/unopim/unopim/actions/workflows/pest_tests.yml"><img src="https://img.shields.io/github/actions/workflow/status/unopim/unopim/pest_tests.yml?branch=3.x&label=tests" alt="Tests"></a>
  <a href="https://packagist.org/packages/unopim/unopim"><img src="https://img.shields.io/packagist/dt/unopim/unopim" alt="Downloads"></a>
  <a href="https://hub.docker.com/r/webkul/unopim"><img src="https://img.shields.io/docker/pulls/webkul/unopim?label=docker%20pulls" alt="Docker pulls"></a>
  <a href="LICENSE"><img src="https://img.shields.io/github/license/unopim/unopim" alt="MIT License"></a>
</p>

<p align="center">
  <a href="https://demo.unopim.com/"><b>Live demo</b></a> ·
  <a href="https://devdocs.unopim.com/3.1/introduction/installation.html"><b>Installation</b></a> ·
  <a href="https://devdocs.unopim.com/"><b>Documentation</b></a> ·
  <a href="https://unopim.com/extensions/"><b>Extensions</b></a> ·
  <a href="https://unopim.com/cloud-hosting"><b>Cloud hosting</b></a> ·
  <a href="https://github.com/unopim/unopim/discussions"><b>Discussions</b></a>
</p>

<p align="center">
  <a href="https://unopim.com/scaling-unopim-for-10-million-products/"><img src="https://github.com/user-attachments/assets/c264d658-3723-46ff-8b60-2b9506a7a412" alt="Scaling UnoPim to 10 million products" width="790"></a>
</p>

## What is UnoPim?

UnoPim is a Product Information Management (PIM) and [Digital Asset Management (DAM)](https://github.com/unopim/unopim-digital-asset-management) platform. It gives your team one place to model products, enrich them per channel and locale, manage their media, and send them to every storefront, marketplace and system that needs them.

- **Built to scale.** [Tested to 10 million products](https://unopim.com/scaling-unopim-for-10-million-products/), with queued imports and exports and optional Elasticsearch.
- **AI built in.** Chat with an agent to search, create, bulk edit and translate products, using OpenAI, Gemini, Groq, Ollama and other providers.
- **Runs your way.** MySQL, MariaDB or PostgreSQL, behind Nginx or Apache, on bare metal or in containers.
- **MIT licensed.** No open-core lock-in. Extend it with ordinary Laravel packages.

## Security and trust

<p align="center">
  <a href="https://www.bestpractices.dev/projects/16085"><img src="https://img.shields.io/cii/level/16085?style=for-the-badge&label=OpenSSF%20Best%20Practices" alt="OpenSSF Best Practices"></a>
  <a href="https://scorecard.dev/viewer/?uri=github.com/unopim/unopim"><img src="https://img.shields.io/ossf-scorecard/github.com/unopim/unopim?style=for-the-badge&label=OpenSSF%20Scorecard" alt="OpenSSF Scorecard"></a>
  <a href="https://sonarcloud.io/summary/new_code?id=unopim_unopim"><img src="https://img.shields.io/sonar/quality_gate/unopim_unopim?server=https%3A%2F%2Fsonarcloud.io&style=for-the-badge&label=SonarCloud" alt="SonarCloud quality gate"></a>
</p>

- **Audited openly.** Meets the [OpenSSF Best Practices](https://www.bestpractices.dev/projects/16085) criteria and is scored weekly by [OpenSSF Scorecard](https://scorecard.dev/viewer/?uri=github.com/unopim/unopim).
- **Verifiable releases.** Every published release ships a source archive with SLSA build provenance.
- **Continuously tested.** CI runs Pest suites on MySQL, MariaDB and PostgreSQL, Playwright end-to-end tests, and Larastan static analysis.
- **Patched dependencies.** Dependabot keeps Composer, npm, Docker and GitHub Actions dependencies current.
- **Responsible disclosure.** Report vulnerabilities privately through the [security policy](SECURITY.md), never in a public issue.

## Quick start

**Docker** (pre-built images, nothing to clone):

```bash
curl -O https://raw.githubusercontent.com/unopim/unopim/3.x/compose.yaml
docker compose up -d
```

Open <http://localhost:8000/admin>. Unless you set `INSTALLER_ADMIN_EMAIL` and `INSTALLER_ADMIN_PASSWORD` before the first start, the generated admin login is in the container:

```bash
docker compose exec unopim cat storage/app/admin-credentials.txt
```

**Composer** (PHP 8.4.1+, MySQL 8.0.32+ / MariaDB 10.11+ / PostgreSQL 16):

```bash
composer create-project unopim/unopim
cd unopim
php artisan unopim:install
php artisan serve
```

Open <http://localhost:8000/admin>, then start a queue worker for imports, exports, AI tasks, webhooks and passport publishing:

```bash
php artisan queue:work --queue=webhooks,system,completeness,default,publication
```

Full guides: [requirements](https://devdocs.unopim.com/3.1/introduction/requirements.html) · [installation](https://devdocs.unopim.com/3.1/introduction/installation.html) · [Docker](https://devdocs.unopim.com/3.1/introduction/installation-docker.html) · [MariaDB](https://devdocs.unopim.com/3.1/introduction/installation-with-mariadb.html) · [upgrading](UPGRADE.md)

## Features

<table>
  <tr>
    <td width="50%" valign="top">
      <img src="https://raw.githubusercontent.com/unopim/temp-media/main/magic-ai-with-multi-platform-support.png" alt="Magic AI"><br>
      <b>Magic AI</b><br>
      Generate descriptions, images and translations with 10+ AI providers, each with encrypted credentials and model selection.
    </td>
    <td width="50%" valign="top">
      <img src="https://raw.githubusercontent.com/unopim/temp-media/main/catalog-management.png" alt="Catalog management"><br>
      <b>Catalogue management</b><br>
      Families, attributes, categories, configurable products with multi-level variants, and association types.
    </td>
  </tr>
  <tr>
    <td width="50%" valign="top">
      <img src="https://raw.githubusercontent.com/unopim/temp-media/main/data-enrichment.png" alt="Data enrichment"><br>
      <b>Enrichment and completeness</b><br>
      Enrich values per channel and locale, and score every product against the attributes its family requires.
    </td>
    <td width="50%" valign="top">
      <img src="https://raw.githubusercontent.com/unopim/temp-media/main/data-transfer.png" alt="Import and export"><br>
      <b>Import and export</b><br>
      CSV, XLSX and ZIP jobs on a queue, with live step-by-step progress and per-job logs.
    </td>
  </tr>
  <tr>
    <td width="50%" valign="top">
      <img src="https://raw.githubusercontent.com/unopim/temp-media/main/api-integration.png" alt="REST API"><br>
      <b>REST API and webhooks</b><br>
      OAuth2 REST API (<a href="https://documenter.getpostman.com/view/37137259/2sBXVhEWjS">docs</a>, <a href="https://www.postman.com/unopim/unopim-apis/collection/kzy03uh/official-unopim-apis">Postman</a>) and asynchronous webhooks on product events.
    </td>
    <td width="50%" valign="top">
      <img src="https://raw.githubusercontent.com/unopim/temp-media/main/dashboard-with-analytics.png" alt="Dashboard with analytics"><br>
      <b>Dashboard and analytics</b><br>
      Channel readiness, product trends, recent activity and items needing attention at a glance.
    </td>
  </tr>
  <tr>
    <td width="50%" valign="top">
      <img src="https://raw.githubusercontent.com/unopim/temp-media/main/localization-and-channels.png" alt="Localization"><br>
      <b>Localization</b><br>
      Enrich content per locale, with the admin itself translated into 33 languages.
    </td>
    <td width="50%" valign="top">
      <img src="https://raw.githubusercontent.com/unopim/temp-media/main/multi-channel-support.png" alt="Multi-channel"><br>
      <b>Multi-channel</b><br>
      Scope attribute values and completeness to each sales channel.
    </td>
  </tr>
  <tr>
    <td width="50%" valign="top">
      <img src="https://raw.githubusercontent.com/unopim/temp-media/main/multi-currency-support.png" alt="Multi-currency"><br>
      <b>Multi-currency</b><br>
      Price products in every currency your channels sell in.
    </td>
    <td width="50%" valign="top">
      <img src="https://raw.githubusercontent.com/unopim/temp-media/main/version-control.png" alt="Version history"><br>
      <b>Version history</b><br>
      See who changed what and when on every product, and roll changes back.
    </td>
  </tr>
  <tr>
    <td width="50%" valign="top">
      <img src="https://raw.githubusercontent.com/unopim/temp-media/main/access-control.png" alt="Roles and permissions"><br>
      <b>Roles and permissions</b><br>
      Fine-grained ACL for users, roles and API integrations.
    </td>
    <td width="50%" valign="top">
      <img src="https://raw.githubusercontent.com/unopim/temp-media/main/user-interface.png" alt="Light and dark themes"><br>
      <b>Light and dark themes</b><br>
      A themeable admin interface that follows the system colour scheme.
    </td>
  </tr>
</table>

Also included: **Digital Product Passports** with QR carriers and a public tier, **publication channels** that push payloads downstream on a queue, and **measurement families** with unit conversion.

## Extensions

<table>
  <tr>
    <td width="50%" valign="top">
      <a href="https://github.com/unopim/unopim-digital-asset-management"><img src="https://raw.githubusercontent.com/unopim/temp-media/main/dam-ai-auto-tagging.png" alt="UnoPim DAM AI auto-tagging"></a><br>
      <b><a href="https://github.com/unopim/unopim-digital-asset-management">Digital Asset Management</a></b><br>
      Upload, preview, tag and organize assets, with AI auto-tagging on upload and bulk assignment to products.
    </td>
    <td width="50%" valign="top">
      <a href="https://github.com/unopim/shopify-connector"><img src="https://raw.githubusercontent.com/unopim/temp-media/main/Shopify-Connector/Export%20Product.png" alt="Shopify Connector product export"></a><br>
      <b><a href="https://github.com/unopim/shopify-connector">Shopify Connector</a></b><br>
      Sync products, prices, descriptions and images to a Shopify store.
    </td>
  </tr>
</table>

Also free and open source:

- [Master Data Management](https://github.com/unopim/master-data-management): governed master records, matching, approvals and code lists, alongside the PIM or standalone.
- [Bagisto Connector](https://github.com/unopim/bagisto-connector): sync products, attributes and media to a [Bagisto](https://bagisto.com/) store.

Browse more on the [extensions marketplace](https://unopim.com/extensions/), or [build your own](https://devdocs.unopim.com/3.1/plugins/).

## AI and developer tools

All MIT licensed.

| Project | What it does |
| --- | --- |
| [MCP server](https://github.com/unopim/unopim-mcp) | Let ChatGPT, Claude, Copilot, Cursor and other AI agents search and enrich your catalogue, with ACL-enforced tools. |
| [Claude plugin](https://github.com/unopim/claude-plugin) | Connect Claude to a self-hosted UnoPim, with skills for catalogue audits and product enrichment. |
| [Agent skills](https://github.com/unopim/agent-skills) | UnoPim context for coding agents working in an UnoPim codebase. |
| [n8n nodes](https://github.com/unopim/n8n-nodes-unopim) | Use your UnoPim catalogue in n8n workflows. |
| [PHP API client](https://github.com/unopim/api-php-client) | Framework-agnostic, PSR-18 client for the UnoPim REST API. |

## Hosting

- **Self-hosted:** Composer or Docker, as above.
- **Managed:** [UnoPim Cloud](https://unopim.com/cloud-hosting), with no setup.
- **AWS Marketplace:** pre-configured AMIs for [x86_64](https://aws.amazon.com/marketplace/pp/prodview-rhsm6cbxqkm6i) and [ARM64 / Graviton](https://aws.amazon.com/marketplace/pp/prodview-flks5cupod5mi).

## Learn more

| | |
| --- | --- |
| [![Introducing UnoPim](https://img.youtube.com/vi/_P1zt4BxJK0/mqdefault.jpg)](https://www.youtube.com/watch?v=_P1zt4BxJK0) | [![10M products](https://img.youtube.com/vi/n76bJmdZkL8/mqdefault.jpg)](https://www.youtube.com/watch?v=n76bJmdZkL8) |
| Introducing UnoPim | Can an open-source PIM handle 10M products? |

Read the [UnoPim at Scale whitepaper (PDF)](https://unopim.com/wp-content/uploads/2026/08/unopim-at-scale-10m-whitepaper.pdf) for full benchmarks.

## Community and support

- [GitHub Discussions](https://github.com/unopim/unopim/discussions) for questions and ideas
- [Issues](https://github.com/unopim/unopim/issues) for bugs
- [YouTube](https://www.youtube.com/@UnoPIM) · [X](https://x.com/uno_pim) · [Facebook](https://www.facebook.com/unopim/)

## Contributing

Contributions are welcome. Read the [contributing guide](.github/CONTRIBUTING.md) and the [code of conduct](CODE_OF_CONDUCT.md) before you open a pull request.

<a href="https://github.com/unopim/unopim/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=unopim/unopim" alt="Contributors">
</a>

## License

UnoPim is open-source software licensed under the [MIT License](LICENSE).
