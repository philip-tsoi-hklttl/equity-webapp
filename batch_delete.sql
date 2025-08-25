BEGIN
    DECLARE drop_stmt TEXT;

    -- Generate the drop statement for tables not containing "batch" at the start
    SELECT GROUP_CONCAT(CONCAT('DROP TABLE `', table_schema, '`.`', table_name, '`;') SEPARATOR ' ')
    INTO drop_stmt
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
    AND table_name NOT LIKE 'batch%';

    -- Output the generated drop statements
    -- SELECT drop_stmt AS GeneratedDropStatements;

    -- Execute the drop statement if it is not NULL
    IF drop_stmt IS NOT NULL THEN
        SET @stmt = drop_stmt;
        PREPARE stmt FROM @stmt;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END;
