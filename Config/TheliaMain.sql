
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- api
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `api`;

CREATE TABLE `api`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `label` VARCHAR(255),
    `api_key` VARCHAR(100),
    `profile_id` INTEGER,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    INDEX `idx_api_profile_id` (`profile_id`),
    CONSTRAINT `fk_api_profile_id`
        FOREIGN KEY (`profile_id`)
        REFERENCES `profile` (`id`)
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
