CREATE TABLE leads (
    id BIGINT PRIMARY KEY,
    status_id BIGINT,
    price INT,
    responsible_user_id BIGINT,
    pipeline_id BIGINT,
    account_id BIGINT,
    created_user_id BIGINT,
    modified_user_id BIGINT,
    created_at INT,
    updated_at INT
);

CREATE TABLE contacts (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    responsible_user_id BIGINT,
    account_id BIGINT,
    created_user_id BIGINT,
    modified_user_id BIGINT,
    created_at INT,
    updated_at INT,
    company_name VARCHAR(255),
    linked_company_id BIGINT,
    type VARCHAR(50)
);
