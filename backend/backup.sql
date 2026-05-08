--
-- PostgreSQL database dump
--

\restrict S10Exn8NQYGfnp7vExvNSe1QVb4yYqNYobLJDSOomiPqSL5pc8T3rhb0t1QQKiQ

-- Dumped from database version 15.17
-- Dumped by pg_dump version 15.17

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
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
-- Name: agencies; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.agencies (
    id uuid NOT NULL,
    owner_id uuid NOT NULL,
    city_id uuid NOT NULL,
    logo_url character varying(255),
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    address character varying(255) NOT NULL,
    phone character varying(255) NOT NULL,
    email character varying(255),
    description text,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    balance numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    avg_rating double precision DEFAULT '0'::double precision NOT NULL,
    total_reviews integer DEFAULT 0 NOT NULL,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    pending_changes json,
    CONSTRAINT agencies_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


ALTER TABLE public.agencies OWNER TO postgres;

--
-- Name: cache; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO postgres;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO postgres;

--
-- Name: car_images; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.car_images (
    id uuid NOT NULL,
    car_id uuid NOT NULL,
    image_url character varying(255) NOT NULL,
    is_primary boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.car_images OWNER TO postgres;

--
-- Name: car_maintenance_periods; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.car_maintenance_periods (
    id uuid NOT NULL,
    car_id uuid NOT NULL,
    start_date date NOT NULL,
    end_date date NOT NULL,
    reason character varying(255),
    status character varying(255) DEFAULT 'scheduled'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT car_maintenance_periods_status_check CHECK (((status)::text = ANY ((ARRAY['scheduled'::character varying, 'completed'::character varying, 'cancelled'::character varying])::text[])))
);


ALTER TABLE public.car_maintenance_periods OWNER TO postgres;

--
-- Name: cars; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cars (
    id uuid NOT NULL,
    agency_id uuid NOT NULL,
    city_id uuid NOT NULL,
    brand character varying(255) NOT NULL,
    model character varying(255) NOT NULL,
    year integer NOT NULL,
    color character varying(255) NOT NULL,
    plate_number character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    transmission character varying(255) NOT NULL,
    seats integer NOT NULL,
    price_per_day numeric(10,2) NOT NULL,
    description text,
    status character varying(255) DEFAULT 'available'::character varying NOT NULL,
    avg_rating double precision DEFAULT '0'::double precision NOT NULL,
    total_reviews integer DEFAULT 0 NOT NULL,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT cars_status_check CHECK (((status)::text = ANY ((ARRAY['available'::character varying, 'rented'::character varying, 'maintenance'::character varying])::text[]))),
    CONSTRAINT cars_transmission_check CHECK (((transmission)::text = ANY ((ARRAY['automatic'::character varying, 'manual'::character varying])::text[]))),
    CONSTRAINT cars_type_check CHECK (((type)::text = ANY ((ARRAY['sedan'::character varying, 'suv'::character varying, 'van'::character varying])::text[])))
);


ALTER TABLE public.cars OWNER TO postgres;

--
-- Name: cities; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cities (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    region character varying(255),
    country character varying(255) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.cities OWNER TO postgres;

--
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.migrations_id_seq OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: payments; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payments (
    id uuid NOT NULL,
    reservation_id uuid,
    agency_id uuid NOT NULL,
    amount numeric(10,2) NOT NULL,
    type character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'completed'::character varying NOT NULL,
    balance_before numeric(10,2) NOT NULL,
    balance_after numeric(10,2) NOT NULL,
    reference character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT payments_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'completed'::character varying, 'failed'::character varying])::text[]))),
    CONSTRAINT payments_type_check CHECK (((type)::text = ANY ((ARRAY['commission'::character varying, 'top_up'::character varying, 'refund'::character varying])::text[])))
);


ALTER TABLE public.payments OWNER TO postgres;

