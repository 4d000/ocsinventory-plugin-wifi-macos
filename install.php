<?php
/**
 * This function is called on installation and is used to 
 * create database schema for the plugin, including cleanup queue, trigger, and event.
 */
function extension_install_wifi()
{
    $commonObject = new ExtensionCommon;

    // Drop main wifi table if exists
    $commonObject->sqlQuery("DROP TABLE IF EXISTS `wifi`;");
    // Create main wifi table
    $commonObject->sqlQuery(
        "CREATE TABLE wifi (
            ID INT UNSIGNED NOT NULL AUTO_INCREMENT, 
            HARDWARE_ID INT UNSIGNED NOT NULL,
            SSID VARCHAR(255) DEFAULT NULL,
            IP VARCHAR(255) DEFAULT NULL,
            MAC VARCHAR(255) DEFAULT NULL,
	    created_on DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (ID, HARDWARE_ID)
        ) ENGINE=INNODB;"
    );

    // Create the cleanup queue table
    $commonObject->sqlQuery("DROP TABLE IF EXISTS `wifi_cleanup_queue`;");
    $commonObject->sqlQuery(
        "CREATE TABLE wifi_cleanup_queue (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            wifi_id INT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );"
    );

    // Drop trigger if exists (in case of reinstall)
    $commonObject->sqlQuery("DROP TRIGGER IF EXISTS wifi_after_insert;");

    // Create the trigger using a multi-query because of the delimiter
    $triggerSQL = "
    CREATE TRIGGER wifi_after_insert
    AFTER INSERT ON wifi
    FOR EACH ROW
    BEGIN
		IF NEW.SSID = '' THEN
			INSERT INTO wifi_cleanup_queue (wifi_id)
			VALUES (NEW.id);
		END IF;
      INSERT INTO wifi_cleanup_queue (wifi_id)
      SELECT id
      FROM wifi
      WHERE SSID = NEW.SSID
        AND hardware_id = NEW.hardware_id
        AND id != NEW.ID
        AND id NOT IN (SELECT wifi_id FROM wifi_cleanup_queue);
    END;
    ";
    $commonObject->sqlQuery($triggerSQL);

    // Drop event if exists
    $commonObject->sqlQuery("DROP EVENT IF EXISTS wifi_cleanup_event;");

    // Create the event
    $eventSQL = "
    CREATE EVENT wifi_cleanup_event
    ON SCHEDULE EVERY 5 MINUTE
    DO
    BEGIN
      DELETE w FROM wifi w
      INNER JOIN wifi_cleanup_queue q ON w.ID = q.wifi_id;

      DELETE FROM wifi_cleanup_queue;
    END;
    ";
    $commonObject->sqlQuery($eventSQL);
}

/**
 * This function is called on removal and is used to 
 * destroy database schema for the plugin including trigger and event.
 */
function extension_delete_wifi()
{
    $commonObject = new ExtensionCommon;

    // Drop event
    $commonObject->sqlQuery("DROP EVENT IF EXISTS wifi_cleanup_event;");

    // Drop trigger
    $commonObject->sqlQuery("DROP TRIGGER IF EXISTS wifi_after_insert;");

    // Drop cleanup queue table
    $commonObject->sqlQuery("DROP TABLE IF EXISTS `wifi_cleanup_queue`;");

    // Drop main wifi table
    $commonObject->sqlQuery("DROP TABLE IF EXISTS `wifi`;");
}


function extension_upgrade_wifi()
{
    // Implement upgrade logic if needed
}
?>
