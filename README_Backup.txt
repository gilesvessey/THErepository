Backup
======
Go to Manage > Configuration > Development > Backup and Migrate

For this system, you will find the Backup and Migrate module has been set up here

Settings is to setup the backup destinations, sources and profiles:
In order to setup private file path for drupal 8, or if you want to change a new private
file path, you will need to go into site folder /root/sites/default to find the settings.php file,
it is set as read only by default, and change its
permission to allow editing, once you reach line 552, you will find
$settings['file_private_path'] =
and
'sites/default/files/private';  has been set up by default, rather than blank
To confirm if you have done correctly, go to Configuration > Media

There are 3 differently backup sources you will need to look at:
Default Drupal Database - the default database where you store data at
Public File Directory - It contains files that you upload, such as images
Entire Site - Database, files and modules used on site and public file directory
 
There are 2 main stream to backup a system:
Quick & Advanced backup -  This option will be taken effect immidately
You can utilize this option to backup in a private storage or network e.g. personal computer
Schedules - This option will be run automatically routinely;
the best part of this function is to be able to have multiple copies in different time frame.
There are 3 Schedule setup that has been set up for your convinence:
Daily Database backup, and Bi-monthly entire site and public files backup 

Naming format - backup timestamp(Y-M-d\TH-i-s).filetype(GZip)

Restore
=======
There are 2 Options to restore the system here.
From the Restore tab, you can upload a file from your private storage here,
and disable the site in the process

From the Saved Backups tab, you can directly overwrite any part based on the backup date.
The saved backup copies can be restore as mentioned, download and delete.
