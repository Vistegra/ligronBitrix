-- ==============================================================
-- 1. Таблица статусов
-- ==============================================================
CREATE TABLE vs_e_order_status (
                                    ID      INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                    SORT    INT UNSIGNED NOT NULL DEFAULT 500,
                                    CODE    VARCHAR(10) NOT NULL,
                                    NAME    VARCHAR(20) NOT NULL,
                                    COLOR   CHAR(7) NULL DEFAULT NULL COMMENT 'HEX, например #22C55E',

                                    PRIMARY KEY (ID),
                                    UNIQUE KEY ux_vs_e_order_status_code (CODE),
                                    KEY ix_vs_e_order_status_sort (SORT)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Стандартные статусы
INSERT INTO vs_e_order_status (SORT, CODE, NAME, COLOR) VALUES

                                                             (200, 'PENDING',    'На рассмотрении', '#0284C7'),
                                                             (300, 'IN_WORK',    'В процессе',      '#F59E0B'),
                                                             (400, 'PRODUCTION', 'На производстве', '#0284C7'),
                                                             (500, 'AWAITING',   'Ожидает доставки','#5EEAD4'),
                                                             (600, 'COMPLETED',  'Завершён',        '#22C55E'),
                                                             (700, 'CANCELED',   'Отменён',         '#ED0F51');



-- ==============================================================
-- 2. Основная таблица заказов
-- ==============================================================
CREATE TABLE vs_e_order (
                            ID              INT UNSIGNED NOT NULL AUTO_INCREMENT,
                            NUMBER          VARCHAR(50) NULL DEFAULT NULL,
                            NAME            VARCHAR(255) NOT NULL,

                            STATUS_ID       INT UNSIGNED NULL DEFAULT NULL,
                            PARENT_ID       INT UNSIGNED NULL DEFAULT NULL,

    -- кто реально нажал кнопку «Создать»
                            CREATED_BY      TINYINT UNSIGNED DEFAULT NULL
        COMMENT '1 - dealer, 2 - manager',
                            CREATED_BY_ID   INT UNSIGNED NOT NULL,

    -- привязки
                            DEALER_PREFIX   VARCHAR(10) NULL DEFAULT NULL
        COMMENT 'префикс таблицы: dea_, pro_ и т.д.',
                            DEALER_USER_ID  INT UNSIGNED NULL DEFAULT NULL
        COMMENT 'ID в calc.{DEALER_PREFIX}users',
                            MANAGER_ID      INT UNSIGNED NULL DEFAULT NULL
        COMMENT 'webcalc.user.id',

                            STATUS_HISTORY  JSON NULL DEFAULT NULL,
                            PRODUCTION_TIME INT UNSIGNED NULL DEFAULT NULL COMMENT 'дней',
                            READY_DATE      DATE NULL DEFAULT NULL,
                            COMMENT         TEXT NULL DEFAULT NULL,

                            CHILDREN_COUNT  INT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'счётчик дочерних заказов',
                            PERCENT_PAYMENT TINYINT UNSIGNED NULL DEFAULT 0 COMMENT 'Процент оплаты';

                            -- тип происхождения заказа
                            ORIGIN_TYPE     TINYINT UNSIGNED DEFAULT 0 COMMENT '0=APP, 1=1C, 2=CALC',

                            CREATED_AT      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            UPDATED_AT      DATETIME NOT NULL
                                                              DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                            PRIMARY KEY (ID),
                            UNIQUE KEY ux_vs_e_order_number (NUMBER),
                            INDEX ix_status (STATUS_ID),
                            INDEX ix_parent (PARENT_ID),
                            INDEX ix_dealer_prefix (DEALER_PREFIX),
                            INDEX ix_dealer_user (DEALER_USER_ID),
                            INDEX ix_dealer_combined (DEALER_PREFIX, DEALER_USER_ID),
                            INDEX ix_manager (MANAGER_ID),
                            INDEX ix_ready_date (READY_DATE),
                            INDEX ix_created (CREATED_AT),
                            INDEX ix_created_by (CREATED_BY, CREATED_BY_ID),
                            INDEX ix_origin_type (ORIGIN_TYPE),

                            CONSTRAINT fk_vs_e_order_status
                                FOREIGN KEY (STATUS_ID) REFERENCES vs_e_order_status(ID)
                                    ON DELETE SET NULL,

                            CONSTRAINT fk_vs_e_order_parent
                                FOREIGN KEY (PARENT_ID) REFERENCES vs_e_order(ID)
                                    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==============================================================
-- 3. Триггеры для CHILDREN_COUNT
-- ==============================================================

-- 3.1 После добавления дочернего заказа
-- CREATE TRIGGER trg_vs_e_order_after_insert
--     AFTER INSERT ON vs_e_order
--     FOR EACH ROW
--     UPDATE vs_e_order
--     SET CHILDREN_COUNT = CHILDREN_COUNT + 1
--     WHERE ID = NEW.PARENT_ID
--       AND NEW.PARENT_ID IS NOT NULL

-- 3.2 После удаления дочернего заказа
-- CREATE TRIGGER trg_vs_e_order_after_delete
--     AFTER DELETE ON vs_e_order
--     FOR EACH ROW
--     UPDATE vs_e_order
--     SET CHILDREN_COUNT = CHILDREN_COUNT - 1
--     WHERE ID = OLD.PARENT_ID
--       AND OLD.PARENT_ID IS NOT NULL
--       AND CHILDREN_COUNT > 0


-- ==============================================================
-- 4. Таблица файлов
-- ==============================================================
CREATE TABLE vs_e_order_file (
                                 ID             INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                 ORDER_ID       INT UNSIGNED NOT NULL,

                                 NAME           VARCHAR(255) NOT NULL,
                                 PATH           VARCHAR(512) NOT NULL,
                                 SIZE           BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Размер в байтах',
                                 MIME           VARCHAR(100) NULL DEFAULT NULL,

                                 UPLOADED_BY    TINYINT UNSIGNED DEFAULT NULL,
                                 UPLOADED_BY_ID INT UNSIGNED NOT NULL,

                                 CREATED_AT     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                                 PRIMARY KEY (ID),

                                 INDEX ix_order (ORDER_ID),
                                 INDEX ix_uploaded (UPLOADED_BY, UPLOADED_BY_ID),
                                 INDEX ix_created (CREATED_AT),

                                 CONSTRAINT fk_vs_e_order_file_order
                                     FOREIGN KEY (ORDER_ID) REFERENCES vs_e_order(ID)
                                         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.1 Триггер после добавления
CREATE TRIGGER trg_vs_e_order_file_after_insert
    AFTER INSERT ON vs_e_order_file
    FOR EACH ROW
    UPDATE vs_e_order
    SET UPDATED_AT = CURRENT_TIMESTAMP
    WHERE ID = NEW.ORDER_ID

-- 4.1 Триггер после удаления
CREATE TRIGGER trg_vs_e_order_file_after_delete
    AFTER DELETE ON vs_e_order_file
    FOR EACH ROW
    UPDATE vs_e_order
    SET UPDATED_AT = CURRENT_TIMESTAMP
    WHERE ID = OLD.ORDER_ID

-- ==============================================================
-- 5. Таблица черновиков заказов (упрощённая версия)
-- ==============================================================
CREATE TABLE vs_e_order_draft (
                                  ID             INT UNSIGNED NOT NULL AUTO_INCREMENT,

                                  NAME           VARCHAR(255) NOT NULL,
                                  TYPE           TINYINT UNSIGNED NOT NULL DEFAULT 0
        COMMENT '0=стандарт, 1=спец, 2=сервис',

    -- кто реально нажал кнопку «Создать черновик»
                                  CREATED_BY     ENUM('dealer','manager') NOT NULL DEFAULT 'dealer',
                                  CREATED_BY_ID  INT UNSIGNED NOT NULL,

    -- привязки (как в основном заказе)
                                  DEALER_ID      INT UNSIGNED NULL DEFAULT NULL COMMENT 'calc.dealers.ID',
                                  DEALER_USER_ID INT UNSIGNED NULL DEFAULT NULL COMMENT 'calc.{prefix}users.ID',
                                  MANAGER_ID     INT UNSIGNED NULL DEFAULT NULL COMMENT 'webcalc.user.id',

                                  COMMENT        TEXT NULL DEFAULT NULL,

                                  CREATED_AT     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                  UPDATED_AT     DATETIME NOT NULL
                                                                   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                                  PRIMARY KEY (ID),

                                  INDEX ix_dealer        (DEALER_ID),
                                  INDEX ix_dealer_user   (DEALER_USER_ID),
                                  INDEX ix_manager       (MANAGER_ID),
                                  INDEX ix_created_by    (CREATED_BY, CREATED_BY_ID),
                                  INDEX ix_created       (CREATED_AT),
                                  INDEX ix_type          (TYPE)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Черновики заказов (упрощённая версия)';


-- ==============================================================
-- Удаление всех триггеров и таблиц в правильном порядке
-- ==============================================================

-- Отключаем проверку внешних ключей для безопасного удаления
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Сначала удаляем триггеры для таблицы файлов
DROP TRIGGER IF EXISTS trg_vs_e_order_file_after_insert;
DROP TRIGGER IF EXISTS trg_vs_e_order_file_after_delete;

-- 2. Затем удаляем триггеры для основной таблицы заказов
DROP TRIGGER IF EXISTS trg_vs_e_order_after_insert;
DROP TRIGGER IF EXISTS trg_vs_e_order_after_delete;

-- 3. Удаляем таблицы в порядке зависимостей (сначала дочерние, потом родительские)
DROP TABLE IF EXISTS vs_e_order_file;
DROP TABLE IF EXISTS vs_e_order;
DROP TABLE IF EXISTS vs_e_order_status;

-- Включаем проверку внешних ключей обратно
SET FOREIGN_KEY_CHECKS = 1;