Description: This file holds the SQL used to create the database, including the table definitions (names, columns, and relationships) and any setup statements needed to get the schema up and running.

Tables
______________________________
Example: 
Table name
- attributes , types



SQL
______________________________
- creating the database
CREATE DATABASE LikhweziTechDB;

- creating the tables
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
  entityID int PRIMARY KEY NOT NULL,
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
  partnerID int PRIMARY KEY NOT NULL,
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
  eventID int NOT NULL,
  image VARCHAR(500) NOT NULL,
  FOREIGN KEY (eventID) REFERENCES Event (eventID) ON DELETE CASCADE,
  FOREIGN KEY (galleryItemID) REFERENCES ArchivableEntity (entityID) ON DELETE CASCADE
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