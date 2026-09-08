-- Migration Blueprint SQL
-- Company is NOT persisted as a standard companies table in v1.

CREATE TABLE service_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE services (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NULL,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  service_code VARCHAR(100) NULL,
  icon VARCHAR(255) NULL,
  short_description TEXT NOT NULL,
  description TEXT NULL,
  content LONGTEXT NOT NULL,
  featured_image VARCHAR(255) NULL,
  gallery_json JSON NULL,
  pricing_type VARCHAR(100) NULL,
  starting_price DECIMAL(15,2) NULL,
  price_note VARCHAR(255) NULL,
  duration_note VARCHAR(255) NULL,
  benefits_json JSON NULL,
  process_steps_json JSON NULL,
  faq_json JSON NULL,
  cta_title VARCHAR(255) NULL,
  cta_description TEXT NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  publish_status VARCHAR(50) NOT NULL,
  published_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_services_category FOREIGN KEY (category_id) REFERENCES service_categories(id)
);

CREATE TABLE projects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  primary_service_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  project_code VARCHAR(100) NULL,
  short_description TEXT NOT NULL,
  description TEXT NULL,
  content LONGTEXT NULL,
  featured_image VARCHAR(255) NULL,
  gallery_json JSON NULL,
  project_type VARCHAR(100) NULL,
  style VARCHAR(100) NULL,
  location_text VARCHAR(255) NULL,
  district VARCHAR(120) NULL,
  city VARCHAR(120) NULL,
  province VARCHAR(120) NULL,
  area_land_m2 DECIMAL(10,2) NULL,
  area_floor_m2 DECIMAL(10,2) NULL,
  floors INT NULL,
  bedrooms INT NULL,
  bathrooms INT NULL,
  budget_min DECIMAL(15,2) NULL,
  budget_max DECIMAL(15,2) NULL,
  construction_time_days INT NULL,
  start_date DATE NULL,
  completion_date DATE NULL,
  client_name VARCHAR(255) NULL,
  materials_json JSON NULL,
  scope_of_work_json JSON NULL,
  before_after_json JSON NULL,
  project_facts_json JSON NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  publish_status VARCHAR(50) NOT NULL,
  published_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_projects_primary_service FOREIGN KEY (primary_service_id) REFERENCES services(id)
);

CREATE TABLE blog_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE blog_posts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NOT NULL,
  author_id BIGINT UNSIGNED NULL,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  excerpt TEXT NOT NULL,
  summary TEXT NULL,
  content LONGTEXT NOT NULL,
  featured_image VARCHAR(255) NULL,
  cover_image VARCHAR(255) NULL,
  reading_time INT NULL,
  source_type VARCHAR(100) NULL,
  content_type VARCHAR(100) NULL,
  intent_type VARCHAR(100) NULL,
  outline_json JSON NULL,
  faq_json JSON NULL,
  references_json JSON NULL,
  cta_service_id BIGINT UNSIGNED NULL,
  canonical_url VARCHAR(255) NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  publish_status VARCHAR(50) NOT NULL,
  published_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_blog_posts_category FOREIGN KEY (category_id) REFERENCES blog_categories(id),
  CONSTRAINT fk_blog_posts_cta_service FOREIGN KEY (cta_service_id) REFERENCES services(id)
);

CREATE TABLE tags (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE blog_post_tag (
  blog_post_id BIGINT UNSIGNED NOT NULL,
  tag_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (blog_post_id, tag_id),
  CONSTRAINT fk_bpt_post FOREIGN KEY (blog_post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_bpt_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

CREATE TABLE blog_post_service (
  blog_post_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (blog_post_id, service_id),
  CONSTRAINT fk_bps_post FOREIGN KEY (blog_post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_bps_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

CREATE TABLE blog_post_project (
  blog_post_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (blog_post_id, project_id),
  CONSTRAINT fk_bpp_post FOREIGN KEY (blog_post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_bpp_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE TABLE project_service (
  project_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (project_id, service_id),
  CONSTRAINT fk_ps_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_ps_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

CREATE TABLE leads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_id BIGINT UNSIGNED NULL,
  project_id BIGINT UNSIGNED NULL,
  source VARCHAR(120) NULL,
  full_name VARCHAR(255) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  email VARCHAR(255) NULL,
  message TEXT NULL,
  budget_range VARCHAR(120) NULL,
  construction_location VARCHAR(255) NULL,
  lead_status VARCHAR(50) NOT NULL,
  assigned_to BIGINT UNSIGNED NULL,
  contacted_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_leads_service FOREIGN KEY (service_id) REFERENCES services(id),
  CONSTRAINT fk_leads_project FOREIGN KEY (project_id) REFERENCES projects(id)
);

CREATE TABLE testimonials (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_id BIGINT UNSIGNED NULL,
  project_id BIGINT UNSIGNED NULL,
  client_name VARCHAR(255) NOT NULL,
  client_role VARCHAR(255) NULL,
  content TEXT NOT NULL,
  rating TINYINT NULL,
  avatar VARCHAR(255) NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  publish_status VARCHAR(50) NOT NULL DEFAULT 'published',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_testimonials_service FOREIGN KEY (service_id) REFERENCES services(id),
  CONSTRAINT fk_testimonials_project FOREIGN KEY (project_id) REFERENCES projects(id)
);

CREATE TABLE faqs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(100) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  question VARCHAR(255) NOT NULL,
  answer TEXT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_faq_entity (entity_type, entity_id)
);

CREATE TABLE seo_meta (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(100) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  meta_title VARCHAR(255) NULL,
  meta_description TEXT NULL,
  meta_keywords TEXT NULL,
  canonical_url VARCHAR(255) NULL,
  robots VARCHAR(100) NULL,
  og_title VARCHAR(255) NULL,
  og_description TEXT NULL,
  og_image VARCHAR(255) NULL,
  twitter_title VARCHAR(255) NULL,
  twitter_description TEXT NULL,
  twitter_image VARCHAR(255) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_seo_meta_entity (entity_type, entity_id)
);

CREATE TABLE seo_schema (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(100) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  schema_type VARCHAR(100) NOT NULL,
  schema_json LONGTEXT NOT NULL,
  is_auto_generated TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_seo_schema_entity (entity_type, entity_id)
);

CREATE TABLE internal_links (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_type VARCHAR(100) NOT NULL,
  source_id BIGINT UNSIGNED NOT NULL,
  target_type VARCHAR(100) NOT NULL,
  target_id BIGINT UNSIGNED NOT NULL,
  anchor_text VARCHAR(255) NOT NULL,
  link_position VARCHAR(100) NULL,
  is_auto_generated TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_internal_links_source (source_type, source_id),
  INDEX idx_internal_links_target (target_type, target_id)
);
