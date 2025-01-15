
Usage:
- Rename config_sample.php to config_php
- fill in mysql, SF credentials
- execute init.sql in appropriate database



Notes:

usage 
realcon.php?action=___

Possible values for $action
- default, view
- retrieve
    retrieve data from SF 
- create
    retrieve data from SF and then insert into db
    specify reason: manual, crontab, cdc

CREATE TABLE `JB_template` ( `id` VARCHAR(32) NOT NULL , `Name` VARCHAR(255) NOT NULL , `CompanyName__c` VARCHAR(255) NOT NULL , `Job_Classify__c` VARCHAR(255) NOT NULL , `CS_Checked__c` TINYINT(1) NOT NULL , `Client_ID__c` VARCHAR(255) NOT NULL ) ENGINE = InnoDB;


CREATE TABLE `JI_template` ( `id` VARCHAR(32) NOT NULL , `Name` VARCHAR(255) NOT NULL , `Bulk_Date__c` VARCHAR(255) NOT NULL , `Job___c` VARCHAR(255) NOT NULL) ENGINE = InnoDB;

TRUNCATE `batch`




CDC related (20240212)
- cd cometd
- npm install dotenv axios jsforce
- node cometd
Reference
https://gaogang.wordpress.com/2020/03/31/subscribe-to-salesforce-streaming-api-in-nodejs/