Database Setup

This file holds the SQL used to create the "LikhweziTechDB" database, including the table definitions (names, columns, and relationships) and any setup statements needed to get the schema up and running.

Note: This SQL is written specifically for MySQL and is not guaranteed to work on other database systems.

*********************************************************************
List of Tables

- UserAccount
- Admin
- StaffMarketing
- StaffCustomerService
- ArchivableEntity
- Enquiry
- Registration
- Programme
- SiteSetting
- Service
- CymPhoto
- Partner
- Report
- Campaign
- Event
- GalleryItem
- ArchiveLog
- Donation
- ActivityLog

*********************************************************************
SQL Code

```sql
-- creating the database
CREATE DATABASE LikhweziTechDB;

USE LikhweziTechDB;

-- creating the tables
CREATE TABLE UserAccount (
  accountID int PRIMARY KEY AUTO_INCREMENT NOT NULL,
  createdBy int NULL,
  firstName VARCHAR(100) NOT NULL DEFAULT '',
  lastName VARCHAR(100) NOT NULL DEFAULT '',
  email VARCHAR(255) UNIQUE NOT NULL,
  username VARCHAR(255) UNIQUE NOT NULL,
  passwordHash VARCHAR(255) NOT NULL,
  dateCreated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  accountStatus ENUM('active', 'suspended', 'deactivated') NOT NULL DEFAULT 'active',
  phoneNumber VARCHAR(30) NULL,
  profileImageURL VARCHAR(500) NULL,
  lastLogin DATETIME NULL,
  passwordChangedAt DATETIME NULL,
  mustChangePassword BOOLEAN NOT NULL DEFAULT TRUE,
  -- Accounts are archived, never deleted, so the records they created keep their owner.
  isArchived BOOLEAN NOT NULL DEFAULT FALSE,
  archivedAt DATETIME NULL,
  archivedBy int NULL,
  FOREIGN KEY (createdBy) REFERENCES UserAccount (accountID) ON DELETE SET NULL,
  CONSTRAINT fk_useraccount_archivedby FOREIGN KEY (archivedBy) REFERENCES UserAccount (accountID) ON DELETE SET NULL,
  INDEX idx_useraccount_status (accountStatus),
  INDEX idx_useraccount_archived (isArchived)
);

CREATE TABLE Admin (
  accountID int PRIMARY KEY NOT NULL,
  FOREIGN KEY (accountID) REFERENCES UserAccount (accountID) ON DELETE CASCADE
);

CREATE TABLE StaffMarketing (
  accountID int PRIMARY KEY NOT NULL,
  FOREIGN KEY (accountID) REFERENCES UserAccount (accountID) ON DELETE CASCADE
);

CREATE TABLE StaffCustomerService (
  accountID int PRIMARY KEY NOT NULL,
  FOREIGN KEY (accountID) REFERENCES UserAccount (accountID) ON DELETE CASCADE
);

CREATE TABLE ArchivableEntity (
  entityID int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  entityType ENUM('Enquiry', 'Registration', 'GalleryItem', 'Partner', 'Event', 'Campaign') NOT NULL
);

CREATE TABLE Enquiry (
  enquiryID int PRIMARY KEY UNIQUE NOT NULL AUTO_INCREMENT,
  handledBy int NULL,
  name VARCHAR(255) NOT NULL,
  companyName VARCHAR(255) NULL,
  email VARCHAR(255) NOT NULL,
  phoneNumber VARCHAR(30) NULL,
  description TEXT NOT NULL,
  meetingType ENUM('virtual', 'physical', 'none') NOT NULL DEFAULT 'none',
  status ENUM('new', 'contacted', 'closed') NOT NULL DEFAULT 'new',
  isArchived BOOLEAN NOT NULL DEFAULT FALSE,
  dateCreated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (enquiryID) REFERENCES ArchivableEntity (entityID) ON DELETE CASCADE,
  -- Removing a customer service role unassigns their enquiries rather than deleting them.
  FOREIGN KEY (handledBy) REFERENCES StaffCustomerService (accountID) ON DELETE SET NULL
);

CREATE TABLE Registration (
  registrationID int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  firstName VARCHAR(100) NOT NULL,
  lastName VARCHAR(100) NOT NULL,
  age int NOT NULL CHECK (age>=1 AND age<=100),
  email VARCHAR(255) NULL,
  phoneNumber VARCHAR(30) NULL,
  programme VARCHAR(255) NOT NULL,
  consentConfirmation BOOLEAN NOT NULL DEFAULT FALSE,
  consentGivenAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  mediaConsent BOOLEAN NOT NULL DEFAULT FALSE,
  guardianName VARCHAR(150) NULL,
  guardianLastName VARCHAR(100) NULL,
  guardianRelationship VARCHAR(100) NULL,
  guardianEmail VARCHAR(150) NULL,
  guardianPhoneNumber VARCHAR(30) NULL,
  guardianConsentConfirmation BOOLEAN NULL DEFAULT FALSE,
  guardianConsentGivenAt DATETIME NULL,
  guardianCommunicationConsent BOOLEAN NULL DEFAULT FALSE,
  isArchived BOOLEAN NOT NULL DEFAULT FALSE,
  dateCreated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (registrationID) REFERENCES ArchivableEntity (entityID) ON DELETE CASCADE
);

-- Cyber Young Minds programmes people can register for. When no programme is
-- active, cym.php falls back to its built-in list. Registration.programme stores
-- the name, so old registrations keep their programme if one is renamed or removed.
CREATE TABLE Programme (
  programmeID int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL UNIQUE,
  isActive BOOLEAN NOT NULL DEFAULT TRUE,
  sortOrder INT NOT NULL DEFAULT 0,
  dateAdd DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Website content edited on staff/manageContent.php. database/websiteContent.sql
-- creates these on an existing database and seeds them with the site's original content.

-- Single values used across the public site (footer contact details, CYM page).
CREATE TABLE SiteSetting (
  settingKey VARCHAR(100) PRIMARY KEY NOT NULL,
  value TEXT NOT NULL
);

-- Service cards on services.php. `includes` holds one item per line.
CREATE TABLE Service (
  serviceID int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL UNIQUE,
  tagline VARCHAR(255) NOT NULL,
  icon VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  includes TEXT NOT NULL,
  isActive BOOLEAN NOT NULL DEFAULT TRUE,
  sortOrder INT NOT NULL DEFAULT 0,
  dateAdd DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Photos in the carousel at the top of cym.php. `src` is relative to the site root.
CREATE TABLE CymPhoto (
  photoID int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  src VARCHAR(255) NOT NULL,
  altText VARCHAR(255) NOT NULL,
  sortOrder INT NOT NULL DEFAULT 0,
  dateAdd DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Partner (
  partnerID int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  logo VARCHAR(255) NOT NULL,
  websiteURL VARCHAR(255) NULL,
  sortOrder INT NOT NULL DEFAULT 0,
  dateAdd DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  isArchived BOOLEAN NOT NULL DEFAULT FALSE,
  FOREIGN KEY (partnerID) REFERENCES ArchivableEntity (entityID) ON DELETE CASCADE
);

CREATE TABLE Report (
  reportID int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  generatedBy int NOT NULL,
  reportType ENUM('enquiry', 'registration', 'event', 'campaign', 'useraccount') NOT NULL,
  dateGenerated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (generatedBy) REFERENCES Admin (accountID)
);

CREATE TABLE Campaign (
  campaignID int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  createdBy int NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  goal DECIMAL(12, 2) NOT NULL,
  status ENUM('draft', 'active', 'closed') NOT NULL DEFAULT 'draft',
  isArchived BOOLEAN NOT NULL DEFAULT FALSE,
  -- Admin and Marketing can both add campaigns, so this references any staff account.
  FOREIGN KEY (createdBy) REFERENCES UserAccount (accountID),
  FOREIGN KEY (campaignID) REFERENCES ArchivableEntity (entityID) ON DELETE CASCADE
);

CREATE TABLE Event (
  eventID int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  createdBy int NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  eventDate DATE NULL,
  -- Only used when eventDate is NULL: TRUE = date to be confirmed (upcoming), FALSE = past event.
  dateToBeConfirmed BOOLEAN NOT NULL DEFAULT FALSE,
  isArchived BOOLEAN NOT NULL DEFAULT FALSE,
  -- Admin and Marketing can both add events, so this references any staff account.
  FOREIGN KEY (createdBy) REFERENCES UserAccount (accountID),
  FOREIGN KEY (eventID) REFERENCES ArchivableEntity (entityID) ON DELETE CASCADE
);

CREATE TABLE GalleryItem (
  galleryItemID int PRIMARY KEY AUTO_INCREMENT NOT NULL,
  eventID int NULL,
  campaignID int NULL,
  image VARCHAR(500) NOT NULL,
  FOREIGN KEY (eventID) REFERENCES Event (eventID) ON DELETE CASCADE,
  FOREIGN KEY (campaignID) REFERENCES Campaign (campaignID) ON DELETE CASCADE,
  FOREIGN KEY (galleryItemID) REFERENCES ArchivableEntity (entityID) ON DELETE CASCADE,
  CONSTRAINT chk_galleryitem_one_parent CHECK (
    (eventID IS NOT NULL AND campaignID IS NULL) OR
    (eventID IS NULL AND campaignID IS NOT NULL)
  )
);

CREATE TABLE ArchiveLog (
  archiveID int PRIMARY KEY AUTO_INCREMENT,
  entityID int NOT NULL,
  performedBy int NOT NULL,
  action ENUM('archived', 'restored'),
  timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (entityID) REFERENCES ArchivableEntity (entityID),
  FOREIGN KEY (performedBy) REFERENCES UserAccount (accountID) 
);

CREATE TABLE Donation (
  donationID int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  campaignID int NOT NULL,
  firstName VARCHAR(100) NOT NULL,
  lastName VARCHAR(100) NOT NULL,
  phoneNumber VARCHAR(30) NULL,
  email VARCHAR(255) NOT NULL,
  donationDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  amount DECIMAL(12,2) NOT NULL CHECK(amount>0),
  paymentReference VARCHAR(255) NOT NULL UNIQUE,
  -- Set by PayFast (see includes/helpers/payfast.php). Only complete donations count towards a campaign's total.
  paymentStatus ENUM('pending', 'complete', 'cancelled', 'failed') NOT NULL DEFAULT 'pending',
  pfPaymentID VARCHAR(64) NULL,
  -- Anonymous donors are still recorded for staff, but their name is never shown publicly.
  isAnonymous BOOLEAN NOT NULL DEFAULT FALSE,
  FOREIGN KEY (campaignID) REFERENCES Campaign (campaignID),
  INDEX idx_donation_campaign_status (campaignID, paymentStatus)
);

-- Everything staff do in the portal (staff/helpers/activityLog.php). username is a copy so the
-- log still reads correctly if the account changes; targetID has no foreign key so a row
-- survives when the record it describes is permanently deleted.
CREATE TABLE ActivityLog (
  logID int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  accountID int NULL,
  username VARCHAR(255) NOT NULL,
  category VARCHAR(30) NOT NULL,
  action VARCHAR(30) NOT NULL,
  description VARCHAR(500) NOT NULL,
  targetID int NULL,
  ipAddress VARCHAR(45) NULL,
  createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (accountID) REFERENCES UserAccount (accountID) ON DELETE SET NULL,
  INDEX idx_activitylog_account (accountID, createdAt),
  INDEX idx_activitylog_category (category, createdAt),
  INDEX idx_activitylog_created (createdAt)
);

-- seeding the website content
-- these are the words and photos the public pages had written into them before,
-- so nothing on the site changes until an admin edits it on staff/manageContent.php
INSERT INTO SiteSetting (settingKey, value) VALUES
  ('contactAddress', 'Office 47, No.8 Incubation Drive, Fourways, Gauteng'),
  ('contactPhone', '+27 11 464 5083'),
  ('contactEmail', 'info@likhwezitech.co.za'),
  ('cymSiteURL', 'https://cyberyoungminds.co.za'),
  ('cymParticipants', '255');

INSERT INTO Service (name, tagline, icon, description, includes, sortOrder) VALUES
  ('Enterprise Architecture', 'Tomorrow''s Direction', 'bi-diagram-3',
   'We map how your systems, data and processes fit together, then design a target architecture that supports where the business is heading.',
   'Business Architecture\nApplication Architecture\nData Architecture\nTechnology Architecture', 1),
  ('Strategic Advisory', 'The Game Plan', 'bi-compass',
   'We give unbiased, experienced advice on high-level decisions, helping you shape business and data strategies that deliver real results.',
   'Business & Data Strategy\nCapability Mapping\nBusiness Analysis & Evaluation\nBusiness Process Management\nBusiness Modelling', 2),
  ('Data Management', 'Drive Efficiency', 'bi-database',
   'We put the governance, structures and controls in place to protect your data, keep it accurate and turn it into a business asset.',
   'Data Governance\nData Quality\nData Modelling & Design\nData Warehousing & BI\nData Integration\nData Security\nMaster Data Management', 3),
  ('Data Testing', '20/20 Sight', 'bi-clipboard-check',
   'We validate, verify and qualify your data so every report, system and decision is built on information that is accurate and fit for purpose.',
   'Test Planning & Strategy\nETL & Integration Testing\nDatabase Testing\nPerformance & Security Testing\nData Model Validation\nReport Testing', 4),
  ('Solution Delivery', 'Span the Enterprise', 'bi-rocket-takeoff',
   'We plan, manage and deliver change from first design to final rollout, making sure your organisation actually realises the benefits.',
   'Programme & Project Management\nDemand Planning & Prioritisation\nStakeholder Management\nSolution Design & Implementation', 5);

INSERT INTO CymPhoto (src, altText, sortOrder) VALUES
  ('assets/images/about-img/school-footage.webp', 'Cyber Young Minds school session', 1),
  ('assets/images/about-img/school-footage2.webp', 'Cyber Young Minds school session', 2);
```

