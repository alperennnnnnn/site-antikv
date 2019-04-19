CREATE TABLE IF NOT EXISTS b_socialservices_user
(
	ID INT NOT NULL AUTO_INCREMENT,
	LOGIN VARCHAR(100) NOT NULL,
	NAME VARCHAR(100) NULL,
	LAST_NAME VARCHAR(100) NULL,
	EMAIL VARCHAR(100) NULL,
	PERSONAL_PHOTO INT NULL,
	EXTERNAL_AUTH_ID VARCHAR(100) NOT NULL,
	USER_ID INT NOT NULL,
	XML_ID VARCHAR(100) NOT NULL,
	CAN_DELETE CHAR(1) NOT NULL DEFAULT 'Y',
	PERSONAL_WWW VARCHAR(100) NULL,
	PERMISSIONS VARCHAR(555) NULL,
	OATOKEN VARCHAR(1000) NULL,
	OATOKEN_EXPIRES INT NULL,
	OASECRET VARCHAR(250) NULL,
	REFRESH_TOKEN VARCHAR(1000) NULL,
	SEND_ACTIVITY CHAR(1) NULL DEFAULT 'Y',
	SITE_ID VARCHAR(50) NULL,
	INITIALIZED CHAR(1) NULL DEFAULT 'N',
	PRIMARY KEY (ID),
	UNIQUE INDEX IX_B_SOCIALSERVICES_USER (XML_ID, EXTERNAL_AUTH_ID),
	INDEX IX_B_SOCIALSERVICES_US_1 (USER_ID),
	INDEX IX_B_SOCIALSERVICES_US_3 (LOGIN)
);

CREATE TABLE IF NOT EXISTS  b_socialservices_message
(
	ID INT NOT NULL AUTO_INCREMENT,
	USER_ID INT NOT NULL,
	SOCSERV_USER_ID INT NOT NULL,
	PROVIDER VARCHAR(100) NOT NULL,
	MESSAGE VARCHAR(1000) NULL,
	INSERT_DATE DATETIME NULL,
	SUCCES_SENT CHAR(1) NOT NULL DEFAULT 'N',
	PRIMARY KEY (ID)
);

CREATE TABLE IF NOT EXISTS b_socialservices_user_link
(
	ID INT NOT NULL AUTO_INCREMENT,
	USER_ID INT NOT NULL,
	SOCSERV_USER_ID INT NOT NULL,
	LINK_USER_ID INT NULL,
	LINK_UID VARCHAR(100) NOT NULL,
	LINK_NAME VARCHAR(255) NULL,
	LINK_LAST_NAME VARCHAR(255) NULL,
	LINK_PICTURE VARCHAR(255) NULL,
	LINK_EMAIL VARCHAR(255) NULL,
	TIMESTAMP_X TIMESTAMP NULL,
	PRIMARY KEY(ID),
	INDEX ix_b_socialservices_user_link_5 (SOCSERV_USER_ID),
	INDEX ix_b_socialservices_user_link_6 (LINK_USER_ID, TIMESTAMP_X),
	INDEX ix_b_socialservices_user_link_7 (LINK_UID)
);

CREATE TABLE IF NOT EXISTS b_socialservices_contact
(
	ID INT NOT NULL AUTO_INCREMENT,
	TIMESTAMP_X TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	USER_ID INT NOT NULL,
	CONTACT_USER_ID INT NULL,
	CONTACT_XML_ID INT NULL,
	CONTACT_NAME VARCHAR(255) NULL,
	CONTACT_LAST_NAME VARCHAR(255) NULL,
	CONTACT_PHOTO VARCHAR(255) NULL,
	NOTIFY CHAR(1) NULL DEFAULT 'N',
	PRIMARY KEY (ID),
	INDEX ix_b_socialservices_contact1(USER_ID),
	INDEX ix_b_socialservices_contact2(CONTACT_USER_ID),
	INDEX ix_b_socialservices_contact3(TIMESTAMP_X)
);

CREATE TABLE IF NOT EXISTS b_socialservices_contact_connect
(
	ID INT NOT NULL AUTO_INCREMENT,
	TIMESTAMP_X TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	CONTACT_ID INT NULL,
	LINK_ID INT NULL,
	CONTACT_PROFILE_ID INT NOT NULL,
	CONTACT_PORTAL VARCHAR(255) NOT NULL,
	CONNECT_TYPE CHAR(1) NULL DEFAULT 'P',
	PRIMARY KEY (ID),
	INDEX ix_b_socialservices_contact_connect1(CONTACT_ID),
	INDEX ix_b_socialservices_contact_connect2(LINK_ID)
);