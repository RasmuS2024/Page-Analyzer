
DROP TABLE IF EXISTS url_checks CASCADE;
DROP TABLE IF EXISTS urls CASCADE;

CREATE TABLE IF NOT EXISTS urls (
	id bigserial NOT NULL,
	name varchar(255) NOT NULL,
	created_at timestamp(0) NOT NULL,
	CONSTRAINT urls_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS url_checks (
	id bigserial NOT NULL,
	url_id int8 NOT NULL,
	status_code int4 NULL,
	h1 varchar(1000) NULL,
	title text NULL,
	description text NULL,
	created_at timestamp(0) NOT NULL,
	CONSTRAINT url_checks_pkey PRIMARY KEY (id)
);

 ALTER TABLE public.url_checks
	ADD CONSTRAINT url_checks_url_id_foreign FOREIGN KEY (url_id) REFERENCES public.urls(id);

/*CREATE TABLE IF NOT EXISTS urls (
	id SERIAL PRIMARY KEY,
	name VARCHAR(255) NOT NULL,
	created_at TIMESTAMP NOT NULL
);

CREATE TABLE IF NOT EXISTS url_checks (
	id SERIAL PRIMARY KEY,
	url_id SERIAL NOT NULL,
	status_code SMALLINT NULL,
	h1 VARCHAR(255) NULL,
	title TEXT NULL,
    description TEXT,
	created_at TIMESTAMP NOT NULL,
    FOREIGN KEY (url_id) REFERENCES urls(id) ON DELETE CASCADE
);
*/