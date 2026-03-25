ALTER TABLE vs_e_order
    ADD COLUMN INN_DEALER VARCHAR(20) NULL DEFAULT NULL COMMENT 'ИНН дилера' AFTER DEALER_USER_ID,
    ADD COLUMN SALON_CODE VARCHAR(50) NULL DEFAULT NULL COMMENT 'Код салона' AFTER INN_DEALER,
    ADD INDEX ix_inn_dealer (INN_DEALER),
    ADD INDEX ix_salon_code (SALON_CODE);


ALTER TABLE vs_e_order
    -- ИД автора вместо старого created_by_id
    ADD COLUMN AUTHOR_ID INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'ID автора из новых таблиц V2 (dealer_users / ligron_users)' AFTER CREATED_BY,

    -- Индексы для быстрого поиска
    ADD INDEX ix_author (AUTHOR_ID, CREATED_BY);


ALTER TABLE vs_e_order
    MODIFY COLUMN CREATED_BY_ID INT UNSIGNED NULL DEFAULT NULL;