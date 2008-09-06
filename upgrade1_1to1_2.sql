// Here are the SQL Statements upgrade scripts needed for version 1.1 
// to upgrade wpClassified tables. 
// You should replace the {table_prefix} with $table_prefix entered in wp_config.php 
// begin
ALTER TABLE {table_prefix}wpClassified_ads_subjects add email varchar(64) NOT NULL;
ALTER TABLE {table_prefix}wpClassified_ads_subjects add location varchar(64);
ALTER TABLE {table_prefix}wpClassified_ads_subjects add fax varchar(64);
ALTER TABLE {table_prefix}wpClassified_ads_subjects add web varchar(64);
ALTER TABLE {table_prefix}wpClassified_ads_subjects add phone varchar(64);
ALTER TABLE {table_prefix}wpClassified_ads_subjects add txt varchar(255);
// end
