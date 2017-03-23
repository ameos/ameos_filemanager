CREATE TABLE tx_ameosfilemanager_domain_model_folder (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,

    uid_parent int(11) DEFAULT '0' NOT NULL,
    storage int(11) DEFAULT '0' NOT NULL,
    identifier text,
    title varchar(255) DEFAULT '' NOT NULL,
    files int(11) DEFAULT '0' NOT NULL,
    folders int(11) DEFAULT '0' NOT NULL,
    # metas
    description text,
    keywords text,
    category int(11) DEFAULT '0' NOT NULL,
    
    no_read_access tinyint(4) DEFAULT '0' NOT NULL,
    no_write_access tinyint(4) DEFAULT '0' NOT NULL,
    owner_has_read_access tinyint(4) DEFAULT '0' NOT NULL,
    owner_has_write_access tinyint(4) DEFAULT '0' NOT NULL,
    
    fe_group_read tinytext NOT NULL,
    fe_group_write tinytext NOT NULL,
    fe_group_addfile tinytext NOT NULL,
    fe_group_addfolder tinytext NOT NULL,
    
    fe_user_id int(11) DEFAULT '0' NOT NULL,
    status int(11) DEFAULT '0' NOT NULL,
    realstatus int(11) DEFAULT '0' NOT NULL,

    cats int(11) DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY fe_user_id (fe_user_id),
    KEY folder_uid (uid_parent),
    KEY status (status),
    KEY realstatus (realstatus)
);


CREATE TABLE tx_ameosfilemanager_domain_model_filedownload (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,

    file int(11) DEFAULT '0' NOT NULL,
    user_download int(11) DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY user_download (user_download)
);

CREATE TABLE sys_file_metadata (
    datetime int(11) unsigned DEFAULT '0' NOT NULL,
    
    no_read_access tinyint(4) DEFAULT '0' NOT NULL,
    no_write_access tinyint(4) DEFAULT '0' NOT NULL,
    owner_has_read_access tinyint(4) DEFAULT '0' NOT NULL,
    owner_has_write_access tinyint(4) DEFAULT '0' NOT NULL,

    fe_group_read tinytext NOT NULL,    
    fe_group_write tinytext NOT NULL,
    
    owner_read_only tinyint(4) DEFAULT '0' NOT NULL,
    
    keywords text,
    fe_user_id int(11) DEFAULT '0' NOT NULL,

    folder_uid int(11) DEFAULT '0' NOT NULL,
    status int(11) DEFAULT '0' NOT NULL,
    realstatus int(11) DEFAULT '0' NOT NULL,

    KEY folder_uid (folder_uid),
    KEY fe_user_id (fe_user_id),
    KEY status (status),
    KEY realstatus (realstatus),
    KEY no_read_access (no_read_access)
);
