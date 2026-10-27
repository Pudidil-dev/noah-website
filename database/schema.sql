-- NOAH Official Website - Basis Data (PostgreSQL 16)

CREATE TYPE admin_role        AS ENUM ('super_admin', 'editor');
CREATE TYPE milestone_type    AS ENUM ('history', 'award');
CREATE TYPE album_type        AS ENUM ('studio', 'kompilasi', 'soundtrack');
CREATE TYPE album_era         AS ENUM ('peterpan', 'noah');
CREATE TYPE event_type        AS ENUM ('concert', 'community');
CREATE TYPE publish_status    AS ENUM ('draft', 'published', 'archived');
CREATE TYPE order_status      AS ENUM ('pending', 'paid', 'cancelled');
CREATE TYPE post_category     AS ENUM ('news', 'behind_scenes');
CREATE TYPE media_type        AS ENUM ('photo', 'video', 'press_kit');
CREATE TYPE product_category  AS ENUM ('kaos', 'hoodie', 'aksesoris', 'musik');
CREATE TYPE submission_type   AS ENUM ('story', 'artwork', 'cover');
CREATE TYPE review_status     AS ENUM ('pending', 'approved', 'rejected');

CREATE TABLE admins (
  id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          admin_role   NOT NULL DEFAULT 'editor',
  created_at    TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE TABLE members (
  id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  position    VARCHAR(50)  NOT NULL,
  bio         TEXT,
  photo_url   VARCHAR(255),
  sort_order  SMALLINT     NOT NULL DEFAULT 0,
  is_active   BOOLEAN      NOT NULL DEFAULT TRUE
);

-- history: tampil di timeline (pakai year). award: tampil di statistik (pakai badge, mis. "3x").
CREATE TABLE milestones (
  id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  year        SMALLINT       CHECK (year IS NULL OR year BETWEEN 1990 AND 2100),
  badge       VARCHAR(20),
  title       VARCHAR(150)   NOT NULL,
  description TEXT,
  type        milestone_type NOT NULL DEFAULT 'history',
  image_url   VARCHAR(255),
  sort_order  SMALLINT       NOT NULL DEFAULT 0
);

CREATE TABLE albums (
  id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  title           VARCHAR(150) NOT NULL,
  slug            VARCHAR(160) NOT NULL UNIQUE,
  type            album_type   NOT NULL DEFAULT 'studio',
  era             album_era    NOT NULL DEFAULT 'noah',
  release_date    DATE,
  total_tracks    SMALLINT     CHECK (total_tracks > 0),
  cover_url       VARCHAR(255),
  is_featured     BOOLEAN      NOT NULL DEFAULT FALSE,
  description     TEXT,
  streaming_links JSONB        NOT NULL DEFAULT '{}'
);

-- album_id NULL = lagu lepas (mis. hanya muncul di setlist). single_year terisi = tampil di tabel Single.
CREATE TABLE tracks (
  id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  album_id     BIGINT       REFERENCES albums(id) ON DELETE SET NULL,
  track_no     SMALLINT     CHECK (track_no > 0),
  title        VARCHAR(150) NOT NULL,
  duration_sec INTEGER      CHECK (duration_sec > 0),
  single_year  SMALLINT     CHECK (single_year IS NULL OR single_year BETWEEN 1990 AND 2100),
  UNIQUE (album_id, track_no)
);

CREATE TABLE communities (
  id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  city         VARCHAR(80)  NOT NULL UNIQUE,
  name         VARCHAR(120) NOT NULL,
  leader_name  VARCHAR(100),
  contact_url  VARCHAR(255),
  member_count INTEGER      NOT NULL DEFAULT 0 CHECK (member_count >= 0)
);

CREATE TABLE events (
  id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  community_id BIGINT         REFERENCES communities(id) ON DELETE SET NULL,
  title        VARCHAR(150)   NOT NULL,
  slug         VARCHAR(160)   NOT NULL UNIQUE,
  type         event_type     NOT NULL DEFAULT 'concert',
  badge        VARCHAR(40),
  city         VARCHAR(80)    NOT NULL,
  venue        VARCHAR(150),
  start_at     TIMESTAMPTZ    NOT NULL,
  description  TEXT,
  maps_url     VARCHAR(500),
  poster_url   VARCHAR(255),
  ticket_price NUMERIC(12,2)  CHECK (ticket_price >= 0),
  quota        INTEGER        CHECK (quota >= 0),
  status       publish_status NOT NULL DEFAULT 'draft'
);
CREATE INDEX idx_events_start_at ON events (start_at);

CREATE TABLE setlist_items (
  event_id  BIGINT   NOT NULL REFERENCES events(id) ON DELETE CASCADE,
  position  SMALLINT NOT NULL CHECK (position > 0),
  track_id  BIGINT   NOT NULL REFERENCES tracks(id) ON DELETE RESTRICT,
  is_encore BOOLEAN  NOT NULL DEFAULT FALSE,
  PRIMARY KEY (event_id, position)
);

CREATE TABLE ticket_orders (
  id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  event_id    BIGINT        NOT NULL REFERENCES events(id) ON DELETE RESTRICT,
  full_name   VARCHAR(120)  NOT NULL,
  email       VARCHAR(150)  NOT NULL,
  phone       VARCHAR(20)   NOT NULL,
  quantity    SMALLINT      NOT NULL CHECK (quantity BETWEEN 1 AND 10),
  total_price NUMERIC(12,2) NOT NULL CHECK (total_price >= 0),
  notes       TEXT,
  status      order_status  NOT NULL DEFAULT 'pending',
  created_at  TIMESTAMPTZ   NOT NULL DEFAULT now()
);
CREATE INDEX idx_ticket_orders_event ON ticket_orders (event_id);

CREATE TABLE posts (
  id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  admin_id     BIGINT         REFERENCES admins(id) ON DELETE SET NULL,
  title        VARCHAR(200)   NOT NULL,
  slug         VARCHAR(210)   NOT NULL UNIQUE,
  category     post_category  NOT NULL DEFAULT 'news',
  tag          VARCHAR(30),
  is_featured  BOOLEAN        NOT NULL DEFAULT FALSE,
  excerpt      TEXT,
  body         TEXT           NOT NULL,
  cover_url    VARCHAR(255),
  status       publish_status NOT NULL DEFAULT 'draft',
  published_at TIMESTAMPTZ
);
CREATE INDEX idx_posts_published ON posts (status, published_at DESC);

-- tag: kategori filter galeri (peterpan, noah, konser, studio). caption: alt foto / info file press kit.
CREATE TABLE media (
  id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  post_id    BIGINT       REFERENCES posts(id) ON DELETE SET NULL,
  type       media_type   NOT NULL DEFAULT 'photo',
  tag        VARCHAR(30),
  title      VARCHAR(150) NOT NULL,
  caption    VARCHAR(255),
  file_url   VARCHAR(255) NOT NULL,
  created_at TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE TABLE products (
  id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  name        VARCHAR(150)     NOT NULL,
  slug        VARCHAR(160)     NOT NULL UNIQUE,
  category    product_category NOT NULL,
  badge       VARCHAR(20),
  price       NUMERIC(12,2)    NOT NULL CHECK (price >= 0),
  stock       INTEGER          NOT NULL DEFAULT 0 CHECK (stock >= 0),
  image_url   VARCHAR(255),
  is_limited  BOOLEAN          NOT NULL DEFAULT FALSE,
  description TEXT,
  created_at  TIMESTAMPTZ      NOT NULL DEFAULT now()
);

CREATE TABLE fan_submissions (
  id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  community_id BIGINT          REFERENCES communities(id) ON DELETE SET NULL,
  reviewed_by  BIGINT          REFERENCES admins(id) ON DELETE SET NULL,
  name         VARCHAR(120)    NOT NULL,
  email        VARCHAR(150)    NOT NULL,
  type         submission_type NOT NULL,
  content      TEXT            NOT NULL,
  file_url     VARCHAR(255),
  status       review_status   NOT NULL DEFAULT 'pending',
  created_at   TIMESTAMPTZ     NOT NULL DEFAULT now()
);
CREATE INDEX idx_fan_submissions_status ON fan_submissions (status);

CREATE TABLE subscribers (
  id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  email      VARCHAR(150) NOT NULL UNIQUE,
  is_active  BOOLEAN      NOT NULL DEFAULT TRUE,
  created_at TIMESTAMPTZ  NOT NULL DEFAULT now()
);
