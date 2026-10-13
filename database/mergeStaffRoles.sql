-- mergeStaffRoles.sql joins the marketing and customer service roles into one staff role
-- run it once on a database made before the change, database/dbSetup.md already has the new table

-- one table for every staff member
CREATE TABLE IF NOT EXISTS Staff (
  accountID int PRIMARY KEY NOT NULL,
  FOREIGN KEY (accountID) REFERENCES UserAccount (accountID) ON DELETE CASCADE
);

-- move everyone from the old tables across
INSERT IGNORE INTO Staff (accountID)
SELECT accountID FROM StaffMarketing
UNION
SELECT accountID FROM StaffCustomerService;

-- claimed enquiries now point at the staff table
ALTER TABLE Enquiry DROP FOREIGN KEY fk_enquiry_handledby;
ALTER TABLE Enquiry ADD CONSTRAINT fk_enquiry_handledby
  FOREIGN KEY (handledBy) REFERENCES Staff (accountID) ON DELETE SET NULL;

-- the old tables are no longer used
DROP TABLE StaffMarketing;
DROP TABLE StaffCustomerService;
