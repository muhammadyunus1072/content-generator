--
-- PostgreSQL database dump
--

\restrict F4v2sY6IjFsLI0VWrpGiNbiH81ahmfoJUdkXxd6VsjcWJqzvHwZmik2oi27PK98

-- Dumped from database version 18.3
-- Dumped by pg_dump version 18.3

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: ai_connections; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ai_connections (
    id bigint NOT NULL,
    user_id bigint,
    name character varying(255) NOT NULL,
    category character varying(255) NOT NULL,
    provider character varying(255) NOT NULL,
    model_id character varying(255) NOT NULL,
    api_key text,
    base_url character varying(255),
    headers json,
    options json,
    is_default boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    last_tested_at timestamp(0) without time zone,
    last_test_status character varying(255),
    last_test_message text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ai_connections_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ai_connections_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ai_connections_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ai_connections_id_seq OWNED BY public.ai_connections.id;


--
-- Name: audio_mixes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.audio_mixes (
    id bigint NOT NULL,
    scene_board_id bigint NOT NULL,
    layer_type character varying(255) NOT NULL,
    audio_path character varying(255) NOT NULL,
    volume numeric(3,2) DEFAULT '1'::numeric NOT NULL,
    start_offset numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    fade_in numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    fade_out numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    loop boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: audio_mixes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.audio_mixes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: audio_mixes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.audio_mixes_id_seq OWNED BY public.audio_mixes.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: character_content_title; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.character_content_title (
    id bigint NOT NULL,
    content_title_id bigint NOT NULL,
    character_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: character_content_title_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.character_content_title_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: character_content_title_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.character_content_title_id_seq OWNED BY public.character_content_title.id;


--
-- Name: character_story; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.character_story (
    id bigint NOT NULL,
    story_id bigint NOT NULL,
    character_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: character_story_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.character_story_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: character_story_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.character_story_id_seq OWNED BY public.character_story.id;


--
-- Name: characters; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.characters (
    id bigint NOT NULL,
    user_id bigint,
    name character varying(255) NOT NULL,
    gender character varying(255),
    age integer,
    appearance text,
    prompt_keywords text,
    avatar_path character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    entity_type character varying(255) DEFAULT 'human'::character varying NOT NULL,
    sheet_image_path character varying(255),
    sheet_data json,
    voice_id character varying(255),
    tts_provider character varying(255) DEFAULT 'edge-tts'::character varying,
    color_theme character varying(255),
    personality text,
    relationships json,
    role character varying(255) DEFAULT 'supporting'::character varying
);


--
-- Name: characters_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.characters_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: characters_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.characters_id_seq OWNED BY public.characters.id;


--
-- Name: clips; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.clips (
    id bigint NOT NULL,
    video_id bigint NOT NULL,
    clip_path character varying(255),
    start_seconds numeric(8,2) NOT NULL,
    end_seconds numeric(8,2) NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    metadata json,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: clips_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.clips_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: clips_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.clips_id_seq OWNED BY public.clips.id;


--
-- Name: content_factory_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.content_factory_templates (
    id bigint NOT NULL,
    user_id bigint,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    niche character varying(255) NOT NULL,
    icon character varying(255) DEFAULT 'sparkles'::character varying NOT NULL,
    description text NOT NULL,
    visual_style character varying(255) NOT NULL,
    color_palette character varying(255),
    lighting_mood character varying(255),
    camera_framing character varying(255),
    genre character varying(255) NOT NULL,
    voice_name character varying(255) NOT NULL,
    tts_rate character varying(255),
    tts_pitch character varying(255),
    aspect_ratio character varying(255) DEFAULT '16:9'::character varying NOT NULL,
    target_platform character varying(255) DEFAULT 'both'::character varying NOT NULL,
    prompt_template text NOT NULL,
    negative_prompt text,
    monetization_tips json,
    example_titles json,
    estimated_rpm_min numeric(8,2),
    estimated_rpm_max numeric(8,2),
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    content_type character varying(255) DEFAULT 'story_video'::character varying NOT NULL,
    default_animation_config json,
    audio_config json,
    voice_mode character varying(255) DEFAULT 'multi_actor'::character varying NOT NULL
);


--
-- Name: content_factory_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.content_factory_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: content_factory_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.content_factory_templates_id_seq OWNED BY public.content_factory_templates.id;


--
-- Name: content_titles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.content_titles (
    id bigint NOT NULL,
    user_id bigint,
    title character varying(255) NOT NULL,
    description text,
    genre character varying(255) DEFAULT 'Action Fantasy'::character varying NOT NULL,
    visual_style character varying(255) DEFAULT 'Dark Fantasy Manhwa'::character varying NOT NULL,
    aspect_ratio character varying(255) DEFAULT '9:16'::character varying NOT NULL,
    voice_name character varying(255) DEFAULT 'id-ID-ArdiNeural'::character varying NOT NULL,
    image_provider character varying(255) DEFAULT 'cloudflare'::character varying NOT NULL,
    tts_provider character varying(255) DEFAULT 'edge-tts'::character varying NOT NULL,
    cover_image_path character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    color_palette character varying(255),
    lighting_mood character varying(255),
    camera_framing character varying(255),
    image_connection_id bigint,
    text_connection_id bigint,
    tts_rate character varying(255),
    tts_pitch character varying(255),
    voice_mode character varying(255) DEFAULT 'multi_actor'::character varying NOT NULL
);


--
-- Name: content_titles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.content_titles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: content_titles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.content_titles_id_seq OWNED BY public.content_titles.id;


--
-- Name: environments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.environments (
    id bigint NOT NULL,
    user_id bigint,
    content_title_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    setting_type character varying(255) DEFAULT 'interior'::character varying NOT NULL,
    time_of_day character varying(255) DEFAULT 'night'::character varying NOT NULL,
    atmosphere character varying(255),
    lighting character varying(255),
    prompt_keywords text NOT NULL,
    description text,
    image_path character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    sheet_image_path character varying(255),
    sheet_data json
);


--
-- Name: environments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.environments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: environments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.environments_id_seq OWNED BY public.environments.id;


--
-- Name: episodes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.episodes (
    id bigint NOT NULL,
    story_id bigint NOT NULL,
    episode_number integer DEFAULT 1 NOT NULL,
    title character varying(255) NOT NULL,
    synopsis text,
    narrative_script text,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    video_path character varying(255),
    audio_path character varying(255),
    subtitle_path character varying(255),
    duration_seconds numeric(8,2),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    webtoon_strip_path character varying(255),
    webtoon_slices json,
    webtoon_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    webtoon_export_pack_path character varying(255),
    manga_pages json,
    manga_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    manga_reading_direction character varying(255) DEFAULT 'rtl'::character varying NOT NULL,
    cbz_package_path character varying(255),
    pdf_package_path character varying(255),
    voice_mode character varying(255)
);


--
-- Name: episodes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.episodes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: episodes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.episodes_id_seq OWNED BY public.episodes.id;


--
-- Name: epub_projects; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.epub_projects (
    id bigint NOT NULL,
    user_id bigint,
    title character varying(255) NOT NULL,
    epub_path character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'uploaded'::character varying NOT NULL,
    aspect_ratio character varying(255) DEFAULT '16:9'::character varying NOT NULL,
    voice_name character varying(255) DEFAULT 'en-US-ChristopherNeural'::character varying NOT NULL,
    output_video_path character varying(255),
    total_scenes integer DEFAULT 0 NOT NULL,
    rendered_scenes integer DEFAULT 0 NOT NULL,
    error_message text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    source_language character varying(255) DEFAULT 'auto'::character varying NOT NULL,
    target_language character varying(255) DEFAULT 'en'::character varying NOT NULL,
    tts_rate character varying(255),
    tts_pitch character varying(255)
);


--
-- Name: epub_projects_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.epub_projects_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: epub_projects_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.epub_projects_id_seq OWNED BY public.epub_projects.id;


--
-- Name: epub_scenes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.epub_scenes (
    id bigint NOT NULL,
    epub_project_id bigint NOT NULL,
    sequence_order integer DEFAULT 0 NOT NULL,
    image_path character varying(255),
    script_text text NOT NULL,
    audio_path character varying(255),
    audio_duration numeric(8,2),
    video_path character varying(255),
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    error_message text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    original_script_text text,
    original_image_path character varying(255),
    subtitle_path character varying(255)
);


--
-- Name: epub_scenes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.epub_scenes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: epub_scenes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.epub_scenes_id_seq OWNED BY public.epub_scenes.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection character varying(255) NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: props; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.props (
    id bigint NOT NULL,
    user_id bigint,
    content_title_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    category character varying(255) DEFAULT 'artifact'::character varying NOT NULL,
    description text,
    prompt_keywords text NOT NULL,
    image_path character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    sheet_image_path character varying(255),
    sheet_data json
);


--
-- Name: props_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.props_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: props_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.props_id_seq OWNED BY public.props.id;


--
-- Name: scene_boards; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.scene_boards (
    id bigint NOT NULL,
    episode_id bigint NOT NULL,
    scene_id bigint NOT NULL,
    board_number integer DEFAULT 1 NOT NULL,
    sequence_order integer DEFAULT 0 NOT NULL,
    narration_chunk text NOT NULL,
    view_type character varying(255) DEFAULT 'wide_shot'::character varying NOT NULL,
    camera_movement character varying(255) DEFAULT 'zoom_in'::character varying NOT NULL,
    visual_prompt text NOT NULL,
    image_path character varying(255),
    audio_path character varying(255),
    duration_seconds numeric(8,2) DEFAULT '4'::numeric NOT NULL,
    start_seconds numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    end_seconds numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    subtitle_path character varying(255),
    environment_id bigint,
    characters_present json,
    props_present json,
    animation_effect character varying(255) DEFAULT 'ken_burns'::character varying NOT NULL,
    transition_type character varying(255) DEFAULT 'crossfade'::character varying NOT NULL,
    speech_bubbles json,
    bubble_style character varying(255) DEFAULT 'manga'::character varying,
    sfx_tags json,
    mood_tag character varying(255) DEFAULT 'action'::character varying,
    parallax_layers json,
    has_particles boolean DEFAULT false NOT NULL,
    particle_type character varying(255),
    speech_bubble_image_path character varying(255),
    sfx_typography json,
    gutter_spacing integer DEFAULT 80 NOT NULL,
    page_layout_hint character varying(255) DEFAULT 'auto'::character varying NOT NULL,
    camera_keyframes json,
    dialogue_audio_timeline json
);


--
-- Name: scene_boards_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.scene_boards_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: scene_boards_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.scene_boards_id_seq OWNED BY public.scene_boards.id;


--
-- Name: scenes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.scenes (
    id bigint NOT NULL,
    episode_id bigint NOT NULL,
    scene_number integer DEFAULT 1 NOT NULL,
    title character varying(255),
    setting character varying(255),
    mood character varying(255),
    narration_text text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: scenes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.scenes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: scenes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.scenes_id_seq OWNED BY public.scenes.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: stories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.stories (
    id bigint NOT NULL,
    user_id bigint,
    title character varying(255) NOT NULL,
    premise text NOT NULL,
    genre character varying(255) DEFAULT 'Action Fantasy'::character varying NOT NULL,
    visual_style character varying(255) DEFAULT 'Dark Fantasy Manhwa'::character varying NOT NULL,
    aspect_ratio character varying(255) DEFAULT '16:9'::character varying NOT NULL,
    voice_name character varying(255) DEFAULT 'en-US-ChristopherNeural'::character varying NOT NULL,
    language character varying(255) DEFAULT 'en'::character varying NOT NULL,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    settings json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    image_provider character varying(255) DEFAULT 'cloudflare'::character varying NOT NULL,
    tts_provider character varying(255) DEFAULT 'edge-tts'::character varying NOT NULL,
    content_title_id bigint,
    tts_rate character varying(255),
    tts_pitch character varying(255)
);


--
-- Name: stories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stories_id_seq OWNED BY public.stories.id;


--
-- Name: transcriptions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.transcriptions (
    id bigint NOT NULL,
    video_id bigint NOT NULL,
    transcript_text text,
    speaker_segments json,
    timeline json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: transcriptions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.transcriptions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: transcriptions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.transcriptions_id_seq OWNED BY public.transcriptions.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: videos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.videos (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    title character varying(255),
    original_filename character varying(255) NOT NULL,
    video_path character varying(255) NOT NULL,
    audio_path character varying(255),
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    error_message text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: videos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.videos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: videos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.videos_id_seq OWNED BY public.videos.id;


--
-- Name: ai_connections id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_connections ALTER COLUMN id SET DEFAULT nextval('public.ai_connections_id_seq'::regclass);


--
-- Name: audio_mixes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audio_mixes ALTER COLUMN id SET DEFAULT nextval('public.audio_mixes_id_seq'::regclass);


--
-- Name: character_content_title id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.character_content_title ALTER COLUMN id SET DEFAULT nextval('public.character_content_title_id_seq'::regclass);


--
-- Name: character_story id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.character_story ALTER COLUMN id SET DEFAULT nextval('public.character_story_id_seq'::regclass);


--
-- Name: characters id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.characters ALTER COLUMN id SET DEFAULT nextval('public.characters_id_seq'::regclass);


--
-- Name: clips id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clips ALTER COLUMN id SET DEFAULT nextval('public.clips_id_seq'::regclass);


--
-- Name: content_factory_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.content_factory_templates ALTER COLUMN id SET DEFAULT nextval('public.content_factory_templates_id_seq'::regclass);


--
-- Name: content_titles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.content_titles ALTER COLUMN id SET DEFAULT nextval('public.content_titles_id_seq'::regclass);


--
-- Name: environments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.environments ALTER COLUMN id SET DEFAULT nextval('public.environments_id_seq'::regclass);


--
-- Name: episodes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.episodes ALTER COLUMN id SET DEFAULT nextval('public.episodes_id_seq'::regclass);


--
-- Name: epub_projects id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.epub_projects ALTER COLUMN id SET DEFAULT nextval('public.epub_projects_id_seq'::regclass);


--
-- Name: epub_scenes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.epub_scenes ALTER COLUMN id SET DEFAULT nextval('public.epub_scenes_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: props id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.props ALTER COLUMN id SET DEFAULT nextval('public.props_id_seq'::regclass);


--
-- Name: scene_boards id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scene_boards ALTER COLUMN id SET DEFAULT nextval('public.scene_boards_id_seq'::regclass);


--
-- Name: scenes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scenes ALTER COLUMN id SET DEFAULT nextval('public.scenes_id_seq'::regclass);


--
-- Name: stories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stories ALTER COLUMN id SET DEFAULT nextval('public.stories_id_seq'::regclass);


--
-- Name: transcriptions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transcriptions ALTER COLUMN id SET DEFAULT nextval('public.transcriptions_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: videos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.videos ALTER COLUMN id SET DEFAULT nextval('public.videos_id_seq'::regclass);


--
-- Name: ai_connections ai_connections_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_connections
    ADD CONSTRAINT ai_connections_pkey PRIMARY KEY (id);


--
-- Name: audio_mixes audio_mixes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audio_mixes
    ADD CONSTRAINT audio_mixes_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: character_content_title character_content_title_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.character_content_title
    ADD CONSTRAINT character_content_title_pkey PRIMARY KEY (id);


--
-- Name: character_story character_story_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.character_story
    ADD CONSTRAINT character_story_pkey PRIMARY KEY (id);


--
-- Name: characters characters_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.characters
    ADD CONSTRAINT characters_pkey PRIMARY KEY (id);


--
-- Name: clips clips_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clips
    ADD CONSTRAINT clips_pkey PRIMARY KEY (id);


--
-- Name: content_factory_templates content_factory_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.content_factory_templates
    ADD CONSTRAINT content_factory_templates_pkey PRIMARY KEY (id);


--
-- Name: content_factory_templates content_factory_templates_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.content_factory_templates
    ADD CONSTRAINT content_factory_templates_slug_unique UNIQUE (slug);


--
-- Name: content_titles content_titles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.content_titles
    ADD CONSTRAINT content_titles_pkey PRIMARY KEY (id);


--
-- Name: environments environments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.environments
    ADD CONSTRAINT environments_pkey PRIMARY KEY (id);


--
-- Name: episodes episodes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.episodes
    ADD CONSTRAINT episodes_pkey PRIMARY KEY (id);


--
-- Name: epub_projects epub_projects_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.epub_projects
    ADD CONSTRAINT epub_projects_pkey PRIMARY KEY (id);


--
-- Name: epub_scenes epub_scenes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.epub_scenes
    ADD CONSTRAINT epub_scenes_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: props props_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.props
    ADD CONSTRAINT props_pkey PRIMARY KEY (id);


--
-- Name: scene_boards scene_boards_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scene_boards
    ADD CONSTRAINT scene_boards_pkey PRIMARY KEY (id);


--
-- Name: scenes scenes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scenes
    ADD CONSTRAINT scenes_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: stories stories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stories
    ADD CONSTRAINT stories_pkey PRIMARY KEY (id);


--
-- Name: transcriptions transcriptions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transcriptions
    ADD CONSTRAINT transcriptions_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: videos videos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.videos
    ADD CONSTRAINT videos_pkey PRIMARY KEY (id);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: failed_jobs_connection_queue_failed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON public.failed_jobs USING btree (connection, queue, failed_at);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: ai_connections ai_connections_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_connections
    ADD CONSTRAINT ai_connections_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: audio_mixes audio_mixes_scene_board_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audio_mixes
    ADD CONSTRAINT audio_mixes_scene_board_id_foreign FOREIGN KEY (scene_board_id) REFERENCES public.scene_boards(id) ON DELETE CASCADE;


--
-- Name: character_content_title character_content_title_character_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.character_content_title
    ADD CONSTRAINT character_content_title_character_id_foreign FOREIGN KEY (character_id) REFERENCES public.characters(id) ON DELETE CASCADE;


--
-- Name: character_content_title character_content_title_content_title_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.character_content_title
    ADD CONSTRAINT character_content_title_content_title_id_foreign FOREIGN KEY (content_title_id) REFERENCES public.content_titles(id) ON DELETE CASCADE;


--
-- Name: character_story character_story_character_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.character_story
    ADD CONSTRAINT character_story_character_id_foreign FOREIGN KEY (character_id) REFERENCES public.characters(id) ON DELETE CASCADE;


--
-- Name: character_story character_story_story_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.character_story
    ADD CONSTRAINT character_story_story_id_foreign FOREIGN KEY (story_id) REFERENCES public.stories(id) ON DELETE CASCADE;


--
-- Name: characters characters_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.characters
    ADD CONSTRAINT characters_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: clips clips_video_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clips
    ADD CONSTRAINT clips_video_id_foreign FOREIGN KEY (video_id) REFERENCES public.videos(id) ON DELETE CASCADE;


--
-- Name: content_factory_templates content_factory_templates_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.content_factory_templates
    ADD CONSTRAINT content_factory_templates_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: content_titles content_titles_image_connection_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.content_titles
    ADD CONSTRAINT content_titles_image_connection_id_foreign FOREIGN KEY (image_connection_id) REFERENCES public.ai_connections(id) ON DELETE SET NULL;


--
-- Name: content_titles content_titles_text_connection_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.content_titles
    ADD CONSTRAINT content_titles_text_connection_id_foreign FOREIGN KEY (text_connection_id) REFERENCES public.ai_connections(id) ON DELETE SET NULL;


--
-- Name: content_titles content_titles_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.content_titles
    ADD CONSTRAINT content_titles_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: environments environments_content_title_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.environments
    ADD CONSTRAINT environments_content_title_id_foreign FOREIGN KEY (content_title_id) REFERENCES public.content_titles(id) ON DELETE CASCADE;


--
-- Name: environments environments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.environments
    ADD CONSTRAINT environments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: episodes episodes_story_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.episodes
    ADD CONSTRAINT episodes_story_id_foreign FOREIGN KEY (story_id) REFERENCES public.stories(id) ON DELETE CASCADE;


--
-- Name: epub_projects epub_projects_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.epub_projects
    ADD CONSTRAINT epub_projects_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: epub_scenes epub_scenes_epub_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.epub_scenes
    ADD CONSTRAINT epub_scenes_epub_project_id_foreign FOREIGN KEY (epub_project_id) REFERENCES public.epub_projects(id) ON DELETE CASCADE;


--
-- Name: props props_content_title_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.props
    ADD CONSTRAINT props_content_title_id_foreign FOREIGN KEY (content_title_id) REFERENCES public.content_titles(id) ON DELETE CASCADE;


--
-- Name: props props_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.props
    ADD CONSTRAINT props_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: scene_boards scene_boards_environment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scene_boards
    ADD CONSTRAINT scene_boards_environment_id_foreign FOREIGN KEY (environment_id) REFERENCES public.environments(id) ON DELETE SET NULL;


--
-- Name: scene_boards scene_boards_episode_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scene_boards
    ADD CONSTRAINT scene_boards_episode_id_foreign FOREIGN KEY (episode_id) REFERENCES public.episodes(id) ON DELETE CASCADE;


--
-- Name: scene_boards scene_boards_scene_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scene_boards
    ADD CONSTRAINT scene_boards_scene_id_foreign FOREIGN KEY (scene_id) REFERENCES public.scenes(id) ON DELETE CASCADE;


--
-- Name: scenes scenes_episode_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scenes
    ADD CONSTRAINT scenes_episode_id_foreign FOREIGN KEY (episode_id) REFERENCES public.episodes(id) ON DELETE CASCADE;


--
-- Name: stories stories_content_title_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stories
    ADD CONSTRAINT stories_content_title_id_foreign FOREIGN KEY (content_title_id) REFERENCES public.content_titles(id) ON DELETE SET NULL;


--
-- Name: stories stories_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stories
    ADD CONSTRAINT stories_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: transcriptions transcriptions_video_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transcriptions
    ADD CONSTRAINT transcriptions_video_id_foreign FOREIGN KEY (video_id) REFERENCES public.videos(id) ON DELETE CASCADE;


--
-- Name: videos videos_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.videos
    ADD CONSTRAINT videos_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict F4v2sY6IjFsLI0VWrpGiNbiH81ahmfoJUdkXxd6VsjcWJqzvHwZmik2oi27PK98

--
-- PostgreSQL database dump
--

\restrict JG5wZP5D7iwG31mTr48D1fXoNli3WK12Ud2vUuXZH2YBSNVOcRJray68ZY2rrxI

-- Dumped from database version 18.3
-- Dumped by pg_dump version 18.3

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_08_22_202000_create_videos_table	1
5	2026_08_22_202100_create_transcriptions_table	1
6	2026_08_22_202200_create_clips_table	1
7	2026_09_01_023214_create_story_generator_tables	2
8	2026_09_02_000001_create_characters_and_story_enhancements	3
9	2026_09_02_000002_create_content_titles_table	4
10	2026_09_02_000003_create_world_bible_tables	5
11	2026_09_03_000001_create_ai_connections_table	6
12	2026_09_03_000002_add_reference_sheet_fields_to_world_bible_tables	7
13	2026_09_04_000001_create_epub_video_tables	8
14	2026_09_05_000001_add_translation_fields_to_epub_tables	9
15	2026_09_05_000002_add_original_image_path_to_epub_scenes	10
16	2026_09_07_000001_add_model_connection_ids_to_content_titles_table	11
17	2026_09_07_000002_create_content_factory_templates_table	12
18	2026_09_07_000003_add_tts_rate_and_pitch_fields	13
19	2026_09_07_000004_add_character_bible_fields	13
20	2026_09_07_000005_add_comic_panel_fields_to_scene_boards	13
21	2026_09_07_000006_create_audio_mixes_table	13
22	2026_09_07_000007_add_content_type_to_content_factory_templates	13
23	2026_09_07_000008_add_webtoon_fields_to_episodes_and_boards	14
24	2026_09_07_000009_add_manga_spread_fields_to_episodes_and_boards	15
25	2026_09_07_000010_add_camera_keyframes_to_scene_boards	16
26	2026_09_07_000011_add_dialogue_timeline_to_scene_boards	17
27	2026_09_07_000012_add_voice_mode_to_titles_and_episodes	18
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 27, true);


--
-- PostgreSQL database dump complete
--

\unrestrict JG5wZP5D7iwG31mTr48D1fXoNli3WK12Ud2vUuXZH2YBSNVOcRJray68ZY2rrxI

