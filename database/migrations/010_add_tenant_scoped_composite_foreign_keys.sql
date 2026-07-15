-- Составные FK, привязывающие ссылку не только к id родителя, но и к тому же
-- user_id: БД физически не даёт вставить/обновить wallet.account_id,
-- category.parent_id, transaction.wallet_id/category_id на запись другого
-- пользователя, даже если сервисный слой это не проверит.
--
-- account_id и parent_id ранее были ON DELETE SET NULL — для составного FK
-- это несовместимо с NOT NULL user_id (SET NULL пришлось бы применить ко всей
-- связке колонок разом, включая user_id), поэтому здесь они становятся
-- ON DELETE RESTRICT (см. AccountService/CategoryService::delete()).
--
-- transaction_tag не входит в эту миграцию: это чистая junction-таблица без
-- user_id, составной FK для неё потребовал бы денормализации; связь
-- транзакция-тег остаётся под защитой только сервисного слоя
-- (TransactionService::assertTagsOwnership()).

ALTER TABLE accounts ADD UNIQUE KEY uniq_accounts_id_user_id (id, user_id);
ALTER TABLE wallets ADD UNIQUE KEY uniq_wallets_id_user_id (id, user_id);
ALTER TABLE categories ADD UNIQUE KEY uniq_categories_id_user_id (id, user_id);

ALTER TABLE wallets DROP FOREIGN KEY fk_wallets_account;
ALTER TABLE wallets ADD CONSTRAINT fk_wallets_account
    FOREIGN KEY (account_id, user_id) REFERENCES accounts (id, user_id) ON DELETE RESTRICT;

ALTER TABLE categories DROP FOREIGN KEY fk_categories_parent;
ALTER TABLE categories ADD CONSTRAINT fk_categories_parent
    FOREIGN KEY (parent_id, user_id) REFERENCES categories (id, user_id) ON DELETE RESTRICT;

ALTER TABLE transactions DROP FOREIGN KEY fk_transactions_wallet;
ALTER TABLE transactions ADD CONSTRAINT fk_transactions_wallet
    FOREIGN KEY (wallet_id, user_id) REFERENCES wallets (id, user_id) ON DELETE CASCADE;

ALTER TABLE transactions DROP FOREIGN KEY fk_transactions_category;
ALTER TABLE transactions ADD CONSTRAINT fk_transactions_category
    FOREIGN KEY (category_id, user_id) REFERENCES categories (id, user_id) ON DELETE RESTRICT;