*********************************************************************
DB Testing

Use this to quickly check that the database is reachable and set up correctly, without leaving any test data behind.
1. Make sure `config/dbConnection.php` exists and has valid credentials.
2. Save the script below anywhere in the project and open it in a browser, or run it from a terminal with `php config/testDb.php`.
3. Delete the script when you're done testing, it's a throwaway tool, not part of the app.

```php
<?php
require __DIR__ . '/dbConnection.php';

echo "Connected OK to database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "\n\n";

$expected = [
    'UserAccount','Admin','StaffMarketing','StaffCustomerService','ArchivableEntity',
    'Enquiry','Registration','Programme','SiteSetting','Service','CymPhoto','Partner','Report','Campaign','Event','GalleryItem',
    'ArchiveLog','Donation','ActivityLog'
];

echo "Checking tables:\n";
foreach ($expected as $table) {
    $found = $conn->query("SHOW TABLES LIKE '$table'")->num_rows > 0;
    echo ($found ? "  OK      " : "  MISSING ") . $table . "\n";
}

$conn->close();
```

Note: because several tables share their primary key with `ArchivableEntity`, a row must exist in `ArchivableEntity` first before you can insert into those tables. 
If you want to test an actual insert, wrap it in a transaction and roll it back afterward so no test data is left in the database:
```sql
START TRANSACTION;
-- your test INSERT statements here
ROLLBACK;
```