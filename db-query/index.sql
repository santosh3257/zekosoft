ALTER TABLE `companies` CHANGE `company_name` `company_name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;

ALTER TABLE `companies` CHANGE `app_name` `app_name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;

ALTER TABLE `companies` CHANGE `address` `address` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;

ALTER TABLE `users` CHANGE `name` `name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;

ALTER TABLE `companies` CHANGE `bankgigo` `bankgiro` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

ALTER TABLE `company_addresses` CHANGE `address` `address` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

ALTER TABLE `universal_search` CHANGE `title` `title` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

ALTER TABLE `companies` CHANGE `origination_no` `origination_number` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

ALTER TABLE `companies` ADD `otp_verify` ENUM('true','false') NOT NULL DEFAULT 'false' AFTER `address`;

php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_04_26_070336_create_users_otp_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_04_27_043624_add_multiple_column_to_company.php
-- php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_07_10_092146_create_manager_details_table.php
-- php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_07_10_092234_create_bookkeeper_details_table.php
-- php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_07_10_092312_create_contractor_details_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_07_11_064252_create_articles_tables.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_07_12_070737_create_forgot_password_otp_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_07_14_050009_create_project_articles_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_08_16_085052_create_vendors_table.php
php artisan migrate --path=database/migrations/2023_08_21_111023_create_tax_account_numbers_table.php
php artisan migrate --path=database/migrations/2023_08_29_071713_house_services.php
php artisan migrate --path=database/migrations/2023_08_29_072459_create_house_works_table.php
php artisan migrate --path=database/migrations/2023_08_29_080201_create_vat_types_table.php
php artisan migrate --path=database/migrations/2023_08_29_080845_add_tax_type_to_taxes_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_09_04_055317_add_date_of_issue_to_estimates_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_09_04_060143_add_house_service_to_estimates_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_09_04_061709_add_vat_type_id_to_estimates_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_09_04_064619_add_house_work_id_to_estimate_items_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_09_04_064954_add_house_work_tax_applicable_to_estimate_items_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_09_04_070236_add_language_id_to_estimates_table.php
//migration by santosh
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_09_14_085844_add_vendor_setting_column_to_vendors.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_09_19_054249_create_estimate_emails_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_09_25_115019_create_bills_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_09_25_115833_create_bill_items_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_10_09_100555_create_add_bill_payments_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_11_15_063211_create_invoice_payments_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_11_15_114906_create_invoice_emails_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_11_23_110102_create_client_co_applicants_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_11_24_122051_create_house_work_tax_reduction_table.php
php artisan migrate --path=Modules/RestAPI/Database/Migrations/2023_12_11_093602_add_columnname_to_project_time_logs_table.php

ALTER TABLE `open_banking_tokens` CHANGE `token_for` `token_for` ENUM('bank_details_token','account_information_token','paymentinitiation') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bank_details_token';
