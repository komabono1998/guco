-- 030: privacy-friendly visitor statistics. No raw IP addresses are stored: a visitor is a keyed hash.

CREATE TABLE IF NOT EXISTS site_visits (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  visited_at DATETIME NOT NULL,
  visitor CHAR(16) NOT NULL,
  page VARCHAR(190) NOT NULL,
  title VARCHAR(160) NULL,
  country CHAR(2) NULL,
  device ENUM('desktop','mobile','tablet') NOT NULL DEFAULT 'desktop',
  referrer VARCHAR(120) NULL,
  PRIMARY KEY (id),
  KEY idx_visit_time (visited_at),
  KEY idx_visit_visitor (visitor),
  KEY idx_visit_page (page),
  KEY idx_visit_country (country)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ip_country_cache (
  ip_hash CHAR(32) NOT NULL,
  country CHAR(2) NULL,
  checked_at DATETIME NOT NULL,
  PRIMARY KEY (ip_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
