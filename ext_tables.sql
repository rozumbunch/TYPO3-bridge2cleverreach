#
# Table structure for table 'tx_bridge2cleverreach_domain_model_dynamic_content_settings'
#
CREATE TABLE tx_bridge2cleverreach_domain_model_dynamic_content_settings (
    uid int(11) NOT NULL auto_increment,
    pid int(11)  DEFAULT '0' NOT NULL,
    type varchar(255) DEFAULT '' NOT NULL,
    news text,
    data text,

    PRIMARY KEY (uid),
    KEY parent (pid)
);