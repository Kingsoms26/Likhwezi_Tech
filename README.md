# Likhwezi Technologies Website

The public website and staff portal for Likhwezi Technologies, a professional services consultancy focused on enterprise data systems.

- **Live site:** https://likhwezi-tech.onrender.com/
- **Staff login:** https://likhwezi-tech.onrender.com/staffLogin.php

## What it does

**Public website**

- Pages for Home, About Us, Services, Gallery & Events, Partners, Cyber Young Minds and Contact Us
- Upcoming and previous events
- Registration for the Cyber Young Minds programme
- Fundraising campaigns with donations through PayFast
- Contact form that sends enquiries to staff

**Staff portal**

There are three roles, and each has its own dashboard:

| Role             | Can do                                                           |
| ---------------- | ---------------------------------------------------------------- |
| Admin            | Manage staff accounts, site content, archive and activity log    |
| Marketing        | Manage events, campaigns, partners, gallery and registrations    |
| Customer Service | Reply to and close enquiries                                     |

## Built with

- PHP 8.2 and Apache
- MySQL (connected over SSL)
- Plain HTML, CSS and JavaScript
- PayFast for donations
- Cloudinary for uploaded photos
- Docker, hosted on Render

## Project structure

```
/               public pages (index.php, about.php, contact.php ...)
assets/         css, images and js for the public site
config/         database connection and .env loader
includes/       shared components (navbar, footer) and helpers
staff/          staff portal: dashboards, components, helpers
database/       SQL to create the database
docs/           user manual and team notes
uploads/        local upload folders
```

## Running it locally

1. Install [XAMPP](https://www.apachefriends.org/) and clone this repo into `htdocs`.
2. Start Apache and MySQL from the XAMPP control panel.
3. Create the database by running the SQL in [database/dbSetup.md](database/dbSetup.md).
4. Create a file called `config/.env` with your settings (see below).
5. Open `http://localhost/<folder-name>/` in your browser.

### The `.env` file

```
# database
host=localhost
port=3306
username=root
password=
database=LikhweziTechDB

# payfast (keep sandbox on while testing)
PAYFAST_MERCHANT_ID=
PAYFAST_MERCHANT_KEY=
PAYFAST_PASSPHRASE=
PAYFAST_SANDBOX=true
PAYFAST_SITE_URL=

# cloudinary (needed for photo uploads)
CLOUDINARY_CLOUD_NAME=
CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=
```

Never commit `.env`. It is already in `.gitignore`. On Render, these same values are set as environment variables instead.

`PAYFAST_SITE_URL` is optional. Set it to a public address (for example an ngrok link) if you want PayFast to reach your local copy.

## Deploying

The site runs on Render using the `Dockerfile`.

## More docs

- [Brief user manual](docs/brief-user-manual.md): how to use the public site and staff portal
- [Staff portal notes](staff/README.txt): how the dashboards, components and CSS are organised
- [Database setup](database/dbSetup.md): tables and setup SQL

Test account passwords are not stored in this repo. Ask the developer for them.
