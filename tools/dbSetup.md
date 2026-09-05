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
- Partner
- Report
- Campaign
- Event
- GalleryItem
- ArchiveLog
- Donation

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
  email VARCHAR(255) UNIQUE NOT NULL,
  username VARCHAR(255) UNIQUE NOT NULL,
  passwordHash VARCHAR(255) NOT NULL,
  dateCreated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  accountStatus ENUM('active', 'suspended', 'deactivated') NOT NULL DEFAULT 'active',
  FOREIGN KEY (createdBy) REFERENCES UserAccount (accountID) ON DELETE SET NULL,
  INDEX idx_useraccount_status (accountStatus)
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
  FOREIGN KEY (handledBy) REFERENCES StaffCustomerService (accountID) ON DELETE CASCADE
);

CREATE TABLE Registration (
  registrationID int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  firstName VARCHAR(100) NOT NULL,
  lastName VARCHAR(100) NOT NULL,
  age int NOT NULL CHECK (age>=1 AND age<=100),
  email VARCHAR(255) NOT NULL,
  phoneNumber VARCHAR(30) NULL,
  programme VARCHAR(255) NOT NULL,
  consentConfirmation BOOLEAN NOT NULL DEFAULT FALSE,
  consentGivenAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guardianName VARCHAR(150) NULL,
  guardianRelationship VARCHAR(100) NULL,
  guardianEmail VARCHAR(150) NULL,
  guardianPhoneNumber VARCHAR(30) NULL,
  guardianConsentConfirmation BOOLEAN NULL DEFAULT FALSE,
  guardianConsentGivenAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  isArchived BOOLEAN NOT NULL DEFAULT FALSE,
  FOREIGN KEY (registrationID) REFERENCES ArchivableEntity (entityID) ON DELETE CASCADE
);

CREATE TABLE Partner (
  partnerID int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  logo VARCHAR(255) NOT NULL,
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
  FOREIGN KEY (createdBy) REFERENCES StaffMarketing (accountID),
  FOREIGN KEY (campaignID) REFERENCES ArchivableEntity (entityID) ON DELETE CASCADE
);

CREATE TABLE Event (
  eventID int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  createdBy int NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  eventDate DATE NULL,
  isArchived BOOLEAN NOT NULL DEFAULT FALSE,
  FOREIGN KEY (createdBy) REFERENCES StaffMarketing (accountID),
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
  FOREIGN KEY (campaignID) REFERENCES Campaign (campaignID)
);
```

*********************************************************************
DB Testing

Use this to quickly check that the database is reachable and set up correctly, without leaving any test data behind.
1. Make sure `tools/dbConnection.php` exists and has valid credentials.
2. Save the script below anywhere in the project and open it in a browser, or run it from a terminal with `php tools/testDb.php`.
3. Delete the script when you're done testing, it's a throwaway tool, not part of the app.

```php
<?php
require __DIR__ . '/dbConnection.php';

echo "Connected OK to database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "\n\n";

$expected = [
    'UserAccount','Admin','StaffMarketing','StaffCustomerService','ArchivableEntity',
    'Enquiry','Registration','Partner','Report','Campaign','Event','GalleryItem',
    'ArchiveLog','Donation'
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