--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id uuid NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.personal_access_tokens OWNER TO postgres;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.personal_access_tokens_id_seq OWNER TO postgres;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: reservations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.reservations (
    id uuid NOT NULL,
    client_id uuid NOT NULL,
    car_id uuid NOT NULL,
    agency_id uuid NOT NULL,
    start_date timestamp(0) without time zone NOT NULL,
    end_date timestamp(0) without time zone NOT NULL,
    price_per_day_snapshot numeric(10,2) NOT NULL,
    total_amount numeric(10,2) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    cancellation_reason text,
    cancelled_by character varying(255),
    confirmed_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    cancelled_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    picked_up_at timestamp(0) without time zone,
    CONSTRAINT reservations_cancelled_by_check CHECK (((cancelled_by)::text = ANY ((ARRAY['client'::character varying, 'agency'::character varying])::text[]))),
    CONSTRAINT reservations_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'confirmed'::character varying, 'cancelled'::character varying, 'completed'::character varying])::text[])))
);


ALTER TABLE public.reservations OWNER TO postgres;

--
-- Name: reviews; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.reviews (
    id uuid NOT NULL,
    reservation_id uuid NOT NULL,
    car_rating integer NOT NULL,
    agency_rating integer NOT NULL,
    comment text,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.reviews OWNER TO postgres;

--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id uuid NOT NULL,
    first_name character varying(255) NOT NULL,
    last_name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    phone character varying(255),
    avatar_url character varying(255),
    role character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    cancel_count_today integer DEFAULT 0 NOT NULL,
    blocked_until timestamp(0) without time zone,
    email_verified_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT users_role_check CHECK (((role)::text = ANY ((ARRAY['admin'::character varying, 'client'::character varying, 'agency_owner'::character varying])::text[]))),
    CONSTRAINT users_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'blocked'::character varying, 'pending'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Data for Name: agencies; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.agencies (id, owner_id, city_id, logo_url, name, slug, address, phone, email, description, status, balance, avg_rating, total_reviews, deleted_at, created_at, updated_at, pending_changes) FROM stdin;
a16694be-2a98-48e0-8b74-432f139c7674	ff854926-4a6b-4a8c-b015-2f199737ad22	fb276cf4-df58-4f03-9d07-2a43464b1cb3	\N	Premium Cars	premium-cars	Tangier Center	0600000100	premium@test.com	Approved agency with enough balance for confirmation tests.	approved	1000.00	0	0	\N	2026-05-07 19:36:55	2026-05-07 19:36:55	\N
c4fd49e4-c2b7-4dee-a927-38bbcb2f5a89	f9a7468b-93c9-4541-9203-67563e372dda	08fe6551-6a1d-4ce3-92f3-ae90ae1228f2	\N	Casa Drive	casa-drive	Maarif, Casablanca	0600000200	casa@test.com	Approved agency with lower balance for payment edge cases.	approved	75.00	0	0	\N	2026-05-07 19:36:55	2026-05-07 19:36:55	\N
590a8e04-3470-440f-963b-300655002d0a	f952f177-e76b-4dbc-92cc-a15f0b569f9d	4507e672-9491-4656-a31d-e29b8bba2e31	\N	Rabat Pending Rentals	rabat-pending-rentals	Agdal, Rabat	0600000300	pending-agency@test.com	Pending agency for admin approval screens.	pending	0.00	0	0	\N	2026-05-07 19:36:55	2026-05-07 19:36:55	\N
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: car_images; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.car_images (id, car_id, image_url, is_primary, created_at, updated_at) FROM stdin;
f950c3dc-4c55-4883-9f40-71dcdc7a760e	ffbc5ac5-0d7e-4191-9833-3a37188e27a8	https://picsum.photos/seed/123-A-45-primary/600/400	t	2026-05-07 19:36:55	2026-05-07 19:36:55
bd74b806-ff07-4879-8002-d23ab1f43b17	ffbc5ac5-0d7e-4191-9833-3a37188e27a8	https://picsum.photos/seed/123-A-45-side/600/400	f	2026-05-07 19:36:55	2026-05-07 19:36:55
a5bd82d0-3d93-43eb-9403-1d1f67bbae1c	ffbc5ac5-0d7e-4191-9833-3a37188e27a8	https://picsum.photos/seed/123-A-45-inside/600/400	f	2026-05-07 19:36:55	2026-05-07 19:36:55
0d094718-a235-4391-a29e-2706ea0fccf6	48b0be6f-3d1f-4f24-aef2-89809c5c1122	https://picsum.photos/seed/789-C-89-primary/600/400	t	2026-05-07 19:36:55	2026-05-07 19:36:55
7c93865c-da57-45f0-b914-7f9a32c1bce3	48b0be6f-3d1f-4f24-aef2-89809c5c1122	https://picsum.photos/seed/789-C-89-side/600/400	f	2026-05-07 19:36:55	2026-05-07 19:36:55
5bcff82f-d0e3-4832-b542-fe91bea12c89	48b0be6f-3d1f-4f24-aef2-89809c5c1122	https://picsum.photos/seed/789-C-89-inside/600/400	f	2026-05-07 19:36:55	2026-05-07 19:36:55
6ffc545e-ab26-4f35-823b-868ffafad755	7a214d77-44b9-4f78-815f-2914776668c4	https://picsum.photos/seed/222-V-88-primary/600/400	t	2026-05-07 19:36:55	2026-05-07 19:36:55
0ecb54cd-3bb3-4cce-b16a-f5397c8854f1	7a214d77-44b9-4f78-815f-2914776668c4	https://picsum.photos/seed/222-V-88-side/600/400	f	2026-05-07 19:36:55	2026-05-07 19:36:55
43f0cf1c-d069-4031-9b29-cb303c1e19a0	7a214d77-44b9-4f78-815f-2914776668c4	https://picsum.photos/seed/222-V-88-inside/600/400	f	2026-05-07 19:36:55	2026-05-07 19:36:55
9a707016-24a3-41db-8c49-3f36a26d72c8	e2a6f939-450e-4a13-bbbc-c93c5eb2046a	https://picsum.photos/seed/456-B-67-primary/600/400	t	2026-05-07 19:36:55	2026-05-07 19:36:55
7e89e482-c805-4c62-a1a0-86e6ab9ace24	e2a6f939-450e-4a13-bbbc-c93c5eb2046a	https://picsum.photos/seed/456-B-67-side/600/400	f	2026-05-07 19:36:55	2026-05-07 19:36:55
04e42515-f628-4f2a-af1a-c4b46cb4536e	e2a6f939-450e-4a13-bbbc-c93c5eb2046a	https://picsum.photos/seed/456-B-67-inside/600/400	f	2026-05-07 19:36:55	2026-05-07 19:36:55
6b8de3b4-0286-429a-8760-49c660cb58f9	7d74e8a1-a4bb-4eb5-a1d6-e2b86d0056cb	https://picsum.photos/seed/999-K-10-primary/600/400	t	2026-05-07 19:36:55	2026-05-07 19:36:55
406dfad3-557c-4246-85ca-24e4ea1830a0	7d74e8a1-a4bb-4eb5-a1d6-e2b86d0056cb	https://picsum.photos/seed/999-K-10-side/600/400	f	2026-05-07 19:36:55	2026-05-07 19:36:55
6621ad7f-ed2e-43f7-afbe-df22ed8082bd	7d74e8a1-a4bb-4eb5-a1d6-e2b86d0056cb	https://picsum.photos/seed/999-K-10-inside/600/400	f	2026-05-07 19:36:55	2026-05-07 19:36:55
\.


--
-- Data for Name: car_maintenance_periods; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.car_maintenance_periods (id, car_id, start_date, end_date, reason, status, created_at, updated_at) FROM stdin;
9f500b45-11da-44cf-8ae5-810857356881	7a214d77-44b9-4f78-815f-2914776668c4	2026-05-11	2026-05-13	Scheduled engine inspection	scheduled	2026-05-07 19:36:55	2026-05-07 19:36:55
12a455f1-62cc-46e8-b123-d7deaa48e57f	7d74e8a1-a4bb-4eb5-a1d6-e2b86d0056cb	2026-05-25	2026-05-27	Scheduled tire replacement	scheduled	2026-05-07 19:36:55	2026-05-07 19:36:55
\.


--
-- Data for Name: cars; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cars (id, agency_id, city_id, brand, model, year, color, plate_number, type, transmission, seats, price_per_day, description, status, avg_rating, total_reviews, deleted_at, created_at, updated_at) FROM stdin;
ffbc5ac5-0d7e-4191-9833-3a37188e27a8	a16694be-2a98-48e0-8b74-432f139c7674	fb276cf4-df58-4f03-9d07-2a43464b1cb3	Toyota	Yaris	2022	white	123-A-45	sedan	manual	5	200.00	Toyota Yaris test car	available	0	0	\N	2026-05-07 19:36:55	2026-05-07 19:36:55
48b0be6f-3d1f-4f24-aef2-89809c5c1122	a16694be-2a98-48e0-8b74-432f139c7674	fb276cf4-df58-4f03-9d07-2a43464b1cb3	Dacia	Duster	2023	gray	789-C-89	suv	manual	5	300.00	Dacia Duster test car	available	0	0	\N	2026-05-07 19:36:55	2026-05-07 19:36:55
7a214d77-44b9-4f78-815f-2914776668c4	a16694be-2a98-48e0-8b74-432f139c7674	4507e672-9491-4656-a31d-e29b8bba2e31	Renault	Trafic	2020	silver	222-V-88	van	manual	9	450.00	Renault Trafic test car	maintenance	0	0	\N	2026-05-07 19:36:55	2026-05-07 19:36:55
e2a6f939-450e-4a13-bbbc-c93c5eb2046a	c4fd49e4-c2b7-4dee-a927-38bbcb2f5a89	08fe6551-6a1d-4ce3-92f3-ae90ae1228f2	Hyundai	i10	2021	black	456-B-67	sedan	automatic	5	180.00	Hyundai i10 test car	available	0	0	\N	2026-05-07 19:36:55	2026-05-07 19:36:55
7d74e8a1-a4bb-4eb5-a1d6-e2b86d0056cb	c4fd49e4-c2b7-4dee-a927-38bbcb2f5a89	08fe6551-6a1d-4ce3-92f3-ae90ae1228f2	Kia	Sportage	2024	blue	999-K-10	suv	automatic	5	380.00	Kia Sportage test car	rented	0	0	\N	2026-05-07 19:36:55	2026-05-07 19:36:55
\.


--
-- Data for Name: cities; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cities (id, name, region, country, is_active, created_at, updated_at) FROM stdin;
fb276cf4-df58-4f03-9d07-2a43464b1cb3	Tangier	Tanger-Tetouan-Al Hoceima	Morocco	t	2026-05-07 19:36:55	2026-05-07 19:36:55
08fe6551-6a1d-4ce3-92f3-ae90ae1228f2	Casablanca	Casablanca-Settat	Morocco	t	2026-05-07 19:36:55	2026-05-07 19:36:55
4507e672-9491-4656-a31d-e29b8bba2e31	Rabat	Rabat-Sale-Kenitra	Morocco	t	2026-05-07 19:36:55	2026-05-07 19:36:55
beeba854-c179-42f2-898c-150872c707de	Marrakech	Marrakech-Safi	Morocco	t	2026-05-07 19:36:55	2026-05-07 19:36:55
7285265f-3bf9-4110-b16c-07df671432dd	Fes	Fes-Meknes	Morocco	f	2026-05-07 19:36:55	2026-05-07 19:36:55
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	2026_04_27_172117_create_users_table	1
2	2026_04_27_180325_create_cities_table	1
3	2026_04_27_180647_create_agencies_table	1
4	2026_04_27_181403_create_cars_table	1
5	2026_04_27_182002_create_reservations_table	1
6	2026_04_27_182438_create_payments_table	1
7	2026_04_27_182639_create_reviews_table	1
8	2026_04_29_182617_create_car_images_table	1
9	2026_04_29_184426_create_personal_access_tokens_table	1
10	2026_05_04_000001_create_car_maintenance_periods_table	1
11	2026_05_05_000001_add_picked_up_at_to_reservations_table	1
12	2026_05_05_100000_add_pending_changes_to_agencies_table	1
13	2026_05_05_213949_change_reservation_dates_to_datetime	1
14	2026_05_06_115218_create_cache_table	1
\.


--
-- Data for Name: payments; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payments (id, reservation_id, agency_id, amount, type, status, balance_before, balance_after, reference, created_at, updated_at) FROM stdin;
daff1707-0171-4f5d-a7e9-4b5b1d4d08e7	5e02092a-05d3-4098-8e99-490c1044928d	c4fd49e4-c2b7-4dee-a927-38bbcb2f5a89	54.00	commission	completed	75.00	21.00	COM-5e02092a-05d3-4098-8e99-490c1044928d	2026-05-07 19:36:55	2026-05-07 19:36:55
d68eb4e6-b254-4d57-9743-b3a38bdfb1de	181cc6b1-03f4-4ba6-a4e8-e27d6e18ebbd	a16694be-2a98-48e0-8b74-432f139c7674	60.00	commission	completed	1000.00	940.00	COM-181cc6b1-03f4-4ba6-a4e8-e27d6e18ebbd	2026-05-07 19:36:55	2026-05-07 19:36:55
99c9451e-9e5a-4d83-beb1-dcd97ab9a380	181cc6b1-03f4-4ba6-a4e8-e27d6e18ebbd	a16694be-2a98-48e0-8b74-432f139c7674	60.00	refund	completed	1000.00	1060.00	REF-181cc6b1-03f4-4ba6-a4e8-e27d6e18ebbd	2026-05-07 19:36:55	2026-05-07 19:36:55
1b40f6cd-84fa-41fe-a7f0-4043e22160a1	3236c50c-5973-49f7-a3a8-35483a634b66	c4fd49e4-c2b7-4dee-a927-38bbcb2f5a89	114.00	commission	completed	75.00	-39.00	COM-3236c50c-5973-49f7-a3a8-35483a634b66	2026-05-07 19:36:55	2026-05-07 19:36:55
ae810bda-bae3-4a5b-baa6-17a23a3afa17	\N	a16694be-2a98-48e0-8b74-432f139c7674	250.00	top_up	completed	1000.00	1250.00	TOP-premium-cars	2026-05-07 19:36:55	2026-05-07 19:36:55
26a3ceee-2857-4305-aa09-f7745c55d91e	\N	c4fd49e4-c2b7-4dee-a927-38bbcb2f5a89	250.00	top_up	completed	75.00	325.00	TOP-casa-drive	2026-05-07 19:36:55	2026-05-07 19:36:55
145a6514-e8b1-419f-8cde-90daa0667a5c	\N	c4fd49e4-c2b7-4dee-a927-38bbcb2f5a89	500.00	top_up	failed	75.00	75.00	FAIL-casa-drive	2026-05-07 19:36:55	2026-05-07 19:36:55
\.


--
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.personal_access_tokens (id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: reservations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.reservations (id, client_id, car_id, agency_id, start_date, end_date, price_per_day_snapshot, total_amount, status, cancellation_reason, cancelled_by, confirmed_at, expires_at, cancelled_at, completed_at, created_at, updated_at, picked_up_at) FROM stdin;
9dcdfbcd-fc1b-4dad-9c4e-06dd33c17f52	80e634ef-ff47-43cc-b0c0-b1d863554d24	ffbc5ac5-0d7e-4191-9833-3a37188e27a8	a16694be-2a98-48e0-8b74-432f139c7674	2026-05-10 00:00:00	2026-05-12 00:00:00	200.00	400.00	pending	\N	\N	\N	2026-05-07 20:36:55	\N	\N	2026-05-07 19:36:55	2026-05-07 19:36:55	\N
5e02092a-05d3-4098-8e99-490c1044928d	9a2d847a-4315-4f1b-81f3-c079d9183abd	e2a6f939-450e-4a13-bbbc-c93c5eb2046a	c4fd49e4-c2b7-4dee-a927-38bbcb2f5a89	2026-05-15 00:00:00	2026-05-18 00:00:00	180.00	540.00	confirmed	\N	\N	2026-05-07 18:36:55	\N	\N	\N	2026-05-07 19:36:55	2026-05-07 19:36:55	\N
181cc6b1-03f4-4ba6-a4e8-e27d6e18ebbd	80e634ef-ff47-43cc-b0c0-b1d863554d24	48b0be6f-3d1f-4f24-aef2-89809c5c1122	a16694be-2a98-48e0-8b74-432f139c7674	2026-05-21 00:00:00	2026-05-23 00:00:00	300.00	600.00	cancelled	Seeded cancellation for refund testing.	client	\N	\N	2026-05-07 19:06:55	\N	2026-05-07 19:36:55	2026-05-07 19:36:55	\N
3236c50c-5973-49f7-a3a8-35483a634b66	9a2d847a-4315-4f1b-81f3-c079d9183abd	7d74e8a1-a4bb-4eb5-a1d6-e2b86d0056cb	c4fd49e4-c2b7-4dee-a927-38bbcb2f5a89	2026-04-27 00:00:00	2026-04-30 00:00:00	380.00	1140.00	completed	\N	\N	2026-04-25 19:36:55	\N	\N	2026-04-30 19:36:55	2026-05-07 19:36:55	2026-05-07 19:36:55	\N
\.


--
-- Data for Name: reviews; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.reviews (id, reservation_id, car_rating, agency_rating, comment, deleted_at, created_at, updated_at) FROM stdin;
38254a8f-be20-4daa-863c-94be8fe015c3	3236c50c-5973-49f7-a3a8-35483a634b66	5	4	Seeded review for completed reservation testing.	\N	2026-05-07 19:36:55	2026-05-07 19:36:55
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, first_name, last_name, email, password, phone, avatar_url, role, status, cancel_count_today, blocked_until, email_verified_at, deleted_at, created_at, updated_at) FROM stdin;
c0a9fcad-ad13-4cc0-a61f-7ec687302166	Admin	System	admin@test.com	$2y$12$a9Qlg1H10z/NrfV.GzCQn.z7vEw5FRFX50XK7L4V1kiPL7kFMpmt.	0600000000	\N	admin	active	0	\N	\N	\N	2026-05-07 19:36:52	2026-05-07 19:36:52
80e634ef-ff47-43cc-b0c0-b1d863554d24	Ahmed	Client	client@test.com	$2y$12$MezyAij6m91X.JxIBZZ6iu5gl6v2OqWNIpPhqwv9FgF0PmQSQAU.i	0600000001	\N	client	active	0	\N	\N	\N	2026-05-07 19:36:53	2026-05-07 19:36:53
9a2d847a-4315-4f1b-81f3-c079d9183abd	Sara	Client	sara@test.com	$2y$12$W1FIstz9nOhH9j30pS2LJe.DlajgzsX1cPJTL19sNuXbSyN4B.gbi	0600000002	\N	client	active	0	\N	\N	\N	2026-05-07 19:36:53	2026-05-07 19:36:53
3445f12b-3725-4061-8bee-98847aab8358	Blocked	Client	blocked@test.com	$2y$12$nskLvEuNDj9i8wQS3xfYweyh2YInjBV3FMVDAOEhfrcqm8ebGwJ1O	0600000003	\N	client	active	0	2026-05-08 07:36:52	\N	\N	2026-05-07 19:36:53	2026-05-07 19:36:53
ff854926-4a6b-4a8c-b015-2f199737ad22	Yassine	Agency	agency@test.com	$2y$12$6CgS04hgHeB/KCqlYQGqgO2l4dsMmJljA2a1H5ebtO4U3MfIXquoi	0600000004	\N	agency_owner	active	0	\N	\N	\N	2026-05-07 19:36:54	2026-05-07 19:36:54
f9a7468b-93c9-4541-9203-67563e372dda	Meryem	Agency	agency2@test.com	$2y$12$Vp5sF5GHR6CE/vdUS2cwk.xpi.a6AxHi5.6bgFEw.eh.n7iwfgfPm	0600000005	\N	agency_owner	active	0	\N	\N	\N	2026-05-07 19:36:54	2026-05-07 19:36:54
f952f177-e76b-4dbc-92cc-a15f0b569f9d	Pending	Owner	pending-owner@test.com	$2y$12$c/HX/eNLeS/vYnCSnJl62uaQODfYCLwonI5NO6poQaj9wukgOo3OC	0600000006	\N	agency_owner	pending	0	\N	\N	\N	2026-05-07 19:36:55	2026-05-07 19:36:55
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.migrations_id_seq', 14, true);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 1, false);


--
-- Name: agencies agencies_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agencies
    ADD CONSTRAINT agencies_pkey PRIMARY KEY (id);


--
-- Name: agencies agencies_slug_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agencies
    ADD CONSTRAINT agencies_slug_unique UNIQUE (slug);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: car_images car_images_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.car_images
    ADD CONSTRAINT car_images_pkey PRIMARY KEY (id);


--
-- Name: car_maintenance_periods car_maintenance_periods_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.car_maintenance_periods
    ADD CONSTRAINT car_maintenance_periods_pkey PRIMARY KEY (id);


--
-- Name: cars cars_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cars
    ADD CONSTRAINT cars_pkey PRIMARY KEY (id);


--
-- Name: cars cars_plate_number_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cars
    ADD CONSTRAINT cars_plate_number_unique UNIQUE (plate_number);


--
-- Name: cities cities_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cities
    ADD CONSTRAINT cities_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: reservations reservations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_pkey PRIMARY KEY (id);


--
-- Name: reviews reviews_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_pkey PRIMARY KEY (id);


--
-- Name: reviews reviews_reservation_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_reservation_id_unique UNIQUE (reservation_id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: agencies_city_id_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX agencies_city_id_status_index ON public.agencies USING btree (city_id, status);


--
-- Name: agencies_owner_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX agencies_owner_id_index ON public.agencies USING btree (owner_id);


--
-- Name: car_images_car_id_is_primary_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX car_images_car_id_is_primary_index ON public.car_images USING btree (car_id, is_primary);


--
-- Name: car_maintenance_periods_car_id_status_start_date_end_date_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX car_maintenance_periods_car_id_status_start_date_end_date_index ON public.car_maintenance_periods USING btree (car_id, status, start_date, end_date);


--
-- Name: cars_agency_id_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cars_agency_id_status_index ON public.cars USING btree (agency_id, status);


--
-- Name: cars_city_id_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cars_city_id_status_index ON public.cars USING btree (city_id, status);


--
-- Name: cities_country_is_active_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cities_country_is_active_index ON public.cities USING btree (country, is_active);


--
-- Name: payments_agency_id_created_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX payments_agency_id_created_at_index ON public.payments USING btree (agency_id, created_at);


--
-- Name: payments_reservation_id_type_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX payments_reservation_id_type_index ON public.payments USING btree (reservation_id, type);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: reservations_agency_id_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX reservations_agency_id_status_index ON public.reservations USING btree (agency_id, status);


--
-- Name: reservations_car_id_status_start_date_end_date_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX reservations_car_id_status_start_date_end_date_index ON public.reservations USING btree (car_id, status, start_date, end_date);


--
-- Name: reservations_client_id_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX reservations_client_id_status_index ON public.reservations USING btree (client_id, status);


--
-- Name: users_role_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX users_role_status_index ON public.users USING btree (role, status);


--
-- Name: agencies agencies_city_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agencies
    ADD CONSTRAINT agencies_city_id_foreign FOREIGN KEY (city_id) REFERENCES public.cities(id);


--
-- Name: agencies agencies_owner_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agencies
    ADD CONSTRAINT agencies_owner_id_foreign FOREIGN KEY (owner_id) REFERENCES public.users(id);


--
-- Name: car_images car_images_car_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.car_images
    ADD CONSTRAINT car_images_car_id_foreign FOREIGN KEY (car_id) REFERENCES public.cars(id) ON DELETE CASCADE;


--
-- Name: car_maintenance_periods car_maintenance_periods_car_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.car_maintenance_periods
    ADD CONSTRAINT car_maintenance_periods_car_id_foreign FOREIGN KEY (car_id) REFERENCES public.cars(id) ON DELETE CASCADE;


--
-- Name: cars cars_agency_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cars
    ADD CONSTRAINT cars_agency_id_foreign FOREIGN KEY (agency_id) REFERENCES public.agencies(id);


--
-- Name: cars cars_city_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cars
    ADD CONSTRAINT cars_city_id_foreign FOREIGN KEY (city_id) REFERENCES public.cities(id);


--
-- Name: payments payments_agency_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_agency_id_foreign FOREIGN KEY (agency_id) REFERENCES public.agencies(id);


--
-- Name: payments payments_reservation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_reservation_id_foreign FOREIGN KEY (reservation_id) REFERENCES public.reservations(id);


--
-- Name: reservations reservations_agency_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_agency_id_foreign FOREIGN KEY (agency_id) REFERENCES public.agencies(id);


--
-- Name: reservations reservations_car_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_car_id_foreign FOREIGN KEY (car_id) REFERENCES public.cars(id);


--
-- Name: reservations reservations_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.users(id);


--
-- Name: reviews reviews_reservation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_reservation_id_foreign FOREIGN KEY (reservation_id) REFERENCES public.reservations(id);


--
-- PostgreSQL database dump complete
--

\unrestrict S10Exn8NQYGfnp7vExvNSe1QVb4yYqNYobLJDSOomiPqSL5pc8T3rhb0t1QQKiQ

