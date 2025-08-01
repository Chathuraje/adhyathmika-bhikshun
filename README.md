# Adhyathmika Bhikshun

A WordPress plugin for managing and enhancing the Adhyathmika Bhikshun website, including advanced admin features such as custom post ordering, language switching, and Airtable synchronization.

## Table of Contents
- [Overview](#overview)
- [Features](#features)
- [Directory Structure](#directory-structure)
- [Installation](#installation)
- [Usage](#usage)
- [Customization](#customization)
- [Contributing](#contributing)
- [License and Contact](#license-and-contact)

## Overview

**Adhyathmika Bhikshun** is a specialized WordPress plugin designed to streamline the administration of the Adhyathmika Bhikshun website. It centralizes several advanced functionalities aimed at enhancing content management workflows, improving admin efficiency, and integrating with third-party platforms like Airtable.

## Features

- **Custom Post Ordering**: Admins can customize the order in which posts appear.
- **Language Switcher**: Easily switch website language from the admin interface.
- **Airtable Synchronization**: Seamless sync between WordPress content and Airtable, with a dedicated summary dashboard showing sync activity and statuses.
- **Admin Dashboard Widgets**: Plugin provides status widgets to help administrators monitor plugin features and add important notes.
- **Settings & Customization**: Various include/settings files allow further configuration.
- **Asset Management**: All necessary CSS and JS are loaded for a polished admin user experience.

## Directory Structure

```
adhyathmika-bhikshun-main/
│
├── .github/              # GitHub workflows/config
├── .vscode/              # Editor configs
├── assets/               # CSS, JS files for admin
├── draft/                # Drafts or experimental features
├── includes/             # Core PHP logic (admin, widgets, notices, settings)
│    ├── admin.php
│    ├── admin_notice.php
│    ├── widgets.php
│    └── settings/
├── pages/                # Admin pages (dashboard, settings)
│    ├── dashboard-page.php
│    ├── main.php
│    └── settings/
├── tools/                # Utility scripts (e.g., encode.php)
├── adhyathmika-bhikshun.php  # Main plugin entry point
└── README.md             # Project documentation
```

## Installation

1. **Download** the plugin files and ensure they are unzipped.
2. **Upload** the `adhyathmika-bhikshun-main` folder to your WordPress site’s `wp-content/plugins/` directory.
3. **Activate** the plugin from the WordPress admin dashboard under **Plugins**.
4. Configure plugin settings via the new “Adhyathmika Bhikshun” menu in your admin sidebar.

## Usage

- Access the plugin’s features from the WordPress admin menu (“Adhyathmika Bhikshun”).
- Utilize the dashboard’s **Airtable Sync Summary** to monitor content sync with Airtable.
- Adjust post order and change languages as needed from the admin interface.
- Use dashboard widgets for a quick status overview and administrative notes.

## Customization

- Core plugin logic can be found under the `includes/` folder.
- Settings and dashboard features are extensible via respective files in `pages/settings` and `includes/settings`.
- The `assets/` folder can be expanded to enhance the admin interface with additional scripts or styles.
- For advanced integration or feature development, review the `tools/` directory for example utility scripts.

## Contributing

Contributions and suggestions are welcome. Please submit a pull request or open an issue if you have feedback or ideas.

## License and Contact

This plugin is developed by **Adhyathmika Bhikshun**. The use and distribution terms can be set according to your preference (e.g., GPL, MIT)—please update this section as needed.

For assistance, please contact the project maintainer.

---
*Generated README.md based on source code and file structure analysis.*
