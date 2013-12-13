CREATE table if NOT eXISTS request_data(
  id_request_data int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_request_number INT UNSIGNED not null,
  id_field int UNSIGNED NOT null,
  field_value varchar(75)
  )ENGINE myisam;

CREATE TABLE IF NOT EXISTS request_number(
  id_request_number INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user VARCHAR(15) NOT NULL,
  request_date DATETIME NOT NULL,
  request_state TINYINT NOT NULL
) ENGINE MYISAM;


CREATE TABLE IF NOT EXISTS sp_field_data(
  id_field_data int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_field INT UNSIGNED NOT NULL,
  field_data_value VARCHAR(100) NOT NULL
  )ENGINE MYISAM;

CREATE TABLE IF NOT EXISTS sp_field_type(
  id_field_type INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  field_type VARCHAR(20) NOT NULL
) ENGINE MYISAM;

